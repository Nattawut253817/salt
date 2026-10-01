@extends('layouts.layout')

@section('title', 'Staff Portal - Salt & Sodium Smart Monitor')
@section('header_title', '')

@section('extra_css')
    <style>
        /* Hide default header */
        .content-header { display: none !important; }

        .auth-container {
            position: relative;
            display: flex;
            justify-content: center;
            align-items: flex-start;
            padding: 20px 20px;
            min-height: 80vh;
            background: #fbfbfd;

            /* --primary-color / --pink-gradient now match this page's rose ->
               crimson theme sitewide (see layouts/layout.blade.php :root), so
               no local override is needed for those here. The submit button
               keeps its own distinct left-to-right gradient though. */
            --pink-gradient-btn: linear-gradient(90deg, #f43f5e 0%, #ec4899 100%);
        }

        .auth-card {
            position: relative;
            z-index: 1;
            display: flex;
            align-items: stretch;
            background: #ffffff;
            width: 100%;
            max-width: 1000px;
            border-radius: 40px;
            box-shadow: 0 30px 60px rgba(0, 0, 0, 0.08);
            border: none;
            overflow: hidden;
            animation: slideUp 0.6s cubic-bezier(0.23, 1, 0.32, 1);
        }

        @keyframes slideUp {
            from { opacity: 0; transform: translateY(40px); }
            to { opacity: 1; transform: translateY(0); }
        }

        /* Left branding panel */
        .auth-side-panel {
            position: relative;
            flex: 0 0 340px;
            padding: 56px 38px 40px;
            background:
                radial-gradient(circle at 15% 8%, rgba(255, 255, 255, 0.35), transparent 45%),
                linear-gradient(180deg, rgba(255, 255, 255, 0.14), rgba(255, 255, 255, 0) 32%),
                var(--pink-gradient);
            color: #fff;
            display: flex;
            flex-direction: column;
            justify-content: flex-start;
            overflow: hidden;
        }

        .auth-side-panel::before,
        .auth-side-panel::after {
            content: '';
            position: absolute;
            border-radius: 50%;
            pointer-events: none;
        }
        .auth-side-panel::before {
            width: 260px;
            height: 260px;
            top: -100px;
            right: -90px;
            background: radial-gradient(circle at 35% 35%, rgba(255, 255, 255, 0.32), rgba(255, 255, 255, 0) 70%);
        }
        .auth-side-panel::after {
            width: 190px;
            height: 190px;
            bottom: -70px;
            left: -60px;
            background: radial-gradient(circle at 40% 40%, rgba(255, 255, 255, 0.22), rgba(255, 255, 255, 0) 70%);
        }

        /* Logo sits on its own white card so it stays legible and balanced
           regardless of the source image's own colors/whitespace, instead of
           floating the raw photo directly on the pink panel. The source
           photo (logo-login.jpg) has a lot of built-in white margin around
           the actual "SSS" mark, so we display a pre-cropped version
           (logo-login-cropped.jpg, tight around the mark, ~2.5:1) and size
           this box to match that ratio - otherwise object-fit:contain
           leaves the mark looking small/off-balance inside the box. */
        .auth-side-logo {
            position: relative;
            z-index: 1;
            width: 100%;
            max-width: 280px;
            aspect-ratio: 2.5;
            margin-bottom: 28px;
            background: #fffafc;
            border-radius: 18px;
            padding: 4px;
            display: flex;
            align-items: center;
            justify-content: center;
            overflow: hidden;
            box-shadow: 0 14px 30px rgba(0, 0, 0, 0.22), 0 2px 6px rgba(0, 0, 0, 0.1);
            transition: transform 0.25s ease, box-shadow 0.25s ease;
        }

        .auth-side-logo:hover {
            transform: translateY(-4px);
            box-shadow: 0 20px 38px rgba(0, 0, 0, 0.26), 0 4px 10px rgba(0, 0, 0, 0.13);
        }

        .auth-side-logo img {
            width: 100%;
            height: 100%;
            object-fit: cover;
            border-radius: 14px;
        }

        .auth-side-title {
            position: relative;
            z-index: 1;
            font-size: 1.5rem;
            font-weight: 800;
            line-height: 1.35;
            margin-bottom: 14px;
            text-shadow: 0 2px 10px rgba(0, 0, 0, 0.18);
        }

        .auth-side-subtitle {
            position: relative;
            z-index: 1;
            font-size: 0.9rem;
            font-weight: 500;
            line-height: 1.7;
            color: rgba(255, 255, 255, 0.92);
            text-shadow: 0 1px 6px rgba(0, 0, 0, 0.12);
        }

        /* Right form panel */
        .auth-main-panel {
            flex: 1;
            min-width: 0;
        }

        /* Section dividers inside the (long) registration form */
        .form-section-title {
            display: flex;
            align-items: center;
            gap: 10px;
            margin: 14px 0 10px;
            font-weight: 800;
            font-family: 'Kodchasan', 'Sarabun', sans-serif;
            font-size: 0.8rem;
            color: var(--primary-color);
            letter-spacing: 0.02em;
        }
        .form-section-title:first-of-type { margin-top: 0; }
        .form-section-title::after {
            content: '';
            flex: 1;
            height: 1px;
            background: linear-gradient(90deg, rgba(225, 29, 72, 0.25), transparent);
        }

        /* Remember-me / forgot-password row */
        .remember-row {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 20px;
            margin-left: 5px;
        }
        .custom-checkbox-label {
            display: flex;
            align-items: center;
            gap: 9px;
            font-size: 0.9rem;
            font-family: 'Trirong', 'Sarabun', sans-serif;
            color: #475569;
            font-weight: 600;
            cursor: pointer;
        }
        .custom-checkbox {
            width: 18px;
            height: 18px;
            accent-color: var(--primary-color);
            cursor: pointer;
        }
        .forgot-link {
            color: var(--primary-color);
            font-size: 0.9rem;
            font-family: 'Trirong', 'Sarabun', sans-serif;
            font-weight: 700;
            text-decoration: none;
            border-bottom: 2px solid transparent;
            transition: border-color 0.2s;
        }
        .forgot-link:hover { border-bottom-color: var(--primary-color); }

        /* Pill Tabs */
        .auth-tabs-wrapper {
            padding: 18px 24px 0;
            display: flex;
            justify-content: center;
        }

        .auth-tabs {
            display: inline-flex;
            background: #f1f5f9;
            padding: 4px;
            border-radius: 12px;
            gap: 4px;
        }

        .auth-tab {
            padding: 9px 26px;
            text-align: center;
            cursor: pointer;
            font-weight: 700;
            font-family: 'Kodchasan', 'Sarabun', sans-serif;
            color: #64748b;
            transition: all 0.3s cubic-bezier(0.175, 0.885, 0.32, 1.275);
            border-radius: 8px;
            font-size: 0.85rem;
            white-space: nowrap;
        }

        .auth-tab.active {
            background: var(--primary-color);
            color: white;
            box-shadow: 0 4px 15px rgba(225, 29, 72, 0.3);
        }

        .auth-form-container {
            padding: 14px 40px 26px;
            position: relative;
        }

        .form-section { display: none; }
        .form-section.active { display: block; animation: fadeIn 0.5s ease; }

        @keyframes fadeIn { from { opacity: 0; } to { opacity: 1; } }

        .form-group { margin-bottom: 12px; } /* Reduced from 20px, then 15px */
        .form-label {
            display: block;
            margin-bottom: 4px; /* Reduced from 8px */
            margin-left: 15px;
            font-weight: 700;
            font-family: 'Kodchasan', 'Sarabun', sans-serif;
            color: #1e293b;
            font-size: 0.85rem;
        }

        .form-row {
            display: grid;
            grid-template-columns: 1fr 1fr 1fr;
            gap: 12px; /* Reduced from 20px, then 15px */
        }

        .form-row-2 {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 12px; /* Reduced from 20px, then 15px */
        }

        /* Live password requirements panel, anchored under the
           password field and shown while it's focused. */
        .password-checklist {
            position: absolute;
            top: calc(100% + 10px);
            left: 0;
            right: 0;
            background: white;
            border: 1px solid #fce7ef;
            border-radius: 16px;
            padding: 14px 18px;
            box-shadow: 0 14px 30px rgba(225, 29, 72, 0.16);
            z-index: 20;
            opacity: 0;
            transform: translateY(-6px);
            pointer-events: none;
            transition: opacity 0.2s ease, transform 0.2s ease;
        }

        .password-checklist.visible {
            opacity: 1;
            transform: translateY(0);
            pointer-events: auto;
        }

        .pw-strength-bar {
            display: flex;
            gap: 5px;
            margin-bottom: 8px;
        }

        .pw-strength-bar span {
            flex: 1;
            height: 5px;
            border-radius: 3px;
            background: #e2e8f0;
            transition: background-color 0.25s ease;
        }

        .pw-strength-bar span.weak { background: #ef4444; }
        .pw-strength-bar span.medium { background: #f59e0b; }
        .pw-strength-bar span.strong { background: #10b981; }

        .pw-strength-label {
            font-size: 0.72rem;
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
            gap: 6px;
        }

        .pw-rules li {
            display: flex;
            align-items: center;
            gap: 8px;
            font-size: 0.78rem;
            color: #94a3b8;
            transition: color 0.2s ease;
        }

        .pw-rules li i {
            font-size: 0.72rem;
            color: #cbd5e1;
            transition: color 0.2s ease;
        }

        .pw-rules li.met {
            color: #10b981;
            font-weight: 600;
        }

        .pw-rules li.met i {
            color: #10b981;
        }

        /* Confirm-password live match feedback */
        .form-control.pw-match { border-color: rgba(16, 185, 129, 0.45); }
        .form-control.pw-mismatch { border-color: rgba(239, 68, 68, 0.45); }

        .pw-match-feedback {
            display: none;
            align-items: center;
            gap: 6px;
            margin: 6px 4px 0;
            font-size: 0.72rem;
            font-weight: 700;
        }

        .pw-match-feedback.match {
            display: flex;
            color: #10b981;
            transition: opacity 0.4s ease;
        }

        .pw-match-feedback.match.fading-out {
            opacity: 0;
        }

        .pw-match-feedback.mismatch {
            display: flex;
            color: #ef4444;
        }

        .input-wrapper { position: relative; }
        .input-wrapper > i {
            position: absolute;
            left: 20px;
            top: 50%;
            transform: translateY(-50%);
            color: var(--primary-color);
            opacity: 0.5;
            z-index: 1;
        }

        /* Thai (+66) country-code badge on the phone field, sitting right
           after the phone icon so the input clearly reads as a Thai
           number rather than the browser's generic US-style tel mask. */
        .input-wrapper .phone-country-code {
            position: absolute;
            left: 44px;
            top: 50%;
            transform: translateY(-50%);
            color: #64748b;
            font-weight: 700;
            font-size: 0.78rem;
            z-index: 1;
            padding-right: 10px;
            border-right: 1px solid #e2e8f0;
        }
        #reg_phone { padding-left: 92px; }

        /* Pill Inputs */
        .form-control {
            width: 100%;
            padding: 10px 25px 10px 50px; /* Reduced vertical padding from 14px */
            border-radius: 50px;
            border: 2px solid transparent;
            background: #f8fafc;
            font-family: 'Trirong', 'Sarabun', sans-serif;
            transition: all 0.3s ease;
            font-size: 0.82rem;
            box-sizing: border-box;
            color: #334155;
        }

        .form-control::placeholder { color: #94a3b8; }

        .form-control:focus {
            border-color: rgba(225, 29, 72, 0.3);
            background: white;
            box-shadow: 0 0 20px rgba(225, 29, 72, 0.1);
            outline: none;
        }

        /* Pill Buttons */
        .btn-auth {
            width: 100%;
            padding: 14px;
            border-radius: 14px;
            border: none;
            background: var(--pink-gradient-btn);
            color: white;
            font-weight: 800;
            font-size: 0.92rem;
            cursor: pointer;
            transition: all 0.4s cubic-bezier(0.175, 0.885, 0.32, 1.275);
            margin-top: 25px;
            box-shadow: 0 10px 25px rgba(225, 29, 72, 0.35);
            font-family: 'Trirong', 'Sarabun', sans-serif;
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 12px;
        }

        .btn-auth:hover {
            transform: translateY(-3px) scale(1.02);
            box-shadow: 0 15px 35px rgba(225, 29, 72, 0.45);
        }

        /* Inline loading state: no spinner icon - instead a solid white
           panel drags in from the left and covers the button completely,
           so the pink is fully gone while we wait on the network. The
           label/icon swap to the brand pink so they stay legible once
           the background underneath them turns white. */
        .btn-auth.is-loading {
            cursor: not-allowed;
            transform: none;
            box-shadow: 0 8px 20px rgba(225, 29, 72, 0.3);
        }

        .btn-auth.is-loading:hover {
            transform: none;
        }

        .btn-fill {
            position: absolute;
            inset: 0;
            background: #ffffff;
            transform: scaleX(0);
            transform-origin: left center;
            transition: transform 0.55s cubic-bezier(0.65, 0, 0.35, 1);
            pointer-events: none;
        }

        .btn-auth.is-loading .btn-fill {
            transform: scaleX(1);
        }

        .btn-auth #login-btn-text,
        .btn-auth #login-btn-arrow {
            position: relative;
            transition: color 0.35s ease 0.15s;
        }

        .btn-auth.is-loading #login-btn-text,
        .btn-auth.is-loading #login-btn-arrow {
            color: var(--primary-color);
        }

        .form-footer {
            margin-top: 35px;
            text-align: center;
            color: #64748b;
            font-family: 'Trirong', 'Sarabun', sans-serif;
            font-size: 0.9rem;
        }

        .form-footer a {
            color: var(--primary-color);
            text-decoration: none;
            font-weight: 800;
            margin-left: 5px;
            border-bottom: 2px solid transparent;
            transition: all 0.3s;
        }

        .form-footer a:hover {
            border-bottom-color: var(--primary-color);
        }

        /* Select styling */
        select.form-control {
            appearance: none;
            background-image: url("data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' width='24' height='24' viewBox='0 0 24 24' fill='none' stroke='%23e11d48' stroke-width='2' stroke-linecap='round' stroke-linejoin='round'%3E%3Cpolyline points='6 9 12 15 18 9'%3E%3C/polyline%3E%3C/svg%3E");
            background-repeat: no-repeat;
            background-position: right 20px center;
            background-size: 16px;
        }

        /* Password toggle button */
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
            padding: 5px 12px;
            font-size: 0.85rem;
            transition: all 0.2s;
            z-index: 2;
        }
        .toggle-password:hover {
            background: #fdf2f8;
            border-color: var(--primary-color);
        }

        /* Modern Alerts */
        .modern-alert {
            padding: 16px 20px;
            border-radius: 20px;
            margin-bottom: 25px;
            font-size: 0.95rem;
            display: flex;
            align-items: center;
            gap: 12px;
            animation: slideDown 0.4s ease;
            transition: opacity 0.4s ease, transform 0.4s ease, margin 0.4s ease, padding 0.4s ease;
        }

        .modern-alert.fading-out {
            opacity: 0;
            transform: translateY(-8px);
            margin-bottom: 0;
            padding-top: 0;
            padding-bottom: 0;
            overflow: hidden;
        }

        @keyframes slideDown { from { opacity: 0; transform: translateY(-10px); } to { opacity: 1; transform: translateY(0); } }

        .modern-alert-success { background: #f0fdf4; color: #166534; border: 1px solid #bbf7d0; }
        .modern-alert-error { background: #fef2f2; color: #991b1b; border: 1px solid #fecaca; }
        .modern-alert-info { background: #eff6ff; color: #1e40af; border: 1px solid #bfdbfe; }

        /* "หรือเข้าใช้งานด้วย" divider between the email/password login and
           the Government SSO button below it. */
        .auth-divider {
            display: flex;
            align-items: center;
            margin: 22px 0 18px;
            color: #94a3b8;
            font-size: 0.78rem;
            font-weight: 600;
        }
        .auth-divider::before,
        .auth-divider::after {
            content: '';
            flex: 1;
            border-bottom: 1px solid #e2e8f0;
        }
        .auth-divider span { padding: 0 14px; white-space: nowrap; }

        /* Government SSO button - deliberately styled apart from the pink
           .btn-auth (dark/official rather than the app's own brand color)
           so it reads as a distinct, trusted sign-in path. UI only for
           now - see showSsoComingSoon(). */
        .btn-sso {
            width: 100%;
            padding: 14px;
            border-radius: 14px;
            border: none;
            background: #0f172a;
            color: white;
            font-weight: 700;
            font-size: 0.88rem;
            cursor: pointer;
            transition: all 0.3s ease;
            font-family: 'Trirong', 'Sarabun', sans-serif;
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 10px;
            box-shadow: 0 8px 20px rgba(15, 23, 42, 0.25);
        }
        .btn-sso i { color: #f5b942; }
        .btn-sso:hover {
            transform: translateY(-2px);
            background: #1e293b;
            box-shadow: 0 12px 28px rgba(15, 23, 42, 0.35);
        }

        @media (max-width: 820px) {
            .auth-card { flex-direction: column; max-width: 550px; }
            .auth-side-panel {
                flex: 0 0 auto;
                flex-direction: row;
                align-items: center;
                justify-content: center;
                gap: 16px;
                padding: 26px 30px;
            }
            .auth-side-logo { margin-bottom: 0; width: 130px; flex-shrink: 0; }
            .auth-side-title { font-size: 1.05rem; margin-bottom: 0; }
            .auth-side-subtitle { display: none; }
        }

        @media (max-width: 600px) {
            .form-row, .form-row-2 { grid-template-columns: 1fr; }
            .auth-card { border-radius: 0; max-height: none; }
            .auth-form-container { padding: 30px 20px; }
        }

        /* Larger tap targets on touch-sized screens (phones/tablets) for
           form inputs, the password-visibility toggle, and the login /
           register pill tabs, per standard ~44px touch-target guidance.
           Desktop (>= the 768px tier below) is untouched. */
        @media (max-width: 768px) {
            .form-control {
                padding-top: 13px;
                padding-bottom: 13px;
            }

            .toggle-password {
                min-width: 42px;
                min-height: 42px;
                padding: 8px 12px;
                display: flex;
                align-items: center;
                justify-content: center;
            }

            .auth-tab {
                padding: 13px 22px;
            }

            .custom-checkbox-label {
                padding: 6px 0;
            }
        }

        .auth-card {
            transition: transform 0.4s cubic-bezier(0.23, 1, 0.32, 1), filter 0.4s ease;
        }


        /* Respect users who've asked for less motion */
        @media (prefers-reduced-motion: reduce) {
            .btn-fill, .btn-spark,
            .auth-card, .btn-auth, .modern-alert { animation: none !important; transition: none !important; }
        }

        /* Small glowing spark that runs left-to-right across the
           register button while it's submitting. */
        .btn-spark {
            position: absolute;
            top: 50%;
            left: 4%;
            width: 12px;
            height: 12px;
            margin-top: -6px;
            border-radius: 50%;
            background: radial-gradient(circle, #ffffff 0%, #ffd1e3 45%, rgba(255, 209, 227, 0) 75%);
            box-shadow: 0 0 10px 4px rgba(255, 255, 255, 0.55), 0 0 18px 6px rgba(225, 29, 72, 0.5);
            opacity: 0;
            pointer-events: none;
        }

        .btn-auth.is-loading .btn-spark {
            animation: sparkRun 1.3s ease-in-out infinite;
        }

        @keyframes sparkRun {
            0%   { left: 4%; opacity: 0; }
            12%  { opacity: 1; }
            88%  { opacity: 1; }
            100% { left: 94%; opacity: 0; }
        }

        /* Ripple feedback on the submit button */
        .btn-auth { position: relative; overflow: hidden; }
        .btn-auth .ripple {
            position: absolute;
            border-radius: 50%;
            background: rgba(255, 255, 255, 0.55);
            transform: scale(0);
            animation: rippleOut 0.6s ease-out;
            pointer-events: none;
        }
        @keyframes rippleOut {
            to { transform: scale(2.5); opacity: 0; }
        }

        /* PDPA consent gate - shown the first time a visitor tries to reach
           the "สมัครสมาชิก" tab (see switchTab()), styled in this page's own
           rose/crimson theme rather than a generic blue so it reads as part
           of the same product instead of a bolted-on legal popup. */
        .pdpa-overlay {
            position: fixed;
            inset: 0;
            background: rgba(15, 23, 42, 0.6);
            backdrop-filter: blur(3px);
            display: flex;
            align-items: center;
            justify-content: center;
            padding: 28px 20px;
            /* .site-header is a sticky element at z-index:11500 (see :root
               above) - this has to clear that or the modal's top gets
               visually sliced off behind the navbar instead of covering the
               whole screen like a proper consent gate. */
            z-index: 12100;
            opacity: 0;
            pointer-events: none;
            transition: opacity 0.25s ease;
        }

        .pdpa-overlay.active {
            opacity: 1;
            pointer-events: auto;
        }

        .pdpa-card {
            position: relative;
            background: #ffffff;
            width: 100%;
            max-width: 520px;
            max-height: min(640px, 88vh);
            display: flex;
            flex-direction: column;
            overflow: hidden;
            border-radius: 24px;
            box-shadow: 0 30px 70px rgba(0, 0, 0, 0.32), 0 0 0 1px rgba(225, 29, 72, 0.06);
            transform: translateY(18px) scale(0.97);
            transition: transform 0.35s cubic-bezier(0.23, 1, 0.32, 1);
        }

        .pdpa-overlay.active .pdpa-card {
            transform: translateY(0) scale(1);
        }

        .pdpa-card::before {
            content: '';
            display: block;
            height: 5px;
            flex-shrink: 0;
            background: var(--pink-gradient);
        }

        .pdpa-close {
            position: absolute;
            top: 16px;
            right: 16px;
            width: 30px;
            height: 30px;
            border-radius: 50%;
            border: none;
            background: rgba(148, 163, 184, 0.12);
            color: #94a3b8;
            font-size: 0.85rem;
            cursor: pointer;
            display: flex;
            align-items: center;
            justify-content: center;
            transition: all 0.2s ease;
        }

        .pdpa-close:hover {
            background: rgba(225, 29, 72, 0.12);
            color: var(--primary-color);
            transform: rotate(90deg);
        }

        .pdpa-header {
            display: flex;
            align-items: center;
            gap: 14px;
            padding: 24px 50px 18px 26px;
            background: linear-gradient(180deg, #fff5f8 0%, #ffffff 100%);
            border-bottom: 1px solid #fde3ec;
        }

        .pdpa-icon {
            flex-shrink: 0;
            width: 48px;
            height: 48px;
            border-radius: 15px;
            background: var(--pink-gradient);
            color: #fff;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 1.2rem;
            box-shadow: 0 8px 18px rgba(225, 29, 72, 0.32);
        }

        .pdpa-title {
            font-family: 'Kodchasan', 'Sarabun', sans-serif;
            font-weight: 800;
            font-size: 1.08rem;
            line-height: 1.35;
            color: #1e293b;
            margin-bottom: 4px;
        }

        .pdpa-subtitle {
            font-size: 0.7rem;
            font-weight: 700;
            letter-spacing: 0.08em;
            color: var(--primary-color);
        }

        .pdpa-body {
            padding: 20px 26px;
            overflow-y: auto;
            font-family: 'Trirong', 'Sarabun', sans-serif;
            color: #334155;
            font-size: 0.85rem;
            line-height: 1.7;
            scrollbar-width: thin;
            scrollbar-color: #f3b6c8 transparent;
        }

        .pdpa-body::-webkit-scrollbar { width: 6px; }
        .pdpa-body::-webkit-scrollbar-track { background: transparent; }
        .pdpa-body::-webkit-scrollbar-thumb {
            background: #f3b6c8;
            border-radius: 10px;
        }
        .pdpa-body::-webkit-scrollbar-thumb:hover { background: var(--primary-color); }

        .pdpa-intro {
            background: #fff5f8;
            border: 1px solid #fde3ec;
            border-radius: 14px;
            padding: 12px 16px;
            margin-bottom: 16px;
            font-size: 0.82rem;
        }

        .pdpa-intro strong { color: var(--primary-color); }

        .pdpa-section { margin-bottom: 14px; }
        .pdpa-section:last-child { margin-bottom: 0; }

        .pdpa-section-title {
            display: flex;
            align-items: center;
            gap: 8px;
            font-family: 'Kodchasan', 'Sarabun', sans-serif;
            font-weight: 800;
            font-size: 0.86rem;
            color: #1e293b;
            margin-bottom: 6px;
        }

        .pdpa-section-title i { color: var(--primary-color); font-size: 0.78rem; }

        .pdpa-section p { margin: 0 0 4px; }
        .pdpa-section p:last-child { margin-bottom: 0; }

        .pdpa-footer {
            display: flex;
            gap: 12px;
            padding: 16px 26px 22px;
            border-top: 1px solid #f1f5f9;
        }

        .pdpa-btn {
            flex: 1;
            padding: 12px;
            border-radius: 12px;
            border: 2px solid transparent;
            font-family: 'Trirong', 'Sarabun', sans-serif;
            font-weight: 800;
            font-size: 0.85rem;
            cursor: pointer;
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 8px;
            transition: all 0.25s ease;
        }

        .pdpa-btn-decline {
            background: #ffffff;
            border-color: #e2e8f0;
            color: #64748b;
        }

        .pdpa-btn-decline:hover {
            background: #f8fafc;
            border-color: #cbd5e1;
        }

        .pdpa-btn-accept {
            background: var(--pink-gradient);
            color: #ffffff;
            box-shadow: 0 10px 22px rgba(225, 29, 72, 0.35);
        }

        .pdpa-btn-accept:hover {
            transform: translateY(-2px);
            box-shadow: 0 14px 28px rgba(225, 29, 72, 0.45);
        }

        @media (max-width: 600px) {
            .pdpa-header, .pdpa-body, .pdpa-footer { padding-left: 18px; padding-right: 18px; }
            .pdpa-footer { flex-direction: column; }
        }
    </style>
@endsection

@section('content')
    <div class="auth-container">
        <div class="auth-card">
            <div class="auth-side-panel">
                <div class="auth-side-logo">
                    <img src="{{ asset('images/logo-login.jpg') }}" alt="Smart Salt & Sodium Monitor Logo">
                </div>
                <div class="auth-side-title">Smart Salt &amp; Sodium Monitor</div>
                <div class="auth-side-subtitle">ระบบเฝ้าระวังติดตามการบริโภคเกลือและโซเดียม เขตสุขภาพที่ 10</div>
            </div>
            <div class="auth-main-panel">
            <div class="auth-tabs-wrapper">
                <div class="auth-tabs">
                    <div class="auth-tab {{ session('success') || !$errors->any() ? 'active' : '' }}"
                        onclick="switchTab('login')">เข้าสู่ระบบ</div>
                    <div class="auth-tab {{ $errors->any() ? 'active' : '' }}"
                        onclick="switchTab('register')">สมัครสมาชิก</div>
                </div>
            </div>

            <div class="auth-form-container">
                <!-- Login Form -->
                <div id="login-section" class="form-section {{ session('success') || !$errors->any() ? 'active' : '' }}">
                    @if (session('success'))
                        <div class="modern-alert modern-alert-success" id="login-success-alert">
                            <i class="fas fa-check-circle"></i> {{ session('success') }}
                        </div>
                    @endif
                    @if (session('error'))
                        <div class="modern-alert modern-alert-error">
                            <i class="fas fa-exclamation-circle"></i> {{ session('error') }}
                        </div>
                    @endif
                    <form action="{{ route('login.post') }}" method="POST" id="login-form" onsubmit="showLoginLoading(event)">
                        @csrf
                        <div class="form-group">
                            <label class="form-label">อีเมล</label>
                            <div class="input-wrapper">
                                <i class="fas fa-envelope"></i>
                                <input type="email" name="email" class="form-control" placeholder="ชื่อผู้ใช้งานของคุณ" value="{{ old('email') }}" required>
                            </div>
                        </div>
                        <div class="form-group">
                            <label class="form-label">รหัสผ่าน</label>
                            <div class="input-wrapper">
                                <i class="fas fa-lock"></i>
                                <input type="password" name="password" id="login_password" class="form-control" placeholder="••••••••" required>
                                <button type="button" class="toggle-password" onclick="togglePassword('login_password', this)" tabindex="-1">
                                    <i class="fas fa-eye"></i>
                                </button>
                            </div>
                        </div>
                        <div class="remember-row">
                            <label class="custom-checkbox-label">
                                <input type="checkbox" class="custom-checkbox" name="remember" value="1" @if (old('remember')) checked @endif> จดจำฉันไว้
                            </label>
                            <a href="{{ route('password.request') }}" class="forgot-link">ลืมรหัสผ่าน?</a>
                        </div>
                        <button type="submit" class="btn-auth" id="login-btn">
                            <span class="btn-fill"></span>
                            <span id="login-btn-text">เข้าใช้งานระบบ</span>
                            <i class="fas fa-right-to-bracket" id="login-btn-arrow"></i>
                        </button>
                    </form>

                    <div class="auth-divider"><span>หรือเข้าใช้งานด้วย</span></div>

                    <div class="modern-alert modern-alert-info" id="sso-coming-soon-alert" style="display: none;">
                        <i class="fas fa-circle-info"></i>
                        <span>ระบบเข้าสู่ระบบด้วย Government SSO อยู่ระหว่างการพัฒนา ขณะนี้กรุณาเข้าสู่ระบบด้วยอีเมลและรหัสผ่านไปก่อนนะครับ</span>
                    </div>

                    <button type="button" class="btn-sso" id="sso-login-btn" onclick="showSsoComingSoon()">
                        <i class="fas fa-shield-halved"></i>
                        <span>เข้าสู่ระบบด้วย Government SSO</span>
                    </button>

                    <div class="form-footer">
                        ยังไม่มีบัญชี? <a href="javascript:void(0)" onclick="switchTab('register')">สร้างบัญชีใหม่</a>
                    </div>
                </div>

                <!-- Registration Form -->
                <div id="register-section" class="form-section {{ $errors->any() ? 'active' : '' }}">
                    @if ($errors->any())
                        <div class="modern-alert modern-alert-error">
                            <i class="fas fa-exclamation-circle"></i>
                            <ul style="margin: 0; padding-left: 20px; list-style: none;">
                                @foreach ($errors->all() as $error)
                                    <li>{{ $error }}</li>
                                @endforeach
                            </ul>
                        </div>
                    @endif
                    <form action="{{ route('register') }}" method="POST" id="register-form" onsubmit="showRegisterLoading(event)">
                        @csrf
                        <div class="form-section-title">ข้อมูลส่วนตัว</div>
                        <!-- Row 1: Identity -->
                        <div class="form-row">
                            <div class="form-group">
                                <label class="form-label">คำนำหน้า</label>
                                <div class="input-wrapper">
                                    <i class="fas fa-address-card"></i>
                                    <select name="prefix" class="form-control" required>
                                        <option value="" disabled {{ old('prefix') ? '' : 'selected' }}>เลือกคำนำหน้า
                                        </option>
                                        <option value="นาย" {{ old('prefix') == 'นาย' ? 'selected' : '' }}>นาย</option>
                                        <option value="นาง" {{ old('prefix') == 'นาง' ? 'selected' : '' }}>นาง</option>
                                        <option value="นางสาว" {{ old('prefix') == 'นางสาว' ? 'selected' : '' }}>นางสาว
                                        </option>
                                    </select>
                                </div>
                            </div>
                            <div class="form-group">
                                <label class="form-label">ชื่อ</label>
                                <div class="input-wrapper">
                                    <i class="fas fa-user"></i>
                                    <input type="text" name="User_firstname" class="form-control" placeholder="ชื่อจริง"
                                        value="{{ old('User_firstname') }}" required>
                                </div>
                            </div>
                            <div class="form-group">
                                <label class="form-label">นามสกุล</label>
                                <div class="input-wrapper">
                                    <i class="fas fa-tag"></i>
                                    <input type="text" name="User_lastname" class="form-control" placeholder="นามสกุล"
                                        value="{{ old('User_lastname') }}" required>
                                </div>
                            </div>
                        </div>

                        <!-- Row 1b: Contact number -->
                        <div class="form-group">
                            <label class="form-label">เบอร์โทรศัพท์</label>
                            <div class="input-wrapper">
                                <i class="fas fa-phone"></i>
                                <span class="phone-country-code">+66</span>
                                <input type="tel" name="phone" id="reg_phone" class="form-control"
                                    placeholder="08X-XXX-XXXX" inputmode="numeric" autocomplete="tel-national"
                                    pattern="0[0-9]{1,2}-[0-9]{3}-[0-9]{3,4}" maxlength="12"
                                    oninput="formatThaiPhone(this)"
                                    title="กรอกเบอร์โทรศัพท์ไทย 9-10 หลัก เช่น 081-234-5678"
                                    value="{{ old('phone') }}">
                            </div>
                        </div>

                        <div class="form-section-title">บัญชีผู้ใช้งาน</div>
                        <!-- Row 2a: Email on its own full-width row -->
                        <div class="form-group">
                            <label class="form-label">อีเมล</label>
                            <div class="input-wrapper">
                                <i class="fas fa-envelope"></i>
                                <input type="email" name="email" class="form-control" placeholder="example@health.go.th"
                                    value="{{ old('email') }}" required>
                            </div>
                        </div>
                        <!-- Row 2b: Password paired with its confirmation -->
                        <div class="form-row-2">
                            <div class="form-group">
                                <label class="form-label">รหัสผ่าน</label>
                                <div class="input-wrapper">
                                    <i class="fas fa-key"></i>
                                    <input type="password" name="password" id="reg_password" class="form-control" placeholder="รหัสผ่านไม่น้อยกว่า 8 ตัวอักษร"
                                        required autocomplete="new-password"
                                        oninput="updatePasswordChecklist()" onfocus="showPasswordChecklist()" onblur="hidePasswordChecklist()">
                                    <button type="button" class="toggle-password" onclick="togglePassword('reg_password', this)" tabindex="-1">
                                        <i class="fas fa-eye"></i>
                                    </button>
                                    <div class="password-checklist" id="password-checklist">
                                        <div class="pw-strength-bar">
                                            <span id="pw-strength-seg-1"></span>
                                            <span id="pw-strength-seg-2"></span>
                                            <span id="pw-strength-seg-3"></span>
                                        </div>
                                        <div class="pw-strength-label" id="pw-strength-label">ความปลอดภัยของรหัสผ่าน</div>
                                        <ul class="pw-rules">
                                            <li id="pw-rule-length"><i class="fas fa-check-circle"></i> อย่างน้อย 8 ตัวอักษร</li>
                                            <li id="pw-rule-upper"><i class="fas fa-check-circle"></i> ตัวพิมพ์ใหญ่ (A-Z)</li>
                                            <li id="pw-rule-lower"><i class="fas fa-check-circle"></i> ตัวพิมพ์เล็ก (a-z)</li>
                                            <li id="pw-rule-number"><i class="fas fa-check-circle"></i> ตัวเลข (0-9)</li>
                                            <li id="pw-rule-special"><i class="fas fa-check-circle"></i> อักขระพิเศษ (เช่น ! @ # $)</li>
                                        </ul>
                                    </div>
                                </div>
                            </div>
                            <div class="form-group">
                                <label class="form-label">ยืนยันรหัสผ่าน</label>
                                <div class="input-wrapper">
                                    <i class="fas fa-shield-alt"></i>
                                    <input type="password" name="password_confirmation" id="reg_password_confirm" class="form-control"
                                        placeholder="รหัสผ่านไม่น้อยกว่า 8 ตัวอักษร" required autocomplete="new-password"
                                        oninput="checkPasswordMatch()">
                                    <button type="button" class="toggle-password" onclick="togglePassword('reg_password_confirm', this)" tabindex="-1">
                                        <i class="fas fa-eye"></i>
                                    </button>
                                </div>
                                <div class="pw-match-feedback" id="pw-match-feedback"></div>
                            </div>
                        </div>

                        <div class="form-section-title">ข้อมูลหน่วยงาน</div>
                        <!-- Row 3: Position, Rank -->
                        <div class="form-row-2">
                            <div class="form-group">
                                <label class="form-label">ตำแหน่ง</label>
                                <div class="input-wrapper">
                                    <i class="fas fa-user-tie"></i>
                                    <input type="text" name="User_position" class="form-control" placeholder="นวก.สธ"
                                        value="{{ old('User_position') }}" required>
                                </div>
                            </div>
                            <div class="form-group">
                                <label class="form-label">ระดับสังกัด (Rank)</label>
                                <div class="input-wrapper">
                                    <i class="fas fa-layer-group"></i>
                                    <select id="rank_select" name="User_rank_id" class="form-control"
                                        onchange="toggleRankFields()" required>
                                        <option value="" disabled {{ old('User_rank_id') ? '' : 'selected' }}>เลือกระดับ
                                        </option>
                                        <option value="1" {{ old('User_rank_id') == '1' ? 'selected' : '' }}>สคร.</option>
                                        <option value="2" {{ old('User_rank_id') == '2' ? 'selected' : '' }}>สสจ.</option>
                                        <option value="3" {{ old('User_rank_id') == '3' ? 'selected' : '' }}>สสอ.</option>
                                        <option value="5" {{ old('User_rank_id') == '5' ? 'selected' : '' }}>รพ.</option>
                                        <option value="4" {{ old('User_rank_id') == '4' ? 'selected' : '' }}>รพ.สต.</option>

                                    </select>
                                </div>
                            </div>
                        </div>

                        <!-- Row 4: Location & Institution -->
                        <div class="form-row">
                            <div class="form-group" id="group_province">
                                <label class="form-label">จังหวัด</label>
                                <div class="input-wrapper">
                                    <i class="fas fa-map-marker-alt"></i>
                                    <select id="province_select" name="Province_id" class="form-control"
                                        onchange="fetchDistricts()" required>
                                        <option value="" disabled {{ old('Province_id') ? '' : 'selected' }}>เลือกจังหวัด
                                        </option>
                                        @foreach ($provinces as $province)
                                            <option value="{{ $province->province_id }}"
                                                {{ old('Province_id') == $province->province_id ? 'selected' : '' }}>
                                                {{ $province->province_name }}
                                            </option>
                                        @endforeach
                                    </select>
                                </div>
                            </div>
                            <div class="form-group" id="group_district">
                                <label class="form-label">อำเภอ</label>
                                <div class="input-wrapper">
                                    <i class="fas fa-map"></i>
                                    <select id="district_select" name="District_id" class="form-control" onchange="handleDistrictChange()">
                                        <option value="" disabled {{ old('District_id') ? '' : 'selected' }}>เลือกอำเภอ
                                        </option>
                                    </select>
                                </div>
                            </div>
                            <div class="form-group" id="group_hospital" style="display: none;">
                                <label class="form-label">โรงพยาบาล</label>
                                <div class="input-wrapper">
                                    <i class="fas fa-hospital"></i>
                                    <select id="hospital_select" name="Hospital_id" class="form-control">
                                        <option value="" disabled selected>เลือกโรงพยาบาล</option>
                                    </select>
                                </div>
                            </div>
                            <div class="form-group" id="group_subdistrict_hospital" style="display: none;">
                                <label class="form-label">ชื่อหน่วยงาน รพ.สต.</label>
                                <div class="input-wrapper">
                                    <i class="fas fa-clinic-medical"></i>
                                    <select id="subdistrict_hospital_select" name="Subdistrict_Hospital_id" class="form-control" onchange="updateAffiliation()">
                                        <option value="" disabled selected>เลือก รพ.สต.</option>
                                    </select>
                                </div>
                            </div>
                            <div class="form-group" id="group_affiliation" style="display: none;">
                                <label class="form-label">สังกัด</label>
                                <div class="input-wrapper">
                                    <i class="fas fa-sitemap"></i>
                                    <input type="text" id="affiliation_input" class="form-control" placeholder="สังกัด" readonly>
                                </div>
                            </div>
                        </div>

                        <button type="submit" class="btn-auth" id="register-btn">
                            <span class="btn-spark"></span>
                            <i class="fas fa-user-plus"></i>
                            ยืนยันการสมัครสมาชิก
                        </button>
                    </form>
                    <div class="form-footer">
                        มีบัญชีอยู่แล้ว? <a href="javascript:void(0)" onclick="switchTab('login')">เข้าสู่ระบบ</a>
                    </div>
                </div>
            </div>
            </div>
        </div>
    </div>

    <!-- PDPA consent gate for "สมัครสมาชิก" - see switchTab() below -->
    <div class="pdpa-overlay" id="pdpa-overlay">
        <div class="pdpa-card">
            <button type="button" class="pdpa-close" onclick="declinePdpa()" aria-label="ปิด" title="ปิด">
                <i class="fas fa-xmark"></i>
            </button>
            <div class="pdpa-header">
                <div class="pdpa-icon"><i class="fas fa-shield-halved"></i></div>
                <div>
                    <div class="pdpa-title">นโยบายคุ้มครองข้อมูลส่วนบุคคล</div>
                    <div class="pdpa-subtitle">PRIVACY POLICY (PDPA)</div>
                </div>
            </div>
            <div class="pdpa-body">
                <div class="pdpa-intro">
                    ระบบเฝ้าระวังติดตามการบริโภคเกลือและโซเดียม เขตสุขภาพที่ 10 <br>
                    สำนักงานป้องกันควบคุมโรคที่ 10 จังหวัดอุบลราชธานี โดย <strong>กลุ่มโรคไม่ติดต่อ</strong>
                    ในฐานะผู้ดูแลข้อมูล ได้ตระหนักถึงความสำคัญของการคุ้มครองข้อมูลส่วนบุคคลของท่าน
                    เพื่อให้สอดคล้องกับพระราชบัญญัติคุ้มครองข้อมูลส่วนบุคคล พ.ศ. 2562 (PDPA)
                    จึงได้กำหนดนโยบายนี้ไว้ดังนี้
                </div>

                <div class="pdpa-section">
                    <div class="pdpa-section-title"><i class="fas fa-bullseye"></i> 1. วัตถุประสงค์ของการเก็บรวบรวม ใช้ และเปิดเผยข้อมูลส่วนบุคคล</div>
                    <p>1.1 <strong>เพื่อการยืนยันตัวตน:</strong> ใช้สำหรับตรวจสอบสิทธิ์และยืนยันตัวตนในการเข้าใช้งานระบบแบบประเมิน เพื่อความปลอดภัยของข้อมูล</p>
                    <p>1.2 <strong>เพื่อการดำเนินงานตามภารกิจ:</strong> เพื่อใช้ในการติดตามและประเมินผลการขับเคลื่อนกลไกยุติปัญหาเอดส์ของสถานบริการสาธารณสุขในพื้นที่รับผิดชอบ</p>
                    <p>1.3 <strong>เพื่อพัฒนาการบริการภาครัฐ:</strong> เพื่อเก็บสถิติการใช้งาน นำไปวิเคราะห์และปรับปรุงประสิทธิภาพการทำงานของระบบให้ดียิ่งขึ้น</p>
                </div>

                <div class="pdpa-section">
                    <div class="pdpa-section-title"><i class="fas fa-database"></i> 2. ข้อมูลส่วนบุคคลที่เก็บรวบรวม</div>
                    <p>ระบบจะจัดเก็บข้อมูลเท่าที่จำเป็นจากการลงทะเบียนของท่าน ดังนี้</p>
                    <p>2.1 <strong>ข้อมูลส่วนบุคคลและสังกัด:</strong> ชื่อ-นามสกุล (ภาษาไทย), อีเมล, ตำแหน่ง, หน่วยงานที่สังกัด, จังหวัดและอำเภอ</p>
                    <p>2.2 <strong>ข้อมูลการใช้งาน:</strong> สิทธิ์การเข้าใช้งานระบบ วันเวลาที่เข้าถึง และประวัติการบันทึกข้อมูล</p>
                </div>

                <div class="pdpa-section">
                    <div class="pdpa-section-title"><i class="fas fa-clock"></i> 3. ระยะเวลาในการเก็บรักษาข้อมูล</div>
                    <p><strong>กลุ่มโรคไม่ติดต่อ</strong> จะเก็บรักษาข้อมูลส่วนบุคคลของท่านไว้ตราบเท่าที่ท่านยังเป็นผู้ใช้งานระบบ หรือตามระยะเวลาที่จำเป็นในการดำเนินงานตามวัตถุประสงค์ของระบบประเมินฯ</p>
                    <p>หากท่านไม่ต้องการใช้งานระบบแล้ว หรือมีการเปลี่ยนแปลงเจ้าหน้าที่ผู้รับผิดชอบ สามารถติดต่อผู้ดูแลข้อมูลเพื่อทำการยกเลิกข้อมูลออกจากฐานข้อมูลได้</p>
                </div>

                <div class="pdpa-section">
                    <div class="pdpa-section-title"><i class="fas fa-address-book"></i> 4. ติดต่อเรา</div>
                    <p><strong>ชื่อหน่วยงาน:</strong> กลุ่มโรคไม่ติดต่อ สำนักงานป้องกันควบคุมโรคที่ 10 จังหวัดอุบลราชธานี</p>
                    <p><strong>ที่อยู่:</strong> สำนักงานป้องกันควบคุมโรคที่ 10 จ.อุบลราชธานี 220 ถ.พรหมเทพ ต.ในเมือง อ.เมือง จ.อุบลราชธานี 34000</p>
                    <p><strong>หมายเลขโทรศัพท์:</strong> 0 4524 2226</p>
                </div>
            </div>
            <div class="pdpa-footer">
                <button type="button" class="pdpa-btn pdpa-btn-decline" onclick="declinePdpa()">ไม่ยอมรับ</button>
                <button type="button" class="pdpa-btn pdpa-btn-accept" onclick="acceptPdpa()"><i class="fas fa-check"></i> เข้าใจและยอมรับ</button>
            </div>
        </div>
    </div>

@endsection

@section('extra_js')
    <script>
        function showLoginLoading(event) {
            event.preventDefault(); // Prevent immediate submission

            const btn = document.getElementById('login-btn');
            const form = document.getElementById('login-form');
            const textEl = document.getElementById('login-btn-text');
            const arrowEl = document.getElementById('login-btn-arrow');

            // Swap the label to "กำลังตรวจสอบ..." and the arrow icon to a
            // spinner while the white fill wipes in, so it reads as an
            // active checking state rather than a static button that
            // merely changed color. No need to revert either on failure -
            // a failed login is a normal full-page reload (validation
            // errors), which resets this markup back to its Blade default.
            btn.classList.add('is-loading');
            btn.disabled = true;
            if (textEl) {
                textEl.textContent = 'กำลังตรวจสอบ...';
            }
            if (arrowEl) {
                arrowEl.classList.remove('fa-right-to-bracket');
                arrowEl.classList.add('fa-circle-notch', 'fa-spin');
            }

            // Submit on the very next frame so we hand off to the network
            // as soon as the spinner has painted, rather than waiting on
            // an arbitrary timeout.
            requestAnimationFrame(() => requestAnimationFrame(() => form.submit()));
        }

        // Small ripple flourish on click for tactile feedback
        document.getElementById('login-btn').addEventListener('click', function (e) {
            const rect = this.getBoundingClientRect();
            const ripple = document.createElement('span');
            const size = Math.max(rect.width, rect.height);
            ripple.className = 'ripple';
            ripple.style.width = ripple.style.height = size + 'px';
            ripple.style.left = (e.clientX - rect.left - size / 2) + 'px';
            ripple.style.top = (e.clientY - rect.top - size / 2) + 'px';
            this.appendChild(ripple);
            ripple.addEventListener('animationend', () => ripple.remove());
        });

        // The "สมัครสมาชิก" tab is gated behind the PDPA consent modal: every
        // time a visitor switches INTO the register tab from elsewhere, the
        // modal opens first and the actual tab switch only happens once they
        // click "เข้าใจและยอมรับ" (declining sends them back to the login tab
        // instead). It intentionally does not "remember" acceptance across
        // clicks within the page, so re-opening the register tab later in
        // the same visit asks again rather than silently skipping the gate.
        function showPdpaModal() {
            const overlay = document.getElementById('pdpa-overlay');
            if (overlay) overlay.classList.add('active');
        }

        function hidePdpaModal() {
            const overlay = document.getElementById('pdpa-overlay');
            if (overlay) overlay.classList.remove('active');
        }

        function acceptPdpa() {
            hidePdpaModal();
            performTabSwitch('register');
        }

        function declinePdpa() {
            hidePdpaModal();
            performTabSwitch('login');
        }

        function switchTab(tab) {
            const registerSection = document.getElementById('register-section');
            const alreadyOnRegister = registerSection && registerSection.classList.contains('active');

            if (tab === 'register' && !alreadyOnRegister) {
                showPdpaModal();
                return;
            }
            performTabSwitch(tab);
        }

        function performTabSwitch(tab) {
            // Elements
            const loginSection = document.getElementById('login-section');
            const registerSection = document.getElementById('register-section');
            const tabs = document.querySelectorAll('.auth-tab');

            // Toggle sections
            if (tab === 'login') {
                loginSection.classList.add('active');
                registerSection.classList.remove('active');
                tabs[0].classList.add('active');
                tabs[1].classList.remove('active');
            } else {
                loginSection.classList.remove('active');
                registerSection.classList.add('active');
                tabs[0].classList.remove('active');
                tabs[1].classList.add('active');
            }
        }

        async function fetchDistricts() {
            const provinceId = document.getElementById('province_select').value;
            const districtSelect = document.getElementById('district_select');

            if (!provinceId) return;

            // Clear current options
            districtSelect.innerHTML = '<option value="" disabled selected>กำลังโหลด...</option>';

            try {
                const response = await fetch("{{ route('get-districts', ['province_id' => ':id']) }}".replace(':id', provinceId));
                const districts = await response.json();

                districtSelect.innerHTML = '<option value="" disabled selected>เลือกอำเภอ</option>';
                districts.forEach(dist => {
                    const option = document.createElement('option');
                    option.value = dist.district_id;
                    option.textContent = dist.district_name;
                    districtSelect.appendChild(option);
                });

                // Auto-select old value if exists
                const oldDistrict = "{{ old('District_id') }}";
                if (oldDistrict) {
                    districtSelect.value = oldDistrict;
                    if (document.getElementById('rank_select').value === '4') {
                        fetchSubdistrictHospitals();
                    } else if (document.getElementById('rank_select').value === '5') {
                        fetchHospitals();
                    }
                }
            } catch (error) {
                console.error('Error fetching districts:', error);
                districtSelect.innerHTML = '<option value="" disabled selected>เกิดข้อผิดพลาด</option>';
            }
        }

        function handleDistrictChange() {
            const rank = document.getElementById('rank_select').value;
            if (rank === '4') {
                fetchSubdistrictHospitals(); // Fetch all in district
            } else if (rank === '5') {
                fetchHospitals();
            }
        }

        async function fetchHospitals() {
            const provinceId = document.getElementById('province_select').value;
            const districtId = document.getElementById('district_select').value;
            const hospitalSelect = document.getElementById('hospital_select');

            if (!provinceId || !districtId) return;

            // Clear current options
            hospitalSelect.innerHTML = '<option value="" disabled selected>กำลังโหลด...</option>';

            try {
                const response = await fetch(`/get-hospitals/${provinceId}/${districtId}`);
                const hospitals = await response.json();

                hospitalSelect.innerHTML = '<option value="" disabled selected>เลือกโรงพยาบาล</option>';
                hospitals.forEach(hospital => {
                    const option = document.createElement('option');
                    option.value = hospital.hos_id;
                    option.textContent = hospital.hos_name;
                    hospitalSelect.appendChild(option);
                });

                // Auto-select old value if exists
                const oldHospital = "{{ old('Hospital_id') }}";
                if (oldHospital) {
                    hospitalSelect.value = oldHospital;
                }
            } catch (error) {
                console.error('Error fetching hospitals:', error);
                hospitalSelect.innerHTML = '<option value="" disabled selected>เกิดข้อผิดพลาด</option>';
            }
        }

        function toggleRankFields() {
            const rank = document.getElementById('rank_select').value;
            const groupProvince = document.getElementById('group_province');
            const groupDistrict = document.getElementById('group_district');
            const groupHospital = document.getElementById('group_hospital');
            const groupSubdistrictHospital = document.getElementById('group_subdistrict_hospital');
            const groupAffiliation = document.getElementById('group_affiliation');
            const provinceSelect = document.getElementById('province_select');
            const districtSelect = document.getElementById('district_select');

            // Reset visibility
            groupProvince.style.display = 'block';
            groupDistrict.style.display = 'none';
            groupHospital.style.display = 'none';
            groupSubdistrictHospital.style.display = 'none';
            groupAffiliation.style.display = 'none';

            // Reset state
            provinceSelect.disabled = false;
            groupProvince.style.pointerEvents = 'auto';
            groupProvince.style.opacity = '1';

            if (rank === '1') { // สคร.
                provinceSelect.value = '34';
                groupProvince.style.pointerEvents = 'none';
                groupProvince.style.opacity = '0.7';
            } else if (rank === '2') { // สสจ.
                // All provinces allowed, no district
            } else if (rank === '3') { // สสอ.
                groupDistrict.style.display = 'block';
            } else if (rank === '4') { // รพ.สต
                groupDistrict.style.display = 'block';
                groupSubdistrictHospital.style.display = 'block';
                groupAffiliation.style.display = 'block';
            } else if (rank === '5') { // รพ.
                groupDistrict.style.display = 'block';
                groupHospital.style.display = 'block';
            }

            if (provinceSelect.value) {
                fetchDistricts();
            }
        }


        let currentHospitals = [];

        async function fetchSubdistrictHospitals() {
            const provinceId = document.getElementById('province_select').value;
            const districtId = document.getElementById('district_select').value;
            const shSelect = document.getElementById('subdistrict_hospital_select');
            const affInput = document.getElementById('affiliation_input');

            if (!provinceId || !districtId) return;

            // Clear current options
            shSelect.innerHTML = '<option value="" disabled selected>กำลังโหลด...</option>';
            affInput.value = '';

            try {
                let url = `/get-subdistrict-hospitals/${provinceId}/${districtId}`;

                const response = await fetch(url);
                currentHospitals = await response.json();

                shSelect.innerHTML = '<option value="" disabled selected>เลือก รพ.สต.</option>';
                currentHospitals.forEach(h => {
                    const option = document.createElement('option');
                    option.value = h.sh_id;
                    option.textContent = h.sh_name;
                    shSelect.appendChild(option);
                });

                // Auto-select old value if exists
                const oldSH = "{{ old('Subdistrict_Hospital_id') }}";
                if (oldSH) {
                    shSelect.value = oldSH;
                    updateAffiliation();
                }
            } catch (error) {
                console.error('Error fetching subdistrict hospitals:', error);
                shSelect.innerHTML = '<option value="" disabled selected>เกิดข้อผิดพลาด</option>';
            }
        }

        function updateAffiliation() {
            const shId = document.getElementById('subdistrict_hospital_select').value;
            const affInput = document.getElementById('affiliation_input');
            const hospital = currentHospitals.find(h => h.sh_id == shId);

            if (hospital) {
                affInput.value = hospital.affiliation || '-';
            } else {
                affInput.value = '';
            }
        }

        // Live password requirements checklist for the registration form
        function evaluatePasswordRules(pw) {
            return {
                length: pw.length >= 8,
                upper: /[A-Z]/.test(pw),
                lower: /[a-z]/.test(pw),
                number: /[0-9]/.test(pw),
                special: /[^A-Za-z0-9]/.test(pw),
            };
        }

        function updatePasswordChecklist() {
            const input = document.getElementById('reg_password');
            if (!input) return;
            const pw = input.value;
            const rules = evaluatePasswordRules(pw);
            const ruleIds = {
                'pw-rule-length': rules.length,
                'pw-rule-upper': rules.upper,
                'pw-rule-lower': rules.lower,
                'pw-rule-number': rules.number,
                'pw-rule-special': rules.special,
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
            } else if (metCount <= 2) {
                if (segs[0]) segs[0].className = 'weak';
                label.textContent = 'รหัสผ่านยังไม่ปลอดภัย';
            } else if (metCount <= 4) {
                if (segs[0]) segs[0].className = 'medium';
                if (segs[1]) segs[1].className = 'medium';
                label.textContent = 'ความปลอดภัยปานกลาง';
            } else {
                segs.forEach((seg) => { if (seg) seg.className = 'strong'; });
                label.textContent = 'รหัสผ่านปลอดภัยดี';
            }

            // Password itself changed - re-validate the confirmation too.
            checkPasswordMatch();
        }

        function showPasswordChecklist() {
            const panel = document.getElementById('password-checklist');
            if (!panel) return;
            panel.classList.add('visible');
            updatePasswordChecklist();
        }

        function hidePasswordChecklist() {
            const panel = document.getElementById('password-checklist');
            if (panel) panel.classList.remove('visible');
        }

        // Confirm-password field: live "does it match?" feedback, kept in
        // sync whenever either the password or its confirmation changes.
        let pwMatchHideTimer = null;

        function checkPasswordMatch() {
            const pw = document.getElementById('reg_password');
            const confirmInput = document.getElementById('reg_password_confirm');
            const feedback = document.getElementById('pw-match-feedback');
            if (!pw || !confirmInput || !feedback) return;

            confirmInput.classList.remove('pw-match', 'pw-mismatch');
            feedback.classList.remove('match', 'mismatch', 'fading-out');
            clearTimeout(pwMatchHideTimer);

            if (confirmInput.value.length === 0) {
                feedback.innerHTML = '';
                return;
            }

            if (pw.value === confirmInput.value) {
                confirmInput.classList.add('pw-match');
                feedback.classList.add('match');
                feedback.innerHTML = '<i class="fas fa-check-circle"></i> รหัสผ่านตรงกัน';

                // Hold the confirmation for 5s, then fade it out - if the
                // user keeps editing before that, restart the timer below.
                pwMatchHideTimer = setTimeout(() => {
                    feedback.classList.add('fading-out');
                }, 5000);
            } else {
                confirmInput.classList.add('pw-mismatch');
                feedback.classList.add('mismatch');
                feedback.innerHTML = '<i class="fas fa-times-circle"></i> รหัสผ่านไม่ตรงกัน';
            }
        }

        function showRegisterLoading(event) {
            event.preventDefault(); // Prevent immediate submission

            const btn = document.getElementById('register-btn');
            const form = document.getElementById('register-form');

            btn.classList.add('is-loading');
            btn.disabled = true;

            requestAnimationFrame(() => requestAnimationFrame(() => form.submit()));
        }

        // Government SSO button is UI-only for now (no provider wired up
        // yet) - clicking it just surfaces this notice instead of
        // pretending to sign the person in. Swap this for a real
        // redirect (e.g. window.location.href = '/sso/redirect')
        // once the actual SSO integration exists.
        function showSsoComingSoon() {
            const alertBox = document.getElementById('sso-coming-soon-alert');
            if (!alertBox) return;
            alertBox.style.display = 'flex';
            alertBox.classList.remove('fading-out');
            clearTimeout(window.__ssoAlertTimer);
            window.__ssoAlertTimer = setTimeout(() => {
                alertBox.classList.add('fading-out');
                setTimeout(() => { alertBox.style.display = 'none'; }, 400);
            }, 5000);
        }

        // Live-format a Thai phone number as the person types: strips
        // everything but digits, caps at 10 (the longest Thai numbers -
        // mobile - run), and groups them 3-3-4 (081-234-5678) which is
        // the format used on Thai government sites and on the เบอร์โทรศัพท์
        // field itself elsewhere in the admin panel. Keeps the caret at
        // the end, which is fine for a field this short and typed in one
        // pass rather than edited in the middle.
        function formatThaiPhone(input) {
            const digits = input.value.replace(/\D/g, '').slice(0, 10);
            let formatted = digits;
            if (digits.length > 6) {
                formatted = digits.slice(0, 3) + '-' + digits.slice(3, 6) + '-' + digits.slice(6);
            } else if (digits.length > 3) {
                formatted = digits.slice(0, 3) + '-' + digits.slice(3);
            }
            input.value = formatted;
        }

        // Toggle password visibility
        function togglePassword(inputId, btn) {
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

        // Initialize display
        window.addEventListener('DOMContentLoaded', () => {
            if (document.getElementById('rank_select')) {
                toggleRankFields();
            }

            // Success banner (e.g. "registered, please log in") holds for
            // 5s, then fades out on its own.
            const successAlert = document.getElementById('login-success-alert');
            if (successAlert) {
                setTimeout(() => {
                    successAlert.classList.add('fading-out');
                    successAlert.addEventListener('transitionend', () => successAlert.remove(), { once: true });
                }, 5000);
            }

            // A failed registration submit re-renders this page with the
            // register tab already active (server-side, via $errors->any())
            // instead of going through switchTab() - so the PDPA gate above
            // would otherwise be skipped entirely on that round trip.
            const registerSection = document.getElementById('register-section');
            if (registerSection && registerSection.classList.contains('active')) {
                showPdpaModal();
            }
        });
    </script>
@endsection
