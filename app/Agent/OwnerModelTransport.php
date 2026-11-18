<?php

namespace App\Agent;

interface OwnerModelTransport
{
    public function generate(array $contents, array $declarations, float $timeout, string $instructions): array;
}
