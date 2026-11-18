<?php

namespace App\Agent;

use App\Domain\CaptureService;
use App\Domain\Input;
use App\Domain\OpportunityService;
use App\Domain\P1Sources;
use App\Domain\PreparationService;
use App\Domain\PublicationService;
use App\Domain\Store;

final class OwnerContext
{
    public function build(int $owner, array $input): array
    {
        $purpose = $input['purpose'];
        $kind = match ($purpose) {
            'capture' => 'capture','feedback' => 'practice','introduction','export' => 'opportunity',default => $input['subject_kind'] ?? 'preparation'
        };
        if (! in_array($kind, match ($purpose) {
            'capture' => ['capture'],'feedback' => ['practice'],'introduction','export' => ['opportunity'],'answer' => ['preparation'],default => ['preparation', 'opportunity']
        }, true)) {
            Input::fail('subject_kind', 'Choose a supported value.');
        }
        $subject = match ($kind) {
            'capture' => app(CaptureService::class)->get($owner, $input['subject_id']),'practice' => app(PreparationService::class)->practice($owner, $input['subject_id']),'opportunity' => app(OpportunityService::class)->get($owner, $input['subject_id']),default => app(PreparationService::class)->get($owner, $input['subject_id'])
        };
        Store::version($subject, $input['expected_version']);
        $sources = [];
        $add = function (string $handle, string $field, mixed $value, ?string $id = null, ?string $version = null) use (&$sources) {
            if ($value === null || $value === '' || $value === []) {
                return;
            }$sources[] = ['handle' => $handle, 'field' => $field, 'text' => is_string($value) ? $value : json_encode($value, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES), 'experience_id' => $id, 'source_version' => $version];
        };
        $context = ['purpose' => $purpose, 'subject_kind' => $kind, 'subject_id' => $subject['id'], 'subject_version' => $subject['version'], 'request' => array_intersect_key($input, array_flip(['purpose', 'subject_id', 'expected_version', 'subject_kind', 'question'])), 'language' => $subject['target_language'] ?? 'en', 'question' => $input['question'] ?? ''];
        if ($purpose === 'capture') {
            if ($subject['applied_experience_id']) {
                Input::fail('subject_id', 'This draft has already been applied.');
            }
            $project = Store::owned('projects', $subject['project_id'], $owner);
            $context['project_version'] = $project['version'];
            $add('notes', 'owner_notes', $subject['notes']);
            foreach (['title', 'role', 'background', 'team_outcome'] as $field) {
                $add('project.'.$field, 'project.'.$field, $project[$field] ?? null);
            }
        } elseif ($purpose === 'export') {
            $export = app(OpportunityService::class)->export($owner, ['id' => $subject['id']]);
            $context['fields'] = $export['fields'];
            $context['export_digest'] = $export['source_digest'];
            foreach ($export['fields'] as $field) {
                $add('export.'.$field['key'], $field['label'], $field['text'], null, $field['source_version']);
            }
        } elseif ($purpose === 'introduction') {
            app(P1Sources::class)->requireFresh($owner, $subject['sources']);
            $preview = app(PublicationService::class)->preview($owner, $subject['selection']);
            $context['source_versions'] = array_map(fn ($source) => array_intersect_key($source, array_flip(['id', 'record_version_id', 'experience_version', 'project_version', 'access_epoch'])), $subject['sources']);
            $context['emphasis'] = $subject['direction'];
            $context['requirements'] = $subject['requirements'];
            $context['projection_digest'] = $preview['digest'];
            foreach ($preview['portfolio']['items'] as $item) {
                foreach ($item as $field => $value) {
                    if (in_array($field, ['id', 'record_version_id', 'confirmed_at'], true)) {
                        continue;
                    }if ($field === 'project') {
                        foreach ($value as $k => $v) {
                            if ($k !== 'id') {
                                $add($item['id'].'.project.'.$k, 'project.'.$k, $v, $item['id'], $item['record_version_id']);
                            }
                        }
                    } else {
                        $add($item['id'].'.'.$field, $field, $value, $item['id'], $item['record_version_id']);
                    }
                }
            }
        } else {
            app(P1Sources::class)->requireFresh($owner, $subject['sources']);
            $context['source_refs'] = $subject['sources'];
            foreach ($subject['sources'] as $source) {
                foreach ($source['content'] as $field => $value) {
                    $add($source['id'].'.'.$field, $field, $value, $source['id'], $source['record_version_id']);
                }foreach ($source['project'] as $field => $value) {
                    $add($source['id'].'.project.'.$field, 'project.'.$field, $value, $source['id'], $source['record_version_id']);
                }
            }
            if ($kind === 'practice') {
                $attempt = $subject['attempts'][$subject['current_index']] ?? null;
                if (! $attempt) {
                    Input::fail('subject_id', 'Write an answer before continuing.');
                }
                $prep = Store::owned('preparations', $subject['preparation_id'], $owner);
                $context['language'] = $prep['target_language'];
                $context['preparation_version'] = $prep['version'];
                $context['question'] = $attempt['question'];
                $context['practice_answer'] = $attempt['answer'];
            } else {
                $context['requirements'] = $subject['requirements'];
                $context['topic'] = $subject['topic'] ?? $subject['direction'];
                $context['answer_length'] = $subject['answer_length'] ?? 'standard';
            }
        }
        if ($purpose === 'answer') {
            Input::nonempty($context, ['question']);
        }
        $context['sources'] = $sources;
        if (strlen(json_encode($context)) > 64000) {
            Input::fail('subject_id', 'Select fewer sources for this request.');
        }

        return $context;
    }

    public function digest(array $context): string
    {
        $canonical = function (array $value) use (&$canonical): array {
            if (! array_is_list($value)) {
                ksort($value);
            }
            foreach ($value as $key => $child) {
                if (is_array($child)) {
                    $value[$key] = $canonical($child);
                }
            }

            return $value;
        };

        return hash('sha256', json_encode($canonical($context), JSON_THROW_ON_ERROR));
    }
}
