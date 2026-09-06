<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Panel gate. One users table and one session guard back every panel, so the
 * panel boundary is enforced here rather than by duplicating auth guards:
 *   ->middleware('user.type:staff')   admin panel
 *   ->middleware('user.type:dealer')  dealer panel
 */
class EnsureUserType
{
    public function handle(Request $request, Closure $next, string ...$types): Response
    {
        $user = $request->user();

        if (! $user) {
            return redirect()->guest(route('login'));
        }

        if (! $user->is_active) {
            auth()->logout();

            return redirect()->route('login')
                ->withErrors(['mobile' => __('Your account has been blocked. Contact support.')]);
        }

        abort_unless(in_array($user->user_type, $types, true), 403);

        return $next($request);
    }
}
