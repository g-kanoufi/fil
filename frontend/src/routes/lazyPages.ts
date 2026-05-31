import { lazy } from 'react';

export const HomePage = lazy(() => import('@/pages/HomePage').then((m) => ({ default: m.HomePage })));
export const GridPage = lazy(() => import('@/pages/GridPage').then((m) => ({ default: m.GridPage })));
export const HistoryPage = lazy(() =>
  import('@/pages/HistoryPage').then((m) => ({ default: m.HistoryPage })),
);
export const LeadDetailPage = lazy(() =>
  import('@/pages/LeadDetailPage').then((m) => ({ default: m.LeadDetailPage })),
);
export const StoreDetailPage = lazy(() =>
  import('@/pages/StoreDetailPage').then((m) => ({ default: m.StoreDetailPage })),
);
export const ContactDetailPage = lazy(() =>
  import('@/pages/ContactDetailPage').then((m) => ({ default: m.ContactDetailPage })),
);
export const ClosingsPage = lazy(() =>
  import('@/pages/ClosingsPage').then((m) => ({ default: m.ClosingsPage })),
);
export const ClosingDetailPage = lazy(() =>
  import('@/pages/ClosingDetailPage').then((m) => ({ default: m.ClosingDetailPage })),
);
export const DocumentsPage = lazy(() =>
  import('@/pages/DocumentsPage').then((m) => ({ default: m.DocumentsPage })),
);
export const DocumentDetailPage = lazy(() =>
  import('@/pages/DocumentDetailPage').then((m) => ({ default: m.DocumentDetailPage })),
);
export const FddPage = lazy(() => import('@/pages/FddPage').then((m) => ({ default: m.FddPage })));
export const RoyaltiesPage = lazy(() =>
  import('@/pages/RoyaltiesPage').then((m) => ({ default: m.RoyaltiesPage })),
);
export const AchPage = lazy(() => import('@/pages/AchPage').then((m) => ({ default: m.AchPage })));
export const AiAssistantPage = lazy(() =>
  import('@/pages/AiAssistantPage').then((m) => ({ default: m.AiAssistantPage })),
);
export const ProfilePage = lazy(() =>
  import('@/pages/ProfilePage').then((m) => ({ default: m.ProfilePage })),
);
export const NotificationPreferencesPage = lazy(() =>
  import('@/pages/NotificationPreferencesPage').then((m) => ({
    default: m.NotificationPreferencesPage,
  })),
);
export const NotificationRulesAdminPage = lazy(() =>
  import('@/pages/NotificationRulesAdminPage').then((m) => ({
    default: m.NotificationRulesAdminPage,
  })),
);
export const MailSettingsPage = lazy(() =>
  import('@/pages/MailSettingsPage').then((m) => ({ default: m.MailSettingsPage })),
);
export const DripCampaignsAdminPage = lazy(() =>
  import('@/pages/DripCampaignsAdminPage').then((m) => ({ default: m.DripCampaignsAdminPage })),
);
export const FieldsAdminPage = lazy(() =>
  import('@/pages/FieldsAdminPage').then((m) => ({ default: m.FieldsAdminPage })),
);
export const WidgetFormBuilderPage = lazy(() =>
  import('@/pages/WidgetFormBuilderPage').then((m) => ({ default: m.WidgetFormBuilderPage })),
);
export const WidgetDemoPage = lazy(() =>
  import('@/pages/WidgetDemoPage').then((m) => ({ default: m.WidgetDemoPage })),
);
export const SettingsPage = lazy(() =>
  import('@/pages/SettingsPage').then((m) => ({ default: m.SettingsPage })),
);
