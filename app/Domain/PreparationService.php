<?php

namespace App\Domain;

use Illuminate\Support\Facades\DB;

final class PreparationService
{
    public function __construct(private P1Sources $sources) {}

    public function save(int $owner, array $input): array
    {
        if (isset($input['id'])) {
            $row = Store::owned('preparations', $input['id'], $owner);
            Store::version($row, $input['expected_version']);
        }
        if (isset($input['topic'])) {
            Input::nonempty($input, ['topic']);
        }
        if (! empty($input['source_url'])) {
            Input::url($input['source_url'], 'source_url');
        }
        if (! empty($input['interview_date'])) {
            Input::occurrence(['start' => $input['interview_date'], 'precision' => 'day']);
        }
        if (isset($input['experience_ids'])) {
            $input['sources'] = $this->sources->select($owner, $input['experience_ids']);
        }
        unset($input['id'], $input['expected_version'], $input['experience_ids']);
        $saved = isset($row) ? Store::update('preparations', $row['id'], $input + ['version' => $row['version'] + 1]) : Store::insert('preparations', $input + ['owner_id' => $owner]);

        return $this->get($owner, $saved['id']);
    }

    public function get(int $owner, string $id): array
    {
        $row = Store::owned('preparations', $id, $owner);
        $row['source_changes'] = $this->sources->freshness($owner, $row['sources']);
        $row['answers'] = DB::table('prepared_answers')->where('preparation_id', $id)->orderByDesc('updated_at')->get()->map(fn ($a) => $this->answer($owner, $a->id))->all();
        $row['practice'] = DB::table('practice_sessions')->where('preparation_id', $id)->where('retained', true)->orderByDesc('updated_at')->get()->map(fn ($r) => Store::decode($r))->all();

        return $row;
    }

    public function listing(int $owner): array
    {
        return DB::table('preparations')->where('owner_id', $owner)->orderByDesc('updated_at')->limit(100)->get()->map(fn ($r) => Store::decode($r))->all();
    }

    public function answer(int $owner, string $id): array
    {
        $row = Store::owned('prepared_answers', $id, $owner);
        $row['source_changes'] = $this->sources->freshness($owner, $row['sources']);

        return $row;
    }

    public function saveAnswer(int $owner, array $input): array
    {
        if (isset($input['id'])) {
            $row = $this->answer($owner, $input['id']);
            Store::version($row, $input['expected_version']);
            $preparation = Store::owned('preparations', $row['preparation_id'], $owner);
        } else {
            $preparation = Store::owned('preparations', $input['preparation_id'], $owner);
        }
        if (isset($input['question'])) {
            Input::nonempty($input, ['question']);
        }
        $sources = isset($input['experience_ids']) ? $this->sources->select($owner, $input['experience_ids']) : ($row['sources'] ?? $preparation['sources']);
        unset($input['id'], $input['expected_version'], $input['experience_ids']);
        $input += ['sources' => $sources, 'state' => 'draft', 'confirmed_at' => null, 'target_language' => $preparation['target_language']];
        $saved = isset($row) ? Store::update('prepared_answers', $row['id'], $input + ['version' => $row['version'] + 1]) : Store::insert('prepared_answers', $input + ['owner_id' => $owner]);
        $this->answerVersion($saved);

        return $this->answer($owner, $saved['id']);
    }

    public function confirmAnswer(int $owner, array $input): array
    {
        $row = $this->answer($owner, $input['id']);
        Store::version($row, $input['expected_version']);
        if ($row['state'] === 'confirmed') {
            return $row;
        }
        Input::nonempty($row, ['question', 'answer']);
        $this->sources->requireFresh($owner, $row['sources']);
        $saved = Store::update('prepared_answers', $row['id'], ['state' => 'confirmed', 'confirmed_at' => now(), 'version' => $row['version'] + 1]);
        $this->answerVersion($saved);

        return $this->answer($owner, $saved['id']);
    }

    private function answerVersion(array $row): void
    {
        Store::insert('answer_versions', ['answer_id' => $row['id'], 'number' => $row['version'], 'payload' => array_intersect_key($row, array_flip(['question', 'answer', 'sources', 'target_language', 'state', 'confirmed_at'])), 'created_at' => now()], false);
    }

    public function history(int $owner, string $id): array
    {
        Store::owned('prepared_answers', $id, $owner);

        return DB::table('answer_versions')->where('answer_id', $id)->orderByDesc('number')->get()->map(fn ($r) => Store::decode($r))->all();
    }

    public function startPractice(int $owner, array $input): array
    {
        $preparation = Store::owned('preparations', $input['preparation_id'], $owner);
        foreach ($input['questions'] as $question) {
            Input::nonempty(['question' => $question], ['question']);
        }

        return Store::insert('practice_sessions', ['owner_id' => $owner, 'preparation_id' => $preparation['id'], 'questions' => $input['questions'], 'sources' => $preparation['sources'], 'expires_at' => now()->addDay()]);
    }

    public function practice(int $owner, string $id): array
    {
        $row = Store::owned('practice_sessions', $id, $owner);
        abort_if(! $row['retained'] && $row['expires_at'] && now()->gt($row['expires_at']), 404);
        $row['source_changes'] = $this->sources->freshness($owner, $row['sources']);

        return $row;
    }

    public function changePractice(int $owner, string $action, array $input): array
    {
        $row = $this->practice($owner, $input['id']);
        Store::version($row, $input['expected_version']);
        if ($action === 'discard') {
            DB::table('practice_sessions')->where('id', $row['id'])->delete();

            return ['id' => $row['id'], 'state' => 'discarded'];
        }
        $patch = ['version' => $row['version'] + 1];
        if ($action === 'answer') {
            if ($row['state'] !== 'active') {
                throw new DomainError('practice_stopped', 'This practice session is stopped.');
            }
            Input::nonempty($input, ['answer']);
            $attempts = $row['attempts'];
            $attempts[$row['current_index']] = ['question' => $row['questions'][$row['current_index']], 'answer' => $input['answer'], 'feedback' => null];
            $patch['attempts'] = $attempts;
        } elseif ($action === 'next') {
            if ($row['state'] !== 'active') {
                throw new DomainError('practice_stopped', 'This practice session is stopped.');
            }
            if (! isset($row['attempts'][$row['current_index']])) {
                Input::fail('answer', 'Write an answer before continuing.');
            }
            if (count($row['questions']) <= $row['current_index'] + 1) {
                $patch['state'] = 'stopped';
            } else {
                $patch['current_index'] = $row['current_index'] + 1;
            }
        } elseif ($action === 'save') {
            $patch += ['state' => 'saved', 'retained' => true, 'expires_at' => null];
        } else {
            $patch['state'] = 'stopped';
        }

        return Store::update('practice_sessions', $row['id'], $patch);
    }
}
