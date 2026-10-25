@extends('layouts.layout')

@section('title', 'หน้าหลัก - Salt & Sodium Smart Monitor')
@section('header_title', 'แดชบอร์ดภาพรวมเขตสุขภาพที่ 10')
@section('header_subtitle', 'ระบบติดตามและสรุปผลข้อมูลสุขภาพ')

@section('content')
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/leaflet/1.9.4/leaflet.min.css">
    <style>
        @import url('https://fonts.googleapis.com/css2?family=Sarabun:wght@300;400;700&display=swap');

        /* The awareness map's tooltip renders with `outside: true` (its own
           overlay appended to <body>, not clipped inside the chart card),
           which by default sits ON TOP of the map at the exact point the
           mouse is over - intercepting the click meant for the province
           underneath it before it ever reaches the map. Since the tooltip
           is purely informational (nothing inside it needs to be clicked),
           letting clicks pass straight through it is what makes the new
           "click a province" drill-down actually reachable with the mouse
           sitting where a person naturally leaves it: on the region they
           just read the tooltip for. */
        .highcharts-tooltip-container {
            pointer-events: none;
            z-index: 10000 !important;
        }

        /* The aurora background now lives once, shared, in
           layouts/layout.blade.php as body.page-aurora (applied to all 7
           themed report pages, this one included) - no longer duplicated
           here. */

        /* Scoped to the page's own content area (.main-content) - these
           were bare element selectors before and were reaching into the
           shared site header/nav (its "span" tags included), clobbering
           the header's Itim font whenever this page was open. */
        body {
            font-family: 'Sarabun', sans-serif !important;
        }

        .main-content .card,
        .main-content h1,
        .main-content h2,
        .main-content h3,
        .main-content h4,
        .main-content h5,
        .main-content h6,
        .main-content p,
        .main-content span,
        .main-content div,
        .main-content td,
        .main-content th,
        .main-content select,
        .main-content input,
        .main-content button {
            font-family: 'Sarabun', sans-serif !important;
        }

        .fas,
        .far,
        .fa-solid,
        .fa-regular,
        i {
            font-family: "Font Awesome 6 Free", "Font Awesome 5 Free" !important;
            font-weight: 900 !important;
            display: inline-block;
            font-style: normal;
            font-variant: normal;
            text-rendering: auto;
            -webkit-font-smoothing: antialiased;
        }

        /* Brand icons (Facebook, Line, etc.) live in a SEPARATE font from
           the solid/regular set above - reusing "Font Awesome 6 Free" for
           them renders a blank glyph, since that font has no brand
           characters at all. This was silently hiding the footer's
           Facebook icon on this page. */
        .fab,
        .fa-brands {
            font-family: "Font Awesome 6 Brands" !important;
            font-weight: 400 !important;
            display: inline-block;
            font-style: normal;
            font-variant: normal;
            text-rendering: auto;
            -webkit-font-smoothing: antialiased;
        }

        .pagination-wrapper nav svg {
            width: 20px;
        }

        .pagination-wrapper nav div:first-child {
            display: none;
        }

        .pagination-wrapper nav div:last-child {
            display: flex;
            gap: 5px;
        }

        .pagination-wrapper span,
        .pagination-wrapper a {
            padding: 5px 10px;
            border-radius: 5px;
            border: 1px solid #ddd;
            background: #fff;
            color: #333;
            font-size: 0.8rem;
        }

        .pagination-wrapper .active span {
            background: #007bff;
            color: #fff;
            border-color: #007bff;
        }

        .clickable-header {
            cursor: pointer;
            transition: background-color 0.2s, color 0.2s;
        }

        .clickable-header:hover {
            background-color: #ebf4ff !important;
            color: #3182ce !important;
        }

        /* Awareness Metrics Card Styles */
        .metric-row {
            display: flex;
            align-items: center;
            gap: 15px;
            margin-bottom: 10px;
            padding: 12px 14px;
            border-radius: 14px;
            transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
            cursor: pointer;
            background: #f8fafc;
            border: 1px solid #f1f5f9;
        }

        /* การ์ดพฤติกรรมการบริโภคโซเดียม (หน้าแรก): แถวเมตริกยืดเต็มความสูงการ์ด + แถบสัดส่วนใต้ชื่อ ให้สมดุลกับการ์ดข้าง ๆ */
        .aw-beh-card { display: flex; flex-direction: column; }
        .aw-beh-card #awarenessContentBody { flex: 1 1 auto; display: flex; flex-direction: column; }
        .aw-beh-card #metricsListView { flex: 1 1 auto; }
        .aw-beh-card #inlineChartContainer { flex: 1 1 auto; }
        .aw-beh-card .metric-row {
            flex: 1 1 auto;
            margin-bottom: 0;
            padding: 14px 18px;
            flex-wrap: wrap;
            align-content: center;
            row-gap: 10px;
        }
        .aw-beh-card .metric-row .metric-content { flex: 1 1 100%; }
        .aw-beh-card .metric-row::after {
            content: '';
            flex: 0 0 100%;
            height: 7px;
            border-radius: 999px;
            background: linear-gradient(90deg, var(--mc, #10b981) var(--pct, 0%), #e8edf3 var(--pct, 0%));
        }

        .metric-row:hover {
            background: #ffffff !important;
            border-color: #3b82f633;
            box-shadow: 0 4px 12px rgba(59, 130, 246, 0.1);
            transform: translateY(-2px);
        }

        .metric-row.active {
            background: #eff6ff !important;
            border-color: #3b82f666;
            box-shadow: inset 0 2px 4px rgba(59, 130, 246, 0.05);
        }

        .view-fade-in {
            animation: fadeIn 0.3s ease-out forwards;
        }

        @keyframes fadeIn {
            from {
                opacity: 0;
                transform: translateY(5px);
            }

            to {
                opacity: 1;
                transform: translateY(0);
            }
        }

        .metric-icon-box {
            width: 45px;
            height: 45px;
            border-radius: 10px;
            background: #f8fafc;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 1.2rem;
            color: #10b981;
            flex-shrink: 0;
            box-shadow: 0 2px 8px rgba(0, 0, 0, 0.05);
        }

        .metric-content {
            flex-grow: 1;
        }

        .metric-label {
            font-size: 0.85rem;
            font-weight: 600;
            color: #475569;
            margin-bottom: 10px;
            display: flex;
            align-items: center;
            gap: 8px;
            flex-wrap: wrap;
        }

        .metric-pill {
            background: #10b981;
            color: #fff;
            padding: 4px 12px;
            border-radius: 20px;
            font-size: 0.75rem;
            font-weight: 700;
            box-shadow: 0 2px 5px rgba(0, 0, 0, 0.1);
            white-space: nowrap;
            display: inline-flex;
            align-items: center;
            justify-content: center;
        }

        /* Page-level filter bar, moved out from inside the "พฤติกรรม
           การบริโภคโซเดียม" card to sit right under the page header,
           matching the same full-width chip treatment used on
           kidney-dhb-report.blade.php and consumption-report.blade.php:
           a "ตัวกรอง" title chip, one rounded chip per filter (icon +
           label + native <select> styled to read like plain text + a
           decorative chevron) that grows to share the bar's width evenly,
           and a rounded "รีเซ็ต" chip at the end. flex-wrap lets it
           reflow based on the real rendered width instead of a
           viewport-width media query. */
        .home-filter-bar {
            background: transparent;
            border: none;
            border-bottom: 1px solid #e2e8f0;
            border-radius: 0;
            box-shadow: none;
            margin-bottom: 20px;
            display: flex;
            flex-wrap: wrap;
            align-items: center;
            gap: 8px;
            padding: 0 0 16px 0;
        }

        .home-filter-title {
            display: flex;
            align-items: center;
            gap: 8px;
            padding: 8px 10px;
            white-space: nowrap;
            font-weight: 800;
            font-size: 0.88rem;
            color: #334155;
        }

        .home-filter-item {
            display: flex;
            align-items: center;
            justify-content: flex-start;
            gap: 8px;
            padding: 9px 16px;
            white-space: nowrap;
            background: #f8fafc;
            border: 1px solid #eef1f6;
            border-radius: 999px;
            flex: 1 1 200px;
        }

        .home-filter-reset {
            flex-shrink: 0;
        }

        .home-filter-title i,
        .home-filter-item-icon {
            color: #94a3b8;
            font-size: 0.82rem;
        }

        .home-filter-item-label {
            font-weight: 700;
            font-size: 0.85rem;
            color: #64748b;
        }

        .awareness-filter-select {
            appearance: none;
            -webkit-appearance: none;
            border: none;
            background: transparent;
            font-family: 'Sarabun', sans-serif;
            font-weight: 700;
            font-size: 0.85rem;
            color: #1a202c;
            cursor: pointer;
            padding: 0;
            outline: none;
            /* Stretch to fill whatever room is left in the chip (icon +
               label + chevron are fixed-width, this grabs the rest)
               instead of shrinking to the selected option's own text
               width. flex items default to min-width:auto, which floors
               shrinking at the content's intrinsic width - min-width:0
               overrides that so a long province name still ellipsizes
               instead of stretching the chip. This also widens the
               native dropdown popup, since browsers size that popup
               from the closed control's own rendered width. */
            flex: 1 1 0;
            min-width: 0;
            width: auto;
            overflow: hidden;
            text-overflow: ellipsis;
            white-space: nowrap;
        }

        .home-filter-chevron {
            color: #94a3b8;
            font-size: 0.65rem;
            pointer-events: none;
            /* Pushed to the far edge of its chip (instead of sitting
               packed next to the select) so each chip reads as
               balanced: icon+label+value on the left, the chevron
               anchored on the right. */
            margin-left: auto;
        }

        .home-filter-reset {
            display: flex;
            align-items: center;
            gap: 8px;
            padding: 9px 18px;
            border-radius: 999px;
            background: #f1f5f9;
            color: #475569;
            font-weight: 700;
            font-size: 0.85rem;
            border: none;
            cursor: pointer;
            text-decoration: none !important;
            transition: all 0.2s ease;
            white-space: nowrap;
        }

        .home-filter-reset:hover {
            background: #55707a;
            color: #fff;
        }

        /* รายการตัวเลือกในตัวกรอง (ปี/จังหวัด): เว้นระยะจากขอบซ้าย อ่านง่าย */
        .awareness-filter-select option {
            padding: 12px 18px 12px 22px;
            font-family: 'Sarabun', sans-serif;
            font-size: 0.95rem;
            font-weight: 600;
            line-height: 1.6;
            color: #1e293b;
            background: #ffffff;
        }

        .awareness-filter-select option:checked,
        .awareness-filter-select option:hover {
            background: #55707a linear-gradient(#55707a, #55707a);
            color: #ffffff;
        }

        /* เมนูเลือกแบบกำหนดเอง (ครอบ select เดิม) */
        .home-filter-item { position: relative; }
        .home-filter-item.hf-enhanced .awareness-filter-select { display: none; }

        .hf-trigger {
            flex: 1 1 0;
            min-width: 0;
            display: block;
            padding: 0;
            border: none;
            background: transparent;
            font-family: 'Sarabun', sans-serif;
            font-weight: 700;
            font-size: .85rem;
            color: #1a202c;
            text-align: left;
            cursor: pointer;
            overflow: hidden;
            text-overflow: ellipsis;
            white-space: nowrap;
            outline: none;
        }

        .hf-menu {
            position: absolute;
            left: 0;
            top: calc(100% + 8px);
            z-index: 1050;
            min-width: 100%;
            max-height: 300px;
            overflow-y: auto;
            margin: 0;
            padding: 8px;
            list-style: none;
            background: #ffffff;
            border: 1px solid #e3eaf1;
            border-radius: 18px;
            box-shadow: 0 18px 40px -16px rgba(51, 85, 105, .4), 0 4px 10px rgba(51, 85, 105, .08);
            opacity: 0;
            transform: translateY(-6px);
            visibility: hidden;
            transition: opacity .16s ease, transform .16s ease, visibility .16s;
        }

        .hf-menu.is-open { opacity: 1; transform: none; visibility: visible; }

        .hf-option {
            display: flex;
            align-items: center;
            justify-content: space-between;
            gap: 14px;
            padding: 11px 16px 11px 20px;
            border-radius: 12px;
            font-family: 'Sarabun', sans-serif;
            font-size: .92rem;
            font-weight: 600;
            color: #334155;
            cursor: pointer;
            white-space: nowrap;
        }

        .hf-option + .hf-option { margin-top: 2px; }
        .hf-option:hover, .hf-option.is-active { background: #eaf1f6; color: #1e293b; }
        .hf-option[aria-selected="true"] { background: #55707a; color: #ffffff; }
        .hf-option[aria-selected="true"]::after {
            content: "\f00c";
            font-family: "Font Awesome 5 Free", "Font Awesome 6 Free", FontAwesome;
            font-weight: 900;
            font-size: .7rem;
        }

        .home-filter-item.is-open .home-filter-chevron { transform: rotate(180deg); }
        .home-filter-chevron { transition: transform .2s ease; }

        /* Dashboard card redesign: separated header + soft accent tint,
           one per card, so each section reads as its own topic at a
           glance instead of blending into one big white surface. */
        .dash-card {
            position: relative;
            overflow: hidden;
            transition: transform 0.25s ease, box-shadow 0.25s ease;
        }

        .dash-card:hover {
            transform: translateY(-3px);
            box-shadow: 0 16px 34px -8px rgba(15, 23, 42, 0.14) !important;
        }

        .dash-card-head {
            display: flex;
            align-items: flex-start;
            gap: 12px;
            margin-bottom: 16px;
            padding-bottom: 14px;
            border-bottom: 1px solid rgba(15, 23, 42, 0.06);
        }

        .dash-card-icon {
            width: 38px;
            height: 38px;
            min-width: 38px;
            border-radius: 12px;
            background: var(--dash-accent, #ec4899);
            color: white;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 1rem;
            box-shadow: 0 6px 14px -4px var(--dash-accent, #ec4899);
        }

        .dash-card-title {
            font-size: 0.98rem;
            font-weight: 800;
            color: #1e293b;
            line-height: 1.3;
        }

        .dash-card-subtitle {
            font-size: 0.72rem;
            color: #94a3b8;
            font-weight: 600;
            margin-top: 2px;
        }

        .dash-card.accent-pink   { --dash-accent: #ec4899; background: #fffafc; }
        .dash-card.accent-purple { --dash-accent: #8b5cf6; background: #fbfaff; }
        .dash-card.accent-blue   { --dash-accent: #3b82f6; background: #f8fbff; }
        .dash-card.accent-amber  { --dash-accent: #f59e0b; background: #fffdf7; }
        .dash-card.accent-teal   { --dash-accent: #14b8a6; background: #f8fefd; }

        /* Province overview table ("เปรียบเทียบสถานการณ์และผลดำเนินงานรายจังหวัด")
           - replaces the old ก่อน/หลัง-ปรับสูตร average-sodium bar chart.
           Each row pairs a mini horizontal bar with its number rather than
           a bare figure, since every column here is naturally compared
           across the same 5 provinces (reusing the bar language already
           established by the ไตรมาส progress bars on /kidney-dhb-report) -
           except ก่อน/หลัง ปรับสูตร, whose paired red/green bars share one
           scale so the drop in menu count between them reads at a glance. */
        .province-overview-scroll {
            overflow-x: auto;
        }

        .province-overview-table {
            width: 100%;
            min-width: 560px;
            border-collapse: collapse;
            font-size: 0.86rem;
        }

        .province-overview-table thead th {
            text-align: center;
            font-size: 0.74rem;
            font-weight: 700;
            color: #92400e;
            text-transform: none;
            padding: 0 10px 10px;
            border-bottom: 2px solid rgba(245, 158, 11, 0.2);
            white-space: nowrap;
        }

        .province-overview-table tbody td {
            text-align: center;
            padding: 10px;
            border-bottom: 1px solid #f1f5f9;
            vertical-align: middle;
        }

        .province-overview-table tbody tr:last-child td {
            border-bottom: none;
        }

        .po-dot {
            display: inline-block;
            width: 8px;
            height: 8px;
            border-radius: 50%;
            margin-right: 4px;
            vertical-align: middle;
        }

        .po-dot-red { background: #fb526b; }
        .po-dot-green { background: #16a34a; }
        .po-dot-amber { background: #f59e0b; }

        /* เมนูลดโซเดียม - one continuous pill split into 3 flat-colored
           segments (matching the reference image the user attached),
           each segment's own width set by its own count rather than a
           shared 0-100 scale, so "เมนูทั้งหมด" in the middle comes out
           visibly wider than either side (it's always exactly as big as
           the other two combined, since ก่อน + หลัง = ทั้งหมด). "ก่อนปรับ
           สูตร" (still pending) stays red, "หลังปรับสูตร" (already done) is
           green - matching the same red/green language the rest of this
           dashboard already uses for "not yet" vs. "done" - while the
           middle gets a dark, neutral tone so its number, the headline
           figure, reads as the one that matters most. */
        .po-seg-bar {
            position: relative;
            display: flex;
            width: 100%;
            min-width: 170px;
            height: 30px;
            border-radius: 999px;
            overflow: hidden;
            box-shadow: inset 0 0 0 1px rgba(15, 23, 42, 0.04), 0 2px 6px -2px rgba(15, 23, 42, 0.18);
        }

        .po-seg {
            display: flex;
            align-items: center;
            justify-content: center;
            min-width: 24px;
            transition: width 0.3s ease;
        }

        .po-seg-bar::after {
            /* a thin gloss line along the top edge - the one small polish
               touch on this bar, not repeated anywhere else on the row */
            content: "";
            position: absolute;
            inset: 1px 1px auto 1px;
            height: 7px;
            border-radius: 999px 999px 0 0;
            background: linear-gradient(180deg, rgba(255, 255, 255, 0.35), rgba(255, 255, 255, 0));
            pointer-events: none;
        }

        .po-seg-before { background: linear-gradient(180deg, #fc8296, #fb526b); }
        .po-seg-total { background: linear-gradient(180deg, #334155, #1e293b); }
        .po-seg-after { background: linear-gradient(180deg, #6ee7ac, #16a34a); }

        .po-seg-chip {
            font-size: 0.72rem;
            font-weight: 800;
            color: #fff;
            text-shadow: 0 1px 1px rgba(0, 0, 0, 0.2);
            padding: 0 4px;
            white-space: nowrap;
            letter-spacing: 0.1px;
        }

        .po-seg-chip-total {
            font-size: 0.82rem;
        }

        .po-seg-caption {
            display: flex;
            justify-content: center;
            gap: 10px;
            margin-top: 7px;
            font-size: 0.64rem;
            color: #94a3b8;
            font-weight: 600;
            white-space: nowrap;
        }

        .po-seg-swatch {
            display: inline-block;
            width: 7px;
            height: 7px;
            border-radius: 50%;
            margin-right: 3px;
            vertical-align: middle;
        }

        .po-seg-swatch-before { background: #fb526b; }
        .po-seg-swatch-total { background: #1e293b; }
        .po-seg-swatch-after { background: #16a34a; }

        .po-seg-rate {
            margin-top: 3px;
            text-align: center;
            font-size: 0.68rem;
            color: #64748b;
        }

        .po-seg-rate b {
            color: #16a34a;
            font-weight: 800;
        }


        .po-bar-wrap {
            display: flex;
            align-items: center;
            gap: 8px;
            min-width: 110px;
        }

        .po-bar-track {
            flex: 1;
            height: 8px;
            border-radius: 999px;
            background: #e2e8f0;
            overflow: hidden;
            min-width: 48px;
        }

        .po-bar-fill {
            height: 100%;
            border-radius: 999px;
        }

        .po-bar-fill.po-bar-ht { background: linear-gradient(90deg, #94a3b8, #64748b); }
        .po-bar-fill.po-bar-area { background: linear-gradient(90deg, #6ee7ac, #16a34a); }
        .po-bar-fill.po-bar-red { background: linear-gradient(90deg, #fc8296, #fb526b); }
        .po-bar-fill.po-bar-green { background: linear-gradient(90deg, #6ee7ac, #39d378); }

        .po-bar-value {
            font-weight: 700;
            color: #1e293b;
            white-space: nowrap;
            font-size: 0.82rem;
        }

        .po-bar-change {
            font-weight: 600;
            color: #16a34a;
            font-size: 0.74rem;
        }

        .po-empty {
            color: #94a3b8;
            font-size: 0.78rem;
            white-space: nowrap;
        }

        .po-badge {
            display: inline-flex;
            align-items: center;
            gap: 5px;
            padding: 4px 11px;
            border-radius: 999px;
            font-size: 0.76rem;
            font-weight: 700;
            white-space: nowrap;
        }

        .po-badge-complete { background: #dcfce7; color: #15803d; }
        .po-badge-partial { background: #fef3c7; color: #b45309; }
        .po-badge-none { background: #f1f5f9; color: #94a3b8; font-weight: 600; }


        /* Overall KPI summary row - จำนวนผู้เข้าร่วมทั้งหมด / ผ่านเกณฑ์ /
           ไม่ผ่านเกณฑ์ / อัตราผ่านเกณฑ์, scoped to the same ปีงบประมาณ/จังหวัด
           filters as every other card on this page. Built on the same glass
           dash-card language (soft accent icon chip, backdrop blur) instead
           of the flat left-border strip the admin แปลงผล page uses, so it
           reads as part of this page rather than a borrowed admin panel. */
        .kpi-summary-row {
            display: grid;
            grid-template-columns: repeat(4, 1fr);
            gap: 18px;
            margin-bottom: 20px;
            transition: filter 0.25s ease, opacity 0.25s ease;
        }
        @media (max-width: 960px) { .kpi-summary-row { grid-template-columns: repeat(2, 1fr); } }
        @media (max-width: 560px) { .kpi-summary-row { grid-template-columns: 1fr; } }

        /* Soft Dimensional Glass - matched to the KPI tiles on
           awareness / reduced-sodium-menu / reduced-sodium-products: a
           translucent frosted tile with a bright inset top edge and an
           accent-tinted shadow for lift, plus a light glass icon chip
           instead of a solid-fill square. */
        .kpi-summary-card {
            position: relative;
            overflow: hidden;
            display: flex;
            align-items: center;
            gap: 14px;
            padding: 20px 22px;
            min-height: 96px;
            border-radius: 18px;
            background: linear-gradient(155deg, rgba(255, 255, 255, 0.86), rgba(255, 255, 255, 0.6));
            backdrop-filter: blur(10px) saturate(150%);
            -webkit-backdrop-filter: blur(10px) saturate(150%);
            border: 1px solid rgba(255, 255, 255, 0.75);
            box-shadow:
                0 1px 0 rgba(255, 255, 255, 0.9) inset,
                0 14px 26px -18px color-mix(in srgb, var(--kpi-accent, #6366f1) 55%, transparent),
                0 2px 6px rgba(15, 23, 42, 0.05);
            transition: transform 0.18s ease, box-shadow 0.18s ease;
        }

        .kpi-summary-card:hover {
            transform: translateY(-2px);
            box-shadow:
                0 1px 0 rgba(255, 255, 255, 0.9) inset,
                0 18px 32px -18px color-mix(in srgb, var(--kpi-accent, #6366f1) 65%, transparent),
                0 4px 10px rgba(15, 23, 42, 0.07);
        }

        .kpi-summary-icon {
            width: 46px;
            height: 46px;
            min-width: 46px;
            border-radius: 13px;
            background: linear-gradient(155deg, color-mix(in srgb, var(--kpi-accent, #6366f1) 30%, white) 0%, color-mix(in srgb, var(--kpi-accent, #6366f1) 10%, white) 100%);
            color: var(--kpi-accent, #6366f1);
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 1.15rem;
            border: 1px solid color-mix(in srgb, var(--kpi-accent, #6366f1) 25%, white);
            box-shadow:
                0 1px 0 rgba(255, 255, 255, 0.8) inset,
                0 6px 14px -8px color-mix(in srgb, var(--kpi-accent, #6366f1) 55%, transparent);
        }

        .kpi-summary-body {
            display: flex;
            flex-direction: column;
            gap: 3px;
            min-width: 0;
        }

        .kpi-summary-label {
            font-size: 0.78rem;
            font-weight: 700;
            color: #64748b;
        }

        .kpi-summary-value {
            font-size: 1.9rem;
            font-weight: 800;
            color: #1e293b;
            font-variant-numeric: tabular-nums;
            line-height: 1.15;
        }

        .kpi-summary-sub {
            white-space: nowrap;
            overflow: hidden;
            text-overflow: ellipsis;
            font-size: 0.74rem;
            color: #94a3b8;
            font-weight: 600;
        }

        /* เมื่อการ์ดกว้าง (1-2 คอลัมน์): ป้าย+คำอธิบายชิดซ้าย ตัวเลขใหญ่ชิดขวา เต็มความกว้างการ์ด */
        @media (max-width: 960px) {
            .kpi-summary-body {
                flex: 1;
                display: grid;
                grid-template-columns: minmax(0, 1fr) auto;
                grid-template-rows: auto auto;
                column-gap: 12px;
                row-gap: 2px;
                align-items: center;
            }
            .kpi-summary-label { grid-column: 1; grid-row: 1; align-self: end; font-size: 0.86rem; }
            .kpi-summary-sub   { grid-column: 1; grid-row: 2; align-self: start; }
            .kpi-summary-value {
                grid-column: 2; grid-row: 1 / span 2;
                font-size: 2.3rem; text-align: right;
                color: color-mix(in srgb, var(--kpi-accent) 55%, #0f172a);
            }
        }
        @media (max-width: 380px) {
            .kpi-summary-value { font-size: 1.9rem; }
        }

        .kpi-summary-card.kpi-total { --kpi-accent: #6366f1; }
        .kpi-summary-card.kpi-pass  { --kpi-accent: #10b981; }
        .kpi-summary-card.kpi-fail  { --kpi-accent: #ef4444; }
        .kpi-summary-card.kpi-rate  { --kpi-accent: #f59e0b; }

        .kpi-pending-note {
            display: flex;
            align-items: center;
            gap: 8px;
            margin: -8px 0 20px;
            font-size: 0.8rem;
            font-weight: 700;
            color: #b45309;
        }

        /* Risk-tier reference: one seamless pill split into three flat-color
           segments (rounded only at the strip's own two outer corners, via
           overflow:hidden on the container - the segments themselves have
           no radius of their own), each with a colored face icon. The
           fa-face-smile/-meh/-frown solid glyphs are themselves drawn as an
           outlined circle with facial features, so no separate icon-badge
           circle is needed - matches the reference layout the user
           supplied. */
        .risk-legend-strip {
            display: flex;
            border-radius: 20px;
            overflow: hidden;
            box-shadow: 0 8px 32px rgba(0, 0, 0, 0.05);
        }

        @media (max-width: 768px) {
            .risk-legend-strip {
                flex-direction: column;
            }
        }

        .risk-legend-card {
            display: flex;
            align-items: center;
            gap: 18px;
            padding: 28px 30px;
            flex: 1;
            min-width: 0;
        }

        .risk-legend-icon {
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 2.75rem;
            line-height: 1;
        }

        .risk-legend-title {
            font-weight: 700;
            font-size: 1.05rem;
            margin-bottom: 4px;
        }

        .risk-legend-sub {
            font-size: 0.82rem;
            font-weight: 600;
            color: #111113;
            line-height: 1.55;
            /* Thin white outline behind the dark text so it stays crisp
               against the flat tier color, plus a soft white glow to
               smooth the outline's edges instead of looking jagged. */
            text-shadow:
                -1px -1px 0 #fff,
                1px -1px 0 #fff,
                -1px 1px 0 #fff,
                1px 1px 0 #fff,
                0 0 4px rgba(255, 255, 255, 0.9);
        }

        .risk-legend-card.tier-pass { background: #cde3ac; }
        .risk-legend-card.tier-pass .risk-legend-icon,
        .risk-legend-card.tier-pass .risk-legend-title { color: #1f4d14; }

        .risk-legend-card.tier-watch { background: #f8cd9c; }
        .risk-legend-card.tier-watch .risk-legend-icon,
        .risk-legend-card.tier-watch .risk-legend-title { color: #b8420f; }

        .risk-legend-card.tier-risk { background: #e2929c; }
        .risk-legend-card.tier-risk .risk-legend-icon,
        .risk-legend-card.tier-risk .risk-legend-title { color: #7a1626; }

        /* Row 1 (map / donut / metrics) and Row 2 (sodium/product charts)
           give each column a flex-basis + min-width via inline style
           (up to 380px per column), sized so 2-3 columns share a wide
           desktop row. Below 992px those inline min-widths no longer
           leave room for that many columns without pushing the row past
           the viewport (horizontal scroll on tablet/phone), so every
           direct-child column of these two rows is stacked to full width
           instead. A stylesheet rule marked !important still overrides a
           plain inline style, so this needs no edit to the columns' own
           style="" attributes, and >=1280px desktop is untouched since
           the query only applies at/below 992px. */
        @media (max-width: 992px) {
            .home-row-top > div,
            .home-row-charts > div {
                flex: 1 1 100% !important;
                min-width: 100% !important;
            }
        }

        /* ================= Home summary dashboard (4 modules) =================
           Section accents: awareness #db2777 / kidney #0369a1 / menu #d97706 /
           HT #0d9488. Province identity colors are ONE set used everywhere
           on this page (donut, tables, bars) - same as the pin map. */
        .hm-band {
            display: grid;
            grid-template-columns: repeat(5, minmax(0, 1fr));
            gap: 14px;
            margin: 4px 0 26px;
        }
        @media (max-width: 1279px) { .hm-band { grid-template-columns: repeat(3, minmax(0, 1fr)); } }
        @media (max-width: 799px) { .hm-band { grid-template-columns: repeat(2, minmax(0, 1fr)); } .hm-band > .hm-tile:last-child:nth-child(odd) { grid-column: 1 / -1; } }
        @media (max-width: 519px) { .hm-band { grid-template-columns: minmax(0, 1fr); } }
        /* Summary tile: frosted glass with a faint accent glow from the top-right
           corner and an accent-tinted shadow for lift (no colored edge strip). */
        .hm-tile {
            position: relative;
            display: flex;
            flex-direction: column;
            border-radius: 18px;
            padding: 16px 18px;
            background: linear-gradient(155deg, rgba(255, 255, 255, 0.95), rgba(255, 255, 255, 0.7));
            border: 1px solid color-mix(in srgb, var(--c) 34%, #e2e8f0);
            box-shadow:
                0 1px 0 rgba(255, 255, 255, 0.95) inset,
                0 16px 28px -20px color-mix(in srgb, var(--c) 28%, transparent),
                0 2px 6px rgba(15, 23, 42, 0.05);
            overflow: hidden;
            text-decoration: none !important;
            color: inherit;
            transition: box-shadow .2s ease, transform .2s ease;
        }
        .hm-tile:hover {
            transform: translateY(-2px);
            box-shadow:
                0 1px 0 rgba(255, 255, 255, 0.95) inset,
                0 20px 34px -18px color-mix(in srgb, var(--c) 36%, transparent),
                0 4px 10px rgba(15, 23, 42, 0.07);
            color: inherit;
        }
        .hm-tile:hover { border-color: color-mix(in srgb, var(--c) 55%, #e2e8f0); }
        .hm-tile:focus-visible { outline: 3px solid var(--c); outline-offset: 2px; }
        .hm-tile::before {
            content: ""; position: absolute; inset: 0; pointer-events: none;
            background:
                radial-gradient(120% 90% at 100% 0%, color-mix(in srgb, var(--c) 7%, transparent) 0%, transparent 55%),
                radial-gradient(70% 60% at 0% 100%, color-mix(in srgb, var(--c) 3%, transparent) 0%, transparent 65%);
        }
        .hm-tile > * { position: relative; }
        .hm-tile-name { font-size: .92rem; font-weight: 800; color: color-mix(in srgb, var(--c) 82%, #000); }
        .hm-tile-big { font-size: 1.95rem; font-weight: 800; line-height: 1.15; margin-top: 2px; color: #0f172a; }
        .hm-tile-big small { font-size: .92rem; font-weight: 800; color: #1e293b; }
        .hm-tile-sub { font-size: .8rem; font-weight: 600; color: #1e293b; margin-top: 2px; }
        /* คำเด่นในการ์ดสรุป: ตัวหนา สีประจำการ์ด และแถบเน้นสีจางใต้ตัวอักษร */
        .hm-em { font-weight: 800; color: var(--c); padding: 0 3px; border-radius: 3px; background: linear-gradient(transparent 58%, color-mix(in srgb, var(--c) 20%, transparent) 58%); }
        .hm-tile-st { margin-top: auto; padding-top: 10px; }
        .hm-chip {
            display: inline-flex; align-items: center; gap: 5px;
            border-radius: 9px; padding: 3px 9px; font-size: .72rem; font-weight: 800;
        }
        .hm-chip.ok { background: #dcf5e7; color: #15803d; }
        .hm-chip.warn { background: #fff1d6; color: #b45309; }
        .hm-chip.none { background: #e8edf4; color: #475569; }
        .hm-chip.bad { background: #fde8e8; color: #b91c1c; }

        .hm-sec { margin-bottom: 26px; --a: #db2777; }
        /* Summary-tile links jump to a section: land it just below the sticky header
           (so the section title is visible, not hidden under the bar) and glide there. */
        html { scroll-behavior: smooth; }
        .hm-sec { scroll-margin-top: calc(var(--hm-head-h, 84px) + 14px); }
        @media (prefers-reduced-motion: reduce) { html { scroll-behavior: auto; } }
        .hm-sec.hm-kidney { --a: #0369a1; }
        .hm-sec.hm-menu { --a: #d97706; }
        .hm-sec.hm-ht { --a: #0d9488; }
        .hm-sec.hm-products { --a: #14b8a6; }
        .hm-sec-head { display: flex; align-items: center; gap: 12px; margin-bottom: 14px; flex-wrap: wrap; }
        .hm-sec-ic {
            width: 38px; height: 38px; border-radius: 12px; background: var(--a); color: #fff;
            display: grid; place-items: center; font-size: 1rem; box-shadow: 0 6px 14px -6px var(--a);
        }
        .hm-sec-head h3 { margin: 0; font-size: 1.12rem; font-weight: 800; color: #1e293b; }
        .hm-scope {
            font-size: .86rem; font-weight: 800; color: #0f172a; background: #fff;
            border: 1.5px solid var(--a, #cbd5e1); border-radius: 999px; padding: 3px 13px;
            letter-spacing: .1px; box-shadow: 0 2px 6px -3px rgba(15, 23, 42, .25);
        }
        /* ป้าย "ข้อมูลอัปเดตล่าสุด" บนหัวการ์ด: เด่นกว่าป้ายขอบเขตปี แต่ไม่แย่งหัวข้อ */
        .hm-updated {
            display: inline-flex; align-items: center; gap: 7px;
            padding: 4px 13px 4px 10px; border-radius: 999px;
            background: color-mix(in srgb, var(--a) 10%, #fff);
            border: 1.5px solid color-mix(in srgb, var(--a) 38%, #fff);
            color: var(--a); font-size: .82rem; line-height: 1.3; white-space: nowrap;
        }
        .hm-updated i { font-size: .8rem; }
        .hm-updated-l { font-weight: 600; color: #475569; }
        .hm-updated b { font-weight: 800; color: var(--a); font-size: .9rem; }
        @media (max-width: 640px) {
            .hm-updated { white-space: normal; flex-wrap: wrap; }
        }
        .hm-more {
            margin-left: auto; font-size: .8rem; font-weight: 800; color: var(--a) !important;
            text-decoration: none !important; border: 1.5px solid var(--a); border-radius: 999px; padding: 3px 14px;
            background: #fff;
        }
        .hm-more:hover { background: var(--a); color: #fff !important; }
        .hm-insight {
            display: flex; gap: 8px; align-items: flex-start; font-size: .84rem; color: #475569;
            margin: 0 0 14px 50px; max-width: 1000px;
        }
        .hm-insight i {
            flex: none; margin-top: 3px; width: 16px; height: 16px; border-radius: 50%;
            background: var(--a); color: #fff; font-size: .65rem; font-style: normal;
            display: grid; place-items: center; font-weight: 800;
        }
        .hm-insight b { color: #1e293b; }

        .hm-grid { display: grid; gap: 14px; }
        .hm-grid > * { min-width: 0; }
        .hm-grid.kd {
            grid-template-columns: 240px minmax(0, 1fr) minmax(0, 1fr);
            grid-template-areas: "k c m" "k s m";
        }
        .hm-grid.kd > .hm-kcol { grid-area: k; }
        .hm-grid.kd > .hm-kd-cmp { grid-area: c; }
        .hm-grid.kd > .hm-kd-map { grid-area: m; }
        .hm-grid.kd > .hm-stack { grid-area: s; grid-template-columns: 1fr; align-content: start; }
        .hm-kd-map { display: flex; flex-direction: column; overflow: hidden; }
        .hm-mp-head { display: flex; align-items: center; gap: 12px; padding-bottom: 10px; margin-bottom: 6px; border-bottom: 1px solid #eef2f6; }
        .hm-mp-ico { width: 40px; height: 40px; border-radius: 12px; background: linear-gradient(135deg, #0f4c5c, #0e7490); color: #fff; display: flex; align-items: center; justify-content: center; font-size: 1.05rem; flex: 0 0 40px; box-shadow: 0 4px 10px rgba(15, 76, 92, .3); }
        .hm-mp-t { font-weight: 800; font-size: .95rem; color: #1e293b; line-height: 1.25; }
        .hm-mp-s { font-size: .76rem; color: #64748b; font-weight: 600; }
        #hmMapChoro { flex: 1 1 auto; min-height: 410px; border-radius: 16px; position: relative;
            background: radial-gradient(60% 55% at 50% 45%, rgba(15, 76, 92, .09), rgba(15, 76, 92, 0) 70%), radial-gradient(rgba(15, 76, 92, .13) 1px, transparent 1.4px) 0 0 / 16px 16px; }
        #hmMapChoro .oa-map-label { position: relative; text-align: center; line-height: 1.15; white-space: nowrap; pointer-events: none; background: rgba(255, 255, 255, .95); border-radius: 9px; padding: 2px 8px 3px; box-shadow: 0 1px 5px rgba(15, 23, 42, .28); font-family: inherit; animation: oaLabelPop .5s cubic-bezier(.2, .9, .3, 1.3) both; }
        #hmMapChoro .oa-map-label-name { display: block; font-size: 12px; font-weight: 700; color: #1e293b; }
        #hmMapChoro .oa-map-label-count { display: block; font-size: 16px; font-weight: 800; }
        #hmMapChoro .oa-map-label-count i { font-size: 11px; margin-right: 3px; opacity: .9; }
        #hmMapChoro .oa-map-label-count small { font-size: 11px; font-weight: 700; margin-left: 2px; }
        #hmMapChoro .oa-map-label-bar { display: block; height: 4px; margin-top: 3px; border-radius: 99px; background: rgba(15, 23, 42, .1); overflow: hidden; }
        #hmMapChoro .oa-map-label-bar > b { display: block; height: 100%; border-radius: 99px; }
        #hmMapChoro .oa-map-label.is-top { box-shadow: 0 0 0 2px #f5b301, 0 3px 10px rgba(245, 179, 1, .55); }
        #hmMapChoro .oa-map-crown { position: absolute; top: -13px; left: 50%; transform: translateX(-50%); display: inline-flex; align-items: center; gap: 4px; padding: 1px 8px; border-radius: 99px; background: linear-gradient(135deg, #fbbf24, #f59e0b); color: #5b3a00; font-size: 10px; font-weight: 800; box-shadow: 0 2px 6px rgba(245, 158, 11, .5); white-space: nowrap; }
        #hmMapChoro .oa-top-province { filter: drop-shadow(0 0 7px rgba(245, 179, 1, .85)); }
        /* แผงรายละเอียดซ้อนทับด้านล่างของแผนที่ (ไม่เพิ่มความสูงการ์ด) */
        .hm-mi { position: relative; z-index: 2; margin: -128px 8px 8px; height: 120px; padding: 9px 14px 8px; border-radius: 14px; border: 1px solid #dbe4ee; background: rgba(255, 255, 255, .97); box-shadow: 0 4px 14px rgba(15, 23, 42, .12); display: flex; flex-direction: column; justify-content: space-between; overflow: hidden; }
        .hm-mi-top { display: flex; align-items: center; justify-content: space-between; gap: 8px; }
        .hm-mi-name { font-weight: 800; font-size: 1rem; color: #0f172a; white-space: nowrap; }
        .hm-mi-go { font-size: .74rem; font-weight: 800; color: var(--pc, #0f4c5c); text-decoration: none; padding: 3px 11px; border-radius: 99px; border: 1.5px solid currentColor; white-space: nowrap; }
        .hm-mi-go:hover { background: var(--pc, #0f4c5c); color: #fff; }
        .hm-mi-stats { display: grid; grid-template-columns: repeat(2, 1fr); gap: 6px; text-align: center; }
        .hm-mi-stats .hm-mi-st { display: block; font-size: .72rem; font-weight: 700; color: #334155; line-height: 1.2; border-right: 1px solid #e2e8f0; }
        .hm-mi-stats .hm-mi-st:last-child { border-right: 0; }
        .hm-mi-stats b { display: block; font-size: 1.1rem; font-weight: 800; color: var(--pc, #0f172a); line-height: 1.2; }
        .hm-mi-pill { font-size: .74rem; font-weight: 800; padding: 2px 11px; border-radius: 99px; background: color-mix(in srgb, var(--pc, #0f4c5c) 14%, #fff); color: var(--pc, #0f4c5c); white-space: nowrap; }
        .hm-mi .hm-mi-mb { display: block; height: 4px; border-radius: 99px; background: #e8eef4; margin: 4px 14% 0; overflow: hidden; }
        .hm-mi .hm-mi-mf { display: block; height: 100%; border-radius: 99px; background: var(--pc, #0f4c5c); text-decoration: none; }
        .hm-mi-bar { height: 5px; border-radius: 99px; background: #e8eef4; overflow: hidden; }
        .hm-mi-bar i { display: block; height: 100%; border-radius: 99px; background: var(--pc, #0f4c5c); }
        .hm-mi-dist { font-size: .72rem; font-weight: 700; color: #334155; white-space: nowrap; overflow: hidden; text-overflow: ellipsis; }
        #hmMapChoro .oa-map-label { transition: outline-color .15s; outline: 2px solid transparent; outline-offset: 1px; }
        #hmMapChoro .oa-map-label.is-sel { outline-color: #0f172a; }
        @keyframes oaLabelPop { from { opacity: 0; transform: translateY(8px) scale(.85); } to { opacity: 1; transform: none; } }
        @media (prefers-reduced-motion: reduce) { #hmMapChoro .oa-map-label { animation: none; } }
        .hm-kd-map #hmPinMap { flex: 1 1 auto; min-height: 320px; border-radius: 12px; overflow: hidden; border: 1px solid #e2e8f0; z-index: 0; }
        .hm-pin-legend { display: flex; flex-wrap: wrap; gap: 6px 8px; margin-top: 10px; }
        .hm-pin-legend span { display: inline-flex; align-items: center; gap: 5px; padding: 2px 9px 2px 7px; border-radius: 999px; border: 1px solid transparent; font-size: .72rem; font-weight: 700; color: #334155; line-height: 1.4; }
        .hm-pin-legend .hm-pin-n { font-weight: 700; font-size: .72rem; opacity: .85; font-variant-numeric: tabular-nums; }
        .hm-pin-legend i { width: 8px; height: 8px; border-radius: 50%; display: inline-block; }
        .hm-pin-pop { min-width: 190px; font-size: .8rem; color: #1e293b; }
        .hm-pin-pop-head { display: flex; align-items: center; justify-content: space-between; gap: 8px; }
        .hm-pin-pop-prov { font-size: .86rem; font-weight: 800; color: var(--pa, #1e293b); }
        .hm-pin-pop-year { flex: none; font-size: .68rem; font-weight: 700; padding: 2px 8px; border-radius: 999px; white-space: nowrap; }
        .hm-pin-pop-bc { margin-top: 3px; font-size: .72rem; color: #64748b; }
        .hm-pin-pop-lb { margin-top: 4px; font-size: .74rem; font-style: italic; color: #64748b; }
        .hm-pin-pop-pr { margin-top: 9px; }
        .hm-pin-pop-row { display: flex; align-items: baseline; justify-content: space-between; font-size: .72rem; color: #64748b; margin-bottom: 4px; }
        .hm-pin-pop-row strong { font-size: .78rem; font-weight: 800; color: #1e293b; }
        .hm-pin-pop-tr { height: 6px; border-radius: 999px; background: #e2e8f0; overflow: hidden; }
        .hm-pin-pop-tr i { display: block; height: 100%; border-radius: 999px; background: var(--pa, #0369a1); }
        .hm-grid.mn { grid-template-columns: 240px 1.5fr 1fr; }
        .hm-grid.ht { grid-template-columns: 216px minmax(0, .85fr) minmax(0, 1.4fr); }
        /* มีการ์ดแนวโน้ม: แถวบน = KPI + ตารางรายจังหวัด, แถวล่าง = การ์ดแนวโน้มเต็มความกว้าง */
        .hm-grid.ht.has-trend { grid-template-columns: minmax(0, 4fr) minmax(0, 8fr); }
        .hm-grid.ht.has-trend > .hm-kcol { grid-column: 1 / -1; grid-template-columns: repeat(3, minmax(0, 1fr)); grid-auto-rows: auto; }
        .hm-grid.ht.has-trend table.hm-t { font-size: .88rem; }
        .hm-grid.ht.has-trend { gap: 12px; }
        .hm-grid.ht.has-trend > .hm-kcol { gap: 12px; }
        .hm-grid.ht.has-trend > .hm-kcol .hm-kpi { padding-top: 8px; padding-bottom: 8px; }
        .hm-grid.ht.has-trend > .hm-ht-sum { padding: 12px 14px; }
        .hm-grid.ht.has-trend table.hm-t td { padding-top: 5px; padding-bottom: 5px; }
        .hm-grid.ht.has-trend > .hm-ht-sum h4 { font-size: 1.08rem; }
        .hm-grid.ht.has-trend > .hm-ht-sum .hm-cap { font-size: .8rem; }
        @media (max-width: 1280px) {
            .hm-grid.ht.has-trend { grid-template-columns: minmax(0, 1fr); }
            .hm-grid.ht.has-trend > .hm-kcol { grid-template-columns: repeat(auto-fit, minmax(220px, 1fr)); }
        }
        .hm-grid.ht table.hm-t { font-size: .8rem; }
        .hm-grid.ht > .hm-ht-sum { display: flex; flex-direction: column; }
        .hm-grid.ht > .hm-ht-sum table.hm-t { flex: 1 1 auto; height: 100%; }
        .hm-grid.ht > .hm-ht-sum table.hm-t thead th { height: 1%; }
        .hm-grid.ht > .hm-ht-sum table.hm-t tbody tr:not(.hm-total) td { height: auto; }
        .hm-grid.ht table.hm-t td { padding: 8px 5px; }
        .hm-grid.ht .hm-bar { gap: 6px; }
        .hm-grid.ht .hm-bar-tr { min-width: 28px; }
        .hm-grid.ht .hm-bar-pc { min-width: 54px !important; }
        .hm-grid.pd { grid-template-columns: 240px minmax(0, 1fr); margin-bottom: 20px; }
        .hm-grid.pd > .hm-pd-chart > .card { height: 100%; margin: 0; }
        .hm-card {
            background: #fff; border-radius: 16px; box-shadow: 0 2px 12px rgba(15, 23, 42, .06);
            padding: 14px 16px; overflow-x: auto;
        }
        .hm-card h4 { margin: 0 0 2px; font-size: .92rem; font-weight: 800; color: #1e293b; }
        .hm-cap { font-size: .72rem; color: #94a3b8; margin-bottom: 10px; }
        .hm-ht-head { display: flex; justify-content: space-between; align-items: flex-start; gap: 8px 12px; flex-wrap: wrap; }
        .hm-total-chip {
            display: inline-flex; align-items: baseline; gap: 5px; padding: 5px 12px; border-radius: 999px;
            background: color-mix(in srgb, #0d9488 10%, #fff); border: 1px solid color-mix(in srgb, #0d9488 28%, #fff);
            color: #0f766e; font-size: .74rem; font-weight: 700; white-space: nowrap;
        }
        .hm-total-chip b { font-size: 1rem; font-weight: 800; color: #134e4a; }
        .hm-total-chip small { font-size: .7rem; font-weight: 600; }
        .hm-stack { display: grid; gap: 14px; align-content: start; }
        /* Left KPI column: same soft-glass tile as the awareness KPI cards -
           frosted gradient, bright top edge, accent-tinted glow, bordered icon
           chip. Tiles share the column's height equally so it lines up with
           the cards beside it. */
        .hm-kcol { display: grid; grid-auto-rows: 1fr; gap: 12px; align-content: stretch; }
        .hm-kpi {
            position: relative; display: flex; align-items: center; gap: 14px;
            padding: 12px 16px; border-radius: 18px; min-width: 0;
            background: linear-gradient(155deg, rgba(255, 255, 255, 0.92), rgba(255, 255, 255, 0.62));
            backdrop-filter: blur(10px) saturate(150%); -webkit-backdrop-filter: blur(10px) saturate(150%);
            border: 1px solid rgba(255, 255, 255, 0.75);
            box-shadow:
                0 1px 0 rgba(255, 255, 255, 0.9) inset,
                0 14px 26px -18px color-mix(in srgb, var(--k, #6366f1) 55%, transparent),
                0 2px 6px rgba(15, 23, 42, 0.05);
        }
        .hm-kpi-ico {
            width: 46px; height: 46px; min-width: 46px; border-radius: 13px;
            display: flex; align-items: center; justify-content: center; font-size: 1.15rem;
            background: linear-gradient(155deg, color-mix(in srgb, var(--k, #6366f1) 30%, white) 0%, color-mix(in srgb, var(--k, #6366f1) 10%, white) 100%);
            color: var(--k, #6366f1);
            border: 1px solid color-mix(in srgb, var(--k, #6366f1) 25%, white);
            box-shadow: 0 1px 0 rgba(255, 255, 255, 0.8) inset, 0 6px 14px -8px color-mix(in srgb, var(--k, #6366f1) 55%, transparent);
        }
        .hm-kpi-body { display: flex; flex-direction: column; gap: 3px; min-width: 0; flex: 1; }
        .hm-kpi-l { font-size: 0.78rem; font-weight: 700; color: #64748b; }
        .hm-kpi-v { font-size: 1.5rem; font-weight: 800; color: #1e293b; line-height: 1.15; font-variant-numeric: tabular-nums; }
        .hm-kpi-v small { font-size: 0.8rem; color: #475569; font-weight: 700; }
        .hm-kpi-v.hm-kpi-v-text { font-size: 1.2rem; }
        .hm-kpi-d { font-size: 0.74rem; color: #64748b; font-weight: 600; }
        .hm-kpi-bar { display: block; height: 7px; border-radius: 99px; background: #e2e8f0; overflow: hidden; margin-top: 4px; }
        /* the global `i { display: inline-block !important }` (icon font rule) sits the
           fill on the line's baseline, below the 7-8px track, where overflow:hidden
           clips it away - force block so the fill really fills the track. */
        .hm-kpi-bar > i, .hm-pin-pop-tr > i { display: block !important; height: 100%; border-radius: 99px; background: var(--k, #0369a1); }
        .hm-kpi-bar > i:not([style*="width: 0%"]) { min-width: 8px; } /* keep a 1-4% fill visible */
        .hm-kpi-bar > i { background: linear-gradient(90deg, color-mix(in srgb, var(--k, #0369a1) 70%, #fff), var(--k, #0369a1)); }
        /* พชอ.ไต section: the KPI column is as tall as map + table, so each tile
           is laid out as header (icon + label) / big value / caption / bar and
           centred - the text fills the tile instead of floating in the middle. */
        .hm-kidney .hm-kpi {
            display: grid; grid-template-columns: auto 1fr; align-content: center; align-items: center;
            column-gap: 12px; row-gap: 5px; padding: 18px 22px;
        }
        .hm-kidney .hm-kpi-body { display: contents; }
        .hm-kidney .hm-kpi-ico { grid-column: 1; grid-row: 1; width: 50px; height: 50px; min-width: 50px; font-size: 1.25rem; border-radius: 14px; }
        .hm-kidney .hm-kpi-l { grid-column: 2; grid-row: 1; font-size: .9rem; color: #475569; line-height: 1.3; }
        .hm-kidney .hm-kpi-v { grid-column: 1 / -1; grid-row: 2; font-size: 2.15rem; margin-top: 8px; }
        .hm-kidney .hm-kpi-v small { font-size: .9rem; }
        .hm-kidney .hm-kpi-d { grid-column: 1 / -1; grid-row: 3; font-size: .82rem; }
        .hm-kidney .hm-kpi-bar { grid-column: 1 / -1; grid-row: 4; height: 8px; margin-top: 8px; }
        /* tighter vertical rhythm so the whole section is shorter */
        .hm-kidney .hm-card { padding: 12px 16px; }
        .hm-kidney .hm-cap { margin-bottom: 6px; }
        .hm-kidney table.hm-t td { padding: 6px 6px; }
        .hm-kidney table.hm-t th { padding-bottom: 6px; }
        .hm-kidney .hm-ind { padding: 2px 0; }
        .hm-kidney .hm-grid.kd, .hm-grid.kd { gap: 12px; }
        .hm-kidney .hm-stack { gap: 12px; }
        .hm-kidney .hm-kpi { padding: 14px 20px; row-gap: 4px; }
        .hm-kidney .hm-kpi-v { font-size: 2rem; margin-top: 4px; }
        .hm-kd-map #hmPinMap { min-height: 280px; }

        /* Butterfly chart: avg sodium before (left, red) | total menus (center) | after (right, green) */
        .hm-fly-legend { display: flex; gap: 16px; font-size: .74rem; color: #475569; font-weight: 700; margin: -2px 0 4px; }
        .hm-fly-legend i { display: inline-block; width: 12px; height: 12px; border-radius: 4px; margin-right: 6px; vertical-align: -1px; }
        .hm-fly { display: grid; gap: 0; }
        .hm-fly-row { display: grid; grid-template-columns: 112px minmax(0, 1fr) 60px minmax(0, 1fr) 70px; align-items: center; gap: 8px; padding: 4px 0; border-bottom: 1px solid #f1f5f9; }
        .hm-fly-row:last-child { border-bottom: none; }
        .hm-fly-prov { font-size: .84rem; font-weight: 700; color: #1e293b; white-space: nowrap; }
        .hm-fly-side { display: flex; align-items: center; gap: 8px; min-width: 0; height: 26px; }
        .hm-fly-left { justify-content: flex-end; }
        .hm-fly-val { font-size: .8rem; font-weight: 800; color: #1e293b; font-variant-numeric: tabular-nums; }
        .hm-fly-bar { display: block; height: 18px; position: relative; }
        .hm-fly-before {
            border-radius: 12px 4px 4px 12px;
            background: linear-gradient(180deg, #fda4b4 0%, #fb526b 45%, #e11d48 100%);
            box-shadow: 0 1px 0 rgba(255, 255, 255, .55) inset, 0 8px 12px -6px rgba(225, 29, 72, .55);
        }
        .hm-fly-after {
            border-radius: 4px 12px 12px 4px;
            background: linear-gradient(180deg, #86efac 0%, #22c55e 45%, #15803d 100%);
            box-shadow: 0 1px 0 rgba(255, 255, 255, .55) inset, 0 8px 12px -6px rgba(21, 128, 61, .55);
        }
        .hm-fly-bar::after { content: ""; position: absolute; left: 6px; right: 6px; top: 3px; height: 5px; border-radius: 99px; background: rgba(255, 255, 255, .38); }
        .hm-fly-core {
            width: 48px; height: 48px; margin: 0 auto; border-radius: 50%; display: flex; flex-direction: column; align-items: center; justify-content: center;
            background: radial-gradient(circle at 32% 25%, #3b5ba9 0%, #1e3a8a 58%, #172554 100%);
            color: #fff; line-height: 1.05;
            box-shadow: 0 1px 0 rgba(255, 255, 255, .25) inset, 0 8px 14px -8px rgba(23, 37, 84, .75), 0 0 0 3px #fff, 0 0 0 4px #dbe4f5;
        }
        .hm-fly-core b { font-size: .98rem; font-weight: 800; font-variant-numeric: tabular-nums; }
        .hm-fly-core span { font-size: .54rem; font-weight: 700; opacity: .8; }
        .hm-fly-core.is-empty { background: radial-gradient(circle at 32% 25%, #e2e8f0, #cbd5e1); color: #64748b; box-shadow: 0 0 0 3px #fff, 0 0 0 4px #e2e8f0; }
        .hm-fly-none { font-size: .76rem; }
        .hm-fly-total { margin-top: 8px; padding: 12px 0 2px; border-top: 2px solid #0f766e; border-bottom: none; }
        .hm-fly-total .hm-fly-prov { color: #0f766e; font-weight: 800; }
        .hm-fly-total .hm-fly-val { font-size: .86rem; }
        .hm-fly-pct { text-align: right; }
        @media (max-width: 760px) {
            .hm-fly-row { grid-template-columns: 1fr 64px 1fr; row-gap: 4px; }
            .hm-fly-prov { grid-column: 1 / -1; }
            .hm-fly-pct { grid-column: 1 / -1; text-align: left; }
            .hm-fly-core { grid-column: 2; grid-row: 2; }
            .hm-fly-left { grid-column: 1; grid-row: 2; }
            .hm-fly-right { grid-column: 3; grid-row: 2; }
        }

        /* Tier card: donut + tier list + before/after average, filling the card */
        .hm-tier-card { display: flex; flex-direction: column; }
        .hm-tier-body { display: flex; flex-wrap: wrap; align-items: center; gap: 14px 22px; flex: 1; padding: 6px 2px 14px; }
        .hm-tier-ring { position: relative; flex: 1 1 170px; max-width: 210px; aspect-ratio: 1; margin: 0 auto; }
        .hm-tier-ring svg { display: block; width: 100%; height: 100%; transform: rotate(-90deg); }
        .hm-tier-ring circle { transition: opacity .15s ease; }
        .hm-tier-center { position: absolute; inset: 0; display: flex; flex-direction: column; align-items: center; justify-content: center; text-align: center; line-height: 1.1; pointer-events: none; }
        .hm-tier-center b { font-size: 2.2rem; font-weight: 800; color: #0f172a; letter-spacing: -.5px; font-variant-numeric: tabular-nums; }
        .hm-tier-center span { margin-top: 4px; font-size: .74rem; font-weight: 600; color: #94a3b8; }
        .hm-tier-list { flex: 1 1 210px; min-width: 0; display: flex; flex-direction: column; gap: 8px; }
        .hm-tier-item { display: flex; align-items: center; gap: 12px; padding: 9px 14px; border-radius: 14px; background: #f8fafc; border: 1px solid #eef1f6; transition: background .15s ease, border-color .15s ease, box-shadow .15s ease; }
        .hm-tier-item:hover { background: #fff; border-color: var(--tp-c); box-shadow: 0 6px 16px -8px var(--tp-c); }
        .hm-tier-dot { width: 13px; height: 13px; border-radius: 50%; background: var(--tp-c); flex: 0 0 auto; }
        .hm-tier-name { flex: 1; min-width: 0; font-size: .88rem; font-weight: 700; color: #1e293b; line-height: 1.25; }
        .hm-tier-name small { display: block; margin-top: 1px; font-size: .68rem; font-weight: 600; color: #94a3b8; }
        .hm-tier-val { text-align: right; line-height: 1.2; white-space: nowrap; }
        .hm-tier-val b { display: block; font-size: .98rem; font-weight: 800; color: #0f172a; font-variant-numeric: tabular-nums; }
        .hm-tier-val small { display: block; margin-top: 1px; font-size: .7rem; font-weight: 600; color: #94a3b8; }
        .hm-tier-item.is-zero { opacity: .5; }
        .hm-tier-cmp { margin-top: auto; padding: 10px 14px; border-radius: 14px; background: linear-gradient(155deg, #fffaf0, #fff); border: 1px solid #fde7c3; display: grid; gap: 6px; }
        .hm-tier-cmp-head { display: flex; justify-content: space-between; align-items: center; gap: 8px; font-size: .76rem; font-weight: 800; color: #475569; }
        .hm-tier-cmp-row { display: grid; grid-template-columns: 54px minmax(0, 1fr) 54px; gap: 8px; align-items: center; font-size: .76rem; font-weight: 700; color: #475569; }
        .hm-tier-cmp-row b { text-align: right; color: #1e293b; font-variant-numeric: tabular-nums; }
        .hm-tier-cmp-trk { display: block; height: 12px; }
        .hm-tier-cmp-trk > span { display: block; height: 100%; }
        .hm-tier-cmp-trk > .hm-fly-before { border-radius: 4px 12px 12px 4px; }
        .hm-tier-cmp-trk > .hm-fly-after { border-radius: 4px 12px 12px 4px; }
        .hm-tier-cmp-trk > span::after { display: none; }
        .hm-tier-empty { flex: 1; display: flex; flex-direction: column; align-items: center; justify-content: center; text-align: center; gap: 6px; color: #94a3b8; padding: 20px 8px; font-size: .8rem; }
        .hm-tier-empty i { font-size: 1.8rem; color: #cbd5e1; }
        .hm-tier-empty b { color: #475569; font-size: .88rem; }

        .hm-dot { display: inline-block; width: 9px; height: 9px; border-radius: 50%; margin-right: 6px; vertical-align: middle; }
        table.hm-t { width: 100%; border-collapse: collapse; font-size: .84rem; }
        table.hm-t th { font-size: .72rem; color: #475569; text-align: left; font-weight: 800; padding: 0 6px 8px; border-bottom: 2px solid #e8edf3; white-space: nowrap; }
        table.hm-t td { padding: 9px 6px; border-bottom: 1px solid #f1f5f9; vertical-align: middle; color: #1e293b; }
        table.hm-t tr:last-child td { border-bottom: none; }
        table.hm-t .num { text-align: right; font-variant-numeric: tabular-nums; }
        table.hm-t tr.hm-total td { background: #f8fafc; }
        /* สรุปอัตราป่วยรายจังหวัด: เพิ่มช่องไฟระหว่างคอลัมน์/แถวให้สมดุล */
        .hm-grid.ht > .hm-ht-sum table.hm-t th,
        .hm-grid.ht > .hm-ht-sum table.hm-t td { padding-left: 14px; padding-right: 14px; }
        .hm-grid.ht > .hm-ht-sum table.hm-t th:first-child,
        .hm-grid.ht > .hm-ht-sum table.hm-t td:first-child { padding-left: 8px; }
        .hm-grid.ht > .hm-ht-sum table.hm-t td { padding-top: 11px; padding-bottom: 11px; }
        .hm-grid.ht > .hm-ht-sum table.hm-t th { padding-bottom: 11px; }
        .hm-grid.ht > .hm-ht-sum table.hm-t td:last-child { padding-right: 8px; }
        .hm-grid.ht > .hm-ht-sum .hm-dot { margin-right: 10px; }
        .hm-muted { color: #94a3b8; }
        .hm-bar { display: flex; align-items: center; gap: 8px; }
        .hm-bar-tr { flex: 1; height: 9px; border-radius: 99px; background: #e2e8f0; overflow: hidden; min-width: 60px; }
        .hm-bar-fl { height: 100%; border-radius: 99px; background: var(--a); }
        /* สีแท่งอัตราป่วยรายจังหวัด (ค่าเฉลี่ย ± SD) */
        .hm-bar-fl.hm-band-red { background: linear-gradient(90deg, #e5484d, #c62a31); }
        .hm-bar-fl.hm-band-yellow { background: linear-gradient(90deg, #f7d154, #eab308); }
        .hm-bar-fl.hm-band-green { background: linear-gradient(90deg, #3fb36e, #2a8f52); }
        .hm-bandleg { margin-top: 10px; padding: 9px 11px 10px; border: 1px solid #e8edf3; border-radius: 12px; background: #fafbfd; }
        .hm-bandleg-h { font-size: .84rem; font-weight: 800; color: #0f172a; margin-bottom: 8px; }
        .hm-bandleg-h span { display: block; margin: 1px 0 0; font-weight: 600; color: #64748b; font-size: .78rem; font-variant-numeric: tabular-nums; }
        .hm-bandleg-g { display: grid; gap: 5px; }
        .hm-bandleg-i { --bc: #94a3b8; display: grid; grid-template-columns: 5px minmax(0, 1fr) auto; align-items: center; gap: 9px; padding: 5px 9px 5px 7px; border-radius: 10px; background: #fff; border: 1px solid #e8edf3; min-width: 0; }
        .hm-bandleg-i.red { --bc: #d6303a; } .hm-bandleg-i.yellow { --bc: #eab308; } .hm-bandleg-i.green { --bc: #2f9e5b; }
        .hm-bandleg-i i { display: block; align-self: stretch; min-height: 28px; border-radius: 99px; background: var(--bc); }
        .hm-bandleg-i div { display: flex; flex-wrap: wrap; align-items: baseline; gap: 0 8px; min-width: 0; }
        .hm-bandleg-i b { font-size: .84rem; font-weight: 800; color: #0f172a; line-height: 1.35; white-space: nowrap; }
        .hm-bandleg-i small { font-size: .78rem; font-weight: 700; color: #475569; font-variant-numeric: tabular-nums; white-space: nowrap; }
        .hm-bandleg-i em { flex-basis: 100%; font-style: normal; font-size: .72rem; font-weight: 600; line-height: 1.35; color: #64748b; }
        .hm-bandleg-i u { text-decoration: none; font-size: .72rem; font-weight: 800; color: #1e293b; background: #e8eef5; padding: 1px 9px; border-radius: 999px; white-space: nowrap; }
                .hm-bar-pc { min-width: 42px; text-align: right; font-weight: 800; font-size: .82rem; font-variant-numeric: tabular-nums; }
        .hm-hl { display: inline-flex; align-items: center; padding: 1px 8px; border-radius: 999px; margin-left: 7px; font-size: .68rem; font-weight: 800; white-space: nowrap; vertical-align: middle; }
        .hm-hl.hi { background: rgba(231, 76, 60, .12); color: #c0392b; }
        .hm-hl.lo { background: rgba(39, 174, 96, .12); color: #1e8449; }
        .hm-flag {
            display: inline-flex; align-items: center; justify-content: center; width: 16px; height: 16px;
            border-radius: 50%; background: #fde68a; color: #92400e; font-size: .65rem; font-weight: 800;
            margin-left: 5px; cursor: help; font-style: normal;
        }
        .hm-ind { display: grid; grid-template-columns: 22px 1fr auto; gap: 8px; align-items: center; padding: 4px 0; font-size: .8rem; font-weight: 700; color: #1e293b; }
        .hm-ind-b { width: 22px; height: 22px; border-radius: 7px; display: grid; place-items: center; color: #fff; font-size: .74rem; font-weight: 800; }
        .hm-ind-c { font-weight: 800; }
        .hm-tier { display: flex; height: 26px; border-radius: 99px; overflow: hidden; font-size: .72rem; font-weight: 800; color: #fff; gap: 2px; }
        .hm-tier span { display: grid; place-items: center; min-width: 22px; }
        .hm-tier-leg { display: flex; flex-wrap: wrap; gap: 4px 14px; font-size: .74rem; color: #475569; margin-top: 8px; }
        .hm-tier-leg i { display: inline-block; width: 9px; height: 9px; border-radius: 3px; margin-right: 5px; }

        /* Awareness: 4 KPI cards stacked on the left, the 3 chart cards beside them */
        .hm-aw-layout { display: grid; grid-template-columns: 240px minmax(0, 1fr); gap: 14px; align-items: stretch; }
        .hm-aw-kpis { display: flex; flex-direction: column; gap: 10px; min-width: 0; }
        .hm-aw-kpis .kpi-summary-row { display: flex; flex-direction: column; flex: 1; gap: 10px; margin-bottom: 0; }
        .hm-aw-kpis .kpi-summary-card { flex: 1; padding: 9px 16px; gap: 12px; }
        .hm-aw-kpis .kpi-summary-icon { width: 40px; height: 40px; min-width: 40px; border-radius: 12px; font-size: 1rem; }
        .hm-aw-kpis .kpi-summary-value { font-size: 1.55rem; }
        .hm-aw-kpis .kpi-pending-note { margin: 0; }
        .hm-aw-main { min-width: 0; }
        /* tighter vertical rhythm for the three awareness cards */
        .hm-aw-main .home-row-top .dash-card { padding: 12px 18px !important; }
        .hm-aw-main .home-row-top .dash-card-head { margin-bottom: 6px; padding-bottom: 8px; }
        .hm-aw-main #donutLegend { margin-top: 8px !important; gap: 4px !important; }
        .hm-aw-main #metricsListView { gap: 8px !important; }
        .hm-aw-main .home-row-top { margin-bottom: 0 !important; }
        @media (max-width: 1280px) {
            .hm-aw-layout { grid-template-columns: 1fr; }
            .hm-aw-kpis .kpi-summary-row { display: grid; grid-template-columns: repeat(4, minmax(0, 1fr)); }
        }
        @media (max-width: 960px) { .hm-aw-kpis .kpi-summary-row { grid-template-columns: repeat(2, minmax(0, 1fr)); } }
        @media (max-width: 560px) { .hm-aw-kpis .kpi-summary-row { grid-template-columns: 1fr; } }

        @media (max-width: 1100px) {
            .hm-band { grid-template-columns: repeat(2, minmax(0, 1fr)); }
            .hm-grid.kd, .hm-grid.mn, .hm-grid.ht, .hm-grid.ht.has-trend, .hm-grid.pd { grid-template-columns: 1fr; }
            .hm-grid.kd { grid-template-areas: none; }
            .hm-grid.kd > .hm-kcol, .hm-grid.kd > .hm-kd-cmp, .hm-grid.kd > .hm-kd-map, .hm-grid.kd > .hm-stack { grid-area: auto; }
            .hm-kd-map #hmPinMap { min-height: 280px; }
            .hm-kcol { grid-template-columns: repeat(auto-fit, minmax(220px, 1fr)); }
            .hm-insight { margin-left: 0; }
        }
        @media (max-width: 560px) {
            .hm-band { grid-template-columns: 1fr; }
            .hm-more { margin-left: 0; }
        }

        /* กราฟก่อน-หลังปรับสูตร รายจังหวัด (หน้าแรก): รูปแบบเดียวกับหน้า reduced-sodium-menu - แท่งมนอ่านง่าย รางพื้นหลัง ตัวเลขชัด */
        .hm-fly-card .hm-fly-row { grid-template-columns: 122px minmax(0, 1fr) 46px minmax(0, 1fr) 70px; gap: 12px; padding: 5px 6px; border-bottom: 1px solid #eef2f7; border-radius: 10px; transition: background-color .15s ease; }
        .hm-fly-card .hm-fly-row:not(.hm-fly-total):hover { background: #f8fafd; }
        .hm-fly-card .hm-fly-row:last-child { border-bottom: none; }
        .hm-fly-card .hm-fly-prov { display: flex; align-items: center; gap: 7px; }
        .hm-fly-card .hm-fly-side { position: relative; gap: 8px; height: 24px; }
        .hm-fly-card .hm-fly-side::before { content: ""; position: absolute; left: 0; right: 0; top: 50%; height: 16px; transform: translateY(-50%); border-radius: 99px; background: #f1f5f9; box-shadow: inset 0 1px 1px rgba(15, 23, 42, .05); pointer-events: none; }
        .hm-fly-card .hm-fly-side > * { position: relative; z-index: 1; }
        .hm-fly-card .hm-fly-val { min-width: 38px; color: #334155; }
        .hm-fly-card .hm-fly-left .hm-fly-val { text-align: right; }
        .hm-fly-card .hm-fly-right .hm-fly-val { text-align: left; }
        .hm-fly-card .hm-fly-bar { height: 12px; border-radius: 99px !important; }
        .hm-fly-card .hm-fly-before { margin-right: 2px; transform-origin: right center; background: linear-gradient(180deg, #fb8da0 0%, #f0476a 55%, #dc2650 100%); box-shadow: 0 1px 0 rgba(255,255,255,.5) inset, 0 3px 6px -3px rgba(225, 29, 72, .45); animation: hmFlyGrowH .7s cubic-bezier(.2,.8,.2,1) both; }
        .hm-fly-card .hm-fly-after { margin-left: 2px; transform-origin: left center; background: linear-gradient(180deg, #7de3a5 0%, #2fc16a 55%, #1a9a4f 100%); box-shadow: 0 1px 0 rgba(255,255,255,.5) inset, 0 3px 6px -3px rgba(21, 128, 61, .45); animation: hmFlyGrowH .7s cubic-bezier(.2,.8,.2,1) both; }
        .hm-fly-card .hm-fly-bar::after { left: 8px; right: 8px; top: 2px; height: 3px; opacity: .7; }
        @keyframes hmFlyGrowH { from { transform: scaleX(.05); opacity: .4; } to { transform: scaleX(1); opacity: 1; } }
        @media (prefers-reduced-motion: reduce) { .hm-fly-card .hm-fly-bar { animation: none !important; } }
        .hm-fly-card .hm-fly-core { width: 40px; height: 40px; box-shadow: 0 -2px 3px rgba(10, 18, 60, .3) inset, 0 4px 8px -4px rgba(23, 37, 84, .6), 0 0 0 2px #fff, 0 0 0 3px #dbe4f5; }
        .hm-fly-card .hm-fly-core b { font-size: .8rem; }
        .hm-fly-card .hm-fly-core span { font-size: .5rem; }
        .hm-fly-card .hm-fly-pct .hm-chip { font-size: .76rem; font-weight: 800; padding: 3px 9px; }
        .hm-fly-card .hm-fly-none { color: #94a3b8; }
        .hm-fly-card .hm-fly-total { margin-top: 6px; padding: 10px 6px 6px; border-top: 2px solid #0f766e; border-bottom: none; background: linear-gradient(180deg, #f2fbf9, #fff); border-radius: 0 0 12px 12px; }

        /* การ์ดแผนที่ร้อยละความตระหนักรู้: ให้แผนที่ขยายเต็มพื้นที่การ์ดตามความสูงของการ์ดข้างเคียง */
        .hm-aw-main .home-row-top .dash-card.accent-pink { display: flex; flex-direction: column; }
        /* แผนที่วางแบบ absolute ในกรอบ จึงไม่ดันความสูงการ์ด (กันขนาดขยายวนไม่รู้จบ) */
        .hm-aw-main .hm-awmap-wrap { flex: 1 1 auto; position: relative; min-height: 330px; }
        .hm-aw-main .hm-awmap-wrap #map-container { position: absolute; top: 0; right: 0; bottom: 0; left: 0; width: 100% !important; height: 100% !important; }

        /* กราฟรายจังหวัดในการ์ดพฤติกรรม: ขยายเต็มพื้นที่การ์ด (กราฟลอยอยู่ในกรอบ ไม่ดันความสูงการ์ด) */
        .aw-beh-card #inlineChartContainer { flex-direction: column; }
        .aw-beh-card .aw-beh-chartbox { flex: 1 1 auto; min-height: 220px; }
    </style>
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/sweetalert2@11/dist/sweetalert2.min.css">
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
    <script src="{{ asset('vendor/chartjs/chart.min.js') }}"></script>
    <script src="{{ asset('vendor/chartjs/chartjs-plugin-datalabels.min.js') }}"></script>

    <!-- Filter bar - moved here (right under the page header) from
         inside the "พฤติกรรมการบริโภคโซเดียม" card, matching the
         page-level filter bar placement used on kidney-dhb-report.blade.php
         and consumption-report.blade.php -->
    <div class="home-filter-bar">
        <div class="home-filter-title">
            <i class="fas fa-filter"></i> ตัวกรอง
        </div>

        <label class="home-filter-item">
            <i class="far fa-calendar-alt home-filter-item-icon"></i>
            <span class="home-filter-item-label">ปี</span>
            <select id="fiscal_year" class="awareness-filter-select" onchange="updateFilters()">
                <option value="" {{ $awarenessMetrics['current_year'] === '' ? 'selected' : '' }}>ทั้งหมด</option>
                @foreach ($years as $year)
                    <option value="{{ $year }}"
                        {{ $awarenessMetrics['current_year'] == $year ? 'selected' : '' }}>
                        {{ $year }}
                    </option>
                @endforeach
            </select>
            <i class="fas fa-chevron-down home-filter-chevron"></i>
        </label>

        <label class="home-filter-item">
            <i class="fas fa-map-marker-alt home-filter-item-icon"></i>
            <span class="home-filter-item-label">จังหวัด</span>
            <select id="province" class="awareness-filter-select" onchange="updateFilters()">
                <option value="all" {{ $awarenessMetrics['current_province'] == 'all' ? 'selected' : '' }}>
                    ทุกจังหวัด
                </option>
                @foreach ($awarenessMetrics['target_provinces'] as $p)
                    <option value="{{ $p }}"
                        {{ $awarenessMetrics['current_province'] == $p ? 'selected' : '' }}>{{ $p }}
                    </option>
                @endforeach
            </select>
            <i class="fas fa-chevron-down home-filter-chevron"></i>
        </label>

        <a href="{{ route('home') }}" class="home-filter-reset">
            <i class="fas fa-undo-alt"></i> ล้างตัวกรอง
        </a>
    </div>

    @php
        // ---- shared helpers for the summary dashboard (band + 4 module sections) ----
        $hmProvColor = [
            'อุบลราชธานี' => '#2563eb', 'ศรีสะเกษ' => '#d97706', 'ยโสธร' => '#7c3aed',
            'อำนาจเจริญ' => '#16a34a', 'มุกดาหาร' => '#db2777',
        ];
        $hmKd = $homeModules['kidney'];
        $hmMn = $homeModules['menu'];
        $hmHt = $homeModules['ht'];
        $hmProvince = $awarenessMetrics['current_province'];
        $hmProvQ = $hmProvince !== 'all' ? ['province' => $hmProvince] : [];
        $hmLink = fn($route, $yearKey = 'fiscal_year') => route($route, array_merge(
            $fiscalYear !== '' ? [$yearKey => $fiscalYear] : [],
            $hmProvQ
        ));
        $hmScope = 'ปี ' . $fiscalYearLabel . ($hmProvince !== 'all' ? ' · ' . $hmProvince : '');
        $hmItemNames = [
            '1' => 'การขับเคลื่อนงาน NCD (ระดับอำเภอ)', '2' => 'การจัดการข้อมูลเฝ้าระวัง',
            '3' => 'การกำหนดประเด็นปัญหาและแผนงาน', '4' => 'นโยบายสาธารณะ NCD/โรคไต',
            '5' => 'การจัดการสิ่งแวดล้อมเพื่อสุขภาพ', '6' => 'ชุมชนลดปัจจัยเสี่ยง NCD/โรคไต',
            '7' => 'การจัดบริการเชิงรุกในชุมชน', '8' => 'ด้านโรคไต (8.1-8.3)',
        ];

        // awareness tile status: pass-rate against the 80% criterion
        $hmAwPending = $awarenessCriteriaPending ?? false;
        $hmAwRate = (float) $awarenessOverallRate;

        // awareness insight: sample imbalance + best province + all-zero behaviours
        $hmAwRows = collect($awarenessData);
        $hmAwTotal = (int) $hmAwRows->sum('total_people');
        $hmAwBiggest = $hmAwRows->sortByDesc('total_people')->first();
        $hmAwBest = $hmAwRows->where('total_people', '>', 0)->sortByDesc('passed_percentage')->first();
        $hmAwBiggestShare = ($hmAwBiggest && $hmAwTotal > 0) ? (int) round($hmAwBiggest['total_people'] / $hmAwTotal * 100) : 0;
        $hmZeroBehaviours = collect([
            'no_seasoning' => 'เติมน้ำปลา/เครื่องปรุง', 'no_instant_food' => 'อาหารแปรรูป',
            'no_pickled_food' => 'อาหารหมักดอง', 'reduction_effort' => 'ลดการจิ้ม/ลดซด',
        ])->filter(fn($label, $k) => ($awarenessMetrics['total'] ?? 0) > 0 && ($awarenessMetrics[$k]['count'] ?? 0) === 0)->values();

        // kidney tile status
        $hmKdPct = $hmKd['coverage_percent'];
        // menu tile status
        $hmMnMeasuredPct = $hmMn['total'] > 0 ? round($hmMn['measured'] / $hmMn['total'] * 100) : 0;
        // HT: latest month label
        $hmHtLatest = $hmHt['latest_month'];
        // Headline total = province-table total (sum of total_a), shared by the tile, KPI card and monthly chart chip.
        $hmHtMonthTotal = (int) $hmHt['total_cases'];
        // product tile (same numbers as the "ผลิตภัณฑ์ลดโซเดียม" section further down)
        $hmPdTile = $productStats ?? ['total' => 0, 'certified' => 0, 'certified_percent' => null, 'top_type' => null, 'top_type_count' => 0, 'type_total' => 0];
        $hmPdTileProv = collect($productCountData ?? [])->filter(fn($r) => ($r['count'] ?? 0) > 0)->count();
    @endphp

    <!-- ===== Summary band: one tile per module (click = jump to its section) ===== -->
    <div class="hm-band">
        <a class="hm-tile" href="#sec-awareness" style="--c: #db2777;">
            <div class="hm-tile-name">ความตระหนักรู้ด้านโซเดียม</div>
            @if ($hmAwPending)
                <div class="hm-tile-big">—</div>
                <div class="hm-tile-sub">ยังไม่ได้ตั้งเกณฑ์การประเมินปี {{ $fiscalYearLabel }}</div>
                <div class="hm-tile-st"><span class="hm-chip none"><i class="fas fa-clock"></i> รอเกณฑ์การประเมิน</span></div>
            @else
                <div class="hm-tile-big">{{ $awarenessOverallRate }}%<small> ผ่านเกณฑ์</small></div>
                <div class="hm-tile-sub">{{ number_format($awarenessOverallPass) }} จาก {{ number_format($awarenessOverallTotal) }} คนที่ตอบแบบประเมิน</div>
                @if ($hmAwBest)
                    <div class="hm-tile-sub"><i class="fas fa-trophy" style="color:#db2777;"></i> ผ่านเกณฑ์สูงสุด: <span class="hm-em">{{ $hmAwBest['name'] }} {{ rtrim(rtrim(number_format($hmAwBest['passed_percentage'], 1), '0'), '.') }}%</span></div>
                @endif
                <div class="hm-tile-st">
                    @if ($hmAwRate >= 80)
                        <span class="hm-chip ok"><i class="fas fa-circle-check"></i> ผ่านเป้า 80%</span>
                    @else
                        <span class="hm-chip warn"><i class="fas fa-circle-half-stroke"></i> ต่ำกว่าเป้า 80%</span>
                    @endif
                </div>
            @endif
        </a>
        <a class="hm-tile" href="#sec-kidney" style="--c: #0a8bbe;">
            <div class="hm-tile-name">พชอ.ไต - ผลการดำเนินงานพื้นที่</div>
            @if ($hmKd['pin_total'] > 0)
                @php $hmKdPinPct = $hmKd['pin_total'] > 0 ? round($hmKd['answered_pins'] / $hmKd['pin_total'] * 100) : 0; @endphp
                <div class="hm-tile-big">{{ number_format($hmKd['pin_all'] ?? $hmKd['pin_total']) }}<small> พื้นที่ที่ดำเนินงาน</small></div>
                @if (($hmKd['display_pin_count'] ?? 0) > 0)
                    <div class="hm-tile-sub">รวมหมุดแสดงผลบนแผนที่ <b>{{ number_format($hmKd['display_pin_count']) }}</b> หมุด</div>
                @endif
                @php
                    $hmKdAreas = collect($hmKd['area_counts'] ?? [])->filter(fn($n) => $n > 0)->sortDesc();
                    $hmKdTopName = $hmKdAreas->keys()->first();
                @endphp
                @if ($hmKdTopName)
                    <div class="hm-tile-sub"><i class="fas fa-trophy" style="color:#0a8bbe;"></i> ดำเนินงานมากที่สุด: <span class="hm-em">{{ $hmKdTopName }} {{ number_format($hmKdAreas->first()) }} แห่ง</span></div>
                @endif
                <div class="hm-tile-st">
                    @if ($hmKdPinPct >= 80)
                        <span class="hm-chip ok"><i class="fas fa-circle-check"></i> ดำเนินงานแล้ว {{ $hmKdPinPct }}%</span>
                    @elseif ($hmKdPinPct > 0)
                        <span class="hm-chip warn"><i class="fas fa-circle-half-stroke"></i> ดำเนินงานแล้ว {{ $hmKdPinPct }}%</span>
                    @else
                        <span class="hm-chip none"><i class="fas fa-circle-minus"></i> ยังไม่รายงาน</span>
                    @endif
                </div>
            @else
                <div class="hm-tile-big">—</div>
                <div class="hm-tile-sub">ยังไม่มีการปักหน่วยงานพื้นที่ดำเนินงานในช่วงนี้</div>
                <div class="hm-tile-st"><span class="hm-chip none"><i class="fas fa-circle-minus"></i> ยังไม่มีข้อมูล</span></div>
            @endif
        </a>
        <a class="hm-tile" href="#sec-menu" style="--c: #d97706;">
            <div class="hm-tile-name">เมนูลดโซเดียม</div>
            @if ($hmMn['best_percent'] !== null)
                <div class="hm-tile-big">{{ $hmMn['best_percent'] }}%<small> ผ่านเกณฑ์ดีมาก</small></div>
                <div class="hm-tile-sub">{{ number_format($hmMn['total']) }} เมนู · เสี่ยงสูง {{ number_format($hmMn['high_risk_count']) }} เมนู</div>
                <div class="hm-tile-st">
                    <span class="hm-chip {{ $hmMnMeasuredPct >= 80 ? 'ok' : 'warn' }}"><i class="fas fa-vial"></i> วัดก่อน-หลังแล้ว {{ number_format($hmMn['measured']) }}/{{ number_format($hmMn['total']) }} เมนู</span>
                </div>
            @else
                <div class="hm-tile-big">{{ number_format($hmMn['total']) }}<small> เมนู</small></div>
                <div class="hm-tile-sub">ยังไม่มีเมนูที่วัดโซเดียมก่อน-หลังปรับสูตร</div>
                <div class="hm-tile-st"><span class="hm-chip none"><i class="fas fa-circle-minus"></i> ยังไม่มีผลวัด</span></div>
            @endif
        </a>
        <a class="hm-tile" href="#sec-ht" style="--c: #0d9488;">
            <div class="hm-tile-name">อัตราป่วยรายใหม่ความดันโลหิตสูง (HT)</div>
            @if ($hmHt['rate'] !== null)
                <div class="hm-tile-big">{{ number_format($hmHt['rate'], 2) }}<small> ต่อแสนประชากร</small></div>
            @else
                <div class="hm-tile-big">{{ number_format($hmHtMonthTotal) }}<small> คน</small></div>
            @endif
            <div class="hm-tile-sub">
                ผู้ป่วยใหม่ {{ number_format($hmHtMonthTotal) }} คน
                @if ($hmHtLatest)
                    · {{ $hmHtLatest['label'] }} {{ number_format($hmHtLatest['value']) }} คน
                @endif
            </div>
            <div class="hm-tile-st">
                @if ($hmHt['unrated_provinces']->count() > 0)
                    <span class="hm-chip warn"><i class="fas fa-triangle-exclamation"></i> {{ $hmHt['unrated_provinces']->count() }} จังหวัดไม่มีเป้าหมายประชากร</span>
                @elseif ($hmHt['total_cases'] > 0)
                    <span class="hm-chip ok"><i class="fas fa-circle-check"></i> มีเป้าหมายครบทุกจังหวัด</span>
                @else
                    <span class="hm-chip none"><i class="fas fa-circle-minus"></i> ยังไม่มีข้อมูล</span>
                @endif
            </div>
        </a>
        <a class="hm-tile" href="#sec-products" style="--c: #6366f1;">
            <div class="hm-tile-name">ผลิตภัณฑ์ลดโซเดียม</div>
            @if (($hmPdTile['total'] ?? 0) > 0)
                <div class="hm-tile-big">{{ number_format($hmPdTile['total']) }}<small> รายการ</small></div>
                <div class="hm-tile-sub">จาก {{ number_format($hmPdTileProv) }} จังหวัด · ได้รับรองมาตรฐาน {{ number_format($hmPdTile['certified']) }} รายการ</div>
                @if ($hmPdTile['top_type'])
                    <div class="hm-tile-sub"><i class="fas fa-tags" style="color:#6366f1;"></i> ยอดนิยม: <span class="hm-em">{{ $hmPdTile['top_type'] }}</span> ({{ number_format($hmPdTile['top_type_count']) }} รายการ)</div>
                @endif
                <div class="hm-tile-st">
                    @if (($hmPdTile['certified_percent'] ?? 0) >= 80)
                        <span class="hm-chip ok"><i class="fas fa-circle-check"></i> ได้มาตรฐาน {{ $hmPdTile['certified_percent'] }}%</span>
                    @else
                        <span class="hm-chip warn"><i class="fas fa-circle-half-stroke"></i> ได้มาตรฐาน {{ $hmPdTile['certified_percent'] ?? 0 }}%</span>
                    @endif
                </div>
            @else
                <div class="hm-tile-big">—</div>
                <div class="hm-tile-sub">ยังไม่มีข้อมูลผลิตภัณฑ์ลดโซเดียมในช่วงนี้</div>
                <div class="hm-tile-st"><span class="hm-chip none"><i class="fas fa-circle-minus"></i> ยังไม่มีข้อมูล</span></div>
            @endif
        </a>
    </div>

    <!-- ===== 1. ความตระหนักรู้ด้านโซเดียม (existing cards, same data/JS) ===== -->
    <section class="hm-sec hm-aware" id="sec-awareness">
        <div class="hm-sec-head">
            <div class="hm-sec-ic"><i class="fas fa-lightbulb"></i></div>
            <h3>ความตระหนักรู้ด้านโซเดียม</h3>
            <span class="hm-scope">{{ $hmScope }}</span>
            <a class="hm-more" href="{{ $hmLink('awareness') }}">ดูรายละเอียด</a>
        </div>

    <div class="hm-aw-layout">
    <!-- Overall KPI summary (left column): จำนวนผู้เข้าร่วมทั้งหมด / ผ่านเกณฑ์ / ไม่ผ่านเกณฑ์ /
         อัตราผ่านเกณฑ์ - same ปีงบประมาณ/จังหวัด scope as the map/metrics beside it -->
    <div class="hm-aw-kpis">
    <div class="kpi-summary-row"
        style="{{ ($awarenessCriteriaPending ?? false) ? 'filter: blur(2px); opacity: 0.55; pointer-events: none;' : '' }}">
        <div class="kpi-summary-card kpi-total">
            <div class="kpi-summary-icon"><i class="fas fa-users"></i></div>
            <div class="kpi-summary-body">
                <span class="kpi-summary-label">ผู้ตอบแบบประเมินทั้งหมด</span>
                <span class="kpi-summary-value">{{ number_format($awarenessOverallTotal) }}</span>
                <span class="kpi-summary-sub">คน · ปีงบฯ {{ $fiscalYearLabel }}</span>
            </div>
        </div>
        <div class="kpi-summary-card kpi-pass">
            <div class="kpi-summary-icon"><i class="fas fa-circle-check"></i></div>
            <div class="kpi-summary-body">
                <span class="kpi-summary-label">ผ่านเกณฑ์</span>
                <span class="kpi-summary-value">{{ number_format($awarenessOverallPass) }}</span>
                <span class="kpi-summary-sub">คน · {{ $awarenessOverallTotal > 0 ? round($awarenessOverallPass * 100 / $awarenessOverallTotal, 1) : 0 }}% ของทั้งหมด</span>
            </div>
        </div>
        <div class="kpi-summary-card kpi-fail">
            <div class="kpi-summary-icon"><i class="fas fa-circle-xmark"></i></div>
            <div class="kpi-summary-body">
                <span class="kpi-summary-label">ไม่ผ่านเกณฑ์</span>
                <span class="kpi-summary-value">{{ number_format($awarenessOverallFail) }}</span>
                <span class="kpi-summary-sub">คน · {{ $awarenessOverallTotal > 0 ? round($awarenessOverallFail * 100 / $awarenessOverallTotal, 1) : 0 }}% ของทั้งหมด</span>
            </div>
        </div>
        <div class="kpi-summary-card kpi-rate">
            <div class="kpi-summary-icon"><i class="fas fa-percent"></i></div>
            <div class="kpi-summary-body">
                <span class="kpi-summary-label">อัตราผ่านเกณฑ์</span>
                <span class="kpi-summary-value">{{ $awarenessOverallRate }}%</span>
                <span class="kpi-summary-sub">
                    @if ($awarenessMethod === 'mixed')
                        ตามเกณฑ์ของแต่ละปีงบฯ
                    @elseif ($awarenessMethod === \App\Models\AwarenessPassSetting::METHOD_SCORE)
                        เกณฑ์ผ่าน &ge; 19.2 / 32 คะแนน (ร้อยละ 60)
                    @else
                        ผ่านเกณฑ์ข้อ 1 และข้อ 2
                    @endif
                </span>
            </div>
        </div>
    </div>
    @if ($awarenessCriteriaPending ?? false)
        <div class="kpi-pending-note">
            <i class="fas fa-clock"></i>
            รอเกณฑ์การประเมินปี {{ $fiscalYearLabel }} - ตัวเลขด้านบนยังไม่ถูกคำนวณจนกว่าจะตั้งค่าเกณฑ์
        </div>
    @endif

    </div>

    <!-- Row 1: Map, Survey Participant, and Metrics List -->
    <div class="hm-aw-main">
    <div class="row home-row-top" style="display: flex; gap: 20px; flex-wrap: wrap; margin-bottom: 20px;">
        <!-- Column 1: Map Section -->
        <div style="flex: 4; min-width: 280px;">
            <div class="card dash-card accent-pink"
                style="padding: 20px; border-radius: 20px; border: 1px solid rgba(255, 255, 255, 0.4); box-shadow: 0 8px 32px rgba(0,0,0,0.03); backdrop-filter: blur(12px); -webkit-backdrop-filter: blur(12px); height: 100%; min-height: 360px;">
                <div class="dash-card-head">
                    <div class="dash-card-icon"><i class="fas fa-map-location-dot"></i></div>
                    <div>
                        <div class="dash-card-title">ร้อยละความตระหนักรู้</div>
                        <div class="dash-card-subtitle">ปี {{ $fiscalYearLabel }} &middot; รายจังหวัด</div>
                    </div>
                </div>
                <!-- Always render map for all years -->
                <div class="hm-awmap-wrap" style="position: relative; min-height: 290px;">
                    <div id="map-container"
                        style="height: 330px; width: 100%; {{ ($awarenessCriteriaPending ?? false) ? 'filter: blur(4px); opacity: 0.6;' : '' }}">
                    </div>
                    @if ($awarenessCriteriaPending ?? false)
                        <div
                            style="position: absolute; top: 0; left: 0; width: 100%; height: 100%; display: flex; align-items: center; justify-content: center; z-index: 10; border-radius: 12px;">
                            <div
                                style="background: rgba(255, 255, 255, 0.95); padding: 30px; border-radius: 12px; box-shadow: 0 10px 25px rgba(0,0,0,0.08); text-align: center; max-width: 450px; width: 90%; border: 1px solid rgba(226, 232, 240, 0.8);">
                                <div
                                    style="width: 48px; height: 48px; background-color: #f59e0b; border-radius: 50%; display: flex; align-items: center; justify-content: center; margin: 0 auto 12px auto;">
                                    <i class="fas fa-clock" style="font-size: 1.4rem; color: #ffffff;"></i>
                                </div>
                                <h4 style="color: #1e293b; font-weight: 800; margin-bottom: 8px; font-size: 1.2rem;">
                                    รอเกณฑ์การประเมินปี {{ $fiscalYearLabel }}</h4>
                                <p style="color: #64748b; font-size: 0.85rem; margin: 0; line-height: 1.5;">
                                    ข้อมูลของปีงบประมาณ {{ $fiscalYearLabel }} จะยังไม่ถูกคำนวณในส่วนนี้ จนกว่าจะมีการกำหนดเกณฑ์ที่ชัดเจนใน
                                    "ตั้งค่าเกณฑ์ความตระหนักรู้"</p>
                            </div>
                        </div>
                    @endif
                </div>
                @if ($awarenessMethod === \App\Models\AwarenessPassSetting::METHOD_SCORE)
                    <p style="font-size:11px;color:#64748b;margin:6px 0 0;text-align:center;">
                        เกณฑ์รายบุคคล: คะแนนรวม &ge; 19.2 / 32 (ร้อยละ 60)
                    </p>
                @endif
            </div>
        </div>

        <!-- Column 2: Survey Participant Donut Chart -->
        <div style="flex: 4; min-width: 280px;">
            <div class="card dash-card accent-purple"
                style="padding: 20px; border-radius: 20px; border: 1px solid rgba(255, 255, 255, 0.4); box-shadow: 0 8px 32px rgba(0,0,0,0.03); backdrop-filter: blur(12px); -webkit-backdrop-filter: blur(12px); height: 100%; display: flex; flex-direction: column; align-items: center; justify-content: flex-start;">
                <div class="dash-card-head" style="width: 100%;">
                    <div class="dash-card-icon"><i class="fas fa-chart-pie"></i></div>
                    <div>
                        <div class="dash-card-title">จำนวนผู้ตอบแบบประเมิน</div>
                        <div class="dash-card-subtitle">แยกตามจังหวัด</div>
                    </div>
                </div>
                <!-- Donut บน + Legend ล่าง -->
                <div style="display:flex; flex-direction:column; align-items:center; gap:12px; width:100%; margin-top:8px; flex:1; min-height:0;">
                    <!-- Donut Chart — TOP CENTER -->
                    <div style="flex-shrink:0; width:180px; height:180px; position:relative; filter:drop-shadow(0 8px 14px rgba(15,23,42,.10));">
                        <canvas id="participantDonutChart"></canvas>
                        <div style="position:absolute; top:50%; left:50%; transform:translate(-50%,-50%); text-align:center; pointer-events:none; width:100px;">
                            <div id="donutCap" style="font-size:0.65rem; color:#64748b; font-weight:600; margin-bottom:2px; white-space:nowrap; overflow:hidden; text-overflow:ellipsis; max-width:96px; margin-left:auto; margin-right:auto;">ทั้งหมด</div>
                            <div id="donutTotal" style="font-size:1.65rem; font-weight:800; color:#0f172a; line-height:1; letter-spacing:-.5px;">0</div>
                            <div id="donutSub" style="font-size:0.65rem; color:#64748b; margin-top:2px; font-weight:600;">รายการ</div>
                        </div>
                    </div>
                    <!-- Legend BOTTOM — 2-column grid -->
                    <div id="donutLegend" style="width:100%; display:grid; grid-template-columns:repeat(auto-fill, minmax(140px, 1fr)); gap:5px 8px; min-width:0;">
                        <!-- generated by JS -->
                    </div>
                </div>
            </div>
        </div>

        <!-- Column 3: Awareness Metrics Section -->
        <div style="flex: 4; min-width: 380px;">
            <div class="card dash-card accent-blue aw-beh-card"
                style="padding: 20px; border-radius: 20px; border: 1px solid rgba(255, 255, 255, 0.4); box-shadow: 0 8px 32px rgba(0,0,0,0.03); backdrop-filter: blur(12px); -webkit-backdrop-filter: blur(12px); height: 100%;">

                <!-- Section Header -->
                <div class="dash-card-head">
                    <div class="dash-card-icon"><i class="fas fa-utensils"></i></div>
                    <div>
                        <div class="dash-card-title">พฤติกรรมการบริโภคโซเดียม</div>
                        <div class="dash-card-subtitle">สัดส่วนพฤติกรรมที่พบ</div>
                    </div>
                </div>

                <!-- Shared Content Container (Prevents Jumping) -->
                <div id="awarenessContentBody" style="min-height: 210px; position: relative;">
                    <!-- View A: Metrics List -->
                    <div id="metricsListView" class="view-fade-in"
                        style="display: flex; flex-direction: column; gap: 10px; padding: 0 2px;">
                        <!-- ไม่เคยเลย (เติมน้ำปลา) -->
                        <div class="metric-row awareness-item" data-type="no_seasoning"
                            style="--mc: #10b981; --pct: {{ min(100, max(0, (float) $awarenessMetrics['no_seasoning']['percentage'])) }}%;"
                            onclick="updateInlineChart('no_seasoning', 'เติมน้ำปลา / เครื่องปรุงรสเพิ่ม', '#10b981')">
                            <div class="metric-content">
                                <div class="metric-label"
                                    style="margin-bottom: 0; display: flex; justify-content: space-between; align-items: center; width: 100%;">
                                    <span
                                        style="line-height: 1.4; color: #1e293b; font-weight: 700; font-size: 0.9rem; display: flex; align-items: center; gap: 10px;">
                                        <i class="fa-solid fa-bottle-droplet"
                                            style="color: #10b981; font-size: 1rem;"></i>
                                        เติมน้ำปลา / เครื่องปรุงรสเพิ่ม
                                        {{-- <i class="fa-solid fa-circle-info"
                                            style="cursor: help; color: #94a3b8; font-size: 0.75rem; margin-left: 2px;"
                                            title="อธิบายข้อมูล"
                                            onclick="event.stopPropagation(); Swal.fire({
                                                                title: 'เกณฑ์การนับข้อมูล',
                                                                text: 'ดึงมาเฉพาะกลุ่มผู้ที่ตอบว่า: ไม่เคยเลย',
                                                                icon: 'info',
                                                                confirmButtonColor: '#10b981'
                                                            })"></i> --}}
                                    </span>
                                    <div class="metric-pill" style="font-size: 0.75rem; padding: 4px 10px;">
                                        {{ (int) $awarenessMetrics['no_seasoning']['count'] }}
                                        ({{ $awarenessMetrics['no_seasoning']['percentage'] }}%)
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- ไม่เคย (อาหารแปรรูป) -->
                        <div class="metric-row awareness-item" data-type="no_instant_food"
                            style="--mc: #6366f1; --pct: {{ min(100, max(0, (float) $awarenessMetrics['no_instant_food']['percentage'])) }}%;"
                            onclick="updateInlineChart('no_instant_food', 'บริโภคอาหารแปรรูป', '#6366f1')">
                            <div class="metric-content">
                                <div class="metric-label"
                                    style="margin-bottom: 0; display: flex; justify-content: space-between; align-items: center; width: 100%;">
                                    <span
                                        style="color: #1e293b; font-weight: 700; font-size: 0.9rem; display: flex; align-items: center; gap: 10px;">
                                        <i class="fa-solid fa-box" style="color: #6366f1; font-size: 1rem;"></i>
                                        บริโภคอาหารแปรรูป
                                        {{-- <i class="fa-solid fa-circle-info"
                                            style="cursor: help; color: #94a3b8; font-size: 0.75rem; margin-left: 2px;"
                                            title="อธิบายข้อมูล"
                                            onclick="event.stopPropagation(); Swal.fire({
                                                                                                   title: 'เกณฑ์การนับข้อมูล',
                                                                                                   text: 'ดึงมาเฉพาะกลุ่มผู้ที่ตอบว่า: ไม่เคย',
                                                                                                   icon: 'info',
                                                                                                   confirmButtonColor: '#6366f1'
                                                                                               })"></i> --}}
                                    </span>
                                    <div class="metric-pill"
                                        style="background: #6366f1; font-size: 0.75rem; padding: 4px 10px;">
                                        {{ (int) $awarenessMetrics['no_instant_food']['count'] }}
                                        ({{ $awarenessMetrics['no_instant_food']['percentage'] }}%)
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- ไม่เคย (อาหารหมักดอง) -->
                        <div class="metric-row awareness-item" data-type="no_pickled_food"
                            style="--mc: #f59e0b; --pct: {{ min(100, max(0, (float) $awarenessMetrics['no_pickled_food']['percentage'])) }}%;"
                            onclick="updateInlineChart('no_pickled_food', 'บริโภคอาหารหมักดอง', '#f59e0b')">
                            <div class="metric-content">
                                <div class="metric-label"
                                    style="margin-bottom: 0; display: flex; justify-content: space-between; align-items: center; width: 100%;">
                                    <span
                                        style="color: #1e293b; font-weight: 700; font-size: 0.9rem; display: flex; align-items: center; gap: 10px;">
                                        <i class="fa-solid fa-flask" style="color: #f59e0b; font-size: 1rem;"></i>
                                        บริโภคอาหารหมักดอง
                                        {{-- <i class="fa-solid fa-circle-info"
                                            style="cursor: help; color: #94a3b8; font-size: 0.75rem; margin-left: 2px;"
                                            title="อธิบายข้อมูล"
                                            onclick="event.stopPropagation(); Swal.fire({
                                                                                                   title: 'เกณฑ์การนับข้อมูล',
                                                                                                   text: 'ดึงมาเฉพาะผู้ที่ตอบว่า: ไม่เคย (อาหารหมักดอง) และ ไม่เคยเลย (อาหารที่มีโซเดียมสูง)',
                                                                                                   icon: 'info',
                                                                                                   confirmButtonColor: '#f59e0b'
                                                                                               })"></i> --}}
                                    </span>
                                    <div class="metric-pill"
                                        style="background: #f59e0b; font-size: 0.75rem; padding: 4px 10px;">
                                        {{ (int) $awarenessMetrics['no_pickled_food']['count'] }}
                                        ({{ $awarenessMetrics['no_pickled_food']['percentage'] }}%)
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- ลดการจิ้ม -->
                        <div class="metric-row awareness-item" data-type="reduction_effort"
                            style="--mc: #10b981; --pct: {{ min(100, max(0, (float) $awarenessMetrics['reduction_effort']['percentage'])) }}%;"
                            onclick="updateInlineChart('reduction_effort', 'ลดการจิ้ม / ลดซด / ลดเครื่อง', '#10b981')">
                            <div class="metric-content">
                                <div class="metric-label"
                                    style="margin-bottom: 0; display: flex; justify-content: space-between; align-items: center; width: 100%;">
                                    <span
                                        style="color: #1e293b; font-weight: 700; font-size: 0.9rem; display: flex; align-items: center; gap: 10px;">
                                        <i class="fa-solid fa-bowl-food" style="color: #10b981; font-size: 1rem;"></i>
                                        ลดการจิ้ม / ลดซด / ลดเครื่อง
                                        {{-- <i class="fa-solid fa-circle-info"
                                            style="cursor: help; color: #94a3b8; font-size: 0.75rem; margin-left: 2px;"
                                            title="อธิบายข้อมูล"
                                            onclick="event.stopPropagation(); Swal.fire({
                                                                                                   title: 'เกณฑ์การนับข้อมูล',
                                                                                                   text: 'ดึงมาเฉพาะกลุ่มผู้ที่ตอบว่า: ทุกครั้ง (ความสำคัญ) และ สำคัญมาก (ความพยายาม)',
                                                                                                   icon: 'info',
                                                                                                   confirmButtonColor: '#10b981'
                                                                                               })"></i> --}}
                                    </span>
                                    <div class="metric-pill" style="font-size: 0.75rem; padding: 4px 10px;">
                                        {{ (int) $awarenessMetrics['reduction_effort']['count'] }}
                                        ({{ $awarenessMetrics['reduction_effort']['percentage'] }}%)
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- View B: Inline Chart Container -->
                    <div id="inlineChartContainer" class="view-fade-in"
                        style="display: none; background: #ffffff; border: 1px solid #e2e8f0; border-radius: 16px; padding: 15px; box-shadow: 0 4px 12px rgba(0,0,0,0.03);">
                        <div
                            style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 12px;">
                            <h6
                                style="margin: 0; font-size: 0.8rem; font-weight: 800; color: #64748b; display: flex; align-items: center; gap: 6px;">
                                <i class="fas fa-chart-simple"></i>
                                <span id="inlineChartTitle">รายละเอียดรายจังหวัด</span>
                            </h6>
                            <button onclick="hideInlineChart()"
                                style="border: none; background: #fee2e2; color: #ef4444; width: 24px; height: 24px; border-radius: 50%; display: flex; align-items: center; justify-content: center; cursor: pointer; transition: all 0.2s;"
                                onmouseover="this.style.background='#fecaca'; this.style.color='#dc2626';"
                                onmouseout="this.style.background='#fee2e2'; this.style.color='#ef4444';">
                                <i class="fas fa-times" style="font-size: 0.7rem;"></i>
                            </button>
                        </div>
                        <div class="aw-beh-chartbox" style="position: relative;">
                            <div style="position: absolute; top: 0; right: 0; bottom: 0; left: 0;">
                                <canvas id="behaviorInlineChart"></canvas>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
    </div>
    </div>

    </section>

    <!-- ===== 2. พชอ.ไต - ผลการดำเนินงานพื้นที่ ===== -->
    <section class="hm-sec hm-kidney" id="sec-kidney">
        <div class="hm-sec-head">
            <div class="hm-sec-ic"><i class="fas fa-location-dot"></i></div>
            <h3>พชอ.ไต - ผลการดำเนินงานพื้นที่</h3>
            <span class="hm-scope">{{ $hmScope }}</span>
            <a class="hm-more" href="{{ $hmLink('kidney-dhb-report') }}">ดูรายละเอียด</a>
        </div>
        <div class="hm-grid kd">
            <div class="hm-kcol">
                <div class="hm-kpi" style="--k: #0369a1;">
                    <div class="hm-kpi-ico"><i class="fas fa-location-dot"></i></div>
                    <div class="hm-kpi-body">
                        <span class="hm-kpi-l">ความครอบคลุมพื้นที่</span>
                        <span class="hm-kpi-v">{{ $hmKd['districts_covered'] }} / {{ $hmKd['districts_total'] }} <small>อำเภอ</small></span>
                        <span class="hm-kpi-d">ครอบคลุม {{ $hmKd['coverage_percent'] }}%</span>
                        <span class="hm-kpi-bar"><i style="width: {{ $hmKd['coverage_percent'] }}%;"></i></span>
                    </div>
                </div>
                <div class="hm-kpi" style="--k: #6366f1;">
                    <div class="hm-kpi-ico"><i class="fas fa-thumbtack"></i></div>
                    <div class="hm-kpi-body">
                        <span class="hm-kpi-l">พื้นที่ที่ดำเนินงาน</span>
                        <span class="hm-kpi-v">{{ number_format($hmKd['answered_pins']) }} / {{ number_format($hmKd['pin_total']) }} <small>หน่วยงาน</small></span>
                        <span class="hm-kpi-d">ครบทั้ง 8 ข้อ {{ number_format($hmKd['complete_pins']) }} หน่วยงาน</span>
                        <span class="hm-kpi-bar"><i style="width: {{ $hmKd['pin_total'] > 0 ? round($hmKd['answered_pins'] / $hmKd['pin_total'] * 100) : 0 }}%;"></i></span>
                    </div>
                </div>
                <div class="hm-kpi" style="--k: #10b981;">
                    <div class="hm-kpi-ico"><i class="fas fa-list-check"></i></div>
                    <div class="hm-kpi-body">
                        <span class="hm-kpi-l">ตัวชี้วัดที่มีหน่วยงานทำแล้ว</span>
                        <span class="hm-kpi-v">{{ 8 - $hmKd['items_pending']->count() }} / 8 <small>ข้อ</small></span>
                        <span class="hm-kpi-d">ยังไม่มีหน่วยงานทำ {{ $hmKd['items_pending']->count() }} ข้อ</span>
                        <span class="hm-kpi-bar"><i style="width: {{ round((8 - $hmKd['items_pending']->count()) / 8 * 100) }}%;"></i></span>
                    </div>
                </div>
            </div>

            <div class="hm-card hm-kd-cmp">
                <h4>เปรียบเทียบผลดำเนินงานรายจังหวัด</h4>
                <div class="hm-cap">พื้นที่ดำเนินงาน = พื้นที่ที่ตอบ ÷ พื้นที่ที่ปักหน่วยงาน (อำเภอ หรือระดับจังหวัด) · ข้อมูลครบ = ทุกหน่วยงานทำครบ 8 ข้อ</div>
                <table class="hm-t">
                    <thead><tr><th>จังหวัด</th><th>พื้นที่ดำเนินงาน</th><th>ข้อมูลครบ</th></tr></thead>
                    <tbody>
                        @foreach ($hmKd['provinces'] as $row)
                            <tr>
                                <td style="white-space: nowrap;"><i class="hm-dot" style="background: {{ $hmProvColor[$row['province']] ?? '#64748b' }};"></i>{{ $row['province'] }}</td>
                                <td>
                                    @if ($row['percent'] !== null)
                                        <div class="hm-bar" title="{{ $row['answered'] }} จาก {{ $row['total'] }} พื้นที่ (อำเภอ/ระดับจังหวัด)">
                                            <div class="hm-bar-tr"><div class="hm-bar-fl" style="width: {{ $row['percent'] }}%;"></div></div>
                                            <span class="hm-bar-pc">{{ $row['percent'] }}%</span>
                                        </div>
                                    @else
                                        <span class="hm-muted">— ยังไม่ปักหน่วยงาน —</span>
                                    @endif
                                </td>
                                <td>
                                    @if ($row['data_status'] === 'complete')
                                        <span class="hm-chip ok"><i class="fas fa-circle-check"></i> ครบ</span>
                                    @elseif ($row['data_status'] === 'partial')
                                        <span class="hm-chip warn"><i class="far fa-clock"></i> บางส่วน</span>
                                    @else
                                        <span class="hm-chip none"><i class="fas fa-circle-minus"></i> ยังไม่รายงาน</span>
                                    @endif
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>

            <div class="hm-card hm-kd-map">
                <div class="hm-mp-head">
                    <div class="hm-mp-ico"><i class="fas fa-map-location-dot"></i></div>
                    <div>
                        <div class="hm-mp-t">จำนวนพื้นที่ดำเนินงาน พชอ.ไต</div>
                        <div class="hm-mp-s">แยกรายจังหวัด · ชี้หรือแตะจังหวัดเพื่อดูรายละเอียด</div>
                    </div>
                </div>
                <div id="hmMapChoro"></div>
                <div id="hmMapInfo" class="hm-mi" aria-live="polite"></div>
            </div>

            <div class="hm-stack">
                <div class="hm-card">
                    <h4>ตัวชี้วัดที่ดำเนินงานได้ดี</h4>
                    <div class="hm-cap">3 อันดับแรก · จำนวนหน่วยงานที่ทำ</div>
                    @forelse ($hmKd['items_top'] as $ind)
                        <div class="hm-ind" title="ข้อ {{ $ind['key'] }} {{ $hmItemNames[$ind['key']] }} - {{ $ind['done'] }} จาก {{ $ind['total'] }} หน่วยงาน">
                            <span class="hm-ind-b" style="background: #2f855a;">{{ $ind['key'] }}</span>
                            <span>{{ $hmItemNames[$ind['key']] }}</span>
                            <span class="hm-ind-c">{{ $ind['done'] }}/{{ $ind['total'] }}</span>
                        </div>
                    @empty
                        <div class="hm-muted" style="font-size: .8rem;">ยังไม่มีตัวชี้วัดที่หน่วยงานใดทำ</div>
                    @endforelse
                </div>
                <div class="hm-card">
                    <h4 style="display: flex; justify-content: space-between; gap: 8px;">ตัวชี้วัดที่ต้องเร่งดำเนินการ
                        <span class="hm-chip {{ $hmKd['items_pending']->count() > 0 ? 'warn' : 'ok' }}">{{ $hmKd['items_pending']->count() }} / 8 ข้อ</span>
                    </h4>
                    <div class="hm-cap">ยังไม่มีหน่วยงานใดทำ</div>
                    @forelse ($hmKd['items_pending']->take(3) as $ind)
                        <div class="hm-ind">
                            <span class="hm-ind-b" style="background: #c2410c;">{{ $ind['key'] }}</span>
                            <span>{{ $ind['key'] === '8' ? 'การประเมินผลลัพธ์การดำเนินงาน' : $hmItemNames[$ind['key']] }}</span>
                            <span></span>
                        </div>
                    @empty
                        <div class="hm-muted" style="font-size: .8rem;">ทุกข้อมีหน่วยงานทำแล้ว</div>
                    @endforelse
                    @if ($hmKd['items_pending']->count() > 3)
                        <div class="hm-muted" style="font-size: .74rem; padding-left: 30px;"
                            title="{{ $hmKd['items_pending']->skip(3)->map(fn($i) => 'ข้อ ' . $i['key'] . ' ' . ($i['key'] === '8' ? 'การประเมินผลลัพธ์การดำเนินงาน (8.1 / 8.2 / 8.3)' : $hmItemNames[$i['key']]))->implode("\n") }}">และอีก {{ $hmKd['items_pending']->count() - 3 }} ข้อ</div>
                    @endif
                </div>
            </div>
        </div>
    </section>

    <!-- ===== 3. เมนูลดโซเดียม ===== -->
    <section class="hm-sec hm-menu" id="sec-menu">
        <div class="hm-sec-head">
            <div class="hm-sec-ic"><i class="fas fa-bowl-food"></i></div>
            <h3>เมนูลดโซเดียม</h3>
            <span class="hm-scope">{{ $hmScope }}</span>
            <a class="hm-more" href="{{ $hmLink('reduced-sodium-menu') }}">ดูรายละเอียด</a>
        </div>
        @php
            $hmMnNoReport = collect($provinceOverviewRows)->filter(fn($r) => $r['menu_before'] === 0)->pluck('province');
            $hmMnRows = collect($provinceOverviewRows);
        @endphp
        <div class="hm-grid mn">
            <div class="hm-kcol">
                <div class="hm-kpi" style="--k: #6366f1;">
                    <div class="hm-kpi-ico"><i class="fas fa-bowl-food"></i></div>
                    <div class="hm-kpi-body">
                        <span class="hm-kpi-l">เมนูลดโซเดียมทั้งหมด</span>
                        <span class="hm-kpi-v">{{ number_format($hmMn['total']) }} <small>เมนู</small></span>
                        <span class="hm-kpi-d">วัดก่อน-หลังแล้ว {{ number_format($hmMn['measured']) }} เมนู</span>
                    </div>
                </div>
                <div class="hm-kpi" style="--k: #d97706;">
                    @php $hmPlace = $hmMn['places']->first(); @endphp
                    <div class="hm-kpi-ico"><i class="fas fa-store"></i></div>
                    <div class="hm-kpi-body">
                        <span class="hm-kpi-l">สถานที่จำหน่ายยอดนิยม</span>
                        <span class="hm-kpi-v hm-kpi-v-text">{{ $hmPlace['name'] ?? '—' }}</span>
                        <span class="hm-kpi-d">{{ $hmPlace ? number_format($hmPlace['count']) . ' เมนู' : 'ยังไม่มีข้อมูล' }}</span>
                    </div>
                </div>
                <div class="hm-kpi" style="--k: #10b981;">
                    <div class="hm-kpi-ico"><i class="fas fa-circle-check"></i></div>
                    <div class="hm-kpi-body">
                        <span class="hm-kpi-l">ผ่านเกณฑ์ระดับดีมาก</span>
                        <span class="hm-kpi-v">{{ $hmMn['best_percent'] !== null ? $hmMn['best_percent'] . '%' : '—' }}</span>
                        <span class="hm-kpi-d">โซเดียม &lt; 600 มก. · จากเมนูที่วัดแล้ว {{ number_format($hmMn['measured']) }} เมนู</span>
                    </div>
                </div>
            </div>

            <div class="hm-card hm-fly-card">
                <h4>ค่าเฉลี่ยโซเดียมก่อน-หลังปรับสูตร รายจังหวัด</h4>
                <div class="hm-cap">มก. ต่อเมนู เฉพาะเมนูที่วัดครบทั้งก่อนและหลัง · ตรงกลางคือจำนวนเมนูทั้งหมด</div>
                @php
                    $flyRows = collect($hmMn['provinces']);
                    $flyMax = max(1, (float) $flyRows->max(fn($r) => max($r['avg_before'] ?? 0, $r['avg_after'] ?? 0)), (float) ($hmMn['avg_before'] ?? 0), (float) ($hmMn['avg_after'] ?? 0));
                    $flyAllHas = ($hmMn['measured'] ?? 0) > 0;
                @endphp
                <div class="hm-fly-legend">
                    <span><i style="background: linear-gradient(180deg, #fc8296, #e11d48);"></i>ก่อนปรับสูตร</span>
                    <span><i style="background: linear-gradient(180deg, #6ee7ac, #15803d);"></i>หลังปรับสูตร</span>
                </div>
                <div class="hm-fly">
                    @foreach ($flyRows as $row)
                        @php $has = $row['avg_before'] !== null; @endphp
                        <div class="hm-fly-row">
                            <div class="hm-fly-prov"><i class="hm-dot" style="background: {{ $hmProvColor[$row['province']] ?? '#64748b' }};"></i>{{ $row['province'] }}</div>
                            <div class="hm-fly-side hm-fly-left" @if ($has) title="ก่อนปรับสูตร เฉลี่ย {{ number_format($row['avg_before'], 1) }} มก." @endif>
                                @if ($has)
                                    <span class="hm-fly-val">{{ number_format($row['avg_before']) }}</span>
                                    <span class="hm-fly-bar hm-fly-before" style="width: {{ max(4, round($row['avg_before'] / $flyMax * 100, 1)) }}%;"></span>
                                @endif
                            </div>
                            <div class="hm-fly-core {{ $row['total'] > 0 ? '' : 'is-empty' }}" title="เมนูทั้งหมด {{ number_format($row['total']) }} เมนู (วัดผลแล้ว {{ number_format($row['measured']) }})">
                                <b>{{ number_format($row['total']) }}</b><span>เมนู</span>
                            </div>
                            <div class="hm-fly-side hm-fly-right" @if ($has) title="หลังปรับสูตร เฉลี่ย {{ number_format($row['avg_after'], 1) }} มก." @endif>
                                @if ($has)
                                    <span class="hm-fly-bar hm-fly-after" style="width: {{ max(4, round($row['avg_after'] / $flyMax * 100, 1)) }}%;"></span>
                                    <span class="hm-fly-val">{{ number_format($row['avg_after']) }}</span>
                                @else
                                    <span class="hm-muted hm-fly-none">{{ $row['total'] > 0 ? 'ยังไม่มีเมนูที่วัดครบก่อน-หลัง' : 'ยังไม่รายงาน' }}</span>
                                @endif
                            </div>
                            <div class="hm-fly-pct">
                                @if ($row['reduction_percent'] !== null)
                                    <span class="hm-chip {{ $row['reduction_percent'] > 0 ? 'ok' : 'warn' }}">{{ $row['reduction_percent'] > 0 ? '▼' : '▲' }} {{ abs($row['reduction_percent']) }}%</span>
                                @else
                                    <span class="hm-muted">—</span>
                                @endif
                            </div>
                        </div>
                    @endforeach

                    <div class="hm-fly-row hm-fly-total">
                        <div class="hm-fly-prov"><i class="hm-dot" style="background: #0f766e;"></i>ภาพรวมทั้งเขต</div>
                        <div class="hm-fly-side hm-fly-left" @if ($flyAllHas) title="ก่อนปรับสูตร เฉลี่ยทั้งเขต {{ number_format($hmMn['avg_before'], 1) }} มก." @endif>
                            @if ($flyAllHas)
                                <span class="hm-fly-val">{{ number_format($hmMn['avg_before']) }}</span>
                                <span class="hm-fly-bar hm-fly-before" style="width: {{ max(4, round($hmMn['avg_before'] / $flyMax * 100, 1)) }}%;"></span>
                            @endif
                        </div>
                        <div class="hm-fly-core {{ $hmMn['total'] > 0 ? '' : 'is-empty' }}" title="เมนูทั้งหมดในเขต {{ number_format($hmMn['total']) }} เมนู (วัดผลแล้ว {{ number_format($hmMn['measured']) }})">
                            <b>{{ number_format($hmMn['total']) }}</b><span>เมนู</span>
                        </div>
                        <div class="hm-fly-side hm-fly-right" @if ($flyAllHas) title="หลังปรับสูตร เฉลี่ยทั้งเขต {{ number_format($hmMn['avg_after'], 1) }} มก." @endif>
                            @if ($flyAllHas)
                                <span class="hm-fly-bar hm-fly-after" style="width: {{ max(4, round($hmMn['avg_after'] / $flyMax * 100, 1)) }}%;"></span>
                                <span class="hm-fly-val">{{ number_format($hmMn['avg_after']) }}</span>
                            @else
                                <span class="hm-muted hm-fly-none">{{ $hmMn['total'] > 0 ? 'ยังไม่มีเมนูที่วัดครบก่อน-หลัง' : 'ยังไม่รายงาน' }}</span>
                            @endif
                        </div>
                        <div class="hm-fly-pct">
                            @if ($flyAllHas && $hmMn['reduction_percent'] !== null)
                                <span class="hm-chip {{ $hmMn['reduction_percent'] > 0 ? 'ok' : 'warn' }}">{{ $hmMn['reduction_percent'] > 0 ? '▼' : '▲' }} {{ abs($hmMn['reduction_percent']) }}%</span>
                            @else
                                <span class="hm-muted">—</span>
                            @endif
                        </div>
                    </div>
                </div>
            </div>

            <div class="hm-card hm-tier-card">
                <h4>ระดับการประเมินโซเดียมของเมนู</h4>
                <div class="hm-cap">ตัดสินจากโซเดียมหลังปรับสูตร · เมนูที่วัดก่อน-หลังแล้ว {{ number_format($hmMn['measured']) }} เมนู</div>
                @if ($hmMn['measured'] > 0)
                    @php
                        $tierMeta = [
                            'ดีมาก' => ['c' => '#16a34a', 'r' => '< 600'],
                            'ดี' => ['c' => '#84cc16', 'r' => '600-800'],
                            'ปานกลาง' => ['c' => '#f59e0b', 'r' => '801-1,000'],
                            'ควรปรับปรุง' => ['c' => '#dc2626', 'r' => '> 1,000'],
                        ];
                        // วงแหวน SVG: แต่ละระดับเป็นส่วนโค้งเว้นช่องไฟ (ไม่มีส่วนที่ 0 รายการ)
                        $ringR = 40; $ringW = 15; $ringC = round(2 * M_PI * $ringR, 3); $ringGap = 2.2;
                        $ringSegs = []; $acc = 0;
                        $ringNonZero = collect($hmMn['tiers'])->filter(fn($c) => $c > 0)->count();
                        foreach ($hmMn['tiers'] as $tier => $cnt) {
                            if ($cnt <= 0) { continue; }
                            $len = $cnt / $hmMn['measured'] * $ringC;
                            $gap = $ringNonZero > 1 ? $ringGap : 0;
                            $ringSegs[] = [
                                'c' => $tierMeta[$tier]['c'], 't' => $tier, 'n' => $cnt,
                                'dash' => round(max(0.3, $len - $gap), 3),
                                'off' => round(-($acc / $hmMn['measured'] * $ringC + $gap / 2), 3),
                            ];
                            $acc += $cnt;
                        }
                        $beforeAvg = $hmMn['avg_before']; $afterAvg = $hmMn['avg_after'];
                        $cmpMax = max(1, $beforeAvg, $afterAvg);
                    @endphp
                    <div class="hm-tier-body">
                        <div class="hm-tier-ring" role="img"
                            aria-label="สัดส่วนระดับการประเมิน: {{ collect($hmMn['tiers'])->map(fn($c, $t) => $t . ' ' . $c)->implode(', ') }}">
                            <svg viewBox="0 0 100 100" aria-hidden="true">
                                @foreach ($ringSegs as $sg)
                                    <circle cx="50" cy="50" r="{{ $ringR }}" fill="none" stroke="{{ $sg['c'] }}" stroke-width="{{ $ringW }}"
                                        stroke-dasharray="{{ $sg['dash'] }} {{ $ringC }}" stroke-dashoffset="{{ $sg['off'] }}"><title>{{ $sg['t'] }} {{ number_format($sg['n']) }} เมนู</title></circle>
                                @endforeach
                            </svg>
                            <div class="hm-tier-center">
                                <b>{{ number_format($hmMn['measured']) }}</b>
                                <span>เมนูที่วัดแล้ว</span>
                            </div>
                        </div>
                        <div class="hm-tier-list">
                            @foreach ($hmMn['tiers'] as $tier => $cnt)
                                @php $pc = round($cnt / $hmMn['measured'] * 100, 1); @endphp
                                <div class="hm-tier-item {{ $cnt === 0 ? 'is-zero' : '' }}" style="--tp-c: {{ $tierMeta[$tier]['c'] }};" title="{{ $tier }} ({{ $tierMeta[$tier]['r'] }} มก.) {{ number_format($cnt) }} เมนู">
                                    <span class="hm-tier-dot"></span>
                                    <span class="hm-tier-name">{{ $tier }}<small>{{ $tierMeta[$tier]['r'] }} มก.</small></span>
                                    <span class="hm-tier-val"><b>{{ number_format($pc, 1) }}%</b><small>{{ number_format($cnt) }} เมนู</small></span>
                                </div>
                            @endforeach
                        </div>
                    </div>
                    <div class="hm-tier-cmp">
                        <div class="hm-tier-cmp-head">
                            <span>โซเดียมเฉลี่ยต่อเมนู (มก.)</span>
                            @if ($hmMn['reduction_percent'] !== null)
                                <span class="hm-chip {{ $hmMn['reduction_percent'] > 0 ? 'ok' : 'warn' }}">{{ $hmMn['reduction_percent'] > 0 ? '▼' : '▲' }} ลดลง {{ abs($hmMn['reduction_percent']) }}%</span>
                            @endif
                        </div>
                        <div class="hm-tier-cmp-row"><span>ก่อนปรับ</span><span class="hm-tier-cmp-trk"><span class="hm-fly-before" style="width: {{ round($beforeAvg / $cmpMax * 100, 1) }}%;"></span></span><b>{{ number_format($beforeAvg, 1) }}</b></div>
                        <div class="hm-tier-cmp-row"><span>หลังปรับ</span><span class="hm-tier-cmp-trk"><span class="hm-fly-after" style="width: {{ round($afterAvg / $cmpMax * 100, 1) }}%;"></span></span><b>{{ number_format($afterAvg, 1) }}</b></div>
                    </div>
                @else
                    <div class="hm-tier-empty">
                        <i class="fas fa-vial"></i>
                        <b>ยังไม่มีเมนูที่วัดครบก่อน-หลังปรับสูตร</b>
                        <span>เมื่อมีผลวัดโซเดียมทั้งสองครั้ง ระดับการประเมินจะแสดงที่นี่</span>
                    </div>
                @endif
            </div>
        </div>
    </section>

    <!-- ===== 4. อัตราป่วยรายใหม่ความดันโลหิตสูง (HT) ===== -->
    <section class="hm-sec hm-ht" id="sec-ht">
        @php
            $htScope = $hmHt['year_is_fallback']
                ? 'ปี ' . $hmHt['year'] . ' (ล่าสุด)'
                : 'ปี ' . $fiscalYearLabel;
            $htScope .= $hmProvince !== 'all' ? ' · ' . $hmProvince : '';
            $htRows = collect($hmHt['provinces'])->sortBy(fn($r) => $r['rate'] === null ? PHP_INT_MAX : -$r['rate'])->values();
            $htMaxRate = collect($hmHt['provinces'])->max('rate') ?: 1;
            // สูงสุด/ต่ำสุด only among provinces that have a real rate (a province with no target is a data gap, not a "lowest")
            $htRanked = $htRows->filter(fn($r) => $r['rate'] !== null)->values();
            $htHiName = $htRanked->count() > 1 ? $htRanked->first()['province'] : null;
            $htLoName = $htRanked->count() > 1 ? $htRanked->last()['province'] : null;
            $htRatedTargets = collect($hmHt['provinces'])->filter(fn($r) => $r['rate'] !== null)->pluck('target');
            $htTop = $hmHt['top_province'];
            $htTopSmall = $htTop && $htRatedTargets->count() > 1
                && $htTop['target'] < 0.25 * ($htRatedTargets->sum() / $htRatedTargets->count());
            $htYearLink = route('new-ht-cases', array_merge(
                $hmHt['year'] !== '' ? ['year' => $hmHt['year']] : [],
                $hmProvQ
            ));
        @endphp
        <div class="hm-sec-head">
            <div class="hm-sec-ic"><i class="fas fa-heart-pulse"></i></div>
            <h3>อัตราป่วยรายใหม่ความดันโลหิตสูง (HT)</h3>
            <span class="hm-scope">{{ $htScope }}</span>
            @php
                $htUpdText = null;
                if (!empty($hmHt['updated_at'])) {
                    $htUpd = \Carbon\Carbon::parse($hmHt['updated_at'])->timezone('Asia/Bangkok');
                    $thMon = ['ม.ค.', 'ก.พ.', 'มี.ค.', 'เม.ย.', 'พ.ค.', 'มิ.ย.', 'ก.ค.', 'ส.ค.', 'ก.ย.', 'ต.ค.', 'พ.ย.', 'ธ.ค.'];
                    $htUpdText = $htUpd->day . ' ' . $thMon[$htUpd->month - 1] . ' ' . ($htUpd->year + 543) . ' เวลา ' . $htUpd->format('H:i') . ' น.';
                }
            @endphp
            @if ($htUpdText)
                <span class="hm-updated" title="วันที่อัปโหลด/แก้ไขไฟล์ข้อมูล HT ล่าสุด">
                    <i class="fas fa-clock-rotate-left"></i>
                    <span class="hm-updated-l">ข้อมูลอัปเดตล่าสุด</span>
                    <b>{{ $htUpdText }}</b>
                </span>
            @endif
            @php $htTrend = $hmHt['trend_pop'] ?? null; @endphp
            <a class="hm-more" href="{{ $htYearLink }}">ดูรายละเอียด</a>
        </div>
        <div class="hm-grid ht{{ $htTrend ? ' has-trend' : '' }}">
            <div class="hm-kcol">
                <div class="hm-kpi" style="--k: #6366f1;">
                    <div class="hm-kpi-ico"><i class="fas fa-users"></i></div>
                    <div class="hm-kpi-body">
                        <span class="hm-kpi-l">ผู้ป่วยรายใหม่สะสม</span>
                        <span class="hm-kpi-v">{{ number_format($hmHtMonthTotal) }} <small>คน</small></span>
                        <span class="hm-kpi-d">ปีงบประมาณ {{ $hmHt['year'] }}</span>
                    </div>
                </div>
                <div class="hm-kpi" style="--k: #10b981;">
                    <div class="hm-kpi-ico"><i class="fas fa-flag"></i></div>
                    <div class="hm-kpi-body">
                        <span class="hm-kpi-l">จังหวัดอัตราป่วยสูงสุด</span>
                        <span class="hm-kpi-v hm-kpi-v-text">{{ $htTop['province'] ?? '—' }}</span>
                        <span class="hm-kpi-d">
                            @if ($htTop)
                                {{ number_format($htTop['rate'], 2) }} ต่อแสนประชากร
                                @if ($htTopSmall)<i class="hm-flag" title="เป้าหมายประชากร {{ number_format($htTop['target']) }} คน น้อยกว่าจังหวัดอื่นมาก เทียบกันตรงๆ ไม่ได้">!</i>@endif
                            @else
                                ยังไม่มีข้อมูล
                            @endif
                        </span>
                    </div>
                </div>
                <div class="hm-kpi" style="--k: #3b82f6;">
                    <div class="hm-kpi-ico"><i class="fas fa-calendar-days"></i></div>
                    <div class="hm-kpi-body">
                        <span class="hm-kpi-l">ผู้ป่วยใหม่เดือนล่าสุด{{ $hmHtLatest ? ' (' . $hmHtLatest['label'] . ')' : '' }}</span>
                        <span class="hm-kpi-v">{{ $hmHtLatest ? number_format($hmHtLatest['value']) : '—' }} <small>คน</small></span>
                        @if ($hmHt['month_change'] !== null && $hmHtLatest && $hmHt['prev_month'])
                            @php $htDelta = $hmHtLatest['value'] - $hmHt['prev_month']['value']; @endphp
                            <span class="hm-kpi-d" style="color: {{ $htDelta > 0 ? '#b91c1c' : '#15803d' }};">
                                {{ $htDelta > 0 ? '▲' : '▼' }} {{ number_format(abs($htDelta)) }} คน ({{ abs($hmHt['month_change']) }}%) จากเดือนก่อน
                            </span>
                        @else
                            <span class="hm-kpi-d">ยังไม่มีข้อมูลเดือนก่อนเทียบ</span>
                        @endif
                    </div>
                </div>
            </div>

            @php
                // แบ่งสีแท่งอัตราป่วยด้วยค่าเฉลี่ย ± SD ของทุกจังหวัดที่มีเป้าหมายประชากร (SD แบบตัวอย่าง n-1 เหมือน STDEV ใน Excel)
                // อัตราปัดเป็น 2 ตำแหน่งให้ตรงกับตัวเลขที่แสดง - ต้องมีอย่างน้อย 2 จังหวัดและ SD > 0 จึงจะแบ่งสีได้
                $htBandRates = $htRows->filter(fn($r) => $r['rate'] !== null)->map(fn($r) => round($r['rate'], 2))->values();
                $htBandN = $htBandRates->count();
                $htBandMean = $htBandN > 0 ? $htBandRates->avg() : 0;
                $htBandSd = $htBandN > 1 ? sqrt($htBandRates->sum(fn($r) => ($r - $htBandMean) ** 2) / ($htBandN - 1)) : 0;
                $htBandOk = $htBandN > 1 && $htBandSd > 0;
                $htBandHigh = round($htBandMean + $htBandSd, 2);
                $htBandLow = round($htBandMean - $htBandSd, 2);
                $htBandOf = function ($rate) use ($htBandOk, $htBandHigh, $htBandLow) {
                    if (!$htBandOk || $rate === null) { return null; }
                    $r = round($rate, 2);
                    return $r >= $htBandHigh ? 'red' : ($r <= $htBandLow ? 'green' : 'yellow');
                };
                $htBandCnt = ['red' => 0, 'yellow' => 0, 'green' => 0];
                foreach ($htRows as $htR) { $b = $htBandOf($htR['rate']); if ($b) { $htBandCnt[$b]++; } }
            @endphp
            <div class="hm-card hm-ht-sum">
                <h4>สรุปอัตราป่วยต่อแสนประชากร (รายจังหวัด)</h4>
                <div class="hm-cap">เรียงจากอัตราสูงสุด · จังหวัดที่ไม่มีเป้าหมายประชากรแสดงเป็นจำนวนผู้ป่วยอย่างเดียว</div>
                <table class="hm-t">
                    <thead><tr><th>จังหวัด</th><th class="num">ผู้ป่วยใหม่</th><th class="num">เป้าหมาย</th><th>อัตรา / แสนคน</th></tr></thead>
                    <tbody>
                        @foreach ($htRows as $row)
                            <tr>
                                <td style="white-space: nowrap;"><i class="hm-dot" style="background: {{ $hmProvColor[$row['province']] ?? '#64748b' }};"></i>{{ $row['province'] }}@if ($row['province'] === $htHiName)<span class="hm-hl hi">สูงสุด</span>@elseif ($row['province'] === $htLoName)<span class="hm-hl lo">ต่ำสุด</span>@endif</td>
                                <td class="num">{{ number_format($row['cases']) }}</td>
                                <td class="num {{ $row['target'] > 0 ? '' : 'hm-muted' }}">{{ $row['target'] > 0 ? number_format($row['target']) : '—' }}</td>
                                <td>
                                    @if ($row['rate'] !== null)
                                        <div class="hm-bar">
                                            <div class="hm-bar-tr"><div class="hm-bar-fl @if ($htBandOf($row['rate'])) hm-band-{{ $htBandOf($row['rate']) }} @endif" style="width: {{ round($row['rate'] / $htMaxRate * 100, 1) }}%;"></div></div>
                                            <span class="hm-bar-pc" style="min-width: 64px;">{{ number_format($row['rate'], 2) }}</span>
                                        </div>
                                    @else
                                        <span class="hm-muted">ไม่มีเป้าหมายประชากร</span>
                                    @endif
                                </td>
                            </tr>
                        @endforeach
                        @if ($htRows->count() > 1)
                            <tr class="hm-total">
                                <td><b>รวม{{ $hmProvince === 'all' ? 'เขต 10' : '' }}</b></td>
                                <td class="num"><b>{{ number_format($hmHt['total_cases']) }}</b></td>
                                <td class="num"><b>{{ $hmHt['total_target'] > 0 ? number_format($hmHt['total_target']) : '—' }}</b></td>
                                <td>
                                    @if ($hmHt['rate'] !== null)
                                        {{-- แถบความก้าวหน้าของอัตรารวม: สเกลเดียวกับแถบรายจังหวัด (เทียบกับจังหวัดสูงสุด) สีเข้มเพื่อแยกว่าเป็นค่ารวม --}}
                                        <div class="hm-bar">
                                            <div class="hm-bar-tr"><div class="hm-bar-fl" style="width: {{ min(100, round($hmHt['rate'] / $htMaxRate * 100, 1)) }}%; background: linear-gradient(90deg, #60a5fa, #2563eb);"></div></div>
                                            <span class="hm-bar-pc" style="min-width: 64px;"><b>{{ number_format($hmHt['rate'], 2) }}</b>
                                            @if ($hmHt['unrated_provinces']->count() > 0)
                                                <i class="hm-flag" title="อัตรารวมคิดจากจังหวัดที่มีเป้าหมายประชากรเท่านั้น (ไม่รวม {{ $hmHt['unrated_provinces']->implode(', ') }})">!</i>
                                            @endif
                                            </span>
                                        </div>
                                    @else
                                        <b>—</b>
                                    @endif
                                </td>
                            </tr>
                        @endif
                    </tbody>
                </table>
                @if ($htBandOk)
                    <div class="hm-bandleg" role="group" aria-label="ความหมายของสีแท่งอัตราป่วย">
                        <div class="hm-bandleg-h">ความหมายของสีแท่ง <span>ค่าเฉลี่ย {{ number_format($htBandMean, 2) }} ± SD {{ number_format($htBandSd, 2) }} ต่อแสนประชากร ({{ $htBandN }} จังหวัด)</span></div>
                        <div class="hm-bandleg-g">
                            <div class="hm-bandleg-i red"><i></i><div><b>แดง · อัตราสูง</b><small>≥ {{ number_format($htBandHigh, 2) }}</small><em>สูงกว่าค่าเฉลี่ยเกิน 1 SD</em></div><u>{{ $htBandCnt['red'] }} จว.</u></div>
                            <div class="hm-bandleg-i yellow"><i></i><div><b>เหลือง · ปานกลาง</b><small>{{ number_format($htBandLow + 0.01, 2) }} – {{ number_format($htBandHigh - 0.01, 2) }}</small><em>อยู่ในช่วงระหว่างเกณฑ์ (ค่าเฉลี่ย ± 1 SD)</em></div><u>{{ $htBandCnt['yellow'] }} จว.</u></div>
                            <div class="hm-bandleg-i green"><i></i><div><b>เขียว · อัตราต่ำ</b><small>≤ {{ number_format($htBandLow, 2) }}</small><em>ต่ำกว่าค่าเฉลี่ยเกิน 1 SD</em></div><u>{{ $htBandCnt['green'] }} จว.</u></div>
                        </div>
                    </div>
                @endif
            </div>

            @if ($htTrend)
                @include('pages.partials.ht-trend-popup', ['htTrend' => $htTrend, 'htTrendColors' => $hmProvColor, 'htTrendScope' => $hmProvince !== 'all' ? $hmProvince : 'เขต 10 (5 จังหวัด)', 'htTrendInline' => true, 'htTrendProvince' => $hmProvince])
            @else
            <div class="hm-card">
                @php
                    $htYt = collect($hmHt['year_trend'])->filter(fn($r) => $r['rate'] !== null)->values();
                @endphp
                @if ($htYt->count() >= 2)
                    @php
                        $vals = $htYt->pluck('rate');
                        $vMin = floor($vals->min() * 0.95 / 100) * 100;
                        $vMax = ceil($vals->max() * 1.05 / 100) * 100;
                        if ($vMax <= $vMin) { $vMax = $vMin + 100; }
                        $n = $htYt->count();
                        $px = fn($i) => $n === 1 ? 198 : 70 + $i * (256 / ($n - 1));
                        $py = fn($v) => 170 - ($v - $vMin) / ($vMax - $vMin) * 150;
                        $path = $htYt->map(fn($r, $i) => ($i === 0 ? 'M' : 'L') . round($px($i), 1) . ' ' . round($py($r['rate']), 1))->implode(' ');
                        $area = $path . ' L' . round($px($n - 1), 1) . ' 170 L' . round($px(0), 1) . ' 170 Z';
                        $ticks = [0, 1, 2, 3];
                    @endphp
                    <h4>แนวโน้มอัตราป่วยรายใหม่ HT</h4>
                    <div class="hm-cap">ต่อแสนประชากร · เฉพาะจังหวัดที่มีเป้าหมายประชากรในแต่ละปี</div>
                    <svg viewBox="0 0 360 210" width="100%" role="img" aria-label="แนวโน้มอัตราป่วย HT ต่อแสนประชากร {{ $htYt->map(fn($r) => $r['year'] . ' ' . number_format($r['rate'], 2))->implode(' · ') }}">
                        <g stroke="#e2e8f0" stroke-width="1">
                            @foreach ($ticks as $t)<line x1="46" y1="{{ 20 + $t * 50 }}" x2="350" y2="{{ 20 + $t * 50 }}"/>@endforeach
                        </g>
                        <g fill="#94a3b8" font-size="10" text-anchor="end">
                            @foreach ($ticks as $t)<text x="40" y="{{ 24 + $t * 50 }}">{{ number_format($vMax - $t * ($vMax - $vMin) / 3) }}</text>@endforeach
                        </g>
                        <path d="{{ $area }}" fill="#0d9488" opacity=".12"/>
                        <path d="{{ $path }}" fill="none" stroke="#0d9488" stroke-width="3" stroke-linejoin="round"/>
                        <g fill="#fff" stroke="#0d9488" stroke-width="3">
                            @foreach ($htYt as $i => $r)<circle cx="{{ round($px($i), 1) }}" cy="{{ round($py($r['rate']), 1) }}" r="5"/>@endforeach
                        </g>
                        <g font-size="11" font-weight="800" fill="#1e293b" text-anchor="middle">
                            @foreach ($htYt as $i => $r)<text x="{{ round($px($i), 1) }}" y="{{ round($py($r['rate']) - 12, 1) }}">{{ number_format($r['rate']) }}</text>@endforeach
                        </g>
                        <g fill="#475569" font-size="11" text-anchor="middle">
                            @foreach ($htYt as $i => $r)<text x="{{ round($px($i), 1) }}" y="192">{{ $r['year'] }}</text>@endforeach
                        </g>
                    </svg>
                @else
                    @php
                        $mt = collect($hmHt['trend'])->values();
                        $latestLabel = $hmHtLatest['label'] ?? null;
                        $latestIdx = $mt->search(fn($m) => $m['label'] === $latestLabel);
                        $latestIdx = $latestIdx === false ? null : $latestIdx;
                        $mTotal = $hmHtMonthTotal;
                        $reported = $latestIdx === null ? collect() : $mt->slice(0, $latestIdx + 1)->values();
                        $mMax = max(1, (int) $mt->max('value'));
                        $niceStep = function ($v) {
                            $p = 10 ** floor(log10(max($v, 1)));
                            foreach ([1, 2, 2.5, 5, 10] as $k) { if ($v <= $k * $p) { return $k * $p; } }
                            return 10 * $p;
                        };
                        $yStep = $niceStep($mMax / 4);
                        $yMax = $yStep * 4;
                        $mx0 = 48; $mx1 = 346; $myTop = 30; $myBase = 178;
                        $mpx = fn($i) => $mx0 + $i * (($mx1 - $mx0) / 11);
                        $mpy = fn($v) => $myBase - ($v / $yMax) * ($myBase - $myTop);
                        $pts = $reported->map(fn($m, $i) => ['x' => $mpx($i), 'y' => $mpy($m['value'])])->all();
                        $nPts = count($pts);
                        // Monotone cubic (Fritsch-Carlson) so the curve stays smooth but never dips below the data
                        $mPath = '';
                        if ($nPts >= 2) {
                            $d = []; $tg = [];
                            for ($k = 0; $k < $nPts - 1; $k++) { $d[$k] = ($pts[$k + 1]['y'] - $pts[$k]['y']) / ($pts[$k + 1]['x'] - $pts[$k]['x']); }
                            $tg[0] = $d[0]; $tg[$nPts - 1] = $d[$nPts - 2];
                            for ($k = 1; $k < $nPts - 1; $k++) { $tg[$k] = ($d[$k - 1] * $d[$k] <= 0) ? 0 : ($d[$k - 1] + $d[$k]) / 2; }
                            for ($k = 0; $k < $nPts - 1; $k++) {
                                if ($d[$k] == 0) { $tg[$k] = 0; $tg[$k + 1] = 0; continue; }
                                $a = $tg[$k] / $d[$k]; $b = $tg[$k + 1] / $d[$k]; $s = $a * $a + $b * $b;
                                if ($s > 9) { $t = 3 / sqrt($s); $tg[$k] = $t * $a * $d[$k]; $tg[$k + 1] = $t * $b * $d[$k]; }
                            }
                            $mPath = 'M' . round($pts[0]['x'], 1) . ' ' . round($pts[0]['y'], 1);
                            for ($k = 0; $k < $nPts - 1; $k++) {
                                $h = $pts[$k + 1]['x'] - $pts[$k]['x'];
                                $mPath .= ' C' . round($pts[$k]['x'] + $h / 3, 1) . ' ' . round($pts[$k]['y'] + $tg[$k] * $h / 3, 1)
                                    . ' ' . round($pts[$k + 1]['x'] - $h / 3, 1) . ' ' . round($pts[$k + 1]['y'] - $tg[$k + 1] * $h / 3, 1)
                                    . ' ' . round($pts[$k + 1]['x'], 1) . ' ' . round($pts[$k + 1]['y'], 1);
                            }
                            $mArea = $mPath . ' L' . round($pts[$nPts - 1]['x'], 1) . ' ' . $myBase . ' L' . round($pts[0]['x'], 1) . ' ' . $myBase . ' Z';
                        }
                        $peakIdx = $reported->isEmpty() ? null : $reported->pluck('value')->search($reported->max('value'));
                        $troughIdx = $reported->isEmpty() ? null : $reported->pluck('value')->search($reported->min('value'));
                        if ($reported->count() < 2 || $reported->max('value') === $reported->min('value')) { $peakIdx = null; $troughIdx = null; }
                    @endphp
                    <div class="hm-ht-head">
                        <div>
                            <h4>ผู้ป่วยรายใหม่รายเดือน</h4>
                            <div class="hm-cap">ปีงบประมาณ {{ $hmHt['year'] }} · ต.ค. - ก.ย. (จำนวนคน)</div>
                        </div>
                        <div class="hm-total-chip" title="ผลรวมผู้ป่วยรายใหม่ทั้งปีงบประมาณ {{ $hmHt['year'] }}">
                            <span>รวมทั้งหมด</span><b>{{ number_format($mTotal) }}</b><small>คน</small>
                        </div>
                    </div>
                    <svg viewBox="0 0 360 214" width="100%" role="img" aria-label="จำนวนผู้ป่วยความดันรายใหม่รายเดือน ปีงบประมาณ {{ $hmHt['year'] }} รวมทั้งหมด {{ number_format($mTotal) }} คน">
                        <defs>
                            <linearGradient id="hmHtArea" x1="0" y1="0" x2="0" y2="1">
                                <stop offset="0" stop-color="#14b8a6" stop-opacity=".38"/>
                                <stop offset="1" stop-color="#14b8a6" stop-opacity=".02"/>
                            </linearGradient>
                            <linearGradient id="hmHtLine" x1="0" y1="0" x2="1" y2="0">
                                <stop offset="0" stop-color="#2dd4bf"/>
                                <stop offset="1" stop-color="#0f766e"/>
                            </linearGradient>
                        </defs>
                        <g stroke="#e2e8f0" stroke-width="1" stroke-dasharray="3 4">
                            @for ($t = 0; $t <= 4; $t++)<line x1="{{ $mx0 - 6 }}" y1="{{ round($mpy($t * $yStep), 1) }}" x2="352" y2="{{ round($mpy($t * $yStep), 1) }}"/>@endfor
                        </g>
                        <g fill="#94a3b8" font-size="9" text-anchor="end">
                            @for ($t = 0; $t <= 4; $t++)<text x="{{ $mx0 - 10 }}" y="{{ round($mpy($t * $yStep) + 3, 1) }}">{{ number_format($t * $yStep) }}</text>@endfor
                        </g>
                        @if ($nPts >= 2)
                            <path d="{{ $mArea }}" fill="url(#hmHtArea)"/>
                            <path d="{{ $mPath }}" fill="none" stroke="url(#hmHtLine)" stroke-width="3" stroke-linecap="round" stroke-linejoin="round" style="filter: drop-shadow(0 4px 5px rgba(13,148,136,.3));"/>
                        @endif
                        @foreach ($reported as $i => $m)
                            @php $isLatest = $i === $latestIdx; @endphp
                            <circle cx="{{ round($mpx($i), 1) }}" cy="{{ round($mpy($m['value']), 1) }}" r="{{ $isLatest ? 5 : 3.2 }}" fill="{{ $isLatest ? '#0d9488' : '#fff' }}" stroke="#0d9488" stroke-width="2"/>
                            <circle cx="{{ round($mpx($i), 1) }}" cy="{{ round($mpy($m['value']), 1) }}" r="10" fill="transparent"><title>{{ $m['label'] }} {{ number_format($m['value']) }} คน</title></circle>
                            @if ($isLatest || $i === $peakIdx || $i === $troughIdx)
                                @php
                                    $lx = $mpx($i); $ly = $mpy($m['value']);
                                    $isLo = $i === $troughIdx;
                                    $below = $isLo && ($ly + 16) < 188;
                                    $anchor = $lx < 70 ? 'start' : ($lx > 320 ? 'end' : 'middle');
                                    $ax = $anchor === 'start' ? $lx - 6 : ($anchor === 'end' ? $lx + 6 : $lx);
                                @endphp
                                <text x="{{ round($ax, 1) }}" y="{{ round($below ? $ly + 16 : $ly - 10, 1) }}" font-size="10" font-weight="800" fill="#134e4a" text-anchor="{{ $anchor }}" stroke="#fff" stroke-width="3" paint-order="stroke" stroke-linejoin="round">@if ($i === $peakIdx)<tspan fill="#c0392b">สูงสุด </tspan>@elseif ($isLo)<tspan fill="#1e8449">ต่ำสุด </tspan>@endif{{ number_format($m['value']) }}</text>
                            @endif
                        @endforeach
                        <g fill="#475569" font-size="9" text-anchor="middle">
                            @foreach ($mt as $i => $m)<text x="{{ round($mpx($i), 1) }}" y="198" @if ($m['label'] === $latestLabel) font-weight="800" fill="#0f766e" @endif>{{ $m['label'] }}</text>@endforeach
                        </g>
                    </svg>
                @endif
            </div>
            @endif
        </div>
    </section>

    <!-- ===== 5. ผลิตภัณฑ์ลดโซเดียม (moved to the bottom) ===== -->
    <section class="hm-sec hm-products" id="sec-products">
        <div class="hm-sec-head">
            <div class="hm-sec-ic"><i class="fas fa-boxes-stacked"></i></div>
            <h3>ผลิตภัณฑ์ลดโซเดียม</h3>
            <span class="hm-scope">{{ $hmScope }}</span>
            <a class="hm-more" href="{{ $hmLink('reduced-sodium-products') }}">ดูรายละเอียด</a>
        </div>
        @php
            $hmPd = $productStats ?? ['total' => 0, 'certified' => 0, 'certified_percent' => null, 'top_type' => null, 'top_type_count' => 0, 'type_total' => 0];
            $hmPdProvCount = collect($productCountData)->filter(fn($r) => ($r['count'] ?? 0) > 0)->count();
        @endphp
        <div class="hm-grid pd">
            <div class="hm-kcol">
                <div class="hm-kpi" style="--k: #6366f1;">
                    <div class="hm-kpi-ico"><i class="fas fa-box-open"></i></div>
                    <div class="hm-kpi-body">
                        <span class="hm-kpi-l">ผลิตภัณฑ์ลดโซเดียมทั้งหมด</span>
                        <span class="hm-kpi-v">{{ number_format($hmPd['total']) }} <small>รายการ</small></span>
                        <span class="hm-kpi-d">{{ $hmPd['total'] > 0 ? 'จาก ' . $hmPdProvCount . ' จังหวัด' : 'ยังไม่มีข้อมูลผลิตภัณฑ์' }}</span>
                    </div>
                </div>
                <div class="hm-kpi" style="--k: #16a34a;">
                    <div class="hm-kpi-ico"><i class="fas fa-certificate"></i></div>
                    <div class="hm-kpi-body">
                        <span class="hm-kpi-l">ได้รับการรับรองมาตรฐาน</span>
                        <span class="hm-kpi-v">{{ $hmPd['certified_percent'] !== null ? $hmPd['certified_percent'] . '%' : '—' }}</span>
                        <span class="hm-kpi-d">{{ number_format($hmPd['certified']) }} จาก {{ number_format($hmPd['total']) }} รายการ</span>
                        @if ($hmPd['certified_percent'] !== null)<span class="hm-kpi-bar"><i style="width: {{ $hmPd['certified_percent'] }}%;"></i></span>@endif
                    </div>
                </div>
                <div class="hm-kpi" style="--k: #d97706;">
                    <div class="hm-kpi-ico"><i class="fas fa-tags"></i></div>
                    <div class="hm-kpi-body">
                        <span class="hm-kpi-l">ประเภทผลิตภัณฑ์ยอดนิยม</span>
                        <span class="hm-kpi-v hm-kpi-v-text">{{ $hmPd['top_type'] ?: '—' }}</span>
                        <span class="hm-kpi-d">{{ $hmPd['top_type'] ? number_format($hmPd['top_type_count']) . ' จาก ' . number_format($hmPd['total']) . ' รายการ' : 'ยังไม่มีข้อมูลผลิตภัณฑ์' }}</span>
                    </div>
                </div>
            </div>
            <div class="hm-pd-chart">
                <div class="card dash-card accent-teal"
                    style="padding: 25px; border-radius: 20px; border: 1px solid rgba(255, 255, 255, 0.4); box-shadow: 0 8px 32px rgba(0,0,0,0.03); backdrop-filter: blur(12px); -webkit-backdrop-filter: blur(12px);">
                    <div class="dash-card-head">
                        <div class="dash-card-icon"><i class="fas fa-boxes-stacked"></i></div>
                        <div>
                            <div class="dash-card-title">จำนวนผลิตภัณฑ์ลดโซเดียมจำแนกรายจังหวัด</div>
                            <div class="dash-card-subtitle">ข้อมูลจากฐานข้อมูลผลิตภัณฑ์ (จำนวนรายการ)</div>
                        </div>
                    </div>
                    <div style="height: 300px; position: relative;">
                        <canvas id="productCountChart"></canvas>
                    </div>
                </div>
            </div>
        </div>

        <!-- Risk-tier reference strip -->
        <div class="row">
            <div class="col-12">
                <div class="risk-legend-strip">
                    <div class="risk-legend-card tier-pass">
                        <span class="risk-legend-icon"><i class="fas fa-face-smile"></i></span>
                        <div>
                            <div class="risk-legend-title">เค็มน้อย</div>
                            <div class="risk-legend-sub">โซเดียมคลอไรด์ น้อยกว่าหรือเท่ากับ 0.70 ก./อาหาร 100 มล. หรือ โซเดียมน้อยกว่า 275 มก./อาหาร 100 มล.</div>
                        </div>
                    </div>
                    <div class="risk-legend-card tier-watch">
                        <span class="risk-legend-icon"><i class="fas fa-face-meh"></i></span>
                        <div>
                            <div class="risk-legend-title">เริ่มเค็ม</div>
                            <div class="risk-legend-sub">โซเดียมคลอไรด์ ระหว่าง 0.71-0.90 ก./อาหาร 100 มล. หรือ โซเดียมระหว่าง 275-354 มก./อาหาร 100 มล.</div>
                        </div>
                    </div>
                    <div class="risk-legend-card tier-risk">
                        <span class="risk-legend-icon"><i class="fas fa-face-frown"></i></span>
                        <div>
                            <div class="risk-legend-title">เค็มมาก</div>
                            <div class="risk-legend-sub">โซเดียมคลอไรด์ มากกว่า 0.90 ก./อาหาร 100 มล. หรือ โซเดียมมากกว่า 354 มก./อาหาร 100 มล.</div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </section>

    @endsection

    @section('extra_js')
        <script src="{{ asset('vendor/highcharts/highmaps.js') }}"></script>
        <script src="{{ asset('vendor/highcharts/exporting.js') }}"></script>
        <script src="{{ asset('vendor/highcharts/th-all.js') }}"></script>
        <script src="https://cdnjs.cloudflare.com/ajax/libs/leaflet/1.9.4/leaflet.min.js"></script>
        <script src="{{ asset('vendor/highcharts/th-districts-r10.js') }}"></script>
        <script>
            // มิติให้แผนที่: เงาตกพื้น + ความหนาของแผ่นจังหวัด (ชั้นสีเข้มเลื่อนลง) + ประกายแสงมุมซ้ายบน
            // ทุกชั้นทำซ้ำจากรูปร่างจังหวัดเอง เลื่อนเป็นพิกเซลจริง (หารด้วยสเกลของกลุ่มแผนที่)
            window.hmMapDepth = function (chart, colorOf) {
                const svg = chart.container.querySelector('svg');
                if (!svg) return;
                svg.querySelectorAll('.hm-depth').forEach(el => el.remove());
                const ns = 'http://www.w3.org/2000/svg';
                const pts = chart.series[0].points.filter(p => p.graphic && p.graphic.element);
                if (!pts.length) return;
                const uid = 'hmd' + (chart.index || 0);
                const parent = pts[0].graphic.element.parentNode;
                // กลุ่มซีรีส์ถูกตัดที่ขอบพื้นที่พล็อต เอาออกเพื่อให้ความหนา/เงาโผล่พ้นขอบล่างได้
                [parent, parent.parentNode].forEach(g => { if (g && g.removeAttribute) g.removeAttribute('clip-path'); });
                const m = /scale\(\s*([-\d.e]+)[ ,]+([-\d.e]+)\s*\)/.exec(parent.getAttribute('transform') || '');
                const kx = m ? parseFloat(m[1]) || 1 : 1, ky = m ? parseFloat(m[2]) || 1 : 1;
                const px = n => n / ky;
                const dark = p => {
                    if (colorOf) { const c = colorOf(p); if (c) return c; }
                    let c = p.color;
                    if (c && c.stops) c = c.stops[c.stops.length - 1][1];
                    try { return Highcharts.color(c).brighten(-0.32).get('rgb'); } catch (e) { return '#334155'; }
                };
                const defs = document.createElementNS(ns, 'defs');
                defs.setAttribute('class', 'hm-depth');
                defs.innerHTML =
                    '<linearGradient id="' + uid + 'g" x1="0" y1="1" x2=".75" y2="0">' +
                    '<stop offset="0" stop-color="#fff" stop-opacity=".42"/><stop offset=".45" stop-color="#fff" stop-opacity=".06"/>' +
                    '<stop offset="1" stop-color="#0f172a" stop-opacity=".16"/></linearGradient>' +
                    '<filter id="' + uid + 'b" x="-20%" y="-20%" width="140%" height="140%"><feGaussianBlur stdDeviation="' + (6 / Math.abs(kx)) + '"/></filter>';
                svg.insertBefore(defs, svg.firstChild);
                const first = pts.map(p => p.graphic.element).sort((a, b) => (a.compareDocumentPosition(b) & 4) ? -1 : 1)[0];
                const mk = (el, attrs, dy) => {
                    const c = el.cloneNode(false);
                    ['class', 'filter', 'fill', 'stroke', 'stroke-width', 'style'].forEach(n => c.removeAttribute(n));
                    c.setAttribute('class', 'hm-depth');
                    c.setAttribute('pointer-events', 'none');
                    Object.keys(attrs).forEach(k => c.setAttribute(k, attrs[k]));
                    if (dy) c.setAttribute('transform', 'translate(0 ' + px(dy) + ')');
                    return c;
                };
                pts.forEach(p => parent.insertBefore(mk(p.graphic.element, { fill: '#0b2f3a', 'fill-opacity': '.30', filter: 'url(#' + uid + 'b)' }, 17), first));
                for (let d = 7; d >= 1; d--) {
                    pts.forEach(p => {
                        const col = dark(p);
                        parent.insertBefore(mk(p.graphic.element, { fill: col, stroke: col, 'stroke-width': 1.2 / Math.abs(kx), 'stroke-linejoin': 'round' }, d), first);
                    });
                }
                pts.forEach(p => {
                    const el = p.graphic.element;
                    el.parentNode.insertBefore(mk(el, { fill: 'url(#' + uid + 'g)' }), el.nextSibling);
                });
            };
        </script>
        @php
            $hmMiPins = [];
            foreach (($hmKd['pins'] ?? []) as $hmP) {
                if (!empty($hmP['display_only'])) { continue; }
                $hmCrumb = explode(' › ', (string) ($hmP['breadcrumb'] ?? ''));
                $hmMiPins[] = ['p' => $hmP['province_name'] ?? null, 'g' => (int) ($hmP['progress'] ?? 0), 't' => (int) ($hmP['progress_total'] ?? 8), 'd' => trim($hmCrumb[0])];
            }
            $hmMiDist = [];
            foreach (($hmKd['provinces'] ?? []) as $hmR) {
                $hmMiDist[$hmR['province']] = ['a' => $hmR['answered'], 't' => $hmR['total']];
            }
        @endphp
        <script>
            (function () {
                const el = document.getElementById('hmMapChoro');
                if (!el || !window.Highcharts || !Highcharts.maps) return;
                const colors = {
                    'อุบลราชธานี': { fill: '#2563eb', stroke: '#1e40af' },
                    'ศรีสะเกษ': { fill: '#d97706', stroke: '#92400e' },
                    'ยโสธร': { fill: '#7c3aed', stroke: '#5b21b6' },
                    'อำนาจเจริญ': { fill: '#16a34a', stroke: '#15803d' },
                    'มุกดาหาร': { fill: '#db2777', stroke: '#9d174d' },
                };
                const dflt = { fill: '#6b7280', stroke: '#374151' };
                const keys = { 'อุบลราชธานี': 'th-ur', 'ศรีสะเกษ': 'th-si', 'ยโสธร': 'th-ys', 'อำนาจเจริญ': 'th-ac', 'มุกดาหาร': 'th-md' };
                const raw = @json($hmKd['area_counts'] ?? []);
                const pinList = @json($hmMiPins);
                const distRows = @json($hmMiDist);
                const esc = t => String(t).replace(/[&<>"]/g, c => ({ '&': '&amp;', '<': '&lt;', '>': '&gt;', '"': '&quot;' }[c]));
                const info = document.getElementById('hmMapInfo');
                const stat = (v, label, pct) => '<div class="hm-mi-st"><b>' + v + '</b>' + label + '<div class="hm-mi-mb"><div class="hm-mi-mf" style="width:' + Math.max(3, Math.min(100, Math.round(pct))) + '%"></div></div></div>';
                function showOverview() {
                    if (!info) return;
                    const sum = (f) => names.reduce((a, n) => a + f(n), 0);
                    const areas = sum(n => raw[n] || 0);
                    const da = sum(n => (distRows[n] || { a: 0 }).a), dt = sum(n => (distRows[n] || { t: 0 }).t);
                    const full = pinList.filter(x => x.g >= x.t).length;
                    const sumG = pinList.reduce((a, x) => a + x.g, 0), sumT = pinList.reduce((a, x) => a + x.t, 0);
                    const avg = sumT > 0 ? Math.round(sumG / sumT * 100) : 0;
                    info.style.setProperty('--pc', '#0f4c5c');
                    info.innerHTML = '<div class="hm-mi-top"><span class="hm-mi-name">ภาพรวม</span>'
                        + '<span class="hm-mi-pill">ก้าวหน้า ' + avg + '%</span></div>'
                        + '<div class="hm-mi-stats">' + stat(areas, 'พื้นที่', 100) + stat(da + '/' + dt, 'อำเภอตอบแล้ว', dt ? da / dt * 100 : 0) + '</div>'
                        + '<div class="hm-mi-bar"><i style="width:' + avg + '%"></i></div>'
                        + '<div class="hm-mi-dist">ก้าวหน้าเฉลี่ย ' + avg + '%' + (top ? ' · มากที่สุด: ' + esc(top) + ' ' + (raw[top] || 0) + ' พื้นที่' : '') + '</div>';
                    document.querySelectorAll('#hmMapChoro .oa-map-label').forEach(l => l.classList.remove('is-sel'));
                }
                function showInfo(name) {
                    if (!info) return;
                    if (!name) { showOverview(); return; }
                    const areasAll = names.reduce((a, n) => a + (raw[n] || 0), 0);
                    const c = colors[name] || dflt;
                    const pp = pinList.filter(x => x.p === name);
                    const full = pp.filter(x => x.g >= x.t).length;
                    const sumG = pp.reduce((a, x) => a + x.g, 0), sumT = pp.reduce((a, x) => a + x.t, 0);
                    const avg = sumT > 0 ? Math.round(sumG / sumT * 100) : 0;
                    const byD = {};
                    pp.forEach(x => { if (!x.d) return; (byD[x.d] = byD[x.d] || { n: 0, ans: 0 }).n++; if (x.g > 0) byD[x.d].ans++; });
                    const on = Object.keys(byD).filter(d => byD[d].ans > 0).sort((a, b) => byD[b].ans - byD[a].ans);
                    const off = Object.keys(byD).filter(d => byD[d].ans === 0).sort();
                    const dr = distRows[name] || { a: 0, t: 0 };
                    info.style.setProperty('--pc', c.fill);
                    const dtitle = 'ดำเนินงานแล้ว: ' + (on.join(', ') || '-') + (off.length ? ' | ยังไม่รายงาน: ' + off.join(', ') : '');
                    info.innerHTML = '<div class="hm-mi-top"><span class="hm-mi-name">' + esc(name) + '</span>'
                        + '<span class="hm-mi-pill">ก้าวหน้า ' + avg + '%</span></div>'
                        + '<div class="hm-mi-stats">' + stat(raw[name] || 0, 'พื้นที่', areasAll ? (raw[name] || 0) / areasAll * 100 : 0) + stat(dr.a + '/' + dr.t, 'อำเภอตอบแล้ว', dr.t ? dr.a / dr.t * 100 : 0) + '</div>'
                        + '<div class="hm-mi-bar" title="ความก้าวหน้าเฉลี่ย ' + avg + '%"><i style="width:' + avg + '%"></i></div>'
                        + '<div class="hm-mi-dist" title="' + esc(dtitle) + '">ก้าวหน้าเฉลี่ย ' + avg + '%' + (off.length ? ' · ยังไม่รายงาน: ' + esc(off.join(', ')) : ' · ทุกอำเภอรายงานแล้ว') + '</div>';
                    document.querySelectorAll('#hmMapChoro .oa-map-label').forEach(l => l.classList.toggle('is-sel', l.dataset.p === name));
                }
                const names = Object.keys(keys).filter(n => (n in raw));
                const max = Math.max(1, ...names.map(n => raw[n] || 0));
                const total = names.reduce((a, n) => a + (raw[n] || 0), 0);
                const top = total > 0 ? names.reduce((a, n) => ((raw[n] || 0) > (raw[a] || 0) ? n : a)) : null;
                const fillOf = n => {
                    const base = Highcharts.color((colors[n] || dflt).fill);
                    const mix = amt => Highcharts.color('#ffffff').tweenTo(base, amt);
                    return { linearGradient: { x1: 0, y1: 0, x2: 0.35, y2: 1 }, stops: [[0, mix(0.6)], [1, mix(0.9)]] };
                };
                const offs = { 'ยโสธร': { x: -26, y: -4 }, 'อำนาจเจริญ': { x: 16, y: 10 }, 'อุบลราชธานี': { x: 18, y: 16 }, 'ศรีสะเกษ': { x: -6, y: 10 } };
                const data = names.map(n => ({ 'hc-key': keys[n], value: raw[n] || 0, name: n, color: fillOf(n), className: n === top ? 'oa-top-province' : undefined, dataLabels: offs[n] || {} }));
                const full = Highcharts.maps['countries/th/th-all'];
                const want = Object.values(keys);
                const mapData = Object.assign({}, full, { features: full.features.filter(f => want.includes(f.properties['hc-key'])) });
                Highcharts.mapChart('hmMapChoro', {
                    chart: { map: mapData, backgroundColor: 'transparent', spacing: [4, 4, 4, 4], marginBottom: 148, events: { load: function () { window.hmMapDepth(this, p => (colors[p.name] || dflt).stroke); }, redraw: function () { window.hmMapDepth(this, p => (colors[p.name] || dflt).stroke); } } },
                    title: { text: '' }, credits: { enabled: false }, legend: { enabled: false },
                    exporting: { enabled: false }, mapNavigation: { enabled: false },
                    plotOptions: { map: {
                        allAreas: false, borderColor: '#FFFFFF', borderWidth: 2.5, cursor: 'pointer',
                        shadow: { color: 'rgba(15, 76, 92, 0.32)', offsetX: 0, offsetY: 6, width: 9 },
                        states: { hover: { brightness: 0.15, borderColor: '#1e293b' } },
                        dataLabels: {
                            enabled: true, useHTML: true, allowOverlap: true, crop: false, overflow: 'allow',
                            style: { textOutline: 'none', pointerEvents: 'none' },
                            formatter: function () {
                                const c = colors[this.point.name] || dflt;
                                const isTop = this.point.name === top;
                                const pct = Math.round(this.point.value / max * 100);
                                const d = (this.point.index * 0.12 + 0.2).toFixed(2);
                                return '<div class="oa-map-label' + (isTop ? ' is-top' : '') + '" data-p="' + this.point.name + '" style="animation-delay:' + d + 's;">'
                                    + (isTop ? '<span class="oa-map-crown"><i class="fas fa-star"></i>มากที่สุด</span>' : '')
                                    + '<span class="oa-map-label-name">' + this.point.name + '</span>'
                                    + '<span class="oa-map-label-count" style="color:' + c.stroke + ';"><i class="fas fa-location-dot"></i>' + this.point.value + '<small>พื้นที่</small></span>'
                                    + '<span class="oa-map-label-bar"><b style="width:' + pct + '%;background:' + c.fill + '"></b></span></div>';
                            }
                        },
                        point: { events: { mouseOver: function () { showInfo(this.name); }, click: function () { showInfo(this.name); } } }
                    } },
                    tooltip: { enabled: false }, /* ป้ายบนแผนที่บอกชื่อ+จำนวนอยู่แล้ว - ไม่ให้ tooltip มาบัง */
                    series: [{ name: 'จำนวนพื้นที่', data: data, joinBy: 'hc-key' }]
                });
                showOverview();
                el.addEventListener('mouseleave', showOverview);
                if (info) info.addEventListener('click', e => { if (!e.target.closest('a')) showOverview(); });
            })();
        </script>

        <script>
            // Set Sarabun as the global font for all Highcharts charts
            Highcharts.setOptions({
                lang: { thousandsSep: ',', decimalPoint: '.' },
                chart: {
                    style: {
                        fontFamily: "'Sarabun', sans-serif"
                    }
                }
            });

            // Keep the anchor offset in sync with the real sticky header height.
            (function () {
                const setHeadH = () => {
                    const h = document.querySelector('.site-header');
                    if (!h) return;
                    // header is only sticky above 768px; below that it scrolls away, so no offset is needed
                    const sticky = getComputedStyle(h).position === 'sticky';
                    document.documentElement.style.setProperty('--hm-head-h', (sticky ? h.offsetHeight : 0) + 'px');
                };
                setHeadH();
                window.addEventListener('resize', setHeadH);
            })();

            let behaviorInlineChart;
            const metricsByProvince = {!! json_encode($metricsByProvince) !!};
            const targetProvinces = {!! json_encode($awarenessMetrics['target_provinces']) !!};

            // Canvas-gradient helper (Chart.js has no declarative gradient object
            // like Highcharts, so the fill is built from the chart's own canvas context)
            function makeBarGradient(context, topColor, bottomColor) {
                const { chart } = context;
                const { ctx, chartArea } = chart;
                if (!chartArea) return topColor;
                const gradient = ctx.createLinearGradient(0, chartArea.top, 0, chartArea.bottom);
                gradient.addColorStop(0, topColor);
                gradient.addColorStop(1, bottomColor);
                return gradient;
            }

            // Lightens a #rrggbb hex color toward white by the given amount
            // (0-1), used as the top gradient stop for the inline behavior
            // chart, whose base color is picked dynamically per metric.
            function lightenHex(hex, amount) {
                const n = parseInt(hex.replace('#', ''), 16);
                const r = (n >> 16) & 255, g = (n >> 8) & 255, b = n & 255;
                const mix = (c) => Math.round(c + (255 - c) * amount);
                return `rgb(${mix(r)}, ${mix(g)}, ${mix(b)})`;
            }

            // One province color set shared by every table/bar/chart on this page.
            const provinceColorMap = {
                'อุบลราชธานี': '#2563eb', 'ศรีสะเกษ': '#d97706', 'ยโสธร': '#7c3aed',
                'อำนาจเจริญ': '#16a34a', 'มุกดาหาร': '#db2777'
            };

            // Darkens a #rrggbb hex color toward black by the given amount (0-1).
            function darkenHex(hex, amount) {
                const n = parseInt(hex.replace('#', ''), 16);
                const r = (n >> 16) & 255, g = (n >> 8) & 255, b = n & 255;
                const mix = (c) => Math.round(c * (1 - amount));
                return `rgb(${mix(r)}, ${mix(g)}, ${mix(b)})`;
            }

            // Depth fill for one arc/bar: a bright highlight on the upper-left that
            // settles into the base color and a slightly deeper shade at the
            // lower-right, so each segment reads as a lit, rounded surface.
            function makeDepthGradient(context, color) {
                const { chart } = context;
                const { ctx, chartArea } = chart;
                if (!chartArea) return color;
                const g = ctx.createLinearGradient(chartArea.left, chartArea.top, chartArea.right, chartArea.bottom);
                g.addColorStop(0, lightenHex(color, 0.45));
                g.addColorStop(0.5, color);
                g.addColorStop(1, darkenHex(color, 0.22));
                return g;
            }

            // Soft drop-shadow behind each dataset (bars, arcs) so charts read
            // with a little depth instead of a flat fill. Uses the SINGULAR
            // beforeDatasetDraw/afterDatasetDraw hooks (not the plural
            // beforeDatasetsDraw/afterDatasetsDraw) so the shadow is closed
            // right after each dataset draws, before chartjs-plugin-datalabels
            // draws its label text in the same phase - otherwise the label
            // text itself picks up the shadow blur.
            const barShadowPlugin = {
                id: 'barShadow',
                beforeDatasetDraw(chart) {
                    const c = chart.ctx;
                    c.save();
                    c.shadowColor = 'rgba(15, 23, 42, 0.18)';
                    c.shadowBlur = 10;
                    c.shadowOffsetX = 0;
                    c.shadowOffsetY = 4;
                },
                afterDatasetDraw(chart) {
                    chart.ctx.restore();
                }
            };

            document.addEventListener('DOMContentLoaded', function() {
                const mapData = {!! json_encode($awarenessData) !!};

                /**
                 * DEFINITIVE Highcharts keys from highcharts/mapdata (ISO Standard)
                 * Verified directly from https://code.highcharts.com/mapdata/countries/th/th-all.js
                 */
                const hcMapKeys = {
                    'อุบลราชธานี': 'th-ur', // 34 Ubon Ratchathani
                    'ศรีสะเกษ': 'th-si', // 33 Si Sa Ket
                    'ยโสธร': 'th-ys', // 35 Yasothon
                    'อำนาจเจริญ': 'th-ac', // 37 Amnat Charoen
                    'มุกดาหาร': 'th-md' // 49 Mukdahan
                };

                // ขยับป้ายของจังหวัดที่พื้นที่แคบ/ติดกัน ไม่ให้ป้ายทับกัน (หน่วยเป็นพิกเซลจากจุดกึ่งกลางพื้นที่)
                const labelOffsets = {
                    'ยโสธร': { x: -26, y: -4 },
                    'อำนาจเจริญ': { x: 16, y: 10 },
                    'อุบลราชธานี': { x: 18, y: 16 },
                    'ศรีสะเกษ': { x: -6, y: 10 }
                };

                const chartData = [];
                for (const [name, data] of Object.entries(mapData)) {
                    if (hcMapKeys[name]) {
                        chartData.push({
                            dataLabels: labelOffsets[name] || {},
                            'hc-key': hcMapKeys[name],
                            'value': data.passed_percentage, // Using percentage for coloring
                            'total_people': data.total_people,
                            'passed_count': data.value,
                            'passed_percentage': data.passed_percentage,
                            'name': name
                        });
                    }
                }

                if (typeof Highcharts === 'undefined') {
                    console.warn('Highcharts not loaded');
                    return;
                }

                if (!Highcharts.maps || !Highcharts.maps['countries/th/th-all']) {
                    console.warn('Highcharts maps or Thailand map data not loaded');
                    return;
                }

                const fullMapData = Highcharts.maps['countries/th/th-all'];
                const targetKeys = Object.values(hcMapKeys);

                // Filter features to ensure they form a CONTIGUOUS cluster
                const filteredFeatures = fullMapData.features.filter(f =>
                    targetKeys.includes(f.properties['hc-key'])
                );
                const filteredMapData = {
                    ...fullMapData,
                    features: filteredFeatures
                };

                // เกณฑ์ผ่านของแผนที่ (ระดับจังหวัด): METHOD_SCORE ใช้ 60%, อื่น ๆ ใช้ 80%
                const awMapPassThreshold = {{ $awarenessMethod === \App\Models\AwarenessPassSetting::METHOD_SCORE ? 60 : 80 }};
                const awMapPassLabel = awMapPassThreshold === 60 ? '60%' : '80%';

                if (document.getElementById('map-container')) {
                    const awMapChart = Highcharts.mapChart('map-container', {
                        chart: {
                            map: filteredMapData,
                            backgroundColor: 'transparent',
                            spacing: [10, 10, 20, 10],
                            events: { load: function () { window.hmMapDepth(this); }, redraw: function () { window.hmMapDepth(this); } }
                        },
                        title: {
                            text: ''
                        },
                        tooltip: {
                            useHTML: true,
                            backgroundColor: 'rgba(255, 255, 255, 0.98)',
                            borderWidth: 0,
                            shadow: true,
                            outside: true, // Render outside chart container to avoid overlap/clipping
                            padding: 0,
                            headerFormat: '',
                            pointFormatter: function() {
                                const status = this.value >= awMapPassThreshold ? 'ผ่านเกณฑ์' : 'ไม่ผ่านเกณฑ์';
                                const statusColor = this.value >= awMapPassThreshold ? '#10b981' : '#ef4444';
                                const badgeBg = this.value >= awMapPassThreshold ? '#f0fdf4' : '#fef2f2';

                                return '<div style="padding: 15px; min-width: 220px; font-family: \'Sarabun\', sans-serif;">' +
                                    '<div style="font-size: 16px; font-weight: 800; color: #1e293b; margin-bottom: 12px; border-bottom: 1px solid #f1f5f9; padding-bottom: 8px;">' +
                                    this.name + '</div>' +
                                    '<table style="width: 100%; border-collapse: collapse;">' +
                                    '<tr>' +
                                    '<td style="color: #64748b; padding: 4px 0;">แบบประเมินทั้งหมด:</td>' +
                                    '<td style="text-align: right; font-weight: 700; color: #334155;">' + (
                                        this.total_people || 0).toLocaleString() +
                                    ' <small>คน</small></td>' +
                                    '</tr>' +
                                    '<tr>' +
                                    '<td style="color: #64748b; padding: 4px 0;">ระดับความตระหนักรู้:</td>' +
                                    '<td style="text-align: right; font-weight: 700; color: #334155;">' + (
                                        this.passed_count || 0).toLocaleString() +
                                    ' <small>คน</small></td>' +
                                    '</tr>' +
                                    '<tr>' +
                                    '<td style="color: #64748b; padding: 4px 0;">ร้อยละความตระหนักรู้:</td>' +
                                    '<td style="text-align: right; font-weight: 800; color: #1e293b; font-size: 1.1rem;">' +
                                    (this.passed_percentage || 0).toLocaleString() + '%</td>' +
                                    '</tr>' +
                                    '</table>' +
                                    '<div style="margin-top: 12px; padding: 8px; border-radius: 6px; text-align: center; background: ' +
                                    badgeBg + '; border: 1px solid ' + statusColor + '20;">' +
                                    '<span style="color: ' + statusColor +
                                    '; font-weight: 800; font-size: 12px;">สถานะ: ' + status +
                                    ' (เป้าหมาย ' + awMapPassLabel + ')</span>' +
                                    '</div>' +
                                    '<div style="margin-top: 10px; text-align: center; color: #94a3b8; font-size: 11px;">' +
                                    '<i class="fas fa-hand-pointer"></i> คลิกที่จังหวัดเพื่อดูรายละเอียด' +
                                    '</div>' +
                                    '</div>';
                            }
                        },
                        mapNavigation: {
                            enabled: false
                        },
                        colorAxis: {
                            dataClasses: [{
                                to: awMapPassThreshold - 0.01,
                                color: '#eb7a72', // Red
                                name: 'ไม่ผ่านเกณฑ์ ( < ' + awMapPassLabel + ' )'
                            }, {
                                from: awMapPassThreshold,
                                color: '#4dbd98', // Green
                                name: 'ผ่านเกณฑ์ ( ≥ ' + awMapPassLabel + ' )'
                            }]
                        },
                        legend: {
                            enabled: true,
                            layout: 'horizontal',
                            align: 'center',
                            verticalAlign: 'bottom',
                            itemStyle: {
                                fontWeight: 'normal',
                                fontSize: '12px'
                            },
                            itemMarginTop: 10 // Space between map and legend
                        },
                        plotOptions: {
                            map: {
                                allAreas: false,
                                joinBy: ['hc-key', 'hc-key'],
                                borderColor: '#FFFFFF',
                                borderWidth: 2,
                                cursor: 'pointer',
                                states: {
                                    hover: {
                                        enabled: false // Disable brightening on hover
                                    }
                                },
                                dataLabels: {
                                    enabled: true,
                                    useHTML: true,
                                    // แสดงป้ายครบทั้ง 5 จังหวัด: จังหวัดเล็ก (เช่น อำนาจเจริญ) เดิมถูก Highcharts
                                    // ซ่อนอัตโนมัติเพราะป้ายซ้อน/ล้นขอบพื้นที่ จึงสั่งให้วาดเสมอ
                                    allowOverlap: true,
                                    crop: false,
                                    overflow: 'allow',
                                    formatter: function() {
                                        const ok = (this.point.value || 0) >= awMapPassThreshold;
                                        const pctColor = ok ? '#047857' : '#b91c1c';
                                        return '<div style="text-align:center; line-height:1.15; font-family: \'Sarabun\', sans-serif; pointer-events:none; ' +
                                            'background: rgba(255,255,255,0.93); border-radius: 9px; padding: 1px 6px 2px; ' +
                                            'box-shadow: 0 1px 4px rgba(15,23,42,0.25); white-space: nowrap;">' +
                                            '<div style="font-weight:700; font-size:11px; color:#1e293b;">' +
                                            this.point.name + '</div>' +
                                            '<div style="font-size:14px; font-weight:800; color:' + pctColor + ';">' +
                                            (this.point.value || 0) + '%</div>' +
                                            '</div>';
                                    },
                                    style: {
                                        textOutline: 'none',
                                        fontFamily: '\'Sarabun\', sans-serif',
                                        pointerEvents: 'none' // Ensure labels don't interfere with map regions
                                    }
                                }
                            }
                        },
                        series: [{
                            data: chartData,
                            name: 'จำนวนผู้มีความตระหนักรู้',
                            cursor: 'pointer',
                            states: {
                                hover: {
                                    color: undefined, // Prevent the fixed pass/fail
                                    brightness: 0, // dataClass colors from washing out
                                    enabled: true,
                                    borderColor: '#1e293b',
                                    borderWidth: 3
                                }
                            },
                            point: {
                                events: {
                                    click: function () {
                                        // Only meaningful in the "ทุกจังหวัด" overview
                                        // (a single already-selected province has
                                        // nothing further to drill into), but
                                        // harmless either way - it just re-applies
                                        // the same province.
                                        const provinceSelect = document.getElementById('province');
                                        if (provinceSelect) {
                                            provinceSelect.value = this.name;
                                            updateFilters();
                                        }
                                    }
                                }
                            }
                        }]
                    });
                    // ปรับขนาดแผนที่ให้พอดีการ์ดหลังเลย์เอาต์นิ่ง
                    setTimeout(function () { awMapChart.reflow(); }, 150);
                    window.addEventListener('load', function () { awMapChart.reflow(); });
                }

                // --- Product Count Chart ---
                const productCountCtx = document.getElementById('productCountChart').getContext('2d');
                const productCountData = {!! json_encode($productCountData) !!};

                new Chart(productCountCtx, {
                    type: 'bar',
                    data: {
                        labels: productCountData.map(d => d.province),
                        datasets: [{
                            label: 'จำนวนผลิตภัณฑ์',
                            data: productCountData.map(d => d.count),
                            backgroundColor: (context) => {
                                const row = productCountData[context.dataIndex];
                                const c = row ? (provinceColorMap[row.province] || '#2563eb') : '#2563eb';
                                return makeBarGradient(context, lightenHex(c, 0.5), c);
                            },
                            hoverBackgroundColor: (context) => {
                                const row = productCountData[context.dataIndex];
                                const c = row ? (provinceColorMap[row.province] || '#2563eb') : '#2563eb';
                                return makeBarGradient(context, lightenHex(c, 0.65), darkenHex(c, 0.1));
                            },
                            borderRadius: 10,
                            borderSkipped: false,
                            barPercentage: 0.6
                        }]
                    },
                    options: {
                        responsive: true,
                        maintainAspectRatio: false,
                        plugins: {
                            legend: {
                                display: false
                            },
                            datalabels: {
                                anchor: 'end',
                                align: 'top',
                                offset: 5,
                                clip: false, // Ensure labels are NOT cut off
                                font: {
                                    family: "'Sarabun', sans-serif",
                                    weight: '800',
                                    size: 12
                                },
                                color: '#1e293b',
                                formatter: (value) => value > 0 ? value.toLocaleString() : '0'
                            },
                            tooltip: {
                                enabled: true,
                                backgroundColor: 'rgba(255, 255, 255, 0.95)',
                                titleColor: '#1e293b',
                                titleFont: {
                                    family: "'Sarabun', sans-serif",
                                    size: 14,
                                    weight: 'bold'
                                },
                                bodyColor: '#64748b',
                                bodyFont: {
                                    family: "'Sarabun', sans-serif",
                                    size: 13
                                },
                                borderColor: '#e2e8f0',
                                borderWidth: 1,
                                padding: 12,
                                displayColors: false,
                                callbacks: {
                                    label: function(context) {
                                        return ' จำนวน: ' + context.parsed.y.toLocaleString() + ' รายการ';
                                    }
                                }
                            }
                        },
                        scales: {
                            y: {
                                beginAtZero: true,
                                grace: '20%',
                                grid: {
                                    drawBorder: false,
                                    color: '#f1f5f9',
                                    borderDash: [5, 5]
                                },
                                ticks: {
                                    font: {
                                        family: "'Sarabun', sans-serif",
                                        size: 11
                                    },
                                    color: '#94a3b8',
                                    precision: 0
                                },
                                title: {
                                    display: true,
                                    text: 'จำนวนรายการ',
                                    font: {
                                        family: "'Sarabun', sans-serif",
                                        weight: 'bold',
                                        size: 12
                                    },
                                    color: '#64748b'
                                }
                            },
                            x: {
                                grid: {
                                    display: false
                                },
                                ticks: {
                                    font: {
                                        family: "'Sarabun', sans-serif",
                                        size: 12,
                                        weight: '700'
                                    },
                                    color: '#1e293b'
                                }
                            }
                        }
                    },
                    plugins: [ChartDataLabels, barShadowPlugin]
                });

                // --- Participant Donut Chart ---
                const participantDonutCtx = document.getElementById('participantDonutChart').getContext('2d');
                const awarenessData = {!! json_encode($awarenessData) !!};
                const donutLabels = Object.keys(awarenessData);
                const donutValues = donutLabels.map(key => awarenessData[key].total_people);
                const totalParticipants = donutValues.reduce((a, b) => a + b, 0);

                const donutColors = donutLabels.map(name => provinceColorMap[name] || '#64748b');

                // Declare donutActiveIdx early so resetDonutCenter (and onHover) can reference it safely
                let donutActiveIdx = -1; // -1 = none selected

                function resetDonutCenter() {
                    const t = document.getElementById('donutTotal');
                    if (!t) return;
                    t.innerText = totalParticipants.toLocaleString();
                    t.style.color = '#0f172a';
                    const c = document.getElementById('donutCap'); if (c) c.innerText = 'ผู้ตอบทั้งหมด';
                    const u = document.getElementById('donutSub'); if (u) u.innerText = 'คน';
                }
                // set initial center
                resetDonutCenter();

                const participantDonut = new Chart(participantDonutCtx, {
                    type: 'doughnut',
                    data: {
                        labels: donutLabels,
                        datasets: [{
                            data: donutValues,
                            backgroundColor: donutColors,
                            hoverBackgroundColor: donutColors.map(c => {
                                // lighten slightly on hover
                                const r = parseInt(c.slice(1,3),16), g2 = parseInt(c.slice(3,5),16), b = parseInt(c.slice(5,7),16);
                                return `rgba(${r},${g2},${b},0.85)`;
                            }),
                            borderColor: '#ffffff',
                            borderWidth: 3,
                            borderRadius: 4,
                            spacing: 3,
                            hoverOffset: 8
                        }]
                    },
                    options: {
                        responsive: true,
                        maintainAspectRatio: false,
                        cutout: '62%',
                        layout: { padding: 6 },
                        onHover: function(event, elements) {
                            if (donutActiveIdx !== -1) return; // locked to clicked item
                            const totalEl = document.getElementById('donutTotal');
                            if (!totalEl) return;
                            const capEl = document.getElementById('donutCap');
                            const subEl = document.getElementById('donutSub');
                            if (elements && elements.length > 0) {
                                const idx = elements[0].index;
                                totalEl.innerText = donutValues[idx].toLocaleString();
                                totalEl.style.color = donutColors[idx];
                                if (capEl) capEl.innerText = donutLabels[idx];
                                if (subEl) subEl.innerText = (donutValues[idx] * 100 / totalParticipants).toFixed(1) + '%';
                            } else {
                                resetDonutCenter();
                            }
                        },
                        plugins: {
                            legend: { display: false },
                            datalabels: { display: false },
                            tooltip: {
                                enabled: true,
                                backgroundColor: '#1e293b',
                                titleColor: '#ffffff',
                                bodyColor: '#94a3b8',
                                padding: 10,
                                cornerRadius: 8,
                                displayColors: true,
                                boxWidth: 10,
                                boxHeight: 10,
                                callbacks: {
                                    title: function(items) {
                                        return items[0].label;
                                    },
                                    label: function(item) {
                                        const val = item.parsed;
                                        const pct = totalParticipants > 0 ? (val * 100 / totalParticipants).toFixed(1) : '0.0';
                                        return ' จำนวน: ' + val.toLocaleString() + ' รายการ (' + pct + '%)';
                                    }
                                }
                            }
                        }
                    },
                    plugins: [ChartDataLabels]
                });

                // Belt-and-suspenders reset: onHover above already reverts
                // the center number once elements is empty, but this
                // guarantees it resets even if the pointer leaves the
                // canvas fast enough that Chart.js's own hover tracking
                // misses the final empty-elements callback.
                participantDonutCtx.canvas.addEventListener('mouseleave', function() {
                    resetDonutCenter();
                });

                // Custom Legend for Donut (right side, new design)
                const legendContainer = document.getElementById('donutLegend');
                // (donutActiveIdx already declared above, before the Chart constructor)

                // Update center text helper
                function setDonutCenter(idx) {
                    const totalEl = document.getElementById('donutTotal');
                    const capEl  = document.getElementById('donutCap');
                    const subEl  = document.getElementById('donutSub');
                    if (idx === -1) {
                        if (totalEl) { totalEl.innerText = totalParticipants.toLocaleString(); totalEl.style.color = '#0f172a'; }
                        if (capEl)  capEl.innerText  = 'ผู้ตอบทั้งหมด';
                        if (subEl)  subEl.innerText  = 'คน';
                    } else {
                        if (totalEl) { totalEl.innerText = donutValues[idx].toLocaleString(); totalEl.style.color = donutColors[idx]; }
                        if (capEl)  capEl.innerText  = donutLabels[idx];
                        if (subEl)  subEl.innerText  = (donutValues[idx] * 100 / totalParticipants).toFixed(1) + '%';
                    }
                }
                // override resetDonutCenter to use new logic
                function resetDonutCenter() { setDonutCenter(donutActiveIdx); }

                // Initial center: "N รายการทั้งหมด"
                setDonutCenter(-1);

                if (legendContainer) {
                    donutLabels.forEach((label, i) => {
                        const val  = donutValues[i];
                        const perc = totalParticipants > 0
                            ? (val * 100 / totalParticipants).toFixed(1)
                            : '0.0';

                        const item = document.createElement('div');
                        item.style.cssText =
                            'display:flex; align-items:center; gap:8px; width:100%; padding:6px 10px; border-radius:8px; cursor:pointer;' +
                            'border:1.5px solid transparent; transition:all .15s ease; background:#ffffff;';
                        item.dataset.idx = i;

                        item.innerHTML =
                            // colored dot
                            `<span style="flex-shrink:0; width:10px; height:10px; border-radius:50%; background:${donutColors[i]};"></span>` +
                            // province name
                            `<span style="flex:1; font-size:0.78rem; color:#334155; font-weight:600; white-space:nowrap; overflow:hidden; text-overflow:ellipsis;">${label}</span>` +
                            // percent (bold accent)
                            `<span style="font-size:0.8rem; font-weight:800; color:${donutColors[i]}; flex-shrink:0; min-width:38px; text-align:right;">${perc}%</span>` +
                            // count
                            `<span style="font-size:0.72rem; color:#64748b; font-weight:600; flex-shrink:0; min-width:36px; text-align:right; font-variant-numeric:tabular-nums;">${val.toLocaleString()}</span>`;

                        legendContainer.appendChild(item);

                        function activateRow(idx) {
                            donutActiveIdx = idx;
                            setDonutCenter(idx);
                            participantDonut.setActiveElements([{ datasetIndex: 0, index: idx }]);
                            participantDonut.update('none');
                            legendContainer.querySelectorAll('[data-idx]').forEach(function (el) {
                                const on = Number(el.dataset.idx) === idx;
                                el.style.opacity     = on ? '1' : '0.45';
                                el.style.borderColor = on ? donutColors[idx] : 'transparent';
                                el.style.background  = on ? `color-mix(in srgb, ${donutColors[idx]} 8%, #ffffff)` : '#ffffff';
                            });
                        }

                        function deactivateAll() {
                            donutActiveIdx = -1;
                            setDonutCenter(-1);
                            participantDonut.setActiveElements([]);
                            participantDonut.update('none');
                            legendContainer.querySelectorAll('[data-idx]').forEach(function (el) {
                                el.style.opacity     = '1';
                                el.style.borderColor = 'transparent';
                                el.style.background  = '#ffffff';
                            });
                        }

                        // Click: toggle select/deselect
                        item.addEventListener('click', function () {
                            if (donutActiveIdx === i) { deactivateAll(); }
                            else { activateRow(i); }
                        });

                        // Hover (only when nothing is pinned-selected)
                        item.addEventListener('mouseenter', function () {
                            if (donutActiveIdx !== -1) return;
                            setDonutCenter(i);
                            participantDonut.setActiveElements([{ datasetIndex: 0, index: i }]);
                            participantDonut.update('none');
                            legendContainer.querySelectorAll('[data-idx]').forEach(function (el) {
                                const on = Number(el.dataset.idx) === i;
                                el.style.opacity     = on ? '1' : '0.45';
                                el.style.borderColor = on ? donutColors[i] : 'transparent';
                            });
                        });
                        item.addEventListener('mouseleave', function () {
                            if (donutActiveIdx !== -1) return;
                            setDonutCenter(-1);
                            participantDonut.setActiveElements([]);
                            participantDonut.update('none');
                            legendContainer.querySelectorAll('[data-idx]').forEach(function (el) {
                                el.style.opacity     = '1';
                                el.style.borderColor = 'transparent';
                            });
                        });
                    });
                }
            });

            function hideInlineChart() {
                const container = document.getElementById('inlineChartContainer');
                const listView = document.getElementById('metricsListView');

                container.style.display = 'none';
                listView.style.display = 'flex';
                // Trigger animation
                listView.classList.remove('view-fade-in');
                void listView.offsetWidth; // force reflow
                listView.classList.add('view-fade-in');

                document.querySelectorAll('.awareness-item').forEach(el => el.classList.remove('active'));
            }

            function updateInlineChart(type, title, color) {
                const container = document.getElementById('inlineChartContainer');
                const listView = document.getElementById('metricsListView');

                // Switch Views
                listView.style.display = 'none';
                container.style.display = 'flex';
                // Trigger animation
                container.classList.remove('view-fade-in');
                void container.offsetWidth; // force reflow
                container.classList.add('view-fade-in');

                document.getElementById('inlineChartTitle').innerText = 'ข้อมูลรายจังหวัด: ' + title;

                // Toggle active state on items
                document.querySelectorAll('.awareness-item').forEach(el => {
                    if (el.dataset.type === type) el.classList.add('active');
                    else el.classList.remove('active');
                });

                const ctx = document.getElementById('behaviorInlineChart').getContext('2d');
                const newData = targetProvinces.map(p => metricsByProvince[type][p] || 0);

                if (behaviorInlineChart) {
                    behaviorInlineChart.data.datasets[0].data = newData;
                    behaviorInlineChart.data.datasets[0].backgroundColor = (context) => makeBarGradient(context, lightenHex(color, 0.4), color);
                    behaviorInlineChart.data.datasets[0].hoverBackgroundColor = (context) => makeBarGradient(context, lightenHex(color, 0.55), color);
                    behaviorInlineChart.data.datasets[0].borderColor = color;
                    if (behaviorInlineChart.options && behaviorInlineChart.options.plugins) {
                        behaviorInlineChart.options.plugins.legend = {
                            display: false
                        };
                    }
                    behaviorInlineChart.update();
                } else {
                    behaviorInlineChart = new Chart(ctx, {
                        type: 'bar',
                        plugins: [ChartDataLabels, barShadowPlugin],
                        data: {
                            labels: targetProvinces,
                            datasets: [{
                                label: 'จำนวนคน',
                                data: newData,
                                backgroundColor: (context) => makeBarGradient(context, lightenHex(color, 0.4), color),
                                hoverBackgroundColor: (context) => makeBarGradient(context, lightenHex(color, 0.55), color),
                                borderColor: color,
                                borderWidth: 1,
                                borderRadius: 8,
                                borderSkipped: false,
                                barPercentage: 0.6,
                                datalabels: {
                                    anchor: 'end',
                                    align: 'top',
                                    offset: 4,
                                    color: '#1e293b',
                                    font: {
                                        family: 'Sarabun',
                                        size: 11,
                                        weight: '800'
                                    },
                                    formatter: (value) => value > 0 ? value.toLocaleString() : ''
                                }
                            }]
                        },
                        options: {
                            layout: {
                                padding: {
                                    top: 25
                                }
                            },
                            responsive: true,
                            maintainAspectRatio: false,
                            plugins: {
                                legend: {
                                    display: false
                                },
                                tooltip: {
                                    backgroundColor: 'rgba(255, 255, 255, 0.95)',
                                    titleColor: '#1e293b',
                                    bodyColor: '#64748b',
                                    borderColor: '#e2e8f0',
                                    borderWidth: 1,
                                    padding: 10,
                                    cornerRadius: 8,
                                    displayColors: false,
                                    callbacks: {
                                        label: function(context) {
                                            return 'จำนวน: ' + context.parsed.y.toLocaleString() + ' คน';
                                        }
                                    }
                                }
                            },
                            scales: {
                                y: {
                                    beginAtZero: true,
                                    grid: {
                                        display: true,
                                        color: '#f1f5f9',
                                        drawBorder: false
                                    },
                                    ticks: {
                                        stepSize: 1,
                                        color: '#94a3b8',
                                        font: {
                                            family: 'Sarabun',
                                            size: 10
                                        }
                                    }
                                },
                                x: {
                                    grid: {
                                        display: false
                                    },
                                    ticks: {
                                        color: '#64748b',
                                        font: {
                                            family: 'Sarabun',
                                            size: 10,
                                            weight: '600'
                                        }
                                    }
                                }
                            }
                        }
                    });
                }
            }

            function showFullHeader(el) {
                const title = el.getAttribute('title');
                const short = el.innerText;

                Swal.fire({
                    title: '<span style="font-family: \'Sarabun\', sans-serif;">' + short + '</span>',
                    html: '<div style="font-family: \'Sarabun\', sans-serif; font-size: 1.1rem; line-height: 1.6; color: #475569;">' +
                        title + '</div>',
                    icon: 'info',
                    confirmButtonText: 'ตกลง',
                    confirmButtonColor: '#3182ce',
                    customClass: {
                        title: 'swal-title-sarabun',
                        content: 'swal-text-sarabun'
                    }
                });
            }

            function updateFilters() {
                const year = document.getElementById('fiscal_year').value;
                const province = document.getElementById('province').value;
                const url = new URL(window.location.href);
                url.searchParams.set('fiscal_year', year);
                url.searchParams.set('province', province);
                window.location.href = url.toString();
            }

            // เมนูตัวกรองแบบกำหนดเอง: ครอบ <select> เดิม (ค่าและ onchange ยังทำงานตามเดิม)
            (function () {
                var closeAll = function (except) {
                    document.querySelectorAll('.hf-menu.is-open').forEach(function (m) {
                        if (m !== except) {
                            m.classList.remove('is-open');
                            m.parentNode.classList.remove('is-open');
                            m._btn.setAttribute('aria-expanded', 'false');
                        }
                    });
                };

                document.querySelectorAll('.home-filter-item .awareness-filter-select').forEach(function (sel) {
                    var chip = sel.closest('.home-filter-item');
                    var btn = document.createElement('button');
                    var menu = document.createElement('ul');
                    btn.type = 'button';
                    btn.className = 'hf-trigger';
                    btn.setAttribute('aria-haspopup', 'listbox');
                    btn.setAttribute('aria-expanded', 'false');
                    menu.className = 'hf-menu';
                    menu.setAttribute('role', 'listbox');
                    menu.tabIndex = -1;
                    menu._btn = btn;
                    menu.addEventListener('click', function (e) { e.preventDefault(); e.stopPropagation(); });

                    var opts = [];
                    Array.prototype.forEach.call(sel.options, function (o, i) {
                        var li = document.createElement('li');
                        li.className = 'hf-option';
                        li.setAttribute('role', 'option');
                        li.textContent = o.textContent.trim();
                        li.dataset.value = o.value;
                        li.setAttribute('aria-selected', o.selected ? 'true' : 'false');
                        li.addEventListener('click', function (e) {
                            e.preventDefault();
                            e.stopPropagation();
                            choose(i);
                        });
                        menu.appendChild(li);
                        opts.push(li);
                    });

                    var active = sel.selectedIndex;
                    function paint() {
                        btn.textContent = opts[sel.selectedIndex] ? opts[sel.selectedIndex].textContent : '';
                        opts.forEach(function (li, i) {
                            li.setAttribute('aria-selected', i === sel.selectedIndex ? 'true' : 'false');
                            li.classList.toggle('is-active', i === active);
                        });
                    }
                    function open() {
                        closeAll(menu);
                        active = sel.selectedIndex;
                        paint();
                        menu.classList.add('is-open');
                        chip.classList.add('is-open');
                        btn.setAttribute('aria-expanded', 'true');
                        var cur = opts[active];
                        if (cur) menu.scrollTop = Math.max(0, cur.offsetTop - 60);
                    }
                    function close() {
                        menu.classList.remove('is-open');
                        chip.classList.remove('is-open');
                        btn.setAttribute('aria-expanded', 'false');
                    }
                    function choose(i) {
                        close();
                        if (i !== sel.selectedIndex) {
                            sel.selectedIndex = i;
                            paint();
                            sel.dispatchEvent(new Event('change', { bubbles: true }));
                        }
                        btn.focus();
                    }

                    btn.addEventListener('click', function (e) {
                        e.stopPropagation();
                        menu.classList.contains('is-open') ? close() : open();
                    });
                    btn.addEventListener('keydown', function (e) {
                        var isOpen = menu.classList.contains('is-open');
                        if (e.key === 'ArrowDown' || e.key === 'ArrowUp') {
                            e.preventDefault();
                            if (!isOpen) { open(); return; }
                            active = Math.min(opts.length - 1, Math.max(0, active + (e.key === 'ArrowDown' ? 1 : -1)));
                            paint();
                            opts[active].scrollIntoView({ block: 'nearest' });
                        } else if ((e.key === 'Enter' || e.key === ' ') && isOpen) {
                            e.preventDefault();
                            choose(active);
                        } else if (e.key === 'Escape' && isOpen) {
                            e.preventDefault();
                            close();
                        }
                    });

                    chip.classList.add('hf-enhanced');
                    sel.parentNode.insertBefore(btn, sel);
                    chip.appendChild(menu);
                    paint();
                });

                document.addEventListener('click', function () { closeAll(null); });
            })();
        </script>
    @endsection
