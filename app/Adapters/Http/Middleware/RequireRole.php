<?php

namespace App\Adapters\Http\Middleware;

use App\Domain\DomainError;
use Closure;
use Illuminate\Http\Request;

/** Usage: ->middleware('role:admin') or 'role:admin,librarian'. A 403 never reveals whether the resource exists. */
final class RequireRole
{
    public function handle(Request $request, Closure $next, string ...$roles)
    {
        if (! in_array($request->attributes->get('auth_role'), $roles, true)) {
            throw new DomainError('FORBIDDEN_ROLE', 'No tiene permisos para realizar esta operación.');
        }

        return $next($request);
    }
}
