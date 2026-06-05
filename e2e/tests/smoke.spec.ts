import { expect, test } from '@playwright/test';
import { DEMO_USERS, loginAs } from './helpers/auth';

test.describe('FIL MVP smoke', () => {
  test('staff login → dashboard → leads grid', async ({ page }) => {
    await loginAs(page, DEMO_USERS.admin);

    const gridResponsePromise = page.waitForResponse(
      (response) => response.url().includes('/api/v1/query/leads') && response.ok(),
    );

    await page.getByRole('navigation', { name: 'Staff navigation' }).getByRole('link', { name: 'Leads', exact: true }).click();

    await page.waitForURL(/\/reports\/leads\/?$/);
    await expect(page.getByRole('heading', { name: 'Leads', level: 1 })).toBeVisible();

    const gridResponse = await gridResponsePromise;
    expect(gridResponse.ok()).toBeTruthy();
  });

  test('health endpoint responds', async ({ request }) => {
    const response = await request.get('/api/health');
    expect(response.ok()).toBeTruthy();

    const body = await response.json();
    expect(body.status).toBe('ok');
  });
});
