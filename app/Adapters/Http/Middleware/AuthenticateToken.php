<?php

namespace App\Adapters\Http\Middleware;

use App\Application\Ports\TokenIssuer;
use App\Domain\DomainError;
use Closure;
use Illuminate\Http\Request;

/** CA-01.3: every endpoint except login and health requires a token. */
final class AuthenticateToken
{
    public function __construct(private readonly TokenIssuer $tokens)
    {
    }

    public function handle(Request $request, Closure $next)
    {
        $token = $request->bearerToken();
        $claims = $token ? $this->tokens->verify($token) : null;
        if ($claims === null) {
            throw new DomainError('UNAUTHENTICATED', 'Se requiere un token válido.');
        }
        $request->attributes->set('auth_user_id', $claims['sub']);
        $request->attributes->set('auth_role', $claims['role']);

        return $next($request);
    }
}
