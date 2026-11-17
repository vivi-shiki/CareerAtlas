<?php

namespace App\Domain;

use Illuminate\Support\Facades\DB;

final class HistoryService
{
    public function record(int $owner, string $id, string $event): void
    {
        $row = app(ExperienceService::class)->get($owner, $id);
        $payload = array_intersect_key($row, array_flip(['title', 'record_type', 'occurrence', 'personal_action', 'result', 'validation', 'remaining_work', 'private_notes', 'provenance', 'status', 'skills', 'evidence', 'archived_at', 'access_epoch']));
        $digest = hash('sha256', json_encode($payload));
        $last = DB::table('experience_revisions')->where('experience_id', $id)->orderByDesc('number')->first();
        if ($last && hash_equals($last->digest, $digest)) {
            return;
        }
        Store::insert('experience_revisions', ['experience_id' => $id, 'number' => ($last?->number ?? 0) + 1, 'source_version' => $row['version'], 'event' => $event, 'payload' => $payload, 'digest' => $digest, 'created_at' => now()], false);
    }

    public function get(int $owner, string $id): array
    {
        Store::owned('experiences', $id, $owner);
        $history = DB::table('experience_revisions')->where('experience_id', $id)->orderBy('number')->get()->map(fn ($row) => Store::decode($row))->all();
        $known = array_column(array_filter($history, fn ($r) => ($r['payload']['status'] ?? '') === 'confirmed'), 'source_version');
        $legacy = DB::table('record_versions')->where('experience_id', $id)->whereNotIn('number', $known)->orderBy('number')->get()->map(fn ($row) => ['id' => $row->id, 'number' => $row->number, 'source_version' => $row->number, 'event' => 'confirmed_version', 'created_at' => $row->confirmed_at, 'payload' => Store::decode($row)['payload'] + ['status' => 'confirmed']])->all();
        $history = [...$legacy, ...$history];
        $previous = [];
        foreach ($history as &$revision) {
            $revision['differences'] = [];
            foreach (array_unique([...array_keys($previous), ...array_keys($revision['payload'])]) as $key) {
                if (($previous[$key] ?? null) !== ($revision['payload'][$key] ?? null)) {
                    $revision['differences'][] = ['field' => $key, 'before' => $previous[$key] ?? null, 'after' => $revision['payload'][$key] ?? null];
                }
            }
            $previous = $revision['payload'];
        }

        return array_reverse($history);
    }
}
