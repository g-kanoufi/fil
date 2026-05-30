import { describe, expect, it, vi } from 'vitest';
import { getFocusableElements, trapTabKey } from '@/lib/a11y/focusTrap';

describe('focusTrap', () => {
  it('collects focusable elements inside a container', () => {
    const container = document.createElement('div');
    container.innerHTML = `
      <button type="button">First</button>
      <input type="text" />
      <button type="button" disabled>Disabled</button>
      <a href="/next">Next</a>
    `;

    const focusable = getFocusableElements(container);

    expect(focusable).toHaveLength(3);
    expect(focusable[0].textContent).toBe('First');
    expect(focusable[2].textContent).toBe('Next');
  });

  it('wraps focus from last to first on Tab', () => {
    const container = document.createElement('div');
    container.innerHTML = `
      <button type="button" id="first">First</button>
      <button type="button" id="last">Last</button>
    `;
    document.body.appendChild(container);

    const first = container.querySelector('#first') as HTMLButtonElement;
    const last = container.querySelector('#last') as HTMLButtonElement;
    last.focus();

    const event = new KeyboardEvent('keydown', { key: 'Tab', bubbles: true });
    Object.defineProperty(event, 'preventDefault', { value: vi.fn() });

    trapTabKey(event, container);

    expect(document.activeElement).toBe(first);

    container.remove();
  });
});
