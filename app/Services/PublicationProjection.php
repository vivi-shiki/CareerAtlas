<?php

namespace App\Services;

use App\Domain\Store;
use Illuminate\Support\Facades\DB;

final class PublicationProjection
{
    public function skills(array $items): array
    {
        $skills = [];
        foreach ($items as $item) {
            foreach ($item['skills'] ?? [] as $skill) {
                $id = $skill['id'];
                $skills[$id] ??= ['id' => $id, 'name' => $skill['name'], 'experience_ids' => [], 'project_ids' => []];
                $skills[$id]['experience_ids'][] = $item['id'];
                $skills[$id]['project_ids'][] = $item['project']['id'];
            }
        }
        foreach ($skills as &$skill) {
            $skill['experience_ids'] = array_values(array_unique($skill['experience_ids']));
            $skill['project_ids'] = array_values(array_unique($skill['project_ids']));
            $skill['experience_count'] = count($skill['experience_ids']);
            $skill['project_count'] = count($skill['project_ids']);
        }
        unset($skill);
        $skills = array_values($skills);
        usort($skills, fn ($a, $b) => strcmp($a['name'], $b['name']));

        return $skills;
    }

    public function publicPortfolio(string $slug): array
    {
        $pub = DB::table('publications')->where('slug', $slug)->where('state', 'published')->first();
        abort_unless($pub, 404);
        $snapshot = Store::row('publication_snapshots', $pub->active_snapshot_id);
        $items = DB::table('publication_items as pi')->join('experiences as e', 'e.id', '=', 'pi.experience_id')->join('projects as p', 'p.id', '=', 'pi.project_id')
            ->where('pi.snapshot_id', $snapshot['id'])->whereNull('e.deleted_at')->whereNull('p.deleted_at')
            ->whereColumn('e.access_epoch', 'pi.source_access_epoch')->orderBy('pi.sort_order')->get(['pi.payload'])
            ->map(fn ($r) => json_decode($r->payload, true, 512, JSON_THROW_ON_ERROR))->all();

        $introduction = ! array_diff($snapshot['introduction_sources'], array_column($items, 'id')) ? $snapshot['introduction'] : '';

        return ['slug' => $slug, 'version' => $pub->version, 'published_at' => $pub->published_at, 'profile' => $snapshot['profile'], 'introduction' => $introduction, 'items' => $items, 'skills' => $this->skills($items)];
    }

    public function citation(string $slug, string $id): array
    {
        $data = $this->publicPortfolio($slug);
        foreach ($data['items'] as $item) {
            if ($item['id'] === $id) {
                return $item;
            }
        }
        abort(404);
    }

    public function project(string $slug, string $id): array
    {
        $data = $this->publicPortfolio($slug);
        $items = array_values(array_filter($data['items'], fn ($i) => $i['project']['id'] === $id));
        abort_unless($items, 404);

        return ['project' => $items[0]['project'], 'items' => $items, 'skills' => $this->skills($items)];
    }

    public function skill(string $slug, string $id): array
    {
        $data = $this->publicPortfolio($slug);
        foreach ($data['skills'] as $skill) {
            if ($skill['id'] === $id) {
                return $skill + ['experiences' => array_values(array_filter($data['items'], fn ($i) => in_array($i['id'], $skill['experience_ids'])))];
            }
        }
        abort(404);
    }
}
