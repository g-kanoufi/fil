import { Suspense } from 'react';
import { BrowserRouter, Navigate, Route, Routes } from 'react-router-dom';
import { AppConfigBrandSync } from '@/components/branding/AppConfigBrandSync';
import { AuthApiNavigation } from '@/components/auth/AuthApiNavigation';
import { RequireAuth } from '@/components/auth/RequireAuth';
import { RequirePermission } from '@/components/auth/RequirePermission';
import { AppLayout } from '@/components/layout/AppLayout';
import { RouteFallback } from '@/components/layout/RouteFallback';
import { AuthProvider, useAuth } from '@/providers/AuthProvider';
import { ClientBrandingProvider } from '@/providers/ClientBrandingProvider';
import { ThemeProvider } from '@/providers/ThemeProvider';
import { LoginPage } from '@/pages/LoginPage';
import { ForbiddenPage } from '@/pages/ForbiddenPage';
import {
  AchPage,
  AiAssistantPage,
  DocumentDetailPage,
  DocumentsPage,
  DripCampaignsAdminPage,
  FddPage,
  FieldsAdminPage,
  GridPage,
  HistoryPage,
  HomePage,
  LeadDetailPage,
  MailSettingsPage,
  NotificationPreferencesPage,
  NotificationRulesAdminPage,
  ProfilePage,
  RoyaltiesPage,
  SettingsPage,
  StoreDetailPage,
  ContactDetailPage,
  WidgetFormBuilderPage,
} from '@/routes/lazyPages';

function AuthApiNavigationBridge() {
  const { expireSession } = useAuth();

  return <AuthApiNavigation onSessionExpired={expireSession} />;
}

export function AppRoutes() {
  return (
    <Suspense fallback={<RouteFallback />}>
      <Routes>
        <Route path="/login" element={<LoginPage />} />
        <Route path="/forbidden" element={<ForbiddenPage />} />

        <Route element={<RequireAuth />}>
          <Route element={<AppLayout />}>
            <Route path="/" element={<HomePage />} />
            <Route path="/history" element={<HistoryPage />} />
          <Route
            path="/reports/leads"
            element={
              <RequirePermission permission="leads.view">
                <GridPage title="Leads" resource="leads" />
              </RequirePermission>
            }
          />
          <Route
            path="/reports/leads/:id"
            element={
              <RequirePermission permission="leads.view">
                <LeadDetailPage />
              </RequirePermission>
            }
          />
          <Route
            path="/reports/stores"
            element={
              <RequirePermission permission="stores.view">
                <GridPage title="Stores" resource="stores" />
              </RequirePermission>
            }
          />
          <Route
            path="/reports/stores/:id"
            element={
              <RequirePermission permission="stores.view">
                <StoreDetailPage />
              </RequirePermission>
            }
          />
          <Route
            path="/reports/contacts"
            element={
              <RequirePermission permission="contacts.view">
                <GridPage title="Contacts" resource="contacts" />
              </RequirePermission>
            }
          />
          <Route
            path="/reports/contacts/:id"
            element={
              <RequirePermission permission="contacts.view">
                <ContactDetailPage />
              </RequirePermission>
            }
          />
          <Route
            path="/documents"
            element={
              <RequirePermission permission="documents.view">
                <DocumentsPage />
              </RequirePermission>
            }
          />
          <Route
            path="/documents/:id"
            element={
              <RequirePermission permission="documents.view">
                <DocumentDetailPage />
              </RequirePermission>
            }
          />
          <Route
            path="/fdd"
            element={
              <RequirePermission permission="fdd.view">
                <FddPage />
              </RequirePermission>
            }
          />
          <Route
            path="/reports/royalties"
            element={
              <RequirePermission permission="royalties.view">
                <RoyaltiesPage />
              </RequirePermission>
            }
          />
          <Route
            path="/reports/ach"
            element={
              <RequirePermission permission="ach.view">
                <AchPage />
              </RequirePermission>
            }
          />
          <Route
            path="/ai"
            element={
              <RequirePermission permission="ai.use">
                <AiAssistantPage />
              </RequirePermission>
            }
          />
          <Route path="/profile" element={<ProfilePage />} />
          <Route path="/settings/notifications" element={<NotificationPreferencesPage />} />
          <Route
            path="/settings/notifications/rules"
            element={
              <RequirePermission permission="settings.manage">
                <NotificationRulesAdminPage />
              </RequirePermission>
            }
          />
          <Route
            path="/settings/drips"
            element={
              <RequirePermission permission="settings.manage">
                <DripCampaignsAdminPage />
              </RequirePermission>
            }
          />
          <Route
            path="/settings/mail"
            element={
              <RequirePermission permission="settings.manage">
                <MailSettingsPage />
              </RequirePermission>
            }
          />
          <Route
            path="/settings/fields"
            element={
              <RequirePermission permission="fields.manage">
                <FieldsAdminPage />
              </RequirePermission>
            }
          />
          <Route
            path="/settings/widget"
            element={
              <RequirePermission permission="fields.manage">
                <WidgetFormBuilderPage />
              </RequirePermission>
            }
          />
          <Route
            path="/settings"
            element={
              <RequirePermission permission="settings.manage">
                <SettingsPage />
              </RequirePermission>
            }
          />
          <Route path="*" element={<Navigate to="/" replace />} />
        </Route>
      </Route>
      </Routes>
    </Suspense>
  );
}

export function AppRouter() {
  return (
    <ThemeProvider>
      <ClientBrandingProvider>
        <BrowserRouter basename="/app">
          <AuthProvider>
            <AuthApiNavigationBridge />
            <AppConfigBrandSync />
            <AppRoutes />
          </AuthProvider>
        </BrowserRouter>
      </ClientBrandingProvider>
    </ThemeProvider>
  );
}
