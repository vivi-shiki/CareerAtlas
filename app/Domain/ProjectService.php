<?php

namespace App\Domain;

use Illuminate\Support\Facades\DB;

final class ProjectService
{
    public function listing(int $owner, array $input): array
    {
        $query = DB::table('projects')->where('owner_id', $owner)->whereNull('deleted_at');
        ($input['archived'] ?? false) ? $query->whereNotNull('archived_at') : $query->whereNull('archived_at');
        if (! empty($input['query'])) {
            $query->where('title', 'ilike', '%'.addcslashes($input['query'], '%_\\').'%');
        }
        if (isset($input['type'])) {
            $query->where('type', $input['type']);
        }
        $total = $query->count();
        $page = $input['page'] ?? 1;
        $size = $input['per_page'] ?? 20;

        return ['items' => $query->orderByDesc('created_at')->orderBy('id')->offset(($page - 1) * $size)->limit($size)->get()->map(fn ($r) => Store::decode($r))->all(), 'total' => $total, 'page' => $page, 'per_page' => $size];
    }

    public function create(int $owner, array $input): array
    {
        Input::nonempty($input, ['title', 'type', 'role']);
        Input::occurrence($input['occurrence'] ?? null);

        return Store::insert('projects', $input + ['owner_id' => $owner]);
    }

    public function update(int $owner, array $input): array
    {
        $row = Store::owned('projects', $input['id'], $owner);
        Store::version($row, $input['expected_version']);
        $new = array_replace($row, $input);
        Input::nonempty($new, ['title', 'type', 'role']);
        Input::occurrence($new['occurrence']);
        unset($input['id'],$input['expected_version']);

        return Store::update('projects', $row['id'], $input + ['version' => $row['version'] + 1]);
    }

    public function lifecycle(int $owner, array $input, string $action): array
    {
        $row = Store::owned('projects', $input['id'], $owner);
        Store::version($row, $input['expected_version']);
        $field = $action === 'delete' ? 'deleted_at' : 'archived_at';

        return Store::update('projects', $row['id'], [$field => $action === 'restore' ? null : now(), 'version' => $row['version'] + 1]);
    }
}
