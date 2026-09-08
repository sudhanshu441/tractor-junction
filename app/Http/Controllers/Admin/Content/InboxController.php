<?php

namespace App\Http\Controllers\Admin\Content;

use App\Http\Controllers\Controller;
use App\Models\BlogComment;
use App\Models\ContactMessage;
use App\Models\NewsletterSubscriber;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Response;
use Illuminate\View\View;
use Symfony\Component\HttpFoundation\StreamedResponse;

/**
 * Everything the public writes to us: contact messages, blog comments and the
 * newsletter list. All three are moderation queues, not archives — the counts
 * on the sidebar are what tells someone to open them.
 */
class InboxController extends Controller
{
    public function messages(Request $request): View
    {
        $status = $request->query('status', 'new');

        return view('admin.content.messages', [
            'messages' => ContactMessage::where('status', $status)
                ->with('handler')->latest()->paginate(25)->withQueryString(),
            'status' => $status,
            'counts' => [
                'new' => ContactMessage::where('status', 'new')->count(),
                'read' => ContactMessage::where('status', 'read')->count(),
                'replied' => ContactMessage::where('status', 'replied')->count(),
                'closed' => ContactMessage::where('status', 'closed')->count(),
            ],
        ]);
    }

    public function updateMessage(Request $request, ContactMessage $message): JsonResponse
    {
        $data = $request->validate(['status' => ['required', 'in:new,read,replied,closed']]);

        $message->update([
            'status' => $data['status'],
            'handled_by' => $request->user()->id,
        ]);

        return response()->json(['status' => 'ok', 'message' => __('Message updated.')]);
    }

    public function comments(Request $request): View
    {
        $status = $request->query('status', 'pending');

        return view('admin.content.comments', [
            'comments' => BlogComment::where('status', $status)
                ->with(['user', 'blog'])->latest()->paginate(25)->withQueryString(),
            'status' => $status,
            'counts' => [
                'pending' => BlogComment::where('status', 'pending')->count(),
                'approved' => BlogComment::where('status', 'approved')->count(),
                'rejected' => BlogComment::where('status', 'rejected')->count(),
            ],
        ]);
    }

    public function moderateComment(Request $request, BlogComment $comment): JsonResponse
    {
        $data = $request->validate(['status' => ['required', 'in:approved,rejected']]);

        $comment->update(['status' => $data['status']]);

        return response()->json([
            'status' => 'ok',
            'message' => $data['status'] === 'approved' ? __('Comment published.') : __('Comment rejected.'),
        ]);
    }

    public function subscribers(Request $request): View
    {
        return view('admin.content.subscribers', [
            'subscribers' => NewsletterSubscriber::latest()->paginate(50),
            'counts' => [
                'active' => NewsletterSubscriber::where('is_active', true)->count(),
                'unsubscribed' => NewsletterSubscriber::where('is_active', false)->count(),
            ],
        ]);
    }

    /** Streamed so a list of a hundred thousand addresses does not exhaust memory. */
    public function exportSubscribers(): StreamedResponse
    {
        $filename = 'krishi-junction-subscribers-'.now()->format('Y-m-d').'.csv';

        return Response::streamDownload(function () {
            $out = fopen('php://output', 'w');
            fputcsv($out, ['Email', 'Mobile', 'Active', 'Subscribed on']);

            NewsletterSubscriber::orderBy('id')->chunk(500, function ($batch) use ($out) {
                foreach ($batch as $row) {
                    fputcsv($out, [
                        $row->email,
                        $row->mobile,
                        $row->is_active ? 'yes' : 'no',
                        $row->created_at?->toDateString(),
                    ]);
                }
            });

            fclose($out);
        }, $filename, ['Content-Type' => 'text/csv']);
    }
}
