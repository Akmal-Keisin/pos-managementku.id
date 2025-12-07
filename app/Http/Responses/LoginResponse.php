<?php

namespace App\Http\Responses;

use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Auth;
use Laravel\Fortify\Contracts\LoginResponse as LoginResponseContract;

class LoginResponse implements LoginResponseContract
{
    /**
     * Create an HTTP response that represents the object.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Symfony\Component\HttpFoundation\Response
     */
    public function toResponse($request)
    {
        /** @var \App\Models\User $user */
        $user = Auth::user();

        // Redirect based on user role
        $redirectUrl = match ($user->role) {
            'cashier' => route('pos-terminal.index'),
            'admin', 'super-admin' => route('dashboard'),
            default => route('dashboard'),
        };

        return $request->wantsJson()
            ? new JsonResponse('', 204)
            : redirect()->intended($redirectUrl);
    }
}
