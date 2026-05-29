<?php

namespace App\Http\Controllers\Api\Auth;

use App\Http\Controllers\Api\ApiController;
use App\Http\Requests\Auth\ForgotPasswordRequest;
use App\Http\Requests\Auth\LoginRequest;
use App\Http\Requests\Auth\RegisterRequest;
use App\Http\Requests\Auth\ResetPasswordRequest;
use App\Http\Resources\UserResource;
use App\Models\User;
use App\Models\UserProfile;
use App\Services\ActivityLogService;
use Illuminate\Auth\Events\Registered;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Password;
use Illuminate\Validation\ValidationException;

class AuthController extends ApiController
{
    public function __construct(private ActivityLogService $log)
    {
    }

    public function register(RegisterRequest $request): JsonResponse
    {
        $user = User::create([
            'name' => $request->name,
            'email' => $request->email,
            'password' => Hash::make($request->password),
            'phone' => $request->phone,
        ]);

        $role = in_array($request->role, ['user', 'owner']) ? $request->role : 'user';
        $user->assignRole($role);

        UserProfile::create(['user_id' => $user->id]);

        event(new Registered($user));

        $token = $user->createToken('auth_token')->plainTextToken;

        $this->log->log('user.register', $user->id, "Rejestracja: {$user->email}", User::class, $user->id, $request);

        return $this->created([
            'token' => $token,
            'user' => new UserResource($user->load('profile')),
        ], 'Konto zostało utworzone.');
    }

    public function login(LoginRequest $request): JsonResponse
    {
        if (!Auth::attempt($request->only('email', 'password'))) {
            throw ValidationException::withMessages([
                'email' => ['Nieprawidłowy email lub hasło.'],
            ]);
        }

        $user = Auth::user();

        if (!$user->is_active) {
            Auth::logout();
            return $this->error('Twoje konto zostało zablokowane.', 403);
        }

        $user->tokens()->where('name', 'auth_token')->delete();
        $token = $user->createToken('auth_token')->plainTextToken;

        $this->log->log('user.login', $user->id, "Logowanie: {$user->email}", User::class, $user->id, $request);

        return $this->success([
            'token' => $token,
            'user' => new UserResource($user->load('profile')),
        ], 'Zalogowano pomyślnie.');
    }

    public function logout(Request $request): JsonResponse
    {
        $user = $request->user();
        $this->log->log('user.logout', $user->id, "Wylogowanie: {$user->email}", User::class, $user->id, $request);
        $request->user()->currentAccessToken()->delete();

        return $this->success(null, 'Wylogowano pomyślnie.');
    }

    public function me(Request $request): JsonResponse
    {
        return $this->success(
            new UserResource($request->user()->load('profile'))
        );
    }

    public function forgotPassword(ForgotPasswordRequest $request): JsonResponse
    {
        Password::sendResetLink($request->only('email'));

        return $this->success(null, 'Jeśli konto istnieje, wyślemy link do resetowania hasła.');
    }

    public function resetPassword(ResetPasswordRequest $request): JsonResponse
    {
        $status = Password::reset(
            $request->only('email', 'password', 'password_confirmation', 'token'),
            function (User $user, string $password) {
                $user->forceFill(['password' => Hash::make($password)])->save();
                $user->tokens()->delete();
            }
        );

        if ($status !== Password::PASSWORD_RESET) {
            throw ValidationException::withMessages([
                'email' => [__($status)],
            ]);
        }

        return $this->success(null, 'Hasło zostało zmienione. Możesz się zalogować.');
    }
}
