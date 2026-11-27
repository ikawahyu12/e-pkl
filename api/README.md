<p align="center"><a href="https://laravel.com" target="_blank"><img src="https://raw.githubusercontent.com/laravel/art/master/logo-lockup/5%20SVG/2%20CMYK/1%20Full%20Color/laravel-logolockup-cmyk-red.svg" width="400" alt="Laravel Logo"></a></p>

<p align="center">
<a href="https://github.com/laravel/framework/actions"><img src="https://github.com/laravel/framework/workflows/tests/badge.svg" alt="Build Status"></a>
<a href="https://packagist.org/packages/laravel/framework"><img src="https://img.shields.io/packagist/dt/laravel/framework" alt="Total Downloads"></a>
<a href="https://packagist.org/packages/laravel/framework"><img src="https://img.shields.io/packagist/v/laravel/framework" alt="Latest Stable Version"></a>
<a href="https://packagist.org/packages/laravel/framework"><img src="https://img.shields.io/packagist/l/laravel/framework" alt="License"></a>
</p>

## Gmail API Mail Delivery

The API can send password-reset messages through Gmail's HTTPS API when direct SMTP is blocked. Configure these values in the local `api/.env` file:

```dotenv
MAIL_MAILER=gmail-api
MAIL_FROM_ADDRESS=your-gmail-address@gmail.com
GOOGLE_MAIL_CLIENT_ID=
GOOGLE_MAIL_CLIENT_SECRET=
GOOGLE_MAIL_REFRESH_TOKEN=
```

To obtain OAuth credentials for your Gmail account:

1. Open Google's [Personal mode onboarding page](https://console.cloud.google.com/auth/overview/personal), create/select a Personal mode project, choose **Web application**, and add `https://developers.google.com/oauthplayground` as an authorized redirect URI.
2. Enable the Gmail API for that project in the Google API Library, then copy the OAuth client ID and secret.
3. In OAuth 2.0 Playground settings, enable **Use your own OAuth credentials** and enter that client's ID and secret.
4. Authorize the scope `https://www.googleapis.com/auth/gmail.send`, exchange the authorization code, and copy the returned refresh token into `GOOGLE_MAIL_REFRESH_TOKEN`.
5. Put the client ID and secret into the matching `.env` values. Keep all three values private and out of source control. The `MAIL_FROM_ADDRESS` must be the authorized Gmail account or a Gmail send-as alias.
6. Clear cached configuration and restart the API process so it reloads `.env`:

   ```powershell
   php artisan config:clear
   ```

OAuth Playground is hosted by Google and sends the OAuth client credentials to Google's service to perform the token exchange. Use only the official `https://developers.google.com/oauthplayground/` site; never paste credentials or refresh tokens into chat or an unrelated website.

Personal mode limits authorization to the Google account that created the project. Google documents that it does not require app verification and is not subject to the seven-day test-user token expiry.

The password-reset URLs in `.env` must also be reachable from the device opening the email. Private LAN IPs only work while that device is on the same network.

Student and teacher accounts are created by an Admin; public self-registration and email-verification endpoints are disabled. Admins must provide a unique recovery email when creating an account. The initial password is temporary and the account requires a password change on first login. Password-reset emails are sent only for active accounts with eligible profiles.

## Admin User Management API

All endpoints below require an active Admin bearer token:

```http
Authorization: Bearer <token>
Accept: application/json
```

Base path: `/api/v1/admin/users`

| Method | Path | Purpose |
|---|---|---|
| `GET` | `/api/v1/admin/users` | List Admin accounts. Optional query parameters: `search`, `status`, `per_page` (1-100). |
| `POST` | `/api/v1/admin/users` | Create an Admin account. |
| `GET` | `/api/v1/admin/users/{id}` | Get one Admin account. |
| `PUT` / `PATCH` | `/api/v1/admin/users/{id}` | Update an Admin account. Omitted fields are left unchanged. |
| `DELETE` | `/api/v1/admin/users/{id}` | Delete an Admin account. |

Create requests require `nama` (or `nama_tampilan`), `username`, and a `password` of at least 8 characters. `email` is optional, and `status` defaults to `aktif`. Update requests accept any of these fields; leave `password` out to keep the current password. Status values are `aktif`, `nonaktif`, or `ditangguhkan`. The API never returns a password or password hash, hashes passwords before storage, and prevents disabling or deleting the last active Admin.

## Admin Student and Teacher Management API

These endpoints also require an active Admin bearer token:

| Method | Path | Purpose |
|---|---|---|
| `GET` | `/api/v1/admin/students` | List/search/filter student accounts. |
| `GET` | `/api/v1/admin/students/classes` | Get classes for the student form. |
| `POST` | `/api/v1/admin/students` | Create a student login and profile in one transaction. |
| `GET` | `/api/v1/admin/students/{userId}` | Get one student account. |
| `PATCH` | `/api/v1/admin/students/{userId}` | Update a student account and profile. |
| `DELETE` | `/api/v1/admin/students/{userId}` | Delete a student profile and account. |
| `GET` | `/api/v1/admin/teachers` | List/search/filter teacher accounts. |
| `POST` | `/api/v1/admin/teachers` | Create a teacher login and profile in one transaction. |
| `GET` | `/api/v1/admin/teachers/{userId}` | Get one teacher account. |
| `PATCH` | `/api/v1/admin/teachers/{userId}` | Update a teacher account and profile. |
| `DELETE` | `/api/v1/admin/teachers/{userId}` | Delete a teacher profile and account. |

Both create endpoints require `nama`, `username`, a unique `email`, and `password`; `status` defaults to `aktif`. Student creation accepts a valid `kelas_id` when classes exist; if no classes are configured, `kelas_id` may be omitted. Updates leave omitted fields unchanged; `email_akun` can update the recovery email, and an omitted or empty password does not change the existing password. Passwords are hashed and never returned.

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

In addition, [Laracasts](https://laracasts.com) contains thousands of video tutorials on a range of topics including Laravel, modern PHP, unit testing, and JavaScript. Boost your skills by digging into our comprehensive video library.

You can also watch bite-sized lessons with real-world projects on [Laravel Learn](https://laravel.com/learn), where you will be guided through building a Laravel application from scratch while learning PHP fundamentals.

## Agentic Development

Laravel's predictable structure and conventions make it ideal for AI coding agents like Claude Code, Cursor, and GitHub Copilot. Install [Laravel Boost](https://laravel.com/docs/ai) to supercharge your AI workflow:

```bash
composer require laravel/boost --dev

php artisan boost:install
```

Boost provides your agent 15+ tools and skills that help agents build Laravel applications while following best practices.

## Contributing

Thank you for considering contributing to the Laravel framework! The contribution guide can be found in the [Laravel documentation](https://laravel.com/docs/contributions).

## Code of Conduct

In order to ensure that the Laravel community is welcoming to all, please review and abide by the [Code of Conduct](https://laravel.com/docs/contributions#code-of-conduct).

## Security Vulnerabilities

If you discover a security vulnerability within Laravel, please send an e-mail to Taylor Otwell via [taylor@laravel.com](mailto:taylor@laravel.com). All security vulnerabilities will be promptly addressed.

## License

The Laravel framework is open-sourced software licensed under the [MIT license](https://opensource.org/licenses/MIT).
