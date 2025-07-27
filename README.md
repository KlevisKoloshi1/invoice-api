<p align="center"><a href="https://laravel.com" target="_blank"><img src="https://raw.githubusercontent.com/laravel/art/master/logo-lockup/5%20SVG/2%20CMYK/1%20Full%20Color/laravel-logolockup-cmyk-red.svg" width="400" alt="Laravel Logo"></a></p>

<p align="center">
<a href="https://github.com/laravel/framework/actions"><img src="https://github.com/laravel/framework/workflows/tests/badge.svg" alt="Build Status"></a>
<a href="https://packagist.org/packages/laravel/framework"><img src="https://img.shields.io/packagist/dt/laravel/framework" alt="Total Downloads"></a>
<a href="https://packagist.org/packages/laravel/framework"><img src="https://img.shields.io/packagist/v/laravel/framework" alt="Latest Stable Version"></a>
<a href="https://packagist.org/packages/laravel/framework"><img src="https://img.shields.io/packagist/l/laravel/framework" alt="License"></a>
</p>

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

- **[Vehikl](https://vehikl.com)**
- **[Tighten Co.](https://tighten.co)**
- **[Kirschbaum Development Group](https://kirschbaumdevelopment.com)**
- **[64 Robots](https://64robots.com)**
- **[Curotec](https://www.curotec.com/services/technologies/laravel)**
- **[DevSquad](https://devsquad.com/hire-laravel-developers)**
- **[Redberry](https://redberry.international/laravel-development)**
- **[Active Logic](https://activelogic.com)**

## Contributing

Thank you for considering contributing to the Laravel framework! The contribution guide can be found in the [Laravel documentation](https://laravel.com/docs/contributions).

## Code of Conduct

In order to ensure that the Laravel community is welcoming to all, please review and abide by the [Code of Conduct](https://laravel.com/docs/contributions#code-of-conduct).

## Security Vulnerabilities

If you discover a security vulnerability within Laravel, please send an e-mail to Taylor Otwell via [taylor@laravel.com](mailto:taylor@laravel.com). All security vulnerabilities will be promptly addressed.

## License

The Laravel framework is open-sourced software licensed under the [MIT license](https://opensource.org/licenses/MIT).

# Invoice API

A Laravel-based API for managing invoices, clients, and imports with Excel upload and fiscalization integration.

## Features
- CRUD for clients, invoices, and imports
- Import invoices from Excel files
- PostgreSQL database (code-first migrations)
- Role-based authentication (admin/public)
- Fiscalization API integration (Albanian tax authority)
- Scheduled cron job for fiscalization
- Comprehensive validation and error handling
- Unit and feature tests
- API documentation and Postman collection

## Setup Instructions

1. **Clone the repository**
   ```sh
   git clone <repo-url>
   cd invoice-api
   ```
2. **Install dependencies**
   ```sh
   composer install
   ```
3. **Copy and configure .env**
   ```sh
   cp .env.example .env
   # Set your DB and fiscalization credentials
   ```
4. **Generate application key**
   ```sh
   php artisan key:generate
   ```
5. **Run migrations and seeders**
   ```sh
   php artisan migrate --seed
   ```
6. **Link storage (for file uploads)**
   ```sh
   php artisan storage:link
   ```
7. **Run the development server**
   ```sh
   php artisan serve
   ```

## Running Tests

- **Unit and feature tests:**
  ```sh
  php artisan test
  ```

## Cron Job for Fiscalization

To enable scheduled fiscalization, add this to your system cron:
```
* * * * * cd /path/to/invoice-api && php artisan schedule:run >> /dev/null 2>&1
```

## API Documentation

See `API_DOCS.md` or below for endpoint details. All endpoints require authentication via Bearer token (see `/api/login`).

- **Login:** `POST /api/login`
- **Clients:** `GET/POST/PUT/DELETE /api/clients`
- **Invoices:** `GET/POST/PUT/DELETE /api/invoices`, `POST /api/invoices/{id}/fiscalize`
- **Imports:** `GET/POST/PUT/DELETE /api/imports`

See the Postman collection for example requests and responses.

## Postman Collection

1. Import the provided `postman_collection.json` into Postman.
2. Set the `baseUrl` environment variable to your API base URL.
3. Use the login endpoint to get a token, then set it as a Bearer token for subsequent requests.

## Security
- All admin endpoints require an admin user and a valid token.
- Public users can only access read-only endpoints.
- File uploads are validated for type and size.

## License
MIT
