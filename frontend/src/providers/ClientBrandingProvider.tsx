import {
  createContext,
  useCallback,
  useContext,
  useEffect,
  useMemo,
  useState,
  type ReactNode,
} from 'react';
import { fetchPublicBranding } from '@/lib/api/branding';
import {
  applyDocumentBrand,
  parseClientBrand,
  type ClientBrand,
} from '@/lib/branding/clientBranding';

interface ClientBrandingContextValue {
  brand: ClientBrand;
  syncOptions: (options: Record<string, unknown> | null | undefined) => void;
}

const ClientBrandingContext = createContext<ClientBrandingContextValue | null>(null);

export function ClientBrandingProvider({ children }: { children: ReactNode }) {
  const [brand, setBrand] = useState<ClientBrand>(() => parseClientBrand(null));

  useEffect(() => {
    void fetchPublicBranding()
      .then((options) => {
        setBrand(parseClientBrand(options));
      })
      .catch(() => {
        // Keep defaults when public branding is unavailable.
      });
  }, []);

  useEffect(() => {
    applyDocumentBrand(brand);
  }, [brand]);

  const syncOptions = useCallback((options: Record<string, unknown> | null | undefined) => {
    if (!options) {
      return;
    }

    setBrand(parseClientBrand(options));
  }, []);

  const value = useMemo(() => ({ brand, syncOptions }), [brand, syncOptions]);

  return (
    <ClientBrandingContext.Provider value={value}>{children}</ClientBrandingContext.Provider>
  );
}

export function useClientBrand(): ClientBrand {
  const context = useContext(ClientBrandingContext);

  if (!context) {
    throw new Error('useClientBrand must be used within ClientBrandingProvider');
  }

  return context.brand;
}

export function useClientBrandingSync(): ClientBrandingContextValue['syncOptions'] {
  const context = useContext(ClientBrandingContext);

  if (!context) {
    throw new Error('useClientBrandingSync must be used within ClientBrandingProvider');
  }

  return context.syncOptions;
}
