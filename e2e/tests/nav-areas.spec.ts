import { expect, test } from '@playwright/test';
import { DEMO_USERS, loginAs } from './helpers/auth';

test.describe('Navigation — units and areas', () => {
  test('sidebar nests unit statuses under Units and lists Areas outside Reports', async ({ page }) => {
    await loginAs(page, DEMO_USERS.admin);

    const appConfigPromise = page.waitForResponse(
      (response) => response.url().includes('/api/v1/app-config') && response.ok(),
    );

    await page.goto('/');
    await appConfigPromise;

    const nav = page.getByRole('navigation', { name: 'Staff navigation' });
    await expect(nav).toBeVisible();
    await expect(nav.getByRole('link', { name: 'Areas' })).toBeVisible();
    await expect(nav.getByText('Reports')).toBeVisible();

    const unitsToggle = nav.getByRole('button', { name: 'Units' });
    await expect(unitsToggle).toBeVisible();
    await expect(nav.getByRole('link', { name: 'Units' })).toHaveCount(0);

    await unitsToggle.click();
    await expect(nav.getByRole('link', { name: 'Open' })).toBeVisible();
    await expect(nav.getByRole('link', { name: 'Pending' })).toBeVisible();
    await expect(nav.getByRole('link', { name: 'In Development' })).toBeVisible();
    await expect(nav.getByRole('link', { name: 'Closed' })).toBeVisible();
  });

  test('admin can open areas page and sync US Canada defaults', async ({ page }) => {
    await loginAs(page, DEMO_USERS.admin);

    await page.goto('/reports/areas');
    await expect(page.getByRole('heading', { name: 'Areas', level: 1 })).toBeVisible();

    await page.getByRole('tab', { name: 'US & Canada defaults' }).click();
    await expect(page.getByRole('button', { name: 'Sync US & Canada defaults' })).toBeVisible();
  });
});
