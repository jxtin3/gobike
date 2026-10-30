# OneGoBike

Laravel web platform and mobile API for the **Go Bike Project** of PadyaRescue, Inc., a youth-led community responder in Pangasinan, Philippines.

## Features

**Public website**
- Home, About, Programs, Organization Structure, Gallery, News & Updates
- Donations through PayPal (one-time and monthly) and PayMongo (GCash and online banking)
- Contact form and Volunteer sign-up form
- GoBot, an FAQ chatbot powered by Google Gemini
- Privacy Policy and Terms pages

**Admin panel** (`/admin`)
- Dashboard with a live GoBiker map and emergency alerts
- User management, News and Gallery management, Reports, Contact messages
- Homepage impact statistics

**Mobile API** (`/api`, Laravel Sanctum)
- Registration, login and Google sign-in for the Flutter app
- GoBiker live location, ronda (patrol) sessions, emergency alerts
- Patient (community member) records

## Tech stack

Laravel 13, PHP 8.4+, Blade, Tailwind CSS v4, Alpine.js, Vite, Laravel Sanctum, MySQL (SQLite for quick local use).

## Local setup

```bash
composer install
npm install
cp .env.example .env
php artisan key:generate
```

Then edit `.env`: set `APP_ENV=local`, `APP_DEBUG=true`, `APP_URL`, `SESSION_SECURE_COOKIE=false`, and your database. After that:

```bash
php artisan migrate
php artisan db:seed      # local only: creates demo accounts and fake GoBikers
npm run dev
```

## Tests

```bash
php artisan test
```

## Production notes

- Set `APP_ENV=production` and `APP_DEBUG=false`.
- Never run `php artisan db:seed` on a live server.
- The PayPal and PayMongo webhook secrets are required. Webhooks are rejected without them.
- Never commit the real `.env` file.

## Deploying on Railway

1. Create a project with a **MySQL** database and a service from this GitHub repo. Railpack detects Laravel and builds it (PHP version from `composer.json`, `npm run build` for the assets). On every start it runs the migrations, `storage:link` and `optimize`, so no custom build, start or pre-deploy command is needed.
2. In the app service settings, set the healthcheck path to `/up` and add your custom domain under Networking (HTTPS is automatic).
3. Set the variables (see `.env.example`):
   - `APP_KEY` (from `php artisan key:generate --show`), `APP_URL=https://your-domain`
   - `DB_CONNECTION=mysql` and `DB_URL=${{MySQL.MYSQL_URL}}`
   - `GEMINI_API_KEY`, `GOOGLE_WEB_CLIENT_ID`, and the PayPal / PayMongo values (below)
   - The other defaults in `.env.example` (`APP_ENV=production`, `APP_DEBUG=false`, `LOG_CHANNEL=stderr`, `SESSION_SECURE_COOKIE=true`) are already right for production.
4. **Uploaded images:** the container disk is wiped on every deploy. Attach a Railway Volume at `/app/storage/app/public` (simplest, one instance only), or use a public R2/S3 bucket with `MEDIA_DISK=s3` and `MEDIA_CSP_ORIGIN`. **Volunteer IDs** are personal documents and must stay private. A Railway service can have only one Volume (the public images use it), so create a Railway **Storage Bucket** (private, S3-compatible), then set `PRIVATE_DISK=s3` and the `AWS_ACCESS_KEY_ID`, `AWS_SECRET_ACCESS_KEY`, `AWS_DEFAULT_REGION=auto`, `AWS_BUCKET` and `AWS_ENDPOINT` values from the bucket's Credentials tab. Never put IDs on the public volume.
5. After the first deploy, create the admin in the Railway shell: `php artisan admin:create --name="..." --email="..."`.
6. Point the RondaGo mobile app at the new domain: set `kProductionApiBaseUrl` in `gobikemob/lib/core/api_config.dart` to `https://your-domain/api` (or pass `--dart-define=API_BASE_URL=https://your-domain/api` when building). Check it with `curl https://your-domain/up`.
7. Optional: a second service from the same repo as a Railway cron job (`0 0 * * *`) with start command `php artisan sanctum:prune-expired --hours=24` clears expired mobile logins. Give it the same `APP_KEY` and `DB_*` variables as the app. No queue worker is needed; nothing is queued.

### Going live with payments

**PayPal**
1. In developer.paypal.com switch to **Live**, create an app (needs a verified Business account) and set `PAYPAL_MODE=live`, `PAYPAL_LIVE_CLIENT_ID`, `PAYPAL_LIVE_CLIENT_SECRET`.
2. Run `php artisan paypal:setup-donation-plan` in live mode and save the new `PAYPAL_DONATION_PRODUCT_ID` / `PAYPAL_DONATION_PLAN_ID` (sandbox plans don't work in live).
3. Add a live webhook to `https://your-domain/webhooks/paypal` with these events, and save its ID in `PAYPAL_WEBHOOK_ID`:
   `PAYMENT.CAPTURE.COMPLETED`, `CHECKOUT.ORDER.APPROVED`, `PAYMENT.SALE.COMPLETED`, `BILLING.SUBSCRIPTION.ACTIVATED`, `BILLING.SUBSCRIPTION.RE-ACTIVATED`, `BILLING.SUBSCRIPTION.SUSPENDED`, `BILLING.SUBSCRIPTION.CANCELLED`, `BILLING.SUBSCRIPTION.EXPIRED`, `BILLING.SUBSCRIPTION.PAYMENT.FAILED`.

**PayMongo**
1. Activate the account in the PayMongo dashboard and make sure GCash and online banking are enabled for it.
2. Set the live keys `PAYMONGO_PUBLIC_KEY=pk_live_...` and `PAYMONGO_SECRET_KEY=sk_live_...`.
3. Create a live webhook (test webhooks don't carry over) and save the returned `secret_key` in `PAYMONGO_WEBHOOK_SECRET`:
   ```bash
   curl -X POST https://api.paymongo.com/v1/webhooks -u sk_live_xxx: \
     -H "Content-Type: application/json" \
     -d '{"data":{"attributes":{"url":"https://your-domain/webhooks/paymongo","events":["checkout_session.payment.paid"]}}}'
   ```


## License

For educational and organizational use by PadyaRescue, Inc. and the thesis group. Licensing terms may be updated.