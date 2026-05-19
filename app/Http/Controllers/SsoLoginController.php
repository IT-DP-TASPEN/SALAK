<?php

namespace App\Http\Controllers;

use App\Models\User;
use App\Services\SsoTokenService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;
use Symfony\Component\HttpKernel\Exception\HttpException;
use Throwable;

class SsoLoginController extends Controller
{
    public function store(Request $request, SsoTokenService $ssoTokenService): RedirectResponse
    {
        abort_unless(config('services.sso.enabled'), 404);

        $token = (string) $request->query('sso_token');

        $payload = null;
        $employeeId = null;
        $user = null;

        try {
            abort_if($token === '', 403, 'Token SSO tidak ditemukan.');

            $payload = $ssoTokenService->decode($token);
            $employeeId = data_get($payload, 'user.employee_id');
            $email = data_get($payload, 'user.email');

            abort_if(
                ! is_string($employeeId) || trim($employeeId) === ''
                    || ! is_string($email) || trim($email) === '',
                403,
                'Employee ID atau Email tidak ditemukan pada token.'
            );

            $employeeId = trim($employeeId);
            $email = trim($email);

            $user = User::query()
                ->where('username', $employeeId)
                ->orWhere('email', $email)
                ->first();

            abort_if(! $user, 403, 'User tidak terdaftar atau tidak aktif.');

            Auth::login($user);
            $request->session()->regenerate();

            return redirect()->intended('/admin');
        } catch (Throwable $e) {
            $status = $e instanceof HttpException ? $e->getStatusCode() : 500;

            Log::warning('SSO login failed', [
                'status' => $status,
                'employee_id' => $employeeId,
                'user_id' => $user instanceof User ? $user->id : null,
                'ip' => $request->ip(),
            ]);

            throw $e;
        }
    }
}
