<?php

namespace App\Http\Controllers;

use App\Domain\OperationDispatcher;
use Illuminate\Http\Request;

final class OperationController
{
    public function __invoke(Request $request, string $operation, OperationDispatcher $dispatcher)
    {
        return response()->json(['data' => $dispatcher->execute($request->user(), $operation, $request->all())]);
    }
}
