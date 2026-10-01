@extends('layouts.layout')

@section('title', 'Reset Password - Salt & Sodium Smart Monitor')

@section('extra_css')
    <style>
        /* Hide default header */
        .content-header {
            display: none !important;
        }

        .auth-container {
            display: flex;
            justify-content: center;
            align-items: center;
            padding: 26px 20px;
            background: #fbfbfd;
        }

        .auth-card {
            background: #ffffff;
            width: 100%;
            max-width: 720px;
            border-radius: 28px;
            box-shadow: 0 30px 60px rgba(0, 0, 0, 0.1);
            overflow: hidden;
            animation: slideUp 0.6s cubic-bezier(0.23, 1, 0.32, 1);
        }

        @keyframes slideUp {
            from {
                opacity: 0;
                transform: translateY(40px);
            }

            to {
                opacity: 1;
                transform: translateY(0);
            }
        }

        /* Card header band - the "หัวการ์ด" for all 3 forgot-password steps */
        .auth-card-header {
            position: relative;
            background: var(--pink-gradient);
            padding: 20px 32px 16px;
            text-align: center;
            overflow: hidden;
        }

        .auth-card-header::before,
        .auth-card-header::after {
            content: '';
            position: absolute;
            border-radius: 50%;
            background: rgba(255, 255, 255, 0.14);
        }

        .auth-card-header::before {
            width: 160px;
            height: 160px;
            top: -85px;
            right: -50px;
        }

        .auth-card-header::after {
            width: 110px;
            height: 110px;
            bottom: -65px;
            left: -35px;
        }

        .auth-steps {
            position: relative;
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 6px;
            margin-bottom: 12px;
        }

        .auth-step {
            width: 22px;
            height: 22px;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 0.68rem;
            font-weight: 800;
            background: rgba(255, 255, 255, 0.25);
            color: rgba(255, 255, 255, 0.8);
            flex-shrink: 0;
        }

        .auth-step.is-done {
            background: #fff;
            color: #f0618f;
        }

        .auth-step.is-active {
            background: #fff;
            color: #f0618f;
            box-shadow: 0 0 0 4px rgba(255, 255, 255, 0.3);
        }

        .auth-step-line {
            width: 24px;
            height: 2px;
            background: rgba(255, 255, 255, 0.32);
            flex-shrink: 0;
        }

        .auth-step-line.is-done {
            background: #fff;
        }

        .auth-icon-badge {
            position: relative;
            width: 44px;
            height: 44px;
            margin: 0 auto 10px;
            border-radius: 14px;
            background: rgba(255, 255, 255, 0.22);
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 1.15rem;
            color: #fff;
        }

        .auth-card-header h2 {
            position: relative;
            font-weight: 800;
            font-family: 'Kodchasan', 'Sarabun', sans-serif;
            color: #fff;
            margin-bottom: 3px;
            font-size: 1.15rem;
        }

        .auth-card-header p {
            position: relative;
            font-family: 'Sarabun', 'TH Sarabun New', 'TH Sarabun PS', sans-serif;
            color: rgba(255, 255, 255, 0.9);
            font-size: 0.8rem;
            margin: 0;
        }

        .auth-card-body {
            padding: 22px 32px 26px;
        }

        .reset-columns {
            display: flex;
            gap: 26px;
            align-items: flex-start;
        }

        .reset-fields-col {
            flex: 1;
            min-width: 0;
        }

        .form-group {
            margin-bottom: 14px;
        }

        .form-label {
            display: block;
            margin-bottom: 5px;
            margin-left: 15px;
            font-weight: 700;
            font-family: 'Kodchasan', 'Sarabun', sans-serif;
            color: #1e293b;
            font-size: 0.82rem;
        }

        .input-wrapper {
            position: relative;
        }

        .input-wrapper>i {
            position: absolute;
            left: 20px;
            top: 50%;
            transform: translateY(-50%);
            color: var(--primary-color);
            opacity: 0.5;
        }

        .form-control {
            width: 100%;
            padding: 11px 25px 11px 48px;
            border-radius: 50px;
            border: 2px solid transparent;
            background: #f8fafc;
            font-family: 'Sarabun', 'TH Sarabun New', 'TH Sarabun PS', sans-serif;
            transition: all 0.3s ease;
            font-size: 0.92rem;
            box-sizing: border-box;
        }

        .form-control:focus {
            border-color: rgba(225, 29, 72, 0.3);
            background: white;
            outline: none;
        }

        .form-control[readonly] {
            color: #94a3b8;
        }

        .btn-auth {
            width: 100%;
            padding: 13px;
            border-radius: 50px;
            border: none;
            background: var(--pink-gradient);
            color: white;
            font-weight: 800;
            font-family: 'Trirong', 'Sarabun', sans-serif;
            font-size: 0.95rem;
            cursor: pointer;
            transition: all 0.4s;
            box-shadow: 0 10px 25px rgba(225, 29, 72, 0.3);
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 10px;
            margin-top: 4px;
        }

        .btn-auth:hover {
            transform: translateY(-3px);
            box-shadow: 0 15px 35px rgba(225, 29, 72, 0.4);
        }

        .modern-alert {
            padding: 12px 18px;
            border-radius: 16px;
            margin-bottom: 14px;
            font-size: 0.85rem;
            display: flex;
            align-items: center;
            gap: 10px;
        }

        .modern-alert-error {
            background: #fef2f2;
            color: #991b1b;
            border: 1px solid #fecaca;
        }

        .toggle-password {
            position: absolute;
            right: 15px;
            top: 50%;
            transform: translateY(-50%);
            background: #fff;
            border: 1px solid #e2e8f0;
            border-radius: 50px;
            cursor: pointer;
            color: var(--primary-color);
            padding: 4px 11px;
            font-size: 0.8rem;
            transition: all 0.2s;
            z-index: 2;
        }

        .remember-row {
            display: flex;
            align-items: center;
            gap: 10px;
            margin: 14px 0;
            padding: 10px 14px;
            background: #f8fafc;
            border-radius: 14px;
        }

        .custom-checkbox-label {
            display: flex;
            align-items: center;
            gap: 9px;
            font-size: 0.8rem;
            font-family: 'Trirong', 'Sarabun', sans-serif;
            color: #475569;
            font-weight: 600;
            cursor: pointer;
            line-height: 1.35;
        }

        .custom-checkbox {
            width: 17px;
            height: 17px;
            accent-color: var(--primary-color);
            cursor: pointer;
            flex-shrink: 0;
        }

        /* Right-hand "strict password rules" panel */
        .rules-panel {
            width: 220px;
            flex-shrink: 0;
            background: #fff7f9;
            border: 1px solid #fde3ec;
            border-radius: 18px;
            padding: 16px 18px;
        }

        .rules-panel-title {
            display: flex;
            align-items: center;
            gap: 7px;
            font-size: 0.78rem;
            font-weight: 800;
            color: #1e293b;
            margin-bottom: 12px;
        }

        .rules-panel-title i {
            color: var(--primary-color);
        }

        .pw-strength-bar {
            display: flex;
            gap: 5px;
            margin-bottom: 6px;
        }

        .pw-strength-bar span {
            flex: 1;
            height: 5px;
            border-radius: 3px;
            background: #e2e8f0;
            transition: background-color 0.25s ease;
        }

        .pw-strength-bar span.weak {
            background: #ef4444;
        }

        .pw-strength-bar span.medium {
            background: #f59e0b;
        }

        .pw-strength-bar span.strong {
            background: #10b981;
        }

        .pw-strength-label {
            font-size: 0.68rem;
            font-weight: 700;
            color: #94a3b8;
            margin-bottom: 10px;
        }

        .pw-rules {
            list-style: none;
            margin: 0;
            padding: 0;
            display: flex;
            flex-direction: column;
            gap: 7px;
        }

        .pw-rules li {
            display: flex;
            align-items: flex-start;
            gap: 7px;
            font-size: 0.74rem;
            color: #94a3b8;
            transition: color 0.2s ease;
            line-height: 1.35;
        }

        .pw-rules li i {
            font-size: 0.7rem;
            color: #cbd5e1;
            margin-top: 2px;
            flex-shrink: 0;
            transition: color 0.2s ease;
        }

        .pw-rules li.met {
            color: #166534;
            font-weight: 600;
        }

        .pw-rules li.met i {
            color: #10b981;
        }

        @media (max-width: 640px) {
            .reset-columns {
                flex-direction: column;
            }

            .rules-panel {
                width: 100%;
            }
        }
    </style>
@endsection

@section('content')
    <div class="auth-container">
        <div class="auth-card">
            <div class="auth-card-header">
                <div class="auth-steps">
                    <span class="auth-step is-done"><i class="fas fa-check"></i></span>
                    <span class="auth-step-line is-done"></span>
                    <span class="auth-step is-done"><i class="fas fa-check"></i></span>
                    <span class="auth-step-line is-done"></span>
                    <span class="auth-step is-active">3</span>
                </div>
                <div class="auth-icon-badge"><i class="fas fa-lock"></i></div>
                <h2>สร้างรหัสผ่านใหม่</h2>
                <p>กรุณากำหนดรหัสผ่านใหม่เพื่อเข้าใช้งานระบบ</p>
            </div>

            <div class="auth-card-body">
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

                <form action="{{ route('password.update') }}" method="POST">
                    @csrf
                    <input type="hidden" name="token" value="{{ $token }}">

                    <div class="reset-columns">
                        <div class="reset-fields-col">
                            <div class="form-group">
                                <label class="form-label">อีเมล</label>
                                <div class="input-wrapper">
                                    <i class="fas fa-envelope"></i>
                                    <input type="email" name="email" id="reset_email" class="form-control"
                                        value="{{ $email ?? old('email') }}" readonly required>
                                </div>
                            </div>

                            <div class="form-group">
                                <label class="form-label">รหัสผ่านใหม่</label>
                                <div class="input-wrapper">
                                    <i class="fas fa-key"></i>
                                    <input type="password" name="password" id="password" class="form-control"
                                        placeholder="••••••••" required autofocus oninput="updatePasswordChecklist()">
                                    <button type="button" class="toggle-password"
                                        onclick="togglePasswordVisibility('password', this)" tabindex="-1">
                                        <i class="fas fa-eye"></i>
                                    </button>
                                </div>
                            </div>

                            <div class="form-group">
                                <label class="form-label">ยืนยันรหัสผ่านใหม่</label>
                                <div class="input-wrapper">
                                    <i class="fas fa-shield-alt"></i>
                                    <input type="password" name="password_confirmation" id="password_confirmation"
                                        class="form-control" placeholder="••••••••" required>
                                    <button type="button" class="toggle-password"
                                        onclick="togglePasswordVisibility('password_confirmation', this)" tabindex="-1">
                                        <i class="fas fa-eye"></i>
                                    </button>
                                </div>
                            </div>
                        </div>

                        <div class="rules-panel">
                            <div class="rules-panel-title"><i class="fas fa-shield-halved"></i> เกณฑ์รหัสผ่านที่ปลอดภัย</div>
                            <div class="pw-strength-bar">
                                <span id="pw-strength-seg-1"></span>
                                <span id="pw-strength-seg-2"></span>
                                <span id="pw-strength-seg-3"></span>
                            </div>
                            <div class="pw-strength-label" id="pw-strength-label">ความปลอดภัยของรหัสผ่าน</div>
                            <ul class="pw-rules">
                                <li id="pw-rule-length"><i class="fas fa-check-circle"></i> อย่างน้อย 10 ตัวอักษร</li>
                                <li id="pw-rule-upper"><i class="fas fa-check-circle"></i> ตัวพิมพ์ใหญ่ (A-Z)</li>
                                <li id="pw-rule-lower"><i class="fas fa-check-circle"></i> ตัวพิมพ์เล็ก (a-z)</li>
                                <li id="pw-rule-number"><i class="fas fa-check-circle"></i> ตัวเลข (0-9)</li>
                                <li id="pw-rule-special"><i class="fas fa-check-circle"></i> อักขระพิเศษ (! @ # $)</li>
                                <li id="pw-rule-noemail"><i class="fas fa-check-circle"></i> ไม่ตรงกับอีเมลที่ใช้เข้าสู่ระบบ</li>
                            </ul>
                        </div>
                    </div>

                    <div class="remember-row">
                        <label class="custom-checkbox-label">
                            <input type="checkbox" name="remember" value="1" class="custom-checkbox">
                            จดจำฉันไว้ (เข้าสู่ระบบอัตโนมัติหลังเปลี่ยนรหัสผ่าน)
                        </label>
                    </div>

                    <button type="submit" class="btn-auth">
                        <i class="fas fa-check-circle"></i>
                        ยืนยันการเปลี่ยนรหัสผ่าน
                    </button>
                </form>
            </div>
        </div>
    </div>
@endsection

@section('extra_js')
    <script>
        function togglePasswordVisibility(inputId, btn) {
            const input = document.getElementById(inputId);
            const icon = btn.querySelector('i');
            if (input.type === 'password') {
                input.type = 'text';
                icon.classList.replace('fa-eye', 'fa-eye-slash');
            } else {
                input.type = 'password';
                icon.classList.replace('fa-eye-slash', 'fa-eye');
            }
        }

        // Strict live password-rule checklist (right-hand panel). Mirrors
        // the same rules enforced server-side in
        // AuthController::resetPassword() - a password only accepted here
        // is also accepted there, and vice versa.
        function evaluatePasswordRules(pw, email) {
            const emailLocal = (email || '').split('@')[0].toLowerCase();
            return {
                length: pw.length >= 10,
                upper: /[A-Z]/.test(pw),
                lower: /[a-z]/.test(pw),
                number: /[0-9]/.test(pw),
                special: /[^A-Za-z0-9]/.test(pw),
                noemail: pw.length === 0 || (emailLocal.length === 0) || !pw.toLowerCase().includes(emailLocal),
            };
        }

        function updatePasswordChecklist() {
            const input = document.getElementById('password');
            const emailInput = document.getElementById('reset_email');
            if (!input) return;
            const pw = input.value;
            const rules = evaluatePasswordRules(pw, emailInput ? emailInput.value : '');
            const ruleIds = {
                'pw-rule-length': rules.length,
                'pw-rule-upper': rules.upper,
                'pw-rule-lower': rules.lower,
                'pw-rule-number': rules.number,
                'pw-rule-special': rules.special,
                'pw-rule-noemail': rules.noemail,
            };

            let metCount = 0;
            Object.keys(ruleIds).forEach((id) => {
                const met = ruleIds[id];
                const el = document.getElementById(id);
                if (el) el.classList.toggle('met', met);
                if (met) metCount++;
            });

            const segs = ['pw-strength-seg-1', 'pw-strength-seg-2', 'pw-strength-seg-3']
                .map((id) => document.getElementById(id));
            segs.forEach((seg) => { if (seg) seg.className = ''; });

            const label = document.getElementById('pw-strength-label');
            if (!label) return;

            if (pw.length === 0) {
                label.textContent = 'ความปลอดภัยของรหัสผ่าน';
            } else if (metCount <= 3) {
                if (segs[0]) segs[0].className = 'weak';
                label.textContent = 'รหัสผ่านยังไม่ปลอดภัย';
            } else if (metCount <= 5) {
                if (segs[0]) segs[0].className = 'medium';
                if (segs[1]) segs[1].className = 'medium';
                label.textContent = 'ความปลอดภัยปานกลาง';
            } else {
                segs.forEach((seg) => { if (seg) seg.className = 'strong'; });
                label.textContent = 'รหัสผ่านปลอดภัยดี';
            }
        }

        window.addEventListener('DOMContentLoaded', updatePasswordChecklist);
    </script>
@endsection
