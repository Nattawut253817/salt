@extends('layouts.layout')

@section('title', 'ยืนยันรหัส OTP - Salt & Sodium Smart Monitor')

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
    }

    .auth-card {
        display: flex;
        width: 100%;
        max-width: 860px;
        border-radius: 32px;
        box-shadow: 0 30px 70px rgba(0,0,0,0.13);
        overflow: hidden;
    }

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
    .brand-logo-wrap span { font-family: 'Kodchasan', sans-serif; font-size: 0.78rem; font-weight: 700; color: rgba(255,255,255,0.9); line-height: 1.3; }

    .hero-block { position: relative; margin-top: 28px; flex: 1; }
    .hero-block h1 { font-family: 'Kodchasan','Sarabun', sans-serif; font-size: 1.55rem; font-weight: 900; line-height: 1.3; color: #fff; margin-bottom: 10px; }
    .hero-block h1 span { color: #fda4af; }
    .hero-block p { font-family: 'Sarabun', sans-serif; font-size: 0.8rem; color: rgba(255,255,255,0.72); line-height: 1.6; max-width: 200px; }

    .feature-list { position: relative; display: flex; flex-direction: column; gap: 12px; margin-top: 22px; }
    .feature-item { display: flex; align-items: center; gap: 12px; font-family: 'Sarabun', sans-serif; font-size: 0.8rem; color: rgba(255,255,255,0.85); font-weight: 600; }
    .feature-icon { width: 32px; height: 32px; background: rgba(255,255,255,0.13); border-radius: 9px; flex-shrink: 0; display: flex; align-items: center; justify-content: center; font-size: 0.82rem; color: #fda4af; }

    .panel-wave { display: block; position: relative; margin-top: 28px; width: calc(100% + 2px); margin-left: -1px; }

    .auth-form-panel { flex: 1; background: #fff; display: flex; flex-direction: column; padding: 32px 34px 28px; animation: panelFromRight 0.65s cubic-bezier(0.23,1,0.32,1) 0.1s both; }

    .auth-byline { font-family: 'Trirong','Sarabun', sans-serif; font-size: 0.72rem; color: #b0748a; font-weight: 600; letter-spacing: 0.04em; margin-bottom: 16px; animation: fadeUp 0.5s ease 0.25s both; }

    .auth-steps { display: flex; align-items: center; gap: 6px; margin-bottom: 18px; animation: fadeUp 0.5s ease 0.3s both; }
    .auth-step { width: 22px; height: 22px; border-radius: 50%; display: flex; align-items: center; justify-content: center; font-size: 0.68rem; font-weight: 800; background: #f1f5f9; color: #94a3b8; flex-shrink: 0; }
    .auth-step.is-done { background: var(--primary-color); color: #fff; }
    .auth-step.is-active { background: #fff; color: var(--primary-color); border: 2px solid var(--primary-color); box-shadow: 0 0 0 3px rgba(225,29,72,0.15); }
    .auth-step-line { width: 24px; height: 2px; background: #e2e8f0; flex-shrink: 0; }
    .auth-step-line.is-done { background: var(--primary-color); }

    .auth-form-heading { margin-bottom: 16px; animation: fadeUp 0.5s ease 0.35s both; }
    .auth-form-heading h2 { font-family: 'Kodchasan','Sarabun', sans-serif; font-size: 1.18rem; font-weight: 800; color: #1e1b2e; margin-bottom: 3px; }
    .auth-form-heading p { font-family: 'Sarabun', sans-serif; font-size: 0.8rem; color: #7c6c78; }

    .email-chip { display: flex; align-items: center; justify-content: center; gap: 8px; margin: 0 auto 12px; padding: 8px 16px; background: #fdf9fb; border: 1.5px solid #ede4ea; border-radius: 14px; color: #1e293b; font-weight: 700; font-family: 'Sarabun', sans-serif; font-size: 0.88rem; max-width: 100%; overflow: hidden; animation: fadeUp 0.45s ease 0.4s both; }
    .email-chip i { color: var(--primary-color); flex-shrink: 0; }
    .email-chip span { overflow: hidden; text-overflow: ellipsis; white-space: nowrap; }

    .otp-timer-pill { display: flex; align-items: center; justify-content: center; gap: 8px; margin: 0 auto 14px; padding: 7px 16px; border-radius: 14px; background: #fff1f5; color: var(--primary-color); font-weight: 700; width: fit-content; transition: background-color .3s ease, color .3s ease; animation: fadeUp 0.45s ease 0.45s both; }
    .otp-timer-pill i { font-size: 0.85rem; }
    .otp-timer-value { font-family: 'Sarabun', sans-serif; font-size: 1.05rem; font-weight: 800; font-variant-numeric: tabular-nums; letter-spacing: .5px; }
    .otp-timer-label { font-family: 'Trirong','Sarabun', sans-serif; font-size: 0.78rem; font-weight: 600; opacity: .85; }
    .otp-timer-pill.expired { background: #fef2f2; color: #ef4444; }

    .otp-input-group { display: flex; justify-content: center; gap: 8px; margin-bottom: 16px; animation: fadeUp 0.45s ease 0.5s both; }
    .otp-input-group input { width: 44px; height: 52px; text-align: center; font-size: 1.35rem; font-weight: 800; border-radius: 14px; border: 2px solid #e2e8f0; background: #f8fafc; font-family: 'Sarabun', sans-serif; color: #1e293b; outline: none; transition: border-color .2s ease, background .2s ease, box-shadow .2s ease, transform .15s ease, color .2s ease; }
    .otp-input-group input:focus { border-color: var(--primary-color); background: #fff; box-shadow: 0 0 0 4px rgba(225,29,72,.12); }
    .otp-input-group input.filled { border-color: var(--primary-color); background: #fff1f5; color: var(--primary-color); }
    .otp-input-group input.complete { border-color: #10b981; background: #f0fdf4; color: #059669; }
    @keyframes digitPop { 0% { transform: scale(1); } 40% { transform: scale(1.18); } 100% { transform: scale(1); } }
    @keyframes digitComplete { 0% { transform: scale(1); } 30% { transform: scale(1.2); } 100% { transform: scale(1); } }

    .btn-auth { width: 100%; padding: 13px; border-radius: 14px; border: none; background: linear-gradient(110deg,#e11d48 0%,#be185d 55%,#db2777 100%); color: #fff; font-weight: 800; font-family: 'Sarabun', sans-serif; font-size: 0.92rem; cursor: pointer; transition: transform .25s cubic-bezier(.23,1,.32,1), box-shadow .25s ease; box-shadow: 0 6px 20px rgba(225,29,72,.38), 0 2px 6px rgba(225,29,72,.2); display: flex; align-items: center; justify-content: center; gap: 10px; position: relative; overflow: hidden; animation: fadeUp 0.45s ease 0.55s both; }
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

    .modern-alert { padding: 10px 16px; border-radius: 14px; margin-bottom: 12px; font-size: 0.83rem; display: flex; align-items: center; gap: 10px; }
    .modern-alert-success { background: #f0fdf4; color: #166534; border: 1px solid #bbf7d0; }
    .modern-alert-error { background: #fef2f2; color: #991b1b; border: 1px solid #fecaca; }

    .auth-footer { text-align: center; margin-top: 14px; font-size: 0.83rem; display: flex; flex-direction: column; gap: 8px; animation: fadeUp 0.45s ease 0.6s both; }
    .auth-footer a, .resend-link { color: var(--primary-color); text-decoration: none; font-weight: 700; background: none; border: none; font-family: 'Trirong','Sarabun', sans-serif; font-size: inherit; cursor: pointer; padding: 0; }
    .resend-link:disabled { color: #cbd5e1; cursor: not-allowed; }
    .auth-footer-divider { border: none; border-top: 1px solid #f1f5f9; margin: 4px 0; }

    @keyframes panelFromLeft { from { opacity: 0; transform: translateX(-28px); } to { opacity: 1; transform: translateX(0); } }
    @keyframes panelFromRight { from { opacity: 0; transform: translateX(20px); } to { opacity: 1; transform: translateX(0); } }
    @keyframes fadeUp { from { opacity: 0; transform: translateY(14px); } to { opacity: 1; transform: translateY(0); } }

    @media (prefers-reduced-motion: reduce) { .auth-side-panel, .auth-form-panel, .auth-byline, .auth-steps, .auth-form-heading, .email-chip, .otp-timer-pill, .otp-input-group, .btn-auth, .auth-footer { animation: none; } .btn-auth .btn-fill { transition: none; } .btn-auth .btn-icon.is-spinner { animation-duration: 1.8s; } }

    @media (max-width: 700px) { .auth-card { flex-direction: column; border-radius: 20px; max-width: 460px; } .auth-side-panel { flex: none; padding: 24px 24px 0; } .hero-block { margin-top: 12px; } .hero-block h1 { font-size: 1.2rem; } .feature-list { flex-direction: row; flex-wrap: wrap; gap: 8px; margin-top: 12px; } .panel-wave { display: none; } .auth-form-panel { padding: 22px 24px 24px; } }
    @media (max-width: 420px) { .auth-container { padding: 12px; } .auth-card { border-radius: 16px; } .otp-input-group input { width: 40px; height: 48px; } }
</style>
@endsection

@section('content')
    <div class="auth-container">
        <div class="auth-card">
            <div class="auth-side-panel">
                <div class="blob blob-1"></div>
                <div class="blob blob-2"></div>
                <div class="blob blob-3"></div>
                <div class="brand-logo-wrap">
                    <img src="{{ asset('images/logo.png') }}" alt="SSS Logo">
                    <span>Salt &amp; Sodium<br>Smart Monitor</span>
                </div>
                <div class="hero-block">
                    <h1>ยืนยันตัวตน<br><span>ปลอดภัย</span></h1>
                    <p>ระบบตรวจสอบตัวตนด้วยรหัส OTP ที่ส่งถึงอีเมลคุณโดยตรง</p>
                    <div class="feature-list">
                        <div class="feature-item">
                            <div class="feature-icon"><i class="fas fa-envelope-open-text"></i></div>
                            <div>รับ OTP ทางอีเมล</div>
                        </div>
                        <div class="feature-item">
                            <div class="feature-icon"><i class="fas fa-keyboard"></i></div>
                            <div>กรอกรหัส 6 หลัก</div>
                        </div>
                        <div class="feature-item">
                            <div class="feature-icon"><i class="fas fa-shield-check"></i></div>
                            <div>ยืนยันตัวตนสำเร็จ</div>
                        </div>
                    </div>
                </div>
                <svg class="panel-wave" viewBox="0 0 344 80" fill="none" xmlns="http://www.w3.org/2000/svg" preserveAspectRatio="none">
                    <path d="M0 40 Q86 10 172 40 Q258 70 344 40 L344 80 L0 80 Z" fill="rgba(255,255,255,0.07)"/>
                    <path d="M0 55 Q86 25 172 55 Q258 85 344 55 L344 80 L0 80 Z" fill="rgba(255,255,255,0.05)"/>
                </svg>
            </div>

            <div class="auth-form-panel">
                <div class="auth-byline">โดย สคร.10 อุบลราชธานี</div>
                <div class="auth-steps">
                    <span class="auth-step is-done"><i class="fas fa-check"></i></span>
                    <span class="auth-step-line is-done"></span>
                    <span class="auth-step is-active">2</span>
                    <span class="auth-step-line"></span>
                    <span class="auth-step">3</span>
                </div>
                <div class="auth-form-heading">
                    <h2>ยืนยันรหัส OTP</h2>
                    <p>ระบบส่งรหัส 6 หลักไปที่อีเมลของคุณแล้ว</p>
                </div>
                <div class="email-chip"><i class="fas fa-envelope"></i> <span>{{ $email }}</span></div>
                <div class="otp-timer-pill" id="otpTimer">
                    <i class="fas fa-clock"></i>
                    <span class="otp-timer-value" id="otpTimerValue">05:00</span>
                    <span class="otp-timer-label" id="otpTimerLabel">จนกว่ารหัสจะหมดอายุ</span>
                </div>

                @if (session('success'))
                    <div class="modern-alert modern-alert-success"><i class="fas fa-check-circle"></i> {{ session('success') }}</div>
                @endif
                @if (session('error'))
                    <div class="modern-alert modern-alert-error"><i class="fas fa-exclamation-circle"></i> {{ session('error') }}</div>
                @endif
                @if ($errors->any())
                    <div class="modern-alert modern-alert-error" style="flex-direction: column; align-items: flex-start;">
                        @foreach ($errors->all() as $error)
                            <div><i class="fas fa-exclamation-circle"></i> {{ $error }}</div>
                        @endforeach
                    </div>
                @endif

                <form action="{{ route('password.otp.verify') }}" method="POST" id="otpForm">
                    @csrf
                    <input type="hidden" name="otp" id="otpHidden">
                    <div class="otp-input-group">
                        @for ($i = 0; $i < 6; $i++)
                            <input type="text" inputmode="numeric" pattern="[0-9]*" maxlength="1" class="otp-digit" @if ($i === 0) autofocus @endif>
                        @endfor
                    </div>
                    <button type="submit" class="btn-auth" id="otpSubmitBtn">
                        <span class="btn-fill"></span>
                        <i class="fas fa-shield-check btn-icon"></i>
                        <span class="btn-label">ยืนยันรหัส OTP</span>
                    </button>
                </form>

                <div class="auth-footer">
                    <hr class="auth-footer-divider">
                    <form action="{{ route('password.email') }}" method="POST" id="resendForm">
                        @csrf
                        <input type="hidden" name="email" value="{{ $email }}">
                        <button type="submit" class="resend-link" id="resendBtn" disabled>ยังไม่ได้รับรหัส? ส่งอีกครั้ง</button>
                    </form>
                    <a href="{{ route('password.request') }}"><i class="fas fa-arrow-left"></i> เริ่มต้นใหม่ด้วยอีเมลอื่น</a>
                </div>
            </div>
        </div>
    </div>
@endsection

@section('extra_js')
    <script>
        (function () {
            const digits = Array.from(document.querySelectorAll('.otp-digit'));
            const hidden = document.getElementById('otpHidden');
            function syncHidden() { hidden.value = digits.map(d => d.value).join(''); }
            let autoSubmitTimer = null;
            function updateDigitState(input) {
                if (input.value) input.classList.add('filled');
                else input.classList.remove('filled', 'complete');
            }
            function triggerPop(input) {
                input.style.animation = 'none'; void input.offsetWidth;
                input.style.animation = 'digitPop 0.2s ease';
            }
            function checkAutoSubmit() {
                const allFilled = digits.every(d => d.value.length === 1);
                if (!allFilled) return;
                digits.forEach(d => {
                    d.classList.remove('filled'); d.classList.add('complete');
                    d.style.animation = 'none'; void d.offsetWidth;
                    d.style.animation = 'digitComplete 0.25s ease';
                });
                clearTimeout(autoSubmitTimer);
                autoSubmitTimer = setTimeout(() => {
                    syncHidden();
                    document.getElementById('otpForm').requestSubmit();
                }, 420);
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
                        if (!input.value && idx > 0) { digits[idx - 1].value = ''; updateDigitState(digits[idx - 1]); digits[idx - 1].focus(); }
                        else { input.value = ''; updateDigitState(input); }
                        clearTimeout(autoSubmitTimer); syncHidden();
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
            document.getElementById('otpForm').addEventListener('submit', syncHidden);

            let remaining = {{ (int) $remainingSeconds }};
            const timerValue = document.getElementById('otpTimerValue');
            const timerLabel = document.getElementById('otpTimerLabel');
            const timerWrap  = document.getElementById('otpTimer');
            const submitBtn  = document.getElementById('otpSubmitBtn');
            const resendBtn  = document.getElementById('resendBtn');
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
            render(); resendBtn.disabled = remaining > 0;
            const interval = setInterval(() => {
                remaining -= 1;
                if (remaining <= 0) { remaining = 0; render(); clearInterval(interval); return; }
                render();
            }, 1000);
        })();
    </script>
    <script>
        (function () {
            const form = document.querySelector('#otpForm');
            const btn  = document.querySelector('#otpSubmitBtn');
            if (!form || !btn) return;
            const icon = btn.querySelector('.btn-icon'), label = btn.querySelector('.btn-label');
            const iconClass = icon ? icon.className : '', labelText = label ? label.textContent : '';
            form.addEventListener('submit', function () {
                btn.classList.add('is-loading');
                if (label) label.textContent = 'กำลังตรวจสอบรหัส...';
                if (icon) icon.className = 'btn-icon is-spinner';
            });
            window.addEventListener('pageshow', function (e) {
                if (!e.persisted) return;
                btn.classList.remove('is-loading');
                if (label) label.textContent = labelText;
                if (icon) icon.className = iconClass;
            });
        })();
    </script>
@endsection
