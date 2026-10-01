@extends('layouts.layout')

@section('title', 'ยืนยันตัวตน - Salt & Sodium Smart Monitor')

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
        max-width: 450px;
        border-radius: 32px;
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

    .auth-card-header {
        position: relative;
        background: var(--pink-gradient);
        padding: 24px 32px 18px;
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

    .auth-card-header-icon {
        position: relative;
        width: 46px;
        height: 46px;
        margin: 0 auto 10px;
        border-radius: 50%;
        background: rgba(255, 255, 255, 0.22);
        display: flex;
        align-items: center;
        justify-content: center;
        color: #fff;
        font-size: 1.2rem;
    }

    .auth-card-header h2 {
        position: relative;
        font-weight: 800;
        font-family: 'Kodchasan', 'Sarabun', sans-serif;
        color: #fff;
        margin-bottom: 3px;
        font-size: 1.2rem;
    }

    .auth-card-header p {
        position: relative;
        font-family: 'Sarabun', 'TH Sarabun New', 'TH Sarabun PS', sans-serif;
        color: rgba(255, 255, 255, 0.9);
        font-size: 0.82rem;
        margin: 0;
    }

    .auth-card-body {
        padding: 20px 32px 26px;
    }

    .email-chip {
        display: flex;
        align-items: center;
        justify-content: center;
        gap: 8px;
        margin: 0 auto 14px;
        padding: 8px 16px;
        background: #f8fafc;
        border: 1px solid #eef2f7;
        border-radius: 999px;
        color: #1e293b;
        font-weight: 700;
        font-family: 'Sarabun', 'TH Sarabun New', 'TH Sarabun PS', sans-serif;
        font-size: 0.88rem;
        max-width: 100%;
        overflow: hidden;
    }

    .email-chip i {
        color: var(--primary-color);
        flex-shrink: 0;
    }

    .email-chip span {
        overflow: hidden;
        text-overflow: ellipsis;
        white-space: nowrap;
    }

    .otp-timer-pill {
        display: flex;
        align-items: center;
        justify-content: center;
        gap: 8px;
        margin: 0 auto 16px;
        padding: 8px 18px;
        border-radius: 999px;
        background: #fff1f5;
        color: var(--primary-color);
        font-weight: 700;
        width: fit-content;
        transition: background-color 0.3s ease, color 0.3s ease;
    }

    .otp-timer-pill i {
        font-size: 0.85rem;
    }

    .otp-timer-value {
        font-family: 'Sarabun', sans-serif;
        font-size: 1.05rem;
        font-weight: 800;
        font-variant-numeric: tabular-nums;
        letter-spacing: 0.5px;
    }

    .otp-timer-label {
        font-family: 'Trirong', 'Sarabun', sans-serif;
        font-size: 0.78rem;
        font-weight: 600;
        opacity: 0.85;
    }

    .otp-timer-pill.expired {
        background: #fef2f2;
        color: #ef4444;
    }

    .otp-input-group {
        display: flex;
        justify-content: center;
        gap: 8px;
        margin-bottom: 16px;
    }

    .otp-input-group input {
        width: 42px;
        height: 48px;
        text-align: center;
        font-size: 1.3rem;
        font-weight: 800;
        border-radius: 14px;
        border: 2px solid #e2e8f0;
        background: #f8fafc;
        font-family: 'Sarabun', sans-serif;
        color: #1e293b;
        transition: all 0.2s ease;
    }

    .otp-input-group input:focus {
        border-color: var(--primary-color);
        background: white;
        outline: none;
        box-shadow: 0 0 0 4px rgba(225, 29, 72, 0.12);
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
    }

    .btn-auth:hover {
        transform: translateY(-3px);
        box-shadow: 0 15px 35px rgba(225, 29, 72, 0.4);
    }

    .btn-auth:disabled {
        opacity: 0.5;
        cursor: not-allowed;
        transform: none;
        box-shadow: none;
    }

    .auth-footer {
        text-align: center;
        margin-top: 14px;
        font-size: 0.83rem;
        display: flex;
        flex-direction: column;
        gap: 8px;
    }

    .auth-footer a,
    .resend-link {
        color: var(--primary-color);
        text-decoration: none;
        font-weight: 700;
        background: none;
        border: none;
        font-family: 'Trirong', 'Sarabun', sans-serif;
        font-size: inherit;
        cursor: pointer;
        padding: 0;
    }

    .resend-link:disabled {
        color: #cbd5e1;
        cursor: not-allowed;
    }

    .auth-footer-divider {
        border: none;
        border-top: 1px solid #f1f5f9;
        margin: 4px 0;
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

    .modern-alert-success {
        background: #f0fdf4;
        color: #166534;
        border: 1px solid #bbf7d0;
    }

    .modern-alert-error {
        background: #fef2f2;
        color: #991b1b;
        border: 1px solid #fecaca;
    }
</style>
@endsection

@section('content')
    <div class="auth-container">
        <div class="auth-card">
            <div class="auth-card-header">
                <div class="auth-card-header-icon"><i class="fas fa-shield-halved"></i></div>
                <h2>ยืนยันตัวตนอีกขั้น</h2>
                <p>เราส่งรหัส 6 หลักไปที่อีเมลของคุณแล้ว</p>
            </div>

            <div class="auth-card-body">
                <div class="email-chip"><i class="fas fa-envelope"></i> <span>{{ $email }}</span></div>

                <div class="otp-timer-pill" id="otpTimer">
                    <i class="fas fa-clock"></i>
                    <span class="otp-timer-value" id="otpTimerValue">05:00</span>
                    <span class="otp-timer-label" id="otpTimerLabel">จนกว่ารหัสจะหมดอายุ</span>
                </div>

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

                <form action="{{ route('login.otp.verify') }}" method="POST" id="otpForm">
                    @csrf
                    <input type="hidden" name="otp" id="otpHidden">
                    <div class="otp-input-group">
                        @for ($i = 0; $i < 6; $i++)
                            <input type="text" inputmode="numeric" pattern="[0-9]*" maxlength="1" class="otp-digit"
                                @if ($i === 0) autofocus @endif>
                        @endfor
                    </div>

                    <button type="submit" class="btn-auth" id="otpSubmitBtn">
                        <i class="fas fa-shield-check"></i>
                        ยืนยันและเข้าสู่ระบบ
                    </button>
                </form>

                <div class="auth-footer">
                    <hr class="auth-footer-divider">
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
@endsection

@section('extra_js')
    <script>
        (function () {
            const digits = Array.from(document.querySelectorAll('.otp-digit'));
            const hidden = document.getElementById('otpHidden');

            function syncHidden() {
                hidden.value = digits.map(d => d.value).join('');
            }

            digits.forEach((input, idx) => {
                input.addEventListener('input', () => {
                    input.value = input.value.replace(/[^0-9]/g, '').slice(0, 1);
                    if (input.value && idx < digits.length - 1) {
                        digits[idx + 1].focus();
                    }
                    syncHidden();
                });

                input.addEventListener('keydown', (e) => {
                    if (e.key === 'Backspace' && !input.value && idx > 0) {
                        digits[idx - 1].focus();
                    }
                });

                input.addEventListener('paste', (e) => {
                    const text = (e.clipboardData || window.clipboardData).getData('text').replace(/[^0-9]/g, '');
                    if (!text) return;
                    e.preventDefault();
                    text.slice(0, digits.length).split('').forEach((ch, i) => {
                        if (digits[i]) digits[i].value = ch;
                    });
                    const nextEmpty = digits.findIndex(d => !d.value);
                    (nextEmpty === -1 ? digits[digits.length - 1] : digits[nextEmpty]).focus();
                    syncHidden();
                });
            });

            document.getElementById('otpForm').addEventListener('submit', syncHidden);

            let remaining = {{ (int) $remainingSeconds }};
            const timerValue = document.getElementById('otpTimerValue');
            const timerLabel = document.getElementById('otpTimerLabel');
            const timerWrap = document.getElementById('otpTimer');
            const submitBtn = document.getElementById('otpSubmitBtn');
            const resendBtn = document.getElementById('resendBtn');

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
