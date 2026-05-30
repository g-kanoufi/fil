import { useCallback, useEffect, useState } from 'react';
import { usePlaidLink } from 'react-plaid-link';
import { Alert } from '@/components/ui/Alert';
import { Button } from '@/components/ui/Button';
import {
  completePlaidLink,
  createPlaidLinkToken,
  type PlaidLinkCompleteResponse,
} from '@/lib/api/ach';

interface PlaidMetadataAccount {
  id: string;
  name?: string;
  verification_status?: string;
}

interface PlaidMetadata {
  accounts: PlaidMetadataAccount[];
}

interface StorePlaidLinkButtonProps {
  storeId: number;
  mode: 'live' | 'sandbox';
  disabled?: boolean;
  onComplete: (result: PlaidLinkCompleteResponse) => void;
}

export function StorePlaidLinkButton({
  storeId,
  mode,
  disabled = false,
  onComplete,
}: StorePlaidLinkButtonProps) {
  const [token, setToken] = useState<string | null>(null);
  const [loading, setLoading] = useState(false);
  const [error, setError] = useState<string | null>(null);

  useEffect(() => {
    if (mode !== 'live') {
      return;
    }

    let cancelled = false;

    void createPlaidLinkToken(storeId)
      .then((response) => {
        if (!cancelled) {
          setToken(response.link_token);
        }
      })
      .catch((linkError: unknown) => {
        if (!cancelled) {
          setError(linkError instanceof Error ? linkError.message : 'Failed to initialize Plaid Link');
        }
      });

    return () => {
      cancelled = true;
    };
  }, [mode, storeId]);

  const handleSuccess = useCallback(
    async (publicToken: string, metadata: PlaidMetadata) => {
      setLoading(true);
      setError(null);

      try {
        const account = metadata.accounts[0];

        if (!account) {
          throw new Error('No bank account was selected.');
        }

        const result = await completePlaidLink(storeId, {
          public_token: publicToken,
          account_id: account.id,
          account_name: account.name ?? null,
          verification_status: account.verification_status ?? null,
        });

        onComplete(result);
      } catch (linkError: unknown) {
        setError(linkError instanceof Error ? linkError.message : 'Bank link failed');
      } finally {
        setLoading(false);
      }
    },
    [onComplete, storeId],
  );

  const { open, ready } = usePlaidLink({
    token,
    onSuccess: (publicToken, metadata) => {
      void handleSuccess(publicToken, metadata as PlaidMetadata);
    },
    onExit: () => {
      setLoading(false);
    },
  });

  async function handleSandboxLink() {
    setLoading(true);
    setError(null);

    try {
      const result = await completePlaidLink(storeId, {
        sandbox: true,
        account_name: 'Sandbox checking',
      });
      onComplete(result);
    } catch (linkError: unknown) {
      setError(linkError instanceof Error ? linkError.message : 'Sandbox bank link failed');
    } finally {
      setLoading(false);
    }
  }

  const isDisabled = disabled || loading || (mode === 'live' && !ready);

  return (
    <div className="flex flex-col gap-2">
      <Button
        size="sm"
        variant="secondary"
        disabled={isDisabled}
        onClick={() => {
          if (mode === 'live') {
            setLoading(true);
            open();
          } else {
            void handleSandboxLink();
          }
        }}
      >
        {loading ? 'Connecting…' : mode === 'live' ? 'Add bank account' : 'Add sandbox bank account'}
      </Button>
      {error ? <Alert variant="error">{error}</Alert> : null}
    </div>
  );
}
