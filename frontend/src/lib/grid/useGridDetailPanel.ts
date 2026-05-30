import { useCallback, useMemo } from 'react';
import { useSearchParams } from 'react-router-dom';

const PANEL_PARAM = 'panel';

export function useGridDetailPanel() {
  const [searchParams, setSearchParams] = useSearchParams();

  const panelId = useMemo(() => {
    const raw = searchParams.get(PANEL_PARAM);
    if (!raw) {
      return null;
    }

    const id = Number(raw);

    return Number.isFinite(id) && id > 0 ? id : null;
  }, [searchParams]);

  const openPanel = useCallback(
    (id: number) => {
      setSearchParams(
        (current) => {
          const next = new URLSearchParams(current);
          next.set(PANEL_PARAM, String(id));
          return next;
        },
        { replace: false },
      );
    },
    [setSearchParams],
  );

  const closePanel = useCallback(() => {
    setSearchParams(
      (current) => {
        const next = new URLSearchParams(current);
        next.delete(PANEL_PARAM);
        return next;
      },
      { replace: true },
    );
  }, [setSearchParams]);

  return { panelId, openPanel, closePanel, isOpen: panelId !== null };
}
