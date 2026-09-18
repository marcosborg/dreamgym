<p align="center"><a href="https://laravel.com" target="_blank"><img src="https://raw.githubusercontent.com/laravel/art/master/logo-lockup/5%20SVG/2%20CMYK/1%20Full%20Color/laravel-logolockup-cmyk-red.svg" width="400" alt="Laravel Logo"></a></p>

<p align="center">
<a href="https://github.com/laravel/framework/actions"><img src="https://github.com/laravel/framework/workflows/tests/badge.svg" alt="Build Status"></a>
<a href="https://packagist.org/packages/laravel/framework"><img src="https://img.shields.io/packagist/dt/laravel/framework" alt="Total Downloads"></a>
<a href="https://packagist.org/packages/laravel/framework"><img src="https://img.shields.io/packagist/v/laravel/framework" alt="Latest Stable Version"></a>
<a href="https://packagist.org/packages/laravel/framework"><img src="https://img.shields.io/packagist/l/laravel/framework" alt="License"></a>
</p>

## Dream Gym deployment

After deploying a release, run the database migrations and ensure the public upload link exists:

```bash
php artisan migrate --force
php artisan storage:link
php artisan optimize
```

Personal Trainer photographs are stored on the `public` filesystem disk, under `personal-trainers/`.

### Read production photos in a local/sandbox environment

Set `MEDIA_REMOTE_BASE_URL=https://dreamgym.pt/media` in the local `.env`, then run `php artisan config:clear`. With `APP_ENV=local` or `APP_ENV=sandbox`, the controlled `/media/personal-trainers/...` route serves an existing local file first, otherwise redirects the browser to the configured public HTTPS media base. Production ignores this setting. An unset/invalid base or an unavailable remote file does not fall back to another host; missing local files return 404 when remote reading is disabled.

This changes reads only. Uploads and edits still use the local `public` disk; no production credentials, uploads, writes or server-side downloads are used. Normal image display uses browser image requests rather than JavaScript/CORS fetches. The remote host must permit these public image requests; its hotlink/access restrictions are not bypassed. Redirects are not cached so a new local upload takes precedence immediately.

## About Laravel

Laravel is a web application framework with expressive, elegant syntax. We believe development must be an enjoyable and creative experience to be truly fulfilling. Laravel takes the pain out of development by easing common tasks used in many web projects, such as:

- [Simple, fast routing engine](https://laravel.com/docs/routing).
- [Powerful dependency injection container](https://laravel.com/docs/container).
- Multiple back-ends for [session](https://laravel.com/docs/session) and [cache](https://laravel.com/docs/cache) storage.
- Expressive, intuitive [database ORM](https://laravel.com/docs/eloquent).
- Database agnostic [schema migrations](https://laravel.com/docs/migrations).
- [Robust background job processing](https://laravel.com/docs/queues).
- [Real-time event broadcasting](https://laravel.com/docs/broadcasting).

Laravel is accessible, powerful, and provides tools required for large, robust applications.

## Learning Laravel

Laravel has the most extensive and thorough [documentation](https://laravel.com/docs) and video tutorial library of all modern web application frameworks, making it a breeze to get started with the framework.

You may also try the [Laravel Bootcamp](https://bootcamp.laravel.com), where you will be guided through building a modern Laravel application from scratch.

If you don't feel like reading, [Laracasts](https://laracasts.com) can help. Laracasts contains thousands of video tutorials on a range of topics including Laravel, modern PHP, unit testing, and JavaScript. Boost your skills by digging into our comprehensive video library.

## Laravel Sponsors

We would like to extend our thanks to the following sponsors for funding Laravel development. If you are interested in becoming a sponsor, please visit the [Laravel Partners program](https://partners.laravel.com).

### Premium Partners

- **[Vehikl](https://vehikl.com/)**
- **[Tighten Co.](https://tighten.co)**
- **[WebReinvent](https://webreinvent.com/)**
- **[Kirschbaum Development Group](https://kirschbaumdevelopment.com)**
- **[64 Robots](https://64robots.com)**
- **[Curotec](https://www.curotec.com/services/technologies/laravel/)**
- **[Cyber-Duck](https://cyber-duck.co.uk)**
- **[DevSquad](https://devsquad.com/hire-laravel-developers)**
- **[Jump24](https://jump24.co.uk)**
- **[Redberry](https://redberry.international/laravel/)**
- **[Active Logic](https://activelogic.com)**
- **[byte5](https://byte5.de)**
- **[OP.GG](https://op.gg)**

## Contributing

Thank you for considering contributing to the Laravel framework! The contribution guide can be found in the [Laravel documentation](https://laravel.com/docs/contributions).

## Code of Conduct

In order to ensure that the Laravel community is welcoming to all, please review and abide by the [Code of Conduct](https://laravel.com/docs/contributions#code-of-conduct).

## Security Vulnerabilities

If you discover a security vulnerability within Laravel, please send an e-mail to Taylor Otwell via [taylor@laravel.com](mailto:taylor@laravel.com). All security vulnerabilities will be promptly addressed.

## License

The Laravel framework is open-sourced software licensed under the [MIT license](https://opensource.org/licenses/MIT).

### September client corrections

After pulling this release, run `php artisan migrate --force` and rebuild the
configuration/view cache (`php artisan optimize:clear`, then `php artisan optimize`).
The compiled public assets are included in Git.

Run `php artisan memberships:reconcile` first to preview historical membership
balances. Review the table (particularly uncovered bookings), then run
`php artisan memberships:reconcile --apply`. `--user=ID` limits either operation.
The reconstruction uses paid membership calendar dates, cumulative renewals, individual
reservations and timely cancellations. It also associates legacy reservations without a separate recorded payment with an account by matching email; paid hourly reservations and
session-pack reservations are excluded. It replaces the membership balance from
that history, so review manually granted credits separately. Repeating it does not
add credits again. No confirmation emails or payment requests are sent by this job.

Set the Portuguese room name and description in Rooms & Pricing. Empty translations
fall back to the editable English fields. New access codes use `LOCK_PIN_DIGITS=123456`;
existing codes are preserved because changing a code also requires configuring the
physical lock and notifying its holder. The `simulated` driver does not operate a
real lock; `manual_ihr` requires manual programming.

`MAIL_MAILER=log` does not deliver email, even outside sandbox. Configure the host's
working SMTP or sendmail transport and verify delivery before claiming emails work.
