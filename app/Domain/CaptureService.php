<?php

namespace App\Domain;

use Illuminate\Support\Facades\DB;

final class CaptureService
{
    public function get(int $owner, string $id): array
    {
        $row = Store::owned('capture_drafts', $id, $owner);
        Store::owned('projects', $row['project_id'], $owner);

        return $row;
    }

    public function save(int $owner, array $input): array
    {
        if (isset($input['id'])) {
            $row = $this->get($owner, $input['id']);
            Store::version($row, $input['expected_version']);
            if ($row['applied_experience_id']) {
                throw new DomainError('already_applied', 'This draft has already been applied.');
            }
        } else {
            Store::owned('projects', $input['project_id'], $owner);
        }
        if (isset($input['notes'])) {
            Input::nonempty($input, ['notes']);
        }
        if (isset($input['draft']['occurrence'])) {
            Input::occurrence($input['draft']['occurrence']);
        }
        unset($input['id'], $input['expected_version']);

        return isset($row) ? Store::update('capture_drafts', $row['id'], $input + ['version' => $row['version'] + 1]) : Store::insert('capture_drafts', $input + ['owner_id' => $owner]);
    }

    public function listing(int $owner): array
    {
        return DB::table('capture_drafts')->where('owner_id', $owner)->whereIn('project_id', DB::table('projects')->where('owner_id', $owner)->whereNull('deleted_at')->select('id'))->orderByDesc('updated_at')->limit(100)->get()->map(fn ($r) => Store::decode($r))->all();
    }

    public function duplicates(int $owner, array $input): array
    {
        $capture = $this->get($owner, $input['id']);
        $text = mb_strtolower(($capture['draft']['title'] ?? '').' '.$capture['notes']);
        $words = array_filter(preg_split('/[^\p{L}\p{N}]+/u', $text), fn ($w) => mb_strlen($w) >= 3);
        $rows = DB::table('experiences')->where('owner_id', $owner)->where('project_id', $capture['project_id'])->whereNull('deleted_at')->orderByDesc('updated_at')->limit(200)->get();
        $candidates = [];
        foreach ($rows as $row) {
            $title = mb_strtolower($row->title ?? '');
            $shared = array_values(array_filter(array_unique($words), fn ($word) => str_contains($title, $word) || str_contains(mb_strtolower($row->personal_action ?? ''), $word)));
            if ($shared || ($title !== '' && str_contains($text, $title))) {
                $candidates[] = ['id' => $row->id, 'title' => $row->title, 'version' => $row->version, 'reason' => 'Shared project and overlapping text', 'matched_terms' => array_slice($shared, 0, 5)];
            }
        }

        return array_slice($candidates, 0, 5);
    }

    public function apply(int $owner, array $input): array
    {
        $row = $this->get($owner, $input['id']);
        if ($row['applied_experience_id']) {
            return app(ExperienceService::class)->get($owner, $row['applied_experience_id']);
        }
        Store::version($row, $input['expected_version']);
        $draft = array_intersect_key($row['draft'], array_flip(['title', 'record_type', 'occurrence', 'personal_action', 'result', 'validation', 'remaining_work']));
        $experiences = app(ExperienceService::class);
        if (! empty($input['merge_id'])) {
            $target = Store::owned('experiences', $input['merge_id'], $owner);
            if ($target['project_id'] !== $row['project_id']) {
                Input::fail('merge_id', 'Choose an experience in this project.');
            }
            Store::version($target, $input['merge_version'] ?? 0);
            // A merge is a reviewed patch. Blank suggestions never erase existing facts.
            $draft = array_filter($draft, fn ($value) => $value !== null && $value !== '');
            $saved = $experiences->update($owner, $draft + ['id' => $target['id'], 'expected_version' => $target['version']]);
        } else {
            $saved = $experiences->create($owner, $draft + ['project_id' => $row['project_id']]);
        }
        if ($row['provenance'] === 'ai_assisted') {
            Store::update('experiences', $saved['id'], ['provenance' => 'ai_assisted']);
        }
        foreach ($input['skill_names'] ?? [] as $name) {
            $skill = app(SkillService::class)->create($owner, ['name' => $name]);
            $saved = app(SkillService::class)->association($owner, ['id' => $saved['id'], 'skill_id' => $skill['id'], 'expected_version' => $saved['version']]);
        }
        Store::update('capture_drafts', $row['id'], ['applied_experience_id' => $saved['id'], 'version' => $row['version'] + 1]);
        app(HistoryService::class)->record($owner, $saved['id'], 'apply_capture');

        return $experiences->get($owner, $saved['id']);
    }
}
