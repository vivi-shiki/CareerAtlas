<?php

namespace App\Agent;

use RuntimeException;

final class AgentFailure extends RuntimeException
{
    public function __construct(public readonly string $reason)
    {
        parent::__construct($reason);
    }
}
