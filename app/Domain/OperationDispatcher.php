<?php

namespace App\Domain;

use App\Models\User;
use App\Services\PublicationProjection;
use Illuminate\Support\Facades\DB;

final class OperationDispatcher
{
    public function __construct(private ProfileService $profiles, private ProjectService $projects, private ExperienceService $experiences, private SkillService $skills, private PublicationService $publications) {}

    public function execute(User $user, string $operation, array $input): array
    {
        $definition = OperationCatalog::get($operation);
        Input::schema($input, $definition['input_schema']);

        return DB::transaction(function () use ($user, $operation, $input, $definition) {
            // Serialize each owner's mutations, previews, and lazy profile creation.
            // Public readers remain independent; no cross-owner lock is taken.
            User::whereKey($user->id)->lockForUpdate()->firstOrFail();
            $owner = $user->id;
            $result = match ($operation) {
                'get_profile' => $this->profiles->get($owner),
                'update_profile' => $this->profiles->update($owner, $input),
                'list_projects' => $this->projects->listing($owner, $input),
                'get_project' => Store::owned('projects', $input['id'], $owner),
                'create_project' => $this->projects->create($owner, $input),
                'update_project' => $this->projects->update($owner, $input),
                'archive_project','restore_project','delete_project' => $this->projects->lifecycle($owner, $input, explode('_', $operation)[0]),
                'list_experiences' => $this->experiences->listing($owner, $input),
                'get_experience' => $this->experiences->get($owner, $input['id']),
                'create_experience' => $this->experiences->create($owner, $input),
                'update_experience' => $this->experiences->update($owner, $input),
                'confirm_experience' => $this->experiences->confirm($owner, $input),
                'archive_experience','restore_experience','delete_experience','revoke_experience_access' => $this->experiences->lifecycle($owner, $input, explode('_', $operation)[0]),
                'list_skills' => $this->skills->listing($owner, $input),
                'get_skill' => $this->skills->get($owner, $input['id']),
                'create_skill' => $this->skills->create($owner, $input),
                'update_skill' => $this->skills->update($owner, $input),
                'add_skill_alias' => $this->skills->alias($owner, $input),
                'delete_skill_alias' => $this->skills->alias($owner, $input, true),
                'attach_skill' => $this->skills->association($owner, $input),
                'detach_skill' => $this->skills->association($owner, $input, true),
                'list_evidence' => $this->experiences->evidence($owner, $input['experience_id']),
                'create_evidence','update_evidence','delete_evidence' => $this->experiences->mutateEvidence($owner, $input, explode('_', $operation)[0]),
                'preview_publication' => $this->publications->preview($owner, $input),
                'publish_publication' => $this->publications->publish($owner, $input),
                'republish_publication' => $this->publications->publish($owner, $input, true),
                'get_publication' => $this->publications->get($owner, $input['id']),
                'list_publications' => $this->publications->listing($owner),
                'revoke_publication' => $this->publications->revoke($owner, $input),
                'get_public_portfolio' => app(PublicationProjection::class)->publicPortfolio($input['slug']),
                'get_public_project' => app(PublicationProjection::class)->project($input['slug'], $input['id']),
                'list_public_skills' => app(PublicationProjection::class)->publicPortfolio($input['slug'])['skills'],
                'get_public_skill' => app(PublicationProjection::class)->skill($input['slug'], $input['id']),
                'get_public_citation' => app(PublicationProjection::class)->citation($input['slug'], $input['id']),
                default => app(P1Dispatcher::class)->execute($owner, $operation, $input),
            };
            if (! $definition['read_only']) {
                if (in_array($operation, ['create_experience', 'update_experience', 'confirm_experience', 'archive_experience', 'restore_experience', 'revoke_experience_access', 'attach_skill', 'detach_skill', 'create_evidence', 'update_evidence', 'delete_evidence'])) {
                    app(HistoryService::class)->record($owner, $result['id'], $operation);
                }
                Store::audit($owner, $operation, 'operation', $result['id'] ?? null, isset($result['version']) ? ['version' => $result['version']] : []);
            }

            return $result;
        }, 3);
    }
}
