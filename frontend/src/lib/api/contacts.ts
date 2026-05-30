import { apiGet } from './client';

export interface Contact {
  id: number;
  name: string;
  first_name: string | null;
  last_name: string | null;
  email: string;
  roles: string[];
  created_at: string | null;
  updated_at: string | null;
}

export function fetchContact(id: number): Promise<Contact> {
  return apiGet<{ data: Contact }>(`/v1/contacts/${id}`).then((body) => body.data);
}
