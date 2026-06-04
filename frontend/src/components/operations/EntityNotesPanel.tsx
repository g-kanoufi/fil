import { useCallback, useEffect, useState } from 'react';
import { Badge } from '@/components/ui/Badge';
import { Button } from '@/components/ui/Button';
import { Card, CardHeader } from '@/components/ui/Card';
import { EmptyState } from '@/components/ui/EmptyState';
import { FormField } from '@/components/ui/FormField';
import { LoadingState } from '@/components/ui/LoadingState';
import { useAuth } from '@/providers/AuthProvider';
import { formControl, formFocus } from '@/lib/ui/tokens';
import {
  createEntityNote,
  deleteEntityNote,
  fetchEntityNotes,
  type EntityNote,
  type EntityNoteSubjectType,
} from '@/lib/api/notes';

interface EntityNotesPanelProps {
  subjectType: EntityNoteSubjectType;
  subjectId: number;
}

const NOTE_ENTITY_MAP: Record<EntityNoteSubjectType, string> = {
  lead: 'application',
  store: 'store',
  contact: 'user',
};

export function EntityNotesPanel({ subjectType, subjectId }: EntityNotesPanelProps) {
  const { canSeeNotes } = useAuth();
  const entityKey = NOTE_ENTITY_MAP[subjectType];
  const canView = canSeeNotes(entityKey, 'notes');
  const canViewPrivate = canSeeNotes(entityKey, 'private_notes');

  const [notes, setNotes] = useState<EntityNote[]>([]);
  const [loading, setLoading] = useState(true);
  const [body, setBody] = useState('');
  const [isPrivate, setIsPrivate] = useState(false);
  const [saving, setSaving] = useState(false);
  const [error, setError] = useState<string | null>(null);

  const load = useCallback(() => {
    if (!canView) {
      setLoading(false);
      return;
    }

    setLoading(true);
    void fetchEntityNotes(subjectType, subjectId)
      .then(setNotes)
      .catch((err: unknown) => {
        setError(err instanceof Error ? err.message : 'Failed to load notes');
      })
      .finally(() => setLoading(false));
  }, [canView, subjectId, subjectType]);

  useEffect(() => {
    load();
  }, [load]);

  if (!canView) {
    return null;
  }

  async function onSubmit(event: React.FormEvent) {
    event.preventDefault();
    const trimmed = body.trim();
    if (!trimmed) return;

    setSaving(true);
    setError(null);
    try {
      const note = await createEntityNote(subjectType, subjectId, {
        body: trimmed,
        is_private: isPrivate && canViewPrivate,
      });
      setNotes((current) => [note, ...current]);
      setBody('');
      setIsPrivate(false);
    } catch (err: unknown) {
      setError(err instanceof Error ? err.message : 'Failed to save note');
    } finally {
      setSaving(false);
    }
  }

  async function onDelete(noteId: number) {
    try {
      await deleteEntityNote(noteId);
      setNotes((current) => current.filter((note) => note.id !== noteId));
    } catch (err: unknown) {
      setError(err instanceof Error ? err.message : 'Failed to delete note');
    }
  }

  return (
    <Card className="mt-6">
      <CardHeader title="Corp notes" description="Internal staff notes — not visible to prospects or franchisees." />
      {error ? <p className="mb-3 text-sm text-danger">{error}</p> : null}
      <form className="mb-4 space-y-3" onSubmit={(event) => void onSubmit(event)}>
        <div className="space-y-1.5">
          <label htmlFor={`corp-note-${subjectType}-${subjectId}`} className="block text-sm font-medium text-foreground">
            Add note
          </label>
          <textarea
            id={`corp-note-${subjectType}-${subjectId}`}
            rows={3}
            value={body}
            onChange={(event) => setBody(event.target.value)}
            placeholder="Internal follow-up, compliance note, etc."
            className={`${formControl} ${formFocus} w-full`}
          />
        </div>
        {canViewPrivate ? (
          <label className="flex items-center gap-2 text-sm text-muted">
            <input
              type="checkbox"
              checked={isPrivate}
              onChange={(event) => setIsPrivate(event.target.checked)}
            />
            Private (corp staff only)
          </label>
        ) : null}
        <Button type="submit" size="sm" disabled={saving || !body.trim()}>
          {saving ? 'Saving…' : 'Add note'}
        </Button>
      </form>
      {loading ? <LoadingState size="compact" label="Loading notes…" /> : null}
      {!loading && notes.length === 0 ? (
        <EmptyState size="compact" title="No notes yet" description="Add the first internal note above." />
      ) : null}
      {!loading && notes.length > 0 ? (
        <ul className="space-y-3">
          {notes.map((note) => (
            <li key={note.id} className="rounded-lg border border-border p-4">
              <div className="flex flex-wrap items-center gap-2 text-xs text-muted">
                <span>{note.author.name}</span>
                <span>{note.created_at ? new Date(note.created_at).toLocaleString() : '—'}</span>
                {note.is_private ? <Badge variant="warning">Private</Badge> : null}
              </div>
              <p className="mt-2 whitespace-pre-wrap text-sm text-foreground">{note.body}</p>
              <Button type="button" variant="ghost" size="sm" className="mt-2" onClick={() => void onDelete(note.id)}>
                Delete
              </Button>
            </li>
          ))}
        </ul>
      ) : null}
    </Card>
  );
}
