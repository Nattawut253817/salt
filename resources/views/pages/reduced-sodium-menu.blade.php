@extends($hideLayout ?? false ? 'layouts.blank' : 'layouts.layout')

@section('title', 'เมนูลดโซเดียม - Salt & Sodium Smart Monitor')
@section('header_title', ($hideLayout ?? false) ? '' : 'เมนูลดโซเดียม')
@section('header_subtitle', ($hideLayout ?? false) ? '' : 'การขับเคลื่อนและรณรงค์เมนูอาหารเพื่อสุขภาพ')

@section('extra_css')
    <style>
        @if($hideLayout ?? false)
            /* Hide scrollbar for Chrome, Safari and Opera */
            body::-webkit-scrollbar {
                display: none;
            }

            /* Hide scrollbar for IE, Edge and Firefox */
            body {
                -ms-overflow-style: none;
                /* IE and Edge */
                scrollbar-width: none;
                /* Firefox */
            }

        @endif

        /* Single pill-shaped filter bar (matches the same "ตัวกรอง" bar
           used on /awareness): a funnel-icon heading, each select inline
           with a thin divider between segments and a static field-name
           label before the value, ending in a rounded ghost "clear" pill -
           instead of the old stacked label-above-box layout. */
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
            margin-bottom: 6px;
            font-family: 'Sarabun', sans-serif !important;
        }

        .awr-filter-bar i {
            font-family: "Font Awesome 6 Free" !important;
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
            min-width: 160px;
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
            font-family: 'Sarabun', 'TH Sarabun New', 'TH Sarabun PS', sans-serif !important;
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

        .awr-filter-select:disabled {
            color: #94a3b8;
            cursor: not-allowed;
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
            border: none;
            cursor: pointer;
        }

        .awr-filter-clear:hover {
            background: #e2e8f0;
            color: #1e293b;
        }

        .awr-filter-clear i {
            font-size: 0.78rem;
        }

        @media (max-width: 1000px) {
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
           aggregates ($menus/$barChartData/$pieChartData/$mapData) the
           charts below already receive - one consolidated card, snug under
           the filter bar, rather than 4 separate shadowed boxes. Each item
           keeps its own tinted icon-square accent, echoing --chart-accent's
           per-card color identity used by the chart-row below. */
        /* Soft Dimensional Glass: the strip itself becomes a faint tri-tone
           gradient "tray" and each item floats above it as a translucent,
           frosted tile - blurred backdrop, a bright inset hairline along the
           top edge for a glass sheen, and a soft accent-tinted shadow beneath
           for lift. Depth comes from layered shadows, not a single flat one. */
        .awr-kpi-strip {
            display: flex;
            align-items: stretch;
            gap: 14px;
            margin-bottom: 25px;
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

        @media (max-width: 1000px) {
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
                overflow: hidden;
                text-overflow: ellipsis;
                max-width: 100%;
            }
        }

        /* Chart row */
        .chart-row {
            display: grid;
            grid-template-columns:
                {{ ($hideLayout ?? false) ? 'repeat(3, 1fr)' : '1fr 1.5fr 1fr' }}
            ;
            gap: 20px;
            margin-bottom: 25px;
            /* Reduced from 40px */
            align-items: stretch;
        }

        .chart-card {
            background: #fff;
            border-radius: 15px;
            box-shadow: 0 4px 15px rgba(0, 0, 0, 0.05);
            padding: 15px;
            /* Reduced from 20px */
            display: flex;
            flex-direction: column;
            transition: transform 0.25s ease, box-shadow 0.25s ease;
        }

        .chart-card:hover {
            transform: translateY(-3px);
            box-shadow: 0 16px 34px -8px rgba(15, 23, 42, 0.14);
        }

        /* Card header, matching the dashboard's design language:
           icon badge + title + subtitle, separated with a divider. */
        .chart-card-head {
            display: flex;
            align-items: flex-start;
            gap: 12px;
            margin-bottom: 14px;
            padding-bottom: 12px;
            border-bottom: 1px solid rgba(15, 23, 42, 0.06);
        }

        .chart-card-icon {
            width: 36px;
            height: 36px;
            min-width: 36px;
            border-radius: 11px;
            background: var(--chart-accent, #ec4899);
            color: #fff;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 0.95rem;
            box-shadow: 0 6px 14px -4px var(--chart-accent, #ec4899);
        }

        .chart-card-title {
            font-size: 0.92rem;
            font-weight: 800;
            color: #1e293b;
        }

        .chart-card-subtitle {
            font-size: 0.72rem;
            color: #94a3b8;
            font-weight: 600;
            margin-top: 2px;
        }

        .chart-card.accent-pink   { --chart-accent: #ec4899; background: #fffafc; }
        .chart-card.accent-purple { --chart-accent: #8b5cf6; background: #fbfaff; }
        .chart-card.accent-amber  { --chart-accent: #f59e0b; background: #fffdf7; }

        @media (max-width: 1200px) {
            .chart-row {
                grid-template-columns: 1fr;
            }
        }

        /* Table styles */
        .menu-table-container {
            border-radius: 15px;
            overflow-x: auto;
            overflow-y: hidden;
            -webkit-overflow-scrolling: touch;
            box-shadow: 0 4px 20px rgba(0, 0, 0, 0.08);
            background: white;
        }

        .menu-table {
            width: 100%;
            border-collapse: collapse;
        }

        /* Below tablet width the table's own nowrap content (food photo,
           status pills, badges) can no longer fit inside 100% width
           without crushing every column - give the table a comfortable
           minimum width instead, so .menu-table-container above scrolls
           it horizontally rather than clipping or squeezing cells
           unreadably small. Desktop (>=1280px) has far more room than
           this and never reaches the breakpoint, so nothing changes
           there. */
        @media (max-width: 1024px) {
            .menu-table {
                min-width: 920px;
            }
        }

        .menu-table th {
            background: linear-gradient(135deg, #eef2ff, #f5f3ff);
            color: #312e81;
            padding: 14px 12px;
            text-align: center;
            font-weight: 800;
            border: none;
            border-bottom: 2px solid #e0e7ff;
            font-size: 0.82rem;
            white-space: nowrap;
        }

        .menu-table td {
            padding: 14px 10px;
            border: none;
            border-bottom: 1px solid #edf2f7;
            text-align: center;
            vertical-align: middle;
            color: #2d3748;
            font-size: 0.9rem;
        }

        .menu-table tbody tr:last-child td {
            border-bottom: none;
        }

        .menu-table tr:hover {
            background-color: #f7fafc;
        }

        .food-img {
            width: 90px;
            /* Slightly smaller */
            height: 68px;
            /* Maintain aspect ratio */
            object-fit: cover;
            border-radius: 10px;
            background-color: #f7fafc;
            border: 2px solid #fff;
            box-shadow: 0 2px 8px rgba(0, 0, 0, 0.1);
        }

        /* Professional table redesign: semantic classes replacing the
           per-row inline styles that used to duplicate these same rules
           on every <td>. Colors are unchanged from the previous inline
           versions - only the mechanism moved from inline style="" to a
           reusable class. */
        .menu-name-cell {
            text-align: left;
            font-weight: 700;
            font-size: 1.02rem;
            color: #1e293b;
        }

        .kitchen-type-tag {
            display: inline-block;
            background-color: #e0f2fe;
            color: #0369a1;
            border: none;
            padding: 6px 12px;
            border-radius: 6px;
            font-size: 0.85rem;
            font-weight: 700;
        }

        .sodium-pill {
            display: inline-flex;
            align-items: center;
            gap: 8px;
            padding: 6px 12px;
            border-radius: 20px;
            font-weight: 800;
            font-size: 0.88rem;
            white-space: nowrap;
        }

        .sodium-pill-before {
            background: #fffbeb;
            color: #b45309;
            border: 1px solid #fde68a;
        }

        .sodium-pill-good {
            background: #f0fdfa;
            color: #115e59;
            border: 1px solid #ccfbf1;
        }

        .sodium-pill-bad {
            background: #fff5f5;
            color: #c53030;
            border: 1px solid #feb2b2;
        }

        .sodium-pill-pending {
            background: #f8fafc;
            color: #64748b;
            border: 1px solid #e2e8f0;
        }

        /* "โซเดียมที่ลดได้" column: the mg amount + percent change,
           reusing $sodiumReductionPercent's existing sign convention
           (positive = real reduction, negative = the dish got saltier).
           A pending (not-yet-measured) row shows a plain muted dash,
           never a fabricated number - same guard as every other column
           driven by $hasSodiumReading. */
        .reduced-badge {
            display: inline-flex;
            align-items: center;
            gap: 8px;
            padding: 6px 12px;
            border-radius: 14px;
            font-weight: 700;
        }

        .reduced-badge.is-good {
            background: #f0f4ff;
            color: #1e1b4b;
            border: 1px solid #e0e7ff;
        }

        .reduced-badge.is-bad {
            background: #fff5f5;
            color: #c53030;
            border: 1px solid #feb2b2;
        }

        .reduced-badge i {
            font-size: 0.85rem;
        }

        /* The "good" (reduced) case gets a lighter blue accent on its
           percent line instead of the value line's dark indigo, matching
           the two-tone reference; "bad" (increase) keeps the plain dimmed
           red from .reduced-badge-percent's base opacity below. */
        .reduced-badge.is-good .reduced-badge-percent {
            color: #6394f7;
            opacity: 1;
        }

        /* The mg amount itself gets its own deep indigo, one shade more
           visibly blue-violet than the badge's base text color, so the
           headline number reads darkest of the two lines. Scoped to the
           "good" (reduced) case only - "bad" (increase) stays plain red. */
        .reduced-badge.is-good .reduced-badge-value {
            color: #3730a3;
        }

        .reduced-badge-text {
            display: flex;
            flex-direction: column;
            line-height: 1.25;
            text-align: left;
        }

        .reduced-badge-value {
            font-size: 0.88rem;
            font-weight: 800;
            white-space: nowrap;
        }

        .reduced-badge-percent {
            font-size: 0.7rem;
            font-weight: 700;
            opacity: 0.8;
        }

        .reduced-badge-pending {
            color: #cbd5e1;
            font-weight: 700;
            font-size: 1.1rem;
        }

        /* Same "not measured yet" idea as .reduced-badge-pending's lone
           dash in the table, but as a short label instead - used in the
           menu-detail modal's arrow slot, which has room for a word. */
        .reduced-badge-pending.is-label {
            display: inline-flex;
            align-items: center;
            gap: 6px;
            padding: 6px 12px;
            border-radius: 14px;
            background: #f8fafc;
            border: 1px solid #e2e8f0;
            color: #64748b;
            font-size: 0.8rem;
            font-weight: 700;
        }

        .agency-tag {
            display: inline-flex;
            align-items: center;
            gap: 9px;
            padding: 7px 14px;
            border-radius: 16px;
            font-weight: 700;
            font-size: 0.82rem;
            background: #f0f4ff;
            color: #1e1b4b;
            border: 1px solid #e0e7ff;
            max-width: 100%;
            text-align: left;
        }

        .agency-tag-icon {
            flex-shrink: 0;
            font-size: 0.9rem;
        }

        .agency-tag-text {
            display: flex;
            flex-direction: column;
            min-width: 0;
            line-height: 1.3;
        }

        .agency-tag-name {
            color: #1e40af;
            white-space: nowrap;
            overflow: hidden;
            text-overflow: ellipsis;
        }

        .agency-tag-province {
            font-size: 0.7rem;
            font-weight: 600;
            color: #6394f7;
            opacity: 1;
            white-space: nowrap;
            overflow: hidden;
            text-overflow: ellipsis;
        }

        .pagination-wrapper {
            margin-top: 25px;
            /* Reduced from 40px */
            display: flex;
            justify-content: center;
            margin-bottom: 15px;
        }

        .pagination-wrapper .pagination {
            display: flex;
            list-style: none;
            padding: 0;
            margin: 0;
            gap: 10px;
            border: none;
            flex-wrap: wrap;
            justify-content: center;
        }

        .pagination-wrapper .page-item .page-link {
            border-radius: 12px !important;
            border: 1px solid #e2e8f0;
            color: #f42ca7;
            font-weight: 700;
            padding: 12px 18px;
            transition: all 0.2s cubic-bezier(0.4, 0, 0.2, 1);
            box-shadow: 0 1px 2px rgba(0, 0, 0, 0.05);
            font-family: 'Sarabun', sans-serif;
            background: white;
        }

        .pagination-wrapper .page-item.active .page-link {
            background-color: #f42ca7 !important;
            border-color: #f42ca7 !important;
            color: white !important;
            box-shadow: 0 10px 15px -3px rgba(67, 56, 202, 0.4);
        }

        .pagination-wrapper .page-item .page-link:hover:not(.active) {
            background-color: #f5f3ff;
            color: #f42ca7;
            border-color: #c4b5fd;
            transform: translateY(-2px);
            box-shadow: 0 4px 6px -1px rgba(0, 0, 0, 0.1);
        }

        .pagination-wrapper .page-item.disabled .page-link {
            color: #94a3b8;
            background-color: #f8fafc;
            border-color: #f1f5f9;
            box-shadow: none;
        }

        /* Detail Modal Styles */
        .clickable-row {
            cursor: pointer;
            transition: all 0.2s ease;
        }

        .clickable-row:hover {
            background-color: #f0f7ff !important;
        }

        .detail-modal {
            display: none;
            position: fixed;
            z-index: 11000;
            left: 0;
            top: 0;
            width: 100%;
            height: 100%;
            overflow: auto;
            background-color: rgba(0, 0, 0, 0.6);
            backdrop-filter: blur(5px);
            animation: fadeIn 0.3s ease;
            align-items: center;
            justify-content: center;
            padding-top: 24px;
            padding-bottom: 24px;
            box-sizing: border-box;
        }

        .modal-dialog-new {
            margin: 0 auto;
            width: 92%;
            max-width: 750px;
            background: white;
            border-radius: 26px;
            box-shadow: 0 25px 60px -12px rgba(15, 23, 42, 0.35);
            overflow: hidden;
            position: relative;
            transform-origin: center;
            animation: slideUp 0.4s cubic-bezier(0.16, 1, 0.3, 1);
        }

        @keyframes fadeIn { from { opacity: 0; } to { opacity: 1; } }
        @keyframes slideUp {
            from { transform: translateY(30px) scale(0.95); opacity: 0; }
            to { transform: translateY(0) scale(1); opacity: 1; }
        }

        .modal-header-new {
            padding: 22px 30px;
            background: linear-gradient(to right, #3b82f6 0%, #2563eb 60%, #1d4ed8 100%);
            color: white;
            display: flex;
            align-items: center;
            justify-content: space-between;
        }

        .modal-header-new h3 {
            margin: 0;
            font-size: 1.25rem;
            font-weight: 800;
            letter-spacing: -0.025em;
            display: flex;
            align-items: center;
            gap: 12px;
            position: relative;
        }

        .close-detail,
        .close-eval-modal {
            color: white;
            font-size: 26px;
            font-weight: bold;
            cursor: pointer;
            opacity: 0.85;
            transition: 0.2s;
            width: 36px;
            height: 36px;
            display: flex;
            align-items: center;
            justify-content: center;
            border-radius: 50%;
            position: relative;
        }

        .close-detail:hover,
        .close-eval-modal:hover {
            opacity: 1;
            background: rgba(255, 255, 255, 0.2);
            transform: rotate(90deg);
        }

        .modal-body-new {
            padding: 35px;
            display: grid;
            grid-template-columns: 280px 1fr;
            gap: 35px;
            background: #f8fafc;
        }

        /* Wraps the photo and the "ระดับการประเมิน" strip below it so both
           share exactly the image's own width/edges, whatever that width
           is at the current breakpoint (280px desktop, 180px mobile - see
           the media query below). */
        .detail-media-col {
            width: 280px;
            display: flex;
            flex-direction: column;
            gap: 12px;
        }

        .detail-image-box {
            width: 100%;
            height: 280px;
            border-radius: 22px;
            overflow: hidden;
            background: white;
            border: 1px solid #e2e8f0;
            display: flex;
            align-items: center;
            justify-content: center;
            box-shadow: 0 10px 20px -6px rgba(15, 23, 42, 0.12);
            position: sticky;
            top: 0;
        }

        /* Full-width tier strip under the photo - same color modifiers
           (.eval-badge-best/-good/-mid/-poor) as the table's pill badge,
           just stretched edge-to-edge instead of pill-shaped. */
        .eval-strip {
            width: 100%;
            box-sizing: border-box;
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 8px;
            padding: 10px 14px;
            border-radius: 14px;
            font-weight: 800;
            font-size: 0.85rem;
            text-align: center;
        }

        .detail-image-box img {
            width: 100%;
            height: 100%;
            object-fit: cover;
            transition: transform 0.5s ease;
        }

        .detail-image-box:hover img {
            transform: scale(1.06);
        }

        .detail-image-placeholder {
            width: 100%;
            height: 100%;
            display: flex;
            flex-direction: column;
            align-items: center;
            justify-content: center;
            gap: 10px;
            background: linear-gradient(160deg, #eff6ff 0%, #f8fafc 100%);
            color: #93a5c4;
        }

        .detail-image-placeholder i {
            font-size: 2.4rem;
            color: #bfdbfe;
        }

        .detail-image-placeholder span {
            font-size: 0.8rem;
            font-weight: 700;
            color: #94a3b8;
        }

        .detail-info {
            display: flex;
            flex-direction: column;
            gap: 16px;
        }

        .detail-hero {
            background: white;
            padding: 18px 20px;
            border-radius: 18px;
            border: 1px solid #e2e8f0;
            box-shadow: 0 4px 6px -1px rgba(0, 0, 0, 0.05);
            display: flex;
            flex-direction: column;
            gap: 8px;
        }

        .info-card-new {
            background: white;
            padding: 20px;
            border-radius: 18px;
            border: 1px solid #e2e8f0;
            box-shadow: 0 4px 6px -1px rgba(0, 0, 0, 0.05);
            display: flex;
            flex-direction: column;
            gap: 18px;
        }

        .info-card-grid {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 18px;
        }

        .info-row {
            display: flex;
            flex-direction: column;
            gap: 6px;
        }

        .info-label {
            font-size: 0.7rem;
            font-weight: 800;
            color: #94a3b8;
            text-transform: uppercase;
            letter-spacing: 0.075em;
            display: flex;
            align-items: center;
            gap: 8px;
        }

        .info-label i {
            color: #3b82f6;
            width: 16px;
            text-align: center;
        }

        .info-value {
            font-size: 1.05rem;
            font-weight: 700;
            color: #1e293b;
            line-height: 1.5;
        }

        /* Small muted line under .info-value, currently used to show the
           province beneath the agency name - same "extra detail below the
           main value" idea as .agency-tag-province in the table. */
        .info-subvalue {
            align-items: center;
            gap: 5px;
            font-size: 0.78rem;
            font-weight: 600;
            color: #94a3b8;
            margin-top: -4px;
        }

        .info-subvalue i {
            font-size: 0.72rem;
        }

        #detailName {
            color: #1d4ed8;
            font-size: 1.3rem;
            font-weight: 800;
        }

        .detail-type-chip {
            align-self: flex-start;
            display: inline-flex;
            align-items: center;
            gap: 8px;
            margin-top: 2px;
            padding: 6px 14px;
            border-radius: 999px;
            background: #eff6ff;
            color: #2563eb;
            font-size: 0.8rem;
            font-weight: 700;
        }

        .sodium-flow-card {
            background: white;
            padding: 20px;
            border-radius: 18px;
            border: 1px solid #e2e8f0;
            box-shadow: 0 4px 6px -1px rgba(0, 0, 0, 0.05);
            display: flex;
            flex-direction: column;
            gap: 14px;
        }


        .sodium-flow {
            display: grid;
            grid-template-columns: 1fr auto 1fr;
            align-items: center;
            gap: 12px;
        }

        .sodium-flow-item {
            display: flex;
            flex-direction: column;
            align-items: center;
            padding: 16px 10px;
            border-radius: 16px;
            border: 1px solid transparent;
            transition: transform 0.2s ease;
        }

        .sodium-flow-before {
            background: #fffbeb;
            border-color: #fde68a;
            color: #92400e;
        }

        .sodium-flow-after {
            background: #f0fff4;
            border-color: #c6f6d5;
            color: #2f855a;
        }

        .sodium-flow-tag {
            font-size: 0.72rem;
            font-weight: 700;
            opacity: 0.75;
            margin-bottom: 4px;
        }

        .sodium-flow-value {
            font-size: 1.5rem;
            font-weight: 900;
            line-height: 1.1;
        }

        .sodium-flow-unit {
            font-size: 0.72rem;
            font-weight: 700;
            opacity: 0.7;
            margin-top: 2px;
        }

        .sodium-flow-arrow {
            display: flex;
            flex-direction: column;
            align-items: center;
            gap: 6px;
            color: #cbd5e1;
            font-size: 1.1rem;
        }

        @media (max-width: 768px) {
            .detail-modal {
                padding-top: 30px;
            }
            .modal-body-new {
                grid-template-columns: 1fr;
                padding: 22px;
                gap: 18px;
            }
            .detail-media-col {
                width: 180px;
                margin: 0 auto;
            }
            .detail-image-box {
                height: 180px;
            }
            .modal-dialog-new {
                margin: 0 auto;
                width: 95%;
            }
            .sodium-flow {
                grid-template-columns: 1fr;
                justify-items: center;
            }
            .sodium-flow-arrow {
                transform: rotate(90deg);
                margin: -4px 0;
            }
            .sodium-flow-arrow .reduced-badge,
            .sodium-flow-arrow .reduced-badge-pending.is-label {
                transform: rotate(-90deg);
            }
            .info-card-grid {
                grid-template-columns: 1fr;
            }
        }


        .sort-link {
            text-decoration: none !important;
            color: #1a202c !important;
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 10px;
            /* Balanced gap between text and icon */
            width: 100%;
            height: 100%;
            padding: 8px 12px;
            border-radius: 8px;
            transition: all 0.15s ease-in-out;
            font-weight: 700;
            border: 1px solid transparent;
            user-select: none;
        }

        .sort-link:hover {
            background-color: rgba(79, 70, 229, 0.06);
            /* Very light indigo */
            border-color: rgba(79, 70, 229, 0.12);
            text-decoration: none !important;
        }

        .sort-link:active {
            transform: scale(0.96);
            /* Pressed effect */
            background-color: rgba(79, 70, 229, 0.12);
        }

        .sort-icon-container {
            display: inline-flex;
            align-items: center;
            justify-content: center;
            width: 32px;
            height: 32px;
            border-radius: 50%;
            /* Clean circular chip */
            background-color: #f1f5f9;
            color: #94a3b8;
            transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
            position: relative;
            overflow: hidden;
        }

        .sort-link:hover .sort-icon-container {
            background-color: #e0e7ff;
            color: #4f46e5;
            transform: translateY(-2px);
        }

        .sort-icon-arrow {
            font-size: 0.9rem;
            position: absolute;
            transition: all 0.3s ease;
        }

        .sort-icon-arrow.fa-arrow-down {
            transform: translateX(5px);
            /* Move to the right side */
        }

        .sort-icon-arrow.fa-arrow-up {
            transform: translateX(-5px);
            /* Move to the left side */
        }

        .sort-active-col .sort-icon-container {
            background: linear-gradient(135deg, #818cf8 0%, #4f46e5 100%);
            color: white;
            box-shadow: 0 4px 12px rgba(79, 70, 229, 0.35);
        }

        .sort-icon-arrow.active {
            opacity: 1;
            transform: translateX(0) scale(1.1);
            color: white;
            position: relative;
            /* Reset positioning when active if needed, or keep it side-by-side */
        }

        /* Adjustment for side-by-side active/inactive look */
        .sort-active-col .fa-arrow-up.active {
            transform: translateX(-5px) scale(1.1);
        }

        .sort-active-col .fa-arrow-down.active {
            transform: translateX(5px) scale(1.1);
        }

        .sort-icon-arrow:not(.active) {
            opacity: 0.5;
        }

        .sort-active-col {
            background: linear-gradient(135deg, #eef2ff, #e0e7ff) !important;
            border-bottom: 3px solid #4f46e5 !important;
        }

        .eval-badge {
            display: inline-flex;
            align-items: center;
            gap: 8px;
            padding: 6px 12px;
            border-radius: 20px;
            font-weight: 800;
            font-size: 0.8rem;
            white-space: nowrap;
        }

        /* The badge's icon as a small solid-filled, drop-shadowed circle
           with a white glyph inside - background picks up whichever tier
           color the badge modifier (.eval-badge-best/-good/-mid/-poor/
           -pending) already sets via `color`, so one rule covers every
           tier without repeating each hex value. */
        .eval-badge-icon {
            display: inline-flex;
            align-items: center;
            justify-content: center;
            width: 18px;
            height: 20px;
            min-width: 18px;
            padding-bottom: 2px;
            /* Shield outline, scaled from the same badge-shape path used on
               the site's header logo mockup - a solid-filled currentColor
               shield instead of a plain circle, echoing the client's own
               "achievement badge" reference. box-shadow can't follow a
               clip-path's silhouette, so the shadow moved to filter:
               drop-shadow below, which hugs the shield's actual outline. */
            clip-path: path('M9 0.51C13.38 0.51 16.15 2.05 17.54 3.08C17.54 11.79 16.15 16.92 9 19.49C1.85 16.92 0.46 11.79 0.46 3.08C1.85 2.05 4.62 0.51 9 0.51Z');
            background: currentColor;
            filter: drop-shadow(0 2px 3px color-mix(in srgb, currentColor 55%, transparent));
            flex-shrink: 0;
        }

        /* The "not yet measured" pending state isn't a rating, so it keeps
           the plain circle instead of the shield - a badge shape would
           read as an achievement that was never actually earned. */
        .eval-badge-pending .eval-badge-icon {
            clip-path: none;
            border-radius: 50%;
            height: 18px;
            padding-bottom: 0;
        }

        .eval-badge-icon i {
            color: #fff;
            font-size: 0.58rem;
        }

        /* The shield's lighter inset circle + star, matching the reference
           badge - a light tint of the same tier color behind a solid-color
           star, both derived from currentColor so every tier (and any
           future one) gets the right shade with no per-tier repetition. */
        .eval-badge-icon-inner {
            display: inline-flex;
            align-items: center;
            justify-content: center;
            width: 11px;
            height: 11px;
            border-radius: 50%;
            background: color-mix(in srgb, currentColor 20%, white);
        }

        .eval-badge-icon-inner i {
            color: currentColor;
            font-size: 0.46rem;
        }

        .eval-badge-best {
            background: #ecfdf5;
            color: #047857;
            border: 1px solid #a7f3d0;
        }

        .eval-badge-good {
            background: #f7fee7;
            color: #4d7c0f;
            border: 1px solid #d9f99d;
        }

        .eval-badge-mid {
            background: #fffbeb;
            color: #92400e;
            border: 1px solid #fde68a;
        }

        .eval-badge-poor {
            background: #fff5f5;
            color: #c53030;
            border: 1px solid #feb2b2;
        }

        .eval-badge-pending {
            background: #f8fafc;
            color: #64748b;
            border: 1px solid #e2e8f0;
        }

        /* Info trigger next to the "ระดับการประเมิน" table header, and the
           explainer modal it opens. Reuses the site's existing
           .detail-modal / .modal-dialog-new / .modal-header-new chrome (see
           "Detail Modal Styles" above) so a second modal on this page
           doesn't introduce a second visual language. */
        .eval-info-btn {
            display: inline-flex;
            align-items: center;
            justify-content: center;
            width: 20px;
            height: 20px;
            border-radius: 50%;
            border: none;
            background: #eef2ff;
            color: #4f46e5;
            font-size: 0.72rem;
            cursor: pointer;
            margin-left: 6px;
            vertical-align: middle;
            transition: all 0.15s ease;
            flex-shrink: 0;
        }

        .eval-info-btn:hover {
            background: #4f46e5;
            color: #fff;
            transform: scale(1.08);
        }

        .eval-modal-body {
            padding: 28px 30px 30px;
            background: #f8fafc;
        }

        .eval-modal-intro {
            margin: 0 0 18px;
            color: #475569;
            font-size: 0.92rem;
            line-height: 1.7;
        }

        .eval-modal-tier {
            display: flex;
            align-items: center;
            justify-content: space-between;
            gap: 12px;
            padding: 14px 18px;
            border-radius: 14px;
            margin-bottom: 10px;
            font-weight: 700;
        }

        .eval-modal-tier-name {
            display: flex;
            align-items: center;
            gap: 10px;
            font-size: 0.92rem;
        }

        .eval-dot {
            width: 10px;
            height: 10px;
            border-radius: 50%;
            flex-shrink: 0;
        }

        .eval-modal-tier-range {
            font-size: 0.92rem;
            white-space: nowrap;
        }

        .eval-modal-tier-best {
            background: #f0fff4;
            color: #2f855a;
        }

        .eval-modal-tier-good {
            background: #f7fee7;
            color: #4d7c0f;
        }

        .eval-modal-tier-mid {
            background: #fffbeb;
            color: #92400e;
        }

        .eval-modal-tier-poor {
            background: #fff5f5;
            color: #c53030;
            margin-bottom: 0;
        }

        .eval-modal-note {
            margin: 18px 2px 0;
            color: #94a3b8;
            font-size: 0.78rem;
            line-height: 1.6;
        }

        /* เครื่องคำนวณโซเดียมในมื้ออาหาร - floating widget fed by the "+"
           button in each table row. State lives in memory only (no
           localStorage) - "ล้างมื้ออาหาร" is the explicit reset control, so
           nothing needs to survive a page reload. */
        /* Collapsed by default - a small round button bottom-right - and
           only opens into the full panel on click, so it never sits over
           the page uninvited. */
        .meal-calc-fab {
            position: fixed;
            right: 24px;
            bottom: 24px;
            width: 56px;
            height: 56px;
            border-radius: 50%;
            border: none;
            background: linear-gradient(135deg, #f97316, #fb923c);
            color: #fff;
            font-size: 1.3rem;
            box-shadow: 0 12px 28px rgba(234, 88, 12, 0.35);
            cursor: pointer;
            display: flex;
            align-items: center;
            justify-content: center;
            z-index: 900;
            transition: transform 0.2s ease, box-shadow 0.2s ease;
        }

        /* Below 768px the shared layout swaps in a fixed bottom tab bar
           (layouts/layout.blade.php's .mobile-nav, with .main-content's
           matching 80px bottom padding) - lift the FAB above it so it
           never sits partly behind/under that bar. */
        @media (max-width: 768px) {
            .meal-calc-fab {
                bottom: 96px;
            }
        }

        .meal-calc-fab:hover {
            transform: translateY(-2px) scale(1.05);
            box-shadow: 0 16px 34px rgba(234, 88, 12, 0.4);
        }

        .meal-calc-fab-badge {
            position: absolute;
            top: -4px;
            right: -4px;
            min-width: 20px;
            height: 20px;
            padding: 0 5px;
            border-radius: 999px;
            background: #dc2626;
            color: #fff;
            font-size: 0.68rem;
            font-weight: 800;
            display: flex;
            align-items: center;
            justify-content: center;
            border: 2px solid #fff;
        }

        /* Floating overlay card: anchored to the same bottom-right corner
           as the FAB it opens from (the FAB hides while it's open), and
           grows upward. A wide, short bar rather than a tall column - the
           card's HEIGHT stays roughly constant regardless of how many
           items are in the meal. Its WIDTH grows to the left with each
           item added (fit-content, not a fixed size) so a few items are
           never squeezed into a tiny scrolling lane; only once the card
           reaches the edge of the viewport does the item list itself
           start scrolling sideways instead of the card growing further -
           see .meal-calc-items-scroll's own overflow-x, which is what
           actually kicks in at that point. */
        .meal-calc-widget {
            display: flex;
            position: fixed;
            bottom: 96px;
            right: 24px;
            max-height: calc(100vh - 140px);
            width: fit-content;
            min-width: 480px;
            max-width: calc(100vw - 48px);
            background: #fff;
            border-radius: 20px;
            box-shadow: 0 20px 45px -8px rgba(15, 23, 42, 0.22);
            z-index: 950;
            flex-direction: column;
            overflow: hidden;
            opacity: 0;
            visibility: hidden;
            pointer-events: none;
            transform: translateY(12px) scale(0.97);
            transition: opacity 0.2s ease, transform 0.2s ease, visibility 0.2s, width 0.2s ease;
        }

        .meal-calc-widget.is-open {
            opacity: 1;
            visibility: visible;
            pointer-events: auto;
            transform: translateY(0) scale(1);
        }

        .meal-calc-header {
            background: linear-gradient(135deg, #f97316, #fb923c);
            color: #fff;
            padding: 14px 16px;
            display: flex;
            align-items: flex-start;
            justify-content: space-between;
            gap: 10px;
        }

        .meal-calc-header-main {
            min-width: 0;
        }

        .meal-calc-header-title {
            font-weight: 800;
            font-size: 0.92rem;
            display: flex;
            align-items: center;
            gap: 8px;
            line-height: 1.3;
        }

        .meal-calc-unit-note {
            display: block;
            margin: 4px 0 0 24px;
            color: rgba(255, 255, 255, 0.85);
            font-size: 0.66rem;
            font-weight: 600;
        }

        .meal-calc-header-actions {
            display: flex;
            align-items: center;
            gap: 8px;
            flex-shrink: 0;
        }

        .meal-calc-count-badge {
            background: rgba(255, 255, 255, 0.92);
            color: #c2410c;
            font-weight: 800;
            font-size: 0.76rem;
            padding: 4px 12px;
            border-radius: 999px;
            white-space: nowrap;
        }

        .meal-calc-collapse-btn {
            width: 26px;
            height: 26px;
            min-width: 26px;
            border-radius: 50%;
            border: none;
            background: rgba(255, 255, 255, 0.2);
            color: #fff;
            display: flex;
            align-items: center;
            justify-content: center;
            cursor: pointer;
            font-size: 0.75rem;
        }

        .meal-calc-collapse-btn:hover {
            background: rgba(255, 255, 255, 0.35);
        }

        /* Body is one horizontal row - the item list scrolls sideways in
           its own lane instead of the whole card growing downward, so
           adding a fourth or fifth item never changes the card's height. */
        .meal-calc-body {
            padding: 14px 18px;
            overflow: hidden;
            flex: 1 1 auto;
            min-height: 90px;
        }

        .meal-calc-row {
            display: flex;
            align-items: stretch;
            gap: 14px;
            height: 100%;
        }

        .meal-calc-divider {
            width: 1px;
            flex: 0 0 1px;
            background: #eef2f7;
            margin: 2px 0;
        }

        .meal-calc-items-scroll {
            flex: 1 1 auto;
            min-width: 160px;
            overflow-x: auto;
            overflow-y: hidden;
            display: flex;
            align-items: center;
        }

        #mealCalcItems {
            display: flex;
            align-items: center;
        }

        .meal-calc-empty {
            display: flex;
            align-items: center;
            text-align: left;
            color: #94a3b8;
            gap: 10px;
            white-space: nowrap;
        }

        .meal-calc-empty-icon {
            width: 38px;
            height: 38px;
            min-width: 38px;
            border-radius: 50%;
            background: #f1f5f9;
            color: #cbd5e1;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 1rem;
        }

        .meal-calc-empty p {
            margin: 0;
            font-size: 0.78rem;
            line-height: 1.5;
            white-space: normal;
            max-width: 200px;
        }

        .meal-calc-item {
            display: flex;
            flex-direction: column;
            justify-content: center;
            flex: 0 0 auto;
            width: 172px;
            align-self: center;
            gap: 2px;
            padding: 8px 12px;
            border: 1px solid #e2e8f0;
            border-radius: 14px;
        }

        .meal-calc-item + .meal-calc-item {
            margin-left: 10px;
        }

        .meal-calc-item-main {
            flex: 1 1 auto;
            min-width: 0;
        }

        .meal-calc-item-name {
            font-weight: 700;
            color: #1e293b;
            font-size: 0.86rem;
            display: flex;
            align-items: center;
            justify-content: space-between;
            gap: 8px;
        }

        .meal-calc-item-name span:first-child {
            overflow: hidden;
            text-overflow: ellipsis;
            white-space: nowrap;
            min-width: 0;
        }

        .meal-calc-item-remove {
            border: none;
            background: none;
            color: #cbd5e1;
            cursor: pointer;
            font-size: 0.9rem;
            line-height: 1;
            padding: 2px;
        }

        .meal-calc-item-remove:hover {
            color: #ef4444;
        }

        .meal-calc-item-unit {
            font-size: 0.72rem;
            color: #94a3b8;
            margin-top: 2px;
        }

        .meal-calc-item-row {
            display: flex;
            align-items: center;
            justify-content: space-between;
            margin-top: 8px;
            gap: 8px;
        }

        .meal-calc-stepper {
            display: flex;
            align-items: center;
            gap: 8px;
            background: #f8fafc;
            border-radius: 999px;
            padding: 3px;
        }

        .meal-calc-stepper button {
            width: 24px;
            height: 24px;
            border-radius: 50%;
            border: none;
            background: #fff;
            color: #475569;
            font-weight: 700;
            box-shadow: 0 1px 3px rgba(15, 23, 42, 0.12);
            cursor: pointer;
            display: flex;
            align-items: center;
            justify-content: center;
        }

        .meal-calc-stepper span {
            min-width: 18px;
            text-align: center;
            font-weight: 700;
            color: #1e293b;
            font-size: 0.85rem;
        }

        .meal-calc-item-mg {
            font-weight: 800;
            color: #16a34a;
            font-size: 0.9rem;
            white-space: nowrap;
        }

        /* Old total -> new total, as one inline "before/after" stat instead
           of two square boxes, with the savings note tucked underneath -
           reads left-to-right like the rest of the row. */
        .meal-calc-summary-group {
            flex: 0 0 auto;
            display: flex;
            flex-direction: column;
            justify-content: center;
            gap: 4px;
            min-width: 168px;
        }

        .meal-calc-summary {
            display: flex;
            align-items: center;
            gap: 10px;
        }

        .meal-calc-sum-old,
        .meal-calc-sum-new {
            text-align: left;
        }

        .meal-calc-sum-arrow {
            color: #cbd5e1;
            font-size: 0.85rem;
        }

        .meal-calc-sum-label {
            display: block;
            font-size: 0.68rem;
            font-weight: 700;
            color: #94a3b8;
            margin-bottom: 2px;
        }

        .meal-calc-sum-old .meal-calc-sum-value {
            display: block;
            font-size: 0.92rem;
            font-weight: 800;
            color: #94a3b8;
            text-decoration: line-through;
            white-space: nowrap;
        }

        .meal-calc-sum-new .meal-calc-sum-value {
            display: block;
            font-size: 1.1rem;
            font-weight: 800;
            color: #15803d;
            white-space: nowrap;
        }

        .meal-calc-savings-tip {
            background: #eff6ff;
            color: #1d4ed8;
            border-radius: 999px;
            padding: 4px 10px;
            font-size: 0.7rem;
            font-weight: 700;
            display: inline-flex;
            align-items: center;
            gap: 6px;
            white-space: nowrap;
            width: fit-content;
        }

        /* WHO comparison as a compact ring gauge (a CSS conic-gradient
           masked into a donut) instead of a long horizontal bar - reads at
           a glance without taking up lane width. */
        .meal-calc-who {
            flex: 0 0 auto;
            display: flex;
            align-items: center;
            gap: 10px;
        }

        .meal-calc-who-ring {
            position: relative;
            width: 44px;
            height: 44px;
            min-width: 44px;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            --pct: 0%;
        }

        .meal-calc-who-ring::before {
            content: '';
            position: absolute;
            inset: 0;
            border-radius: 50%;
            background: conic-gradient(#22c55e var(--pct), #eef2f7 0);
            -webkit-mask: radial-gradient(farthest-side, transparent calc(100% - 6px), #000 calc(100% - 6px));
            mask: radial-gradient(farthest-side, transparent calc(100% - 6px), #000 calc(100% - 6px));
            transition: background 0.3s ease;
        }

        .meal-calc-who-ring.is-over::before {
            background: conic-gradient(#ef4444 var(--pct), #eef2f7 0);
        }

        .meal-calc-who-ring span {
            position: relative;
            font-size: 0.7rem;
            font-weight: 800;
            color: #1e293b;
        }

        .meal-calc-who-caption {
            display: flex;
            flex-direction: column;
            font-size: 0.68rem;
            color: #94a3b8;
            font-weight: 700;
            line-height: 1.4;
            white-space: nowrap;
        }

        .meal-calc-who-caption span:first-child {
            color: #64748b;
        }

        .meal-calc-status {
            flex: 0 0 auto;
            align-self: center;
            max-width: 160px;
            border-radius: 999px;
            padding: 8px 12px;
            font-size: 0.74rem;
            font-weight: 700;
            display: flex;
            align-items: center;
            gap: 7px;
            background: #f8fafc;
            color: #64748b;
            line-height: 1.3;
        }

        .meal-calc-status.is-safe {
            background: #f0fdf4;
            color: #15803d;
        }

        .meal-calc-status.is-over {
            background: #fef2f2;
            color: #b91c1c;
        }

        .meal-calc-clear-btn {
            flex: 0 0 auto;
            align-self: center;
            width: 38px;
            height: 38px;
            border: none;
            background: #f1f5f9;
            color: #94a3b8;
            font-size: 0.9rem;
            border-radius: 50%;
            cursor: pointer;
            display: flex;
            align-items: center;
            justify-content: center;
        }

        .meal-calc-clear-btn:hover {
            background: #fef2f2;
            color: #ef4444;
        }

        .meal-calc-add-btn {
            width: 32px;
            height: 32px;
            border-radius: 10px;
            border: none;
            background: #fff7ed;
            color: #ea580c;
            font-size: 0.9rem;
            cursor: pointer;
            display: flex;
            align-items: center;
            justify-content: center;
            transition: background 0.15s ease, color 0.15s ease;
        }

        .meal-calc-add-btn:hover {
            background: #ea580c;
            color: #fff;
        }

        .meal-calc-add-btn.is-added {
            background: #dcfce7;
            color: #16a34a;
        }

        @media (max-width: 640px) {
            /* Compact bottom sheet on a phone screen, sliding up from the
               bottom instead of fading in place. */
            .meal-calc-widget {
                top: auto;
                left: 16px;
                right: 16px;
                bottom: 96px;
                width: auto;
                /* The desktop rule's `min-width: 480px` is never reset
                   here, which forces this sheet wider than a ~375-430px
                   phone viewport (pushing it past `right: 16px`) - reset
                   it so `width: auto` above actually governs the size. */
                min-width: 0;
                max-height: calc(100vh - 112px);
                border-radius: 20px;
                transform: translateY(24px);
            }

            .meal-calc-widget.is-open {
                transform: translateY(0);
            }

            /* No spare width on a phone to lay the row out sideways -
               back to a stacked sheet, each section full width. */
            .meal-calc-row {
                flex-direction: column;
                align-items: stretch;
                gap: 12px;
                overflow-y: auto;
            }

            .meal-calc-divider {
                width: auto;
                height: 1px;
                flex: 0 0 1px;
                margin: 0;
            }

            .meal-calc-items-scroll {
                min-width: 0;
            }

            .meal-calc-status {
                max-width: none;
                align-self: stretch;
            }

            .meal-calc-clear-btn {
                align-self: flex-end;
            }
        }
    </style>
@endsection

@section('content')
    <div class="container-fluid">
        <!-- Restructured Filters -->
        <form action="{{ route('reduced-sodium-menu') }}" method="GET" class="awr-filter-bar">
            @if($hideLayout ?? false)
                <input type="hidden" name="iframe" value="1">
            @endif
            @if($isLocked ?? false)
                <input type="hidden" name="org_lock" value="1">
            @endif

            <div class="awr-filter-heading">
                <i class="fa-solid fa-filter"></i>
                <span>ตัวกรอง</span>
            </div>

            <div class="awr-filter-item">
                <i class="far fa-calendar-alt awr-filter-icon"></i>
                <span class="awr-filter-field-label">ปีงบประมาณ</span>
                <select name="fiscal_year" class="awr-filter-select" onchange="this.form.submit()">
                    <option value="">ทั้งหมด</option>
                    @foreach($years as $y)
                        <option value="{{ $y }}" {{ request('fiscal_year') == $y ? 'selected' : '' }}>{{ $y }}</option>
                    @endforeach
                </select>
            </div>

            <div class="awr-filter-item">
                <i class="fas fa-map-marked-alt awr-filter-icon"></i>
                <span class="awr-filter-field-label">จังหวัด</span>
                <select name="province" class="awr-filter-select" onchange="this.form.submit()" {{ ($lockedProvinceName ?? $lockedOrgName ?? false) ? 'disabled' : '' }}>
                    @if(!($lockedProvinceName ?? $lockedOrgName ?? false))
                        <option value="ทั้งหมด">ทั้งหมด</option>
                    @endif
                    @foreach($provinces as $p)
                        <option value="{{ $p }}" {{ (request('province') == $p || ($lockedProvinceName ?? $lockedOrgName ?? false)) ? 'selected' : '' }}>{{ $p }}</option>
                    @endforeach
                </select>
                @if($lockedProvinceName ?? $lockedOrgName ?? false)
                    <input type="hidden" name="province" value="{{ $provinces[0] ?? '' }}">
                @endif
            </div>

            <div class="awr-filter-item">
                <i class="fas fa-map-marker-alt awr-filter-icon"></i>
                <span class="awr-filter-field-label">อำเภอ</span>
                <select name="district" class="awr-filter-select" onchange="this.form.submit()" {{ ($lockedOrgName ?? false) ? 'disabled' : '' }}>
                    @if(!($lockedOrgName ?? false))
                        <option value="ทั้งหมด">ทั้งหมด</option>
                    @endif
                    @foreach($districts as $d)
                        <option value="{{ $d }}" {{ (request('district') == $d || (($lockedOrgName ?? false) && count($districts) == 1)) ? 'selected' : '' }}>{{ $d }}</option>
                    @endforeach
                </select>
                @if(($lockedOrgName ?? false) && count($districts) == 1)
                    <input type="hidden" name="district" value="{{ $districts[0] ?? '' }}">
                @endif
            </div>

            <div class="awr-filter-item">
                <i class="fas fa-store awr-filter-icon"></i>
                <span class="awr-filter-field-label">หน่วยงาน/สถานที่จำหน่าย</span>
                <select name="org_name" class="awr-filter-select" onchange="this.form.submit()" {{ ($isLocked && ($lockedOrgName ?? false)) ? 'disabled' : '' }}>
                    @if(!($isLocked && ($lockedOrgName ?? false)))
                        <option value="ทั้งหมด">ทั้งหมด</option>
                    @endif
                    @foreach($orgNames as $o)
                        @php
                            $isSelected = request('org_name') == $o;
                            if ($isLocked && ($lockedOrgName ?? false)) {
                                // If locked, $orgNames will contain the specific org name from DB
                                // but we are overriding it in controller to be categories for non-locked
                                // Wait, I need to be careful with the isLocked case.
                                $isSelected = $o == $lockedOrgName;
                            }
                        @endphp
                        <option value="{{ $o }}" {{ $isSelected ? 'selected' : '' }}>{{ $o }}</option>
                    @endforeach
                </select>
                @if($isLocked && ($lockedOrgName ?? false))
                    <input type="hidden" name="org_name" value="{{ $lockedOrgName }}">
                @endif
            </div>

            <a href="{{ route('reduced-sodium-menu', request()->only(['iframe', 'org_lock'])) }}"
                class="awr-filter-clear text-decoration-none">
                <i class="fas fa-sync-alt"></i>
                <span>ล้างตัวกรอง</span>
            </a>
        </form>

        {{-- Headline summary strip: derives 3 at-a-glance stats from the
             same collections the charts below already receive from
             MainController::reducedSodiumMenu() - no new queries. --}}
        @php
            $kpiTotalMenus = $menus->total();
            $kpiTopCategory = collect($pieChartData)->sortDesc()->keys()->first();
            $kpiProvinceCoverage = collect($mapData)->count();
            $kpiProvinceTotal = collect($provinces)->count();
        @endphp

        <div class="awr-kpi-strip">
            <div class="awr-kpi-item" style="--kpi-accent:#6366f1;">
                <div class="awr-kpi-icon"><i class="fas fa-utensils"></i></div>
                <div class="awr-kpi-text">
                    <div class="awr-kpi-value">{{ number_format($kpiTotalMenus) }}</div>
                    <div class="awr-kpi-label">เมนูลดโซเดียมทั้งหมด</div>
                </div>
            </div>
            <div class="awr-kpi-item" style="--kpi-accent:#f59e0b;">
                <div class="awr-kpi-icon"><i class="fas fa-store"></i></div>
                <div class="awr-kpi-text">
                    <div class="awr-kpi-value">{{ $kpiTopCategory ?: '-' }}</div>
                    <div class="awr-kpi-label">สถานที่จำหน่ายยอดนิยม</div>
                </div>
            </div>
            <div class="awr-kpi-item" style="--kpi-accent:#06b6d4;">
                <div class="awr-kpi-icon"><i class="fas fa-map-location-dot"></i></div>
                <div class="awr-kpi-text">
                    <div class="awr-kpi-value">{{ $kpiProvinceCoverage }}/{{ $kpiProvinceTotal }}</div>
                    <div class="awr-kpi-label">ความครอบคลุมพื้นที่ (จังหวัด)</div>
                </div>
            </div>
            <div class="awr-kpi-item" style="--kpi-accent:#2f855a;">
                <div class="awr-kpi-icon"><i class="fas fa-award"></i></div>
                <div class="awr-kpi-text"
                    title="{{ $sodiumEvalSummary['best_count'] }} จาก {{ $sodiumEvalSummary['evaluated_count'] }} เมนู ที่มีผลตรวจแล้ว">
                    <div class="awr-kpi-value">
                        {{ $sodiumEvalSummary['evaluated_count'] > 0 ? number_format($sodiumEvalSummary['best_percent'], 1) . '%' : '-' }}
                    </div>
                    <div class="awr-kpi-label">ผ่านเกณฑ์ที่ดีมาก</div>
                </div>
            </div>
            <div class="awr-kpi-item" style="--kpi-accent:#c53030;">
                <div class="awr-kpi-icon"><i class="fas fa-triangle-exclamation"></i>
                </div>
                <div class="awr-kpi-text"
                    title="{{ $sodiumEvalSummary['high_risk_count'] > 0 ? 'ต้องปรับปรุงสูตรเร่งด่วน' : 'ไม่มีเมนูความเสี่ยงสูงในผลตรวจล่าสุด' }}">
                    <div class="awr-kpi-value">{{ $sodiumEvalSummary['high_risk_count'] }}</div>
                    <div class="awr-kpi-label">เมนูความเสี่ยงสูง (&gt;1,000mg)</div>
                </div>
            </div>
        </div>

        <!-- Charts Section -->
        <div class="chart-row">
            <!-- Map -->
            <div class="chart-card accent-pink">
                <div class="chart-card-head">
                    <div class="chart-card-icon"><i class="fas fa-map-location-dot"></i></div>
                    <div>
                        <div class="chart-card-title">จำนวนเมนูลดโซเดียม แยกตามจังหวัด</div>
                        <div class="chart-card-subtitle">ปี
                            {{ request('fiscal_year') ?: (isset($years[0]) ? $years[0] : (date('Y') + 543)) }}</div>
                    </div>
                </div>
                <div id="map-container" style="height: 340px;"></div>
            </div>

            <!-- Pie Chart -->
            <div class="chart-card accent-purple">
                <div class="chart-card-head">
                    <div class="chart-card-icon"><i class="fas fa-chart-pie"></i></div>
                    <div>
                        <div class="chart-card-title">ร้อยละเมนูอาหารที่ปรับสูตรลดโซเดียม</div>
                        <div class="chart-card-subtitle">แยกตามสถานที่จำหน่าย</div>
                    </div>
                </div>
                <div id="sourcePieChart" style="height: 320px; width: 100%;"></div>
            </div>

            <!-- Bar Chart -->
            <div class="chart-card accent-amber">
                <div class="chart-card-head">
                    <div class="chart-card-icon"><i class="fas fa-chart-column"></i></div>
                    <div>
                        <div class="chart-card-title">ภาพรวมค่าเฉลี่ยโซเดียม ก่อน - หลัง ปรับสูตร</div>
                        <div class="chart-card-subtitle">เปรียบเทียบค่าเฉลี่ยจากทุกเมนู (มก./100 มล.)</div>
                    </div>
                </div>
                <div id="sodiumBarChart" style="height: 320px; width: 100%;"></div>
            </div>


        </div>

        <!-- Table Section -->
        <div class="menu-table-container">
            <table class="menu-table">
                <thead>
                    <tr>
                        <th width="9%">รูปภาพ</th>
                        <th width="14%">ชื่ออาหาร</th>
                        <th width="9%">สถานที่จำหน่าย</th>
                        <th width="13%" class="{{ request('sort') == 'sodium_before' ? 'sort-active-col' : '' }}"
                            style="padding: 0;">
                            @php
                                $isSortedBefore = request('sort') == 'sodium_before';
                                $dirBefore = request('direction');
                                $urlBefore = request()->fullUrlWithQuery(['sort' => 'sodium_before', 'direction' => 'asc']);
                                if ($isSortedBefore && $dirBefore == 'asc') {
                                    $urlBefore = request()->fullUrlWithQuery(['sort' => 'sodium_before', 'direction' => 'desc']);
                                } elseif ($isSortedBefore && $dirBefore == 'desc') {
                                    $urlBefore = request()->fullUrlWithQuery(['sort' => null, 'direction' => null]);
                                }
                            @endphp
                            <a href="{{ $urlBefore }}" class="sort-link">
                                <span>โซเดียมก่อน (mg)</span>
                                <div class="sort-icon-container">
                                    <i
                                        class="fas fa-arrow-down sort-icon-arrow {{ request('sort') == 'sodium_before' && request('direction') == 'desc' ? 'active' : '' }}"></i>
                                    <i
                                        class="fas fa-arrow-up sort-icon-arrow {{ request('sort') == 'sodium_before' && request('direction') == 'asc' ? 'active' : '' }}"></i>
                                </div>
                            </a>
                        </th>
                        <th width="13%" class="{{ request('sort') == 'sodium_after' ? 'sort-active-col' : '' }}"
                            style="padding: 0;">
                            @php
                                $isSortedAfter = request('sort') == 'sodium_after';
                                $dirAfter = request('direction');
                                $urlAfter = request()->fullUrlWithQuery(['sort' => 'sodium_after', 'direction' => 'asc']);
                                if ($isSortedAfter && $dirAfter == 'asc') {
                                    $urlAfter = request()->fullUrlWithQuery(['sort' => 'sodium_after', 'direction' => 'desc']);
                                } elseif ($isSortedAfter && $dirAfter == 'desc') {
                                    $urlAfter = request()->fullUrlWithQuery(['sort' => null, 'direction' => null]);
                                }
                            @endphp
                            <a href="{{ $urlAfter }}" class="sort-link">
                                <span>โซเดียมหลัง (mg)</span>
                                <div class="sort-icon-container">
                                    <i
                                        class="fas fa-arrow-down sort-icon-arrow {{ request('sort') == 'sodium_after' && request('direction') == 'asc' ? 'active' : '' }}"></i>
                                    <i
                                        class="fas fa-arrow-up sort-icon-arrow {{ request('sort') == 'sodium_after' && request('direction') == 'desc' ? 'active' : '' }}"></i>
                                </div>
                            </a>
                        </th>
                        <th width="13%">โซเดียมที่ลดได้</th>
                        <th width="12%">หน่วยงาน / แหล่งผลิต</th>
                        <th width="15%">
                            ระดับการประเมิน
                            <button type="button" id="evalInfoBtn" class="eval-info-btn"
                                title="ดูคำอธิบายเกณฑ์การประเมิน">
                                <i class="fas fa-circle-info"></i>
                            </button>
                        </th>
                        <th width="7%">คำนวณมื้อ</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($menus as $menu)
                        @php
                            // A "หลัง" reading of exactly 0.0 is treated as "ยังไม่ได้วัด"
                            // (not yet re-tested), same rule as the "โซเดียมลดลงเฉลี่ย" KPI
                            // above (MainController::reducedSodiumMenu()) - a blank cell in
                            // either the Excel import or the manual edit form ends up stored
                            // as a literal 0 rather than null, and a real re-tested dish
                            // essentially never comes back at EXACTLY 0.00 mg. Without this
                            // guard, such a row both computed as a false "ลดลง 100%" here and
                            // showed the green "ปรับลดสำเร็จ" badge below - the opposite of
                            // what "ยังไม่มีผลตรวจ" actually means.
                            $hasSodiumReading = $menu->sodium_before > 0 && $menu->sodium_after > 0;
                            $sodiumReductionPercent = $hasSodiumReading
                                ? round((($menu->sodium_before - $menu->sodium_after) / $menu->sodium_before) * 100, 1)
                                : null;
                            // "โซเดียมที่ลดได้": mg amount removed, same sign convention as
                            // $sodiumReductionPercent above (positive = real reduction,
                            // negative = the dish came back saltier) and the same
                            // $hasSodiumReading guard - a not-yet-measured row must never
                            // show a fabricated reduction number.
                            $reducedMg = $hasSodiumReading ? ($menu->sodium_before - $menu->sodium_after) : null;

                            // Evaluation tier ("ระดับการประเมิน"), computed once per row here
                            // so both the table badge below AND the menu-detail modal's
                            // data-menu JSON can reuse the same $evalClass/$evalIcon/
                            // $evalLabel/$evalRange - previously this was computed a second
                            // time inside the table cell itself, and the modal didn't carry
                            // it at all.
                            if ($hasSodiumReading) {
                                $afterVal = $menu->sodium_after;
                                // Every measured tier now shares one glyph - a star,
                                // matching the client's own badge/shield reference - so
                                // the tier is read entirely from the shield's color
                                // (.eval-badge-icon's shield + .eval-badge-icon-inner's
                                // light tinted circle behind it) rather than from a
                                // different icon shape per tier.
                                if ($afterVal < 600) {
                                    $evalClass = 'eval-badge-best';
                                    $evalIcon = 'fa-star';
                                    $evalLabel = 'ดีมาก';
                                    $evalRange = '<600mg';
                                } elseif ($afterVal <= 800) {
                                    $evalClass = 'eval-badge-good';
                                    $evalIcon = 'fa-star';
                                    $evalLabel = 'ดี';
                                    $evalRange = '601-800mg';
                                } elseif ($afterVal <= 1000) {
                                    $evalClass = 'eval-badge-mid';
                                    $evalIcon = 'fa-star';
                                    $evalLabel = 'ปานกลาง';
                                    $evalRange = '801-1000mg';
                                } else {
                                    $evalClass = 'eval-badge-poor';
                                    $evalIcon = 'fa-star';
                                    $evalLabel = 'ควรปรับปรุง';
                                    $evalRange = '>1000mg';
                                }
                            } else {
                                $evalClass = $evalIcon = $evalLabel = $evalRange = null;
                            }

                            $menuData = [
                                'name' => $menu->menu_name,
                                'type' => $menu->kitchen_type ?: '-',
                                'before' => number_format($menu->sodium_before, 1),
                                'after' => number_format($menu->sodium_after, 1),
                                'has_reading' => $hasSodiumReading,
                                'is_bad' => $hasSodiumReading ? $menu->sodium_after > $menu->sodium_before : false,
                                'reduction_percent' => $sodiumReductionPercent,
                                // Same "โซเดียมที่ลดได้" figure as the table's new column -
                                // stored as an already-absolute, already-formatted string
                                // since the modal's JS decides the +/- wording from
                                // is_bad, same as it already does for reduction_percent.
                                'reduced_mg' => $hasSodiumReading ? number_format(abs($reducedMg), 1) : null,
                                'agency' => $menu->agency ?: $menu->org_name,
                                'province' => $menu->province ?: null,
                                'eval_label' => $evalLabel,
                                'eval_class' => $evalClass,
                                'eval_icon' => $evalIcon,
                                'eval_range' => $evalRange,
                                'has_image' => (bool) $menu->product_image,
                                'image' => $menu->product_image ? asset('storage/' . $menu->product_image) : null,
                                // Each menu row has its own real "year" column (an admin-entered
                                // Buddhist-era value - see ReducedSodiumMenu::$fillable and the
                                // "นำเข้า Excel" template), so the detail popup for THIS menu must
                                // show THIS row's own year. Falling back to the page's current
                                // filter/default instead (as before) showed every menu on the page
                                // under the same year regardless of what was actually saved for it.
                                'year' => $menu->year ?: (request('fiscal_year') ?: (isset($years[0]) ? $years[0] : (date('Y') + 543)))
                            ];
                        @endphp
                        <tr class="clickable-row" data-menu="{{ json_encode($menuData) }}">
                            <td>
                                @if($menu->product_image)
                                    <img src="{{ asset('storage/' . $menu->product_image) }}" class="food-img"
                                        alt="{{ $menu->menu_name }}">
                                @else
                                    <img src="{{ asset('images/picture.png') }}" class="food-img" alt="placeholder">
                                @endif
                            </td>
                            <td class="menu-name-cell">{{ $menu->menu_name }}</td>
                            <td>
                                <span class="kitchen-type-tag">{{ $menu->kitchen_type }}</span>
                            </td>
                            <td>
                                <span class="sodium-pill sodium-pill-before">
                                    {{ number_format($menu->sodium_before, 1) }}
                                </span>
                            </td>
                            <td>
                                @if(!$hasSodiumReading)
                                    <span class="sodium-pill sodium-pill-pending" title="ยังไม่มีผลตรวจโซเดียมหลังปรับสูตร">
                                        <i class="fas fa-hourglass-half"></i>
                                        ยังไม่มีผล
                                    </span>
                                @else
                                    <span
                                        class="sodium-pill {{ $menu->sodium_after > $menu->sodium_before ? 'sodium-pill-bad' : 'sodium-pill-good' }}">
                                        {{ number_format($menu->sodium_after, 1) }}
                                    </span>
                                @endif
                            </td>
                            <td>
                                @if(!$hasSodiumReading)
                                    <span class="reduced-badge-pending" title="ยังไม่มีผลตรวจ จึงยังไม่สามารถคำนวณโซเดียมที่ลดได้">&ndash;</span>
                                @else
                                    <span class="reduced-badge {{ $reducedMg >= 0 ? 'is-good' : 'is-bad' }}"
                                        title="{{ $reducedMg >= 0 ? 'ลดลงจากก่อนปรับสูตร' : 'เพิ่มขึ้นจากก่อนปรับสูตร' }}">
                                        <i class="fas {{ $reducedMg >= 0 ? 'fa-arrow-down' : 'fa-arrow-up' }}"></i>
                                        <span class="reduced-badge-text">
                                            <span class="reduced-badge-value">{{ number_format(abs($reducedMg), 1) }} mg</span>
                                            <span class="reduced-badge-percent">{{ $reducedMg >= 0 ? '' : '+' }}{{ number_format(abs($sodiumReductionPercent), 1) }}%</span>
                                        </span>
                                    </span>
                                @endif
                            </td>
                            <td style="text-align: left;">
                                <span
                                    title="{{ $menu->agency ?: $menu->org_name }}{{ $menu->province ? ' (' . $menu->province . ')' : '' }}"
                                    class="agency-tag">
                                    <i class="fas fa-hospital agency-tag-icon"></i>
                                    <span class="agency-tag-text">
                                        <span class="agency-tag-name">{{ $menu->agency ?: $menu->org_name }}</span>
                                        @if($menu->province)
                                            <span class="agency-tag-province">{{ $menu->province }}</span>
                                        @endif
                                    </span>
                                </span>
                            </td>
                            <td>
                                @if(!$hasSodiumReading)
                                    <span class="eval-badge eval-badge-pending" title="ยังไม่มีผลตรวจโซเดียมหลังปรับสูตร">
                                        <span class="eval-badge-icon"><i class="fas fa-hourglass-half"></i></span>
                                        ยังไม่ได้วัด
                                    </span>
                                @else
                                    {{-- $evalClass/$evalIcon/$evalLabel/$evalRange computed once,
                                         above, in this row's @php block --}}
                                    <span class="eval-badge {{ $evalClass }}">
                                        <span class="eval-badge-icon">
                                            <span class="eval-badge-icon-inner"><i class="fas {{ $evalIcon }}"></i></span>
                                        </span>
                                        {{ $evalLabel }}
                                    </span>
                                @endif
                            </td>
                            <td>
                                <button type="button" class="meal-calc-add-btn" id="mealCalcAddBtn{{ $menu->id }}"
                                    data-menu-id="{{ $menu->id }}" data-menu-name="{{ $menu->menu_name }}"
                                    data-sodium-before="{{ $menu->sodium_before }}"
                                    data-sodium-after="{{ $hasSodiumReading ? $menu->sodium_after : $menu->sodium_before }}"
                                    onclick="event.stopPropagation();" title="เพิ่มเข้าเครื่องคำนวณมื้ออาหาร">
                                    <i class="fas fa-plus"></i>
                                </button>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="9" style="padding: 60px; color: #a0aec0; font-style: italic;">
                                <i class="fas fa-search mb-3" style="font-size: 2rem; display: block;"></i>
                                ไม่พบข้อมูลเมนูที่ตรงกับเงื่อนไขการค้นหา
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        @if($menus->hasPages())
            <div class="pagination-wrapper">
                {{ $menus->links('pagination::bootstrap-4') }}
            </div>
        @endif
    </div>

    <!-- Menu Detail Modal -->
    <div id="menuDetailModal" class="detail-modal">
        <div class="modal-dialog-new">
            <div class="modal-header-new">
                <h3><i class="fas fa-utensils"></i> รายละเอียดเมนูอาหาร</h3>
                <span class="close-detail">&times;</span>
            </div>
            <div class="modal-body-new">
                <div class="detail-media-col">
                    <div class="detail-image-box" id="detailImageBox">
                        <img id="detailImg" src="" alt="Menu Image" style="display: none;">
                        <div class="detail-image-placeholder" id="detailImagePlaceholder">
                            <i class="fas fa-utensils"></i>
                            <span>ยังไม่มีรูปภาพ</span>
                        </div>
                    </div>
                    <span class="eval-strip" id="detailEvalStrip" style="display: none;">
                        <i class="fas" id="detailEvalStripIcon"></i>
                        <span id="detailEvalStripLabel"></span>
                    </span>
                </div>
                <div class="detail-info">
                    <div class="detail-hero">
                        <span class="info-label"><i class="fas fa-hamburger"></i> ชื่อเมนูอาหาร</span>
                        <span class="info-value" id="detailName">-</span>
                        <span class="detail-type-chip"><i class="fas fa-store"></i> <span id="detailType">-</span></span>
                    </div>

                    <div class="sodium-flow-card">
                        <span class="info-label"><i class="fas fa-vial"></i> ปริมาณโซเดียม (เปรียบเทียบ)</span>
                        <div class="sodium-flow">
                            <div class="sodium-flow-item sodium-flow-before">
                                <span class="sodium-flow-tag">ก่อนปรับสูตร</span>
                                <span class="sodium-flow-value" id="detailSodiumBefore">0</span>
                                <span class="sodium-flow-unit">มก.</span>
                            </div>
                            <div class="sodium-flow-arrow">
                                <i class="fas fa-arrow-right"></i>
                                <span class="reduced-badge" id="detailReducedBadge">
                                    <i class="fas" id="detailReducedIcon"></i>
                                    <span class="reduced-badge-text">
                                        <span class="reduced-badge-value" id="detailReducedValue">0 mg</span>
                                        <span class="reduced-badge-percent" id="detailReducedPercent">0%</span>
                                    </span>
                                </span>
                            </div>
                            <div class="sodium-flow-item sodium-flow-after" id="detailSodiumAfterContainer">
                                <span class="sodium-flow-tag">หลังปรับสูตร</span>
                                <span class="sodium-flow-value" id="detailSodiumAfter">0</span>
                                <span class="sodium-flow-unit">มก.</span>
                            </div>
                        </div>
                    </div>

                    <div class="info-card-new info-card-grid">
                        <div class="info-row">
                            <span class="info-label"><i class="fas fa-hospital"></i> หน่วยงาน / แหล่งผลิต</span>
                            <span class="info-value" id="detailAgency">-</span>
                            <span class="info-subvalue" id="detailProvince" style="display: none;"><i
                                    class="fas fa-map-marker-alt"></i><span id="detailProvinceText"></span></span>
                        </div>
                        <div class="info-row">
                            <span class="info-label"><i class="fas fa-calendar-alt"></i> ปีงบประมาณ</span>
                            <span class="info-value" id="detailYear">-</span>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Sodium Evaluation Criteria Modal -->
    <div id="evalCriteriaModal" class="detail-modal">
        <div class="modal-dialog-new" style="max-width: 560px;">
            <div class="modal-header-new">
                <h3><i class="fas fa-shield-heart"></i> เกณฑ์การประเมินระดับโซเดียมต่อมื้อ</h3>
                <span class="close-eval-modal">&times;</span>
            </div>
            <div class="eval-modal-body">
                <p class="eval-modal-intro">
                    "ระดับการประเมิน" ของแต่ละเมนูคำนวณจากปริมาณโซเดียม<b>หลังปรับสูตร</b> (มก. ต่อมื้อ)
                    แบ่งเป็น 4 ระดับ ดังนี้
                </p>
                <div class="eval-modal-tier eval-modal-tier-best">
                    <span class="eval-modal-tier-name"><span class="eval-dot" style="background:#2f855a;"></span>
                        ดีมาก</span>
                    <span class="eval-modal-tier-range">&lt; 600 mg</span>
                </div>
                <div class="eval-modal-tier eval-modal-tier-good">
                    <span class="eval-modal-tier-name"><span class="eval-dot" style="background:#84cc16;"></span>
                        ดี</span>
                    <span class="eval-modal-tier-range">601 - 800 mg</span>
                </div>
                <div class="eval-modal-tier eval-modal-tier-mid">
                    <span class="eval-modal-tier-name"><span class="eval-dot" style="background:#d97706;"></span>
                        ปานกลาง</span>
                    <span class="eval-modal-tier-range">801 - 1,000 mg</span>
                </div>
                <div class="eval-modal-tier eval-modal-tier-poor">
                    <span class="eval-modal-tier-name"><span class="eval-dot" style="background:#c53030;"></span>
                        ควรปรับปรุง</span>
                    <span class="eval-modal-tier-range">&gt; 1,000 mg</span>
                </div>
                <p class="eval-modal-note">
                    <i class="fas fa-circle-info"></i>
                    นับเฉพาะเมนูที่มีผลตรวจโซเดียมหลังปรับสูตรแล้ว เมนูที่ยังไม่ได้วัด (แสดง "ยังไม่ได้วัด" ในตาราง)
                    จะไม่ถูกนำมาประเมินระดับ
                </p>
            </div>
        </div>
    </div>

    <!-- เครื่องคำนวณโซเดียมในมื้ออาหาร - collapsed into a small round button
         by default; opens into the full panel on click. Fed by the
         "+ คำนวณมื้อ" button in each table row. -->
    <button type="button" class="meal-calc-fab" id="mealCalcFab" title="เปิดเครื่องคำนวณโซเดียมในมื้ออาหาร">
        <i class="fas fa-calculator"></i>
        <span class="meal-calc-fab-badge" id="mealCalcFabBadge" style="display: none;">0</span>
    </button>

    <div class="meal-calc-widget" id="mealCalcWidget">
        <div class="meal-calc-header">
            <div class="meal-calc-header-main">
                <div class="meal-calc-header-title"><i class="fas fa-calculator"></i> การคำนวณโซเดียมในมื้ออาหาร</div>
                <span class="meal-calc-unit-note">*ต่อหน่วยบริโภค 100 กรัม</span>
            </div>
            <div class="meal-calc-header-actions">
                <span class="meal-calc-count-badge" id="mealCalcCountBadge">0 รายการ</span>
                <button type="button" class="meal-calc-collapse-btn" id="mealCalcCollapseBtn" title="ย่อ">
                    <i class="fas fa-chevron-down"></i>
                </button>
            </div>
        </div>
        <div class="meal-calc-body" id="mealCalcBody">
            <div class="meal-calc-row">
                <div class="meal-calc-items-scroll">
                    <div class="meal-calc-empty" id="mealCalcEmpty">
                        <div class="meal-calc-empty-icon"><i class="fas fa-bag-shopping"></i></div>
                        <p>ยังไม่มีรายการอาหารในมื้อนี้<br>กดปุ่ม "+ คำนวณมื้อ" ในตารางเพื่อเลือกรายการ</p>
                    </div>
                    <div id="mealCalcItems"></div>
                </div>
                <div class="meal-calc-divider"></div>
                <div class="meal-calc-summary-group">
                    <div class="meal-calc-summary">
                        <div class="meal-calc-sum-old">
                            <span class="meal-calc-sum-label">เดิม</span>
                            <span class="meal-calc-sum-value" id="mealCalcOldTotal">0 mg</span>
                        </div>
                        <i class="fas fa-arrow-right-long meal-calc-sum-arrow"></i>
                        <div class="meal-calc-sum-new">
                            <span class="meal-calc-sum-label">สูตรใหม่</span>
                            <span class="meal-calc-sum-value" id="mealCalcNewTotal">0 mg</span>
                        </div>
                    </div>
                    <div class="meal-calc-savings-tip" id="mealCalcSavingsTip" style="display: none;">
                        <i class="fas fa-wand-magic-sparkles"></i>
                        <span id="mealCalcSavingsText"></span>
                    </div>
                </div>
                <div class="meal-calc-divider"></div>
                <div class="meal-calc-who">
                    <div class="meal-calc-who-ring" id="mealCalcWhoBarFill">
                        <span id="mealCalcWhoPercent">0%</span>
                    </div>
                    <div class="meal-calc-who-caption">
                        <span>เทียบค่า WHO</span>
                        <span>2,000 mg/วัน</span>
                    </div>
                </div>
                <div class="meal-calc-divider"></div>
                <div class="meal-calc-status" id="mealCalcStatus">
                    <i class="fas fa-circle-info" id="mealCalcStatusIcon"></i>
                    <span id="mealCalcStatusText">ยังไม่ได้เลือกมื้ออาหาร</span>
                </div>
                <button type="button" class="meal-calc-clear-btn" id="mealCalcClearBtn" title="ล้างมื้ออาหาร">
                    <i class="fas fa-trash-can"></i>
                </button>
            </div>
        </div>
    </div>
@endsection

@section('extra_js')
    <script src="{{ asset('vendor/highcharts/highmaps.js') }}"></script>
    <script src="{{ asset('vendor/highcharts/th-all.js') }}"></script>

    <script>
        document.addEventListener('DOMContentLoaded', function () {
            // --- Global Font for Beauty ---
            Highcharts.setOptions({
                lang: { thousandsSep: ',' },
                chart: {
                    style: {
                        fontFamily: "'Sarabun', sans-serif"
                    }
                }
            });

            // Soft top->bottom gradient fill and drop shadow, so bars/slices
            // read with a little depth instead of a flat solid fill.
            function toGradient(hex) {
                const light = Highcharts.color(hex).brighten(0.25).get('rgba');
                return {
                    linearGradient: { x1: 0, y1: 0, x2: 0, y2: 1 },
                    stops: [
                        [0, light],
                        [1, hex]
                    ]
                };
            }

            const barShadow = { color: 'rgba(15, 23, 42, 0.18)', offsetX: 0, offsetY: 4, width: 6 };

            // --- 1. Map Logic (Health Region 10) ---
            const hcMapKeys = {
                'อุบลราชธานี': 'th-ur',
                'ศรีสะเกษ': 'th-si',
                'ยโสธร': 'th-ys',
                'อำนาจเจริญ': 'th-ac',
                'มุกดาหาร': 'th-md'
            };

            const mapDataRaw = @json($mapData);
            const mapSeriesData = Object.keys(hcMapKeys).map(name => ({
                'hc-key': hcMapKeys[name],
                'value': mapDataRaw[name] || 0,
                'name': name
            }));

            if (!Highcharts.maps || !Highcharts.maps['countries/th/th-all']) {
                console.warn('Highcharts Thailand map data not loaded');
            } else {
                const fullMapData = Highcharts.maps['countries/th/th-all'];
                const targetKeys = Object.values(hcMapKeys);
                const filteredFeatures = fullMapData.features.filter(f => targetKeys.includes(f.properties['hc-key']));
                const filteredMapData = { ...fullMapData, features: filteredFeatures };

                Highcharts.mapChart('map-container', {
                    chart: {
                        map: filteredMapData,
                        backgroundColor: 'transparent',
                        spacing: [10, 10, 10, 10]
                    },
                    title: { text: '' },
                    mapNavigation: { enabled: false },
                    colorAxis: {
                        min: 0,
                        minColor: '#fdf2f8',
                        maxColor: '#f42ca7',
                        labels: {
                            style: { color: '#64748b', fontWeight: 'bold' }
                        }
                    },
                    legend: {
                        enabled: {{ ($hideLayout ?? false) ? 'false' : 'true' }},
                        layout: 'horizontal',
                        align: 'center',
                        verticalAlign: 'bottom',
                        itemStyle: { fontSize: '12px', fontWeight: '600', color: '#64748b' }
                    },
                    plotOptions: {
                        map: {
                            allAreas: false,
                            borderColor: '#FFFFFF',
                            borderWidth: 2,
                            shadow: barShadow,
                            states: { hover: { color: '#fb7185' } },
                            dataLabels: {
                                enabled: true,
                                format: '{point.name}<br>{point.value} เมนู',
                                style: {
                                    fontSize: '11px',
                                    fontWeight: '700',
                                    textOutline: 'none',
                                    color: '#1e293b'
                                }
                            }
                        }
                    },
                    series: [{
                        name: 'จำนวนเมนู',
                        data: mapSeriesData,
                        joinBy: 'hc-key',
                    }],
                    credits: { enabled: false }
                });
            }

            // --- 2. Bar Chart Logic (Global Before vs After) ---
            const barData = @json($barChartData);

            Highcharts.chart('sodiumBarChart', {
                chart: {
                    type: 'column',
                    backgroundColor: 'transparent',
                    height: 380,
                    spacingBottom: 30
                },
                title: { text: '' },
                xAxis: {
                    categories: ['ปริมาณโซเดียม'],
                    labels: { enabled: false },
                    lineColor: '#e2e8f0'
                },
                yAxis: {
                    title: { text: 'ปริมาณโซเดียม (มก.)', style: { fontWeight: '600', color: '#64748b' } },
                    gridLineDashStyle: 'Dash',
                    gridLineColor: '#f1f5f9',
                    labels: { style: { color: '#94a3b8' } }
                },
                tooltip: {
                    headerFormat: '<span style="font-size: 13px; font-weight: bold;">{point.key}</span><br/>',
                    pointFormat: '<span style="color:{point.color}">\u25CF</span> {series.name}: <b>{point.y:.1f} มก.</b><br/>',
                    backgroundColor: 'rgba(255, 255, 255, 0.95)',
                    borderRadius: 12,
                    borderWidth: 1,
                    borderColor: '#e2e8f0',
                    shadow: true
                },
                plotOptions: {
                    column: {
                        borderRadius: 15,
                        shadow: barShadow,
                        dataLabels: {
                            enabled: true,
                            format: '{point.y:,.1f} มก.',
                            style: { fontWeight: '800', fontSize: '14px', textOutline: 'none' },
                            y: -5
                        },
                        groupPadding: 0.1,
                        pointPadding: 0.1,
                        maxPointWidth: 120
                    }
                },
                legend: {
                    align: 'center',
                    verticalAlign: 'bottom',
                    itemStyle: { fontSize: '13px', fontWeight: '700', color: '#475569' },
                    symbolRadius: 6
                },
                series: [{
                    name: 'ก่อนปรับสูตร',
                    data: [barData.before],
                    color: {
                        linearGradient: { x1: 0, x2: 0, y1: 0, y2: 1 },
                        stops: [[0, '#fb7185'], [1, '#e11d48']]
                    }
                }, {
                    name: 'หลังปรับสูตร',
                    data: [barData.after],
                    color: {
                        linearGradient: { x1: 0, x2: 0, y1: 0, y2: 1 },
                        stops: [[0, '#4ade80'], [1, '#16a34a']]
                    }
                }],
                credits: { enabled: false }
            });

            // --- 3. Pie Chart Logic ---
            const pieDataRaw = @json($pieChartData);
            const pieData = Object.keys(pieDataRaw).map(key => ({
                name: key || 'ไม่ระบุข้อมูล',
                y: pieDataRaw[key]
            }));

            Highcharts.chart('sourcePieChart', {
                chart: { type: 'pie', backgroundColor: 'transparent' },
                title: { text: '' },
                tooltip: {
                    pointFormat: '{series.name}: <b>{point.percentage:.1f}%</b> ({point.y} เมนู)'
                },
                plotOptions: {
                    pie: {
                        allowPointSelect: true,
                        cursor: 'pointer',
                        shadow: barShadow,
                        dataLabels: {
                            enabled: true,
                            format: '<b>{point.name}</b><br>{point.percentage:.1f} %',
                            style: { fontSize: '11px', fontWeight: '600', textOutline: 'none' },
                            distance: 15
                        },
                        showInLegend: true
                    }
                },
                legend: {
                    itemStyle: { fontSize: '11px', fontWeight: '500' }
                },
                colors: ['#22d3ee', '#c084fc', '#fb923c', '#4ade80', '#f472b6'].map(toGradient),
                series: [{
                    name: 'สัดส่วน',
                    colorByPoint: true,
                    data: pieData
                }],
                credits: { enabled: false }
            });
        });
    </script>

    <script>
        document.addEventListener('DOMContentLoaded', function () {
            const detailModal = document.getElementById("menuDetailModal");
            const closeDetail = document.querySelector(".close-detail");
            const rows = document.querySelectorAll(".clickable-row");
            // Charts sitting behind the modal (map + graphs) can show through the
            // blurred overlay on some screens/data. Hide them while the modal is
            // open so nothing ever bleeds through, then restore on close.
            const bgChartIds = ['map-container', 'sourcePieChart', 'sodiumBarChart'];
            function setBgChartsVisible(visible) {
                bgChartIds.forEach(function (id) {
                    const el = document.getElementById(id);
                    if (el) { el.style.visibility = visible ? 'visible' : 'hidden'; }
                });
            }

            // The sticky top nav sits above the overlay (it has a higher
            // z-index) but still counts toward the overlay's own 100%
            // height, so centering "within the whole viewport" visually
            // reads as too high - the header eats into the top half only.
            // Start the overlay right below the header's actual current
            // height (it can vary - stacked brand+nav at the very top of
            // the page vs. just the slim sticky nav once scrolled) so the
            // modal centers within the space that's actually visible.
            function positionDetailOverlay() {
                const header = document.querySelector('.site-header');
                const headerH = header ? header.getBoundingClientRect().height : 0;
                detailModal.style.top = headerH + 'px';
                detailModal.style.height = 'calc(100% - ' + headerH + 'px)';
            }
            window.addEventListener('resize', function () {
                if (detailModal.style.display === 'flex') positionDetailOverlay();
            });

            rows.forEach(row => {
                row.addEventListener("click", function() {
                    const menuRaw = this.getAttribute("data-menu");
                    if (!menuRaw) return;

                    const menu = JSON.parse(menuRaw);

                    // Image vs. "no photo yet" placeholder
                    const imgEl = document.getElementById("detailImg");
                    const placeholderEl = document.getElementById("detailImagePlaceholder");
                    if (menu.has_image && menu.image) {
                        imgEl.src = menu.image;
                        imgEl.style.display = "block";
                        placeholderEl.style.display = "none";
                    } else {
                        imgEl.style.display = "none";
                        placeholderEl.style.display = "flex";
                    }

                    document.getElementById("detailName").textContent = menu.name;
                    document.getElementById("detailType").textContent = menu.type;
                    document.getElementById("detailSodiumBefore").textContent = menu.before;
                    document.getElementById("detailSodiumAfter").textContent = menu.after;
                    document.getElementById("detailAgency").textContent = menu.agency;
                    document.getElementById("detailYear").textContent = menu.year;

                    // จังหวัด: same "small muted line under the agency name" as
                    // the table's .agency-tag-province - hidden entirely when
                    // this menu has no province on file.
                    const provinceEl = document.getElementById("detailProvince");
                    if (menu.province) {
                        document.getElementById("detailProvinceText").textContent = menu.province;
                        provinceEl.style.display = "flex";
                    } else {
                        provinceEl.style.display = "none";
                    }

                    // ระดับการประเมิน: shown as a full-width strip under the photo
                    // (edges matching the photo frame) instead of a small badge -
                    // same eval_label/eval_class/eval_icon/eval_range the table
                    // badge already uses for this row (both come from the same
                    // per-row @php block server-side), so the tier shown here can
                    // never disagree with the tier shown in the table.
                    const evalStripEl = document.getElementById("detailEvalStrip");
                    if (menu.has_reading && menu.eval_label) {
                        document.getElementById("detailEvalStripIcon").className = "fas " + menu.eval_icon;
                        document.getElementById("detailEvalStripLabel").textContent = menu.eval_label + " (" + menu.eval_range + ")";
                        evalStripEl.className = "eval-strip " + menu.eval_class;
                        evalStripEl.style.display = "flex";
                    } else {
                        evalStripEl.style.display = "none";
                    }

                    // Sodium After Enrichment
                    const afterContainer = document.getElementById("detailSodiumAfterContainer");
                    const reducedBadge = document.getElementById("detailReducedBadge");
                    const reducedIcon = document.getElementById("detailReducedIcon");
                    const reducedValue = document.getElementById("detailReducedValue");
                    const reducedPercent = document.getElementById("detailReducedPercent");
                    const percentValue = Math.abs(parseFloat(menu.reduction_percent) || 0);
                    if (menu.has_reading === false) {
                        // sodium_before/sodium_after recorded as exactly 0.0 -
                        // treated as "ยังไม่ได้วัด" (see the @php block above),
                        // not a real before/after reading to compare, so no mg/%
                        // figure is shown here either - same guard as the table's
                        // "โซเดียมที่ลดได้" column.
                        afterContainer.style.background = "#f8fafc";
                        afterContainer.style.color = "#64748b";
                        afterContainer.style.borderColor = "#e2e8f0";
                        reducedBadge.className = "reduced-badge-pending is-label";
                        reducedBadge.title = "ยังไม่มีผลตรวจโซเดียมหลังปรับสูตร";
                        reducedIcon.style.display = "none";
                        reducedValue.textContent = "ยังไม่มีผล";
                        reducedPercent.textContent = "";
                    } else if (menu.is_bad) {
                        afterContainer.style.background = "#fff5f5";
                        afterContainer.style.color = "#c53030";
                        afterContainer.style.borderColor = "#feb2b2";
                        reducedBadge.className = "reduced-badge is-bad";
                        reducedBadge.title = "เพิ่มขึ้นจากก่อนปรับสูตร";
                        reducedIcon.style.display = "";
                        reducedIcon.className = "fas fa-arrow-up";
                        reducedValue.textContent = menu.reduced_mg + " mg";
                        reducedPercent.textContent = "+" + percentValue + "%";
                    } else {
                        afterContainer.style.background = "#f0fff4";
                        afterContainer.style.color = "#2f855a";
                        afterContainer.style.borderColor = "#c6f6d5";
                        reducedBadge.className = "reduced-badge is-good";
                        reducedBadge.title = "ลดลงจากก่อนปรับสูตร";
                        reducedIcon.style.display = "";
                        reducedIcon.className = "fas fa-arrow-down";
                        reducedValue.textContent = menu.reduced_mg + " mg";
                        reducedPercent.textContent = percentValue + "%";
                    }

                    positionDetailOverlay();
                    detailModal.style.display = "flex";
                    setBgChartsVisible(false);
                });
            });

            if (closeDetail) {
                closeDetail.onclick = () => {
                    detailModal.style.display = "none";
                    setBgChartsVisible(true);
                };
            }

            window.onclick = (e) => {
                if (e.target == detailModal) {
                    detailModal.style.display = "none";
                    setBgChartsVisible(true);
                }
            }
        });
    </script>

    <script>
        // Kept in its own listener (rather than folded into the menu-detail
        // modal's script above) so it can use addEventListener for the
        // outside-click close instead of overwriting window.onclick, which
        // that other script already assigns to.
        document.addEventListener('DOMContentLoaded', function () {
            const evalModal = document.getElementById("evalCriteriaModal");
            const evalInfoBtn = document.getElementById("evalInfoBtn");
            const closeEvalModal = evalModal ? evalModal.querySelector(".close-eval-modal") : null;

            if (!evalModal || !evalInfoBtn) return;

            function openEvalModal() {
                evalModal.style.display = "flex";
            }
            function closeEvalModalFn() {
                evalModal.style.display = "none";
            }

            evalInfoBtn.addEventListener("click", function (e) {
                e.stopPropagation();
                openEvalModal();
            });

            if (closeEvalModal) {
                closeEvalModal.addEventListener("click", closeEvalModalFn);
            }

            window.addEventListener("click", function (e) {
                if (e.target === evalModal) closeEvalModalFn();
            });
        });
    </script>

    <script>
        // เครื่องคำนวณโซเดียมในมื้ออาหาร - self-contained: state lives only
        // in this closure (mealItems), never localStorage, since
        // "ล้างมื้ออาหาร" is the explicit reset control and nothing here
        // needs to survive a page reload.
        document.addEventListener('DOMContentLoaded', function () {
            const mealItems = {};
            const WHO_DAILY_LIMIT_MG = 2000;

            const fab = document.getElementById('mealCalcFab');
            const fabBadge = document.getElementById('mealCalcFabBadge');
            const widget = document.getElementById('mealCalcWidget');
            const collapseBtn = document.getElementById('mealCalcCollapseBtn');
            const countBadge = document.getElementById('mealCalcCountBadge');
            const emptyEl = document.getElementById('mealCalcEmpty');
            const itemsEl = document.getElementById('mealCalcItems');
            const oldTotalEl = document.getElementById('mealCalcOldTotal');
            const newTotalEl = document.getElementById('mealCalcNewTotal');
            const savingsTipEl = document.getElementById('mealCalcSavingsTip');
            const savingsTextEl = document.getElementById('mealCalcSavingsText');
            const whoPercentEl = document.getElementById('mealCalcWhoPercent');
            const whoBarFillEl = document.getElementById('mealCalcWhoBarFill');
            const statusEl = document.getElementById('mealCalcStatus');
            const statusIconEl = document.getElementById('mealCalcStatusIcon');
            const statusTextEl = document.getElementById('mealCalcStatusText');
            const clearBtn = document.getElementById('mealCalcClearBtn');

            if (!itemsEl || !clearBtn || !fab || !widget) return;

            function formatMg(n) {
                return Math.round(n).toLocaleString('th-TH') + ' mg';
            }

            function openWidget() {
                widget.classList.add('is-open');
                fab.style.display = 'none';
            }

            function closeWidget() {
                widget.classList.remove('is-open');
                fab.style.display = 'flex';
            }

            fab.addEventListener('click', openWidget);
            if (collapseBtn) collapseBtn.addEventListener('click', closeWidget);

            function setAddBtnState(id, added) {
                const addBtn = document.getElementById('mealCalcAddBtn' + id);
                if (addBtn) addBtn.classList.toggle('is-added', added);
            }

            function renderMealCalc() {
                const ids = Object.keys(mealItems);
                countBadge.textContent = ids.length + ' รายการ';

                if (ids.length > 0) {
                    fabBadge.textContent = ids.length;
                    fabBadge.style.display = 'flex';
                } else {
                    fabBadge.style.display = 'none';
                }

                if (ids.length === 0) {
                    emptyEl.style.display = 'flex';
                    itemsEl.innerHTML = '';
                    oldTotalEl.textContent = '0 mg';
                    newTotalEl.textContent = '0 mg';
                    savingsTipEl.style.display = 'none';
                    whoPercentEl.textContent = '0%';
                    whoBarFillEl.style.setProperty('--pct', '0%');
                    whoBarFillEl.classList.remove('is-over');
                    statusEl.className = 'meal-calc-status';
                    statusIconEl.className = 'fas fa-circle-info';
                    statusTextEl.textContent = 'ยังไม่ได้เลือกมื้ออาหาร';
                    return;
                }

                emptyEl.style.display = 'none';

                let oldTotal = 0;
                let newTotal = 0;
                itemsEl.innerHTML = ids.map(function (id) {
                    const item = mealItems[id];
                    oldTotal += item.before * item.qty;
                    newTotal += item.after * item.qty;
                    return '' +
                        '<div class="meal-calc-item">' +
                            '<div class="meal-calc-item-main">' +
                                '<div class="meal-calc-item-name">' +
                                    '<span title="' + item.name + '">' + item.name + '</span>' +
                                    '<button type="button" class="meal-calc-item-remove" data-remove-id="' + id + '" title="นำออก">' +
                                        '<i class="fas fa-xmark"></i>' +
                                    '</button>' +
                                '</div>' +
                                '<div class="meal-calc-item-unit">' + Math.round(item.after) + ' mg / ชิ้น</div>' +
                                '<div class="meal-calc-item-row">' +
                                    '<div class="meal-calc-stepper">' +
                                        '<button type="button" data-qty-id="' + id + '" data-qty-delta="-1">-</button>' +
                                        '<span>' + item.qty + '</span>' +
                                        '<button type="button" data-qty-id="' + id + '" data-qty-delta="1">+</button>' +
                                    '</div>' +
                                    '<span class="meal-calc-item-mg">' + Math.round(item.after * item.qty) + ' mg</span>' +
                                '</div>' +
                            '</div>' +
                        '</div>';
                }).join('');

                oldTotalEl.textContent = formatMg(oldTotal);
                newTotalEl.textContent = formatMg(newTotal);

                const savings = oldTotal - newTotal;
                if (savings > 0) {
                    savingsTipEl.style.display = 'flex';
                    savingsTextEl.textContent = 'ช่วยลดโซเดียมลงได้ ' + Math.round(savings).toLocaleString('th-TH') + ' mg ในมื้อนี้!';
                } else {
                    savingsTipEl.style.display = 'none';
                }

                const percent = Math.round((newTotal / WHO_DAILY_LIMIT_MG) * 100);
                whoPercentEl.textContent = percent + '%';
                whoBarFillEl.style.setProperty('--pct', Math.min(percent, 100) + '%');

                if (newTotal <= WHO_DAILY_LIMIT_MG) {
                    whoBarFillEl.classList.remove('is-over');
                    statusEl.className = 'meal-calc-status is-safe';
                    statusIconEl.className = 'fas fa-circle-check';
                    statusTextEl.textContent = 'ปริมาณโซเดียมปลอดภัย (ต่ำกว่าเกณฑ์)';
                } else {
                    whoBarFillEl.classList.add('is-over');
                    statusEl.className = 'meal-calc-status is-over';
                    statusIconEl.className = 'fas fa-triangle-exclamation';
                    statusTextEl.textContent = 'มื้อนี้มีโซเดียมเกินเกณฑ์ที่ WHO แนะนำต่อวันแล้ว';
                }
            }

            document.querySelectorAll('.meal-calc-add-btn').forEach(function (btn) {
                btn.addEventListener('click', function (e) {
                    e.stopPropagation();
                    const id = btn.getAttribute('data-menu-id');
                    if (mealItems[id]) {
                        mealItems[id].qty += 1;
                    } else {
                        mealItems[id] = {
                            name: btn.getAttribute('data-menu-name'),
                            before: parseFloat(btn.getAttribute('data-sodium-before')) || 0,
                            after: parseFloat(btn.getAttribute('data-sodium-after')) || 0,
                            qty: 1
                        };
                    }
                    setAddBtnState(id, true);
                    renderMealCalc();
                    openWidget();
                });
            });

            itemsEl.addEventListener('click', function (e) {
                const removeBtn = e.target.closest('[data-remove-id]');
                if (removeBtn) {
                    const id = removeBtn.getAttribute('data-remove-id');
                    delete mealItems[id];
                    setAddBtnState(id, false);
                    renderMealCalc();
                    return;
                }
                const qtyBtn = e.target.closest('[data-qty-id]');
                if (qtyBtn) {
                    const id = qtyBtn.getAttribute('data-qty-id');
                    const delta = parseInt(qtyBtn.getAttribute('data-qty-delta'), 10);
                    if (!mealItems[id]) return;
                    mealItems[id].qty += delta;
                    if (mealItems[id].qty <= 0) {
                        delete mealItems[id];
                        setAddBtnState(id, false);
                    }
                    renderMealCalc();
                }
            });

            clearBtn.addEventListener('click', function () {
                Object.keys(mealItems).forEach(function (id) {
                    setAddBtnState(id, false);
                    delete mealItems[id];
                });
                renderMealCalc();
            });

            renderMealCalc();
        });
    </script>
@endsection
