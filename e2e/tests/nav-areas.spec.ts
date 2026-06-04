import { expect, test } from '@playwright/test';
import { DEMO_USERS, loginAs } from './helpers/auth';

test.describe('Navigation — units and areas', () => {
  test('sidebar shows unit status links without My Units parent', async ({ page }) => {
    await loginAs(page, DEMO_USERS.admin);

    const appConfigPromise = page.waitForResponse(
      (response) => response.url().includes('/api/v1/app-config') && response.ok(),
    );

    await page.goto('/app');
    await appConfigPromise;

    const nav = page.getByRole('navigation', { name: 'Staff navigation' });
    await expect(nav).toBeVisible();
    await expect(nav.getByRole('link', { name: 'Open' })).toBeVisible({ timeout: 15_000 });
    await expect(nav.getByRole('link', { name: 'My Units' })).toHaveCount(0);
    await expect(nav.getByRole('link', { name: 'Pending' })).toBeVisible();
    await expect(nav.getByRole('link', { name: 'In Development' })).toBeVisible();
    await expect(nav.getByRole('link', { name: 'Closed' })).toBeVisible();
    await expect(nav.getByRole('link', { name: 'Areas' })).toBeVisible();
  });

  test('admin can open areas page and sync US Canada defaults', async ({ page }) => {
    await loginAs(page, DEMO_USERS.admin);

    await page.goto('/app/reports/areas');
    await expect(page.getByRole('heading', { name: 'Areas', level: 1 })).toBeVisible();

    await page.getByRole('tab', { name: 'US & Canada defaults' }).click();
    await expect(page.getByRole('button', { name: 'Sync US & Canada defaults' })).toBeVisible();
  });
});
