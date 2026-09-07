<?php

namespace App\Http\Controllers\Ajax;

use App\Http\Controllers\Controller;
use App\Models\ListingReport;
use App\Models\UsedListing;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class ListingReportController extends Controller
{
    public function store(Request $request, UsedListing $listing): JsonResponse
    {
        $data = $request->validate([
            'reason' => ['required', 'in:sold,fake,wrong_price,spam,abusive,duplicate,other'],
            'details' => ['nullable', 'string', 'max:1000'],
        ]);

        // One open report per person per listing; piling on adds no information.
        $existing = ListingReport::where('used_listing_id', $listing->id)
            ->where('status', 'open')
            ->when($request->user(), fn ($q) => $q->where('reported_by', $request->user()->id))
            ->exists();

        if ($existing) {
            return response()->json([
                'status' => 'ok',
                'message' => __('You have already reported this listing. Our team is looking at it.'),
            ]);
        }

        ListingReport::create([
            'used_listing_id' => $listing->id,
            'reported_by' => $request->user()?->id,
            'reason' => $data['reason'],
            'details' => $data['details'] ?? null,
            'status' => 'open',
        ]);

        return response()->json([
            'status' => 'ok',
            'message' => __('Thank you. Our moderation team will review this listing.'),
        ]);
    }
}
