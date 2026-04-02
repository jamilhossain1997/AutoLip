<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Symfony\Component\DomCrawler\Crawler;

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
        $response = Http::withHeaders(array_merge([
            'User-Agent' => 'Mozilla/5.0 (compatible; AutoLib/1.0; +https://autolib.dev)',
            'Accept'     => 'text/html,application/xhtml+xml,application/xml;q=0.9,*/*;q=0.8',
        ], $headers))
            ->timeout($timeout)
            ->get($url);

        if ($response->failed()) {
            throw new \RuntimeException(
                "HTTP {$response->status()} returned from {$url}"
            );
        }

        $html = $response->body();

        if (! $selector) {
            return [
                'url'     => $url,
                'html'    => $html,
                'count'   => 1,
                'method'  => 'static',
            ];
        }

        $crawler = new Crawler($html, $url);

        $results = $crawler->filter($selector)->each(function (Crawler $node) use ($attribute) {
            if ($attribute) {
                return $node->attr($attribute);
            }
            return trim($node->text('', false));
        });

        $results = array_values(array_filter($results, fn($r) => $r !== null && $r !== ''));

        return [
            'url'     => $url,
            'selector' => $selector,
            'results' => $results,
            'count'   => count($results),
            'method'  => 'static',
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
            ->timeout($timeout * 1000)    // Browsershot uses milliseconds
            ->dismissDialogs()
            ->disableJavascript(false);

        // Add custom headers
        foreach ($headers as $key => $value) {
            $browsershot->setExtraHttpHeaders([$key => $value]);
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
            if ($attribute) {
                return $node->attr($attribute);
            }
            return trim($node->text('', false));
        });

        $results = array_values(array_filter($results, fn($r) => $r !== null && $r !== ''));

        return [
            'url'     => $url,
            'selector' => $selector,
            'results' => $results,
            'count'   => count($results),
            'method'  => 'js_render',
        ];
    }
}
