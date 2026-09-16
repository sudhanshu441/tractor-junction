<?php

namespace App\Http\Controllers\Ajax;

use App\Domain\Analytics\Services\VisitorJourney;
use App\Domain\Lead\Services\LeadService;
use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

/**
 * "Shall we call you back?" — the only way a visitor's name and number enter
 * the system.
 *
 * There is no OTP here on purpose: a callback request is a low-commitment ask,
 * and putting a verification step in front of it loses most of the people it is
 * meant to catch. The lead is therefore created unverified and the desk knows
 * it: the number is confirmed when somebody rings it.
 */
class CallbackController extends Controller
{
    public function __construct(
        private readonly LeadService $leads,
        private readonly VisitorJourney $journey,
    ) {}

    public function store(Request $request): JsonResponse
    {
        $data = $request->validate([
            'name' => ['required', 'string', 'max:100'],
            'mobile' => ['required', 'digits:10', 'regex:/^[6-9]\d{9}$/'],
            'interest' => ['nullable', 'in:buy,sell,finance,insurance,dealer'],
            'message' => ['nullable', 'string', 'max:500'],
            'consent' => ['accepted'],
        ], [
            'consent.accepted' => __('Please tick the box so we know it is alright to call you.'),
            'mobile.regex' => __('Enter a valid 10-digit Indian mobile number.'),
        ]);

        $visitorId = VisitorJourney::idFrom($request);

        $lead = $this->leads->capture('callback', [
            'name' => $data['name'],
            'mobile' => $data['mobile'],
            'mobile_verified' => false,
            'message' => $data['message'] ?? null,
            'visitor_id' => $visitorId,
            'channel' => 'web',
            'meta' => array_filter([
                'interest' => $data['interest'] ?? null,
                'requested_on' => $request->headers->get('referer'),
                'consent_given_at' => now()->toIso8601String(),
            ]),
            'utm_source' => $request->query('utm_source'),
            'ip' => $request->ip(),
            'user_agent' => substr((string) $request->userAgent(), 0, 255),
        ], null, $request->user());

        return response()->json([
            'status' => 'ok',
            'message' => $lead->status === 'duplicate'
                ? __('We already have your number and someone will call you.')
                : __('Thank you. Someone will call you on :mobile.', ['mobile' => $data['mobile']]),
            'data' => ['reference' => $lead->reference_no],
        ]);
    }
}
