<?php

namespace App\Agent;

use Illuminate\Support\Arr;

final class PublicSources
{
    public function all(array $portfolio): array
    {
        $sources = [];
        $append = function (string $record, string $title, array $fields, mixed $version) use (&$sources): void {
            foreach (Arr::dot($fields) as $field => $value) {
                if (! is_scalar($value) || $value === '' || preg_match('/(^|\.)(id|version|confirmed_version_id|source_access_epoch)$/', $field)) {
                    continue;
                }
                $handle = substr(hash('sha256', $record.'|'.$version.'|'.$field.'|'.json_encode($value)), 0, 24);
                $sources[$handle] = ['handle' => $handle, 'experience_id' => $record === 'profile' ? null : $record,
                    'title' => $title, 'version' => $version, 'field' => $field, 'text' => is_bool($value) ? ($value ? 'true' : 'false') : (string) $value];
            }
        };
        $append('profile', 'Published profile', $portfolio['profile'], $portfolio['version']);
        foreach ($portfolio['items'] as $item) {
            $append($item['id'], $item['title'] ?? $item['project']['title'] ?? 'Published experience',
                Arr::except($item, ['id', 'record_version_id', 'confirmed_at', 'confirmed_version_id', 'source_access_epoch', 'version']), $item['record_version_id'] ?? $portfolio['version']);
        }

        return $sources;
    }

    public function retrieve(array $sources, string $name, array $args): array
    {
        if ($name === 'search_experiences') {
            $query = mb_strtolower(trim($args['query'] ?? ''));
            if (mb_strlen($query) > 200 || array_diff(array_keys($args), ['query'])) {
                throw new AgentFailure('invalid_tool');
            }
            $tokens = preg_split('/\s+/u', $query, -1, PREG_SPLIT_NO_EMPTY);
            $ranked = [];
            foreach ($sources as $source) {
                $haystack = mb_strtolower($source['title'].' '.$source['field'].' '.$source['text']);
                $score = $query === '' ? 1 : count(array_filter($tokens, fn ($token) => str_contains($haystack, $token)));
                if ($score) {
                    $ranked[] = [$score, $source];
                }
            }
            usort($ranked, fn ($a, $b) => $b[0] <=> $a[0]);
            $results = array_map(fn ($entry) => $entry[1], array_slice($ranked, 0, 16));
        } elseif ($name === 'get_experience') {
            if (! is_string($args['experience_id'] ?? null) || array_diff(array_keys($args), ['experience_id'])) {
                throw new AgentFailure('invalid_tool');
            }
            $results = array_slice(array_values(array_filter($sources, fn ($s) => $s['experience_id'] === $args['experience_id'])), 0, 20);
        } else {
            throw new AgentFailure('invalid_tool');
        }

        return array_map(fn ($s) => array_replace($s, ['text' => mb_substr($s['text'], 0, 1000)]), $results);
    }
}
