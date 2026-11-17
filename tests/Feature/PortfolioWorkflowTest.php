<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Database\Events\QueryExecuted;
use Illuminate\Database\QueryException;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Str;
use Illuminate\Testing\TestResponse;
use PHPUnit\Framework\Attributes\DataProvider;
use Tests\TestCase;

class PortfolioWorkflowTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        Http::preventStrayRequests();
        $this->actingAs(User::factory()->create());
    }

    private function op(string $name, array $input = []): TestResponse
    {
        return $this->postJson('/api/operations/'.$name, $input);
    }

    private function project(array $overrides = []): array
    {
        return $this->op('create_project', array_replace([
            'title' => 'Atlas Notes', 'type' => 'personal', 'role' => 'Frontend developer',
            'background' => 'A fictional accessible notes project.', 'team_outcome' => 'The team shipped search.',
        ], $overrides))->assertSuccessful()->json('data');
    }

    private function experience(?array $project = null, array $overrides = []): array
    {
        $project ??= $this->project();

        return $this->op('create_experience', array_replace([
            'project_id' => $project['id'], 'title' => 'Accessible project search',
            'record_type' => 'work_contribution',
            'occurrence' => ['start' => '2026-10', 'precision' => 'month', 'ongoing' => false],
            'personal_action' => 'I built keyboard navigation and wrote component tests.',
            'result' => 'Keyboard navigation works.', 'private_notes' => 'PRIVATE_SENTINEL',
        ], $overrides))->assertSuccessful()->json('data');
    }

    private function confirm(array $experience): array
    {
        return $this->op('confirm_experience', ['id' => $experience['id'], 'expected_version' => $experience['version']])
            ->assertSuccessful()->json('data');
    }

    private function selection(array $experience): array
    {
        return [
            'profile_fields' => ['display_name', 'biography'],
            'items' => [[
                'experience_id' => $experience['id'],
                'fields' => ['title', 'record_type', 'occurrence', 'personal_action', 'skills', 'evidence'],
                'project_fields' => ['title', 'type', 'role'],
            ]],
        ];
    }

    private function publish(array $experience, string $key = 'publication-request-001'): array
    {
        $selection = $this->selection($experience);
        $preview = $this->op('preview_publication', $selection)->assertSuccessful()->json('data');

        return $this->op('publish_publication', $selection + ['preview_digest' => $preview['digest'], 'idempotency_key' => $key])
            ->assertSuccessful()->json('data');
    }

    public function test_complete_owner_to_public_workflow_preserves_selected_fields_and_provenance(): void
    {
        $profile = $this->op('get_profile')->assertOk()->json('data');
        $this->op('update_profile', ['expected_version' => $profile['version'], 'display_name' => 'Alex Morgan',
            'biography' => 'Fictional demo profile', 'contact_links' => [['label' => 'Private contact', 'url' => 'https://example.com/contact']]])->assertOk();
        $project = $this->project();
        $exp = $this->experience($project);
        $skill = $this->op('create_skill', ['name' => 'Vue 3'])->assertSuccessful()->json('data');
        $exp = $this->op('attach_skill', ['id' => $exp['id'], 'skill_id' => $skill['id'], 'expected_version' => $exp['version']])->assertOk()->json('data');
        $exp = $this->op('create_evidence', ['experience_id' => $exp['id'], 'expected_version' => $exp['version'],
            'kind' => 'repository', 'label' => 'Pull request', 'url' => 'https://example.com/pr/24'])->assertSuccessful()->json('data');
        $exp = $this->confirm($exp);
        $pub = $this->publish($exp);
        $public = $this->getJson('/api/public/'.$pub['slug'])->assertOk();
        $public->assertJsonPath('data.profile.display_name', 'Alex Morgan')
            ->assertJsonPath('data.items.0.personal_action', $exp['personal_action'])
            ->assertJsonPath('data.items.0.occurrence.start', '2026-10')
            ->assertJsonPath('data.items.0.evidence.0.provenance', 'supplied_link');
        $text = $public->getContent();
        foreach (['PRIVATE_SENTINEL', 'Private contact', 'The team shipped search.', 'owner_id', 'password'] as $secret) {
            $this->assertStringNotContainsString($secret, $text);
        }
        $this->assertDatabaseCount('record_versions', 1);
        $this->assertDatabaseCount('publication_snapshots', 1);
        $this->assertDatabaseHas('audit_events', ['action' => 'publish_publication']);
    }

    public function test_incomplete_draft_can_be_saved_but_confirmation_reports_required_fields(): void
    {
        $project = $this->project();
        $exp = $this->op('create_experience', ['project_id' => $project['id'], 'title' => 'One sentence note'])->assertSuccessful()->json('data');
        $this->assertSame('draft', $exp['status']);
        $this->op('confirm_experience', ['id' => $exp['id'], 'expected_version' => $exp['version']])
            ->assertUnprocessable()->assertJsonValidationErrors(['personal_action', 'record_type', 'occurrence']);
        $this->assertDatabaseCount('record_versions', 0);
        $this->op('preview_publication', $this->selection($exp))->assertUnprocessable();
    }

    public function test_confirmation_replay_creates_exactly_one_immutable_version(): void
    {
        $exp = $this->experience();
        $first = $this->confirm($exp);
        $again = $this->confirm($exp);
        $this->assertSame($first['confirmed_version_id'], $again['confirmed_version_id']);
        $this->assertDatabaseCount('record_versions', 1);
        $changed = $this->op('update_experience', ['id' => $exp['id'], 'expected_version' => $exp['version'], 'personal_action' => 'Revised private action'])->assertOk()->json('data');
        $this->assertSame('draft', $changed['status']);
        $this->assertSame($exp['personal_action'], json_decode(DB::table('record_versions')->first()->payload, true)['personal_action']);
        $this->confirm($changed);
        $this->assertDatabaseCount('record_versions', 2);
    }

    public function test_stale_writes_fail_without_overwriting_current_values(): void
    {
        $exp = $this->experience();
        $this->op('update_experience', ['id' => $exp['id'], 'expected_version' => $exp['version'], 'title' => 'New title'])->assertOk();
        $this->op('update_experience', ['id' => $exp['id'], 'expected_version' => $exp['version'], 'title' => 'Stale title'])->assertConflict()->assertJsonPath('code', 'version_conflict');
        $this->assertDatabaseHas('experiences', ['id' => $exp['id'], 'title' => 'New title']);
        $this->op('update_experience', ['id' => $exp['id'], 'title' => 'No version'])->assertUnprocessable();
    }

    public function test_foreign_and_missing_private_objects_have_the_same_response(): void
    {
        $exp = $this->experience();
        $projectId = $exp['project_id'];
        $this->actingAs(User::factory()->create());
        foreach (['get_experience', 'update_experience', 'confirm_experience', 'archive_experience', 'delete_experience', 'revoke_experience_access'] as $operation) {
            $input = $operation === 'get_experience' ? [] : ['expected_version' => $exp['version']];
            $foreign = $this->op($operation, ['id' => $exp['id']] + $input)->assertNotFound();
            $missing = $this->op($operation, ['id' => '00000000-0000-4000-8000-000000000000'] + $input)->assertNotFound();
            $this->assertSame($foreign->json(), $missing->json());
        }
        $this->op('create_experience', ['project_id' => $projectId])->assertNotFound();
        $this->op('list_projects')->assertOk()->assertJsonCount(0, 'data.items');
        $this->op('list_evidence', ['experience_id' => $exp['id']])->assertNotFound();
    }

    public function test_publication_is_exact_preview_and_changes_only_after_explicit_republish(): void
    {
        $exp = $this->confirm($this->experience());
        $selection = $this->selection($exp);
        $preview = $this->op('preview_publication', $selection)->assertOk()->json('data');
        $pub = $this->op('publish_publication', $selection + ['preview_digest' => $preview['digest'], 'idempotency_key' => 'publish-original'])->assertSuccessful()->json('data');
        $public = $this->getJson('/api/public/'.$pub['slug'])->assertOk()->json('data');
        $this->assertEquals($preview['portfolio']['profile'], $public['profile']);
        $this->assertEquals($preview['portfolio']['items'], $public['items']);
        $changed = $this->op('update_experience', ['id' => $exp['id'], 'expected_version' => $exp['version'], 'personal_action' => 'A newer confirmed action'])->assertOk()->json('data');
        $changed = $this->confirm($changed);
        $this->getJson('/api/public/'.$pub['slug'])->assertOk()->assertJsonPath('data.items.0.personal_action', $exp['personal_action']);
        $selection = $this->selection($changed) + ['publication_id' => $pub['id']];
        $preview = $this->op('preview_publication', $selection)->assertOk()->json('data');
        $this->op('republish_publication', $selection + ['expected_version' => $pub['version'], 'preview_digest' => $preview['digest'], 'idempotency_key' => 'publish-new-version'])->assertOk();
        $this->getJson('/api/public/'.$pub['slug'])->assertOk()->assertJsonPath('data.items.0.personal_action', 'A newer confirmed action');
        $this->assertDatabaseCount('publication_snapshots', 2);
    }

    public function test_stale_preview_and_disallowed_fields_cannot_publish(): void
    {
        $exp = $this->confirm($this->experience());
        $selection = $this->selection($exp);
        $preview = $this->op('preview_publication', $selection)->assertOk()->json('data');
        $profile = $this->op('get_profile')->json('data');
        $this->op('update_profile', ['expected_version' => $profile['version'], 'display_name' => 'Changed display name'])->assertOk();
        $this->op('publish_publication', $selection + ['preview_digest' => $preview['digest'], 'idempotency_key' => 'stale-preview'])->assertConflict();
        $selection['items'][0]['fields'][] = 'private_notes';
        $this->op('preview_publication', $selection)->assertUnprocessable();
        $this->assertDatabaseCount('publications', 0);
    }

    public function test_publication_replay_is_idempotent_and_changed_payload_conflicts(): void
    {
        $exp = $this->confirm($this->experience());
        $first = $this->publish($exp);
        $second = $this->publish($exp);
        $this->assertSame($first['id'], $second['id']);
        $this->assertDatabaseCount('publications', 1);
        $this->assertDatabaseCount('publication_snapshots', 1);
        $selection = $this->selection($exp);
        $selection['profile_fields'] = ['display_name'];
        $preview = $this->op('preview_publication', $selection)->assertOk()->json('data');
        $this->op('publish_publication', $selection + ['preview_digest' => $preview['digest'], 'idempotency_key' => 'publication-request-001'])->assertConflict();
    }

    public function test_archive_does_not_revoke_but_source_revocation_blocks_all_derived_reads(): void
    {
        $exp = $this->confirm($this->experience());
        $pub = $this->publish($exp);
        $archived = $this->op('archive_experience', ['id' => $exp['id'], 'expected_version' => $exp['version']])->assertOk()->json('data');
        $this->getJson('/api/public/'.$pub['slug'])->assertOk()->assertJsonCount(1, 'data.items');
        $this->op('revoke_experience_access', ['id' => $exp['id'], 'expected_version' => $archived['version']])->assertOk();
        $this->getJson('/api/public/'.$pub['slug'])->assertOk()->assertJsonCount(0, 'data.items')->assertJsonCount(0, 'data.skills');
        $this->getJson('/api/public/'.$pub['slug'].'/experiences/'.$exp['id'])->assertNotFound();
        $this->getJson('/api/public/'.$pub['slug'].'/projects/'.$exp['project_id'])->assertNotFound();
    }

    public function test_publication_revocation_is_immediate_and_idempotent(): void
    {
        $exp = $this->confirm($this->experience());
        $pub = $this->publish($exp);
        $input = ['id' => $pub['id'], 'expected_version' => $pub['version']];
        $this->op('revoke_publication', $input)->assertOk();
        $this->op('revoke_publication', $input)->assertOk();
        $this->getJson('/api/public/'.$pub['slug'])->assertNotFound();
        $this->getJson('/api/public/'.$pub['slug'].'/skills')->assertNotFound();
        $this->getJson('/api/public/'.$pub['slug'].'/experiences/'.$exp['id'])->assertNotFound();
    }

    public function test_skill_aliases_and_duplicate_associations_do_not_inflate_public_counts(): void
    {
        $skill = $this->op('create_skill', ['name' => 'Vue 3'])->assertSuccessful()->json('data');
        $this->op('add_skill_alias', ['id' => $skill['id'], 'expected_version' => $skill['version'], 'alias' => 'VueJS'])->assertSuccessful();
        $alias = $this->op('create_skill', ['name' => ' vuejs '])->assertSuccessful()->json('data');
        $this->assertSame($skill['id'], $alias['id']);
        $exp = $this->experience();
        $exp = $this->op('attach_skill', ['id' => $exp['id'], 'skill_id' => $skill['id'], 'expected_version' => $exp['version']])->assertOk()->json('data');
        $exp = $this->op('attach_skill', ['id' => $exp['id'], 'skill_id' => $skill['id'], 'expected_version' => $exp['version']])->assertOk()->json('data');
        $exp = $this->confirm($exp);
        $pub = $this->publish($exp);
        $private = $this->experience();
        $this->op('attach_skill', ['id' => $private['id'], 'skill_id' => $skill['id'], 'expected_version' => $private['version']])->assertOk();
        $this->assertDatabaseCount('experience_skill', 2);
        $this->getJson('/api/public/'.$pub['slug'].'/skills')->assertOk()->assertJsonPath('data.0.project_count', 1)->assertJsonPath('data.0.experience_count', 1);
    }

    public function test_evidence_edits_make_new_draft_without_altering_published_evidence(): void
    {
        $exp = $this->experience();
        $exp = $this->op('create_evidence', ['experience_id' => $exp['id'], 'expected_version' => $exp['version'], 'kind' => 'statement', 'label' => 'Test notes', 'excerpt' => 'Keyboard tested manually'])->assertSuccessful()->json('data');
        $exp = $this->confirm($exp);
        $pub = $this->publish($exp);
        $evidence = $this->op('list_evidence', ['experience_id' => $exp['id']])->assertOk()->json('data.0');
        $changed = $this->op('delete_evidence', ['id' => $evidence['id'], 'expected_version' => $exp['version']])->assertOk()->json('data');
        $this->assertSame('draft', $changed['status']);
        $this->getJson('/api/public/'.$pub['slug'])->assertOk()->assertJsonPath('data.items.0.evidence.0.excerpt', 'Keyboard tested manually');
    }

    #[DataProvider('unsafeLinks')]
    public function test_unsafe_evidence_links_are_rejected_without_network_access(string $url): void
    {
        $exp = $this->experience();
        $this->op('create_evidence', ['experience_id' => $exp['id'], 'expected_version' => $exp['version'], 'kind' => 'demo', 'label' => 'Unsafe', 'url' => $url])->assertUnprocessable()->assertJsonValidationErrors(['url']);
        $this->assertDatabaseCount('evidence', 0);
        Http::assertNothingSent();
    }

    public static function unsafeLinks(): array
    {
        return [['javascript:alert(1)'], ['file:///private'], ['data:text/html,hello'], ['ftp://example.com/file'], ['https://user:password@example.com']];
    }

    #[DataProvider('invalidOccurrences')]
    public function test_invalid_date_precision_and_ranges_are_rejected(array $occurrence): void
    {
        $project = $this->project();
        $this->op('create_experience', ['project_id' => $project['id'], 'occurrence' => $occurrence])->assertUnprocessable();
        $this->assertDatabaseCount('experiences', 0);
    }

    public static function invalidOccurrences(): array
    {
        return [
            [['start' => '2026-02-30', 'precision' => 'day']],
            [['start' => '2026-10-01', 'precision' => 'month']],
            [['start' => '2026-13', 'precision' => 'month']],
            [['start' => '2026-10', 'precision' => 'month', 'end' => '2026-09', 'end_precision' => 'month']],
            [['start' => '2026', 'precision' => 'year', 'ongoing' => true, 'end' => '2027', 'end_precision' => 'year']],
        ];
    }

    public function test_project_validation_archive_restore_and_deletion_are_distinct(): void
    {
        $this->op('create_project', ['title' => 'Missing role', 'type' => 'personal'])->assertUnprocessable()->assertJsonValidationErrors(['role']);
        $project = $this->project();
        $exp = $this->confirm($this->experience($project));
        $pub = $this->publish($exp);
        $project = $this->op('archive_project', ['id' => $project['id'], 'expected_version' => $project['version']])->assertOk()->json('data');
        $this->op('list_projects')->assertOk()->assertJsonCount(0, 'data.items');
        $this->op('list_projects', ['archived' => true])->assertOk()->assertJsonCount(1, 'data.items');
        $this->getJson('/api/public/'.$pub['slug'])->assertOk()->assertJsonCount(1, 'data.items');
        $project = $this->op('restore_project', ['id' => $project['id'], 'expected_version' => $project['version']])->assertOk()->json('data');
        $this->op('delete_project', ['id' => $project['id'], 'expected_version' => $project['version']])->assertOk();
        $this->getJson('/api/public/'.$pub['slug'])->assertOk()->assertJsonCount(0, 'data.items');
    }

    public function test_failed_mixed_owner_publication_rolls_back_all_related_rows(): void
    {
        $exp = $this->confirm($this->experience());
        $owner = auth()->user();
        $this->actingAs(User::factory()->create());
        $foreign = $this->confirm($this->experience());
        $this->actingAs($owner);
        $selection = $this->selection($exp);
        $selection['items'][] = $this->selection($foreign)['items'][0];
        $this->op('preview_publication', $selection)->assertNotFound();
        $this->op('publish_publication', $selection + ['preview_digest' => str_repeat('0', 64), 'idempotency_key' => 'mixed-owner'])->assertNotFound();
        $this->assertDatabaseCount('publications', 0);
        $this->assertDatabaseCount('publication_items', 0);
        $this->assertDatabaseCount('idempotency_keys', 0);
    }

    public function test_project_and_experience_pagination_are_bounded_and_owner_scoped(): void
    {
        for ($i = 0; $i < 4; $i++) {
            $this->project(['title' => 'Project '.$i]);
        }
        $this->op('list_projects', ['per_page' => 2])->assertOk()->assertJsonCount(2, 'data.items')->assertJsonPath('data.total', 4);
        $this->op('list_projects', ['query' => 'Project 2'])->assertOk()->assertJsonCount(1, 'data.items');
        $this->op('list_projects', ['per_page' => 10000])->assertUnprocessable();
        $this->op('create_project', ['title' => 'Bad', 'type' => 'personal', 'role' => 'Developer', 'owner_id' => 99])->assertUnprocessable();
    }

    public function test_remaining_skill_evidence_and_experience_operations_keep_revisions_consistent(): void
    {
        $exp = $this->experience();
        $skill = $this->op('create_skill', ['name' => 'TypeScript'])->assertOk()->json('data');
        $exp = $this->op('attach_skill', ['id' => $exp['id'], 'skill_id' => $skill['id'], 'expected_version' => $exp['version']])->assertOk()->json('data');
        $this->op('add_skill_alias', ['id' => $skill['id'], 'expected_version' => $skill['version'], 'alias' => 'TS'])->assertOk();
        $this->op('get_skill', ['id' => $skill['id']])->assertOk()->assertJsonPath('data.aliases.0', 'ts');
        $skill = $this->op('get_skill', ['id' => $skill['id']])->json('data');
        $this->op('delete_skill_alias', ['id' => $skill['id'], 'expected_version' => $skill['version'], 'alias' => 'TS'])->assertOk()->assertJsonCount(0, 'data.aliases');
        $skill = $this->op('get_skill', ['id' => $skill['id']])->json('data');
        $this->op('update_skill', ['id' => $skill['id'], 'expected_version' => $skill['version'], 'name' => 'TypeScript language'])->assertOk();
        $exp = $this->op('get_experience', ['id' => $exp['id']])->assertOk()->json('data');
        $exp = $this->op('detach_skill', ['id' => $exp['id'], 'skill_id' => $skill['id'], 'expected_version' => $exp['version']])->assertOk()->json('data');
        $this->assertCount(0, $exp['skills']);
        $exp = $this->op('create_evidence', ['experience_id' => $exp['id'], 'expected_version' => $exp['version'], 'kind' => 'document', 'label' => 'Notes', 'url' => 'https://example.com/notes'])->assertOk()->json('data');
        $evidence = $exp['evidence'][0];
        $exp = $this->op('update_evidence', ['id' => $evidence['id'], 'expected_version' => $exp['version'], 'label' => 'Updated notes'])->assertOk()->json('data');
        $this->assertSame('Updated notes', $exp['evidence'][0]['label']);
        $this->op('list_experiences', ['project_id' => $exp['project_id']])->assertOk()->assertJsonCount(1, 'data.items');
        $exp = $this->op('archive_experience', ['id' => $exp['id'], 'expected_version' => $exp['version']])->assertOk()->json('data');
        $this->op('list_experiences')->assertOk()->assertJsonCount(0, 'data.items');
        $exp = $this->op('restore_experience', ['id' => $exp['id'], 'expected_version' => $exp['version']])->assertOk()->json('data');
        $exp = $this->confirm($exp);
        $pub = $this->publish($exp);
        $this->op('list_publications')->assertOk()->assertJsonCount(1, 'data');
        $this->op('get_publication', ['id' => $pub['id']])->assertOk()->assertJsonPath('data.has_unpublished_changes', false);
        $this->op('delete_experience', ['id' => $exp['id'], 'expected_version' => $exp['version']])->assertOk();
        $this->op('get_publication', ['id' => $pub['id']])->assertOk()->assertJsonPath('data.has_unpublished_changes', true);
        $this->getJson('/api/public/'.$pub['slug'])->assertOk()->assertJsonCount(0, 'data.items');
    }

    public function test_skill_alias_collisions_and_foreign_associations_are_rejected(): void
    {
        $skill = $this->op('create_skill', ['name' => 'Vue'])->assertOk()->json('data');
        $second = $this->op('create_skill', ['name' => 'React'])->assertOk()->json('data');
        $this->op('add_skill_alias', ['id' => $second['id'], 'expected_version' => $second['version'], 'alias' => ' VUE '])->assertUnprocessable();
        $this->op('update_skill', ['id' => $second['id'], 'expected_version' => $second['version'], 'name' => 'vue'])->assertUnprocessable();
        $this->actingAs(User::factory()->create());
        $exp = $this->experience();
        $this->op('attach_skill', ['id' => $exp['id'], 'skill_id' => $skill['id'], 'expected_version' => $exp['version']])->assertNotFound();
        $this->op('update_skill', ['id' => $skill['id'], 'expected_version' => $skill['version'], 'name' => 'Hijacked'])->assertNotFound();
        $this->assertDatabaseCount('experience_skill', 0);
    }

    public function test_project_and_profile_stale_writes_and_unsafe_contacts_are_rejected(): void
    {
        $profile = $this->op('get_profile')->json('data');
        $this->op('update_profile', ['expected_version' => $profile['version'], 'contact_links' => [['label' => 'Bad', 'url' => 'javascript:alert(1)']]])->assertUnprocessable();
        $this->op('update_profile', ['expected_version' => $profile['version'], 'display_name' => 'Updated'])->assertOk();
        $this->op('update_profile', ['expected_version' => $profile['version'], 'display_name' => 'Stale'])->assertConflict();
        $project = $this->project(['occurrence' => ['start' => '2024', 'precision' => 'year', 'ongoing' => true]]);
        $this->op('get_project', ['id' => $project['id']])->assertOk()->assertJsonPath('data.occurrence.start', '2024');
        $this->op('update_project', ['id' => $project['id'], 'expected_version' => $project['version'], 'title' => 'Updated project'])->assertOk();
        $this->op('update_project', ['id' => $project['id'], 'expected_version' => $project['version'], 'title' => 'Stale project'])->assertConflict();
    }

    public function test_public_skill_and_citation_endpoints_apply_current_visibility(): void
    {
        $exp = $this->experience();
        $skill = $this->op('create_skill', ['name' => 'Accessibility'])->json('data');
        $exp = $this->op('attach_skill', ['id' => $exp['id'], 'skill_id' => $skill['id'], 'expected_version' => $exp['version']])->json('data');
        $exp = $this->confirm($exp);
        $pub = $this->publish($exp);
        $this->getJson('/api/public/'.$pub['slug'].'/skills/'.$skill['id'])->assertOk()->assertJsonCount(1, 'data.experiences');
        $this->getJson('/api/public/'.$pub['slug'].'/experiences/'.$exp['id'])->assertOk()->assertJsonPath('data.record_version_id', $exp['confirmed_version_id']);
        $this->op('revoke_experience_access', ['id' => $exp['id'], 'expected_version' => $exp['version']])->assertOk();
        $this->getJson('/api/public/'.$pub['slug'].'/skills/'.$skill['id'])->assertNotFound();
    }

    public function test_database_constraints_reject_cross_owner_links_without_partial_changes(): void
    {
        $project = $this->project();
        $other = User::factory()->create();
        try {
            DB::transaction(fn () => DB::table('experiences')->insert(['id' => (string) Str::uuid(), 'owner_id' => $other->id, 'project_id' => $project['id']]));
            $this->fail('Cross-owner foreign key was not enforced.');
        } catch (QueryException $e) {
            $this->assertSame('23503', $e->errorInfo[0]);
        }
        $this->assertDatabaseCount('experiences', 0);
    }

    public function test_every_operation_is_listed_in_the_shared_machine_readable_contract(): void
    {
        $catalog = json_decode(file_get_contents(base_path('contracts/operations.json')), true);
        $this->assertCount(41, $catalog);
        foreach ($catalog as $name => $spec) {
            $this->assertFalse($spec['input_schema']['additionalProperties'], $name);
            $this->assertArrayNotHasKey('owner_id', $spec['input_schema']['properties']);
        }
    }

    public function test_skill_updates_require_current_versions_and_project_edits_warn_about_old_snapshots(): void
    {
        $skill = $this->op('create_skill', ['name' => 'Vue'])->json('data');
        $this->op('update_skill', ['id' => $skill['id'], 'expected_version' => $skill['version'], 'name' => 'Vue 3'])->assertOk();
        $this->op('update_skill', ['id' => $skill['id'], 'expected_version' => $skill['version'], 'name' => 'Stale'])->assertConflict();
        $project = $this->project();
        $exp = $this->confirm($this->experience($project));
        $pub = $this->publish($exp);
        $this->op('update_project', ['id' => $project['id'], 'expected_version' => $project['version'], 'title' => 'Unpublished project name'])->assertOk();
        $this->op('get_publication', ['id' => $pub['id']])->assertOk()->assertJsonPath('data.has_unpublished_changes', true);
        $this->getJson('/api/public/'.$pub['slug'])->assertOk()->assertJsonPath('data.items.0.project.title', $project['title']);
    }

    public function test_imprecise_range_uses_end_precision_without_inventing_a_stored_day(): void
    {
        $exp = $this->experience(null, ['occurrence' => ['start' => '2026-10', 'precision' => 'month', 'end' => '2026', 'end_precision' => 'year']]);
        $this->assertSame('2026', $exp['occurrence']['end']);
        $this->confirm($exp);
    }

    public function test_project_type_filter_applies_before_pagination_and_total_count(): void
    {
        $this->project(['type' => 'personal']);
        $commercial = $this->project(['title' => 'Commercial record', 'type' => 'commercial']);
        $this->project(['title' => 'Learning record', 'type' => 'learning']);
        $this->op('list_projects', ['type' => 'commercial', 'per_page' => 1])
            ->assertOk()->assertJsonPath('data.total', 1)->assertJsonPath('data.items.0.id', $commercial['id']);
        $this->op('list_projects', ['type' => 'unsupported'])->assertUnprocessable();
    }

    public function test_skill_alias_search_returns_only_the_owners_canonical_skill(): void
    {
        $skill = $this->op('create_skill', ['name' => 'Vue 3'])->json('data');
        $this->op('add_skill_alias', ['id' => $skill['id'], 'expected_version' => $skill['version'], 'alias' => 'VueJS'])->assertOk();
        $this->op('list_skills', ['query' => 'vuejs'])->assertOk()->assertJsonCount(1, 'data')->assertJsonPath('data.0.id', $skill['id']);
        $this->actingAs(User::factory()->create());
        $this->op('list_skills', ['query' => 'vuejs'])->assertOk()->assertJsonCount(0, 'data');
    }

    public function test_transaction_rolls_back_a_business_write_when_audit_write_fails(): void
    {
        DB::listen(function (QueryExecuted $query) {
            if (str_starts_with($query->sql, 'insert into "audit_events"')) {
                throw new \RuntimeException('Simulated persistence failure.');
            }
        });
        $this->op('create_project', ['title' => 'Rollback fixture', 'type' => 'personal', 'role' => 'Developer'])->assertStatus(500);
        $this->assertDatabaseCount('projects', 0);
        $this->assertDatabaseCount('audit_events', 0);
    }
}
