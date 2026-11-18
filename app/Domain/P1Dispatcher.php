<?php

namespace App\Domain;

use App\Agent\OwnerAgentService;

final class P1Dispatcher
{
    public function execute(int $owner, string $operation, array $input): array
    {
        $capture = app(CaptureService::class);
        $preparation = app(PreparationService::class);
        $opportunity = app(OpportunityService::class);

        return match ($operation) {
            'owner_ai_status' => ['available' => app(OwnerAgentService::class)->available($owner)],
            'start_owner_ai' => app(OwnerAgentService::class)->start($owner, $input),
            'get_owner_ai', 'cancel_owner_ai' => app(OwnerAgentService::class)->read($owner, $input['id'], $operation === 'cancel_owner_ai'),
            'apply_owner_ai' => app(OwnerAgentService::class)->apply($owner, $input['id']),
            'create_capture', 'update_capture' => $capture->save($owner, $input),
            'get_capture' => $capture->get($owner, $input['id']),
            'list_captures' => $capture->listing($owner),
            'find_capture_duplicates' => $capture->duplicates($owner, $input),
            'apply_capture' => $capture->apply($owner, $input),
            'get_experience_history' => app(HistoryService::class)->get($owner, $input['id']),
            'create_preparation', 'update_preparation' => $preparation->save($owner, $input),
            'get_preparation' => $preparation->get($owner, $input['id']),
            'list_preparations' => $preparation->listing($owner),
            'create_prepared_answer', 'update_prepared_answer' => $preparation->saveAnswer($owner, $input),
            'get_prepared_answer' => $preparation->answer($owner, $input['id']),
            'confirm_prepared_answer' => $preparation->confirmAnswer($owner, $input),
            'get_answer_history' => $preparation->history($owner, $input['id']),
            'start_practice' => $preparation->startPractice($owner, $input),
            'get_practice' => $preparation->practice($owner, $input['id']),
            'answer_practice', 'next_practice', 'stop_practice', 'save_practice', 'discard_practice' => $preparation->changePractice($owner, explode('_', $operation)[0], $input),
            'create_opportunity', 'update_opportunity' => $opportunity->save($owner, $input),
            'get_opportunity' => $opportunity->get($owner, $input['id']),
            'list_opportunities' => $opportunity->listing($owner),
            'confirm_introduction' => $opportunity->confirmIntroduction($owner, $input),
            'preview_opportunity' => $opportunity->preview($owner, $input['id']),
            'publish_opportunity' => $opportunity->publish($owner, $input),
            'revoke_opportunity', 'archive_opportunity', 'restore_opportunity' => $opportunity->lifecycle($owner, $input, explode('_', $operation)[0]),
            'export_opportunity' => $opportunity->export($owner, $input),
            default => abort(404),
        };
    }
}
