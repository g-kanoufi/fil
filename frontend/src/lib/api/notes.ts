import { apiDelete, apiGet, apiPatch, apiPost } from './client';

export interface EntityNote {
  id: number;
  body: string;
  is_private: boolean;
  author: { id: number; name: string };
  created_at: string | null;
  updated_at: string | null;
}

export type EntityNoteSubjectType = 'lead' | 'store' | 'contact';

function notesPath(type: EntityNoteSubjectType, id: number): string {
  const segment = type === 'lead' ? 'leads' : type === 'store' ? 'stores' : 'contacts';
  return `/v1/${segment}/${id}/notes`;
}

export async function fetchEntityNotes(type: EntityNoteSubjectType, id: number): Promise<EntityNote[]> {
  const response = await apiGet<{ data: EntityNote[] }>(notesPath(type, id));
  return response.data;
}

export async function createEntityNote(
  type: EntityNoteSubjectType,
  id: number,
  payload: { body: string; is_private?: boolean },
): Promise<EntityNote> {
  const response = await apiPost<{ data: EntityNote }>(notesPath(type, id), payload);
  return response.data;
}

export async function updateEntityNote(
  noteId: number,
  payload: { body?: string; is_private?: boolean },
): Promise<EntityNote> {
  const response = await apiPatch<{ data: EntityNote }>(`/v1/entity-notes/${noteId}`, payload);
  return response.data;
}

export async function deleteEntityNote(noteId: number): Promise<void> {
  await apiDelete(`/v1/entity-notes/${noteId}`);
}
