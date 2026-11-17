<?php

namespace App\Domain;

final class OperationCatalog
{
    public static function all(): array
    {
        return json_decode(file_get_contents(base_path('contracts/operations.json')), true, 512, JSON_THROW_ON_ERROR)
            + json_decode(file_get_contents(base_path('contracts/operations.p1.json')), true, 512, JSON_THROW_ON_ERROR);
    }

    public static function get(string $operation): array
    {
        $catalog = self::all();
        abort_unless(isset($catalog[$operation]), 404);

        return $catalog[$operation];
    }
}
