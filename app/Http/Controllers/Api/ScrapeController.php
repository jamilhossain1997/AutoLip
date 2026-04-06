<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Symfony\Component\DomCrawler\Crawler;
use GuzzleHttp\Client;

class ScrapeController extends Controller
{
    /**
     * POST /api/scrape
     *
     * Request body:
     * {
     *   "url":        "https://example.com",          // required
     *   "selector":   "h1",                           // optional — CSS selector
     *   "attribute":  "href",                         // optional — extract attribute instead of text
     *   "js_render":  false,                          // optional — use headless browser (Pro+ only)
     *   "timeout":    10,                             // optional — seconds (default 10, max 30)
     *   "headers":    { "Accept-Language": "en" }    // optional — custom request headers
     * }
     *
     * Response:
     * {
     *   "url":     "https://example.com",
     *   "results": ["Example Domain"],
     *   "count":   1,
     *   "method":  "static"
     * }
     */
    public function scrape(Request $request)
    {
        // ── Validate input ─────────────────────────────────────────
        $validated = $request->validate([
            'url'       => 'required|url|max:2048',
            'selector'  => 'nullable|string|max:500',
            'attribute' => 'nullable|string|max:100',
            'js_render' => 'nullable|boolean',
            'timeout'   => 'nullable|integer|min:1|max:30',
            'headers'   => 'nullable|array',
            'headers.*' => 'string',
        ]);

        $url       = $validated['url'];
        $selector  = $validated['selector'] ?? null;
        $attribute = $validated['attribute'] ?? null;
        $jsRender  = $validated['js_render'] ?? false;
        $timeout   = $validated['timeout'] ?? 10;
        $headers   = $validated['headers'] ?? [];

        // ── JS rendering requires Pro+ plan ───────────────────────
        if ($jsRender) {
            $user = $request->auth_user;
            if (in_array($user->plan, ['free', 'starter'])) {
                return response()->json([
                    'error'   => 'JS rendering (js_render: true) requires Pro plan or higher.',
                    'upgrade' => 'https://autolib.dev/billing',
                ], 403);
            }
        }

        try {
            if ($jsRender) {
                $result = $this->scrapeWithBrowser($url, $selector, $attribute, $timeout, $headers);
            } else {
                $result = $this->scrapeStatic($url, $selector, $attribute, $timeout, $headers);
            }

            return response()->json($result);
        } catch (\Exception $e) {
            Log::warning('AutoLib scrape failed', [
                'url'   => $url,
                'error' => $e->getMessage(),
            ]);

            return response()->json([
                'error'  => 'Failed to scrape URL.',
                'reason' => $e->getMessage(),
                'url'    => $url,
            ], 422);
        }
    }

    // ── Static scraping (Guzzle + DomCrawler) ─────────────────────

    private function scrapeStatic(
        string $url,
        ?string $selector,
        ?string $attribute,
        int $timeout,
        array $headers
    ): array {

        $client = new Client([
            'timeout'         => $timeout,
            'connect_timeout' => 5,   // max time to connect
            'headers'         => array_merge([
                'User-Agent' => 'Mozilla/5.0 (compatible; AutoLib/1.0)',
                'Accept'     => 'text/html,application/xhtml+xml,application/xml;q=0.9,*/*;q=0.8',
                'Connection' => 'close',   // important for slow servers
            ], $headers),
            'read_timeout' => 10, // max idle time between chunks
        ]);

        $response = $client->request('GET', $url, ['stream' => true]);

        $html = '';
        foreach ($response->getBody() as $chunk) {
            $html .= $chunk;
            // optional: break early if you got enough
            if (strlen($html) > 1024 * 1024) break; // 1MB max
        }

        if (! $selector) {
            return [
                'url'    => $url,
                'html'   => $html,
                'count'  => 1,
                'method' => 'static',
            ];
        }

        $crawler = new Crawler($html, $url);
        $results = $crawler->filter($selector)->each(function (Crawler $node) use ($attribute) {
            return $attribute ? $node->attr($attribute) : trim($node->text('', false));
        });

        return [
            'url'      => $url,
            'selector' => $selector,
            'results'  => array_values(array_filter($results)),
            'count'    => count($results),
            'method'   => 'static',
        ];
    }

    // ── JS rendering (Browsershot + Puppeteer) ────────────────────

    private function scrapeWithBrowser(
        string $url,
        ?string $selector,
        ?string $attribute,
        int $timeout,
        array $headers
    ): array {

        if (! class_exists(\Spatie\Browsershot\Browsershot::class)) {
            throw new \RuntimeException('Browsershot is not installed. Run: composer require spatie/browsershot && npm install puppeteer');
        }

        $browsershot = \Spatie\Browsershot\Browsershot::url($url)
            ->waitUntilNetworkIdle()
            ->timeout($timeout * 1000)
            ->dismissDialogs()
            ->disableJavascript(false)

            ->addChromiumArguments([
                '--no-sandbox',
                '--disable-setuid-sandbox',
                '--disable-dev-shm-usage',
                '--disable-gpu',
            ])

            // 👇 optional but improves stability
            ->setOption('args', [
                '--no-sandbox',
                '--disable-setuid-sandbox'
            ]);

        // Add custom headers (fixed loop)
        if (!empty($headers)) {
            $browsershot->setExtraHttpHeaders($headers);
        }

        $html = $browsershot->bodyHtml();

        if (! $selector) {
            return [
                'url'    => $url,
                'html'   => $html,
                'count'  => 1,
                'method' => 'js_render',
            ];
        }

        $crawler = new Crawler($html, $url);

        $results = $crawler->filter($selector)->each(function (Crawler $node) use ($attribute) {
            return $attribute
                ? $node->attr($attribute)
                : trim($node->text('', false));
        });

        $results = array_values(array_filter($results, fn($r) => $r !== null && $r !== ''));

        return [
            'url'      => $url,
            'selector' => $selector,
            'results'  => $results,
            'count'    => count($results),
            'method'   => 'js_render',
        ];
    }
}
