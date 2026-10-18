# Deploy Ruang to Vercel

The Laravel application is deployed from this directory (`ruang-1.0/app`) as a FrankenPHP container. The container builds the Inertia/Vue assets and serves Laravel's `public` directory.

## Prepare the project

Commit these deployment files with the application:

- `Dockerfile.vercel`
- `Caddyfile`
- `.dockerignore`
- `vercel.json`

The Docker image installs production Composer dependencies, builds `public/build` with `npm ci` and `npm run build`, and serves PHP on the port supplied by Vercel. It does not include `.env`, `vendor`, or local uploads.

## Configure Vercel

The Vercel project is `ruang-lms`, connected to `ChikoHakles/LMS`. Set the project's **Root Directory** to `app` (the Git repository root is `ruang-1.0`). The `vercel.json` in this directory routes all requests to the Laravel container and selects the Singapore (`sin1`) Function region.

Create a managed MySQL database reachable from Vercel. Keep it in Singapore or a nearby region. Add the shared values below in Vercel Project Settings > Environment Variables for both Preview and Production, using separate credentials and a different `APP_KEY` for each environment:

```text
APP_NAME=Ruang
APP_TIMEZONE=Asia/Jakarta
APP_LOCALE=id
DB_CONNECTION=mysql
DB_HOST=<managed MySQL host>
DB_PORT=3306
DB_DATABASE=<database name>
DB_USERNAME=<database user>
DB_PASSWORD=<database password>
SESSION_DRIVER=database
CACHE_STORE=database
QUEUE_CONNECTION=sync
LOG_CHANNEL=stderr
```

For Preview deployments used for testing, set `APP_ENV=testing` and `APP_DEBUG=true`. For Production, set `APP_ENV=production` and `APP_DEBUG=false`. Set `APP_URL` to the stable Preview or Production domain for each environment.

Generate a key from the application directory with `php artisan key:generate --show`. Add it directly to the Vercel environment variables. Use a different stable `APP_KEY` for Preview and Production, and never add `.env` or generated secrets to Git or the container image.

The repository already has migrations for users/sessions, cache, and jobs. Run migrations once as a release step using the production database credentials:

```bash
php artisan migrate --force
```

Do not run migrations from the container startup command; Vercel can start multiple instances at once. The database queue driver requires a separate worker process, so `QUEUE_CONNECTION=sync` is suitable while this application has no background worker. Use a durable external queue and worker for future asynchronous jobs.

The application currently has no local file upload flow. If one is added, store uploaded files in durable object storage rather than the container's local filesystem.

If production account verification or password reset emails should be sent, configure `MAIL_MAILER=smtp` and the provider's `MAIL_HOST`, `MAIL_PORT`, `MAIL_USERNAME`, `MAIL_PASSWORD`, `MAIL_FROM_ADDRESS`, and `MAIL_FROM_NAME`. The repository's example defaults to logging mail instead of sending it.

## Deploy and verify

With Git integration, push a branch to get a Preview Deployment. For a manual deployment, install the Vercel CLI and run `vercel login`, then run the command from the Git repository root (`ruang-1.0`):

```bash
vercel deploy . --project ruang-lms
```

Set the environment variables before testing. Verify `/up`, sign-in, session persistence, and the main student/tutor flows on the Preview URL. Then deploy the production branch or run `vercel deploy --prod`.

The local admin seeder is restricted to local/testing. Create the first production administrator through a controlled, documented process; do not expose a default admin password in the deployment configuration.
