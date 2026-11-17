<?php

namespace Tests\Fakes;

use App\Agent\AgentFailure;
use App\Agent\ModelTransport;

/** Explicit testing driver only. Never selected by request headers or public input in production. */
final class PortfolioModel implements ModelTransport
{
    public function generate(array $contents, array $declarations, float $timeout): array
    {
        $question = json_decode($contents[0]['parts'][0]['text'], true)['question'];
        if (str_contains($question, '[test:failure]')) {
            throw new AgentFailure('provider_error');
        }
        if (str_contains($question, '[test:slow]')) {
            usleep(2500000);
        }
        if (count($contents) === 1) {
            return $this->call('search_experiences', ['query' => 'personal_action']);
        }
        $sources = $contents[array_key_last($contents)]['parts'][0]['functionResponse']['response']['sources'] ?? [];
        $source = $sources[0] ?? null;
        if (str_contains($question, 'missing')) {
            $source = null;
        }

        return $this->call('finish_answer', ['answer' => $source ? 'The published personal action is: '.$source['text'] : 'The published records do not establish that experience.',
            'insufficient_information' => ! $source,
            'citations' => $source ? [['handle' => $source['handle'], 'excerpt' => $source['text']]] : [],
            'follow_up_questions' => ['What personal actions are documented?']]);
    }

    private function call(string $name, array $args): array
    {
        return ['content' => ['role' => 'model', 'parts' => [['functionCall' => ['name' => $name, 'args' => $args]]]], 'usage' => ['totalTokenCount' => 25]];
    }
}
