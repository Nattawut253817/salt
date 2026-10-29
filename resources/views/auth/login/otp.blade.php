@extends('layouts.layout')

@section('title', 'ยืนยันตัวตน - Salt & Sodium Smart Monitor')

@section('extra_css')
<style>
    /* ========================================================
       OTP Verification — Two-panel Auth Page
       Rose/crimson theme · Sarabun/Kodchasan/Trirong fonts
       Matches staff.blade.php visual language
    ======================================================== */

    .content-header { display: none !important; }

    /* ── Page shell ─────────────────────────────────────── */
    .auth-container {
        position: relative;
        display: flex;
        justify-content: center;
        align-items: center;
        padding: 24px 20px;
        min-height: 88vh;
        --pink-gradient-btn: linear-gradient(110deg, #e11d48 0%, #be185d 55%, #db2777 100%);
        --pink-gradient: linear-gradient(135deg, #f43f5e 0%, #ec4899 100%);
        --primary-color: #e11d48;
    }

    .auth-card {
        position: relative;
        z-index: 1;
        display: flex;
        align-items: stretch;
        background: #ffffff;
        width: 100%;
        max-width: 1160px;
        min-height: 670px;
        border-radius: 32px;
        box-shadow:
            0 2px 4px rgba(225, 29, 72, 0.04),
            0 8px 24px rgba(15, 23, 42, 0.07),
            0 32px 72px rgba(15, 23, 42, 0.10);
        border: none;
        overflow: hidden;
        animation: cardRise 0.55s cubic-bezier(0.22, 1, 0.36, 1) both;
    }

    @keyframes cardRise {
        from { opacity: 0; transform: translateY(32px) scale(0.98); }
        to   { opacity: 1; transform: translateY(0)   scale(1); }
    }

    /* ── Left branding panel ────────────────────────────── */
    .auth-side-panel {
        position: relative;
        flex: 0 0 42%;
        padding: 48px 40px 44px;
        background:
            radial-gradient(ellipse 80% 55% at 95% 95%, rgba(244,63,94,.65), transparent 68%),
            radial-gradient(ellipse 65% 45% at 5%  5%,  rgba(136,19,55,.6),  transparent 62%),
            linear-gradient(148deg, #1e0210 0%, #49082b 52%, #820d3a 100%);
        color: #fff;
        display: flex;
        flex-direction: column;
        overflow: hidden;
        isolation: isolate;
        border-radius: 0 0 160px 0 / 0 0 180px 0;
    }

    .auth-side-art {
        position: absolute;
        inset: 0;
        z-index: 0;
        pointer-events: none;
    }
    .auth-side-art > svg {
        position: absolute;
        inset: 0;
        width: 100%;
        height: 100%;
    }
    .auth-side-art::after {
        content: '';
        position: absolute;
        inset: 0;
        background: linear-gradient(100deg, rgba(30,2,16,.55) 0%, rgba(30,2,16,.08) 55%, transparent 75%);
    }

    .art-blob { position: absolute; z-index: 1; }
    .art-blob-1 {
        width: 160px; height: 160px; border-radius: 50%;
        background: rgba(251,113,133,.22);
        left: 2%; top: 30%; filter: blur(32px);
    }
    .art-blob-2 {
        width: 52px; height: 52px; border-radius: 14px;
        background: rgba(255,255,255,.18);
        left: 28%; top: 28%; filter: blur(6px);
    }
    .art-blob-3 {
        width: 30px; height: 30px; border-radius: 9px;
        background: rgba(255,255,255,.14);
        left: 18%; top: 39%; filter: blur(4px);
    }

    .art-dots { position: absolute; background-image: radial-gradient(circle, rgba(255,255,255,.5) 1.4px, transparent 2px); background-size: 20px 20px; opacity: .75; z-index: 1; }
    .art-dots-1 { width: 112px; height: 72px; top: 17%; left: 38%; }
    .art-dots-2 { width: 136px; height: 64px; bottom: 11%; left: 44%; opacity: .5; }

    .auth-side-logo,
    .auth-side-hero,
    .auth-side-features { position: relative; z-index: 3; }

    .auth-side-logo {
        width: 100%; max-width: 210px;
        aspect-ratio: 2.5;
        background: #fffafc;
        border-radius: 16px; padding: 4px;
        display: flex; align-items: center; justify-content: center;
        overflow: hidden;
        box-shadow: 0 12px 28px rgba(0,0,0,.32), 0 2px 6px rgba(0,0,0,.1);
    }
    .auth-side-logo img { width: 100%; height: 100%; object-fit: cover; border-radius: 12px; }

    .auth-side-hero { margin-top: auto; }

    .auth-side-title {
        font-size: 2.35rem; font-weight: 800;
        line-height: 1.1; letter-spacing: -0.01em;
        margin: 0 0 14px;
        text-shadow: 0 2px 12px rgba(30,2,16,.5);
    }
    .auth-side-title span { display: block; }
    .auth-side-title .accent {
        background: linear-gradient(88deg, #fda4af, #f472b6 55%, #fbcfe8);
        -webkit-background-clip: text; background-clip: text;
        -webkit-text-fill-color: transparent; color: #fda4af;
    }

    .auth-side-subtitle {
        font-size: 0.88rem; font-weight: 500;
        line-height: 1.7; color: rgba(255,255,255,.78);
        max-width: 30ch;
    }

    .auth-side-features {
        margin-top: 36px; display: flex; flex-direction: row; gap: 10px;
    }
    .auth-side-feature {
        display: flex; flex-direction: column; align-items: center;
        text-align: center; gap: 8px;
        font-size: 0.72rem; font-weight: 600; line-height: 1.3;
        color: rgba(255,255,255,.88); flex: 1;
    }
    .auth-side-feature-icon {
        flex-shrink: 0; width: 40px; height: 40px;
        display: flex; align-items: center; justify-content: center;
        border-radius: 12px;
        background: rgba(255,255,255,.14);
        border: 1px solid rgba(255,255,255,.2);
        color: #fda4af; font-size: 0.9rem;
    }

    /* ── Right form panel ───────────────────────────────── */
    .auth-main-panel {
        flex: 1; min-width: 0;
        display: flex; flex-direction: column;
        justify-content: flex-start;
        position: relative;
        overflow-y: auto; overflow-x: hidden;
    }

    .auth-byline {
        position: absolute; top: 22px; right: 32px;
        font-size: 0.73rem; font-weight: 700;
        color: #c4a4b2; letter-spacing: .04em;
    }

    .auth-welcome { padding: 40px 50px 0; }

    .auth-welcome-label {
        font-size: 0.68rem; font-weight: 700;
        letter-spacing: .22em; color: #b0748a; margin-bottom: 4px;
    }

    .auth-welcome-title {
        font-size: 1.8rem; font-weight: 800;
        line-height: 1.15; color: #1e1b2e; letter-spacing: -.01em;
    }
    .auth-welcome-title em { font-style: normal; color: var(--primary-color); }

    .auth-welcome-sub {
        margin-top: 6px; font-size: 0.88rem; font-weight: 500;
        color: #6b7280; line-height: 1.55;
    }

    /* ── Form section ──────────────────────────────────── */
    .auth-form-section { padding: 28px 50px 40px; }

    /* Email chip */
    .email-chip {
        display: inline-flex; align-items: center; gap: 8px;
        padding: 7px 14px;
        background: #f8fafc; border: 1px solid #eef2f7;
        border-radius: 999px;
        color: #1e293b; font-weight: 700;
        font-size: 0.88rem; max-width: 100%;
        overflow: hidden; margin-bottom: 20px;
    }
    .email-chip i { color: var(--primary-color); flex-shrink: 0; }
    .email-chip span { overflow: hidden; text-overflow: ellipsis; white-space: nowrap; }

    /* Timer pill */
    .otp-timer-pill {
        display: inline-flex; align-items: center; gap: 8px;
        padding: 7px 16px; border-radius: 999px;
        background: #fff1f5; color: var(--primary-color);
        font-weight: 700; margin-bottom: 20px;
        transition: background-color 0.3s ease, color 0.3s ease;
    }
    .otp-timer-pill i { font-size: 0.85rem; }
    .otp-timer-value {
        font-size: 1.05rem; font-weight: 800;
        font-variant-numeric: tabular-nums; letter-spacing: 0.5px;
    }
    .otp-timer-label { font-size: 0.78rem; font-weight: 600; opacity: 0.85; }
    .otp-timer-pill.expired { background: #fef2f2; color: #ef4444; }

    /* Row: chip + timer side by side */
    .otp-meta-row {
        display: flex; flex-wrap: wrap; align-items: center;
        gap: 10px; margin-bottom: 4px;
    }
    .otp-meta-row .otp-timer-pill,
    .otp-meta-row .email-chip { margin-bottom: 0; }

    /* OTP input group */
    .otp-input-group {
        display: flex; gap: 10px;
        margin-bottom: 22px;
    }
    .otp-input-group input {
        width: 52px; height: 60px;
        text-align: center;
        font-size: 1.5rem; font-weight: 800;
        border-radius: 14px;
        border: 2px solid #e2e8f0;
        background: #f8fafc;
        color: #1e293b; outline: none;
        transition: border-color 0.2s ease, background-color 0.2s ease,
                    box-shadow 0.2s ease, transform 0.15s ease, color 0.2s ease;
    }
    .otp-input-group input:focus {
        border-color: var(--primary-color);
        background: white;
        box-shadow: 0 0 0 4px rgba(225, 29, 72, 0.12);
    }
    .otp-input-group input.filled {
        border-color: var(--primary-color); background: #fff1f5; color: var(--primary-color);
    }
    .otp-input-group input.complete {
        border-color: #10b981; background: #f0fdf4; color: #059669;
    }

    @keyframes digitPop {
        0%   { transform: scale(1); }
        40%  { transform: scale(1.18); }
        100% { transform: scale(1); }
    }
    @keyframes digitComplete {
        0%   { transform: scale(1); }
        30%  { transform: scale(1.2); }
        100% { transform: scale(1); }
    }

    /* Submit button */
    .btn-auth {
        padding: 13px 28px; border-radius: 50px; border: none;
        background: var(--pink-gradient-btn); color: white;
        font-weight: 800; font-size: 0.95rem;
        cursor: pointer; transition: all 0.4s;
        box-shadow: 0 10px 25px rgba(225, 29, 72, 0.3);
        display: inline-flex; align-items: center; gap: 10px;
        position: relative; overflow: hidden;
    }
    .btn-auth:hover { transform: translateY(-2px); box-shadow: 0 15px 35px rgba(225, 29, 72, 0.4); }
    .btn-auth:disabled { opacity: 0.5; cursor: not-allowed; transform: none; box-shadow: none; }

    .btn-auth .btn-fill {
        position: absolute; inset: 0; background: #ffffff;
        transform: scaleX(0); transform-origin: left center;
        transition: transform 0.55s cubic-bezier(0.65, 0, 0.35, 1);
        pointer-events: none;
    }
    .btn-auth .btn-icon, .btn-auth .btn-label { position: relative; transition: color 0.35s ease 0.15s; }
    .btn-auth.is-loading { cursor: wait; transform: none; pointer-events: none; box-shadow: 0 8px 20px rgba(225, 29, 72, 0.3); }
    .btn-auth.is-loading .btn-fill { transform: scaleX(1); }
    .btn-auth.is-loading .btn-icon,
    .btn-auth.is-loading .btn-label { color: #e11d48; }
    .btn-auth .btn-icon.is-spinner {
        display: inline-block; flex: 0 0 auto; width: 1.1em; height: 1.1em; box-sizing: border-box;
        border: 2.5px solid rgba(225, 29, 72, 0.22); border-top-color: #e11d48;
        border-radius: 50%; animation: btnSpin 0.75s linear infinite;
    }
    .btn-auth .btn-icon.is-spinner::before { content: none; }
    @keyframes btnSpin { to { transform: rotate(360deg); } }

    /* Alerts */
    .modern-alert {
        padding: 12px 18px; border-radius: 14px; margin-bottom: 16px;
        font-size: 0.85rem; display: flex; align-items: center; gap: 10px;
    }
    .modern-alert-success { background: #f0fdf4; color: #166534; border: 1px solid #bbf7d0; }
    .modern-alert-error   { background: #fef2f2; color: #991b1b; border: 1px solid #fecaca; }

    /* Footer links */
    .auth-footer {
        margin-top: 20px; display: flex; flex-direction: column; gap: 10px;
        padding-top: 16px; border-top: 1px solid #f1f5f9;
    }
    .auth-footer a,
    .resend-link {
        color: var(--primary-color); text-decoration: none;
        font-weight: 700; background: none; border: none;
        font-size: 0.86rem; cursor: pointer; padding: 0;
    }
    .resend-link:disabled { color: #cbd5e1; cursor: not-allowed; }

    /* Responsive */
    @media (max-width: 820px) {
        .auth-side-panel { display: none; }
        .auth-card { min-height: unset; }
        .auth-welcome { padding: 32px 28px 0; }
        .auth-form-section { padding: 20px 28px 32px; }
        .otp-input-group input { width: 42px; height: 50px; font-size: 1.3rem; }
    }

    @media (prefers-reduced-motion: reduce) {
        .btn-auth .btn-fill { transition: none; }
        .btn-auth .btn-icon.is-spinner { animation-duration: 1.8s; }
        .otp-input-group input { transition: none; }
    }
</style>
@endsection

@section('content')
<div class="auth-container">
    <div class="auth-card">

        {{-- ── Left branding panel ──────────────────────────── --}}
        <div class="auth-side-panel">
            <div class="auth-side-art">
                <div class="art-blob art-blob-1"></div>
                <div class="art-blob art-blob-2"></div>
                <div class="art-blob art-blob-3"></div>
                <div class="art-dots art-dots-1"></div>
                <div class="art-dots art-dots-2"></div>
            </div>

            <div class="auth-side-logo">
                <img src="{{ asset('images/logo.png') }}" alt="Salt &amp; Sodium Smart Monitor">
            </div>

            <div class="auth-side-hero">
                <h1 class="auth-side-title">
                    <span>ยืนยัน</span>
                    <span class="accent">ตัวตน</span>
                    <span>ของคุณ</span>
                </h1>
                <p class="auth-side-subtitle">
                    รหัส 6 หลักถูกส่งไปยังอีเมลของคุณแล้ว<br>
                    กรอกรหัสเพื่อเข้าสู่ระบบอย่างปลอดภัย
                </p>
            </div>

            <div class="auth-side-features">
                <div class="auth-side-feature">
                    <div class="auth-side-feature-icon"><i class="fas fa-shield-halved"></i></div>
                    ปลอดภัย<br>2 ขั้นตอน
                </div>
                <div class="auth-side-feature">
                    <div class="auth-side-feature-icon"><i class="fas fa-clock"></i></div>
                    รหัสหมด<br>อายุใน 5 นาที
                </div>
                <div class="auth-side-feature">
                    <div class="auth-side-feature-icon"><i class="fas fa-envelope-circle-check"></i></div>
                    ส่งทาง<br>อีเมล
                </div>
            </div>
        </div>

        {{-- ── Right form panel ────────────────────────────── --}}
        <div class="auth-main-panel">
            <span class="auth-byline">SALT &amp; SODIUM</span>

            <div class="auth-welcome">
                <p class="auth-welcome-label">STEP 2 OF 2</p>
                <h2 class="auth-welcome-title">ยืนยัน<em>รหัส OTP</em></h2>
                <p class="auth-welcome-sub">กรอกรหัส 6 หลักที่ส่งไปยังอีเมลของคุณ</p>
            </div>

            <div class="auth-form-section">

                {{-- Meta: email + timer --}}
                <div class="otp-meta-row">
                    <div class="email-chip">
                        <i class="fas fa-envelope"></i>
                        <span>{{ $email }}</span>
                    </div>
                    <div class="otp-timer-pill" id="otpTimer">
                        <i class="fas fa-clock"></i>
                        <span class="otp-timer-value" id="otpTimerValue">05:00</span>
                        <span class="otp-timer-label" id="otpTimerLabel">จนกว่ารหัสจะหมดอายุ</span>
                    </div>
                </div>

                {{-- Alerts --}}
                @if (session('success'))
                    <div class="modern-alert modern-alert-success">
                        <i class="fas fa-check-circle"></i> {{ session('success') }}
                    </div>
                @endif
                @if (session('error'))
                    <div class="modern-alert modern-alert-error">
                        <i class="fas fa-exclamation-circle"></i> {{ session('error') }}
                    </div>
                @endif
                @if ($errors->any())
                    <div class="modern-alert modern-alert-error" style="flex-direction: column; align-items: flex-start;">
                        @foreach ($errors->all() as $error)
                            <div><i class="fas fa-exclamation-circle"></i> {{ $error }}</div>
                        @endforeach
                    </div>
                @endif

                {{-- OTP form --}}
                <form action="{{ route('login.otp.verify') }}" method="POST" id="otpForm">
                    @csrf
                    <input type="hidden" name="otp" id="otpHidden">

                    <div class="otp-input-group">
                        @for ($i = 0; $i < 6; $i++)
                            <input type="text" inputmode="numeric" pattern="[0-9]*" maxlength="1"
                                   class="otp-digit" @if ($i === 0) autofocus @endif>
                        @endfor
                    </div>

                    <button type="submit" class="btn-auth" id="otpSubmitBtn">
                        <span class="btn-fill"></span>
                        <i class="fas fa-shield-check btn-icon"></i>
                        <span class="btn-label">ยืนยันและเข้าสู่ระบบ</span>
                    </button>
                </form>

                {{-- Footer --}}
                <div class="auth-footer">
                    <form action="{{ route('login.otp.resend') }}" method="POST" id="resendForm">
                        @csrf
                        <button type="submit" class="resend-link" id="resendBtn" disabled>
                            ยังไม่ได้รับรหัส? ส่งอีกครั้ง
                        </button>
                    </form>
                    <a href="{{ route('staff') }}"><i class="fas fa-arrow-left"></i> ยกเลิกและกลับไปหน้าเข้าสู่ระบบ</a>
                </div>

            </div>
        </div>

    </div>
</div>
@endsection

@section('extra_js')
<script>
    (function () {
        const digits     = Array.from(document.querySelectorAll('.otp-digit'));
        const hidden     = document.getElementById('otpHidden');
        const form       = document.getElementById('otpForm');
        const submitBtn  = document.getElementById('otpSubmitBtn');
        const resendBtn  = document.getElementById('resendBtn');
        const timerValue = document.getElementById('otpTimerValue');
        const timerLabel = document.getElementById('otpTimerLabel');
        const timerWrap  = document.getElementById('otpTimer');
        const btnIcon    = submitBtn.querySelector('.btn-icon');
        const btnLabel   = submitBtn.querySelector('.btn-label');
        const savedIconClass  = btnIcon  ? btnIcon.className  : '';
        const savedLabelText  = btnLabel ? btnLabel.textContent : '';

        function syncHidden() {
            hidden.value = digits.map(d => d.value).join('');
        }

        function updateDigitState(input) {
            if (input.value) { input.classList.add('filled'); }
            else { input.classList.remove('filled', 'complete'); }
        }

        function triggerPop(input) {
            input.style.animation = 'none';
            void input.offsetWidth;
            input.style.animation = 'digitPop 0.2s ease';
        }

        let autoSubmitTimer = null;
        function checkAutoSubmit() {
            const allFilled = digits.every(d => d.value.length === 1);
            if (!allFilled) return;
            digits.forEach(d => {
                d.classList.remove('filled');
                d.classList.add('complete');
                d.style.animation = 'none';
                void d.offsetWidth;
                d.style.animation = 'digitComplete 0.25s ease';
            });
            clearTimeout(autoSubmitTimer);
            autoSubmitTimer = setTimeout(() => {
                syncHidden();
                form.requestSubmit();
            }, 420);
        }

        digits.forEach((input, idx) => {
            input.addEventListener('input', () => {
                input.value = input.value.replace(/[^0-9]/g, '').slice(0, 1);
                updateDigitState(input);
                if (input.value) {
                    triggerPop(input);
                    if (idx < digits.length - 1) digits[idx + 1].focus();
                }
                syncHidden();
                checkAutoSubmit();
            });

            input.addEventListener('keydown', (e) => {
                if (e.key === 'Backspace') {
                    if (input.value) {
                        input.value = '';
                        updateDigitState(input);
                        syncHidden();
                    } else if (idx > 0) {
                        digits[idx - 1].focus();
                    }
                    clearTimeout(autoSubmitTimer);
                }
            });

            input.addEventListener('paste', (e) => {
                const text = (e.clipboardData || window.clipboardData)
                    .getData('text').replace(/[^0-9]/g, '');
                if (!text) return;
                e.preventDefault();
                text.slice(0, digits.length).split('').forEach((ch, i) => {
                    if (digits[i]) { digits[i].value = ch; updateDigitState(digits[i]); }
                });
                const nextEmpty = digits.findIndex(d => !d.value);
                (nextEmpty === -1 ? digits[digits.length - 1] : digits[nextEmpty]).focus();
                syncHidden();
                checkAutoSubmit();
            });
        });

        form.addEventListener('submit', () => {
            syncHidden();
            submitBtn.classList.add('is-loading');
            if (btnLabel) btnLabel.textContent = 'กำลังยืนยัน...';
            if (btnIcon)  btnIcon.className = 'btn-icon is-spinner';
        });

        window.addEventListener('pageshow', (e) => {
            if (!e.persisted) return;
            submitBtn.classList.remove('is-loading');
            if (btnIcon)  btnIcon.className  = savedIconClass;
            if (btnLabel) btnLabel.textContent = savedLabelText;
        });

        let remaining = {{ (int) $remainingSeconds }};

        function render() {
            const m = Math.floor(remaining / 60).toString().padStart(2, '0');
            const s = (remaining % 60).toString().padStart(2, '0');
            timerValue.textContent = `${m}:${s}`;

            if (remaining <= 0) {
                timerWrap.classList.add('expired');
                timerLabel.textContent = 'รหัสหมดอายุแล้ว';
                digits.forEach(d => d.disabled = true);
                submitBtn.disabled = true;
                resendBtn.disabled = false;
            }
        }

        render();
        resendBtn.disabled = remaining > 0;

        const interval = setInterval(() => {
            remaining -= 1;
            if (remaining <= 0) {
                remaining = 0;
                render();
                clearInterval(interval);
                return;
            }
            render();
        }, 1000);
    })();
</script>
@endsection
