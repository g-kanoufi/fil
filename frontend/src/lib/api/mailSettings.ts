import { apiGet, apiPost } from './client';

export interface MailSettingsStatus {
  mailer: string;
  using_mailgun: boolean;
  configured: boolean;
  from_address: string;
  from_name: string;
  outbound: {
    guarded: boolean;
    allows_real_recipients: boolean;
    sink_addresses: string[];
    effective_mailer: string;
  };
  mailgun: {
    domain: string | null;
    domain_state: string | null;
    verified: boolean;
    error: string | null;
    webhook_signing_configured: boolean;
  };
}

export interface MailTestResult {
  sent: boolean;
  recipient: string;
  intended_recipient?: string;
  error?: string | null;
}

export function fetchMailSettings(): Promise<MailSettingsStatus> {
  return apiGet<{ data: MailSettingsStatus }>('/v1/settings/mail').then((body) => body.data);
}

export function verifyMailgunDomain(): Promise<{ mailgun: MailSettingsStatus['mailgun']; status: MailSettingsStatus }> {
  return apiPost<{ data: { mailgun: MailSettingsStatus['mailgun']; status: MailSettingsStatus } }>(
    '/v1/settings/mail/verify',
    {},
  ).then((body) => body.data);
}

export function sendMailTest(recipient?: string): Promise<MailTestResult> {
  return apiPost<{ data: MailTestResult }>('/v1/settings/mail/test', recipient ? { recipient } : {})
    .then((body) => body.data);
}
