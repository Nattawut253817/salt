@extends('layouts.admin')

@section('title', 'จัดการระบบ')

@section('header_title')
    <div class="page-header">
        <div class="page-header-accent"></div>
        <div class="page-header-text">
            <span class="page-header-title">จัดการระบบ</span>
            <small class="page-header-subtitle">
                <i class="fas fa-circle-info"></i>
                ตั้งค่าและจัดการภาพรวมของระบบ
            </small>
        </div>
    </div>
@endsection

@section('content')
    @include('partials.flash-alert')

    <div style="background: linear-gradient(135deg, #eff6ff 0%, #f0f9ff 100%); border: 2px solid #bfdbfe; border-radius: 14px; padding: 16px 20px; margin-bottom: 22px; display: flex; gap: 12px; align-items: flex-start;">
        <i class="fas fa-info-circle" style="color: #2563eb; font-size: 1.1rem; margin-top: 2px;"></i>
        <div style="font-family: 'Noto Serif Thai', 'TH Sarabun New', 'TH Sarabun PS', sans-serif; font-size: 0.85rem; font-weight: 600; color: #1e3a8a; line-height: 1.6;">
            <strong>หน้านี้อยู่ระหว่างการพัฒนา</strong> ด้านล่างคือรายการตั้งค่าที่วางแผนไว้ จะทยอยเปิดใช้งานทีละส่วน ยกเว้นหัวข้อ "ยืนยันตัวตนด้วย OTP", "เปิดรับสมัครสมาชิกระดับ สคร.", "จำนวนการเข้าชมเว็บไซต์" และ "บันทึกการใช้งานระบบ" ที่เปิดใช้งานแล้ว
        </div>
    </div>

    <style>
        .sysmgmt-row {
            display: grid;
            grid-template-columns: auto minmax(220px, 1fr) auto;
            align-items: center;
            gap: 18px;
            padding: 16px 22px;
        }

        .sysmgmt-row:not(:last-child) {
            border-bottom: 1px solid #f1f5f9;
        }

        .sysmgmt-icon {
            width: 44px;
            height: 44px;
            border-radius: 12px;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 1.1rem;
            color: #fff;
            flex-shrink: 0;
        }

        .sysmgmt-title {
            font-family: 'Noto Serif Thai', 'TH Sarabun New', 'TH Sarabun PS', sans-serif;
            font-weight: 700;
            font-size: 0.95rem;
            color: #1f2937;
        }

        .sysmgmt-subtitle {
            font-family: 'Noto Serif Thai', 'TH Sarabun New', 'TH Sarabun PS', sans-serif;
            font-size: 0.8rem;
            color: #6b7280;
            margin-top: 2px;
        }

        .sysmgmt-badge {
            font-family: 'Noto Serif Thai', 'TH Sarabun New', 'TH Sarabun PS', sans-serif;
            font-size: 0.75rem;
            font-weight: 700;
            color: #9ca3af;
            background: #f3f4f6;
            border-radius: 999px;
            padding: 5px 14px;
            white-space: nowrap;
        }

        .sysmgmt-status {
            font-family: 'Noto Serif Thai', 'TH Sarabun New', 'TH Sarabun PS', sans-serif;
            font-size: 0.75rem;
            font-weight: 700;
            border-radius: 999px;
            padding: 5px 14px;
            white-space: nowrap;
        }

        .sysmgmt-status.is-on {
            color: #15803d;
            background: #dcfce7;
        }

        .sysmgmt-status.is-off {
            color: #991b1b;
            background: #fee2e2;
        }

        .sysmgmt-toggle-cell {
            display: flex;
            align-items: center;
            gap: 12px;
            justify-self: end;
        }

        /* Simple checkbox-as-switch, no JS library needed. */
        .sysmgmt-switch {
            position: relative;
            display: inline-block;
            width: 44px;
            height: 24px;
            flex-shrink: 0;
        }

        .sysmgmt-switch input {
            opacity: 0;
            width: 0;
            height: 0;
        }

        .sysmgmt-switch-track {
            position: absolute;
            inset: 0;
            background: #d1d5db;
            border-radius: 999px;
            cursor: pointer;
            transition: background 0.2s ease;
        }

        .sysmgmt-switch-track::before {
            content: "";
            position: absolute;
            width: 18px;
            height: 18px;
            left: 3px;
            top: 3px;
            background: #fff;
            border-radius: 50%;
            transition: transform 0.2s ease;
            box-shadow: 0 1px 2px rgba(0, 0, 0, 0.25);
        }

        .sysmgmt-switch input:checked + .sysmgmt-switch-track {
            background: #16a34a;
        }

        .sysmgmt-switch input:checked + .sysmgmt-switch-track::before {
            transform: translateX(20px);
        }

        .sysmgmt-switch input:focus-visible + .sysmgmt-switch-track {
            outline: 2px solid #2563eb;
            outline-offset: 2px;
        }


        /* ตัวนับการเข้าชมเว็บไซต์ + ปุ่มรีเซ็ต */
        .sysmgmt-views {
            padding: 4px 22px 20px 84px;
            border-bottom: 1px solid #f1f5f9;
        }

        .sysmgmt-views-grid {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(210px, 1fr));
            gap: 8px 10px;
        }

        .sysmgmt-views-item {
            display: flex;
            align-items: center;
            justify-content: space-between;
            gap: 10px;
            padding: 9px 14px;
            border-radius: 10px;
            background: #f8fffe;
            border: 1px solid #d1fae5;
            border-left: 3px solid #16a34a;
            font-family: 'Noto Serif Thai', 'TH Sarabun New', 'TH Sarabun PS', sans-serif;
            font-size: 0.82rem;
            color: #166534;
            transition: background 0.12s;
        }
        .sysmgmt-views-item:hover { background: #f0fdf4; }

        .sysmgmt-views-item b {
            font-variant-numeric: tabular-nums;
            font-size: 0.87rem;
            font-weight: 700;
            color: #15803d;
        }

        .sysmgmt-reset-btn {
            display: inline-flex;
            align-items: center;
            gap: 8px;
            padding: 9px 16px;
            border-radius: 10px;
            border: 1.5px solid #fca5a5;
            background: #fff5f5;
            color: #b91c1c;
            font-family: 'Noto Serif Thai', 'TH Sarabun New', 'TH Sarabun PS', sans-serif;
            font-size: 0.82rem;
            font-weight: 700;
            cursor: pointer;
            white-space: nowrap;
            transition: background 0.2s ease, color 0.2s ease, border-color 0.2s ease, box-shadow 0.2s ease;
        }

        .sysmgmt-reset-btn:hover {
            background: #dc2626;
            border-color: #dc2626;
            color: #fff;
            box-shadow: 0 4px 12px rgba(220, 38, 38, 0.28);
        }

        .sysmgmt-reset-btn:focus-visible {
            outline: 2px solid #2563eb;
            outline-offset: 2px;
        }

        .sysmgmt-open-btn {
            display: inline-flex;
            align-items: center;
            gap: 8px;
            padding: 9px 16px;
            border-radius: 10px;
            border: 1.5px solid #c7d2fe;
            background: #eef2ff;
            color: #4338ca;
            font-family: 'Noto Serif Thai', 'TH Sarabun New', 'TH Sarabun PS', sans-serif;
            font-size: 0.82rem;
            font-weight: 700;
            text-decoration: none;
            white-space: nowrap;
            transition: background 0.2s ease, color 0.2s ease, box-shadow 0.2s ease;
        }

        .sysmgmt-open-btn:hover {
            background: #4f46e5;
            border-color: #4f46e5;
            color: #fff;
            text-decoration: none;
            box-shadow: 0 4px 12px rgba(79, 70, 229, 0.28);
        }

        .sysmgmt-open-btn:focus-visible {
            outline: 2px solid #2563eb;
            outline-offset: 2px;
        }

        @media (max-width: 700px) {
            .sysmgmt-views {
                padding: 0 22px 18px;
            }
        }

        @media (max-width: 700px) {
            .sysmgmt-row {
                grid-template-columns: auto 1fr;
            }

            .sysmgmt-badge,
            .sysmgmt-toggle-cell {
                grid-column: 1 / -1;
                justify-self: start;
            }
        }
    </style>

    <div style="background: #fff; border-radius: 16px; border: 1px solid #eef2f7; box-shadow: 0 4px 16px rgba(15, 23, 42, 0.06); overflow: hidden;">
        <div class="sysmgmt-row">
            <div class="sysmgmt-icon" style="background: #db2777;">
                <i class="fas fa-key"></i>
            </div>
            <div>
                <div class="sysmgmt-title">ยืนยันตัวตนด้วย OTP เมื่อเข้าสู่ระบบ</div>
                <div class="sysmgmt-subtitle">ให้ระบบส่งรหัส OTP ทางอีเมลเพื่อยืนยันตัวตนทุกครั้งที่เข้าสู่ระบบ</div>
            </div>
            <div class="sysmgmt-toggle-cell">
                <span class="sysmgmt-status {{ $otpEnabled ? 'is-on' : 'is-off' }}" id="otpStatusLabel">
                    {{ $otpEnabled ? 'เปิดใช้งาน' : 'ปิดใช้งาน' }}
                </span>
                <form action="{{ route('admin.system-management.toggle-otp') }}" method="POST" id="otpToggleForm">
                    @csrf
                    <input type="hidden" name="enabled" id="otpToggleHidden" value="{{ $otpEnabled ? '1' : '0' }}">
                    <label class="sysmgmt-switch">
                        <input type="checkbox" id="otpToggleCheckbox" {{ $otpEnabled ? 'checked' : '' }}
                            onchange="document.getElementById('otpToggleHidden').value = this.checked ? '1' : '0'; document.getElementById('otpToggleForm').submit();">
                        <span class="sysmgmt-switch-track"></span>
                    </label>
                </form>
            </div>
        </div>


        {{-- เปิด/ปิดรับสมัครสมาชิกระดับ สคร. (ตัวเลือก "สคร." ในหน้าสมัครสมาชิก) --}}
        <div class="sysmgmt-row">
            <div class="sysmgmt-icon" style="background: #4f46e5;">
                <i class="fas fa-user-plus"></i>
            </div>
            <div>
                <div class="sysmgmt-title">เปิดรับสมัครสมาชิกระดับ สคร.</div>
                <div class="sysmgmt-subtitle">
                    เปิดเมื่อต้องการให้เจ้าหน้าที่ สคร. สมัครสมาชิก เมื่อสมัครครบแล้วให้ปิด ตัวเลือก "สคร." จะหายจากหน้าสมัครสมาชิก และระบบไม่รับสมัครระดับนี้
                    · ขณะนี้มีผู้ใช้ สคร. {{ number_format($rank1UserCount) }} คน
                </div>
            </div>
            <div class="sysmgmt-toggle-cell">
                <span class="sysmgmt-status {{ $rank1RegisterEnabled ? 'is-on' : 'is-off' }}">
                    {{ $rank1RegisterEnabled ? 'เปิดรับสมัคร' : 'ปิดรับสมัคร' }}
                </span>
                @if ((int) auth()->user()->User_rank_id === 1)
                    <form action="{{ route('admin.system-management.toggle-rank1-register') }}" method="POST" id="rank1RegisterForm">
                        @csrf
                        <input type="hidden" name="enabled" id="rank1RegisterHidden" value="{{ $rank1RegisterEnabled ? '1' : '0' }}">
                        <label class="sysmgmt-switch">
                            <input type="checkbox" id="rank1RegisterCheckbox" {{ $rank1RegisterEnabled ? 'checked' : '' }}
                                onchange="document.getElementById('rank1RegisterHidden').value = this.checked ? '1' : '0'; document.getElementById('rank1RegisterForm').submit();">
                            <span class="sysmgmt-switch-track"></span>
                        </label>
                    </form>
                @endif
            </div>
        </div>

        {{-- จำนวนการเข้าชมเว็บไซต์ (แยกรายหน้า) + รีเซ็ต --}}
        <div class="sysmgmt-row" style="border-bottom: 0;">
            <div class="sysmgmt-icon" style="background: #16a34a;">
                <i class="fas fa-chart-simple"></i>
            </div>
            <div>
                <div class="sysmgmt-title">จำนวนการเข้าชมเว็บไซต์</div>
                <div class="sysmgmt-subtitle">
                    รวม {{ number_format($pageViewsTotal) }} ครั้ง
                    @if ($pageViewsSince)
                        @php
                            $pvMonths = ['ม.ค.', 'ก.พ.', 'มี.ค.', 'เม.ย.', 'พ.ค.', 'มิ.ย.', 'ก.ค.', 'ส.ค.', 'ก.ย.', 'ต.ค.', 'พ.ย.', 'ธ.ค.'];
                        @endphp
                        · เริ่มนับ {{ $pageViewsSince->day }} {{ $pvMonths[$pageViewsSince->month - 1] }} {{ $pageViewsSince->year + 543 }}
                    @endif
                </div>
            </div>
            <div class="sysmgmt-toggle-cell">
                @if ((int) auth()->user()->User_rank_id === 1)
                    <form action="{{ route('admin.system-management.reset-page-views') }}" method="POST" id="resetPageViewsForm">
                        @csrf
                        <button type="button" class="sysmgmt-reset-btn" id="resetPageViewsBtn">
                            <i class="fas fa-rotate-left"></i> รีเซ็ตจำนวนการเข้าชม
                        </button>
                    </form>
                @else
                    <span class="sysmgmt-badge">รีเซ็ตได้เฉพาะ สคร.</span>
                @endif
            </div>
        </div>
        <div class="sysmgmt-views">
            <div class="sysmgmt-views-grid">
                @foreach ($pageViews as $pv)
                    <div class="sysmgmt-views-item">
                        <span>{{ $pv['label'] }}</span>
                        <b>{{ number_format($pv['views']) }}</b>
                    </div>
                @endforeach
            </div>
        </div>

        {{-- บันทึกการใช้งานระบบ (log การเข้าใช้ของผู้ใช้แต่ละคน + เหตุการณ์สำคัญ) --}}
        <div class="sysmgmt-row">
            <div class="sysmgmt-icon" style="background: #f59e0b;">
                <i class="fas fa-clock-rotate-left"></i>
            </div>
            <div>
                <div class="sysmgmt-title">บันทึกการใช้งานระบบ</div>
                <div class="sysmgmt-subtitle">ประวัติการเข้าใช้ 5 ครั้งล่าสุดของผู้ใช้แต่ละคน และการแก้ไขข้อมูลสำคัญ เก็บย้อนหลัง 1 ปี</div>
            </div>
            <div class="sysmgmt-toggle-cell">
                @if ((int) auth()->user()->User_rank_id === 1)
                    <a href="{{ route('admin.system-management.activity-logs') }}" class="sysmgmt-open-btn">
                        <i class="fas fa-list-check"></i> เปิดดูบันทึก
                    </a>
                @else
                    <span class="sysmgmt-badge">ดูได้เฉพาะ สคร.</span>
                @endif
            </div>
        </div>

    </div>

    {{-- ===== การเปิด/ปิดรับข้อมูลแบบประเมิน (รายปีงบประมาณ) ===== --}}
    <div style="margin-top: 20px; background: #fff; border-radius: 16px; border: 1px solid #eef2f7; box-shadow: 0 4px 16px rgba(15, 23, 42, 0.06); overflow: hidden;">

        {{-- หัวข้อส่วน --}}
        <div style="padding: 14px 22px 12px; border-bottom: 1px solid #f1f5f9; display: flex; align-items: center; gap: 12px;">
            <div style="width: 34px; height: 34px; border-radius: 9px; background: #7c3aed; display: flex; align-items: center; justify-content: center; color: #fff; font-size: 0.85rem; flex-shrink: 0;">
                <i class="fas fa-lock-open"></i>
            </div>
            <div>
                <div class="sysmgmt-title" style="font-size: 0.95rem;">การเปิด/ปิดรับข้อมูลแบบประเมิน</div>
                <div class="sysmgmt-subtitle">ควบคุมว่าปีงบประมาณใดบันทึก/แก้ไขข้อมูลได้</div>
            </div>
        </div>

        @foreach ($assessmentModules as $moduleKey => $module)
            @php
                $openCount   = collect($module['states'])->filter()->count();
                $totalCount  = count($module['states']);
                $closedCount = $totalCount - $openCount;
                $isFirst     = $loop->first;
            @endphp

            {{-- Dropdown panel per module --}}
            <div class="amod-panel" id="amod_{{ $moduleKey }}"
                 data-open="{{ $isFirst ? 'true' : 'false' }}">

                <button type="button" class="amod-header"
                        onclick="toggleAmod('{{ $moduleKey }}')"
                        aria-expanded="{{ $isFirst ? 'true' : 'false' }}">
                    <span class="amod-stripe" style="background: {{ $module['color'] }};"></span>
                    <span class="amod-name">{{ $module['label'] }}</span>
                    <span class="amod-pills">
                        @if ($openCount > 0)
                            <span class="amod-pill amod-pill-open">{{ $openCount }} เปิด</span>
                        @endif
                        @if ($closedCount > 0)
                            <span class="amod-pill amod-pill-closed">{{ $closedCount }} ปิด</span>
                        @endif
                    </span>
                    <i class="fas fa-chevron-down amod-chevron"></i>
                </button>

                <div class="amod-body" style="{{ $isFirst ? '' : 'display:none;' }}">
                    @if ($module['years']->isEmpty())
                        <div class="amod-empty">ยังไม่มีปีงบประมาณในโมดูลนี้</div>
                    @else
                        <div class="ayr-wrap" style="--mod-color: {{ $module['color'] }};">

                            {{-- Pill year tabs --}}
                            {{-- Year status chips — one per year, colored by status, click to manage --}}
                            <div class="ayr-chips" role="tablist">
                                @foreach ($module['years'] as $year)
                                    @php $isOpen = $module['states'][$year] ?? true; @endphp
                                    <button class="ayr-chip {{ $isOpen ? 'ayr-chip-open' : 'ayr-chip-closed' }}"
                                            onclick="ayrSwitch('{{ $moduleKey }}', '{{ $year }}', this)"
                                            role="tab" aria-selected="false">
                                        <span class="ayr-chip-yr">{{ $year }}</span>
                                        <span class="ayr-chip-lbl">{{ $isOpen ? 'รับข้อมูลได้' : 'ปิดรับข้อมูล' }}</span>
                                    </button>
                                @endforeach
                            </div>
                            {{-- Hidden placeholder — JS looks for this element when toggling back to overview --}}
                            <div style="display:none;" id="ayr_grid_{{ $moduleKey }}" role="tabpanel"></div>

                            {{-- Detail panels — one per year --}}
                            @foreach ($module['years'] as $year)
                                @php $isOpen = $module['states'][$year] ?? true; @endphp
                                <div class="ayr-pane" id="ayr_det_{{ $moduleKey }}_{{ $year }}"
                                     style="display:none;" role="tabpanel">
                                    <div class="ayr-det-row">
                                        <div class="ayr-det-left">
                                            <span class="ayr-det-sdot {{ $isOpen ? 'dot-open' : 'dot-closed' }}"></span>
                                            <div>
                                                <div class="ayr-det-yr">{{ $year }}</div>
                                                <div class="ayr-det-status {{ $isOpen ? 'ds-open' : 'ds-closed' }}">
                                                    {{ $isOpen ? 'รับข้อมูลได้' : 'ปิดรับข้อมูล' }}
                                                </div>
                                            </div>
                                        </div>
                                        <form action="{{ route('admin.system-management.toggle-assessment') }}" method="POST" style="margin:0;">
                                            @csrf
                                            <input type="hidden" name="module" value="{{ $moduleKey }}">
                                            <input type="hidden" name="year"   value="{{ $year }}">
                                            <input type="hidden" name="enabled" value="{{ $isOpen ? '0' : '1' }}">
                                            <button type="submit" class="ayr-act-btn {{ $isOpen ? 'ab-close' : 'ab-open' }}">
                                                <i class="fas {{ $isOpen ? 'fa-lock' : 'fa-lock-open' }}"></i>
                                                {{ $isOpen ? 'ปิดรับข้อมูล' : 'เปิดรับข้อมูล' }}
                                            </button>
                                        </form>
                                    </div>
                                </div>
                            @endforeach

                        </div>
                    @endif
                </div>
            </div>

            @if (!$loop->last)
                <div class="amod-divider"></div>
            @endif
        @endforeach
    </div>

    <style>
        /* ── Module dropdown panel ─────────────────── */
        .amod-panel { position: relative; }

        .amod-divider {
            height: 1px;
            background: #f1f5f9;
            margin: 0 18px;
        }

        /* Header button */
        .amod-header {
            width: 100%;
            display: flex;
            align-items: center;
            gap: 12px;
            padding: 14px 20px 14px 0;
            background: none;
            border: none;
            cursor: pointer;
            text-align: left;
            transition: background 0.14s;
            position: relative;
            overflow: hidden;
        }
        .amod-header:hover { background: #f8fafc; }
        .amod-header:focus-visible {
            outline: 2px solid #6366f1;
            outline-offset: -2px;
        }

        /* Left color stripe — the distinctive structural move */
        .amod-stripe {
            width: 4px;
            align-self: stretch;
            border-radius: 0 3px 3px 0;
            flex-shrink: 0;
            min-height: 24px;
            margin-left: 0;
        }

        .amod-name {
            flex: 1;
            font-family: 'Noto Serif Thai', 'TH Sarabun New', sans-serif;
            font-size: 0.97rem;
            font-weight: 600;
            color: #1e293b;
            min-width: 0;
            overflow: hidden;
            text-overflow: ellipsis;
            white-space: nowrap;
        }

        /* Status pills */
        .amod-pills {
            display: flex;
            align-items: center;
            gap: 6px;
            flex-shrink: 0;
        }
        .amod-pill {
            font-family: 'Noto Serif Thai', 'TH Sarabun New', sans-serif;
            font-size: 0.78rem;
            font-weight: 600;
            padding: 3px 10px;
            border-radius: 999px;
        }
        .amod-pill-open   { background: #dcfce7; color: #15803d; }
        .amod-pill-closed { background: #f3f4f6; color: #6b7280; }

        .amod-chevron {
            font-size: 0.78rem;
            color: #cbd5e1;
            transition: transform 0.22s ease;
            flex-shrink: 0;
            margin-right: 4px;
        }
        .amod-panel[data-open="true"] .amod-chevron {
            transform: rotate(180deg);
        }

        /* Dropdown body */
        .amod-body { overflow: hidden; }

        /* ── Year picker (ayr) ─────────────────────────── */
        /* ── Pill year tabs ── */
        .ayr-wrap { padding: 10px 16px 14px; }
        .ayr-tabs { display: flex; gap: 6px; flex-wrap: wrap; margin-bottom: 14px; }
        /* ── Year status chips ── */
        .ayr-chips { display: flex; flex-wrap: wrap; gap: 8px; margin-bottom: 4px; }
        .ayr-chip {
            display: inline-flex; flex-direction: column; align-items: flex-start;
            padding: 9px 16px; border-radius: 12px; border: 2px solid transparent;
            cursor: pointer; background: none; text-align: left;
            transition: transform 0.12s ease, box-shadow 0.12s ease;
        }
        .ayr-chip:hover { transform: translateY(-2px); box-shadow: 0 4px 14px rgba(0,0,0,0.1); }
        .ayr-chip:focus-visible { outline: 2px solid #2563eb; outline-offset: 2px; }
        .ayr-chip-open  { background: #f0fdf4; border-color: #86efac; }
        .ayr-chip-open  .ayr-chip-yr  { color: #166534; }
        .ayr-chip-open  .ayr-chip-lbl { color: #16a34a; }
        .ayr-chip-closed { background: #f9fafb; border-color: #e5e7eb; }
        .ayr-chip-closed .ayr-chip-yr  { color: #9ca3af; }
        .ayr-chip-closed .ayr-chip-lbl { color: #d1d5db; }
        .ayr-chip-open.ayr-tab-active  { background: #16a34a; border-color: #15803d; box-shadow: 0 4px 14px rgba(22,163,74,0.3); }
        .ayr-chip-open.ayr-tab-active  .ayr-chip-yr,
        .ayr-chip-open.ayr-tab-active  .ayr-chip-lbl { color: #fff; }
        .ayr-chip-closed.ayr-tab-active { background: #6b7280; border-color: #4b5563; }
        .ayr-chip-closed.ayr-tab-active .ayr-chip-yr,
        .ayr-chip-closed.ayr-tab-active .ayr-chip-lbl { color: #fff; }
        .ayr-chip-yr {
            font-family: 'Noto Serif Thai', 'TH Sarabun New', 'TH Sarabun PS', sans-serif;
            font-size: 0.97rem; font-weight: 800; line-height: 1;
        }
        .ayr-chip-lbl {
            font-family: 'Noto Serif Thai', 'TH Sarabun New', 'TH Sarabun PS', sans-serif;
            font-size: 0.72rem; font-weight: 600; margin-top: 3px;
        }
        .ayr-pane { display: block; }
        /* ── Detail panel ── */
        .ayr-det-row { display: flex; align-items: center; justify-content: space-between; gap: 12px; flex-wrap: wrap; padding: 4px 0; }
        .ayr-det-left { display: flex; align-items: center; gap: 12px; }
        .ayr-det-sdot { width: 10px; height: 10px; border-radius: 50%; flex-shrink: 0; }
        .ayr-det-yr {
            font-family: 'Noto Serif Thai', 'TH Sarabun New', 'TH Sarabun PS', sans-serif;
            font-size: 2rem; font-weight: 800; color: #1e293b; line-height: 1;
        }
        .ayr-det-status {
            font-family: 'Noto Serif Thai', 'TH Sarabun New', 'TH Sarabun PS', sans-serif;
            font-size: 0.82rem; font-weight: 600; margin-top: 4px;
        }
        .ds-open   { color: #15803d; }
        .ds-closed { color: #9ca3af; }
        .ayr-act-btn {
            font-family: 'Noto Serif Thai', 'TH Sarabun New', 'TH Sarabun PS', sans-serif;
            font-size: 0.88rem; font-weight: 700; padding: 9px 22px;
            border-radius: 10px; border: none; cursor: pointer;
            display: inline-flex; align-items: center; gap: 8px;
            transition: opacity 0.14s, box-shadow 0.14s; white-space: nowrap;
        }
        .ayr-act-btn:hover { opacity: 0.84; box-shadow: 0 4px 12px rgba(0,0,0,0.14); }
        .ab-open  { background: var(--mod-color, #7c3aed); color: #fff; }
        .ab-close { background: #f1f5f9; color: #6b7280; border: 1.5px solid #e2e8f0; }

        .amod-empty {
            padding: 12px 22px;
            font-family: 'Noto Serif Thai', 'TH Sarabun New', sans-serif;
            font-size: 0.9rem;
            color: #9ca3af;
        }
    </style>

    <script>
        function toggleAmod(key) {
            var panel  = document.getElementById('amod_' + key);
            var body   = panel.querySelector('.amod-body');
            var btn    = panel.querySelector('.amod-header');
            var isOpen = panel.getAttribute('data-open') === 'true';
            if (isOpen) {
                body.style.display = 'none';
                panel.setAttribute('data-open', 'false');
                btn.setAttribute('aria-expanded', 'false');
            } else {
                body.style.display = '';
                panel.setAttribute('data-open', 'true');
                btn.setAttribute('aria-expanded', 'true');
            }
        }

        function ayrSwitch(moduleKey, target, tabEl) {
            var isActive = tabEl.classList.contains('ayr-tab-active');
            var grid = document.getElementById('ayr_grid_' + moduleKey);

            // Deselect all chips
            tabEl.closest('.ayr-chips').querySelectorAll('.ayr-chip').forEach(function(t) {
                t.classList.remove('ayr-tab-active');
                t.setAttribute('aria-selected', 'false');
            });

            // Hide all detail panels
            document.querySelectorAll('[id^="ayr_det_' + moduleKey + '_"]').forEach(function(el) {
                el.style.display = 'none';
            });

            if (isActive) {
                // Clicking active tab → go back to summary grid
                if (grid) grid.style.display = '';
            } else {
                tabEl.classList.add('ayr-tab-active');
                tabEl.setAttribute('aria-selected', 'true');
                if (grid) grid.style.display = 'none';
                var det = document.getElementById('ayr_det_' + moduleKey + '_' + target);
                if (det) det.style.display = '';
            }
        }
    </script>

    <script>
        var resetBtn = document.getElementById('resetPageViewsBtn');
        if (resetBtn) resetBtn.addEventListener('click', function () {
            var form = document.getElementById('resetPageViewsForm');
            Swal.fire({
                icon: 'warning',
                title: 'รีเซ็ตจำนวนการเข้าชม?',
                html: 'จำนวนการเข้าชมของทุกหน้าจะกลับเป็น <b>0</b> และเริ่มนับใหม่ตั้งแต่ตอนนี้<br>ไม่สามารถย้อนกลับได้',
                showCancelButton: true,
                confirmButtonText: 'รีเซ็ต',
                cancelButtonText: 'ยกเลิก',
                confirmButtonColor: '#dc2626',
                reverseButtons: true,
                focusCancel: true
            }).then(function (result) {
                if (result.isConfirmed) { form.submit(); }
            });
        });
    </script>
@endsection
