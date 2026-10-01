@extends('layouts.admin')

@section('title', 'ร้อยละความตระหนักรู้ - ตั้งค่าเกณฑ์ - Admin Dashboard')

@section('header_title')
    <div class="page-header">
        <div class="page-header-accent"></div>
        <div class="page-header-text">
            <span class="page-header-title">ร้อยละความตระหนักรู้</span>
            <small class="page-header-subtitle">
                <i class="fas fa-circle-info"></i>
                ตั้งค่าเกณฑ์ผ่าน/ไม่ผ่านของแต่ละปีงบประมาณ
            </small>
        </div>
    </div>
@endsection

@section('content')
    <style>
        .settings-container {
            max-width: 98%;
            margin: 10px auto 20px;
            padding: 0 15px;
            font-family: 'Noto Serif Thai', 'TH Sarabun New', 'TH Sarabun PS', sans-serif;
            font-weight: 600;
        }

        .hero-card {
            background: #fff;
            border-radius: 18px;
            box-shadow: 0 2px 10px rgba(0, 0, 0, 0.05);
            padding: 20px;
            margin-bottom: 26px;
            display: flex;
            align-items: center;
            gap: 16px;
        }

        .hero-icon {
            width: 52px;
            height: 52px;
            min-width: 52px;
            border-radius: 14px;
            background: linear-gradient(135deg, #ec4899 0%, #db2777 100%);
            color: #fff;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 1.4rem;
        }

        .hero-title {
            font-weight: 800;
            font-size: 1.15rem;
            color: #1e293b;
        }

        .hero-sub {
            font-size: 0.85rem;
            color: #64748b;
            margin-top: 3px;
            line-height: 1.5;
        }

        .hero-card {
            flex-wrap: wrap;
        }

        .hero-card-main {
            display: flex;
            align-items: center;
            gap: 16px;
            flex: 1;
            min-width: 240px;
        }

        .hero-year-picker {
            display: flex;
            align-items: center;
            gap: 10px;
            background: #fdf2f8;
            border: 1.5px solid #fbcfe8;
            border-radius: 14px;
            padding: 9px 16px;
            flex-shrink: 0;
            margin-left: auto;
        }

        .hero-year-picker i.fa-calendar-days {
            color: #db2777;
            font-size: 0.9rem;
        }

        .hero-year-picker {
            position: relative;
        }

        .hero-year-picker .year-dropdown-label {
            margin: 0;
            font-weight: 700;
            font-size: 0.82rem;
            color: #db2777;
            white-space: nowrap;
        }

        /* The closed pill button - the open <option> list below is what
           actually needed the redesign, since a native <select> popup
           can't be restyled with CSS across browsers. */
        .hero-year-picker .year-dropdown-toggle {
            display: flex;
            align-items: center;
            gap: 8px;
            cursor: pointer;
            font-weight: 800;
            font-size: 0.95rem;
            color: #1e293b;
            outline: none;
            border-radius: 6px;
        }

        .hero-year-picker .year-dropdown-toggle:focus-visible {
            box-shadow: 0 0 0 3px rgba(219, 39, 119, 0.25);
        }

        .hero-year-picker .year-dropdown-caret {
            color: #db2777;
            font-size: 0.68rem;
            transition: transform 0.18s ease;
        }

        .hero-year-picker.is-open .year-dropdown-caret {
            transform: rotate(180deg);
        }

        .hero-year-picker .year-dropdown-menu {
            list-style: none;
            margin: 0;
            padding: 6px;
            position: absolute;
            top: calc(100% + 10px);
            right: 0;
            min-width: 130px;
            background: #fff;
            border-radius: 14px;
            box-shadow: 0 12px 30px rgba(219, 39, 119, 0.2), 0 2px 8px rgba(0, 0, 0, 0.06);
            border: 1px solid #fbcfe8;
            opacity: 0;
            visibility: hidden;
            transform: translateY(-6px) scale(0.97);
            transform-origin: top right;
            transition: opacity 0.15s ease, transform 0.15s ease, visibility 0.15s;
            z-index: 60;
            max-height: 260px;
            overflow-y: auto;
        }

        .hero-year-picker.is-open .year-dropdown-menu {
            opacity: 1;
            visibility: visible;
            transform: translateY(0) scale(1);
        }

        .hero-year-picker .year-dropdown-option {
            display: flex;
            align-items: center;
            justify-content: space-between;
            gap: 14px;
            padding: 9px 12px;
            border-radius: 10px;
            font-weight: 700;
            font-size: 0.92rem;
            color: #334155;
            cursor: pointer;
            transition: background 0.12s ease, color 0.12s ease;
        }

        .hero-year-picker .year-dropdown-option + .year-dropdown-option {
            margin-top: 2px;
        }

        .hero-year-picker .year-dropdown-option i {
            font-size: 0.72rem;
            opacity: 0;
            color: #fff;
        }

        .hero-year-picker .year-dropdown-option:hover {
            background: #fdf2f8;
            color: #db2777;
        }

        .hero-year-picker .year-dropdown-option.is-selected {
            background: linear-gradient(135deg, #ec4899 0%, #db2777 100%);
            color: #fff;
        }

        .hero-year-picker .year-dropdown-option.is-selected i {
            opacity: 1;
        }

        @media (max-width: 680px) {
            .hero-card {
                flex-direction: column;
                align-items: stretch;
            }

            .hero-year-picker {
                margin-left: 0;
                justify-content: center;
            }
        }

        .settings-card {
            background: #fff;
            border-radius: 18px;
            box-shadow: 0 2px 10px rgba(0, 0, 0, 0.03);
            padding: 20px;
        }

        .panel-list {
            display: grid;
            grid-template-columns: repeat(2, 1fr);
            gap: 18px;
            margin-bottom: 6px;
        }

        .panel-picker-card {
            background: #fff;
            border-radius: 18px;
            box-shadow: 0 4px 15px rgba(0, 0, 0, 0.05);
            padding: 22px 26px;
            display: flex;
            align-items: center;
            justify-content: space-between;
            gap: 22px;
            cursor: pointer;
            transition: transform 0.15s ease, box-shadow 0.15s ease;
        }

        .panel-picker-card:hover {
            transform: translateY(-3px);
            box-shadow: 0 10px 25px rgba(0, 0, 0, 0.09);
        }

        .panel-picker-content {
            flex: 1;
            min-width: 0;
        }

        .panel-picker-illustration {
            width: 128px;
            height: 128px;
            min-width: 128px;
            border-radius: 50%;
            background: var(--panel-bg, #fdf2f8);
            display: flex;
            align-items: center;
            justify-content: center;
            overflow: hidden;
        }

        .panel-picker-illustration svg {
            width: 82px;
            height: 82px;
        }

        .panel-picker-badge {
            display: inline-block;
            font-size: 0.7rem;
            font-weight: 700;
            color: var(--panel-color, #db2777);
            background: #f1f5f9;
            border: 1px solid #e2e8f0;
            border-radius: 999px;
            padding: 2px 10px;
            margin-bottom: 8px;
            width: fit-content;
        }

        .panel-picker-title {
            font-weight: 800;
            font-size: 1.05rem;
            color: #1e293b;
            margin-bottom: 6px;
            line-height: 1.4;
        }

        .panel-picker-title i {
            margin-right: 6px;
        }

        .panel-picker-desc {
            font-size: 0.84rem;
            color: #64748b;
            line-height: 1.5;
        }

        .panel-picker-footer {
            display: flex;
            flex-direction: column;
            align-items: flex-start;
            gap: 4px;
            margin-top: 14px;
            padding-top: 12px;
            border-top: 1px dashed #f1f5f9;
        }

        .panel-picker-count {
            font-size: 0.8rem;
            font-weight: 700;
            color: #334155;
            margin-bottom: 2px;
        }

        .panel-picker-question-box {
            width: 100%;
            font-size: 0.86rem;
            font-weight: 700;
            color: var(--panel-color, #db2777);
            background: var(--panel-bg, #fdf2f8);
            border: 1px solid var(--panel-color, #db2777);
            border-radius: 10px;
            padding: 9px 13px;
            line-height: 1.55;
        }

        .panel-picker-question-box.is-empty {
            color: #94a3b8;
            background: #f8fafc;
            border-color: #e2e8f0;
            font-weight: 600;
        }

        .panel-picker-cta {
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 8px;
            width: 100%;
            box-sizing: border-box;
            margin-top: 10px;
            padding: 10px 18px;
            border-radius: 999px;
            background: var(--panel-color, #db2777);
            color: #fff;
            font-size: 0.82rem;
            font-weight: 700;
            white-space: nowrap;
        }

        @media (max-width: 992px) {
            .panel-list {
                grid-template-columns: 1fr;
            }
        }

        @media (max-width: 680px) {
            .panel-picker-card {
                flex-direction: column;
                align-items: stretch;
                text-align: center;
            }

            .panel-picker-illustration {
                margin: 4px auto 0;
            }

            .panel-picker-footer {
                justify-content: center;
            }

            .panel-picker-badge {
                margin-left: auto;
                margin-right: auto;
            }
        }

        .modal-header {
            border-top: 5px solid var(--panel-color, #db2777);
        }

        .question-check-row {
            display: flex;
            align-items: flex-start;
            gap: 10px;
            padding: 10px 12px;
            border-radius: 10px;
            margin-bottom: 6px;
            background: #f8fafc;
        }

        .question-check-row label {
            margin-bottom: 0;
            font-size: 0.88rem;
            color: #1e293b;
            cursor: pointer;
        }

        .question-other-panel-note {
            display: flex;
            align-items: flex-start;
            gap: 7px;
            margin-top: 6px;
            padding: 7px 10px;
            background: #fff7ed;
            border: 1px solid #fed7aa;
            border-radius: 8px;
            font-size: 0.78rem;
            font-weight: 600;
            color: #9a3412;
            line-height: 1.5;
        }

        .question-other-panel-note i {
            margin-top: 2px;
            color: #f59e0b;
        }

        .question-other-panel-note b {
            color: #c2410c;
        }

        .semantic-note {
            display: flex;
            align-items: flex-start;
            gap: 7px;
            margin-top: 6px;
            padding: 7px 10px;
            background: #f1f5f9;
            border: 1px solid #e2e8f0;
            border-radius: 8px;
            font-size: 0.76rem;
            font-weight: 600;
            color: #475569;
            line-height: 1.5;
        }

        .semantic-note i {
            margin-top: 2px;
            color: #94a3b8;
        }

        .answer-picker {
            width: 100%;
            margin-top: 10px;
            margin-left: 26px;
            padding: 12px 14px;
            background: #f0fdf4;
            border: 1px solid #bbf7d0;
            border-radius: 10px;
        }

        .answer-picker-label {
            font-size: 0.8rem;
            font-weight: 700;
            color: #166534;
            margin-bottom: 8px;
        }

        .answer-check-pill {
            display: inline-flex;
            align-items: center;
            gap: 5px;
            background: #fff;
            border: 1px solid #d1fae5;
            border-radius: 999px;
            padding: 4px 12px;
            margin: 0 6px 6px 0;
            font-size: 0.82rem;
            font-weight: 600;
            color: #1e293b;
            cursor: pointer;
        }

        .answer-check-pill input {
            margin: 0;
        }

        /* "คอลัมน์สำหรับการ์ด 'พฤติกรรมการบริโภคโซเดียม' (หน้าแรก)" section -
           reuses .question-check-row / .semantic-note from the criteria
           modals above. Cards deliberately have NO colored border-left
           accent (matches the panel-picker-card style used elsewhere on
           this page). All 4 cards (one per widget row) sit together in one
           balanced 2-column grid - each card carries its own "แถวที่ N"
           badge instead of being grouped under a separate row heading,
           since a row heading above a single card read as wasted space. */
        .behavior-section-title {
            font-weight: 800;
            font-size: 1.05rem;
            color: #1e293b;
            margin: 30px 0 6px;
            display: flex;
            align-items: center;
            gap: 8px;
        }

        .behavior-section-sub {
            font-size: 0.84rem;
            color: #64748b;
            line-height: 1.6;
            margin-bottom: 18px;
        }

        .behavior-grid {
            display: grid;
            grid-template-columns: repeat(4, 1fr);
            gap: 16px;
            margin-bottom: 6px;
        }

        @media (max-width: 1100px) {
            .behavior-grid {
                grid-template-columns: repeat(2, 1fr);
            }
        }

        @media (max-width: 560px) {
            .behavior-grid {
                grid-template-columns: 1fr;
            }
        }

        .behavior-card {
            background: #fff;
            border-radius: 16px;
            border: 1.5px solid #f1f5f9;
            box-shadow: 0 2px 10px rgba(0, 0, 0, 0.05);
            padding: 18px;
            display: flex;
            flex-direction: column;
            align-items: flex-start;
            gap: 10px;
            cursor: pointer;
            transition: transform 0.15s ease, box-shadow 0.15s ease, border-color 0.15s ease;
            /* Without this, the card's intrinsic minimum width follows its
               widest un-wrapped content (see .behavior-status-pill below),
               which can force this grid column past its 1fr share and push
               the whole row wider than the page. */
            min-width: 0;
        }

        .behavior-card:hover {
            transform: translateY(-3px);
            box-shadow: 0 10px 22px rgba(0, 0, 0, 0.08);
            border-color: var(--behavior-color, #6366f1);
        }

        .behavior-icon {
            width: 42px;
            height: 42px;
            min-width: 42px;
            border-radius: 12px;
            background: var(--behavior-bg, #f1f5f9);
            color: var(--behavior-color, #6366f1);
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 1.05rem;
        }

        .behavior-card-badge {
            display: inline-block;
            font-size: 0.7rem;
            font-weight: 700;
            line-height: 1.4;
            color: var(--behavior-color, #6366f1);
            background: var(--behavior-bg, #f1f5f9);
            border: 1px solid color-mix(in srgb, var(--behavior-color, #6366f1) 35%, #fff);
            border-radius: 10px;
            padding: 4px 10px;
            width: 100%;
            overflow-wrap: break-word;
            word-break: break-word;
            box-sizing: border-box;
        }

        .behavior-card-title {
            font-weight: 700;
            font-size: 0.9rem;
            color: #1e293b;
            line-height: 1.45;
            flex: 1;
            overflow-wrap: break-word;
            word-break: break-word;
        }

        .behavior-card-current {
            font-size: 0.78rem;
            color: #64748b;
            line-height: 1.5;
        }

        .behavior-card-footer {
            display: flex;
            flex-direction: column;
            align-items: flex-start;
            gap: 6px;
            width: 100%;
            margin-top: auto;
            padding-top: 10px;
            border-top: 1px dashed #f1f5f9;
            min-width: 0;
        }

        .behavior-status-label {
            display: flex;
            align-items: center;
            gap: 6px;
            font-size: 0.74rem;
            font-weight: 600;
            color: #94a3b8;
            white-space: nowrap;
        }

        .behavior-status-label .status-dot {
            color: #cbd5e1;
            font-size: 0.6rem;
        }

        .behavior-status-pill {
            display: block;
            width: 100%;
            font-size: 0.76rem;
            font-weight: 700;
            color: #94a3b8;
            background: #f8fafc;
            border: 1px solid #e2e8f0;
            border-radius: 10px;
            padding: 6px 10px;
            line-height: 1.5;
            white-space: normal;
            overflow-wrap: break-word;
            word-break: break-word;
            box-sizing: border-box;
        }

        .behavior-status-pill.is-selected {
            color: var(--behavior-color, #6366f1);
            background: var(--behavior-bg, #f1f5f9);
            border-color: var(--behavior-color, #6366f1);
        }

        .behavior-hint {
            display: flex;
            align-items: flex-start;
            gap: 8px;
            margin-bottom: 14px;
            padding: 10px 12px;
            background: #eff6ff;
            border: 1px solid #bfdbfe;
            border-radius: 10px;
            font-size: 0.8rem;
            color: #1e3a5f;
            line-height: 1.6;
        }

        .behavior-hint i {
            margin-top: 2px;
            color: #3b82f6;
        }

        /* Prominent "no data yet for this fiscal year" notice - replaces a
           plain muted line of text so a year with nothing uploaded yet
           reads unmistakably as "ยังไม่มีข้อมูล" rather than looking like an
           empty/broken page. */
        .no-data-notice {
            display: flex;
            align-items: flex-start;
            gap: 16px;
            background: #fffbeb;
            border: 1.5px solid #fde68a;
            border-radius: 18px;
            box-shadow: 0 2px 10px rgba(0, 0, 0, 0.04);
            padding: 22px 24px;
        }

        .no-data-notice-icon {
            width: 46px;
            height: 46px;
            min-width: 46px;
            border-radius: 13px;
            background: #fef3c7;
            color: #d97706;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 1.25rem;
        }

        .no-data-notice-title {
            font-weight: 800;
            font-size: 1.02rem;
            color: #92400e;
            margin-bottom: 6px;
        }

        .no-data-notice-desc {
            font-size: 0.86rem;
            color: #78350f;
            line-height: 1.6;
        }
        .method-picker-card {
            background: linear-gradient(180deg, #fff 0%, #fafaff 100%);
            border-radius: 20px;
            border: 1px solid #eef0f7;
            box-shadow: 0 4px 18px rgba(30, 41, 59, 0.06);
            padding: 24px 26px 26px;
            margin-bottom: 28px;
        }

        .method-picker-title {
            font-weight: 800;
            font-size: 1rem;
            color: #1e293b;
            margin-bottom: 18px;
            display: flex;
            align-items: center;
            gap: 10px;
        }

        .method-picker-title i {
            display: inline-flex;
            align-items: center;
            justify-content: center;
            width: 30px;
            height: 30px;
            border-radius: 9px;
            background: #f3e8ff;
            color: #7c3aed;
            font-size: 0.85rem;
        }

        .method-option-list {
            display: grid;
            grid-template-columns: repeat(2, 1fr);
            gap: 18px;
        }

        @media (max-width: 680px) {
            .method-option-list { grid-template-columns: 1fr; }
        }

        .method-option {
            display: flex;
            flex-direction: column;
            gap: 9px;
            padding: 20px 22px;
            background: #fff;
            border: 1.5px solid #e5e7eb;
            border-radius: 16px;
            cursor: pointer;
            transition: border-color 0.18s ease, background 0.18s ease, box-shadow 0.18s ease, transform 0.18s ease;
            position: relative;
        }

        .method-option:hover {
            border-color: color-mix(in srgb, var(--option-color) 55%, #e5e7eb);
            box-shadow: 0 6px 16px rgba(30, 41, 59, 0.08);
            transform: translateY(-2px);
        }

        .method-option input[type="radio"] {
            position: absolute;
            top: 18px;
            right: 18px;
            margin: 0;
            width: 19px;
            height: 19px;
            accent-color: var(--option-color);
            cursor: pointer;
        }

        .method-option.is-selected {
            border-color: var(--option-color);
            background: color-mix(in srgb, var(--option-color) 6%, #fff);
            box-shadow: 0 0 0 3px color-mix(in srgb, var(--option-color) 16%, transparent);
        }

        .method-option.is-selected:hover {
            transform: none;
        }

        .method-option-head {
            display: flex;
            align-items: center;
            justify-content: space-between;
            gap: 10px;
            padding-right: 30px;
        }

        .method-option-head-left {
            display: flex;
            align-items: center;
            gap: 10px;
            min-width: 0;
        }

        /* Explicit "currently in use" marker on the selected card - the
           border/tint highlight alone was easy to miss at a glance, so
           this spells it out in words + icon right in the card header. */
        .method-option-active-badge {
            display: none;
            align-items: center;
            gap: 5px;
            flex: 0 0 auto;
            font-size: 0.7rem;
            font-weight: 800;
            color: #fff;
            background: var(--option-color);
            border-radius: 999px;
            padding: 3px 10px;
            letter-spacing: 0.01em;
        }

        .method-option.is-selected .method-option-active-badge {
            display: inline-flex;
        }

        .method-option-icon {
            flex: 0 0 auto;
            display: inline-flex;
            align-items: center;
            justify-content: center;
            width: 34px;
            height: 34px;
            border-radius: 10px;
            background: color-mix(in srgb, var(--option-color) 14%, #fff);
            color: var(--option-color);
            font-size: 0.9rem;
        }

        .method-option-eyebrow {
            font-size: 0.72rem;
            font-weight: 700;
            letter-spacing: 0.04em;
            text-transform: uppercase;
            color: var(--option-color);
        }

        .method-option-title {
            font-weight: 700;
            font-size: 0.98rem;
            color: #1e293b;
            line-height: 1.4;
        }

        .method-option-desc {
            font-size: 0.8rem;
            color: #64748b;
            line-height: 1.6;
        }

        .method-picker-warning {
            display: flex;
            align-items: flex-start;
            gap: 10px;
            margin-top: 14px;
            padding: 12px 14px;
            background: #fffbeb;
            border: 1px solid #fde68a;
            border-radius: 12px;
            font-size: 0.8rem;
            font-weight: 600;
            color: #92400e;
            line-height: 1.6;
        }

        .method-picker-warning i {
            margin-top: 2px;
        }

        .method-inactive-note {
            display: flex;
            align-items: flex-start;
            gap: 10px;
            margin-bottom: 16px;
            padding: 12px 16px;
            background: #f1f5f9;
            border: 1px solid #e2e8f0;
            border-radius: 12px;
            font-size: 0.82rem;
            font-weight: 600;
            color: #475569;
            line-height: 1.6;
        }

        .method-inactive-note i {
            margin-top: 2px;
            color: #94a3b8;
        }

        .panel-list.is-inactive-method {
            opacity: 0.55;
            pointer-events: none;
        }

        .panel-list.is-inactive-method .panel-picker-card {
            cursor: not-allowed;
        }
    </style>

    <div class="settings-container">
        <div class="hero-card">
            <div class="hero-card-main">
                <div class="hero-icon"><i class="fa-solid fa-location-dot"></i></div>
                <div>
                    <div class="hero-title">
                        ร้อยละความตระหนักรู้
                        <button type="button" class="preview-image-btn"
                            data-preview-src="{{ asset('images/Additional-photos/map.png') }}"
                            data-preview-label="ตัวอย่าง: แผนที่รายจังหวัดในหน้าแรกที่เกณฑ์นี้มีผล"
                            title="ดูตัวอย่างกราฟที่ตั้งค่านี้มีผล">
                            <i class="fa-solid fa-image"></i>
                        </button>
                    </div>
                    <div class="hero-sub">
                        เลือกคำถาม 2 ข้อของแต่ละปีงบประมาณให้เป็นเกณฑ์ "ผ่าน" (ตอบ "ใช่" หรือ "เคย" ทั้งคู่) - มีผลกับแผนที่รายจังหวัดในหน้าแรก,
                        หน้ารายงานความตระหนักรู้ (/awareness) และป้ายสถานะในหน้าแอดมินอัปโหลดข้อมูล
                    </div>
                </div>
            </div>
            <div class="hero-year-picker year-dropdown">
                <i class="fa-solid fa-calendar-days"></i>
                <span id="hero-fiscal-year-label" class="year-dropdown-label">ปีงบประมาณ</span>
                <div class="year-dropdown-toggle" tabindex="0" role="button"
                    aria-haspopup="listbox" aria-expanded="false" aria-labelledby="hero-fiscal-year-label">
                    <span class="year-dropdown-value">{{ $selectedYear }}</span>
                    <i class="fa-solid fa-chevron-down year-dropdown-caret"></i>
                </div>
                <ul class="year-dropdown-menu" role="listbox" aria-labelledby="hero-fiscal-year-label">
                    @foreach ($years as $year)
                        <li role="option" aria-selected="{{ (string) $selectedYear === (string) $year ? 'true' : 'false' }}"
                            class="year-dropdown-option {{ (string) $selectedYear === (string) $year ? 'is-selected' : '' }}"
                            data-value="{{ $year }}">
                            <span>{{ $year }}</span>
                            <i class="fa-solid fa-check"></i>
                        </li>
                    @endforeach
                </ul>
                {{-- Real GET form the JS submits after picking a year - kept hidden --}}
                <form method="GET" action="{{ route('admin.awareness.settings.criteria') }}" style="display:none;">
                    <input type="hidden" name="fiscal_year" value="{{ $selectedYear }}">
                </form>
                {{-- No-JS fallback: plain native select, always works --}}
                <noscript>
                    <style>.year-dropdown-toggle, .year-dropdown-menu { display: none !important; }</style>
                    <form method="GET" action="{{ route('admin.awareness.settings.criteria') }}"
                        style="display: inline-flex; align-items: center; gap: 6px;">
                        <select name="fiscal_year" onchange="this.form.submit()" class="form-control form-control-sm">
                            @foreach ($years as $year)
                                <option value="{{ $year }}" {{ (string) $selectedYear === (string) $year ? 'selected' : '' }}>
                                    {{ $year }}
                                </option>
                            @endforeach
                        </select>
                        <button type="submit" class="btn btn-secondary btn-sm">ไปที่ปีนี้</button>
                    </form>
                </noscript>
            </div>
        </div>

        @include('partials.flash-alert')

        @if ($hasData)
            <div class="method-picker-card">
                <div class="method-picker-title"><i class="fa-solid fa-scale-balanced"></i> วิธีตั้งเกณฑ์ผ่าน/ไม่ผ่านของปีงบประมาณ {{ $selectedYear }}</div>
                <form method="POST" action="{{ route('admin.awareness.settings.criteria.pass-method.update') }}" class="method-picker-form">
                    @csrf
                    <input type="hidden" name="fiscal_year" value="{{ $selectedYear }}">
                    <div class="method-option-list">
                        @foreach ($passMethods as $methodKey => $methodMeta)
                            @php
                                $optionColor = $methodMeta['color'] ?? '#7c3aed';
                                $optionIcon = $methodMeta['icon'] ?? 'fa-circle-check';
                                $eyebrow = \Illuminate\Support\Str::before($methodMeta['title'], ':');
                                $mainTitle = trim(\Illuminate\Support\Str::after($methodMeta['title'], ':')) ?: $methodMeta['title'];
                            @endphp
                            <label class="method-option {{ $currentPassMethod === $methodKey ? 'is-selected' : '' }}"
                                style="--option-color: {{ $optionColor }};">
                                <input type="radio" name="method" value="{{ $methodKey }}"
                                    onchange="this.form.submit()" {{ $currentPassMethod === $methodKey ? 'checked' : '' }}>
                                <span class="method-option-head">
                                    <span class="method-option-head-left">
                                        <span class="method-option-icon"><i class="fa-solid {{ $optionIcon }}"></i></span>
                                        <span class="method-option-eyebrow">{{ $eyebrow }}</span>
                                    </span>
                                    <span class="method-option-active-badge"><i class="fa-solid fa-check"></i> กำลังใช้งานอยู่</span>
                                </span>
                                <span class="method-option-title">{{ $mainTitle }}</span>
                                <span class="method-option-desc">{{ $methodMeta['description'] }}</span>
                            </label>
                        @endforeach
                    </div>
                </form>
                @if ($currentPassMethod === \App\Models\AwarenessPassSetting::METHOD_SCORE && !$scoreConfigured)
                    <div class="method-picker-warning">
                        <i class="fa-solid fa-triangle-exclamation"></i>
                        ยังตั้งค่าคะแนนไม่ครบที่หน้า "ตั้งค่าคะแนนความตระหนักรู้" - จนกว่าจะครบ ผลผ่าน/ไม่ผ่านของปีนี้จะแสดงเป็น "รอเกณฑ์การประเมิน" ทุกที่
                    </div>
                @endif
            </div>
        @endif

        @if (!$hasData)
            <div class="no-data-notice">
                <div class="no-data-notice-icon"><i class="fa-solid fa-circle-info"></i></div>
                <div>
                    <div class="no-data-notice-title">ยังไม่มีข้อมูลของปีงบประมาณ {{ $selectedYear }}</div>
                    <div class="no-data-notice-desc">
                        ยังไม่เคยมีการอัปโหลดไฟล์ "การประเมินความตระหนักรู้" ของปีงบประมาณนี้เข้าระบบ (หรืออัปโหลดแล้วแต่ไม่มีข้อมูลเหลืออยู่) จึงยังไม่มีคำถามให้ตั้งค่า -
                        กรุณาอัปโหลดไฟล์ข้อมูลของปีนี้อย่างน้อย 1 ครั้งก่อน ระบบจะดึงคำถามจากหัวคอลัมน์ในไฟล์มาให้ตั้งค่าที่นี่โดยอัตโนมัติ
                    </div>
                </div>
            </div>
        @else
            @if ($currentPassMethod === \App\Models\AwarenessPassSetting::METHOD_SCORE)
                <div class="method-inactive-note">
                    <i class="fa-solid fa-circle-info"></i>
                    ปีงบประมาณนี้ใช้ "วิธีที่ 2: คิดจากคะแนนที่ตั้งค่าไว้" อยู่ จึงไม่แสดงเกณฑ์ข้อ 1/ข้อ 2 ที่นี่ (ไม่ถูกใช้ตัดสินผ่าน/ไม่ผ่านของปีงบประมาณนี้)
                    - หากต้องการตั้งค่าคะแนนแทน ไปที่หน้า
                    <a href="{{ route('admin.awareness.settings.scoring', ['fiscal_year' => $selectedYear]) }}" style="color:#0d9488; font-weight:700;">ตั้งค่าคะแนนความตระหนักรู้</a>
                </div>
            @else
            <div class="panel-list">
                @foreach ($criteriaMeta as $role => $meta)
                    @php $current = $currentByRole[$role] ?? collect(); @endphp
                    <div class="panel-picker-card" style="--panel-color: {{ $meta['color'] }}; --panel-bg: {{ $meta['color'] }}1f;"
                        data-toggle="modal" data-target="#criteriaModal{{ $loop->index }}">
                        <div class="panel-picker-content">
                            <div class="panel-picker-badge">เกณฑ์ข้อ {{ $meta['order'] }}</div>
                            <div class="panel-picker-title">
                                <i class="fas {{ $meta['icon'] }}" style="color: {{ $meta['color'] }};"></i>{{ $meta['title'] }}
                            </div>
                            <div class="panel-picker-desc">{{ $meta['description'] }}</div>
                            <div class="panel-picker-footer">
                                <div class="panel-picker-count">
                                    @if ($current->isNotEmpty())
                                        คำถามที่เลือกไว้ ({{ $current->count() }}):
                                    @else
                                        คำถามที่เลือกไว้:
                                    @endif
                                </div>
                                <div class="panel-picker-question-box {{ $current->isNotEmpty() ? '' : 'is-empty' }}">
                                    @if ($current->isNotEmpty())
                                        {{ $current->pluck('question_label')->implode(', ') }}
                                    @else
                                        ยังไม่ได้เลือก
                                    @endif
                                </div>
                                <div class="panel-picker-cta"><i class="fa-solid fa-gear"></i> ตั้งค่า <i class="fas fa-arrow-right"></i></div>
                            </div>
                        </div>
                    </div>
                @endforeach
            </div>

            @foreach ($criteriaMeta as $role => $meta)
                <div class="modal fade" id="criteriaModal{{ $loop->index }}" tabindex="-1" role="dialog">
                    <div class="modal-dialog modal-lg" role="document">
                        <div class="modal-content" style="--panel-color: {{ $meta['color'] }}; border-radius: 16px; overflow: hidden;">
                            <form method="POST" action="{{ route('admin.awareness.settings.criteria.update') }}">
                                @csrf
                                <input type="hidden" name="fiscal_year" value="{{ $selectedYear }}">
                                <input type="hidden" name="role" value="{{ $role }}">
                                <div class="modal-header">
                                    <h5 class="modal-title">
                                        <i class="fas {{ $meta['icon'] }}" style="color: {{ $meta['color'] }};"></i>
                                        เกณฑ์ข้อ {{ $meta['order'] }}: {{ $meta['title'] }}
                                    </h5>
                                    <button type="button" class="close" data-dismiss="modal" aria-label="Close"><span aria-hidden="true">&times;</span></button>
                                </div>
                                <div class="modal-body">
                                    <p class="text-muted small mb-3">{{ $meta['description'] }}</p>
                                    <p class="small font-weight-bold mb-2" style="color:#334155;">
                                        เลือกคำถามที่จะใช้เป็นเกณฑ์นี้ (เลือกได้มากกว่า 1 ข้อ - ผ่านข้อใดข้อหนึ่งที่เลือกไว้ก็นับว่าผ่านเกณฑ์นี้):
                                    </p>
                                    <div style="max-height: 420px; overflow-y: auto;">
                                        @foreach ($questions as $q)
                                            @php $isCurrentQuestion = ($currentByRole[$role] ?? collect())->contains('id', $q->id); @endphp
                                            <div class="question-check-row" style="flex-wrap: wrap;">
                                                <input type="checkbox" name="question_ids[]" value="{{ $q->id }}"
                                                    class="criteria-question-checkbox"
                                                    id="role{{ $loop->parent->index }}_q{{ $q->id }}"
                                                    {{ $isCurrentQuestion ? 'checked' : '' }}>
                                                <label for="role{{ $loop->parent->index }}_q{{ $q->id }}">
                                                    {{ $q->question_label }}
                                                    @if ($q->semantic_key && $q->semantic_key !== $role && in_array($q->semantic_key, \App\Models\SurveyYearMapping::CRITERIA_ROLES, true))
                                                        <span class="question-other-panel-note">
                                                            <i class="fa-solid fa-circle-info"></i>
                                                            <span>
                                                                ตอนนี้ใช้เป็น "<b>{{ $criteriaMeta[$q->semantic_key]['title'] ?? $q->semantic_key }}</b>" อยู่ -
                                                                เลือกที่นี่จะย้ายมาเกณฑ์นี้แทน
                                                            </span>
                                                        </span>
                                                    @elseif ($q->semantic_key && !in_array($q->semantic_key, \App\Models\SurveyYearMapping::CRITERIA_ROLES, true))
                                                        <span class="semantic-note">
                                                            <i class="fa-solid fa-circle-info"></i>
                                                            <span>ใช้อยู่สำหรับฟีเจอร์อื่น ({{ $q->semantic_key }}) - ไม่เกี่ยวกับหน้านี้</span>
                                                        </span>
                                                    @endif
                                                </label>
                                                <div class="answer-picker" data-question-id="{{ $q->id }}"
                                                    style="{{ $isCurrentQuestion ? '' : 'display:none;' }}">
                                                    <div class="answer-picker-label">
                                                        <i class="fa-solid fa-circle-check mr-1"></i>
                                                        เลือกคำตอบที่นับเป็น "ผ่าน" สำหรับคำถามนี้ (เลือกได้มากกว่า 1 คำตอบ):
                                                    </div>
                                                    @forelse ($distinctAnswers[$q->id] ?? [] as $val)
                                                        <label class="answer-check-pill">
                                                            <input type="checkbox" name="pass_values[{{ $q->id }}][]" value="{{ $val }}"
                                                                {{ in_array($val, $currentPassValues[$role][$q->id] ?? [], true) ? 'checked' : '' }}>
                                                            {{ $val }}
                                                        </label>
                                                    @empty
                                                        <span class="text-muted small">ยังไม่มีข้อมูลคำตอบของคำถามนี้ในปีงบประมาณนี้ (ยังไม่มีใครตอบคำถามนี้ในปีนี้)</span>
                                                    @endforelse
                                                </div>
                                            </div>
                                        @endforeach
                                    </div>
                                </div>
                                <div class="modal-footer">
                                    <button type="button" class="btn btn-secondary" data-dismiss="modal">ยกเลิก</button>
                                    <button type="submit" class="btn" style="background: {{ $meta['color'] }}; border-color: {{ $meta['color'] }}; color: #fff;">
                                        <i class="fa-solid fa-floppy-disk mr-1"></i> บันทึก
                                    </button>
                                </div>
                            </form>
                        </div>
                    </div>
                </div>
            @endforeach
            @endif

            <div class="behavior-section-title">
                <i class="fa-solid fa-bowl-food" style="color:#16a34a;"></i>
                คอลัมน์สำหรับการ์ด "พฤติกรรมการบริโภคโซเดียม" (หน้าแรก)
                <button type="button" class="preview-image-btn"
                    data-preview-src="{{ asset('images/Additional-photos/chart.png') }}"
                    data-preview-label="ตัวอย่าง: การ์ด &quot;พฤติกรรมการบริโภคโซเดียม&quot; ในหน้าแรก"
                    title="ดูตัวอย่างกราฟที่ตั้งค่านี้มีผล">
                    <i class="fa-solid fa-image"></i>
                </button>
            </div>
            <div class="behavior-section-sub">
                เลือกคำถามของปีงบประมาณนี้ที่จะใช้คำนวณสัดส่วนพฤติกรรมทั้ง 4 แถวบนการ์ด "พฤติกรรมการบริโภคโซเดียม" ในหน้าแรก - คำตอบที่นับเป็น
                "ทำพฤติกรรมที่ดี" ของแต่ละข้อถูกกำหนดตายตัวไว้แล้วในระบบ (ดูหมายเหตุใต้แต่ละคำถามในหน้าต่างตั้งค่า) เพียงแค่เลือกว่าจะใช้คำถามข้อไหนของปีนี้
                @if ($isFy69)
                    <span style="font-weight: 700; color: #0d9488;">- กำลังแสดงตัวเลือกสำหรับปีงบประมาณ 2569 เป็นต้นไป</span>
                @else
                    <span style="font-weight: 700; color: #0d9488;">- กำลังแสดงตัวเลือกสำหรับปีงบประมาณก่อน 2569</span>
                @endif
            </div>

            {{-- $behaviorMeta is already in row order (BEHAVIOR_META's own
                 declaration order, filtered by SurveyQuestionSettingsController
                 without re-indexing), so this single grid naturally lays the
                 4 cards out row-1..row-4 in reading order, all in a single
                 row of 4 on desktop width, each carrying its own "แถวที่ N"
                 badge instead of a separate heading above a lone card. --}}
            <div class="behavior-grid">
                @foreach ($behaviorMeta as $role => $meta)
                    @php $current = $currentByBehaviorRole[$role] ?? collect(); @endphp
                    <div class="behavior-card" style="--behavior-color: {{ $meta['color'] }}; --behavior-bg: {{ $meta['color'] }}1f;"
                        data-toggle="modal" data-target="#behaviorModal-{{ $role }}">
                        <div class="behavior-icon"><i class="fas {{ $meta['icon'] }}"></i></div>
                        <div class="behavior-card-badge">แถวที่ {{ $meta['row'] }}: {{ $meta['row_title'] }}</div>
                        <div class="behavior-card-title">{{ $meta['title'] }}</div>
                        <div class="behavior-card-footer">
                            <div class="behavior-status-label">
                                <span class="status-dot">&#9679;</span> สถานะการเลือก
                            </div>
                            <div class="behavior-status-pill {{ $current->isNotEmpty() ? 'is-selected' : '' }}"
                                title="{{ $current->isNotEmpty() ? $current->pluck('question_label')->implode(', ') : 'ยังไม่ได้เลือก' }}">
                                @if ($current->isNotEmpty())
                                    {{ $current->pluck('question_label')->implode(', ') }}
                                @else
                                    ยังไม่ได้เลือก
                                @endif
                            </div>
                        </div>
                    </div>
                @endforeach
            </div>

            @foreach ($behaviorMeta as $role => $meta)
                @php $current = $currentByBehaviorRole[$role] ?? collect(); @endphp
                <div class="modal fade" id="behaviorModal-{{ $role }}" tabindex="-1" role="dialog">
                    <div class="modal-dialog modal-lg" role="document">
                        <div class="modal-content" style="--panel-color: {{ $meta['color'] }}; border-radius: 16px; overflow: hidden;">
                            <form method="POST" action="{{ route('admin.awareness.settings.criteria.behavior.update') }}">
                                @csrf
                                <input type="hidden" name="fiscal_year" value="{{ $selectedYear }}">
                                <input type="hidden" name="role" value="{{ $role }}">
                                <div class="modal-header">
                                    <h5 class="modal-title">
                                        <i class="fas {{ $meta['icon'] }}" style="color: {{ $meta['color'] }};"></i>
                                        {{ $meta['title'] }}
                                    </h5>
                                    <button type="button" class="close" data-dismiss="modal" aria-label="Close"><span aria-hidden="true">&times;</span></button>
                                </div>
                                <div class="modal-body">
                                    <p class="text-muted small mb-2">{{ $meta['description'] }}</p>
                                    @php $defaultHint = $isFy69 ? ($meta['hint_fy69'] ?? []) : ($meta['hint_pre'] ?? []); @endphp
                                    @if (!empty($defaultHint))
                                        <div class="behavior-hint">
                                            <i class="fa-solid fa-circle-info"></i>
                                            <span>
                                                ค่าเริ่มต้นของคอลัมน์นี้: ระบบจะนับเป็น "ทำพฤติกรรมที่ดี" เมื่อคำตอบคือ
                                                <b>{{ implode(' หรือ ', $defaultHint) }}</b>
                                                - หากต้องการใช้คำตอบอื่นแทน ให้เลือก/ยกเลิกคำตอบในช่องสีเขียวด้านล่างของคำถามที่เลือกไว้
                                            </span>
                                        </div>
                                    @endif
                                    <p class="small font-weight-bold mt-3 mb-2" style="color:#334155;">
                                        เลือกคำถามที่จะใช้กับคอลัมน์นี้ (เลือกได้มากกว่า 1 ข้อ):
                                    </p>
                                    <div style="max-height: 420px; overflow-y: auto;">
                                        @foreach ($questions as $q)
                                            @php $isSelected = $current->contains('id', $q->id); @endphp
                                            <div class="question-check-row" style="flex-wrap: wrap;">
                                                <input type="checkbox" name="question_ids[]" value="{{ $q->id }}"
                                                    class="behavior-question-checkbox"
                                                    id="behavior-{{ $role }}-q{{ $q->id }}"
                                                    {{ $isSelected ? 'checked' : '' }}>
                                                <label for="behavior-{{ $role }}-q{{ $q->id }}">
                                                    {{ $q->question_label }}
                                                </label>
                                                <div class="answer-picker" data-question-id="{{ $q->id }}"
                                                    style="{{ $isSelected ? '' : 'display:none;' }}">
                                                    <div class="answer-picker-label">
                                                        <i class="fa-solid fa-circle-check mr-1"></i>
                                                        เลือกคำตอบที่นับเป็น "ทำพฤติกรรมที่ดี" สำหรับคำถามนี้ (เลือกได้มากกว่า 1 คำตอบ - ค่าเริ่มต้น
                                                        {{ !empty($defaultHint) ? implode(' หรือ ', $defaultHint) : 'ไม่มี' }} จะถูกใช้เองถ้าไม่เลือกอะไรเลย):
                                                    </div>
                                                    @forelse ($distinctAnswers[$q->id] ?? [] as $val)
                                                        <label class="answer-check-pill">
                                                            <input type="checkbox" name="pass_values[{{ $q->id }}][]" value="{{ $val }}"
                                                                {{ in_array($val, $currentBehaviorPassValues[$role][$q->id] ?? $defaultHint, true) ? 'checked' : '' }}>
                                                            {{ $val }}
                                                        </label>
                                                    @empty
                                                        <span class="text-muted small">ยังไม่มีข้อมูลคำตอบของคำถามนี้ในปีงบประมาณนี้ (ยังไม่มีใครตอบคำถามนี้ในปีนี้)</span>
                                                    @endforelse
                                                </div>
                                            </div>
                                        @endforeach
                                    </div>
                                </div>
                                <div class="modal-footer">
                                    <button type="button" class="btn btn-secondary" data-dismiss="modal">ยกเลิก</button>
                                    <button type="submit" class="btn" style="background: {{ $meta['color'] }}; border-color: {{ $meta['color'] }}; color: #fff;">
                                        <i class="fa-solid fa-floppy-disk mr-1"></i> บันทึก
                                    </button>
                                </div>
                            </form>
                        </div>
                    </div>
                </div>
            @endforeach
        @endif
    </div>

    <script>
        // Each modal's "which question(s)" checkboxes show/hide that one
        // question's own answer-value checklist (built server-side per
        // question in .answer-picker[data-question-id]) - scoped to the
        // checkbox's own <form> so the two criteria modals (which both list
        // the same question set) never affect each other. Several questions
        // can be checked at once now, so toggling one only shows/hides that
        // one question's own box - never touches any other question's box
        // in the same modal.
        document.querySelectorAll('.criteria-question-checkbox').forEach(function (checkbox) {
            checkbox.addEventListener('change', function () {
                var form = this.closest('form');
                if (!form) {
                    return;
                }
                var target = form.querySelector('.answer-picker[data-question-id="' + this.value + '"]');
                if (target) {
                    target.style.display = this.checked ? '' : 'none';
                }
            });
        });

        // Behavior modals: each question is its own independent checkbox
        // (unlike the criteria modals' radios above, several can be
        // checked at once), so toggling one only shows/hides that one
        // question's own answer-picker box - never touches any other
        // question's box in the same modal.
        document.querySelectorAll('.behavior-question-checkbox').forEach(function (checkbox) {
            checkbox.addEventListener('change', function () {
                var form = this.closest('form');
                if (!form) {
                    return;
                }
                var target = form.querySelector('.answer-picker[data-question-id="' + this.value + '"]');
                if (target) {
                    target.style.display = this.checked ? '' : 'none';
                }
            });
        });

        // Custom-styled replacement for the native <select> year picker -
        // a browser's own OPEN dropdown popup can't be restyled with CSS
        // across browsers, so this renders our own themed list instead and
        // submits the same hidden GET form the plain <select> used to
        // submit directly (falls back to a real <select> in <noscript>).
        document.querySelectorAll('.year-dropdown').forEach(function (wrapper) {
            var toggle = wrapper.querySelector('.year-dropdown-toggle');
            var menu = wrapper.querySelector('.year-dropdown-menu');
            var form = wrapper.querySelector('form');
            var hiddenInput = form ? form.querySelector('input[name="fiscal_year"]') : null;
            if (!toggle || !menu || !form || !hiddenInput) {
                return;
            }

            function closeMenu() {
                wrapper.classList.remove('is-open');
                toggle.setAttribute('aria-expanded', 'false');
            }

            function openMenu() {
                wrapper.classList.add('is-open');
                toggle.setAttribute('aria-expanded', 'true');
            }

            toggle.addEventListener('click', function (e) {
                e.stopPropagation();
                wrapper.classList.contains('is-open') ? closeMenu() : openMenu();
            });

            toggle.addEventListener('keydown', function (e) {
                if (e.key === 'Enter' || e.key === ' ') {
                    e.preventDefault();
                    wrapper.classList.contains('is-open') ? closeMenu() : openMenu();
                } else if (e.key === 'Escape') {
                    closeMenu();
                }
            });

            menu.querySelectorAll('.year-dropdown-option').forEach(function (opt) {
                opt.addEventListener('click', function () {
                    closeMenu();
                    if (opt.classList.contains('is-selected')) {
                        return;
                    }
                    hiddenInput.value = opt.getAttribute('data-value');
                    form.submit();
                });
            });

            document.addEventListener('click', function (e) {
                if (!wrapper.contains(e.target)) {
                    closeMenu();
                }
            });
        });
    </script>

    @include('partials.image-preview-modal')
@endsection
