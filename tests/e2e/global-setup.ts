import { execSync } from 'child_process';
import { writeFileSync, existsSync } from 'fs';
import { resolve } from 'path';

export default function globalSetup() {
  const dbPath = resolve(process.cwd(), 'database/e2e.sqlite');
  if (!existsSync(dbPath)) {
    writeFileSync(dbPath, '');
  }

  console.log('Resetting test database...');
  execSync('php artisan migrate:fresh --env=testing --seed --seeder=PlaywrightSeeder --force', {
    cwd: process.cwd(),
    stdio: 'inherit',
  });
  console.log('Test database ready.');
}
