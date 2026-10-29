@extends('layouts.layout')

@section('title', 'ยืนยันตัวตน - Salt & Sodium Smart Monitor')

@section('extra_css')
<style>
    .content-header { display: none !important; }

    .auth-container {
        display: flex;
        justify-content: center;
        align-items: center;
        padding: 26px 20px;
        background: #fbfbfd;
        min-height: calc(100vh - 60px);
        --pink-gradient-btn: linear-gradient(110deg, #e11d48 0%, #be185d 55%, #db2777 100%);
        --pink-gradient: linear-gradient(135deg, #f43f5e 0%, #ec4899 100%);
        --primary-color: #e11d48;
    }

    .auth-card {
        display: flex;
        width: 100%;
        max-width: 860px;
        border-radius: 32px;
        box-shadow: 0 30px 70px rgba(0,0,0,0.13);
        overflow: hidden;
    }

    /* ── Left branding panel ─────────────────────────── */
    .auth-side-panel {
        flex: 0 0 40%;
        position: relative;
        background:
            radial-gradient(ellipse at 30% 70%, #6b0f2e 0%, transparent 55%),
            linear-gradient(148deg, #1e0210 0%, #49082b 52%, #820d3a 100%);
        display: flex;
        flex-direction: column;
        justify-content: space-between;
        padding: 36px 28px 0;
        overflow: hidden;
        animation: panelFromLeft 0.65s cubic-bezier(0.23,1,0.32,1) both;
    }

    .blob { position: absolute; border-radius: 50%; background: rgba(255,255,255,0.12); pointer-events: none; }
    .blob-1 { width: 200px; height: 200px; top: -60px; right: -70px; }
    .blob-2 { width: 130px; height: 130px; bottom: 80px; left: -50px; }
    .blob-3 { width: 80px; height: 80px; top: 48%; right: -25px; opacity: .55; }

    .brand-logo-wrap { position: relative; display: flex; align-items: center; gap: 10px; }
    .brand-logo-wrap img { width: 36px; height: 36px; background: rgba(255,255,255,0.16); border-radius: 10px; padding: 4px; object-fit: contain; }
    .brand-logo-wrap span { font-family: 'Sarabun', sans-serif; font-size: 0.78rem; font-weight: 700; color: rgba(255,255,255,0.9); line-height: 1.3; }

    .hero-block { position: relative; margin-top: 28px; flex: 1; }
    .hero-block h1 { font-size: 2.2rem; font-weight: 800; line-height: 1.1; letter-spacing: -0.01em; margin: 0 0 14px; color: #fff; text-shadow: 0 2px 12px rgba(30,2,16,.5); }
    .hero-block h1 span { display: block; }
    .hero-block h1 .accent { background: linear-gradient(88deg, #fda4af, #f472b6 55%, #fbcfe8); -webkit-background-clip: text; background-clip: text; -webkit-text-fill-color: transparent; color: #fda4af; }
    .hero-block p { font-family: 'Sarabun', sans-serif; font-size: 0.8rem; color: rgba(255,255,255,0.72); line-height: 1.6; max-width: 200px; }

    .feature-list { position: relative; display: flex; flex-direction: column; gap: 12px; margin-top: 22px; }
    .feature-item { display: flex; align-items: center; gap: 12px; font-family: 'Sarabun', sans-serif; font-size: 0.8rem; color: rgba(255,255,255,0.85); font-weight: 600; }
    .feature-icon { width: 32px; height: 32px; background: rgba(255,255,255,0.13); border-radius: 9px; flex-shrink: 0; display: flex; align-items: center; justify-content: center; font-size: 0.82rem; color: #fda4af; }

    .panel-wave { display: block; position: relative; margin-top: 28px; width: calc(100% + 2px); margin-left: -1px; }

    /* ── Right form panel ────────────────────────────── */
    .auth-form-panel { flex: 1; background: #fff; display: flex; flex-direction: column; padding: 32px 34px 28px; animation: panelFromRight 0.65s cubic-bezier(0.23,1,0.32,1) 0.1s both; }

    .auth-byline { font-size: 0.73rem; color: #c4a4b2; font-weight: 700; letter-spacing: 0.04em; margin-bottom: 16px; animation: fadeUp 0.5s ease 0.25s both; }

    .auth-steps { display: flex; align-items: center; gap: 6px; margin-bottom: 18px; animation: fadeUp 0.5s ease 0.3s both; }
    .auth-step { width: 24px; height: 24px; border-radius: 50%; display: flex; align-items: center; justify-content: center; font-size: 0.72rem; font-weight: 800; background: #f1e4ea; color: #b0748a; flex-shrink: 0; }
    .auth-step.is-done { background: var(--primary-color); color: #fff; box-shadow: 0 0 0 4px rgba(225,29,72,.15); }
    .auth-step.is-active { background: var(--primary-color); color: #fff; box-shadow: 0 0 0 4px rgba(225,29,72,.15); }
    .auth-step-line { width: 28px; height: 2px; background: #ede4ea; flex-shrink: 0; }
    .auth-step-line.is-done { background: var(--primary-color); }

    .auth-form-heading { margin-bottom: 16px; animation: fadeUp 0.5s ease 0.35s both; }
    .auth-form-heading h2 { font-size: 1.65rem; font-weight: 800; line-height: 1.15; letter-spacing: -.01em; color: #1e1b2e; margin-bottom: 3px; }
    .auth-form-heading p { font-family: 'Sarabun', sans-serif; font-size: 0.8rem; color: #7c6c78; }

    /* Email chip — blue tint */
    .email-chip { display: flex; align-items: center; justify-content: flex-start; gap: 10px; margin: 0 0 12px; padding: 10px 18px; background: #eff6ff; border: 1.5px solid #bfdbfe; border-radius: 14px; color: #1e293b; font-weight: 700; font-family: 'Sarabun', sans-serif; font-size: 0.88rem; max-width: 100%; overflow: hidden; animation: fadeUp 0.45s ease 0.4s both; box-shadow: 0 2px 8px rgba(59,130,246,0.08); }
    .email-chip-icon { width: 28px; height: 28px; border-radius: 8px; background: linear-gradient(135deg,#dbeafe,#93c5fd); display: flex; align-items: center; justify-content: center; flex-shrink: 0; }
    .email-chip-icon i { font-size: 0.72rem; color: #2563eb; }
    .email-chip span { overflow: hidden; text-overflow: ellipsis; white-space: nowrap; }

    /* Timer pill */
    .otp-timer-pill { display: flex; align-items: center; gap: 10px; margin: 0 0 14px; padding: 10px 16px; border-radius: 14px; background: #fff1f5; color: var(--primary-color); font-weight: 700; width: 100%; transition: background-color .3s ease, color .3s ease; animation: fadeUp 0.45s ease 0.45s both; border: 1.5px solid #fecdd3; box-shadow: 0 2px 8px rgba(225,29,72,0.06); }
    .otp-timer-icon { width: 28px; height: 28px; border-radius: 8px; background: linear-gradient(135deg,#ffe4e6,#fda4af); display: flex; align-items: center; justify-content: center; flex-shrink: 0; }
    .otp-timer-icon i { font-size: 0.72rem; color: #e11d48; }
    .otp-timer-value { font-family: 'Sarabun', sans-serif; font-size: 1.05rem; font-weight: 800; font-variant-numeric: tabular-nums; letter-spacing: .5px; }
    .otp-timer-label { font-family: 'Sarabun', sans-serif; font-size: 0.78rem; font-weight: 600; opacity: .85; margin-left: 4px; }
    .otp-timer-pill.expired { background: #fef2f2; color: #ef4444; }

    /* OTP inputs — centered */
    .otp-input-group { display: flex; justify-content: center; gap: 8px; margin-bottom: 16px; animation: fadeUp 0.45s ease 0.5s both; }
    .otp-input-group input { width: 44px; height: 52px; text-align: center; font-size: 1.35rem; font-weight: 800; border-radius: 14px; border: 2px solid #e2e8f0; background: #f8fafc; font-family: 'Sarabun', sans-serif; color: #1e293b; outline: none; transition: border-color .2s ease, background .2s ease, box-shadow .2s ease, transform .15s ease, color .2s ease; }
    .otp-input-group input:focus { border-color: var(--primary-color); background: #fff; box-shadow: 0 0 0 4px rgba(225,29,72,.12); }
    .otp-input-group input.filled { border-color: var(--primary-color); background: #fff1f5; color: var(--primary-color); }
    .otp-input-group input.complete { border-color: #10b981; background: #f0fdf4; color: #059669; }
    @keyframes digitPop { 0% { transform: scale(1); } 40% { transform: scale(1.18); } 100% { transform: scale(1); } }
    @keyframes digitComplete { 0% { transform: scale(1); } 30% { transform: scale(1.2); } 100% { transform: scale(1); } }

    /* Submit button — full-width, rounded-14px */
    .btn-auth { width: 100%; padding: 13px; border-radius: 14px; border: none; background: linear-gradient(110deg,#e11d48 0%,#be185d 55%,#db2777 100%); color: #fff; font-weight: 800; font-family: 'Sarabun', 'TH Sarabun New', sans-serif; font-size: 0.92rem; cursor: pointer; transition: transform .25s cubic-bezier(.23,1,.32,1), box-shadow .25s ease; box-shadow: 0 6px 20px rgba(225,29,72,.38), 0 2px 6px rgba(225,29,72,.2); display: flex; align-items: center; justify-content: center; gap: 10px; position: relative; overflow: hidden; animation: fadeUp 0.45s ease 0.55s both; }
    .btn-auth:hover { transform: translateY(-2px); box-shadow: 0 12px 28px rgba(225,29,72,.45), 0 4px 10px rgba(225,29,72,.22); }
    .btn-auth .btn-fill { position: absolute; inset: 0; background: #fff; transform: scaleX(0); transform-origin: left center; transition: transform 0.55s cubic-bezier(0.65,0,0.35,1); pointer-events: none; }
    .btn-auth .btn-icon, .btn-auth .btn-label { position: relative; transition: color 0.35s ease 0.15s; }
    .btn-auth.is-loading { cursor: wait; transform: none; pointer-events: none; box-shadow: 0 8px 20px rgba(225,29,72,.3); }
    .btn-auth.is-loading .btn-fill { transform: scaleX(1); }
    .btn-auth.is-loading .btn-icon, .btn-auth.is-loading .btn-label { color: #e11d48; }
    .btn-auth .btn-icon.is-spinner { display: inline-block; flex: 0 0 auto; width: 1.1em; height: 1.1em; box-sizing: border-box; border: 2.5px solid rgba(225,29,72,.22); border-top-color: #e11d48; border-radius: 50%; animation: btnSpin 0.75s linear infinite; }
    .btn-auth .btn-icon.is-spinner::before { content: none; }
    @keyframes btnSpin { to { transform: rotate(360deg); } }
    .btn-auth:disabled { opacity: .5; cursor: not-allowed; transform: none; box-shadow: none; }
    .btn-spark { position: absolute; top: 50%; left: 4%; width: 11px; height: 11px; margin-top: -5.5px; border-radius: 50%; background: radial-gradient(circle, #fff 0%, #ffd1e3 45%, rgba(255,209,227,0) 75%); box-shadow: 0 0 9px 3px rgba(255,255,255,.5), 0 0 16px 5px rgba(225,29,72,.45); opacity: 0; pointer-events: none; }
    .btn-auth.is-loading .btn-spark { animation: sparkRun 1.3s ease-in-out infinite; }
    @keyframes sparkRun { 0% { left: 4%; opacity: 0; } 12% { opacity: 1; } 88% { opacity: 1; } 100% { left: 94%; opacity: 0; } }
    .btn-auth .ripple { position: absolute; border-radius: 50%; background: rgba(255,255,255,.5); transform: scale(0); animation: rippleOut .6s ease-out; pointer-events: none; }
    @keyframes rippleOut { to { transform: scale(2.5); opacity: 0; } }

    /* Alerts */
    .modern-alert { padding: 10px 16px; border-radius: 14px; margin-bottom: 12px; font-size: 0.83rem; display: flex; align-items: center; gap: 10px; }
    .modern-alert-success { background: #f0fdf4; color: #166534; border: 1px solid #bbf7d0; }
    .modern-alert-error { background: #fef2f2; color: #991b1b; border: 1px solid #fecaca; }

    /* Divider + secondary button */
    .auth-divider { display: flex; align-items: center; margin: 20px 0 16px; color: #b0a0a8; font-size: 0.76rem; font-weight: 600; animation: fadeUp 0.45s ease 0.6s both; }
    .auth-divider::before, .auth-divider::after { content: ''; flex: 1; border-bottom: 1px solid #ede4ea; }
    .auth-divider span { padding: 0 12px; white-space: nowrap; }

    .btn-resend { width: 100%; padding: 13px; border-radius: 14px; border: 1.5px solid #ede4ea; background: #fff; color: #1e1b2e; font-weight: 700; font-size: 0.85rem; cursor: pointer; font-family: 'Sarabun', sans-serif; display: flex; align-items: center; justify-content: center; gap: 10px; transition: all .22s ease; animation: fadeUp 0.45s ease 0.65s both; }
    .btn-resend i { color: var(--primary-color); }
    .btn-resend:hover { background: #fff7fa; border-color: rgba(225,29,72,.4); box-shadow: 0 6px 18px rgba(225,29,72,.1); }
    .btn-resend:disabled { opacity: .5; cursor: not-allowed; }

    .auth-back-link { text-align: center; margin-top: 14px; font-size: 0.83rem; animation: fadeUp 0.45s ease 0.7s both; }
    .auth-back-link a { color: var(--primary-color); text-decoration: none; font-weight: 700; font-family: 'Sarabun', sans-serif; }
    .auth-back-link a:hover { text-decoration: underline; }

    /* Animations */
    @keyframes panelFromLeft { from { opacity: 0; transform: translateX(-28px); } to { opacity: 1; transform: translateX(0); } }
    @keyframes panelFromRight { from { opacity: 0; transform: translateX(20px); } to { opacity: 1; transform: translateX(0); } }
    @keyframes fadeUp { from { opacity: 0; transform: translateY(14px); } to { opacity: 1; transform: translateY(0); } }

    @media (prefers-reduced-motion: reduce) {
        .auth-side-panel, .auth-form-panel, .auth-byline, .auth-steps, .auth-form-heading,
        .email-chip, .otp-timer-pill, .otp-input-group, .btn-auth, .auth-divider, .btn-resend, .auth-back-link { animation: none; }
        .btn-auth .btn-fill { transition: none; }
        .btn-auth .btn-icon.is-spinner { animation-duration: 1.8s; }
        .btn-auth.is-loading .btn-spark { animation: none; }
    }

    @media (max-width: 700px) {
        .auth-card { flex-direction: column; border-radius: 20px; max-width: 460px; }
        .auth-side-panel { flex: none; padding: 24px 24px 0; }
        .hero-block { margin-top: 12px; }
        .hero-block h1 { font-size: 1.05rem; margin-bottom: 0; }
        .hero-block h1 span { display: inline; }
        .feature-list { flex-direction: row; flex-wrap: wrap; gap: 8px; margin-top: 12px; }
        .panel-wave { display: none; }
        .auth-form-panel { padding: 22px 24px 24px; }
    }
    @media (max-width: 420px) {
        .auth-container { padding: 12px; }
        .auth-card { border-radius: 16px; }
        .otp-input-group input { width: 40px; height: 48px; }
    }
</style>
@endsection

@section('content')
<div class="auth-container">
    <div class="auth-card">

        {{-- ── Left branding panel ──────────────────────────── --}}
        <div class="auth-side-panel">
            <div class="blob blob-1"></div>
            <div class="blob blob-2"></div>
            <div class="blob blob-3"></div>

            <div class="brand-logo-wrap">
                <img src="{{ asset('images/logo.png') }}" alt="SSS Logo">
                <span>Salt &amp; Sodium<br>Smart Monitor</span>
            </div>

            <div class="hero-block">
                <h1>
                    <span>ยืนยัน</span>
                    <span class="accent">ตัวตน</span>
                    <span>ของคุณ</span>
                </h1>
                <p>รหัส 6 หลักถูกส่งไปยังอีเมลของคุณแล้ว กรอกรหัสเพื่อเข้าสู่ระบบอย่างปลอดภัย</p>
                <div class="feature-list">
                    <div class="feature-item">
                        <div class="feature-icon"><i class="fas fa-shield-halved"></i></div>
                        <div>ปลอดภัย 2 ขั้นตอน</div>
                    </div>
                    <div class="feature-item">
                        <div class="feature-icon"><i class="fas fa-clock"></i></div>
                        <div>รหัสหมดอายุใน 5 นาที</div>
                    </div>
                    <div class="feature-item">
                        <div class="feature-icon"><i class="fas fa-envelope-circle-check"></i></div>
                        <div>ส่งทางอีเมล</div>
                    </div>
                </div>
            </div>

            <svg class="panel-wave" viewBox="0 0 344 80" fill="none" xmlns="http://www.w3.org/2000/svg" preserveAspectRatio="none">
                <path d="M0 40 Q86 10 172 40 Q258 70 344 40 L344 80 L0 80 Z" fill="rgba(255,255,255,0.07)"/>
                <path d="M0 55 Q86 25 172 55 Q258 85 344 55 L344 80 L0 80 Z" fill="rgba(255,255,255,0.05)"/>
            </svg>
        </div>

        {{-- ── Right form panel ────────────────────────────── --}}
        <div class="auth-form-panel">
            <div class="auth-byline">SALT &amp; SODIUM</div>

            <div class="auth-steps">
                <span class="auth-step is-done"><i class="fas fa-check"></i></span>
                <span class="auth-step-line is-done"></span>
                <span class="auth-step is-active">2</span>
                <span class="auth-step-line"></span>
                <span class="auth-step">3</span>
            </div>

            <div class="auth-form-heading">
                <h2>ยืนยันรหัส OTP</h2>
                <p>กรอกรหัส 6 หลักที่ส่งไปยังอีเมลของคุณ</p>
            </div>

            <div class="email-chip">
                <span class="email-chip-icon"><i class="fas fa-envelope"></i></span>
                <span>{{ $email }}</span>
            </div>

            <div class="otp-timer-pill" id="otpTimer">
                <span class="otp-timer-icon"><i class="fas fa-clock"></i></span>
                <span>
                    <span class="otp-timer-value" id="otpTimerValue">05:00</span>
                    <span class="otp-timer-label" id="otpTimerLabel">จนกว่ารหัสจะหมดอายุ</span>
                </span>
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
                    <span class="btn-spark"></span>
                    <i class="fas fa-shield-check btn-icon"></i>
                    <span class="btn-label">ยืนยันและเข้าสู่ระบบ</span>
                </button>
            </form>

            <div class="auth-divider"><span>หรือ</span></div>

            <form action="{{ route('login.otp.resend') }}" method="POST" id="resendForm">
                @csrf
                <button type="submit" class="btn-resend" id="resendBtn" disabled>
                    <i class="fas fa-paper-plane"></i>
                    <span>ยังไม่ได้รับรหัส? ส่งอีกครั้ง</span>
                </button>
            </form>

            <div class="auth-back-link">
                <a href="{{ route('staff') }}"><i class="fas fa-arrow-left"></i> ยกเลิกและกลับไปหน้าเข้าสู่ระบบ</a>
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

        function syncHidden() { hidden.value = digits.map(d => d.value).join(''); }

        function updateDigitState(input) {
            if (input.value) input.classList.add('filled');
            else input.classList.remove('filled', 'complete');
        }

        function triggerPop(input) {
            input.style.animation = 'none'; void input.offsetWidth;
            input.style.animation = 'digitPop 0.2s ease';
        }

        let autoSubmitTimer = null;
        function checkAutoSubmit() {
            const allFilled = digits.every(d => d.value.length === 1);
            if (!allFilled) return;
            digits.forEach(d => {
                d.classList.remove('filled'); d.classList.add('complete');
                d.style.animation = 'none'; void d.offsetWidth;
                d.style.animation = 'digitComplete 0.25s ease';
            });
            clearTimeout(autoSubmitTimer);
            autoSubmitTimer = setTimeout(() => { syncHidden(); form.requestSubmit(); }, 420);
        }

        digits.forEach((input, idx) => {
            input.addEventListener('input', () => {
                input.value = input.value.replace(/[^0-9]/g, '').slice(0, 1);
                updateDigitState(input);
                if (input.value) { triggerPop(input); if (idx < digits.length - 1) digits[idx + 1].focus(); }
                syncHidden(); checkAutoSubmit();
            });
            input.addEventListener('keydown', (e) => {
                if (e.key === 'Backspace') {
                    if (input.value) { input.value = ''; updateDigitState(input); syncHidden(); }
                    else if (idx > 0) { digits[idx - 1].focus(); }
                    clearTimeout(autoSubmitTimer);
                }
            });
            input.addEventListener('paste', (e) => {
                const text = (e.clipboardData || window.clipboardData).getData('text').replace(/[^0-9]/g, '');
                if (!text) return; e.preventDefault();
                text.slice(0, digits.length).split('').forEach((ch, i) => { if (digits[i]) { digits[i].value = ch; updateDigitState(digits[i]); } });
                const nextEmpty = digits.findIndex(d => !d.value);
                (nextEmpty === -1 ? digits[digits.length - 1] : digits[nextEmpty]).focus();
                syncHidden(); checkAutoSubmit();
            });
        });

        form.addEventListener('submit', () => {
            syncHidden();
            submitBtn.classList.add('is-loading');
            if (btnLabel) btnLabel.textContent = 'กำลังยืนยัน...';
            if (btnIcon)  btnIcon.className = 'btn-icon is-spinner';
        });

        submitBtn.addEventListener('click', function (e) {
            const r = document.createElement('span'); r.className = 'ripple';
            const rect = submitBtn.getBoundingClientRect();
            const size = Math.max(rect.width, rect.height);
            r.style.cssText = `width:${size}px;height:${size}px;left:${e.clientX-rect.left-size/2}px;top:${e.clientY-rect.top-size/2}px`;
            submitBtn.appendChild(r); r.addEventListener('animationend', () => r.remove());
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
                submitBtn.disabled = true; resendBtn.disabled = false;
            }
        }

        render();
        resendBtn.disabled = remaining > 0;

        const interval = setInterval(() => {
            remaining -= 1;
            if (remaining <= 0) { remaining = 0; render(); clearInterval(interval); return; }
            render();
        }, 1000);
    })();
</script>
@endsection
