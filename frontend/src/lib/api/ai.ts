import { apiGet, apiPost } from './client';

export interface AiMessage {
  id: number;
  role: 'user' | 'assistant' | string;
  content: string;
  created_at: string | null;
}

export interface AiThread {
  id: number;
  title: string | null;
  lead_id: number | null;
  status: string;
  updated_at: string | null;
  messages?: AiMessage[];
}

interface AiThreadResponse {
  data: AiThread;
}

interface AiThreadListResponse {
  data: AiThread[];
}

interface SendMessageResponse {
  data: {
    user_message: AiMessage;
    assistant_message: AiMessage;
  };
}

const baseUrl = import.meta.env.VITE_API_URL ?? '/api';

export function fetchAiThreads(): Promise<AiThread[]> {
  return apiGet<AiThreadListResponse>('/v1/ai/threads').then((body) => body.data);
}

export function createAiThread(title?: string): Promise<AiThread> {
  return apiPost<AiThreadResponse>('/v1/ai/threads', { title }).then((body) => body.data);
}

export function fetchAiThread(id: number): Promise<AiThread> {
  return apiGet<AiThreadResponse>(`/v1/ai/threads/${id}`).then((body) => body.data);
}

export function sendAiMessage(threadId: number, content: string): Promise<SendMessageResponse['data']> {
  return apiPost<SendMessageResponse>(`/v1/ai/threads/${threadId}/messages`, { content }).then(
    (body) => body.data,
  );
}

export interface StreamAiMessageResult {
  userContent: string;
  assistantContent: string;
  assistantMessageId: number | null;
}

export async function streamAiMessage(threadId: number, content: string): Promise<StreamAiMessageResult> {
  const response = await fetch(`${baseUrl}/v1/ai/threads/${threadId}/stream`, {
    method: 'POST',
    credentials: 'include',
    headers: {
      Accept: 'text/event-stream',
      'Content-Type': 'application/json',
    },
    body: JSON.stringify({ content }),
  });

  if (!response.ok || !response.body) {
    const fallback = await sendAiMessage(threadId, content);

    return {
      userContent: fallback.user_message.content,
      assistantContent: fallback.assistant_message.content,
      assistantMessageId: fallback.assistant_message.id,
    };
  }

  const reader = response.body.getReader();
  const decoder = new TextDecoder();
  let buffer = '';
  let assistantContent = '';
  let assistantMessageId: number | null = null;

  while (true) {
    const { done, value } = await reader.read();

    if (done) {
      break;
    }

    buffer += decoder.decode(value, { stream: true });

    const events = buffer.split('\n\n');
    buffer = events.pop() ?? '';

    for (const event of events) {
      const dataLine = event.split('\n').find((line) => line.startsWith('data: '));

      if (!dataLine) {
        continue;
      }

      const payload = JSON.parse(dataLine.slice(6)) as {
        token?: string;
        done?: boolean;
        message_id?: number;
      };

      if (payload.token) {
        assistantContent += payload.token;
      }

      if (payload.done && payload.message_id) {
        assistantMessageId = payload.message_id;
      }
    }
  }

  return {
    userContent: content,
    assistantContent,
    assistantMessageId,
  };
}
