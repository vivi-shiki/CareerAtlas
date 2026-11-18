<?php

namespace App\Agent;

use App\Domain\Input;
use App\Domain\OperationCatalog;

final class OwnerOutput
{
    public static function schema(string $purpose): array
    {
        $text = fn (int $max = 6000) => ['type' => 'string', 'maxLength' => $max];
        $object = fn (array $properties, array $required) => ['type' => 'object', 'additionalProperties' => false, 'properties' => $properties, 'required' => $required];
        $array = fn (array $items, int $max) => ['type' => 'array', 'items' => $items, 'maxItems' => $max];
        $properties = ['questions' => $array($text(300), 2), 'insufficient_information' => ['type' => 'boolean'], 'citations' => $array($object(['handle' => $text(200), 'excerpt' => $text(1000)], ['handle', 'excerpt']), 16)];
        $properties += match ($purpose) {
            'capture' => ['draft' => OperationCatalog::get('create_capture')['input_schema']['properties']['draft']],
            'mapping' => ['mapping' => $array($object(['requirement' => $text(1000), 'source_ids' => $array(['type' => 'string', 'format' => 'uuid'], 30), 'reason' => $text(2000), 'gap' => $text(2000)], ['requirement', 'source_ids', 'reason', 'gap']), 20)],
            'feedback' => ['feedback' => $object(array_fill_keys(['specificity', 'structure', 'consistency', 'clarity'], $text(2000)), ['specificity', 'structure', 'consistency', 'clarity'])],
            'export' => ['translations' => $array($object(['key' => $text(200), 'text' => $text(20000)], ['key', 'text']), 1000)],
            default => ['text' => $text(12000)],
        };

        return $object($properties, array_keys($properties));
    }

    public static function providerSchema(array $schema): array
    {
        $result = [];
        foreach ($schema as $key => $value) {
            if ($key === 'type') {
                $types = (array) $value;
                $result['type'] = strtoupper(current(array_diff($types, ['null'])) ?: 'STRING');
                if (in_array('null', $types, true)) {
                    $result['nullable'] = true;
                }
            } elseif ($key === 'properties') {
                foreach ($value as $name => $child) {
                    $result['properties'][$name] = self::providerSchema($child);
                }
            } elseif ($key === 'items') {
                $result['items'] = self::providerSchema($value);
            } elseif ($key === 'enum') {
                $result[$key] = array_values(array_filter($value, fn ($item) => $item !== null));
            } elseif (in_array($key, ['required', 'description'], true)) {
                $result[$key] = $value;
            }
        }

        return $result;
    }

    public function validate(array $output, array $context): array
    {
        try {
            Input::schema($output, self::schema($context['purpose']));
        } catch (\Throwable) {
            throw new AgentFailure('invalid_response');
        }
        $sources = array_column($context['sources'], null, 'handle');
        $citations = [];
        foreach ($output['citations'] as $citation) {
            $source = $sources[$citation['handle']] ?? null;
            if (! $source || trim($citation['excerpt']) === '' || ! str_contains($source['text'], $citation['excerpt'])) {
                throw new AgentFailure('invalid_citation');
            }$citations[] = array_diff_key($source, ['text' => true]) + ['excerpt' => $citation['excerpt']];
        }
        if (! $output['insufficient_information'] && $context['sources'] && ! $citations && $context['purpose'] !== 'export') {
            throw new AgentFailure('invalid_citation');
        }
        if ($context['purpose'] === 'capture') {
            try {
                Input::occurrence($output['draft']['occurrence'] ?? null);
            } catch (\Throwable) {
                throw new AgentFailure('invalid_response');
            }
        }
        if ($context['purpose'] === 'mapping') {
            $allowed = array_filter(array_column($context['sources'], 'experience_id'));
            $cited = array_filter(array_column($citations, 'experience_id'));
            foreach ($output['mapping'] as $mapping) {
                if (array_diff($mapping['source_ids'], $allowed) || array_diff($mapping['source_ids'], $cited)) {
                    throw new AgentFailure('invalid_citation');
                }
                if ($mapping['gap'] !== '') {
                    $output['insufficient_information'] = true;
                }
            }
        }
        $prose = $output['questions'];
        if (isset($output['text'])) {
            $prose[] = $output['text'];
        }
        if (isset($output['draft'])) {
            foreach (['personal_action', 'result', 'validation', 'remaining_work'] as $field) {
                if (! empty($output['draft'][$field])) {
                    $prose[] = $output['draft'][$field];
                }
            }
        }
        foreach ($output['mapping'] ?? [] as $mapping) {
            $prose[] = $mapping['reason'];
            if ($mapping['gap'] !== '') {
                $prose[] = $mapping['gap'];
            }
        }
        foreach ($output['feedback'] ?? [] as $value) {
            $prose[] = $value;
        }
        if (($context['language'] ?? 'en') !== 'en') {
            $pattern = $context['language'] === 'ja' ? '/[\p{Hiragana}\p{Katakana}]/u' : '/\p{Han}/u';
            foreach ($prose as $text) {
                if (mb_strlen($text) > 12 && ! preg_match($pattern, $text)) {
                    throw new AgentFailure('invalid_language');
                }
            }
        }
        if ($context['purpose'] === 'export') {
            $keys = array_column($context['fields'], 'key');
            $translations = array_column($output['translations'], 'text', 'key');
            if (count($output['translations']) !== count($keys) || count($translations) !== count($keys) || array_diff($keys, array_keys($translations))) {
                throw new AgentFailure('invalid_response');
            }
            foreach ($context['fields'] as $field) {
                preg_match_all('/\b\d+(?:\.\d+)?\b/u', $field['text'], $numbers);
                foreach (array_unique($numbers[0]) as $number) {
                    if (! str_contains($translations[$field['key']], $number)) {
                        throw new AgentFailure('invalid_translation');
                    }
                }
            }
        }
        $output['citations'] = $citations;

        return $output;
    }
}
