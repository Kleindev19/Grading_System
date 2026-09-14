<?php

namespace App\Http\Controllers;

use App\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class ProfessorController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        abort_unless($request->attributes->get('auth_user')->role === 'registrar', 403, 'Only registrars can view professors.');

        return response()->json([
            'professors' => User::query()
                ->where('role', 'professor')
                ->latest()
                ->get(['id', 'name', 'email', 'username'])
                ->map(fn (User $professor): array => [
                    'id' => $professor->id,
                    'name' => $professor->name,
                    'email' => $professor->email,
                    'username' => $professor->username,
                    'status' => $professor->email_verified_at ? 'Active' : 'Pending',
                ]),
        ]);
    }
}
