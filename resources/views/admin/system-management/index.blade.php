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
            padding: 8px 12px;
            border-radius: 10px;
            background: #f0fdf4;
            border: 1px solid #dcfce7;
            font-family: 'Noto Serif Thai', 'TH Sarabun New', 'TH Sarabun PS', sans-serif;
            font-size: 0.8rem;
            color: #166534;
        }

        .sysmgmt-views-item b {
            font-variant-numeric: tabular-nums;
            font-size: 0.85rem;
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

        @foreach ($sections as $section)
            <div class="sysmgmt-row">
                <div class="sysmgmt-icon" style="background: {{ $section['color'] }};">
                    <i class="fas {{ $section['icon'] }}"></i>
                </div>
                <div>
                    <div class="sysmgmt-title">{{ $section['title'] }}</div>
                    <div class="sysmgmt-subtitle">{{ $section['subtitle'] }}</div>
                </div>
                <span class="sysmgmt-badge">เร็ว ๆ นี้</span>
            </div>
        @endforeach
    </div>

    {{-- ===== การเปิด/ปิดรับข้อมูลแบบประเมิน (รายปีงบประมาณ) ===== --}}
    <div style="margin-top: 20px; background: #fff; border-radius: 16px; border: 1px solid #eef2f7; box-shadow: 0 4px 16px rgba(15, 23, 42, 0.06); overflow: hidden;">

        {{-- หัวข้อส่วน --}}
        <div style="padding: 16px 22px 14px; border-bottom: 1px solid #f1f5f9; display: flex; align-items: center; gap: 12px;">
            <div style="width: 36px; height: 36px; border-radius: 10px; background: #7c3aed; display: flex; align-items: center; justify-content: center; color: #fff; font-size: 0.9rem; flex-shrink: 0;">
                <i class="fas fa-lock-open"></i>
            </div>
            <div>
                <div class="sysmgmt-title" style="font-size: 1rem;">การเปิด/ปิดรับข้อมูลแบบประเมิน</div>
                <div class="sysmgmt-subtitle">ควบคุมว่าปีงบประมาณใดสามารถบันทึก/แก้ไขข้อมูลได้ — ปีที่ปิดจะไม่สามารถบันทึกหรือแก้ไขได้</div>
            </div>
        </div>

        @foreach ($assessmentModules as $moduleKey => $module)
            {{-- แถวชื่อ module --}}
            <div style="padding: 14px 22px 6px; background: #fafafa; border-bottom: 1px solid #f1f5f9; display: flex; align-items: center; gap: 10px;">
                <div style="width: 30px; height: 30px; border-radius: 8px; background: {{ $module['color'] }}; display: flex; align-items: center; justify-content: center; color: #fff; font-size: 0.8rem; flex-shrink: 0;">
                    <i class="fas {{ $module['icon'] }}"></i>
                </div>
                <span class="sysmgmt-title" style="font-size: 0.88rem; color: #374151;">{{ $module['label'] }}</span>
            </div>

            @if ($module['years']->isEmpty())
                <div style="padding: 12px 22px 12px 64px; font-family: 'Noto Serif Thai', 'TH Sarabun New', sans-serif; font-size: 0.82rem; color: #9ca3af;">
                    ยังไม่มีปีงบประมาณในโมดูลนี้
                </div>
            @else
                @foreach ($module['years'] as $year)
                    @php $isOpen = $module['states'][$year] ?? true; @endphp
                    <div class="sysmgmt-row sysmgmt-assess-row">
                        <div class="sysmgmt-icon" style="background: {{ $isOpen ? '#16a34a' : '#6b7280' }}; width: 36px; height: 36px; border-radius: 9px; font-size: 0.85rem;">
                            <i class="fas {{ $isOpen ? 'fa-lock-open' : 'fa-lock' }}"></i>
                        </div>
                        <div>
                            <div class="sysmgmt-title" style="font-size: 0.9rem;">ปีงบประมาณ {{ $year }}</div>
                            <div class="sysmgmt-subtitle">{{ $isOpen ? 'รับข้อมูลได้ — สามารถบันทึกและแก้ไขได้ปกติ' : 'ปิดรับข้อมูล — ไม่สามารถบันทึกหรือแก้ไขข้อมูลได้' }}</div>
                        </div>
                        <div class="sysmgmt-toggle-cell">
                            <span class="sysmgmt-status {{ $isOpen ? 'is-on' : 'is-off' }}">
                                {{ $isOpen ? 'เปิดอยู่' : 'ปิดอยู่' }}
                            </span>
                            <form action="{{ route('admin.system-management.toggle-assessment') }}" method="POST"
                                  id="assessForm_{{ $moduleKey }}_{{ $year }}">
                                @csrf
                                <input type="hidden" name="module" value="{{ $moduleKey }}">
                                <input type="hidden" name="year" value="{{ $year }}">
                                <input type="hidden" name="enabled" id="assessEnabled_{{ $moduleKey }}_{{ $year }}" value="{{ $isOpen ? '1' : '0' }}">
                                <label class="sysmgmt-switch">
                                    <input type="checkbox" {{ $isOpen ? 'checked' : '' }}
                                        onchange="document.getElementById('assessEnabled_{{ $moduleKey }}_{{ $year }}').value = this.checked ? '1' : '0'; document.getElementById('assessForm_{{ $moduleKey }}_{{ $year }}').submit();">
                                    <span class="sysmgmt-switch-track"></span>
                                </label>
                            </form>
                        </div>
                    </div>
                @endforeach
            @endif

            @if (!$loop->last)
                <div style="border-top: 2px dashed #f1f5f9;"></div>
            @endif
        @endforeach
    </div>

    <style>
        .sysmgmt-assess-row {
            grid-template-columns: auto minmax(200px, 1fr) auto;
            padding: 12px 22px;
        }
        .sysmgmt-assess-row:not(:last-child) {
            border-bottom: 1px solid #f8fafc;
        }
    </style>

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
