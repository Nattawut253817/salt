@extends('layouts.layout')

@section('title', 'ลืมรหัสผ่าน - Salt & Sodium Smart Monitor')

@section('extra_css')
<style>
    .content-header { display: none !important; }

    /* ── Page shell ── */
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
        max-width: 1000px;
        min-height: 560px;
        border-radius: 32px;
        box-shadow:
            0 2px 4px rgba(225, 29, 72, 0.04),
            0 8px 24px rgba(15, 23, 42, 0.07),
            0 32px 72px rgba(15, 23, 42, 0.10);
        overflow: hidden;
        animation: cardRise 0.55s cubic-bezier(0.22, 1, 0.36, 1) both;
    }

    @keyframes cardRise {
        from { opacity: 0; transform: translateY(32px) scale(0.98); }
        to   { opacity: 1; transform: translateY(0)   scale(1); }
    }

    /* ── Left branding panel ── */
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
        position: absolute; inset: 0; z-index: 0; pointer-events: none;
    }
    .auth-side-art > svg { position: absolute; inset: 0; width: 100%; height: 100%; }
    .auth-side-art::after {
        content: '';
        position: absolute; inset: 0;
        background: linear-gradient(100deg, rgba(30,2,16,.55) 0%, rgba(30,2,16,.08) 55%, transparent 75%);
    }

    .art-blob { position: absolute; z-index: 1; }
    .art-blob-1 {
        width: 160px; height: 160px; border-radius: 50%;
        background: rgba(251,113,133,.22); left: 2%; top: 30%; filter: blur(32px);
    }
    .art-blob-2 {
        width: 52px; height: 52px; border-radius: 14px;
        background: rgba(255,255,255,.18); left: 28%; top: 28%; filter: blur(6px);
    }

    .art-dots {
        position: absolute;
        background-image: radial-gradient(circle, rgba(255,255,255,.5) 1.4px, transparent 2px);
        background-size: 20px 20px; opacity: .75; z-index: 1;
    }
    .art-dots-1 { width: 112px; height: 72px; top: 17%; left: 38%; }

    .auth-side-logo, .auth-side-hero, .auth-side-features { position: relative; z-index: 3; }

    .auth-side-logo {
        width: 100%; max-width: 210px; aspect-ratio: 2.5;
        background: #fffafc; border-radius: 16px; padding: 4px;
        display: flex; align-items: center; justify-content: center; overflow: hidden;
        box-shadow: 0 12px 28px rgba(0,0,0,.32), 0 2px 6px rgba(0,0,0,.1);
    }
    .auth-side-logo img { width: 100%; height: 100%; object-fit: cover; border-radius: 12px; }

    .auth-side-hero { margin-top: auto; }

    .auth-side-title {
        font-size: 2.1rem; font-weight: 800; line-height: 1.1;
        letter-spacing: -0.01em; margin: 0 0 14px;
        text-shadow: 0 2px 12px rgba(30,2,16,.5);
    }
    .auth-side-title span { display: block; }
    .auth-side-title .accent {
        background: linear-gradient(88deg, #fda4af, #f472b6 55%, #fbcfe8);
        -webkit-background-clip: text; background-clip: text;
        -webkit-text-fill-color: transparent; color: #fda4af;
    }

    .auth-side-subtitle {
        font-size: 0.87rem; font-weight: 500; line-height: 1.75;
        color: rgba(255,255,255,.78); max-width: 28ch;
    }

    .auth-side-features {
        margin-top: 36px; display: flex; flex-direction: row; gap: 10px;
    }
    .auth-side-feature {
        display: flex; flex-direction: column; align-items: center;
        text-align: center; gap: 8px; font-size: 0.72rem; font-weight: 600;
        line-height: 1.3; color: rgba(255,255,255,.88); flex: 1;
    }
    .auth-side-feature-icon {
        flex-shrink: 0; width: 40px; height: 40px;
        display: flex; align-items: center; justify-content: center;
        border-radius: 12px;
        background: rgba(255,255,255,.14); border: 1px solid rgba(255,255,255,.2);
        color: #fda4af; font-size: 0.9rem;
    }

    /* ── Right form panel ── */
    .auth-main-panel {
        flex: 1; min-width: 0;
        display: flex; flex-direction: column; justify-content: center;
        position: relative; overflow-y: auto; overflow-x: hidden;
    }

    .auth-byline {
        position: absolute; top: 22px; right: 32px;
        font-size: 0.73rem; font-weight: 700; color: #c4a4b2; letter-spacing: .04em;
    }

    /* Step indicator — sits inside right panel */
    .auth-steps {
        display: flex; align-items: center; justify-content: flex-start; gap: 6px;
        margin-bottom: 18px;
    }
    .auth-step {
        width: 24px; height: 24px; border-radius: 50%;
        display: flex; align-items: center; justify-content: center;
        font-size: 0.72rem; font-weight: 800;
        background: #f1e4ea; color: #b0748a; flex-shrink: 0;
    }
    .auth-step.is-active {
        background: var(--primary-color); color: #fff;
        box-shadow: 0 0 0 4px rgba(225,29,72,.15);
    }
    .auth-step-line {
        width: 28px; height: 2px; background: #ede4ea; flex-shrink: 0;
    }

    /* Welcome heading */
    .auth-welcome { padding: 40px 50px 0; }

    .auth-welcome-title {
        font-size: 1.65rem; font-weight: 800; line-height: 1.15;
        letter-spacing: -.01em; color: #1e1b2e;
    }
    .auth-welcome-desc {
        margin-top: 5px;
        font-size: 0.86rem; font-weight: 500; color: #7a6070; line-height: 1.6;
    }

    /* Form container */
    .auth-form-container { padding: 20px 50px 30px; }

    /* Form fields */
    .form-group { margin-bottom: 16px; }

    .form-label {
        display: block; margin-bottom: 6px; margin-left: 1px;
        font-weight: 600; font-family: 'Sarabun', sans-serif;
        color: #5a3d52; font-size: 0.82rem; letter-spacing: .012em;
    }

    .input-wrapper { position: relative; }
    .input-wrapper > i {
        position: absolute; left: 18px; top: 50%; transform: translateY(-50%);
        color: var(--primary-color); opacity: .55; z-index: 1; font-size: .85rem;
    }

    .form-control {
        width: 100%;
        padding: 13px 16px 13px 46px;
        border-radius: 14px;
        border: 1.5px solid #ede4ea;
        background: #fdf9fb;
        font-family: 'Sarabun', sans-serif;
        font-size: 0.88rem; font-weight: 500; color: #1e1b2e;
        box-sizing: border-box;
        transition: border-color .2s ease, background .2s ease, box-shadow .2s ease;
    }
    .form-control::placeholder { color: #b8a8b2; font-weight: 400; }
    .form-control:focus {
        border-color: rgba(225,29,72,.45); background: #fff;
        box-shadow: 0 0 0 3.5px rgba(225,29,72,.1); outline: none;
    }

    /* Submit button */
    .btn-auth {
        position: relative; overflow: hidden;
        width: 100%; padding: 14px; margin-top: 8px;
        border-radius: 14px; border: none;
        background: var(--pink-gradient-btn); color: #fff;
        font-weight: 800; font-size: 0.9rem; font-family: 'Sarabun', sans-serif;
        cursor: pointer; display: flex; align-items: center; justify-content: center; gap: 10px;
        box-shadow: 0 6px 20px rgba(225,29,72,.38), 0 2px 6px rgba(225,29,72,.2);
        transition: transform .25s cubic-bezier(.23,1,.32,1), box-shadow .25s ease;
    }
    .btn-auth:hover {
        transform: translateY(-2px);
        box-shadow: 0 12px 28px rgba(225,29,72,.45), 0 4px 10px rgba(225,29,72,.22);
    }
    .btn-auth:active { transform: translateY(0); }

    /* Loading wipe */
    .btn-auth .btn-fill {
        position: absolute; inset: 0; background: #fff;
        transform: scaleX(0); transform-origin: left center;
        transition: transform .55s cubic-bezier(.65,0,.35,1); pointer-events: none;
    }
    .btn-auth .btn-icon, .btn-auth .btn-label { position: relative; transition: color .35s ease .15s; }
    .btn-auth.is-loading { cursor: wait; transform: none; pointer-events: none; box-shadow: 0 4px 14px rgba(225,29,72,.25); }
    .btn-auth.is-loading .btn-fill { transform: scaleX(1); }
    .btn-auth.is-loading .btn-icon, .btn-auth.is-loading .btn-label { color: #e11d48; }
    .btn-auth .btn-icon.is-spinner {
        display: inline-block; flex: 0 0 auto; width: 1.1em; height: 1.1em; box-sizing: border-box;
        border: 2.5px solid rgba(225,29,72,.22); border-top-color: #e11d48; border-radius: 50%;
        animation: btnSpin .75s linear infinite;
    }
    .btn-auth .btn-icon.is-spinner::before { content: none; }
    @keyframes btnSpin { to { transform: rotate(360deg); } }

    /* Back link */
    .auth-footer { margin-top: 20px; }
    .auth-footer a {
        color: var(--primary-color); text-decoration: none;
        font-weight: 700; font-family: 'Sarabun', sans-serif; font-size: 0.85rem;
        display: inline-flex; align-items: center; gap: 6px;
        border-bottom: 1.5px solid transparent; transition: border-color .2s;
    }
    .auth-footer a:hover { border-bottom-color: var(--primary-color); }
    .auth-footer a i { font-size: 0.78rem; }

    /* Alerts */
    .modern-alert {
        padding: 12px 18px; border-radius: 14px; margin-bottom: 16px;
        font-size: 0.85rem; display: flex; align-items: center; gap: 10px;
    }
    .modern-alert-success { background: #f0fdf4; color: #166534; border: 1px solid #bbf7d0; }
    .modern-alert-error   { background: #fef2f2; color: #991b1b; border: 1px solid #fecaca; }

    /* ── Responsive ── */
    @media (max-width: 820px) {
        .auth-card { flex-direction: column; max-width: 540px; min-height: 0; border-radius: 24px; }
        .auth-side-panel {
            flex: 0 0 auto; flex-direction: row; align-items: center; justify-content: center;
            gap: 16px; padding: 24px 28px; border-radius: 0 0 36px 36px;
        }
        .auth-side-logo { width: 120px; flex-shrink: 0; }
        .auth-side-hero { margin-top: 0; }
        .auth-side-title { font-size: 1rem; margin-bottom: 0; }
        .auth-side-title span { display: inline; }
        .auth-side-subtitle, .auth-side-features, .art-dots, .art-blob { display: none; }
        .auth-byline { display: none; }
        .auth-welcome { padding: 22px 22px 0; }
        .auth-form-container { padding: 16px 22px 28px; }
    }
    @media (max-width: 600px) {
        .auth-container { padding: 0; }
        .auth-card { border-radius: 0; }
    }
    @media (prefers-reduced-motion: reduce) {
        .auth-card, .btn-auth { animation: none !important; transition: none !important; }
        .btn-auth .btn-fill { transition-duration: .01s; }
        .btn-auth .btn-icon.is-spinner { animation-duration: 1.8s; }
    }
</style>
@endsection

@section('content')
    <div class="auth-container">
        <div class="auth-card">

            {{-- ── Left branding panel ── --}}
            <div class="auth-side-panel">
                <div class="auth-side-art" aria-hidden="true">
                    <svg viewBox="0 0 600 800" preserveAspectRatio="none">
                        <defs>
                            <linearGradient id="pwWaveTop" x1="0" y1="0" x2="1" y2="1">
                                <stop offset="0" stop-color="#fda4af" stop-opacity="0.55"/>
                                <stop offset="1" stop-color="#fecdd3" stop-opacity="0.95"/>
                            </linearGradient>
                            <linearGradient id="pwWaveBot" x1="0" y1="0" x2="1" y2="0">
                                <stop offset="0" stop-color="#fb7185" stop-opacity="0.95"/>
                                <stop offset="1" stop-color="#f472b6" stop-opacity="0.55"/>
                            </linearGradient>
                            <linearGradient id="pwWaveBotDeep" x1="0" y1="0" x2="1" y2="0">
                                <stop offset="0" stop-color="#be123c" stop-opacity="0.9"/>
                                <stop offset="1" stop-color="#9d174d" stop-opacity="0.2"/>
                            </linearGradient>
                        </defs>
                        <path d="M300 0 C 335 95 430 150 530 172 C 565 180 588 195 600 212 L 600 0 Z" fill="url(#pwWaveTop)"/>
                        <path d="M385 0 C 410 62 490 112 600 132 L 600 0 Z" fill="#fff" fill-opacity="0.2"/>
                        <path d="M0 662 C 110 622 235 662 305 735 C 330 762 350 785 366 800 L 0 800 Z" fill="url(#pwWaveBot)"/>
                        <path d="M0 712 C 105 682 215 718 285 785 C 292 792 298 797 303 800 L 0 800 Z" fill="url(#pwWaveBotDeep)"/>
                        <path d="M0 662 C 110 622 235 662 305 735" fill="none" stroke="#fff" stroke-opacity="0.35" stroke-width="1.4"/>
                    </svg>
                    <div class="art-blob art-blob-1"></div>
                    <div class="art-blob art-blob-2"></div>
                    <div class="art-dots art-dots-1"></div>
                </div>

                <div class="auth-side-logo">
                    <img src="{{ asset('images/logo-login.jpg') }}" alt="Smart Salt & Sodium Monitor Logo">
                </div>

                <div class="auth-side-hero">
                    <div class="auth-side-title">
                        <span>สร้างรหัสผ่าน</span>
                        <span class="accent">ใหม่ได้เลย</span>
                    </div>
                    <div class="auth-side-subtitle">รับรหัส OTP ทางอีเมล ยืนยันตัวตน แล้วตั้งรหัสผ่านใหม่ได้ทันที</div>
                </div>

                <div class="auth-side-features">
                    <div class="auth-side-feature">
                        <span class="auth-side-feature-icon"><i class="fas fa-envelope-open-text"></i></span>
                        <span>รับ OTP<br>ทางอีเมล</span>
                    </div>
                    <div class="auth-side-feature">
                        <span class="auth-side-feature-icon"><i class="fas fa-shield-halved"></i></span>
                        <span>ยืนยัน<br>ตัวตน</span>
                    </div>
                    <div class="auth-side-feature">
                        <span class="auth-side-feature-icon"><i class="fas fa-key"></i></span>
                        <span>ตั้งรหัส<br>ใหม่</span>
                    </div>
                </div>
            </div>

            {{-- ── Right form panel ── --}}
            <div class="auth-main-panel">
                <div class="auth-byline">โดย สคร.10 อุบลราชธานี</div>

                <div class="auth-welcome">
                    <div class="auth-steps">
                        <span class="auth-step is-active">1</span>
                        <span class="auth-step-line"></span>
                        <span class="auth-step">2</span>
                        <span class="auth-step-line"></span>
                        <span class="auth-step">3</span>
                    </div>
                    <div class="auth-welcome-title">ลืมรหัสผ่าน?</div>
                    <div class="auth-welcome-desc">กรอกอีเมลของคุณเพื่อรับรหัส OTP สำหรับสร้างรหัสผ่านใหม่</div>
                </div>

                <div class="auth-form-container">
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

                    <form action="{{ route('password.email') }}" method="POST">
                        @csrf
                        <div class="form-group">
                            <label class="form-label">อีเมล</label>
                            <div class="input-wrapper">
                                <i class="fas fa-envelope"></i>
                                <input type="email" name="email" class="form-control"
                                    placeholder="example@health.go.th"
                                    value="{{ old('email') }}" required autofocus>
                            </div>
                        </div>

                        <button type="submit" class="btn-auth">
                            <span class="btn-fill"></span>
                            <i class="fas fa-paper-plane btn-icon"></i>
                            <span class="btn-label">ส่งรหัส OTP</span>
                        </button>
                    </form>

                    <div class="auth-footer">
                        <a href="{{ route('staff') }}">
                            <i class="fas fa-arrow-left"></i> กลับไปหน้าเข้าสู่ระบบ
                        </a>
                    </div>
                </div>
            </div>

        </div>
    </div>
@endsection

@section('extra_js')
    <script>
        (function () {
            const form  = document.querySelector('form[action="{{ route('password.email') }}"]');
            const btn   = form ? form.querySelector('.btn-auth') : null;
            if (!form || !btn) return;
            const icon  = btn.querySelector('.btn-icon');
            const label = btn.querySelector('.btn-label');
            const savedIcon  = icon  ? icon.className  : '';
            const savedLabel = label ? label.textContent : '';

            form.addEventListener('submit', function () {
                btn.classList.add('is-loading');
                if (label) label.textContent = 'กำลังส่งรหัส OTP...';
                if (icon)  icon.className = 'btn-icon is-spinner';
            });

            window.addEventListener('pageshow', function (e) {
                if (!e.persisted) return;
                btn.classList.remove('is-loading');
                if (icon)  icon.className   = savedIcon;
                if (label) label.textContent = savedLabel;
            });
        })();
    </script>
@endsection
