<?php

namespace App\Http\Controllers;

use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Laravel\Socialite\Facades\Socialite;

class GoogleAuthController extends Controller
{
    /**
     * Redirect to Google OAuth consent screen.
     */
    public function redirect()
    {
        return Socialite::driver('google')->stateless()->redirect();
    }

    /**
     * Handle Google OAuth callback — match email to local User, then login.
     */
    public function callback(Request $request)
    {
        // Handle OAuth error (user denied access, etc.)
        if ($request->query('error')) {
            return redirect()->route('login')
                ->with('login_alert', ['type' => 'warning', 'title' => 'ยกเลิกการเข้าสู่ระบบ', 'message' => 'การเข้าสู่ระบบด้วย Google ถูกยกเลิก', 'tips' => []]);
        }

        try {
            $googleUser = Socialite::driver('google')->stateless()->user();
        } catch (\Throwable $e) {
            return redirect()->route('login')
                ->with('login_alert', ['type' => 'error', 'title' => 'ยืนยันตัวตนไม่สำเร็จ', 'message' => 'ไม่สามารถยืนยันตัวตนกับ Google ได้ กรุณาลองใหม่', 'tips' => []]);
        }

        $email = strtolower(trim($googleUser->getEmail() ?? ''));

        if (empty($email)) {
            return redirect()->route('login')
                ->with('login_alert', ['type' => 'error', 'title' => 'ไม่พบอีเมล', 'message' => 'Google ไม่ได้ส่งอีเมลมา กรุณาตรวจสอบการตั้งค่าบัญชี', 'tips' => []]);
        }

        $user = User::where('email', $email)->first();

        if (! $user) {
            return redirect()->route('login')
                ->with('login_alert', ['type' => 'error', 'title' => 'ไม่พบบัญชีในระบบ', 'message' => "ไม่พบบัญชีสำหรับ {$email} ในระบบ กรุณาติดต่อผู้ดูแล", 'tips' => []]);
        }

        if (isset($user->is_approved) && ! $user->is_approved) {
            return redirect()->route('login')
                ->with('login_alert', ['type' => 'warning', 'title' => 'รอการอนุมัติ', 'message' => 'บัญชีของคุณยังรอการอนุมัติจากผู้ดูแลระบบ', 'tips' => []]);
        }

        Auth::login($user, remember: false);
        $request->session()->regenerate();

        return redirect()->intended(route('admin.dashboard'));
    }
}
