<!DOCTYPE html>
<html lang="th">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>@yield('title', 'Admin Panel - Salt & Sodium Smart Monitor')</title>
    <!-- Fonts -->
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link
        href="https://fonts.googleapis.com/css2?family=Kanit:wght@300;400;500;600;700;800&family=IBM+Plex+Sans+Thai:wght@100;200;300;400;500;600;700&family=Sarabun:wght@400;500;600;700;800&family=Noto+Serif+Thai:wght@400;500;600;700&family=Prompt:wght@500;600;700;800&family=K2D:wght@400;500;600;700&family=Kodchasan:wght@500;600;700;800&family=Pridi:wght@400;500;600;700&family=Itim&display=swap"
        rel="stylesheet">
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
            /* White sidebar */
            --sidebar-text: #64748b;
            /* Slate 500 */
            --sidebar-active-bg: #4f46e5;
            --sidebar-active-text: #ffffff;
            --sidebar-width: 280px;
            --navbar-height: 96px;
            /* Brand banner - matches the public site's pink shell (see
               layouts/layout.blade.php's --pink-gradient) but rendered
               full-bleed above the sidebar, per the backend redesign
               request. (No matching footer in the admin panel - removed
               per request, public site footer is unaffected.) */
            --pink-gradient: linear-gradient(145deg, #f0527f 0%, #d81e4a 100%);
            --admin-banner-height: 76px;
        }

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
            overflow-x: hidden;
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
            padding: 4px 14px 20px 14px;
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
            padding: 22px 12px 10px 12px;
            font-size: 0.72rem;
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
            padding-top: 8px;
        }

        .nav-item {
            margin-bottom: 2px;
        }

        .nav-link {
            display: flex;
            align-items: center;
            gap: 10px;
            padding: 10px 12px;
            color: var(--sidebar-text);
            text-decoration: none !important;
            border-radius: 12px;
            font-weight: 600;
            font-family: 'K2D', 'Sarabun', sans-serif;
            line-height: 1.35;
            transition: background 0.2s ease, color 0.2s ease, transform 0.2s ease, box-shadow 0.2s ease;
            position: relative;
        }

        .nav-link i {
            font-size: 0.86rem;
            width: 29px;
            height: 29px;
            flex-shrink: 0;
            display: flex;
            align-items: center;
            justify-content: center;
            text-align: center;
            color: #64748b;
            background: #f1f5f9;
            border-radius: 9px;
            transition: color 0.2s ease, background-color 0.2s ease;
        }

        .nav-link span:not(.nav-badge) {
            font-size: 0.85rem;
            flex: 1;
            min-width: 0;
        }

        .nav-link:hover {
            color: var(--admin-primary);
            background: #f1f5f9;
            transform: translateX(2px);
        }

        .nav-link:hover i {
            color: var(--admin-primary);
            background: #e0e7ff;
        }

        .nav-link.active {
            background: linear-gradient(135deg, var(--admin-primary) 0%, #6366f1 100%);
            color: white;
            box-shadow: 0 6px 14px rgba(79, 70, 229, 0.28);
        }

        .nav-link.active i {
            color: white;
            background: rgba(255, 255, 255, 0.18);
        }

        .sidebar-footer {
            padding: 16px 20px 20px 20px;
            background: #fff;
            border-top: 1px solid #f1f5f9;
        }

        .btn-logout {
            width: 100%;
            padding: 11px;
            border-radius: 12px;
            border: none;
            background: #fef2f2;
            color: #ef4444;
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 10px;
            cursor: pointer;
            font-weight: 700;
            font-family: 'K2D', 'Sarabun', sans-serif;
            transition: all 0.2s;
            font-size: 0.85rem;
        }

        .btn-logout:hover {
            background: #ef4444;
            color: white;
            border-color: #ef4444;
            box-shadow: 0 4px 10px rgba(239, 68, 68, 0.15);
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

        .btn-take-assessment:active,
        .btn-back-home:active {
            transform: translateY(0);
        }

        @media (max-width: 576px) {

            .btn-take-assessment span,
            .btn-back-home span {
                display: none;
            }

            .btn-take-assessment,
            .btn-back-home {
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
                width: 280px;
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
    </style>
    @yield('extra_css')
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

    <aside class="admin-sidebar" id="sidebar">
        <div class="sidebar-brand">
            <div class="brand-logo-frame">
                <img src="{{ asset('images/logo-login.jpg') }}" alt="Smart Salt Sodium Monitor">
            </div>
        </div>

        <ul class="sidebar-nav">
            @if (auth()->user()->User_rank_id == 1)
                <!-- Admin Only Menus -->
                <div class="nav-section-title">จัดการข้อมูล</div>
                <li class="nav-item">
                    <a href="{{ route('admin.awareness') }}"
                        class="nav-link {{ request()->routeIs('admin.awareness') ? 'active' : '' }}">
                        <i class="fas fa-file-signature"></i>
                        <span>การประเมินความตระหนักรู้</span>
                    </a>
                </li>
                <li class="nav-item">
                    <a href="{{ route('admin.awareness.settings.criteria') }}"
                        class="nav-link {{ request()->routeIs('admin.awareness.settings.criteria') ? 'active' : '' }}"
                        style="padding-left: 40px;">
                        <i class="fas fa-sliders"></i>
                        <span style="font-size: 0.78rem; font-style: italic;">ตั้งค่าเกณฑ์ความตระหนักรู้</span>
                    </a>
                </li>
                <li class="nav-item">
                    <a href="{{ route('admin.awareness.settings.dashboard') }}"
                        class="nav-link {{ request()->routeIs('admin.awareness.settings.dashboard') ? 'active' : '' }}"
                        style="padding-left: 40px;">
                        <i class="fas fa-table-cells"></i>
                        <span style="font-style: italic;">ตั้งค่าแดชบอร์ด</span>
                    </a>
                </li>
                <li class="nav-item">
                    <a href="{{ route('admin.awareness.settings.scoring') }}"
                        class="nav-link {{ request()->routeIs('admin.awareness.settings.scoring') ? 'active' : '' }}"
                        style="padding-left: 40px;">
                        <i class="fas fa-calculator"></i>
                        <span style="font-style: italic;">ตั้งค่าคะแนนความตระหนักรู้</span>
                    </a>
                </li>
                <li class="nav-item">
                    <a href="{{ route('admin.sodium-menus') }}"
                        class="nav-link {{ request()->routeIs('admin.sodium-menus', 'admin.reduced-sodium-menu-dashboard') ? 'active' : '' }}">
                        <i class="fas fa-poll"></i>
                        <span>เมนูลดโซเดียม</span>
                    </a>
                </li>
                <li class="nav-item">
                    <a href="{{ route('admin.sodium-products') }}"
                        class="nav-link {{ request()->routeIs('admin.sodium-products', 'admin.reduced-sodium-products-dashboard') ? 'active' : '' }}">
                        <i class="fas fa-bottle-droplet"></i>
                        <span>ผลิตภัณฑ์ลดโซเดียม</span>
                    </a>
                </li>
                <li class="nav-item">
                    <a href="{{ route('admin.hi.index') }}"
                        class="nav-link {{ request()->routeIs('admin.hi.index', 'admin.new-ht-cases-dashboard') ? 'active' : '' }}">
                        <i class="fas fa-chart-line"></i>
                        <span>อัตราป่วยรายใหม่ HT</span>
                    </a>
                </li>

                <div class="nav-section-title">จัดการแบบประเมิน</div>
                <li class="nav-item">
                    <a href="{{ route('admin.kidney-dhb-list') }}"
                        class="nav-link {{ request()->routeIs('admin.kidney-dhb-list', 'admin.kidney-dhb', 'admin.kidney-dhb.show') ? 'active' : '' }}">
                        <i class="fas fa-file-medical"></i>
                        <span>แบบประเมินพชอ.ไต</span>
                        @if (isset($unreadKidneyCount) && $unreadKidneyCount > 0)
                            <span class="nav-badge">{{ $unreadKidneyCount }}</span>
                        @endif
                    </a>
                </li>
                <li class="nav-item">
                    <a href="{{ route('admin.salt-assessment-list') }}"
                        class="nav-link {{ request()->routeIs('admin.salt-assessment-list', 'admin.report-progress') ? 'active' : '' }}">
                        <i class="fas fa-clipboard-check"></i>
                        <span>แบบประเมินลดการบริโภคเกลือ</span>
                        @if (isset($unreadSaltCount) && $unreadSaltCount > 0)
                            <span class="nav-badge">{{ $unreadSaltCount }}</span>
                        @endif
                    </a>
                </li>

                <div class="nav-section-title">จัดการระบบ</div>
                <li class="nav-item">
                    <a href="{{ route('admin.users.index') }}"
                        class="nav-link {{ request()->routeIs('admin.users.index') ? 'active' : '' }}">
                        <i class="fas fa-users-cog"></i>
                        <span>จัดการผู้ใช้งาน</span>
                        @if (isset($pendingUserCount) && $pendingUserCount > 0)
                            <span class="nav-badge">{{ $pendingUserCount }}</span>
                        @endif
                    </a>
                </li>
                <li class="nav-item">
                    <a href="{{ route('admin.fiscal-years.index') }}"
                        class="nav-link {{ request()->routeIs('admin.fiscal-years.index') ? 'active' : '' }}">
                        <i class="fas fa-calendar-alt"></i>
                        <span>จัดการปีงบประมาณ</span>
                    </a>
                </li>
                <li class="nav-item">
                    <a href="{{ route('admin.excel-templates.index') }}"
                        class="nav-link {{ request()->routeIs('admin.excel-templates.index') ? 'active' : '' }}">
                        <i class="fas fa-file-excel"></i>
                        <span>จัดการแบบฟอร์ม Excel</span>
                    </a>
                </li>
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
                        class="nav-link {{ request()->routeIs('admin.kidney-dhb-list', 'admin.kidney-dhb', 'admin.kidney-dhb.show') ? 'active' : '' }}">
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
                        class="nav-link {{ request()->routeIs('admin.kidney-dhb-list', 'admin.kidney-dhb', 'admin.kidney-dhb.show') ? 'active' : '' }}">
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
                        class="nav-link {{ request()->routeIs('admin.kidney-dhb-list', 'admin.kidney-dhb', 'admin.kidney-dhb.show') ? 'active' : '' }}">
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
                        class="nav-link {{ request()->routeIs('admin.kidney-dhb-list', 'admin.kidney-dhb', 'admin.kidney-dhb.show') ? 'active' : '' }}">
                        <i class="fas fa-file-medical"></i>
                        <span>พชอ.ไต</span>
                    </a>
                </li> --}}

            @endif
        </ul>
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
            <div class="admin-user-nav">
                <!-- User profile moved to sidebar -->
                @if(auth()->user()->User_rank_id == 2 && request()->routeIs('admin.salt-assessment-list'))
                    <a href="{{ route('admin.report-progress') }}" class="btn-take-assessment">
                        <i class="fas fa-file-signature"></i>
                        <span>ทำแบบประเมิน</span>
                    </a>
                @elseif(auth()->user()->User_rank_id == 2 && request()->routeIs('admin.report-progress'))
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
                    <a href="{{ route('admin.kidney-dhb') }}" class="btn-take-assessment">
                        <i class="fas fa-file-signature"></i>
                        <span>ทำแบบประเมิน</span>
                    </a>
                @elseif(in_array(auth()->user()->User_rank_id, [2, 3, 4]) && request()->routeIs('admin.kidney-dhb'))
                    <a href="{{ route('admin.kidney-dhb-list') }}" class="btn-back-home">
                        <i class="fas fa-arrow-left"></i>
                        <span>กลับหน้าหลัก</span>
                    </a>
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

    <div id="navLoadingOverlay"
        style="position: fixed; top: var(--admin-banner-height); left: var(--sidebar-width); width: calc(100% - var(--sidebar-width)); height: calc(100% - var(--admin-banner-height)); background: rgba(255, 255, 255, 0.35); backdrop-filter: blur(10px); -webkit-backdrop-filter: blur(10px); display: none; z-index: 900; opacity: 0;">
    </div>

    <!-- Core Scripts -->
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/popper.js@1.16.1/dist/umd/popper.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@4.6.2/dist/js/bootstrap.min.js"></script>
    <!-- SweetAlert2 -->
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>

    <script>
        $(document).ready(function() {
            // Show the blur/loading overlay on every real sidebar navigation
            // click - a consistent page-transition standard, not just a
            // handful of specific routes.
            $('a.nav-link')
                .on('click', function(e) {
                    // If it's just a placeholder (no real destination), don't load
                    const href = $(this).attr('href');
                    if (!href || href === '#' || href === 'javascript:void(0);') return;

                    // Show overlay with smooth fade
                    $('#navLoadingOverlay').css('display', 'flex').animate({
                        opacity: 1
                    }, 400);
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
            <div class="logout-confirm-icon"><i class="fas fa-right-from-bracket"></i></div>
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

    @yield('extra_js')
</body>

</html>
