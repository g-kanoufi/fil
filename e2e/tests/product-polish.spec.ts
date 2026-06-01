import { expect, test } from '@playwright/test';
import { DEMO_USERS, loginAs } from './helpers/auth';

test.describe('Lead custom fields panel', () => {
  test('lead detail shows custom fields section for staff', async ({ page }) => {
    await loginAs(page, DEMO_USERS.admin);

    const leadsResponsePromise = page.waitForResponse(
      (response) => response.url().includes('/api/v1/query/leads') && response.ok(),
    );

    await page.getByRole('navigation', { name: 'Staff navigation' }).getByRole('link', { name: 'Leads', exact: true }).click();
    await page.waitForURL(/\/app\/reports\/leads\/?$/);
    await leadsResponsePromise;

    await page.getByRole('link', { name: /Jane Smith/i }).first().click();
    await page.waitForURL(/\/app\/reports\/leads\/\d+\/?$/);

    await expect(page.getByRole('heading', { name: /custom fields/i })).toBeVisible();
  });
});

test.describe('Widget demo page', () => {
  test('staff can open widget demo preview', async ({ page }) => {
    await loginAs(page, DEMO_USERS.admin);

    await page.goto('/app/settings/widget/demo');
    await expect(page.getByRole('heading', { name: /widget demo/i })).toBeVisible();
    await expect(page.getByTitle('FIL widget inline preview')).toBeVisible();
  });
});
