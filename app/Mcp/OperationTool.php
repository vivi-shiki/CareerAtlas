<?php

namespace App\Mcp;

use App\Domain\DomainError;
use App\Domain\OperationDispatcher;
use Illuminate\Validation\ValidationException;
use Laravel\Mcp\Request;
use Laravel\Mcp\Response;
use Laravel\Mcp\ResponseFactory;
use Laravel\Mcp\Server\Tool;
use Symfony\Component\HttpKernel\Exception\HttpExceptionInterface;

final class OperationTool extends Tool
{
    public function __construct(string $name, private readonly array $definition)
    {
        $this->name = $name;
        $this->description = $definition['description'];
    }

    public function toArray(): array
    {
        $schema = $this->definition['input_schema'];
        if (! $schema['properties']) {
            $schema['properties'] = (object) [];
        }

        return ['name' => $this->name, 'description' => $this->description, 'inputSchema' => $schema, 'annotations' => [
            'readOnlyHint' => $this->definition['read_only'], 'destructiveHint' => str_starts_with($this->name, 'delete_') || str_starts_with($this->name, 'revoke_'), 'openWorldHint' => false]];
    }

    public function handle(Request $request, OperationDispatcher $dispatcher): Response|ResponseFactory
    {
        try {
            return Response::structured(['data' => $dispatcher->execute($request->user(), $this->name, $request->all())]);
        } catch (ValidationException $e) {
            return Response::error(json_encode(['code' => 'validation_error', 'errors' => $e->errors()], JSON_THROW_ON_ERROR));
        } catch (DomainError $e) {
            return Response::error(json_encode(['code' => $e->errorCode, 'message' => $e->getMessage()], JSON_THROW_ON_ERROR));
        } catch (HttpExceptionInterface $e) {
            return Response::error(json_encode(['code' => 'unavailable', 'message' => 'The requested resource is unavailable.'], JSON_THROW_ON_ERROR));
        }
    }
}
