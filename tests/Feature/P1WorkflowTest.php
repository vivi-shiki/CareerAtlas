<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Str;
use Tests\TestCase;

class P1WorkflowTest extends TestCase
{
    use RefreshDatabase;

    private User $owner;

    protected function setUp(): void
    {
        parent::setUp();
        Http::preventStrayRequests();
        $this->owner = User::factory()->create();
        $this->actingAs($this->owner);
    }

    private function op(string $name, array $input = []): array
    {
        return $this->postJson('/api/operations/'.$name, $input)->assertOk()->json('data');
    }

    private function fixture(string $title = 'Fictional Search'): array
    {
        $project = $this->op('create_project', ['title' => $title, 'type' => 'personal', 'role' => 'Developer', 'background' => 'Fictional selected project.']);
        $experience = $this->op('create_experience', ['project_id' => $project['id'], 'title' => 'Keyboard search', 'record_type' => 'work_contribution', 'occurrence' => ['start' => '2026-10', 'precision' => 'month'], 'personal_action' => 'I implemented keyboard navigation.', 'private_notes' => 'PRIVATE_P1_SENTINEL']);
        $experience = $this->op('confirm_experience', ['id' => $experience['id'], 'expected_version' => $experience['version']]);

        return [$project, $experience];
    }

    private function selection(array ...$experiences): array
    {
        return ['profile_fields' => ['display_name'], 'items' => array_map(fn ($e) => ['experience_id' => $e['id'], 'fields' => ['title', 'personal_action', 'occurrence'], 'project_fields' => ['title', 'role']], $experiences)];
    }

    private function opportunity(array $selection, string $title = 'PRIVATE_OPPORTUNITY'): array
    {
        return $this->op('create_opportunity', ['title' => $title, 'direction' => 'PRIVATE_DIRECTION', 'requirements' => 'PRIVATE_REQUIREMENTS', 'selection' => $selection, 'target_language' => 'ja']);
    }

    private function publish(array $opportunity): array
    {
        $preview = $this->op('preview_opportunity', ['id' => $opportunity['id']]);

        return $this->op('publish_opportunity', ['id' => $opportunity['id'], 'expected_version' => $opportunity['version'], 'preview_digest' => $preview['digest'], 'idempotency_key' => (string) Str::uuid()]);
    }

    public function test_capture_drafts_apply_once_without_confirmation_or_publication(): void
    {
        [$project] = $this->fixture();
        $capture = $this->op('create_capture', ['project_id' => $project['id'], 'notes' => 'I implemented keyboard search.', 'target_language' => 'zh']);
        $capture = $this->op('update_capture', ['id' => $capture['id'], 'expected_version' => 1, 'draft' => ['title' => 'Keyboard navigation', 'personal_action' => 'I implemented keyboard navigation.', 'skill_suggestions' => ['Vue 3']]]);
        $duplicates = $this->op('find_capture_duplicates', ['id' => $capture['id']]);
        $this->assertNotEmpty($duplicates);
        $this->assertArrayNotHasKey('matching_percentage', $duplicates[0]);
        $input = ['id' => $capture['id'], 'expected_version' => $capture['version'], 'skill_names' => ['Vue 3']];
        $created = $this->op('apply_capture', $input);
        $this->assertSame('draft', $created['status']);
        $this->assertNull($created['confirmed_version_id']);
        $this->assertCount(1, $created['skills']);
        $this->assertSame($created['id'], $this->op('apply_capture', $input)['id']);
        $this->assertDatabaseCount('experiences', 2);
        $this->assertDatabaseCount('publications', 0);
        $this->postJson('/api/operations/update_capture', ['id' => $capture['id'], 'expected_version' => $capture['version'] + 1, 'notes' => 'Changed'])->assertConflict();
    }

    public function test_capture_merge_checks_versions_and_preserves_previous_confirmed_facts(): void
    {
        [$project, $experience] = $this->fixture();
        $capture = $this->op('create_capture', ['project_id' => $project['id'], 'notes' => 'I added focus recovery.', 'draft' => ['title' => '', 'personal_action' => 'I added focus recovery.']]);
        $base = ['id' => $capture['id'], 'expected_version' => 1, 'merge_id' => $experience['id']];
        $this->postJson('/api/operations/apply_capture', $base + ['merge_version' => 99])->assertConflict();
        $merged = $this->op('apply_capture', $base + ['merge_version' => $experience['version']]);
        $this->assertSame($experience['title'], $merged['title']);
        $this->assertSame('draft', $merged['status']);
        $this->assertSame($experience['confirmed_version_id'], $merged['confirmed_version_id']);
        $this->assertDatabaseCount('experiences', 1);
        $this->assertDatabaseCount('record_versions', 1);
        $history = $this->op('get_experience_history', ['id' => $experience['id']]);
        $this->assertGreaterThanOrEqual(3, count($history));
        $this->assertContains('personal_action', array_column($history[0]['differences'], 'field'));
    }

    public function test_history_is_immutable_owner_scoped_and_confirmation_is_idempotent(): void
    {
        [, $experience] = $this->fixture();
        $before = $this->op('get_experience_history', ['id' => $experience['id']]);
        $this->op('confirm_experience', ['id' => $experience['id'], 'expected_version' => 1]);
        $this->assertSame($before, $this->op('get_experience_history', ['id' => $experience['id']]));
        $this->op('update_experience', ['id' => $experience['id'], 'expected_version' => 1, 'personal_action' => 'Revised action']);
        $after = $this->op('get_experience_history', ['id' => $experience['id']]);
        $this->assertSame($before[0], $after[1]);
        $this->actingAs(User::factory()->create());
        $this->postJson('/api/operations/get_experience_history', ['id' => $experience['id']])->assertNotFound();
    }

    public function test_preparation_and_answer_versions_preserve_language_and_flag_changed_sources(): void
    {
        [$project, $experience] = $this->fixture();
        $prep = $this->op('create_preparation', ['topic' => 'Frontend role', 'company' => 'Fictional Company', 'target_language' => 'ja', 'answer_length' => 'short', 'requirements' => 'Vue and Laravel', 'source_url' => 'https://example.test/role', 'experience_ids' => [$experience['id']]]);
        $this->assertStringNotContainsString('PRIVATE_P1_SENTINEL', json_encode($prep['sources']));
        $answer = $this->op('create_prepared_answer', ['preparation_id' => $prep['id'], 'question' => 'What did you implement?', 'answer' => 'キーボード操作を実装しました。']);
        $answer = $this->op('confirm_prepared_answer', ['id' => $answer['id'], 'expected_version' => 1]);
        $this->assertSame('ja', $answer['target_language']);
        $this->op('update_project', ['id' => $project['id'], 'expected_version' => 1, 'role' => 'Revised role']);
        $this->assertCount(1, $this->op('get_prepared_answer', ['id' => $answer['id']])['source_changes']);
        $edited = $this->op('update_prepared_answer', ['id' => $answer['id'], 'expected_version' => $answer['version'], 'answer' => 'Edited draft']);
        $this->postJson('/api/operations/confirm_prepared_answer', ['id' => $answer['id'], 'expected_version' => $edited['version']])->assertConflict();
        $fresh = $this->op('update_prepared_answer', ['id' => $answer['id'], 'expected_version' => $edited['version'], 'experience_ids' => [$experience['id']]]);
        $this->op('confirm_prepared_answer', ['id' => $fresh['id'], 'expected_version' => $fresh['version']]);
        $versions = $this->op('get_answer_history', ['id' => $answer['id']]);
        $this->assertCount(5, $versions);
        $this->assertSame('キーボード操作を実装しました。', $versions[4]['payload']['answer']);
        Http::assertNothingSent();
    }

    public function test_preparation_validates_dates_urls_and_selected_source_ownership(): void
    {
        [, $experience] = $this->fixture();
        $this->postJson('/api/operations/create_preparation', ['topic' => '  '])->assertUnprocessable();
        $this->postJson('/api/operations/create_preparation', ['topic' => 'Role', 'interview_date' => '2026-02-30'])->assertUnprocessable();
        $this->postJson('/api/operations/create_preparation', ['topic' => 'Role', 'source_url' => 'javascript:alert(1)'])->assertUnprocessable();
        $this->actingAs(User::factory()->create());
        $this->postJson('/api/operations/create_preparation', ['topic' => 'Foreign', 'experience_ids' => [$experience['id']]])->assertNotFound();
        $this->assertDatabaseCount('preparations', 0);
    }

    public function test_practice_is_one_question_at_a_time_retryable_and_explicitly_retained_or_discarded(): void
    {
        [, $experience] = $this->fixture();
        $prep = $this->op('create_preparation', ['topic' => 'Role', 'experience_ids' => [$experience['id']]]);
        $practice = $this->op('start_practice', ['preparation_id' => $prep['id'], 'questions' => ['What did you build?', 'How did you validate it?']]);
        $this->postJson('/api/operations/next_practice', ['id' => $practice['id'], 'expected_version' => 1])->assertUnprocessable();
        $practice = $this->op('answer_practice', ['id' => $practice['id'], 'expected_version' => 1, 'answer' => 'Initial attempt']);
        $practice = $this->op('answer_practice', ['id' => $practice['id'], 'expected_version' => $practice['version'], 'answer' => 'Revised attempt']);
        $this->assertCount(1, $practice['attempts']);
        $practice = $this->op('next_practice', ['id' => $practice['id'], 'expected_version' => $practice['version']]);
        $this->assertSame(1, $practice['current_index']);
        $practice = $this->op('stop_practice', ['id' => $practice['id'], 'expected_version' => $practice['version']]);
        $this->postJson('/api/operations/answer_practice', ['id' => $practice['id'], 'expected_version' => $practice['version'], 'answer' => 'Late'])->assertConflict();
        $this->assertEmpty($this->op('get_preparation', ['id' => $prep['id']])['practice']);
        $practice = $this->op('save_practice', ['id' => $practice['id'], 'expected_version' => $practice['version']]);
        $this->assertTrue($practice['retained']);
        $this->assertCount(1, $this->op('get_preparation', ['id' => $prep['id']])['practice']);
        $this->op('discard_practice', ['id' => $practice['id'], 'expected_version' => $practice['version']]);
        $this->assertDatabaseCount('practice_sessions', 0);
        $this->assertDatabaseCount('experiences', 1);
        $this->assertDatabaseCount('prepared_answers', 0);
    }

    public function test_opportunity_pages_are_independent_ordered_and_exclude_private_metadata(): void
    {
        [, $a] = $this->fixture('Project A');
        [, $b] = $this->fixture('Project B');
        $first = $this->publish($this->opportunity($this->selection($a, $b), 'Private first'));
        $second = $this->publish($this->opportunity($this->selection($b), 'Private second'));
        $viewA = $this->getJson('/api/public/'.$first['publication']['slug'])->assertOk()->json('data');
        $viewB = $this->getJson('/api/public/'.$second['publication']['slug'])->assertOk()->json('data');
        $this->assertSame([$a['id'], $b['id']], array_column($viewA['items'], 'id'));
        $this->assertSame([$b['id']], array_column($viewB['items'], 'id'));
        foreach (['PRIVATE_', 'Private first', 'Private second', 'Project A'] as $private) {
            $this->assertStringNotContainsString($private, json_encode($viewB));
        }
        $this->assertEmpty($this->op('list_publications'));
        $this->op('revoke_opportunity', ['id' => $first['id'], 'expected_version' => $first['version']]);
        $this->getJson('/api/public/'.$first['publication']['slug'])->assertNotFound();
        $this->getJson('/api/public/'.$second['publication']['slug'])->assertOk();
        $this->getJson('/api/public/'.$second['publication']['slug'].'/experiences/'.$a['id'])->assertNotFound();
    }

    public function test_reviewed_introduction_snapshot_survives_edits_but_not_source_withdrawal(): void
    {
        [, $experience] = $this->fixture();
        $opportunity = $this->opportunity($this->selection($experience));
        $opportunity = $this->op('update_opportunity', ['id' => $opportunity['id'], 'expected_version' => 1, 'introduction' => 'I implemented keyboard navigation.', 'include_introduction' => true]);
        $this->postJson('/api/operations/preview_opportunity', ['id' => $opportunity['id']])->assertUnprocessable();
        $opportunity = $this->op('confirm_introduction', ['id' => $opportunity['id'], 'expected_version' => $opportunity['version']]);
        $published = $this->publish($opportunity);
        $this->getJson('/api/public/'.$published['publication']['slug'])->assertOk()->assertJsonPath('data.introduction', 'I implemented keyboard navigation.');
        $this->op('update_experience', ['id' => $experience['id'], 'expected_version' => 1, 'personal_action' => 'Changed action']);
        $this->getJson('/api/public/'.$published['publication']['slug'])->assertJsonPath('data.introduction', 'I implemented keyboard navigation.');
        $this->postJson('/api/operations/preview_opportunity', ['id' => $opportunity['id']])->assertConflict();
        $this->op('revoke_experience_access', ['id' => $experience['id'], 'expected_version' => 2]);
        $this->getJson('/api/public/'.$published['publication']['slug'])->assertJsonPath('data.introduction', '')->assertJsonCount(0, 'data.items');
        $export = $this->op('export_opportunity', ['id' => $opportunity['id']]);
        $this->assertStringNotContainsString('keyboard navigation', $export['markdown']);
    }

    public function test_opportunity_preview_conflicts_idempotency_and_separate_republication_path(): void
    {
        [, $experience] = $this->fixture();
        $opportunity = $this->opportunity($this->selection($experience));
        $preview = $this->op('preview_opportunity', ['id' => $opportunity['id']]);
        $input = ['id' => $opportunity['id'], 'expected_version' => 1, 'preview_digest' => $preview['digest'], 'idempotency_key' => (string) Str::uuid()];
        $published = $this->op('publish_opportunity', $input);
        $this->assertSame($published['publication_id'], $this->op('publish_opportunity', $input)['publication_id']);
        $this->postJson('/api/operations/publish_opportunity', array_replace($input, ['preview_digest' => 'changed']))->assertConflict();
        $this->postJson('/api/operations/preview_publication', $opportunity['selection'] + ['publication_id' => $published['publication_id']])->assertConflict();
        $updated = $this->op('update_opportunity', ['id' => $opportunity['id'], 'expected_version' => $published['version'], 'title' => 'Updated private title']);
        $this->postJson('/api/operations/publish_opportunity', array_replace($input, ['expected_version' => $updated['version'], 'idempotency_key' => (string) Str::uuid()]))->assertConflict();
        $republished = $this->publish($updated);
        $this->assertSame($published['publication_id'], $republished['publication_id']);
        $this->assertSame(2, $republished['publication']['version']);
    }

    public function test_export_uses_selected_snapshot_fields_and_rejects_stale_or_extra_translations(): void
    {
        [, $experience] = $this->fixture();
        $opportunity = $this->publish($this->opportunity($this->selection($experience)));
        $export = $this->op('export_opportunity', ['id' => $opportunity['id']]);
        $this->assertStringContainsString('公開ポートフォリオ', $export['markdown']);
        $this->assertStringContainsString($experience['confirmed_version_id'], $export['markdown']);
        $this->assertStringNotContainsString('PRIVATE_', $export['markdown']);
        $translations = array_map(fn ($f) => ['key' => $f['key'], 'text' => $f['text']], $export['fields']);
        $input = ['id' => $opportunity['id'], 'source_digest' => $export['source_digest'], 'reviewed' => true, 'translations' => $translations];
        $this->op('export_opportunity', $input);
        $this->postJson('/api/operations/export_opportunity', array_replace($input, ['source_digest' => 'stale']))->assertConflict();
        $this->postJson('/api/operations/export_opportunity', array_replace($input, ['translations' => [...$translations, ['key' => 'private_notes', 'text' => 'Injected']]]))->assertUnprocessable();
        $this->op('revoke_opportunity', ['id' => $opportunity['id'], 'expected_version' => $opportunity['version']]);
        $this->postJson('/api/operations/export_opportunity', $input)->assertNotFound();
    }

    public function test_all_p1_owner_resources_reject_foreign_reads_and_writes(): void
    {
        [$project, $experience] = $this->fixture();
        $capture = $this->op('create_capture', ['project_id' => $project['id'], 'notes' => 'Private capture']);
        $prep = $this->op('create_preparation', ['topic' => 'Private preparation']);
        $answer = $this->op('create_prepared_answer', ['preparation_id' => $prep['id'], 'question' => 'Private question']);
        $practice = $this->op('start_practice', ['preparation_id' => $prep['id'], 'questions' => ['Private question']]);
        $opportunity = $this->opportunity($this->selection($experience));
        $this->actingAs(User::factory()->create());
        foreach (['capture' => $capture, 'preparation' => $prep, 'prepared_answer' => $answer, 'practice' => $practice, 'opportunity' => $opportunity] as $kind => $row) {
            $this->postJson('/api/operations/get_'.$kind, ['id' => $row['id']])->assertNotFound();
            if ($kind !== 'practice') {
                $this->postJson('/api/operations/update_'.$kind, ['id' => $row['id'], 'expected_version' => 1])->assertNotFound();
            }
        }
        $this->postJson('/api/operations/export_opportunity', ['id' => $opportunity['id']])->assertNotFound();
        $this->postJson('/api/operations/get_answer_history', ['id' => $answer['id']])->assertNotFound();
    }

    public function test_expired_unretained_practice_is_removed_but_saved_work_is_retained(): void
    {
        $prep = $this->op('create_preparation', ['topic' => 'Retention check']);
        $expired = $this->op('start_practice', ['preparation_id' => $prep['id'], 'questions' => ['Explain your work.']]);
        $saved = $this->op('start_practice', ['preparation_id' => $prep['id'], 'questions' => ['Explain your work.']]);
        $this->op('save_practice', ['id' => $saved['id'], 'expected_version' => $saved['version']]);
        $this->travel(2)->days();
        $this->postJson('/api/operations/get_practice', ['id' => $expired['id']])->assertNotFound();
        $this->artisan('practice:prune')->assertSuccessful();
        $this->assertDatabaseMissing('practice_sessions', ['id' => $expired['id']]);
        $this->assertDatabaseHas('practice_sessions', ['id' => $saved['id'], 'retained' => true]);
    }
}
