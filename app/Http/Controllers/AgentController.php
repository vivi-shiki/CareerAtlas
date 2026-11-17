<?php

namespace App\Http\Controllers;

use App\Agent\AgentService;
use Illuminate\Http\Request;
use Illuminate\Support\Str;

final class AgentController
{
    public function __construct(private AgentService $agent) {}

    private function principal(Request $request): string
    {
        if (! $request->session()->has('agent_principal')) {
            $request->session()->put('agent_principal', (string) Str::uuid());
        }

        return hash_hmac('sha256', $request->session()->get('agent_principal'), config('app.key'));
    }

    public function status(string $slug): array
    {
        $this->agent->scope($slug);

        return ['data' => ['available' => $this->agent->available($slug), 'max_input' => config('agent.max_input')]];
    }

    public function create(Request $request, string $slug): array
    {
        return ['data' => $this->agent->create($slug, $this->principal($request), $request->ip())];
    }

    public function history(Request $request, string $slug, string $conversation): array
    {
        return ['data' => $this->agent->history($conversation, $this->principal($request), $slug)];
    }

    public function submit(Request $request, string $slug, string $conversation): array
    {
        $input = $request->validate(['request_id' => 'required|uuid', 'question' => 'required|string|max:'.config('agent.max_input')]);

        return ['data' => $this->agent->submit($conversation, $this->principal($request), $slug, $request->ip(), $input)];
    }

    public function read(Request $request, string $slug, string $run): array
    {
        return ['data' => $this->agent->run($run, $this->principal($request), $slug)];
    }

    public function cancel(Request $request, string $slug, string $run): array
    {
        return ['data' => $this->agent->run($run, $this->principal($request), $slug, true)];
    }
}
