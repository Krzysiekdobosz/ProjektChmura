<?php

namespace App\Http\Middleware;

use App\Models\ActivityLog;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class LogActivity
{
    private array $actions = [
        'POST /api/v1/auth/login' => 'user.login',
        'POST /api/v1/auth/logout' => 'user.logout',
        'POST /api/v1/auth/register' => 'user.register',
        'POST /api/v1/owner/properties' => 'property.created',
        'PUT /api/v1/owner/properties' => 'property.updated',
        'DELETE /api/v1/owner/properties' => 'property.deleted',
        'POST /api/v1/owner/contracts' => 'contract.generated',
        'PATCH /api/v1/owner/payments' => 'payment.status_changed',
    ];

    public function handle(Request $request, Closure $next): Response
    {
        return $next($request);
    }

    public function terminate(Request $request, Response $response): void
    {
        $method = $request->method();
        $path = '/' . $request->path();

        foreach ($this->actions as $pattern => $action) {
            [$patternMethod, $patternPath] = explode(' ', $pattern, 2);

            if ($method === $patternMethod && str_starts_with($path, $patternPath)) {
                ActivityLog::create([
                    'user_id' => $request->user()?->id,
                    'action' => $action,
                    'description' => "{$method} {$path}",
                    'ip_address' => $request->ip(),
                    'user_agent' => $request->userAgent(),
                ]);
                break;
            }
        }
    }
}
