<?php

namespace App\Domain;

use Illuminate\Support\Facades\DB;

final class P1Sources
{
    public function select(int $owner, array $ids): array
    {
        $sources = [];
        foreach (array_unique($ids) as $id) {
            $experience = Store::owned('experiences', $id, $owner);
            if (! $experience['confirmed_version_id']) {
                Input::fail('experience_ids', 'Select confirmed experiences.');
            }
            $project = Store::owned('projects', $experience['project_id'], $owner);
            $version = Store::row('record_versions', $experience['confirmed_version_id']);
            $sources[] = ['id' => $id, 'record_version_id' => $version['id'], 'experience_version' => $experience['version'],
                'project_id' => $project['id'], 'project_version' => $project['version'], 'access_epoch' => $experience['access_epoch'],
                'content' => array_intersect_key($version['payload'], array_flip(['title', 'record_type', 'occurrence', 'personal_action', 'result', 'validation', 'remaining_work', 'skills', 'evidence'])),
                'project' => array_intersect_key($project, array_flip(['title', 'type', 'role', 'background', 'team_outcome', 'occurrence']))];
        }

        return $sources;
    }

    public function freshness(int $owner, array $sources): array
    {
        $changes = [];
        foreach ($sources as $ref) {
            $experience = DB::table('experiences')->where('owner_id', $owner)->where('id', $ref['id'])->first();
            $project = $experience ? DB::table('projects')->where('owner_id', $owner)->where('id', $experience->project_id)->first() : null;
            if (! $experience || ! $project || $experience->deleted_at || $project->deleted_at) {
                $changes[] = ['id' => $ref['id'], 'state' => 'unavailable'];
            } elseif ($experience->version !== $ref['experience_version'] || $experience->confirmed_version_id !== $ref['record_version_id'] || $project->version !== $ref['project_version'] || $experience->access_epoch !== $ref['access_epoch']) {
                $changes[] = ['id' => $ref['id'], 'state' => 'changed'];
            }
        }

        return $changes;
    }

    public function requireFresh(int $owner, array $sources): void
    {
        if ($this->freshness($owner, $sources)) {
            throw new DomainError('source_changed', 'Sources changed. Review and select them again.');
        }
    }
}
