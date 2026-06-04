import { useCallback, useEffect, useState } from 'react';
import { Badge } from '@/components/ui/Badge';
import { Button } from '@/components/ui/Button';
import { Card, CardHeader } from '@/components/ui/Card';
import { EmptyState } from '@/components/ui/EmptyState';
import { FormField } from '@/components/ui/FormField';
import { LoadingState } from '@/components/ui/LoadingState';
import { useAuth } from '@/providers/AuthProvider';
import { createStaffTodo, fetchStaffTodos, updateStaffTodo, type StaffTodo } from '@/lib/api/todos';

export function StaffTodosPanel() {
  const { can } = useAuth();
  const canView = can('todos.view');
  const canManage = can('todos.manage');

  const [todos, setTodos] = useState<StaffTodo[]>([]);
  const [loading, setLoading] = useState(true);
  const [title, setTitle] = useState('');
  const [dueAt, setDueAt] = useState('');
  const [saving, setSaving] = useState(false);
  const [error, setError] = useState<string | null>(null);

  const load = useCallback(() => {
    if (!canView) {
      setLoading(false);
      return;
    }

    setLoading(true);
    void fetchStaffTodos({ completed: false })
      .then(setTodos)
      .catch((err: unknown) => {
        setError(err instanceof Error ? err.message : 'Failed to load todos');
      })
      .finally(() => setLoading(false));
  }, [canView]);

  useEffect(() => {
    load();
  }, [load]);

  if (!canView) {
    return null;
  }

  async function onSubmit(event: React.FormEvent) {
    event.preventDefault();
    const trimmed = title.trim();
    if (!trimmed || !canManage) return;

    setSaving(true);
    setError(null);
    try {
      const todo = await createStaffTodo({
        title: trimmed,
        due_at: dueAt || undefined,
      });
      setTodos((current) => [todo, ...current]);
      setTitle('');
      setDueAt('');
    } catch (err: unknown) {
      setError(err instanceof Error ? err.message : 'Failed to create todo');
    } finally {
      setSaving(false);
    }
  }

  async function onToggle(todo: StaffTodo) {
    if (!canManage) return;
    try {
      const updated = await updateStaffTodo(todo.id, { completed: !todo.completed });
      setTodos((current) => current.filter((row) => row.id !== todo.id));
      if (!updated.completed) {
        setTodos((current) => [updated, ...current]);
      }
    } catch (err: unknown) {
      setError(err instanceof Error ? err.message : 'Failed to update todo');
    }
  }

  return (
    <Card>
      <CardHeader title="Corp todos" description="Lightweight follow-ups for your team." />
      {error ? <p className="mb-3 text-sm text-danger">{error}</p> : null}
      {canManage ? (
        <form className="mb-4 flex flex-wrap items-end gap-3" onSubmit={(event) => void onSubmit(event)}>
          <FormField
            id="corp-todo-title"
            label="New todo"
            value={title}
            onChange={(event) => setTitle(event.target.value)}
            placeholder="Schedule inspection"
            className="min-w-[14rem] flex-1"
          />
          <FormField
            id="corp-todo-due"
            label="Due"
            type="date"
            value={dueAt}
            onChange={(event) => setDueAt(event.target.value)}
          />
          <Button type="submit" size="sm" disabled={saving || !title.trim()}>
            {saving ? 'Adding…' : 'Add'}
          </Button>
        </form>
      ) : null}
      {loading ? <LoadingState size="compact" label="Loading todos…" /> : null}
      {!loading && todos.length === 0 ? (
        <EmptyState size="compact" title="No open todos" description="Add a follow-up above when something needs tracking." />
      ) : null}
      {!loading && todos.length > 0 ? (
        <ul className="space-y-2">
          {todos.map((todo) => (
            <li key={todo.id} className="flex flex-wrap items-center gap-3 rounded-lg border border-border px-3 py-2">
              {canManage ? (
                <input
                  type="checkbox"
                  checked={todo.completed}
                  onChange={() => void onToggle(todo)}
                  aria-label={`Complete ${todo.title}`}
                />
              ) : null}
              <span className="flex-1 text-sm text-foreground">{todo.title}</span>
              {todo.due_at ? <Badge variant="neutral">{todo.due_at}</Badge> : null}
            </li>
          ))}
        </ul>
      ) : null}
    </Card>
  );
}
