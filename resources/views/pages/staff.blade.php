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
            max-width: 1180px;
            min-height: 660px;
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

        /* Left branding panel - dark wine base with flowing rose / pink
           ribbons, a floating chart card, dot grids and a 3-line headline
           (all art lives in .auth-side-art, behind the content). The big
           bottom-right radius gives the white form side its sweeping edge.
           Palette stays inside the site's rose/pink theme. */
        .auth-side-panel {
            position: relative;
            flex: 0 0 54%;
            padding: 48px 48px 40px;
            background:
                radial-gradient(90% 60% at 100% 100%, rgba(236, 72, 153, 0.45), transparent 65%),
                radial-gradient(80% 50% at 0% 0%, rgba(190, 18, 60, 0.45), transparent 60%),
                linear-gradient(160deg, #2a0314 0%, #4c0a28 50%, #6b0f33 100%);
            color: #fff;
            display: flex;
            flex-direction: column;
            overflow: hidden;
            isolation: isolate;
            border-radius: 0 70px 220px 0 / 0 70px 180px 0;
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
        /* keeps the headline legible where the ribbons pass behind it */
        .auth-side-art::after {
            content: '';
            position: absolute;
            inset: 0;
            background: linear-gradient(90deg, rgba(42, 3, 20, 0.5) 0%, rgba(42, 3, 20, 0.12) 50%, transparent 72%);
        }

        .art-dots {
            position: absolute;
            width: 110px;
            height: 70px;
            background-image: radial-gradient(circle, rgba(255, 255, 255, 0.55) 1.6px, transparent 2px);
            background-size: 22px 22px;
            opacity: 0.8;
            z-index: 1;
        }
        .art-dots-1 { top: 17%; left: 38%; }
        .art-dots-2 { bottom: 12%; left: 46%; width: 132px; height: 66px; opacity: 0.55; }

        /* Floating glass cards */
        .art-chart,
        .art-donut {
            position: absolute;
            z-index: 2;
            border-radius: 18px;
            background: linear-gradient(145deg, rgba(255, 255, 255, 0.2), rgba(255, 255, 255, 0.06));
            border: 1px solid rgba(255, 255, 255, 0.28);
            box-shadow: 0 24px 50px -16px rgba(30, 0, 14, 0.7);
            -webkit-backdrop-filter: blur(8px);
            backdrop-filter: blur(8px);
        }
        .art-chart {
            top: 8%;
            right: 9%;
            width: 36%;
            height: 25%;
            padding: 16px 18px 14px;
            display: flex;
            align-items: flex-end;
            gap: 7%;
            transform: perspective(700px) rotateY(-14deg) rotateZ(-3deg);
        }
        .art-chart span {
            flex: 1;
            border-radius: 4px 4px 0 0;
            background: linear-gradient(180deg, #fda4af, #f43f5e);
        }
        .art-chart span:nth-child(odd) { background: linear-gradient(180deg, #fbcfe8, #ec4899); }
        .art-chart span:nth-child(1) { height: 28%; }
        .art-chart span:nth-child(2) { height: 40%; }
        .art-chart span:nth-child(3) { height: 52%; }
        .art-chart span:nth-child(4) { height: 66%; }
        .art-chart span:nth-child(5) { height: 80%; }
        .art-chart span:nth-child(6) { height: 100%; }
        .art-donut {
            top: 36%;
            right: 3%;
            width: 31%;
            height: 12%;
            padding: 0 16px;
            display: flex;
            align-items: center;
            gap: 12px;
            opacity: 0.9;
            transform: perspective(700px) rotateY(-14deg);
        }
        .art-donut i {
            flex: 0 0 auto;
            width: 28px;
            height: 28px;
            border-radius: 50%;
            border: 5px solid rgba(255, 255, 255, 0.85);
            border-right-color: rgba(255, 255, 255, 0.2);
        }
        .art-donut b {
            flex: 1;
            height: 5px;
            border-radius: 3px;
            background: rgba(255, 255, 255, 0.55);
            box-shadow: 0 11px 0 -1px rgba(255, 255, 255, 0.3);
        }

        .auth-side-logo,
        .auth-side-hero,
        .auth-side-features { position: relative; z-index: 3; }

        /* Logo sits on its own white card so it stays legible and balanced
           regardless of the source image's own colors/whitespace. The source
           photo (logo-login.jpg) has a lot of built-in white margin around
           the actual "SSS" mark, so we display a pre-cropped version
           (logo-login-cropped.jpg, tight around the mark, ~2.5:1) and size
           this box to match that ratio. */
        .auth-side-logo {
            width: 100%;
            max-width: 220px;
            aspect-ratio: 2.5;
            background: #fffafc;
            border-radius: 18px;
            padding: 4px;
            display: flex;
            align-items: center;
            justify-content: center;
            overflow: hidden;
            box-shadow: 0 14px 30px rgba(0, 0, 0, 0.3), 0 2px 6px rgba(0, 0, 0, 0.12);
        }
        .auth-side-logo img {
            width: 100%;
            height: 100%;
            object-fit: cover;
            border-radius: 14px;
        }

        .auth-side-hero { margin-top: auto; }

        .auth-side-title {
            font-size: 2.6rem;
            font-weight: 800;
            line-height: 1.12;
            letter-spacing: -0.01em;
            margin: 0 0 16px;
            text-shadow: 0 2px 14px rgba(42, 3, 20, 0.45);
        }
        .auth-side-title span { display: block; }
        .auth-side-title .accent {
            background: linear-gradient(90deg, #fda4af, #f472b6 60%, #fbcfe8);
            -webkit-background-clip: text;
            background-clip: text;
            -webkit-text-fill-color: transparent;
            color: #fda4af;
        }

        .auth-side-subtitle {
            font-size: 0.95rem;
            font-weight: 500;
            line-height: 1.75;
            color: rgba(255, 255, 255, 0.82);
            max-width: 32ch;
        }

        /* Three highlights pinned to the bottom, separated by hairlines */
        .auth-side-features {
            margin-top: 40px;
            display: flex;
            align-items: flex-start;
        }
        .auth-side-feature {
            display: flex;
            flex-direction: column;
            gap: 10px;
            padding: 0 22px;
            font-size: 0.8rem;
            font-weight: 600;
            line-height: 1.35;
            color: rgba(255, 255, 255, 0.9);
        }
        .auth-side-feature:first-child { padding-left: 0; }
        .auth-side-feature + .auth-side-feature { border-left: 1px solid rgba(255, 255, 255, 0.18); }
        .auth-side-feature-icon {
            width: 44px;
            height: 44px;
            display: flex;
            align-items: center;
            justify-content: center;
            border-radius: 13px;
            background: rgba(255, 255, 255, 0.12);
            border: 1px solid rgba(255, 255, 255, 0.2);
            color: #fda4af;
            font-size: 1.05rem;
        }

        /* Right form panel */
        .auth-main-panel {
            flex: 1;
            min-width: 0;
            display: flex;
            flex-direction: column;
            justify-content: center;
        }

        .auth-welcome { padding: 30px 40px 0; }
        .auth-welcome-label {
            font-size: 0.72rem;
            font-weight: 600;
            letter-spacing: 0.22em;
            color: #94a3b8;
            margin-bottom: 4px;
        }
        .auth-welcome-title {
            font-size: 1.7rem;
            font-weight: 800;
            line-height: 1.2;
            color: #1e1b2e;
        }
        .auth-welcome-title em { font-style: normal; color: var(--primary-color); }

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
            top: calc(100% + 8px);
            left: 0;
            right: 0;
            background: #fff;
            border: 1px solid #f1f5f9;
            border-radius: 14px;
            padding: 14px 16px 16px;
            box-shadow: 0 8px 24px rgba(15, 23, 42, 0.10), 0 2px 6px rgba(15, 23, 42, 0.05);
            z-index: 20;
            opacity: 0;
            transform: translateY(-5px);
            pointer-events: none;
            transition: opacity 0.18s ease, transform 0.18s ease;
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
            height: 4px;
            border-radius: 2px;
            background: #e2e8f0;
            transition: background-color 0.3s ease;
        }

        .pw-strength-bar span.weak { background: #ef4444; }
        .pw-strength-bar span.medium { background: #f59e0b; }
        .pw-strength-bar span.strong { background: #10b981; }

        .pw-strength-label {
            font-size: 0.71rem;
            font-weight: 600;
            letter-spacing: 0.01em;
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
            font-size: 0.79rem;
            color: #94a3b8;
            line-height: 1.4;
            transition: color 0.2s, opacity 0.2s;
        }

        .pw-rules li i {
            font-size: 0.73rem;
            color: #d1d9e3;
            flex-shrink: 0;
            transition: color 0.2s ease, transform 0.2s ease;
        }

        .pw-rules li.met {
            color: #059669;
            font-weight: 600;
        }

        .pw-rules li.met i {
            color: #10b981;
            transform: scale(1.1);
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
            display: inline-flex;
            align-items: center;
            gap: 5px;
            color: #059669;
            background: #f0fdf4;
            border: 1px solid #bbf7d0;
            border-radius: 8px;
            padding: 4px 10px 4px 8px;
            font-size: 0.73rem;
            transition: opacity 0.4s ease;
        }

        .pw-match-feedback.match.fading-out {
            opacity: 0;
        }

        .pw-match-feedback.mismatch {
            display: inline-flex;
            align-items: center;
            gap: 5px;
            color: #dc2626;
            background: #fef2f2;
            border: 1px solid #fecaca;
            border-radius: 8px;
            padding: 4px 10px 4px 8px;
            font-size: 0.73rem;
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

        /* Register button: same white-wipe + spinner loading state as sign-in */
        .btn-auth #register-btn-text,
        .btn-auth #register-btn-icon {
            position: relative;
            transition: color 0.35s ease 0.15s;
        }

        .btn-auth.is-loading #register-btn-text,
        .btn-auth.is-loading #register-btn-icon {
            color: var(--primary-color);
        }

        .btn-auth #register-btn-icon.is-spinner {
            display: inline-block;
            flex: 0 0 auto;
            width: 1.15em;
            height: 1.15em;
            box-sizing: border-box;
            border: 2.5px solid color-mix(in srgb, currentColor 22%, transparent);
            border-top-color: currentColor;
            border-radius: 50%;
            animation: loginSpin 0.75s linear infinite;
        }

        @media (prefers-reduced-motion: reduce) {
            .btn-auth #register-btn-icon.is-spinner,
            .btn-auth #login-btn-arrow.is-spinner { animation-duration: 1.8s; }
            .btn-fill { transition-duration: 0.01s; }
        }

        /* ตัวหมุนระหว่างตรวจสอบ: วงกลมจางๆ ที่มีส่วนโค้งทึบหมุนรอบ (ไม่ใช้ไอคอนลูกศรหมุน) */
        .btn-auth #login-btn-arrow.is-spinner {
            display: inline-block;
            flex: 0 0 auto;
            width: 1.15em;
            height: 1.15em;
            box-sizing: border-box;
            border: 2.5px solid color-mix(in srgb, currentColor 22%, transparent);
            border-top-color: currentColor;
            border-radius: 50%;
            animation: loginSpin 0.75s linear infinite;
        }

        @keyframes loginSpin {
            to { transform: rotate(360deg); }
        }

        @media (prefers-reduced-motion: reduce) {
            .btn-auth #login-btn-arrow.is-spinner { animation-duration: 1.8s; }
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

        /* Formal sign-in alert: icon tile + title + cause + checklist */
        .auth-alert {
            --aa: #b42318; --aa-bg: #fef3f2; --aa-bd: #fecdca; --aa-ic: #fee4e2;
            display: flex; gap: 14px; align-items: flex-start;
            padding: 16px 18px; margin-bottom: 22px;
            border-radius: 16px; background: var(--aa-bg);
            border: 1px solid var(--aa-bd);
            box-shadow: 0 6px 18px -10px var(--aa);
            animation: slideDown 0.4s ease;
        }
        .auth-alert-warning { --aa: #b54708; --aa-bg: #fffaeb; --aa-bd: #fedf89; --aa-ic: #fef0c7; }
        .auth-alert-lock { --aa: #344054; --aa-bg: #f8fafc; --aa-bd: #d0d5dd; --aa-ic: #e4e7ec; }
        .auth-alert-icon {
            flex: none; width: 40px; height: 40px; border-radius: 12px;
            display: flex; align-items: center; justify-content: center;
            font-size: 1.1rem; color: var(--aa); background: var(--aa-ic);
        }
        .auth-alert-body { min-width: 0; }
        .auth-alert-title { font-size: 0.98rem; font-weight: 800; color: var(--aa); line-height: 1.35; }
        .auth-alert-msg { margin-top: 2px; font-size: 0.9rem; font-weight: 600; color: #1f2937; line-height: 1.5; }
        .auth-alert-tips {
            margin: 10px 0 0; padding: 10px 0 0 18px;
            border-top: 1px dashed var(--aa-bd);
            font-size: 0.86rem; font-weight: 700; color: #1d2939; line-height: 1.65;
        }
        .auth-alert-tips li { margin-bottom: 2px; }
        .auth-alert-tips li::marker { color: var(--aa); }

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

        /* ===== Reference-style refresh (split card: curved art panel + clean form) =====
           Rose/pink palette kept; shapes follow the reference: big curved bottom-right
           edge, pale wave sweeping in from the top-right, light wave rising from the
           bottom-left, tilted frosted "folder" cards and soft blurred shapes. */
        .auth-container { background: #ffffff; align-items: center; padding: 28px 20px; }
        .auth-card { border-radius: 36px; box-shadow: 0 24px 60px rgba(15, 23, 42, 0.1), 0 2px 10px rgba(15, 23, 42, 0.04); }

        .auth-side-panel {
            flex: 0 0 43%; /* แคบลง/ชิดซ้าย เพื่อให้ฝั่งฟอร์มกว้างพอสำหรับสมัครสมาชิก */
            background:
                radial-gradient(75% 55% at 82% 92%, rgba(244, 63, 94, 0.6), transparent 70%),
                radial-gradient(70% 50% at 0% 0%, rgba(131, 24, 67, 0.55), transparent 62%),
                linear-gradient(155deg, #2a0314 0%, #52092a 55%, #8a1244 100%);
            border-radius: 0 0 150px 0 / 0 0 170px 0;
        }
        .auth-side-art::after { background: linear-gradient(90deg, rgba(42, 3, 20, 0.35) 0%, transparent 60%); }

        .art-blob { position: absolute; z-index: 1; background: rgba(251, 113, 133, 0.28); }
        .art-blob-1 { width: 130px; height: 130px; border-radius: 50%; left: 5%; top: 33%; filter: blur(26px); }
        .art-blob-2 { width: 44px; height: 44px; border-radius: 12px; left: 30%; top: 30%; background: rgba(255, 255, 255, 0.2); filter: blur(5px); }
        .art-blob-3 { width: 26px; height: 26px; border-radius: 8px; left: 20%; top: 40%; background: rgba(255, 255, 255, 0.16); filter: blur(4px); }

        .art-card {
            position: absolute;
            z-index: 2;
            display: flex;
            flex-direction: column;
            gap: 5px;
            padding: 14px 16px;
            border-radius: 16px;
            color: #fff;
            background: linear-gradient(145deg, rgba(255, 255, 255, 0.26), rgba(255, 255, 255, 0.07));
            border: 1px solid rgba(255, 255, 255, 0.3);
            box-shadow: 0 24px 50px -16px rgba(30, 0, 14, 0.7);
            -webkit-backdrop-filter: blur(8px);
            backdrop-filter: blur(8px);
            font-weight: 700;
            line-height: 1.25;
        }
        .art-card::before {
            content: '';
            position: absolute;
            top: -9px;
            left: 14px;
            width: 36%;
            height: 10px;
            border-radius: 8px 8px 0 0;
            background: rgba(255, 255, 255, 0.28);
        }
        .art-card i { color: #fecdd3; font-size: 1.15rem; }
        .art-card b { font-size: 0.8rem; font-weight: 700; }
        .art-card small { font-size: 0.64rem; font-weight: 500; opacity: 0.8; }
        .art-card-1 { top: 26%; right: 7%; width: 40%; height: 21%;
            transform: perspective(800px) rotateX(16deg) rotateY(-22deg) rotateZ(13deg); }
        .art-card-2 { top: 52%; right: 1%; width: 36%; height: 15%; opacity: 0.8; filter: blur(0.6px);
            transform: perspective(800px) rotateX(16deg) rotateY(-22deg) rotateZ(13deg); }
        .art-card-3 { top: 70%; right: 11%; width: 31%; height: 13%; opacity: 0.55; filter: blur(1.4px);
            transform: perspective(800px) rotateX(16deg) rotateY(-22deg) rotateZ(13deg); }

        .auth-side-panel { padding: 44px 36px 38px; }
        .auth-side-title { font-size: 2.3rem; }
        .auth-side-features { margin-top: 32px; gap: 14px; }
        .auth-side-feature { font-size: 0.74rem; gap: 8px; }
        .auth-side-feature-icon { width: 38px; height: 38px; font-size: 0.95rem; }
        .auth-side-feature { flex-direction: row; align-items: center; gap: 10px; padding: 0; }
        .auth-side-feature + .auth-side-feature { border-left: 0; }
        .auth-side-feature-icon { border-radius: 14px; background: rgba(255, 255, 255, 0.14); }

        .auth-main-panel { position: relative; justify-content: center; }
        .auth-byline { position: absolute; top: 26px; right: 36px; font-size: 0.8rem; font-weight: 600; color: #8a5a6e; }
        .auth-welcome { padding: 44px 40px 0; }
        .auth-welcome-title { font-size: 2rem; font-weight: 800; letter-spacing: -0.01em; color: #1e1b2e; }
        .auth-welcome-title:empty { display: none; }
        .auth-welcome-sub { margin-top: 6px; font-size: 1.5rem; font-weight: 800; line-height: 1.3; letter-spacing: -0.01em; color: #1e1b2e; }
        .auth-brand-name { display: block; white-space: nowrap; margin-top: 2px; font-size: 1.15rem; font-weight: 800; color: var(--primary-color); letter-spacing: 0; }

        .auth-tabs-wrapper { justify-content: flex-start; padding: 18px 40px 0; }
        .auth-tabs { background: #fdf2f6; }

        .form-label { margin-left: 2px; margin-bottom: 6px; color: #1e1b2e; }
        .form-group { margin-bottom: 14px; }
        .form-control {
            border-radius: 14px;
            border: 1.5px solid #f0dde4;
            background: #fff;
            padding: 13px 16px 13px 48px;
        }
        .form-control:focus { border-color: rgba(225, 29, 72, 0.55); box-shadow: 0 0 0 4px rgba(225, 29, 72, 0.1); }
        .input-wrapper > i { left: 18px; opacity: 0.6; }
        .toggle-password { border: 0; background: transparent; padding: 5px 8px; right: 12px; }
        .toggle-password:hover { background: transparent; color: #be123c; }
        #reg_phone { padding-left: 92px; }
        .input-wrapper .phone-country-code { left: 44px; }
        .remember-row { margin-left: 0; margin-bottom: 4px; }

        .btn-auth { margin-top: 18px; padding: 15px; border-radius: 14px; }
        .btn-auth:hover { transform: translateY(-2px); }

        .btn-sso {
            background: #fff;
            color: #1e1b2e;
            border: 1.5px solid #f0dde4;
            box-shadow: none;
        }
        .btn-sso i { color: var(--primary-color); }
        .btn-sso:hover { background: #fff7fa; border-color: rgba(225, 29, 72, 0.45); box-shadow: 0 8px 20px rgba(225, 29, 72, 0.1); }
        .form-footer { margin-top: 24px; }


        @media (max-width: 820px) {
            .auth-card { flex-direction: column; max-width: 550px; min-height: 0; }
            .auth-side-panel {
                flex: 0 0 auto;
                flex-direction: row;
                align-items: center;
                justify-content: center;
                gap: 16px;
                padding: 26px 30px;
                border-radius: 0 0 40px 40px;
            }
            .auth-side-logo { width: 130px; flex-shrink: 0; }
            .auth-side-hero { margin-top: 0; }
            .auth-side-title { font-size: 1.05rem; margin-bottom: 0; }
            .auth-side-title span { display: inline; }
            .auth-side-subtitle,
            .auth-side-features,
            .art-chart, .art-donut, .art-dots, .art-card, .art-blob { display: none; }
            .auth-byline { display: none; }
            .auth-side-panel { border-radius: 0 0 40px 40px; }
            .auth-tabs-wrapper { padding: 16px 20px 0; }
            .auth-welcome { padding: 22px 20px 0; }
            .auth-welcome-title { font-size: 1.55rem; }
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
                <div class="auth-side-art" aria-hidden="true">
                    <svg viewBox="0 0 600 800" preserveAspectRatio="none">
                        <defs>
                            <linearGradient id="waveTop" x1="0" y1="0" x2="1" y2="1">
                                <stop offset="0" stop-color="#fda4af" stop-opacity="0.55"/>
                                <stop offset="1" stop-color="#fecdd3" stop-opacity="0.95"/>
                            </linearGradient>
                            <linearGradient id="waveBottom" x1="0" y1="0" x2="1" y2="0">
                                <stop offset="0" stop-color="#fb7185" stop-opacity="0.95"/>
                                <stop offset="1" stop-color="#f472b6" stop-opacity="0.55"/>
                            </linearGradient>
                            <linearGradient id="waveBottomDeep" x1="0" y1="0" x2="1" y2="0">
                                <stop offset="0" stop-color="#be123c" stop-opacity="0.9"/>
                                <stop offset="1" stop-color="#9d174d" stop-opacity="0.2"/>
                            </linearGradient>
                        </defs>
                        <path d="M300 0 C 335 95 430 150 530 172 C 565 180 588 195 600 212 L 600 0 Z" fill="url(#waveTop)"/>
                        <path d="M385 0 C 410 62 490 112 600 132 L 600 0 Z" fill="#fff" fill-opacity="0.2"/>
                        <path d="M0 662 C 110 622 235 662 305 735 C 330 762 350 785 366 800 L 0 800 Z" fill="url(#waveBottom)"/>
                        <path d="M0 712 C 105 682 215 718 285 785 C 292 792 298 797 303 800 L 0 800 Z" fill="url(#waveBottomDeep)"/>
                        <path d="M0 662 C 110 622 235 662 305 735" fill="none" stroke="#fff" stroke-opacity="0.35" stroke-width="1.4"/>
                    </svg>
                    <div class="art-blob art-blob-1"></div>
                    <div class="art-blob art-blob-2"></div>
                    <div class="art-blob art-blob-3"></div>
                    <div class="art-card art-card-1"><i class="fas fa-folder-open"></i><b>รายงานผลการดำเนินงาน</b><small>ข้อมูลลดเค็ม ลดโรค</small></div>
                    <div class="art-card art-card-2"><i class="fas fa-folder"></i><b>เมนูลดโซเดียม</b></div>
                    <div class="art-card art-card-3"><i class="fas fa-folder"></i><b>ผลิตภัณฑ์ลดโซเดียม</b></div>
                </div>
                <div class="auth-side-logo">
                    <img src="{{ asset('images/logo-login.jpg') }}" alt="Smart Salt & Sodium Monitor Logo">
                </div>
                <div class="auth-side-hero">
                    <div class="auth-side-title"><span>เฝ้าระวัง</span><span>ติดตามผล</span><span class="accent">ลดเค็ม ลดโรค</span></div>
                    <div class="auth-side-subtitle">ระบบเฝ้าระวังติดตามการบริโภคเกลือและโซเดียม เขตสุขภาพที่ 10</div>
                </div>
                <div class="auth-side-features">
                    <div class="auth-side-feature"><span class="auth-side-feature-icon"><i class="fas fa-clipboard-list"></i></span><span>บันทึก<br>ข้อมูล</span></div>
                    <div class="auth-side-feature"><span class="auth-side-feature-icon"><i class="fas fa-chart-line"></i></span><span>ติดตาม<br>ผลงาน</span></div>
                    <div class="auth-side-feature"><span class="auth-side-feature-icon"><i class="fas fa-users"></i></span><span>เฝ้าระวัง<br>ร่วมกัน</span></div>
                </div>
            </div>
            <div class="auth-main-panel">
            <div class="auth-byline">โดย สคร.10 อุบลราชธานี</div>
            <div class="auth-welcome">
                <div class="auth-welcome-title" id="auth-welcome-title">{{ $errors->default->any() ? 'สร้างบัญชีใหม่' : '' }}</div>
                <div class="auth-welcome-sub" id="auth-welcome-sub">{!! $errors->default->any() ? 'กรอกข้อมูลเพื่อขอสิทธิ์เข้าใช้งาน '.'<span class="auth-brand-name">Smart Salt &amp; Sodium Monitor</span>' : 'เข้าสู่ระบบเพื่อใช้งาน '.'<span class="auth-brand-name">Smart Salt &amp; Sodium Monitor</span>' !!}</div>
            </div>
            <div class="auth-tabs-wrapper">
                <div class="auth-tabs">
                    <div class="auth-tab {{ session('success') || !$errors->default->any() ? 'active' : '' }}"
                        onclick="switchTab('login')">เข้าสู่ระบบ</div>
                    <div class="auth-tab {{ $errors->default->any() ? 'active' : '' }}"
                        onclick="switchTab('register')">สมัครสมาชิก</div>
                </div>
            </div>

            <div class="auth-form-container">
                <!-- Login Form -->
                <div id="login-section" class="form-section {{ session('success') || !$errors->default->any() ? 'active' : '' }}">
                    @if (session('success'))
                        <div class="modern-alert modern-alert-success" id="login-success-alert">
                            <i class="fas fa-check-circle"></i> {{ session('success') }}
                        </div>
                    @endif
                    @php
                        $loginAlert = session('login_alert');
                        if (!$loginAlert && session('error')) {
                            $loginAlert = ['type' => 'error', 'title' => 'ไม่สามารถดำเนินการได้', 'message' => session('error'), 'tips' => []];
                        }
                        if (!$loginAlert && $errors->login->any()) {
                            $loginAlert = ['type' => 'warning', 'title' => 'กรุณากรอกข้อมูลให้ครบถ้วน', 'message' => $errors->login->first(), 'tips' => []];
                        }
                        $alertIcon = ['error' => 'fa-circle-xmark', 'warning' => 'fa-hourglass-half', 'lock' => 'fa-lock'];
                    @endphp
                    @if ($loginAlert)
                        <div class="auth-alert auth-alert-{{ $loginAlert['type'] }}" role="alert" aria-live="assertive">
                            <div class="auth-alert-icon"><i class="fas {{ $alertIcon[$loginAlert['type']] ?? 'fa-circle-xmark' }}"></i></div>
                            <div class="auth-alert-body">
                                <div class="auth-alert-title">{{ $loginAlert['title'] }}</div>
                                @if (!empty($loginAlert['message']))
                                    <div class="auth-alert-msg">{{ $loginAlert['message'] }}</div>
                                @endif
                                @if (!empty($loginAlert['tips']))
                                    <ul class="auth-alert-tips">
                                        @foreach ($loginAlert['tips'] as $tip)
                                            <li>{{ $tip }}</li>
                                        @endforeach
                                    </ul>
                                @endif
                            </div>
                        </div>
                    @endif
                    <form action="{{ route('login.post') }}" method="POST" id="login-form" onsubmit="showLoginLoading(event)">
                        @csrf
                        <div class="form-group">
                            <label class="form-label">อีเมล</label>
                            <div class="input-wrapper">
                                <i class="fas fa-envelope"></i>
                                <input type="email" name="email" class="form-control" placeholder="อีเมลผู้ใช้งานของคุณ" value="{{ old('email') }}" required>
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
                            <i class="fas fa-arrow-right" id="login-btn-arrow"></i>
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
                <div id="register-section" class="form-section {{ $errors->default->any() ? 'active' : '' }}">
                    @if ($errors->default->any())
                        <div class="modern-alert modern-alert-error">
                            <i class="fas fa-exclamation-circle"></i>
                            <ul style="margin: 0; padding-left: 20px; list-style: none;">
                                @foreach ($errors->default->all() as $error)
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
                                        @if (\App\Support\SystemSettings::get('register_rank1_enabled', true))
                                            <option value="1" {{ old('User_rank_id') == '1' ? 'selected' : '' }}>สคร.</option>
                                        @endif
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
                            <div class="form-group" id="group_tambon" style="display: none;">
                                <label class="form-label">ตำบล</label>
                                <div class="input-wrapper">
                                    <i class="fas fa-map-pin"></i>
                                    <select id="tambon_select" name="Tambon_id" class="form-control">
                                        <option value="" disabled selected>เลือกตำบล</option>
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
                            <span class="btn-fill"></span>
                            <i class="fas fa-user-plus" id="register-btn-icon"></i>
                            <span id="register-btn-text">ยืนยันการสมัครสมาชิก</span>
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
                    <p>1.1 <strong>เพื่อการยืนยันตัวตน:</strong> ใช้สำหรับตรวจสอบสิทธิ์และยืนยันตัวตนในการเข้าใช้งานระบบ เพื่อความปลอดภัยของข้อมูล</p>
                    <p>1.2 <strong>เพื่อการดำเนินงานตามภารกิจ:</strong> เพื่อใช้ในการติดตามและประเมินผลการดำเนินงานลดการบริโภคเกลือและโซเดียม การขับเคลื่อน พชอ.ไต และการเฝ้าระวังโรคไม่ติดต่อเรื้อรังของหน่วยบริการสาธารณสุขในพื้นที่รับผิดชอบ</p>
                    <p>1.3 <strong>เพื่อพัฒนาการบริการภาครัฐ:</strong> เพื่อเก็บสถิติการใช้งาน นำไปวิเคราะห์และปรับปรุงประสิทธิภาพการทำงานของระบบให้ดียิ่งขึ้น</p>
                </div>

                <div class="pdpa-section">
                    <div class="pdpa-section-title"><i class="fas fa-database"></i> 2. ข้อมูลส่วนบุคคลที่เก็บรวบรวม</div>
                    <p>ระบบจะจัดเก็บข้อมูลเท่าที่จำเป็นจากการลงทะเบียนของท่าน ดังนี้</p>
                    <p>2.1 <strong>ข้อมูลส่วนบุคคลและสังกัด:</strong> ชื่อ-นามสกุล (ภาษาไทย), อีเมล, เบอร์โทรศัพท์ (ถ้ามี), ตำแหน่ง, หน่วยงานที่สังกัด, จังหวัดและอำเภอ</p>
                    <p>2.2 <strong>ข้อมูลการใช้งาน:</strong> สิทธิ์การเข้าใช้งานระบบ วันเวลาที่เข้าถึง และประวัติการบันทึกข้อมูล</p>
                </div>

                <div class="pdpa-section">
                    <div class="pdpa-section-title"><i class="fas fa-clock"></i> 3. ระยะเวลาในการเก็บรักษาข้อมูล</div>
                    <p><strong>กลุ่มโรคไม่ติดต่อ</strong> จะเก็บรักษาข้อมูลส่วนบุคคลของท่านไว้ตราบเท่าที่ท่านยังเป็นผู้ใช้งานระบบ หรือตามระยะเวลาที่จำเป็นในการดำเนินงานตามวัตถุประสงค์ของระบบ</p>
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
            // circular spinner while the white fill wipes in, so it reads as an
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
                // ไอคอนลูกศร -> วงกลมหมุน (CSS .is-spinner)
                arrowEl.className = 'is-spinner';
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

            const wTitle = document.getElementById('auth-welcome-title');
            const wSub = document.getElementById('auth-welcome-sub');
            if (wTitle && wSub) {
                if (tab === 'login') {
                    wTitle.textContent = '';
                    wSub.innerHTML = 'เข้าสู่ระบบเพื่อใช้งาน <span class="auth-brand-name">Smart Salt &amp; Sodium Monitor</span>';
                } else {
                    wTitle.textContent = 'สร้างบัญชีใหม่';
                    wSub.innerHTML = 'กรอกข้อมูลเพื่อขอสิทธิ์เข้าใช้งาน <span class="auth-brand-name">Smart Salt &amp; Sodium Monitor</span>';
                }
            }

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
                    const rank = document.getElementById('rank_select').value;
                    if (['2', '3', '4', '5'].includes(rank)) {
                        fetchTambons();
                    }
                    if (rank === '4') {
                        fetchSubdistrictHospitals();
                    } else if (rank === '5') {
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
            if (['2', '3', '4', '5'].includes(rank)) {
                fetchTambons();
            }
            if (rank === '4') {
                fetchSubdistrictHospitals(); // Fetch all in district
            } else if (rank === '5') {
                fetchHospitals();
            }
        }

        async function fetchTambons() {
            const provinceId = document.getElementById('province_select').value;
            const districtId = document.getElementById('district_select').value;
            const tambonSelect = document.getElementById('tambon_select');

            if (!provinceId || !districtId) return;

            tambonSelect.innerHTML = '<option value="" disabled selected>กำลังโหลด...</option>';

            try {
                const response = await fetch(`${APP_BASE}/get-subdistricts/${provinceId}/${districtId}`);
                const tambons = await response.json();

                tambonSelect.innerHTML = '<option value="" disabled selected>เลือกตำบล</option>';
                tambons.forEach(t => {
                    const option = document.createElement('option');
                    option.value = t.tambon_id;
                    option.textContent = t.tambon_name;
                    tambonSelect.appendChild(option);
                });

                const oldTambon = "{{ old('Tambon_id') }}";
                if (oldTambon) {
                    tambonSelect.value = oldTambon;
                }
            } catch (error) {
                console.error('Error fetching tambons:', error);
                tambonSelect.innerHTML = '<option value="" disabled selected>เกิดข้อผิดพลาด</option>';
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
                const response = await fetch(`${APP_BASE}/get-hospitals/${provinceId}/${districtId}`);
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
            const groupTambon = document.getElementById('group_tambon');
            const groupSubdistrictHospital = document.getElementById('group_subdistrict_hospital');
            const groupAffiliation = document.getElementById('group_affiliation');
            const provinceSelect = document.getElementById('province_select');

            // Reset visibility
            groupProvince.style.display = 'block';
            groupDistrict.style.display = 'none';
            groupHospital.style.display = 'none';
            groupTambon.style.display = 'none';
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
                groupDistrict.style.display = 'block';
                groupTambon.style.display = 'block';
            } else if (rank === '3') { // สสอ.
                groupDistrict.style.display = 'block';
                groupTambon.style.display = 'block';
            } else if (rank === '4') { // รพ.สต
                groupDistrict.style.display = 'block';
                groupTambon.style.display = 'block';
                groupSubdistrictHospital.style.display = 'block';
                groupAffiliation.style.display = 'block';
            } else if (rank === '5') { // รพ.
                groupDistrict.style.display = 'block';
                groupTambon.style.display = 'block';
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
                let url = `${APP_BASE}/get-subdistrict-hospitals/${provinceId}/${districtId}`;

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
            const textEl = document.getElementById('register-btn-text');
            const iconEl = document.getElementById('register-btn-icon');

            // White panel wipes across, label -> "กำลังสมัครสมาชิก..." and the
            // user icon -> circular spinner (a failed submit reloads the page,
            // which resets this markup to its Blade default).
            btn.classList.add('is-loading');
            btn.disabled = true;
            if (textEl) textEl.textContent = 'กำลังสมัครสมาชิก...';
            if (iconEl) iconEl.className = 'is-spinner';

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

            // หมายเหตุ: ถ้าสมัครไม่ผ่าน (เช่น อีเมลซ้ำ / ข้อมูลไม่ครบ) หน้าจะโหลดใหม่และเปิดแท็บ
            // "สมัครสมาชิก" พร้อมข้อความผิดพลาดและข้อมูลเดิมที่กรอกไว้ - ไม่เปิดนโยบาย PDPA ซ้ำ
            // เพราะผู้ใช้ยอมรับไปแล้วก่อนกดสมัคร (นโยบายจะเด้งเฉพาะตอนกดสลับมาแท็บสมัครสมาชิกเอง)
        });
    </script>
@endsection
