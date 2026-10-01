@extends('layouts.admin')

@section('title', 'ตั้งค่าแดชบอร์ด - Admin Dashboard')

@section('header_title')
    <div class="page-header">
        <div class="page-header-accent"></div>
        <div class="page-header-text">
            <span class="page-header-title">ตั้งค่าแดชบอร์ด</span>
            <small class="page-header-subtitle">
                <i class="fas fa-circle-info"></i>
                เลือกกรอบที่ต้องการ แล้วเลือกคำถามที่จะแสดงในกรอบนั้น
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
            margin-bottom: 20px;
            display: flex;
            align-items: center;
            gap: 16px;
        }

        .hero-icon {
            width: 52px;
            height: 52px;
            min-width: 52px;
            border-radius: 14px;
            background: linear-gradient(135deg, #4f46e5 0%, #3730a3 100%);
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

        /* For a plain icon+text .hero-card with no year-picker sibling
           (the section headers below) - without this, the generic
           .hero-card { flex-wrap: wrap } rule above wraps the text onto
           its own line under the icon, since the text div has no
           flex-basis constraint of its own. */
        .hero-card-body {
            flex: 1;
            min-width: 0;
        }

        .hero-year-picker {
            display: flex;
            align-items: center;
            gap: 10px;
            background: #eef2ff;
            border: 1.5px solid #c7d2fe;
            border-radius: 14px;
            padding: 9px 16px;
            flex-shrink: 0;
            margin-left: auto;
        }

        .hero-year-picker i.fa-calendar-days {
            color: #4f46e5;
            font-size: 0.9rem;
        }

        .reset-mapping-btn {
            display: inline-flex;
            align-items: center;
            gap: 8px;
            flex-shrink: 0;
            background: #fff1f2;
            border: 1.5px solid #fecdd3;
            color: #be123c;
            font-weight: 700;
            font-size: 0.82rem;
            padding: 9px 16px;
            border-radius: 14px;
            cursor: pointer;
            transition: all 0.15s;
            white-space: nowrap;
        }
        .reset-mapping-btn:hover {
            background: #ffe4e6;
            border-color: #fb7185;
        }
        .reset-mapping-btn i { font-size: 0.85rem; }

        .hero-year-picker {
            position: relative;
        }

        .hero-year-picker .year-dropdown-label {
            margin: 0;
            font-weight: 700;
            font-size: 0.82rem;
            color: #4f46e5;
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
            box-shadow: 0 0 0 3px rgba(79, 70, 229, 0.25);
        }

        .hero-year-picker .year-dropdown-caret {
            color: #4f46e5;
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
            box-shadow: 0 12px 30px rgba(79, 70, 229, 0.2), 0 2px 8px rgba(0, 0, 0, 0.06);
            border: 1px solid #c7d2fe;
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
            background: #eef2ff;
            color: #4f46e5;
        }

        .hero-year-picker .year-dropdown-option.is-selected {
            background: linear-gradient(135deg, #4f46e5 0%, #3730a3 100%);
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


        .panel-list {
            display: grid;
            grid-template-columns: repeat(4, 1fr);
            align-items: stretch;
            gap: 16px;
            margin-bottom: 6px;
        }

        .panel-picker-card {
            background: #fff;
            border-radius: 18px;
            border: 1.5px solid #f1f5f9;
            box-shadow: 0 4px 15px rgba(0, 0, 0, 0.05);
            padding: 20px;
            display: flex;
            flex-direction: column;
            gap: 12px;
            cursor: pointer;
            transition: transform 0.15s ease, box-shadow 0.15s ease, border-color 0.15s ease;
        }

        .panel-picker-card:hover {
            transform: translateY(-3px);
            box-shadow: 0 10px 25px rgba(0, 0, 0, 0.09);
            border-color: var(--panel-color, #4f46e5);
        }

        .panel-picker-top {
            display: flex;
            align-items: center;
            justify-content: space-between;
            gap: 10px;
        }

        .panel-picker-icon {
            width: 44px;
            height: 44px;
            min-width: 44px;
            border-radius: 13px;
            background: var(--panel-bg, #eef2ff);
            color: var(--panel-color, #4f46e5);
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 1.15rem;
        }

        .panel-picker-content {
            flex: 1;
            min-width: 0;
            display: flex;
            flex-direction: column;
            gap: 10px;
        }

        .panel-picker-illustration {
            width: 92px;
            height: 92px;
            min-width: 92px;
            border-radius: 50%;
            background: var(--panel-bg, #eef2ff);
            display: flex;
            align-items: center;
            justify-content: center;
            overflow: hidden;
        }

        .panel-picker-illustration svg {
            width: 40px;
            height: 40px;
        }

        .panel-picker-badge {
            display: inline-block;
            font-size: 0.7rem;
            font-weight: 700;
            color: var(--panel-color, #4f46e5);
            background: var(--panel-bg, #eef2ff);
            border: 1px solid color-mix(in srgb, var(--panel-color, #4f46e5) 35%, #fff);
            border-radius: 999px;
            padding: 3px 12px;
            width: fit-content;
        }

        .panel-picker-title {
            font-weight: 800;
            font-size: 0.98rem;
            color: #1e293b;
            line-height: 1.45;
            min-height: 2.9em;
            display: -webkit-box;
            -webkit-line-clamp: 2;
            -webkit-box-orient: vertical;
            overflow: hidden;
        }

        .panel-picker-title i {
            margin-right: 6px;
        }

        .panel-picker-desc {
            font-size: 0.82rem;
            color: #64748b;
            line-height: 1.55;
            min-height: 3.1em;
            display: -webkit-box;
            -webkit-line-clamp: 2;
            -webkit-box-orient: vertical;
            overflow: hidden;
        }

        .panel-picker-footer {
            display: flex;
            flex-direction: column;
            align-items: stretch;
            gap: 10px;
            margin-top: auto;
            padding-top: 12px;
            border-top: 1px dashed #f1f5f9;
        }

        .panel-picker-count {
            display: block;
            width: fit-content;
            max-width: 100%;
            font-size: 0.78rem;
            font-weight: 700;
            color: var(--panel-color, #4f46e5);
            background: var(--panel-bg, #eef2ff);
            border: 1px solid var(--panel-color, #4f46e5);
            border-radius: 999px;
            padding: 5px 14px;
            overflow: hidden;
            text-overflow: ellipsis;
            white-space: nowrap;
            box-sizing: border-box;
        }

        .panel-picker-count.is-empty {
            color: #94a3b8;
            background: #f8fafc;
            border-color: #e2e8f0;
        }

        .panel-picker-count b {
            font-weight: 700;
        }

        .panel-status-footer {
            display: flex;
            flex-direction: column;
            align-items: stretch;
            gap: 8px;
            margin-top: auto;
            padding-top: 12px;
            border-top: 1px dashed #f1f5f9;
        }

        .panel-status-label {
            display: flex;
            align-items: center;
            gap: 6px;
            font-size: 0.76rem;
            font-weight: 600;
            color: #94a3b8;
            white-space: nowrap;
        }

        .panel-status-label .status-dot {
            color: #cbd5e1;
            font-size: 0.6rem;
        }

        .panel-status-pill {
            width: 100%;
            box-sizing: border-box;
            font-size: 0.76rem;
            font-weight: 700;
            line-height: 1.4;
            color: #94a3b8;
            background: #f8fafc;
            border: 1px solid #e2e8f0;
            border-radius: 10px;
            padding: 6px 12px;
            min-height: 2.5em;
            overflow: hidden;
            display: -webkit-box;
            -webkit-line-clamp: 2;
            -webkit-box-orient: vertical;
            white-space: normal;
            word-break: break-word;
        }

        .panel-status-pill.is-selected {
            color: var(--panel-color, #4f46e5);
            background: var(--panel-bg, #eef2ff);
            border-color: var(--panel-color, #4f46e5);
        }

        .panel-picker-cta {
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 6px;
            width: 100%;
            box-sizing: border-box;
            font-size: 0.82rem;
            font-weight: 700;
            color: #fff;
            background: var(--panel-color, #4f46e5);
            padding: 9px 16px;
            border-radius: 999px;
            white-space: nowrap;
        }

        @media (max-width: 1100px) {
            .panel-list {
                grid-template-columns: repeat(2, 1fr);
            }
        }

        @media (max-width: 560px) {
            .panel-list {
                grid-template-columns: 1fr;
            }
        }

        .modal-header {
            border-top: 5px solid var(--panel-color, #4f46e5);
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

        .polarity-picker {
            width: 100%;
            margin-top: 8px;
            margin-left: 26px;
            padding: 10px 12px;
            background: #f8fafc;
            border: 1px solid #e2e8f0;
            border-radius: 8px;
            display: flex;
            align-items: center;
            gap: 10px;
            flex-wrap: wrap;
        }

        .polarity-label {
            font-size: 0.76rem;
            font-weight: 600;
            color: #64748b;
            white-space: nowrap;
        }

        .polarity-select {
            width: auto;
            flex: 1;
            min-width: 220px;
            font-size: 0.8rem;
        }

        .extreme-value-picker {
            width: calc(100% - 26px);
            box-sizing: border-box;
            margin-top: 8px;
            margin-left: 26px;
            padding: 12px 14px;
            background: #f0fdf4;
            border: 1px solid #bbf7d0;
            border-radius: 10px;
        }

        .extreme-value-picker-label {
            font-size: 0.78rem;
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
    </style>

    <div class="settings-container">
        <div class="hero-card">
            <div class="hero-card-main">
                <div class="hero-icon"><i class="fa-solid fa-table-cells-large"></i></div>
                <div>
                    <div class="hero-title">ตั้งค่าแดชบอร์ด</div>
                    <div class="hero-sub">
                        ตั้งค่าทุกอย่างที่ใช้แสดงผลในหน้ารายงานความตระหนักรู้ (/awareness) ของปีงบประมาณนี้ ทั้งข้อมูลทั่วไปของผู้ตอบและกรอบแสดงพฤติกรรม
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
                <form method="GET" action="{{ route('admin.awareness.settings.dashboard') }}" style="display:none;">
                    <input type="hidden" name="fiscal_year" value="{{ $selectedYear }}">
                </form>
                {{-- No-JS fallback: plain native select, always works --}}
                <noscript>
                    <style>.year-dropdown-toggle, .year-dropdown-menu { display: none !important; }</style>
                    <form method="GET" action="{{ route('admin.awareness.settings.dashboard') }}"
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

            <button type="button" id="resetQuestionMappingBtn" class="reset-mapping-btn"
                title="ใช้เมื่อฟอร์มของปีงบประมาณนี้ถูกปรับโครงสร้างใหม่ (คอลัมน์/คำถามเปลี่ยนไปมาก) แล้วอัปโหลดไฟล์ไม่ผ่านเพราะระบบเทียบกับรูปแบบเดิม">
                <i class="fa-solid fa-arrow-rotate-left"></i> รีเซ็ตรูปแบบคำถาม
            </button>
            <form id="resetQuestionMappingForm" method="POST"
                action="{{ route('admin.awareness.settings.dashboard.reset') }}" style="display:none;">
                @csrf
                <input type="hidden" name="fiscal_year" value="{{ $selectedYear }}">
            </form>
        </div>

        @include('partials.flash-alert')

        <div class="hero-card">
            <div class="hero-icon" style="background: linear-gradient(135deg, #0ea5e9 0%, #0369a1 100%);">
                <i class="fa-solid fa-users-viewfinder"></i>
            </div>
            <div class="hero-card-body">
                <div class="hero-title">ข้อมูลทั่วไปของผู้ตอบ</div>
                <div class="hero-sub">
                    เลือกว่าคอลัมน์ไหนของไฟล์ Excel ปีนี้เก็บข้อมูลเพศ / ช่วงอายุ / ระดับการศึกษา เผื่อบางปีลำดับคอลัมน์ในไฟล์สลับตำแหน่งไปจากปกติ -
                    มีผลกับการอัปโหลดไฟล์ครั้งถัดไปของปีงบประมาณนี้เท่านั้น (ไม่ย้อนแก้ข้อมูลที่อัปโหลดไปแล้ว ต้องอัปโหลดไฟล์ซ้ำแบบ "แทนที่ข้อมูลเดิม"
                    อีกครั้งหลังตั้งค่าตรงนี้ จึงจะแก้ข้อมูลเดิมให้ถูกต้อง)
                </div>
            </div>
        </div>

        @if (!$hasData)
            <div class="no-data-notice">
                <div class="no-data-notice-icon"><i class="fa-solid fa-circle-info"></i></div>
                <div>
                    <div class="no-data-notice-title">ยังไม่มีข้อมูลของปีงบประมาณ {{ $selectedYear }}</div>
                    <div class="no-data-notice-desc">
                        ยังไม่เคยมีการอัปโหลดไฟล์ "การประเมินความตระหนักรู้" ของปีงบประมาณนี้เข้าระบบ (หรืออัปโหลดแล้วแต่ไม่มีข้อมูลเหลืออยู่) จึงยังไม่มีคอลัมน์ให้ตั้งค่า -
                        กรุณาอัปโหลดไฟล์ข้อมูลของปีนี้อย่างน้อย 1 ครั้งก่อน ระบบจะดึงคอลัมน์จากหัวไฟล์มาให้ตั้งค่าที่นี่โดยอัตโนมัติ
                    </div>
                </div>
            </div>
        @else
            <div class="panel-list">
                @foreach ($demographicFields as $fieldKey => $meta)
                    @php
                        $currentIndex = (int) $demographicColumnIndex[$fieldKey];
                        $currentColumn = $fixedColumns->first(fn ($c) => (int) $c->column_index === $currentIndex);
                    @endphp
                    <div class="panel-picker-card" style="--panel-color: {{ $meta['color'] }}; --panel-bg: {{ $meta['color'] }}1f;"
                        data-toggle="modal" data-target="#demographicModal{{ $loop->index }}">
                        <div class="panel-picker-top">
                            <div class="panel-picker-icon"><i class="fas {{ $meta['icon'] }}"></i></div>
                            <div class="panel-picker-badge">ข้อมูลทั่วไป</div>
                        </div>
                        <div class="panel-picker-content">
                            <div class="panel-picker-title">
                                {{ $meta['title'] }}
                                @if (!empty($meta['preview_image']))
                                    <button type="button" class="preview-image-btn"
                                        data-preview-src="{{ asset('images/Additional-photos/' . $meta['preview_image']) }}"
                                        data-preview-label="ตัวอย่าง: {{ $meta['title'] }}"
                                        title="ดูตัวอย่างกราฟที่ตั้งค่านี้มีผล"
                                        onclick="event.stopPropagation();">
                                        <i class="fa-solid fa-image"></i>
                                    </button>
                                @endif
                            </div>
                            <div class="panel-picker-desc">{{ $meta['description'] }}</div>
                            <div class="panel-picker-footer">
                                <div class="panel-picker-count">
                                    คอลัมน์ปัจจุบัน:
                                    @if ($currentColumn)
                                        {{ $currentColumn->question_label }}
                                    @else
                                        คอลัมน์ที่ {{ $currentIndex + 1 }}
                                    @endif
                                </div>
                                <div class="panel-picker-cta"><i class="fa-solid fa-gear"></i> ตั้งค่า <i class="fas fa-arrow-right"></i></div>
                            </div>
                        </div>
                    </div>
                @endforeach
            </div>

            @foreach ($demographicFields as $fieldKey => $meta)
                <div class="modal fade" id="demographicModal{{ $loop->index }}" tabindex="-1" role="dialog">
                    <div class="modal-dialog modal-lg" role="document">
                        <div class="modal-content" style="--panel-color: {{ $meta['color'] }}; border-radius: 16px; overflow: hidden;">
                            <form method="POST" action="{{ route('admin.awareness.settings.dashboard.demographic.update') }}">
                                @csrf
                                <input type="hidden" name="fiscal_year" value="{{ $selectedYear }}">
                                <input type="hidden" name="field_key" value="{{ $fieldKey }}">
                                <div class="modal-header">
                                    <h5 class="modal-title">
                                        <i class="fas {{ $meta['icon'] }}" style="color: {{ $meta['color'] }};"></i>
                                        {{ $meta['title'] }}
                                    </h5>
                                    <button type="button" class="close" data-dismiss="modal" aria-label="Close"><span aria-hidden="true">&times;</span></button>
                                </div>
                                <div class="modal-body">
                                    <p class="text-muted small mb-3">{{ $meta['description'] }}</p>
                                    <p class="small font-weight-bold mb-2" style="color:#334155;">
                                        เลือกคอลัมน์ของไฟล์ Excel ปีนี้ที่เก็บข้อมูลนี้จริง:
                                    </p>
                                    <div style="max-height: 420px; overflow-y: auto; overflow-x: hidden;">
                                        @foreach ($fixedColumns as $col)
                                            <div class="question-check-row">
                                                <input type="radio" name="column_index" value="{{ $col->column_index }}"
                                                    id="demo{{ $loop->parent->index }}_col{{ $col->column_index }}"
                                                    {{ (int) $demographicColumnIndex[$fieldKey] === (int) $col->column_index ? 'checked' : '' }}>
                                                <label for="demo{{ $loop->parent->index }}_col{{ $col->column_index }}">
                                                    {{ $col->question_label }}
                                                    <span class="text-muted" style="font-size:0.78rem;">(คอลัมน์ที่ {{ $col->column_index + 1 }})</span>
                                                </label>
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

        <div class="hero-card" style="margin-top: 8px;">
            <div class="hero-icon"><i class="fa-solid fa-table-cells-large"></i></div>
            <div class="hero-card-body">
                <div class="hero-title">
                    กรอบแสดงพฤติกรรม 4 กรอบ
                    <button type="button" class="preview-image-btn"
                        data-preview-src="{{ asset('images/Additional-photos/chart-health.png') }}"
                        data-preview-label="ตัวอย่าง: กรอบแสดงพฤติกรรมในหน้ารายงานความตระหนักรู้ (/awareness)"
                        title="ดูตัวอย่างกราฟที่ตั้งค่านี้มีผล">
                        <i class="fa-solid fa-image"></i>
                    </button>
                </div>
                <div class="hero-sub">
                    คลิกที่การ์ดกรอบที่ต้องการด้านล่าง เพื่อเลือกว่าคำถามข้อไหนของปีงบประมาณนี้จะไปแสดงผลในกรอบนั้น
                    ในหน้ารายงานความตระหนักรู้ (/awareness)
                </div>
            </div>
        </div>

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
            <div class="panel-list">
                @foreach ($panelOrder as $i => $panelNum)
                    @php $meta = $panelMeta[$panelNum]; @endphp
                    <div class="panel-picker-card" style="--panel-color: {{ $meta['color'] }}; --panel-bg: {{ $meta['color'] }}1f;"
                        data-toggle="modal" data-target="#panelModal{{ $panelNum }}">
                        <div class="panel-picker-top">
                            <div class="panel-picker-icon"><i class="fas {{ $meta['icon'] }}"></i></div>
                            <div class="panel-picker-badge">กรอบที่ {{ $i + 1 }}</div>
                        </div>
                        <div class="panel-picker-content">
                            <div class="panel-picker-title">{{ $panelTitles[$panelNum] }}</div>
                            <div class="panel-picker-desc">{{ $meta['description'] }}</div>
                            @php
                                $panelCount = $countsByPanel[$panelNum] ?? 0;
                                $panelQuestionLabels = ($questionsByPanel[$panelNum] ?? collect())->pluck('question_label')->implode(', ');
                            @endphp
                            <div class="panel-status-footer">
                                <div class="panel-status-label">
                                    <span class="status-dot">&#9679;</span> สถานะการเลือก:
                                </div>
                                <div class="panel-status-pill {{ $panelCount > 0 ? 'is-selected' : '' }}"
                                    title="{{ $panelQuestionLabels }}">
                                    @if ($panelCount > 0)
                                        {{ $panelQuestionLabels }}
                                    @else
                                        ยังไม่ได้เลือก
                                    @endif
                                </div>
                            </div>
                        </div>
                    </div>
                @endforeach
            </div>

            @foreach ($panelOrder as $i => $panelNum)
                @php $meta = $panelMeta[$panelNum]; @endphp
                <div class="modal fade" id="panelModal{{ $panelNum }}" tabindex="-1" role="dialog">
                    <div class="modal-dialog modal-xl" role="document">
                        <div class="modal-content" style="--panel-color: {{ $meta['color'] }}; border-radius: 16px; overflow: hidden;">
                            <form method="POST" action="{{ route('admin.awareness.settings.dashboard.panel.update') }}">
                                @csrf
                                <input type="hidden" name="fiscal_year" value="{{ $selectedYear }}">
                                <input type="hidden" name="panel" value="{{ $panelNum }}">
                                <div class="modal-header">
                                    <h5 class="modal-title">
                                        <i class="fas {{ $meta['icon'] }}" style="color: {{ $meta['color'] }};"></i>
                                        กรอบที่ {{ $i + 1 }}: {{ $panelTitles[$panelNum] }}
                                    </h5>
                                    <button type="button" class="close" data-dismiss="modal" aria-label="Close"><span aria-hidden="true">&times;</span></button>
                                </div>
                                <div class="modal-body">
                                    <p class="text-muted small mb-3">{{ $meta['description'] }}</p>
                                    <p class="small font-weight-bold mb-2" style="color:#334155;">เลือกคำถามที่จะแสดงในกรอบนี้:</p>
                                    <div style="max-height: 420px; overflow-y: auto; overflow-x: hidden;">
                                        @foreach ($questions as $q)
                                            <div class="question-check-row" style="flex-wrap: wrap;">
                                                <input type="checkbox" name="question_ids[]" value="{{ $q->id }}"
                                                    class="panel-question-checkbox"
                                                    id="panel{{ $panelNum }}_q{{ $q->id }}"
                                                    {{ (int) $q->dashboard_panel === $panelNum ? 'checked' : '' }}>
                                                <label for="panel{{ $panelNum }}_q{{ $q->id }}">
                                                    {{ $q->question_label }}
                                                    @if ($q->dashboard_panel && (int) $q->dashboard_panel !== $panelNum)
                                                        <span class="question-other-panel-note">
                                                            <i class="fa-solid fa-circle-info"></i>
                                                            <span>
                                                                ตอนนี้อยู่ในกรอบ "<b>{{ $panelTitles[$q->dashboard_panel] ?? '' }}</b>" -
                                                                ติ๊กที่นี่จะย้ายมากรอบนี้แทน
                                                            </span>
                                                        </span>
                                                    @endif
                                                </label>
                                                @php
                                                    // Real answers actually recorded this year for this
                                                    // question - backs the checkbox picker below so the
                                                    // admin can see (and directly pick from) the real
                                                    // wording instead of guessing blind.
                                                    $sampleAnswers = $distinctAnswers[$q->id] ?? [];
                                                    $selectedExtremeValues = (array) ($q->chart_polarity_values ?? []);
                                                @endphp
                                                <div class="polarity-settings-group" data-question-id="{{ $q->id }}"
                                                    style="width:100%; {{ (int) $q->dashboard_panel === $panelNum ? '' : 'display:none;' }}">
                                                    <div class="extreme-value-picker">
                                                        <div class="extreme-value-picker-label">
                                                            <i class="fa-solid fa-circle-check mr-1"></i>
                                                            เลือกคำตอบที่นับเป็น "ความถี่สูงสุด" ของคำถามนี้ (เลือกได้มากกว่า 1 คำตอบ - ถ้าไม่เลือกอะไรเลย
                                                            ระบบจะเดาจากคำตอบเองอัตโนมัติ):
                                                        </div>
                                                        @forelse ($sampleAnswers as $val)
                                                            <label class="answer-check-pill">
                                                                <input type="checkbox" name="polarity_values[{{ $q->id }}][]" value="{{ $val }}"
                                                                    {{ in_array($val, $selectedExtremeValues, true) ? 'checked' : '' }}>
                                                                {{ $val }}
                                                            </label>
                                                        @empty
                                                            <span class="text-muted small">ยังไม่มีข้อมูลคำตอบของคำถามนี้ในปีงบประมาณนี้</span>
                                                        @endforelse
                                                    </div>
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

        // Panel modals: the "คำตอบ...ของข้อนี้คือ" select + "ความถี่สูงสุด"
        // checkbox list only make sense for a question that's actually
        // checked into this panel, so they stay hidden until its own
        // checkbox is ticked (each row toggles only its own group, found
        // via the row's own data-question-id - never another question's).
        document.querySelectorAll('.panel-question-checkbox').forEach(function (checkbox) {
            checkbox.addEventListener('change', function () {
                var row = this.closest('.question-check-row');
                if (!row) {
                    return;
                }
                var group = row.querySelector('.polarity-settings-group[data-question-id="' + this.value + '"]');
                if (group) {
                    group.style.display = this.checked ? '' : 'none';
                }
            });
        });

        // "รีเซ็ตรูปแบบคำถาม" - destructive (clears every per-question
        // setting for this fiscal year: ร้อยละความตระหนักรู้ criteria,
        // ตั้งค่าแดชบอร์ด panels, ตั้งค่าคะแนนความตระหนักรู้ mappings), so it
        // always confirms first and spells out exactly what happens before
        // submitting the hidden form.
        var resetBtn = document.getElementById('resetQuestionMappingBtn');
        var resetForm = document.getElementById('resetQuestionMappingForm');
        if (resetBtn && resetForm) {
            resetBtn.addEventListener('click', function () {
                var year = @json($selectedYear);
                Swal.fire({
                    title: 'รีเซ็ตรูปแบบคำถามปีงบประมาณ ' + year + '?',
                    html: '<div style="text-align:left; font-size:0.9rem;">' +
                        '<p style="font-weight:700; color:#be123c;">ใช้เมื่อฟอร์มของปีนี้ถูกปรับโครงสร้างใหม่จริง แล้วอัปโหลดไฟล์ไม่ผ่านเพราะระบบเทียบกับรูปแบบคำถามเดิม</p>' +
                        '<p style="margin-top:10px;">การรีเซ็ตจะ:</p>' +
                        '<ul style="margin-top:6px; padding-left:20px;">' +
                        '<li>ล้างรายการคำถาม/คอลัมน์ทั้งหมดที่เคยบันทึกไว้ของปีนี้ - อัปโหลดไฟล์ครั้งถัดไปจะถูกมองเป็น "ปีใหม่" ที่ยังไม่มีรูปแบบเดิมให้เทียบ</li>' +
                        '<li>ล้างการตั้งค่า "ร้อยละความตระหนักรู้", "ตั้งค่าแดชบอร์ด" และ "ตั้งค่าคะแนนความตระหนักรู้" ของปีนี้ทั้งหมด (ต้องตั้งค่าใหม่หลังอัปโหลด)</li>' +
                        '</ul>' +
                        '<p style="margin-top:10px; font-weight:700; color:#475569;">* ข้อมูลผู้ตอบแบบสอบถามที่นำเข้าไว้แล้วจะไม่ถูกลบ - มีผลเฉพาะการตั้งค่ารูปแบบคำถามเท่านั้น</p>' +
                        '</div>',
                    icon: 'warning',
                    showCancelButton: true,
                    confirmButtonColor: '#be123c',
                    cancelButtonColor: '#64748b',
                    confirmButtonText: 'ยืนยันรีเซ็ต',
                    cancelButtonText: 'ยกเลิก',
                    width: '480px'
                }).then(function (result) {
                    if (result.isConfirmed) {
                        resetForm.submit();
                    }
                });
            });
        }
    </script>

    @include('partials.image-preview-modal')
@endsection
