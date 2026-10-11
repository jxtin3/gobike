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

Laravel 13, PHP 8.3+, Blade, Tailwind CSS v4, Alpine.js, Vite, Laravel Sanctum, MySQL (SQLite for quick local use).

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

## License

For educational and organizational use by PadyaRescue, Inc. and the thesis group. Licensing terms may be updated.