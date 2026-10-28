@extends('layouts.layout')

@section('title', 'สร้างรหัสผ่านใหม่ - Salt & Sodium Smart Monitor')

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
        max-width: 1040px;
        border-radius: 32px;
        box-shadow: 0 30px 70px rgba(0,0,0,0.13);
        overflow: hidden;
    }

    .auth-side-panel {
        flex: 0 0 300px;
        position: relative;
        background:
            radial-gradient(ellipse at 30% 70%, #6b0f2e 0%, transparent 55%),
            linear-gradient(148deg, #1e0210 0%, #49082b 52%, #820d3a 100%);
        display: flex;
        flex-direction: column;
        justify-content: space-between;
        padding: 36px 26px 0;
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
    .hero-block h1 { font-family: 'Kodchasan','Sarabun', sans-serif; font-size: 1.45rem; font-weight: 900; line-height: 1.3; color: #fff; margin-bottom: 10px; }
    .hero-block h1 span { color: #fda4af; }
    .hero-block p { font-family: 'Sarabun', sans-serif; font-size: 0.8rem; color: rgba(255,255,255,0.72); line-height: 1.6; max-width: 190px; }

    .feature-list { position: relative; display: flex; flex-direction: column; gap: 12px; margin-top: 22px; }
    .feature-item { display: flex; align-items: center; gap: 12px; font-family: 'Sarabun', sans-serif; font-size: 0.8rem; color: rgba(255,255,255,0.85); font-weight: 600; }
    .feature-icon { width: 32px; height: 32px; background: rgba(255,255,255,0.13); border-radius: 9px; flex-shrink: 0; display: flex; align-items: center; justify-content: center; font-size: 0.82rem; color: #fda4af; }

    .panel-wave { display: block; position: relative; margin-top: 28px; width: calc(100% + 2px); margin-left: -1px; }

    .auth-form-panel { flex: 1; background: #fff; display: flex; flex-direction: column; padding: 28px 32px 26px; animation: panelFromRight 0.65s cubic-bezier(0.23,1,0.32,1) 0.1s both; }

    .auth-byline { font-family: 'Trirong','Sarabun', sans-serif; font-size: 0.72rem; color: #b0748a; font-weight: 600; letter-spacing: 0.04em; margin-bottom: 14px; animation: fadeUp 0.5s ease 0.25s both; }

    .auth-steps { display: flex; align-items: center; gap: 6px; margin-bottom: 16px; animation: fadeUp 0.5s ease 0.3s both; }
    .auth-step { width: 22px; height: 22px; border-radius: 50%; display: flex; align-items: center; justify-content: center; font-size: 0.68rem; font-weight: 800; background: #f1f5f9; color: #94a3b8; flex-shrink: 0; }
    .auth-step.is-done { background: var(--primary-color); color: #fff; }
    .auth-step.is-active { background: #fff; color: var(--primary-color); border: 2px solid var(--primary-color); box-shadow: 0 0 0 3px rgba(225,29,72,0.15); }
    .auth-step-line { width: 24px; height: 2px; background: #e2e8f0; flex-shrink: 0; }
    .auth-step-line.is-done { background: var(--primary-color); }

    .auth-form-heading-row { display: flex; align-items: center; gap: 12px; margin-bottom: 14px; animation: fadeUp 0.5s ease 0.35s both; }
    .auth-icon-badge { width: 40px; height: 40px; flex-shrink: 0; background: linear-gradient(135deg, #e11d48, #be185d); border-radius: 12px; display: flex; align-items: center; justify-content: center; color: #fff; font-size: 1rem; box-shadow: 0 4px 12px rgba(225,29,72,0.35); }
    .auth-form-heading h2 { font-family: 'Kodchasan','Sarabun', sans-serif; font-size: 1.12rem; font-weight: 800; color: #1e1b2e; margin-bottom: 1px; }
    .auth-form-heading p { font-family: 'Sarabun', sans-serif; font-size: 0.78rem; color: #7c6c78; }

    .form-label { display: block; font-family: 'Sarabun', sans-serif; font-size: 0.82rem; font-weight: 700; color: #374151; margin-bottom: 5px; }
    .form-control { width: 100%; padding: 10px 14px; border: 1.5px solid #ede4ea; border-radius: 14px; background: #fdf9fb; color: #1e293b; font-family: 'Sarabun', sans-serif; font-size: 0.92rem; outline: none; transition: border-color .2s ease, background .2s ease, box-shadow .2s ease; box-sizing: border-box; }
    .form-control:focus { border-color: var(--primary-color); background: #fff; box-shadow: 0 0 0 4px rgba(225,29,72,.1); }
    .form-control.is-invalid { border-color: #ef4444; }

    .pw-field-wrap { position: relative; }
    .pw-toggle-btn { position: absolute; right: 12px; top: 50%; transform: translateY(-50%); background: none; border: none; cursor: pointer; padding: 4px; color: #94a3b8; font-size: 0.88rem; line-height: 1; transition: color .2s ease; }
    .pw-toggle-btn:hover { color: var(--primary-color); }
    .pw-field-wrap .form-control { padding-right: 38px; }

    .reset-columns { display: flex; gap: 20px; flex: 1; animation: fadeUp 0.45s ease 0.4s both; }
    .reset-fields-col { flex: 1; display: flex; flex-direction: column; gap: 12px; }
    .rules-panel { width: 200px; flex-shrink: 0; background: #fdf9fb; border-radius: 16px; border: 1.5px solid #ede4ea; padding: 14px 16px; display: flex; flex-direction: column; gap: 8px; align-self: flex-start; }
    .rules-panel-title { font-family: 'Sarabun', sans-serif; font-size: 0.75rem; font-weight: 800; color: #7c6c78; margin-bottom: 4px; }
    .rule-item { display: flex; align-items: center; gap: 8px; font-family: 'Sarabun', sans-serif; font-size: 0.75rem; color: #94a3b8; font-weight: 600; transition: color .25s ease; }
    .rule-item.is-ok { color: #10b981; }
    .rule-icon { width: 18px; height: 18px; border-radius: 50%; background: #f1f5f9; flex-shrink: 0; display: flex; align-items: center; justify-content: center; font-size: 0.6rem; color: #94a3b8; transition: background .25s ease, color .25s ease; }
    .rule-item.is-ok .rule-icon { background: #dcfce7; color: #10b981; }

    .remember-row { display: flex; align-items: center; gap: 8px; margin-top: 14px; margin-bottom: 14px; font-family: 'Sarabun', sans-serif; font-size: 0.82rem; color: #64748b; font-weight: 600; animation: fadeUp 0.45s ease 0.55s both; }
    .remember-row input[type=checkbox] { accent-color: var(--primary-color); width: 16px; height: 16px; }

    .btn-auth { width: 100%; padding: 13px; border-radius: 14px; border: none; background: linear-gradient(110deg,#e11d48 0%,#be185d 55%,#db2777 100%); color: #fff; font-weight: 800; font-family: 'Sarabun', sans-serif; font-size: 0.92rem; cursor: pointer; transition: transform .25s cubic-bezier(.23,1,.32,1), box-shadow .25s ease; box-shadow: 0 6px 20px rgba(225,29,72,.38), 0 2px 6px rgba(225,29,72,.2); display: flex; align-items: center; justify-content: center; gap: 10px; position: relative; overflow: hidden; animation: fadeUp 0.45s ease 0.6s both; }
    .btn-auth:hover { transform: translateY(-2px); box-shadow: 0 12px 28px rgba(225,29,72,.45), 0 4px 10px rgba(225,29,72,.22); }
    .btn-auth .btn-fill { position: absolute; inset: 0; background: #fff; transform: scaleX(0); transform-origin: left center; transition: transform 0.55s cubic-bezier(0.65,0,0.35,1); pointer-events: none; }
    .btn-auth .btn-icon, .btn-auth .btn-label { position: relative; transition: color 0.35s ease 0.15s; }
    .btn-auth.is-loading { cursor: wait; transform: none; pointer-events: none; box-shadow: 0 8px 20px rgba(225,29,72,.3); }
    .btn-auth.is-loading .btn-fill { transform: scaleX(1); }
    .btn-auth.is-loading .btn-icon, .btn-auth.is-loading .btn-label { color: #e11d48; }
    .btn-auth .btn-icon.is-spinner { display: inline-block; flex: 0 0 auto; width: 1.1em; height: 1.1em; box-sizing: border-box; border: 2.5px solid rgba(225,29,72,.22); border-top-color: #e11d48; border-radius: 50%; animation: btnSpin 0.75s linear infinite; }
    .btn-auth .btn-icon.is-spinner::before { content: none; }
    @keyframes btnSpin { to { transform: rotate(360deg); } }

    .modern-alert { padding: 10px 16px; border-radius: 14px; margin-bottom: 12px; font-size: 0.83rem; display: flex; align-items: center; gap: 10px; }
    .modern-alert-success { background: #f0fdf4; color: #166534; border: 1px solid #bbf7d0; }
    .modern-alert-error { background: #fef2f2; color: #991b1b; border: 1px solid #fecaca; }

    .auth-footer { text-align: center; margin-top: 12px; font-size: 0.83rem; display: flex; flex-direction: column; gap: 6px; animation: fadeUp 0.45s ease 0.65s both; }
    .auth-footer a { color: var(--primary-color); text-decoration: none; font-weight: 700; font-family: 'Trirong','Sarabun', sans-serif; }
    .auth-footer-divider { border: none; border-top: 1px solid #f1f5f9; margin: 4px 0; }

    @keyframes panelFromLeft { from { opacity: 0; transform: translateX(-28px); } to { opacity: 1; transform: translateX(0); } }
    @keyframes panelFromRight { from { opacity: 0; transform: translateX(20px); } to { opacity: 1; transform: translateX(0); } }
    @keyframes fadeUp { from { opacity: 0; transform: translateY(14px); } to { opacity: 1; transform: translateY(0); } }

    @media (prefers-reduced-motion: reduce) { .auth-side-panel, .auth-form-panel, .auth-byline, .auth-steps, .auth-form-heading-row, .reset-columns, .remember-row, .btn-auth, .auth-footer { animation: none; } .btn-auth .btn-fill { transition: none; } .btn-auth .btn-icon.is-spinner { animation-duration: 1.8s; } }

    @media (max-width: 820px) { .rules-panel { width: 170px; } }
    @media (max-width: 700px) { .auth-card { flex-direction: column; border-radius: 20px; } .auth-side-panel { flex: none; padding: 24px 24px 0; } .hero-block { margin-top: 12px; } .hero-block h1 { font-size: 1.2rem; } .feature-list { flex-direction: row; flex-wrap: wrap; gap: 8px; margin-top: 12px; } .panel-wave { display: none; } .auth-form-panel { padding: 22px 24px 24px; } .reset-columns { flex-direction: column; } .rules-panel { width: 100%; } }
    @media (max-width: 420px) { .auth-container { padding: 12px; } .auth-card { border-radius: 16px; } }
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
                    <h1>ตั้งรหัสผ่านใหม่<br><span>ปลอดภัยมั่นใจ</span></h1>
                    <p>กำหนดรหัสผ่านที่แข็งแกร่งเพื่อปกป้องบัญชีของคุณ</p>
                    <div class="feature-list">
                        <div class="feature-item">
                            <div class="feature-icon"><i class="fas fa-lock"></i></div>
                            <div>กำหนดรหัสผ่านใหม่</div>
                        </div>
                        <div class="feature-item">
                            <div class="feature-icon"><i class="fas fa-shield-alt"></i></div>
                            <div>ตรวจสอบความปลอดภัย</div>
                        </div>
                        <div class="feature-item">
                            <div class="feature-icon"><i class="fas fa-sign-in-alt"></i></div>
                            <div>เข้าสู่ระบบได้เลย</div>
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
                    <span class="auth-step is-done"><i class="fas fa-check"></i></span>
                    <span class="auth-step-line is-done"></span>
                    <span class="auth-step is-active">3</span>
                </div>

                <div class="auth-form-heading-row">
                    <div class="auth-icon-badge"><i class="fas fa-key"></i></div>
                    <div class="auth-form-heading">
                        <h2>สร้างรหัสผ่านใหม่</h2>
                        <p>กรอกรหัสผ่านที่ต้องการตั้งใหม่ด้านล่าง</p>
                    </div>
                </div>

                @if (session('status'))
                    <div class="modern-alert modern-alert-success"><i class="fas fa-check-circle"></i> {{ session('status') }}</div>
                @endif
                @if ($errors->any())
                    <div class="modern-alert modern-alert-error" style="flex-direction: column; align-items: flex-start;">
                        @foreach ($errors->all() as $error)
                            <div><i class="fas fa-exclamation-circle"></i> {{ $error }}</div>
                        @endforeach
                    </div>
                @endif

                <form method="POST" action="{{ route('password.update') }}" id="resetForm">
                    @csrf
                    <input type="hidden" name="token" value="{{ $token }}">
                    <input type="hidden" name="email" value="{{ $email ?? old('email') }}">

                    <div class="reset-columns">
                        <div class="reset-fields-col">
                            <div>
                                <label class="form-label"><i class="fas fa-envelope" style="color:var(--primary-color);margin-right:5px;"></i>อีเมล</label>
                                <input type="email" class="form-control" value="{{ $email ?? old('email') }}" readonly style="background:#f8fafc; color:#6b7280; cursor:default;">
                            </div>
                            <div>
                                <label class="form-label"><i class="fas fa-lock" style="color:var(--primary-color);margin-right:5px;"></i>รหัสผ่านใหม่</label>
                                <div class="pw-field-wrap">
                                    <input type="password" name="password" id="passwordField"
                                        class="form-control @error('password') is-invalid @enderror"
                                        placeholder="กรอกรหัสผ่านใหม่" autocomplete="new-password"
                                        oninput="updatePasswordChecklist(this.value)">
                                    <button type="button" class="pw-toggle-btn" onclick="togglePasswordVisibility('passwordField', this)" title="แสดง/ซ่อนรหัสผ่าน">
                                        <i class="fas fa-eye"></i>
                                    </button>
                                </div>
                            </div>
                            <div>
                                <label class="form-label"><i class="fas fa-lock-open" style="color:var(--primary-color);margin-right:5px;"></i>ยืนยันรหัสผ่าน</label>
                                <div class="pw-field-wrap">
                                    <input type="password" name="password_confirmation" id="passwordConfirmField"
                                        class="form-control" placeholder="กรอกรหัสผ่านอีกครั้ง" autocomplete="new-password">
                                    <button type="button" class="pw-toggle-btn" onclick="togglePasswordVisibility('passwordConfirmField', this)" title="แสดง/ซ่อนรหัสผ่าน">
                                        <i class="fas fa-eye"></i>
                                    </button>
                                </div>
                            </div>
                        </div>

                        <div class="rules-panel">
                            <div class="rules-panel-title"><i class="fas fa-shield-check" style="margin-right:4px;color:var(--primary-color);"></i>ข้อกำหนดรหัสผ่าน</div>
                            <div class="rule-item" id="rule-length"><span class="rule-icon"><i class="fas fa-times"></i></span><span>อย่างน้อย 8 ตัว</span></div>
                            <div class="rule-item" id="rule-upper"><span class="rule-icon"><i class="fas fa-times"></i></span><span>ตัวพิมพ์ใหญ่</span></div>
                            <div class="rule-item" id="rule-lower"><span class="rule-icon"><i class="fas fa-times"></i></span><span>ตัวพิมพ์เล็ก</span></div>
                            <div class="rule-item" id="rule-number"><span class="rule-icon"><i class="fas fa-times"></i></span><span>ตัวเลข</span></div>
                            <div class="rule-item" id="rule-special"><span class="rule-icon"><i class="fas fa-times"></i></span><span>อักขระพิเศษ</span></div>
                        </div>
                    </div>

                    <div class="remember-row">
                        <input type="checkbox" name="logout_other" id="logoutOther" value="1" checked>
                        <label for="logoutOther">ออกจากระบบอุปกรณ์อื่นทั้งหมด</label>
                    </div>

                    <button type="submit" class="btn-auth" id="resetSubmitBtn">
                        <span class="btn-fill"></span>
                        <i class="fas fa-key btn-icon"></i>
                        <span class="btn-label">บันทึกรหัสผ่านใหม่</span>
                    </button>
                </form>

                <div class="auth-footer">
                    <hr class="auth-footer-divider">
                    <a href="{{ route('login') }}"><i class="fas fa-arrow-left"></i> กลับไปเข้าสู่ระบบ</a>
                </div>
            </div>
        </div>
    </div>
@endsection

@section('extra_js')
    <script>
        function togglePasswordVisibility(fieldId, btn) {
            const field = document.getElementById(fieldId);
            const icon  = btn.querySelector('i');
            if (!field) return;
            if (field.type === 'password') { field.type = 'text'; icon.classList.replace('fa-eye', 'fa-eye-slash'); }
            else { field.type = 'password'; icon.classList.replace('fa-eye-slash', 'fa-eye'); }
        }
        function updatePasswordChecklist(value) {
            const rules = {
                'rule-length':  value.length >= 8,
                'rule-upper':   /[A-Z]/.test(value),
                'rule-lower':   /[a-z]/.test(value),
                'rule-number':  /[0-9]/.test(value),
                'rule-special': /[^A-Za-z0-9]/.test(value),
            };
            Object.entries(rules).forEach(([id, ok]) => {
                const el = document.getElementById(id);
                const icon = el ? el.querySelector('.rule-icon i') : null;
                if (!el || !icon) return;
                el.classList.toggle('is-ok', ok);
                icon.classList.toggle('fa-check', ok);
                icon.classList.toggle('fa-times', !ok);
            });
        }
    </script>
    <script>
        (function () {
            const form = document.querySelector('#resetForm');
            const btn  = document.querySelector('#resetSubmitBtn');
            if (!form || !btn) return;
            const icon = btn.querySelector('.btn-icon'), label = btn.querySelector('.btn-label');
            const iconClass = icon ? icon.className : '', labelText = label ? label.textContent : '';
            form.addEventListener('submit', function () {
                btn.classList.add('is-loading');
                if (label) label.textContent = 'กำลังบันทึก...';
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
