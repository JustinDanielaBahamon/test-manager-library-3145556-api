<?php

namespace App\Adapters\Http\Controllers;

use App\Application\Services\LoginService;
use Illuminate\Http\Request;

final class AuthController
{
    /** POST /auth/login */
    public function login(Request $request, LoginService $login)
    {
        $data = $request->validate([
            'username' => ['required', 'string'],
            'password' => ['required', 'string'],
        ]);

        return response()->json($login->execute($data['username'], $data['password']));
    }
}
