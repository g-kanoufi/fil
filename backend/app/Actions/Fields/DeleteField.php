<?php

declare(strict_types=1);

namespace App\Actions\Fields;

use App\Models\Field;

final class DeleteField
{
    public function handle(Field $field): void
    {
        // field_values and field_relation_links cascade on delete via FK constraints.
        $field->delete();
    }
}
