import type { SVGProps } from 'react';

type IconProps = SVGProps<SVGSVGElement>;

function IconBase({ children, ...props }: IconProps & { children: React.ReactNode }) {
  return (
    <svg
      width="20"
      height="20"
      viewBox="0 0 24 24"
      fill="none"
      stroke="currentColor"
      strokeWidth="1.75"
      strokeLinecap="round"
      strokeLinejoin="round"
      aria-hidden
      {...props}
    >
      {children}
    </svg>
  );
}

export function IconDashboard(props: IconProps) {
  return (
    <IconBase {...props}>
      <path d="M3 10.5 12 3l9 7.5" />
      <path d="M5 9.5V20h5v-6h4v6h5V9.5" />
    </IconBase>
  );
}

export function IconLeads(props: IconProps) {
  return (
    <IconBase {...props}>
      <circle cx="12" cy="8" r="3.5" />
      <path d="M5 20c0-3.5 3.1-6 7-6s7 2.5 7 6" />
    </IconBase>
  );
}

export function IconStores(props: IconProps) {
  return (
    <IconBase {...props}>
      <path d="M4 10 12 4l8 6v10H4V10z" />
      <path d="M9 20v-6h6v6" />
    </IconBase>
  );
}

export function IconContacts(props: IconProps) {
  return (
    <IconBase {...props}>
      <path d="M17 21v-2a4 4 0 0 0-4-4H6a4 4 0 0 0-4 4v2" />
      <circle cx="9.5" cy="7" r="3.5" />
      <path d="M22 21v-2a4 4 0 0 0-3-3.87" />
      <path d="M16 3.13a4 4 0 0 1 0 7.75" />
    </IconBase>
  );
}

export function IconDocuments(props: IconProps) {
  return (
    <IconBase {...props}>
      <path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z" />
      <path d="M14 2v6h6" />
    </IconBase>
  );
}

export function IconFdd(props: IconProps) {
  return (
    <IconBase {...props}>
      <path d="M12 3 4 7v10l8 4 8-4V7l-8-4z" />
      <path d="M12 11 4 7" />
      <path d="m12 11 8-4" />
      <path d="M12 11v10" />
    </IconBase>
  );
}

export function IconRoyalties(props: IconProps) {
  return (
    <IconBase {...props}>
      <path d="M12 2v20" />
      <path d="M17 5H9.5a3.5 3.5 0 0 0 0 7H14a3.5 3.5 0 0 1 0 7H6" />
    </IconBase>
  );
}

export function IconAch(props: IconProps) {
  return (
    <IconBase {...props}>
      <path d="M7 10h10" />
      <path d="M7 14h10" />
      <path d="M4 6h16v12H4z" />
    </IconBase>
  );
}

export function IconAi(props: IconProps) {
  return (
    <IconBase {...props}>
      <path d="M12 3 9.5 9 3 10.5 9.5 12 12 21l2.5-9L21 10.5 14.5 9 12 3z" />
    </IconBase>
  );
}

export function IconSettings(props: IconProps) {
  return (
    <IconBase {...props}>
      <circle cx="12" cy="12" r="3" />
      <path d="M12 2v2M12 20v2M4.93 4.93l1.41 1.41M17.66 17.66l1.41 1.41M2 12h2M20 12h2M4.93 19.07l1.41-1.41M17.66 6.34l1.41-1.41" />
    </IconBase>
  );
}

export function IconProfile(props: IconProps) {
  return (
    <IconBase {...props}>
      <circle cx="12" cy="8" r="3.5" />
      <path d="M5 20c0-3.5 3.1-6 7-6s7 2.5 7 6" />
    </IconBase>
  );
}

export function IconMail(props: IconProps) {
  return (
    <IconBase {...props}>
      <path d="M4 6h16v12H4z" />
      <path d="m4 7 8 6 8-6" />
    </IconBase>
  );
}

export function IconBell(props: IconProps) {
  return (
    <IconBase {...props}>
      <path d="M18 16H6l1.2-1.6A6 6 0 0 0 8 10V7a4 4 0 1 1 8 0v3a6 6 0 0 0 .8 4.4L18 16z" />
      <path d="M10 20a2 2 0 0 0 4 0" />
    </IconBase>
  );
}

export function IconFields(props: IconProps) {
  return (
    <IconBase {...props}>
      <path d="M4 6h16M4 12h16M4 18h10" />
    </IconBase>
  );
}

export function IconWidget(props: IconProps) {
  return (
    <IconBase {...props}>
      <rect x="4" y="4" width="7" height="7" rx="1" />
      <rect x="13" y="4" width="7" height="7" rx="1" />
      <rect x="4" y="13" width="16" height="7" rx="1" />
    </IconBase>
  );
}

export function IconDrips(props: IconProps) {
  return (
    <IconBase {...props}>
      <path d="M12 3a5 5 0 0 0-5 5c0 3.5 5 9 5 9s5-5.5 5-9a5 5 0 0 0-5-5z" />
    </IconBase>
  );
}

export function IconMenu(props: IconProps) {
  return (
    <IconBase {...props}>
      <path d="M4 7h16M4 12h16M4 17h16" />
    </IconBase>
  );
}

export function IconClose(props: IconProps) {
  return (
    <IconBase {...props}>
      <path d="M6 6l12 12M18 6 6 18" />
    </IconBase>
  );
}

export function IconMoon(props: IconProps) {
  return (
    <IconBase {...props}>
      <path d="M21 14.5A8.5 8.5 0 1 1 9.5 3 7 7 0 0 0 21 14.5z" />
    </IconBase>
  );
}

export function IconSun(props: IconProps) {
  return (
    <IconBase {...props}>
      <circle cx="12" cy="12" r="4" />
      <path d="M12 2v2M12 20v2M4.93 4.93l1.41 1.41M17.66 17.66l1.41 1.41M2 12h2M20 12h2M4.93 19.07l1.41-1.41M17.66 6.34l1.41-1.41" />
    </IconBase>
  );
}

export function IconChevronDown(props: IconProps) {
  return (
    <IconBase {...props}>
      <path d="m6 9 6 6 6-6" />
    </IconBase>
  );
}

export function IconChevronRight(props: IconProps) {
  return (
    <IconBase {...props}>
      <path d="m9 6 6 6-6 6" />
    </IconBase>
  );
}

export function IconLogout(props: IconProps) {
  return (
    <IconBase {...props}>
      <path d="M10 17l-1 1H5a2 2 0 0 1-2-2V8a2 2 0 0 1 2-2h4l1 1" />
      <path d="M15 12H8" />
      <path d="m18 9 3 3-3 3" />
    </IconBase>
  );
}

const navIconMap = {
  dashboard: IconDashboard,
  leads: IconLeads,
  stores: IconStores,
  contacts: IconContacts,
  documents: IconDocuments,
  fdd: IconFdd,
  royalties: IconRoyalties,
  ach: IconAch,
  ai: IconAi,
  profile: IconProfile,
  notifications: IconBell,
  settings: IconSettings,
  'settings-notifications': IconBell,
  'settings-rules': IconBell,
  'settings-mail': IconMail,
  'settings-drips': IconDrips,
  'settings-fields': IconFields,
  'settings-widget': IconWidget,
} as const;

export function NavIcon({ id }: { id: string }) {
  const Icon = navIconMap[id as keyof typeof navIconMap] ?? IconSettings;

  return <Icon className="h-5 w-5 shrink-0 opacity-90" />;
}
