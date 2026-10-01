@extends('layouts.layout')

@section('title', 'การประเมินความตระหนักรู้ - Salt & Sodium Smart Monitor')
@section('header_title', 'การประเมินความตระหนักรู้')
@section('header_subtitle', 'ผลการประเมินและระดับความรู้พฤติกรรมสุขภาพ')

@section('extra_css')
    <!-- Bootstrap 4 -->
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@4.6.2/dist/css/bootstrap.min.css"
        integrity="sha384-xOolHFLEh07PJGoPkLv1IbcEPTNtaed2xpHsD9ESMhqIYd0nLMwNLD69Npy4HI+N" crossorigin="anonymous">

    <style>
        /* Fix container padding issues when mixing layouts */
        .container-fluid {
            padding-left: 0;
            padding-right: 0;
        }

        .chart-card {
            background: #fff;
            border-radius: 12px;
            box-shadow: 0 4px 15px rgba(0, 0, 0, 0.05);
            padding: 15px;
            /* Reduced from 20px */
            height: 100%;
            display: flex;
            flex-direction: column;
            border: none;
        }

        /* Icon-badge + title/subtitle header, matching .dash-card-head on
           the home page (resources/views/pages/home.blade.php) exactly,
           per reference screenshot. Each header sets --chart-accent inline
           to color its own icon badge. */
        .chart-header {
            display: flex;
            align-items: flex-start;
            gap: 12px;
            margin-bottom: 16px;
            padding-bottom: 14px;
            border-bottom: 1px solid rgba(15, 23, 42, 0.06);
            text-align: left;
        }

        .chart-header-icon {
            width: 38px;
            height: 38px;
            min-width: 38px;
            border-radius: 12px;
            background: var(--chart-accent, #ec4899);
            color: white;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 1rem;
            box-shadow: 0 6px 14px -4px var(--chart-accent, #ec4899);
        }

        .chart-header-title {
            font-family: 'Sarabun', 'TH Sarabun New', 'TH Sarabun PS', sans-serif;
            font-size: 0.98rem;
            font-weight: 800;
            color: #1e293b;
            line-height: 1.3;
        }

        .chart-header-subtitle {
            font-size: 0.78rem;
            color: #475569;
            font-weight: 700;
            margin-top: 2px;
        }

        .filter-card {
            background: #fff;
            border-radius: 12px;
            box-shadow: 0 2px 10px rgba(0, 0, 0, 0.03);
            margin-bottom: 15px;
            /* Reduced from 20px */
            padding: 15px;
            /* Reduced from 20px */
        }

        /* Single pill-shaped filter bar (matches the reference "ตัวกรอง"
           bar): a funnel-icon heading, each select inline with a thin
           divider between segments and no visible per-field box, and a
           rounded ghost "clear" pill at the end - instead of the old
           stacked label-above-box layout. */
        .awr-filter-bar {
            display: flex;
            align-items: center;
            flex-wrap: wrap;
            gap: 6px 0;
            background: #fff;
            border: 1px solid #e5e9f0;
            border-radius: 999px;
            box-shadow: 0 2px 10px rgba(0, 0, 0, 0.03);
            padding: 6px 10px 6px 20px;
            margin-bottom: 0;
        }

        .awr-filter-heading {
            display: flex;
            align-items: center;
            gap: 8px;
            color: #475569;
            font-weight: 700;
            font-size: 0.85rem;
            white-space: nowrap;
            padding-right: 18px;
            border-right: 1px solid #e2e8f0;
            margin-right: 6px;
        }

        .awr-filter-heading i {
            color: #94a3b8;
            font-size: 0.8rem;
        }

        .awr-filter-item {
            display: flex;
            align-items: center;
            padding: 0 16px;
            border-right: 1px solid #e2e8f0;
            flex: 1 1 0;
            min-width: 130px;
        }

        .awr-filter-item:last-of-type {
            border-right: none;
        }

        .awr-filter-icon {
            color: #94a3b8;
            font-size: 0.78rem;
            margin-right: 8px;
            flex-shrink: 0;
        }

        /* Static field name shown before the select's own value (e.g.
           "ปีงบประมาณ" next to "2569" / "ทั้งหมด"), so it's always clear
           which filter a box is for even once "ทั้งหมด" is selected on
           more than one of them. */
        .awr-filter-field-label {
            color: #94a3b8;
            font-weight: 600;
            font-size: 0.78rem;
            white-space: nowrap;
            margin-right: 6px;
            flex-shrink: 0;
        }

        .awr-filter-select {
            border: none;
            background-color: transparent !important;
            font-family: 'Sarabun', sans-serif;
            font-weight: 600;
            font-size: 0.85rem;
            color: #1e293b;
            width: 100%;
            height: 40px;
            cursor: pointer;
            appearance: none;
            background-image: url("data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' fill='none' viewBox='0 0 24 24' stroke='%2394a3b8'%3E%3Cpath stroke-linecap='round' stroke-linejoin='round' stroke-width='2' d='M19 9l-7 7-7-7'%3E%3C/path%3E%3C/svg%3E");
            background-repeat: no-repeat;
            background-position: right 2px center;
            background-size: 13px;
            padding-right: 20px;
        }

        .awr-filter-select:focus {
            outline: none;
        }

        .awr-filter-clear {
            display: flex;
            align-items: center;
            gap: 8px;
            background: #f1f5f9;
            color: #475569;
            border-radius: 999px;
            padding: 9px 20px;
            font-weight: 700;
            font-size: 0.85rem;
            text-decoration: none !important;
            white-space: nowrap;
            transition: all 0.2s;
            margin-left: 10px;
        }

        .awr-filter-clear:hover {
            background: #e2e8f0;
            color: #1e293b;
        }

        .awr-filter-clear i {
            font-size: 0.78rem;
        }

        @media (max-width: 900px) {
            .awr-filter-bar {
                border-radius: 18px;
                padding: 14px 16px;
            }

            .awr-filter-heading {
                border-right: none;
                width: 100%;
                padding-right: 0;
                margin-right: 0;
                margin-bottom: 6px;
            }

            .awr-filter-item {
                border-right: none;
                flex: 1 1 45%;
                min-width: 45%;
                padding: 6px 8px;
            }

            .awr-filter-clear {
                margin-left: 0;
                width: 100%;
                justify-content: center;
                margin-top: 10px;
            }
        }

        /* Headline summary strip: 4 at-a-glance stats pulled from the same
           aggregates the charts below already render (see the PHP block
           right before it in the content section) - one consolidated card
           rather than 4 separate shadowed boxes, so it reads as "the
           takeaway" sitting above the detail rather than competing with
           the chart-card grid for weight. Each item keeps its own tinted
           icon-square accent, echoing --chart-accent's per-card color
           identity used throughout this page. */
        /* Soft Dimensional Glass: the strip itself becomes a faint tri-tone
           gradient "tray" and each item floats above it as a translucent,
           frosted tile - blurred backdrop, a bright inset hairline along the
           top edge for a glass sheen, and a soft accent-tinted shadow beneath
           for lift. Depth comes from layered shadows, not a single flat one. */
        .awr-kpi-strip {
            display: flex;
            align-items: stretch;
            gap: 14px;
        }

        .awr-kpi-item {
            position: relative;
            flex: 1 1 0;
            display: flex;
            align-items: center;
            gap: 14px;
            padding: 16px 20px;
            border-radius: 16px;
            min-width: 0;
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

        .awr-kpi-item:hover {
            transform: translateY(-2px);
            box-shadow:
                0 1px 0 rgba(255, 255, 255, 0.9) inset,
                0 18px 32px -18px color-mix(in srgb, var(--kpi-accent, #6366f1) 65%, transparent),
                0 4px 10px rgba(15, 23, 42, 0.07);
        }

        .awr-kpi-icon {
            width: 44px;
            height: 44px;
            min-width: 44px;
            border-radius: 12px;
            background: linear-gradient(155deg, color-mix(in srgb, var(--kpi-accent, #6366f1) 30%, white) 0%, color-mix(in srgb, var(--kpi-accent, #6366f1) 10%, white) 100%);
            color: var(--kpi-accent, #6366f1);
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 1.05rem;
            border: 1px solid color-mix(in srgb, var(--kpi-accent, #6366f1) 25%, white);
            box-shadow:
                0 1px 0 rgba(255, 255, 255, 0.8) inset,
                0 6px 14px -8px color-mix(in srgb, var(--kpi-accent, #6366f1) 55%, transparent);
        }

        .awr-kpi-text {
            min-width: 0;
        }

        .awr-kpi-value {
            font-family: 'Sarabun', 'TH Sarabun New', 'TH Sarabun PS', sans-serif;
            font-size: 1.45rem;
            font-weight: 800;
            color: #1e293b;
            line-height: 1.2;
            white-space: nowrap;
        }

        .awr-kpi-label {
            font-size: 0.76rem;
            color: #94a3b8;
            font-weight: 600;
            margin-top: 2px;
            white-space: nowrap;
            overflow: hidden;
            text-overflow: ellipsis;
        }

        .awr-kpi-note {
            font-size: 0.7rem;
            color: #94a3b8;
            padding: 6px 20px 2px;
        }

        @media (max-width: 900px) {
            .awr-kpi-strip {
                flex-wrap: wrap;
            }

            .awr-kpi-item {
                flex: 1 1 45%;
                min-width: 45%;
                padding: 14px 12px;
            }

            .awr-kpi-value {
                font-size: 1.25rem;
            }
        }

        @media (max-width: 480px) {
            .awr-kpi-item {
                flex: 1 1 100%;
                min-width: 100%;
            }
        }

        /* Respondents-by-province donut + custom legend row (see the
           chart-card right after the KPI strip). Desktop/tablet keeps the
           original fixed 55/45 split at a fixed row height; below 768px
           there isn't enough width left for a 45%-wide legend column to
           show province names without truncating them hard, so the chart
           and legend stack instead, each taking the full card width. */
        .awr-donut-row {
            display: flex;
            align-items: center;
            justify-content: center;
            height: 300px;
            width: 100%;
            padding: 12px 0;
        }

        .awr-donut-chart {
            position: relative;
            width: 55%;
            height: 100%;
        }

        .awr-donut-legend {
            width: 45%;
            padding-left: 15px;
            padding-right: 15px;
            display: flex;
            flex-direction: column;
            justify-content: center;
            gap: 12px;
        }

        @media (max-width: 768px) {
            .awr-donut-row {
                flex-direction: column;
                height: auto;
                gap: 14px;
                padding: 8px 0 4px;
            }

            .awr-donut-chart {
                width: 100%;
                height: 220px;
            }

            .awr-donut-legend {
                width: 100%;
                padding-left: 4px;
                padding-right: 4px;
            }
        }

        /* Bigger touch targets for the filter bar's selects/clear button
           once it stacks into a single column on phones (matches the
           .awr-filter-item full-width tier added at 480px below). */
        @media (max-width: 480px) {
            .awr-filter-item {
                flex: 1 1 100%;
                min-width: 100%;
            }

            .awr-filter-select {
                height: 44px;
            }

            .awr-filter-clear {
                padding: 12px 20px;
            }
        }

        .map-container {
            height: 500px;
            width: 100%;
        }

        /* Ensure fonts match */
        body,
        h1,
        h2,
        h3,
        h4,
        h5,
        h6,
        .btn,
        .form-control {
            font-family: 'Sarabun', sans-serif !important;
        }

        .filter-select {
            background-color: #f8fafc !important;
            border: 1px solid #e2e8f0;
            border-radius: 8px;
            padding: 6px 30px 6px 12px;
            /* Reduced vertical padding */
            font-weight: 600;
            font-size: 0.85rem;
            color: #1a202c;
            width: 100%;
            height: 38px;
            /* Reduced from 42px */
            font-family: 'Sarabun', sans-serif;
            cursor: pointer;
            appearance: none;
            background-image: url("data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' fill='none' viewBox='0 0 24 24' stroke='%234a5568'%3E%3Cpath stroke-linecap='round' stroke-linejoin='round' stroke-width='2' d='M19 9l-7 7-7-7'%3E%3C/path%3E%3C/svg%3E");
            background-repeat: no-repeat;
            background-position: right 10px center;
            background-size: 14px;
        }

        .btn-clear-new {
            background-color: #718096;
            color: white;
            padding: 0 20px;
            height: 38px;
            /* Reduced from 42px */
            border-radius: 8px;
            font-weight: 700;
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 8px;
            transition: all 0.2s;
            border: none;
            cursor: pointer;
            text-decoration: none !important;
            font-size: 0.9rem;
            width: 100%;
        }

        .btn-clear-new:hover {
            background-color: #4a5568;
            color: white;
            transform: translateY(-1px);
        }

        .filter-label-new {
            color: #3b82f6 !important;
            /* Modern Blue */
            font-weight: 700 !important;
            font-size: 0.8rem !important;
            margin-bottom: 8px;
            display: flex;
            align-items: center;
            gap: 8px;
        }

        /* Diverging chart footer: legend note + "view as table" toggle */
        .chart-figure-footer {
            margin-top: 8px;
            border-top: 1px solid #f1f5f9;
            padding-top: 8px;
        }

        .chart-caption {
            font-style: italic;
            color: #94a3b8;
            font-size: 0.78rem;
            margin: 0 0 8px;
            line-height: 1.5;
        }

        .chart-table-toggle {
            cursor: pointer;
            list-style: none;
            color: #64748b;
            font-size: 0.8rem;
            font-weight: 700;
            display: inline-flex;
            align-items: center;
            gap: 6px;
            user-select: none;
            background: #f8fafc;
            border: 1px solid #eef2f7;
            padding: 6px 12px;
            border-radius: 6px;
            transition: background-color 0.15s, color 0.15s;
        }

        .chart-table-toggle::-webkit-details-marker {
            display: none;
        }

        .chart-table-toggle i {
            transition: transform 0.15s;
            font-size: 0.7rem;
        }

        details[open] > .chart-table-toggle i {
            transform: rotate(90deg);
        }

        .chart-table-toggle:hover {
            color: #334155;
            background: #eef2f7;
        }

        details[open] > .chart-table-toggle {
            color: #334155;
            background: #eef2f7;
        }

        .figure-table-wrap {
            margin-top: 10px;
            overflow-x: auto;
            overflow-y: auto;
            max-height: 420px;
            border: 1px solid #f1f5f9;
            border-radius: 8px;
        }

        table.figure-table {
            width: 100%;
            border-collapse: collapse;
            font-size: 0.82rem;
        }

        table.figure-table thead th {
            position: sticky;
            top: 0;
            z-index: 1;
            background: #f8fafc;
            color: #475569;
            font-weight: 800;
            text-align: left;
            padding: 10px 12px;
            border-bottom: 2px solid #e2e8f0;
            white-space: nowrap;
        }

        table.figure-table td {
            padding: 7px 12px;
            border-bottom: 1px solid #f1f5f9;
            color: #475569;
        }

        table.figure-table td:first-child {
            font-weight: 700;
            color: #1e293b;
            white-space: nowrap;
        }

        table.figure-table tr.group-start td {
            border-top: 1px solid #e2e8f0;
        }

        table.figure-table tr.group-start:first-child td {
            border-top: none;
        }

        table.figure-table tbody tr:nth-child(even) {
            background: #fafbfc;
        }

        /* Rows carrying a dominant share (>=20% of their group) are called
           out in blue so the biggest numbers in a table read at a glance. */
        table.figure-table tbody tr.row-highlight {
            background: #eaf2ff;
        }

        table.figure-table td:last-child,
        table.figure-table th:last-child {
            text-align: right;
            font-variant-numeric: tabular-nums;
            font-weight: 700;
            color: #1e293b;
        }

        table.figure-table tbody tr.row-highlight td:last-child {
            color: #2563eb;
        }

        table.figure-table tbody tr:hover {
            background: #f1f5f9;
        }

        table.figure-table tbody tr.row-highlight:hover {
            background: #dbeafe;
        }

        .figure-table-qbadge {
            display: inline-block;
            background: #eef2f7;
            color: #1e293b;
            font-weight: 700;
            padding: 3px 10px;
            border-radius: 6px;
        }

        /* One full-width diverging bar per question - see
           createDivergingBars() in extra_js below. */
        .divrow {
            margin-bottom: 18px;
        }

        .divrow:last-child {
            margin-bottom: 0;
        }

        .divrow-label {
            font-weight: 800;
            font-size: 0.9rem;
            color: #1e293b;
            margin-bottom: 8px;
            line-height: 1.4;
        }

        .divrow-bar {
            display: flex;
            width: 100%;
            height: 34px;
            border-radius: 6px;
            overflow: hidden;
            background: #f1f5f9;
        }

        .divrow-seg {
            display: flex;
            align-items: center;
            justify-content: center;
            overflow: hidden;
            box-sizing: border-box;
            border-right: 2px solid #fff;
        }

        .divrow-seg:last-child {
            border-right: none;
        }

        .divrow-seg span {
            display: inline-block;
            max-width: calc(100% - 8px);
            overflow: hidden;
            text-overflow: ellipsis;
            white-space: nowrap;
            color: #fff;
            font-size: 11.5px;
            font-weight: 800;
            padding: 2px 6px;
            background: rgba(15, 23, 42, 0.22);
            border-radius: 4px;
        }

        .chart-legend {
            display: flex;
            flex-wrap: wrap;
            gap: 8px 16px;
            margin-top: 14px;
            padding-top: 12px;
            border-top: 1px solid #f1f5f9;
        }

        .chart-legend-item {
            display: inline-flex;
            align-items: center;
            gap: 6px;
            font-size: 11.5px;
            font-weight: 700;
            color: #475569;
            white-space: nowrap;
            cursor: pointer;
            user-select: none;
            padding: 2px 4px;
            border-radius: 4px;
            transition: opacity 0.15s, background-color 0.15s;
        }

        .chart-legend-item:hover {
            background-color: #f1f5f9;
        }

        .chart-legend-item.is-hidden {
            opacity: 0.4;
            text-decoration: line-through;
        }

        .chart-legend-dot {
            width: 10px;
            height: 10px;
            border-radius: 50%;
            flex-shrink: 0;
        }

        /* Per-question mini legend under each diverging bar - a colored
           dot + the real answer wording(s) grouped into that segment, so
           it's clear exactly which recorded answers count as "red" vs
           "green" for THIS question (they differ question to question). */
        .divrow-legend {
            display: flex;
            flex-wrap: wrap;
            gap: 6px 16px;
            margin-top: 6px;
        }

        .divrow-legend-item {
            display: inline-flex;
            align-items: flex-start;
            gap: 6px;
            font-size: 11.5px;
            font-weight: 600;
            color: #64748b;
            max-width: 100%;
        }

        .divrow-legend-dot {
            width: 9px;
            height: 9px;
            min-width: 9px;
            border-radius: 50%;
            margin-top: 3px;
            flex-shrink: 0;
        }
    </style>
@endsection

@section('content')
    <!-- Filter Section -->
    <div class="row" style="margin-bottom: 6px;">
        <div class="col-12">
            <form action="{{ route('awareness') }}" method="GET" class="awr-filter-bar">
                <div class="awr-filter-heading">
                    <i class="fa-solid fa-filter"></i>
                    <span>ตัวกรอง</span>
                </div>

                <div class="awr-filter-item">
                    <i class="fa-solid fa-calendar-days awr-filter-icon"></i>
                    <span class="awr-filter-field-label">ปีงบประมาณ</span>
                    {{-- Defaults to the latest fiscal year with data (see
                         MainController::awareness()'s $effectiveFiscalYear)
                         rather than "ทั้งหมด" - selected here follows that
                         same effective value, not the raw querystring, so a
                         bare first visit shows the latest year pre-picked
                         instead of looking like "ทั้งหมด" is chosen while
                         the page actually only loaded one year's data. --}}
                    <select name="fiscal_year" class="awr-filter-select" onchange="this.form.submit()">
                        <option value="" {{ request()->has('fiscal_year') && request('fiscal_year') === '' ? 'selected' : '' }}>ทั้งหมด</option>
                        @foreach ($years as $year)
                            <option value="{{ $year }}"
                                {{ (string) $effectiveFiscalYear === (string) $year ? 'selected' : '' }}>
                                {{ $year }}
                            </option>
                        @endforeach
                    </select>
                </div>

                <div class="awr-filter-item">
                    <i class="fa-solid fa-map-location-dot awr-filter-icon"></i>
                    <span class="awr-filter-field-label">จังหวัด</span>
                    <select name="province" class="awr-filter-select" onchange="this.form.submit()">
                        <option value="">ทั้งหมด</option>
                        @foreach ($provinces as $prov)
                            <option value="{{ $prov }}"
                                {{ request('province') == $prov ? 'selected' : '' }}>{{ $prov }}
                            </option>
                        @endforeach
                    </select>
                </div>

                <div class="awr-filter-item">
                    <i class="fa-solid fa-location-dot awr-filter-icon"></i>
                    <span class="awr-filter-field-label">อำเภอ</span>
                    <select name="district" class="awr-filter-select" onchange="this.form.submit()">
                        <option value="">ทั้งหมด</option>
                        @foreach ($districts as $dist)
                            <option value="{{ $dist }}"
                                {{ request('district') == $dist ? 'selected' : '' }}>{{ $dist }}
                            </option>
                        @endforeach
                    </select>
                </div>

                <a href="{{ route('awareness') }}" class="awr-filter-clear">
                    <i class="fa-solid fa-rotate-left"></i>
                    <span>ล้างตัวกรอง</span>
                </a>
            </form>
        </div>
    </div>

    {{-- Headline summary strip: derives 4 at-a-glance stats from the same
         collections the charts below already receive from
         MainController::awareness() - no new queries, just a summary of
         what's already computed for this filter selection. --}}
    @php
        $kpiTotalRespondents = collect($provinceStats)->sum();
        $kpiPassTotal = collect($passFailByProvince)->sum('pass');
        $kpiFailTotal = collect($passFailByProvince)->sum('fail');
        $kpiPassRate = ($kpiPassTotal + $kpiFailTotal) > 0
            ? round($kpiPassTotal / ($kpiPassTotal + $kpiFailTotal) * 100, 1)
            : null;
        $kpiTopDistrict = collect($districtStats)->keys()->first();
        $kpiTopDistrictPct = collect($districtStats)->first();
        $kpiProvinceCoverage = collect($provinceStats)->count();
        $kpiProvinceTotal = collect($provinces)->count();
    @endphp

    <div class="row mb-4">
        <div class="col-12">
            <div class="awr-kpi-strip">
                <div class="awr-kpi-item" style="--kpi-accent:#6366f1;">
                    <div class="awr-kpi-icon"><i class="fas fa-users"></i></div>
                    <div class="awr-kpi-text">
                        <div class="awr-kpi-value">{{ number_format($kpiTotalRespondents) }}</div>
                        <div class="awr-kpi-label">ผู้ตอบแบบประเมินทั้งหมด</div>
                    </div>
                </div>
                <div class="awr-kpi-item" style="--kpi-accent:#22c55e;">
                    <div class="awr-kpi-icon"><i class="fas fa-award"></i></div>
                    <div class="awr-kpi-text">
                        <div class="awr-kpi-value">{{ $kpiPassRate !== null ? $kpiPassRate . '%' : '-' }}</div>
                        <div class="awr-kpi-label">อัตราผ่านเกณฑ์ความตระหนักรู้{{ $hasPendingCriteria ? ' *' : '' }}</div>
                    </div>
                </div>
                <div class="awr-kpi-item" style="--kpi-accent:#f59e0b;">
                    <div class="awr-kpi-icon"><i class="fas fa-trophy"></i></div>
                    <div class="awr-kpi-text">
                        <div class="awr-kpi-value">{{ $kpiTopDistrict ? ($kpiTopDistrictPct . '%') : '-' }}</div>
                        <div class="awr-kpi-label">{{ $kpiTopDistrict ? 'อำเภอผ่านเกณฑ์สูงสุด: ' . $kpiTopDistrict : 'อำเภอผ่านเกณฑ์สูงสุด' }}</div>
                    </div>
                </div>
                <div class="awr-kpi-item" style="--kpi-accent:#06b6d4;">
                    <div class="awr-kpi-icon"><i class="fas fa-map-location-dot"></i></div>
                    <div class="awr-kpi-text">
                        <div class="awr-kpi-value">{{ $kpiProvinceCoverage }}/{{ $kpiProvinceTotal }}</div>
                        <div class="awr-kpi-label">ความครอบคลุมพื้นที่ (จังหวัด)</div>
                    </div>
                </div>
            </div>
            @if ($hasPendingCriteria)
                <div class="awr-kpi-note">* ไม่รวมปีงบประมาณที่ยังไม่ได้ตั้งค่าเกณฑ์ความตระหนักรู้</div>
            @endif
        </div>
    </div>

    <!-- Demographic Charts -->
    <div class="row mb-4">
        <div class="col-lg-4 mb-4">
            <div class="chart-card">
                <div class="chart-header" style="--chart-accent:#ec4899;">
                    <div class="chart-header-icon"><i class="fas fa-map-location-dot"></i></div>
                    <div>
                        <div class="chart-header-title">ร้อยละผู้ตอบแบบประเมิน</div>
                        <div class="chart-header-subtitle">แยกรายจังหวัด</div>
                    </div>
                </div>
                {{-- Overview strip: just the 2 totals (pass/fail), not a
                     per-province breakdown - that detail already lives in
                     the "สถานะความตระหนักรู้ รายจังหวัด" card below, which
                     shares this same $kpiPassTotal/$kpiFailTotal source
                     (computed once, near the top of this view). --}}
                <div style="display: flex; align-items: center; justify-content: center; gap: 8px; margin-bottom: 10px;">
                    <span
                        style="display: inline-flex; align-items: center; gap: 5px; font-size: 12px; font-weight: 700; color: #059669; background: #ecfdf5; border: 1px solid #a7f3d0; border-radius: 999px; padding: 4px 12px;">
                        <i class="fas fa-check" style="font-size: 10px;"></i>
                        ผ่านเกณฑ์ {{ number_format($kpiPassTotal) }} คน
                    </span>
                    <span
                        style="display: inline-flex; align-items: center; gap: 5px; font-size: 12px; font-weight: 700; color: #dc2626; background: #fef2f2; border: 1px solid #fecaca; border-radius: 999px; padding: 4px 12px;">
                        <i class="fas fa-xmark" style="font-size: 10px;"></i>
                        ไม่ผ่านเกณฑ์ {{ number_format($kpiFailTotal) }} คน
                    </span>
                </div>
                <div class="awr-donut-row">
                    <div class="awr-donut-chart">
                        <canvas id="respondentsChart"></canvas>
                    </div>
                    <div id="respondentsLegend" class="awr-donut-legend">
                        <!-- Custom Legend -->
                    </div>
                </div>
                @if ($hasPendingCriteria)
                    <div class="chart-figure-footer" style="padding-top: 0;">
                        <p class="chart-caption">* ไม่รวมปีงบประมาณที่ยังไม่ได้ตั้งค่าเกณฑ์ความตระหนักรู้</p>
                    </div>
                @endif
            </div>
        </div>
        <div class="col-lg-4 mb-4">
            <div class="chart-card">
                <div class="chart-header" style="--chart-accent:#8b5cf6;">
                    <div class="chart-header-icon"><i class="fas fa-venus-mars"></i></div>
                    <div>
                        <div class="chart-header-title">สัดส่วนผู้ตอบ</div>
                        <div class="chart-header-subtitle">แยกตามเพศ</div>
                    </div>
                </div>
                <div id="genderChart" style="height: 320px; width: 100%;"></div>
            </div>
        </div>
        <div class="col-lg-4 mb-4">
            <div class="chart-card">
                <div class="chart-header" style="--chart-accent:#3b82f6;">
                    <div class="chart-header-icon"><i class="fas fa-users"></i></div>
                    <div>
                        <div class="chart-header-title">สัดส่วนผู้ตอบ</div>
                        <div class="chart-header-subtitle">แยกตามกลุ่มอายุ</div>
                    </div>
                </div>
                <div id="ageChart" style="height: 320px; width: 100%;"></div>
            </div>
        </div>
    </div>

    <!-- Dashboard Grid -->
    <div class="row mb-4">

        {{-- <div></div> --}}
        <div class="col-lg-6 mb-4">
            <div class="chart-card">
                <div class="chart-header" style="--chart-accent:#f59e0b;">
                    <div class="chart-header-icon"><i class="fas fa-graduation-cap"></i></div>
                    <div>
                        <div class="chart-header-title">สัดส่วนผู้ตอบ</div>
                        <div class="chart-header-subtitle">แยกตามระดับการศึกษา</div>
                    </div>
                </div>
                <div id="educationChart" style="height: 350px; width: 100%;"></div>
                <div class="chart-figure-footer">
                    <details>
                        <summary class="chart-table-toggle"><i class="fas fa-caret-right"></i> ดูข้อมูลตัวเลข</summary>
                        <div class="figure-table-wrap">
                            <div id="educationTable"></div>
                        </div>
                    </details>
                </div>
            </div>
        </div>

        <div class="col-lg-6 mb-4">
            <div class="chart-card">
                <div class="chart-header" style="--chart-accent:#14b8a6;">
                    <div class="chart-header-icon"><i class="fas fa-list-check"></i></div>
                    <div>
                        <div class="chart-header-title">สถานะความตระหนักรู้</div>
                        <div class="chart-header-subtitle">รายจังหวัด</div>
                    </div>
                </div>
                <div style="position: relative; height: 350px; width: 100%;">
                    @if (isset($hasPendingCriteria) && $hasPendingCriteria)
                        <div
                            style="position: absolute; top:0; left:0; width:100%; height:100%; background:rgba(255,255,255,0.7); z-index:10; display:flex; align-items:center; justify-content:center; backdrop-filter:blur(3px); border-radius:8px;">
                            <div
                                style="text-align:center; padding:20px; background:white; border-radius:12px; box-shadow:0 10px 25px rgba(0,0,0,0.1); border:1px solid #e2e8f0; max-width:80%;">
                                <i class="fas fa-clock mb-3" style="font-size:2.5rem; color:#f59e0b;"></i>
                                <h5 style="font-weight:800; color:#1e293b; margin-bottom:5px;">รอเกณฑ์การประเมินปี 2569</h5>
                                <p style="font-size:0.9rem; color:#64748b; margin:0; line-height:1.4;">ข้อมูลของปีงบประมาณ
                                    2569 จะยังไม่ถูกคำนวณในส่วนนี้ จนกว่าจะมีการกำหนดเกณฑ์ที่ชัดเจน</p>
                            </div>
                        </div>
                    @endif
                    <canvas id="passFailChart"></canvas>
                </div>
                <div class="chart-figure-footer">
                    <details>
                        <summary class="chart-table-toggle"><i class="fas fa-caret-right"></i> ดูข้อมูลตัวเลข</summary>
                        <div class="figure-table-wrap">
                            <div id="passFailTable"></div>
                        </div>
                    </details>
                </div>
            </div>
        </div>
    </div>


    <!-- Row 3: Consumption Behavior Charts (fixed panels - which question
         appears in which panel is set per fiscal year from "การประเมินความตระหนักรู้
         > ตั้งค่าแดชบอร์ด") -->
    <div class="row mb-4">
            <!-- Chart 0: Sodium Consumption Behavior -->
            <div class="col-lg-6 mb-4">
                <div class="chart-card"
                    style="box-shadow: 0 10px 15px -3px rgba(0, 0, 0, 0.05); border-radius: 16px;">
                    <div class="chart-header" style="--chart-accent:#8b5cf6;">
                        <div class="chart-header-icon"><i class="fas fa-bottle-droplet"></i></div>
                        <div>
                            <div class="chart-header-title">พฤติกรรมการบริโภคโซเดียม</div>
                            <div class="chart-header-subtitle">ภาพรวมพฤติกรรมที่พบ</div>
                        </div>
                    </div>
                    <div id="sodiumBehaviorChart" style="width: 100%; height: 350px;"></div>
                </div>
            </div>

            <!-- Chart 1: Risky Consumption (Negative) -->
            <div class="col-lg-6 mb-4">
                <div class="chart-card"
                    style="box-shadow: 0 10px 15px -3px rgba(0, 0, 0, 0.05); border-radius: 16px;">
                    <div class="chart-header" style="--chart-accent:#e74c3c;">
                        <div class="chart-header-icon"><i class="fas fa-exclamation-triangle"></i></div>
                        <div>
                            <div class="chart-header-title">พฤติกรรมการบริโภคที่เสี่ยง</div>
                            <div class="chart-header-subtitle">ความถี่การทาน</div>
                        </div>
                    </div>
                    <div id="riskChart" style="width: 100%;"></div>
                    <div class="chart-figure-footer">
                        <p class="chart-caption">แถบด้านซ้ายคือคำตอบเชิงเสี่ยง แถบด้านขวาคือคำตอบเชิงบวก
                            ความยาวของแถบแสดงสัดส่วนผู้ตอบแบบประเมิน</p>
                        <details>
                            <summary class="chart-table-toggle"><i class="fas fa-caret-right"></i> ดูข้อมูลตัวเลข</summary>
                            <div class="figure-table-wrap">
                                <div id="riskTable"></div>
                            </div>
                        </details>
                    </div>
                </div>
            </div>

            <!-- Chart 2: Seasoning & Eating Out (Negative) -->
            <div class="col-lg-6 mb-4">
                <div class="chart-card"
                    style="box-shadow: 0 10px 15px -3px rgba(0, 0, 0, 0.05); border-radius: 16px;">
                    <div class="chart-header" style="--chart-accent:#e67e22;">
                        <div class="chart-header-icon"><i class="fas fa-utensils"></i></div>
                        <div>
                            <div class="chart-header-title">พฤติกรรมการปรุงและการทานนอกบ้าน</div>
                            <div class="chart-header-subtitle">สัดส่วนพฤติกรรมที่พบ</div>
                        </div>
                    </div>
                    <div id="seasoningChart" style="width: 100%;"></div>
                    <div class="chart-figure-footer">
                        <p class="chart-caption">แถบด้านซ้ายคือคำตอบเชิงเสี่ยง แถบด้านขวาคือคำตอบเชิงบวก
                            ความยาวของแถบแสดงสัดส่วนผู้ตอบแบบประเมิน</p>
                        <details>
                            <summary class="chart-table-toggle"><i class="fas fa-caret-right"></i> ดูข้อมูลตัวเลข</summary>
                            <div class="figure-table-wrap">
                                <div id="seasoningTable"></div>
                            </div>
                        </details>
                    </div>
                </div>
            </div>

            <!-- Chart 3: Healthy Habits (Positive) -->
            <div class="col-lg-6 mb-4">
                <div class="chart-card"
                    style="box-shadow: 0 10px 15px -3px rgba(0, 0, 0, 0.05); border-radius: 16px;">
                    <div class="chart-header" style="--chart-accent:#3498db;">
                        <div class="chart-header-icon"><i class="fas fa-heartbeat"></i></div>
                        <div>
                            <div class="chart-header-title">พฤติกรรมสุขภาพและการสั่งอาหาร</div>
                            <div class="chart-header-subtitle">สัดส่วนพฤติกรรมที่พบ</div>
                        </div>
                    </div>
                    <div id="healthyHabitChart" style="width: 100%;"></div>
                    <div class="chart-figure-footer">
                        <p class="chart-caption">แถบด้านซ้ายคือคำตอบเชิงเสี่ยง แถบด้านขวาคือคำตอบเชิงบวก
                            ความยาวของแถบแสดงสัดส่วนผู้ตอบแบบประเมิน</p>
                        <details>
                            <summary class="chart-table-toggle"><i class="fas fa-caret-right"></i> ดูข้อมูลตัวเลข</summary>
                            <div class="figure-table-wrap">
                                <div id="healthyHabitTable"></div>
                            </div>
                        </details>
                    </div>
                </div>
            </div>

            <!-- Chart 4: Awareness & Mindset (Positive) -->
            <div class="col-lg-6 mb-4">
                <div class="chart-card"
                    style="box-shadow: 0 10px 15px -3px rgba(0, 0, 0, 0.05); border-radius: 16px;">
                    <div class="chart-header" style="--chart-accent:#2ecc71;">
                        <div class="chart-header-icon"><i class="fas fa-brain"></i></div>
                        <div>
                            <div class="chart-header-title">ความตระหนักรู้ ทัศนคติ และความพยายาม</div>
                            <div class="chart-header-subtitle">สัดส่วนพฤติกรรมที่พบ</div>
                        </div>
                    </div>
                    <div id="mindsetChart" style="width: 100%;"></div>
                    <div class="chart-figure-footer">
                        <p class="chart-caption">แถบด้านซ้ายคือคำตอบเชิงเสี่ยง แถบด้านขวาคือคำตอบเชิงบวก
                            ความยาวของแถบแสดงสัดส่วนผู้ตอบแบบประเมิน</p>
                        <details>
                            <summary class="chart-table-toggle"><i class="fas fa-caret-right"></i> ดูข้อมูลตัวเลข</summary>
                            <div class="figure-table-wrap">
                                <div id="mindsetTable"></div>
                            </div>
                        </details>
                    </div>
                </div>
            </div>
        </div>

    <!-- Data Table Card -->
@endsection

@section('extra_js')
    <!-- Highmaps -->
    <script src="{{ asset('vendor/highcharts/highmaps.js') }}"></script>
    <script src="{{ asset('vendor/highcharts/th-all.js') }}"></script>

    <!-- Chart.js -->
    <script src="{{ asset('vendor/chartjs/chart.min.js') }}"></script>
    <!-- ChartJS Data Labels Plugin -->
    <script src="{{ asset('vendor/chartjs/chartjs-plugin-datalabels.min.js') }}"></script>

    <script>
        document.addEventListener('DOMContentLoaded', function() {
            // --- Global Settings for "Beautiful" Thai Fonts ---
            // 1. Chart.js Global Configuration
            if (typeof Chart !== 'undefined') {
                Chart.defaults.font.family = "'Sarabun', sans-serif";
                Chart.defaults.color = '#4a5568';
                Chart.defaults.font.size = 13;
            }

            // 2. Highcharts Global Configuration
            if (typeof Highcharts !== 'undefined') {
                Highcharts.setOptions({
                    lang: {
                        thousandsSep: ','
                    },
                    chart: {
                        style: {
                            fontFamily: "'Sarabun', sans-serif"
                        }
                    }
                });
            }

            // --- Shared "depth" helpers, used by every pie/donut/bar chart
            //     on this page instead of flat fills, so they all share the
            //     same light-to-dark shine recipe. ---
            // Lightens (positive percent) or darkens (negative percent) a
            // hex color by the given amount.
            const shadeHexColor = (hex, percent) => {
                hex = hex.replace('#', '');
                if (hex.length === 3) hex = hex.split('').map(c => c + c).join('');
                const num = parseInt(hex, 16);
                const amt = Math.round(2.55 * percent);
                let r = Math.max(Math.min((num >> 16) + amt, 255), 0);
                let g = Math.max(Math.min(((num >> 8) & 0x00FF) + amt, 255), 0);
                let b = Math.max(Math.min((num & 0x0000FF) + amt, 255), 0);
                return '#' + (0x1000000 + r * 0x10000 + g * 0x100 + b).toString(16).slice(1);
            };

            // Blends two hex colors: t=0 -> hexA, t=1 -> hexB. Used for the
            // education chart's "color follows the NUMBER, not the category"
            // gradient below - a deliberate, explicitly requested exception
            // to this page's usual rule (color keyed by label/entity), so
            // the bar with the most respondents reads as the most intense
            // and the smallest as the palest, regardless of which
            // attainment level happens to be biggest this year.
            const interpolateHexColor = (hexA, hexB, t) => {
                hexA = hexA.replace('#', '');
                hexB = hexB.replace('#', '');
                const a = parseInt(hexA, 16),
                    b = parseInt(hexB, 16);
                const ar = (a >> 16) & 255,
                    ag = (a >> 8) & 255,
                    ab = a & 255;
                const br = (b >> 16) & 255,
                    bg = (b >> 8) & 255,
                    bb = b & 255;
                const r = Math.round(ar + (br - ar) * t);
                const g = Math.round(ag + (bg - ag) * t);
                const bch = Math.round(ab + (bb - ab) * t);
                return '#' + (0x1000000 + r * 0x10000 + g * 0x100 + bch).toString(16).slice(1);
            };

            // Soft top->bottom gradient fill (light at the top of the
            // chart area, richer toward the bottom) - reused for the
            // stacked bar chart and the province donut below.
            const makeVerticalGradient = (ctx, chartArea, topColor, bottomColor) => {
                if (!chartArea) return topColor;
                const gradient = ctx.createLinearGradient(0, chartArea.top, 0, chartArea.bottom);
                gradient.addColorStop(0, topColor);
                gradient.addColorStop(1, bottomColor);
                return gradient;
            };

            // --- Data from Controller ---
            const provinceStats = {!! json_encode($provinceStats) !!};
            const passFailByProvince = {!! json_encode($passFailByProvince) !!};
            const districtStats = {!! json_encode($districtStats) !!};
            const genderStats = {!! json_encode($genderStats) !!};
            const ageStatsRaw = {!! json_encode($ageStats) !!};
            const ageOrder = ['ต่ำกว่า 20 ปี', '20 - 35 ปี', '36 - 50 ปี', '51 - 60 ปี', 'มากกว่า 60 ปี', 'อื่นๆ'];
            const ageStats = {};
            ageOrder.forEach(key => {
                if (ageStatsRaw[key] !== undefined) {
                    ageStats[key + ' '] = ageStatsRaw[key];
                }
            });

            const educationStats = {!! json_encode($educationStats) !!};

            // Colors - assigned by what each chart's data actually IS, not by
            // whatever order the database happens to return rows in or by
            // on-screen sort position. A color keyed by array index silently
            // repaints itself onto a different category the moment a GROUP
            // BY returns rows in a different order (no ORDER BY is applied
            // to any of these breakdowns) or a bar chart re-sorts by value -
            // every lookup below is by the category's own label instead, so
            // the same label always wears the same color.
            const NEUTRAL_FALLBACK = '#94a3b8';

            // เพศ: identity (2 fixed values) - nominal categorical, arbitrary
            // fixed order.
            const genderColorMap = {
                'ชาย': '#3498db',
                'หญิง': '#e84393'
            };

            // กลุ่มอายุ: age bands are ORDINAL - a monotone light->dark blue
            // ramp is the "correct" encoding for that (validated with the
            // dataviz skill's --ordinal check, and used elsewhere on this
            // page). On a PIE specifically, though, that's a real trade-off:
            // a pie leans on hue to tell wedges apart at a glance (there's
            // no shared baseline the way a bar's length gives you), so 5
            // shades of one blue read as "too similar" exactly where a
            // reader wants "obviously different slices" - which is the
            // readability problem asked to be fixed here.
            //
            // Switched to 5 distinct hues from the dataviz skill's own
            // documented categorical theme (never eyeballed hex values),
            // kept in a FIXED slot order so a color never gets reassigned
            // if the data changes - and still assigned in age order (not by
            // size), so the ring itself still reads young -> old going
            // around. Order now lives in position, not in the ramp.
            //
            // Honesty about the trade-off: the categorical checks guarantee
            // full colorblind-safe separation for at most 3 slots at once
            // (`references/palette.md`); past that, some pairs land in the
            // "legal only with secondary encoding" band (validated: worst
            // pair ΔE 6.1 deutan / 12.9 normal-vision, between the magenta
            // and orange/aqua slots). That's why every slice keeps its own
            // visible label (name + %) right on the chart, not just a
            // legend - the mitigation the dataviz skill calls for whenever
            // a palette goes past the guaranteed-safe count.
            //
            // อื่นๆ still gets the neutral gray, not a 6th hue - it's the
            // catch-all bucket, not a real step in the age progression, same
            // convention used for it everywhere else on this page.
            // (ageStats' keys carry a trailing space - see the ageOrder
            // remap above.)
            const ageColorMap = {
                'ต่ำกว่า 20 ปี ': '#2a78d6', // categorical slot 1 - blue
                '20 - 35 ปี ': '#eb6834', // slot 2 - orange
                '36 - 50 ปี ': '#1baf7a', // slot 3 - aqua
                '51 - 60 ปี ': '#eda100', // slot 4 - yellow
                'มากกว่า 60 ปี ': '#e87ba4', // slot 5 - magenta
                'อื่นๆ ': NEUTRAL_FALLBACK
            };

            // ระดับการศึกษา: also ordinal (attainment low->high) - a second,
            // distinct hue (teal) from the age ramp above so the two
            // adjacent ordinal sections still read as different charts at a
            // glance, each internally monotone light->dark. "สูงกว่าปริญญาโท"
            // is a separate label some survey years used for the same top
            // tier as "ปริญญาโท/สูงกว่า" (a data-entry inconsistency worth
            // reviewing upstream) - both get their own step here so neither
            // silently falls back to gray.
            const educationColorMap = {
                'ไม่เคยศึกษา/ไม่ได้เรียนหนังสือ': '#5cc6b3',
                'ประถมศึกษา': '#38ad98',
                'มัธยมศึกษา': '#1f9680',
                'ปวช./ปวส./อนุปริญญา': '#167c69',
                'ปริญญาตรี': '#106453',
                'ปริญญาโท/สูงกว่า': '#0a4c3f',
                'สูงกว่าปริญญาโท': '#06342b'
            };

            // Natural attainment order (low -> high), same idea as ageOrder
            // above - the bar chart is sorted by THIS, not by count, so the
            // light->dark teal ramp actually reads as "the gradient IS the
            // order" instead of jumping around whenever the biggest group
            // changes. Any label the survey ever produces outside this list
            // (a data-entry variant, say) still shows up - it's appended
            // after the known levels rather than silently dropped.
            const educationOrder = [
                'ไม่เคยศึกษา/ไม่ได้เรียนหนังสือ',
                'ประถมศึกษา',
                'มัธยมศึกษา',
                'ปวช./ปวส./อนุปริญญา',
                'ปริญญาตรี',
                'ปริญญาโท/สูงกว่า',
                'สูงกว่าปริญญาโท'
            ];

            // จังหวัด: identity (5 known provinces) - nominal categorical,
            // re-validated with the dataviz skill's palette checker (the
            // previous #ffb74d/#f1c40f pair failed CVD separation, ΔE 2.7
            // deutan / 5.5 normal-vision - too close to tell apart).
            const provinceColorMap = {
                'อุบลราชธานี': '#1abc9c',
                'ศรีสะเกษ': '#9b59b6',
                'ยโสธร': '#e67e22',
                'อำนาจเจริญ': '#3498db',
                'มุกดาหาร': '#e84393'
            };

            // --- Gender ratio: a segmented ratio bar + stat rows, not a
            //     pie. A 2-slice pie is a flagged anti-pattern (angle
            //     judgments are harder to compare than a single bar split
            //     in two, and a 2-category composition is exactly the case
            //     a stat-style card reads faster than a chart) - this gives
            //     the same information as one glanceable bar plus the exact
            //     counts, using the same validated genderColorMap hues. ---
            const renderGenderRatio = (containerId, data, colorMap, fallback) => {
                const container = document.getElementById(containerId);
                if (!container) return;
                const entries = Object.entries(data);
                const total = entries.reduce((sum, [, v]) => sum + v, 0);

                container.innerHTML = '';
                container.style.display = 'flex';
                container.style.flexDirection = 'column';
                container.style.justifyContent = 'center';
                container.style.height = '100%';
                container.style.gap = '18px';
                container.style.padding = '4px 6px';

                const iconFor = (label) => label === 'ชาย' ? 'fa-mars' : (label === 'หญิง' ?
                    'fa-venus' : 'fa-genderless');

                // Segmented ratio bar - one glance at the split.
                const barTrack = document.createElement('div');
                barTrack.style.display = 'flex';
                barTrack.style.width = '100%';
                barTrack.style.height = '22px';
                barTrack.style.borderRadius = '11px';
                barTrack.style.overflow = 'hidden';
                barTrack.style.background = '#eef2f7';
                barTrack.style.boxShadow = '0 4px 10px rgba(15, 23, 42, 0.10)';

                entries.forEach(([label, value], i) => {
                    const pct = total > 0 ? (value / total) * 100 : 0;
                    const base = colorMap[label] || fallback;
                    const seg = document.createElement('div');
                    seg.style.width = pct + '%';
                    seg.style.background = 'linear-gradient(180deg, ' + shadeHexColor(base,
                        18) + ', ' + shadeHexColor(base, -10) + ')';
                    seg.title = label + ': ' + pct.toFixed(1) + '%';
                    if (i > 0) seg.style.borderLeft = '2px solid #fff';
                    barTrack.appendChild(seg);
                });
                container.appendChild(barTrack);

                // One stat row per category - icon, label, count, and the
                // percentage as the headline figure.
                const rows = document.createElement('div');
                rows.style.display = 'flex';
                rows.style.flexDirection = 'column';
                rows.style.gap = '10px';

                entries.forEach(([label, value]) => {
                    const pct = total > 0 ? ((value / total) * 100).toFixed(1) : '0.0';
                    const base = colorMap[label] || fallback;

                    const row = document.createElement('div');
                    row.style.display = 'flex';
                    row.style.alignItems = 'center';
                    row.style.justifyContent = 'space-between';
                    row.style.padding = '10px 14px';
                    row.style.borderRadius = '12px';
                    row.style.background = 'linear-gradient(135deg, ' + base +
                        '18, ' + base + '08)';
                    row.style.border = '1px solid ' + base + '2a';

                    const left = document.createElement('div');
                    left.style.display = 'flex';
                    left.style.alignItems = 'center';
                    left.style.gap = '10px';

                    const iconChip = document.createElement('div');
                    iconChip.style.width = '32px';
                    iconChip.style.height = '32px';
                    iconChip.style.borderRadius = '50%';
                    iconChip.style.display = 'flex';
                    iconChip.style.alignItems = 'center';
                    iconChip.style.justifyContent = 'center';
                    iconChip.style.background = 'linear-gradient(135deg, ' + base +
                        '2e, ' + base + '14)';
                    iconChip.style.color = base;
                    iconChip.innerHTML = '<i class="fas ' + iconFor(label) + '"></i>';

                    const textWrap = document.createElement('div');
                    const nameEl = document.createElement('div');
                    nameEl.textContent = label;
                    nameEl.style.fontWeight = '700';
                    nameEl.style.fontSize = '13px';
                    nameEl.style.color = '#334155';
                    const countEl = document.createElement('div');
                    countEl.textContent = value.toLocaleString() + ' คน';
                    countEl.style.fontSize = '13px';
                    countEl.style.fontWeight = '700';
                    countEl.style.color = '#1e293b';
                    countEl.style.marginTop = '2px';
                    textWrap.appendChild(nameEl);
                    textWrap.appendChild(countEl);

                    left.appendChild(iconChip);
                    left.appendChild(textWrap);

                    const pctEl = document.createElement('div');
                    pctEl.textContent = pct + '%';
                    pctEl.style.fontWeight = '800';
                    pctEl.style.fontSize = '19px';
                    pctEl.style.color = base;

                    row.appendChild(left);
                    row.appendChild(pctEl);
                    rows.appendChild(row);
                });

                container.appendChild(rows);
            };

            // --- Ordinal pie chart (age bands) - back to a pie per the
            //     requested reference look (percentage on a dark pill,
            //     category name alongside it). Colors still follow the
            //     data's own job rather than copying the reference literally:
            //     age bands are ORDINAL, so instead of "one accent + gray"
            //     (right for the reference's one-channel-dominates support
            //     data) every slice keeps its validated light->dark blue
            //     ramp step, in age order - the slice colors themselves
            //     still read as the age progression, not just as 5 arbitrary
            //     wedges.
            //
            //     One label per point, not the reference's separate
            //     inside-% / outside-name pair: this vendored Highcharts
            //     build's pie module throws (getConnectorPath is undefined)
            //     the moment a point gets a SECOND data label - reproduced
            //     directly against this page's own chart instance, not
            //     specific to this data. A single HTML label combining both
            //     (the same dark percentage pill, the name underneath it)
            //     gets the reference's look without that second label. ---
            const createOrdinalPieChart = (containerId, data, colorMap, fallback) => {
                const labels = Object.keys(data);
                const chartData = labels.map(label => ({
                    name: label.trim(),
                    y: data[label],
                    color: colorMap[label] || fallback
                }));

                Highcharts.chart(containerId, {
                    chart: {
                        type: 'pie',
                        backgroundColor: 'transparent',
                        style: {
                            fontFamily: 'Sarabun'
                        }
                    },
                    title: {
                        text: ''
                    },
                    tooltip: {
                        pointFormat: '{series.name}: <b>{point.percentage:.1f}%</b> ({point.y} คน)'
                    },
                    accessibility: {
                        point: {
                            valueSuffix: '%'
                        }
                    },
                    plotOptions: {
                        pie: {
                            allowPointSelect: true,
                            cursor: 'pointer',
                            borderWidth: 2,
                            borderColor: '#fff',
                            shadow: {
                                color: 'rgba(15, 23, 42, 0.18)',
                                offsetX: 0,
                                offsetY: 4,
                                width: 6
                            },
                            states: {
                                hover: {
                                    brightness: 0.06
                                }
                            },
                            showInLegend: false,
                            // One connected label per slice: the percentage
                            // on a dark pill (readable over any slice color,
                            // light or dark) with the category name right
                            // underneath it, same visual idea as the
                            // reference's dark percentage badge.
                            dataLabels: {
                                enabled: true,
                                useHTML: true,
                                distance: 16,
                                format: '<div style="text-align:center; line-height:1.25;">' +
                                    '<span style="display:inline-block; background:{point.color}; color:#fff; font-weight:700; font-size:12px; padding:2px 7px; border-radius:5px; white-space:nowrap; text-shadow:0 1px 2px rgba(15,23,42,0.35);">{point.percentage:.0f}%</span>' +
                                    '<br/><span style="color:#475569; font-weight:600; font-size:11px; white-space:nowrap;">{point.name}</span>' +
                                    '<br/><span style="color:#334155; font-weight:800; font-size:11px; white-space:nowrap;">{point.y:,.0f} คน</span>' +
                                    '</div>',
                                connectorColor: '#cbd5e1',
                                style: {
                                    textOutline: 'none'
                                }
                            }
                        }
                    },
                    series: [{
                        name: 'สัดส่วน',
                        colorByPoint: true,
                        data: chartData
                    }],
                    credits: {
                        enabled: false
                    }
                });
            };

            // --- Bar Chart Helper for Education (Horizontal) ---
            const createEducationBarChart = (containerId, data, colorMap, fallback, order) => {
                // Sorted purely by COUNT now (largest first), not by the
                // natural attainment order - Highcharts' horizontal 'bar'
                // type renders array index 0 at the TOP and the last index
                // at the BOTTOM, so "largest value first" here is what
                // makes the chart read smallest-at-bottom ->
                // largest-at-top going up, as requested.
                const sortedData = Object.entries(data).sort(([, valA], [, valB]) => valB - valA);

                const labels = sortedData.map(([label]) => label);
                const values = sortedData.map(([, value]) => value);
                const total = values.reduce((a, b) => a + b, 0);

                // Per the reference layout: rounded "pill" bars with the
                // count shown in a floating white badge at the bar's tip,
                // and - per explicit request - color intensity that follows
                // the NUMBER itself (lightest for the smallest group,
                // deepest for the largest), not the attainment order. Same
                // teal family already used for this chart (light/dark
                // endpoints taken from educationColorMap's own low/high
                // steps), just re-purposed as a magnitude ramp instead of
                // an ordinal one.
                const maxVal = Math.max(...values, 0);
                const minVal = Math.min(...values, 0);
                const range = maxVal - minVal;
                // Deep-navy ramp (per explicit request, replacing the
                // earlier mint/teal family): a clear, saturated light blue
                // at the low end through to a rich dark navy at the high
                // end - stopping well short of near-black, which is what
                // read as "harsh"/not smooth on the biggest bars before. A
                // gentle sqrt easing on t also keeps large-but-different
                // values (e.g. 4,915 vs 4,655) visibly distinct instead of
                // both clipping to the same near-max shade. Each bar's own
                // sheen is still a soft 3-stop light -> base -> light-dark
                // blend (smaller deltas than before) for a smoother satin
                // look instead of one hard light/dark split. The gradient
                // still follows the NUMBER itself (lightest for the
                // smallest group, deepest for the largest), exactly as
                // before - only the hue family changed.
                const rampLight = '#93c5fd';
                const rampDark = '#1e3a8a';
                const colorPalette = values.map(v => {
                    const tLinear = range > 0 ? (v - minVal) / range : 1;
                    const t = Math.sqrt(tLinear);
                    const base = interpolateHexColor(rampLight, rampDark, t);
                    return {
                        linearGradient: {
                            x1: 0,
                            y1: 0,
                            x2: 1,
                            y2: 0
                        },
                        stops: [
                            [0, shadeHexColor(base, 10)],
                            [0.55, base],
                            [1, shadeHexColor(base, -8)]
                        ]
                    };
                });

                Highcharts.chart(containerId, {
                    chart: {
                        type: 'bar',
                        backgroundColor: 'transparent',
                        style: {
                            fontFamily: 'Sarabun'
                        }
                    },
                    title: {
                        text: ''
                    },
                    xAxis: {
                        categories: labels,
                        labels: {
                            style: {
                                fontSize: '12px',
                                fontWeight: 'bold',
                                color: '#333'
                            }
                        },
                        lineColor: '#ccd6eb',
                        lineWidth: 1
                    },
                    yAxis: {
                        title: {
                            text: ''
                        },
                        gridLineColor: '#f3f3f3',
                        labels: {
                            enabled: false
                        }, // Hide Y axis labels like in ref image
                        // A little headroom past the tallest bar so its
                        // floating label badge never gets clipped against
                        // the plot edge.
                        max: maxVal > 0 ? maxVal * 1.18 : null
                    },
                    legend: {
                        enabled: false
                    },
                    tooltip: {
                        formatter: function() {
                            const pct = total > 0 ? ((this.y / total) * 100).toFixed(1) : 0;
                            return '<b>' + this.x + '</b><br/>จำนวน: <b>' + Highcharts
                                .numberFormat(this.y, 0) + ' คน</b> (' + pct + '%)';
                        }
                    },
                    plotOptions: {
                        bar: {
                            borderRadius: 14,
                            pointWidth: 26,
                            colorByPoint: true,
                            colors: colorPalette,
                            dataLabels: {
                                // Floats just past the tip of the bar
                                // (never inside it) so a short bar's badge
                                // can't collide with the category label on
                                // the left - the same size and offset for
                                // every bar regardless of its length, which
                                // is what actually reads as "balanced"
                                // rather than a badge that's forced to
                                // overlap a tiny bar.
                                enabled: true,
                                useHTML: true,
                                align: 'left',
                                inside: false,
                                x: 8,
                                crop: false,
                                overflow: 'allow',
                                format: '<span style="display:inline-block; background:#fff; color:#0f172a; font-weight:700; font-size:12px; padding:3px 9px; border-radius:8px; box-shadow:0 2px 6px rgba(15,23,42,0.16); white-space:nowrap;">{point.y:,.0f}</span>'
                            },
                            borderWidth: 0
                        }
                    },
                    series: [{
                        name: 'จำนวน',
                        data: values
                    }],
                    credits: {
                        enabled: false
                    }
                });
            };

            renderGenderRatio('genderChart', genderStats, genderColorMap, NEUTRAL_FALLBACK);
            createOrdinalPieChart('ageChart', ageStats, ageColorMap, NEUTRAL_FALLBACK);
            createEducationBarChart('educationChart', educationStats, educationColorMap,
                NEUTRAL_FALLBACK, educationOrder);

            // Define chartsPlugins for Chart.js charts
            const chartsPlugins = [];
            if (typeof ChartDataLabels !== 'undefined') {
                chartsPlugins.push(ChartDataLabels);
            }

            // --- 2. Respondents by Province (Donut) ---
            const provLabels = Object.keys(provinceStats);
            const provValues = Object.values(provinceStats);

            // Soft shadow cast under the whole ring, so it lifts slightly
            // off the card instead of sitting flush with it (same recipe
            // as the stacked bar chart below).
            const donutRingShadow = {
                id: 'donutRingShadow',
                beforeDatasetDraw(chart) {
                    chart.ctx.save();
                    chart.ctx.shadowColor = 'rgba(15, 23, 42, 0.18)';
                    chart.ctx.shadowBlur = 10;
                    chart.ctx.shadowOffsetY = 4;
                },
                afterDatasetDraw(chart) {
                    chart.ctx.restore();
                }
            };

            new Chart(document.getElementById('respondentsChart'), {
                type: 'doughnut',
                data: {
                    labels: provLabels,
                    datasets: [{
                        data: provValues,
                        // Each slice gets a light-to-dark gradient of its
                        // own base color (instead of one flat fill) for a
                        // touch of shine/depth, plus a thin white seam
                        // between slices so they read as separate pieces.
                        backgroundColor: (context) => {
                            const {
                                chart,
                                dataIndex
                            } = context;
                            const base = provinceColorMap[provLabels[dataIndex]] ||
                                NEUTRAL_FALLBACK;
                            return makeVerticalGradient(chart.ctx, chart.chartArea, shadeHexColor(
                                base, 24), shadeHexColor(base, -12));
                        },
                        hoverBackgroundColor: (context) => {
                            const {
                                chart,
                                dataIndex
                            } = context;
                            const base = provinceColorMap[provLabels[dataIndex]] ||
                                NEUTRAL_FALLBACK;
                            return makeVerticalGradient(chart.ctx, chart.chartArea, shadeHexColor(
                                base, 32), shadeHexColor(base, -18));
                        },
                        borderWidth: 3,
                        borderColor: '#fff',
                        hoverBorderWidth: 3,
                        hoverOffset: 8
                    }]
                },
                options: {
                    responsive: true,
                    maintainAspectRatio: false,
                    cutout: '75%', // Increased cutout for cleaner look
                    plugins: {
                        legend: {
                            display: false
                        },
                        datalabels: {
                            display: false
                        } // Hide inner labels, use custom legend
                    }
                },
                plugins: [...chartsPlugins, {
                    // Center Text Plugin
                    id: 'centerText',
                    beforeDraw: function(chart) {
                        var ctx = chart.ctx;
                        var chartArea = chart.chartArea;
                        ctx.save();

                        // Calculate Total
                        var total = chart.config.data.datasets[0].data.reduce((a, b) => a + b,
                            0);

                        ctx.textAlign = "center";
                        ctx.textBaseline = "middle";

                        var meta = chart.getDatasetMeta(0);
                        if (meta.data.length === 0) return;

                        var centerX = meta.data[0].x;
                        var centerY = meta.data[0].y;

                        // 1. Draw Number (Large)
                        var fontSizeNum = (chartArea.height / 300).toFixed(2);
                        ctx.font = "800 " + (fontSizeNum * 1.8) + "em 'Sarabun'";
                        ctx.fillStyle = "#1e293b";
                        ctx.textBaseline = "bottom";
                        ctx.fillText(total.toLocaleString(), centerX, centerY - 2);

                        // 2. Draw Label (Small)
                        ctx.font = "600 " + (fontSizeNum * 0.9) + "em 'Sarabun'";
                        ctx.fillStyle = "#94a3b8";
                        ctx.textBaseline = "top";
                        ctx.fillText("คน", centerX, centerY + 5);

                        ctx.restore();
                    }
                }, {
                    // Custom HTML Legend Plugin
                    id: 'htmlLegend',
                    afterUpdate(chart, args, options) {
                        const dl = document.getElementById('respondentsLegend');
                        if (!dl) return;

                        while (dl.firstChild) {
                            dl.firstChild.remove();
                        }

                        const items = chart.options.plugins.legend.labels.generateLabels(chart);
                        const total = chart.data.datasets[0].data.reduce((a, b) => a + b, 0);

                        items.forEach(item => {
                            const li = document.createElement('div');
                            li.style.display = 'flex';
                            li.style.alignItems = 'center';
                            li.style.justifyContent = 'space-between';
                            li.style.width = '100%';
                            li.style.padding = '6px 0';
                            li.style.borderBottom = '1px dashed #f1f5f9';
                            li.style.flexWrap = 'nowrap';
                            li.style.overflow = 'hidden';

                            const leftWrapper = document.createElement('div');
                            leftWrapper.style.display = 'flex';
                            leftWrapper.style.alignItems = 'center';
                            leftWrapper.style.minWidth = '0';
                            leftWrapper.style.flex = '1';
                            leftWrapper.style.marginRight = '6px';

                            const boxSpan = document.createElement('span');
                            boxSpan.style.background = item.fillStyle;
                            boxSpan.style.display = 'inline-block';
                            boxSpan.style.height = '10px';
                            boxSpan.style.width = '10px';
                            boxSpan.style.borderRadius = '50%';
                            boxSpan.style.marginRight = '5px';
                            boxSpan.style.flexShrink = '0';

                            const textContainer = document.createElement('span');
                            textContainer.style.color = '#475569';
                            textContainer.style.fontSize = '11px';
                            textContainer.style.fontWeight = '600';
                            textContainer.innerText = item.text;
                            textContainer.style.whiteSpace = 'nowrap';
                            textContainer.style.overflow = 'hidden';
                            textContainer.style.textOverflow = 'ellipsis';
                            textContainer.style.minWidth = '0';

                            leftWrapper.appendChild(boxSpan);
                            leftWrapper.appendChild(textContainer);

                            const rightWrapper = document.createElement('div');
                            rightWrapper.style.display = 'flex';
                            rightWrapper.style.alignItems = 'center';
                            rightWrapper.style.gap = '4px';
                            rightWrapper.style.flexShrink = '0';
                            rightWrapper.style.whiteSpace = 'nowrap';

                            const countSpan = document.createElement('span');
                            countSpan.style.color = '#64748b';
                            countSpan.style.fontSize = '11px';
                            countSpan.style.fontWeight = '500';
                            countSpan.style.whiteSpace = 'nowrap';
                            const val = chart.data.datasets[0].data[item.index];
                            countSpan.innerText = val.toLocaleString();

                            const percSpan = document.createElement('span');
                            percSpan.style.color = '#1e293b';
                            percSpan.style.fontWeight = '800';
                            percSpan.style.fontSize = '12px';
                            percSpan.style.minWidth = '28px';
                            percSpan.style.textAlign = 'right';

                            const perc = total > 0 ? ((val / total) * 100).toFixed(0) : 0;
                            percSpan.innerText = perc + '%';

                            rightWrapper.appendChild(countSpan);
                            rightWrapper.appendChild(percSpan);

                            li.appendChild(leftWrapper);
                            li.appendChild(rightWrapper);
                            dl.appendChild(li);
                        });
                    }
                }, donutRingShadow]
            });


            // --- 3. Pass/Fail by Province (Stacked Bar) ---
            // Ranked by pass rate (best -> worst), not by whatever order the
            // controller's GROUP BY happened to return - a ranked bar is
            // read at a glance ("who's ahead, who needs attention"), while
            // an arbitrary order makes the reader hunt for their province
            // and do the comparison in their head.
            const pfEntries = Object.entries(passFailByProvince)
                .map(([name, v]) => {
                    const total = (v.pass || 0) + (v.fail || 0);
                    return {
                        name,
                        pass: v.pass,
                        fail: v.fail,
                        rate: total > 0 ? v.pass / total : 0
                    };
                })
                .sort((a, b) => b.rate - a.rate);
            const pfLabels = pfEntries.map(e => e.name);
            const passData = pfEntries.map(e => e.pass);
            const failData = pfEntries.map(e => e.fail);

            // makeVerticalGradient is defined once, near the top of this
            // script, and reused here (green/red family also matches the
            // success/error alert cards elsewhere in the app).

            // Casts a soft shadow under the whole stacked column (drawn
            // once, keyed off the bottom-most dataset) so each bar lifts
            // slightly off the page instead of sitting flush with it.
            const passFailBarShadow = {
                id: 'passFailBarShadow',
                beforeDatasetDraw(chart, args) {
                    if (args.index !== 0) return;
                    chart.ctx.save();
                    chart.ctx.shadowColor = 'rgba(15, 23, 42, 0.18)';
                    chart.ctx.shadowBlur = 10;
                    chart.ctx.shadowOffsetY = 4;
                },
                afterDatasetDraw(chart, args) {
                    if (args.index !== 0) return;
                    chart.ctx.restore();
                }
            };

            new Chart(document.getElementById('passFailChart'), {
                type: 'bar',
                data: {
                    labels: pfLabels,
                    datasets: [{
                            label: 'ตระหนักรู้',
                            data: passData,
                            backgroundColor: (context) => makeVerticalGradient(context.chart.ctx, context.chart.chartArea, '#6ee7b7', '#059669'),
                            hoverBackgroundColor: (context) => makeVerticalGradient(context.chart.ctx, context.chart.chartArea, '#86f0c4', '#047857'),
                            borderColor: '#fff',
                            borderWidth: { top: 3, left: 0, right: 0, bottom: 0 },
                            borderRadius: { topLeft: 0, topRight: 0, bottomLeft: 10, bottomRight: 10 },
                            borderSkipped: false,
                            stack: 'Stack 0',
                            maxBarThickness: 54,
                            // Value badge sits centered INSIDE its own
                            // green segment - a dark, near-black-green pill
                            // (not the segment's own lighter green) for
                            // readability. This used to float just above
                            // the green/red boundary instead, but that put
                            // it on a collision course with the red
                            // label's own floating position whenever the
                            // red segment was thin (they'd land at nearly
                            // the same height). Centering inside the green
                            // segment makes this label's position depend
                            // only on the green value, never on red's, so
                            // the two can no longer chase each other.
                            // clamp keeps it from spilling below the
                            // x-axis on the rare row where the green
                            // segment itself is almost zero height.
                            datalabels: {
                                backgroundColor: '#064e3b',
                                borderRadius: 6,
                                color: '#fff',
                                font: { weight: '800', size: 12 },
                                padding: { top: 4, bottom: 4, left: 8, right: 8 },
                                anchor: 'center',
                                align: 'center',
                                clamp: true
                            }
                        },
                        {
                            label: 'ไม่ตระหนัก',
                            data: failData,
                            backgroundColor: (context) => makeVerticalGradient(context.chart.ctx, context.chart.chartArea, '#fca5a5', '#dc2626'),
                            hoverBackgroundColor: (context) => makeVerticalGradient(context.chart.ctx, context.chart.chartArea, '#fdb8b8', '#c81e1e'),
                            borderRadius: { topLeft: 10, topRight: 10, bottomLeft: 0, bottomRight: 0 },
                            borderSkipped: false,
                            stack: 'Stack 0',
                            maxBarThickness: 54,
                            // Value badge floats just above the (usually
                            // much shorter) red segment instead of trying
                            // to fit inside it, in a solid red pill that
                            // matches this series' own color family.
                            datalabels: {
                                backgroundColor: '#dc2626',
                                borderRadius: 6,
                                color: '#fff',
                                font: { weight: '800', size: 12 },
                                padding: { top: 4, bottom: 4, left: 8, right: 8 },
                                anchor: 'end',
                                align: 'end',
                                offset: 6
                            }
                        }
                    ]
                },
                options: {
                    responsive: true,
                    maintainAspectRatio: false,
                    categoryPercentage: 0.58,
                    barPercentage: 0.9,
                    scales: {
                        x: {
                            stacked: true,
                            grid: {
                                display: false
                            },
                            ticks: {
                                font: {
                                    weight: '600'
                                }
                            }
                        },
                        y: {
                            stacked: true,
                            beginAtZero: true,
                            grid: {
                                color: '#eef1f6',
                                drawTicks: false
                            },
                            ticks: {
                                padding: 8
                            }
                        }
                    },
                    plugins: {
                        legend: {
                            position: 'top',
                            labels: {
                                usePointStyle: true,
                                pointStyle: 'circle',
                                boxWidth: 8,
                                boxHeight: 8,
                                padding: 16,
                                font: {
                                    weight: '600'
                                }
                            }
                        },
                        tooltip: {
                            backgroundColor: 'rgba(15, 23, 42, 0.92)',
                            padding: 12,
                            cornerRadius: 10,
                            titleFont: {
                                weight: '700'
                            },
                            bodyFont: {
                                weight: '500'
                            },
                            boxPadding: 4,
                            callbacks: {
                                afterBody: function(items) {
                                    const idx = items[0].dataIndex;
                                    const total = passData[idx] + failData[idx];
                                    if (total <= 0) return [];
                                    const pct = ((passData[idx] / total) * 100).toFixed(1);
                                    return [`รวม ${total.toLocaleString()} คน - ตระหนักรู้ ${pct}%`];
                                }
                            }
                        },
                        datalabels: {
                            display: function(context) {
                                return context.dataset.data[context.dataIndex] > 0;
                            }
                        }
                    },
                    layout: {
                        padding: { top: 26 }
                    }
                },
                plugins: [...chartsPlugins, passFailBarShadow]
            });


            // --- 5. Behavioral Charts (full-width diverging bar rows, one
            //     per question, matching the approved "ออกแบบกราฟพฤติกรรม
            //     โซเดียม" mockup) ---
            const createDivergingBars = (containerId, tableContainerId, statsData, polarityOverrides, extremeValuesOverrides) => {
                const categories = Object.keys(statsData);
                const container = document.getElementById(containerId);
                if (!container) return;
                if (categories.length === 0) {
                    container.innerHTML = '';
                    return;
                }

                polarityOverrides = polarityOverrides || {};
                extremeValuesOverrides = extremeValuesOverrides || {};

                // Traffic-light ramp: red (risky) -> orange -> yellow
                // (neutral) -> light green -> dark green (healthy), matching
                // the reference palette requested for the dashboard.
                const rankColors = ['#e04b3f', '#f2994a', '#f2c94c', '#8bc78f', '#2f8f46'];

                // Semantic Keyword Mapping (Map ANY label to 0-4 scale: High Freq -> Low Freq)
                const getSemanticRank = (label) => {
                    label = label.toString().trim().replace(/\s/g, '');
                    // Lowest Frequency / Best for Negative Polarity (Or Highest Score for +)
                    if (/^([9]|10)$|ไม่เคย|ไม่เลย|ไม่ใช่|ไม่สุ่มเสี่ยง|0%|ไม่เห็นด้วย/i.test(label))
                        return 4;
                    // Infrequent
                    if (/^([7-8])$|นานๆครั้ง|1-2ครั้ง|น้อยกว่า50%/i.test(label)) return 3;
                    // Neutral / Moderate
                    if (/^([5-6])$|พอดี|บ่อย|ปานกลาง|บางครั้ง|50-100%|สำคัญพอควร|ทราบเป็นบางรายการ|ไม่แน่ใจ/i
                        .test(label)) return 2;
                    // Frequent / Risk
                    if (/^([3-4])$|บ่อยครั้ง|เกือบทุกวัน|เกือบทุกมื้อ|น้อย|ไม่ค่อยสำคัญ|เห็นด้วย$/i.test(
                            label)) return 1;
                    // Highest Frequency / Worst for Negative Polarity (Or Lowest Score for +)
                    if (/^([1-2]|0)$|เค็ม|ทุกวัน|ทุกครั้ง|ทุกมื้อ|เป็นส่วนใหญ่|ไม่ทราบเลย|ไม่สำคัญเลย|น้อยที่สุด|สำคัญมาก|มากที่สุด|เคย|เห็นด้วยอย่างยิ่ง/i
                        .test(label)) return 0;
                    return 2;
                };

                // Rank for one answer of one question - prefers an explicit
                // admin override (extremeValuesOverrides, from
                // chart_polarity_values in "ตั้งค่าแดชบอร์ด": a checkbox list
                // of that question's own real recorded answers picked as
                // "ความถี่สูงสุด") over the built-in keyword guess above. An
                // exact-match override always wins and is forced to rank 0
                // (the extreme end, before polarityFor's negative/positive
                // flip is applied) - this only fixes which answers count as
                // "most extreme" for THIS question, it never changes the
                // overall negative/positive direction choice.
                // An admin override that ends up covering literally every
                // real answer recorded for a question can't split anyone
                // into "the rest" - every respondent lands in the same
                // "selected" bucket, producing a single meaningless 100%
                // segment instead of a real breakdown. That's a checkbox
                // list that answers a different question ("which answers
                // are valid for this question") than the one it's meant to
                // ("which answer(s) count as the extreme end"), so it's
                // treated the same as if nothing had been picked at all
                // (falls back to the keyword guess below) rather than
                // rendering a chart that can't show any variation.
                const degenerateOverrideCategories = new Set();
                categories.forEach(c => {
                    const overrides = extremeValuesOverrides[c];
                    if (!overrides || !overrides.length) return;
                    const distinctAnswers = Object.keys(statsData[c] || {});
                    if (distinctAnswers.length > 0 && distinctAnswers.every(a => overrides.indexOf(a) !== -1)) {
                        degenerateOverrideCategories.add(c);
                    }
                });

                const getRank = (category, label) => {
                    const overrides = extremeValuesOverrides[category];
                    if (overrides && !degenerateOverrideCategories.has(category) && overrides.indexOf(label) !== -1) return 0;
                    return getSemanticRank(label);
                };

                // Automatic fallback guess, used only when the admin hasn't
                // set an explicit direction for a question from
                // "ตั้งค่าแดชบอร์ด" (polarityOverrides, from chart_polarity).
                const defaultPolarityMap = {
                    // Legacy Generic
                    'อาหารกึ่งสำเร็จรูป': 'negative',
                    'อาหารแช่แข็ง': 'negative',
                    'อาหารหมักดอง': 'negative',
                    'ทานอาหารโซเดียมสูง': 'negative',
                    'ทำอาหารทานเอง': 'positive',
                    'ทานอาหารนอกบ้าน': 'negative',
                    'เติมเครื่องปรุงขณะทำ': 'negative',
                    'เติมเครื่องปรุงบนโต๊ะ': 'negative',
                    'สั่งไม่ใส่ผงชูรส': 'positive',
                    'ระดับความสำคัญ': 'positive',
                    'ระดับความพยายาม': 'positive',
                    'ระดับความรู้': 'positive',
                    'ความตระหนักต่อสุขภาพ': 'positive',
                    // FY69 Precise
                    'บะหมี่กึ่งสำเร็จรูป/อาหารสำเร็จรูปแบบกล่อง/อาหารขยะ/ขนมกรุบกรอบ': 'negative',
                    'อาหารแปรรูป/หมักดอง (เช่น ไส้กรอก แหนม หมูยอ ผักดอง)': 'negative',
                    'ปรุงอาหารทานเองมีการเติมเครื่องปรุงรสเค็ม': 'negative',
                    'ซื้อแกงถุง/อาหารตามสั่ง เติมเครื่องปรุงเพิ่ม': 'negative',
                    'ลดอาหารที่มีรสเค็มจัด': 'positive',
                    'ลดอาหารแปรรูป/กึ่งสำเร็จรูป': 'positive',
                    'หลีกเลี่ยง/ลดซดน้ำแกง/น้ำซุป': 'positive',
                    'ลดการจิ้มน้ำจิ้ม': 'positive',
                    'เพิ่มผักผลไม้สด': 'positive',
                    'ออกกำลังกายสม่ำเสมอ': 'positive',
                    'ดื่มน้ำเปล่า 8 แก้ว/วัน': 'positive',
                    'มั่นใจว่าปรับเปลี่ยนพฤติกรรมได้': 'positive',
                    'ตระหนักว่าทานเค็มมากไป ส่งผลเสียต่อสุขภาพ': 'positive',
                    'ทราบว่าไม่ควรทานเกลือเกิน 1 ช้อนชา/วัน': 'positive',
                    'คิดว่าปริมาณความเค็มที่ทานปัจจุบันอยู่ในระดับใด': 'negative', // "เค็มจัด" is bad -> 0 -> red
                    'ความสำคัญของการลดโซเดียม': 'positive',
                    'ความพยายามในการลดโซเดียม': 'positive',
                    'ความมั่นใจในการลดโซเดียม': 'positive',
                    // FY2569 (ปีงบประมาณ 2569) dashboard questions - keyed by
                    // the exact short label these render under (see
                    // DefaultQuestionPanels::shortLabelFor). None of these
                    // have an explicit "ทิศทาง" set in "ตั้งค่าแดชบอร์ด" yet
                    // (chart_polarity is null for all of them), so without
                    // an entry here every one of them fell through to the
                    // hard-coded '|| negative' default below - including the
                    // awareness/attitude statements where agreeing is the
                    // GOOD answer, which then showed the exact same red
                    // "risk" color as the true risk-behavior questions. This
                    // is a best-effort guess from the question wording
                    // itself; the admin should confirm (or override) each
                    // one from the "ทิศทาง" dropdown next to the question in
                    // "ตั้งค่าแดชบอร์ด" - an explicit choice made there always
                    // wins over this fallback map (see polarityFor below).
                    'ท่านเติมน้ำปลา...': 'negative', // เติมเครื่องปรุงรสเค็มขณะทาน - ทำบ่อย = เสี่ยง
                    'บริโภคอาหารแปรรูป...': 'negative', // อาหารแปรรูป - ทานบ่อย = เสี่ยง
                    'บริโภคอาหารหมักดอง...': 'negative', // อาหารหมักดอง - ทานบ่อย = เสี่ยง
                    'คนใกล้ชิดป่วย...จะปรับพฤติกรรม': 'positive', // เต็มใจปรับพฤติกรรม - เห็นด้วยมาก = ดี
                    'คนใกล้ชิดชอบทานเค็ม': 'negative', // คนรอบข้างทานเค็ม (ปัจจัยเสี่ยงแวดล้อม) - เห็นด้วยมาก = เสี่ยงมาก
                    'การรับประทานเกลือ...เสี่ยงโรค': 'positive', // ข้อความความรู้ที่ถูกต้อง - เห็นด้วยมาก = ตระหนักรู้ดี
                    'โรคจากเกลือ...ค่าใช้จ่ายสูง': 'positive', // ข้อความความรู้ที่ถูกต้อง - เห็นด้วยมาก = ตระหนักรู้ดี
                    'ลดเกลือ...ลดเสี่ยง NCDs': 'positive', // ข้อความความรู้ที่ถูกต้อง - เห็นด้วยมาก = ตระหนักรู้ดี
                    'ผลิตภัณฑ์ลดเกลือ...หาซื้อยาก': 'negative', // อุปสรรคในการเข้าถึง - เห็นด้วยมาก = อุปสรรคสูง
                };

                const polarityFor = (category) => polarityOverrides[category] || defaultPolarityMap[category] ||
                    'negative';

                const totals = {};
                categories.forEach(c => {
                    totals[c] = statsData[c] ? Object.values(statsData[c]).reduce((a, b) => a + b, 0) : 0;
                });

                // The data table is a fixed, complete reference - built
                // once from the full data and never affected by the legend
                // toggle below. Each row's color matches its bar segment
                // (same rank + polarity logic), so the table doubles as a
                // lookup back into the bars.
                const tableRows = [];
                categories.forEach(c => {
                    const answersForCat = statsData[c] || {};
                    const total = totals[c] || 0;
                    if (total <= 0) return;
                    const pol = polarityFor(c);
                    Object.keys(answersForCat).forEach(ans => {
                        const count = answersForCat[ans];
                        const pct = (count / total) * 100;
                        if (pct <= 0) return;
                        const rawRank = getRank(c, ans);
                        const finalRank = pol === 'negative' ? rawRank : (4 - rawRank);
                        tableRows.push({
                            category: c,
                            answer: ans,
                            pct: pct,
                            color: rankColors[finalRank]
                        });
                    });
                });
                renderFigureTable(tableContainerId, tableRows);

                // Per-answer model: every distinct real answer gets its own
                // segment and its own traffic-light color from rankColors,
                // exactly like the data table above (tableRows) - a 5-point
                // scale question shows up to 5 segments, not just a
                // red/green split. getRank still decides where each real
                // answer falls (admin override from "ตั้งค่าแดชบอร์ด" when
                // it's usable, else the built-in keyword guess), and the
                // polarity flip still mirrors the whole 0-4 ramp per
                // question, so the riskiest real answer always lands red
                // and the healthiest always lands green regardless of which
                // side of the picker the admin ticked it on.
                const renderBars = () => {
                    let rowsHtml = '';
                    categories.forEach(c => {
                        const answersForCat = statsData[c] || {};
                        const pol = polarityFor(c);
                        const total = totals[c] || 0;
                        if (total <= 0) return;

                        const segments = Object.keys(answersForCat)
                            .map(ans => {
                                const count = answersForCat[ans];
                                const pct = (count / total) * 100;
                                if (pct <= 0) return null;
                                const rawRank = getRank(c, ans);
                                const finalRank = pol === 'negative' ? rawRank : (4 - rawRank);
                                return {
                                    pct: pct,
                                    rank: finalRank,
                                    color: rankColors[finalRank],
                                    title: ans
                                };
                            })
                            .filter(s => s !== null)
                            .sort((a, b) => a.rank - b.rank);

                        if (segments.length === 0) return;

                        const segHtml = segments.map(s => {
                            // The bar itself shows only the % once a segment
                            // is wide enough to hold it - hover (native
                            // title tooltip) always shows the real answer
                            // wording, which is also spelled out below as a
                            // colored-dot legend, and again in the data
                            // table above ("ดูข้อมูลตัวเลข").
                            const label = s.pct >= 9 ? Math.round(s.pct) + '%' : '';
                            return '<div class="divrow-seg" style="flex:0 0 ' + s.pct + '%;background:' +
                                s.color + '" title="' + escapeHtml(s.title) + ': ' + s.pct.toFixed(1) +
                                '%">' + (label ? '<span>' + label + '</span>' : '') + '</div>';
                        }).join('');

                        // Colored dot + the real answer wording behind each
                        // segment - which literal answer counts as "red" vs
                        // "green" differs per question, so this is per-row
                        // rather than one shared legend.
                        const legendHtml = '<div class="divrow-legend">' + segments.map(s =>
                            '<span class="divrow-legend-item"><span class="divrow-legend-dot" style="background:' +
                            s.color + '"></span>' + escapeHtml(s.title) + ' (' + s.pct.toFixed(1) +
                            '%)</span>'
                        ).join('') + '</div>';

                        rowsHtml += '<div class="divrow"><div class="divrow-label">' + escapeHtml(c) +
                            '</div><div class="divrow-bar">' + segHtml + '</div>' + legendHtml + '</div>';
                    });

                    container.innerHTML = rowsHtml;
                };

                renderBars();
            };

            // Plain HTML table twin of a diverging chart's data, grouped by
            // question - the "ดูข้อมูลตัวเลข" toggle under each chart.
            const renderFigureTable = (containerId, rows) => {
                const el = document.getElementById(containerId);
                if (!el) return;
                if (rows.length === 0) {
                    el.innerHTML = '';
                    return;
                }
                let html =
                    '<table class="figure-table"><thead><tr><th>คำถาม</th><th>คำตอบ</th><th>ร้อยละ (%)</th></tr></thead><tbody>';
                let lastCategory = null;
                rows.forEach(function(r) {
                    const isGroupStart = r.category !== lastCategory;
                    const catCell = isGroupStart ?
                        '<span class="figure-table-qbadge">' + escapeHtml(r.category) + '</span>' : '';
                    lastCategory = r.category;
                    const rowClasses = [isGroupStart ? 'group-start' : '', r.pct >= 20 ? 'row-highlight' :
                        ''
                    ].filter(Boolean).join(' ');
                    html += '<tr class="' + rowClasses + '"><td>' + catCell +
                        '</td><td>' + escapeHtml(r.answer) + '</td><td>' + r.pct.toFixed(1) +
                        '%</td></tr>';
                });
                html += '</tbody></table>';
                el.innerHTML = html;
            };

            const escapeHtml = (s) => String(s)
                .replace(/&/g, '&amp;')
                .replace(/</g, '&lt;')
                .replace(/>/g, '&gt;');

            const renderEducationTable = (containerId, data) => {
                const el = document.getElementById(containerId);
                if (!el) return;
                const entries = Object.entries(data).sort(([, a], [, b]) => b - a);
                if (entries.length === 0) {
                    el.innerHTML = '';
                    return;
                }
                const total = entries.reduce((sum, [, v]) => sum + v, 0);
                let html =
                    '<table class="figure-table"><thead><tr><th>ระดับการศึกษา</th><th>จำนวน (คน)</th><th>ร้อยละ (%)</th></tr></thead><tbody>';
                entries.forEach(([label, value]) => {
                    const pct = total > 0 ? (value / total * 100) : 0;
                    const rowClass = pct >= 20 ? ' class="row-highlight"' : '';
                    html += '<tr' + rowClass + '><td>' + escapeHtml(label) + '</td><td>' + value
                        .toLocaleString() + '</td><td>' + pct.toFixed(1) + '%</td></tr>';
                });
                html += '</tbody></table>';
                el.innerHTML = html;
            };

            const renderPassFailTable = (containerId, data) => {
                const el = document.getElementById(containerId);
                if (!el) return;
                const provinces = Object.keys(data);
                if (provinces.length === 0) {
                    el.innerHTML = '';
                    return;
                }
                let html =
                    '<table class="figure-table"><thead><tr><th>จังหวัด</th><th>ตระหนักรู้ (คน)</th><th>ไม่ตระหนัก (คน)</th><th>ร้อยละตระหนักรู้ (%)</th></tr></thead><tbody>';
                provinces.forEach((p) => {
                    const pass = data[p].pass || 0;
                    const fail = data[p].fail || 0;
                    const total = pass + fail;
                    const pct = total > 0 ? (pass / total * 100) : 0;
                    html += '<tr><td>' + escapeHtml(p) + '</td><td>' + pass.toLocaleString() +
                        '</td><td>' + fail.toLocaleString() + '</td><td>' + pct.toFixed(1) +
                        '%</td></tr>';
                });
                html += '</tbody></table>';
                el.innerHTML = html;
            };



            // Data from Controller - the 4 fixed panels. Which question
            // lands in which panel (for whichever fiscal year(s) are in
            // view) is configured from "การประเมินความตระหนักรู้ >
            // ตั้งค่าคำถามและเกณฑ์การประเมิน", not hardcoded here. polarityPanelN
            // (chart_polarity, set per question from "ตั้งค่าแดชบอร์ด") tells
            // the chart which side ("good" vs "risky") each question's high
            // end belongs on; unset questions fall back to the built-in
            // keyword guess.
            const statsPanel1 = @json($statsPanel1 ?? []);
            const statsPanel2 = @json($statsPanel2 ?? []);
            const statsPanel3 = @json($statsPanel3 ?? []);
            const statsPanel4 = @json($statsPanel4 ?? []);
            const polarityPanel1 = @json($polarityPanel1 ?? []);
            const polarityPanel2 = @json($polarityPanel2 ?? []);
            const polarityPanel3 = @json($polarityPanel3 ?? []);
            const polarityPanel4 = @json($polarityPanel4 ?? []);
            const extremeValuesPanel1 = @json($extremeValuesPanel1 ?? []);
            const extremeValuesPanel2 = @json($extremeValuesPanel2 ?? []);
            const extremeValuesPanel3 = @json($extremeValuesPanel3 ?? []);
            const extremeValuesPanel4 = @json($extremeValuesPanel4 ?? []);

            if (document.getElementById('riskChart')) {
                const sodiumBehaviorEl = document.getElementById('sodiumBehaviorChart');
                if (sodiumBehaviorEl) {
                    sodiumBehaviorEl.closest('.col-lg-6').style.display = 'none';
                }
                createDivergingBars('riskChart', 'riskTable', statsPanel1, polarityPanel1, extremeValuesPanel1);
                createDivergingBars('seasoningChart', 'seasoningTable', statsPanel2, polarityPanel2, extremeValuesPanel2);
                createDivergingBars('healthyHabitChart', 'healthyHabitTable', statsPanel3, polarityPanel3, extremeValuesPanel3);
                createDivergingBars('mindsetChart', 'mindsetTable', statsPanel4, polarityPanel4, extremeValuesPanel4);
            }

            renderEducationTable('educationTable', educationStats);
            renderPassFailTable('passFailTable', passFailByProvince);
        });
    </script>
@endsection
