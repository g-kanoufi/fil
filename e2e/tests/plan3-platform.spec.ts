import { expect, test } from '@playwright/test';
import { DEMO_STORE_IDS, DEMO_USERS, loginAs } from './helpers/auth';

/**
 * Plan 3 platform smoke — phases A–D (prospect portal shell, build/open, operate, grow/earn).
 * Run via: ./scripts/e2e-smoke.sh
 */
test.describe('Plan 3 — Grow + Earn (Phase D)', () => {
  test('admin sees royalty intelligence on royalties page', async ({ page }) => {
    await loginAs(page, DEMO_USERS.admin);

    const intelligencePromise = page.waitForResponse(
      (response) => response.url().includes('/api/v1/royalties/intelligence') && response.ok(),
    );

    await page.goto('/reports/royalties');
    await intelligencePromise;

    await expect(page.getByRole('heading', { name: 'Royalties', level: 1 })).toBeVisible();
    await expect(page.getByRole('heading', { name: 'Unit performance' })).toBeVisible();
    await expect(page.getByRole('heading', { name: 'Area performance' })).toBeVisible();
  });

  test('admin sees ACH reconciliation summary', async ({ page }) => {
    await loginAs(page, DEMO_USERS.admin);

    const reconPromise = page.waitForResponse(
      (response) => response.url().includes('/api/v1/ach-transfers/reconciliation') && response.ok(),
    );

    await page.goto('/reports/ach');
    await reconPromise;

    await expect(page.getByRole('heading', { name: 'ACH transfers', level: 1 })).toBeVisible();
    await expect(page.getByText('Reconciliation')).toBeVisible();
    await expect(page.getByText('Unpaid line items')).toBeVisible();
  });
});

test.describe('Plan 3 — Operate + Inspect (Phase C)', () => {
  test('franchisee dashboard shows store operations summary', async ({ page }) => {
    await loginAs(page, DEMO_USERS.franchisee);

    const dashboardPromise = page.waitForResponse(
      (response) => response.url().includes('/api/v1/dashboard') && response.ok(),
    );

    await page.goto('/');
    await dashboardPromise;

    await expect(page.getByText('Store operations')).toBeVisible();
    await expect(page.getByText('My stores')).toBeVisible();
  });

  test('admin can open history page visits tab', async ({ page }) => {
    await loginAs(page, DEMO_USERS.admin);
    await page.goto('/history');

    await page.getByRole('button', { name: 'Page visits' }).click();
    await expect(page.getByRole('heading', { name: 'Page visits' })).toBeVisible();
  });

  test('admin sees corp todos on dashboard', async ({ page }) => {
    await loginAs(page, DEMO_USERS.admin);
    await page.goto('/');

    await expect(page.getByText('Corp todos')).toBeVisible();
  });
});

test.describe('Plan 3 — Build + Open (Phase B)', () => {
  test('admin store detail shows opening checklist', async ({ page }) => {
    await loginAs(page, DEMO_USERS.admin);
    await page.goto(`/reports/stores/${DEMO_STORE_IDS.scottsdale}`);

    await expect(page.getByRole('heading', { name: 'PrimeIV Scottsdale', level: 1 })).toBeVisible();
    await expect(page.getByText('Opening checklist')).toBeVisible();
  });

  test('admin can open closings grid', async ({ page }) => {
    await loginAs(page, DEMO_USERS.admin);

    const gridPromise = page.waitForResponse(
      (response) =>
        (response.url().includes('/api/v1/query/closings') ||
          response.url().includes('/api/v1/closings')) &&
        response.ok(),
    );

    await page.goto('/reports/closings');
    await page.waitForURL(/\/reports\/closings\/?$/);
    await gridPromise;

    await expect(page.getByRole('heading', { name: 'Closings', level: 1 })).toBeVisible();
  });
});

test.describe('Plan 3 — Find + Sell (Phase A)', () => {
  test('prospect portal login page loads', async ({ page }) => {
    await page.goto('/portal/login');

    await expect(page.getByRole('heading', { name: 'Prospect sign in' })).toBeVisible();
    await expect(page.locator('#portal-email')).toBeVisible();
  });

  test('admin lead detail shows corp notes panel', async ({ page }) => {
    await loginAs(page, DEMO_USERS.admin);
    await page.goto('/reports/leads/1');

    await expect(page.getByText('Corp notes')).toBeVisible();
  });

  test('admin store detail shows corp notes panel', async ({ page }) => {
    await loginAs(page, DEMO_USERS.admin);
    await page.goto(`/reports/stores/${DEMO_STORE_IDS.scottsdale}`);

    await expect(page.getByText('Corp notes')).toBeVisible();
  });
});
