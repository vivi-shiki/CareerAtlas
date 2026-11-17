<?php

namespace App\Agent;

interface ModelTransport
{
    /** Returns opaque model content and aggregate usage; never persists provider payloads. */
    public function generate(array $contents, array $declarations, float $timeout): array;
}
