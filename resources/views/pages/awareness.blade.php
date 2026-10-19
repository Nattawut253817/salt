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
        /* ปรับให้ดูดีขึ้น: พื้นไล่สีอ่อนตามเพศ + แถบสัดส่วนเต็มความกว้าง + ตัวเลขจำนวนคนเป็นแคปซูล */
        .gr-row { background: linear-gradient(120deg, color-mix(in srgb, var(--gr-c) 15%, #fff) 0%, color-mix(in srgb, var(--gr-c) 4%, #fff) 80%);
            border: 1px solid color-mix(in srgb, var(--gr-c) 22%, #fff); box-shadow: 0 10px 18px -16px var(--gr-c); }
        .gr-row::after { display: none; }
        .gr-name { font-size: 14px; font-weight: 800; color: color-mix(in srgb, var(--gr-c) 45%, #334155); }
        .gr-track { display: block; margin-top: 9px; height: 8px; border-radius: 99px; background: color-mix(in srgb, var(--gr-c) 16%, #fff); overflow: hidden; }
        .gr-track i { display: block; height: 100%; width: var(--gr-w); border-radius: 99px; background: linear-gradient(90deg, color-mix(in srgb, var(--gr-c) 70%, #fff), var(--gr-c)); box-shadow: 0 0 8px -1px var(--gr-c); }
        .gr-count { display: flex; flex-direction: column; align-items: center; padding: 6px 14px 7px; border-radius: 14px; background: rgba(255,255,255,.85);
            border: 1px solid color-mix(in srgb, var(--gr-c) 25%, #fff); line-height: 1.1; }
        .gr-count b { font-size: 17px; font-weight: 800; color: var(--gr-ink); }
        .gr-count small { font-size: 11.5px; font-weight: 700; color: #64748b; }
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

        /* ปรับแท่งให้มีมิติ: หลอดแก้วนุ่ม + ส่วนที่ตระหนักรู้เป็นของเหลวเงา + ป้ายร้อยละเด่น */
        .pf-cols { gap: 14px; padding-top: 2px; }
        .pf-badge {
            max-width: 92px; margin-bottom: 11px; padding: 7px 4px 6px; border-radius: 14px;
            background: linear-gradient(180deg, #ffffff 0%, #d7f8e8 100%);
            border: 1px solid #9fe8c6;
            box-shadow: 0 1px 0 #fff inset, 0 4px 8px -6px rgba(5, 150, 105, .4);
            position: relative;
        }
        .pf-badge::after {
            content: ""; position: absolute; left: 50%; bottom: -6px; width: 10px; height: 10px;
            transform: translateX(-50%) rotate(45deg); background: #d7f8e8;
            border-right: 1px solid #9fe8c6; border-bottom: 1px solid #9fe8c6; border-radius: 0 0 3px 0;
        }
        .pf-pct { font-size: 1.3rem; color: #047857; letter-spacing: -.3px; }
        .pf-tube {
            max-width: 68px; border-radius: 20px;
            background:
                linear-gradient(180deg, #ffdede 0%, #ffeeee 100%);
            box-shadow: inset 0 0 0 1px rgba(239, 68, 68, .10), 0 6px 10px -8px rgba(15, 23, 42, .28);
            position: relative;
        }
        .pf-tube::after { /* เงาสะท้อนแก้วด้านซ้าย */
            content: ""; position: absolute; left: 7px; top: 12px; bottom: 12px; width: 5px; border-radius: 99px;
            background: linear-gradient(180deg, rgba(255,255,255,.35), rgba(255,255,255,0)); pointer-events: none;
        }
        .pf-tube-fail { font-size: .86rem; font-weight: 800; color: #8a1c1f; padding-top: 10px; }
        .pf-tube-pass {
            position: relative; z-index: 1;
            background: linear-gradient(90deg, #10b981 0%, #34d399 38%, #059669 100%);
            border-radius: 16px 16px 0 0;
            box-shadow: 0 -2px 0 #fff, 0 -4px 8px -5px rgba(16, 185, 129, .4), inset 0 2px 3px rgba(255,255,255,.25);
        }
        .pf-name { margin-top: 10px; font-weight: 800; font-size: .86rem; color: #1e293b; }
        .pf-name small, .pf-name span { color: #334155; font-weight: 700; }
        .pf-col:first-child .pf-badge {
            background: linear-gradient(180deg, #ffffff 0%, #b8f2d6 100%); border-color: #5fd6a2;
            box-shadow: 0 1px 0 #fff inset, 0 10px 18px -8px rgba(5, 150, 105, .75);
        }
        .pf-col:first-child .pf-badge::after { background: #b8f2d6; border-color: #5fd6a2; }
        .pf-top { padding: 6px 4px 8px; border-bottom: 1px dashed #e2e8f0; }
        @media (max-width: 560px) { .pf-cols { gap: 7px; } .pf-name { font-size: .76rem; } .pf-badge { padding: 6px 2px 5px; } .pf-pct { font-size: 1.12rem; } }

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
           createDivergingBars() in extra_js below. สีแดง-ส้ม = คำตอบที่ควรปรับปรุง,
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

        .divsum i { color: #b8323f; flex: 0 0 auto; }
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

        .scale-info-btn { display: inline-flex; align-items: center; justify-content: center; width: 22px; height: 22px; margin-left: auto; padding: 0; border-radius: 50%; border: 1.5px solid #94a3b8; background: #fff; color: #475569; font-size: 0.68rem; cursor: pointer; transition: background .15s, color .15s, border-color .15s; }
        .scale-info-btn:hover, .scale-info-btn[aria-expanded="true"] { background: #1d5a96; border-color: #1d5a96; color: #fff; }
        .scale-info-btn:focus-visible { outline: 2px solid #1d5a96; outline-offset: 2px; }
        .sim-overlay { position: fixed; inset: 0; z-index: 2000; display: flex; align-items: center; justify-content: center; padding: 16px; background: rgba(15, 23, 42, .55); backdrop-filter: blur(3px); animation: simFade .18s ease-out; }
        .sim-overlay.is-closing { opacity: 0; transition: opacity .16s ease; }
        .sim-dialog { width: min(560px, 100%); max-height: min(86vh, 720px); display: flex; flex-direction: column; background: #fff; border-radius: 18px; box-shadow: 0 24px 60px rgba(15, 23, 42, .35); overflow: hidden; animation: simPop .22s cubic-bezier(.2, .9, .3, 1.2); }
        .sim-head { position: relative; display: flex; align-items: center; gap: 12px; padding: 16px 16px 16px 18px; color: #fff; background: radial-gradient(120% 160% at 100% 0%, rgba(240, 138, 60, .28) 0%, rgba(240, 138, 60, 0) 45%), linear-gradient(135deg, #123a63 0%, #1d5a96 60%, #2f7fc0 100%); }
        .sim-head::after { content: ""; position: absolute; left: 0; right: 0; bottom: 0; height: 4px; background: linear-gradient(90deg, #b8323f 0 33.3%, #f4e4b0 33.3% 66.6%, #6fb1d6 66.6% 100%); }
        .sim-head-ico { flex: 0 0 auto; width: 42px; height: 42px; display: flex; align-items: center; justify-content: center; border-radius: 12px; background: rgba(255, 255, 255, .16); border: 1px solid rgba(255, 255, 255, .28); font-size: 1.05rem; }
        .sim-head-txt { flex: 1 1 auto; min-width: 0; }
        .sim-eyebrow { font-size: .76rem; font-weight: 600; color: rgba(255, 255, 255, .78); margin-bottom: 1px; white-space: nowrap; overflow: hidden; text-overflow: ellipsis; }
        .sim-head h3 { margin: 0; font-size: 1.15rem; font-weight: 800; color: #fff; }
        .sim-close { flex: 0 0 auto; width: 34px; height: 34px; border: 1px solid rgba(255, 255, 255, .3); border-radius: 50%; background: rgba(255, 255, 255, .14); color: #fff; cursor: pointer; transition: background .15s; }
        .sim-close:hover { background: rgba(255, 255, 255, .3); }
        .sim-body { padding: 16px 18px 20px; overflow-y: auto; font-size: .86rem; line-height: 1.6; color: #334155; scrollbar-width: none; -ms-overflow-style: none; background: #fbfcfe; }
        .sim-body::-webkit-scrollbar { display: none; }
        .sim-foot { padding: 12px 18px 16px; border-top: 1px solid #e8edf3; text-align: center; background: #fff; }
        .sim-ok { min-width: 160px; padding: 9px 26px; border: 0; border-radius: 999px; background: #1d5a96; color: #fff; font-weight: 700; cursor: pointer; }
        .sim-ok:hover { background: #164a7c; }
        .sim-ok:focus-visible, .sim-close:focus-visible { outline: 2px solid #1d5a96; outline-offset: 2px; }
        .sim-sec { margin: 18px 0 10px; font-size: .8rem; font-weight: 800; color: #1d5a96; display: flex; align-items: center; justify-content: center; gap: 10px; text-align: center; }
        .sim-sec:first-child { margin-top: 0; }
        .sim-sec::before, .sim-sec::after { content: ""; flex: 1; height: 1px; background: linear-gradient(90deg, rgba(29, 90, 150, 0), #c5d5e6); }
        .sim-sec::after { transform: scaleX(-1); }
        .sim-mean { display: flex; gap: 14px; align-items: center; padding: 10px 12px; margin-bottom: 8px; border: 1px solid #e8edf3; border-radius: 14px; background: #fff; box-shadow: 0 1px 2px rgba(15, 23, 42, .04); }
        .sim-mean b { display: block; font-size: .92rem; color: #1e293b; }
        .sim-mean div span { display: block; font-size: .8rem; color: #64748b; text-wrap: balance; }
        .sim-swatch { flex: 0 0 38px; height: 38px; border-radius: 10px; box-shadow: inset 0 0 0 1px rgba(15, 23, 42, .08); }
        .sim-ex { padding: 12px; border: 1px solid #dbe4ee; border-radius: 12px; background: #f8fafc; }
        .sim-ex-q { font-weight: 700; color: #1e293b; margin-bottom: 10px; font-size: .84rem; text-align: center; }
        .sim-ex-calc { display: flex; align-items: stretch; justify-content: center; gap: 6px; flex-wrap: nowrap; }
        .sim-ex-calc span { display: flex; flex-direction: column; align-items: center; text-align: center; padding: 6px 8px; border-radius: 10px; background: #fff; border: 1px solid #e2e8f0; min-width: 0; flex: 1 1 0; }
        .sim-ex-calc span.is-res { background: #fbeeec; border-color: #f0c9c5; }
        .sim-ex-calc small { font-size: .66rem; color: #64748b; line-height: 1.25; }
        .sim-ex-calc b { font-size: .95rem; color: #1e293b; white-space: nowrap; }
        .sim-ex-calc .is-res b { color: #b8323f; }
        .sim-ex-calc em { align-self: center; font-style: normal; font-weight: 800; color: #94a3b8; }
        .sim-ex-note { margin-top: 10px; font-size: .76rem; color: #64748b; text-align: center; text-wrap: balance; }
        .sim-q { margin-bottom: 10px; padding: 10px; border: 1px solid #e3eaf2; border-radius: 14px; background: #fff; box-shadow: 0 1px 2px rgba(15, 23, 42, .04); }
        .sim-qt { margin: 0 2px 8px; font-weight: 800; font-size: .86rem; color: #1e293b; }
        .sim-grp { display: flex; align-items: center; gap: 10px; padding: 7px 10px; margin-top: 4px; border-radius: 10px; }
        .sim-grp:first-of-type { margin-top: 0; }
        .sim-grp.is-risk { background: #fcefee; }
        .sim-grp.is-mid { background: #fdf6df; }
        .sim-grp.is-good { background: #e9f1f9; }
        .sim-grp-name { flex: 0 0 74px; display: flex; align-items: center; gap: 6px; font-size: .78rem; font-weight: 800; color: #334155; }
        .sim-grp-name i { flex: 0 0 10px; width: 10px; height: 10px; border-radius: 50%; box-shadow: inset 0 0 0 1px rgba(15, 23, 42, .12); }
        .sim-chips { flex: 1 1 auto; display: flex; flex-wrap: wrap; gap: 5px; min-width: 0; }
        .sim-chip { padding: 2px 10px; border-radius: 999px; background: #fff; border: 1px solid rgba(15, 23, 42, .1); font-size: .78rem; color: #334155; box-shadow: 0 1px 1px rgba(15, 23, 42, .04); }
        .sim-grp-pct { flex: 0 0 auto; min-width: 38px; text-align: right; font-size: .84rem; font-weight: 800; color: #334155; }
        .sim-grp.is-risk .sim-grp-pct { color: #b8323f; }
        .sim-grp.is-good .sim-grp-pct { color: #1d5a96; }
        .sim-tip { display: flex; gap: 10px; align-items: center; margin-top: 14px; padding: 10px 14px; border-radius: 14px; background: #fff8e6; border: 1px solid #f3e0a8; font-size: .78rem; color: #6b5413; text-wrap: balance; }
        .sim-tip i { flex: 0 0 auto; color: #d19a1a; font-size: 1rem; }
        /* ปรับข้อความในป๊อปอัปให้เข้มและคมชัดขึ้น */
        .sim-body { color: #0f172a; }
        .sim-sec { color: #0f4c81; font-size: .84rem; }
        .sim-mean b { font-size: 1rem; font-weight: 800; color: #0f172a; }
        .sim-mean div span { font-size: .85rem; font-weight: 500; color: #1e293b; }
        .sim-ex-q { font-weight: 800; color: #0f172a; }
        .sim-ex-calc small { font-weight: 600; color: #334155; }
        .sim-ex-calc b { color: #0f172a; }
        .sim-ex-calc .is-res b { color: #a3202d; }
        .sim-ex-note { font-weight: 500; color: #1e293b; }
        .sim-qt { font-size: .92rem; font-weight: 800; color: #0f172a; }
        .sim-grp-name { font-size: .82rem; font-weight: 800; color: #0f172a; }
        .sim-chip { font-size: .82rem; font-weight: 600; color: #0f172a; border-color: rgba(15, 23, 42, .22); }
        .sim-grp-pct { font-size: .9rem; font-weight: 800; color: #0f172a; }
        .sim-grp.is-risk .sim-grp-pct { color: #a3202d; }
        .sim-grp.is-good .sim-grp-pct { color: #0f4c81; }
        .sim-tip { font-size: .83rem; font-weight: 500; color: #3f2f05; }
        .sim-eyebrow { color: rgba(255, 255, 255, .92); }
        body.sim-open { overflow: hidden; }
        @keyframes simFade { from { opacity: 0; } to { opacity: 1; } }
        @keyframes simPop { from { opacity: 0; transform: translateY(14px) scale(.96); } to { opacity: 1; transform: none; } }
        @media (prefers-reduced-motion: reduce) { .sim-overlay, .sim-dialog { animation: none; } }

        .divrow-glabs { display: flex; gap: 2px; margin-top: 5px; }
        .divrow-glab small { display: block; font-size: 0.7rem; font-weight: 500; color: #94a3b8; }
        .divrow-glab { min-width: 0; text-align: center; font-size: 0.78rem; font-weight: 700; color: #475569; white-space: nowrap; overflow: hidden; text-overflow: ellipsis; }

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


        /* donut (กรอบที่ 2) */
        .chart-card:has(#seasoningChart){display:flex;flex-direction:column;height:100%}
        #seasoningChart{flex:1 1 auto;display:flex;min-height:0}
        #seasoningChart .lk-split{width:100%;flex:1 1 auto}
        #seasoningChart .lk-main{display:flex;min-width:0;container-type:inline-size}
        #seasoningChart .lk-side .lk-pts{justify-content:space-evenly;gap:10px}
        .dn-grid{flex:1 1 auto;width:100%;display:grid;grid-template-columns:repeat(2,minmax(0,1fr));grid-auto-rows:1fr;gap:12px}
        /* 3 หัวข้อ: สองใบแรกอยู่บน ใบที่สามยาวเต็มแถวด้านล่าง | 4 หัวข้อ: 2 x 2 | 1 หัวข้อ: เต็มแถว */
        .dn-grid[data-n="3"] .dn:nth-child(3),.dn-grid[data-n="1"] .dn:first-child{grid-column:1/-1}
        .dn-grid[data-n="3"] .dn:nth-child(3) .dn-body,.dn-grid[data-n="1"] .dn-body{gap:48px}
        .dn-grid[data-n="3"] .dn:nth-child(3) .dn-list,.dn-grid[data-n="1"] .dn-list{max-width:340px}
        @container (max-width:520px){.dn-grid{grid-template-columns:minmax(0,1fr)}.dn-grid[data-n="3"] .dn:nth-child(3){grid-column:auto}}
        /* ยืดหยุ่นตามจำนวนกล่อง: เลขคี่ใบสุดท้ายยาวเต็มแถว, 4+ กล่องลดขนาดวง, กล่องแคบวางวงไว้บนรายการ */
        .dn-grid:not([data-n="1"]) .dn:last-child:nth-child(odd){grid-column:1/-1}
        .dn-grid:not([data-n="1"]):not([data-n="2"]) .dn:last-child:nth-child(odd) .dn-list{max-width:340px}
        .dn-grid:not([data-n="1"]):not([data-n="2"]):not([data-n="3"]) .dn-svg{width:128px;height:128px}
        .dn-grid:not([data-n="1"]):not([data-n="2"]):not([data-n="3"]) .dn-body{gap:14px}
        .dn{container-type:inline-size}
        @container (max-width:300px){.dn-body{flex-direction:column;gap:10px}.dn-list{width:100%;flex:none}}
        @container (max-width:520px){.dn-grid .dn:last-child:nth-child(odd){grid-column:auto}}
        .dn{display:flex;flex-direction:column;border:1px solid #eef2f6;border-radius:14px;background:linear-gradient(180deg,#fff,#fafbfd);padding:14px 16px}
        .dn-title{font-size:16.5px;font-weight:800;line-height:1.35;color:#0f172a;margin-bottom:4px}
        .dn-title small{display:block;font-size:11px;font-weight:500;color:#94a3b8;margin-top:1px}
        .dn-body{flex:1;display:flex;align-items:center;justify-content:center;gap:18px}
        .dn-svg{width:150px;height:150px;flex:none;filter:drop-shadow(0 7px 9px rgba(15,23,42,.18))}
        .dn-disc{filter:drop-shadow(0 1px 2px rgba(15,23,42,.22));stroke:#fff;stroke-width:1.5}
        .dn-arc{cursor:default;transition:stroke-width .12s}
        .dn-arc:hover{stroke-width:18}
        .dn-pct{font-size:15px;font-weight:800}
        .dn-cap{font-size:7.5px;fill:#64748b}
        .dn-list{flex:1;min-width:0;display:flex;flex-direction:column;gap:10px}
        .dn-r{display:grid;grid-template-columns:10px 1fr auto;gap:0 7px;align-items:center;font-size:12.5px;color:#475569}
        .dn-r i{width:10px;height:10px;border-radius:50%}
        .dn-r b{color:#0f172a;font-size:14px}
        .dn-r small{grid-column:2/4;color:#334155;font-weight:600;font-size:12px;margin-top:-2px}
        /* heatmap (กรอบที่ 3) */
        .hm{display:flex;flex-direction:column;gap:4px}
        .hm-row{display:grid;grid-template-columns:minmax(120px,1.6fr) repeat(3,1fr);gap:4px;align-items:stretch}
        .hm-col{text-align:center;padding:2px 0 0}
        .hm-col span{display:block;width:100%;padding:6px 8px;border-radius:10px 10px 4px 4px;font-size:14px;font-weight:800;letter-spacing:.2px;text-align:center;white-space:nowrap;box-shadow:inset 0 1px 0 rgba(255,255,255,.35),inset 0 -2px 0 rgba(15,23,42,.12)}
        .hm-cell b{font-size:20px !important;font-weight:800;letter-spacing:-.3px}
        .hm-cell small{font-size:12.5px !important;font-weight:700}
        .hm-cell[style*="color:#fff"] b,.hm-cell[style*="color:#fff"] small{text-shadow:0 1px 2px rgba(15,23,42,.35)}
        @media(min-width:576px){
            .hm-row{grid-template-columns:minmax(170px,.85fr) repeat(3,minmax(0,1.05fr)) !important;gap:8px !important}
            .hm-lab{padding:0 16px 0 18px !important}
            .hm-cell{min-height:60px}
        }
        @media(min-width:1200px){ .hm-row{grid-template-columns:minmax(190px,.8fr) repeat(3,minmax(0,1fr)) !important} }
        /* ช่องคอลัมน์ "ควรปรับปรุง" ใช้สีเต็มเหมือนแถบหัวคอลัมน์ */
        .hm-row .hm-cell:nth-child(2){background:linear-gradient(155deg,#c42a65 0%,#b0164e 55%,#98113f 100%) !important;color:#fff !important}
        .hm-row .hm-cell:nth-child(2) b,.hm-row .hm-cell:nth-child(2) small{color:#fff !important;text-shadow:0 1px 3px rgba(60,5,30,.5)}
        /* หัวคอลัมน์สีอ่อนลง */
        .hm-headrow .hm-col:nth-child(2) span{background:linear-gradient(180deg,#f1b6cb,#e397b3) !important;color:#6b0f33 !important}
        .hm-headrow .hm-col:nth-child(3) span{background:linear-gradient(180deg,#ddd3f4,#cbbcee) !important;color:#2b1b59 !important}
        .hm-headrow .hm-col:nth-child(4) span{background:linear-gradient(180deg,#b4a3e6,#9d89dc) !important;color:#20134f !important}
        .hm-cell b{font-size:22px !important;font-weight:800 !important}
        .hm-cell small{font-size:13.5px !important;font-weight:800 !important;margin-top:1px}
        .hm-row .hm-cell:nth-child(2):not([style*="color:#fff"]){color:#4a0b2c !important}
        .hm-row .hm-cell:nth-child(3):not([style*="color:#fff"]){color:#1c1046 !important}
        .hm-cell[style*="color:#fff"] b,.hm-cell[style*="color:#fff"] small{text-shadow:0 1px 3px rgba(15,10,50,.5)}
        .hm-lab{font-size:17px !important;font-weight:800 !important;color:#0b1220 !important;line-height:1.3 !important}
        .hm-lab{display:flex;flex-direction:column;justify-content:center;gap:1px;padding-right:10px;font-size:16px;font-weight:800;line-height:1.35;color:#0f172a;letter-spacing:.1px}
        .hm-lab small{font-size:11px;font-weight:500;color:#94a3b8}
        .lbl-gap{color:#94a3b8;font-weight:500}
        .hm-cell{position:relative;overflow:hidden;box-shadow:inset 0 1px 0 rgba(255,255,255,.6),inset 0 -2px 0 rgba(15,23,42,.07),0 7px 12px -8px rgba(var(--c,100,100,100),.75);display:flex;flex-direction:column;align-items:center;justify-content:center;padding:9px 4px;border-radius:8px;min-height:52px;cursor:default;transition:transform .12s}
        .hm-cell::after{content:'';position:absolute;inset:0;background:linear-gradient(155deg,rgba(255,255,255,.38),rgba(255,255,255,0) 55%);pointer-events:none}
        .hm-cell>*{position:relative;z-index:1}
        .hm-cell:hover{transform:translateY(-2px) scale(1.03);box-shadow:inset 0 1px 0 rgba(255,255,255,.7),inset 0 -2px 0 rgba(15,23,42,.07),0 12px 18px -8px rgba(var(--c,100,100,100),.85)}
        .hm-cell b{font-size:16px;line-height:1.1}
        .hm-cell small{font-size:12px;font-weight:700;opacity:1}
        .hm-cell.is-zero{background:#f8fafc;color:#94a3b8}
        @media(max-width:575px){.hm-row{grid-template-columns:minmax(80px,1.2fr) repeat(3,1fr)}.hm-lab{font-size:12px}}
        /* พื้นหลังโทนบางต่อการ์ด เพิ่มมิติโดยไม่แย่งสีข้อมูล */
        #riskChart .divrow{background:linear-gradient(135deg,rgba(184,50,63,.05),rgba(29,90,150,.03) 70%,transparent)}
        #seasoningChart .dn{background:linear-gradient(150deg,rgba(232,89,12,.07),rgba(246,223,180,.10) 45%,rgba(11,114,133,.08));border-color:rgba(11,114,133,.08)}
        .hm{padding:10px;border-radius:14px;background:linear-gradient(160deg,rgba(194,37,92,.04),rgba(95,61,196,.05))}
        .hm-lab{padding-left:12px;border-radius:9px;background:linear-gradient(90deg,rgba(69,39,160,.16),rgba(69,39,160,.02) 88%)}
        .hm-headrow .hm-lab{background:none}
        /* ===== ตารางแนววิชาการ (ตารางสามเส้น) ===== */
        .hm-cap{font-size:14.5px;line-height:1.5;color:#1f2a44;margin:2px 0 10px}
        .hm-cap b{font-weight:800;margin-right:6px}
        .hm-note{font-size:12.5px;line-height:1.6;color:#5b6475;margin-top:10px;padding-top:8px;border-top:1px solid #e5e8ee}
        .hm-note b{color:#1f2a44;font-weight:700}
        .hm.hm-ac{padding:0;border-radius:0;background:#fff;gap:0;border-top:2px solid #1f2a44;border-bottom:2px solid #1f2a44;font-variant-numeric:tabular-nums}
        .hm-ac .hm-row{gap:6px !important;align-items:center;padding:7px 0;border-bottom:1px solid #e5e8ee}
        .hm-ac .hm-row:last-child{border-bottom:0}
        .hm-ac .hm-headrow{padding:8px 0 7px;border-bottom:1.5px solid #1f2a44 !important;align-items:end}
        .hm-ac .hm-col{padding:0}
        .hm-ac .hm-col span,.hm-ac .hm-headrow .hm-col:nth-child(n) span{background:none !important;color:#1f2a44 !important;box-shadow:none !important;border-radius:0;padding:0 4px 7px;font-size:15px;font-weight:800;position:relative;line-height:1.25}
        .hm-ac .hm-col span::after{content:'';position:absolute;left:12%;right:12%;bottom:0;height:3px;border-radius:2px;background:var(--gc,#cbd5e1)}
        .hm-ac .hm-col span small{display:block;font-size:11.5px;font-weight:500;color:#64748b;margin-top:1px}
        .hm-ac .hm-ncol span::after{display:none}
        .hm-ac .hm-lab,.hm-ac .hm-lab{background:none !important;border-radius:0;padding:0 8px 0 2px !important;flex-direction:row;align-items:baseline;gap:9px;font-size:16px !important;font-weight:600 !important;color:#1f2a44 !important}
        .hm-ac .hm-labh{font-size:13.5px !important;font-weight:800 !important;color:#1f2a44 !important;align-items:flex-end;padding-bottom:7px !important}
        .hm-no{font-style:normal;font-weight:500;font-size:13px;color:#8a93a5;min-width:16px;text-align:right}
        .hm-ac .hm-cell{border-radius:6px;box-shadow:none !important;min-height:54px;transform:none !important}
        .hm-ac .hm-cell::after{display:none}
        .hm-ac .hm-cell b{font-size:20px !important;font-weight:800 !important;letter-spacing:0;text-shadow:none !important}
        .hm-ac .hm-cell small{font-size:12px !important;font-weight:500 !important;opacity:.9;text-shadow:none !important}
        .hm-n{text-align:center;font-size:14px;font-weight:700;color:#334155}
        @media(min-width:576px){
            .hm-ac .hm-row{grid-template-columns:minmax(190px,.9fr) repeat(3,minmax(0,1fr)) 76px !important}
        }
        @media(max-width:575px){
            .hm-ac .hm-row{grid-template-columns:minmax(78px,1.2fr) repeat(3,minmax(0,1fr)) 38px !important;gap:3px !important}
            .hm-ac .hm-col span,.hm-ac .hm-headrow .hm-col:nth-child(n) span{font-size:11px;padding:0 0 6px}
            .hm-ac .hm-col span small{font-size:9.5px}
            .hm-ac .hm-lab{font-size:12px !important;gap:4px}
            .hm-no{min-width:10px;font-size:11px}
            .hm-ac .hm-labh{font-size:11px !important}
            .hm-ac .hm-cell b{font-size:15px !important}
            .hm-ac .hm-cell small{font-size:10px !important}
            .hm-n{font-size:11px}
            .hm-cap{font-size:13px}.hm-note{font-size:11.5px}
        }
        /* ===== แถบ 100% ซ้อนต่อข้อ ===== */
        .sb{border-top:2px solid #1f2a44;border-bottom:2px solid #1f2a44;padding:4px 0 6px}
        .sb-key{display:flex;flex-wrap:wrap;gap:6px 22px;padding:10px 2px 10px;border-bottom:1.5px solid #1f2a44;font-size:14px;font-weight:800;color:#1f2a44}
        .sb-key span{display:inline-flex;align-items:center;gap:7px}
        .sb-key i{width:14px;height:14px;border-radius:4px;display:inline-block}
        .sb-key small{font-weight:500;color:#64748b;font-size:12px}
        .sb-item{padding:12px 0 13px;border-bottom:1px solid #e5e8ee}
        .sb-item:last-child{border-bottom:0}
        .sb-head{display:flex;align-items:baseline;gap:9px;margin-bottom:8px}
        .sb-q{flex:1;min-width:0;font-size:16px;font-weight:600;color:#1f2a44;line-height:1.35}
        .sb-n{font-size:13px;font-weight:600;color:#64748b;white-space:nowrap;font-variant-numeric:tabular-nums}
        .sb-bar{display:flex;gap:3px;height:58px}
        .sb-seg{display:flex;flex-direction:column;align-items:center;justify-content:center;min-width:34px;border-radius:7px;cursor:default;transition:filter .12s;font-variant-numeric:tabular-nums}
        .sb-seg:hover{filter:brightness(1.07) saturate(1.1)}
        .sb-seg b{font-size:19px;font-weight:800;line-height:1.1}
        .sb-seg small{font-size:12px;font-weight:500;opacity:.92}
        @media(max-width:575px){.sb-q{font-size:13px}.sb-n{font-size:11px}.sb-bar{height:50px}.sb-seg{min-width:30px}.sb-seg b{font-size:15px}.sb-seg small{font-size:10px}.sb-key{font-size:12px;gap:4px 14px}.sb-key small{display:none}}
        /* ===== วาฟเฟิล 100 ช่อง ===== */
        .wf{border-top:2px solid #1f2a44;border-bottom:2px solid #1f2a44}
        .wf .sb-key{border-bottom:1px solid #e5e8ee}
        .wf-grid{display:grid;grid-template-columns:1fr;gap:0}
        @media(min-width:900px){.wf-grid{grid-template-columns:repeat(3,1fr)}}
        .wf-item{padding:14px 16px 12px;border-bottom:1px solid #e5e8ee}
        .wf-item:last-child{border-bottom:0}
        .wf-item+.wf-item{border-left:0}
        @media(min-width:900px){.wf-item{border-bottom:0}.wf-item+.wf-item{border-left:1px solid #e5e8ee}}
        .wf-head{display:flex;align-items:baseline;gap:8px;min-height:44px;margin-bottom:8px}
        .wf-sq{display:grid;grid-template-columns:repeat(20,1fr);grid-template-rows:repeat(5,1fr);grid-auto-flow:column;gap:3px}
        .wf-sq i{aspect-ratio:1;border-radius:3px;display:block;transition:transform .1s}
        .wf-sq i:hover{transform:scale(1.35);position:relative;z-index:2}
        .wf-stats{display:flex;justify-content:space-between;gap:6px;margin-top:12px}
        .wf-st{display:flex;flex-direction:column;align-items:flex-start;line-height:1.2}
        .wf-dot{width:18px;height:4px;border-radius:2px;margin-bottom:5px}
        .wf-st b{font-size:21px;font-weight:800;color:#1f2a44;font-variant-numeric:tabular-nums}
        .wf-st small{font-size:12px;color:#64748b;font-variant-numeric:tabular-nums}
        .wf-n{margin-top:8px;font-size:12px;font-weight:600;color:#64748b;text-align:right}
        /* ===== Likert แยกซ้าย-ขวา ===== */
        .lk{border-top:2px solid #1f2a44;border-bottom:2px solid #1f2a44}
        .lk .sb-key{border-bottom:1px solid #e5e8ee}
        .lk-axis{padding:10px 0 0}
        .lk-ticks{position:relative;height:16px;margin:0 14px}
        .lk-tk{position:absolute;transform:translateX(-50%);font-size:11.5px;color:#64748b;font-variant-numeric:tabular-nums}
        .lk-tk.z{font-weight:800;color:#1f2a44}
        .lk-item{padding:10px 14px 12px;border-bottom:1px solid #e5e8ee}
        .lk-item:last-child{border-bottom:0}
        .lk-track{position:relative;height:56px}
        .lk-gl{display:block;position:absolute;top:-4px;bottom:-4px;width:0;border-left:1px dashed #cbd2de}
        .lk-item .wf-head{min-height:0;margin-bottom:10px}
        .lk-gl.z{border-left:1.5px solid #1f2a44}
        .lk-bar{position:relative;display:flex;gap:2px;height:100%}
        .lk-bar .sb-seg{min-width:0;overflow:hidden;flex:none}
        @media(max-width:575px){.lk-track{height:48px}.lk-item{padding:8px 8px 10px}.lk-ticks{margin:0 8px}.lk-bar .sb-seg b{font-size:14px}}
        /* ===== แบ่ง 2 ฝั่ง: กราฟ + สรุป ===== */
        .lk-split{display:grid;grid-template-columns:1fr;gap:14px;align-items:start}
        @media(min-width:900px){.lk-split{grid-template-columns:minmax(0,1.7fr) minmax(260px,1fr);gap:18px}}
        .lk-side{border:1px solid #e5e8ee;border-top:2px solid #1f2a44;border-radius:0 0 10px 10px;padding:14px 16px;background:#fbfbfd}
        .lk-sh{font-size:14px;font-weight:800;color:#1f2a44;margin-bottom:10px}
        .lk-big{display:flex;flex-direction:column;gap:2px;padding-bottom:12px;border-bottom:1px solid #e5e8ee}
        .lk-big b{font-size:34px;line-height:1.1;font-weight:800;color:#4527a0;font-variant-numeric:tabular-nums}
        .lk-big span{font-size:13px;color:#475569}
        .lk-avg{padding:12px 0;border-bottom:1px solid #e5e8ee}
        .lk-avgt{font-size:12.5px;font-weight:700;color:#334155;margin-bottom:7px}
        .lk-avgbar{display:flex;gap:2px;height:14px;border-radius:7px;overflow:hidden}
        .lk-avgbar i{display:block;height:100%}
        .lk-avgl{display:flex;flex-wrap:wrap;gap:4px 14px;margin-top:8px;font-size:12px;color:#475569}
        .lk-avgl em{display:inline-block;width:9px;height:9px;border-radius:3px;margin-right:5px}
        .lk-avgl b{color:#1f2a44;font-variant-numeric:tabular-nums}
        .lk-pts{list-style:none;margin:0;padding:0}
        .lk-pts li{display:grid;grid-template-columns:1fr auto;column-gap:10px;padding:10px 0;border-bottom:1px solid #eef0f4}
        .lk-pts li:last-child{border-bottom:0;padding-bottom:0}
        .lk-pts small{grid-column:1/3;font-size:11.5px;color:#64748b}
        .lk-pts b{font-size:14px;font-weight:700;color:#1f2a44;line-height:1.35}
        .lk-pts em{font-style:normal;font-weight:800;font-size:16px;color:#1f2a44;align-self:center;font-variant-numeric:tabular-nums}
        .lk-pts em.r{color:#c0344f}.lk-pts em.g{color:#1f7a5c}
        .lk-g{padding:8px 0}
        .lk-gh{display:flex;align-items:baseline;gap:7px}
        .lk-gd{width:10px;height:10px;border-radius:3px;align-self:center}
        .lk-gh b{font-size:14px;color:#1f2a44}
        .lk-gh small{font-size:11.5px;color:#64748b}
        .lk-gh em{margin-left:auto;font-style:normal;font-size:20px;font-weight:800;font-variant-numeric:tabular-nums}
        .lk-gb{height:8px;border-radius:4px;background:#eceef4;margin:6px 0 4px;overflow:hidden}
        .lk-gb span{display:block;height:100%;border-radius:4px}
        .lk-gr{font-size:11.5px;color:#64748b}
        .lk-pts{margin-top:6px;border-top:1px solid #e5e8ee}
        /* ฝั่งสรุปสูงเท่ากราฟ กระจายรายการให้สมดุล */
        .lk-split{align-items:stretch}
        .lk-side{display:flex;flex-direction:column;border:0;border-top:2px solid #1f2a44;border-bottom:2px solid #1f2a44;border-radius:0;padding:12px 18px;background:#fbfbfd}
        .lk-side .lk-sh{font-size:15px;padding-bottom:8px;border-bottom:1px solid #e5e8ee;margin-bottom:0}
        .lk-side .lk-pts{flex:1;display:flex;flex-direction:column;justify-content:space-evenly;margin-top:0;border-top:0}
        .lk-side .lk-pts li{padding:0;border-bottom:0;row-gap:3px}
        .lk-side .lk-pts li+li{border-top:1px solid #eef0f4;padding-top:12px}
        .lk-side .lk-pts small{font-size:12.5px}
        .lk-side .lk-pts b{font-size:16px;line-height:1.4}
        .lk-side .lk-pts em{font-size:24px}
        @media(max-width:899px){.lk-side .lk-pts{gap:12px}}
        /* เอาเส้นบนของกราฟและกล่องสรุปออก */
        .lk{border-top:0}
        .lk-side{border-top:0 !important}
        /* เอาเส้นล่างของกราฟและกล่องสรุปออก */
        .lk{border-bottom:0}
        .lk-side{border-bottom:0 !important}
        /* กล่องสรุป: การ์ดรายการมีไอคอนและแถบสี */
        .lk-side .lk-pts{gap:10px;justify-content:center;padding-top:12px}
        .lk-side .lk-pts li,.lk-side .lk-pts li+li{display:grid;grid-template-columns:42px 1fr auto;column-gap:12px;align-items:center;padding:12px 14px;border:0;border-radius:10px;background:var(--lb)}
        .lk-pts li.is-risk{--lc:#c0344f;--lb:rgba(217,67,94,.08)}
        .lk-pts li.is-good{--lc:#1f7a5c;--lb:rgba(42,157,120,.09)}
        .lk-pts li.is-n{--lc:#64748b;--lb:rgba(100,116,139,.08)}
        .lk-ic{width:42px;height:42px;border-radius:50%;display:flex;align-items:center;justify-content:center;background:#fff;color:var(--lc);font-size:18px;box-shadow:0 2px 6px rgba(15,23,42,.08)}
        .lk-tx{display:flex;flex-direction:column;gap:2px;min-width:0}
        .lk-side .lk-tx small{grid-column:auto;font-size:12.5px;color:#475569}
        .lk-side .lk-tx b{font-size:15.5px;line-height:1.35}
        .lk-v{display:flex;flex-direction:column;align-items:flex-end;line-height:1.1}
        .lk-side .lk-v em{font-size:26px;font-weight:800;color:var(--lc)}
        .lk-side .lk-v small{font-size:11.5px;color:#64748b}
        .lk-pts li.is-n .lk-v em{color:#1f2a44}
        @media(max-width:575px){.lk-side .lk-pts li{grid-template-columns:34px 1fr auto;column-gap:9px;padding:10px 10px}.lk-ic{width:34px;height:34px;font-size:14px}.lk-side .lk-v em{font-size:21px}.lk-side .lk-tx b{font-size:14px}}
        /* หัวกล่องสรุป + พื้นหลัง */
        .lk-side{border-radius:14px;background:linear-gradient(165deg,#fbfcfd 0%,#f3f7f6 60%,#fbf4f5 100%);padding:16px 18px}
        .lk-side .lk-sh{display:flex;align-items:center;gap:12px;font-size:inherit;padding-bottom:12px;border-bottom:1px solid rgba(31,42,68,.12);margin-bottom:0}
        .lk-shi{width:40px;height:40px;border-radius:12px;display:flex;align-items:center;justify-content:center;flex:none;color:#fff;font-size:17px;background:linear-gradient(135deg,#3b4f86,#1f2a44);box-shadow:0 6px 14px -6px rgba(31,42,68,.55)}
        .lk-sht{display:flex;flex-direction:column;line-height:1.25}
        .lk-sht b{font-size:17px;font-weight:800;color:#1f2a44}
        .lk-sht small{font-size:12.5px;color:#64748b}
        .lk-side .lk-pts li,.lk-side .lk-pts li+li{background:#fff;box-shadow:0 1px 0 rgba(15,23,42,.04),0 8px 18px -12px rgba(31,42,68,.25)}
        .lk-pts li.is-risk .lk-ic{background:rgba(217,67,94,.14)}
        .lk-pts li.is-good .lk-ic{background:rgba(42,157,120,.16)}
        .lk-pts li.is-n .lk-ic{background:rgba(100,116,139,.12)}
        .lk-side .lk-ic{box-shadow:none}
        /* การ์ดแถบซ้อน (2 ฝั่ง): ให้แถวฝั่งกราฟยืดเท่ากับความสูงฝั่งสรุป ไม่เหลือพื้นที่ว่างใต้กราฟ */
        .lk-main:has(> .divrow){display:flex;flex-direction:column;gap:14px}
        .lk-main > .divrow{flex:1 1 0;display:flex;flex-direction:column;justify-content:center;margin:0}
        .lk-main > .divrow + .divrow{border-top:1px solid rgba(184,50,63,.08)}
        .lk-split:has(> .lk-main > .divrow) .lk-side .lk-pts{justify-content:space-evenly;gap:10px}
        /* รองรับ 3-4+ ข้อ: ฝั่งกราฟไม่ล้น ฝั่งสรุปยืดตามความสูงและกระจายรายการเอง */
        .lk-main{min-width:0}
        .lk-main > .divrow{min-height:88px}
        /* สีตามสัญญาณจราจร: แดง = ควรปรับปรุง, เหลือง = ปานกลาง, เขียว = ดี */
        .lk-bar{gap:3px}
        .lk-bar .sb-seg{position:relative;border-radius:10px;box-shadow:inset 0 -3px 0 rgba(0,0,0,.10)}
        .lk-bar .sb-seg::after{content:'';position:absolute;inset:0;border-radius:inherit;background:linear-gradient(180deg,rgba(255,255,255,.22),rgba(255,255,255,0) 55%);pointer-events:none}
        .lk-bar .sb-seg>*{position:relative;z-index:1}
        .lk-bar .sb-seg b{font-size:21px;letter-spacing:-.2px}
        .lk-bar .sb-seg small{font-size:12.5px;font-weight:600}
        .lk-track{height:60px}
        .lk-item{padding:12px 14px 14px}
        .lk-item .sb-q{font-size:16.5px;font-weight:700;color:#1f2a44}
        .awr-loadbar{position:fixed;left:0;top:0;height:3px;width:0;z-index:10000;background:linear-gradient(90deg,#22d3ee,#6366f1);opacity:0;pointer-events:none}
        .awr-loadbar.on{opacity:1;width:92%;transition:width 6s cubic-bezier(.1,.7,.2,1)}
        body.awr-busy .chart-card{opacity:.65;transition:opacity .2s}
        .dtip{position:fixed;left:0;top:0;z-index:9999;pointer-events:none;min-width:190px;max-width:260px;padding:10px 12px 9px;
            background:#fff;border:1px solid #e3e8ef;border-radius:12px;box-shadow:0 10px 28px rgba(15,23,42,.18);
            font-size:12.5px;color:#334155;opacity:0;transition:opacity .12s}
        .dtip.on{opacity:1}
        .dtip-h{display:flex;align-items:center;gap:7px}
        .dtip-h b{font-size:14px;color:#0f172a}
        .dtip-h em{margin-left:auto;font-style:normal;font-weight:800;font-size:16px;color:#0f172a}
        .dtip-dot{width:10px;height:10px;border-radius:50%;flex:none}
        .dtip-sub{margin:1px 0 7px 17px;color:#64748b;font-size:12px}
        .dtip-r{display:grid;grid-template-columns:1fr auto;gap:2px 10px;padding:5px 0;border-top:1px solid #eef2f6}
        .dtip-t{line-height:1.3}
        .dtip-v{font-weight:700;color:#0f172a}
        .dtip-b{grid-column:1/-1;display:block;height:4px;border-radius:3px;background:#eef2f6;overflow:hidden}
        .dtip-b u{display:block;height:100%;border-radius:3px}
        /* ปรับ tooltip ให้อ่านง่าย: หัวเด่น แถบสัดส่วนทุกแถว ตัวเลขชิดขวาเป็นระเบียบ */
        .dtip{min-width:210px;max-width:280px;padding:12px 14px 10px;border-radius:14px;border:1px solid #e2e8f0;box-shadow:0 16px 36px -10px rgba(15,23,42,.28);font-size:13px}
        .dtip-h{padding-bottom:6px;gap:8px}
        .dtip-h b{font-size:15.5px;font-weight:800;letter-spacing:-.1px}
        .dtip-h em{font-size:19px;font-weight:800;letter-spacing:-.4px;font-variant-numeric:tabular-nums}
        .dtip-dot{width:12px;height:12px;box-shadow:0 0 0 3px rgba(15,23,42,.06)}
        .dtip-sub{margin:0 0 8px 20px;font-size:12.5px;font-weight:600;color:#64748b}
        .dtip-r{padding:7px 0 8px;gap:4px 12px;border-top:1px solid #f1f5f9}
        .dtip-t{font-size:13px;font-weight:600;color:#334155}
        .dtip-v{font-size:13.5px;font-weight:800;font-variant-numeric:tabular-nums}
        .dtip-b{height:5px;border-radius:99px;background:#eef2f7}
        .dtip-b u{border-radius:99px}
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
            color: #b8323f;
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
            display: none !important; /* เอาเลขลำดับวงกลมออก */
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
        /* ชี้ที่ส่วนของกราฟวงกลม = รายการจังหวัดนั้นเด่นขึ้นด้วยสีของจังหวัด ที่เหลือจางลง */
        .awr-leg-item { transition: opacity .15s ease, box-shadow .15s ease, background-color .15s ease, transform .15s ease; }
        .awr-leg-item.is-active {
            background: color-mix(in srgb, var(--leg-c) 12%, #fff);
            border-color: var(--leg-c);
            box-shadow: inset 4px 0 0 var(--leg-c), 0 8px 18px -10px var(--leg-c);
            transform: translateX(-3px);
        }
        .awr-leg-item.is-active .awr-leg-name,
        .awr-leg-item.is-active .awr-leg-nums b { color: var(--leg-c); }
        .awr-leg-item.is-active .awr-leg-dot { transform: scale(1.25); box-shadow: 0 0 0 4px color-mix(in srgb, var(--leg-c) 25%, transparent); }
        .awr-leg-item.is-dim { opacity: .5; }
        @media (prefers-reduced-motion: reduce) { .awr-leg-item { transition: none; } .awr-leg-item.is-active { transform: none; } }
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

        /* Filter bar: dividers between every segment + balanced widths */
        .awr-filter-bar { padding: 6px 8px 6px 22px; }
        .awr-filter-heading { padding-right: 20px; margin-right: 0; border-right: 1.5px solid #cbd5e1; }
        .awr-filter-item { flex: 1 1 0; padding: 0 20px; min-width: 150px; border-right: 1.5px solid #cbd5e1 !important; }
        .awr-filter-item:last-of-type { border-right: 1.5px solid #cbd5e1 !important; }
        .awr-filter-item::before { content:''; }
        .awr-filter-clear { flex: 0 0 auto; margin-left: 14px; padding: 10px 22px; justify-content: center; }
        @media (max-width: 900px) {
            .awr-filter-heading, .awr-filter-item, .awr-filter-item:last-of-type { border-right: none !important; }
            .awr-filter-item { border-bottom: 1px solid #e2e8f0; padding: 4px 0; }
            .awr-filter-item:last-of-type { border-bottom: none; }
            .awr-filter-clear { margin: 8px 0 0; width: 100%; }
        }
        /* การ์ดแถบ: เพิ่มมิติ (แสงสะท้อนบนแถบ เงาใต้แถบ ยกตัวเมื่อชี้) */
        #riskChart .divrow{border:1px solid rgba(184,50,63,.08);box-shadow:0 6px 14px -10px rgba(15,23,42,.35)}
        #riskChart .divrow-bar{overflow:hidden;border-radius:14px;box-shadow:0 8px 14px -8px rgba(29,45,80,.45),inset 0 -3px 0 rgba(15,23,42,.10)}
        #riskChart .divrow-seg{position:relative;overflow:hidden;transition:filter .15s ease,transform .15s ease}
        #riskChart .divrow-seg::after{content:'';position:absolute;inset:0;background:linear-gradient(180deg,rgba(255,255,255,.34) 0,rgba(255,255,255,.10) 48%,rgba(255,255,255,0) 52%,rgba(0,0,0,.08) 100%);pointer-events:none}
        #riskChart .divrow-seg>*{position:relative;z-index:1}
        #riskChart .divrow-seg:hover{filter:brightness(1.05) saturate(1.08)}
        .divrow-glab small{color:#334155 !important;font-weight:600 !important}
    </style>
@endsection

@section('content')
    <!-- Filter Section -->
    <div class="row" style="margin-bottom: 14px;">
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
                    <select name="province" class="awr-filter-select" onchange="this.form.elements.district.value=''; this.form.submit()">
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

    <div style="height:1px;background:#e2e8f0;margin:0 0 18px;"></div>

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
                <div class="chart-header" style="--chart-accent:#f97316;">
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
            <div class="col-12 mb-4">
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
                            <span class="scale-legend-item"><span class="scale-legend-swatch" style="background:linear-gradient(90deg,#b8323f,#f08a3c)"></span>ควรปรับปรุง</span>
                            <span class="scale-legend-item"><span class="scale-legend-swatch" style="background:#f4e4b0"></span>ปานกลาง</span>
                            <span class="scale-legend-item"><span class="scale-legend-swatch" style="background:linear-gradient(90deg,#6fb1d6,#1d5a96)"></span>ดี</span>
                            <span class="scale-legend-note">ความยาวแถบ = สัดส่วนผู้ตอบ · เรียงจากข้อที่ควรปรับปรุงมากที่สุด</span>
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
            <div class="col-12 mb-4">
                <div class="chart-card"
                    style="box-shadow: 0 10px 15px -3px rgba(0, 0, 0, 0.05); border-radius: 16px;">
                    <div class="chart-header" style="--chart-accent:#e67e22;">
                        <div class="chart-header-icon"><i class="fas fa-utensils"></i></div>
                        <div>
                            <div class="chart-header-title">พฤติกรรมการปรุง การสั่งอาหาร และการทานนอกบ้าน</div>
                            <div class="chart-header-subtitle">สัดส่วนพฤติกรรมที่พบ</div>
                        </div>
                    </div>
                    <div id="seasoningChart" style="width: 100%;"></div>
                    <div class="chart-figure-footer">
                        <div class="scale-legend" aria-label="คำอธิบายสี">
                            <span class="scale-legend-item"><span class="scale-legend-swatch" style="background:linear-gradient(90deg,#b8323f,#f08a3c)"></span>ควรปรับปรุง</span>
                            <span class="scale-legend-item"><span class="scale-legend-swatch" style="background:#f4e4b0"></span>ปานกลาง</span>
                            <span class="scale-legend-item"><span class="scale-legend-swatch" style="background:linear-gradient(90deg,#6fb1d6,#1d5a96)"></span>ดี</span>
                            <span class="scale-legend-note">ความยาวแถบ = สัดส่วนผู้ตอบ · เรียงจากข้อที่ควรปรับปรุงมากที่สุด</span>
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

            <!-- Chart 4: Awareness & Mindset (Positive) -->
            <div class="col-12 mb-4">
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
                            <span class="scale-legend-item"><span class="scale-legend-swatch" style="background:linear-gradient(90deg,#b8323f,#f08a3c)"></span>ควรปรับปรุง</span>
                            <span class="scale-legend-item"><span class="scale-legend-swatch" style="background:#f4e4b0"></span>ปานกลาง</span>
                            <span class="scale-legend-item"><span class="scale-legend-swatch" style="background:linear-gradient(90deg,#6fb1d6,#1d5a96)"></span>ดี</span>
                            <span class="scale-legend-note">ความยาวแถบ = สัดส่วนผู้ตอบ · เรียงจากข้อที่ควรปรับปรุงมากที่สุด</span>
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
                // โทนนุ่มลง (ลดความสดจัดให้สบายตา) แต่ยังคงแยกสีชัดเจนทั้ง 5 ชิ้น
                'ต่ำกว่า 20 ปี ': '#4a86d8', // slot 1 - blue
                '20 - 35 ปี ': '#ee8a5e', // slot 2 - orange
                '36 - 50 ปี ': '#3dae8b', // slot 3 - aqua
                '51 - 60 ปี ': '#efb94d', // slot 4 - yellow
                'มากกว่า 60 ปี ': '#d98cb3', // slot 5 - magenta
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
                        '<span class="gr-pct">' + pct.toFixed(1) + '%</span>' +
                        '<span class="gr-track"><i></i></span></span>' +
                        '<span class="gr-count"><b>' + value.toLocaleString('en-US') + '</b><small>คน</small></span></div>';
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
                    // ไล่เฉดนุ่มๆ แบบเรียบ (ลดมิติให้สบายตา)
                    color: {
                        linearGradient: { x1: 0, y1: 0, x2: 1, y2: 1 },
                        stops: [
                            [0, shadeHexColor(colorMap[label] || fallback, 8)],
                            [1, shadeHexColor(colorMap[label] || fallback, -6)]
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
                    title: { text: '' },
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
                            borderWidth: 3,
                            borderColor: '#fff',
                            innerSize: '58%',
                            borderRadius: 6,
                            shadow: false,
                            slicedOffset: 6,
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
                    // ชี้ส่วนไหน = แสดงชื่อจังหวัด/จำนวน/ร้อยละ "ในวง" แทน tooltip (ไม่บังกราฟ)
                    onHover: function(event, elements, chart) {
                        const idx = (elements && elements.length) ? elements[0].index : null;
                        if (chart.$hoverIdx !== idx) {
                            chart.$hoverIdx = idx;
                            if (chart.$markLegend) chart.$markLegend(idx);
                            chart.draw();
                        }
                        if (event && event.native && event.native.target) {
                            event.native.target.style.cursor = idx === null ? 'default' : 'pointer';
                        }
                    },
                    plugins: {
                        legend: {
                            display: false
                        },
                        tooltip: { enabled: false },
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

                        // ชี้ที่ส่วนใดส่วนหนึ่ง: แสดงชื่อจังหวัด + จำนวน + ร้อยละในวง
                        var hi = chart.$hoverIdx;
                        if (hi !== null && hi !== undefined && chart.config.data.datasets[0].data[hi] !== undefined) {
                            var hv = chart.config.data.datasets[0].data[hi];
                            var hn = chart.config.data.labels[hi];
                            var hc = provinceColorMap[hn] || NEUTRAL_FALLBACK;
                            var hf = (chartArea.height / 300);
                            ctx.textAlign = 'center';
                            ctx.textBaseline = 'bottom';
                            ctx.font = '700 ' + (hf * 1.0).toFixed(2) + "em 'Sarabun'";
                            ctx.fillStyle = '#475569';
                            ctx.fillText(hn, centerX, centerY - hf * 30);
                            ctx.font = '800 ' + (hf * 1.8).toFixed(2) + "em 'Sarabun'";
                            ctx.fillStyle = hc;
                            ctx.textBaseline = 'middle';
                            ctx.fillText(hv.toLocaleString(), centerX, centerY - hf * 4);
                            ctx.font = '600 ' + (hf * 0.9).toFixed(2) + "em 'Sarabun'";
                            ctx.fillStyle = '#64748b';
                            ctx.textBaseline = 'top';
                            ctx.fillText('คน · ' + (total > 0 ? (hv * 100 / total).toFixed(1) : '0.0') + '%', centerX, centerY + hf * 14);
                            ctx.restore();
                            return;
                        }

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
                        // ไฮไลต์รายการใน legend ที่ตรงกับส่วนที่ชี้ (idx = null = ปกติ)
                        chart.$markLegend = function (idx) {
                            dl.querySelectorAll('.awr-leg-item').forEach(function (el) {
                                const on = idx !== null && idx !== undefined && Number(el.dataset.idx) === idx;
                                el.classList.toggle('is-active', on);
                                el.classList.toggle('is-dim', idx !== null && idx !== undefined && !on);
                            });
                        };

                        items.forEach(item => {
                            const val = chart.data.datasets[0].data[item.index] || 0;
                            const pct = total > 0 ? (val / total) * 100 : 0;

                            const row = document.createElement('div');
                            row.className = 'awr-leg-item';
                            row.dataset.idx = item.index;
                            row.addEventListener('mouseenter', function () {
                                chart.$hoverIdx = item.index;
                                chart.$markLegend(item.index);
                                chart.draw();
                            });
                            row.addEventListener('mouseleave', function () {
                                chart.$hoverIdx = null;
                                chart.$markLegend(null);
                                chart.draw();
                            });
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
            const createDivergingBars = (containerId, tableContainerId, statsData, polarityOverrides, extremeValuesOverrides, mode) => {
                mode = mode || 'bars'; // bars = แถบซ้อน | donut = วงแหวนต่อข้อ | heat = ตารางความร้อน
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
                // สีแบบสองขั้ว ส้ม-แดง (ควรปรับปรุง) <-> ฟ้า-น้ำเงิน (ดี) ผ่านเทากลาง: ส้ม/ฟ้าแยกได้ชัดแม้ตาบอดสีแดง-เขียว
                // ปลายสองข้างเข้มสุด ตรงกลางอ่อนสุด จึงอ่านระดับความเสี่ยงได้ทันทีจากความเข้ม
                const rankColors = ['#b8323f', '#f08a3c', '#f4e4b0', '#6fb1d6', '#1d5a96'];
                const rankTextColors = ['#ffffff', '#3d1d03', '#5a4713', '#08304d', '#ffffff'];

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
                    // ระดับความรู้ (ทราบ/ไม่ทราบ): ต้องเช็กก่อนกฎ "0%" ด้านล่าง เพราะ "50-100%" และ "50%" มี "0%" อยู่ในข้อความ
                    // ไม่ทราบเลย = ปลายแย่สุด, ทราบเป็นบางรายการ = กลาง, ทราบเป็นส่วนใหญ่ = ปลายดี (เชิงบวก)
                    if (/ไม่ทราบเลย/.test(label)) return 4;
                    if (/ทราบเป็นบางรายการ/.test(label)) return 2;
                    if (/ทราบเป็นส่วนใหญ่/.test(label)) return 0;
                    // Lowest Frequency / Best for Negative Polarity (Or Highest Score for +)
                    if (/^([9]|10)$|ไม่เคย|ไม่เลย|ไม่ใช่|ไม่สุ่มเสี่ยง|0%|ไม่เห็นด้วย/i.test(label))
                        return 4;
                    // Infrequent
                    if (/^([7-8])$|นานๆครั้ง|1-2ครั้ง|น้อยกว่า50%/i.test(label)) return 3;
                    // "บ่อย" (ตรงตัว) = ความถี่สูงรองจากทุกวัน -> ฝั่งควรปรับปรุง, ไม่ใช่กลาง
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
                    'ความดันสูง...เสี่ยงโรค': 'positive', // ข้อความความรู้ที่ถูกต้อง - เห็นด้วยมาก = ดี (เดิมไม่มีในรายการจึงถูกกลับสีเป็นควรปรับปรุง)
                    'จำกัดการบริโภคโซเดียม...': 'positive', // ทำบ่อย = ดี (เดิมไม่มีในรายการจึงถูกนับเป็นเสี่ยง)
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
                // ป๊อปอัปอธิบายวิธีคิดเกณฑ์สี (ใช้ร่วมกันทุกการ์ด)
                const openScaleInfo = (bodyHtml, title, opener) => {
                    document.querySelectorAll('.sim-overlay').forEach(el => el.remove());
                    const ov = document.createElement('div');
                    ov.className = 'sim-overlay';
                    ov.innerHTML =
                        '<div class="sim-dialog" role="dialog" aria-modal="true" aria-labelledby="simTitle">' +
                        '<div class="sim-head"><div class="sim-head-ico"><i class="fas fa-palette"></i></div>' +
                        '<div class="sim-head-txt"><div class="sim-eyebrow">' + escapeHtml(title) + '</div>' +
                        '<h3 id="simTitle">วิธีคิดเกณฑ์สี</h3></div>' +
                        '<button type="button" class="sim-close" aria-label="ปิด"><i class="fas fa-times"></i></button></div>' +
                        '<div class="sim-body">' +
                        bodyHtml + '</div>' +
                        '<div class="sim-foot"><button type="button" class="sim-ok">เข้าใจแล้ว</button></div></div>';
                    document.body.appendChild(ov);
                    document.body.classList.add('sim-open');
                    const close = () => {
                        ov.classList.add('is-closing');
                        document.removeEventListener('keydown', onKey);
                        setTimeout(() => { ov.remove(); document.body.classList.remove('sim-open'); }, 160);
                        if (opener) opener.focus();
                    };
                    const onKey = e => { if (e.key === 'Escape') close(); };
                    document.addEventListener('keydown', onKey);
                    ov.addEventListener('click', e => { if (e.target === ov) close(); });
                    ov.querySelector('.sim-close').addEventListener('click', close);
                    ov.querySelector('.sim-ok').addEventListener('click', close);
                    ov.querySelector('.sim-ok').focus();
                };

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

                        // สัดส่วน "ควรปรับปรุง" = ผู้ตอบที่อยู่ปลายส้มสองระดับ (rank 0-1)
                        const risk = segments.filter(s => s.rank <= 1).reduce((a, s) => a + s.pct, 0);
                        rows.push({ c: c, segments: segments, risk: risk, total: total });
                    });

                    // เรียงจากข้อที่ควรปรับปรุงมากที่สุด -> อ่านเป็นลำดับความสำคัญได้ทันที
                    rows.sort((a, b) => b.risk - a.risk);

                    // รวม 5 ระดับเป็น 3 กลุ่มอ่านง่าย: ควรปรับปรุง (rank 0-1) / กลาง (2) / ดี (3-4)
                    // รายละเอียดรายคำตอบดูได้จาก tooltip และปุ่ม "ดูข้อมูลตัวเลข"
                    // ชื่อข้อที่ถูกตัดด้วย ... จากหัวคอลัมน์: แสดงเป็นจุดไข่ปลาจางๆ คั่นกลาง อ่านต่อเนื่องและไม่ดูเหมือนข้อความขาด
                    const pretty = t => escapeHtml(t).replace(/\.{2,}|…/g, '<span class="lbl-gap"> … </span>');
                    const GROUPS = [
                        { key: 'risk', name: 'ควรปรับปรุง', ranks: [0, 1], color: rankColors[0], text: rankTextColors[0] },
                        { key: 'mid', name: 'ปานกลาง', ranks: [2], color: rankColors[2], text: rankTextColors[2] },
                        { key: 'good', name: 'ดี', ranks: [3, 4], color: rankColors[4], text: rankTextColors[4] }
                    ];

                    // สีเฉพาะการ์ด: คงความหมาย อุ่น = ควรปรับปรุง / กลางอ่อน = กลาง / เย็น = ดี แต่แต่ละการ์ดใช้โทนต่างกัน
                    const MODE_PALETTE = {
                        donut: [['#e8590c', '#ffffff'], ['#f6dfb4', '#5a3d0a'], ['#0b7285', '#ffffff']],
                        heat: [['#d9435e', '#ffffff'], ['#f2c14e', '#4a3205'], ['#2a9d78', '#ffffff']]
                    };
                    if (MODE_PALETTE[mode]) {
                        GROUPS.forEach((g, i) => { g.color = MODE_PALETTE[mode][i][0]; g.text = MODE_PALETTE[mode][i][1]; });
                    }

                    const buildGroups = r => {
                        const groups = GROUPS.map(g => {
                            const items = r.segments.filter(s => g.ranks.indexOf(s.rank) !== -1);
                            return {
                                g: g,
                                items: items,
                                pct: items.reduce((a, s) => a + s.pct, 0),
                                count: items.reduce((a, s) => a + s.count, 0)
                            };
                        }).filter(x => x.pct > 0);

                        // ปัดเศษแบบ largest remainder: ผลรวมของ 3 กลุ่มต้องได้ 100% พอดี
                        const sumPct = groups.reduce((a, x) => a + x.pct, 0) || 1;
                        groups.forEach(x => { x.exact = x.pct * 100 / sumPct; x.rnd = Math.floor(x.exact); });
                        let left = 100 - groups.reduce((a, x) => a + x.rnd, 0);
                        groups.slice().sort((a, b) => (b.exact - b.rnd) - (a.exact - a.rnd))
                            .forEach(x => { if (left > 0) { x.rnd++; left--; } });
                        const riskGroup = groups.find(x => x.g.key === 'risk');
                        r.riskRnd = riskGroup ? riskGroup.rnd : 0;
                        return groups;
                    };
                    rows.forEach(r => { r.groups = buildGroups(r); });

                    let html = '';
                    // ไม่แสดงป้ายสรุป "ควรปรับปรุงมากที่สุด" ที่หัวการ์ด (ลบป้ายเก่าที่อาจค้างอยู่)
                    const headerEl = container.closest('.chart-card')
                        ? container.closest('.chart-card').querySelector('.chart-header') : null;
                    if (headerEl) {
                        headerEl.querySelectorAll('.divsum').forEach(function (el) { el.remove(); });
                    }

                    const tip2 = x => '<div class="dtip-h"><span class="dtip-dot" style="background:' + x.g.color + '"></span><b>' + escapeHtml(x.g.name) + '</b><em>' + x.pct.toFixed(1) + '%</em></div><div class="dtip-sub">' + x.count.toLocaleString() + ' คน</div>' +
                        x.items.map(s => '<div class="dtip-r"><span class="dtip-t">' + escapeHtml(s.title) + '</span><span class="dtip-v">' + s.pct.toFixed(1) + '%</span>' +
                            '<i class="dtip-b"><u style="width:' + Math.min(100, s.pct / (x.pct || 1) * 100).toFixed(0) + '%;background:' + x.g.color + '"></u></i></div>').join('');
                    const escAttr = h => h.replace(/&/g, '&amp;').replace(/"/g, '&quot;');
                    if (mode === 'heat') {
                        // Likert แบบแยกซ้าย-ขวา: จัดแนวกึ่งกลางที่ "ปานกลาง" ซ้าย = ควรปรับปรุง ขวา = ดี ใช้แกนเดียวกันทุกข้อ
                        const LV = { risk: 'ระดับ 1–2', mid: 'ระดับ 3', good: 'ระดับ 4–5' };
                        // ปีที่ใช้ชื่อย่อ (ระดับความรู้ ฯลฯ) แสดงเป็นหัวข้อ = ข้อความคำถามจริง เหมือนปีที่ใช้คำถามรายข้อ
                        const FULL_Q = {
                            'ระดับความรู้': 'คุณทราบปริมาณโซเดียมหรือเกลือในอาหารที่คุณรับประทานในแต่ละวัน',
                            'ระดับความพยายาม': 'ในแต่ละวันคุณได้พยายามจำกัดหรือลดปริมาณการบริโภคเกลือและโซเดียม',
                            'ระดับความสำคัญ': 'คุณตระหนักว่าการจำกัดการบริโภคโซเดียมและเกลือ มีความสำคัญมากเท่าใด'
                        };
                        const gv = (r, k) => { const x = r.groups.find(y => y.g.key === k); return x ? x.pct : 0; };
                        const sumP = r => (gv(r, 'risk') + gv(r, 'mid') + gv(r, 'good')) || 1;
                        let L = 0, Rr = 0;
                        rows.forEach(r => { const t = sumP(r); L = Math.max(L, (gv(r, 'risk') + gv(r, 'mid') / 2) * 100 / t); Rr = Math.max(Rr, (gv(r, 'good') + gv(r, 'mid') / 2) * 100 / t); });
                        L = Math.ceil(L / 10) * 10; Rr = Math.ceil(Rr / 10) * 10;
                        const span = L + Rr, pos = v => ((L + v) * 100 / span).toFixed(2) + '%';
                        let ticks = '';
                        for (let d = -Math.floor(L / 20) * 20; d <= Math.floor(Rr / 20) * 20; d += 20) {
                            ticks += '<span class="lk-tk' + (d === 0 ? ' z' : '') + '" style="left:' + pos(d) + '">' + Math.abs(d) + '%</span>';
                        }
                        let grid = '';
                        for (let d = -Math.floor(L / 20) * 20; d <= Math.floor(Rr / 20) * 20; d += 20) {
                            grid += '<span class="lk-gl' + (d === 0 ? ' z' : '') + '" style="left:' + pos(d) + '"></span>';
                        }
                        html += '<div class="hm-cap"><b>ภาพที่ 1</b> ร้อยละของผู้ตอบแบบประเมินจำแนกตามระดับคำตอบรายข้อ แถบเต็ม 100% ของผู้ตอบแต่ละข้อ (ซ้าย = ควรปรับปรุง, กลาง = ปานกลาง, ขวา = ดี) เรียงจากข้อที่มีสัดส่วน "ควรปรับปรุง" สูงสุด</div>';
                        html += '<div class="lk-split"><div class="lk-main"><div class="lk">';
                        
                        rows.forEach((r, ri) => {
                            const t = sumP(r);
                            const midHalf = gv(r, 'mid') / 2 * 100 / t;
                            const startV = -(gv(r, 'risk') * 100 / t + midHalf);
                            const segs = r.groups.filter(x => x.pct > 0).map(x => {
                                const g = x.g;
                                const tipTxt = '<div class="dtip-h"><span class="dtip-dot" style="background:' + g.color + '"></span><b>' + escapeHtml(g.name) + '</b><em>' + x.pct.toFixed(1) + '%</em></div><div class="dtip-sub">' + x.count.toLocaleString() + ' คน</div>' +
                                    x.items.map(s => '<div class="dtip-r"><span class="dtip-t">' + escapeHtml(s.title) + '</span><span class="dtip-v">' + s.pct.toFixed(1) + '%</span>' +
                                        '<i class="dtip-b"><u style="width:' + Math.min(100, s.pct / (x.pct || 1) * 100).toFixed(0) + '%;background:' + g.color + '"></u></i></div>').join('');
                                const w = (x.pct * 100 / t).toFixed(2);
                                return '<div class="sb-seg" style="flex:' + w + ' 1 0;background:' + g.color + ';color:' + g.text + '" data-dtip="' + escAttr(tipTxt) + '">' + (x.rnd >= 1 ? '<b>' + x.rnd + '%</b>' : '') + (x.rnd >= 14 ? '<small>' + x.count.toLocaleString() + ' คน</small>' : '') + '</div>';
                            }).join('');
                            html += '<div class="lk-item"><div class="wf-head"><span class="sb-q">' + pretty(FULL_Q[r.c] || r.c) + '</span></div>' +
                                '<div class="lk-track">' + '<div class="lk-bar">' + segs + '</div></div></div>';
                        });
                        html += '</div></div>';
                        const byRisk = rows.slice().sort((a, b) => gv(b, 'risk') / sumP(b) - gv(a, 'risk') / sumP(a));
                        const byGood = rows.slice().sort((a, b) => gv(b, 'good') / sumP(b) - gv(a, 'good') / sumP(a));
                        const pc = (r, k) => (gv(r, k) * 100 / sumP(r)).toFixed(1);
                        // ฝั่งสรุป: ใช้สี/ลำดับเดียวกับกราฟ (ซ้าย = ควรปรับปรุง, กลาง, ขวา = ดี)
                        const nMax = Math.max.apply(null, rows.map(r => r.total || 0));
                        const totAll = rows.reduce((a, r) => a + (r.total || 0), 0) || 1;
                        const pcOf = (r, k) => gv(r, k) * 100 / sumP(r);
                        const avgOf = k => rows.reduce((a, r) => a + pcOf(r, k) * (r.total || 0), 0) / totAll;
                        const rowsHtml = GROUPS.map(g => {
                            const vals = rows.map(r => pcOf(r, g.key));
                            const lo = Math.round(Math.min.apply(null, vals)), hi = Math.round(Math.max.apply(null, vals));
                            const av = avgOf(g.key);
                            return '<div class="lk-g"><div class="lk-gh"><span class="lk-gd" style="background:' + g.color + '"></span><b>' + g.name + '</b><small>' + LV[g.key] + '</small><em style="color:' + (g.key === 'mid' ? '#6d55b8' : g.color) + '">' + av.toFixed(1) + '%</em></div>'  + '</div>';
                        }).join('');
                        html += '<aside class="lk-side"><div class="lk-sh"><span class="lk-shi"><i class="fas fa-chart-pie"></i></span><div class="lk-sht"><b>สรุปผล</b><small>ภาพรวมจากทุกข้อที่ประเมิน</small></div></div>' +
                            '<ul class="lk-pts">' +
                            '<li class="is-risk"><span class="lk-ic"><i class="fas fa-exclamation-triangle"></i></span><div class="lk-tx"><small>ข้อที่ควรปรับปรุงมากที่สุด</small><b>' + pretty(FULL_Q[byRisk[0].c] || byRisk[0].c) + '</b></div><div class="lk-v"><em>' + pc(byRisk[0], 'risk') + '%</em><small>ของผู้ตอบ</small></div></li>' +
                            '<li class="is-good"><span class="lk-ic"><i class="fas fa-check-circle"></i></span><div class="lk-tx"><small>ข้อที่ผู้ตอบอยู่ในระดับดีมากที่สุด</small><b>' + pretty(FULL_Q[byGood[0].c] || byGood[0].c) + '</b></div><div class="lk-v"><em>' + pc(byGood[0], 'good') + '%</em><small>ของผู้ตอบ</small></div></li>' +
                            '<li class="is-n"><span class="lk-ic"><i class="fas fa-users"></i></span><div class="lk-tx"><small>จำนวนผู้ตอบต่อข้อ</small><b>ผู้ตอบแบบประเมิน (n)</b></div><div class="lk-v"><em>' + nMax.toLocaleString() + '</em><small>คน</small></div></li>' +
                            '</ul></aside></div>';
                        html += '<div class="hm-note"><b>หมายเหตุ:</b> ร้อยละคำนวณจากจำนวนผู้ตอบในแต่ละข้อ (n) แต่ละแถบรวมเป็น 100% ของผู้ตอบข้อนั้น ความกว้างของแต่ละส่วนเทียบกันได้ตรง ๆ · วางเมาส์เหนือแต่ละส่วนเพื่อดูรายละเอียดรายคำตอบ</div>';
                    }

                    const buildSummaryAside = () => {
                        const gx = (r, k) => { const x = r.groups.find(y => y.g.key === k); return x ? x.exact : 0; };
                        const gRisk = GROUPS.find(g => g.key === 'risk'), gMid = GROUPS.find(g => g.key === 'mid'), gGood = GROUPS.find(g => g.key === 'good');
                        const topRisk = rows.slice().sort((a, b) => gx(b, 'risk') - gx(a, 'risk'))[0];
                        const topGood = rows.slice().sort((a, b) => gx(b, 'good') - gx(a, 'good'))[0];
                        const nMaxB = Math.max.apply(null, rows.map(r => r.total || 0));
                        const tint = (hex, a) => { const h = hex.replace('#', ''); return 'rgba(' + [0, 2, 4].map(i => parseInt(h.substr(i, 2), 16)).join(',') + ',' + a + ')'; };
                        const pcv = (hex) => '--lc:' + hex + ';--lb:' + tint(hex, 0.08);
                        return '<aside class="lk-side"><div class="lk-sh"><span class="lk-shi"><i class="fas fa-chart-pie"></i></span><div class="lk-sht"><b>สรุปผล</b><small>ภาพรวมจากทุกข้อที่ประเมิน</small></div></div>' +
                            '<ul class="lk-pts">' +
                            '<li class="is-risk" style="' + pcv(gRisk.color) + '"><span class="lk-ic" style="background:' + tint(gRisk.color, 0.14) + '"><i class="fas fa-exclamation-triangle"></i></span><div class="lk-tx"><small>ข้อที่ควรปรับปรุงมากที่สุด</small><b>' + pretty(topRisk.c) + '</b></div><div class="lk-v"><em>' + gx(topRisk, 'risk').toFixed(1) + '%</em><small>ของผู้ตอบ</small></div></li>' +
                            '<li class="is-good" style="' + pcv(gGood.color) + '"><span class="lk-ic" style="background:' + tint(gGood.color, 0.16) + '"><i class="fas fa-check-circle"></i></span><div class="lk-tx"><small>ข้อที่ผู้ตอบอยู่ในระดับดีมากที่สุด</small><b>' + pretty(topGood.c) + '</b></div><div class="lk-v"><em>' + gx(topGood, 'good').toFixed(1) + '%</em><small>ของผู้ตอบ</small></div></li>' +
                            '<li class="is-n"><span class="lk-ic"><i class="fas fa-users"></i></span><div class="lk-tx"><small>จำนวนผู้ตอบต่อข้อ</small><b>ผู้ตอบแบบประเมิน (n)</b></div><div class="lk-v"><em>' + nMaxB.toLocaleString() + '</em><small>คน</small></div></li>' +
                            '</ul></aside></div>';
                    };

                    if (mode === 'donut') {
                        // โดนัทต่อข้อ: วงแหวนสามช่วง กลางวงบอกสัดส่วน "ควรปรับปรุง"
                        const R = 42, C = 2 * Math.PI * R;
                        const mix = (hex, t) => {   // ผสมกับขาว (t>0) หรือดำ (t<0) เพื่อไล่เฉดให้ดูมีมิติ
                            const h = hex.replace('#', '');
                            return '#' + [0, 2, 4].map(i => {
                                const v = parseInt(h.substr(i, 2), 16);
                                const m = t >= 0 ? v + (255 - v) * t : v * (1 + t);
                                return Math.round(Math.max(0, Math.min(255, m))).toString(16).padStart(2, '0');
                            }).join('');
                        };
                        let uid = 0;
                        html += '<div class="lk-split"><div class="lk-main"><div class="dn-grid" data-n="' + rows.length + '">';
                        rows.forEach(r => {
                            let off = 0;
                            const id = 'dng' + (++uid) + '-' + Math.random().toString(36).slice(2, 6);
                            const defs = '<defs>' + r.groups.map((x, i) =>
                                '<linearGradient id="' + id + i + '" x1="0" y1="0" x2="1" y2="1"><stop offset="0" stop-color="' + mix(x.g.color, 0.28) + '"/><stop offset="1" stop-color="' + mix(x.g.color, -0.14) + '"/></linearGradient>').join('') +
                                '<radialGradient id="' + id + 'c" cx=".35" cy=".3" r=".9"><stop offset="0" stop-color="#ffffff"/><stop offset="1" stop-color="#eef2f6"/></radialGradient></defs>';
                            const arcs = r.groups.map((x, i) => {
                                const len = C * x.pct / 100;
                                const gap = r.groups.length > 1 ? 1.5 : 0;
                                const a = '<circle class="dn-arc" cx="55" cy="55" r="' + R + '" fill="none" stroke="url(#' + id + i + ')" stroke-width="14" ' +
                                    'stroke-dasharray="' + Math.max(0, len - gap).toFixed(2) + ' ' + (C - Math.max(0, len - gap)).toFixed(2) + '" stroke-dashoffset="' + (-off).toFixed(2) +
                                    '" transform="rotate(-90 55 55)" data-dtip="' + escAttr(tip2(x)) + '"></circle>';
                                off += len;
                                return a;
                            }).join('');
                            const rowsTxt = GROUPS.map(g => {
                                const x = r.groups.find(y => y.g.key === g.key);
                                return '<div class="dn-r"><i style="background:' + g.color + '"></i><span>' + g.name + '</span><b>' + (x ? x.rnd : 0) + '%</b><small>' + (x ? x.count.toLocaleString() : 0) + ' คน</small></div>';
                            }).join('');
                            html += '<div class="dn"><div class="dn-title">' + pretty(r.c) + '</div><div class="dn-body">' +
                                '<svg class="dn-svg" viewBox="0 0 110 110" role="img" aria-label="' + escAttr(escapeHtml(r.c)) + '">' + defs + '<circle cx="55" cy="55" r="' + R + '" fill="none" stroke="#e8edf3" stroke-width="14"></circle>' + arcs +
                                '<circle class="dn-disc" cx="55" cy="55" r="32" fill="url(#' + id + 'c)"></circle>' +
                                '<text x="55" y="56" text-anchor="middle" class="dn-pct" fill="' + GROUPS[0].color + '">' + r.riskRnd + '%</text><text x="55" y="67" text-anchor="middle" class="dn-cap">ควรปรับปรุง</text></svg>' +
                                '<div class="dn-list">' + rowsTxt + '</div></div></div>';
                        });
                        html += '</div></div>' + buildSummaryAside();
                    }

                    if (mode === 'bars') html += '<div class="lk-split"><div class="lk-main">';
                    if (mode === 'bars') rows.forEach(r => {
                        const groups = r.groups;

                        // ข้อมูลที่แสดงตอนชี้: ป้ายกลุ่ม + รายคำตอบพร้อมแถบสัดส่วนเล็ก ๆ
                        const tip = x => '<div class="dtip-h"><span class="dtip-dot" style="background:' + x.g.color + '"></span>' +
                            '<b>' + escapeHtml(x.g.name) + '</b><em>' + x.pct.toFixed(1) + '%</em></div>' +
                            '<div class="dtip-sub">' + x.count.toLocaleString() + ' คน</div>' +
                            x.items.map(s => '<div class="dtip-r"><span class="dtip-t">' + escapeHtml(s.title) + '</span>' +
                                '<span class="dtip-v">' + s.pct.toFixed(1) + '%</span>' +
                                '<i class="dtip-b"><u style="width:' + Math.min(100, s.pct / (x.pct || 1) * 100).toFixed(0) + '%;background:' + x.g.color + '"></u></i></div>').join('');

                        const segHtml = groups.map(x =>
                            '<div class="divrow-seg" style="flex:' + x.pct.toFixed(3) + ' 1 0%;background:' + x.g.color +
                            ';color:' + x.g.text + '" data-dtip="' + tip(x).replace(/&/g, '&amp;').replace(/"/g, '&quot;') + '">' +
                            (x.pct >= 6 ? '<b>' + x.rnd + '%</b>' : '') + '</div>'
                        ).join('');

                        const labHtml = groups.map(x =>
                            '<span class="divrow-glab" style="flex:' + x.pct.toFixed(3) + ' 1 0%" data-dtip="' + tip(x).replace(/&/g, '&amp;').replace(/"/g, '&quot;') + '">' +
                            x.g.name + (x.pct < 6 ? ' ' + x.rnd + '%' : '') +
                            '<small>' + x.count.toLocaleString() + ' คน</small></span>'
                        ).join('');

                        html += '<div class="divrow"><div class="divrow-head"><div class="divrow-label">' + pretty(r.c) +
                            '</div><span class="divrow-risk' + (r.risk < 10 ? ' is-low' : '') + '" title="ผู้ตอบที่อยู่ในกลุ่มคำตอบที่ควรปรับปรุง">ควรปรับปรุง ' +
                            r.riskRnd + '%</span></div>' +
                            '<div class="divrow-bar">' + segHtml + '</div>' +
                            '<div class="divrow-glabs">' + labHtml + '</div></div>';
                    });

                    if (mode === 'bars') {
                        html += '</div>';
                        html += buildSummaryAside();
                    }

                    container.innerHTML = html;

                    // ทูลทิปแบบกำหนดเอง (แทน title ของเบราว์เซอร์) — ผูกครั้งเดียว
                    if (!window.__dtipBound) {
                        window.__dtipBound = true;
                        const tipEl = document.createElement('div');
                        tipEl.className = 'dtip';
                        document.body.appendChild(tipEl);
                        const place = e => {
                            const w = tipEl.offsetWidth, h = tipEl.offsetHeight;
                            let x = e.clientX - w / 2, y = e.clientY - h - 16;
                            x = Math.max(8, Math.min(x, window.innerWidth - w - 8));
                            if (y < 8) y = e.clientY + 20;
                            tipEl.style.transform = 'translate(' + x + 'px,' + y + 'px)';
                        };
                        document.addEventListener('mousemove', e => {
                            const t = e.target.closest ? e.target.closest('[data-dtip]') : null;
                            if (!t) { tipEl.classList.remove('on'); return; }
                            if (tipEl.__src !== t) { tipEl.innerHTML = t.getAttribute('data-dtip'); tipEl.__src = t; }
                            tipEl.classList.add('on');
                            place(e);
                        });
                        document.addEventListener('scroll', () => tipEl.classList.remove('on'), true);
                    }

                    // ไอคอน (i) ข้างคำอธิบายสี: คลิกเพื่อดูวิธีคิดเกณฑ์สีของการ์ดนี้
                    const cardEl = container.closest('.chart-card');
                    const legendEl = cardEl ? cardEl.querySelector('.scale-legend') : null;
                    if (legendEl) {
                        legendEl.querySelectorAll('.scale-info-btn').forEach(el => el.remove());
                        if (MODE_PALETTE[mode]) {
                            legendEl.querySelectorAll('.scale-legend-swatch').forEach((el, i) => {
                                if (GROUPS[i]) el.style.background = GROUPS[i].color;
                            });
                        }

                        const cardTitle = (cardEl.querySelector('.chart-header-title') || {}).textContent || '';
                        const GDESC = {
                            risk: 'คำตอบที่เสี่ยงต่อสุขภาพที่สุด 2 ระดับ ควรช่วยกันปรับพฤติกรรม',
                            mid: 'คำตอบระดับปานกลาง ยังปรับให้ดีขึ้นได้',
                            good: 'คำตอบที่ดีต่อสุขภาพที่สุด 2 ระดับ'
                        };
                        const fmt = n => n.toLocaleString();

                        const meaning = GROUPS.map(g =>
                            '<div class="sim-mean"><span class="sim-swatch" style="background:' + g.color + '"></span>' +
                            '<div><b>' + g.name + '</b><span>' + GDESC[g.key] + '</span></div></div>'
                        ).join('');

                        // ตัวอย่างคำนวณจากข้อแรกของการ์ด (ข้อที่ควรปรับปรุงมากที่สุด) ด้วยตัวเลขจริง
                        const ex = rows[0];
                        const exRisk = ex ? ex.groups.find(x => x.g.key === 'risk') : null;
                        const exHtml = (ex && exRisk) ?
                            '<div class="sim-ex"><div class="sim-ex-q">' + escapeHtml(ex.c) + '</div>' +
                            '<div class="sim-ex-calc"><span><small>ผู้ตอบกลุ่มควรปรับปรุง</small><b>' + fmt(exRisk.count) + ' คน</b></span>' +
                            '<em>÷</em><span><small>ผู้ตอบทั้งหมด</small><b>' + fmt(ex.total) + ' คน</b></span>' +
                            '<em>=</em><span class="is-res"><small>สัดส่วน</small><b>' + exRisk.exact.toFixed(1) + '%</b></span></div>' +
                            '<div class="sim-ex-note">แสดงในแถบเป็น ' + exRisk.rnd + '% (ปัดเศษให้ทั้ง 3 กลุ่มรวมกันได้ 100%)</div></div>' : '';

                        const qRows = rows.map(r => {
                            const lines = r.groups.map(x =>
                                '<div class="sim-grp is-' + x.g.key + '">' +
                                '<span class="sim-grp-name"><i style="background:' + x.g.color + '"></i>' + x.g.name + '</span>' +
                                '<span class="sim-chips">' + x.items.map(i => '<span class="sim-chip">' + escapeHtml(i.title) + '</span>').join('') + '</span>' +
                                '<span class="sim-grp-pct">' + x.rnd + '%</span></div>'
                            ).join('');
                            return '<div class="sim-q"><div class="sim-qt">' + escapeHtml(r.c) + '</div>' + lines + '</div>';
                        }).join('');

                        const infoHtml =
                            '<div class="sim-sec">สีแต่ละสีหมายถึงอะไร</div>' + meaning +
                            '<div class="sim-sec">ตัวอย่างการคำนวณ</div>' + exHtml +
                            '<div class="sim-sec">คำตอบในแต่ละกลุ่ม แยกตามข้อ</div>' + qRows +
                            '<div class="sim-tip"><i class="fas fa-lightbulb"></i><span>บางข้อถามสิ่งที่ <b>ควรทำ</b> หรือถามความเห็น เช่น "เห็นด้วย" สีจะกลับด้าน คือคำตอบที่เห็นด้วยจะเป็นสีน้ำเงิน (ดี) ตั้งค่าได้ที่ "ตั้งค่าแดชบอร์ด"</span></div>';

                        const btn = document.createElement('button');
                        btn.type = 'button';
                        btn.className = 'scale-info-btn';
                        btn.setAttribute('aria-expanded', 'false');
                        btn.setAttribute('aria-label', 'วิธีคิดเกณฑ์สี');
                        btn.title = 'วิธีคิดเกณฑ์สี';
                        btn.innerHTML = '<i class="fas fa-info"></i>';
                        btn.setAttribute('aria-haspopup', 'dialog');
                        btn.addEventListener('click', () => openScaleInfo(infoHtml, cardTitle, btn));
                        const noteEl = legendEl.querySelector('.scale-legend-note');
                        legendEl.insertBefore(btn, noteEl);
                    }
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
                createDivergingBars('seasoningChart', 'seasoningTable', statsPanel2, polarityPanel2, extremeValuesPanel2, 'donut');
                createDivergingBars('mindsetChart', 'mindsetTable', statsPanel4, polarityPanel4, extremeValuesPanel4, 'heat');
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

    <script>
        // ตัวกรองเร็วขึ้น: (1) แถบโหลดทันทีที่เลือก (2) โหลดตัวเลือกอื่นไว้ล่วงหน้าเงียบๆ ให้เบราว์เซอร์เก็บไว้
        // ตอนผู้ใช้เลือกจริงจะขึ้นทันทีจากแคชในเครื่อง (เซิร์ฟเวอร์ส่ง Cache-Control 2 นาที)
        (function () {
            var form = document.querySelector('.awr-filter-bar');
            if (!form) return;

            var bar = document.createElement('div');
            bar.className = 'awr-loadbar';
            document.body.appendChild(bar);
            form.addEventListener('change', function () { bar.classList.add('on'); document.body.classList.add('awr-busy'); });
            window.addEventListener('pageshow', function () { bar.classList.remove('on'); document.body.classList.remove('awr-busy'); });

            var stopped = false;
            ['pointerdown', 'keydown'].forEach(function (ev) {
                form.addEventListener(ev, function () { stopped = true; }, { once: true, capture: true });
            });

            function urlWith(name, value) {
                var fd = new FormData(form);
                fd.set(name, value);
                if (name === 'province') fd.set('district', '');
                return form.action + '?' + new URLSearchParams(fd).toString();
            }

            var queue = [];
            ['fiscal_year', 'province'].forEach(function (name) { // อำเภอมีเยอะ ไม่โหลดล่วงหน้าเพื่อไม่ให้เซิร์ฟเวอร์ทำงานหนักเกินจำเป็น
                var sel = form.elements[name];
                if (!sel) return;
                Array.prototype.forEach.call(sel.options, function (o) {
                    if (o.selected) return;
                    if (name === 'fiscal_year' && o.value === '') return; // "ทั้งหมด" ของทุกปี หนักสุด ไม่โหลดล่วงหน้า
                    queue.push(urlWith(name, o.value));
                });
            });

            function next() {
                if (stopped || !queue.length) return;
                fetch(queue.shift(), { credentials: 'same-origin', headers: { 'X-Prefetch': '1' } })
                    .then(function (r) { return r.text(); })
                    .catch(function () {})
                    .then(function () { setTimeout(next, 250); });
            }
            var start = function () { setTimeout(next, 1200); };
            if (document.readyState === 'complete') start(); else window.addEventListener('load', start);
        })();
    </script>
@endsection
