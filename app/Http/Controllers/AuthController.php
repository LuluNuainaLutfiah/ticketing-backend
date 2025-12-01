<?php
namespace App\Http\Controllers;

use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rules\Password;

class AuthController extends Controller
{
    // REGISTER: untuk role "user" (mahasiswa/dosen)
    public function register(Request $request)
{
    $data = $request->validate([
        'name'      => ['required','string','min:3'],
        'email'     => ['required','email','unique:users,email'],
        'password'  => ['required','string','min:8'],
        'user_type' => ['required', 'in:mahasiswa,dosen'],
        'npm'       => ['nullable', 'required_if:user_type,mahasiswa'],
        'nik'       => ['nullable', 'required_if:user_type,dosen'],
        'phone'     => ['nullable', 'string'],
    ]);

    $user = User::create([
        'name'      => $data['name'],
        'email'     => $data['email'],
        'password'  => Hash::make($data['password']),

        // default semua register = user
        'role'      => 'user',

        'user_type' => $data['user_type'],
        'npm'       => $data['user_type'] === 'mahasiswa' ? $data['npm'] : null,
        'nik'       => $data['user_type'] === 'dosen' ? $data['nik'] : null,
        'phone'     => $data['phone'] ?? null,
    ]);

    $token = $user->createToken('auth_token')->plainTextToken;

    return response()->json([
        'message' => 'Registrasi berhasil',
        'data' => [
            'user' => $user,
            'token' => $token
        ]
    ], 201);
}

    // LOGIN: untuk admin & user
   public function login(Request $request)
{
    $credentials = $request->validate([
        'email'    => ['required','email'],
        'password' => ['required','string'],
    ]);

    if (!Auth::attempt($credentials)) {
        return response()->json(['message' => 'Email atau password salah'], 401);
    }

    $user = Auth::user();
    $token = $user->createToken('auth_token')->plainTextToken;

    return response()->json([
        'message' => 'Login berhasil',
        'data' => [
            'user' => [
                'id'        => $user->id,
                'name'      => $user->name,
                'email'     => $user->email,
                'role'      => $user->role,
                'user_type' => $user->user_type,
                'npm'       => $user->npm,
                'nik'       => $user->nik,
                'phone'     => $user->phone,
            ],
            'token' => $token
        ]
    ]);
}

    public function me(Request $request)
    {
        return response()->json(['data' => $request->user()]);
    }

    public function logout(Request $request)
    {
        $request->user()->currentAccessToken()->delete();
        return response()->json(['message' => 'Logout berhasil']);
    }
}
