@extends('layouts.layout')

@section('title', 'หน้าหลัก - Salt & Sodium Smart Monitor')
@section('header_title', 'แดชบอร์ดภาพรวมเขตสุขภาพที่ 10')
@section('header_subtitle', 'ระบบติดตามและสรุปผลข้อมูลสุขภาพ')

@section('content')
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
            font-size: 0.74rem;
            color: #94a3b8;
            font-weight: 600;
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

    <!-- Overall KPI summary: จำนวนผู้เข้าร่วมทั้งหมด / ผ่านเกณฑ์ / ไม่ผ่านเกณฑ์ /
         อัตราผ่านเกณฑ์ - same ปีงบประมาณ/จังหวัด scope as the map/metrics below -->
    <div class="kpi-summary-row"
        style="{{ ($awarenessCriteriaPending ?? false) ? 'filter: blur(2px); opacity: 0.55; pointer-events: none;' : '' }}">
        <div class="kpi-summary-card kpi-total">
            <div class="kpi-summary-icon"><i class="fas fa-users"></i></div>
            <div class="kpi-summary-body">
                <span class="kpi-summary-label">จำนวนผู้เข้าร่วมทั้งหมด</span>
                <span class="kpi-summary-value">{{ number_format($awarenessOverallTotal) }}</span>
                <span class="kpi-summary-sub">ปีงบฯ {{ $fiscalYearLabel }}</span>
            </div>
        </div>
        <div class="kpi-summary-card kpi-pass">
            <div class="kpi-summary-icon"><i class="fas fa-circle-check"></i></div>
            <div class="kpi-summary-body">
                <span class="kpi-summary-label">ผ่านเกณฑ์</span>
                <span class="kpi-summary-value">{{ number_format($awarenessOverallPass) }}</span>
                <span class="kpi-summary-sub">คน</span>
            </div>
        </div>
        <div class="kpi-summary-card kpi-fail">
            <div class="kpi-summary-icon"><i class="fas fa-circle-xmark"></i></div>
            <div class="kpi-summary-body">
                <span class="kpi-summary-label">ไม่ผ่านเกณฑ์</span>
                <span class="kpi-summary-value">{{ number_format($awarenessOverallFail) }}</span>
                <span class="kpi-summary-sub">คน</span>
            </div>
        </div>
        <div class="kpi-summary-card kpi-rate">
            <div class="kpi-summary-icon"><i class="fas fa-percent"></i></div>
            <div class="kpi-summary-body">
                <span class="kpi-summary-label">อัตราผ่านเกณฑ์</span>
                <span class="kpi-summary-value">{{ $awarenessOverallRate }}%</span>
                <span class="kpi-summary-sub">
                    @if ($awarenessMethod === 'mixed')
                        เกณฑ์ผ่าน: ตามเกณฑ์ที่กำหนดของแต่ละปีงบประมาณ
                    @elseif ($awarenessMethod === \App\Models\AwarenessPassSetting::METHOD_SCORE)
                        เกณฑ์ผ่าน &ge; 19.2 / 32 คะแนน
                    @else
                        เกณฑ์ผ่าน: ตรงตามเกณฑ์ข้อ 1 และข้อ 2 ที่กำหนด
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

    <!-- Row 1: Map, Survey Participant, and Metrics List -->
    <div class="row home-row-top" style="display: flex; gap: 20px; flex-wrap: wrap; margin-bottom: 20px;">
        <!-- Column 1: Map Section -->
        <div style="flex: 4; min-width: 280px;">
            <div class="card dash-card accent-pink"
                style="padding: 20px; border-radius: 20px; border: 1px solid rgba(255, 255, 255, 0.4); box-shadow: 0 8px 32px rgba(0,0,0,0.03); backdrop-filter: blur(12px); -webkit-backdrop-filter: blur(12px); height: 100%; min-height: 450px;">
                <div class="dash-card-head">
                    <div class="dash-card-icon"><i class="fas fa-map-location-dot"></i></div>
                    <div>
                        <div class="dash-card-title">ร้อยละความตระหนักรู้</div>
                        <div class="dash-card-subtitle">ปี {{ $fiscalYearLabel }} &middot; รายจังหวัด</div>
                    </div>
                </div>
                <!-- Always render map for all years -->
                <div style="position: relative; height: 100%; min-height: 400px;">
                    <div id="map-container"
                        style="height: 400px; width: 100%; {{ ($awarenessCriteriaPending ?? false) ? 'filter: blur(4px); opacity: 0.6;' : '' }}">
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
                <!-- Donut Chart -->
                <div style="width: 100%; max-width: 220px; height: 220px; position: relative; flex-shrink: 0;">
                    <canvas id="participantDonutChart"></canvas>
                    <div
                        style="position: absolute; top: 50%; left: 50%; transform: translate(-50%, -50%); text-align: center; pointer-events: none;">
                        <div id="donutTotal" style="font-size: 1.6rem; font-weight: 800; color: #1e293b; line-height: 1;">0
                        </div>
                        <div style="font-size: 0.7rem; color: #64748b; margin-top: 4px;">คน</div>
                    </div>
                </div>
                <!-- Legend Below Chart -->
                <div id="donutLegend"
                    style="width: 100%; max-width: 320px; margin-top: 14px; display: flex; flex-direction: column; gap: 6px;">
                    <!-- generated by JS -->
                </div>
            </div>
        </div>

        <!-- Column 3: Awareness Metrics Section -->
        <div style="flex: 4; min-width: 380px;">
            <div class="card dash-card accent-blue"
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
                <div id="awarenessContentBody" style="min-height: 250px; position: relative;">
                    <!-- View A: Metrics List -->
                    <div id="metricsListView" class="view-fade-in"
                        style="display: flex; flex-direction: column; gap: 10px; padding: 0 2px;">
                        <!-- ไม่เคยเลย (เติมน้ำปลา) -->
                        <div class="metric-row awareness-item" data-type="no_seasoning"
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
                        <div style="height: 180px; position: relative;">
                            <canvas id="behaviorInlineChart"></canvas>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
    </div>

    <!-- Row 2: Bar Charts side-by-side -->
    <div class="row home-row-charts" style="display: flex; gap: 20px; flex-wrap: wrap; margin-bottom: 30px;">
        <!-- Sodium Comparison Card -->
        <div style="flex: 1; min-width: 350px;">
            <div class="card dash-card accent-amber"
                style="padding: 25px; border-radius: 20px; border: 1px solid rgba(255, 255, 255, 0.4); box-shadow: 0 8px 32px rgba(0,0,0,0.03); backdrop-filter: blur(12px); -webkit-backdrop-filter: blur(12px);">
                <div class="dash-card-head">
                    <div class="dash-card-icon"><i class="fas fa-chart-column"></i></div>
                    <div>
                        <div class="dash-card-title">ภาพรวมค่าเฉลี่ยโซเดียม ก่อน - หลัง ปรับสูตร (รายจังหวัด)</div>
                        <div class="dash-card-subtitle">เปรียบเทียบค่าเฉลี่ยจากทุกเมนู (มก./100 มล.)</div>
                    </div>
                </div>
                <div style="height: 300px; position: relative;">
                    <canvas id="sodiumCompareChart"></canvas>
                </div>
            </div>
        </div>

        <!-- Product Count Card -->
        <div style="flex: 1; min-width: 350px;">
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
    <div class="row" style="margin-bottom: 30px;">
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
    @endsection

    @section('extra_js')
        <script src="{{ asset('vendor/highcharts/highmaps.js') }}"></script>
        <script src="{{ asset('vendor/highcharts/exporting.js') }}"></script>
        <script src="{{ asset('vendor/highcharts/th-all.js') }}"></script>

        <script>
            // Set Sarabun as the global font for all Highcharts charts
            Highcharts.setOptions({
                chart: {
                    style: {
                        fontFamily: "'Sarabun', sans-serif"
                    }
                }
            });

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

                const chartData = [];
                for (const [name, data] of Object.entries(mapData)) {
                    if (hcMapKeys[name]) {
                        chartData.push({
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

                if (document.getElementById('map-container')) {
                    Highcharts.mapChart('map-container', {
                        chart: {
                            map: filteredMapData,
                            backgroundColor: 'transparent',
                            spacing: [10, 10, 10, 10]
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
                                const status = this.value >= 80 ? 'ผ่านเกณฑ์' : 'ไม่ผ่านเกณฑ์';
                                const statusColor = this.value >= 80 ? '#10b981' : '#ef4444';
                                const badgeBg = this.value >= 80 ? '#f0fdf4' : '#fef2f2';

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
                                    ' (เป้าหมาย 80%)</span>' +
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
                                to: 79.99,
                                color: '#eb7a72', // Reference image Red
                                name: 'ไม่ผ่านเกณฑ์ ( < 80% )'
                            }, {
                                from: 80,
                                color: '#4dbd98', // Reference image Green
                                name: 'ผ่านเกณฑ์ ( ≥ 80% )'
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
                                    color: '#000000',
                                    formatter: function() {
                                        return '<div style="text-align:center; line-height: 1.1; font-family: \'Sarabun\', sans-serif; pointer-events: none;">' +
                                            '<div style="font-weight:bold; font-size:10px; margin-bottom: 2px;">' +
                                            this.point.name + '</div>' +
                                            '<div style="font-size:11px; font-weight: 800; color: #1e293b;">' +
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
                }

                // --- Sodium Comparison Chart ---
                const sodiumCompareCtx = document.getElementById('sodiumCompareChart').getContext('2d');
                const sodiumCompareData = {!! json_encode($sodiumCompareData) !!};

                const provinces = sodiumCompareData.map(d => d.province);
                const avgBefore = sodiumCompareData.map(d => d.avg_before);
                const avgAfter = sodiumCompareData.map(d => d.avg_after);

                new Chart(sodiumCompareCtx, {
                    type: 'bar',
                    data: {
                        labels: provinces,
                        datasets: [{
                                label: 'ก่อนปรับสูตร',
                                data: avgBefore,
                                backgroundColor: (context) => makeBarGradient(context, '#fc8296', '#fb526b'),
                                hoverBackgroundColor: (context) => makeBarGradient(context, '#fda9b6', '#e11d48'),
                                borderRadius: 10,
                                borderSkipped: false,
                                barPercentage: 0.8,
                                categoryPercentage: 0.7
                            },
                            {
                                label: 'หลังปรับสูตร',
                                data: avgAfter,
                                backgroundColor: (context) => makeBarGradient(context, '#6ee7ac', '#39d378'),
                                hoverBackgroundColor: (context) => makeBarGradient(context, '#9bf0c4', '#16a34a'),
                                borderRadius: 10,
                                borderSkipped: false,
                                barPercentage: 0.8,
                                categoryPercentage: 0.7
                            }
                        ]
                    },
                    options: {
                        responsive: true,
                        maintainAspectRatio: false,
                        plugins: {
                            legend: {
                                display: true,
                                position: 'bottom',
                                labels: {
                                    font: {
                                        family: "'Sarabun', sans-serif",
                                        size: 12
                                    },
                                    usePointStyle: true,
                                    padding: 20
                                }
                            },
                            datalabels: {
                                anchor: 'end',
                                align: 'top',
                                offset: 5,
                                clip: false, // Ensure labels are NOT cut off
                                font: {
                                    family: "'Sarabun', sans-serif",
                                    weight: '800',
                                    size: 11
                                },
                                color: '#1e293b',
                                formatter: (value) => value > 0 ? value.toLocaleString() + ' มก.' : ''
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
                                displayColors: true,
                                callbacks: {
                                    label: function(context) {
                                        return ' ' + context.dataset.label + ': ' + context.parsed.y
                                            .toLocaleString() + ' มก.';
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
                                    stepSize: 250
                                },
                                title: {
                                    display: true,
                                    text: 'ปริมาณโซเดียม (มก.)',
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
                            backgroundColor: (context) => makeBarGradient(context, '#7dabfb', '#2563eb'),
                            hoverBackgroundColor: (context) => makeBarGradient(context, '#a6c6fd', '#1d4ed8'),
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

                document.getElementById('donutTotal').innerText = totalParticipants.toLocaleString();

                const donutColors = [
                    '#26c6da', // Teal
                    '#9575cd', // Purple
                    '#ffb74d', // Orange
                    '#ffd54f', // Yellow
                    '#4db6ac' // Darker Teal
                ];

                new Chart(participantDonutCtx, {
                    type: 'doughnut',
                    data: {
                        labels: donutLabels,
                        datasets: [{
                            data: donutValues,
                            backgroundColor: donutColors,
                            borderWidth: 0,
                            hoverOffset: 10
                        }]
                    },
                    options: {
                        responsive: true,
                        maintainAspectRatio: false,
                        cutout: '75%',
                        // Swap the center number for the hovered province's
                        // own count, reverting to the grand total once the
                        // pointer leaves that slice (elements comes back
                        // empty) or leaves the canvas entirely.
                        onHover: function(event, elements) {
                            const totalEl = document.getElementById('donutTotal');
                            if (!totalEl) return;
                            if (elements && elements.length > 0) {
                                const idx = elements[0].index;
                                totalEl.innerText = donutValues[idx].toLocaleString();
                            } else {
                                totalEl.innerText = totalParticipants.toLocaleString();
                            }
                        },
                        plugins: {
                            legend: {
                                display: false
                            },
                            datalabels: {
                                display: false
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
                                callbacks: {
                                    label: function(context) {
                                        let label = context.label || '';
                                        let value = context.parsed || 0;
                                        let percentage = (value * 100 / totalParticipants).toFixed(1);
                                        return ` ${label}: ${value.toLocaleString()} คน (${percentage}%)`;
                                    }
                                }
                            }
                        }
                    },
                    plugins: [ChartDataLabels, barShadowPlugin]
                });

                // Belt-and-suspenders reset: onHover above already reverts
                // the center number once elements is empty, but this
                // guarantees it resets even if the pointer leaves the
                // canvas fast enough that Chart.js's own hover tracking
                // misses the final empty-elements callback.
                participantDonutCtx.canvas.addEventListener('mouseleave', function() {
                    const totalEl = document.getElementById('donutTotal');
                    if (totalEl) totalEl.innerText = totalParticipants.toLocaleString();
                });

                // Custom Legend for Donut (below chart)
                const legendContainer = document.getElementById('donutLegend');
                if (legendContainer) {
                    donutLabels.forEach((label, i) => {
                        const val = donutValues[i];
                        const perc = totalParticipants > 0 ? Math.round(val * 100 / totalParticipants) : 0;

                        const item = document.createElement('div');
                        item.style.cssText =
                            'display:flex; align-items:center; justify-content:space-between; width:100%; padding:5px 0; border-bottom:1px dashed #f1f5f9; overflow:hidden;';

                        // Left: dot + name
                        const left = document.createElement('div');
                        left.style.cssText =
                            'display:flex; align-items:center; gap:6px; flex:1; min-width:0; margin-right:8px;';
                        left.innerHTML = `
                            <span style="width:10px;height:10px;border-radius:50%;background:${donutColors[i]};flex-shrink:0;display:inline-block;"></span>
                            <span style="color:#475569;font-size:12px;font-weight:600;white-space:nowrap;overflow:hidden;text-overflow:ellipsis;">${label}</span>
                        `;

                        // Right: count + percent
                        const right = document.createElement('div');
                        right.style.cssText =
                            'display:flex; align-items:center; gap:6px; flex-shrink:0; white-space:nowrap;';
                        right.innerHTML = `
                            <span style="color:#64748b;font-size:11px;font-weight:500;">${val.toLocaleString()}</span>
                            <span style="color:#1e293b;font-weight:800;font-size:12px;min-width:30px;text-align:right;">${perc}%</span>
                        `;

                        item.appendChild(left);
                        item.appendChild(right);
                        legendContainer.appendChild(item);
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
                container.style.display = 'block';
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
        </script>
    @endsection
