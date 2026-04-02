<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;

class PdfController extends Controller
{
    /**
     * POST /api/pdf
     *
     * Request body (one of html or url is required):
     * {
     *   "html":        "<h1>Hello</h1>",              // raw HTML string
     *   "url":         "https://example.com",         // OR fetch a URL and convert it
     *   "filename":    "invoice.pdf",                 // optional — default: document.pdf
     *   "paper":       "a4",                          // optional — a4 | letter | legal (default: a4)
     *   "orientation": "portrait",                    // optional — portrait | landscape (default: portrait)
     *   "inline":      false,                         // optional — true = view in browser, false = download
     *   "options": {                                  // optional — DomPDF options
     *     "font_size":    12,
     *     "margin_top":   10,
     *     "margin_bottom":10
     *   }
     * }
     *
     * Response: PDF binary (application/pdf)
     */
    public function generate(Request $request)
    {
        // ── Validate ───────────────────────────────────────────────
        $validated = $request->validate([
            'html'             => 'nullable|string',
            'url'              => 'nullable|url|max:2048',
            'filename'         => 'nullable|string|max:255',
            'paper'            => 'nullable|in:a4,letter,legal',
            'orientation'      => 'nullable|in:portrait,landscape',
            'inline'           => 'nullable|boolean',
            'options'          => 'nullable|array',
            'options.font_size'=> 'nullable|integer|min:8|max:72',
            'options.margin_top'   => 'nullable|numeric',
            'options.margin_bottom'=> 'nullable|numeric',
            'options.margin_left'  => 'nullable|numeric',
            'options.margin_right' => 'nullable|numeric',
        ]);

        // Must provide either html or url
        if (empty($validated['html']) && empty($validated['url'])) {
            return response()->json([
                'error' => 'Provide either "html" (HTML string) or "url" (page to convert).',
            ], 422);
        }

        try {
            $html = $this->resolveHtml($validated);

            $paper       = $validated['paper'] ?? 'a4';
            $orientation = $validated['orientation'] ?? 'portrait';
            $filename    = $this->sanitizeFilename($validated['filename'] ?? 'document.pdf');
            $inline      = $validated['inline'] ?? false;
            $options     = $validated['options'] ?? [];

            // Apply options
            $pdfOptions = [
                'isHtml5ParserEnabled' => true,
                'isRemoteEnabled'      => true,   // allows loading remote images/CSS
                'defaultFont'          => 'sans-serif',
                'dpi'                  => 150,
            ];

            if (isset($options['font_size'])) {
                $pdfOptions['defaultFontSize'] = $options['font_size'];
            }

            $pdf = Pdf::loadHTML($html)
                ->setPaper($paper, $orientation)
                ->setOptions($pdfOptions);

            if (! empty($options)) {
                $html = $this->injectMarginStyles($html, $options);
                $pdf  = Pdf::loadHTML($html)
                    ->setPaper($paper, $orientation)
                    ->setOptions($pdfOptions);
            }

            if ($inline) {
                return $pdf->stream($filename);       // opens in browser tab
            }

            return $pdf->download($filename);         // triggers file download

        } catch (\Exception $e) {
            Log::warning('AutoLib PDF generation failed', [
                'error' => $e->getMessage(),
                'url'   => $validated['url'] ?? null,
            ]);

            return response()->json([
                'error'  => 'PDF generation failed.',
                'reason' => $e->getMessage(),
            ], 422);
        }
    }

    // ── Helpers ────────────────────────────────────────────────────

    /**
     * Return the HTML to convert — either from the request body
     * or by fetching a URL.
     */
    private function resolveHtml(array $validated): string
    {
        // Direct HTML provided
        if (! empty($validated['html'])) {
            return $validated['html'];
        }

        // Fetch from URL
        $response = Http::withHeaders([
            'User-Agent' => 'Mozilla/5.0 (compatible; AutoLib/1.0)',
        ])
            ->timeout(15)
            ->get($validated['url']);

        if ($response->failed()) {
            throw new \RuntimeException(
                "HTTP {$response->status()} when fetching {$validated['url']}"
            );
        }

        return $response->body();
    }

    /**
     * Inject a @page CSS rule with custom margins into the HTML.
     * DomPDF respects @page margin declarations.
     */
    private function injectMarginStyles(string $html, array $options): string
    {
        $top    = $options['margin_top']    ?? 10;
        $bottom = $options['margin_bottom'] ?? 10;
        $left   = $options['margin_left']   ?? 10;
        $right  = $options['margin_right']  ?? 10;

        $style = "<style>
            @page {
                margin-top: {$top}mm;
                margin-bottom: {$bottom}mm;
                margin-left: {$left}mm;
                margin-right: {$right}mm;
            }
            body { margin: 0; padding: 0; }
        </style>";

        // Inject before </head> if it exists, otherwise prepend
        if (stripos($html, '</head>') !== false) {
            return str_ireplace('</head>', $style . '</head>', $html);
        }

        return $style . $html;
    }

    /**
     * Sanitize the filename — ensure it ends in .pdf and has no path traversal.
     */
    private function sanitizeFilename(string $filename): string
    {
        $filename = basename($filename);                         // strip any path
        $filename = preg_replace('/[^a-zA-Z0-9_\-.]/', '_', $filename);

        if (! Str::endsWith(strtolower($filename), '.pdf')) {
            $filename .= '.pdf';
        }

        return $filename;
    }
}