import { expect, type Page } from '@playwright/test';

export const DEMO_PASSWORD = 'password';

export const DEMO_USERS = {
  admin: 'admin@fil.test',
  franchisor: 'franchisor@fil.test',
  leadOwner: 'owner@fil.test',
  areaRep: 'area_rep@fil.test',
  franchisee: 'franchisee@fil.test',
  storeManager: 'storemanager@fil.test',
  employee: 'employee@fil.test',
  prospect: 'prospect@fil.test',
} as const;

/** DemoSeeder assigns PrimeIV Scottsdale → id 1, PrimeIV Phoenix → id 2 on fresh migrate. */
export const DEMO_STORE_IDS = {
  scottsdale: 1,
  phoenix: 2,
} as const;

export async function loginAs(page: Page, email: string, password = DEMO_PASSWORD): Promise<void> {
  await page.goto('/app/login');
  await page.locator('#email').fill(email);
  await page.locator('#password').fill(password);
  await page.getByRole('button', { name: 'Sign in' }).click();
  await page.waitForURL(/\/app(\/)?(\?.*)?$/, { timeout: 15_000 });
  await expect(page.getByRole('navigation', { name: 'Staff navigation' })).toBeVisible();
}
