<?php

declare(strict_types=1);

namespace App\Services\Leads;

use App\Models\Lead;
use App\Support\Fields\FieldChoiceCatalog;
use App\Support\Fields\FieldChoiceSet;

final class LeadStatusMenuService
{
    public const SUBMENU_ACTIVE = 'leads_active';

    public const SUBMENU_AWARDED = 'leads_awarded_deals';

    public function __construct(
        private readonly FieldChoiceCatalog $choices,
        private readonly LeadPipelineCatalog $pipelineCatalog,
    ) {}

    /**
     * @return array{
     *     choices: list<array{value: string, label: string, slug: string, category: string|null, closed: bool, pipeline_phase: int|null, sort: int, filter_values: list<string>}>,
     *     groups: array{
     *         active: array{submenu_key: string, values: list<string>, filter_values: list<string>},
     *         won: array{submenu_key: string, values: list<string>, filter_values: list<string>},
     *         closed: array{values: list<string>, filter_values: list<string>}
     *     }
     * }
     */
    public function forAppConfig(): array
    {
        $choiceSet = $this->mergedChoiceSet();
        $choices = [];
        $byCategory = [
            'active' => ['values' => [], 'filter_values' => []],
            'won' => ['values' => [], 'filter_values' => []],
            'closed' => ['values' => [], 'filter_values' => []],
        ];

        foreach ($choiceSet->all() as $choice) {
            $value = $choice['value'];
            $category = $choiceSet->categoryFor($value) ?? 'active';
            $filterValues = $choiceSet->filterValuesFor($value);
            $choices[] = [
                'value' => $value,
                'label' => $choice['label'],
                'slug' => $this->slugForStored($value),
                'category' => $category,
                'closed' => $choiceSet->isClosed($value),
                'pipeline_phase' => $choiceSet->pipelinePhase($value),
                'sort' => (int) ($choice['meta']['sort'] ?? 0),
                'filter_values' => $filterValues,
            ];

            if (! isset($byCategory[$category])) {
                $byCategory[$category] = ['values' => [], 'filter_values' => []];
            }

            $byCategory[$category]['values'][] = $value;
            array_push($byCategory[$category]['filter_values'], ...$filterValues);
        }

        return [
            'choices' => $choices,
            'groups' => [
                'active' => [
                    'submenu_key' => self::SUBMENU_ACTIVE,
                    'values' => array_values(array_unique($byCategory['active']['values'])),
                    'filter_values' => array_values(array_unique($byCategory['active']['filter_values'])),
                ],
                'won' => [
                    'submenu_key' => self::SUBMENU_AWARDED,
                    'values' => array_values(array_unique($byCategory['won']['values'])),
                    'filter_values' => array_values(array_unique($byCategory['won']['filter_values'])),
                ],
                'closed' => [
                    'values' => array_values(array_unique($byCategory['closed']['values'])),
                    'filter_values' => array_values(array_unique($byCategory['closed']['filter_values'])),
                ],
            ],
        ];
    }

    /**
     * @param  array{menuItems: array<string, array{label: string|null, slug: string}>, subMenuItems: array<string, array<string, array{label: string|null, slug: string}>>}  $menu
     * @return array{menuItems: array<string, array{label: string|null, slug: string}>, subMenuItems: array<string, array<string, array{label: string|null, slug: string}>>}
     */
    public function enrichLeadMenus(array $menu): array
    {
        if (! isset($menu['menuItems']['lead_status'])) {
            return $menu;
        }

        $catalog = $this->forAppConfig();
        $sub = $menu['subMenuItems']['lead_status'] ?? [];

        foreach ($catalog['choices'] as $choice) {
            $sub[$choice['slug']] = [
                'label' => $choice['label'],
                'slug' => $choice['slug'],
            ];
        }

        foreach ($this->distinctStoredStatuses() as $stored) {
            $slug = $this->slugForStored($stored);
            if (isset($sub[$slug])) {
                continue;
            }

            $sub[$slug] = [
                'label' => $this->pipelineCatalog->normalizeStatusValue($stored) ?? $stored,
                'slug' => $slug,
            ];
        }

        if (isset($sub[self::SUBMENU_ACTIVE])) {
            $sub = [self::SUBMENU_ACTIVE => $sub[self::SUBMENU_ACTIVE]] + array_diff_key($sub, [self::SUBMENU_ACTIVE => true]);
        }

        if (isset($sub[self::SUBMENU_AWARDED])) {
            $ordered = [];
            if (isset($sub[self::SUBMENU_ACTIVE])) {
                $ordered[self::SUBMENU_ACTIVE] = $sub[self::SUBMENU_ACTIVE];
            }
            if (isset($sub[self::SUBMENU_AWARDED])) {
                $ordered[self::SUBMENU_AWARDED] = $sub[self::SUBMENU_AWARDED];
            }

            foreach ($sub as $key => $item) {
                if ($key === self::SUBMENU_ACTIVE || $key === self::SUBMENU_AWARDED) {
                    continue;
                }
                $ordered[$key] = $item;
            }

            $sub = $ordered;
        }

        $menu['subMenuItems']['lead_status'] = $sub;

        return $menu;
    }

    /**
     * @return list<string>
     */
    public function filterValuesForGroup(string $group): array
    {
        $groups = $this->forAppConfig()['groups'];

        return match ($group) {
            'active' => $groups['active']['filter_values'],
            'won' => $groups['won']['filter_values'],
            'closed' => $groups['closed']['filter_values'],
            default => [],
        };
    }

    private function mergedChoiceSet(): FieldChoiceSet
    {
        $primary = $this->choices->choicesFor('lead', 'lead_status');
        $fdd = $this->choices->choicesFor('lead', 'lead_fdd_status');

        if ($primary->isEmpty() && $fdd->isEmpty()) {
            return $primary;
        }

        if ($primary->isEmpty()) {
            return $fdd;
        }

        if ($fdd->isEmpty()) {
            return $primary;
        }

        $merged = $primary->all();
        $known = [];

        foreach ($merged as $choice) {
            $known[$choice['value']] = true;
            foreach ($choice['aliases'] ?? [] as $alias) {
                $known[$alias] = true;
            }
        }

        foreach ($fdd->all() as $choice) {
            if (isset($known[$choice['value']])) {
                continue;
            }

            $match = $primary->matchChoice($choice['label']);
            if ($match !== null) {
                continue;
            }

            $merged[] = $choice;
            $known[$choice['value']] = true;
        }

        usort($merged, fn (array $a, array $b): int => ((int) ($a['meta']['sort'] ?? 0)) <=> ((int) ($b['meta']['sort'] ?? 0)));

        return new FieldChoiceSet($merged);
    }

    /**
     * @return list<string>
     */
    private function distinctStoredStatuses(): array
    {
        $statuses = Lead::query()
            ->where('status', 'active')
            ->selectRaw('lead_status AS value')
            ->whereNotNull('lead_status')
            ->where('lead_status', '!=', '')
            ->union(
                Lead::query()
                    ->where('status', 'active')
                    ->selectRaw('lead_fdd_status AS value')
                    ->whereNotNull('lead_fdd_status')
                    ->where('lead_fdd_status', '!=', ''),
            )
            ->distinct()
            ->orderBy('value')
            ->pluck('value')
            ->map(fn ($value): string => (string) $value)
            ->all();

        return $statuses;
    }

    private function slugForStored(string $stored): string
    {
        $canonical = $this->mergedChoiceSet()->matchChoice($stored);

        if ($canonical !== null) {
            $normalized = preg_replace('/[-\s]+/', '_', trim($canonical['value'])) ?? $canonical['value'];

            return strtolower($normalized);
        }

        $normalized = preg_replace('/[-\s]+/', '_', trim($stored)) ?? $stored;

        return strtolower($normalized);
    }
}
