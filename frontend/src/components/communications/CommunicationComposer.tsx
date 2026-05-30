import { FormEvent, useState } from 'react';
import { Alert } from '@/components/ui/Alert';
import { Button } from '@/components/ui/Button';
import { FormField } from '@/components/ui/FormField';
import { sendCommunication } from '@/lib/api/communications';
import { cn } from '@/lib/cn';
import { formControl, formFocus, segmentActive, segmentInactive } from '@/lib/ui/tokens';

interface CommunicationComposerProps {
  leadId: number;
  canSend: boolean;
  prospectEmail?: string | null;
  prospectPhone?: string | null;
  onSent: () => void;
}

export function CommunicationComposer({
  leadId,
  canSend,
  prospectEmail,
  prospectPhone,
  onSent,
}: CommunicationComposerProps) {
  const [channel, setChannel] = useState<'email' | 'sms'>('email');
  const [subject, setSubject] = useState('');
  const [message, setMessage] = useState('');
  const [sending, setSending] = useState(false);
  const [error, setError] = useState<string | null>(null);

  if (!canSend) {
    return null;
  }

  const channelDisabled =
    (channel === 'email' && !prospectEmail) || (channel === 'sms' && !prospectPhone);

  async function onSubmit(event: FormEvent) {
    event.preventDefault();

    if (!message.trim() || channelDisabled) {
      return;
    }

    setSending(true);
    setError(null);

    try {
      await sendCommunication({
        lead_id: leadId,
        channel,
        message: message.trim(),
        subject: channel === 'email' ? subject.trim() || undefined : undefined,
      });

      setMessage('');
      setSubject('');
      onSent();
    } catch (submitError: unknown) {
      setError(submitError instanceof Error ? submitError.message : 'Send failed');
    } finally {
      setSending(false);
    }
  }

  return (
    <form onSubmit={(event) => void onSubmit(event)} className="mb-4 space-y-3 rounded-lg border border-border p-4">
      <p className="text-sm font-medium text-foreground">Send message</p>

      <div className="flex flex-wrap gap-2">
        <button
          type="button"
          className={channel === 'email' ? segmentActive : segmentInactive}
          onClick={() => setChannel('email')}
        >
          Email
        </button>
        <button
          type="button"
          className={channel === 'sms' ? segmentActive : segmentInactive}
          onClick={() => setChannel('sms')}
        >
          SMS
        </button>
      </div>

      {channel === 'email' && !prospectEmail ? (
        <p className="text-sm text-muted">Prospect has no email on file.</p>
      ) : null}
      {channel === 'sms' && !prospectPhone ? (
        <p className="text-sm text-muted">Prospect has no phone on file.</p>
      ) : null}

      {channel === 'email' ? (
        <FormField
          label="Subject"
          value={subject}
          onChange={(event) => setSubject(event.target.value)}
          placeholder="Subject line"
        />
      ) : null}

      <label className="block text-sm font-medium text-foreground">
        Message
        <textarea
          className={cn('mt-1', formControl, formFocus)}
          rows={4}
          value={message}
          onChange={(event) => setMessage(event.target.value)}
          placeholder={channel === 'sms' ? 'SMS message…' : 'Email body…'}
          required
        />
      </label>

      {error ? <Alert variant="error">{error}</Alert> : null}

      <Button type="submit" disabled={sending || channelDisabled || !message.trim()}>
        {sending ? 'Sending…' : channel === 'email' ? 'Send email' : 'Send SMS'}
      </Button>
    </form>
  );
}
