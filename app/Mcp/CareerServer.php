<?php

namespace App\Mcp;

use App\Domain\OperationCatalog;
use Laravel\Mcp\Server;

final class CareerServer extends Server
{
    protected string $name = 'CareerAtlas Owner Operations';

    protected string $version = '0.1.0';

    protected string $instructions = 'Authenticated owner operations for explicit development workflows. Publication is an explicit mutation. Public portfolio agents must never receive these mutation tools.';

    public int $defaultPaginationLength = 50;

    protected function boot(): void
    {
        foreach (OperationCatalog::all() as $name => $definition) {
            $this->tools[] = new OperationTool($name, $definition);
        }
    }
}
