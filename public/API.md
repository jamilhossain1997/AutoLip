# AutoLib API Reference

**Base URL:** `https://autolip.onrender.com`  
**Version:** 1.0.0

---

## Contents

1. [Authentication](#authentication)
2. [Rate limiting & plan limits](#rate-limiting--plan-limits)
3. [Response format](#response-format)
4. [Error codes](#error-codes)
5. [Endpoints](#endpoints)
   - [GET /health](#get-health)
   - [POST /scrape](#post-scrape)
   - [POST /pdf](#post-pdf)
   - [POST /email](#post-email)
6. [Dashboard API](#dashboard-api)
   - [GET /keys](#get-keys)
   - [POST /keys](#post-keys)
   - [DELETE /keys/{id}](#delete-keysid)
   - [GET /usage](#get-usage)
7. [Webhooks](#webhooks)
8. [SDKs & libraries](#sdks--libraries)
9. [Changelog](#changelog)

---

## Authentication

All feature endpoints require an API key passed as a Bearer token in the `Authorization` header.

```
Authorization: Bearer al_YOUR_API_KEY
```

API keys are generated from your dashboard at [/dashboard/keys](/dashboard/keys).

**Key format:** Keys always start with `al_` followed by 37 random characters (40 chars total).

**Security notes:**
- Keys are stored as SHA-256 hashes — we never store your raw key
- Copy your key immediately after generation — it is shown only once
- Revoke compromised keys instantly from the dashboard
- Never commit API keys to version control

**Example:**

```bash
curl -X POST /api/scrape \
  -H "Authorization: Bearer al_xK9mP2qRt7vBnLwZdYcE3jHsUoAiMfGpX8eK1" \
  -H "Content-Type: application/json" \
  -d '{"url": "https://example.com"}'
```

---

## Rate limiting & plan limits

Limits are enforced per calendar month and reset on the 1st of each month.

| Plan | Scrapes/mo | PDFs/mo | Emails/mo | API Keys | JS Rendering |
|------|-----------|---------|-----------|----------|--------------|
| Free | 50 | 10 | 100 | 1 | No |
| Starter | 500 | 100 | 1,000 | 1 | No |
| Pro | 5,000 | 500 | 10,000 | 5 | Yes |
| Business | Unlimited | 2,000 | 50,000 | Unlimited | Yes |

Every response includes rate limit headers:

```
X-Plan: pro
X-RateLimit-Limit: 5000
X-RateLimit-Remaining: 4823
X-RateLimit-Reset: 2024-02-01
```

When you exceed your limit, the API returns `429 Too Many Requests`:

```json
{
  "error": "Monthly scrape limit reached.",
  "used": 500,
  "limit": 500,
  "plan": "starter",
  "resets": "2024-02-01",
  "upgrade": "/billing"
}
```

---

## Response format

All responses are JSON with `Content-Type: application/json`, except the PDF endpoint which returns `application/pdf` binary.

Successful responses return HTTP `200` (or `201` for resource creation) with the data directly in the response body.

Error responses always include an `error` field:

```json
{
  "error": "Description of what went wrong.",
  "hint": "Optional guidance on how to fix it.",
  "docs": "/relevant-page"
}
```

---

## Error codes

| Status | Meaning |
|--------|---------|
| `400` | Validation failed — check your request body |
| `401` | Invalid or missing API key |
| `403` | Feature not available on your plan |
| `422` | Request was valid but the operation failed (e.g. URL unreachable) |
| `429` | Monthly plan limit reached |
| `500` | Internal server error |
| `502` | Upstream service error (e.g. AWS SES unreachable) |

---

## Endpoints

---

### GET /health

Check API status. No authentication required.

**Request:**

```bash
curl /api/health
```

**Response:**

```json
{
  "status": "ok",
  "service": "AutoLib API",
  "version": "1.0.0",
  "time": "2024-01-15T10:30:00.000000Z"
}
```

---

### POST /scrape

Scrape content from any web page using CSS selectors.

**Authentication:** Required  
**Plan:** All plans (JS rendering requires Pro+)

#### Request body

| Field | Type | Required | Description |
|-------|------|----------|-------------|
| `url` | string | Yes | The URL to scrape. Must be a valid URL. Max 2048 chars. |
| `selector` | string | No | CSS selector to extract. If omitted, returns raw HTML. |
| `attribute` | string | No | Extract an attribute instead of text content (e.g. `href`, `src`, `data-id`). |
| `js_render` | boolean | No | Use a headless browser for JS-rendered pages. Default: `false`. Pro+ only. |
| `timeout` | integer | No | Request timeout in seconds. Min: 1, Max: 30. Default: 10. |
| `headers` | object | No | Custom HTTP headers to send with the request. |

#### Example — scrape text with CSS selector

```bash
curl -X POST /api/scrape \
  -H "Authorization: Bearer al_YOUR_KEY" \
  -H "Content-Type: application/json" \
  -d '{
    "url": "https://news.ycombinator.com",
    "selector": ".titleline a"
  }'
```

```json
{
  "url": "https://news.ycombinator.com",
  "selector": ".titleline a",
  "results": [
    "Show HN: I built an all-in-one API for scraping, PDF and email",
    "Ask HN: What automation tools do you use?",
    "Postgres 17 released"
  ],
  "count": 30,
  "method": "static"
}
```

#### Example — extract all hrefs from a page

```bash
curl -X POST /api/scrape \
  -H "Authorization: Bearer al_YOUR_KEY" \
  -H "Content-Type: application/json" \
  -d '{
    "url": "https://example.com",
    "selector": "a",
    "attribute": "href"
  }'
```

```json
{
  "url": "https://example.com",
  "selector": "a",
  "results": [
    "https://www.iana.org/domains/example"
  ],
  "count": 1,
  "method": "static"
}
```

#### Example — get raw HTML (no selector)

```bash
curl -X POST /api/scrape \
  -H "Authorization: Bearer al_YOUR_KEY" \
  -H "Content-Type: application/json" \
  -d '{
    "url": "https://example.com"
  }'
```

```json
{
  "url": "https://example.com",
  "html": "<!doctype html><html>...</html>",
  "count": 1,
  "method": "static"
}
```

#### Example — JS-rendered page (Pro+ only)

```bash
curl -X POST /api/scrape \
  -H "Authorization: Bearer al_YOUR_KEY" \
  -H "Content-Type: application/json" \
  -d '{
    "url": "https://spa-example.com/products",
    "selector": ".product-title",
    "js_render": true,
    "timeout": 15
  }'
```

```json
{
  "url": "https://spa-example.com/products",
  "selector": ".product-title",
  "results": ["Widget Pro", "Widget Lite", "Widget Max"],
  "count": 3,
  "method": "js_render"
}
```

#### Example — custom headers

```bash
curl -X POST /api/scrape \
  -H "Authorization: Bearer al_YOUR_KEY" \
  -H "Content-Type: application/json" \
  -d '{
    "url": "https://example.com",
    "selector": "h1",
    "headers": {
      "Accept-Language": "en-US",
      "X-Custom-Header": "my-value"
    }
  }'
```

#### Response fields

| Field | Type | Description |
|-------|------|-------------|
| `url` | string | The URL that was scraped |
| `selector` | string | The CSS selector used (if provided) |
| `results` | array | Extracted text or attribute values |
| `count` | integer | Number of matched elements |
| `method` | string | `static` or `js_render` |
| `html` | string | Raw HTML (only when no selector is provided) |

---

### POST /pdf

Convert HTML or a URL to a PDF file.

**Authentication:** Required  
**Plan:** All plans  
**Response:** `application/pdf` binary

#### Request body

| Field | Type | Required | Description |
|-------|------|----------|-------------|
| `html` | string | One of `html` or `url` | Raw HTML string to convert. |
| `url` | string | One of `html` or `url` | Fetch a URL and convert it to PDF. |
| `filename` | string | No | Output filename. Default: `document.pdf`. |
| `paper` | string | No | Paper size: `a4`, `letter`, `legal`. Default: `a4`. |
| `orientation` | string | No | `portrait` or `landscape`. Default: `portrait`. |
| `inline` | boolean | No | `true` = stream in browser, `false` = download. Default: `false`. |
| `options.margin_top` | number | No | Top margin in mm. Default: 10. |
| `options.margin_bottom` | number | No | Bottom margin in mm. Default: 10. |
| `options.margin_left` | number | No | Left margin in mm. Default: 10. |
| `options.margin_right` | number | No | Right margin in mm. Default: 10. |
| `options.font_size` | integer | No | Base font size in pt. Min: 8, Max: 72. |

#### Example — HTML to PDF

```bash
curl -X POST /api/pdf \
  -H "Authorization: Bearer al_YOUR_KEY" \
  -H "Content-Type: application/json" \
  -d '{
    "html": "<html><body><h1>Invoice #001</h1><p>Amount due: $19.00</p></body></html>",
    "filename": "invoice.pdf",
    "paper": "a4",
    "orientation": "portrait"
  }' \
  --output invoice.pdf
```

#### Example — URL to PDF

```bash
curl -X POST /api/pdf \
  -H "Authorization: Bearer al_YOUR_KEY" \
  -H "Content-Type: application/json" \
  -d '{
    "url": "https://example.com",
    "filename": "snapshot.pdf",
    "paper": "letter",
    "orientation": "landscape"
  }' \
  --output snapshot.pdf
```

#### Example — custom margins and font size

```bash
curl -X POST /api/pdf \
  -H "Authorization: Bearer al_YOUR_KEY" \
  -H "Content-Type: application/json" \
  -d '{
    "html": "<h1>Report</h1><p>Content here...</p>",
    "filename": "report.pdf",
    "options": {
      "margin_top": 20,
      "margin_bottom": 20,
      "margin_left": 25,
      "margin_right": 25,
      "font_size": 12
    }
  }' \
  --output report.pdf
```

#### Example — view in browser (inline)

```bash
curl -X POST /api/pdf \
  -H "Authorization: Bearer al_YOUR_KEY" \
  -H "Content-Type: application/json" \
  -d '{
    "html": "<h1>Preview</h1>",
    "inline": true
  }' \
  --output preview.pdf
```

#### PHP example

```php
$response = Http::withHeaders([
    'Authorization' => 'Bearer al_YOUR_KEY',
])->post('/api/pdf', [
    'html'     => '<h1>Invoice</h1><p>Total: $99</p>',
    'filename' => 'invoice.pdf',
    'paper'    => 'a4',
]);

file_put_contents('invoice.pdf', $response->body());
```

#### JavaScript example

```javascript
const response = await fetch('/api/pdf', {
  method: 'POST',
  headers: {
    'Authorization': 'Bearer al_YOUR_KEY',
    'Content-Type': 'application/json',
  },
  body: JSON.stringify({
    html: '<h1>Invoice</h1><p>Total: $99</p>',
    filename: 'invoice.pdf',
  }),
});

const buffer = await response.arrayBuffer();
fs.writeFileSync('invoice.pdf', Buffer.from(buffer));
```

---

### POST /email

Send a transactional email via AWS SES.

**Authentication:** Required  
**Plan:** All plans

#### Request body

| Field | Type | Required | Description |
|-------|------|----------|-------------|
| `to` | string or array | Yes | Recipient email(s). String for single, array for multiple. |
| `subject` | string | Yes | Email subject line. Max 998 chars. |
| `html` | string | Yes | HTML body of the email. |
| `text` | string | No | Plain text fallback. Recommended — improves deliverability. |
| `from_name` | string | No | Sender display name. Default: `AutoLib`. |
| `from_address` | string | No | Custom from address. Must be verified in AWS SES. |
| `reply_to` | array | No | Array of reply-to email addresses. |
| `cc` | array | No | Array of CC email addresses. |
| `bcc` | array | No | Array of BCC email addresses. |

**Recipient limits per request:**

| Plan | Max recipients in `to` |
|------|----------------------|
| Free | 1 |
| Starter | 5 |
| Pro | 20 |
| Business | 50 |

#### Example — single recipient

```bash
curl -X POST /api/email \
  -H "Authorization: Bearer al_YOUR_KEY" \
  -H "Content-Type: application/json" \
  -d '{
    "to": "user@example.com",
    "subject": "Your invoice is ready",
    "html": "<h1>Invoice #001</h1><p>Amount due: <strong>$19.00</strong></p>",
    "text": "Invoice #001 — Amount due: $19.00"
  }'
```

```json
{
  "sent": true,
  "recipients": 1,
  "to": ["user@example.com"],
  "subject": "Your invoice is ready",
  "message_id": "0102018e1234abcd-abc123-..."
}
```

#### Example — multiple recipients with CC

```bash
curl -X POST /api/email \
  -H "Authorization: Bearer al_YOUR_KEY" \
  -H "Content-Type: application/json" \
  -d '{
    "to": ["alice@example.com", "bob@example.com"],
    "cc": ["manager@example.com"],
    "subject": "Weekly report",
    "html": "<h2>Weekly Report</h2><p>All systems operational.</p>",
    "reply_to": ["noreply@yourapp.com"]
  }'
```

```json
{
  "sent": true,
  "recipients": 2,
  "to": ["alice@example.com", "bob@example.com"],
  "subject": "Weekly report",
  "message_id": "0102018e1234abcd-def456-..."
}
```

#### Example — custom sender

```bash
curl -X POST /api/email \
  -H "Authorization: Bearer al_YOUR_KEY" \
  -H "Content-Type: application/json" \
  -d '{
    "to": "customer@example.com",
    "from_name": "Acme Corp",
    "from_address": "hello@acmecorp.com",
    "subject": "Welcome to Acme!",
    "html": "<h1>Welcome!</h1><p>Thanks for signing up.</p>"
  }'
```

> **Note:** `from_address` must be a verified sender in your AWS SES account. Unverified addresses will return a `502` error.

#### PHP example

```php
$response = Http::withHeaders([
    'Authorization' => 'Bearer al_YOUR_KEY',
])->post('/api/email', [
    'to'      => 'user@example.com',
    'subject' => 'Hello from AutoLib',
    'html'    => '<h1>Hello!</h1>',
    'text'    => 'Hello!',
]);

$data = $response->json();
// $data['sent'] === true
// $data['message_id'] === "0102018e..."
```

#### JavaScript example

```javascript
const response = await fetch('/api/email', {
  method: 'POST',
  headers: {
    'Authorization': 'Bearer al_YOUR_KEY',
    'Content-Type': 'application/json',
  },
  body: JSON.stringify({
    to: 'user@example.com',
    subject: 'Hello from AutoLib',
    html: '<h1>Hello!</h1>',
    text: 'Hello!',
  }),
});

const data = await response.json();
console.log(data.message_id);
```

---

## Dashboard API

These endpoints use **Sanctum session authentication** (for logged-in web users), not API key authentication. Use them to build integrations with the AutoLib dashboard.

Include the `Authorization: Bearer SANCTUM_TOKEN` header using the token returned from `POST /api/login`.

---

### GET /keys

List all API keys for the authenticated user. Raw key values are never returned — only the prefix and metadata.

```bash
curl /api/keys \
  -H "Authorization: Bearer SANCTUM_TOKEN"
```

```json
{
  "data": [
    {
      "id": 1,
      "name": "Production",
      "prefix": "al_xK9mP",
      "display": "al_xK9mP••••••••••••••••••••••••••••••",
      "is_active": true,
      "last_used_at": "2024-01-15T09:22:00.000000Z",
      "created_at": "2024-01-01T00:00:00.000000Z"
    }
  ]
}
```

---

### POST /keys

Generate a new API key. The raw key is returned **once only** — store it immediately.

```bash
curl -X POST /api/keys \
  -H "Authorization: Bearer SANCTUM_TOKEN" \
  -H "Content-Type: application/json" \
  -d '{"name": "My App"}'
```

```json
{
  "message": "API key created. Copy this — it will not be shown again.",
  "key": "al_xK9mP2qRt7vBnLwZdYcE3jHsUoAiMfGpX8eK1",
  "id": 2,
  "prefix": "al_xK9mP"
}
```

---

### DELETE /keys/{id}

Revoke an API key. Any requests using this key will immediately return `401`.

```bash
curl -X DELETE /api/keys/2 \
  -H "Authorization: Bearer SANCTUM_TOKEN"
```

```json
{
  "message": "Key revoked successfully."
}
```

---

### GET /usage

Get monthly usage summary for all endpoints.

```bash
curl /api/usage \
  -H "Authorization: Bearer SANCTUM_TOKEN"
```

```json
{
  "plan": "pro",
  "period": "January 2024",
  "usage": {
    "scrape": {
      "used": 1247,
      "limit": 5000,
      "percent": 24,
      "remaining": 3753,
      "reset_date": "2024-01-31"
    },
    "pdf": {
      "used": 89,
      "limit": 500,
      "percent": 17,
      "remaining": 411,
      "reset_date": "2024-01-31"
    },
    "email": {
      "used": 320,
      "limit": 10000,
      "percent": 3,
      "remaining": 9680,
      "reset_date": "2024-01-31"
    }
  }
}
```

---

## Webhooks

AutoLib uses Stripe webhooks to sync subscription changes. Configure your webhook endpoint in the Stripe dashboard:

**URL:** `/stripe/webhook`

**Events to enable:**

| Event | What happens |
|-------|-------------|
| `customer.subscription.updated` | User plan is updated in DB |
| `customer.subscription.deleted` | User is downgraded to free |
| `invoice.payment_succeeded` | Subscription confirmed active |
| `invoice.payment_failed` | User notified of payment failure |

The webhook is verified using `STRIPE_WEBHOOK_SECRET` — never disable signature verification in production.

---

## SDKs & libraries

Official SDKs are coming soon. In the meantime, AutoLib works with any HTTP client.

**PHP:**
```php
// Using Laravel HTTP client
$response = Http::withToken('al_YOUR_KEY')
    ->post('/api/scrape', [
        'url'      => 'https://example.com',
        'selector' => 'h1',
    ]);
```

**JavaScript / Node.js:**
```javascript
// Using fetch (works in Node 18+ and all modern browsers)
const res = await fetch('/api/scrape', {
  method: 'POST',
  headers: { 'Authorization': 'Bearer al_YOUR_KEY', 'Content-Type': 'application/json' },
  body: JSON.stringify({ url: 'https://example.com', selector: 'h1' }),
});
const data = await res.json();
```

**Python:**
```python
import requests

response = requests.post(
    '/api/scrape',
    headers={'Authorization': 'Bearer al_YOUR_KEY'},
    json={'url': 'https://example.com', 'selector': 'h1'}
)
data = response.json()
```

---

## Changelog

### v1.0.0 — Initial release
- `POST /api/scrape` — static and JS-rendered scraping
- `POST /api/pdf` — HTML and URL to PDF conversion
- `POST /api/email` — transactional email via AWS SES
- Dashboard with API key management, usage analytics, and Stripe billing