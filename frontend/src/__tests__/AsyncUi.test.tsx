import { render, screen } from '@testing-library/react';
import { describe, expect, it, vi } from 'vitest';
import { EntityLoadState } from '@/components/ui/EntityLoadState';
import { EmptyState } from '@/components/ui/EmptyState';
import { AsyncSection } from '@/components/ui/AsyncSection';
import { MemoryRouter } from 'react-router-dom';

describe('EntityLoadState', () => {
  it('shows loading label while loading', () => {
    render(
      <EntityLoadState loading found={false} loadingLabel="Loading lead…">
        content
      </EntityLoadState>,
    );

    expect(screen.getByTestId('loading-state')).toHaveTextContent('Loading lead…');
  });

  it('shows not found with back link on page layout', () => {
    render(
      <MemoryRouter>
        <EntityLoadState
          loading={false}
          found={false}
          loadingLabel="Loading lead…"
          notFoundMessage="Lead not found"
          backTo={{ label: '← Back to leads', href: '/reports/leads' }}
        >
          content
        </EntityLoadState>
      </MemoryRouter>,
    );

    expect(screen.getByText('Lead not found')).toBeInTheDocument();
    expect(screen.getByRole('link', { name: '← Back to leads' })).toHaveAttribute('href', '/reports/leads');
  });
});

describe('EmptyState', () => {
  it('renders compact size with smaller title', () => {
    render(<EmptyState size="compact" title="No messages yet" />);

    expect(screen.getByText('No messages yet')).toHaveClass('text-sm');
  });
});

describe('AsyncSection', () => {
  it('renders retry on error', () => {
    const onRetry = vi.fn();

    render(
      <AsyncSection status="error" error="Failed to load" onRetry={onRetry}>
        child
      </AsyncSection>,
    );

    expect(screen.getByText('Failed to load')).toBeInTheDocument();
    screen.getByRole('button', { name: 'Try again' }).click();
    expect(onRetry).toHaveBeenCalledOnce();
  });
});
