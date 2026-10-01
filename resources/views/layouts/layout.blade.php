<!DOCTYPE html>
<html lang="th">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>@yield('title', 'Salt & Sodium Smart Monitor')</title>

    <!-- Resource hints: open connections to the CDNs early so the fonts/icons
         don't wait for DNS + TLS negotiation once the stylesheet is discovered -->
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link rel="preconnect" href="https://cdn.jsdelivr.net" crossorigin>
    <link rel="dns-prefetch" href="https://fonts.googleapis.com">
    <link rel="dns-prefetch" href="https://fonts.gstatic.com">
    <link rel="dns-prefetch" href="https://cdn.jsdelivr.net">

    <!-- Sarabun (Google Fonts) -->
    <!-- Loaded non-blocking: the fallback sans-serif font paints immediately
         and swaps to Sarabun once it arrives (display=swap), so first paint
         no longer waits on this request. -->
    <link rel="preload" as="style"
        href="https://fonts.googleapis.com/css2?family=Sarabun:ital,wght@0,100;0,200;0,300;0,400;0,500;0,600;0,700;0,800;1,100;1,200;1,300;1,400;1,500;1,600;1,700;1,800&display=swap"
        onload="this.onload=null;this.rel='stylesheet'">
    <!-- Itim (Google Fonts) - used for the site footer brand text -->
    <link rel="preload" as="style"
        href="https://fonts.googleapis.com/css2?family=Itim&display=swap"
        onload="this.onload=null;this.rel='stylesheet'">
    <!-- Kanit (Google Fonts) - used for the site header brand text / nav,
         a clean geometric Thai sans-serif with real weight steps, for a
         more formal/official register than the rounded, single-weight Itim. -->
    <link rel="preload" as="style"
        href="https://fonts.googleapis.com/css2?family=Kanit:wght@400;500;600;700&display=swap"
        onload="this.onload=null;this.rel='stylesheet'">
    <!-- Kodchasan + Trirong (Google Fonts) - used on the staff login/register page -->
    <link rel="preload" as="style"
        href="https://fonts.googleapis.com/css2?family=Kodchasan:wght@500;600;700;800&family=Trirong:wght@400;500;600;700&display=swap"
        onload="this.onload=null;this.rel='stylesheet'">
    <!-- Font Awesome for icons -->
    <link rel="preload" as="style" href="https://cdn.jsdelivr.net/npm/@fortawesome/fontawesome-free@6.5.1/css/all.min.css"
        onload="this.onload=null;this.rel='stylesheet'">
    <noscript>
        <link
            href="https://fonts.googleapis.com/css2?family=Sarabun:ital,wght@0,100;0,200;0,300;0,400;0,500;0,600;0,700;0,800;1,100;1,200;1,300;1,400;1,500;1,600;1,700;1,800&display=swap"
            rel="stylesheet">
        <link href="https://fonts.googleapis.com/css2?family=Itim&display=swap" rel="stylesheet">
        <link href="https://fonts.googleapis.com/css2?family=Kanit:wght@400;500;600;700&display=swap" rel="stylesheet">
        <link
            href="https://fonts.googleapis.com/css2?family=Kodchasan:wght@500;600;700;800&family=Trirong:wght@400;500;600;700&display=swap"
            rel="stylesheet">
        <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/@fortawesome/fontawesome-free@6.5.1/css/all.min.css">
    </noscript>
    <style>
        /* Protection for icons against global font overrides.
           Brand icons (.fab / .fa-brands - Facebook, Line, etc.) live in a
           SEPARATE font ("Font Awesome 6 Brands", weight 400) from the
           solid/regular set - forcing them onto "Font Awesome 6 Free" at
           weight 900 silently renders as a blank glyph, since that font
           has no Facebook/brand characters at all. They need their own
           rule, kept below the general one so it wins on specificity. */
        .fas,
        .far,
        .fa-solid,
        .fa-regular,
        i {
            font-family: "Font Awesome 6 Free" !important;
            font-weight: 900 !important;
            display: inline-block !important;
            font-style: normal !important;
        }

        .fab,
        .fa-brands {
            font-family: "Font Awesome 6 Brands" !important;
            font-weight: 400 !important;
            display: inline-block !important;
            font-style: normal !important;
        }
    </style>
    <style>
        :root {
            --primary-color: #e11d48;
            /* Deep Rose / Crimson */
            --secondary-color: #ffffff;
            /* Pure white background */
            --accent-color: #f8bbd0;
            --text-color: #2d3436;
            --navbar-height: 70px;
            --top-bar-height: 50px;
            --sidebar-width: 0px;
            --glass-bg: rgba(255, 255, 255, 0.95);
            --header-shadow: 0 4px 6px -1px rgba(0, 0, 0, 0.05), 0 2px 4px -1px rgba(0, 0, 0, 0.03);
            /* Dark -> light running strictly left to right (90deg), instead
               of the earlier 145deg diagonal - the darker crimson anchors
               the left edge (where the brand mark sits) and eases toward
               the lighter rose on the right. */
            --pink-gradient: linear-gradient(90deg, #d81e4a 0%, #f0527f 100%);
        }

        * {
            box-sizing: border-box;
        }

        html {
            /* Scales the whole public site down to ~90% so it reads less
               "zoomed in" by default (matches what 90% browser zoom looked
               like) without anyone having to zoom out manually each time. */
            zoom: 90%;
        }

        body {
            font-family: 'Sarabun', 'TH Sarabun New', 'TH Sarabun PS', sans-serif;
            background-color: var(--secondary-color);
            overflow-x: hidden;
            /* Prevent horizontal scrollbar */
            color: var(--text-color);
            margin: 0;
            display: block;
            /* Switch to block for top-nav layout */
            min-height: 100vh;
        }

        /* Soft aurora background, shared across all 7 themed report pages
           (see $headerThemes above - the same set that gets a colored
           content-header card gets this too): an off-white base with
           three blurred glows - a warm rose/red one pinned to the
           top-left corner, a cool lavender one pinned to the top-right
           corner, and a much fainter rose glow drifting near the page's
           own center. background-attachment: fixed keeps all three
           anchored to the viewport (not the page), so they read as a
           fixed backdrop rather than scrolling with content. */
        body.page-aurora {
            background-color: #fafafa !important;
            background-image:
                radial-gradient(ellipse 560px 440px at 6% 4%, rgba(248, 113, 113, 0.16) 0%, rgba(248, 113, 113, 0) 62%),
                radial-gradient(ellipse 500px 400px at 95% 6%, rgba(167, 139, 250, 0.15) 0%, rgba(167, 139, 250, 0) 62%),
                radial-gradient(ellipse 640px 540px at 50% 46%, rgba(244, 63, 94, 0.05) 0%, rgba(244, 63, 94, 0) 58%) !important;
            background-repeat: no-repeat !important;
            background-attachment: fixed !important;
        }

        /* Header / Navbar Styling - one continuous bar: brand mark on the
           left, icon+text nav inline in the middle, staff sign-in as its
           own pill on the right past a hairline divider. Matches the
           reference screenshot. */
        .site-header {
            position: sticky;
            top: 0;
            z-index: 11500;
            width: 100%;
            box-shadow: var(--header-shadow);
        }

        .header-container {
            display: flex;
            align-items: center;
            gap: 18px;
            width: 100%;
            min-height: 68px;
            padding: 10px 4%;
            background:
                radial-gradient(circle at 8% 0%, rgba(255, 255, 255, 0.22), transparent 45%),
                var(--pink-gradient);
        }

        .brand-container {
            display: flex;
            flex-direction: row;
            align-items: center;
            gap: 12px;
            text-decoration: none;
            flex-shrink: 0;
        }

        .brand-logo-wrapper {
            background: #ffffff;
            padding: 7px;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            box-shadow: 0 6px 16px rgba(0, 0, 0, 0.18);
            border: 2px solid rgba(255, 255, 255, 0.6);
            transition: all 0.3s ease;
            width: 54px;
            height: 54px;
            flex-shrink: 0;
        }

        .brand-logo-wrapper:hover {
            transform: translateY(-2px) rotate(5deg);
            box-shadow: 0 10px 22px rgba(0, 0, 0, 0.22);
        }

        .brand-container img {
            height: 28px;
            width: auto;
            transition: transform 0.3s ease;
        }

        /* The header's sole mark: the SSS icon, sized to fill its round
           badge with a clear, confident presence now that it stands alone. */
        .brand-logo-wrapper--sss img {
            height: 32px;
            width: auto;
        }

        .brand-text-group {
            display: flex;
            flex-direction: column;
            line-height: 1.25;
            align-items: flex-start;
            text-align: left;
            gap: 2px;
            min-width: 0;
        }

        .brand-text-main {
            font-family: 'Itim', 'Sarabun', sans-serif;
            font-size: 0.98rem;
            font-weight: 400;
            color: #ffffff;
            letter-spacing: 0.2px;
            text-shadow: 0 1px 5px rgba(0, 0, 0, 0.15);
            white-space: nowrap;
            line-height: 1.2;
        }

        .brand-text-sub {
            font-family: 'Itim', 'Sarabun', sans-serif;
            font-size: 0.64rem;
            color: #ffffff;
            font-weight: 400;
            letter-spacing: 1px;
            text-transform: uppercase;
            white-space: normal;
        }

        .main-navbar {
            display: flex;
            align-items: center;
            justify-content: flex-end;
            flex: 1;
            min-width: 0;
        }

        .main-navbar .nav-links {
            display: flex;
            align-items: center;
            list-style: none;
            margin: 0;
            padding: 0;
            gap: 4px;
            overflow-x: auto;
            scrollbar-width: none;
            /* Firefox */
        }

        .main-navbar .nav-links::-webkit-scrollbar {
            display: none;
            /* Chrome/Safari */
        }

        .main-navbar .nav-item {
            flex-shrink: 0;
        }

        /* Each link sits inline on the gradient bar - the CURRENT page
           fills a solid white pill (icon + text switch to the brand red),
           everything else stays translucent white until hovered. */
        .main-navbar .nav-link {
            display: flex;
            align-items: center;
            gap: 5px;
            height: 34px;
            padding: 0 10px;
            border-radius: 999px;
            color: #ffffff;
            text-decoration: none;
            font-family: 'Itim', 'Sarabun', sans-serif;
            font-weight: 400;
            font-size: 0.82rem;
            white-space: nowrap;
            transition: all 0.2s ease;
        }

        .main-navbar .nav-link i {
            font-size: 0.95rem;
            opacity: 1;
        }

        .main-navbar .nav-link:hover {
            color: #ffffff;
            background: rgba(255, 255, 255, 0.14);
        }

        .main-navbar .nav-link.active {
            color: var(--primary-color);
            background: #ffffff;
            font-weight: 600;
            box-shadow: 0 4px 12px -2px rgba(0, 0, 0, 0.18);
        }

        .main-navbar .nav-link.active i {
            opacity: 1;
        }

        /* The staff sign-in, set apart from the wayfinding list by a
           hairline divider and its own solid-white pill - reads as "an
           action" rather than "another page to browse to". */
        .header-actions {
            display: flex;
            align-items: center;
            flex-shrink: 0;
            padding-left: 14px;
            margin-left: 2px;
            border-left: 1px solid rgba(255, 255, 255, 0.28);
        }

        .header-cta {
            display: flex;
            align-items: center;
            gap: 7px;
            height: 34px;
            padding: 0 14px 0 7px;
            border-radius: 999px;
            background: #ffffff;
            color: var(--primary-color);
            text-decoration: none;
            font-family: 'Itim', 'Sarabun', sans-serif;
            font-weight: 400;
            font-size: 0.78rem;
            white-space: nowrap;
            box-shadow: 0 4px 14px -2px rgba(0, 0, 0, 0.2);
            transition: all 0.2s ease;
        }

        .header-cta:hover {
            transform: translateY(-1px);
            box-shadow: 0 6px 18px -2px rgba(0, 0, 0, 0.26);
        }

        .header-cta-icon {
            width: 21px;
            height: 21px;
            min-width: 21px;
            border-radius: 50%;
            background: var(--pink-gradient);
            color: #ffffff;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 0.65rem;
        }

        .user-profile {
            display: flex;
            align-items: center;
            gap: 12px;
            padding-left: 20px;
            border-left: 1px solid #fce4ec;
            margin-left: 10px;
        }

        .avatar {
            width: 38px;
            height: 38px;
            background: var(--pink-gradient);
            color: white;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 1rem;
            box-shadow: 0 2px 8px rgba(225, 29, 72, 0.3);
        }

        .main-content {
            position: relative;
            margin-left: 0;
            padding: 25px 3%;
            max-width: 1600px;
            /* Wider max for large screens */
            width: 95%;
            /* Fluid width */
            margin: 0 auto;
        }

        .content-header {
            margin-bottom: 25px;
            padding: 15px 25px;
            background: white;
            border-radius: 18px;
            box-shadow: 0 2px 10px rgba(0, 0, 0, 0.02);
            border-left: 5px solid var(--primary-color);
            display: flex;
            align-items: center;
            gap: 16px;
        }

        .content-header h1 {
            font-size: 1.4rem;
            font-weight: 800;
            color: #2d3436;
            margin: 0;
        }

        /* Themed variant: each of the 7 report pages gets its own color
           (matching that page's card in the site's quick-menu palette),
           an icon badge, and a short subtitle - replacing the plain white
           / red-left-border bar every page used to share. The background
           itself never carries the full-strength accent (that stays
           reserved for the icon badge below) - it starts as a thin ~20%
           tint of the accent over white (kept faint/diffuse on purpose,
           so the card reads as a soft wash rather than a bold color
           block that clashes with the pink header above it), eases
           through the page's own light bgA pastel, and settles into bgB
           toward the right. No separate border-left, so there is no seam
           between a border and the fill. Pages outside that set (staff,
           etc.) keep the plain bar above untouched. */
        .content-header.content-header-themed {
            border-left: none;
            background: linear-gradient(100deg,
                color-mix(in srgb, var(--ch-accent) 20%, #fff) 0%,
                var(--ch-bg-a) 45%,
                var(--ch-bg-b) 100%);
            box-shadow: 0 8px 24px -18px rgba(0, 0, 0, 0.06);
        }

        .content-header-icon {
            width: 46px;
            height: 46px;
            min-width: 46px;
            border-radius: 14px;
            background: var(--ch-accent);
            color: #ffffff;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 1.2rem;
            box-shadow: 0 4px 10px rgba(var(--ch-accent-rgb), 0.35);
        }

        .content-header-text {
            display: flex;
            flex-direction: column;
            gap: 2px;
            min-width: 0;
        }

        .content-header.content-header-themed h1 {
            /* Darkened past the raw accent (mixed toward black) so the
               title stays bold and legible against the lightened
               background wash above, instead of reading washed-out. */
            color: color-mix(in srgb, var(--ch-accent) 75%, #000);
        }

        .content-header-subtitle {
            margin: 0;
            font-size: 0.85rem;
            font-weight: 500;
            color: #64748b;
        }

        .content-header.content-header-themed .content-header-subtitle {
            /* Same idea for the subtitle: a darker, accent-tinted slate
               instead of the plain neutral gray, so it reads with more
               weight on the themed cards. */
            color: color-mix(in srgb, var(--ch-accent) 40%, #334155);
        }

        /* Sidebar Toggle Button */
        .sidebar-toggle {
            display: none;
            background: none;
            border: none;
            font-size: 1.5rem;
            color: var(--sidebar-text);
            cursor: pointer;
            padding: 5px;
            margin-right: 15px;
        }

        /* Responsive Adjustment for Top Nav - the bar stays a single row
           all the way down to the 768px handoff to the mobile bottom tab
           bar; the nav list also scrolls horizontally
           (.main-navbar .nav-links) as a last-resort fallback, but every
           rule below is sized/verified so that fallback is never actually
           needed at any common desktop or laptop width - all 7 items plus
           the staff link stay on screen. */
        @media (max-width: 1500px) {
            .header-container {
                gap: 10px;
            }

            /* Below this width the title no longer fits on one line
               alongside every nav item and the staff link - wrapping it
               to two lines here (rather than at every width) frees the
               room those need, while keeping the reference layout's
               single-line title at ordinary desktop widths. */
            .brand-text-group {
                max-width: 195px;
            }

            .brand-text-main {
                white-space: normal;
            }

            .main-navbar .nav-links {
                gap: 1px;
            }

            .main-navbar .nav-link {
                gap: 3px;
                padding: 0 7px;
                font-size: 0.72rem;
            }

            .main-navbar .nav-link i {
                font-size: 0.86rem;
            }

            .header-cta > span:not(.header-cta-icon) {
                display: none;
            }

            .header-cta {
                padding: 0 7px;
            }
        }

        @media (max-width: 992px) {
            .header-container {
                padding: 10px 3%;
            }

            .brand-text-sub {
                display: none;
            }

            /* Text labels no longer fit next to every icon in this range -
               drop to icon-only pills (title attribute keeps a tooltip) so
               all 7 items still fit without the nav row silently
               scroll-clipping any of them. */
            .main-navbar .nav-links {
                gap: 2px;
            }

            .main-navbar .nav-link span {
                display: none;
            }

            .main-navbar .nav-link {
                padding: 0 8px;
                gap: 0;
            }

            .main-navbar .nav-link i {
                font-size: 1.05rem;
            }
        }

        @media (max-width: 768px) {
            .site-header {
                position: relative;
            }

            .brand-text-main {
                font-size: 0.9rem;
            }

            .brand-text-sub {
                display: none;
            }

            .brand-logo-wrapper {
                width: 46px;
                height: 46px;
                padding: 6px;
            }

            .brand-container img {
                height: 22px;
            }

            .brand-logo-wrapper--sss img {
                height: 26px;
            }

            .main-navbar,
            .header-actions {
                display: none;
            }

            .main-content {
                padding: 20px 15px 80px 15px;
            }
        }

        /* Small-phone fine-tuning (~480px and below): the content-header
           card's fixed padding/icon size otherwise eats a lot of the
           narrow viewport, so tighten it here rather than at the shared
           768px handoff (which also has to fit larger phones/small
           tablets and shouldn't shrink for them). */
        @media (max-width: 480px) {
            .main-content {
                padding: 16px 10px 80px 10px;
            }

            .content-header {
                padding: 12px 16px;
                gap: 12px;
                border-radius: 14px;
            }

            .content-header-icon {
                width: 38px;
                height: 38px;
                min-width: 38px;
                border-radius: 11px;
                font-size: 1rem;
            }

            .content-header h1 {
                font-size: 1.15rem;
            }

            .content-header-subtitle {
                font-size: 0.78rem;
            }
        }

        /* Quality Dashboard Cards */
        .card {
            background: white;
            padding: 25px;
            border-radius: 20px;
            box-shadow: 0 10px 30px rgba(0, 0, 0, 0.05);
            margin-bottom: 30px;
            border: 1px solid rgba(0, 0, 0, 0.02);
            transition: transform 0.3s ease;
        }

        .card:hover {
            transform: translateY(-5px);
        }

        .card-title {
            font-size: 1.4rem;
            font-weight: 700;
            margin-bottom: 20px;
            color: #1e293b;
            display: flex;
            align-items: center;
            gap: 10px;
        }

        /* Mobile Bottom Nav Bar */
        .mobile-nav {
            display: none;
            position: fixed;
            bottom: 0;
            left: 0;
            width: 100%;
            background: white;
            box-shadow: 0 -2px 10px rgba(0, 0, 0, 0.1);
            z-index: 1001;
            padding: 10px 0;
            justify-content: space-around;
            border-top-left-radius: 20px;
            border-top-right-radius: 20px;
        }

        /* Actually switches the bar above on at the same 768px handoff
           where the top nav (.main-navbar/.header-actions) disappears -
           placed AFTER the base rule (not inside that earlier @media
           block) so it wins the cascade tie against the unconditional
           `display: none` above; a same-specificity rule placed earlier
           in the stylesheet never wins regardless of its own media query
           matching, which is why an earlier attempt at this had no
           visible effect. */
        @media (max-width: 768px) {
            .mobile-nav {
                display: flex;
            }
        }

        .mobile-nav-item {
            display: flex;
            flex-direction: column;
            align-items: center;
            color: #666;
            text-decoration: none;
            font-size: 0.75rem;
        }

        .mobile-nav-item i {
            font-size: 1.2rem;
            margin-bottom: 4px;
        }

        .mobile-nav-item.active {
            color: var(--primary-color);
        }

        .btn {
            padding: 10px 20px;
            border-radius: 10px;
            font-weight: 600;
            border: none;
            cursor: pointer;
            transition: all 0.2s;
            font-family: 'Sarabun', sans-serif;
        }

        .btn-primary {
            background-color: var(--primary-color);
            color: white;
        }

        .btn-primary:hover {
            background-color: var(--hover-color);
            box-shadow: 0 4px 15px rgba(225, 29, 72, 0.4);
        }

        /* ── Site Footer ──
           A distinct "end of page" zone. Three columns: org/contact,
           social + official links, and a map, matching the header's own
           logo/brand treatment.

           "Pastel Rose" tone: a soft blush-pink field (not flat - a
           gentle top-to-bottom gradient) with white cards floating on
           top, rather than the earlier dark glass panel. The brand red
           (var(--primary-color)) now carries identity as solid icon
           fills and the accent button, instead of being the one bright
           note against a dark field - it's a lighter, more "front desk of
           a public health office" register than the dark themes. */
        .site-footer {
            width: 100%;
            margin-top: 40px;
            position: relative;
            background: linear-gradient(180deg, #fff1f6 0%, #ffdce9 55%, #ffcbdd 100%);
            color: #5c1433;
        }

        .site-footer::before {
            content: '';
            display: block;
            height: 4px;
            width: 100%;
            background: var(--pink-gradient);
        }

        .footer-inner-wrap {
            max-width: 1360px;
            margin: 0 auto;
            padding: 44px 5% 26px;
        }

        .footer-grid {
            display: grid;
            grid-template-columns: 1.3fr 1fr 1.15fr;
            gap: 40px;
            margin-bottom: 34px;
        }

        .footer-brand {
            display: flex;
            align-items: center;
            gap: 14px;
            margin-bottom: 14px;
        }

        .footer-logo-wrapper {
            width: 50px;
            height: 50px;
            border-radius: 50%;
            background: #ffffff;
            display: flex;
            align-items: center;
            justify-content: center;
            box-shadow: 0 6px 16px rgba(225, 29, 72, 0.18);
            flex-shrink: 0;
        }

        .footer-logo-wrapper img {
            height: 30px;
            width: auto;
        }

        .footer-org-name {
            font-family: 'Itim', 'Sarabun', sans-serif;
            font-size: 1.05rem;
            font-weight: 400;
            color: #4a0f28;
            line-height: 1.35;
        }

        .footer-org-sub {
            font-size: 0.82rem;
            color: var(--primary-color);
            font-weight: 500;
            margin-top: 2px;
        }

        .footer-desc {
            font-size: 0.85rem;
            line-height: 1.75;
            color: rgba(74, 15, 40, 0.66);
            margin: 14px 0 18px;
            max-width: 40ch;
        }

        .footer-contact-list {
            display: flex;
            flex-direction: column;
            gap: 11px;
            font-size: 0.85rem;
        }

        .footer-contact-row {
            display: flex;
            align-items: flex-start;
            gap: 11px;
            color: rgba(74, 15, 40, 0.82);
        }

        .footer-contact-row a {
            color: inherit;
            text-decoration: none;
        }

        .footer-contact-row a:hover {
            color: var(--primary-color);
        }

        .footer-contact-icon {
            width: 30px;
            height: 30px;
            border-radius: 50%;
            background: var(--primary-color);
            display: flex;
            align-items: center;
            justify-content: center;
            color: #ffffff;
            flex-shrink: 0;
            font-size: 0.78rem;
            box-shadow: 0 3px 8px rgba(225, 29, 72, 0.3);
            transition: all 0.2s;
        }

        .footer-contact-row:hover .footer-contact-icon {
            transform: translateY(-1px);
            box-shadow: 0 5px 12px rgba(225, 29, 72, 0.4);
        }

        .footer-heading {
            font-size: 0.8rem;
            font-weight: 700;
            color: #4a0f28;
            text-transform: uppercase;
            letter-spacing: 0.06em;
            display: flex;
            align-items: center;
            gap: 8px;
            margin-bottom: 16px;
        }

        .footer-heading::before {
            content: '';
            width: 7px;
            height: 7px;
            border-radius: 50%;
            background: var(--primary-color);
            flex-shrink: 0;
        }

        .footer-link-card {
            display: flex;
            align-items: center;
            gap: 12px;
            padding: 12px 14px;
            border-radius: 14px;
            background: #ffffff;
            border: 1px solid rgba(225, 29, 72, 0.1);
            box-shadow: 0 4px 14px rgba(225, 29, 72, 0.1);
            color: #4a0f28;
            text-decoration: none;
            transition: all 0.2s;
            margin-bottom: 10px;
        }

        .footer-link-card:hover {
            border-color: rgba(225, 29, 72, 0.3);
            box-shadow: 0 8px 20px rgba(225, 29, 72, 0.18);
            transform: translateY(-1px);
        }

        .footer-link-icon {
            width: 38px;
            height: 38px;
            border-radius: 10px;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 1.05rem;
            color: #ffffff;
            flex-shrink: 0;
            transition: all 0.2s;
        }

        .footer-link-icon.fb {
            background: #1877f2;
        }

        .footer-link-icon.web {
            background: var(--primary-color);
        }

        .footer-link-text {
            display: flex;
            flex-direction: column;
            line-height: 1.3;
            min-width: 0;
        }

        .footer-link-label {
            font-size: 0.7rem;
            color: rgba(74, 15, 40, 0.5);
        }

        .footer-link-value {
            font-size: 0.85rem;
            font-weight: 600;
            overflow-wrap: anywhere;
        }

        .footer-link-arrow {
            margin-left: auto;
            font-size: 0.7rem;
            color: rgba(74, 15, 40, 0.35);
            flex-shrink: 0;
        }

        .footer-map-wrap {
            position: relative;
            width: 100%;
            height: 190px;
            border-radius: 16px;
            overflow: hidden;
            background: #ffffff;
            border: 1px solid rgba(225, 29, 72, 0.1);
            box-shadow: 0 4px 14px rgba(225, 29, 72, 0.1);
        }

        .footer-map-wrap iframe {
            width: 100%;
            height: 100%;
            border: 0;
            opacity: 0.96;
            transition: opacity 0.3s;
        }

        .footer-map-wrap:hover iframe {
            opacity: 1;
        }

        .footer-map-btn {
            position: absolute;
            bottom: 10px;
            right: 10px;
            display: flex;
            align-items: center;
            gap: 6px;
            padding: 8px 13px;
            border-radius: 10px;
            background: var(--primary-color);
            color: #ffffff;
            font-size: 0.72rem;
            font-weight: 600;
            text-decoration: none;
            box-shadow: 0 4px 12px rgba(225, 29, 72, 0.35);
            transition: all 0.2s;
        }

        .footer-map-btn:hover {
            background: #c81640;
            transform: translateY(-1px);
        }

        .footer-bottom {
            display: flex;
            flex-wrap: wrap;
            align-items: center;
            justify-content: space-between;
            gap: 12px;
            padding-top: 22px;
            border-top: 1px solid rgba(225, 29, 72, 0.15);
            font-size: 0.78rem;
            color: rgba(74, 15, 40, 0.58);
        }

        .footer-bottom-links {
            display: flex;
            align-items: center;
            gap: 14px;
        }

        .footer-bottom-links a {
            color: inherit;
            text-decoration: none;
        }

        .footer-bottom-links a:hover {
            color: var(--primary-color);
        }

        @media (max-width: 900px) {
            .footer-grid {
                grid-template-columns: 1fr;
                gap: 32px;
            }
        }

        @media (max-width: 768px) {
            .footer-bottom {
                flex-direction: column;
                text-align: center;
            }
        }
    </style>
    @yield('extra_css')
</head>

@php
    // Per-page color theme for the content-header card, matched to this
    // page's card in the site's quick-menu palette - and now also what
    // decides the <body> "page-aurora" background wash below. Moved up
    // here (out of its old spot right before <main>) so it's available
    // for the <body> class attribute too, not just the content header
    // further down. Only these 7 public report pages get a theme (keyed
    // by route name) - any other page (staff, etc.) falls back to the
    // plain bar and the plain body background.
    $headerThemes = [
        'home' => ['accent' => '#0e7490', 'accentRgb' => '14, 116, 144', 'bgA' => '#ecfeff', 'bgB' => '#cffafe', 'icon' => 'fa-chart-line'],
        'awareness' => ['accent' => '#9333ea', 'accentRgb' => '147, 51, 234', 'bgA' => '#f7f0ff', 'bgB' => '#efe0ff', 'icon' => 'fa-brain'],
        'reduced-sodium-menu' => ['accent' => '#16a34a', 'accentRgb' => '22, 163, 74', 'bgA' => '#f0fdf5', 'bgB' => '#dcfce7', 'icon' => 'fa-bowl-food'],
        'reduced-sodium-products' => ['accent' => '#d97706', 'accentRgb' => '217, 119, 6', 'bgA' => '#fffbeb', 'bgB' => '#fef3c7', 'icon' => 'fa-bottle-droplet'],
        'new-ht-cases' => ['accent' => '#dc2626', 'accentRgb' => '220, 38, 38', 'bgA' => '#fff1f0', 'bgB' => '#fee2e2', 'icon' => 'fa-heart-pulse'],
        'kidney-dhb-report' => ['accent' => '#2563eb', 'accentRgb' => '37, 99, 235', 'bgA' => '#eff6ff', 'bgB' => '#dbeafe', 'icon' => 'fa-hospital-user'],
        'consumption-report' => ['accent' => '#7c3aed', 'accentRgb' => '124, 58, 237', 'bgA' => '#f5f0ff', 'bgB' => '#ede9fe', 'icon' => 'fa-file-contract'],
    ];
    $currentRouteName = optional(request()->route())->getName();
    $headerTheme = $headerThemes[$currentRouteName] ?? null;
    $headerSubtitle = trim($__env->yieldContent('header_subtitle'));
@endphp

<body class="{{ $headerTheme ? 'page-aurora' : '' }}">
    <div class="overlay" id="sidebar-overlay"></div>

    <header class="site-header">
        <div class="header-container">
            <div class="brand-container">
                <div class="brand-logo-wrapper brand-logo-wrapper--sss" title="Smart Salt &amp; Sodium Monitor">
                    <img src="{{ asset('images/logo-sss-icon.png') }}" alt="SSS Logo">
                </div>
                <div class="brand-text-group">
                    <span class="brand-text-main">ระบบเฝ้าระวังติดตามการบริโภคเกลือและโซเดียม เขตสุขภาพที่ 10</span>
                    <span class="brand-text-sub">Smart Salt &amp; Sodium Monitor</span>
                </div>
            </div>

            <nav class="main-navbar">
                <ul class="nav-links">
                    <li class="nav-item">
                        <a href="{{ route('home') }}" title="หน้าหลัก"
                            class="nav-link {{ request()->routeIs('home') ? 'active' : '' }}">
                            <i class="fas fa-chart-line"></i>
                            <span>หน้าหลัก</span>
                        </a>
                    </li>
                    <li class="nav-item">
                        <a href="{{ route('awareness') }}" title="ความตระหนักรู้"
                            class="nav-link {{ request()->routeIs('awareness') ? 'active' : '' }}">
                            <i class="fas fa-brain"></i>
                            <span>ความตระหนักรู้</span>
                        </a>
                    </li>
                    {{-- <li class="nav-item">
                        <a href="{{ route('food-survey') }}"
                            class="nav-link {{ request()->routeIs('food-survey') ? 'active' : '' }}">
                            <i class="fas fa-utensils"></i>
                            <span>สำรวจอาหาร</span>
                        </a>
                    </li> --}}
                    <li class="nav-item">
                        <a href="{{ route('reduced-sodium-menu') }}" title="เมนูลดโซเดียม"
                            class="nav-link {{ request()->routeIs('reduced-sodium-menu') ? 'active' : '' }}">
                            <i class="fas fa-bowl-food"></i>
                            <span>เมนูลดโซเดียม</span>
                        </a>
                    </li>
                    <li class="nav-item">
                        <a href="{{ route('reduced-sodium-products') }}" title="ผลิตภัณฑ์ลดโซเดียม"
                            class="nav-link {{ request()->routeIs('reduced-sodium-products') ? 'active' : '' }}">
                            <i class="fas fa-bottle-droplet"></i>
                            <span>ผลิตภัณฑ์ลดโซเดียม</span>
                        </a>
                    </li>
                    <li class="nav-item">
                        <a href="{{ route('new-ht-cases') }}" title="ผู้ป่วยใหม่ HT"
                            class="nav-link {{ request()->routeIs('new-ht-cases') ? 'active' : '' }}">
                            <i class="fas fa-heart-pulse"></i>
                            <span>ผู้ป่วยใหม่ HT</span>
                        </a>
                    </li>
                    <li class="nav-item">
                        <a href="{{ route('kidney-dhb-report') }}" title="พชอ.ไต"
                            class="nav-link {{ request()->routeIs('kidney-dhb-report') ? 'active' : '' }}">
                            <i class="fas fa-hospital-user"></i>
                            <span>พชอ.ไต</span>
                        </a>
                    </li>
                    <li class="nav-item">
                        <a href="{{ route('consumption-report') }}" title="แบบรายงานบริโภคเกลือ"
                            class="nav-link {{ request()->routeIs('consumption-report') ? 'active' : '' }}">
                            <i class="fas fa-file-contract"></i>
                            <span>แบบรายงานบริโภคเกลือ</span>
                        </a>
                    </li>
                </ul>
            </nav>

            <div class="header-actions">
                <a href="{{ route('staff') }}" class="header-cta">
                    <span class="header-cta-icon"><i class="fas fa-user-shield"></i></span>
                    <span>สำหรับเจ้าหน้าที่</span>
                </a>
            </div>
        </div>
    </header>

    <main class="main-content">
        <div class="content-header {{ $headerTheme ? 'content-header-themed' : '' }}"
            @if ($headerTheme)
                style="--ch-accent: {{ $headerTheme['accent'] }}; --ch-accent-rgb: {{ $headerTheme['accentRgb'] }}; --ch-bg-a: {{ $headerTheme['bgA'] }}; --ch-bg-b: {{ $headerTheme['bgB'] }};"
            @endif>
            @if ($headerTheme)
                <div class="content-header-icon"><i class="fa-solid {{ $headerTheme['icon'] }}"></i></div>
            @endif
            <div class="content-header-text">
                <h1>@yield('header_title', 'แดชบอร์ดระบบสุขภาพ')</h1>
                @if ($headerSubtitle !== '')
                    <p class="content-header-subtitle">{{ $headerSubtitle }}</p>
                @endif
            </div>
        </div>

        <section class="content">
            @yield('content')
        </section>
    </main>

    <footer class="site-footer">
        <div class="footer-inner-wrap">
            <div class="footer-grid">

                {{-- Column 1: organization + contact --}}
                <div>
                    <div class="footer-brand">
                        <div class="footer-logo-wrapper">
                            <img src="{{ asset('images/logo.png') }}" alt="DDC Logo"
                                onerror="this.onerror=null; this.parentElement.innerHTML='<i class=\'fa-solid fa-staff-snake\' style=\'color:#d81e4a;font-size:1.2rem;\'></i>'">
                        </div>
                        <div>
                            <div class="footer-org-name">สำนักงานป้องกันควบคุมโรคที่ 10</div>
                            <div class="footer-org-sub">จังหวัดอุบลราชธานี</div>
                        </div>
                    </div>
                    <p class="footer-desc">
                        ระบบเฝ้าระวังติดตามการบริโภคเกลือและโซเดียม เขตสุขภาพที่ 10 หน่วยงานสังกัดกรมควบคุมโรค กระทรวงสาธารณสุข
                    </p>
                    <div class="footer-contact-list">
                        <div class="footer-contact-row">
                            <span class="footer-contact-icon"><i class="fa-solid fa-location-dot"></i></span>
                            <span>220 ถ.พรหมเทพ ต.ในเมือง อ.เมือง จ.อุบลราชธานี 34000</span>
                        </div>
                        <div class="footer-contact-row">
                            <span class="footer-contact-icon"><i class="fa-solid fa-phone"></i></span>
                            <a href="tel:045255934">0 4525 5934</a>
                        </div>
                        <div class="footer-contact-row">
                            <span class="footer-contact-icon"><i class="fa-solid fa-envelope"></i></span>
                            <a href="mailto:saraban.odpc10@ddc.mail.go.th">saraban.odpc10@ddc.mail.go.th</a>
                        </div>
                    </div>
                </div>

                {{-- Column 2: social media + official website --}}
                <div>
                    <div class="footer-heading">ติดตามข่าวสารและเว็บไซต์</div>
                    <a href="https://www.facebook.com/odpc10ubon/" target="_blank" rel="noopener noreferrer" class="footer-link-card">
                        <span class="footer-link-icon fb"><i class="fa-brands fa-facebook-f"></i></span>
                        <span class="footer-link-text">
                            <span class="footer-link-label">Facebook Page</span>
                            <span class="footer-link-value">สคร.10 อุบลราชธานี</span>
                        </span>
                        <i class="fa-solid fa-arrow-up-right-from-square footer-link-arrow"></i>
                    </a>
                    <a href="https://ddc.moph.go.th/odpc10/" target="_blank" rel="noopener noreferrer" class="footer-link-card">
                        <span class="footer-link-icon web"><i class="fa-solid fa-globe"></i></span>
                        <span class="footer-link-text">
                            <span class="footer-link-label">เว็บไซต์หลัก</span>
                            <span class="footer-link-value">ddc.moph.go.th/odpc10</span>
                        </span>
                        <i class="fa-solid fa-arrow-up-right-from-square footer-link-arrow"></i>
                    </a>
                </div>

                {{-- Column 3: map --}}
                <div>
                    <div class="footer-heading">แผนที่และการเดินทาง</div>
                    <div class="footer-map-wrap">
                        <iframe title="สำนักงานป้องกันควบคุมโรคที่ 10 จังหวัดอุบลราชธานี"
                            src="https://www.google.com/maps/embed?pb=!1m18!1m12!1m3!1d3851.353401569345!2d104.8582!3d15.2285!2m3!1f0!2f0!3f0!3m2!1i1024!2i768!4f13.1!3m3!1m2!1s0x3116860d5b244793%3A0xb3cf51fb264f3d2f!2z4Liq4Liy4LiZ4Lix4LiB4LiB4Lij4Lij4Lih4Lib4LmJ4Liy4Lit4Lij4LiB4LiE4Lin4Lij4LmE4LiC4LmI4LiX4Li1IDEwIOC4reC4p-C4p-C4p-C4o-C4o-C4o-C4oA!5e0!3m2!1sth!2sth!4v1700000000000!5m2!1sth!2sth"
                            loading="lazy" referrerpolicy="no-referrer-when-downgrade" allowfullscreen></iframe>
                        <a href="https://maps.app.goo.gl/caSxXa18aGavSazH9" target="_blank" rel="noopener noreferrer" class="footer-map-btn">
                            <i class="fa-solid fa-diamond-turn-right"></i> นำทาง
                        </a>
                    </div>
                </div>

            </div>

            <div class="footer-bottom">
                <div>&copy; {{ date('Y') }} สำนักงานป้องกันควบคุมโรคที่ 10 จังหวัดอุบลราชธานี. All rights reserved.</div>
                <div class="footer-bottom-links">
                    <span>นโยบายความเป็นส่วนตัว</span>
                    <span>·</span>
                    <span>ข้อกำหนดการใช้งาน</span>
                </div>
            </div>
        </div>
    </footer>

    <!-- Mobile Bottom Navigation -->
    <nav class="mobile-nav">
        <a href="{{ route('home') }}" class="mobile-nav-item {{ request()->routeIs('home') ? 'active' : '' }}">
            <i class="fas fa-home"></i>
            <span>หน้าหลัก</span>
        </a>
        <a href="{{ route('awareness') }}"
            class="mobile-nav-item {{ request()->routeIs('awareness') ? 'active' : '' }}">
            <i class="fas fa-brain"></i>
            <span>ประเมิน</span>
        </a>
        <a href="{{ route('food-survey') }}"
            class="mobile-nav-item {{ request()->routeIs('food-survey') ? 'active' : '' }}">
            <i class="fas fa-utensils"></i>
            <span>สำรวจ</span>
        </a>
        <a href="{{ route('staff') }}" class="mobile-nav-item {{ request()->routeIs('staff') ? 'active' : '' }}">
            <i class="fas fa-user-shield"></i>
            <span>เจ้าหน้าที่</span>
        </a>
    </nav>

    <!-- SweetAlert2 (for nicer, non-native alert/confirm popups) -->
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/sweetalert2@11/dist/sweetalert2.min.css">
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
    <style>
        /* Force SweetAlert2 above any page overlay/modal (some in this
           app use z-index up to 99999) so its popups are never hidden
           behind an already-open modal. */
        .swal2-container { z-index: 999999 !important; }
    </style>

    @yield('extra_js')
</body>

</html>
