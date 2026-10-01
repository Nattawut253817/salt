@extends('layouts.layout')

@section('title', 'แบบรายงานลดการบริโภคเกลือ - Salt & Sodium Smart Monitor')
@section('header_title', 'แบบรายงานลดการบริโภคเกลือ')
@section('header_subtitle', 'ข้อมูลการบันทึกรายงานและการประเมินผลลดเค็ม')

@section('extra_css')
    <style>
        @import url('https://fonts.googleapis.com/css2?family=Sarabun:wght@300;400;700;800;900&display=swap');

        /* Scoped to this page's own content wrapper only - a bare `*`
           here would leak into the shared site header/nav (it did:
           it was clobbering the header's Itim font on this page). */
        .cr-wrap,
        .cr-wrap * {
            font-family: 'Sarabun', sans-serif !important;
        }

        /* The scoped rule above ties in specificity with the shared
           Font Awesome protection rule in layout.blade.php and, being
           later in the document, was winning - turning every icon on
           this page into Sarabun glyphs (i.e. making them disappear).
           Re-assert Font Awesome here with higher specificity so icons
           inside .cr-wrap render correctly again. Brand icons (.fab /
           .fa-brands) get their own rule since they live in a separate
           font ("Font Awesome 6 Brands", weight 400) - folding them into
           the solid-icon font above would render a blank glyph. */
        .cr-wrap .fas,
        .cr-wrap .far,
        .cr-wrap .fa-solid,
        .cr-wrap .fa-regular,
        .cr-wrap i {
            font-family: "Font Awesome 6 Free" !important;
            font-weight: 900 !important;
        }

        .cr-wrap .fab,
        .cr-wrap .fa-brands {
            font-family: "Font Awesome 6 Brands" !important;
            font-weight: 400 !important;
        }

        .cr-wrap {
            /* Top padding trimmed (was 24px all around) - the shared
               .content-header above this already adds its own 25px
               margin-bottom, so the two stacked together left a large
               gap before the filter bar. Left/right padding removed too
               (was 24px) - it was insetting the filter bar and every
               card by 24px on each side compared to .content-header
               above, which has no side padding of its own, so the two
               edges never lined up. */
            padding: 4px 0 24px;
            background: #ffffff;
            min-height: 100vh;
        }

        /* --- Chart + leaderboard split --- */
        .cr-chart-split {
            display: grid;
            grid-template-columns: 1.6fr 1fr;
        }

        @media (max-width: 860px) {
            .cr-chart-split {
                grid-template-columns: 1fr;
            }

            .cr-chart-pane {
                border-right: none !important;
                border-bottom: 1px solid #f1f5f9;
            }
        }

        .cr-chart-pane {
            padding: 24px;
            border-right: 1px solid #f1f5f9;
        }

        .cr-leaderboard-pane {
            padding: 24px 22px;
            display: flex;
            flex-direction: column;
            justify-content: space-between;
            height: 100%;
        }

        .cr-lb-row {
            display: flex;
            align-items: center;
            gap: 12px;
            padding: 11px 8px;
            border-radius: 10px;
            border-bottom: 1px solid #f1f5f9;
            transition: background-color 0.15s;
        }

        .cr-lb-row:last-child {
            border-bottom: none;
        }

        .cr-lb-row:hover {
            background-color: #f8fafc;
        }

        /* Background/glow color comes from each row's own status (green =
           ครบ 4 ไตรมาส, orange = ยังไม่ครบ - same threshold and colors as
           the bar fill and % text below) via an inline style, not rank
           position - see the province-ranked loop below. */
        .cr-lb-rank {
            width: 27px;
            height: 27px;
            border-radius: 50%;
            font-size: 0.72rem;
            font-weight: 800;
            display: flex;
            align-items: center;
            justify-content: center;
            flex-shrink: 0;
        }

        .cr-lb-name {
            width: 96px;
            flex-shrink: 0;
            font-size: 0.8rem;
            font-weight: 700;
            color: #1e293b;
            overflow: hidden;
            text-overflow: ellipsis;
            white-space: nowrap;
        }

        /* Track now grows to fill the row instead of a fixed 64px, so the
           name-gap-bar-number layout doesn't leave a large dead zone for
           short names - the bar length reads as the visual anchor. A faint
           inset shadow gives the track a groove for the fill to sit in. */
        .cr-lb-pct-track {
            flex: 1;
            min-width: 40px;
            height: 8px;
            border-radius: 99px;
            background: #eef1f6;
            overflow: hidden;
            flex-shrink: 1;
            box-shadow: inset 0 1px 2px rgba(15, 23, 42, 0.07);
        }

        .cr-lb-pct-fill {
            height: 100%;
            border-radius: 99px;
            transition: width 0.5s ease;
            box-shadow: 0 1px 2px rgba(0, 0, 0, 0.15);
        }

        .cr-lb-pct-num {
            font-size: 0.8rem;
            font-weight: 800;
            width: 46px;
            text-align: right;
            flex-shrink: 0;
            font-variant-numeric: tabular-nums;
        }

        /* A right-aligned pill in a .cr-card-header, e.g. the fiscal-year
           tag beside the province-ranking card's title. */
        .cr-card-header-pill {
            background: #eef2ff;
            color: #4338ca;
            font-size: 0.72rem;
            font-weight: 800;
            padding: 5px 13px;
            border-radius: 999px;
            white-space: nowrap;
            flex-shrink: 0;
        }

        /* --- Filter Bar --- */
        /* Each filter is its own self-contained chip (icon + label + value
           + chevron) rather than a fixed-width column of a single-row
           grid. flex-wrap lets chips reflow onto as many lines as the
           ACTUAL rendered width needs - this reacts to the real container
           (narrower than the viewport because of the side nav) instead of
           a viewport-width media query, which was guessing wrong and
           letting "ล้าง" get clipped off the edge instead of wrapping. No
           horizontal scroll needed since overflow simply wraps to a new
           line. Matches the same treatment used on kidney-dhb-report.blade.php. */
        .cr-filter-card {
            background: #fff;
            border: 1px solid #e2e8f0;
            border-radius: 20px;
            box-shadow: 0 2px 12px rgba(0, 0, 0, 0.04);
            margin-bottom: 20px;
            display: flex;
            flex-wrap: wrap;
            align-items: center;
            gap: 8px;
            padding: 10px 12px 10px 22px;
        }

        .cr-filter-title {
            display: flex;
            align-items: center;
            gap: 8px;
            padding: 8px 10px;
            white-space: nowrap;
            font-weight: 800;
            font-size: 0.85rem;
            color: #334155;
        }

        .cr-filter-item {
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 8px;
            padding: 9px 16px;
            white-space: nowrap;
            background: #f8fafc;
            border: 1px solid #eef1f6;
            border-radius: 999px;
            /* Grow to share the bar's width evenly (balanced left-to-
               right) instead of sitting at its own natural size with
               empty space trailing after the last chip. The min-width is
               what still lets flex-wrap fall back to multiple rows once
               the REAL container (not just the viewport - the side nav
               narrows it) gets too tight, rather than squeezing chips
               down to nothing. */
            flex: 1 1 200px;
        }

        .cr-btn-reset {
            flex-shrink: 0;
        }

        .cr-filter-title i,
        .cr-filter-item-icon {
            color: #94a3b8;
            font-size: 0.8rem;
        }

        .cr-filter-item-label {
            font-weight: 700;
            font-size: 0.82rem;
            color: #64748b;
        }

        .cr-select {
            appearance: none;
            -webkit-appearance: none;
            border: none;
            background: transparent;
            font-family: 'Sarabun', sans-serif;
            font-weight: 700;
            font-size: 0.83rem;
            color: #1e293b;
            cursor: pointer;
            padding: 0;
            /* Not just any width: a native <select> sizes itself to fit
               its WIDEST <option> (the หน่วยงาน/agency list runs long),
               not the currently shown value. A capped max-width plus
               ellipsis keeps one long option list from blowing up its
               chip's width. */
            max-width: 130px;
            overflow: hidden;
            text-overflow: ellipsis;
            white-space: nowrap;
        }

        .cr-select:focus {
            outline: none;
        }

        .cr-filter-chevron {
            color: #94a3b8;
            font-size: 0.65rem;
            pointer-events: none;
        }

        .cr-btn-reset {
            display: flex;
            align-items: center;
            gap: 8px;
            padding: 9px 18px;
            border-radius: 999px;
            background: #eef1f6;
            color: #475569;
            font-weight: 700;
            font-size: 0.83rem;
            border: none;
            cursor: pointer;
            text-decoration: none !important;
            transition: all 0.2s ease;
            white-space: nowrap;
        }

        .cr-btn-reset:hover {
            background: #475569;
            color: #fff;
        }

        /* --- Content Card --- */
        .cr-card {
            background: #fff;
            border-radius: 14px;
            border: 1px solid #e2e8f0;
            box-shadow: 0 2px 12px rgba(0, 0, 0, 0.04);
            margin-bottom: 20px;
            overflow: hidden;
        }

        /* Hover lift for the chart card only (matches the "dimension" treatment
           on new-ht-cases.blade.php and kidney-dhb-report.blade.php) */
        .cr-card.cr-card-chart {
            transition: transform 0.25s ease, box-shadow 0.25s ease;
        }

        .cr-card.cr-card-chart:hover {
            transform: translateY(-3px);
            box-shadow: 0 10px 26px rgba(0, 0, 0, 0.09);
        }

        .cr-card-header {
            padding: 14px 22px;
            border-bottom: 1px solid #f1f5f9;
            display: flex;
            align-items: center;
            justify-content: space-between;
        }

        /* Icon-badge + title/subtitle, matching .chart-header on the
           awareness page (resources/views/pages/awareness.blade.php) and
           .dash-card-head on the home page. Sets --cr-accent inline to
           color its own icon badge. */
        .cr-card-header-main {
            display: flex;
            align-items: flex-start;
            gap: 12px;
        }

        .cr-card-icon {
            width: 36px;
            height: 36px;
            min-width: 36px;
            border-radius: 10px;
            background: var(--cr-accent, #6366f1);
            color: white;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 0.95rem;
            box-shadow: 0 6px 14px -4px var(--cr-accent, #6366f1);
        }

        .cr-card-title {
            font-size: 0.95rem;
            font-weight: 800;
            color: #1e293b;
            margin: 0;
            line-height: 1.3;
        }

        .cr-card-subtitle {
            font-size: 0.72rem;
            color: #94a3b8;
            font-weight: 600;
            margin-top: 2px;
        }

        /* --- Legend Bar --- */
        .cr-legend-bar {
            padding: 8px 22px;
            background: #f8fafc;
            border-bottom: 1px solid #f1f5f9;
            display: flex;
            flex-wrap: wrap;
            gap: 14px;
            align-items: center;
            font-size: 0.76rem;
            color: #475569;
            font-weight: 600;
        }

        .cr-legend-dot {
            width: 10px;
            height: 10px;
            border-radius: 50%;
            display: inline-block;
        }

        .cr-legend-item {
            display: flex;
            align-items: center;
            gap: 5px;
        }

        /* --- Table --- */
        .cr-table {
            font-size: 0.8rem;
            margin: 0;
        }

        .cr-table thead th {
            background: #f8fafc;
            color: #475569;
            font-weight: 800;
            border-bottom: 1px solid #e2e8f0;
            border-top: none;
            padding: 10px 12px;
            white-space: nowrap;
            text-align: center;
            font-size: 0.78rem;
        }

        .cr-table thead th.th-left {
            text-align: left;
        }

        .cr-table tbody td {
            vertical-align: middle;
            padding: 10px 12px;
            border-bottom: 1px solid #f1f5f9;
            border-top: none;
            text-align: center;
        }

        .cr-table tbody td.td-left {
            text-align: left;
        }

        .cr-table tbody tr:hover {
            background: #fafbfc;
        }

        /* --- Milestone Cell --- */
        .ms-cell {
            display: inline-flex;
            position: relative;
            align-items: center;
            justify-content: center;
            width: 32px;
            height: 32px;
        }

        .ms-empty {
            width: 22px;
            height: 22px;
            border-radius: 50%;
            border: 2px dashed #e2e8f0;
        }

        .ms-q-tag {
            position: absolute;
            top: 0;
            right: 0;
            width: 15px;
            height: 15px;
            border-radius: 50%;
            color: white;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 0.55rem;
            font-weight: 900;
            border: 2px solid white;
            box-shadow: 0 1px 3px rgba(0, 0, 0, 0.15);
        }

        /* --- Status Badge --- */
        .badge-complete {
            background: #d1fae5;
            color: #047857;
            border: 1.5px solid #6ee7b7;
            border-radius: 20px;
            padding: 4px 12px;
            font-size: 0.7rem;
            font-weight: 700;
            white-space: nowrap;
        }

        .badge-pending {
            background: #f1f5f9;
            color: #64748b;
            border: 1.5px solid #e2e8f0;
            border-radius: 20px;
            padding: 4px 12px;
            font-size: 0.7rem;
            font-weight: 700;
            white-space: nowrap;
        }

        .badge-inprogress {
            background: #ffefff; /* fallback */
            background: #ffedd5;
            color: #ea580c;
            border: 1.5px solid #fdba74;
            border-radius: 20px;
            padding: 4px 12px;
            font-size: 0.7rem;
            font-weight: 700;
            white-space: nowrap;
        }

        /* --- Pagination --- */
        .cr-pagination {
            padding: 14px 22px;
            background: #f8fafc;
            border-top: 1px solid #f1f5f9;
            display: flex;
            justify-content: center;
        }

        .cr-pagination nav svg {
            width: 18px;
        }

        .cr-pagination nav>div:first-child {
            display: none;
        }

        .cr-pagination nav>div:last-child {
            display: flex;
            gap: 4px;
            align-items: center;
        }

        .cr-pagination span,
        .cr-pagination a {
            padding: 4px 10px;
            border-radius: 6px;
            border: 1px solid #e2e8f0;
            background: white;
            color: #475569;
            font-size: 0.78rem;
            font-weight: 600;
        }

        .cr-pagination .active span {
            background: #6366f1;
            color: white;
            border-color: #6366f1;
        }

        /* Data rows in the detail table are clickable - they scroll the
           page down to the ขั้นตอนดำเนินงาน card below instead of leaving
           it to be found by scrolling manually. */
        .cr-detail-row {
            cursor: pointer;
            transition: background-color 0.15s;
        }

        .cr-detail-row:hover {
            background-color: #f8fafc;
        }

        .cr-detail-row.is-selected {
            background-color: #e0f2fe;
        }

        .cr-detail-row.is-selected:hover {
            background-color: #e0f2fe;
        }

        /* --- Step Overview (ขั้นตอนดำเนินงาน 5 ข้อ) - an aggregate,
           text-curated summary of every agency's own submitted answers,
           grouped by report step. Reuses the page's existing badge-*
           status colors so the pill matches the detail table above. --- */
        .cr-step-toggle-btn {
            background: #ffedd5;
            color: #c2410c;
            border: 1.5px solid #fdba74;
            border-radius: 8px;
            padding: 7px 14px;
            font-size: 0.78rem;
            font-weight: 700;
            cursor: pointer;
            display: flex;
            align-items: center;
            gap: 6px;
            transition: background 0.15s;
        }

        .cr-step-toggle-btn:hover {
            background: #fed7aa;
        }

        .cr-note-panel {
            display: none;
            grid-template-columns: 1fr 1fr;
            gap: 16px;
            padding: 18px 22px;
            background: #f8fafc;
            border-bottom: 1px solid #f1f5f9;
        }

        .cr-note-panel.is-open {
            display: grid;
        }

        @media (max-width: 720px) {
            .cr-note-panel {
                grid-template-columns: 1fr;
            }
        }

        .cr-note-card {
            background: white;
            border-radius: 10px;
            border: 1px solid #e2e8f0;
            padding: 14px 16px;
        }

        .cr-note-card h6 {
            font-size: 0.8rem;
            font-weight: 800;
            color: #1e293b;
            margin: 0 0 8px;
            display: flex;
            align-items: center;
            gap: 6px;
        }

        .cr-step-row {
            border-bottom: 1px solid #f1f5f9;
        }

        .cr-step-row:last-child {
            border-bottom: none;
        }

        .cr-step-row-head {
            display: flex;
            align-items: center;
            gap: 12px;
            padding: 14px 22px;
            cursor: pointer;
            user-select: none;
        }

        .cr-step-row-head:hover {
            background: #f8fafc;
        }

        .cr-step-num {
            width: 26px;
            height: 26px;
            border-radius: 50%;
            background: #0ea5e9;
            color: white;
            font-weight: 800;
            font-size: 0.8rem;
            display: flex;
            align-items: center;
            justify-content: center;
            flex-shrink: 0;
        }

        .cr-step-q-tag {
            background: #e0f2fe;
            color: #0369a1;
            font-size: 0.68rem;
            font-weight: 800;
            padding: 3px 9px;
            border-radius: 6px;
            flex-shrink: 0;
            white-space: nowrap;
        }

        .cr-step-title {
            flex: 1;
            font-weight: 700;
            font-size: 0.86rem;
            color: #1e293b;
        }

        .cr-step-chevron {
            color: #94a3b8;
            transition: transform 0.2s;
            flex-shrink: 0;
        }

        .cr-step-row.is-open .cr-step-chevron {
            transform: rotate(180deg);
        }

        .cr-step-body {
            display: none;
            padding: 0 22px 18px 60px;
        }

        .cr-step-row.is-open .cr-step-body {
            display: block;
        }

        /* Every line the agency wrote is shown here in full (no cap, no
           truncation) - a list can now run long, so it scrolls internally
           past a comfortable reading height instead of stretching the
           whole page. The scrollbar is styled thin rather than hidden, so
           it's clear at a glance that there's more to read. */
        .cr-step-points {
            margin: 0;
            padding: 0 4px 0 0;
            list-style: none;
            display: flex;
            flex-direction: column;
            gap: 10px;
            max-height: 380px;
            overflow-y: auto;
        }

        .cr-step-points::-webkit-scrollbar {
            width: 6px;
        }

        .cr-step-points::-webkit-scrollbar-thumb {
            background: #cbd5e1;
            border-radius: 6px;
        }

        .cr-step-points::-webkit-scrollbar-track {
            background: transparent;
        }

        .cr-step-points li {
            position: relative;
            padding-left: 16px;
            font-size: 0.85rem;
            line-height: 1.7;
            color: #334155;
            word-break: break-word;
        }

        .cr-step-points li::before {
            content: '';
            position: absolute;
            left: 0;
            top: 0.65em;
            width: 6px;
            height: 6px;
            border-radius: 50%;
            background: #0ea5e9;
        }

        .cr-step-empty {
            font-size: 0.82rem;
            color: #94a3b8;
            font-style: italic;
        }

        .cr-step-placeholder {
            padding: 40px 22px;
            text-align: center;
            color: #94a3b8;
            font-size: 0.85rem;
            font-weight: 600;
        }

        .cr-step-placeholder i {
            display: block;
            font-size: 1.5rem;
            margin-bottom: 10px;
            color: #cbd5e1;
        }

        /* Step-overview card header - unlike the other .cr-card-header
           cards on this page (small bold title + pale subtitle), this
           card's "subtitle" IS the point once a row is clicked - it's the
           specific agency being shown - so it gets its own dark banner
           treatment (eyebrow label + large bold name), matching the
           agency-detail-panel header on kidney-dhb-report.blade.php. */
        .cr-step-panel-head {
            display: flex;
            align-items: flex-start;
            justify-content: space-between;
            gap: 14px;
            padding: 16px 22px;
            background: linear-gradient(160deg, #0c4a6e, #0284c7);
        }

        .cr-step-panel-head-main {
            display: flex;
            align-items: flex-start;
            gap: 12px;
        }

        .cr-step-panel-head-main .cr-card-icon {
            background: rgba(255, 255, 255, 0.16);
            box-shadow: none;
        }

        .cr-step-panel-eyebrow {
            font-size: 0.7rem;
            font-weight: 700;
            letter-spacing: 0.06em;
            text-transform: uppercase;
            color: #bae6fd;
            margin-bottom: 3px;
        }

        .cr-step-panel-title {
            font-size: 1.05rem;
            font-weight: 800;
            color: #ffffff;
            line-height: 1.3;
        }

        /* --- Mobile/tablet refinements (<=768px) ---
           Everything above this already reflows on its own (the chart
           + leaderboard split at 860px, the note panel grid at 720px,
           the filter bar via flex-wrap, the detail table via its own
           overflow-x:auto wrapper below) - this block only trims side
           padding that was eating too much of a phone's width and
           enlarges a couple of touch targets. No color, font-family or
           copy changes, and nothing here fires above 768px, so desktop
           and tablet-landscape rendering is untouched. */
        @media (max-width: 768px) {
            .cr-filter-card {
                padding-left: 16px;
                padding-right: 16px;
            }

            .cr-card-header,
            .cr-legend-bar,
            .cr-step-row-head {
                padding-left: 16px;
                padding-right: 16px;
            }

            /* Was 0 22px 18px 60px - the 60px left indent (lined up
               with the step-number circle above) left very little
               room for the curated text on a narrow phone. */
            .cr-step-body {
                padding: 0 16px 18px 38px;
            }

            .cr-table thead th,
            .cr-table tbody td {
                padding: 8px 8px;
            }

            /* Bigger tap targets for the filter chips and reset button
               on touch (was ~34px tall) - same colors/labels, just
               more vertical padding. */
            .cr-filter-item,
            .cr-btn-reset {
                padding-top: 12px;
                padding-bottom: 12px;
            }

            /* A wide province/agency list can add more page-number
               links than a phone's width holds - let them wrap onto a
               second line, centered, instead of overflowing past the
               card edge. */
            .cr-pagination nav > div:last-child {
                flex-wrap: wrap;
                justify-content: center;
            }
        }

        /* Smooth momentum scrolling for the detail table's horizontal
           scroll wrapper on iOS Safari - the wrapper itself already
           handles the overflow (inline style on the .table-responsive
           div below), this only makes the drag feel native. Harmless
           at any width. */
        .cr-wrap .table-responsive {
            -webkit-overflow-scrolling: touch;
        }
    </style>
@endsection

@section('content')
    <div class="cr-wrap">

        @php
            // Province leaderboard numbers below - derived from $stats,
            // which the controller already computes.
            $provincePct = collect($stats['provinces'] ?? [])->map(function ($p, $name) {
                $total = ($p['complete'] ?? 0) + ($p['in_progress'] ?? 0);
                // Percentage is the share of milestone fields actually done
                // across the province's agencies (not just the share of
                // agencies that are 100% finished), so an agency that has
                // started but not yet completed still moves its province's
                // bar instead of showing 0%.
                $totalFields = $p['total_fields'] ?? 0;
                $doneFields = $p['done_fields'] ?? 0;
                return [
                    'name' => $name,
                    'complete' => $p['complete'] ?? 0,
                    'in_progress' => $p['in_progress'] ?? 0,
                    'total' => $total,
                    'pct' => $totalFields > 0 ? round($doneFields / $totalFields * 100, 1) : 0,
                ];
            })->values();

            $provinceRanked = $provincePct->sortByDesc('pct')->values();
        @endphp

        {{-- ── ROW 2: FILTER BAR ── --}}
        <form action="{{ route('consumption-report') }}" method="GET" class="cr-filter-card">
            <div class="cr-filter-title">
                <i class="fas fa-filter"></i> ตัวกรอง
            </div>

            {{-- ปีงบประมาณ --}}
            <label class="cr-filter-item">
                <i class="fas fa-calendar-alt cr-filter-item-icon"></i>
                <span class="cr-filter-item-label">ปีงบประมาณ</span>
                <select name="fiscal_year" class="cr-select" onchange="this.form.submit()">
                    <option value="ทั้งหมด" {{ (string) $fiscalYear == 'ทั้งหมด' ? 'selected' : '' }}>ทั้งหมด</option>
                    @foreach($years as $y)
                        <option value="{{ $y }}" {{ (string) $fiscalYear == (string) $y ? 'selected' : '' }}>{{ $y }}</option>
                    @endforeach
                </select>
                <i class="fas fa-chevron-down cr-filter-chevron"></i>
            </label>

            {{-- จังหวัด --}}
            <label class="cr-filter-item">
                <i class="fas fa-map-marker-alt cr-filter-item-icon"></i>
                <span class="cr-filter-item-label">จังหวัด</span>
                <select name="province_search" class="cr-select" onchange="this.form.submit()">
                    <option value="ทั้งหมด" {{ (string) $selectedProvince == 'ทั้งหมด' ? 'selected' : '' }}>ทั้งหมด
                    </option>
                    @foreach($dbProvinces as $prov)
                        <option value="{{ $prov->province_name }}" {{ (string) $selectedProvince == $prov->province_name ? 'selected' : '' }}>{{ $prov->province_name }}
                        </option>
                    @endforeach
                </select>
                <i class="fas fa-chevron-down cr-filter-chevron"></i>
            </label>

            {{-- หน่วยงาน --}}
            <label class="cr-filter-item">
                <i class="fas fa-building cr-filter-item-icon"></i>
                <span class="cr-filter-item-label">หน่วยงาน</span>
                <select name="agency_search" class="cr-select" onchange="this.form.submit()">
                    <option value="ทั้งหมด" {{ (string) $selectedAgency == 'ทั้งหมด' ? 'selected' : '' }}>ทั้งหมด</option>
                    @foreach($dbAgencies as $ag)
                        @php
                            if ($ag->User_rank_id == 2)
                                $lb = 'สสจ.' . ($ag->province->province_name ?? '');
                            elseif ($ag->User_rank_id == 3)
                                $lb = 'สสอ.' . ($ag->district->district_name ?? '');
                            elseif ($ag->User_rank_id == 4)
                                $lb = $ag->subdistrictHospital ? $ag->subdistrictHospital->hospital_name : $ag->Con_name;
                            elseif ($ag->User_rank_id == 5)
                                $lb = $ag->hospital ? $ag->hospital->hos_name : $ag->Con_name;
                            else
                                $lb = $ag->Con_name ?: $ag->name;
                        @endphp
                        <option value="{{ $ag->id }}" {{ (string) $selectedAgency == (string) $ag->id ? 'selected' : '' }}>
                            {{ $lb }}</option>
                    @endforeach
                </select>
                <i class="fas fa-chevron-down cr-filter-chevron"></i>
            </label>

            {{-- ไตรมาส --}}
            <label class="cr-filter-item">
                <i class="fas fa-clock cr-filter-item-icon"></i>
                <span class="cr-filter-item-label">ไตรมาส</span>
                <select name="quarter" class="cr-select" onchange="this.form.submit()">
                    <option value="ทั้งหมด" {{ (string) $selectedQuarter == 'ทั้งหมด' ? 'selected' : '' }}>ทั้งหมด</option>
                    @foreach([1, 2, 3, 4] as $q)
                        <option value="{{ $q }}" {{ (string) $selectedQuarter == (string) $q ? 'selected' : '' }}>ไตรมาส
                            {{ $q }}</option>
                    @endforeach
                </select>
                <i class="fas fa-chevron-down cr-filter-chevron"></i>
            </label>

            {{-- ปุ่มล้าง --}}
            <a href="{{ route('consumption-report') }}" class="cr-btn-reset">
                <i class="fas fa-sync-alt"></i> ล้างตัวกรอง
            </a>
        </form>

        {{-- ── ROW 3: PROGRESS CHART ── --}}
        <div class="cr-card cr-card-chart">
            <div class="cr-card-header">
                <div class="cr-card-header-main" style="--cr-accent:#6366f1;">
                    <div class="cr-card-icon"><i class="fas fa-chart-bar"></i></div>
                    <div>
                        <h5 class="cr-card-title">อันดับการส่งข้อมูลรายจังหวัด</h5>
                        <div class="cr-card-subtitle">หน่วยงานที่ส่งข้อมูลครบ 4 ไตรมาส</div>
                    </div>
                </div>
                <div class="cr-card-header-pill">ปี {{ $fiscalYear }}</div>
            </div>
            <div class="cr-chart-split">
                <div class="cr-chart-pane">
                    <div style="height: 280px;">
                        <canvas id="progressChart"></canvas>
                    </div>
                </div>
                <div class="cr-leaderboard-pane">
                    @forelse ($provinceRanked as $i => $p)
                        <div class="cr-lb-row">
                            <div class="cr-lb-rank" style="background: {{ $p['pct'] >= 60 ? 'linear-gradient(135deg, #4ade80, #16a34a)' : 'linear-gradient(135deg, #fdba74, #ea580c)' }}; color: #fff; box-shadow: 0 3px 8px -1px {{ $p['pct'] >= 60 ? 'rgba(22, 163, 74, 0.5)' : 'rgba(234, 88, 12, 0.45)' }};">{{ $i + 1 }}</div>
                            <div class="cr-lb-name" title="{{ $p['name'] }}">{{ $p['name'] }}</div>
                            <div class="cr-lb-pct-track">
                                <div class="cr-lb-pct-fill" style="width: {{ $p['pct'] }}%; background: {{ $p['pct'] >= 60 ? '#22c55e' : '#fb923c' }};"></div>
                            </div>
                            <div class="cr-lb-pct-num" style="color: {{ $p['pct'] >= 60 ? '#16a34a' : '#ea580c' }};">{{ $p['pct'] }}%</div>
                        </div>
                    @empty
                        <div style="font-size: 0.8rem; color: #94a3b8; font-weight: 600;">ไม่มีข้อมูล</div>
                    @endforelse
                </div>
            </div>
        </div>

        {{-- ── ROW 4: DETAIL TABLE ── --}}
        <div class="cr-card">
            <div class="cr-card-header">
                <div class="cr-card-header-main" style="--cr-accent:#10b981;">
                    <div class="cr-card-icon"><i class="fas fa-list-check"></i></div>
                    <div>
                        <h5 class="cr-card-title">ความก้าวหน้าผลการดำเนินงาน</h5>
                        <div class="cr-card-subtitle">รายละเอียดตามหน่วยงานและไตรมาส</div>
                    </div>
                </div>
            </div>

            {{-- Legend --}}
            <div class="cr-legend-bar">
                <span style="font-weight: 700; color: #475569;">คำอธิบาย:</span>
                <span class="cr-legend-item">
                    <span style="width:18px;height:18px;border-radius:50%;background:#10b981;color:white;display:inline-flex;align-items:center;justify-content:center;font-size:0.65rem;font-weight:900;">1</span>
                    ทำในไตรมาส 1
                </span>
                <span class="cr-legend-item">
                    <span style="width:18px;height:18px;border-radius:50%;background:#3b82f6;color:white;display:inline-flex;align-items:center;justify-content:center;font-size:0.65rem;font-weight:900;">2</span>
                    ทำในไตรมาส 2
                </span>
                <span class="cr-legend-item">
                    <span style="width:18px;height:18px;border-radius:50%;background:#6366f1;color:white;display:inline-flex;align-items:center;justify-content:center;font-size:0.65rem;font-weight:900;">3</span>
                    ทำในไตรมาส 3
                </span>
                <span class="cr-legend-item">
                    <span style="width:18px;height:18px;border-radius:50%;background:#8b5cf6;color:white;display:inline-flex;align-items:center;justify-content:center;font-size:0.65rem;font-weight:900;">4</span>
                    ทำในไตรมาส 4
                </span>
                <span class="cr-legend-item" style="margin-left: 4px; color:#94a3b8;">
                    <i class="fas fa-hand-pointer mr-1"></i> คลิกหัวข้อเพื่อดูคำอธิบายเต็ม
                </span>
            </div>

            <div class="table-responsive" style="overflow-x: auto;">
                <table class="table cr-table" style="width:100%; table-layout: auto;">
                    <thead>
                        <tr>
                            <th class="th-left" style="width:90px; white-space:nowrap;">ปีงบประมาณ</th>
                            <th class="th-left" style="min-width:200px;">ชื่อหน่วยงาน</th>
                            @php
                                $milestones = [
                                    ['short' => '๑. MOU',       'full' => '๑. จัดทำบันทึกความเข้าใจ (MOU) หรือข้อตกลงความร่วมมือร่วมกับหน่วยงานเครือข่ายระดับจังหวัด'],
                                    ['short' => '๒. สำรวจ',     'full' => '๒. การสำรวจปริมาณโซเดียมในอาหารด้วยเครื่องวัดความเค็ม (Salt meter)'],
                                    ['short' => '๓. แผน',       'full' => '๓. จัดทำแผนปฏิบัติการลดการบริโภคเกลือและโซเดียมระดับจังหวัด'],
                                    ['short' => '๔. ตระหนัก',  'full' => '๔. การประเมินความตระหนักรู้ความเสี่ยงการบริโภคเกลือและโซเดียมระดับจังหวัด'],
                                    ['short' => '๕.๑ สื่อสาร', 'full' => '๕.๑ การส่งเสริมการสื่อสารความเสี่ยงต่อสุขภาพผ่านสื่อสารมวลชน'],
                                    ['short' => '๕.๒ ผลิตภัณฑ์','full' => '๕.๒ การปรับลดปริมาณเกลือและโซเดียมในผลิตภัณฑ์อาหาร'],
                                    ['short' => '๕.๓ ปรุงสุก', 'full' => '๕.๓ การปรับลดปริมาณเกลือและโซเดียมในอาหารปรุงสุกที่จำหน่าย'],
                                    ['short' => '๕.๔ สิ่งแวดล้อม','full' => '๕.๔ การปรับสิ่งแวดล้อมที่เอื้อต่อสุขภาพในโรงเรียน / โรงพยาบาล / สถานที่ทำงาน'],
                                    ['short' => '๕.๕ โรคไต',   'full' => '๕.๕ การดำเนินงานป้องกันควบคุมโรคไตในชุมชน ผ่านกลไก พชอ.'],
                                ];
                            @endphp
                            @foreach($milestones as $m)
                                <th
                                    title="{{ $m['full'] }}"
                                    onclick="showMilestone(this)"
                                    style="cursor:pointer; white-space:nowrap; font-size:0.72rem; text-align:center; padding:8px 10px;"
                                    class="clickable-th">
                                    {{ $m['short'] }}
                                    <i class="fas fa-circle-question" style="font-size:0.6rem; color:#94a3b8; margin-left:2px;"></i>
                                </th>
                            @endforeach
                            <th style="width:100px; white-space:nowrap; text-align:center;">สถานะ</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($paginatedSalt as $assessment)
                            <tr class="cr-detail-row"
                                onclick="selectStepOverviewRow(this)"
                                title="คลิกเพื่อดูขั้นตอนดำเนินงานของหน่วยงานนี้ด้านล่าง"
                                data-step-overview="{{ json_encode(['agency' => $assessment['agency'], 'fiscal_year' => $assessment['fiscal_year'], 'steps' => $assessment['step_overview'], 'problems' => $assessment['step_problems'], 'suggestions' => $assessment['step_suggestions']]) }}">
                                <td class="td-left" style="color:#64748b; font-weight:600; white-space:nowrap;">{{ $assessment['fiscal_year'] }}</td>
                                <td class="td-left" style="font-weight:700; color:#1e293b;">{{ $assessment['agency'] }}</td>
                                @foreach($assessment['items'] as $item)
                                    <td style="text-align:center; padding:8px 6px;">
                                        <div class="ms-cell" style="margin: 0 auto;">
                                            @if($item['done'])
                                                <i class="fas fa-check-circle" style="font-size:1.35rem; color:#22c55e;"></i>
                                                @php $qColor = $item['q']==1?'#10b981':($item['q']==2?'#3b82f6':($item['q']==3?'#6366f1':'#8b5cf6')); @endphp
                                                <span class="ms-q-tag" style="background:{{ $qColor }};">{{ $item['q'] }}</span>
                                            @else
                                                <i class="fas fa-check-circle" style="font-size:1.35rem; color:#cbd5e1;"></i>
                                            @endif
                                        </div>
                                    </td>
                                @endforeach
                                <td style="text-align:center;">
                                    @if($assessment['status']==='ส่งครบแล้ว')
                                        <span class="badge-complete">ส่งครบแล้ว</span>
                                    @elseif($assessment['status']==='กำลังดำเนินการ')
                                        <span class="badge-inprogress">{{ $assessment['status'] }}</span>
                                    @else
                                        <span class="badge-pending">{{ $assessment['status'] }}</span>
                                    @endif
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="12" class="text-center text-muted p-5">ไม่พบข้อมูล</td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>

            @if($paginatedSalt->hasPages())
                <div class="cr-pagination">
                    {{ $paginatedSalt->appends(request()->except('detail_page'))->links() }}
                </div>
            @endif
        </div>

        {{-- ── ROW 5: STEP OVERVIEW (ขั้นตอนดำเนินงาน 5 ข้อ) ──
             Per-agency, text-curated summary of that agency's own
             submitted answers, grouped into the 5 report steps - step 5
             folds its five sub-items together. Curation happens
             server-side in MainController::curateNarrativePoints() (split
             each answer into lines, dedupe repeated lines) and is embedded
             per row above as data-step-overview, in full - every distinct
             line the agency wrote is shown, nothing is capped or
             truncated. Clicking a row renders it here via
             selectStepOverviewRow() in extra_js - same click-to-select
             principle as the agency detail panel on kidney-dhb-report. --}}
        <div class="cr-card" id="stepOverviewCard">
            <div class="cr-step-panel-head">
                <div class="cr-step-panel-head-main">
                    <div class="cr-card-icon" style="--cr-accent:#0ea5e9;"><i class="fas fa-shoe-prints"></i></div>
                    <div>
                        <div class="cr-step-panel-eyebrow">ขั้นตอนดำเนินงาน 5 ข้อ (รายไตรมาส)</div>
                        <div class="cr-step-panel-title" id="stepOverviewSubtitle">ยังไม่ได้เลือกหน่วยงาน</div>
                    </div>
                </div>
                <button type="button" id="stepOverviewToggleBtn" class="cr-step-toggle-btn" style="display:none;" onclick="toggleStepNotes(this)">
                    <i class="fas fa-triangle-exclamation"></i> ปัญหา &amp; ข้อเสนอแนะ
                </button>
            </div>

            {{-- Nothing shows until an agency row above is clicked - see
                 selectStepOverviewRow() in extra_js. --}}
            <div id="stepOverviewPlaceholder" class="cr-step-placeholder">
                <i class="fas fa-hand-pointer"></i>
                คลิกแถวหน่วยงานในตารางด้านบน เพื่อดูขั้นตอนดำเนินงานและคำตอบที่คัดมาแล้วของหน่วยงานนั้น
            </div>

            <div id="stepOverviewBody" style="display:none;">
                <div id="crNotePanel" class="cr-note-panel"></div>
                <div id="stepOverviewSteps"></div>
            </div>
        </div>

    </div>
@endsection

@section('extra_js')
    {{-- SweetAlert2 --}}
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/chartjs-plugin-datalabels@2"></script>
    <script>
        document.addEventListener('DOMContentLoaded', function () {
            const ctx = document.getElementById('progressChart').getContext('2d');
            const stats = @json($stats);
            const provinces = Object.keys(stats.provinces);

            // Soft drop-shadow behind every bar, matching the "dimension" treatment
            // used on new-ht-cases.blade.php and kidney-dhb-report.blade.php.
            // NOTE: uses the per-dataset hooks (singular), not beforeDatasetsDraw/
            // afterDatasetsDraw (plural). The plural hooks wrap ALL datasets, which
            // runs in the same draw phase as chartjs-plugin-datalabels' own label
            // drawing - so the shadow was still active when the labels drew,
            // making the text look blurry. The singular hooks close the shadow
            // right after each dataset's bars are drawn, before any label text.
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

            Chart.register(ChartDataLabels, barShadowPlugin);

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

            // Unique color per province
            const palette = [
                '#38bdf8', '#818cf8', '#34d399', '#fb923c', '#f472b6',
                '#a78bfa', '#4ade80', '#fbbf24', '#60a5fa', '#f87171',
            ];
            const completeData = provinces.map(p => stats.provinces[p].complete);
            const inProgData   = provinces.map(p => stats.provinces[p].in_progress);
            const barColors    = provinces.map((_, i) => palette[i % palette.length]);

            new Chart(ctx, {
                type: 'bar',
                data: {
                    labels: provinces,
                    datasets: [
                        {
                            label: 'ครบ 4 ไตรมาส',
                            data: provinces.map(p => stats.provinces[p].complete),
                            backgroundColor: (context) => makeBarGradient(context, '#4ade80', '#16a34a'),
                            hoverBackgroundColor: (context) => makeBarGradient(context, '#6ee7b7', '#15803d'),
                            borderRadius: 8,
                            borderSkipped: false,
                        },
                        {
                            label: 'ยังไม่ครบ 4 ไตรมาส',
                            data: provinces.map(p => stats.provinces[p].in_progress),
                            backgroundColor: (context) => makeBarGradient(context, '#fdba74', '#ea580c'),
                            hoverBackgroundColor: (context) => makeBarGradient(context, '#fed7aa', '#c2410c'),
                            borderRadius: 8,
                            borderSkipped: false,
                        }
                    ]
                },
                options: {
                    responsive: true,
                    maintainAspectRatio: false,
                    layout: {
                        padding: {
                            top: 30
                        }
                    },
                    scales: {
                        x: {
                            stacked: true,
                            grid: { display: false },
                            ticks: {
                                font: { family: 'Sarabun', size: 12, weight: '700' },
                                color: '#475569'
                            }
                        },
                        y: {
                            stacked: true,
                            beginAtZero: true,
                            grid: {
                                color: '#f1f5f9',
                                drawBorder: false
                            },
                            ticks: {
                                stepSize: 1,
                                font: { family: 'Sarabun', size: 11 },
                                color: '#94a3b8'
                            }
                        }
                    },
                    plugins: {
                        legend: {
                            display: true,
                            position: 'bottom',
                            labels: {
                                font: { family: 'Sarabun', size: 11 },
                                usePointStyle: true,
                                pointStyle: 'circle'
                            }
                        },
                        datalabels: {
                            color: '#ffffff',
                            // Font is a function (re-evaluated on every redraw,
                            // including the automatic one Chart.js runs on
                            // window resize since responsive:true is set)
                            // so the label shrinks to fit a narrow phone-width
                            // bar instead of spilling past its edges - same
                            // family/weight/style, only the size adapts.
                            font: () => ({
                                style: 'italic',
                                weight: '600',
                                size: window.innerWidth <= 480 ? 8 : 11,
                                family: 'Sarabun'
                            }),
                            formatter: v => v > 0 ? v + ' หน่วยงาน' : ''
                        },
                        tooltip: {
                            enabled: true,
                            titleFont: { family: 'Sarabun' },
                            bodyFont: { family: 'Sarabun' },
                            backgroundColor: 'rgba(30, 41, 59, 0.9)',
                            padding: 10,
                            displayColors: false,
                            callbacks: {
                                label: function(context) {
                                    return context.dataset.label + ': ' + context.parsed.y + ' หน่วยงาน';
                                }
                            }
                        }
                    }
                }
            });
        });

        function revealStepOverview() {
            const placeholder = document.getElementById('stepOverviewPlaceholder');
            const body = document.getElementById('stepOverviewBody');
            const btn = document.getElementById('stepOverviewToggleBtn');
            if (placeholder) placeholder.style.display = 'none';
            if (body) body.style.display = '';
            if (btn) btn.style.display = '';
        }

        // Reverse of revealStepOverview() - back to the placeholder, used
        // when the open row is clicked again to close the panel.
        function hideStepOverview() {
            const placeholder = document.getElementById('stepOverviewPlaceholder');
            const body = document.getElementById('stepOverviewBody');
            const btn = document.getElementById('stepOverviewToggleBtn');
            if (body) body.style.display = 'none';
            if (placeholder) placeholder.style.display = '';
            if (btn) btn.style.display = 'none';
        }

        // Curated text (agency/problems/suggestions) is free-form content
        // the agency typed in, and is inserted below via innerHTML - escape
        // it first. Same pattern as kidney-dhb-report.blade.php.
        function escapeHtml(str) {
            const div = document.createElement('div');
            div.textContent = str == null ? '' : String(str);
            return div.innerHTML;
        }

        // Render a { items: string[] } curated bundle (already split and
        // deduped server-side by curateNarrativePoints(), with every
        // distinct line shown in full - nothing capped or truncated) as
        // <li> markup, or a muted placeholder line when the agency hasn't
        // submitted anything for it yet.
        function renderCuratedPoints(curated) {
            const items = (curated && curated.items) || [];
            if (items.length === 0) {
                return '<li class="cr-step-empty">ยังไม่มีข้อมูล</li>';
            }
            return items.map(function (p) {
                return '<li>' + escapeHtml(p) + '</li>';
            }).join('');
        }

        let selectedStepOverviewRow = null;

        // Per-agency master-detail: clicking a detail-table row parses its
        // own data-step-overview JSON (already curated server-side, one
        // bundle per row - no extra request) and rebuilds the step-overview
        // card below to show that specific agency's own data. Same
        // click-to-select principle as selectAgencyRow() on
        // kidney-dhb-report.blade.php.
        // Clicking a row fills the step-overview panel below with that
        // agency's data. Clicking the SAME row again closes it back to
        // the placeholder, instead of just re-rendering the same data -
        // a proper open/close toggle.
        function resetStepOverviewPanel() {
            selectedStepOverviewRow.classList.remove('is-selected');
            selectedStepOverviewRow = null;
            const subtitle = document.getElementById('stepOverviewSubtitle');
            if (subtitle) subtitle.textContent = 'ยังไม่ได้เลือกหน่วยงาน';
            const notePanel = document.getElementById('crNotePanel');
            if (notePanel) {
                notePanel.innerHTML = '';
                notePanel.classList.remove('is-open');
            }
            const stepsContainer = document.getElementById('stepOverviewSteps');
            if (stepsContainer) stepsContainer.innerHTML = '';
            hideStepOverview();
        }

        function selectStepOverviewRow(trEl) {
            if (selectedStepOverviewRow === trEl) {
                resetStepOverviewPanel();
                return;
            }

            let data = {};
            try {
                data = JSON.parse(trEl.getAttribute('data-step-overview') || '{}');
            } catch (e) {
                data = {};
            }

            if (selectedStepOverviewRow) selectedStepOverviewRow.classList.remove('is-selected');
            trEl.classList.add('is-selected');
            selectedStepOverviewRow = trEl;

            const subtitle = document.getElementById('stepOverviewSubtitle');
            if (subtitle) {
                subtitle.textContent = (data.agency || '') + (data.fiscal_year ? ' · ปีงบประมาณ ' + data.fiscal_year : '');
            }

            const steps = data.steps || [];
            let stepsHtml = '';
            steps.forEach(function (step, idx) {
                const statusClass = 'badge-' + (step.status_class || 'pending');
                // q_label now comes from the agency's real submitted data
                // (see buildStepOverviewRows() server-side) rather than a
                // fixed per-step value, so a step with nothing filled in
                // yet has no quarter to show - skip the pill instead of
                // rendering it empty.
                const qTagHtml = step.q_label
                    ? '<div class="cr-step-q-tag">' + escapeHtml(step.q_label) + '</div>'
                    : '';
                stepsHtml += '<div class="cr-step-row is-open">'
                    + '<div class="cr-step-row-head" onclick="toggleStepRow(this)">'
                    + '<div class="cr-step-num">' + escapeHtml(step.num) + '</div>'
                    + qTagHtml
                    + '<div class="cr-step-title">' + escapeHtml(step.title || '') + '</div>'
                    + '<span class="' + statusClass + '">' + escapeHtml(step.status_label || '') + '</span>'
                    + '<i class="fas fa-chevron-down cr-step-chevron"></i>'
                    + '</div>'
                    + '<div class="cr-step-body">'
                    + '<ul class="cr-step-points">' + renderCuratedPoints(step.curated) + '</ul>'
                    + '</div>'
                    + '</div>';
            });
            const stepsContainer = document.getElementById('stepOverviewSteps');
            if (stepsContainer) stepsContainer.innerHTML = stepsHtml;

            const notePanel = document.getElementById('crNotePanel');
            if (notePanel) {
                notePanel.innerHTML = '<div class="cr-note-card">'
                    + '<h6><i class="fas fa-triangle-exclamation" style="color:#c2410c;"></i>ปัญหา/อุปสรรค</h6>'
                    + '<ul class="cr-step-points">' + renderCuratedPoints(data.problems) + '</ul>'
                    + '</div>'
                    + '<div class="cr-note-card">'
                    + '<h6><i class="fas fa-lightbulb" style="color:#0ea5e9;"></i>ข้อเสนอแนะ/โอกาสพัฒนา</h6>'
                    + '<ul class="cr-step-points">' + renderCuratedPoints(data.suggestions) + '</ul>'
                    + '</div>';
            }

            revealStepOverview();
            const card = document.getElementById('stepOverviewCard');
            if (card) card.scrollIntoView({ behavior: 'auto', block: 'start' });
        }

        function toggleStepRow(el) {
            const row = el.closest('.cr-step-row');
            if (row) row.classList.toggle('is-open');
        }

        function toggleStepNotes(btn) {
            const panel = document.getElementById('crNotePanel');
            if (panel) panel.classList.toggle('is-open');
        }

        function showMilestone(el) {
            const full = el.getAttribute('title') || '';
            // Extract just the short label from the th text (strip the icon text)
            const short = el.getAttribute('data-short') || el.childNodes[0]?.textContent?.trim() || '';
            Swal.fire({
                title: '<div style="font-family:\'Sarabun\'; font-weight:800; font-size:1rem; color:#1e293b;">' + short + '</div>',
                html:  '<div style="font-family:\'Sarabun\'; text-align:left; line-height:1.9; font-size:0.95rem; color:#475569;">' + full + '</div>',
                icon: 'info',
                confirmButtonText: 'รับทราบ',
                confirmButtonColor: '#6366f1',
                width: '520px',
                customClass: { popup: 'swal-sarabun' }
            });
        }
    </script>
@endsection
