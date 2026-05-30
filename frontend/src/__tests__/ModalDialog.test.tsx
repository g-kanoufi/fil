import { fireEvent, render, screen } from '@testing-library/react';
import { describe, expect, it, vi } from 'vitest';
import { ModalDialog } from '@/components/ui/ModalDialog';

describe('ModalDialog', () => {
  it('renders dialog with labelled title when open', () => {
    render(
      <ModalDialog open labelledBy="dialog-title" onClose={() => undefined}>
        <h2 id="dialog-title">Test dialog</h2>
        <button type="button">Action</button>
      </ModalDialog>,
    );

    expect(screen.getByRole('dialog', { name: 'Test dialog' })).toBeInTheDocument();
    expect(screen.getByRole('button', { name: 'Close dialog' })).toBeInTheDocument();
  });

  it('calls onClose when backdrop is clicked', () => {
    const onClose = vi.fn();

    render(
      <ModalDialog open labelledBy="dialog-title" onClose={onClose}>
        <h2 id="dialog-title">Test dialog</h2>
      </ModalDialog>,
    );

    fireEvent.click(screen.getByRole('button', { name: 'Close dialog' }));
    expect(onClose).toHaveBeenCalledTimes(1);
  });

  it('calls onClose when Escape is pressed', () => {
    const onClose = vi.fn();

    render(
      <ModalDialog open labelledBy="dialog-title" onClose={onClose}>
        <h2 id="dialog-title">Test dialog</h2>
      </ModalDialog>,
    );

    fireEvent.keyDown(window, { key: 'Escape' });
    expect(onClose).toHaveBeenCalledTimes(1);
  });

  it('does not render when closed', () => {
    render(
      <ModalDialog open={false} labelledBy="dialog-title" onClose={() => undefined}>
        <h2 id="dialog-title">Test dialog</h2>
      </ModalDialog>,
    );

    expect(screen.queryByRole('dialog')).not.toBeInTheDocument();
  });
});
