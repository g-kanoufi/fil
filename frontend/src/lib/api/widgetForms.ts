import { apiGet, apiPatch, apiPost, apiPut } from './client';
import type { FieldDef } from './fields';

export interface WidgetFormFieldDef {
  id: number;
  field_id: number;
  sort_order: number;
  label_override: string | null;
  placeholder: string | null;
  required_override: boolean | null;
  width: string | null;
  status: string;
  field?: FieldDef;
}

export interface WidgetFormDef {
  id: number;
  key: string;
  name: string;
  site_key: string | null;
  entity: string;
  version: number;
  status: string;
  settings: Record<string, unknown>;
  fields: WidgetFormFieldDef[];
}

export interface SyncFieldInput {
  field_id: number;
  sort_order: number;
  label_override?: string | null;
  placeholder?: string | null;
  required_override?: boolean | null;
  width?: string | null;
}

export function fetchWidgetForms(): Promise<WidgetFormDef[]> {
  return apiGet<{ data: WidgetFormDef[] }>('/v1/widget-forms').then((body) => body.data);
}

export function fetchWidgetForm(id: number): Promise<WidgetFormDef> {
  return apiGet<{ data: WidgetFormDef }>(`/v1/widget-forms/${id}`).then((body) => body.data);
}

export function createWidgetForm(payload: {
  key: string;
  name: string;
  site_key?: string | null;
}): Promise<WidgetFormDef> {
  return apiPost<{ data: WidgetFormDef }>('/v1/widget-forms', payload).then((body) => body.data);
}

export function updateWidgetForm(
  id: number,
  payload: Partial<Pick<WidgetFormDef, 'name' | 'site_key' | 'status'>>,
): Promise<WidgetFormDef> {
  return apiPatch<{ data: WidgetFormDef }>(`/v1/widget-forms/${id}`, payload).then((body) => body.data);
}

export function rotateWidgetFormSiteKey(id: number): Promise<WidgetFormDef> {
  return apiPost<{ data: WidgetFormDef }>(`/v1/widget-forms/${id}/rotate-site-key`, {}).then(
    (body) => body.data,
  );
}

export function syncWidgetFormFields(id: number, fields: SyncFieldInput[]): Promise<WidgetFormDef> {
  return apiPut<{ data: WidgetFormDef }>(`/v1/widget-forms/${id}/fields`, { fields }).then(
    (body) => body.data,
  );
}
