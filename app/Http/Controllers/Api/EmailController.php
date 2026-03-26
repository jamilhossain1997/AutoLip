<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Mail\AutolibMail;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Validator;

class EmailController extends Controller
{
    /**
     * POST /api/email
     *
     * Request body:
     * {
     *   "to": "user@example.com",                    // required — string or array of strings
     *   "subject": "Your invoice is ready",          // required
     *   "html": "<h1>Hello</h1>",                    // required — HTML email body
     *   "text": "Hello",                             // optional — plain text fallback (improves deliverability)
     *   "from_name": "AutoLib",                      // optional — sender display name
     *   "from_address": "hello@yourdomain.com",      // optional — custom from (must be verified in SES)
     *   "reply_to": ["support@example.com"],         // optional — reply-to addresses
     *   "cc": ["manager@example.com"],               // optional
     *   "bcc": ["archive@example.com"]               // optional
     * }
     *
     * Response:
     * {
     *   "sent": true,
     *   "recipients": 1,
     *   "to": ["user@example.com"],
     *   "subject": "Your invoice is ready"
     * }
     */
    public function send(Request $request)
    {
        // ── Validate ───────────────────────────────────────────────
        $validated = $request->validate([
            'to'           => 'required',           // string or array — validated further below
            'subject'      => 'required|string|max:998',
            'html'         => 'required|string',
            'text'         => 'nullable|string',
            'from_name'    => 'nullable|string|max:100',
            'from_address' => 'nullable|email',
            'reply_to'     => 'nullable|array',
            'reply_to.*'   => 'email',
            'cc'           => 'nullable|array',
            'cc.*'         => 'email',
            'bcc'          => 'nullable|array',
            'bcc.*'        => 'email',
        ]);

        // ── Normalise "to" field (string or array) ─────────────────
        $toAddresses = $this->normaliseAddresses($validated['to']);

        if (empty($toAddresses)) {
            return response()->json(['error' => '"to" must contain at least one valid email address.'], 422);
        }

        // ── Enforce per-request recipient cap ──────────────────────
        $user    = $request->auth_user;
        $maxTo   = match ($user->plan) {
            'business' => 50,
            'pro'      => 20,
            'starter'  => 5,
            default    => 1,     // free plan: single recipient only
        };

        if (count($toAddresses) > $maxTo) {
            return response()->json([
                'error' => "Your {$user->plan} plan allows up to {$maxTo} recipients per request.",
                'got'   => count($toAddresses),
            ], 403);
        }

        // ── Build the Mailable ─────────────────────────────────────
        $mailable = new AutolibMail(
            subjectLine:  $validated['subject'],
            htmlBody:     $validated['html'],
            textBody:     $validated['text'] ?? null,
            fromName:     $validated['from_name'] ?? null,
            fromAddress:  $validated['from_address'] ?? null,
            replyTo:      $validated['reply_to'] ?? [],
        );

        // ── Send ───────────────────────────────────────────────────
        try {
            $mailer = Mail::to($toAddresses);

            if (! empty($validated['cc'])) {
                $mailer = $mailer->cc($validated['cc']);
            }

            if (! empty($validated['bcc'])) {
                $mailer = $mailer->bcc($validated['bcc']);
            }

            $mailer->send($mailable);

            return response()->json([
                'sent'       => true,
                'recipients' => count($toAddresses),
                'to'         => $toAddresses,
                'subject'    => $validated['subject'],
            ]);

        } catch (\Symfony\Component\Mailer\Exception\TransportException $e) {
            // SES-specific errors — likely unverified sender or bad credentials
            Log::error('AutoLib email transport error', [
                'error' => $e->getMessage(),
                'to'    => $toAddresses,
            ]);

            return response()->json([
                'error'  => 'Email delivery failed. Check your SES configuration.',
                'reason' => $e->getMessage(),
                'docs'   => 'https://docs.autolib.dev/email',
            ], 502);

        } catch (\Exception $e) {
            Log::error('AutoLib email failed', [
                'error' => $e->getMessage(),
                'to'    => $toAddresses,
            ]);

            return response()->json([
                'error'  => 'Failed to send email.',
                'reason' => $e->getMessage(),
            ], 500);
        }
    }

    // ── Helpers ────────────────────────────────────────────────────

    /**
     * Accept "to" as either a string email or an array of emails.
     * Validates each address and returns a clean array.
     */
    private function normaliseAddresses(mixed $to): array
    {
        $addresses = is_array($to) ? $to : [$to];

        return array_values(array_filter(
            $addresses,
            fn($addr) => is_string($addr) && filter_var($addr, FILTER_VALIDATE_EMAIL)
        ));
    }
}