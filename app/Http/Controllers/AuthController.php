<?php

namespace App\Http\Controllers;

use App\Models\User;
use App\Models\District;
use App\Models\Hospital;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Password;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Carbon\Carbon;

class AuthController extends Controller
{
    /** Max wrong guesses for an OTP code / failed password logins before lockout. */
    private const OTP_MAX_ATTEMPTS = 5;
    private const LOGIN_MAX_ATTEMPTS = 5;
    private const LOGIN_LOCK_SECONDS = 300;
    /** Per-IP cap (any email) so one address cannot spray many accounts. */
    private const LOGIN_IP_MAX_ATTEMPTS = 20;
    /** Valid-format bcrypt hash that matches nothing; burns the same time as a real check. */
    private const DUMMY_HASH = '$2y$10$UrRrIF7sqaFI4.B16nSGJ.O6s/u2A34YsM07gyQ0vtWnov1LZTnc.';
    private const LOGIN_OTP_TTL_SECONDS = 300;

    /** Cache key holding the wrong-guess counter for a forgot-password OTP. */
    private function resetOtpAttemptsKey(string $email): string
    {
        return 'pwreset_otp_attempts_' . sha1(strtolower($email));
    }

    // ... (existing methods)

    public function showForgotPasswordForm()
    {
        return view('auth.passwords.email');
    }

    /**
     * Step 1 of the OTP forgot-password flow: an admin verifies the email
     * exists, then a 6-digit OTP code (not a link) is generated, hashed and
     * stored in password_reset_tokens (email is the primary key, so a
     * re-submit for the same email simply overwrites the previous code -
     * this doubles as the "resend OTP" action from the OTP page), and
     * emailed to the address. The email itself is kept in the session
     * (never the URL) so step 2/3 know which address is mid-flow.
     */
    public function sendResetLinkEmail(Request $request)
    {
        $request->validate(['email' => 'required|email']);

        // Check if user exists
        $user = User::where('email', $request->email)->first();
        if (!$user) {
            return back()->with('error', 'ไม่พบอีเมลนี้ในระบบ')->withInput();
        }

        // Generate a 6-digit OTP code
        $otp = str_pad((string) random_int(0, 999999), 6, '0', STR_PAD_LEFT);

        // Store in password_reset_tokens table (hashed, same as the old
        // token-based flow - just a short numeric code instead of a
        // 64-char string, and a 5-minute window instead of 60).
        DB::table('password_reset_tokens')->updateOrInsert(
            ['email' => $request->email],
            [
                'email' => $request->email,
                'token' => Hash::make($otp),
                'created_at' => Carbon::now()
            ]
        );

        // A newly issued code starts with a fresh wrong-guess budget.
        \Illuminate\Support\Facades\Cache::forget($this->resetOtpAttemptsKey($request->email));

        // Send email
        try {
            \Illuminate\Support\Facades\Mail::send('auth.emails.otp-code', ['otp' => $otp, 'email' => $request->email], function ($message) use ($request) {
                $message->to($request->email);
                $message->subject('รหัส OTP สำหรับสร้างรหัสผ่านใหม่ - Salt & Sodium Smart Monitor');
            });
        } catch (\Exception $e) {
            return back()->with('error', 'ไม่สามารถส่งอีเมลได้ในขณะนี้ กรุณาตรวจสอบการตั้งค่าเมล์ของเซิร์ฟเวอร์')->withInput();
        }

        // Keep the email in the session (not the URL/query string) for the
        // OTP-entry and resend steps.
        $request->session()->put('otp_reset_email', $request->email);

        \App\Support\ActivityLog::record('password_reset_requested', 'ขอรหัส OTP เพื่อตั้งรหัสผ่านใหม่', $user, [], $request);

        return redirect()->route('password.otp.form')->with('success', 'เราได้ส่งรหัส OTP ไปที่อีเมลของคุณแล้ว');
    }

    /**
     * Step 2: show the "enter OTP" page. The 5-minute countdown shown to
     * the admin is computed from the stored created_at (not restarted on
     * every page reload/back-navigation), so refreshing the page can't be
     * used to extend the actual server-side expiry checked in verifyOtp().
     */
    public function showOtpForm(Request $request)
    {
        $email = $request->session()->get('otp_reset_email');
        if (!$email) {
            return redirect()->route('password.request')->with('error', 'กรุณากรอกอีเมลเพื่อขอรหัส OTP ก่อนครับ');
        }

        $reset = DB::table('password_reset_tokens')->where('email', $email)->first();
        $remainingSeconds = 300;
        if ($reset) {
            // diffInSeconds() defaults to an absolute (always non-negative)
            // value, which is exactly "seconds elapsed since created_at"
            // here since created_at can never be in the future.
            $elapsed = Carbon::parse($reset->created_at)->diffInSeconds(Carbon::now());
            $remainingSeconds = max(0, 300 - $elapsed);
        }

        return view('auth.passwords.otp', ['email' => $email, 'remainingSeconds' => (int) $remainingSeconds]);
    }

    /**
     * Step 2 -> 3: verify the 6-digit code against the hashed value stored
     * for this email, within the 5-minute window. On success, a fresh
     * random reset token is generated and swapped into the SAME
     * password_reset_tokens row (created_at refreshed too), and the admin
     * is handed straight to the existing link-based reset-password
     * page/flow below - it never sees this token appear anywhere except
     * as the redirect target, so simply knowing an email address is never
     * enough to reach the reset-password form without first passing the
     * OTP check.
     */
    public function verifyOtp(Request $request)
    {
        $request->validate([
            'otp' => 'required|digits:6',
        ], [
            'otp.required' => 'กรุณากรอกรหัส OTP',
            'otp.digits' => 'รหัส OTP ต้องเป็นตัวเลข 6 หลัก',
        ]);

        $email = $request->session()->get('otp_reset_email');
        if (!$email) {
            return redirect()->route('password.request')->with('error', 'เซสชันหมดอายุ กรุณาเริ่มต้นขอรหัส OTP ใหม่อีกครั้ง');
        }

        $reset = DB::table('password_reset_tokens')->where('email', $email)->first();
        if (!$reset) {
            return redirect()->route('password.request')->with('error', 'ไม่พบคำขอสร้างรหัสผ่านใหม่ กรุณาขอรหัส OTP อีกครั้ง');
        }

        if (Carbon::parse($reset->created_at)->addMinutes(5)->isPast()) {
            DB::table('password_reset_tokens')->where('email', $email)->delete();
            return back()->with('error', 'รหัส OTP หมดอายุแล้ว กรุณากดขอรหัสใหม่อีกครั้ง');
        }

        $attemptsKey = $this->resetOtpAttemptsKey($email);
        if (!Hash::check($request->otp, $reset->token)) {
            // Max 5 wrong guesses per issued code, same budget as the
            // login OTP. On the 5th miss the code is destroyed, so the
            // user has to request a new one from step 1.
            $attempts = (int) \Illuminate\Support\Facades\Cache::get($attemptsKey, 0) + 1;
            if ($attempts >= self::OTP_MAX_ATTEMPTS) {
                DB::table('password_reset_tokens')->where('email', $email)->delete();
                \Illuminate\Support\Facades\Cache::forget($attemptsKey);
                $request->session()->forget('otp_reset_email');
                \App\Support\ActivityLog::record('password_reset_otp_failed', 'กรอก OTP ลืมรหัสผ่านผิดครบ 5 ครั้ง (ยกเลิกรหัส)', null, ['reason' => 'locked'], $request, $email);
                return redirect()->route('password.request')->with('error', 'กรอกรหัส OTP ผิดเกินจำนวนครั้งที่กำหนด กรุณาขอรหัส OTP ใหม่อีกครั้ง');
            }
            \Illuminate\Support\Facades\Cache::put($attemptsKey, $attempts, now()->addMinutes(10));
            \App\Support\ActivityLog::record('password_reset_otp_failed', 'กรอก OTP ลืมรหัสผ่านไม่ถูกต้อง (ครั้งที่ ' . $attempts . ')', null, ['reason' => 'wrong_otp'], $request, $email);
            return back()->with('error', 'รหัส OTP ไม่ถูกต้อง เหลืออีก ' . (self::OTP_MAX_ATTEMPTS - $attempts) . ' ครั้ง');
        }
        \Illuminate\Support\Facades\Cache::forget($attemptsKey);

        // OTP confirmed - issue a fresh unguessable token for the actual
        // password-change step and give it its own full window.
        $resetToken = Str::random(64);
        DB::table('password_reset_tokens')->where('email', $email)->update([
            'token' => Hash::make($resetToken),
            'created_at' => Carbon::now(),
        ]);

        $request->session()->forget('otp_reset_email');

        return redirect()->route('password.reset', ['token' => $resetToken, 'email' => $email]);
    }

    public function showResetPasswordForm($token)
    {
        return view('auth.passwords.reset', ['token' => $token, 'email' => request()->get('email')]);
    }

    public function resetPassword(Request $request)
    {
        $request->validate([
            'token' => 'required',
            'email' => 'required|email',
            'password' => [
                'required',
                'string',
                'confirmed',
                // Strict rules, mirrored 1:1 by the right-hand checklist in
                // auth.passwords.reset (updatePasswordChecklist()) - a
                // password the UI shows as fully green here always passes,
                // and one that's missing something is always rejected.
                function ($attribute, $value, $fail) use ($request) {
                    $unmet = [];
                    if (mb_strlen($value) < 8) {
                        $unmet[] = 'อย่างน้อย 8 ตัวอักษร';
                    }
                    if (!preg_match('/[A-Z]/', $value)) {
                        $unmet[] = 'ตัวพิมพ์ใหญ่ (A-Z)';
                    }
                    if (!preg_match('/[a-z]/', $value)) {
                        $unmet[] = 'ตัวพิมพ์เล็ก (a-z)';
                    }
                    if (!preg_match('/[0-9]/', $value)) {
                        $unmet[] = 'ตัวเลข (0-9)';
                    }
                    if (!preg_match('/[^A-Za-z0-9]/', $value)) {
                        $unmet[] = 'อักขระพิเศษ';
                    }
                    $emailLocal = strtolower((string) (strstr((string) $request->input('email'), '@', true) ?: ''));
                    if ($emailLocal !== '' && str_contains(strtolower($value), $emailLocal)) {
                        $unmet[] = 'ต้องไม่มีส่วนหนึ่งส่วนใดของอีเมลที่ใช้เข้าสู่ระบบ';
                    }

                    if (!empty($unmet)) {
                        $fail('รหัสผ่านยังไม่ผ่านเกณฑ์ความปลอดภัย: ' . implode(', ', $unmet));
                    }
                },
            ],
        ], [
            'password.required' => 'กรุณากรอกรหัสผ่านใหม่',
            'password.confirmed' => 'การยืนยันรหัสผ่านไม่ตรงกัน',
        ]);

        $reset = DB::table('password_reset_tokens')
            ->where('email', $request->email)
            ->first();

        if (!$reset || !Hash::check($request->token, $reset->token)) {
            return back()->with('error', 'ลิงก์รีเซ็ตรหัสผ่านไม่ถูกต้องหรือหมดอายุ');
        }

        // Check expiry (e.g., 60 minutes)
        if (Carbon::parse($reset->created_at)->addMinutes(60)->isPast()) {
            DB::table('password_reset_tokens')->where('email', $request->email)->delete();
            return back()->with('error', 'ลิงก์รีเซ็ตรหัสผ่านหมดอายุแล้ว');
        }

        $user = User::where('email', $request->email)->first();
        if (!$user) {
            return back()->with('error', 'ไม่พบผู้ใช้งานรายนี้')->withInput();
        }

        $user->password = Hash::make($request->password);
        $user->save();

        // Delete token after use
        DB::table('password_reset_tokens')->where('email', $request->email)->delete();

        \App\Support\ActivityLog::record('password_reset_done', 'ตั้งรหัสผ่านใหม่สำเร็จ (ผ่านการยืนยัน OTP)', $user, [], $request);

        // Auto-login straight into the system after a successful password reset —
        // no need to go through the login page again.  If the user ticked
        // "ออกจากระบบอุปกรณ์อื่น" we also invalidate every other session.
        Auth::login($user, true);
        $request->session()->regenerate();

        if ($request->boolean('logout_other')) {
            Auth::logoutOtherDevices($request->input('password'));
        }

        return redirect()->intended('admin')->with('success', 'ตั้งรหัสผ่านใหม่สำเร็จ เข้าสู่ระบบอัตโนมัติแล้ว');
    }

    public function getDistricts($province_id)
    {
        $districts = District::where('province_id', $province_id)
            ->orderBy('district_name', 'asc')
            ->get();

        return response()->json($districts);
    }

    public function getHospitals($province_id, $district_id)
    {
        $hospitals = Hospital::where('province_id', $province_id)
            ->where('district_id', $district_id)
            ->orderBy('hos_name', 'asc')
            ->get();

        return response()->json($hospitals);
    }

    /**
     * Backs the ตำบล dropdown on the staff registration form (รพ.สต. rank).
     * This route existed before the dropdown did - it used to read distinct
     * (subdistrict, subdistrict_code) pairs out of subdistrict_hospital,
     * but that table only lists subdistrict for a tambon that already has
     * a รพ.สต. row in it, and subdistrict_code was actually populated with
     * the district_id (see SubdistrictHospitalImport's comment), so every
     * tambon in a district came back with the same bogus id. Tambon_id on
     * users needs a real, unique code, so this now reads the dedicated
     * tambon reference table (imported from the official ตำบล list)
     * instead - complete for every tambon in the 5 provinces, not just
     * ones with a facility already on file.
     */
    public function getSubdistricts($province_id, $district_id)
    {
        $tambons = \App\Models\Tambon::where('province_id', $province_id)
            ->where('district_id', $district_id)
            ->orderBy('tambon_name', 'asc')
            ->get(['tambon_id', 'tambon_name']);

        return response()->json($tambons);
    }

    public function getSubdistrictHospitals($province_id, $district_id, $tambon_id = null)
    {
        $query = \DB::table('subdistrict_hospital')
            ->where('province_id', $province_id)
            ->where('district_id', $district_id);

        if ($tambon_id) {
            $query->where('subdistrict_code', $tambon_id);
        }

        $hospitals = $query->select('hospital_code_5_digit as sh_id', 'hospital_name as sh_name', 'affiliation')
            ->orderBy('sh_name', 'asc')
            ->get();

        return response()->json($hospitals);
    }

    public function register(Request $request)
    {
        // แอดมิน (สคร.) ปิดรับสมัครระดับ สคร. ได้ที่ จัดการระบบ - กันการส่งค่าตรงมาโดยไม่ผ่านฟอร์ม
        if ((string) $request->input('User_rank_id') === '1' && !\App\Support\SystemSettings::get('register_rank1_enabled', true)) {
            return back()->withErrors(['User_rank_id' => 'ขณะนี้ปิดรับสมัครสมาชิกระดับ สคร.'])->withInput();
        }

        $validator = Validator::make($request->all(), [
            'prefix' => 'required|string|max:10',
            'User_firstname' => 'required|string|max:255',
            'User_lastname' => 'required|string|max:255',
            'phone' => 'nullable|string|max:20|regex:/^0[0-9]{1,2}-?[0-9]{3}-?[0-9]{3,4}$/',
            'email' => 'required|string|email|max:255|unique:users',
            'password' => 'required|string|min:8|confirmed',
            'User_position' => 'required|string|max:255',
            'User_rank_id' => 'required|in:1,2,3,4,5',
            'Province_id' => 'required|string|max:255',
            'District_id' => 'nullable|string|max:255',
            'Hospital_id' => 'nullable|string|max:255',
            'Tambon_id' => 'nullable|string|max:255',
            'Subdistrict_Hospital_id' => 'nullable|string|max:255',
            'Con_name' => 'nullable|string|max:255',
        ], [
            'required' => 'กรุณากรอกข้อมูล :attribute',
            'email' => 'รูปแบบอีเมลไม่ถูกต้อง',
            'unique' => 'อีเมลนี้ถูกใช้งานแล้ว',
            'min' => 'รหัสผ่านต้องมีความยาวอย่างน้อย :min ตัวอักษร',
            'confirmed' => 'การยืนยันรหัสผ่านไม่ตรงกัน',
            'phone.regex' => 'รูปแบบเบอร์โทรศัพท์ไม่ถูกต้อง (เช่น 081-234-5678)',
        ]);

        if ($validator->fails()) {
            return back()->withErrors($validator)->withInput();
        }

        $user = User::create([
            'name' => $request->User_firstname . ' ' . $request->User_lastname,
            'email' => $request->email,
            'password' => Hash::make($request->password),
            'prefix' => $request->prefix,
            'User_firstname' => $request->User_firstname,
            'User_lastname' => $request->User_lastname,
            'phone' => $request->phone,
            'User_position' => $request->User_position,
            'User_rank_id' => $request->User_rank_id,
            'Province_id' => $request->Province_id,
            'District_id' => $request->District_id,
            'Tambon_id' => $request->Tambon_id,
            'hos_id' => $request->Hospital_id,
            'sh_id' => $request->Subdistrict_Hospital_id,
            'Con_name' => $request->Con_name,
            'is_approved' => ($request->User_rank_id == 1) ? 1 : 0,
        ]);

        \App\Support\ActivityLog::record('register', 'สมัครสมาชิกใหม่ (รอการอนุมัติ)', $user, ['rank' => (string) $request->User_rank_id], $request);

        // You can auto-login the user or redirect to login page
        return redirect()->route('staff')->with('success', 'สมัครสมาชิกเรียบร้อยแล้ว กรุณาเข้าสู่ระบบ');
    }

    public function login(Request $request)
    {
        // Named bag: a failed sign-in must not light up the register tab
        // (which reads the default bag).
        $request->validateWithBag('login', [
            'email' => 'required|email',
            'password' => 'required',
        ], [
            'email.required' => 'กรุณากรอกอีเมล',
            'email.email' => 'รูปแบบอีเมลไม่ถูกต้อง',
            'password.required' => 'กรุณากรอกรหัสผ่าน',
        ]);

        // Throttle: max 5 failed sign-ins per email + IP, then locked for 5 minutes.
        $throttleKey = 'login|' . strtolower($request->email) . '|' . $request->ip();
        $ipKey = 'login-ip|' . $request->ip();
        $locked = \Illuminate\Support\Facades\RateLimiter::tooManyAttempts($throttleKey, self::LOGIN_MAX_ATTEMPTS)
            ? $throttleKey
            : (\Illuminate\Support\Facades\RateLimiter::tooManyAttempts($ipKey, self::LOGIN_IP_MAX_ATTEMPTS) ? $ipKey : null);
        if ($locked !== null) {
            $minutes = (int) ceil(\Illuminate\Support\Facades\RateLimiter::availableIn($locked) / 60);
            $this->logAfterResponse('login_failed', 'ถูกล็อกชั่วคราว: กรอกรหัสผ่านผิดเกินกำหนด', null, ['reason' => 'locked'], $request, strtolower($request->email));
            $alert = [
                'type' => 'lock',
                'title' => 'ระบบล็อกการเข้าสู่ระบบชั่วคราว',
                'message' => 'กรอกรหัสผ่านผิดเกินจำนวนครั้งที่กำหนด กรุณาลองใหม่อีกครั้งใน ' . max(1, $minutes) . ' นาที',
                'tips' => [
                    'การล็อกนี้ช่วยป้องกันบัญชีของท่านจากการเดารหัสผ่าน และจะปลดล็อกอัตโนมัติเมื่อครบเวลา',
                    'หากจำรหัสผ่านไม่ได้ สามารถใช้เมนู "ลืมรหัสผ่าน" เพื่อตั้งรหัสใหม่',
                ],
            ];
            if ($request->expectsJson()) {
                return response()->json(['alert' => $alert, 'email' => $request->email]);
            }
            return back()->with('login_alert', $alert)->withInput($request->only('email'));
        }

        // Single lookup, reused for both the existence check and the password
        // check (avoids Auth::attempt() re-querying the same row a second time).
        $user = User::where('email', $request->email)->first();

        // One bcrypt check for both cases so response time stays the same
        // whether or not the email exists.
        $passwordOk = Hash::check($request->password, $user ? $user->password : self::DUMMY_HASH);
        if (!$user || !$passwordOk) {
            \Illuminate\Support\Facades\RateLimiter::hit($throttleKey, self::LOGIN_LOCK_SECONDS);
            \Illuminate\Support\Facades\RateLimiter::hit($ipKey, self::LOGIN_LOCK_SECONDS);
            if (!$user) {
                $this->logAfterResponse('login_failed', 'เข้าสู่ระบบไม่สำเร็จ: ไม่พบอีเมลนี้ในระบบ', null, ['reason' => 'unknown_email'], $request, strtolower($request->email));
            } else {
                $this->logAfterResponse('login_failed', 'เข้าสู่ระบบไม่สำเร็จ: รหัสผ่านไม่ถูกต้อง', $user, ['reason' => 'wrong_password'], $request);
            }
            // Unknown email gets the combined message; a known email with a
            // wrong password is told the password is wrong (product owner's
            // decision). Attempt throttling above limits probing speed.
            $remaining = \Illuminate\Support\Facades\RateLimiter::remaining($throttleKey, self::LOGIN_MAX_ATTEMPTS);
            $lockMinutes = (int) (self::LOGIN_LOCK_SECONDS / 60);
            if (!$user) {
                $alert = [
                    'type' => 'error',
                    'title' => 'ไม่สามารถเข้าสู่ระบบได้',
                    'message' => 'อีเมลหรือรหัสผ่านไม่ถูกต้อง',
                    'tips' => [
                        'ตรวจสอบการสะกดอีเมล และไม่มีช่องว่างเกินท้ายข้อความ',
                        'รหัสผ่านแยกตัวพิมพ์เล็ก-ใหญ่ ตรวจสอบว่าไม่ได้เปิด Caps Lock',
                        'หากยังไม่เคยสมัคร ให้เลือกแท็บ "สมัครสมาชิก" หรือใช้เมนู "ลืมรหัสผ่าน"',
                    ],
                ];
            } else {
                $alert = [
                    'type' => 'error',
                    'title' => 'รหัสผ่านไม่ถูกต้อง',
                    'message' => '',
                    'tips' => [
                        'รหัสผ่านแยกตัวพิมพ์เล็ก-ใหญ่ ตรวจสอบว่าไม่ได้เปิด Caps Lock',
                        'หากจำรหัสผ่านไม่ได้ ให้ใช้เมนู "ลืมรหัสผ่าน" เพื่อตั้งรหัสใหม่',
                    ],
                ];
            }
            if ($remaining <= 3) {
                $alert['tips'][] = $remaining > 0
                    ? 'เหลือโอกาสอีก ' . $remaining . ' ครั้ง ก่อนระบบล็อกการเข้าสู่ระบบชั่วคราว ' . $lockMinutes . ' นาที'
                    : 'ระบบจะล็อกการเข้าสู่ระบบชั่วคราวในความพยายามครั้งถัดไป';
            }
            if ($request->expectsJson()) {
                return response()->json(['alert' => $alert, 'email' => $request->email]);
            }
            return back()->with('login_alert', $alert)->withInput($request->only('email'));
        }

        // Upgrade the stored hash when the configured cost has changed.
        if (Hash::needsRehash($user->password)) {
            $user->forceFill(['password' => Hash::make($request->password)])->save();
        }
        \Illuminate\Support\Facades\RateLimiter::clear($throttleKey);

        // Bypass approval check for Level 1 users
        if ($user->User_rank_id != 1 && !$user->is_approved) {
            $this->logAfterResponse('login_failed', 'เข้าสู่ระบบไม่สำเร็จ: บัญชียังรอการอนุมัติ', $user, ['reason' => 'pending_approval'], $request);
            $pendingAlert = [
                'type' => 'warning',
                'title' => 'บัญชีอยู่ระหว่างรอการอนุมัติ',
                'message' => 'บัญชีของท่านอยู่ระหว่างรอการอนุมัติจากผู้ดูแลระบบ จึงยังไม่สามารถเข้าใช้งานได้',
                'tips' => [
                    'ผู้ดูแลระบบจะตรวจสอบข้อมูลการสมัครและอนุมัติสิทธิ์ให้ตามลำดับ',
                    'หากรอนานเกินกำหนด กรุณาติดต่อผู้ดูแลระบบของหน่วยงาน',
                ],
            ];
            if ($request->expectsJson()) {
                return response()->json(['alert' => $pendingAlert, 'email' => $request->email]);
            }
            return back()->with('login_alert', $pendingAlert)->withInput($request->only('email'));
        }

        // Credentials and approval check passed, but the account is not
        // considered logged in yet - a second factor (6-digit OTP emailed
        // to the user) has to be confirmed first in verifyLoginOtp()
        // below. Regenerating the session id now, before anything
        // 2FA-related is written into it, still protects against session
        // fixation the same way the old immediate-login code did.
        $request->session()->regenerate();

        // Level-1-only switch (admin/system-management) - lets an admin
        // turn the login OTP step off entirely, e.g. while diagnosing a
        // mail-server problem. Defaults to true (today's behavior)
        // whenever the setting has never been touched.
        if (!\App\Support\SystemSettings::get('login_otp_enabled', true)) {
            // The session id was already regenerated above, before any
            // login state was written - regenerating it a second time only
            // costs another session-file rewrite.
            Auth::login($user, $request->boolean('remember'));
            if ($request->expectsJson()) {
                return response()->json(['redirect' => redirect()->intended('admin')->getTargetUrl()]);
            }
            return redirect()->intended('admin');
        }

        $this->issueLoginOtp($user);

        // Only the pending user id (and the "remember me" choice, so
        // it isn't lost across the OTP detour) lives in the session
        // during this window - Auth::check() is still false, so every
        // existing 'auth' middleware route stays protected until
        // verifyLoginOtp() succeeds below.
        $request->session()->put('login_2fa_user_id', $user->id);
        $request->session()->put('login_2fa_remember', $request->boolean('remember'));

        if ($request->expectsJson()) {
            return response()->json(['redirect' => route('login.otp.form')]);
        }
        return redirect()->route('login.otp.form');
    }

    /**
     * Step 2 of login: show the "enter OTP" page for the user who just
     * passed the password check in login() above. Mirrors
     * showOtpForm()/verifyOtp() for the forgot-password flow, but keyed on
     * the pending user id (not an email the browser could resubmit) and
     * stored in the cache (not a DB table - CACHE_DRIVER=file here, so
     * this needs no migration) under its own key namespace, so a
     * concurrent forgot-password request for the same email can never
     * clash with an in-progress login OTP.
     */
    public function showLoginOtpForm(Request $request)
    {
        $userId = $request->session()->get('login_2fa_user_id');
        if (!$userId) {
            return redirect()->route('staff')->with('error', 'กรุณาเข้าสู่ระบบก่อนครับ');
        }

        $user = User::find($userId);
        if (!$user) {
            $request->session()->forget('login_2fa_user_id');
            return redirect()->route('staff')->with('error', 'ไม่พบบัญชีผู้ใช้นี้ กรุณาเข้าสู่ระบบใหม่อีกครั้ง');
        }

        $entry = \Illuminate\Support\Facades\Cache::get('login_otp_' . $user->id);
        $remainingSeconds = self::LOGIN_OTP_TTL_SECONDS;
        if ($entry) {
            $elapsed = Carbon::now()->timestamp - $entry['created_at'];
            $remainingSeconds = max(0, self::LOGIN_OTP_TTL_SECONDS - $elapsed);
        }

        return view('auth.login.otp', ['email' => $user->email, 'remainingSeconds' => (int) $remainingSeconds]);
    }

    /**
     * Step 2 -> logged in: verify the 6-digit code against the hashed
     * value cached for the pending user, within the 5-minute window and a
     * 5-attempt budget (each wrong guess increments `attempts`; hitting
     * the cap discards the code and sends the admin back to the login
     * form, the same as letting it expire). Only on success is
     * Auth::login() finally called.
     */
    public function verifyLoginOtp(Request $request)
    {
        $request->validate([
            'otp' => 'required|digits:6',
        ], [
            'otp.required' => 'กรุณากรอกรหัส OTP',
            'otp.digits' => 'รหัส OTP ต้องเป็นตัวเลข 6 หลัก',
        ]);

        $userId = $request->session()->get('login_2fa_user_id');
        if (!$userId) {
            return redirect()->route('staff')->with('error', 'เซสชันหมดอายุ กรุณาเข้าสู่ระบบใหม่อีกครั้ง');
        }

        $user = User::find($userId);
        if (!$user) {
            $request->session()->forget('login_2fa_user_id');
            return redirect()->route('staff')->with('error', 'ไม่พบบัญชีผู้ใช้นี้ กรุณาเข้าสู่ระบบใหม่อีกครั้ง');
        }

        $cacheKey = 'login_otp_' . $user->id;
        $entry = \Illuminate\Support\Facades\Cache::get($cacheKey);
        if (!$entry) {
            return redirect()->route('staff')->with('error', 'ไม่พบคำขอยืนยันตัวตน กรุณาเข้าสู่ระบบใหม่อีกครั้ง');
        }

        $elapsed = Carbon::now()->timestamp - $entry['created_at'];
        if ($elapsed > self::LOGIN_OTP_TTL_SECONDS) {
            \Illuminate\Support\Facades\Cache::forget($cacheKey);
            $request->session()->forget('login_2fa_user_id');
            \App\Support\ActivityLog::record('otp_failed', 'OTP เข้าสู่ระบบหมดอายุ', $user, ['reason' => 'expired'], $request);
            return redirect()->route('staff')->with('error', 'รหัส OTP หมดอายุแล้ว กรุณาเข้าสู่ระบบใหม่อีกครั้ง');
        }

        if (!$this->otpMatches($request->otp, $entry['hash'])) {
            $attempts = $entry['attempts'] + 1;
            if ($attempts >= 5) {
                \Illuminate\Support\Facades\Cache::forget($cacheKey);
                $request->session()->forget('login_2fa_user_id');
                \App\Support\ActivityLog::record('otp_failed', 'กรอก OTP เข้าสู่ระบบผิดครบ 5 ครั้ง (ยกเลิกรหัส)', $user, ['reason' => 'locked'], $request);
                return redirect()->route('staff')->with('error', 'กรอกรหัส OTP ผิดเกินจำนวนครั้งที่กำหนด กรุณาเข้าสู่ระบบใหม่อีกครั้ง');
            }
            $entry['attempts'] = $attempts;
            \Illuminate\Support\Facades\Cache::put($cacheKey, $entry, now()->addMinutes(10));
            \App\Support\ActivityLog::record('otp_failed', 'กรอก OTP เข้าสู่ระบบไม่ถูกต้อง (ครั้งที่ ' . $attempts . ')', $user, ['reason' => 'wrong_otp'], $request);
            return back()->with('error', 'รหัส OTP ไม่ถูกต้อง กรุณาลองใหม่อีกครั้ง');
        }

        \Illuminate\Support\Facades\Cache::forget($cacheKey);
        $remember = (bool) $request->session()->get('login_2fa_remember', false);
        $request->session()->forget('login_2fa_user_id');
        $request->session()->forget('login_2fa_remember');

        Auth::login($user, $remember);
        $request->session()->regenerate();

        return redirect()->intended('admin');
    }

    /**
     * Resend action from the login-OTP page - reuses issueLoginOtp() so
     * "ส่งรหัสใหม่อีกครั้ง" doesn't require retyping the password.
     */
    public function resendLoginOtp(Request $request)
    {
        $userId = $request->session()->get('login_2fa_user_id');
        if (!$userId) {
            return redirect()->route('staff')->with('error', 'กรุณาเข้าสู่ระบบก่อนครับ');
        }

        $user = User::find($userId);
        if (!$user) {
            $request->session()->forget('login_2fa_user_id');
            return redirect()->route('staff')->with('error', 'ไม่พบบัญชีผู้ใช้นี้ กรุณาเข้าสู่ระบบใหม่อีกครั้ง');
        }

        $this->issueLoginOtp($user);

        return redirect()->route('login.otp.form')->with('success', 'เราได้ส่งรหัส OTP ไปที่อีเมลของคุณอีกครั้งแล้ว');
    }

    /**
     * Generates a fresh 6-digit code, (re)writes it into the cache for
     * this user, and emails it. Shared by login() (first send) and
     * resendLoginOtp() (every resend), so the two paths can never drift.
     * The code is cached before the mail goes out (see
     * sendLoginOtpAfterResponse()), so a retry from the OTP page's resend
     * button works without the admin re-entering their password.
     */
    private function issueLoginOtp(User $user): void
    {
        $otp = str_pad((string) random_int(0, 999999), 6, '0', STR_PAD_LEFT);

        \Illuminate\Support\Facades\Cache::put(
            'login_otp_' . $user->id,
            [
                'hash' => $this->otpDigest($otp),
                'attempts' => 0,
                'created_at' => Carbon::now()->timestamp,
            ],
            now()->addMinutes(10)
        );

        $this->sendLoginOtpAfterResponse($user->email, $otp);
    }

    /**
     * Keyed digest of a 6-digit code. The code lives at most 5 minutes and
     * dies after 5 wrong guesses, so a slow password hash (bcrypt, ~0.25 s
     * for make() and again for check()) buys nothing here - while keying
     * the digest with the app key still keeps the code unrecoverable from a
     * leaked cache file.
     */
    private function otpDigest(string $otp): string
    {
        return hash_hmac('sha256', $otp, (string) config('app.key'));
    }

    /**
     * Accepts the keyed digest, and the bcrypt hash that codes issued by the
     * previous version carry (they expire within minutes of deploy).
     */
    private function otpMatches(string $otp, string $stored): bool
    {
        if (str_starts_with($stored, '$2y$')) {
            return Hash::check($otp, $stored);
        }

        return hash_equals($stored, $this->otpDigest($otp));
    }


    /**
     * Defer an ActivityLog::record() call to after the HTTP response has been sent,
     * so audit writes never add latency to the login response.
     */
    private function logAfterResponse(string $event, ?string $description, $user, array $meta, $request, ?string $actorName = null): void
    {
        // Capture all values now (closures are lazy, so pass by value)
        $reqCapture  = $request;
        $userCapture = $user;
        app()->terminating(static function () use ($event, $description, $userCapture, $meta, $reqCapture, $actorName) {
            \App\Support\ActivityLog::record($event, $description, $userCapture, $meta, $reqCapture, $actorName);
        });
    }

    /**
     * Mails the code once the redirect to the OTP page has been sent, so the
     * SMTP handshake (typically 1-4 s) no longer delays the login response.
     * A failed send is logged; the OTP page's "ส่งรหัสใหม่อีกครั้ง" button is
     * the retry path.
     */
    private function sendLoginOtpAfterResponse(string $email, string $otp): void
    {
        app()->terminating(function () use ($email, $otp) {
            try {
                \Illuminate\Support\Facades\Mail::send('auth.emails.login-otp-code', ['otp' => $otp, 'email' => $email], function ($message) use ($email) {
                    $message->to($email);
                    $message->subject('รหัส OTP สำหรับเข้าสู่ระบบ - Salt & Sodium Smart Monitor');
                });
            } catch (\Throwable $e) {
                report($e);
            }
        });
    }

    public function logout(Request $request)
    {
        Auth::logout();
        $request->session()->invalidate();
        $request->session()->regenerateToken();
        return redirect()->route('staff');
    }
}
