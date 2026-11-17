<?php

namespace App\Support;

use Illuminate\Http\Request;

/** Presentation only: shared catalogs never change domain values or stored content. */
final class Localize
{
    public static function message(string $message, Request $request): string
    {
        $locale = 'en';
        foreach ($request->getLanguages() as $language) {
            $base = strtolower(explode('_', str_replace('-', '_', $language))[0]);
            if (in_array($base, ['en', 'ja', 'zh'], true)) {
                $locale = $base === 'zh' ? 'zh-CN' : $base;
                break;
            }
        }
        if ($locale === 'en') {
            return $message;
        }
        $catalog = json_decode(file_get_contents(resource_path("js/i18n/$locale.json")), true, flags: JSON_THROW_ON_ERROR);
        if (isset($catalog[$message])) {
            return $catalog[$message];
        }
        foreach ($catalog as $key => $translation) {
            if (! str_contains($key, '{field}')) {
                continue;
            }
            preg_match_all('/\{(\w+)\}/', $key, $names);
            $pattern = preg_replace('/\\\\\{\w+\\\\\}/', '(.+?)', preg_quote($key, '/'));
            if (preg_match('/^'.$pattern.'$/u', $message, $values)) {
                $parameters = [];
                foreach ($names[1] as $index => $name) {
                    $value = $values[$index + 1];
                    if ($name === 'field') {
                        $label = ['name' => 'Display name', 'email' => 'Email', 'password' => 'Password', 'question' => 'Your question', 'request id' => 'Request ID'][$value] ?? $value;
                        $value = $catalog[$label] ?? $label;
                    }
                    $parameters['{'.$name.'}'] = $value;
                }

                return strtr($translation, $parameters);
            }
        }

        return $message;
    }
}
