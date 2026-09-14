<?php

namespace App\Http\Controllers;

use App\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;

class AuthController extends Controller
{
    public function register(Request $request): JsonResponse
    {
        $data = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'username' => ['required', 'string', 'alpha_dash', 'min:3', 'max:32', 'unique:users,username'],
            'email' => ['required', 'email', 'max:255', 'unique:users,email'],
            'password' => ['required', 'string', 'min:8', 'confirmed'],
            'role' => ['required', 'in:student,professor,registrar'],
            'student_id' => ['required_if:role,student', 'nullable', 'string', 'max:80', 'unique:users,student_id'],
        ]);

        $user = User::create([
            'name' => $data['name'],
            'username' => $data['username'],
            'email' => $data['email'],
            'password' => Hash::make($data['password']),
            'role' => $data['role'],
            'student_id' => $data['role'] === 'student' ? ($data['student_id'] ?? null) : null,
            'api_token' => Str::random(60),
        ]);

        $code = (string) random_int(100000, 999999);
        $user->verificationCodes()->create([
            'code_hash' => Hash::make($code),
            'expires_at' => now()->addMinutes(10),
        ]);
        Mail::raw("Your Colegio de Montalban verification code is: {$code}\n\nThis code expires in 10 minutes.", function ($message) use ($user) {
            $message->to($user->email)->subject('Verify your Colegio de Montalban account');
        });

        return response()->json([
            'message' => 'Registration successful. Check your email for the verification code.',
            'requires_verification' => true,
            'email' => $user->email,
        ], 201);
    }

    public function login(Request $request): JsonResponse
    {
        $data = $request->validate([
            'username' => ['required', 'string'],
            'password' => ['required', 'string'],
        ]);

        $user = User::where('username', $data['username'])->first();
        if (!$user || !Hash::check($data['password'], $user->password)) {
            return response()->json(['message' => 'Invalid username or password.'], 422);
        }
        if (!$user->email_verified_at) {
            return response()->json(['message' => 'Please verify your email before logging in.', 'requires_verification' => true, 'email' => $user->email], 403);
        }

        $user->forceFill(['api_token' => Str::random(60)])->save();

        return response()->json(['user' => $user, 'token' => $user->api_token]);
    }

    public function verifyEmail(Request $request): JsonResponse
    {
        $data = $request->validate([
            'email' => ['required', 'email'],
            'code' => ['required', 'digits:6'],
        ]);
        $user = User::where('email', $data['email'])->first();
        $verification = $user?->verificationCodes()->latest()->first();

        if (!$user || !$verification || $verification->expires_at->isPast() || !Hash::check($data['code'], $verification->code_hash)) {
            return response()->json(['message' => 'Invalid or expired verification code.'], 422);
        }

        $user->forceFill(['email_verified_at' => now(), 'api_token' => Str::random(60)])->save();
        $verification->delete();

        return response()->json(['message' => 'Email verified successfully.', 'user' => $user, 'token' => $user->api_token]);
    }

    public function me(Request $request): JsonResponse
    {
        return response()->json(['user' => $request->attributes->get('auth_user')]);
    }

    public function logout(Request $request): JsonResponse
    {
        $user = $request->attributes->get('auth_user');
        $user->forceFill(['api_token' => null])->save();

        return response()->json(['message' => 'Logged out successfully.']);
    }
}