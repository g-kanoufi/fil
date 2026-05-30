import { expect, test } from '@playwright/test';
import { DEMO_STORE_IDS, DEMO_USERS, loginAs } from './helpers/auth';

test.describe('FIL auth & authorization', () => {
  test('prospect cannot sign in to staff app', async ({ page }) => {
    await page.goto('/app/login');
    await page.locator('#email').fill(DEMO_USERS.prospect);
    await page.locator('#password').fill('password');
    await page.getByRole('button', { name: 'Sign in' }).click();

    await expect(page.getByRole('alert')).toContainText(/cannot access the staff application/i);
    await expect(page).toHaveURL(/\/app\/login\/?$/);
  });

  test('expired session redirects to login', async ({ page, context }) => {
    await loginAs(page, DEMO_USERS.admin);
    await context.clearCookies();
    await page.goto('/app/reports/stores');

    await page.waitForURL(/\/app\/login\/?$/);
    await expect(page.getByRole('heading', { name: 'Staff sign in' })).toBeVisible();
  });

  test('missing route permission redirects to forbidden', async ({ page }) => {
    await loginAs(page, DEMO_USERS.franchisee);
    await page.goto('/app/reports/leads');

    await page.waitForURL(/\/app\/forbidden\/?$/);
    await expect(page.getByRole('heading', { name: 'Access denied' })).toBeVisible();
    await expect(page.getByRole('link', { name: '← Back to dashboard' })).toBeVisible();
  });

  test('out-of-scope store API redirects to forbidden', async ({ page }) => {
    await loginAs(page, DEMO_USERS.franchisee);
    await page.goto(`/app/reports/stores/${DEMO_STORE_IDS.phoenix}`);

    await page.waitForURL(/\/app\/forbidden\/?$/);
    await expect(page.getByRole('heading', { name: 'Access denied' })).toBeVisible();
  });

  test('franchisee can view assigned store', async ({ page }) => {
    await loginAs(page, DEMO_USERS.franchisee);
    await page.goto(`/app/reports/stores/${DEMO_STORE_IDS.scottsdale}`);

    await expect(page.getByRole('heading', { name: 'PrimeIV Scottsdale', level: 1 })).toBeVisible();
  });

  test('area rep sees leads in assigned territory', async ({ page }) => {
    await loginAs(page, DEMO_USERS.areaRep);

    const gridResponsePromise = page.waitForResponse(
      (response) => response.url().includes('/api/v1/query/leads') && response.ok(),
    );

    await page.getByRole('navigation', { name: 'Staff navigation' }).getByRole('link', { name: 'Leads', exact: true }).click();
    await page.waitForURL(/\/app\/reports\/leads\/?$/);

    const gridResponse = await gridResponsePromise;
    expect(gridResponse.ok()).toBeTruthy();
  });
});
