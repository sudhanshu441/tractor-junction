<?php

namespace App\Http\Controllers\Web;

use App\Http\Controllers\Controller;
use App\Models\DealerDocument;
use App\Models\LoanDocument;
use App\Support\DocumentVault;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Symfony\Component\HttpFoundation\StreamedResponse;

/**
 * The only way a private document is ever served.
 *
 * The route is signed and expires in minutes, and the signature alone is not
 * enough — every request is also checked against who is asking.
 */
class DocumentController extends Controller
{
    public function __construct(private readonly DocumentVault $vault) {}

    public function loanDocument(Request $request, LoanDocument $document): StreamedResponse
    {
        $user = $request->user();
        $application = $document->application;

        abort_unless($user && $application, 403);

        $ownsIt = $application->user_id === $user->id;
        $canReview = $user->can('loans.view');

        abort_unless($ownsIt || $canReview, 403);
        abort_unless($this->vault->exists($document->file_path), 404);

        $this->vault->logAccess($document->file_path, $user->id, 'loan:'.$application->reference_no);

        return Storage::disk(DocumentVault::DISK)->download(
            $document->file_path,
            $document->original_name ?: basename($document->file_path),
        );
    }

    public function dealerDocument(Request $request, DealerDocument $document): StreamedResponse
    {
        $user = $request->user();
        $dealer = $document->dealer;

        abort_unless($user && $dealer, 403);

        $ownsIt = $dealer->owner_user_id === $user->id;
        $canReview = $user->can('dealers.approve') || $user->can('dealers.view');

        abort_unless($ownsIt || $canReview, 403);
        abort_unless($this->vault->exists($document->file_path), 404);

        $this->vault->logAccess($document->file_path, $user->id, 'dealer:'.$dealer->code);

        return Storage::disk(DocumentVault::DISK)->download(
            $document->file_path,
            $document->original_name ?: basename($document->file_path),
        );
    }
}
