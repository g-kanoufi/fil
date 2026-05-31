import { apiDelete, apiGet, apiPatch, apiPost } from './client';

export type FieldType =
  | 'text'
  | 'textarea'
  | 'number'
  | 'range'
  | 'select'
  | 'multiselect'
  | 'true_false'
  | 'date'
  | 'date_time'
  | 'email'
  | 'url'
  | 'relation_one'
  | 'relation_many';

export interface FieldConfig {
  choices?: Record<string, string> | Array<{ value: string; label: string }>;
  related_entity?: string;
  [key: string]: unknown;
}

export interface FieldDef {
  id: number;
  key: string;
  name: string;
  type: FieldType;
  entity: string;
  storage: string;
  maps_to_column: string | null;
  config: FieldConfig;
  required: boolean;
  is_filterable: boolean;
  is_sortable: boolean;
  is_facetable: boolean;
  sort_order: number;
  status: string;
  field_group_id: number;
}

export interface FieldGroupDef {
  id: number;
  key: string;
  title: string;
  slug: string | null;
  sort_order: number;
  status: string;
  fields: FieldDef[];
}

export interface RelatableCatalogue {
  types: FieldType[];
  relatable_entities: Array<{ key: string; label: string }>;
}

export interface CreateFieldPayload {
  field_group_id: number;
  entity: string;
  key: string;
  name: string;
  type: FieldType;
  required?: boolean;
  is_filterable?: boolean;
  config?: FieldConfig | null;
}

export function fetchFieldGroups(
  entity = 'lead',
  options?: { context?: 'widget' },
): Promise<FieldGroupDef[]> {
  const params = new URLSearchParams({ entity });
  if (options?.context) {
    params.set('context', options.context);
  }

  return apiGet<{ data: FieldGroupDef[] }>(`/v1/field-groups?${params.toString()}`).then(
    (body) => body.data,
  );
}

/** Role-aware schema for entity detail forms (not admin field-groups). */
export function fetchFieldSchema(entity: string): Promise<FieldGroupDef[]> {
  return apiGet<{ data: { groups: FieldGroupDef[] } }>(
    `/v1/fields?entity=${encodeURIComponent(entity)}`,
  ).then((body) => body.data.groups);
}

export function fetchRelatableEntities(): Promise<RelatableCatalogue> {
  return apiGet<{ data: RelatableCatalogue }>('/v1/fields/relatable-entities').then(
    (body) => body.data,
  );
}

export function createField(payload: CreateFieldPayload): Promise<FieldDef> {
  return apiPost<{ data: FieldDef }>('/v1/fields', payload).then((body) => body.data);
}

export function updateField(id: number, payload: Partial<CreateFieldPayload> & { status?: string }): Promise<FieldDef> {
  return apiPatch<{ data: FieldDef }>(`/v1/fields/${id}`, payload).then((body) => body.data);
}

export function deleteField(id: number): Promise<void> {
  return apiDelete(`/v1/fields/${id}`).then(() => undefined);
}

export function reorderFields(
  items: Array<{ id: number; sort_order: number; field_group_id?: number }>,
): Promise<void> {
  return apiPost('/v1/fields/reorder', { items }).then(() => undefined);
}

export function createFieldGroup(payload: {
  key: string;
  title: string;
  slug?: string | null;
}): Promise<FieldGroupDef> {
  return apiPost<{ data: FieldGroupDef }>('/v1/field-groups', payload).then((body) => body.data);
}
