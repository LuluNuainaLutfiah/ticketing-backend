<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Str;

class ForgotPasswordController extends Controller
{
    /**
     * Kirim email reset password
     * POST /api/auth/forgot-password
     */
    public function sendResetLink(Request $request)
    {
        $request->validate([
            'email' => ['required', 'email'],
        ]);

        $user = User::where('email', $request->email)->first();

        // Jangan bocorkan apakah email terdaftar atau tidak (best practice)
        if (!$user) {
            return response()->json([
                'message' => 'Jika email terdaftar, link reset akan dikirim.',
            ], 200);
        }

        // Buat token (plain dikirim via email, hashed disimpan di DB)
        $plainToken = Str::random(64);
        $hashedToken = Hash::make($plainToken);

        // Simpan/update token untuk email ini
        DB::table('password_reset_tokens')->updateOrInsert(
            ['email' => $request->email],
            ['token' => $hashedToken, 'created_at' => now()]
        );

        // Buat link full pakai FRONTEND_URL
        $frontend = rtrim(env('FRONTEND_URL', 'http://localhost:5173'), '/');
        $link = $frontend . '/reset-password?email=' . urlencode($request->email) . '&token=' . urlencode($plainToken);

        // Email clickable (HTML)
        $subject = 'Reset Password - Helpdesk UIKA';
        $html = '
            <div style="font-family: Arial, sans-serif; line-height: 1.6;">
              <h2 style="margin:0 0 10px;">Reset Password</h2>
              <p>Halo <b>' . e($user->name) . '</b>,</p>
              <p>Kami menerima permintaan untuk reset password akun Helpdesk UIKA.</p>

              <p style="margin:16px 0;">
                <a href="' . e($link) . '"
                   style="display:inline-block; padding:10px 16px; background:#16a34a; color:#fff; text-decoration:none; border-radius:8px;">
                   Reset Password
                </a>
              </p>

              <p>Atau copy link ini:</p>
              <p><a href="' . e($link) . '">' . e($link) . '</a></p>

              <p style="color:#6b7280; font-size:12px;">
                Link ini berlaku selama <b>15 menit</b>. Jika Anda tidak merasa meminta reset password, abaikan email ini.
              </p>
            </div>
        ';

        Mail::html($html, function ($msg) use ($request, $subject) {
            $msg->to($request->email)->subject($subject);
        });

        return response()->json([
            'message' => 'Link reset password berhasil dikirim ke email.',
        ], 200);
    }

    /**
     * Reset password
     * POST /api/auth/reset-password
     */
    public function resetPassword(Request $request)
    {
        $request->validate([
            'email' => ['required', 'email'],
            'token' => ['required', 'string'],
            'password' => ['required', 'string', 'min:8', 'confirmed'],
        ]);

        $record = DB::table('password_reset_tokens')
            ->where('email', $request->email)
            ->first();

        if (!$record) {
            return response()->json(['message' => 'Token tidak valid atau sudah kadaluarsa.'], 400);
        }

        // Expired 15 menit
        $expired = Carbon::parse($record->created_at)->addMinutes(15)->isPast();
        $tokenOk = Hash::check($request->token, $record->token);

        if (!$tokenOk || $expired) {
            return response()->json(['message' => 'Token tidak valid atau sudah kadaluarsa.'], 400);
        }

        $user = User::where('email', $request->email)->first();
        if (!$user) {
            return response()->json(['message' => 'User tidak ditemukan.'], 404);
        }

        $user->password = Hash::make($request->password);
        $user->save();

        // Hapus token setelah sukses
        DB::table('password_reset_tokens')->where('email', $request->email)->delete();

        return response()->json([
            'message' => 'Password berhasil direset. Silakan login dengan password baru.',
        ], 200);
    }
}
