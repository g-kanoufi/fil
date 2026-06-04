import { apiDelete, apiGet, apiPatch, apiPost } from './client';

export interface StaffTodo {
  id: number;
  title: string;
  body: string | null;
  due_at: string | null;
  completed: boolean;
  completed_at: string | null;
  assignee: { id: number; name: string } | null;
  author: { id: number; name: string };
  subject: { type: string; id: number } | null;
  created_at: string | null;
  updated_at: string | null;
}

export async function fetchStaffTodos(params?: {
  completed?: boolean;
  assignee?: 'me';
}): Promise<StaffTodo[]> {
  const search = new URLSearchParams();
  if (params?.completed === true) search.set('completed', '1');
  if (params?.completed === false) search.set('completed', '0');
  if (params?.assignee === 'me') search.set('assignee', 'me');
  const query = search.toString();
  const response = await apiGet<{ data: StaffTodo[] }>(`/v1/todos${query ? `?${query}` : ''}`);
  return response.data;
}

export async function createStaffTodo(payload: {
  title: string;
  body?: string;
  due_at?: string;
  subject_type?: string;
  subject_id?: number;
}): Promise<StaffTodo> {
  const response = await apiPost<{ data: StaffTodo }>('/v1/todos', payload);
  return response.data;
}

export async function updateStaffTodo(
  id: number,
  payload: Partial<{
    title: string;
    body: string | null;
    due_at: string | null;
    completed: boolean;
  }>,
): Promise<StaffTodo> {
  const response = await apiPatch<{ data: StaffTodo }>(`/v1/todos/${id}`, payload);
  return response.data;
}

export async function deleteStaffTodo(id: number): Promise<void> {
  await apiDelete(`/v1/todos/${id}`);
}
