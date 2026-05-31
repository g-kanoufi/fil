import { fireEvent, render, screen } from '@testing-library/react';
import { describe, expect, it, vi } from 'vitest';
import { PageTabs } from '@/components/ui/PageTabs';

describe('PageTabs', () => {
  it('renders an accessible tablist with underline selection', () => {
    const onChange = vi.fn();

    render(
      <PageTabs
        ariaLabel="Demo tabs"
        items={[
          { id: 'one', label: 'First' },
          { id: 'two', label: 'Second' },
        ]}
        value="one"
        onChange={onChange}
      />,
    );

    expect(screen.getByRole('tablist', { name: 'Demo tabs' })).toBeInTheDocument();

    const first = screen.getByRole('tab', { name: 'First' });
    const second = screen.getByRole('tab', { name: 'Second' });

    expect(first).toHaveAttribute('aria-selected', 'true');
    expect(second).toHaveAttribute('aria-selected', 'false');

    fireEvent.click(second);
    expect(onChange).toHaveBeenCalledWith('two');
  });
});
