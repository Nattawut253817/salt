<!DOCTYPE html>
<html lang="th">

<head>
    <script>window.APP_BASE = @json(rtrim(url('/'), '/')); // ฐาน URL ของแอป (รองรับติดตั้งในโฟลเดอร์ย่อย/โดเมนต่างๆ ตอนขึ้น server)</script>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>@yield('title', 'Admin Panel - Salt & Sodium Smart Monitor')</title>
    <!-- Fonts -->
    <link rel="preconnect" href="https://cdn.jsdelivr.net" crossorigin>
    <link rel="preconnect" href="https://cdnjs.cloudflare.com" crossorigin>
    <link rel="preconnect" href="https://code.jquery.com" crossorigin>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link
        href="https://fonts.googleapis.com/css2?family=IBM+Plex+Sans+Thai:wght@300;400;500;600;700&family=Sarabun:wght@300;400;500;600;700;800&family=Noto+Serif+Thai:wght@400;500;600;700&family=Prompt:wght@500;600;700;800&family=K2D:wght@400;500;600;700&display=swap"
        rel="stylesheet">
    <!-- ฟอนต์หลักของระบบหลังบ้าน: Noto Sans Thai Looped -->
    <link href="https://fonts.googleapis.com/css2?family=Noto+Sans+Thai+Looped:wght@300;400;500;600;700;800&display=swap" rel="stylesheet">
    <!-- Font Awesome for icons -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <!-- Bootstrap 4 CSS -->
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@4.6.2/dist/css/bootstrap.min.css"
        integrity="sha384-xOolHFLEh07PJGoPkLv1IbcEPTNtaed2xpHsD9ESMhqIYd0nLMwNLD69Npy4HI+N" crossorigin="anonymous">
    <!-- SweetAlert2 -->
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/sweetalert2@11/dist/sweetalert2.min.css">
    <style>
        /* Force SweetAlert2 above any page overlay/modal (some in this
           app use z-index up to 99999) so its popups are never hidden
           behind an already-open modal. */
        .swal2-container { z-index: 999999 !important; }
    </style>
    <style>
        :root {
            --admin-primary: #4f46e5;
            /* Indigo */
            --admin-primary-hover: #4338ca;
            --admin-bg: #f8fafc;
            /* Lighter background */
            --sidebar-bg: #ffffff;
            --sidebar-text: #334155;
            /* Slate 700 */
            --sidebar-active-bg: #4f46e5;
            --sidebar-active-text: #ffffff;
            --sidebar-width: 300px;
            --navbar-height: 96px;
            /* Brand banner - matches the public site's pink shell (see
               layouts/layout.blade.php's --pink-gradient) but rendered
               full-bleed above the sidebar, per the backend redesign
               request. (No matching footer in the admin panel - removed
               per request, public site footer is unaffected.) */
            --pink-gradient: linear-gradient(145deg, #f0527f 0%, #d81e4a 100%);
            --admin-banner-height: 76px;
        }

        /* Welcome pop up - first sign-in only */
        .wlc-backdrop { position: fixed; inset: 0; z-index: 2000; display: flex; align-items: center; justify-content: center; padding: 20px; background: rgba(76, 5, 25, .55); backdrop-filter: blur(3px); animation: wlcFade .3s ease both; }
        .wlc-card { width: 100%; max-width: 480px; max-height: calc(100vh - 40px); overflow: auto; background: #fff; border-radius: 24px; box-shadow: 0 30px 70px -12px rgba(76, 5, 25, .55); animation: wlcRise .45s cubic-bezier(.2, .9, .25, 1.1) both; }
        .wlc-head { position: relative; padding: 34px 30px 28px; color: #fff; background: linear-gradient(145deg, #f0527f 0%, #d81e4a 100%); overflow: hidden; }
        .wlc-head::after { content: ""; position: absolute; right: -50px; top: -60px; width: 190px; height: 190px; border-radius: 50%; background: rgba(255, 255, 255, .14); }
        .wlc-head::before { content: ""; position: absolute; right: 40px; bottom: -70px; width: 130px; height: 130px; border-radius: 50%; background: rgba(255, 255, 255, .1); }
        .wlc-icon { position: relative; z-index: 1; width: 54px; height: 54px; border-radius: 16px; background: rgba(255, 255, 255, .22); display: flex; align-items: center; justify-content: center; font-size: 1.5rem; margin-bottom: 16px; }
        .wlc-head h2 { position: relative; z-index: 1; margin: 0 0 6px; font-size: 1.45rem; font-weight: 800; line-height: 1.3; }
        .wlc-head p { position: relative; z-index: 1; margin: 0; font-size: .95rem; opacity: .95; }
        .wlc-body { padding: 24px 30px 8px; color: #334155; }
        .wlc-body > p { margin: 0 0 16px; font-size: .95rem; line-height: 1.7; }
        .wlc-points { list-style: none; margin: 0 0 18px; padding: 0; display: grid; gap: 10px; }
        .wlc-points li { display: flex; align-items: center; gap: 12px; padding: 11px 14px; border-radius: 14px; background: #fff1f4; color: #881337; font-weight: 700; font-size: .92rem; }
        .wlc-points i { width: 32px; height: 32px; flex: 0 0 auto; border-radius: 10px; background: #fff; color: #d81e4a; display: flex; align-items: center; justify-content: center; box-shadow: 0 2px 6px rgba(216, 30, 74, .15); }
        .wlc-note { display: flex; gap: 10px; align-items: flex-start; padding: 12px 14px; border-radius: 14px; background: #fffbeb; border: 1px solid #fde68a; color: #92400e; font-size: .85rem; line-height: 1.55; font-weight: 600; }
        .wlc-note i { margin-top: 3px; color: #d97706; }
        .wlc-foot { padding: 18px 30px 28px; }
        .wlc-btn { width: 100%; border: 0; border-radius: 14px; padding: 14px; font-family: inherit; font-size: 1rem; font-weight: 800; color: #fff; cursor: pointer; background: linear-gradient(135deg, #f0527f, #d81e4a); box-shadow: 0 10px 24px -6px rgba(216, 30, 74, .6); transition: transform .15s ease, box-shadow .15s ease; }
        .wlc-btn:hover { transform: translateY(-2px); box-shadow: 0 14px 28px -6px rgba(216, 30, 74, .7); }
        .wlc-btn:focus-visible { outline: 3px solid #fda4af; outline-offset: 2px; }
        .wlc-backdrop.is-closing { animation: wlcFadeOut .2s ease both; }
        @keyframes wlcFade { from { opacity: 0; } }
        @keyframes wlcFadeOut { to { opacity: 0; } }
        @keyframes wlcRise { from { opacity: 0; transform: translateY(24px) scale(.96); } }
        @media (prefers-reduced-motion: reduce) { .wlc-backdrop, .wlc-card { animation: none; } .wlc-btn { transition: none; } }

        html {
            overflow-x: hidden;
            /* Scales the whole admin panel down to ~90% so it reads less
               "zoomed in" by default (matches what 90% browser zoom looked
               like) without anyone having to zoom out manually each time. */
            zoom: 90%;
        }

        body {
            font-family: 'Noto Serif Thai', 'TH Sarabun New', 'TH Sarabun PS', sans-serif;
            background-color: var(--admin-bg);
            margin: 0;
            min-height: 100vh;
            /* ไม่ตั้ง overflow บน body (ตั้งที่ html อยู่แล้ว): ถ้าตั้งทั้งคู่ body จะกลายเป็นตัวเลื่อนอีกชั้น เกิดสกอลล์บาร์ซ้อนสองอัน */
            overflow-x: clip;
            width: 100%;
        }

        /* Hide Horizontal Scrollbars (Scorebars) for tables while keeping scroll functionality */
        .table-responsive::-webkit-scrollbar {
            display: none !important;
        }

        .table-responsive {
            -ms-overflow-style: none !important;
            /* IE and Edge */
            scrollbar-width: none !important;
            /* Firefox */
        }

        * {
            box-sizing: border-box;
        }

        /* Sidebar Styling */
        .admin-sidebar {
            width: var(--sidebar-width);
            background-color: var(--sidebar-bg);
            color: var(--sidebar-text);
            /* Anchored below the fixed top banner (rather than
               height: 100vh, or top: 0, which would run under it) so it
               reaches exactly the real bottom of the viewport, on every
               viewport size. */
            position: fixed;
            left: 0;
            top: var(--admin-banner-height);
            bottom: 0;
            display: flex;
            flex-direction: column;
            box-shadow: 1px 0 10px rgba(0, 0, 0, 0.05);
            /* Softer shadow */
            z-index: 1000;
            border-right: 1px solid #e2e8f0;
        }

        /* Keep every modal (detail popups, edit forms, the user profile
           modal, etc. across all admin pages) scoped to the content area to
           the right of the sidebar AND below the pink header banner, so
           neither one gets dimmed/blurred under the modal backdrop or
           hidden behind a centered dialog box. Covers both Bootstrap's
           own .modal/.modal-backdrop AND the hand-built .modal-overlay /
           .detail-modal-overlay popups used on the "เมนูลดโซเดียม" /
           "ผลิตภัณฑ์ลดโซเดียม" admin pages (resources/views/pages/partials/
           reduced-sodium-menus-content.blade.php and
           sodium-products-content.blade.php), plus .import-modal-overlay -
           the "นำเข้าข้อมูลจากไฟล์ Word" popup on kidney-dhb-content.blade.php
           and report-progress-content.blade.php, which used to cover the
           whole screen (header + sidebar included) because it was missing
           from this list. None of these are Bootstrap modals, so the rule
           above never reached them. The same partials are also reused
           inside the iframe-embedded "รายการ ..." dashboard pages
           (layouts.blank, no sidebar there at all) - the
           var(--sidebar-width, 0px) / var(--admin-banner-height, 0px)
           fallbacks keep this a no-op in that context since neither
           variable is defined anywhere but here. Only applies once the
           sidebar is actually occupying that space on screen (it goes
           off-canvas below 768px - see the media query further down) - at
           that width modals go back to covering the full screen as normal. */
        /* Exclude the user-profile modal from sidebar-scoping so it is
           always centered in the full viewport (not just the content area),
           regardless of sidebar width. The .profile-modal-backdrop class is
           added by JS (show.bs.modal) to the backdrop Bootstrap creates. */
        #userProfileModal,
        .profile-modal-backdrop {
            left: 0 !important;
            width: 100% !important;
            top: 0 !important;
            height: 100vh !important;
            bottom: auto !important;
        }
        #userProfileModal .modal-dialog {
            margin-top: 80px !important;
        }

        @media (min-width: 769px) {

            .modal-backdrop,
            .modal,
            .modal-overlay,
            .detail-modal-overlay,
            .import-modal-overlay {
                /* !important: .modal-overlay/.detail-modal-overlay are
                   defined in a <style> block inside the included partial
                   itself, which sits later in the page's source than this
                   layout's <head> - equal specificity would otherwise let
                   that later declaration (left: 0; width: 100%) win. */
                left: var(--sidebar-width, 0px) !important;
                width: calc(100% - var(--sidebar-width, 0px)) !important;
                /* Bootstrap's default .modal-backdrop is sized with
                   height: 100vh (and .modal with height: 100%, which for a
                   fixed-position element resolves the same way) - same
                   vh-based shortfall bug as the sidebar had (see
                   .admin-sidebar above): once the page is scaled by the
                   site-wide zoom below 100%, that comes up short of the
                   real bottom of the screen, leaving the backdrop's dark
                   overlay not quite reaching the bottom edge. Anchoring to
                   top+bottom instead avoids the vh calculation entirely -
                   top starts BELOW the banner (same var() the sidebar
                   itself uses, see .admin-sidebar above) instead of at the
                   very top of the viewport, so the backdrop/blur never
                   covers the pink header. */
                top: var(--admin-banner-height, 0px) !important;
                bottom: 0 !important;
                height: auto !important;
            }
        }

        .sidebar-brand {
            padding: 20px 20px 8px;
            display: flex;
            align-items: center;
            justify-content: center;
        }

        .brand-logo-frame {
            width: 100%;
            max-width: 228px;
            height: 102px;
            border-radius: 16px;
            overflow: hidden;
            border: 1px solid #eef0f6;
            box-shadow: 0 10px 22px -10px rgba(30, 41, 59, 0.35);
        }

        .brand-logo-frame img {
            display: block;
            width: 100%;
            height: 100%;
            object-fit: cover;
            object-position: center;
        }

        /* Collapsed sidebar: show a compact icon instead of the full logo */
        .brand-logo-collapsed {
            display: none;
            width: 44px;
            height: 44px;
            border-radius: 14px;
            background: linear-gradient(145deg, #f0527f 0%, #d81e4a 100%);
            box-shadow: 0 6px 18px -4px rgba(216, 30, 74, 0.55),
                        0 2px 6px -2px rgba(216, 30, 74, 0.3),
                        inset 0 1px 0 rgba(255,255,255,0.25);
            align-items: center;
            justify-content: center;
            flex-shrink: 0;
        }
        .brand-logo-collapsed span {
            font-family: 'Prompt', 'Noto Sans Thai Looped', sans-serif;
            font-size: 1.25rem;
            font-weight: 800;
            color: #fff;
            letter-spacing: -0.02em;
            line-height: 1;
            text-shadow: 0 1px 3px rgba(0,0,0,0.2);
        }

        /* Full-width pink brand banner, spanning above the sidebar (matches
           the public site's pink header - see layouts/layout.blade.php's
           .header-brand-row - but left-aligned instead of centered, and
           carrying the signed-in user's org/level on the right in place of
           the removed sidebar profile card, see .admin-banner-org below). */
        .admin-top-banner {
            position: fixed;
            top: 0;
            left: 0;
            right: 0;
            height: var(--admin-banner-height);
            background:
                radial-gradient(circle at 10% 0%, rgba(255, 255, 255, 0.28), transparent 45%),
                linear-gradient(180deg, rgba(255, 255, 255, 0.10), rgba(255, 255, 255, 0) 60%),
                var(--pink-gradient);
            display: flex;
            align-items: center;
            justify-content: space-between;
            gap: 16px;
            padding: 0 28px;
            z-index: 1030;
            box-shadow: 0 2px 10px rgba(0, 0, 0, 0.08);
        }

        .admin-banner-brand {
            display: flex;
            align-items: center;
            gap: 14px;
            min-width: 0;
            text-decoration: none !important;
        }

        .admin-banner-logo {
            width: 50px;
            height: 50px;
            border-radius: 50%;
            background: #ffffff;
            display: flex;
            align-items: center;
            justify-content: center;
            flex-shrink: 0;
            overflow: hidden;
            box-shadow: 0 6px 14px rgba(0, 0, 0, 0.2);
            border: 2px solid rgba(255, 255, 255, 0.6);
        }

        .admin-banner-logo img {
            width: 100%;
            height: 100%;
            object-fit: contain;
        }

        .admin-banner-text {
            display: flex;
            flex-direction: column;
            line-height: 1.3;
            min-width: 0;
            gap: 2px;
        }

        .admin-banner-title {
            font-family: 'Noto Serif Thai', 'TH Sarabun New', 'TH Sarabun PS', sans-serif;
            font-size: 1.05rem;
            font-weight: 400;
            color: #ffffff;
            letter-spacing: 0.2px;
            text-shadow: 0 2px 8px rgba(0, 0, 0, 0.18);
            -webkit-text-stroke: 0.3px #ffffff;
            white-space: nowrap;
            overflow: hidden;
            text-overflow: ellipsis;
        }

        /* Shared "muted caption" typography - used for BOTH the line under
           the system title (left) and the rank/level line under the org
           name (right), so the two text stacks read as one consistent
           design language instead of two different styles (per feedback:
           make the line under the system name harmonious with the new
           profile-chip on the right, not clash with it). */
        .admin-banner-subtitle,
        .admin-banner-org-rank {
            font-family: 'Noto Serif Thai', 'TH Sarabun New', 'TH Sarabun PS', sans-serif;
            font-size: 0.74rem;
            font-weight: 500;
            color: rgba(255, 255, 255, 0.75);
            letter-spacing: 0.2px;
            text-transform: none;
        }

        /* Right side of the banner - a profile chip (avatar photo -> chevron
           -> name/role text) in place of the old sidebar profile card,
           still opening the existing user-profile modal on click. Its own
           highlight is just a soft fill on hover/press - the outlined pill
           now belongs to .admin-banner-right, wrapping this AND the
           sign-out button together in one shared border. */
        .admin-banner-org {
            display: flex;
            align-items: center;
            gap: 10px;
            flex-shrink: 1;
            min-width: 0;
            cursor: pointer;
            padding: 4px 10px 4px 3px;
            border-radius: 999px;
            transition: background 0.2s ease;
        }

        .admin-banner-org:hover {
            background: rgba(255, 255, 255, 0.14);
        }

        .admin-banner-avatar {
            position: relative;
            width: 38px;
            height: 38px;
            border-radius: 50%;
            background: #ffe4e6;
            border: 2px solid rgba(255, 255, 255, 0.55);
            display: flex;
            align-items: center;
            justify-content: center;
            color: #be123c;
            flex-shrink: 0;
            font-size: 0.95rem;
            box-shadow: 0 2px 8px rgba(0, 0, 0, 0.15);
            transition: transform 0.2s ease, box-shadow 0.2s ease;
        }

        .admin-banner-org:hover .admin-banner-avatar {
            transform: translateY(-1px);
            box-shadow: 0 6px 14px rgba(0, 0, 0, 0.2);
        }

        .admin-banner-org-chevron {
            font-size: 0.62rem;
            color: rgba(255, 255, 255, 0.65);
            transition: transform 0.2s ease;
            flex-shrink: 0;
        }

        .admin-banner-org:hover .admin-banner-org-chevron {
            transform: translateY(1px);
        }

        .admin-banner-org-info {
            display: flex;
            flex-direction: column;
            align-items: flex-start;
            gap: 2px;
            min-width: 0;
            text-align: left;
        }

        .admin-banner-org-name {
            font-family: 'Noto Serif Thai', 'TH Sarabun New', 'TH Sarabun PS', sans-serif;
            font-size: 0.85rem;
            font-weight: 700;
            color: #ffffff;
            max-width: 240px;
            white-space: nowrap;
            overflow: hidden;
            text-overflow: ellipsis;
            text-shadow: 0 1px 4px rgba(0, 0, 0, 0.12);
        }

        /* Groups the profile chip and sign-out button together on the
           banner's right side as ONE outlined pill (space-between on
           .admin-top-banner now only ever sees two children: the brand on
           the left, this group on the right) - the border/fill that used
           to live on .admin-banner-org and .admin-banner-logout-btn
           separately now lives here once, wrapping both. */
        .admin-banner-right {
            display: flex;
            align-items: center;
            gap: 8px;
            flex-shrink: 0;
            padding: 5px 6px 5px 5px;
            border-radius: 999px;
            border: none;
            background: linear-gradient(90deg, #e11d48 0%, #db2777 100%);
            box-shadow: 0 4px 14px -4px rgba(219, 39, 119, 0.6);
        }

        /* Thin separator between the profile chip and the sign-out button,
           now that they share one pill instead of each having its own. */
        .admin-banner-divider {
            display: none;
        }

        .admin-banner-logout-form {
            margin: 0;
            flex-shrink: 0;
        }

        /* Same soft hover-fill treatment as .admin-banner-org next to it -
           a small rounded-square icon badge (red accent) plus a text
           label, instead of the old icon-only circle. */
        .admin-banner-logout-btn {
            display: flex;
            align-items: center;
            gap: 7px;
            padding: 8px 16px;
            border-radius: 999px;
            border: none;
            background: #ffffff;
            color: #e11d48;
            flex-shrink: 0;
            font-family: 'Noto Serif Thai', 'TH Sarabun New', 'TH Sarabun PS', sans-serif;
            font-size: 0.82rem;
            font-weight: 700;
            cursor: pointer;
            box-shadow: 0 2px 6px rgba(0, 0, 0, 0.12);
            transition: transform 0.15s ease, box-shadow 0.15s ease, background 0.15s ease;
        }

        .admin-banner-logout-btn:hover,
        .admin-banner-logout-btn:focus-visible {
            background: #fff1f2;
            transform: translateY(-1px);
            box-shadow: 0 4px 10px rgba(0, 0, 0, 0.18);
        }

        /* Icon sits directly in the white pill now, same rose tone as
           the text - no separate badge square needed. */
        .admin-banner-logout-icon {
            display: flex;
            align-items: center;
            justify-content: center;
            flex-shrink: 0;
            font-size: 0.78rem;
            color: #e11d48;
        }

        @media (max-width: 576px) {
            .admin-banner-text {
                display: none;
            }

            .admin-banner-org-info {
                display: none;
            }
        }

        .sidebar-nav,
        .sidebar-footer {
            font-family: 'TH Sarabun New', 'TH Sarabun PS', sans-serif;
        }

        .sidebar-nav {
            flex: 1;
            padding: 4px 16px 20px 16px;
            list-style: none;
            margin: 0;
            overflow-y: auto;
            overflow-x: hidden;
            scrollbar-width: thin;
            scrollbar-color: #e2e8f0 transparent;
        }


        .sidebar-nav::-webkit-scrollbar {
            width: 5px;
        }

        .sidebar-nav::-webkit-scrollbar-thumb {
            background-color: #e2e8f0;
            border-radius: 10px;
        }

        /* Section Headings */
        .nav-section-title {
            padding: 14px 14px 6px 14px;
            font-size: 0.75rem;
            font-weight: 800;
            font-family: 'Prompt', 'Sarabun', sans-serif;
            color: #94a3b8;
            text-transform: uppercase;
            letter-spacing: 0.9px;
            display: flex;
            align-items: center;
            gap: 8px;
        }

        .nav-section-title::after {
            content: '';
            flex: 1;
            height: 1px;
            background: #eef2f7;
        }

        .sidebar-nav > .nav-section-title:first-child {
            padding-top: 10px;
        }

        .nav-item {
            margin-bottom: 0;
        }

        .nav-link {
            display: flex;
            align-items: center;
            gap: 12px;
            padding: 10px 14px;
            color: #64748b;
            text-decoration: none !important;
            border-radius: 12px;
            font-weight: 500;
            font-family: 'K2D', 'Sarabun', sans-serif;
            line-height: 1.35;
            transition: background 0.18s ease, color 0.18s ease;
            position: relative;
        }

        .nav-link i {
            font-size: 1rem;
            width: 22px;
            flex-shrink: 0;
            display: flex;
            align-items: center;
            justify-content: center;
            text-align: center;
            color: #94a3b8;
            transition: color 0.2s ease;
        }

        .nav-link span:not(.nav-badge) {
            font-size: 0.90rem;
            flex: 1;
            min-width: 0;
        }

        .nav-link:hover {
            color: #4f46e5;
            background: #eef2ff;
        }

        .nav-link:hover i {
            color: #4f46e5;
        }

        .nav-link.active {
            background: #4f46e5;
            color: #ffffff;
            font-weight: 600;
        }

        .nav-link.active i {
            color: #ffffff;
        }

        /* ── Sidebar search ── */
        .sidebar-search {
            padding: 0 16px 12px;
        }
        .sidebar-search-inner {
            display: flex;
            align-items: center;
            gap: 8px;
            background: #f8fafc;
            border: 1.5px solid #e2e8f0;
            border-radius: 12px;
            padding: 8px 13px;
            transition: border-color 0.2s;
        }
        .sidebar-search-inner:focus-within {
            border-color: #a5b4fc;
            background: #fff;
        }
        .sidebar-search-icon {
            color: #94a3b8;
            font-size: 0.78rem;
            flex-shrink: 0;
        }
        .sidebar-search-input {
            border: none;
            background: transparent;
            outline: none;
            font-size: 0.85rem;
            font-family: 'K2D', 'Sarabun', sans-serif;
            color: #334155;
            width: 100%;
            line-height: 1;
        }
        .sidebar-search-input::placeholder {
            color: #cbd5e1;
        }

        /* ── Sidebar footer ── */
        .sidebar-footer {
            flex-shrink: 0;
            padding: 0 14px 20px;
            background: var(--sidebar-bg);
        }
        .sidebar-footer-divider {
            height: 1px;
            background: #f1f5f9;
            margin-bottom: 12px;
        }

        .btn-logout {
            width: 100%;
            padding: 10px 14px;
            border-radius: 12px;
            border: none;
            background: transparent;
            color: #64748b;
            display: flex;
            align-items: center;
            gap: 12px;
            cursor: pointer;
            font-weight: 500;
            font-family: 'K2D', 'Sarabun', sans-serif;
            transition: background 0.18s, color 0.18s;
            font-size: 0.88rem;
        }

        .btn-logout:hover {
            background: #fef2f2;
            color: #ef4444;
        }
        .btn-logout i {
            font-size: 1rem;
            width: 22px;
            flex-shrink: 0;
        }

        /* Dark mode toggle row */
        .sidebar-dark-toggle {
            display: flex;
            align-items: center;
            gap: 12px;
            padding: 10px 14px;
            border-radius: 12px;
            cursor: pointer;
            transition: background 0.18s;
            user-select: none;
        }
        .sidebar-dark-toggle:hover {
            background: #f1f5f9;
        }
        .sidebar-dark-icon {
            font-size: 1rem;
            width: 22px;
            color: #94a3b8;
            flex-shrink: 0;
        }
        .sidebar-dark-label {
            flex: 1;
            font-size: 0.88rem;
            font-family: 'K2D', 'Sarabun', sans-serif;
            font-weight: 500;
            color: #64748b;
        }
        .sidebar-toggle-switch {
            width: 38px;
            height: 22px;
            border-radius: 11px;
            background: #e2e8f0;
            position: relative;
            transition: background 0.25s;
            flex-shrink: 0;
        }
        .sidebar-toggle-switch.on {
            background: #4f46e5;
        }
        .sidebar-toggle-thumb {
            position: absolute;
            top: 3px;
            left: 3px;
            width: 16px;
            height: 16px;
            border-radius: 50%;
            background: #fff;
            box-shadow: 0 1px 4px rgba(0,0,0,0.18);
            transition: transform 0.25s;
        }
        .sidebar-toggle-switch.on .sidebar-toggle-thumb {
            transform: translateX(16px);
        }

        /* Main Content Styling */
        .admin-main {
            margin-left: var(--sidebar-width);
            margin-top: var(--admin-banner-height);
            display: flex;
            flex-direction: column;
            min-height: calc(100vh - var(--admin-banner-height));
            width: auto;
            position: relative;
        }

        .admin-navbar {
            min-height: var(--navbar-height);
            height: auto;
            background: linear-gradient(135deg, #eef2ff 0%, #f5f3ff 100%);
            padding: 0 30px;
            display: flex;
            justify-content: space-between;
            align-items: center;
            box-shadow: 0 1px 2px rgba(0, 0, 0, 0.03);
            border-bottom: 1px solid #eaebed;
        }

        /* Reusable page header block (accent bar + title + optional subtitle
           pill) used inside @section('header_title') across admin pages. */
        .page-header {
            display: flex;
            align-items: center;
            gap: 14px;
            margin-top: 14px;
            margin-bottom: 6px;
        }

        .page-header-accent {
            width: 5px;
            height: 36px;
            background: linear-gradient(180deg, #818cf8 0%, #4338ca 100%);
            border-radius: 4px;
            flex-shrink: 0;
        }

        .page-header-text {
            display: flex;
            flex-direction: column;
            line-height: 1.4;
        }

        .page-header-title {
            font-weight: 700;
            font-size: 1.25rem;
            color: #3730a3;
            font-family: 'Noto Serif Thai', 'TH Sarabun New', 'TH Sarabun PS', sans-serif;
        }

        .page-header-subtitle {
            display: inline-flex;
            align-items: center;
            gap: 6px;
            width: fit-content;
            font-weight: 600;
            color: #6366a8;
            font-size: 0.78rem;
            margin-top: 6px;
            padding: 4px 13px;
            background: rgba(255, 255, 255, 0.65);
            border: 1px solid #dfe3fb;
            border-radius: 999px;
            font-family: 'Noto Serif Thai', 'TH Sarabun New', 'TH Sarabun PS', sans-serif;
        }

        .page-header-subtitle i {
            font-size: 0.68rem;
            color: #818cf8;
        }

        .nav-badge {
            background-color: #ef4444;
            color: white;
            font-size: 0.65rem;
            font-weight: 700;
            padding: 2px 7px;
            border-radius: 10px;
            min-width: 18px;
            height: 18px;
            text-align: center;
            display: flex;
            align-items: center;
            justify-content: center;
            font-family: 'Noto Serif Thai', 'TH Sarabun New', 'TH Sarabun PS', sans-serif;
            flex-shrink: 0;
            margin-left: 8px;
        }

        .nav-link.active .nav-badge {
            background-color: white;
            color: #ef4444;
            box-shadow: none;
        }

        .page-title h2 {
            margin: 0;
            font-size: 1.25rem;
            font-weight: 700;
            color: #1e293b;
            line-height: 1.4;
        }

        .btn-take-assessment,
        .btn-back-home {
            display: inline-flex;
            align-items: center;
            gap: 9px;
            color: #fff;
            font-family: 'Noto Serif Thai', sans-serif;
            font-size: 0.9rem;
            font-weight: 600;
            padding: 11px 24px;
            /* Fully rounded pill shape, per the reference image (was 12px). */
            border-radius: 999px;
            text-decoration: none;
            white-space: nowrap;
            transition: transform 0.18s ease, box-shadow 0.18s ease, filter 0.18s ease;
        }

        .btn-take-assessment i,
        .btn-back-home i {
            font-size: 0.95rem;
        }

        /* ปุ่มหัวหน้า (เพิ่มเมนู/เพิ่มผลิตภัณฑ์/กลับหน้าหลัก) ไม่ขีดเส้นใต้ลิงก์ ทั้งปกติและเมื่อชี้/โฟกัส */
        .btn-take-assessment, .btn-take-assessment:hover, .btn-take-assessment:focus, .btn-take-assessment:active,
        .btn-back-home, .btn-back-home:hover, .btn-back-home:focus, .btn-back-home:active,
        .btn-take-assessment span, .btn-back-home span { text-decoration: none !important; }

        /* Primary / forward action (ทำแบบประเมิน, เพิ่มเมนู, เพิ่มผลิตภัณฑ์) -
           lighter indigo pill, matches the left button in the reference image. */
        .btn-take-assessment {
            background: linear-gradient(135deg, #6366f1 0%, #4338ca 100%);
            box-shadow: 0 6px 16px rgba(67, 56, 202, 0.35);
        }

        .btn-take-assessment:hover {
            transform: translateY(-2px);
            box-shadow: 0 10px 22px rgba(67, 56, 202, 0.45);
            filter: brightness(1.08);
            color: #fff;
        }

        /* Back / return action (กลับหน้าหลัก) - deeper indigo pill, matches
           the right, darker button in the reference image. */
        .btn-back-home {
            background: linear-gradient(135deg, #4f46e5 0%, #3730a3 100%);
            box-shadow: 0 6px 16px rgba(55, 48, 163, 0.35);
        }

        .btn-back-home:hover {
            transform: translateY(-2px);
            box-shadow: 0 10px 22px rgba(55, 48, 163, 0.45);
            filter: brightness(1.1);
            color: #fff;
        }

        /* ปุ่ม "เลือกพื้นที่ดำเนินงาน" - สีส้ม */
        .btn-take-assessment.btn-pick-area {
            background: linear-gradient(135deg, #fb923c 0%, #ea580c 100%);
            box-shadow: 0 6px 16px rgba(234, 88, 12, 0.35);
        }

        .btn-take-assessment.btn-pick-area:hover {
            box-shadow: 0 10px 22px rgba(234, 88, 12, 0.45);
        }

        .btn-take-assessment:active,
        .btn-back-home:active {
            transform: translateY(0);
        }

        /* ปุ่ม "อ่านแล้วทั้งหมด" - สีเขียวอมน้ำเงิน ชัดเจนว่าเป็น action ที่ต่างจากปุ่มนำทาง */
        .btn-mark-read {
            display: inline-flex;
            align-items: center;
            gap: 9px;
            color: #fff;
            font-family: 'Noto Serif Thai', sans-serif;
            font-size: 0.9rem;
            font-weight: 600;
            padding: 11px 24px;
            border-radius: 999px;
            border: none;
            cursor: pointer;
            white-space: nowrap;
            background: linear-gradient(135deg, #059669 0%, #047857 100%);
            box-shadow: 0 6px 16px rgba(4, 120, 87, 0.35);
            text-decoration: none !important;
            transition: transform 0.18s ease, box-shadow 0.18s ease, filter 0.18s ease;
        }
        .btn-mark-read i { font-size: 0.95rem; }
        .btn-mark-read:hover {
            transform: translateY(-2px);
            box-shadow: 0 10px 22px rgba(4, 120, 87, 0.45);
            filter: brightness(1.08);
            color: #fff;
        }
        .btn-mark-read:active { transform: translateY(0); }

        @media (max-width: 576px) {

            .btn-take-assessment span,
            .btn-back-home span,
            .btn-mark-read span {
                display: none;
            }

            .btn-take-assessment,
            .btn-back-home,
            .btn-mark-read {
                padding: 11px 14px;
            }
        }

        .admin-content {
            padding: 30px;
            flex: 1;
            width: 100%;
            box-sizing: border-box;
        }

        .stat-icon {
            width: 60px;
            height: 60px;
            border-radius: 16px;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 1.5rem;
        }

        .icon-blue {
            background: rgba(59, 130, 246, 0.1);
            color: #3b82f6;
        }

        .icon-purple {
            background: rgba(139, 92, 246, 0.1);
            color: #8b5cf6;
        }

        .icon-green {
            background: rgba(34, 197, 94, 0.1);
            color: #22c55e;
        }

        .icon-orange {
            background: rgba(249, 115, 22, 0.1);
            color: #f97316;
        }

        .stat-info h4 {
            margin: 0;
            color: #64748b;
            font-size: 0.9rem;
            font-weight: 500;
        }

        .stat-info p {
            margin: 5px 0 0 0;
            font-size: 1.6rem;
            font-weight: 800;
            color: #1e293b;
        }

        @media (max-width: 1024px) {
            :root {
                --sidebar-width: 80px;
            }

            .sidebar-brand span,
            .nav-link span,
            .nav-section-title,
            .btn-logout span {
                display: none;
            }

            .sidebar-brand {
                justify-content: center;
                padding: 20px 0;
            }

            .nav-link {
                justify-content: center;
                padding: 15px 0;
            }

            .admin-main {
                margin-left: 80px;
            }
        }

        @media (max-width: 768px) {
            .admin-sidebar {
                transform: translateX(-100%);
            }

            .admin-sidebar.open {
                transform: translateX(0);
                width: 300px;
            }

            .admin-sidebar.open span,
            .admin-sidebar.open .nav-section-title {
                display: block;
            }

            .admin-main {
                margin-left: 0;
            }

            .admin-content {
                padding: 20px;
            }
        }

        /* ========================================================
           Sidebar collapse toggle button
           ======================================================== */
        .sidebar-toggle-btn {
            position: fixed;
            top: calc(var(--admin-banner-height) + 14px);
            left: calc(var(--sidebar-width) - 14px);
            z-index: 1100;
            width: 28px;
            height: 28px;
            border-radius: 50%;
            background: #ffffff;
            border: 1.5px solid #e2e8f0;
            box-shadow: 0 2px 8px rgba(0,0,0,0.10);
            display: flex;
            align-items: center;
            justify-content: center;
            cursor: pointer;
            transition: left 0.3s ease, background 0.2s, border-color 0.2s;
            color: #94a3b8;
            font-size: 0.68rem;
            line-height: 1;
        }
        .sidebar-toggle-btn:hover {
            background: #f1f5f9;
            color: var(--admin-primary);
            border-color: #c7d2fe;
        }

        /* ---- Collapsed state ---- */
        body.sidebar-collapsed {
            /* Update the sidebar-width token so every consumer
               (modal overlays, backdrop, etc.) automatically tracks
               the collapsed width without a separate rule per consumer. */
            --sidebar-width: 68px;
        }
        body.sidebar-collapsed .admin-sidebar {
            width: 68px;
            overflow: visible;
        }
        body.sidebar-collapsed .admin-main {
            margin-left: 68px;
        }
        body.sidebar-collapsed .sidebar-toggle-btn {
            left: calc(68px - 14px);
        }
        body.sidebar-collapsed .sidebar-toggle-btn i {
            transform: rotate(180deg);
        }

        /* hide text labels, section titles, arrows, submenus */
        body.sidebar-collapsed .admin-sidebar .nav-link span:not(.nav-badge),
        body.sidebar-collapsed .admin-sidebar .nav-group-header .group-label,
        body.sidebar-collapsed .admin-sidebar .nav-section-title,
        body.sidebar-collapsed .admin-sidebar .sidebar-brand span,
        body.sidebar-collapsed .admin-sidebar .btn-logout span,
        body.sidebar-collapsed .admin-sidebar .submenu-arrow,
        body.sidebar-collapsed .admin-sidebar .nav-submenu {
            display: none !important;
        }

        /* uniform icon pill — both regular nav-link AND group headers */
        body.sidebar-collapsed .admin-sidebar .nav-link,
        body.sidebar-collapsed .admin-sidebar .nav-group-header {
            justify-content: center;
            padding: 8px 0;
            margin: 0 10px;
            border-radius: 12px;
            position: relative;
        }

        /* icon box: same size, same shape, same background */
        body.sidebar-collapsed .admin-sidebar .nav-link i,
        body.sidebar-collapsed .admin-sidebar .nav-group-header i.group-icon {
            width: 36px !important;
            height: 36px !important;
            font-size: 0.9rem !important;
            border-radius: 10px !important;
            background: #f1f5f9 !important;
            color: #64748b !important;
            display: flex !important;
            align-items: center !important;
            justify-content: center !important;
            flex-shrink: 0;
            transition: background 0.2s, color 0.2s;
        }

        /* active state icon */
        body.sidebar-collapsed .admin-sidebar .nav-link.active i,
        body.sidebar-collapsed .admin-sidebar .nav-group-header.group-active i.group-icon {
            background: var(--admin-primary) !important;
            color: #fff !important;
            box-shadow: 0 4px 10px rgba(79,70,229,0.3);
        }

        /* hover icon */
        body.sidebar-collapsed .admin-sidebar .nav-link:hover i,
        body.sidebar-collapsed .admin-sidebar .nav-group-header:hover i.group-icon {
            background: #e0e7ff !important;
            color: var(--admin-primary) !important;
        }
        body.sidebar-collapsed .admin-sidebar .nav-link:hover,
        body.sidebar-collapsed .admin-sidebar .nav-group-header:hover {
            background: transparent;
            transform: none;
        }

        /* badge stays visible on group header */
        body.sidebar-collapsed .admin-sidebar .nav-group-header .nav-badge {
            position: absolute;
            top: 4px;
            right: 6px;
            min-width: 16px;
            height: 16px;
            font-size: 0.6rem;
            padding: 0 4px;
            line-height: 16px;
        }
        body.sidebar-collapsed .admin-sidebar .nav-link .nav-badge {
            position: absolute;
            top: 4px;
            right: 6px;
            min-width: 16px;
            height: 16px;
            font-size: 0.6rem;
            padding: 0 4px;
            line-height: 16px;
        }

        /* brand logo centred + swap to icon */
        body.sidebar-collapsed .admin-sidebar .sidebar-brand {
            justify-content: center;
            padding: 18px 0;
        }
        body.sidebar-collapsed .admin-sidebar .brand-logo-frame {
            display: none !important;
        }
        body.sidebar-collapsed .admin-sidebar .brand-logo-collapsed {
            display: flex !important;
        }

        /* logout button */
        body.sidebar-collapsed .admin-sidebar .btn-logout {
            justify-content: center;
            padding: 8px 0;
            margin: 0 4px;
        }

        /* hide search + dark mode label in collapsed */
        body.sidebar-collapsed .admin-sidebar .sidebar-search,
        body.sidebar-collapsed .admin-sidebar .sidebar-dark-label,
        body.sidebar-collapsed .admin-sidebar .sidebar-toggle-switch {
            display: none !important;
        }
        body.sidebar-collapsed .admin-sidebar .sidebar-dark-toggle {
            justify-content: center;
            padding: 8px 0;
            margin: 0 4px;
        }

        /* Sidebar tooltip — rendered as a fixed-position element appended
           to <body> by JS, so it is never clipped by sidebar overflow.
           The ::after pseudo approach was removed because CSS cannot have
           overflow-x:visible and overflow-y:auto on the same container. */
        #sidebar-tooltip {
            position: fixed;
            background: #1e293b;
            color: #fff;
            padding: 5px 12px;
            border-radius: 8px;
            font-size: 0.76rem;
            font-family: 'K2D', 'Sarabun', sans-serif;
            white-space: nowrap;
            pointer-events: none;
            z-index: 99999;
            opacity: 0;
            transition: opacity 0.12s ease;
            box-shadow: 0 4px 12px rgba(0,0,0,0.18);
        }
        #sidebar-tooltip.visible {
            opacity: 1;
        }

        /* responsive: at 1024px, sidebar is already icon-only via media query */
        @media (max-width: 1024px) {
            .sidebar-toggle-btn {
                left: calc(80px - 14px);
            }
            body.sidebar-collapsed .sidebar-toggle-btn {
                left: calc(68px - 14px);
            }
        }
        @media (max-width: 768px) {
            .sidebar-toggle-btn {
                left: calc(100% - 14px) !important;
                top: calc(var(--admin-banner-height) + 8px);
            }
        }

        /* ========================================================
           Collapsible submenu group
           ======================================================== */
        .nav-group-header {
            display: flex;
            align-items: center;
            gap: 10px;
            padding: 7px 12px;
            border-radius: 12px;
            cursor: pointer;
            color: #64748b;
            font-weight: 600;
            font-family: 'K2D', 'Sarabun', sans-serif;
            transition: background 0.2s ease, color 0.2s ease, transform 0.2s ease;
            position: relative;
            user-select: none;
            text-decoration: none !important;
        }
        .nav-group-header i.group-icon {
            font-size: 1rem;
            width: 22px;
            flex-shrink: 0;
            display: flex;
            align-items: center;
            justify-content: center;
            color: #94a3b8;
            transition: color 0.2s;
        }
        .nav-group-header .group-label {
            font-size: 0.85rem;
            flex: 1;
            min-width: 0;
        }
        .nav-group-header .nav-badge {
            flex-shrink: 0;
        }
        .submenu-arrow {
            font-size: 0.65rem;
            color: #94a3b8;
            transition: transform 0.25s ease;
            flex-shrink: 0;
            margin-left: 2px;
        }
        .nav-group-header:hover {
            background: #eef2ff;
            color: #4f46e5;
        }
        .nav-group-header:hover i.group-icon {
            color: #4f46e5;
        }
        .nav-group-header.group-active {
            background: #4f46e5;
            color: white;
        }
        .nav-group-header.group-active i.group-icon {
            color: white;
        }
        .nav-group-header.group-active .submenu-arrow {
            color: rgba(255,255,255,0.7);
        }

        /* When group is active AND submenu is open: soften the header so the
           active child item can stand out more clearly (parent recedes slightly) */
        .nav-group-header.group-active.open {
            background: rgba(79,70,229,0.82);
        }
        /* Open state: rotate arrow */
        .nav-group-header.open .submenu-arrow {
            transform: rotate(90deg);
        }
        /* Submenu list */
        .nav-submenu {
            list-style: none;
            padding: 0;
            margin: 0;
            overflow: hidden;
            max-height: 0;
            transition: max-height 0.3s ease, padding 0.3s ease;
        }
        .nav-submenu.submenu-open {
            max-height: 300px;
            padding: 4px 0 6px 0;
            margin: 2px 0 0 0;
        }
        .nav-submenu .nav-link {
            padding: 7px 12px 7px 20px;
            font-size: 0.81rem;
            border-radius: 10px;
            margin-bottom: 2px;
        }
        .nav-submenu .nav-link i {
            width: 24px;
            height: 24px;
            font-size: 0.78rem;
        }
        /* No connector line */
        .nav-group {
            position: relative;
            margin-bottom: 0;  /* extra gap after a group-with-submenu block */
        }

        /* ── Submenu active item: lighter tint (not full gradient) ─────────────────
           The group-header already carries the full indigo gradient to anchor the
           section; the child item should recede — a translucent indigo pill keeps
           hierarchy clear without fighting the parent for attention.
        ──────────────────────────────────────────────────────────────────────────── */
        .nav-submenu .nav-link.active {
            background: rgba(79, 70, 229, 0.18);
            color: #3730a3;
            box-shadow: none;
            font-weight: 600;
        }
        .nav-submenu .nav-link.active i {
            color: #3730a3;
            background: rgba(79, 70, 229, 0.22);
        }
        /* Nav-badge inside group header */
        .nav-group-header .nav-badge {
            margin-left: 0;
        }

    </style>
    @yield('extra_css')
    <style>
        /* =====================================================================
           ระบบหลังบ้านทั้งหมดใช้ฟอนต์ Noto Sans Thai Looped (ตัวเลข 0-9 และอีเมลใช้ Sarabun แบบมีหัว)
           ยกเว้น: เมนู sidebar ด้านซ้าย, ปุ่ม Export (และข้อความในปุ่ม), ไอคอน Font Awesome,
                   กราฟ/แผนที่ SVG และโค้ด (code/pre/kbd) - ทั้งหมดนี้คงฟอนต์เดิม
           ปุ่ม Export ถูกติดคลาส .kd-keep-font อัตโนมัติด้วยสคริปต์ท้ายหน้า (ดู kd-keep-font)
           ===================================================================== */
        /* ตัวเลข 0-9 ใช้ Sarabun (Google Fonts) - ใช้เฉพาะช่วงตัวเลข (หนาขึ้นหนึ่งระดับจากที่ระบุ เพื่ออ่านง่าย) ส่วนอักษรอื่นยังเป็น Noto Sans Thai Looped */
        @font-face { font-family: 'Sarabun Digits'; font-style: normal; font-weight: 100; font-display: swap; src: url(https://fonts.gstatic.com/s/sarabun/v17/DtVmJx26TKEr37c9YNpoilss6w.woff2) format('woff2'); unicode-range: U+30-39; }
        @font-face { font-family: 'Sarabun Digits'; font-style: normal; font-weight: 200; font-display: swap; src: url(https://fonts.gstatic.com/s/sarabun/v17/DtVmJx26TKEr37c9YL5rilss6w.woff2) format('woff2'); unicode-range: U+30-39; }
        @font-face { font-family: 'Sarabun Digits'; font-style: normal; font-weight: 300; font-display: swap; src: url(https://fonts.gstatic.com/s/sarabun/v17/DtVjJx26TKEr37c9aBVJnw.woff2) format('woff2'); unicode-range: U+30-39; }
        @font-face { font-family: 'Sarabun Digits'; font-style: normal; font-weight: 400; font-display: swap; src: url(https://fonts.gstatic.com/s/sarabun/v17/DtVmJx26TKEr37c9YOZqilss6w.woff2) format('woff2'); unicode-range: U+30-39; }
        @font-face { font-family: 'Sarabun Digits'; font-style: normal; font-weight: 500; font-display: swap; src: url(https://fonts.gstatic.com/s/sarabun/v17/DtVmJx26TKEr37c9YMptilss6w.woff2) format('woff2'); unicode-range: U+30-39; }
        @font-face { font-family: 'Sarabun Digits'; font-style: normal; font-weight: 600; font-display: swap; src: url(https://fonts.gstatic.com/s/sarabun/v17/DtVmJx26TKEr37c9YK5silss6w.woff2) format('woff2'); unicode-range: U+30-39; }
        @font-face { font-family: 'Sarabun Digits'; font-style: normal; font-weight: 700; font-display: swap; src: url(https://fonts.gstatic.com/s/sarabun/v17/DtVmJx26TKEr37c9YLJvilss6w.woff2) format('woff2'); unicode-range: U+30-39; }
        @font-face { font-family: 'Sarabun Digits'; font-style: normal; font-weight: 800; font-display: swap; src: url(https://fonts.gstatic.com/s/sarabun/v17/DtVmJx26TKEr37c9YLJvilss6w.woff2) format('woff2'); unicode-range: U+30-39; }
        :root { --kd-font: 'Sarabun Digits', 'Noto Sans Thai Looped', 'Noto Serif Thai', 'TH Sarabun New', sans-serif; }
        body *:not(.admin-sidebar):not(.admin-sidebar *):not(.kd-keep-font):not(.kd-keep-font *):not(.kd-email):not(.kd-email *):not(i):not(.fas):not(.far):not(.fab):not(.fa):not([class*="fa-"]):not(svg):not(svg *):not(code):not(pre):not(kbd):not(samp),
        body {
            font-family: var(--kd-font) !important;
        }
        /* อีเมล: ใช้ Sarabun ทั้งตัวอักษรและตัวเลข - ติดคลาส .kd-email อัตโนมัติด้วยสคริปต์ท้ายหน้า (ไม่แตะไอคอน) */
        .kd-email, .kd-email *:not(i):not(.fas):not(.far):not(.fab):not(.fa):not([class*="fa-"]):not(svg):not(svg *) {
            font-family: 'Sarabun', 'Noto Sans Thai Looped', sans-serif;
            font-weight: 600;
        }
        /* ส่วนที่ยกเว้นต้องกลับไปใช้ฟอนต์เดิมของระบบ (ไม่ใช่สืบทอดจาก body ที่เปลี่ยนแล้ว) */
        .admin-sidebar,
        .kd-keep-font {
            font-family: 'Noto Serif Thai', 'TH Sarabun New', 'TH Sarabun PS', sans-serif;
        }
    </style>
    <style>
        /* ── Dark mode (sidebar only) ───────────────────────────────────── */
        html.dark-mode .admin-sidebar {
            --sidebar-bg: #18181b;
            --sidebar-text: #ffffff;
            background-color: #18181b;
            border-right-color: #2d2d35;
            box-shadow: 1px 0 12px rgba(0,0,0,0.4);
        }
        /* nav links: pure white */
        html.dark-mode .admin-sidebar .nav-link { color: #ffffff; }
        html.dark-mode .admin-sidebar .nav-link i { color: rgba(255,255,255,0.55); }
        html.dark-mode .admin-sidebar .nav-link:hover {
            background: rgba(255,255,255,0.07);
            color: #ffffff;
        }
        html.dark-mode .admin-sidebar .nav-link:hover i { color: #a5b4fc; }
        html.dark-mode .admin-sidebar .nav-link.active { background: #4f46e5; color: #fff; }
        html.dark-mode .admin-sidebar .nav-link.active i { color: #fff; }
        /* section titles */
        html.dark-mode .admin-sidebar .nav-section-title { color: rgba(255,255,255,0.3); }
        html.dark-mode .admin-sidebar .nav-section-title::after { background: rgba(255,255,255,0.08); }
        /* group headers */
        html.dark-mode .admin-sidebar .nav-group-header { color: #ffffff; }
        html.dark-mode .admin-sidebar .nav-group-header i { color: rgba(255,255,255,0.55); }
        html.dark-mode .admin-sidebar .nav-group-header:hover { background: rgba(255,255,255,0.07); }
        html.dark-mode .admin-sidebar .nav-group-header.group-active { background: #4f46e5; color: #fff; }
        /* submenu links */
        html.dark-mode .admin-sidebar .nav-submenu .nav-link { color: rgba(255,255,255,0.8); }
        html.dark-mode .admin-sidebar .nav-submenu .nav-link:hover { color: #ffffff; }
        /* footer */
        html.dark-mode .admin-sidebar .sidebar-footer { background: #18181b; }
        html.dark-mode .admin-sidebar .sidebar-footer-divider { background: rgba(255,255,255,0.08); }
        html.dark-mode .admin-sidebar .sidebar-dark-toggle:hover { background: rgba(255,255,255,0.07); }
        html.dark-mode .admin-sidebar .sidebar-dark-label { color: #ffffff; }
        html.dark-mode .admin-sidebar .sidebar-dark-icon { color: rgba(255,255,255,0.6); }
        /* brand area */
        html.dark-mode .admin-sidebar .sidebar-brand { background: #18181b; }
        html.dark-mode .admin-sidebar .brand-logo-frame { border-color: rgba(255,255,255,0.1); }
    </style>
</head>

<body>
    @php
        // Org display-name pattern reused from
        // App\Http\Controllers\MainController (see e.g. its
        // adminUsersIndex()/agency-listing methods) so the banner shows the
        // same "หน่วยงาน" label used everywhere else in the app - rank 1
        // (สคร.) naturally falls through to the Con_name/name branch, same
        // as there.
        $adminBannerUser = auth()->user();
        $adminBannerOrgName = ($adminBannerUser->User_rank_id == 2 && $adminBannerUser->province)
            ? 'สํานักงานสาธารณสุขจังหวัด' . $adminBannerUser->province->province_name
            : (($adminBannerUser->User_rank_id == 3 && $adminBannerUser->district)
                ? 'สํานักงานสาธารณสุขอำเภอ' . $adminBannerUser->district->district_name
                : (($adminBannerUser->User_rank_id == 4 && $adminBannerUser->subdistrictHospital)
                    ? $adminBannerUser->subdistrictHospital->hospital_name
                    : (($adminBannerUser->User_rank_id == 5 && $adminBannerUser->hospital)
                        ? $adminBannerUser->hospital->hos_name
                        : ($adminBannerUser->Con_name ?: $adminBannerUser->name ?: 'N/A'))));
        $adminBannerRankLabels = [
            1 => 'สคร.',
            2 => 'สสจ.',
            3 => 'สสอ.',
            4 => 'รพ.สต',
            5 => 'รพ.',
        ];
        $adminBannerRankLabel = $adminBannerRankLabels[$adminBannerUser->User_rank_id] ?? '';
        if ($adminBannerUser->isSuperAdmin()) {
            $adminBannerRankLabel = 'SuperAdmin';
        }
    @endphp

    <!-- Full-width pink brand banner (spans above the sidebar). Left: logo
         + system name. Right: signed-in user's org name + rank/level -
         replaces the old sidebar profile card, and still opens the same
         user-profile modal on click. -->
    <div class="admin-top-banner">
        <a href="{{ route('home') }}" class="admin-banner-brand">
            <div class="admin-banner-logo">
                <img src="{{ asset('images/logo.png') }}" alt="Smart Salt Sodium Monitor"
                    onerror="this.onerror=null; this.parentElement.innerHTML='<i class=\'fa-solid fa-staff-snake\' style=\'color:#d81e4a;font-size:1.3rem;\'></i>'">
            </div>
            <div class="admin-banner-text">
                <span class="admin-banner-title">ระบบเฝ้าระวังติดตามการบริโภคเกลือและโซเดียม เขตสุขภาพที่ 10</span>
                <span class="admin-banner-subtitle">Smart Salt &amp; Sodium Monitor</span>
            </div>
        </a>

        <div class="admin-banner-right">
            <div class="admin-banner-org" data-toggle="modal" data-target="#userProfileModal">
                <div class="admin-banner-avatar">
                    <i class="fa-solid fa-user"></i>
                </div>
                <i class="fa-solid fa-chevron-down admin-banner-org-chevron"></i>
                <div class="admin-banner-org-info">
                    <span class="admin-banner-org-name">{{ $adminBannerOrgName }}</span>
                    @if($adminBannerRankLabel !== '')
                        <span class="admin-banner-org-rank">{{ $adminBannerRankLabel }}</span>
                    @endif
                </div>
            </div>

            <div class="admin-banner-divider"></div>

            {{-- Sign-out, moved here from the old sidebar footer card so it
                 sits with the rest of the account controls in the banner -
                 sharing ONE outlined pill with the profile chip above
                 (rather than each having its own separate border), split
                 only by the thin divider between them. --}}
            <form action="{{ route('logout') }}" method="POST" id="logout-form" class="admin-banner-logout-form">
                @csrf
                <button type="button" class="admin-banner-logout-btn" id="logout-btn">
                    <span class="admin-banner-logout-icon"><i class="fas fa-right-from-bracket"></i></span>
                    <span>ออกจากระบบ</span>
                </button>
            </form>
        </div>
    </div>

    <!-- Sidebar collapse/expand button -->
    <button class="sidebar-toggle-btn" id="sidebarToggleBtn" title="ย่อ/ขยายเมนู" aria-label="ย่อ/ขยายเมนู">
        <i class="fas fa-chevron-left"></i>
    </button>

    <aside class="admin-sidebar" id="sidebar">
        <div class="sidebar-brand">
            <div class="brand-logo-frame">
                <img src="{{ asset('images/logo-login.jpg') }}" alt="Smart Salt Sodium Monitor">
            </div>
            <div class="brand-logo-collapsed" aria-hidden="true">
                <span>S</span>
            </div>
        </div>

        <ul class="sidebar-nav">
            @if (auth()->user()->User_rank_id == 1)
                <!-- Admin Only Menus -->
                <div class="nav-section-title">จัดการข้อมูล</div>
                @php
                    $awarenessActive = request()->routeIs('admin.awareness', 'admin.awareness.settings.criteria', 'admin.awareness.settings.dashboard', 'admin.awareness.settings.scoring');
                @endphp
                <li class="nav-group">
                    <div class="nav-group-header {{ $awarenessActive ? 'group-active' : '' }} {{ $awarenessActive ? '' : 'open' }}"
                         data-target="submenu-awareness"
                         data-tooltip="ความตระหนักรู้">
                        <i class="fas fa-file-signature group-icon"></i>
                        <span class="group-label">การประเมินความตระหนักรู้</span>
                        <i class="fas fa-chevron-right submenu-arrow"></i>
                    </div>
                    <ul class="nav-submenu {{ $awarenessActive ? 'submenu-open' : '' }}" id="submenu-awareness">
                        <li class="nav-item">
                            <a href="{{ route('admin.awareness') }}"
                                class="nav-link {{ request()->routeIs('admin.awareness') ? 'active' : '' }}"
                                data-tooltip="ดูผลการประเมิน">
                                <i class="fas fa-eye"></i>
                                <span>ดูผลการประเมิน</span>
                            </a>
                        </li>
                        <li class="nav-item">
                            <a href="{{ route('admin.awareness.settings.criteria') }}"
                                class="nav-link {{ request()->routeIs('admin.awareness.settings.criteria') ? 'active' : '' }}"
                                data-tooltip="ตั้งค่าเกณฑ์">
                                <i class="fas fa-sliders"></i>
                                <span>ตั้งค่าเกณฑ์</span>
                            </a>
                        </li>
                        <li class="nav-item">
                            <a href="{{ route('admin.awareness.settings.dashboard') }}"
                                class="nav-link {{ request()->routeIs('admin.awareness.settings.dashboard') ? 'active' : '' }}"
                                data-tooltip="ตั้งค่าแดชบอร์ด">
                                <i class="fas fa-table-cells"></i>
                                <span>ตั้งค่าแดชบอร์ด</span>
                            </a>
                        </li>
                        <li class="nav-item">
                            <a href="{{ route('admin.awareness.settings.scoring') }}"
                                class="nav-link {{ request()->routeIs('admin.awareness.settings.scoring') ? 'active' : '' }}"
                                data-tooltip="ตั้งค่าคะแนน">
                                <i class="fas fa-calculator"></i>
                                <span>ตั้งค่าคะแนน</span>
                            </a>
                        </li>
                    </ul>
                </li>
                <li class="nav-item">
                    <a href="{{ route('admin.sodium-menus') }}"
                        class="nav-link {{ request()->routeIs('admin.sodium-menus', 'admin.reduced-sodium-menu-dashboard') ? 'active' : '' }}" data-tooltip="เมนูลดโซเดียม">
                        <i class="fas fa-poll"></i>
                        <span>เมนูลดโซเดียม</span>
                    </a>
                </li>
                <li class="nav-item">
                    <a href="{{ route('admin.sodium-products') }}"
                        class="nav-link {{ request()->routeIs('admin.sodium-products', 'admin.reduced-sodium-products-dashboard') ? 'active' : '' }}" data-tooltip="ผลิตภัณฑ์โซเดียม">
                        <i class="fas fa-bottle-droplet"></i>
                        <span>ผลิตภัณฑ์ลดโซเดียม</span>
                    </a>
                </li>
                <li class="nav-item">
                    <a href="{{ route('admin.hi.index') }}"
                        class="nav-link {{ request()->routeIs('admin.hi.index', 'admin.new-ht-cases-dashboard') ? 'active' : '' }}" data-tooltip="HI ความดันโลหิต">
                        <i class="fas fa-chart-line"></i>
                        <span>อัตราป่วยรายใหม่ HT</span>
                    </a>
                </li>

                <div class="nav-section-title">จัดการแบบประเมิน</div>
                @php
                    $kidneyActive = request()->routeIs('admin.kidney-dhb-list', 'admin.kidney-dhb', 'admin.kidney-dhb.show', 'admin.kidney-dhb.areas', 'admin.operating-areas.index');
                @endphp
                <li class="nav-group">
                    <div class="nav-group-header {{ $kidneyActive ? 'group-active' : '' }} {{ $kidneyActive ? '' : 'open' }}"
                         data-target="submenu-kidney"
                         data-tooltip="พชอ.ไต">
                        <i class="fas fa-file-medical group-icon"></i>
                        <span class="group-label">แบบประเมินพชอ.ไต</span>
                        @if (isset($unreadKidneyCount) && $unreadKidneyCount > 0 && auth()->user()->User_rank_id == 1)
                            <span class="nav-badge">{{ $unreadKidneyCount }}</span>
                        @endif
                        <i class="fas fa-chevron-right submenu-arrow"></i>
                    </div>
                    <ul class="nav-submenu {{ $kidneyActive ? 'submenu-open' : '' }}" id="submenu-kidney">
                        <li class="nav-item">
                            <a href="{{ route('admin.kidney-dhb-list') }}"
                                class="nav-link {{ request()->routeIs('admin.kidney-dhb-list', 'admin.kidney-dhb', 'admin.kidney-dhb.show') ? 'active' : '' }}"
                                data-tooltip="รายการประเมิน">
                                <i class="fas fa-list-check"></i>
                                <span>รายการประเมิน</span>
                            </a>
                        </li>
                        @if (auth()->user()->isSuperAdmin())
                        <li class="nav-item">
                            <a href="{{ route('admin.operating-areas.index') }}"
                                class="nav-link {{ request()->routeIs('admin.operating-areas.index') ? 'active' : '' }}"
                                data-tooltip="จัดการพื้นที่">
                                <i class="fas fa-map-location-dot"></i>
                                <span>จัดการพื้นที่</span>
                            </a>
                        </li>
                        @endif
                    </ul>
                </li>
                @php
                    $saltActive = request()->routeIs('admin.salt-assessment-list');
                @endphp
                <li class="nav-group">
                    <div class="nav-group-header {{ $saltActive ? 'group-active' : '' }} {{ $saltActive ? '' : 'open' }}"
                         data-target="submenu-salt"
                         data-tooltip="ลดการบริโภคเกลือ">
                        <i class="fas fa-clipboard-check group-icon"></i>
                        <span class="group-label">แบบประเมินลดการบริโภคเกลือ</span>
                        @if (isset($unreadSaltCount) && $unreadSaltCount > 0 && auth()->user()->User_rank_id == 1)
                            <span class="nav-badge">{{ $unreadSaltCount }}</span>
                        @endif
                        <i class="fas fa-chevron-right submenu-arrow"></i>
                    </div>
                    <ul class="nav-submenu {{ $saltActive ? 'submenu-open' : '' }}" id="submenu-salt">
                        <li class="nav-item">
                            <a href="{{ route('admin.salt-assessment-list') }}"
                                class="nav-link {{ request()->routeIs('admin.salt-assessment-list') ? 'active' : '' }}"
                                data-tooltip="รายการประเมิน">
                                <i class="fas fa-list-check"></i>
                                <span>รายการประเมิน</span>
                            </a>
                        </li>

                    </ul>
                </li>

                <div class="nav-section-title">จัดการระบบ</div>
                <li class="nav-item">
                    <a href="{{ route('admin.users.index') }}"
                        class="nav-link {{ request()->routeIs('admin.users.index') ? 'active' : '' }}" data-tooltip="ผู้ใช้งาน">
                        <i class="fas fa-users-cog"></i>
                        <span>จัดการผู้ใช้งาน</span>
                        @if (isset($pendingUserCount) && $pendingUserCount > 0)
                            <span class="nav-badge">{{ $pendingUserCount }}</span>
                        @endif
                    </a>
                </li>
                <li class="nav-item">
                    <a href="{{ route('admin.fiscal-years.index') }}"
                        class="nav-link {{ request()->routeIs('admin.fiscal-years.index') ? 'active' : '' }}" data-tooltip="ปีงบประมาณ">
                        <i class="fas fa-calendar-alt"></i>
                        <span>จัดการปีงบประมาณ</span>
                    </a>
                </li>
                <li class="nav-item">
                    <a href="{{ route('admin.excel-templates.index') }}"
                        class="nav-link {{ request()->routeIs('admin.excel-templates.index') ? 'active' : '' }}" data-tooltip="เทมเพลต Excel">
                        <i class="fas fa-file-excel"></i>
                        <span>จัดการแบบฟอร์ม Excel</span>
                    </a>
                </li>
                @if (auth()->user()->isSuperAdmin())
                <li class="nav-item">
                    <a href="{{ route('admin.system-management.index') }}"
                        class="nav-link {{ request()->routeIs('admin.system-management.index') ? 'active' : '' }}">
                        <i class="fas fa-gears"></i>
                        <span>จัดการระบบ</span>
                    </a>
                </li>
                @endif
            @elseif(auth()->user()->User_rank_id == 2)
                <!-- สสจ. Menus -->
                <div class="nav-section-title">แดชบอร์ด</div>
                <li class="nav-item">
                    <a href="{{ route('admin.salt-assessment-list') }}"
                        class="nav-link {{ request()->routeIs('admin.salt-assessment-list', 'admin.report-progress') ? 'active' : '' }}">
                        <i class="fas fa-file-invoice"></i>
                        <span>การประเมินลดการบริโภคเกลือ</span>
                    </a>
                </li>
                <li class="nav-item">
                    <a href="{{ route('admin.kidney-dhb-list') }}"
                        class="nav-link {{ request()->routeIs('admin.kidney-dhb-list', 'admin.kidney-dhb', 'admin.kidney-dhb.show', 'admin.kidney-dhb.areas') ? 'active' : '' }}">
                        <i class="fas fa-stethoscope"></i>
                        <span>การประเมินพชอ.ไต</span>
                    </a>
                </li>

                <li class="nav-item">
                    <a href="{{ route('admin.reduced-sodium-menu-dashboard') }}"
                        class="nav-link {{ request()->routeIs('admin.reduced-sodium-menu-dashboard', 'admin.sodium-menus') ? 'active' : '' }}">
                        <i class="fas fa-utensils"></i>
                        <span>เมนูลดโซเดียม</span>
                    </a>
                </li>

                <li class="nav-item">
                    <a href="{{ route('admin.reduced-sodium-products-dashboard') }}"
                        class="nav-link {{ request()->routeIs('admin.reduced-sodium-products-dashboard', 'admin.sodium-products') ? 'active' : '' }}">
                        <i class="fas fa-shopping-basket"></i>
                        <span>ผลิตภัณฑ์ลดโซเดียม</span>
                    </a>
                </li>
            @elseif(auth()->user()->User_rank_id == 3)
                <!-- สสอ. Menus -->
                <div class="nav-section-title">แดชบอร์ด</div>
                <li class="nav-item">
                    <a href="{{ route('admin.kidney-dhb-list') }}"
                        class="nav-link {{ request()->routeIs('admin.kidney-dhb-list', 'admin.kidney-dhb', 'admin.kidney-dhb.show', 'admin.kidney-dhb.areas') ? 'active' : '' }}">
                        <i class="fas fa-file-medical"></i>
                        <span>การประเมินพชอ.ไต</span>
                    </a>
                </li>
                <li class="nav-item">
                    <a href="{{ route('admin.reduced-sodium-menu-dashboard') }}"
                        class="nav-link {{ request()->routeIs('admin.reduced-sodium-menu-dashboard', 'admin.sodium-menus') ? 'active' : '' }}">
                        <i class="fas fa-utensils"></i>
                        <span>เมนูลดโซเดียม</span>
                    </a>
                </li>
            @elseif(auth()->user()->User_rank_id == 4)
                <!-- รพ.สต. Menus -->
                <div class="nav-section-title">แดชบอร์ด</div>
                <li class="nav-item">
                    <a href="{{ route('admin.kidney-dhb-list') }}"
                        class="nav-link {{ request()->routeIs('admin.kidney-dhb-list', 'admin.kidney-dhb', 'admin.kidney-dhb.show', 'admin.kidney-dhb.areas') ? 'active' : '' }}">
                        <i class="fas fa-file-medical"></i>
                        <span>การประเมินพชอ.ไต</span>
                    </a>
                </li>
                <li class="nav-item">
                    <a href="{{ route('admin.reduced-sodium-menu-dashboard') }}"
                        class="nav-link {{ request()->routeIs('admin.reduced-sodium-menu-dashboard', 'admin.sodium-menus') ? 'active' : '' }}">
                        <i class="fas fa-utensils"></i>
                        <span>เมนูลดโซเดียม</span>
                    </a>
                </li>
            @elseif(auth()->user()->User_rank_id == 5)
                <!-- รพ. Menus -->
                <div class="nav-section-title">แดชบอร์ด</div>
                <li class="nav-item">
                    <a href="{{ route('admin.reduced-sodium-menu-dashboard') }}"
                        class="nav-link {{ request()->routeIs('admin.reduced-sodium-menu-dashboard', 'admin.sodium-menus') ? 'active' : '' }}">
                        <i class="fas fa-utensils"></i>
                        <span>รายการ เมนูลดโซเดียม</span>
                    </a>
                </li>

                {{-- <li class="nav-item">
                    <a href="{{ route('admin.kidney-dhb-list') }}"
                        class="nav-link {{ request()->routeIs('admin.kidney-dhb-list', 'admin.kidney-dhb', 'admin.kidney-dhb.show', 'admin.kidney-dhb.areas') ? 'active' : '' }}">
                        <i class="fas fa-file-medical"></i>
                        <span>พชอ.ไต</span>
                    </a>
                </li> --}}

            @endif
        </ul>
        <div class="sidebar-footer">
            <div class="sidebar-footer-divider"></div>
            <div class="sidebar-dark-toggle" id="darkModeToggle" title="เปลี่ยนธีม">
                <i class="fas fa-moon sidebar-dark-icon"></i>
                <span class="sidebar-dark-label">โหมดมืด</span>
                <div class="sidebar-toggle-switch" id="darkModeSwitch">
                    <div class="sidebar-toggle-thumb"></div>
                </div>
            </div>
        </div>
    </aside>

    <div class="admin-main">
        <header class="admin-navbar">
            <div class="page-title">
                @hasSection('header_title')
                    @yield('header_title')
                @else
                    <div style="display: flex; flex-direction: column; line-height: 1.4;">
                        <span style="font-weight: 700; font-size: 1.1rem; color: #1e293b;">
                            แบบรายงานการประเมินความตระหนักรู้
                        </span>
                        <small style="font-weight: 500; color: #64748b; font-size: 0.85rem; margin-top: 2px;">
                            สำรวจจากบุคคลทั่วไป
                        </small>
                    </div>
                @endif
            </div>
            <div class="admin-user-nav" style="display:flex; align-items:center; gap:10px; flex-wrap:wrap; justify-content:flex-end;">
                <!-- User profile moved to sidebar -->
                {{-- หน้ารายการ salt-assessment-list: ไม่มีปุ่ม "ทำแบบประเมิน" ที่ส่วนหัวแล้ว (ใช้ปุ่มในแต่ละแถวของตารางแทน) --}}
                @if(auth()->user()->User_rank_id == 2 && request()->routeIs('admin.report-progress'))
                    <a href="{{ route('admin.salt-assessment-list') }}" class="btn-back-home">
                        <i class="fas fa-arrow-left"></i>
                        <span>กลับหน้าหลัก</span>
                    </a>
                @elseif(auth()->user()->User_rank_id != 1 && request()->routeIs('admin.reduced-sodium-menu-dashboard'))
                    <a href="{{ route('admin.sodium-menus') }}" class="btn-take-assessment">
                        <i class="fas fa-plus"></i>
                        <span>เพิ่มเมนู</span>
                    </a>
                @elseif(auth()->user()->User_rank_id == 2 && request()->routeIs('admin.reduced-sodium-products-dashboard'))
                    <a href="{{ route('admin.sodium-products') }}" class="btn-take-assessment">
                        <i class="fas fa-plus"></i>
                        <span>เพิ่มผลิตภัณฑ์</span>
                    </a>
                @elseif(auth()->user()->User_rank_id != 1 && request()->routeIs('admin.sodium-menus'))
                    <a href="{{ route('admin.reduced-sodium-menu-dashboard') }}" class="btn-back-home">
                        <i class="fas fa-arrow-left"></i>
                        <span>กลับหน้าหลัก</span>
                    </a>
                @elseif(auth()->user()->User_rank_id == 2 && request()->routeIs('admin.sodium-products'))
                    <a href="{{ route('admin.reduced-sodium-products-dashboard') }}" class="btn-back-home">
                        <i class="fas fa-arrow-left"></i>
                        <span>กลับหน้าหลัก</span>
                    </a>
                @elseif(in_array(auth()->user()->User_rank_id, [2, 3, 4]) && request()->routeIs('admin.kidney-dhb-list'))
                    <a href="{{ route('admin.kidney-dhb.areas') }}" class="btn-take-assessment btn-pick-area">
                        <i class="fas fa-map-location-dot"></i>
                        <span>เลือกพื้นที่ดำเนินงาน</span>
                    </a>
                @elseif(in_array(auth()->user()->User_rank_id, [2, 3, 4]) && request()->routeIs('admin.kidney-dhb.areas'))
                    <a href="{{ route('admin.kidney-dhb-list') }}" class="btn-back-home">
                        <i class="fas fa-arrow-left"></i>
                        <span>กลับหน้าหลัก</span>
                    </a>
                @elseif(in_array(auth()->user()->User_rank_id, [2, 3, 4]) && request()->routeIs('admin.kidney-dhb'))
                    <a href="{{ route('admin.kidney-dhb-list') }}" class="btn-back-home">
                        <i class="fas fa-arrow-left"></i>
                        <span>กลับหน้าหลัก</span>
                    </a>
                @elseif(auth()->user()->User_rank_id == 1 && request()->routeIs('admin.kidney-dhb-list') && isset($unreadKidneyCount) && $unreadKidneyCount > 0)
                    <form method="POST" action="{{ route('admin.kidney-dhb.mark-all-read') }}" style="display:inline;">
                        @csrf
                        <button type="submit" class="btn-mark-read">
                            <i class="fas fa-check-double"></i>
                            <span>อ่านแล้วทั้งหมด</span>
                        </button>
                    </form>
                @elseif(auth()->user()->User_rank_id == 1 && request()->routeIs('admin.salt-assessment-list') && isset($unreadSaltCount) && $unreadSaltCount > 0)
                    <form method="POST" action="{{ route('admin.salt-assessment.mark-all-read') }}" style="display:inline;">
                        @csrf
                        <button type="submit" class="btn-mark-read">
                            <i class="fas fa-check-double"></i>
                            <span>อ่านแล้วทั้งหมด</span>
                        </button>
                    </form>
                @endif
            </div>
        </header>

        <main class="admin-content"
            style="{{ request()->routeIs('admin.reduced-sodium-menu-dashboard') || request()->routeIs('admin.reduced-sodium-products-dashboard') || request()->routeIs('admin.new-ht-cases-dashboard') ? 'padding: 0;' : '' }}">
            @yield('content')
        </main>
    </div>

    <!-- Global Nav Loading Overlay -->
    <style>
        .loader-rotating-arcs {
            position: relative;
            width: 70px;
            height: 70px;
            display: flex;
            justify-content: center;
            align-items: center;
            margin-bottom: 20px;
        }

        .center-dot {
            width: 14px;
            height: 14px;
            background-color: #4f46e5;
            border-radius: 50%;
            position: absolute;
            z-index: 10;
            box-shadow: 0 0 10px rgba(79, 70, 229, 0.3);
        }

        .arc {
            position: absolute;
            border-radius: 50%;
            border: 3px solid transparent;
        }

        .arc-inner {
            width: 36px;
            height: 36px;
            border-top-color: #a5b4fc;
            border-left-color: #a5b4fc;
            opacity: 0.8;
            animation: spin 1.5s linear infinite;
        }

        .arc-outer {
            width: 58px;
            height: 58px;
            border-top-color: #4f46e5;
            border-right-color: #4f46e5;
            animation: spin 2s linear infinite reverse;
        }

        @keyframes spin {
            from {
                transform: rotate(0deg);
            }

            to {
                transform: rotate(360deg);
            }
        }

        /* Self-contained logout confirm modal (no SweetAlert2/CDN dependency),
           matching the admin panel's own card/icon language (see the
           user-profile modal below: squircle icon box, rounded card, etc). */
        .logout-confirm-overlay {
            position: fixed;
            inset: 0;
            background: rgba(30, 41, 59, 0.45);
            backdrop-filter: blur(2px);
            display: flex;
            align-items: center;
            justify-content: center;
            z-index: 10050;
            opacity: 0;
            pointer-events: none;
            transition: opacity 0.25s ease;
        }

        .logout-confirm-overlay.is-visible {
            opacity: 1;
            pointer-events: auto;
        }

        .logout-confirm-card {
            background: #fff;
            border-radius: 20px;
            padding: 32px 28px 26px;
            width: 90%;
            max-width: 380px;
            text-align: center;
            box-shadow: 0 10px 30px rgba(0, 0, 0, 0.18);
            transform: translateY(16px) scale(0.96);
            transition: transform 0.25s ease;
        }

        .logout-confirm-overlay.is-visible .logout-confirm-card {
            transform: translateY(0) scale(1);
        }

        .logout-confirm-icon {
            width: 80px;
            height: 80px;
            border-radius: 20px;
            background: rgba(239, 68, 68, 0.1);
            color: #ef4444;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 2.1rem;
            margin: 0 auto 18px;
        }

        .logout-confirm-title {
            font-size: 1.25rem;
            font-weight: 800;
            color: #1e293b;
            margin-bottom: 6px;
        }

        .logout-confirm-text {
            font-size: 0.9rem;
            color: #64748b;
            line-height: 1.6;
            margin: 0;
        }

        .logout-confirm-actions {
            display: flex;
            justify-content: center;
            gap: 12px;
            margin-top: 24px;
        }

        .logout-confirm-btn {
            border: none;
            border-radius: 12px;
            padding: 12px 26px;
            font-weight: 700;
            font-size: 0.92rem;
            cursor: pointer;
            transition: all 0.25s ease;
            font-family: inherit;
        }

        .logout-confirm-btn:focus-visible {
            box-shadow: 0 0 0 3px rgba(79, 70, 229, 0.25);
            outline: none;
        }

        .logout-confirm-btn-confirm {
            background: linear-gradient(135deg, #ef5350 0%, #dc2626 100%);
            color: #fff;
            box-shadow: 0 10px 22px rgba(220, 38, 38, 0.3);
        }

        .logout-confirm-btn-confirm:hover {
            transform: translateY(-2px);
            box-shadow: 0 14px 28px rgba(220, 38, 38, 0.38);
        }

        .logout-confirm-btn-cancel {
            background: #f1f5f9;
            color: #475569;
            padding: 12px 24px;
        }

        .logout-confirm-btn-cancel:hover {
            background: #e2e8f0;
            transform: translateY(-2px);
        }

        /* Logout modal - modern polish: frosted backdrop, spring-in card,
           glossy icon tile with soft halo, equal-width buttons. */
        .logout-confirm-overlay { background: radial-gradient(circle at 50% 40%, rgba(30,41,59,.38), rgba(15,23,42,.62)); backdrop-filter: blur(6px) saturate(1.1); -webkit-backdrop-filter: blur(6px) saturate(1.1); }
        .logout-confirm-card {
            position: relative; overflow: hidden;
            max-width: 400px; padding: 36px 30px 28px; border-radius: 26px;
            background: linear-gradient(180deg, #ffffff 0%, #fbfcff 100%);
            border: 1px solid rgba(226,232,240,.9);
            box-shadow: 0 2px 4px rgba(15,23,42,.04), 0 24px 48px -12px rgba(15,23,42,.35), 0 0 0 6px rgba(255,255,255,.35);
            transform: translateY(24px) scale(.94);
            transition: transform .35s cubic-bezier(.2,1.1,.3,1);
        }
        .logout-confirm-card::before { content:''; position:absolute; left:0; right:0; top:0; height:5px; background: linear-gradient(90deg,#fb7185,#ef4444,#f97316); }
        .logout-confirm-card::after { content:''; position:absolute; top:-70px; left:50%; width:240px; height:170px; transform:translateX(-50%); background: radial-gradient(closest-side, rgba(254,202,202,.7), rgba(254,202,202,0)); pointer-events:none; }
        .logout-confirm-icon {
            position: relative; z-index: 1; width: 84px; height: 84px; border-radius: 26px; margin-bottom: 20px;
            color: #fff; font-size: 2rem;
            background:
                radial-gradient(circle at 30% 22%, rgba(255,255,255,.5) 0, rgba(255,255,255,0) 45%),
                linear-gradient(145deg, #fb7185 0%, #ef4444 55%, #dc2626 100%);
            box-shadow: inset 0 -5px 10px rgba(127,29,29,.3), inset 0 2px 4px rgba(255,255,255,.4), 0 0 0 8px rgba(239,68,68,.1), 0 14px 24px -8px rgba(220,38,38,.55);
        }
        .logout-confirm-icon i { filter: drop-shadow(0 2px 2px rgba(127,29,29,.4)); }
        .logout-confirm-title { position: relative; z-index: 1; font-size: 1.35rem; letter-spacing: .1px; color: #0f172a; margin-bottom: 8px; }
        .logout-confirm-text { position: relative; z-index: 1; font-size: .95rem; color: #64748b; }
        .logout-confirm-actions { position: relative; z-index: 1; gap: 12px; margin-top: 28px; }
        .logout-confirm-btn { flex: 1 1 0; padding: 13px 16px !important; border-radius: 14px; font-size: .95rem; }
        .logout-confirm-btn-cancel { background: #fff; color: #475569; border: 1.5px solid #e2e8f0; box-shadow: 0 1px 2px rgba(15,23,42,.05); }
        .logout-confirm-btn-cancel:hover { background: #f8fafc; border-color: #cbd5e1; color: #1e293b; }
        .logout-confirm-btn-confirm { background: linear-gradient(135deg, #f43f5e 0%, #dc2626 100%); box-shadow: inset 0 1px 0 rgba(255,255,255,.3), 0 12px 22px -6px rgba(220,38,38,.5); }
        .logout-confirm-btn-confirm:hover { box-shadow: inset 0 1px 0 rgba(255,255,255,.3), 0 16px 28px -6px rgba(220,38,38,.6); }
        @media (prefers-reduced-motion: reduce) { .logout-confirm-card { transition: none; } }

        /* Logout modal - tech theme: dark slate card, faint grid, red neon accents */
        .logout-confirm-overlay { background: radial-gradient(circle at 50% 35%, rgba(15,23,42,.55), rgba(2,6,23,.8)); }
        .logout-confirm-card {
            background:
                linear-gradient(rgba(148,163,184,.05) 1px, transparent 1px) 0 0 / 24px 24px,
                linear-gradient(90deg, rgba(148,163,184,.05) 1px, transparent 1px) 0 0 / 24px 24px,
                linear-gradient(180deg, #151c2e 0%, #0e1424 100%);
            border: 1px solid rgba(148,163,184,.16); border-radius: 22px;
            box-shadow: 0 0 0 1px rgba(0,0,0,.4), 0 30px 60px -16px rgba(0,0,0,.75), 0 0 60px -20px rgba(244,63,94,.35);
        }
        .logout-confirm-card::before { height: 2px; background: linear-gradient(90deg, transparent, #f43f5e 30%, #fb7185 50%, #f43f5e 70%, transparent); box-shadow: 0 0 14px rgba(244,63,94,.7); }
        .logout-confirm-card::after { background: radial-gradient(closest-side, rgba(244,63,94,.22), rgba(244,63,94,0)); top: -60px; }
        .logout-confirm-icon {
            width: 76px; height: 76px; border-radius: 22px; color: #fb7185; font-size: 1.8rem;
            background: linear-gradient(160deg, rgba(244,63,94,.18), rgba(244,63,94,.05));
            border: 1px solid rgba(251,113,133,.55);
            box-shadow: inset 0 0 18px rgba(244,63,94,.25), 0 0 0 6px rgba(244,63,94,.07), 0 0 28px -4px rgba(244,63,94,.55);
        }
        .logout-confirm-icon i { filter: drop-shadow(0 0 6px rgba(251,113,133,.7)); }
        .logout-confirm-title { color: #f1f5f9; }
        .logout-confirm-text { color: #94a3b8; }
        .logout-confirm-btn { border-radius: 12px; }
        .logout-confirm-btn-cancel { background: rgba(148,163,184,.08); color: #cbd5e1; border: 1px solid rgba(148,163,184,.25); box-shadow: none; }
        .logout-confirm-btn-cancel:hover { background: rgba(148,163,184,.16); border-color: rgba(148,163,184,.45); color: #fff; }
        .logout-confirm-btn-confirm { background: linear-gradient(135deg, #f43f5e 0%, #be123c 100%); border: 1px solid rgba(251,113,133,.5); box-shadow: inset 0 1px 0 rgba(255,255,255,.25), 0 0 22px -4px rgba(244,63,94,.65); }
        .logout-confirm-btn-confirm:hover { box-shadow: inset 0 1px 0 rgba(255,255,255,.3), 0 0 30px -2px rgba(244,63,94,.85); }
        .logout-confirm-btn:focus-visible { box-shadow: 0 0 0 3px rgba(251,113,133,.35); }

        /* Logout icon v2: line-art exit glyph inside concentric tech rings */
        .logout-confirm-icon { width: 88px; height: 88px; border-radius: 50%; background: radial-gradient(circle at 50% 38%, rgba(244,63,94,.28), rgba(244,63,94,.06) 70%); border: 1px solid rgba(251,113,133,.5); box-shadow: inset 0 0 22px rgba(244,63,94,.28), 0 0 0 7px rgba(244,63,94,.06), 0 0 0 8px rgba(251,113,133,.18), 0 0 34px -4px rgba(244,63,94,.6); }
        .logout-confirm-icon::before { content:''; position:absolute; inset:-15px; border-radius:50%; border:1px dashed rgba(251,113,133,.35); animation: logoutRingSpin 18s linear infinite; }
        .logout-confirm-icon svg { color: #fda4af; filter: drop-shadow(0 0 7px rgba(251,113,133,.85)); }
        @keyframes logoutRingSpin { to { transform: rotate(360deg); } }
        @media (prefers-reduced-motion: reduce) { .logout-confirm-icon::before { animation: none; } }

        /* User profile modal - richer header, icon-badged info rows, and
           gentle hover states for a more polished, easier-to-read look. */
        #userProfileModal .modal-content {
            box-shadow: 0 20px 45px rgba(15, 23, 42, 0.18);
        }

        #userProfileModal .modal-header {
            background: linear-gradient(135deg, #4f46e5 0%, #6366f1 100%) !important;
        }

        #userProfileModal .modal-title i {
            display: inline-flex;
            align-items: center;
            justify-content: center;
            width: 34px;
            height: 34px;
            background: rgba(255, 255, 255, 0.16);
            border-radius: 10px;
            font-size: 0.95rem;
            margin-right: 10px !important;
        }

        #userProfileModal .close {
            background: rgba(255, 255, 255, 0.16);
            border-radius: 50%;
            width: 32px;
            height: 32px;
            display: flex;
            align-items: center;
            justify-content: center;
            transition: background-color 0.2s ease;
        }

        #userProfileModal .close:hover {
            background: rgba(255, 255, 255, 0.3);
            opacity: 1 !important;
        }

        #userProfileModal .profile-avatar {
            width: 84px;
            height: 84px;
            border-radius: 22px;
            background: linear-gradient(135deg, #eef2ff 0%, #e0e7ff 100%);
            color: var(--admin-primary);
            display: flex;
            align-items: center;
            justify-content: center;
            margin: 0 auto 18px;
            font-size: 2.5rem;
            box-shadow: 0 0 0 6px rgba(79, 70, 229, 0.07);
        }

        #userProfileModal .profile-badge {
            box-shadow: 0 4px 12px rgba(79, 70, 229, 0.16);
            margin-top: 4px;
            display: inline-flex;
            align-items: center;
        }

        #userProfileModal .profile-info-card {
            background: #f8fafc;
            border-radius: 18px;
            padding: 6px 20px;
            margin-top: 26px;
        }

        #userProfileModal .profile-info-row {
            display: flex;
            align-items: center;
            gap: 16px;
            padding: 15px 8px;
            border-bottom: 1px solid #e2e8f0;
            border-radius: 12px;
            transition: background-color 0.2s ease;
        }

        #userProfileModal .profile-info-row:last-child {
            border-bottom: none;
        }

        #userProfileModal .profile-info-row:hover {
            background: #eef2ff;
        }

        #userProfileModal .profile-info-icon {
            flex: 0 0 auto;
            width: 38px;
            height: 38px;
            border-radius: 11px;
            background: #eef2ff;
            color: var(--admin-primary);
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 0.95rem;
        }

        #userProfileModal .profile-info-label {
            font-size: 0.74rem;
            color: #64748b;
            font-weight: 700;
            text-transform: uppercase;
            letter-spacing: 0.3px;
            margin-bottom: 4px;
            display: block;
        }

        #userProfileModal .profile-info-value {
            font-weight: 700;
            color: #1e293b;
            font-size: 0.95rem;
            line-height: 1.3;
        }

        #userProfileModal .btn-profile-close {
            border-radius: 13px;
            padding: 13px;
            font-weight: 700;
            font-size: 0.9rem;
            background: #eef2ff;
            border: none;
            color: #4338ca;
            transition: background-color 0.2s ease, transform 0.2s ease;
        }

        #userProfileModal .btn-profile-close:hover {
            background: #e0e7ff;
            transform: translateY(-2px);
        }

        #userProfileModal .profile-section-gap {
            margin-top: 22px;
        }
    </style>



    <!-- Core Scripts -->
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/popper.js@1.16.1/dist/umd/popper.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@4.6.2/dist/js/bootstrap.min.js"></script>
    <!-- SweetAlert2 -->
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>

    <script>
        $(document).ready(function() {
            // ── Dark mode toggle ───────────────────────────────────────────────
            var darkKey = 'sidebar_dark_mode';
            var isDark = localStorage.getItem(darkKey) === '1';

            function applyDarkMode(on) {
                $('html').toggleClass('dark-mode', on);
                $('#darkModeSwitch').toggleClass('on', on);
                $('.sidebar-dark-icon').toggleClass('fa-moon', !on).toggleClass('fa-sun', on);
                localStorage.setItem(darkKey, on ? '1' : '0');
            }

            applyDarkMode(isDark);

            $('#darkModeToggle').on('click', function () {
                isDark = !isDark;
                applyDarkMode(isDark);
            });

            // Profile modal: tag its Bootstrap-generated backdrop so the CSS
            // exclusion rule can target it without a broad body > :last-of-type selector.
            $('#userProfileModal')
                .on('show.bs.modal', function () {
                    // Backdrop is appended just before 'shown'; use a short
                    // rAF so it already exists in the DOM when we tag it.
                    requestAnimationFrame(function () {
                        $('.modal-backdrop').addClass('profile-modal-backdrop');
                    });
                })
                .on('hidden.bs.modal', function () {
                    $('.modal-backdrop').removeClass('profile-modal-backdrop');
                });

            // Confirm before signing out, so an accidental click on the
            // sidebar button doesn't kick the user out of the app.
            $('#logout-btn').on('click', function () {
                const overlay = document.getElementById('logoutConfirmOverlay');
                const confirmBtn = document.getElementById('logoutConfirmConfirm');
                const cancelBtn = document.getElementById('logoutConfirmCancel');

                function close() {
                    overlay.classList.remove('is-visible');
                    confirmBtn.removeEventListener('click', onConfirm);
                    cancelBtn.removeEventListener('click', onCancel);
                    overlay.removeEventListener('click', onOverlayClick);
                    document.removeEventListener('keydown', onKeydown);
                }
                function onConfirm() { close(); document.getElementById('logout-form').submit(); }
                function onCancel() { close(); }
                function onOverlayClick(e) { if (e.target === overlay) close(); }
                function onKeydown(e) { if (e.key === 'Escape') close(); }

                confirmBtn.addEventListener('click', onConfirm);
                cancelBtn.addEventListener('click', onCancel);
                overlay.addEventListener('click', onOverlayClick);
                document.addEventListener('keydown', onKeydown);

                overlay.classList.add('is-visible');
                cancelBtn.focus();
            });
        });
    </script>

    <!-- Logout confirm modal (self-contained, no external CDN dependency) -->
    <div class="logout-confirm-overlay" id="logoutConfirmOverlay">
        <div class="logout-confirm-card">
            <div class="logout-confirm-icon" aria-hidden="true">
                <svg viewBox="0 0 48 48" width="40" height="40" fill="none" stroke="currentColor" stroke-width="2.6" stroke-linecap="round" stroke-linejoin="round">
                    <path d="M20 8h-8a4 4 0 0 0-4 4v24a4 4 0 0 0 4 4h8" opacity=".55"/>
                    <path d="M18 24h22"/>
                    <path d="M32 15l9 9-9 9"/>
                    <circle cx="24" cy="24" r="1.4" fill="currentColor" stroke="none" opacity=".0"/>
                </svg>
            </div>
            <div class="logout-confirm-title">ยืนยันการออกจากระบบ?</div>
            <p class="logout-confirm-text">คุณต้องการออกจากระบบตอนนี้ใช่หรือไม่</p>
            <div class="logout-confirm-actions">
                <button type="button" class="logout-confirm-btn logout-confirm-btn-cancel" id="logoutConfirmCancel">ยกเลิก</button>
                <button type="button" class="logout-confirm-btn logout-confirm-btn-confirm" id="logoutConfirmConfirm">ออกจากระบบ</button>
            </div>
        </div>
    </div>

    <!-- User Profile Modal -->
    <div class="modal fade" id="userProfileModal" tabindex="-1" aria-labelledby="userProfileModalLabel"
        aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content" style="border-radius: 20px; border: none; overflow: hidden;">
                <div class="modal-header" style="color: white; border-bottom: none; padding: 25px 28px;">
                    <h5 class="modal-title" id="userProfileModalLabel"
                        style="font-weight: 800; letter-spacing: 0.3px; display: flex; align-items: center;">
                        <i class="fas fa-id-card-clip"></i> ข้อมูลส่วนตัวผู้ใช้งาน
                    </h5>
                    <button type="button" class="close text-white" data-dismiss="modal" aria-label="Close">
                        <span aria-hidden="true">&times;</span>
                    </button>
                </div>
                <div class="modal-body" style="padding: 28px 30px 30px;">
                    <div class="text-center mb-4">
                        <div class="profile-avatar">
                            <i class="fas fa-user-tie"></i>
                        </div>
                        <h4 style="font-weight: 800; color: #1e293b; margin-bottom: 8px;">
                            {{ auth()->user()->prefix }}{{ auth()->user()->User_firstname }}
                            {{ auth()->user()->User_lastname }}
                        </h4>
                        <span class="badge badge-pill profile-badge"
                            style="background: #eef2ff; color: #4f46e5; padding: 9px 18px; font-weight: 700; font-size: 0.82rem;">
                            <i class="fas fa-building mr-1"></i>
                            @php
                                $ranks = [
                                    1 => 'สำนักงานป้องกันควบคุมโรค (สคร.)',
                                    2 => 'สำนักงานสาธารณสุขจังหวัด (สสจ.)',
                                    3 => 'สำนักงานสาธารณสุขอำเภอ (สสอ.)',
                                    4 => 'โรงพยาบาลส่งเสริมสุขภาพตำบล (รพ.สต.)',
                                    5 => 'โรงพยาบาล (รพ.)',
                                ];
                            @endphp
                            {{ $ranks[auth()->user()->User_rank_id] ?? 'เจ้าหน้าที่' }}
                        </span>
                    </div>

                    <div class="profile-info-card">
                        <div class="profile-info-row">
                            <div class="profile-info-icon"><i class="fas fa-envelope"></i></div>
                            <div>
                                <label class="profile-info-label">ชื่อผู้ใช้งาน / อีเมล</label>
                                <div class="profile-info-value">{{ auth()->user()->email }}</div>
                            </div>
                        </div>

                        <div class="profile-info-row">
                            <div class="profile-info-icon"><i class="fas fa-briefcase"></i></div>
                            <div>
                                <label class="profile-info-label">ตำแหน่ง</label>
                                <div class="profile-info-value">{{ auth()->user()->User_position ?: '-' }}</div>
                            </div>
                        </div>

                        <div class="profile-info-row">
                            <div class="profile-info-icon"><i class="fas fa-building"></i></div>
                            <div>
                                <label class="profile-info-label">หน่วยงาน / สถานบริการ</label>
                                <div class="profile-info-value">{{ auth()->user()->Con_name ?: '-' }}</div>
                            </div>
                        </div>

                        <div class="profile-info-row">
                            <div class="profile-info-icon"><i class="fas fa-map-location-dot"></i></div>
                            <div>
                                <label class="profile-info-label">พื้นที่รับผิดชอบ</label>
                                <div class="profile-info-value">
                                    @if (auth()->user()->province)
                                        {{ auth()->user()->province->province_name }}
                                    @endif
                                    @if (auth()->user()->district)
                                        / อ.{{ auth()->user()->district->district_name }}
                                    @endif
                                </div>
                            </div>
                        </div>
                    </div>

                    <button type="button" class="btn btn-block btn-profile-close profile-section-gap"
                        data-dismiss="modal">
                        <i class="fas fa-xmark mr-2"></i>ปิดหน้าต่าง
                    </button>
                </div>
            </div>
        </div>
    </div>

    {{-- แจ้งเตือน: ยังไม่ได้เลือกพื้นที่ดำเนินงาน (ปุ่มทำแบบประเมิน / redirect จากหน้าแบบประเมิน) --}}
    <style>
        .swal2-popup.need-area-popup { border-radius: 24px; padding: 0 0 26px; width: 440px; max-width: calc(100vw - 32px); overflow: hidden; box-shadow: 0 30px 70px -20px rgba(49, 46, 129, .45); }
        .need-area-hero { background: linear-gradient(160deg, #eef2ff 0%, #e0e7ff 100%); padding: 34px 28px 26px; border-bottom: 1px solid #e0e7ff; }
        .need-area-badge { position: relative; width: 76px; height: 76px; margin: 0 auto 14px; border-radius: 50%; background: #fff; display: grid; place-items: center; color: #4f46e5; font-size: 1.9rem; box-shadow: 0 10px 24px -8px rgba(79, 70, 229, .5); }
        .need-area-badge::after { content: "!"; position: absolute; right: -2px; top: -2px; width: 26px; height: 26px; border-radius: 50%; background: #f59e0b; color: #fff; font: 800 .9rem/26px 'Noto Serif Thai', sans-serif; text-align: center; border: 3px solid #eef2ff; }
        .need-area-title { font-family: 'Noto Serif Thai', sans-serif; font-size: 1.2rem; font-weight: 700; color: #1e1b4b; margin: 0; }
        .need-area-body { padding: 20px 30px 4px; text-align: center; color: #475569; font-size: .92rem; line-height: 1.7; }
        .need-area-steps { display: flex; justify-content: center; align-items: center; gap: 10px; margin-top: 16px; font-size: .8rem; font-weight: 600; color: #94a3b8; }
        .need-area-steps .on { color: #4338ca; }
        .need-area-steps .dot { width: 22px; height: 22px; border-radius: 50%; display: inline-grid; place-items: center; margin-right: 6px; background: #e2e8f0; color: #64748b; font-size: .72rem; }
        .need-area-steps .on .dot { background: #4f46e5; color: #fff; }
        .need-area-steps .bar { width: 28px; height: 2px; background: #e2e8f0; border-radius: 2px; }
        .need-area-popup .swal2-actions { margin-top: 22px; gap: 10px; }
        .need-area-popup .swal2-confirm { border-radius: 999px !important; padding: 11px 26px !important; font-family: 'Noto Serif Thai', sans-serif; font-weight: 600; background: linear-gradient(135deg, #6366f1 0%, #4338ca 100%) !important; box-shadow: 0 8px 18px rgba(67, 56, 202, .35) !important; }
        .need-area-popup .swal2-cancel { border-radius: 999px !important; padding: 11px 22px !important; font-family: 'Noto Serif Thai', sans-serif; font-weight: 600; background: #f1f5f9 !important; color: #475569 !important; }
        .need-area-popup .swal2-confirm:focus-visible, .need-area-popup .swal2-cancel:focus-visible { outline: 2px solid #4f46e5; outline-offset: 2px; box-shadow: none !important; }
    </style>
    <script>
        (function () {
            const areasUrl = @json(route('admin.kidney-dhb.areas'));
            function showNeedAreaAlert() {
                Swal.fire({
                    html: '<div class="need-area-hero"><div class="need-area-badge"><i class="fas fa-map-location-dot"></i></div>' +
                        '<h2 class="need-area-title">ท่านยังไม่ได้เลือกพื้นที่ดำเนินงาน</h2></div>' +
                        '<div class="need-area-body">กรุณาเลือกพื้นที่ดำเนินงานของหน่วยงานก่อน จึงจะเริ่มทำแบบประเมินได้' +
                        '<div class="need-area-steps"><span class="on"><span class="dot">1</span>เลือกพื้นที่</span><span class="bar"></span><span><span class="dot">2</span>ทำแบบประเมิน</span></div></div>',
                    showCancelButton: true,
                    confirmButtonText: '<i class="fas fa-map-pin mr-2"></i> เลือกพื้นที่ดำเนินงาน',
                    cancelButtonText: 'ไว้ทีหลัง',
                    reverseButtons: true,
                    buttonsStyling: false,
                    customClass: { popup: 'need-area-popup', confirmButton: 'swal2-confirm', cancelButton: 'swal2-cancel' },
                    showClass: { popup: 'swal2-show' }
                }).then(function (r) { if (r.isConfirmed) window.location.href = areasUrl; });
            }
            document.querySelectorAll('.js-assess-link').forEach(function (a) {
                a.addEventListener('click', function (e) {
                    if (a.dataset.needArea === '1') { e.preventDefault(); showNeedAreaAlert(); }
                });
            });
            @if (session('need_area'))
                document.addEventListener('DOMContentLoaded', showNeedAreaAlert);
            @endif
        })();
    </script>

    @if (session()->pull('show_welcome') && auth()->check())
        @php $wlcName = trim((string) (auth()->user()->User_firstname ?: auth()->user()->name)); @endphp
        <div class="wlc-backdrop" id="welcomeModal" role="dialog" aria-modal="true" aria-labelledby="wlcTitle">
            <div class="wlc-card">
                <div class="wlc-head">
                    <div class="wlc-icon"><i class="fas fa-hand-sparkles" aria-hidden="true"></i></div>
                    <h2 id="wlcTitle">ยินดีต้อนรับสู่ระบบ{{ $wlcName !== '' ? ' คุณ' . $wlcName : '' }}</h2>
                    <p>Smart Salt &amp; Sodium Monitor · สคร.10 อุบลราชธานี</p>
                </div>
                <div class="wlc-body">
                    <p>ขอบคุณที่เข้าใช้งานระบบเฝ้าระวังติดตามการบริโภคเกลือและโซเดียม เขตสุขภาพที่ 10 ท่านสามารถเริ่มต้นใช้งานได้ทันทีจากเมนูด้านข้าง</p>
                    <ul class="wlc-points">
                        <li><i class="fas fa-clipboard-list" aria-hidden="true"></i> บันทึกข้อมูลการดำเนินงาน</li>
                        <li><i class="fas fa-chart-line" aria-hidden="true"></i> ติดตามผลการดำเนินงาน</li>
                        <li><i class="fas fa-users" aria-hidden="true"></i> เฝ้าระวังร่วมกัน</li>
                    </ul>
                    <div class="wlc-note">
                        <i class="fas fa-circle-info" aria-hidden="true"></i>
                        <span>ข้อความต้อนรับนี้แสดงเฉพาะครั้งแรกที่เข้าใช้งานระบบเท่านั้น จะไม่แสดงอีกในการเข้าสู่ระบบครั้งต่อไป</span>
                    </div>
                </div>
                <div class="wlc-foot">
                    <button type="button" class="wlc-btn" id="wlcClose">เริ่มใช้งาน</button>
                </div>
            </div>
        </div>
        <script>
            (function () {
                var m = document.getElementById('welcomeModal');
                var b = document.getElementById('wlcClose');
                if (!m || !b) return;
                function close() {
                    m.classList.add('is-closing');
                    document.removeEventListener('keydown', onKey);
                    setTimeout(function () { m.remove(); }, 200);
                }
                function onKey(e) { if (e.key === 'Escape') close(); }
                b.addEventListener('click', close);
                m.addEventListener('click', function (e) { if (e.target === m) close(); });
                document.addEventListener('keydown', onKey);
                b.focus();
            })();
        </script>
    @endif

    @yield('extra_js')
    <script>
        /* kd-keep-font: ปุ่ม/ลิงก์ Export (ข้อความ "Export" / "ส่งออก" หรือชี้ไปยังเส้นทาง export) คงฟอนต์เดิม */
        (function () {
            var RE = /export|ส่งออก|นำออก/i;
            function isExport(el) {
                if (el.classList.contains('kd-keep-font')) return false;
                var attrs = [el.id, el.className && el.className.baseVal === undefined ? el.className : '',
                    el.getAttribute('href'), el.getAttribute('data-target'), el.getAttribute('data-bs-target'),
                    el.getAttribute('onclick'), el.getAttribute('title')].join(' ');
                if (RE.test(attrs)) return true;
                var t = (el.textContent || '').replace(/\s+/g, ' ').trim();
                return t.length > 0 && t.length < 40 && RE.test(t);
            }
            function mark(root) {
                (root.querySelectorAll ? root : document).querySelectorAll('a, button, .btn, [role="button"]').forEach(function (el) {
                    if (!el.closest('.admin-sidebar') && isExport(el)) el.classList.add('kd-keep-font');
                });
            }
            var EMAIL = /^[^\s@<>]+@[^\s@<>]+\.[A-Za-z]{2,}$/;
            function markEmails(root) {
                (root.querySelectorAll ? root : document).querySelectorAll('td, th, span, div, a, p, li, small, strong, b, label, dd, dt, option, h1, h2, h3, h4, h5, h6, input[type="email"]').forEach(function (el) {
                    if (el.classList.contains('kd-email') || el.closest('.admin-sidebar') || el.closest('.kd-email')) return;
                    if (el.tagName === 'INPUT') { el.classList.add('kd-email'); return; }
                    var t = (el.textContent || '').trim();
                    if (t.length > 3 && t.length < 120 && EMAIL.test(t)) el.classList.add('kd-email');
                });
            }
            function markAll() { mark(document); markEmails(document); }
            markAll();
            document.addEventListener('DOMContentLoaded', markAll);
            var timer = null;
            new MutationObserver(function () {
                clearTimeout(timer);
                timer = setTimeout(markAll, 60);
            }).observe(document.body, { childList: true, subtree: true });
        })();

        /* ============================================================
           Sidebar collapse toggle
           ============================================================ */
        (function () {
            const body   = document.body;
            const btn    = document.getElementById('sidebarToggleBtn');

            // Restore saved state
            try {
                if (localStorage.getItem('sidebarCollapsed') === '1') {
                    body.classList.add('sidebar-collapsed');
                }
            } catch(e) {}

            if (btn) {
                btn.addEventListener('click', function () {
                    body.classList.toggle('sidebar-collapsed');
                    try {
                        localStorage.setItem('sidebarCollapsed',
                            body.classList.contains('sidebar-collapsed') ? '1' : '0');
                    } catch(e) {}
                });
            }
        })();

        /* ============================================================
           Sidebar collapsed-state tooltips (fixed-position, body-level)
           Uses position:fixed so it is never clipped by sidebar overflow.
           ============================================================ */
        (function () {
            var tip = document.createElement('div');
            tip.id  = 'sidebar-tooltip';
            document.body.appendChild(tip);

            var hideTimer = null;

            function showTip(el) {
                var label = el.getAttribute('data-tooltip');
                if (!label || !document.body.classList.contains('sidebar-collapsed')) return;
                clearTimeout(hideTimer);
                var rect = el.getBoundingClientRect();
                tip.textContent = label;
                tip.style.top  = Math.round(rect.top + rect.height / 2) + 'px';
                tip.style.left = Math.round(rect.right + 10) + 'px';
                tip.style.transform = 'translateY(-50%)';
                tip.classList.add('visible');
            }

            function hideTip() {
                tip.classList.remove('visible');
            }

            var sidebar = document.querySelector('.admin-sidebar');
            if (sidebar) {
                sidebar.addEventListener('mouseover', function (e) {
                    var el = e.target.closest('[data-tooltip]');
                    if (el) showTip(el);
                });
                sidebar.addEventListener('mouseout', function (e) {
                    var el = e.target.closest('[data-tooltip]');
                    if (el) hideTip();
                });
            }
        })();

        /* ============================================================
           Collapsible submenu groups
           ============================================================ */
        (function () {
            document.querySelectorAll('.nav-group-header').forEach(function (header) {
                var targetId = header.getAttribute('data-target');
                if (!targetId) return;
                var submenu  = document.getElementById(targetId);
                if (!submenu) return;

                // Restore saved state — override Blade's default open/close
                try {
                    var saved = localStorage.getItem('submenu_' + targetId);
                    if (saved === '1') {
                        header.classList.add('open');
                        submenu.classList.add('submenu-open');
                    } else if (saved === '0') {
                        header.classList.remove('open');
                        submenu.classList.remove('submenu-open');
                    }
                    // if null → keep Blade's rendered class (active page auto-opens)
                } catch(e) {}

                header.addEventListener('click', function () {
                    var isOpen = submenu.classList.contains('submenu-open');
                    if (isOpen) {
                        submenu.classList.remove('submenu-open');
                        header.classList.remove('open');
                        try { localStorage.setItem('submenu_' + targetId, '0'); } catch(e) {}
                    } else {
                        submenu.classList.add('submenu-open');
                        header.classList.add('open');
                        try { localStorage.setItem('submenu_' + targetId, '1'); } catch(e) {}
                    }
                });
            });
        })();

    </script>
</body>

</html>
