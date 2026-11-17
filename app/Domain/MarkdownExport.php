<?php

namespace App\Domain;

use App\Support\Localize;
use Illuminate\Http\Request;

final class MarkdownExport
{
    public function fields(array $portfolio): array
    {
        $fields = [];
        $add = function (string $key, string $label, mixed $value, string $version = '') use (&$fields) {
            if ($value === null || $value === '' || $value === []) {
                return;
            }
            $fields[] = ['key' => $key, 'label' => $label, 'text' => is_string($value) ? $value : json_encode($value, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES), 'source_version' => $version];
        };
        foreach (['display_name' => 'Display name', 'direction' => 'Career direction', 'biography' => 'Bio', 'contact_links' => 'Contact links', 'preferred_language' => 'Preferred language'] as $key => $label) {
            $add('profile.'.$key, $label, $portfolio['profile'][$key] ?? null);
        }
        $add('introduction', 'Introduction', $portfolio['introduction'] ?? '');
        foreach ($portfolio['items'] as $item) {
            foreach (['title' => 'Project title', 'type' => 'Project type', 'role' => 'Role', 'background' => 'Background', 'team_outcome' => 'Team outcome', 'occurrence' => 'Project dates'] as $key => $label) {
                $add($item['id'].'.project.'.$key, $label, $item['project'][$key] ?? null, $item['record_version_id']);
            }
            foreach (['title' => 'Experience title', 'record_type' => 'Record type', 'occurrence' => 'Occurrence date', 'personal_action' => 'My contribution', 'result' => 'Result', 'validation' => 'Validation', 'remaining_work' => 'Remaining work', 'skills' => 'Skills', 'evidence' => 'Evidence'] as $key => $label) {
                $add($item['id'].'.'.$key, $label, $item[$key] ?? null, $item['record_version_id']);
            }
        }

        return $fields;
    }

    public function render(array $fields, string $language, array $portfolio): string
    {
        $request = Request::create('/');
        $request->headers->set('Accept-Language', $language);
        $label = fn ($text) => Localize::message($text, $request);
        // Escape HTML, Markdown links/images and fenced-code delimiters in user text.
        $escape = fn ($text) => str_replace(['&', '<', '>', '\\', '`', '[', ']', '*', '_', '#'], ['&amp;', '&lt;', '&gt;', '\\\\', '\\`', '\\[', '\\]', '\\*', '\\_', '\\#'], $text);
        $markdown = '# '.$label('Public portfolio')."\n\n".$label('Version').': '.$portfolio['version']."\n\n";
        foreach ($fields as $field) {
            $markdown .= '## '.$label($field['label'])."\n\n".$escape($field['text'])."\n\n";
            if ($field['source_version']) {
                $markdown .= $label('Source version').': `'.$field['source_version']."`\n\n";
            }
        }

        return $markdown;
    }
}
