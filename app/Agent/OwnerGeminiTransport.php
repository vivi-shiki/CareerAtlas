<?php

namespace App\Agent;

final class OwnerGeminiTransport implements OwnerModelTransport
{
    public function generate(array $contents, array $declarations, float $timeout, string $instructions): array
    {
        return app(GeminiTransport::class)->generateWithInstructions($contents, $declarations, $timeout, $instructions, 4000);
    }
}
