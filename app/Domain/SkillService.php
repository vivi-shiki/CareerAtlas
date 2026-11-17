<?php

namespace App\Domain;

use Illuminate\Support\Facades\DB;

final class SkillService
{
    private function normalized(string $name): string
    {
        return mb_strtolower(preg_replace('/\s+/u', ' ', trim($name)));
    }

    private function find(int $owner, string $name): ?array
    {
        $normal = $this->normalized($name);
        $row = DB::table('skills')->where('owner_id', $owner)->where('normalized_name', $normal)->first();
        if ($row) {
            return Store::decode($row);
        }
        $alias = DB::table('skill_aliases')->where('owner_id', $owner)->where('normalized_alias', $normal)->first();

        return $alias ? Store::owned('skills', $alias->skill_id, $owner) : null;
    }

    public function listing(int $owner, array $input): array
    {
        $query = DB::table('skills')->where('owner_id', $owner);
        if (! empty($input['query'])) {
            $pattern = '%'.addcslashes($this->normalized($input['query']), '%_\\').'%';
            $query->where(function ($matches) use ($owner, $pattern) {
                $matches->where('normalized_name', 'like', $pattern)
                    ->orWhereIn('id', DB::table('skill_aliases')->where('owner_id', $owner)->where('normalized_alias', 'like', $pattern)->select('skill_id'));
            });
        }

        return $query->orderBy('normalized_name')->limit(100)->get()->map(fn ($r) => $this->get($owner, $r->id))->all();
    }

    public function get(int $owner, string $id): array
    {
        $row = Store::owned('skills', $id, $owner);
        $row['aliases'] = DB::table('skill_aliases')->where('skill_id', $id)->orderBy('normalized_alias')->pluck('normalized_alias')->all();
        $experiences = DB::table('experiences')->where('owner_id', $owner)->whereNull('deleted_at')->whereNull('archived_at')
            ->whereIn('id', DB::table('experience_skill')->where('skill_id', $id)->select('experience_id'))
            ->whereIn('project_id', DB::table('projects')->whereNull('deleted_at')->select('id'))->get();
        $row['experiences'] = $experiences->map(fn ($r) => ['id' => $r->id, 'title' => $r->title, 'project_id' => $r->project_id, 'status' => $r->status])->all();
        $row['experience_count'] = $experiences->count();
        $row['project_count'] = $experiences->pluck('project_id')->unique()->count();

        return $row;
    }

    public function create(int $owner, array $input): array
    {
        Input::nonempty($input, ['name']);
        $found = $this->find($owner, $input['name']);

        return $found ?? Store::insert('skills', ['owner_id' => $owner, 'name' => trim($input['name']), 'normalized_name' => $this->normalized($input['name'])]);
    }

    public function update(int $owner, array $input): array
    {
        $skill = Store::owned('skills', $input['id'], $owner);
        Store::version($skill, $input['expected_version']);
        Input::nonempty($input, ['name']);
        $found = $this->find($owner, $input['name']);
        if ($found && $found['id'] !== $skill['id']) {
            Input::fail('name', 'This name or alias is already assigned.');
        }
        Store::update('skills', $skill['id'], ['name' => trim($input['name']), 'normalized_name' => $this->normalized($input['name']), 'version' => $skill['version'] + 1]);
        foreach (DB::table('experience_skill')->where('skill_id', $skill['id'])->pluck('experience_id') as $id) {
            $row = DB::table('experiences')->where('id', $id)->whereNull('deleted_at')->first();
            if ($row) {
                Store::update('experiences', $id, ['status' => 'draft', 'version' => $row->version + 1]);
            }
        }

        return $this->get($owner, $skill['id']);
    }

    public function alias(int $owner, array $input, bool $delete = false): array
    {
        $skill = Store::owned('skills', $input['id'], $owner);
        Store::version($skill, $input['expected_version']);
        Input::nonempty($input, ['alias']);
        $normal = $this->normalized($input['alias']);
        if ($delete) {
            DB::table('skill_aliases')->where('skill_id', $skill['id'])->where('normalized_alias', $normal)->delete();
        } else {
            $found = $this->find($owner, $input['alias']);
            if ($found && $found['id'] !== $skill['id']) {
                Input::fail('alias', 'This alias is already assigned.');
            }
            if (! $found) {
                Store::insert('skill_aliases', ['owner_id' => $owner, 'skill_id' => $skill['id'], 'normalized_alias' => $normal]);
            }
        }
        Store::update('skills', $skill['id'], ['version' => $skill['version'] + 1]);

        return $this->get($owner, $skill['id']);
    }

    public function association(int $owner, array $input, bool $detach = false): array
    {
        $experience = Store::owned('experiences', $input['id'], $owner);
        $skill = Store::owned('skills', $input['skill_id'], $owner);
        Store::version($experience, $input['expected_version']);
        $query = DB::table('experience_skill')->where('experience_id', $experience['id'])->where('skill_id', $skill['id']);
        $exists = $query->exists();
        if (($exists && ! $detach) || (! $exists && $detach)) {
            return app(ExperienceService::class)->get($owner, $experience['id']);
        }
        if ($detach) {
            $query->delete();
        } else {
            DB::table('experience_skill')->insert(['owner_id' => $owner, 'experience_id' => $experience['id'], 'skill_id' => $skill['id']]);
        }

        return app(ExperienceService::class)->touchDraft($owner, $experience);
    }
}
