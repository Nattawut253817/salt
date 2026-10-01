@extends('layouts.layout')

@section('title', 'Forgot Password - Salt & Sodium Smart Monitor')

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

    /* Card header band - the "หัวการ์ด" for all 3 forgot-password steps */
    .auth-card-header {
        position: relative;
        background: var(--pink-gradient);
        padding: 22px 32px 18px;
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
        margin-bottom: 14px;
    }

    .auth-step {
        width: 22px;
        height: 22px;
        border-radius: 50%;
        display: flex;
        align-items: center;
        justify-content: center;
        font-size: 0.7rem;
        font-weight: 800;
        background: rgba(255, 255, 255, 0.25);
        color: rgba(255, 255, 255, 0.8);
        flex-shrink: 0;
    }

    .auth-step.is-active {
        background: #fff;
        color: #f0618f;
        box-shadow: 0 0 0 4px rgba(255, 255, 255, 0.3);
    }

    .auth-step-line {
        width: 26px;
        height: 2px;
        background: rgba(255, 255, 255, 0.32);
        flex-shrink: 0;
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
        padding: 22px 32px 28px;
    }

    .form-group {
        margin-bottom: 14px;
    }

    .form-label {
        display: block;
        margin-bottom: 8px;
        margin-left: 15px;
        font-weight: 700;
        font-family: 'Kodchasan', 'Sarabun', sans-serif;
        color: #1e293b;
        font-size: 0.85rem;
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

    .auth-footer {
        text-align: center;
        margin-top: 16px;
        font-size: 0.85rem;
    }

    .auth-footer a {
        color: var(--primary-color);
        text-decoration: none;
        font-weight: 700;
        font-family: 'Trirong', 'Sarabun', sans-serif;
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
                <div class="auth-steps">
                    <span class="auth-step is-active">1</span>
                    <span class="auth-step-line"></span>
                    <span class="auth-step">2</span>
                    <span class="auth-step-line"></span>
                    <span class="auth-step">3</span>
                </div>
                <h2>ลืมรหัสผ่าน?</h2>
                <p>กรอกอีเมลของคุณเพื่อรับรหัส OTP สำหรับสร้างรหัสผ่านใหม่</p>
            </div>

            <div class="auth-card-body">
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
                            <input type="email" name="email" class="form-control" placeholder="example@health.go.th"
                                value="{{ old('email') }}" required>
                        </div>
                    </div>

                    <button type="submit" class="btn-auth">
                        <i class="fas fa-paper-plane"></i>
                        ส่งรหัส OTP
                    </button>
                </form>

                <div class="auth-footer">
                    <a href="{{ route('staff') }}"><i class="fas fa-arrow-left"></i> กลับไปหน้าเข้าสู่ระบบ</a>
                </div>
            </div>
        </div>
    </div>
@endsection
