<?php

declare(strict_types=1);

namespace App\Actions\WidgetForms;

use App\Models\WidgetForm;
use Illuminate\Support\Facades\DB;

final class SyncWidgetFormFields
{
    /**
     * Replace the form's field set in one call — supports add, remove, and reorder
     * from a single drag-and-drop save.
     *
     * @param  list<array<string, mixed>>  $fields
     */
    public function handle(WidgetForm $form, array $fields): WidgetForm
    {
        DB::transaction(function () use ($form, $fields): void {
            $keepFieldIds = [];

            foreach (array_values($fields) as $index => $row) {
                $fieldId = (int) $row['field_id'];
                $keepFieldIds[] = $fieldId;

                $form->formFields()->updateOrCreate(
                    ['field_id' => $fieldId],
                    [
                        'sort_order' => $row['sort_order'] ?? $index,
                        'label_override' => $row['label_override'] ?? null,
                        'placeholder' => $row['placeholder'] ?? null,
                        'required_override' => $row['required_override'] ?? null,
                        'width' => $row['width'] ?? null,
                        'status' => 'active',
                    ],
                );
            }

            $form->formFields()
                ->when($keepFieldIds !== [], fn ($query) => $query->whereNotIn('field_id', $keepFieldIds))
                ->delete();
        });

        return $form->load(['formFields.field']);
    }
}
