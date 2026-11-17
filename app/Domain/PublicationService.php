<?php

namespace App\Domain;

use App\Services\PublicationProjection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

final class PublicationService
{
    public function __construct(private ProfileService $profiles, private PublicationProjection $projection) {}

    private function selection(array $input): array
    {
        return array_intersect_key($input, array_flip(['profile_fields', 'items', 'publication_id']));
    }

    public function preview(int $owner, array $input, ?string $opportunityId = null): array
    {
        if (isset($input['publication_id'])) {
            Store::owned('publications', $input['publication_id'], $owner);
            $opportunity = DB::table('opportunities')->where('publication_id', $input['publication_id'])->first();
            if ($opportunity && $opportunity->id !== $opportunityId) {
                throw new DomainError('opportunity_required', 'Review this page in its opportunity.');
            }
        }
        $profile = $this->profiles->get($owner);
        $profileProjection = array_intersect_key($profile, array_flip($input['profile_fields']));
        $items = [];
        $references = [];
        $seen = [];
        foreach ($input['items'] as $i => $selected) {
            $experience = Store::owned('experiences', $selected['experience_id'], $owner);
            if (isset($seen[$experience['id']])) {
                Input::fail('items', 'Select each experience only once.');
            }$seen[$experience['id']] = true;
            if (! $experience['confirmed_version_id']) {
                Input::fail("items.$i.experience_id", 'Confirm this experience before publication.');
            }
            $version = Store::row('record_versions', $experience['confirmed_version_id']);
            $project = Store::owned('projects', $experience['project_id'], $owner);
            $item = array_intersect_key($version['payload'], array_flip($selected['fields']));
            if (isset($item['evidence'])) {
                $item['evidence'] = array_map(fn ($e) => array_intersect_key($e, array_flip(['id', 'kind', 'label', 'url', 'excerpt', 'provenance'])), $item['evidence']);
            }
            $item = ['id' => $experience['id'], 'record_version_id' => $version['id'], 'confirmed_at' => $version['confirmed_at'], ...$item,
                'project' => ['id' => $project['id'], ...array_intersect_key($project, array_flip($selected['project_fields']))]];
            $items[] = $item;
            $references[] = ['experience_id' => $experience['id'], 'project_id' => $project['id'], 'record_version_id' => $version['id'], 'source_access_epoch' => $experience['access_epoch'], 'project_version' => $project['version']];
        }
        $portfolio = ['profile' => $profileProjection, 'items' => $items, 'skills' => $this->projection->skills($items)];
        $digest = hash_hmac('sha256', json_encode([$this->selection($input), $portfolio, $references, $profile['version']], JSON_THROW_ON_ERROR | JSON_UNESCAPED_UNICODE), config('app.key'));

        return ['portfolio' => $portfolio, 'digest' => $digest, 'references' => $references, 'source_versions' => ['profile' => $profile['version'], 'projects' => array_column($references, 'project_version', 'project_id')]];
    }

    public function publish(int $owner, array $input, bool $republish = false, ?string $opportunityId = null): array
    {
        $operation = $republish ? 'republish_publication' : 'publish_publication';
        $hash = hash('sha256', json_encode($input, JSON_THROW_ON_ERROR | JSON_UNESCAPED_UNICODE));
        $existing = DB::table('idempotency_keys')->where('owner_id', $owner)->where('operation', $operation)->where('key', $input['idempotency_key'])->first();
        if ($existing) {
            if (! hash_equals($existing->request_hash, $hash)) {
                throw new DomainError('idempotency_conflict', 'This request key was used with different input.');
            }

            return $this->get($owner, json_decode($existing->response, true)['id']);
        }
        $preview = $this->preview($owner, $input, $opportunityId);
        if (! hash_equals($preview['digest'], $input['preview_digest'])) {
            throw new DomainError('preview_conflict', 'The preview has changed. Review it again before publishing.');
        }
        if ($republish) {
            $pub = Store::owned('publications', $input['publication_id'], $owner);
            Store::version($pub, $input['expected_version']);
            $pub = Store::update('publications', $pub['id'], ['version' => $pub['version'] + 1, 'state' => 'published', 'published_at' => now(), 'revoked_at' => null]);
        } else {
            if (isset($input['publication_id'])) {
                Input::fail('publication_id', 'Use explicit republication for an existing page.');
            }
            $pub = Store::insert('publications', ['owner_id' => $owner, 'slug' => 'atlas-'.Str::lower(Str::random(20)), 'state' => 'published', 'published_at' => now()]);
        }
        $snapshot = Store::insert('publication_snapshots', ['publication_id' => $pub['id'], 'number' => $pub['version'], 'profile' => $preview['portfolio']['profile'], 'selection' => $this->selection($input), 'source_versions' => $preview['source_versions'], 'created_at' => now()], false);
        foreach ($preview['portfolio']['items'] as $i => $item) {
            $ref = $preview['references'][$i];
            unset($ref['project_version']);
            Store::insert('publication_items', $ref + ['snapshot_id' => $snapshot['id'], 'sort_order' => $i, 'payload' => $item], false);
        }
        Store::update('publications', $pub['id'], ['active_snapshot_id' => $snapshot['id']]);
        Store::insert('idempotency_keys', ['owner_id' => $owner, 'operation' => $operation, 'key' => $input['idempotency_key'], 'request_hash' => $hash, 'response' => ['id' => $pub['id']]]);

        return $this->get($owner, $pub['id']);
    }

    public function get(int $owner, string $id): array
    {
        $pub = Store::owned('publications', $id, $owner);
        $pub['has_unpublished_changes'] = false;
        if ($pub['active_snapshot_id']) {
            $snapshot = Store::row('publication_snapshots', $pub['active_snapshot_id']);
            $pub['selection'] = $snapshot['selection'];
            $sourceVersions = $snapshot['source_versions'];
            if (isset($sourceVersions['profile']) && $this->profiles->get($owner)['version'] !== $sourceVersions['profile']) {
                $pub['has_unpublished_changes'] = true;
            }
            foreach ($sourceVersions['projects'] ?? [] as $projectId => $version) {
                $project = DB::table('projects')->where('id', $projectId)->first();
                if (! $project || $project->deleted_at || $project->version !== $version) {
                    $pub['has_unpublished_changes'] = true;
                }
            }
            foreach (DB::table('publication_items')->where('snapshot_id', $snapshot['id'])->get() as $item) {
                $source = DB::table('experiences')->where('id', $item->experience_id)->first();
                if (! $source || $source->deleted_at || $source->status === 'draft' || $source->confirmed_version_id !== $item->record_version_id || $source->access_epoch !== $item->source_access_epoch) {
                    $pub['has_unpublished_changes'] = true;
                }
            }
        }

        return $pub;
    }

    public function listing(int $owner): array
    {
        return DB::table('publications')->where('owner_id', $owner)->whereNotIn('id', DB::table('opportunities')->whereNotNull('publication_id')->select('publication_id'))->orderByDesc('created_at')->get()->map(fn ($r) => $this->get($owner, $r->id))->all();
    }

    public function revoke(int $owner, array $input): array
    {
        $pub = Store::owned('publications', $input['id'], $owner);
        if ($pub['state'] === 'revoked' && in_array($input['expected_version'], [$pub['version'], $pub['version'] - 1], true)) {
            return $this->get($owner, $pub['id']);
        }
        Store::version($pub, $input['expected_version']);
        Store::update('publications', $pub['id'], ['state' => 'revoked', 'version' => $pub['version'] + 1, 'revoked_at' => now()]);

        return $this->get($owner, $pub['id']);
    }
}
