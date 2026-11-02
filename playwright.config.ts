import { defineConfig } from '@playwright/test';

export default defineConfig({
  testDir: './tests/e2e',
  timeout: 30000,
  retries: 0,
  use: {
    baseURL: 'http://localhost:8001',
    screenshot: 'only-on-failure',
  },
  projects: [
    { name: 'chromium', use: { browserName: 'chromium' } },
  ],
  globalSetup: './tests/e2e/global-setup.ts',
  webServer: {
    command: 'APP_ENV=testing php artisan serve --port=8001',
    port: 8001,
    reuseExistingServer: true,
    timeout: 15000,
    env: {
      APP_ENV: 'testing',
    },
  },
});
