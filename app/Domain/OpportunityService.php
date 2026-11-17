<?php

namespace App\Domain;

use App\Services\PublicationProjection;
use Illuminate\Support\Facades\DB;

final class OpportunityService
{
    public function __construct(private PublicationService $publications, private P1Sources $sources) {}

    public function get(int $owner, string $id): array
    {
        $row = Store::owned('opportunities', $id, $owner);
        $row['source_changes'] = $this->sources->freshness($owner, $row['sources']);
        $row['publication'] = $row['publication_id'] ? $this->publications->get($owner, $row['publication_id']) : null;

        return $row;
    }

    public function listing(int $owner): array
    {
        return DB::table('opportunities')->where('owner_id', $owner)->orderByDesc('updated_at')->limit(100)->get()->map(fn ($row) => $this->get($owner, $row->id))->all();
    }

    public function save(int $owner, array $input): array
    {
        if (isset($input['id'])) {
            $row = $this->get($owner, $input['id']);
            Store::version($row, $input['expected_version']);
        }
        foreach (['title', 'direction'] as $field) {
            if (isset($input[$field])) {
                Input::nonempty($input, [$field]);
            }
        }
        if (! empty($input['deadline'])) {
            Input::occurrence(['start' => $input['deadline'], 'precision' => 'day']);
        }
        if (isset($input['selection'])) {
            $this->publications->preview($owner, $input['selection']);
            $input['sources'] = $this->sources->select($owner, array_column($input['selection']['items'], 'experience_id'));
        }
        if (isset($input['selection']) || isset($input['introduction']) || isset($input['target_language'])) {
            $input['introduction_confirmed'] = false;
        }
        unset($input['id'], $input['expected_version']);
        $saved = isset($row) ? Store::update('opportunities', $row['id'], $input + ['version' => $row['version'] + 1]) : Store::insert('opportunities', $input + ['owner_id' => $owner]);

        return $this->get($owner, $saved['id']);
    }

    public function confirmIntroduction(int $owner, array $input): array
    {
        $row = $this->get($owner, $input['id']);
        Store::version($row, $input['expected_version']);
        Input::nonempty($row, ['introduction']);
        $sources = $this->sources->select($owner, array_column($row['selection']['items'], 'experience_id'));
        if (! $sources) {
            Input::fail('selection', 'Select confirmed experiences.');
        }
        Store::update('opportunities', $row['id'], ['sources' => $sources, 'introduction_confirmed' => true, 'version' => $row['version'] + 1]);

        return $this->get($owner, $row['id']);
    }

    public function preview(int $owner, string $id): array
    {
        $row = $this->get($owner, $id);
        if (! $row['selection']['items']) {
            Input::fail('selection', 'Select confirmed experiences.');
        }
        $selection = $row['selection'] + ($row['publication_id'] ? ['publication_id' => $row['publication_id']] : []);
        $preview = $this->publications->preview($owner, $selection, $row['id']);
        $introduction = '';
        if ($row['include_introduction'] && $row['introduction'] !== '') {
            if (! $row['introduction_confirmed']) {
                Input::fail('introduction', 'Review and confirm the introduction first.');
            }
            $this->sources->requireFresh($owner, $row['sources']);
            $introduction = $row['introduction'];
        }
        $preview['portfolio']['introduction'] = $introduction;
        $preview['publication_digest'] = $preview['digest'];
        $preview['digest'] = hash_hmac('sha256', json_encode([$row['id'], $row['version'], $preview['digest'], $introduction]), config('app.key'));

        return $preview;
    }

    public function publish(int $owner, array $input): array
    {
        $row = $this->get($owner, $input['id']);
        $previous = DB::table('idempotency_keys')->where('owner_id', $owner)->where('operation', 'publish_opportunity')->where('key', $input['idempotency_key'])->first();
        $hash = hash('sha256', json_encode($input));
        if ($previous) {
            if (! hash_equals($previous->request_hash, $hash)) {
                throw new DomainError('idempotency_conflict', 'This request key was used with different input.');
            }

            return $row;
        }
        Store::version($row, $input['expected_version']);
        if ($row['archived_at']) {
            Input::fail('id', 'Restore this opportunity before publishing.');
        }
        $preview = $this->preview($owner, $row['id']);
        if (! hash_equals($preview['digest'], $input['preview_digest'])) {
            throw new DomainError('preview_conflict', 'The preview has changed. Review it again before publishing.');
        }
        $publication = $this->publications->publish($owner, $row['selection'] + ['preview_digest' => $preview['publication_digest'], 'idempotency_key' => 'opportunity-'.$input['idempotency_key']]
            + ($row['publication_id'] ? ['publication_id' => $row['publication_id'], 'expected_version' => $row['publication']['version']] : []), (bool) $row['publication_id'], $row['id']);
        DB::table('publication_snapshots')->where('id', $publication['active_snapshot_id'])->update(['introduction' => $preview['portfolio']['introduction'], 'introduction_sources' => json_encode(array_column($preview['references'], 'experience_id'))]);
        Store::update('opportunities', $row['id'], ['publication_id' => $publication['id'], 'version' => $row['version'] + 1]);
        Store::insert('idempotency_keys', ['owner_id' => $owner, 'operation' => 'publish_opportunity', 'key' => $input['idempotency_key'], 'request_hash' => $hash, 'response' => ['id' => $row['id']]]);

        return $this->get($owner, $row['id']);
    }

    public function lifecycle(int $owner, array $input, string $action): array
    {
        $row = $this->get($owner, $input['id']);
        Store::version($row, $input['expected_version']);
        if ($action === 'revoke') {
            if ($row['publication']) {
                $this->publications->revoke($owner, ['id' => $row['publication_id'], 'expected_version' => $row['publication']['version']]);
            }
            Store::update('opportunities', $row['id'], ['version' => $row['version'] + 1]);
        } else {
            Store::update('opportunities', $row['id'], ['archived_at' => $action === 'archive' ? now() : null, 'version' => $row['version'] + 1]);
        }

        return $this->get($owner, $row['id']);
    }

    public function export(int $owner, array $input): array
    {
        $row = $this->get($owner, $input['id']);
        abort_unless($row['publication'] && $row['publication']['state'] === 'published', 404);
        $portfolio = app(PublicationProjection::class)->publicPortfolio($row['publication']['slug']);
        $fields = app(MarkdownExport::class)->fields($portfolio);
        if (isset($input['translations'])) {
            if (empty($input['reviewed']) || ! hash_equals($this->exportDigest($portfolio), $input['source_digest'] ?? '')) {
                throw new DomainError('source_changed', 'Review the current export before downloading.');
            }
            $keys = array_column($fields, 'key');
            $translated = array_column($input['translations'], 'text', 'key');
            if (count($input['translations']) !== count($keys) || count($translated) !== count($keys) || array_diff($keys, array_keys($translated))) {
                Input::fail('translations', 'Translate exactly the selected export fields.');
            }
            foreach ($fields as &$field) {
                $field['text'] = $translated[$field['key']];
            }
            unset($field);
        }

        return ['filename' => 'portfolio-'.$row['target_language'].'.md', 'markdown' => app(MarkdownExport::class)->render($fields, $row['target_language'], $portfolio), 'fields' => $fields, 'source_digest' => $this->exportDigest($portfolio), 'language' => $row['target_language']];
    }

    private function exportDigest(array $portfolio): string
    {
        return hash_hmac('sha256', json_encode($portfolio), config('app.key'));
    }
}
