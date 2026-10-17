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
            gap: 14px;
            flex: 1 1 auto;
            min-height: 252px;
            width: 100%;
            padding: 4px 0 2px;
        }

        .awr-donut-chart {
            position: relative;
            flex: 0 0 46%;
            width: 46%;
            align-self: stretch;
            min-height: 220px;
        }

        .awr-donut-legend {
            flex: 1 1 0;
            min-width: 0;
            display: flex;
            flex-direction: column;
            justify-content: center;
            align-self: stretch;
            gap: 8px;
        }

        /* แถวจังหวัดในคำอธิบาย: จุดสี + ชื่อ + แถบสัดส่วน | ร้อยละ + จำนวนคน */
        .awr-leg-item {
            display: flex;
            align-items: center;
            gap: 9px;
            flex: 1 1 0;
            max-height: 68px;
            padding: 6px 10px;
            border-radius: 11px;
            background: #f8fafc;
            border: 1px solid #eef1f6;
            transition: background .15s ease, border-color .15s ease, box-shadow .15s ease;
        }
        .awr-leg-item:hover { background: #fff; border-color: var(--leg-c); box-shadow: 0 6px 14px -8px var(--leg-c); }
        .awr-leg-dot { flex: 0 0 auto; width: 10px; height: 10px; border-radius: 50%; background: var(--leg-c); }
        .awr-leg-main { flex: 1 1 0; min-width: 0; display: flex; flex-direction: column; gap: 4px; }
        .awr-leg-name { font-size: 12.5px; font-weight: 700; color: #1e293b; line-height: 1.15; white-space: nowrap; overflow: hidden; text-overflow: ellipsis; }
        .awr-leg-bar { display: block; height: 4px; border-radius: 99px; background: #e8edf3; overflow: hidden; }
        .awr-leg-bar > span { display: block; width: var(--leg-w); height: 100%; border-radius: 99px; background: var(--leg-c); }
        .awr-leg-nums { flex: 0 0 auto; text-align: right; line-height: 1.15; white-space: nowrap; }
        .awr-leg-nums b { display: block; font-size: 13.5px; font-weight: 800; color: #0f172a; font-variant-numeric: tabular-nums; }
        .awr-leg-nums small { display: block; margin-top: 1px; font-size: 10.5px; font-weight: 600; color: #94a3b8; }
        /* การ์ดสัดส่วนผู้ตอบแยกตามเพศ */
        .gr-rows { display: flex; flex-direction: column; gap: 14px; flex: 1 1 auto; justify-content: center; }
        #genderChart { flex: 1 1 auto; }
        .gr-row { flex: 1 1 0; max-height: 170px; position: relative; overflow: hidden; display: flex; align-items: center; gap: 16px; min-height: 96px; padding: 16px 20px; border-radius: 16px;
            background: #f8fafc; border: 1px solid #e8edf3; }
        .gr-row::after { content: ""; position: absolute; left: 0; bottom: 0; height: 3px; width: var(--gr-w); background: var(--gr-c); opacity: .55; border-radius: 0 3px 0 0; }
        .gr-icon { flex: 0 0 auto; width: 56px; height: 56px; border-radius: 50%; display: flex; align-items: center; justify-content: center; font-size: 26px; color: #fff;
            background: linear-gradient(145deg, color-mix(in srgb, var(--gr-c) 78%, #fff), var(--gr-c)); box-shadow: 0 8px 16px -6px var(--gr-c); }
        .gr-main { flex: 1 1 0; min-width: 0; display: flex; flex-direction: column; line-height: 1.15; }
        .gr-name { font-size: 13.5px; font-weight: 600; color: #64748b; }
        .gr-pct { margin-top: 3px; font-size: clamp(1.9rem, 2.6vw, 2.35rem); font-weight: 800; line-height: 1.05; color: var(--gr-ink); font-variant-numeric: tabular-nums; letter-spacing: -.5px; }
        .gr-count { flex: 0 0 auto; text-align: right; font-size: 15px; font-weight: 700; color: #1e293b; font-variant-numeric: tabular-nums; white-space: nowrap; }
        #genderChart { container-type: inline-size; }
        @container (max-width: 310px) {
            .gr-row { gap: 12px; padding: 14px; min-height: 84px; }
            .gr-icon { width: 46px; height: 46px; font-size: 21px; }
            .gr-name { font-size: 12.5px; white-space: nowrap; }
            .gr-pct { font-size: 1.85rem; }
            .gr-count { font-size: 13px; }
        }
        .gr-empty { padding: 48px 0; text-align: center; color: #94a3b8; font-size: 14px; }
        @media (max-width: 420px) { .gr-row { gap: 12px; padding: 14px; } .gr-icon { width: 46px; height: 46px; font-size: 21px; } .gr-count { font-size: 13.5px; } }
        .gr-ratio-badge { margin-left: auto; flex: 0 0 auto; align-self: center; display: flex; flex-direction: column; align-items: center; padding: 5px 14px 6px; border-radius: 14px; background: #f5f3ff; border: 1px solid #ddd6fe; line-height: 1.15; }
        .gr-ratio-badge small { font-size: 11.5px; font-weight: 700; color: #6d28d9; }
        .gr-ratio-badge b { font-size: 1.2rem; font-weight: 800; color: #4c1d95; font-variant-numeric: tabular-nums; letter-spacing: -.2px; white-space: nowrap; }
        .awr-pf-pills { display: flex; align-items: stretch; gap: 8px; margin-bottom: 8px; }
        .awr-pf-pills > span { flex: 1 1 0; justify-content: center; }

        @media (max-width: 768px) {
            .awr-donut-row {
                flex-direction: column;
                height: auto;
                gap: 14px;
                padding: 8px 0 4px;
            }

            .awr-donut-chart {
                flex: none;
                width: 100%;
                height: 220px;
            }

            .awr-leg-item { flex: none; max-height: none; }

            .awr-donut-legend {
                flex: none;
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

        /* คำอธิบายสีใต้กราฟ: ชิปสีตามความหมายแทนข้อความยาว */
        .scale-legend {
            display: flex;
            flex-wrap: wrap;
            align-items: center;
            gap: 6px 14px;
            margin: 0 0 8px;
            color: #64748b;
            font-size: 0.8rem;
            line-height: 1.5;
        }

        .scale-legend-item {
            display: inline-flex;
            align-items: center;
            gap: 6px;
            font-weight: 600;
            color: #475569;
        }

        .scale-legend-swatch {
            display: inline-block;
            width: 26px;
            height: 10px;
            border-radius: 3px;
        }

        .scale-legend-note {
            flex: 1 1 100%;
            color: #94a3b8;
            font-size: 0.76rem;
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

        /* สถานะความตระหนักรู้ รายจังหวัด: แท่งแนวตั้ง 100% ต่อจังหวัด เรียงตามร้อยละตระหนักรู้ */
        .pf-rows {
            height: 100%;
            display: flex;
            flex-direction: column;
            gap: 10px;
            padding: 0 2px;
        }

        .pf-top {
            display: flex;
            flex-wrap: wrap;
            align-items: center;
            justify-content: space-between;
            gap: 6px 12px;
            font-size: .8rem;
            color: #475569;
            font-weight: 600;
        }

        .pf-top .pf-sum b {
            color: #047857;
        }

        .pf-legend {
            display: flex;
            gap: 14px;
        }

        .pf-legend i {
            display: inline-block;
            width: 10px;
            height: 10px;
            border-radius: 3px;
            margin-right: 5px;
            vertical-align: -1px;
        }

        .pf-cols {
            flex: 1 1 auto;
            min-height: 0;
            display: grid;
            grid-template-columns: repeat(var(--pf-n, 5), minmax(0, 1fr));
            gap: 10px;
            align-items: stretch;
        }

        .pf-col {
            display: flex;
            flex-direction: column;
            align-items: center;
            min-width: 0;
            text-align: center;
        }

        .pf-badge {
            width: 100%;
            max-width: 84px;
            margin-bottom: 9px;
            padding: 6px 4px 5px;
            border-radius: 12px;
            background: linear-gradient(180deg, #ecfdf5, #d1fae5);
            border: 1px solid #a7f3d0;
            box-shadow: 0 3px 8px rgba(5, 150, 105, .16);
        }

        .pf-pct {
            font-weight: 800;
            font-size: 1.15rem;
            line-height: 1.1;
            color: #047857;
            font-variant-numeric: tabular-nums;
        }

        .pf-pct-cap {
            font-size: .68rem;
            font-weight: 600;
            color: #065f46;
            line-height: 1.3;
        }

        .pf-tube {
            flex: 1 1 auto;
            min-height: 120px;
            width: 100%;
            max-width: 62px;
            display: flex;
            flex-direction: column;
            border-radius: 16px;
            overflow: hidden;
            background: linear-gradient(180deg, #fecaca, #fee2e2);
            box-shadow: 0 6px 14px rgba(15, 23, 42, .10), inset 0 0 0 1px rgba(15, 23, 42, .04);
        }

        .pf-tube-fail {
            flex: 1 1 auto;
            display: flex;
            align-items: flex-start;
            justify-content: center;
            padding-top: 7px;
            font-size: .68rem;
            font-weight: 700;
            color: #c2494a;
        }

        .pf-tube-pass {
            flex: 0 0 auto;
            min-height: 8px;
            background: linear-gradient(180deg, #34d399, #059669);
            border-radius: 12px 12px 0 0;
            box-shadow: 0 -2px 0 #fff;
        }

        .pf-name {
            margin-top: 8px;
            width: 100%;
            min-width: 0;
            font-weight: 600;
            font-size: .8rem;
            color: #475569;
            line-height: 1.2;
            /* การ์ดแคบลงเมื่อจัดแถวละ 3 - ให้ชื่อจังหวัดตัดคำขึ้นบรรทัดใหม่ ไม่ล้นไปทับคอลัมน์ข้าง ๆ */
            overflow-wrap: anywhere;
        }

        .pf-n {
            font-size: .7rem;
            font-weight: 500;
            color: #64748b;
            line-height: 1.3;
        }

        .pf-n b {
            color: #1e293b;
            font-weight: 700;
        }

        @media (max-width: 420px) {
            .pf-cols {
                gap: 6px;
            }

            .pf-name {
                font-size: .76rem;
            }
        }

        /* ตารางตัวเลขเปิดเป็นแผงลอยเหนือปุ่ม (ไม่ดันเนื้อหา) -
           ความสูงการ์ดจึงคงที่ และการ์ดในแถวเดียวกันไม่ขยับเมื่อกด "ดูข้อมูลตัวเลข" */
        .chart-figure-footer details { position: relative; }
        .figure-table-wrap {
            position: absolute;
            left: 0;
            right: 0;
            bottom: calc(100% + 8px);
            z-index: 30;
            margin-top: 0;
            overflow-x: auto;
            overflow-y: auto;
            max-height: min(340px, 60vh);
            background: #fff;
            border: 1px solid #e2e8f0;
            border-radius: 12px;
            box-shadow: 0 18px 38px -14px rgba(15, 23, 42, .35);
        }
        @media (prefers-reduced-motion: no-preference) {
            .chart-figure-footer details[open] > .figure-table-wrap { animation: figTablePop .16s ease-out; }
        }
        @keyframes figTablePop { from { opacity: 0; transform: translateY(6px); } to { opacity: 1; transform: none; } }

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
           createDivergingBars() in extra_js below. สีแดง-ส้ม = คำตอบที่ควรปรับ,
           เทา = กลาง, ฟ้าอมเขียว = คำตอบที่ดี (แดง/ฟ้าแยกกันได้แม้ตาบอดสีแดง-เขียว) */
        .divsum {
            display: flex;
            align-items: baseline;
            gap: 8px;
            margin: 0 0 18px;
            padding: 11px 14px;
            background: #fbf1ef;
            border-radius: 12px;
            color: #7a2e29;
            font-size: 0.86rem;
            line-height: 1.5;
        }

        .divsum i { color: #b9534d; flex: 0 0 auto; }
        .divsum b { font-weight: 800; }

        .divrow {
            padding: 14px 0 16px;
            border-top: 1px solid #eef1f5;
        }

        .divrow:first-child,
        .divsum + .divrow {
            border-top: none;
            padding-top: 0;
        }

        .divrow-head {
            display: flex;
            align-items: flex-start;
            justify-content: space-between;
            gap: 12px;
            margin-bottom: 9px;
        }

        .divrow-label {
            font-weight: 700;
            font-size: 0.9rem;
            color: #1e293b;
            line-height: 1.45;
            min-width: 0;
        }

        .divrow-risk {
            flex: 0 0 auto;
            padding: 2px 10px;
            border-radius: 999px;
            background: #fbeeec;
            color: #a6443e;
            font-size: 0.78rem;
            font-weight: 800;
            white-space: nowrap;
        }

        .divrow-risk.is-low {
            background: #eef2f6;
            color: #64748b;
        }

        .divrow-bar {
            display: flex;
            gap: 2px;
            width: 100%;
            height: 40px;
            border-radius: 10px;
            overflow: hidden;
        }

        .divrow-seg {
            display: flex;
            flex-direction: column;
            align-items: center;
            justify-content: center;
            min-width: 0;
            overflow: hidden;
            line-height: 1.15;
            text-align: center;
        }

        .divrow-seg b {
            font-size: 12.5px;
            font-weight: 800;
        }

        .divrow-seg small {
            max-width: 100%;
            padding: 0 4px;
            overflow: hidden;
            text-overflow: ellipsis;
            white-space: nowrap;
            font-size: 10.5px;
            font-weight: 600;
            opacity: 0.92;
        }

        @media (max-width: 575.98px) {
            .divrow-seg small { display: none; }
        }

        .divrow-foot {
            display: flex;
            align-items: baseline;
            justify-content: space-between;
            gap: 12px;
            margin-top: 7px;
            color: #94a3b8;
            font-size: 11.5px;
            font-weight: 600;
        }

        .divrow-empty {
            padding: 18px 14px;
            border: 1.5px dashed #dbe3ec;
            border-radius: 12px;
            color: #94a3b8;
            font-size: 0.86rem;
            line-height: 1.6;
            text-align: center;
        }

        .divrow-others { display: flex; flex-wrap: wrap; gap: 3px 14px; min-width: 0; }
        .divrow-n { flex: 0 0 auto; white-space: nowrap; }

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

        .divrow-legend-item {
            display: inline-flex;
            align-items: center;
            gap: 5px;
            color: #64748b;
        }

        .divrow-legend-dot {
            width: 8px;
            height: 8px;
            min-width: 8px;
            border-radius: 50%;
            flex-shrink: 0;
        }
    
        /* ===== ปรับโฉมการ์ดกราฟแถบ (ทันสมัย อ่านง่าย) ===== */
        .chart-card {
            counter-reset: divrank;
            padding: 22px 22px 18px !important;
            border-radius: 24px !important;
            border: 1px solid #edf1f6;
            box-shadow: 0 14px 34px -18px rgba(30, 41, 59, .22), 0 2px 6px rgba(30, 41, 59, .04) !important;
        }

        .chart-header {
            align-items: center;
            gap: 14px;
            margin-bottom: 18px;
            padding-bottom: 16px;
        }

        .chart-header-icon {
            width: 46px;
            height: 46px;
            min-width: 46px;
            border-radius: 16px;
            background: linear-gradient(145deg, var(--chart-accent, #ec4899), color-mix(in srgb, var(--chart-accent, #ec4899) 70%, #000));
            font-size: 1.1rem;
        }

        .chart-header-title { font-size: 1.12rem; letter-spacing: -.005em; }
        .chart-header-subtitle {
            display: inline-block;
            margin-top: 4px;
            padding: 1px 10px;
            border-radius: 999px;
            background: #f1f5f9;
            color: #64748b;
            font-size: .76rem;
        }

        .divsum {
            align-items: center;
            gap: 12px;
            margin-bottom: 14px;
            padding: 13px 16px;
            border-radius: 18px;
            background: linear-gradient(100deg, #fdf0ed 0%, #fff7f4 100%);
            border: 1px solid #f7dcd6;
            color: #7a2e29;
        }

        .divsum i {
            display: inline-block !important;
            flex: 0 0 28px;
            width: 28px;
            height: 28px;
            padding: 0;
            margin: 0;
            line-height: 28px !important;
            text-align: center;
            border-radius: 50%;
            background: #fff;
            color: #c0584f;
            box-shadow: 0 2px 6px rgba(185, 83, 77, .2);
            font-size: .8rem;
        }

        .divsum span { flex: 1 1 auto; min-width: 0; overflow: hidden; text-overflow: ellipsis; white-space: nowrap; }
        
        /* อยู่แถวเดียวกับหัวข้อการ์ด ชิดขวา */
        .chart-header { flex-wrap: nowrap; }
        .chart-header > div:not(.chart-header-icon):not(.divsum) { flex: 1 1 auto; min-width: 0; }
        .chart-header .divsum {
            flex: 0 1 auto;
            margin: 0 0 0 auto;
            padding: 7px 14px;
            max-width: 52%;
            border-radius: 16px;
        }

        .divsum .divsum-text {
            display: flex;
            flex-direction: column;
            gap: 1px;
            white-space: normal;
            overflow: visible;
            line-height: 1.25;
        }

        .divsum .divsum-text small {
            font-size: .68rem;
            font-weight: 700;
            color: #a6685f;
        }

        .divsum .divsum-text b {
            display: -webkit-box;
            -webkit-line-clamp: 2;
            -webkit-box-orient: vertical;
            overflow: hidden;
            font-size: .8rem;
            font-weight: 800;
        }

        .chart-header .divsum .divsum-pct { font-size: 1rem; }

        @media (max-width: 767.98px) {
            .chart-header { flex-wrap: wrap; }
            .chart-header .divsum { flex: 1 1 100%; max-width: 100%; margin: 4px 0 0; }
        }

        .divsum-pct {
            flex: 0 0 auto;
            font-style: normal;
            font-weight: 800;
            font-size: 1.05rem;
            color: #b9534d;
        }

        .divrow,
        .divsum + .divrow,
        .divrow:first-child {
            margin-bottom: 12px;
            padding: 14px 16px 16px;
            border: 1px solid #eef2f6;
            border-radius: 20px;
            background: #fafcfd;
        }

        .divrow-head { align-items: center; margin-bottom: 11px; }

        .divrow-label {
            display: flex;
            align-items: center;
            gap: 10px;
            font-size: .95rem;
        }

        .divrow-label::before {
            counter-increment: divrank;
            content: counter(divrank);
            flex: 0 0 auto;
            display: inline-flex;
            align-items: center;
            justify-content: center;
            width: 24px;
            height: 24px;
            border-radius: 50%;
            background: #e9eef3;
            color: #475569;
            font-size: .75rem;
            font-weight: 800;
        }

        .divsum + .divrow .divrow-label::before,
        .divrow:first-child .divrow-label::before { background: #f3c9c4; color: #8a2f29; }

        .divrow-risk {
            padding: 4px 12px;
            background: #fde8e5;
            color: #a6443e;
            font-size: .8rem;
        }

        .divrow-bar {
            height: 46px;
            gap: 0;
            padding: 0;
            border-radius: 14px;
            overflow: hidden;
            background: transparent;
            box-shadow: none;
        }

        /* ช่วงสีต่อเนื่องเป็นแถบเดียว คั่นด้วยเส้นขาวบาง ไม่เป็นก้อนแยก */
        .divrow-seg { border-radius: 0; transition: filter .15s ease; }
        .divrow-seg + .divrow-seg { box-shadow: -2px 0 0 rgba(255, 255, 255, .85); }
        .divrow-seg:hover { filter: brightness(.96); transform: none; }
        .divrow-seg b { font-size: 13.5px; }

        .divrow-foot { margin-top: 10px; }
        .divrow-legend-item {
            padding: 2px 10px 2px 8px;
            border-radius: 999px;
            background: #fff;
            border: 1px solid #edf1f6;
        }

        .scale-legend {
            margin-top: 6px;
            padding: 10px 14px;
            border-radius: 14px;
            background: #f8fafc;
            gap: 6px 16px;
        }

        .scale-legend-swatch { height: 8px; width: 28px; border-radius: 999px; }
        .scale-legend-note { flex: 1 1 100%; margin-top: 2px; }

        .chart-table-toggle {
            display: inline-flex;
            align-items: center;
            gap: 6px;
            padding: 7px 16px !important;
            border: 1px solid #e3eaf1;
            border-radius: 999px !important;
            background: #fff;
            color: #475569 !important;
            font-weight: 700;
            transition: background .15s ease, border-color .15s ease;
        }

        .chart-table-toggle:hover { background: #f1f5f9; border-color: #cfd9e3; }

        /* ตารางตัวเลข: แสดงเต็มพื้นที่การ์ด ใต้หัวข้อ (ไม่บังหัวข้อ) และเหนือปุ่ม */
        .chart-card { position: relative; }
        .chart-figure-footer details { position: static; }
        .chart-figure-footer .figure-table-wrap {
            left: 22px;
            right: 22px;
            top: 92px;
            bottom: 64px;
            max-height: none;
            border-radius: 18px;
            border: 1px solid #e3eaf1;
            box-shadow: 0 22px 44px -18px rgba(30, 41, 59, .38);
        }
        .chart-figure-footer .figure-table-wrap table.figure-table { font-size: .88rem; }

        /* ===== กระชับความสูงการ์ดกราฟแถบ ===== */
        .chart-card { padding: 16px 18px 12px !important; }
        .chart-header { margin-bottom: 10px; padding-bottom: 10px; gap: 12px; }
        .chart-header-icon { width: 40px; height: 40px; min-width: 40px; border-radius: 14px; font-size: 1rem; }
        .chart-header-title { font-size: 1.04rem; }
        .chart-header-subtitle { margin-top: 2px; font-size: .72rem; padding: 0 9px; }
        .chart-header .divsum { padding: 5px 12px; }

        .divrow,
        .divsum + .divrow,
        .divrow:first-child { margin-bottom: 8px; padding: 9px 12px 10px; border-radius: 16px; }
        .divrow-head { margin-bottom: 7px; }
        .divrow-label { font-size: .9rem; }
        .divrow-label::before { width: 22px; height: 22px; font-size: .7rem; }
        .divrow-risk { padding: 2px 10px; font-size: .76rem; }
        .divrow-bar { height: 38px; border-radius: 12px; }
        .divrow-seg b { font-size: 12.5px; }
        .divrow-seg small { font-size: 10px; }
        .divrow-foot { margin-top: 6px; }
        .divrow-legend-item { padding: 0 9px 0 7px; font-size: 11px; }

        .chart-figure-footer { margin-top: 4px; padding-top: 6px; }
        .scale-legend { margin: 0 0 6px; padding: 6px 12px; gap: 3px 14px; border-radius: 12px; }
        .scale-legend-note { margin-top: 0; font-size: .72rem; }
        .chart-table-toggle { padding: 5px 14px !important; font-size: .78rem; }

        /* การ์ดร้อยละผู้ตอบแบบประเมิน แยกรายจังหวัด: อ่านง่าย สบายตา (คงตำแหน่งข้อมูลเดิม) */
        .awr-pf-pills > span { font-size: 13px !important; padding: 7px 12px !important; box-shadow: 0 2px 8px -5px rgba(15, 23, 42, .25); }
        .awr-donut-row { min-height: 300px; gap: 18px; }
        .awr-donut-legend { gap: 7px; }
        .awr-leg-item { max-height: 58px; padding: 7px 12px; gap: 11px; border-radius: 12px; background: #fff; box-shadow: 0 1px 2px rgba(15, 23, 42, .04); }
        .awr-leg-item:hover { background: color-mix(in srgb, var(--leg-c) 5%, #fff); }
        .awr-leg-dot { width: 12px; height: 12px; box-shadow: 0 0 0 3px color-mix(in srgb, var(--leg-c) 18%, #fff); }
        .awr-leg-main { gap: 6px; }
        .awr-leg-name { font-size: 14px; font-weight: 700; color: #0f172a; }
        .awr-leg-bar { height: 6px; background: color-mix(in srgb, var(--leg-c) 12%, #eef1f6); }
        .awr-leg-bar > span { background: linear-gradient(90deg, color-mix(in srgb, var(--leg-c) 70%, #fff), var(--leg-c)); min-width: 4px; }
        .awr-leg-nums b { font-size: 16px; }
        .awr-leg-nums small { font-size: 11.5px; color: #64748b; }

        /* ตารางตัวเลขในการ์ด: ให้พอดีความกว้าง ไม่มีแถบเลื่อนแนวนอน */
        .figure-table-wrap { overflow-x: hidden; }
        table.figure-table { table-layout: auto; font-size: .8rem; }
        table.figure-table thead th { padding: 9px 7px; white-space: normal; line-height: 1.25; vertical-align: bottom; }
        table.figure-table td { padding: 7px 7px; }
        table.figure-table th:not(:first-child), table.figure-table td:not(:first-child) { text-align: right; font-variant-numeric: tabular-nums; }
        table.figure-table td:first-child, table.figure-table th:first-child { padding-left: 12px; }
        table.figure-table td:last-child, table.figure-table th:last-child { padding-right: 12px; }
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

    <!-- แถว 1: แผนที่ (แคบ) | ผู้ตอบแบบประเมิน (กลาง) | สถานะความตระหนักรู้ (กว้าง) -->
    <div class="row mb-4">
        <div class="col-md-6 col-lg-4 col-xl-3 mb-4">
            <div class="chart-card">
                <div class="chart-header" style="--chart-accent:#ec4899;">
                    <div class="chart-header-icon"><i class="fas fa-map-location-dot"></i></div>
                    <div>
                        <div class="chart-header-title">ร้อยละความตระหนักรู้</div>
                        <div class="chart-header-subtitle">{{ filled($effectiveFiscalYear ?? null) ? 'ปี ' . $effectiveFiscalYear : 'ทุกปีงบประมาณ' }} &middot; รายจังหวัด</div>
                    </div>
                </div>
                <div style="position: relative; height: 350px; width: 100%;">
                    <div id="awarenessMap" style="height: 350px; width: 100%;{{ !empty($hasPendingCriteria) ? ' filter: blur(4px); opacity: .6;' : '' }}"></div>
                    @if (!empty($hasPendingCriteria))
                        <div
                            style="position: absolute; top:0; left:0; width:100%; height:100%; display:flex; align-items:center; justify-content:center; z-index:10;">
                            <div
                                style="text-align:center; padding:20px; background:white; border-radius:12px; box-shadow:0 10px 25px rgba(0,0,0,0.1); border:1px solid #e2e8f0; max-width:80%;">
                                <i class="fas fa-clock mb-3" style="font-size:2.5rem; color:#f59e0b;"></i>
                                <h5 style="font-weight:800; color:#1e293b; margin-bottom:5px;">รอเกณฑ์การประเมินปี 2569</h5>
                                <p style="font-size:0.9rem; color:#64748b; margin:0; line-height:1.4;">ข้อมูลของปีงบประมาณ
                                    2569 จะยังไม่ถูกคำนวณในส่วนนี้ จนกว่าจะมีการกำหนดเกณฑ์ที่ชัดเจน</p>
                            </div>
                        </div>
                    @endif
                </div>
                <div class="chart-figure-footer">
                    <p class="chart-caption">เกณฑ์ผ่าน: ร้อยละความตระหนักรู้ตั้งแต่ 80% ขึ้นไป</p>
                </div>
            </div>
        </div>

        <div class="col-md-6 col-lg-4 col-xl-4 mb-4">
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
                <div class="awr-pf-pills">
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
                <div class="chart-figure-footer" style="margin-top: auto;">
                    @if ($hasPendingCriteria)
                        <p class="chart-caption">* ไม่รวมปีงบประมาณที่ยังไม่ได้ตั้งค่าเกณฑ์ความตระหนักรู้</p>
                    @endif
                    <details>
                        <summary class="chart-table-toggle"><i class="fas fa-caret-right"></i> ดูข้อมูลตัวเลข</summary>
                        <div class="figure-table-wrap">
                            <div id="respondentsTable"></div>
                        </div>
                    </details>
                </div>
            </div>
        </div>

        <div class="col-md-12 col-lg-4 col-xl-5 mb-4">
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
                    <div id="passFailRows" class="pf-rows" role="list" aria-label="สถานะความตระหนักรู้รายจังหวัด"></div>
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

    <!-- แถว 2: เพศ (แคบ) | กลุ่มอายุ (กลาง) | ระดับการศึกษา (กว้าง) -->
    <div class="row mb-4">
        <div class="col-md-6 col-lg-4 col-xl-3 mb-4">
            <div class="chart-card">
                <div class="chart-header" style="--chart-accent:#8b5cf6;">
                    <div class="chart-header-icon"><i class="fas fa-venus-mars"></i></div>
                    <div>
                        <div class="chart-header-title">สัดส่วนผู้ตอบ</div>
                        <div class="chart-header-subtitle">แยกตามเพศ</div>
                    </div>
                    <div id="genderRatioBadge" class="gr-ratio-badge" style="display: none;"></div>
                </div>
                <div id="genderChart" style="height: 320px; width: 100%;"></div>
            </div>
        </div>
        <div class="col-md-6 col-lg-4 col-xl-4 mb-4">
            <div class="chart-card">
                <div class="chart-header" style="--chart-accent:#3b82f6;">
                    <div class="chart-header-icon"><i class="fas fa-users"></i></div>
                    <div>
                        <div class="chart-header-title">สัดส่วนผู้ตอบ</div>
                        <div class="chart-header-subtitle">แยกตามกลุ่มอายุ</div>
                    </div>
                </div>
                <div id="ageChart" style="flex: 1 1 0; min-height: 320px; width: 100%;"></div>
                <div class="chart-figure-footer">
                    <details>
                        <summary class="chart-table-toggle"><i class="fas fa-caret-right"></i> ดูข้อมูลตัวเลข</summary>
                        <div class="figure-table-wrap">
                            <div id="ageTable"></div>
                        </div>
                    </details>
                </div>
            </div>
        </div>
        <div class="col-md-12 col-lg-4 col-xl-5 mb-4">
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
                        <div class="scale-legend" aria-label="คำอธิบายสี">
                            <span class="scale-legend-item"><span class="scale-legend-swatch" style="background:linear-gradient(90deg,#b9534d,#f2a65a)"></span>ควรปรับ</span>
                            <span class="scale-legend-item"><span class="scale-legend-swatch" style="background:#e2e8f0"></span>กลาง</span>
                            <span class="scale-legend-item"><span class="scale-legend-swatch" style="background:linear-gradient(90deg,#8cc0dc,#2b6a9e)"></span>ดี</span>
                            <span class="scale-legend-note">ความยาวแถบ = สัดส่วนผู้ตอบ · เรียงจากข้อที่ควรปรับมากที่สุด</span>
                        </div>
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
                        <div class="scale-legend" aria-label="คำอธิบายสี">
                            <span class="scale-legend-item"><span class="scale-legend-swatch" style="background:linear-gradient(90deg,#b9534d,#f2a65a)"></span>ควรปรับ</span>
                            <span class="scale-legend-item"><span class="scale-legend-swatch" style="background:#e2e8f0"></span>กลาง</span>
                            <span class="scale-legend-item"><span class="scale-legend-swatch" style="background:linear-gradient(90deg,#8cc0dc,#2b6a9e)"></span>ดี</span>
                            <span class="scale-legend-note">ความยาวแถบ = สัดส่วนผู้ตอบ · เรียงจากข้อที่ควรปรับมากที่สุด</span>
                        </div>
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
                        <div class="scale-legend" aria-label="คำอธิบายสี">
                            <span class="scale-legend-item"><span class="scale-legend-swatch" style="background:linear-gradient(90deg,#b9534d,#f2a65a)"></span>ควรปรับ</span>
                            <span class="scale-legend-item"><span class="scale-legend-swatch" style="background:#e2e8f0"></span>กลาง</span>
                            <span class="scale-legend-item"><span class="scale-legend-swatch" style="background:linear-gradient(90deg,#8cc0dc,#2b6a9e)"></span>ดี</span>
                            <span class="scale-legend-note">ความยาวแถบ = สัดส่วนผู้ตอบ · เรียงจากข้อที่ควรปรับมากที่สุด</span>
                        </div>
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
                        <div class="scale-legend" aria-label="คำอธิบายสี">
                            <span class="scale-legend-item"><span class="scale-legend-swatch" style="background:linear-gradient(90deg,#b9534d,#f2a65a)"></span>ควรปรับ</span>
                            <span class="scale-legend-item"><span class="scale-legend-swatch" style="background:#e2e8f0"></span>กลาง</span>
                            <span class="scale-legend-item"><span class="scale-legend-swatch" style="background:linear-gradient(90deg,#8cc0dc,#2b6a9e)"></span>ดี</span>
                            <span class="scale-legend-note">ความยาวแถบ = สัดส่วนผู้ตอบ · เรียงจากข้อที่ควรปรับมากที่สุด</span>
                        </div>
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
@include('pages.partials.filter-dropdown')
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
                'ไม่เคยศึกษา/ไม่ได้เรียนหนังสือ': '#64748b', // slate
                'ประถมศึกษา': '#f5a524', // amber
                'มัธยมศึกษา': '#12a594', // teal
                'ปวช./ปวส./อนุปริญญา': '#2f6fdd', // blue
                'ปริญญาตรี': '#8e5cf0', // violet
                'ปริญญาโท/สูงกว่า': '#d6409f', // magenta
                'สูงกว่าปริญญาโท': '#ee5a3c' // coral
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

            // --- สัดส่วนผู้ตอบแยกตามเพศ: แถวสถิติซ้อนกัน (หญิง → ชาย → อื่น ๆ)
            //     แต่ละแถว = ไอคอนวงกลม + ชื่อ + ร้อยละตัวใหญ่ + จำนวนคนชิดขวา
            //     เส้นบางด้านล่างของแถวบอกสัดส่วนเทียบผู้ตอบทั้งหมด
            //     อัตราส่วนชาย : หญิง อยู่ที่ป้ายมุมขวาบนของหัวการ์ด ---
            const renderGenderRatio = (containerId, data, colorMap, fallback) => {
                const container = document.getElementById(containerId);
                if (!container) return;
                const esc = (t) => String(t).replace(/[&<>"']/g, (c) => ({'&':'&amp;','<':'&lt;','>':'&gt;','"':'&quot;',"'":'&#39;'}[c]));
                const iconFor = (label) => label === 'ชาย' ? 'fa-mars' : (label === 'หญิง' ? 'fa-venus' : 'fa-genderless');
                const labelFor = (label) => label === 'ชาย' ? 'ชาย (Male)' : (label === 'หญิง' ? 'หญิง (Female)' : label);
                const order = {'หญิง': 0, 'ชาย': 1};
                const entries = Object.entries(data || {})
                    .map(([label, v]) => [label, Number(v) || 0])
                    .sort((x, y) => (order[x[0]] ?? 9) - (order[y[0]] ?? 9));
                const total = entries.reduce((sum, [, v]) => sum + v, 0);
                const badge = document.getElementById('genderRatioBadge');

                container.style.height = 'auto';
                container.style.minHeight = '300px';
                container.style.display = 'flex';
                container.style.flexDirection = 'column';

                if (total <= 0) {
                    if (badge) badge.style.display = 'none';
                    container.innerHTML = '<div class="gr-empty">ยังไม่มีผู้ตอบในช่วงที่เลือก</div>';
                    return;
                }

                let rows = '';
                entries.forEach(([label, value]) => {
                    const pct = (value / total) * 100;
                    const base = colorMap[label] || fallback;
                    rows += '<div class="gr-row" style="--gr-c:' + base + '; --gr-ink:' + shadeHexColor(base, -22) + '; --gr-w:' + pct.toFixed(2) + '%;">' +
                        '<span class="gr-icon" aria-hidden="true"><i class="fas ' + iconFor(label) + '"></i></span>' +
                        '<span class="gr-main"><span class="gr-name">' + esc(labelFor(label)) + '</span>' +
                        '<span class="gr-pct">' + pct.toFixed(1) + '%</span></span>' +
                        '<span class="gr-count">' + value.toLocaleString('en-US') + ' คน</span></div>';
                });
                container.innerHTML = '<div class="gr-rows">' + rows + '</div>';

                const male = Number(data['ชาย']) || 0;
                const female = Number(data['หญิง']) || 0;
                if (badge) {
                    if (male > 0 && female > 0) {
                        const r = female / male;
                        badge.innerHTML = '<small>ชาย : หญิง</small><b>1 : ' + (r >= 10 ? r.toFixed(1) : r.toFixed(2)) + '</b>';
                        badge.style.display = '';
                    } else {
                        badge.style.display = 'none';
                    }
                }
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
                    baseColor: colorMap[label] || fallback,
                    // ไล่เฉดแบบทรงกลม: สว่างบน-เข้มล่าง ให้ชิ้นพายดูมีมิติ
                    color: {
                        radialGradient: { cx: 0.35, cy: 0.25, r: 1.0 },
                        stops: [
                            [0, shadeHexColor(colorMap[label] || fallback, 22)],
                            [0.55, colorMap[label] || fallback],
                            [1, shadeHexColor(colorMap[label] || fallback, -14)]
                        ]
                    }
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
                                color: 'rgba(15, 23, 42, 0.28)',
                                offsetX: 0,
                                offsetY: 7,
                                width: 12
                            },
                            slicedOffset: 8,
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
                                // ป้ายร้อยละเป็นแคปซูลสีของส่วนนั้น (ตัวหนังสือขาว/เข้มเลือกตามความสว่างของสี)
                                // ตามด้วยชื่อกลุ่มอายุตัวหนาเข้ม และจำนวนคนตัวรอง
                                formatter: function () {
                                    const hex = String(this.point.options.baseColor || '#64748b').replace('#', '');
                                    const full = hex.length === 3 ? hex.split('').map(ch => ch + ch).join('') : hex;
                                    const r = parseInt(full.substr(0, 2), 16), g = parseInt(full.substr(2, 2), 16), bl = parseInt(full.substr(4, 2), 16);
                                    const light = (0.299 * r + 0.587 * g + 0.114 * bl) > 150;
                                    const ink = light ? '#422006' : '#ffffff';
                                    const glow = light ? 'none' : '0 1px 2px rgba(15,23,42,0.35)';
                                    return '<div style="text-align:center; line-height:1.3;">' +
                                        '<span style="display:inline-block; background:' + this.point.options.baseColor + '; color:' + ink + '; font-weight:800; font-size:15px; letter-spacing:-.2px; padding:2px 11px 3px; border-radius:999px; white-space:nowrap; text-shadow:' + glow + '; box-shadow:0 4px 10px -4px ' + this.point.options.baseColor + ';">' + Highcharts.numberFormat(this.percentage, 1) + '%</span>' +
                                        '<br/><span style="display:inline-block; margin-top:3px; color:#0f172a; font-weight:800; font-size:13.5px; white-space:nowrap;">' + this.point.name + '</span>' +
                                        '<br/><span style="color:#334155; font-weight:700; font-size:12px; white-space:nowrap;">' + Highcharts.numberFormat(this.y, 0) + ' คน</span>' +
                                        '</div>';
                                },
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
            // One distinct hue per education level (educationColorMap), pill
            // bars on a pale track so every bar reads against the same full
            // length, a colour dot beside each level name, and a badge at the
            // right edge with the count and its share. Sorted by count
            // (largest at the top).
            const createEducationBarChart = (containerId, data, colorMap, fallback, order) => {
                const sortedData = Object.entries(data).sort(([, valA], [, valB]) => valB - valA);
                const labels = sortedData.map(([label]) => label);
                const values = sortedData.map(([, value]) => value);
                const total = values.reduce((a, b) => a + b, 0);
                const maxVal = Math.max(...values, 0);
                const trackMax = maxVal > 0 ? maxVal * 1.38 : 1;
                const colorOf = (label) => colorMap[label] || fallback;

                const barColors = labels.map(label => {
                    const base = colorOf(label);
                    return {
                        linearGradient: { x1: 0, y1: 0, x2: 1, y2: 0 },
                        stops: [
                            [0, shadeHexColor(base, 14)],
                            [1, base]
                        ]
                    };
                });
                const trackData = values.map((v, i) => ({
                    y: trackMax,
                    custom: {
                        value: v,
                        pct: total > 0 ? (v / total) * 100 : 0,
                        color: colorOf(labels[i])
                    }
                }));

                Highcharts.chart(containerId, {
                    chart: {
                        type: 'bar',
                        backgroundColor: 'transparent',
                        spacingTop: 8,
                        spacingRight: 8,
                        style: { fontFamily: 'Sarabun' }
                    },
                    title: { text: '' },
                    xAxis: {
                        categories: labels,
                        lineWidth: 0,
                        tickLength: 0,
                        labels: {
                            useHTML: true,
                            style: { width: '150px', textOverflow: 'none' },
                            formatter: function() {
                                const c = colorOf(this.value);
                                return '<span style="display:inline-flex; align-items:center; gap:8px; font-size:12px; font-weight:600; color:#1e293b; width:150px; white-space:normal; line-height:1.25;">' +
                                    '<i style="flex:0 0 10px; width:10px; height:10px; border-radius:50%; background:' + c + '; box-shadow:0 0 0 3px ' + c + '26;"></i>' +
                                    '<span>' + this.value + '</span></span>';
                            }
                        }
                    },
                    yAxis: {
                        title: { text: '' },
                        gridLineWidth: 0,
                        labels: { enabled: false },
                        min: 0,
                        max: trackMax
                    },
                    legend: { enabled: false },
                    tooltip: {
                        useHTML: true,
                        borderRadius: 10,
                        borderWidth: 0,
                        shadow: true,
                        formatter: function() {
                            const v = this.series.name === 'track' ? this.point.custom.value : this.y;
                            const pct = total > 0 ? ((v / total) * 100).toFixed(1) : 0;
                            return '<b>' + this.x + '</b><br/>จำนวน: <b>' + Highcharts.numberFormat(v, 0) + ' คน</b> (' + pct + '%)';
                        }
                    },
                    plotOptions: {
                        bar: {
                            grouping: false,
                            borderWidth: 0,
                            borderRadius: 13,
                            pointWidth: 26,
                            states: { hover: { brightness: 0.06 }, inactive: { opacity: 1 } }
                        }
                    },
                    series: [{
                        name: 'track',
                        data: trackData,
                        color: '#eef2f7',
                        enableMouseTracking: true,
                        states: { hover: { enabled: false } },
                        dataLabels: {
                            enabled: true,
                            useHTML: true,
                            inside: true,
                            align: 'right',
                            x: -4,
                            crop: false,
                            overflow: 'allow',
                            zIndex: 5,
                            formatter: function() {
                                const c = this.point.custom;
                                return '<span style="display:inline-flex; align-items:baseline; gap:6px; background:#fff; padding:3px 10px; border-radius:999px; box-shadow:0 1px 4px rgba(15,23,42,0.14); white-space:nowrap;">' +
                                    '<b style="color:' + c.color + '; font-size:13px;">' + Highcharts.numberFormat(c.value, 0) + '</b>' +
                                    '<span style="color:#64748b; font-size:11px; font-weight:600;">' + c.pct.toFixed(1) + '%</span></span>';
                            }
                        }
                    }, {
                        name: 'จำนวน',
                        colorByPoint: true,
                        colors: barColors,
                        data: values,
                        dataLabels: { enabled: false },
                        zIndex: 2
                    }],
                    credits: { enabled: false }
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
                    cutout: '72%',
                    layout: { padding: 8 },
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
                            const val = chart.data.datasets[0].data[item.index] || 0;
                            const pct = total > 0 ? (val / total) * 100 : 0;

                            const row = document.createElement('div');
                            row.className = 'awr-leg-item';
                            row.style.setProperty('--leg-c', item.fillStyle && typeof item.fillStyle === 'string' ? item.fillStyle : (provinceColorMap[item.text] || NEUTRAL_FALLBACK));
                            row.style.setProperty('--leg-w', Math.max(pct, 2).toFixed(1) + '%');
                            row.title = item.text + ' ' + val.toLocaleString() + ' คน (' + pct.toFixed(1) + '%)';

                            const dot = document.createElement('span');
                            dot.className = 'awr-leg-dot';

                            const main = document.createElement('span');
                            main.className = 'awr-leg-main';
                            const name = document.createElement('span');
                            name.className = 'awr-leg-name';
                            name.textContent = item.text;
                            const bar = document.createElement('span');
                            bar.className = 'awr-leg-bar';
                            bar.appendChild(document.createElement('span'));
                            main.appendChild(name);
                            main.appendChild(bar);

                            const nums = document.createElement('span');
                            nums.className = 'awr-leg-nums';
                            const p = document.createElement('b');
                            p.textContent = pct.toFixed(1) + '%';
                            const c = document.createElement('small');
                            c.textContent = val.toLocaleString() + ' คน';
                            nums.appendChild(p);
                            nums.appendChild(c);

                            row.appendChild(dot);
                            row.appendChild(main);
                            row.appendChild(nums);
                            dl.appendChild(row);
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
            // Rendered as HTML vertical columns (not a stacked count chart):
            // every province is a 100% column - green at the bottom is the
            // share who are aware, so provinces with very different
            // respondent counts compare by share, and the exact counts sit
            // under each name.
            (function renderPassFailColumns() {
                const host = document.getElementById('passFailRows');
                if (!host) return;
                if (pfEntries.length === 0) {
                    host.innerHTML = '<div class="text-center text-muted">ยังไม่มีข้อมูล</div>';
                    return;
                }
                const esc = (t) => String(t).replace(/[&<>"']/g, (c) => ({'&':'&amp;','<':'&lt;','>':'&gt;','"':'&quot;',"'":'&#39;'}[c]));
                const fmt = (n) => Number(n || 0).toLocaleString();
                const first = pfEntries[0];
                const last = pfEntries[pfEntries.length - 1];
                let html = '<div class="pf-top">';
                if (pfEntries.length > 1) {
                    html += '<span class="pf-sum">สูงสุด <b>' + esc(first.name) + ' ' + (first.rate * 100).toFixed(1) +
                        '%</b> และต่ำสุด <b style="color:#b42323">' + esc(last.name) + ' ' + (last.rate * 100).toFixed(1) + '%</b></span>';
                } else {
                    html += '<span class="pf-sum"><b>' + esc(first.name) + ' ' + (first.rate * 100).toFixed(1) + '%</b> ตระหนักรู้</span>';
                }
                html += '<span class="pf-legend"><span><i style="background:#10b981"></i>ตระหนักรู้</span>' +
                    '<span><i style="background:#f87171"></i>ยังไม่ตระหนัก</span></span></div>';
                html += '<div class="pf-cols" style="--pf-n:' + pfEntries.length + '">';
                pfEntries.forEach((e) => {
                    const total = (e.pass || 0) + (e.fail || 0);
                    const pPct = total > 0 ? (e.pass / total) * 100 : 0;
                    const fPct = total > 0 ? 100 - pPct : 0;
                    html += '<div class="pf-col" role="listitem" title="' + esc(e.name) + ': ตระหนักรู้ ' + fmt(e.pass) + ' จาก ' + fmt(total) + ' คน (' + pPct.toFixed(1) + '%)">' +
                        '<div class="pf-badge"><div class="pf-pct">' + pPct.toFixed(1) + '%</div>' +
                        '<div class="pf-pct-cap">ตระหนักรู้</div></div>' +
                        '<div class="pf-tube">' +
                        '<div class="pf-tube-fail">' + fPct.toFixed(0) + '%</div>' +
                        '<div class="pf-tube-pass" style="height:' + pPct.toFixed(2) + '%"></div></div>' +
                        '<div class="pf-name">' + esc(e.name) + '</div>' +
                        '<div class="pf-n"><b>' + fmt(e.pass) + '</b> จาก ' + fmt(total) + ' คน</div></div>';
                });
                html += '</div>';
                host.innerHTML = html;
            })();


            // --- 5. Behavioral Charts (full-width diverging bar rows, one
            //     per question, matching the approved "ออกแบบกราฟพฤติกรรม
            //     โซเดียม" mockup) ---
            const createDivergingBars = (containerId, tableContainerId, statsData, polarityOverrides, extremeValuesOverrides) => {
                const categories = Object.keys(statsData);
                const container = document.getElementById(containerId);
                if (!container) return;
                if (categories.length === 0) {
                    container.innerHTML = '<div class="divrow-empty"><i class="fas fa-circle-info"></i> ยังไม่ได้กำหนดคำถามสำหรับการ์ดนี้ในปีงบประมาณที่เลือก ' +
                        '(ตั้งค่าได้ที่ ตั้งค่าแดชบอร์ด)</div>';
                    return;
                }

                polarityOverrides = polarityOverrides || {};
                extremeValuesOverrides = extremeValuesOverrides || {};

                // Traffic-light ramp: red (risky) -> orange -> yellow
                // (neutral) -> light green -> dark green (healthy), matching
                // the reference palette requested for the dashboard.
                // สีแบบสองขั้ว ส้ม-แดง (ควรปรับ) <-> ฟ้า-น้ำเงิน (ดี) ผ่านเทากลาง: ส้ม/ฟ้าแยกได้ชัดแม้ตาบอดสีแดง-เขียว
                // ปลายสองข้างเข้มสุด ตรงกลางอ่อนสุด จึงอ่านระดับความเสี่ยงได้ทันทีจากความเข้ม
                const rankColors = ['#b9534d', '#f2a65a', '#e2e8f0', '#8cc0dc', '#2b6a9e'];
                const rankTextColors = ['#ffffff', '#4a2606', '#334155', '#0c3550', '#ffffff'];

                // Semantic Keyword Mapping (Map ANY label to 0-4 scale: High Freq -> Low Freq)
                // ระดับคำตอบมาตรฐาน (มาก -> น้อย) ที่พบจริงในแบบสอบถาม: ใช้ตรงตัวก่อน
                // เพื่อให้ทั้ง 5 ระดับเรียงต่อเนื่องและไม่ปนกัน (เดิม "บ่อยครั้ง" ถูกนับเป็นกลาง,
                // "ไม่ทานเลย" ไม่ถูกจัดเป็นปลายดี, และ "ไม่เห็นด้วย" ทั้งสองระดับถูกรวมเป็นระดับเดียว)
                const ORDINAL_RANK = {
                    'ทุกวัน': 0, 'บ่อยครั้ง': 1, 'บ่อย': 1, 'บ่อยๆ': 1, 'บางครั้ง': 2, 'นานๆครั้ง': 3,
                    'ไม่ทานเลย': 4, 'ไม่ทาน': 4, 'ไม่เคย': 4,
                    'เห็นด้วยอย่างยิ่ง': 0, 'เห็นด้วย': 1, 'เฉยๆ': 2, 'ไม่แน่ใจ': 2,
                    'ไม่เห็นด้วย': 3, 'ไม่เห็นด้วยอย่างยิ่ง': 4
                };

                const getSemanticRank = (label) => {
                    label = label.toString().trim().replace(/\s/g, '');
                    if (Object.prototype.hasOwnProperty.call(ORDINAL_RANK, label)) return ORDINAL_RANK[label];
                    // Lowest Frequency / Best for Negative Polarity (Or Highest Score for +)
                    if (/^([9]|10)$|ไม่เคย|ไม่เลย|ไม่ใช่|ไม่สุ่มเสี่ยง|0%|ไม่เห็นด้วย/i.test(label))
                        return 4;
                    // Infrequent
                    if (/^([7-8])$|นานๆครั้ง|1-2ครั้ง|น้อยกว่า50%/i.test(label)) return 3;
                    // "บ่อย" (ตรงตัว) = ความถี่สูงรองจากทุกวัน -> ฝั่งควรปรับ, ไม่ใช่กลาง
                    if (/^บ่อย(ๆ)?$/.test(label)) return 1;
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

                // คำถามที่คำตอบทุกตัวอยู่ในมาตรามาตรฐาน (ความถี่ / ระดับความเห็นด้วย) มีลำดับในตัวอยู่แล้ว -
                // ใช้ลำดับนั้นเลย และให้ "ทิศทาง" (chart_polarity) เป็นตัวกำหนดว่าปลายไหนดี/เสี่ยง.
                // ไม่ใช้รายการ "ความถี่สูงสุด" ที่เลือกไว้มาบังคับ เพราะถ้าเผลอติ๊กปลายผิดด้าน
                // (เช่น ติ๊ก "ไม่เห็นด้วย" ในข้อที่เห็นด้วย = ดี) กราฟจะกลับด้านจากความจริง
                const ordinalScaleCategories = new Set();
                categories.forEach(c => {
                    const answers = Object.keys(statsData[c] || {});
                    if (answers.length > 0 && answers.every(a => Object.prototype.hasOwnProperty.call(ORDINAL_RANK, a.toString().trim().replace(/\s/g, '')))) {
                        ordinalScaleCategories.add(c);
                    }
                });

                const getRank = (category, label) => {
                    const overrides = extremeValuesOverrides[category];
                    if (overrides && !degenerateOverrideCategories.has(category) && !ordinalScaleCategories.has(category) && overrides.indexOf(label) !== -1) return 0;
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
                    const rows = [];
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
                                return { pct: pct, count: count, rank: finalRank, title: ans };
                            })
                            .filter(s => s !== null)
                            .sort((a, b) => a.rank - b.rank);

                        if (segments.length === 0) return;

                        // สัดส่วน "ควรปรับ" = ผู้ตอบที่อยู่ปลายส้มสองระดับ (rank 0-1)
                        const risk = segments.filter(s => s.rank <= 1).reduce((a, s) => a + s.pct, 0);
                        rows.push({ c: c, segments: segments, risk: risk, total: total });
                    });

                    // เรียงจากข้อที่ควรปรับมากที่สุด -> อ่านเป็นลำดับความสำคัญได้ทันที
                    rows.sort((a, b) => b.risk - a.risk);

                    let html = '';
                    // สรุป "ควรปรับมากที่สุด" ไปอยู่แถวเดียวกับหัวข้อการ์ด (ชิดขวา)
                    const headerEl = container.closest('.chart-card')
                        ? container.closest('.chart-card').querySelector('.chart-header') : null;
                    if (headerEl) {
                        headerEl.querySelectorAll('.divsum').forEach(function (el) { el.remove(); });
                    }
                    if (rows.length > 1 && rows[0].risk > 0) {
                        const sumHtml = '<div class="divsum"><i class="fas fa-lightbulb"></i><span class="divsum-text"><small>ควรปรับมากที่สุด</small><b>' +
                            escapeHtml(rows[0].c) + '</b></span><em class="divsum-pct">' + Math.round(rows[0].risk) + '%</em></div>';
                        if (headerEl) {
                            headerEl.insertAdjacentHTML('beforeend', sumHtml);
                        } else {
                            html += sumHtml;
                        }
                    }

                    rows.forEach(r => {
                        const segHtml = r.segments.map(s => {
                            // ชื่อคำตอบแสดงในแถบเมื่อกว้างพอ, ตัวเลข % เมื่อกว้างพอ, ที่เหลืออยู่ใน tooltip และบรรทัด "อื่น ๆ"
                            const showName = s.pct >= 15;
                            const showPct = s.pct >= 7;
                            return '<div class="divrow-seg" style="flex:' + s.pct.toFixed(3) + ' 1 0%;background:' +
                                rankColors[s.rank] + ';color:' + rankTextColors[s.rank] + '" title="' +
                                escapeHtml(s.title) + ': ' + s.pct.toFixed(1) + '% (' + s.count.toLocaleString() + ' คน)">' +
                                (showPct ? '<b>' + Math.round(s.pct) + '%</b>' : '') +
                                (showName ? '<small>' + escapeHtml(s.title) + '</small>' : '') + '</div>';
                        }).join('');

                        const hidden = r.segments.filter(s => s.pct < 15);
                        const othersHtml = hidden.length ? '<div class="divrow-others">' + hidden.map(s =>
                            '<span class="divrow-legend-item"><span class="divrow-legend-dot" style="background:' +
                            rankColors[s.rank] + '"></span>' + escapeHtml(s.title) + ' ' + s.pct.toFixed(1) + '%</span>'
                        ).join('') + '</div>' : '<div></div>';

                        html += '<div class="divrow"><div class="divrow-head"><div class="divrow-label">' + escapeHtml(r.c) +
                            '</div><span class="divrow-risk' + (r.risk < 10 ? ' is-low' : '') + '" title="ผู้ตอบที่อยู่ในกลุ่มคำตอบที่ควรปรับ">ควรปรับ ' +
                            Math.round(r.risk) + '%</span></div>' +
                            '<div class="divrow-bar">' + segHtml + '</div>' +
                            (hidden.length ? '<div class="divrow-foot">' + othersHtml + '</div>' : '') + '</div>';
                    });

                    container.innerHTML = html;
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

            // กลุ่มอายุ: คงลำดับช่วงอายุตามข้อมูล (ordinal) ไม่เรียงตามจำนวน
            const renderAgeTable = (containerId, data) => {
                const el = document.getElementById(containerId);
                if (!el) return;
                const entries = Object.entries(data);
                const total = entries.reduce((sum, [, v]) => sum + v, 0);
                if (entries.length === 0 || total <= 0) {
                    el.innerHTML = '';
                    return;
                }
                let html =
                    '<table class="figure-table"><thead><tr><th>กลุ่มอายุ</th><th>จำนวน (คน)</th><th>ร้อยละ (%)</th></tr></thead><tbody>';
                entries.forEach(([label, value]) => {
                    const pct = value / total * 100;
                    html += '<tr' + (pct >= 20 ? ' class="row-highlight"' : '') + '><td>' + escapeHtml(label.trim()) + '</td><td>' +
                        Number(value).toLocaleString() + '</td><td>' + pct.toFixed(1) + '%</td></tr>';
                });
                html += '<tr class="group-start"><td><b>รวม</b></td><td><b>' + total.toLocaleString() + '</b></td><td><b>100.0%</b></td></tr>';
                html += '</tbody></table>';
                el.innerHTML = html;
            };

            // ร้อยละผู้ตอบแบบประเมิน แยกรายจังหวัด (ตารางคู่กับกราฟโดนัท)
            const renderRespondentsTable = (containerId, labels, values) => {
                const el = document.getElementById(containerId);
                if (!el) return;
                const total = values.reduce((sum, v) => sum + (Number(v) || 0), 0);
                if (labels.length === 0 || total <= 0) {
                    el.innerHTML = '';
                    return;
                }
                const rows = labels.map((l, i) => ({ label: l, value: Number(values[i]) || 0 }))
                    .sort((a, b) => b.value - a.value);
                let html =
                    '<table class="figure-table"><thead><tr><th>จังหวัด</th><th>จำนวนผู้ตอบ (คน)</th><th>ร้อยละ (%)</th></tr></thead><tbody>';
                rows.forEach(r => {
                    const pct = r.value / total * 100;
                    html += '<tr' + (pct >= 20 ? ' class="row-highlight"' : '') + '><td>' + escapeHtml(String(r.label)) +
                        '</td><td>' + r.value.toLocaleString() + '</td><td>' + pct.toFixed(1) + '%</td></tr>';
                });
                html += '<tr class="group-start"><td><b>รวม</b></td><td><b>' + total.toLocaleString() +
                    '</b></td><td><b>100.0%</b></td></tr></tbody></table>';
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
                    '<table class="figure-table"><thead><tr><th>จังหวัด</th><th>แบบประเมินทั้งหมด</th><th>ตระหนักรู้ (คน)</th><th>ไม่ตระหนัก (คน)</th><th>ร้อยละตระหนักรู้ (%)</th></tr></thead><tbody>';
                let sumPass = 0, sumFail = 0;
                provinces.forEach((p) => {
                    const pass = data[p].pass || 0;
                    const fail = data[p].fail || 0;
                    const total = pass + fail;
                    const pct = total > 0 ? (pass / total * 100) : 0;
                    sumPass += pass; sumFail += fail;
                    html += '<tr><td>' + escapeHtml(p) + '</td><td>' + total.toLocaleString() +
                        '</td><td>' + pass.toLocaleString() +
                        '</td><td>' + fail.toLocaleString() + '</td><td>' + pct.toFixed(1) +
                        '%</td></tr>';
                });
                const sumAll = sumPass + sumFail;
                html += '<tr class="group-start"><td><b>รวม</b></td><td><b>' + sumAll.toLocaleString() +
                    '</b></td><td><b>' + sumPass.toLocaleString() + '</b></td><td><b>' + sumFail.toLocaleString() +
                    '</b></td><td><b>' + (sumAll > 0 ? (sumPass / sumAll * 100).toFixed(1) : '0.0') + '%</b></td></tr>';
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
            renderAgeTable('ageTable', ageStats);
            renderRespondentsTable('respondentsTable', provLabels, provValues);
            renderPassFailTable('passFailTable', passFailByProvince);

            // --- แผนที่: ร้อยละความตระหนักรู้รายจังหวัด (ร้อยละ = ผ่านเกณฑ์ / ผู้ตอบทั้งหมด ของจังหวัดนั้น
            //     ตัวเลขชุดเดียวกับการ์ด "สถานะความตระหนักรู้" จึงตรงกันเสมอ) ---
            (function renderAwarenessMap() {
                const host = document.getElementById('awarenessMap');
                const thMap = typeof Highcharts !== 'undefined' && Highcharts.maps && Highcharts.maps['countries/th/th-all'];
                if (!host || !thMap || typeof Highcharts.mapChart !== 'function') return;

                const hcMapKeys = {
                    'อุบลราชธานี': 'th-ur', 'ศรีสะเกษ': 'th-si', 'ยโสธร': 'th-ys',
                    'อำนาจเจริญ': 'th-ac', 'มุกดาหาร': 'th-md'
                };
                // ขยับป้ายของจังหวัดที่พื้นที่แคบ/ติดกัน ไม่ให้ป้ายทับกัน (พิกเซลจากกึ่งกลางพื้นที่)
                const labelOffsets = {
                    'ยโสธร': { x: -26, y: -4 }, 'อำนาจเจริญ': { x: 16, y: 10 },
                    'อุบลราชธานี': { x: 18, y: 16 }, 'ศรีสะเกษ': { x: -6, y: 10 }
                };
                const points = Object.entries(passFailByProvince)
                    .filter(([name]) => hcMapKeys[name])
                    .map(([name, v]) => {
                        const pass = v.pass || 0;
                        const total = pass + (v.fail || 0);
                        return {
                            name,
                            'hc-key': hcMapKeys[name],
                            dataLabels: labelOffsets[name] || {},
                            value: total > 0 ? Math.round(pass / total * 10000) / 100 : 0,
                            total_people: total,
                            passed_count: pass
                        };
                    });

                const targetKeys = Object.values(hcMapKeys);
                const regionMap = { ...thMap, features: thMap.features.filter(f => targetKeys.includes(f.properties['hc-key'])) };

                Highcharts.mapChart('awarenessMap', {
                    chart: { map: regionMap, backgroundColor: 'transparent', spacing: [4, 4, 4, 4] },
                    title: { text: '' },
                    credits: { enabled: false },
                    mapNavigation: { enabled: false },
                    colorAxis: {
                        dataClasses: [
                            { to: 79.99, color: '#eb7a72', name: 'ไม่ผ่านเกณฑ์ ( < 80% )' },
                            { from: 80, color: '#4dbd98', name: 'ผ่านเกณฑ์ ( ≥ 80% )' }
                        ]
                    },
                    legend: {
                        enabled: true, layout: 'horizontal', align: 'center', verticalAlign: 'bottom',
                        itemStyle: { fontWeight: 'normal', fontSize: '12px' }, itemMarginTop: 6
                    },
                    tooltip: {
                        useHTML: true, outside: true, padding: 0, borderWidth: 0, shadow: true,
                        backgroundColor: 'rgba(255, 255, 255, 0.98)', headerFormat: '',
                        pointFormatter: function () {
                            const ok = this.value >= 80;
                            const color = ok ? '#10b981' : '#ef4444';
                            const row = (label, val, strong) =>
                                '<tr><td style="color:#64748b; padding:4px 0;">' + label + '</td>' +
                                '<td style="text-align:right; font-weight:' + (strong ? 800 : 700) + '; color:#334155;' + (strong ? ' font-size:1.1rem;' : '') + '">' + val + '</td></tr>';
                            return '<div style="padding:15px; min-width:220px; font-family:\'Sarabun\', sans-serif;">' +
                                '<div style="font-size:16px; font-weight:800; color:#1e293b; margin-bottom:12px; border-bottom:1px solid #f1f5f9; padding-bottom:8px;">' + this.name + '</div>' +
                                '<table style="width:100%; border-collapse:collapse;">' +
                                row('แบบประเมินทั้งหมด:', this.total_people.toLocaleString() + ' <small>คน</small>') +
                                row('ระดับความตระหนักรู้:', this.passed_count.toLocaleString() + ' <small>คน</small>') +
                                row('ร้อยละความตระหนักรู้:', this.value.toLocaleString() + '%', true) +
                                '</table>' +
                                '<div style="margin-top:12px; padding:8px; border-radius:6px; text-align:center; background:' + (ok ? '#f0fdf4' : '#fef2f2') + '; border:1px solid ' + color + '20;">' +
                                '<span style="color:' + color + '; font-weight:800; font-size:12px;">สถานะ: ' + (ok ? 'ผ่านเกณฑ์' : 'ไม่ผ่านเกณฑ์') + ' (เป้าหมาย 80%)</span></div></div>';
                        }
                    },
                    plotOptions: {
                        map: {
                            allAreas: false, joinBy: ['hc-key', 'hc-key'], borderColor: '#FFFFFF', borderWidth: 2,
                            states: { hover: { enabled: true, brightness: 0, borderColor: '#1e293b', borderWidth: 3 } },
                            dataLabels: {
                                enabled: true, useHTML: true, allowOverlap: true, crop: false, overflow: 'allow',
                                formatter: function () {
                                    const pctColor = (this.point.value || 0) >= 80 ? '#047857' : '#b91c1c';
                                    return '<div style="text-align:center; line-height:1.15; font-family:\'Sarabun\', sans-serif; pointer-events:none; ' +
                                        'background:rgba(255,255,255,0.93); border-radius:9px; padding:1px 6px 2px; box-shadow:0 1px 4px rgba(15,23,42,0.25); white-space:nowrap;">' +
                                        '<div style="font-weight:700; font-size:11px; color:#1e293b;">' + this.point.name + '</div>' +
                                        '<div style="font-size:14px; font-weight:800; color:' + pctColor + ';">' + (this.point.value || 0) + '%</div></div>';
                                },
                                style: { textOutline: 'none', fontFamily: "'Sarabun', sans-serif", pointerEvents: 'none' }
                            }
                        }
                    },
                    series: [{ data: points, name: 'ร้อยละความตระหนักรู้' }]
                });
            })();
        });

        // ปิดแผงตารางตัวเลขเมื่อคลิกที่อื่นหรือกด Esc
        document.addEventListener('click', (e) => {
            document.querySelectorAll('.chart-figure-footer details[open]').forEach((d) => {
                if (!d.contains(e.target)) d.removeAttribute('open');
            });
        });
        document.addEventListener('keydown', (e) => {
            if (e.key !== 'Escape') return;
            document.querySelectorAll('.chart-figure-footer details[open]').forEach((d) => d.removeAttribute('open'));
        });
    </script>

    <script>
        // วางตารางตัวเลขให้เต็มการ์ด: เริ่มใต้หัวข้อ จบเหนือปุ่ม "ดูข้อมูลตัวเลข"
        (function () {
            function place(details) {
                var wrap = details.querySelector('.figure-table-wrap');
                var card = details.closest('.chart-card');
                if (!wrap || !card) return;
                var head = card.querySelector('.chart-header');
                var cardRect = card.getBoundingClientRect();
                var top = head ? head.getBoundingClientRect().bottom - cardRect.top + 10 : 92;
                var bottom = cardRect.bottom - details.getBoundingClientRect().top + 8;
                wrap.style.top = top + 'px';
                wrap.style.bottom = bottom + 'px';
            }
            document.addEventListener('toggle', function (e) {
                var d = e.target;
                if (d && d.tagName === 'DETAILS' && d.open && d.closest('.chart-figure-footer')) place(d);
            }, true);
            window.addEventListener('resize', function () {
                document.querySelectorAll('.chart-figure-footer details[open]').forEach(place);
            });
        })();
    </script>
@endsection
