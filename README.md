# Explora

Explora is a Symfony 6.3 travel booking platform for hotels, transport, activities, voyages, cart checkout, payments, and admin partner management.

## Tech Stack

- PHP 8.1+
- Symfony 6.3
- Doctrine ORM / Migrations
- Twig
- Stripe, Mailer, QR code, PDF, uploader, and chart integrations

## Requirements

- PHP 8.1 or newer
- Composer
- A database configured in `.env` or `.env.local`
- Optional: Docker / Docker Compose

## Local Setup

1. Install dependencies:

```bash
composer install
```

2. Configure your environment variables in `.env.local` if needed.

3. Create or update the database schema:

```bash
php bin/console doctrine:database:create
php bin/console doctrine:migrations:migrate
```

4. Start the Symfony server:

```bash
symfony server:start
```

## Docker Setup

If you use Docker Compose, start the database and mailer helpers with:

```bash
docker compose up -d
```

## Main Features

- Hotel, transport, activity, and voyage browsing
- Unified panier / cart flow
- Payment and invoice handling
- User profile management
- Admin partner review and approval
- AI-powered Gemini features for voyage content and recommendations

## Useful Routes

- `/` home page
- `/profile` user profile
- `/panier` shopping cart
- `/admin/partnerships` admin partnership dashboard

## Notes

- Image uploads and generated files are stored under `public/uploads/`.
- If a route or feature depends on external services, check the relevant API keys in your Symfony environment configuration.
