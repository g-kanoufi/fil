export interface NavItem {
  id: string;
  label: string;
  path: string;
  children?: NavItem[];
}

export interface NavSection {
  type: 'section';
  label: string;
}

export type NavigationEntry = NavItem | NavSection;

export interface UiRestrictions {
  tabs: string[];
  leads: { menuItems: string[]; subMenuItems: string[] };
  stores: { menuItems: string[]; subMenuItems: string[] };
  contacts: { menuItems: string[]; subMenuItems: string[] };
  nav_admin: { menuItems: string[]; subMenuItems: string[] };
  nav_stores: { menuItems: string[]; subMenuItems: string[] };
  nav_contacts: { menuItems: string[]; subMenuItems: string[] };
  resources: number[];
  allowed_zai_resources: number[];
  features: Record<string, boolean>;
}

export interface SessionUser {
  id: number;
  email: string;
  first_name: string | null;
  last_name: string | null;
  name: string;
  roles: string[];
  primary_role: string | null;
  permissions: string[];
  navigation: NavigationEntry[];
  ui_restrictions: UiRestrictions;
  authorized_notes: Record<string, { notes: boolean; private_notes: boolean }>;
  hidden_field_keys: string[];
  readonly_field_keys: string[];
  user_has_panel_access: boolean;
}

export interface LeadApplicationStatusChoice {
  value: string;
  label: string;
  slug: string;
  category: string | null;
  closed: boolean;
  pipeline_phase: number | null;
  sort: number;
  filter_values: string[];
}

export interface AppConfig {
  options: Record<string, unknown>;
  menus: {
    top_menus: Array<{ name: string; label: string; url: string }>;
    menus_with_columns: Record<string, unknown>;
  };
  lead_application_status?: {
    choices: LeadApplicationStatusChoice[];
    groups: {
      active: { submenu_key: string; values: string[]; filter_values: string[] };
      won: { submenu_key: string; values: string[]; filter_values: string[] };
      closed: { values: string[]; filter_values: string[] };
    };
  };
  pipeline?: {
    phases: Array<{ id: number; label: string; description: string }>;
  };
}

export interface SessionResponse {
  data: SessionUser;
}

export type AuthStatus = 'loading' | 'authenticated' | 'unauthenticated';
