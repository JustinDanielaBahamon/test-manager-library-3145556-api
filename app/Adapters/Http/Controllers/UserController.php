<?php

namespace App\Adapters\Http\Controllers;

use App\Application\Services\CreateLibrarianService;
use Illuminate\Http\Request;

final class UserController
{
    /** POST /users (admin) — response never includes password nor password_hash */
    public function store(Request $request, CreateLibrarianService $create)
    {
        $data = $request->validate([
            'username' => ['required', 'string', 'max:120'],
            'password' => ['required', 'string', 'min:8'],
            'role' => ['required', 'string'],
        ]);
        $user = $create->execute($data['username'], $data['password'], $data['role']);

        return response()->json(['id' => $user->id, 'username' => $user->username, 'role' => $user->role], 201);
    }
}
