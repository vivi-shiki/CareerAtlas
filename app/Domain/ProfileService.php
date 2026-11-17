<?php

namespace App\Domain;

use App\Models\User;
use Illuminate\Support\Facades\DB;

final class ProfileService
{
    public function get(int $owner): array
    {
        $row = DB::table('profiles')->where('user_id', $owner)->first();

        return $row ? Store::decode($row) : Store::insert('profiles', ['user_id' => $owner, 'display_name' => User::findOrFail($owner)->name]);
    }

    public function update(int $owner, array $input): array
    {
        $profile = $this->get($owner);
        Store::version($profile, $input['expected_version']);
        if (array_key_exists('display_name', $input)) {
            Input::nonempty($input, ['display_name']);
        }
        foreach ($input['contact_links'] ?? [] as $i => $link) {
            Input::nonempty($link, ['label']);
            Input::url($link['url'], "contact_links.$i.url");
        }
        unset($input['expected_version']);

        return Store::update('profiles', $profile['id'], $input + ['version' => $profile['version'] + 1]);
    }
}
