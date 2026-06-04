<?php

declare(strict_types=1);

namespace App\Services\Platform;

final class PlatformStageCatalog
{
    /**
     * @return array<string, array{label: string, description: string, sort: int, pipeline_phases: list<int>, navigation_ids: list<string>}>
     */
    public function stages(): array
    {
        /** @var array<string, array{label: string, description: string, sort: int, pipeline_phases: list<int>, navigation_ids: list<string>}> */
        return config('fil-platform.stages', []);
    }

    /**
     * @return list<array{id: string, label: string, description: string, sort: int, navigation_ids: list<string>}>
     */
    public function forAppConfig(): array
    {
        return collect($this->stages())
            ->map(fn (array $stage, string $id) => [
                'id' => $id,
                'label' => $stage['label'],
                'description' => $stage['description'],
                'sort' => $stage['sort'],
                'navigation_ids' => $stage['navigation_ids'],
            ])
            ->sortBy('sort')
            ->values()
            ->all();
    }

    public function stageForPipelinePhase(int $phase): ?string
    {
        foreach ($this->stages() as $id => $stage) {
            if (in_array($phase, $stage['pipeline_phases'], true)) {
                return $id;
            }
        }

        return null;
    }

    public function stageForNavigationId(string $navigationId): ?string
    {
        foreach ($this->stages() as $id => $stage) {
            if (in_array($navigationId, $stage['navigation_ids'], true)) {
                return $id;
            }
        }

        return null;
    }
}
