<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;

class ProfileController extends Controller
{
    // =========================
    // GET PROFILE
    // =========================
    // public function show(Request $request)
    // {
    //     $user = $request->user();

    //     return response()->json([
    //         'message' => 'Profile fetched',
    //         'data' => [
    //             'id'        => $user->id,
    //             'name'      => $user->name,
    //             'email'     => $user->email,
    //             'phone'     => $user->phone,
    //             'role'      => $user->role,
    //             'user_type' => $user->user_type,
    //             'avatar'    => $user->avatar
    //                 ? asset('storage/' . $user->avatar)
    //                 : null,
    //         ],
    //     ]);
    // }

    // =========================
    // UPDATE PROFILE DATA
    // =========================
    // public function update(Request $request)
    // {
    //     $user = $request->user();

    //     $validated = $request->validate([
    //         'name'  => 'required|string|max:255',
    //         'phone' => 'nullable|string|max:20',
    //     ]);

    //     $user->update($validated);

    //     return response()->json([
    //         'message' => 'Profile updated',
    //         'data'    => $user,
    //     ]);
    // }

    // =========================
    // UPDATE AVATAR
    // =========================
    public function updateAvatar(Request $request)
    {
        $user = $request->user();

        $request->validate([
            'avatar' => 'required|image|mimes:jpg,jpeg,png|max:2048',
        ]);

        // hapus avatar lama (kalau ada)
        if ($user->avatar && Storage::disk('public')->exists($user->avatar)) {
            Storage::disk('public')->delete($user->avatar);
        }

        // simpan avatar baru
        $path = $request->file('avatar')
            ->store('avatars', 'public');

        $user->update([
            'avatar' => $path,
        ]);

        return response()->json([
            'message' => 'Avatar updated',
            'avatar_url' => asset('storage/' . $path),
        ]);
    }
}
