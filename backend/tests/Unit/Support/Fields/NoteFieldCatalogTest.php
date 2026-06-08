<?php

declare(strict_types=1);

use App\Support\Fields\NoteFieldCatalog;

test('note field catalog excludes legacy note groups and repeater keys', function (): void {
    expect(NoteFieldCatalog::excludedGroupKeys())
        ->toContain('private-notes', 'administrative-notes', 'private-notes-application')
        ->and(NoteFieldCatalog::excludedFieldKeys())
        ->toEqual(['private_notes', 'administrative_notes'])
        ->and(NoteFieldCatalog::isNoteGroupKey('private-notes'))->toBeTrue()
        ->and(NoteFieldCatalog::isNoteFieldKey('private_notes'))->toBeTrue()
        ->and(NoteFieldCatalog::isNoteGroupKey('applications'))->toBeFalse();
});
