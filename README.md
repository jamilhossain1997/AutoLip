# AutoLib

**One API for web scraping, PDF generation, and email automation.**
Stop juggling 3 separate services. AutoLib gives you all three behind a single REST API, starting at $10/month.

---

## What is AutoLib?

AutoLib is a Laravel-based SaaS API that bundles three essential developer tools:

| Tool | What it does |
|------|-------------|
| **Web Scraping** | Scrape any page with CSS selectors. Static or JS-rendered. |
| **PDF Generation** | Convert HTML or any URL to a pixel-perfect PDF. |
| **Email Automation** | Send transactional emails reliably via AWS SES. |

Built for solo developers and small startups who don't want to manage three dashboards, three API keys, and three invoices.

---

## Quick Start

```bash
# 1. Get an API key at autolib.dev
# 2. Make your first request

curl -X POST https://api.autolib.dev/api/scrape \
  -H "Authorization: Bearer al_YOUR_KEY" \
  -H "Content-Type: application/json" \
  -d '{"url": "https://example.com", "selector": "h1"}'
```

Response:

```json
{
  "url": "https://example.com",
  "selector": "h1",
  "results": ["Example Domain"],
  "count": 1,
  "method": "static"
}
```

Full documentation → [API Reference](./API.md)

---

## Pricing

| Plan | Price | Scrapes | PDFs | Emails | API Keys |
|------|-------|---------|------|--------|----------|
| Free | $0 | 50/mo | 10/mo | 100/mo | 1 |
| Starter | $10/mo | 500/mo | 100/mo | 1,000/mo | 1 |
| Pro | $19/mo | 5,000/mo | 500/mo | 10,000/mo | 5 |
| Business | $29/mo | Unlimited | 2,000/mo | 50,000/mo | Unlimited |

Free tier available — no credit card required.

---

## Local Development Setup

### Requirements

- PHP 8.2+
- Composer
- MySQL 8.0+
- Node.js 18+

### Installation

```bash
# 1. Clone the repo
git clone https://github.com/YOUR_USERNAME/autolib.git
cd autolib

# 2. Install PHP dependencies
composer install

# 3. Install Node dependencies and build assets
npm install
npm run build

# 4. Copy and configure environment
cp .env.example .env
php artisan key:generate

# 5. Create MySQL database
mysql -u root -p -e "CREATE DATABASE autolib_db CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;"

# 6. Run migrations
php artisan migrate

# 7. Start the dev server
php artisan serve
```

The API will be live at `http://localhost:8000/api/`

### Required packages

```bash
composer require laravel/sanctum
composer require laravel/cashier
composer require barryvdh/laravel-dompdf
composer require symfony/dom-crawler symfony/css-selector
composer require spatie/browsershot   # Pro plan scraping only
```

---

## Project Structure

```
autolib/
├── app/
│   ├── Http/
│   │   ├── Controllers/
│   │   │   ├── Api/
│   │   │   │   ├── ScrapeController.php   ← POST /api/scrape
│   │   │   │   ├── PdfController.php      ← POST /api/pdf
│   │   │   │   ├── EmailController.php    ← POST /api/email
│   │   │   │   └── ApiKeyController.php   ← Key management
│   │   │   └── DashboardController.php    ← Web dashboard
│   │   └── Middleware/
│   │       └── ValidateApiKey.php         ← Auth + rate limiting
│   ├── Mail/
│   │   └── AutolibMail.php
│   └── Models/
│       ├── User.php                       ← Plan limits + helpers
│       ├── ApiKey.php                     ← Key generation + lookup
│       └── UsageLog.php                   ← Per-request logging
├── database/
│   └── migrations/                        ← 4 migration files
├── resources/views/
│   ├── layouts/app.blade.php              ← Sidebar layout
│   └── dashboard/
│       ├── index.blade.php                ← Overview + charts
│       ├── api-keys.blade.php             ← Key management
│       ├── usage.blade.php                ← Usage analytics
│       └── billing.blade.php             ← Stripe plans
└── routes/
    ├── api.php                            ← API routes
    └── web.php                            ← Dashboard routes
```

---

## Environment Variables

| Variable | Description |
|----------|-------------|
| `DB_DATABASE` | MySQL database name |
| `DB_USERNAME` | MySQL username |
| `DB_PASSWORD` | MySQL password |
| `STRIPE_KEY` | Stripe publishable key |
| `STRIPE_SECRET` | Stripe secret key |
| `STRIPE_WEBHOOK_SECRET` | Stripe webhook signing secret |
| `STRIPE_PRICE_STARTER` | Stripe Price ID for Starter plan |
| `STRIPE_PRICE_PRO` | Stripe Price ID for Pro plan |
| `STRIPE_PRICE_BUSINESS` | Stripe Price ID for Business plan |
| `AWS_ACCESS_KEY_ID` | AWS IAM access key (for SES) |
| `AWS_SECRET_ACCESS_KEY` | AWS IAM secret key |
| `AWS_DEFAULT_REGION` | AWS region (default: us-east-1) |
| `MAIL_FROM_ADDRESS` | Default sender email address |

---

## Deployment

See [DEPLOY.md](./DEPLOY.md) for the full Railway deployment guide.

Quick version:

```bash
# Push to GitHub → connect Railway → add MySQL plugin → set env vars → deploy
git push origin main
```

---

## Tech Stack

| Layer | Technology |
|-------|-----------|
| Framework | Laravel 11 |
| Database | MySQL 8 + Eloquent ORM |
| Auth | Laravel Sanctum + Breeze |
| Billing | Laravel Cashier + Stripe |
| Scraping (static) | Guzzle + Symfony DomCrawler |
| Scraping (JS) | Spatie Browsershot + Puppeteer |
| PDF generation | barryvdh/laravel-dompdf |
| Email delivery | Laravel Mail + AWS SES |
| Dashboard UI | Blade + Tailwind CSS + Alpine.js |
| Deployment | Railway + Nixpacks |

---

## License

MIT