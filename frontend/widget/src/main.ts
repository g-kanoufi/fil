import widgetCss from './widget.css?inline';
import { applyTheme, listenForThemeUpdates, parseDatasetTheme, type WidgetTheme } from './theme';

interface WidgetConfig {
  apiBase: string;
  siteKey: string;
  recaptchaSiteKey?: string;
}

interface FormConfigField {
  key: string;
  name: string;
  label: string;
  type: string;
  required: boolean;
  placeholder: string | null;
  width: string | null;
  options: Record<string, string> | Array<{ value: string; label: string }> | null;
}

interface FormConfig {
  form_key: string;
  version: number;
  fields: FormConfigField[];
  recaptcha_site_key?: string | null;
  theme?: WidgetTheme | null;
}

const STYLE_ID = 'fil-widget-styles';

function ensureStyles(): void {
  if (document.getElementById(STYLE_ID)) {
    return;
  }

  const style = document.createElement('style');
  style.id = STYLE_ID;
  style.textContent = widgetCss;
  document.head.appendChild(style);
}

function resolveConfig(mount: HTMLElement): WidgetConfig {
  return {
    apiBase: mount.dataset.apiBase ?? window.location.origin,
    siteKey: mount.dataset.siteKey ?? 'pk_dev',
    recaptchaSiteKey: mount.dataset.recaptchaSiteKey,
  };
}

function loadRecaptcha(siteKey: string): Promise<void> {
  return new Promise((resolve, reject) => {
    if (window.grecaptcha) {
      resolve();
      return;
    }

    const script = document.createElement('script');
    script.src = `https://www.google.com/recaptcha/api.js?render=${encodeURIComponent(siteKey)}`;
    script.async = true;
    script.onload = () => resolve();
    script.onerror = () => reject(new Error('reCAPTCHA failed to load'));
    document.head.appendChild(script);
  });
}

async function recaptchaToken(config: WidgetConfig): Promise<string | undefined> {
  if (!config.recaptchaSiteKey || !window.grecaptcha) {
    return undefined;
  }

  await new Promise<void>((resolve) => window.grecaptcha.ready(resolve));

  return window.grecaptcha.execute(config.recaptchaSiteKey, { action: 'lead_intake' });
}

declare global {
  interface Window {
    grecaptcha: {
      ready: (callback: () => void) => void;
      execute: (siteKey: string, options: { action: string }) => Promise<string>;
    };
    FIL_WIDGET_THEME?: WidgetTheme;
  }
}

function escapeHtml(value: string): string {
  return value
    .replace(/&/g, '&amp;')
    .replace(/</g, '&lt;')
    .replace(/>/g, '&gt;')
    .replace(/"/g, '&quot;');
}

function fieldId(name: string): string {
  return `fil-${name.replace(/[^a-z0-9_-]/gi, '-')}`;
}

function optionsToList(
  options: FormConfigField['options'],
): Array<{ value: string; label: string }> {
  if (!options) return [];
  if (Array.isArray(options)) return options;
  return Object.entries(options).map(([value, label]) => ({ value, label: String(label) }));
}

function renderCustomField(field: FormConfigField): string {
  const name = `custom_${field.key}`;
  const id = fieldId(name);
  const required = field.required ? 'required' : '';
  const placeholder = field.placeholder ? `placeholder="${escapeHtml(field.placeholder)}"` : '';
  const label = escapeHtml(field.label);

  if (field.type === 'textarea') {
    return `
      <div class="fil-field" data-fil-field="${escapeHtml(field.key)}">
        <label class="fil-label" for="${id}">${label}</label>
        <textarea class="fil-textarea" id="${id}" name="${name}" ${required} ${placeholder}></textarea>
        <span class="fil-field-error" role="alert"></span>
      </div>`;
  }

  if (field.type === 'true_false') {
    return `
      <div class="fil-field" data-fil-field="${escapeHtml(field.key)}">
        <label class="fil-label fil-label--checkbox" for="${id}">
          <input class="fil-input" type="checkbox" id="${id}" name="${name}" />
          ${label}
        </label>
        <span class="fil-field-error" role="alert"></span>
      </div>`;
  }

  if (field.type === 'select' || field.type === 'multiselect') {
    const multiple = field.type === 'multiselect' ? 'multiple' : '';
    const options = optionsToList(field.options)
      .map((option) => `<option value="${escapeHtml(option.value)}">${escapeHtml(option.label)}</option>`)
      .join('');
    return `
      <div class="fil-field" data-fil-field="${escapeHtml(field.key)}">
        <label class="fil-label" for="${id}">${label}</label>
        <select class="fil-select" id="${id}" name="${name}" ${multiple} ${required}>${
          field.type === 'select' ? '<option value=""></option>' : ''
        }${options}</select>
        <span class="fil-field-error" role="alert"></span>
      </div>`;
  }

  const inputType = (() => {
    switch (field.type) {
      case 'number':
      case 'range':
      case 'relation_one':
        return 'number';
      case 'date':
        return 'date';
      case 'date_time':
        return 'datetime-local';
      case 'email':
        return 'email';
      case 'url':
        return 'url';
      default:
        return 'text';
    }
  })();

  return `
    <div class="fil-field" data-fil-field="${escapeHtml(field.key)}">
      <label class="fil-label" for="${id}">${label}</label>
      <input class="fil-input" type="${inputType}" id="${id}" name="${name}" ${required} ${placeholder} />
      <span class="fil-field-error" role="alert"></span>
    </div>`;
}

function renderCoreField(
  name: string,
  label: string,
  type: string,
  required = false,
): string {
  const id = fieldId(name);
  const req = required ? 'required' : '';
  return `
    <div class="fil-field" data-fil-field="${name}">
      <label class="fil-label" for="${id}">${escapeHtml(label)}</label>
      <input class="fil-input" type="${type}" id="${id}" name="${name}" ${req} />
      <span class="fil-field-error" role="alert"></span>
    </div>`;
}

async function fetchFormConfig(config: WidgetConfig): Promise<FormConfig | null> {
  try {
    const response = await fetch(
      `${config.apiBase.replace(/\/$/, '')}/api/public/v1/form-config?site_key=${encodeURIComponent(config.siteKey)}`,
      { headers: { Accept: 'application/json', 'X-FIL-Site-Key': config.siteKey } },
    );
    if (!response.ok) return null;
    const body = (await response.json()) as { data: FormConfig };
    return body.data;
  } catch {
    return null;
  }
}

function buildPayload(form: HTMLFormElement, customFields: FormConfigField[]): Record<string, unknown> {
  const data = new FormData(form);
  const payload: Record<string, unknown> = {};
  const custom: Record<string, unknown> = {};

  for (const [name, value] of data.entries()) {
    if (name.startsWith('custom_')) {
      continue;
    }
    payload[name] = value;
  }

  for (const field of customFields) {
    const name = `custom_${field.key}`;
    if (field.type === 'true_false') {
      custom[field.key] = data.has(name);
    } else if (field.type === 'multiselect' || field.type === 'relation_many') {
      custom[field.key] = data.getAll(name);
    } else {
      const value = data.get(name);
      if (value !== null && value !== '') {
        custom[field.key] = value;
      }
    }
  }

  if (Object.keys(custom).length > 0) {
    payload.custom = custom;
  }

  return payload;
}

function clearFieldErrors(form: HTMLFormElement): void {
  form.querySelectorAll('.fil-field--error').forEach((field) => {
    field.classList.remove('fil-field--error');
    const errorEl = field.querySelector('.fil-field-error');
    if (errorEl) {
      errorEl.textContent = '';
    }
  });
}

function showFieldErrors(form: HTMLFormElement, errors: Record<string, string[] | string>): void {
  clearFieldErrors(form);

  for (const [key, messages] of Object.entries(errors)) {
    const message = Array.isArray(messages) ? messages[0] : messages;
    if (!message) continue;

    const fieldKey = key.startsWith('custom.') ? key.slice('custom.'.length) : key;
    const field = form.querySelector(`[data-fil-field="${fieldKey}"]`);

    if (!field) continue;

    field.classList.add('fil-field--error');
    const errorEl = field.querySelector('.fil-field-error');
    if (errorEl) {
      errorEl.textContent = message;
    }
  }
}

function setStatus(status: HTMLElement, message: string, tone: 'default' | 'success' | 'error'): void {
  status.textContent = message;
  status.classList.remove('fil-status--success', 'fil-status--error');
  if (tone === 'success') {
    status.classList.add('fil-status--success');
  } else if (tone === 'error') {
    status.classList.add('fil-status--error');
  }
}

function initWidget(mount: HTMLElement): void {
  ensureStyles();
  applyTheme(mount, window.FIL_WIDGET_THEME, parseDatasetTheme(mount));
  listenForThemeUpdates(mount);

  const widgetConfig = resolveConfig(mount);

  void fetchFormConfig(widgetConfig).then((formConfig) => {
    applyTheme(mount, formConfig?.theme ?? undefined, parseDatasetTheme(mount));
    renderForm(mount, widgetConfig, formConfig);
  });
}

function renderForm(mount: HTMLElement, config: WidgetConfig, formConfig: FormConfig | null): void {
  const recaptchaSiteKey = config.recaptchaSiteKey ?? formConfig?.recaptcha_site_key ?? undefined;
  const customFields = formConfig?.fields ?? [];
  const customMarkup = customFields.map(renderCustomField).join('');

  mount.innerHTML = `
    <form class="fil-form" data-fil-form novalidate>
      <h2 class="fil-form__title">Franchise inquiry</h2>
      ${renderCoreField('first_name', 'First name', 'text', true)}
      ${renderCoreField('last_name', 'Last name', 'text', true)}
      ${renderCoreField('email', 'Email', 'email', true)}
      ${renderCoreField('phone', 'Phone', 'tel')}
      ${customMarkup}
      <button class="fil-btn" type="submit">Submit</button>
      <p class="fil-status" data-fil-status role="status" aria-live="polite"></p>
    </form>
  `;

  const form = mount.querySelector<HTMLFormElement>('[data-fil-form]');
  const status = mount.querySelector<HTMLElement>('[data-fil-status]');
  const submitBtn = mount.querySelector<HTMLButtonElement>('.fil-btn');

  if (!form || !status || !submitBtn) {
    return;
  }

  if (recaptchaSiteKey) {
    config.recaptchaSiteKey = recaptchaSiteKey;
    void loadRecaptcha(recaptchaSiteKey).catch(() => {
      setStatus(status, 'Security check failed to load.', 'error');
    });
  }

  form.addEventListener('submit', (event) => {
    event.preventDefault();
    clearFieldErrors(form);

    if (!form.checkValidity()) {
      form.reportValidity();
      form.querySelectorAll(':invalid').forEach((input) => {
        const field = input.closest('.fil-field');
        if (field) {
          field.classList.add('fil-field--error');
          const errorEl = field.querySelector('.fil-field-error');
          if (errorEl && !errorEl.textContent) {
            errorEl.textContent = 'This field is required.';
          }
        }
      });
      setStatus(status, 'Please fix the highlighted fields.', 'error');
      return;
    }

    setStatus(status, 'Submitting…', 'default');
    submitBtn.disabled = true;

    const payload = buildPayload(form, customFields);

    void (async () => {
      const token = await recaptchaToken(config);

      if (token) {
        payload['g-recaptcha-response'] = token;
      }

      payload.site_key = config.siteKey;

      return fetch(`${config.apiBase.replace(/\/$/, '')}/api/public/v1/leads`, {
        method: 'POST',
        headers: {
          'Content-Type': 'application/json',
          Accept: 'application/json',
          'X-FIL-Site-Key': config.siteKey,
        },
        body: JSON.stringify(payload),
      });
    })()
      .then(async (response) => {
        if (response.status === 422) {
          const body = (await response.json()) as { errors?: Record<string, string[] | string> };
          if (body.errors) {
            showFieldErrors(form, body.errors);
          }
          throw new Error('validation');
        }

        if (!response.ok) {
          throw new Error(await response.text());
        }

        setStatus(status, 'Thanks — we received your inquiry.', 'success');
        form.reset();
        clearFieldErrors(form);
      })
      .catch((error: Error) => {
        if (error.message !== 'validation') {
          setStatus(status, 'Something went wrong. Please try again.', 'error');
        } else {
          setStatus(status, 'Please fix the highlighted fields.', 'error');
        }
      })
      .finally(() => {
        submitBtn.disabled = false;
      });
  });
}

document.querySelectorAll<HTMLElement>('[data-fil-widget="lead-form"]').forEach(initWidget);
