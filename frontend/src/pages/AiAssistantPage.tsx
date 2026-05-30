import { FormEvent, useEffect, useState } from 'react';
import { Alert } from '@/components/ui/Alert';
import { Button } from '@/components/ui/Button';
import { Card, CardHeader } from '@/components/ui/Card';
import { LoadingState } from '@/components/ui/LoadingState';
import { PageHeader } from '@/components/ui/PageHeader';
import { cn } from '@/lib/cn';
import { surface, formFocus } from '@/lib/ui/tokens';
import {
  createAiThread,
  fetchAiThread,
  streamAiMessage,
  type AiMessage,
  type AiThread,
} from '@/lib/api/ai';

export function AiAssistantPage() {
  const [thread, setThread] = useState<AiThread | null>(null);
  const [messages, setMessages] = useState<AiMessage[]>([]);
  const [draft, setDraft] = useState('');
  const [loading, setLoading] = useState(true);
  const [sending, setSending] = useState(false);
  const [error, setError] = useState<string | null>(null);

  useEffect(() => {
    let cancelled = false;

    void (async () => {
      try {
        const created = await createAiThread('FIL assistant');
        const loaded = await fetchAiThread(created.id);

        if (!cancelled) {
          setThread(loaded);
          setMessages(loaded.messages ?? []);
        }
      } catch (loadError: unknown) {
        if (!cancelled) {
          setError(loadError instanceof Error ? loadError.message : 'Failed to start assistant');
        }
      } finally {
        if (!cancelled) {
          setLoading(false);
        }
      }
    })();

    return () => {
      cancelled = true;
    };
  }, []);

  async function onSubmit(event: FormEvent) {
    event.preventDefault();

    if (!thread || draft.trim() === '') {
      return;
    }

    const content = draft.trim();
    setSending(true);
    setError(null);
    setDraft('');

    const tempUserId = Date.now();
    const tempAssistantId = tempUserId + 1;

    setMessages((current) => [
      ...current,
      { id: tempUserId, role: 'user', content, created_at: new Date().toISOString() },
      { id: tempAssistantId, role: 'assistant', content: '', created_at: new Date().toISOString() },
    ]);

    try {
      const result = await streamAiMessage(thread.id, content);

      setMessages((current) =>
        current.map((message) => {
          if (message.id === tempUserId) {
            return { ...message, content: result.userContent };
          }

          if (message.id === tempAssistantId) {
            return {
              ...message,
              id: result.assistantMessageId ?? tempAssistantId,
              content: result.assistantContent,
            };
          }

          return message;
        }),
      );
    } catch (sendError: unknown) {
      setError(sendError instanceof Error ? sendError.message : 'Failed to send message');
      setMessages((current) => current.filter((message) => message.id !== tempAssistantId));
    } finally {
      setSending(false);
    }
  }

  return (
    <>
      <PageHeader
        title="Assistant"
        description="Streaming replies via SSE when available; falls back to standard API otherwise."
      />

      {loading ? <LoadingState label="Starting conversation…" /> : null}
      {error ? <Alert variant="error" className="mb-4">{error}</Alert> : null}

      <Card className="mb-4 max-w-3xl">
        <section aria-label="Conversation" className="min-h-60 space-y-4">
          {messages.length === 0 && !loading ? (
            <p className="text-sm text-muted">No messages yet.</p>
          ) : null}
          {messages.map((message) => (
            <article
              key={message.id}
              className={cn(
                'rounded-lg px-4 py-3 text-sm',
                message.role === 'user'
                  ? surface.aiBubble
                  : 'mr-8 border border-border bg-surface-muted text-foreground',
              )}
            >
              <strong className="block text-xs uppercase tracking-wide text-muted">
                {message.role === 'user' ? 'You' : 'Assistant'}
              </strong>
              <p className="mt-1 whitespace-pre-wrap">
                {message.content || (sending && message.role === 'assistant' ? '…' : '')}
              </p>
            </article>
          ))}
        </section>
      </Card>

      <Card className="max-w-3xl">
        <CardHeader title="Message" />
        <form onSubmit={onSubmit} className="space-y-4">
          <textarea
            id="ai-prompt"
            value={draft}
            onChange={(event) => setDraft(event.target.value)}
            rows={3}
            disabled={loading || sending || !thread}
            className={cn('block w-full rounded-lg border border-border bg-surface px-3 py-2 text-sm disabled:opacity-60', formFocus)}
          />
          <Button type="submit" disabled={loading || sending || !thread || draft.trim() === ''}>
            {sending ? 'Sending…' : 'Send'}
          </Button>
        </form>
      </Card>
    </>
  );
}
