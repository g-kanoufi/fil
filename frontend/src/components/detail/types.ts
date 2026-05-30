export interface EntityDetailPageProps {
  /** When embedded in the grid slide-over panel */
  recordId?: number;
  layout?: 'page' | 'panel';
  onPanelClose?: () => void;
  fullPagePath?: string;
}

export function isPanelLayout(props: EntityDetailPageProps): boolean {
  return props.layout === 'panel';
}
