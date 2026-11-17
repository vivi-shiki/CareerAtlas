<?php

namespace App\Domain;

use DateTimeImmutable;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

final class Input
{
    public static function fail(string $field, string $message): never
    {
        throw ValidationException::withMessages([$field => [$message]]);
    }

    public static function schema(mixed $value, array $schema, string $path = ''): void
    {
        $types = (array) ($schema['type'] ?? []);
        $valid = false;
        foreach ($types as $type) {
            $valid = $valid || match ($type) {
                'null' => $value === null, 'string' => is_string($value), 'integer' => is_int($value),
                'boolean' => is_bool($value), 'array' => is_array($value) && array_is_list($value),
                'object' => is_array($value) && ($value === [] || ! array_is_list($value)), default => false,
            };
        }
        if (! $valid) {
            self::fail($path ?: 'input', 'Invalid value type.');
        }
        if ($value === null) {
            return;
        }
        if (isset($schema['enum']) && ! in_array($value, $schema['enum'], true)) {
            self::fail($path, 'Choose a supported value.');
        }
        if (is_string($value)) {
            if (mb_strlen($value) > ($schema['maxLength'] ?? PHP_INT_MAX) || mb_strlen($value) < ($schema['minLength'] ?? 0)) {
                self::fail($path, 'Invalid text length.');
            }
            if (($schema['format'] ?? null) === 'uuid' && ! Str::isUuid($value)) {
                self::fail($path, 'Invalid identifier.');
            }
        }
        if (is_int($value) && ($value < ($schema['minimum'] ?? PHP_INT_MIN) || $value > ($schema['maximum'] ?? PHP_INT_MAX))) {
            self::fail($path, 'Value is out of range.');
        }
        if (in_array('object', $types, true)) {
            foreach ($schema['required'] ?? [] as $key) {
                if (! array_key_exists($key, $value)) {
                    self::fail($path ? "$path.$key" : $key, 'This field is required.');
                }
            }
            foreach ($value as $key => $item) {
                $child = $path ? "$path.$key" : (string) $key;
                if (! isset($schema['properties'][$key])) {
                    self::fail($child, 'This field is not accepted.');
                }
                self::schema($item, $schema['properties'][$key], $child);
            }
        } elseif (in_array('array', $types, true)) {
            if (count($value) < ($schema['minItems'] ?? 0) || count($value) > ($schema['maxItems'] ?? PHP_INT_MAX)) {
                self::fail($path, 'Invalid number of items.');
            }
            if (($schema['uniqueItems'] ?? false) && count(array_unique(array_map('serialize', $value))) !== count($value)) {
                self::fail($path, 'Duplicate items are not allowed.');
            }
            foreach ($value as $i => $item) {
                self::schema($item, $schema['items'], "$path.$i");
            }
        }
    }

    public static function occurrence(?array $value): void
    {
        if ($value === null) {
            return;
        }
        $start = self::date($value['start'], $value['precision']);
        if (! empty($value['end'])) {
            if (empty($value['end_precision'])) {
                self::fail('occurrence.end_precision', 'Choose the end precision.');
            }
            $end = self::date($value['end'], $value['end_precision'], true);
            if (($value['ongoing'] ?? false) || $end < $start) {
                self::fail('occurrence', 'The date range is inconsistent.');
            }
        } elseif (! empty($value['end_precision'])) {
            self::fail('occurrence.end', 'Provide an end date.');
        }
    }

    private static function date(string $value, string $precision, bool $end = false): string
    {
        $format = match ($precision) {
            'year' => 'Y','month' => 'Y-m','day' => 'Y-m-d'
        };
        $parsed = DateTimeImmutable::createFromFormat('!'.$format, $value);
        if (! $parsed || $parsed->format($format) !== $value) {
            self::fail('occurrence', 'The date must match its precision and be a valid calendar date.');
        }
        if ($end && $precision === 'year') {
            $parsed = $parsed->setDate((int) $parsed->format('Y'), 12, 31);
        }
        if ($end && $precision === 'month') {
            $parsed = $parsed->modify('last day of this month');
        }

        return $parsed->format('Y-m-d');
    }

    public static function url(?string $url, string $field = 'url'): void
    {
        if (! $url || ! filter_var($url, FILTER_VALIDATE_URL)) {
            self::fail($field, 'Enter an HTTP or HTTPS URL.');
        }
        $parts = parse_url($url);
        if (! in_array(strtolower($parts['scheme'] ?? ''), ['http', 'https']) || isset($parts['user']) || isset($parts['pass'])) {
            self::fail($field, 'Only HTTP/HTTPS URLs without credentials are accepted.');
        }
    }

    public static function nonempty(array $input, array $fields): void
    {
        $errors = [];
        foreach ($fields as $field) {
            if (! isset($input[$field]) || (is_string($input[$field]) && trim($input[$field]) === '')) {
                $errors[$field] = ['This field is required.'];
            }
        }
        if ($errors) {
            throw ValidationException::withMessages($errors);
        }
    }
}
