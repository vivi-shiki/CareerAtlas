<?php

return [
    'enabled' => (bool) env('AI_ENABLED', false),
    // Owner drafts require a separate explicit launch decision; never inherit public-page access.
    'owner_allowlist' => env('AI_OWNER_ALLOWLIST', ''),
    'driver' => env('AI_DRIVER', 'gemini'),
    'model' => env('AI_MODEL', ''),
    'key' => env('GEMINI_API_KEY', ''),
    // Optional launch restriction. An empty list disables questions for every publication.
    'publication_allowlist' => env('AI_PUBLICATION_ALLOWLIST', '*'),
    'deadline_seconds' => 45,
    'max_tools' => 4,
    'max_steps' => 4,
    'max_input' => 2000,
    'max_output_tokens' => 2000,
    'session_limit' => 10,
    'ip_limit' => 30,
];
