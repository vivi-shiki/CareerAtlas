<?php

namespace App\Domain;

use Illuminate\Support\Facades\DB;

final class ExperienceService
{
    public function get(int $owner, string $id): array
    {
        $row = Store::owned('experiences', $id, $owner);
        $row['skills'] = DB::table('skills')->join('experience_skill', 'skills.id', '=', 'experience_skill.skill_id')
            ->where('experience_skill.experience_id', $id)->orderBy('skills.normalized_name')->get(['skills.id', 'skills.name'])->map(fn ($r) => (array) $r)->all();
        $row['evidence'] = $this->evidence($owner, $id);

        return $row;
    }

    public function listing(int $owner, array $input): array
    {
        $query = DB::table('experiences')->where('owner_id', $owner)->whereNull('deleted_at')
            ->whereIn('project_id', DB::table('projects')->where('owner_id', $owner)->whereNull('deleted_at')->select('id'));
        ($input['archived'] ?? false) ? $query->whereNotNull('archived_at') : $query->whereNull('archived_at');
        if (isset($input['project_id'])) {
            Store::owned('projects', $input['project_id'], $owner);
            $query->where('project_id', $input['project_id']);
        }
        if (! empty($input['query'])) {
            $query->where('title', 'ilike', '%'.addcslashes($input['query'], '%_\\').'%');
        }
        $total = $query->count();
        $page = $input['page'] ?? 1;
        $size = $input['per_page'] ?? 20;

        return ['items' => $query->orderByDesc('created_at')->orderBy('id')->offset(($page - 1) * $size)->limit($size)->get()->map(fn ($r) => $this->get($owner, $r->id))->all(), 'total' => $total, 'page' => $page, 'per_page' => $size];
    }

    public function create(int $owner, array $input): array
    {
        Store::owned('projects', $input['project_id'], $owner);
        Input::occurrence($input['occurrence'] ?? null);
        $row = Store::insert('experiences', $input + ['owner_id' => $owner]);

        return $this->get($owner, $row['id']);
    }

    public function update(int $owner, array $input): array
    {
        $row = Store::owned('experiences', $input['id'], $owner);
        Store::version($row, $input['expected_version']);
        Input::occurrence($input['occurrence'] ?? $row['occurrence']);
        unset($input['id'],$input['expected_version']);
        Store::update('experiences', $row['id'], $input + ['version' => $row['version'] + 1, 'status' => 'draft']);

        return $this->get($owner, $row['id']);
    }

    public function touchDraft(int $owner, array $row): array
    {
        Store::update('experiences', $row['id'], ['version' => $row['version'] + 1, 'status' => 'draft']);

        return $this->get($owner, $row['id']);
    }

    public function confirm(int $owner, array $input): array
    {
        $row = $this->get($owner, $input['id']);
        Store::version($row, $input['expected_version']);
        if ($row['archived_at']) {
            Input::fail('id', 'Restore this experience before confirmation.');
        }
        Input::nonempty($row, ['title', 'record_type', 'occurrence', 'personal_action']);
        Input::occurrence($row['occurrence']);
        if ($row['status'] === 'confirmed') {
            return $row;
        }
        $payload = array_intersect_key($row, array_flip(['title', 'record_type', 'occurrence', 'personal_action', 'result', 'validation', 'remaining_work', 'private_notes', 'provenance', 'skills', 'evidence']));
        $version = Store::insert('record_versions', ['experience_id' => $row['id'], 'number' => $row['version'], 'payload' => $payload, 'confirmed_at' => now()], false);
        Store::update('experiences', $row['id'], ['status' => 'confirmed', 'confirmed_version_id' => $version['id']]);

        return $this->get($owner, $row['id']);
    }

    public function lifecycle(int $owner, array $input, string $action): array
    {
        $row = Store::owned('experiences', $input['id'], $owner);
        Store::version($row, $input['expected_version']);
        $data = ['version' => $row['version'] + 1];
        if ($action === 'revoke') {
            $data['access_epoch'] = $row['access_epoch'] + 1;
        } else {
            $data[$action === 'delete' ? 'deleted_at' : 'archived_at'] = $action === 'restore' ? null : now();
        }
        $saved = Store::update('experiences', $row['id'], $data);

        return $action === 'delete' ? $saved : $this->get($owner, $row['id']);
    }

    public function evidence(int $owner, string $id): array
    {
        Store::owned('experiences', $id, $owner);

        return DB::table('evidence')->where('experience_id', $id)->orderBy('created_at')->orderBy('id')->get()->map(fn ($r) => Store::decode($r))->all();
    }

    public function mutateEvidence(int $owner, array $input, string $action): array
    {
        if ($action === 'create') {
            $experience = Store::owned('experiences', $input['experience_id'], $owner);
        } else {
            $evidence = Store::row('evidence', $input['id']);
            $experience = Store::owned('experiences', $evidence['experience_id'], $owner);
        }
        Store::version($experience, $input['expected_version']);
        if ($action === 'delete') {
            DB::table('evidence')->where('id', $evidence['id'])->delete();
        } else {
            $data = array_intersect_key($input, array_flip(['kind', 'label', 'url', 'excerpt']));
            $complete = array_replace($evidence ?? [], $data);
            Input::nonempty($complete, ['kind', 'label']);
            if ($complete['kind'] === 'statement') {
                Input::nonempty($complete, ['excerpt']);
                $data['provenance'] = 'owner_statement';
                $data['url'] = null;
            } else {
                Input::url($complete['url'] ?? null);
                $data['provenance'] = 'supplied_link';
            }
            if ($action === 'create') {
                Store::insert('evidence', $data + ['experience_id' => $experience['id']]);
            } else {
                Store::update('evidence', $evidence['id'], $data + ['version' => $evidence['version'] + 1]);
            }
        }

        return $this->touchDraft($owner, $experience);
    }
}
