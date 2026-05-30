import { useEffect } from 'react';
import { useAuth } from '@/providers/AuthProvider';
import { useClientBrandingSync } from '@/providers/ClientBrandingProvider';

/** Applies authenticated app-config branding over the public defaults. */
export function AppConfigBrandSync() {
  const { appConfig } = useAuth();
  const syncOptions = useClientBrandingSync();

  useEffect(() => {
    syncOptions(appConfig?.options);
  }, [appConfig?.options, syncOptions]);

  return null;
}
