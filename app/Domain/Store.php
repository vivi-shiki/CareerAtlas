<?php

namespace App\Domain;

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

final class Store
{
    private const JSON = ['occurrence', 'contact_links', 'payload', 'profile', 'selection', 'response', 'metadata', 'source_versions', 'draft', 'questions', 'sources', 'attempts', 'introduction_sources', 'context', 'output', 'application_result'];

    public static function decode(object|array $row): array
    {
        $data = (array) $row;
        foreach (self::JSON as $key) {
            if (isset($data[$key]) && is_string($data[$key])) {
                $data[$key] = json_decode($data[$key], true, 512, JSON_THROW_ON_ERROR);
            }
        }

        return $data;
    }

    private static function encode(array $data): array
    {
        // Keep absolute instants intact when PHP and PostgreSQL use different time zones.
        foreach ($data as $key => $value) {
            if ($value instanceof \DateTimeInterface) {
                $data[$key] = $value->format(DATE_ATOM);
            }
        }
        foreach (self::JSON as $key) {
            if (isset($data[$key]) && is_array($data[$key])) {
                $data[$key] = json_encode($data[$key], JSON_THROW_ON_ERROR | JSON_UNESCAPED_UNICODE);
            }
        }

        return $data;
    }

    public static function insert(string $table, array $data, bool $timestamps = true): array
    {
        $data = ['id' => (string) Str::uuid(), ...$data];
        if ($timestamps) {
            $data += ['created_at' => now(), 'updated_at' => now()];
        }
        DB::table($table)->insert(self::encode($data));

        return self::row($table, $data['id']);
    }

    public static function update(string $table, string $id, array $data): array
    {
        DB::table($table)->where('id', $id)->update(self::encode($data + ['updated_at' => now()]));

        return self::row($table, $id);
    }

    public static function row(string $table, string $id): array
    {
        $row = DB::table($table)->where('id', $id)->first();
        abort_unless($row, 404);

        return self::decode($row);
    }

    public static function owned(string $table, string $id, int $owner): array
    {
        $query = DB::table($table)->where('id', $id)->where('owner_id', $owner);
        if (in_array($table, ['projects', 'experiences'])) {
            $query->whereNull('deleted_at');
        }
        $row = $query->first();
        abort_unless($row, 404);
        $data = self::decode($row);
        if ($table === 'experiences') {
            self::owned('projects', $data['project_id'], $owner);
        }

        return $data;
    }

    public static function version(array $row, int $expected): void
    {
        if ($row['version'] !== $expected) {
            throw new DomainError('version_conflict', 'A newer version exists. Reload before applying your changes.');
        }
    }

    public static function audit(int $owner, string $operation, string $table, ?string $id, array $metadata = []): void
    {
        self::insert('audit_events', ['actor_id' => $owner, 'action' => $operation, 'subject_type' => $table, 'subject_id' => $id, 'metadata' => $metadata, 'created_at' => now()], false);
    }
}
