<?php

namespace Tests\Feature;

use App\Agent\AgentFailure;
use App\Agent\OwnerAgentRunner;
use App\Agent\OwnerModelTransport;
use App\Agent\OwnerOutput;
use App\Domain\OperationDispatcher;
use App\Jobs\RunOwnerAgent;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Queue;
use Illuminate\Support\Str;
use Tests\Fakes\OwnerModel;
use Tests\TestCase;

class OwnerAgentTest extends TestCase
{
    use RefreshDatabase;

    private User $owner;

    protected function setUp(): void
    {
        parent::setUp();
        $this->owner = User::factory()->create();
        $this->actingAs($this->owner);
        config(['agent.enabled' => true, 'agent.driver' => 'fake', 'agent.owner_allowlist' => '*']);
        Queue::fake();
        Http::preventStrayRequests();
    }

    private function op(string $name, array $input = []): array
    {
        return app(OperationDispatcher::class)->execute($this->owner, $name, $input);
    }

    private function fixture(): array
    {
        $project = $this->op('create_project', ['title' => 'Fictional Atlas Notes', 'type' => 'personal', 'role' => 'Developer']);
        $record = $this->op('create_experience', ['project_id' => $project['id'], 'title' => 'Keyboard search', 'record_type' => 'work_contribution', 'occurrence' => ['start' => '2026-10', 'precision' => 'month'], 'personal_action' => 'I added 12 keyboard tests.', 'private_notes' => 'PRIVATE_OWNER_AI_SENTINEL']);
        $record = $this->op('confirm_experience', ['id' => $record['id'], 'expected_version' => $record['version']]);
        $capture = $this->op('create_capture', ['project_id' => $project['id'], 'notes' => 'I added 12 keyboard tests.']);
        $preparation = $this->op('create_preparation', ['topic' => 'Frontend', 'requirements' => 'Explain accessible interactions.', 'experience_ids' => [$record['id']]]);

        return [$project, $record, $capture, $preparation];
    }

    private function start(string $purpose, array $subject, array $extra = []): array
    {
        return $this->op('start_owner_ai', ['purpose' => $purpose, 'subject_id' => $subject['id'], 'expected_version' => $subject['version'], 'request_id' => (string) Str::uuid()] + $extra);
    }

    private function complete(array $run): array
    {
        app(OwnerAgentRunner::class)->execute($run['id']);
        $run = $this->op('get_owner_ai', ['id' => $run['id']]);
        $this->assertSame('completed', $run['state'], json_encode($run));

        return $run;
    }

    public function test_capture_reads_only_allowed_context_and_applies_a_private_draft_explicitly(): void
    {
        [$project,$record,$capture] = $this->fixture();
        $run = $this->start('capture', $capture);
        Queue::assertPushed(RunOwnerAgent::class);
        $stored = DB::table('owner_ai_runs')->where('id', $run['id'])->first();
        $this->assertStringNotContainsString('PRIVATE_OWNER_AI_SENTINEL', $stored->context);
        $run = $this->complete($run);
        $this->assertNotEmpty($run['output']['citations']);
        $this->assertCount(0, $this->op('get_capture', ['id' => $capture['id']])['draft']);
        $result = $this->op('apply_owner_ai', ['id' => $run['id']]);
        $this->assertSame('ai_assisted', $result['subject']['provenance']);
        $this->assertNull($result['subject']['applied_experience_id']);
        $this->assertDatabaseCount('experiences', 1);
        $this->assertDatabaseCount('publications', 0);
        $this->assertEquals($result, $this->op('apply_owner_ai', ['id' => $run['id']]));
    }

    public function test_answer_mapping_introduction_feedback_and_export_have_separate_review_outputs(): void
    {
        [, $record,, $prep] = $this->fixture();
        $mapping = $this->complete($this->start('mapping', $prep));
        $this->assertNotEmpty($mapping['output']['mapping']);
        $answer = $this->complete($this->start('answer', $prep, ['question' => 'What did you contribute?']));
        $applied = $this->op('apply_owner_ai', ['id' => $answer['id']]);
        $this->assertSame('draft', $applied['subject']['state']);
        $this->assertCount(1, $this->op('get_answer_history', ['id' => $applied['subject']['id']]));
        $practice = $this->op('start_practice', ['preparation_id' => $prep['id'], 'questions' => ['What did you contribute?']]);
        $practice = $this->op('answer_practice', ['id' => $practice['id'], 'expected_version' => $practice['version'], 'answer' => 'I added 12 keyboard tests.']);
        $feedback = $this->complete($this->start('feedback', $practice));
        $this->assertEqualsCanonicalizing(['specificity', 'structure', 'consistency', 'clarity'], array_keys($feedback['output']['feedback']));
        $applied = $this->op('apply_owner_ai', ['id' => $feedback['id']]);
        $this->assertFalse($applied['subject']['retained']);
        $this->assertNotNull($applied['subject']['attempts'][0]['feedback']);
        $opportunity = $this->op('create_opportunity', ['title' => 'Fictional opportunity', 'direction' => 'Frontend', 'selection' => ['profile_fields' => [], 'items' => [['experience_id' => $record['id'], 'fields' => ['title'], 'project_fields' => []]]]]);
        $introRun = $this->start('introduction', $opportunity);
        $context = DB::table('owner_ai_runs')->where('id', $introRun['id'])->value('context');
        $this->assertStringNotContainsString('I added 12 keyboard tests.', $context);
        $intro = $this->complete($introRun);
        $applied = $this->op('apply_owner_ai', ['id' => $intro['id']]);
        $this->assertFalse($applied['subject']['introduction_confirmed']);
        $opportunity = $applied['subject'];
        $preview = $this->op('preview_opportunity', ['id' => $opportunity['id']]);
        $opportunity = $this->op('publish_opportunity', ['id' => $opportunity['id'], 'expected_version' => $opportunity['version'], 'preview_digest' => $preview['digest'], 'idempotency_key' => (string) Str::uuid()]);
        $export = $this->complete($this->start('export', $opportunity));
        $this->assertCount(1, $export['output']['translations']);
        $this->assertDatabaseCount('publications', 1);
    }

    public function test_same_request_is_idempotent_and_mismatched_retry_is_rejected(): void
    {
        [,,$capture] = $this->fixture();
        $input = ['purpose' => 'capture', 'subject_id' => $capture['id'], 'expected_version' => $capture['version'], 'request_id' => (string) Str::uuid()];
        $run = $this->op('start_owner_ai', $input);
        $this->assertSame($run['id'], $this->op('start_owner_ai', $input)['id']);
        Queue::assertPushed(RunOwnerAgent::class, 1);
        $this->postJson('/api/operations/start_owner_ai', $input + ['question' => 'Changed request'])->assertConflict();
        $this->postJson('/api/operations/start_owner_ai', array_replace($input, ['request_id' => (string) Str::uuid()]))->assertConflict();
    }

    public function test_foreign_disabled_cancelled_expired_and_stale_runs_cannot_produce_or_apply_output(): void
    {
        [, $record,$capture,$prep] = $this->fixture();
        config(['agent.owner_allowlist' => '']);
        $this->postJson('/api/operations/start_owner_ai', ['purpose' => 'capture', 'subject_id' => $capture['id'], 'expected_version' => 1, 'request_id' => (string) Str::uuid()])->assertStatus(503);
        config(['agent.owner_allowlist' => '*']);
        $run = $this->start('capture', $capture);
        $this->actingAs(User::factory()->create());
        $this->postJson('/api/operations/get_owner_ai', ['id' => $run['id']])->assertNotFound();
        $this->postJson('/api/operations/apply_owner_ai', ['id' => $run['id']])->assertNotFound();
        $this->actingAs($this->owner);
        $this->op('cancel_owner_ai', ['id' => $run['id']]);
        app(OwnerAgentRunner::class)->execute($run['id']);
        $this->assertSame('cancelled', $this->op('get_owner_ai', ['id' => $run['id']])['state']);
        $run = $this->start('answer', $prep, ['question' => 'What did you contribute?']);
        $this->op('update_experience', ['id' => $record['id'], 'expected_version' => $record['version'], 'personal_action' => 'Changed contribution.']);
        app(OwnerAgentRunner::class)->execute($run['id']);
        $this->assertSame('failed', $this->op('get_owner_ai', ['id' => $run['id']])['state']);
        $this->postJson('/api/operations/apply_owner_ai', ['id' => $run['id']])->assertConflict();
        $run = $this->start('capture', $capture);
        $this->travel(46)->seconds();
        app(OwnerAgentRunner::class)->execute($run['id']);
        $this->assertSame('expired', $this->op('get_owner_ai', ['id' => $run['id']])['state']);
    }

    public function test_invalid_citations_questions_provider_failure_and_late_cancellation_are_rejected(): void
    {
        [,,$capture] = $this->fixture();
        foreach (['citation', 'questions', 'failure', 'cancel'] as $mode) {
            $model = new OwnerModel;
            $model->fault = $mode;
            $this->app->instance(OwnerModelTransport::class, $model);
            $run = $this->start('capture', $capture);
            $model->runId = $run['id'];
            app(OwnerAgentRunner::class)->execute($run['id']);
            $result = $this->op('get_owner_ai', ['id' => $run['id']]);
            $this->assertContains($result['state'], ['failed', 'cancelled']);
            $this->assertNull($result['output']);
        }
    }

    public function test_completed_proposals_are_invalidated_by_later_changes_and_never_apply_stale_text(): void
    {
        [, $record,, $prep] = $this->fixture();
        $run = $this->complete($this->start('answer', $prep, ['question' => 'What did you contribute?']));
        $this->op('revoke_experience_access', ['id' => $record['id'], 'expected_version' => $record['version']]);
        $read = $this->op('get_owner_ai', ['id' => $run['id']]);
        $this->assertSame('failed', $read['state']);
        $this->assertNull($read['output']);
        $this->postJson('/api/operations/apply_owner_ai', ['id' => $run['id']])->assertConflict();
        $this->assertDatabaseCount('prepared_answers', 0);
    }

    public function test_deadlines_are_absolute_instants_and_foreign_subjects_cannot_be_queued(): void
    {
        [,,$capture] = $this->fixture();
        $this->freezeTime();
        $run = $this->start('capture', $capture);
        $this->assertEqualsWithDelta(45, now()->diffInSeconds(Carbon::parse($run['deadline_at']), false), 1);
        $foreign = User::factory()->create();
        $this->actingAs($foreign);
        $this->postJson('/api/operations/start_owner_ai', ['purpose' => 'capture', 'subject_id' => $capture['id'], 'expected_version' => 1, 'request_id' => (string) Str::uuid()])->assertNotFound();
        $this->assertDatabaseCount('owner_ai_runs', 1);
    }

    public function test_export_rejects_dropped_numbers_and_extra_fields(): void
    {
        $validator = app(OwnerOutput::class);
        $context = ['purpose' => 'export', 'sources' => [], 'fields' => [['key' => 'example', 'text' => 'I added 12 tests.']]];
        foreach ([
            [['key' => 'example', 'text' => 'I added tests.']],
            [['key' => 'example', 'text' => 'I added 12 tests.'], ['key' => 'private_notes', 'text' => 'Injected']],
        ] as $translations) {
            try {
                $validator->validate(['translations' => $translations, 'questions' => [], 'citations' => [], 'insufficient_information' => false], $context);
                $this->fail('Invalid translation accepted.');
            } catch (AgentFailure $failure) {
                $this->assertContains($failure->reason, ['invalid_response', 'invalid_translation']);
            }
        }
    }

    public function test_tool_round_trip_preserves_empty_argument_objects_and_rejects_wrong_language(): void
    {
        [,,$capture] = $this->fixture();
        $model = new OwnerModel;
        $this->app->instance(OwnerModelTransport::class, $model);
        $this->complete($this->start('capture', $capture));
        $this->assertInstanceOf(\stdClass::class, $model->requests[1]['contents'][1]['parts'][0]['functionCall']['args']);
        $schema = OwnerOutput::providerSchema(OwnerOutput::schema('capture'));
        $this->assertNotContains(null, $schema['properties']['draft']['properties']['record_type']['enum']);
        try {
            app(OwnerOutput::class)->validate(['text' => 'An untranslated English answer remains here.', 'questions' => [], 'citations' => [], 'insufficient_information' => true], ['purpose' => 'answer', 'language' => 'ja', 'sources' => []]);
            $this->fail('An untranslated answer was accepted.');
        } catch (AgentFailure $failure) {
            $this->assertSame('invalid_language', $failure->reason);
        }
    }
}
