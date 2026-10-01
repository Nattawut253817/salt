@extends('layouts.admin')

@section('title', 'รายละเอียด แบบประเมินลดการบริโภคเกลือ')

@section('header_title')
    <div class="page-header">
        <div class="page-header-accent"></div>
        <div class="page-header-text">
            <span class="page-header-title">รายละเอียด แบบประเมินลดการบริโภคเกลือ</span>
            <small class="page-header-subtitle">
                <i class="fas fa-circle-info"></i>
                แบบรายงานความก้าวหน้าผลการดำเนินงานลดการบริโภคเกลือและโซเดียม
            </small>
        </div>
    </div>
@endsection

@section('extra_css')
    <style>
        :root {
            --glass-bg: rgba(255, 255, 255, 0.85);
            --primary-pink: #f06292;
            --secondary-pink: #fdf2f6;
            --accent-pink: #f8bbd0;
            --pink-gradient: linear-gradient(135deg, #f06292 0%, #ec407a 100%);
        }

        .report-container {
            max-width: 1400px;
            margin: 0 auto 30px;
            padding: 0 15px;
        }

        .report-card {
            position: relative;
            background: #ffffff;
            border-radius: 16px;
            border: 1px solid #cbd5e1;
            box-shadow: 0 2px 4px rgba(0, 0, 0, 0.05);
            padding: 25px;
            margin-bottom: 25px;
        }

        .report-section {
            margin-bottom: 20px;
            padding-bottom: 20px;
            border-bottom: 1px solid #e2e8f0;
        }

        .report-section:last-child {
            border-bottom: none;
            margin-bottom: 0;
            padding-bottom: 0;
        }

        .section-title {
            display: flex;
            align-items: center;
            gap: 12px;
            margin-bottom: 12px;
        }

        .section-number {
            background: var(--primary-pink);
            color: white;
            width: 28px;
            height: 28px;
            border-radius: 6px;
            display: flex;
            align-items: center;
            justify-content: center;
            font-weight: 700;
            font-size: 0.9rem;
            flex-shrink: 0;
            margin-top: 0;
            box-shadow: none;
        }

        .section-label {
            font-size: 1rem;
            font-weight: 600;
            color: #334155;
            line-height: 1.4;
        }

        .report-detail-box {
            width: 100%;
            min-height: 60px;
            padding: 8px 15px 15px;
            border-radius: 8px;
            border: 1px solid #e2e8f0;
            background: #f8fafc;
            font-family: 'Noto Serif Thai', sans-serif;
            font-size: 0.95rem;
            color: #334155;
            line-height: 1.6;
            white-space: pre-wrap;
            text-align: left;
        }

        .year-quarter-section {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 15px;
            margin-bottom: 20px;
            padding-bottom: 15px;
            border-bottom: 1px solid #f1f5f9;
        }

        .info-grid-section {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 15px;
            margin-bottom: 15px;
        }

        .form-group {
            display: flex;
            flex-direction: column;
        }

        .form-group label {
            font-weight: 600;
            color: #475569;
            margin-bottom: 4px;
            font-size: 0.9rem;
        }

        .static-value {
            padding: 10px 12px;
            border-radius: 8px;
            border: 1px solid #e2e8f0;
            background: #f8fafc;
            font-family: 'Noto Serif Thai', sans-serif;
            font-size: 0.95rem;
            color: #1e293b;
            font-weight: 600;
        }

        .static-value-highlight {
            background: linear-gradient(135deg, #eff6ff 0%, #dbeafe 100%);
            border: 1.5px solid #93c5fd;
            color: #1e40af;
            font-size: 1.1rem;
            font-weight: 700;
        }

        .file-attachment {
            display: inline-flex;
            align-items: center;
            gap: 8px;
            margin-top: 8px;
            padding: 8px 12px;
            background: #f1f5f9;
            border-radius: 6px;
            color: #475569;
            font-size: 0.85rem;
            text-decoration: none;
            transition: all 0.2s;
        }

        .file-attachment:hover {
            background: #e2e8f0;
            color: #2563eb;
            text-decoration: none;
        }

        .btn-back {
            display: inline-flex;
            align-items: center;
            justify-content: center;
            gap: 8px;
            padding: 10px 20px;
            background: white;
            color: #475569;
            border: 1px solid #cbd5e1;
            border-radius: 8px;
            font-size: 0.95rem;
            font-weight: 600;
            cursor: pointer;
            transition: all 0.2s ease;
            text-decoration: none;
        }

        .btn-back:hover {
            background: #f1f5f9;
            color: #1e293b;
            text-decoration: none;
        }
    </style>
@endsection

@section('content')
    @php
        // ข้อมูลผู้ตอบแบบประเมินของรายการนี้โดยเฉพาะ - ใช้หลักเดียวกับหน้า
        // export PDF (resources/views/admin/salt-assessment-print.blade.php)
        // และหน้ารายละเอียด พชอ.ไต (kidney-dhb-detail.blade.php): ถ้าเคยนำเข้า
        // จากไฟล์ Word ให้ใช้ข้อมูลผู้รายงานที่บันทึกไว้ตอนนำเข้า
        // (reporter_metadata->_import_reporter) เพราะเป็นข้อมูลในอดีต ไม่ผูกกับ
        // บัญชีที่แถวนี้ถูกเก็บไว้ ถ้าไม่เคยนำเข้าก็ใช้ข้อมูลบัญชีเจ้าของแถวแทน
        $importReporter = $assessment->reporter_metadata['_import_reporter'] ?? null;
        $reporterName = $importReporter['name'] ?? ($assessment->user->name ?? null);
        $reporterPosition = $importReporter['position'] ?? ($assessment->user->User_position ?? null);
        $reporterPhoneRaw = $importReporter['phone'] ?? ($assessment->user->phone ?? null);
        $reporterEmail = $importReporter['email'] ?? ($assessment->user->email ?? null);

        $reporterPhoneDigits = preg_replace('/\D/', '', (string) ($reporterPhoneRaw ?? ''));
        $reporterPhone = strlen($reporterPhoneDigits) === 10
            ? substr($reporterPhoneDigits, 0, 3) . '-' . substr($reporterPhoneDigits, 3)
            : ($reporterPhoneRaw ?? null);
    @endphp
    <div class="report-container">
        <div style="background: linear-gradient(135deg, #f06292 0%, #ec407a 100%); border-radius: 16px; padding: 14px 20px; margin-bottom: 20px; display: flex; align-items: center; justify-content: space-between; gap: 15px; flex-wrap: wrap; box-shadow: 0 6px 16px rgba(236,64,122,0.3);">
            <div style="display: flex; align-items: center; gap: 15px;">
                <div style="width: 46px; height: 46px; border-radius: 12px; background: rgba(255,255,255,0.18); display: flex; align-items: center; justify-content: center; flex-shrink: 0;">
                    <i class="fas fa-building" style="color: #fff; font-size: 1.2rem;"></i>
                </div>
                <div>
                    <div style="color: rgba(255,255,255,0.75); font-size: 0.72rem; font-weight: 700; text-transform: uppercase; letter-spacing: 0.5px; margin-bottom: 2px;">กำลังดูข้อมูลของหน่วยงาน</div>
                    <div style="color: #fff; font-size: 1.25rem; font-weight: 800; line-height: 1.3;">
                    @if($assessment->user)
                        @if($assessment->user->User_rank_id == 2)
                            สำนักงานสาธารณสุขจังหวัด{{ $assessment->user->province->province_name ?? '' }}
                        @elseif($assessment->user->User_rank_id == 3)
                            สำนักงานสาธารณสุขอำเภอ{{ $assessment->user->district->district_name ?? '' }}
                        @elseif($assessment->user->User_rank_id == 4)
                            {{ $assessment->user->subdistrictHospital ? $assessment->user->subdistrictHospital->hospital_name : $assessment->user->Con_name }}
                        @elseif($assessment->user->User_rank_id == 5)
                            โรงพยาบาล{{ $assessment->user->hospital ? $assessment->user->hospital->hos_name : $assessment->user->Con_name }}
                        @else
                            {{ $assessment->user->name }}
                        @endif
                    @else
                        -
                    @endif
                    </div>
                </div>
            </div>

            <div style="display: flex; align-items: center; gap: 8px; flex-shrink: 0;">
                <a href="{{ route('admin.salt-assessment-list') }}" class="btn-back"
                    style="font-size: 0.78rem; padding: 7px 14px;">
                    <i class="fas fa-arrow-left"></i> กลับหน้ารายการ
                </a>
                <a href="{{ route('admin.salt-assessment.export-pdf', $assessment->id) }}" target="_blank"
                    class="btn text-white shadow"
                    style="background: linear-gradient(135deg, #f87171 0%, #ef4444 100%); border: none; border-radius: 12px; padding: 7px 14px; font-size: 0.78rem; transition: all 0.3s; font-weight: 500;">
                    <i class="fas fa-file-pdf mr-1"></i> Export PDF
                </a>
            </div>
        </div>

        <div class="report-card">
            <div class="year-quarter-section" style="margin-bottom: 0; padding-bottom: 0; border-bottom: none; grid-template-columns: 1fr 1fr auto auto;">
                <form action="{{ route('admin.salt-assessment.show', $assessment->id) }}" method="GET" style="display: contents;">
                    <div class="form-group">
                        <label for="filter_fiscal_year"><i class="fas fa-calendar-alt"></i> ปีงบประมาณ</label>
                        <select name="filter_fiscal_year" id="filter_fiscal_year" class="form-control static-value"
                            onchange="this.form.submit()"
                            style="padding: 8px 12px; height: auto; cursor: pointer; border: 1px solid #cbd5e1 !important; appearance: auto;">
                            @foreach ($availableYears as $year)
                                <option value="{{ $year }}" {{ $year == (session('filter_year') ?? $assessment->fiscal_year) ? 'selected' : '' }}>
                                    {{ $year }}
                                </option>
                            @endforeach
                        </select>
                    </div>
                    <div class="form-group">
                        <label for="filter_quarter"><i class="fas fa-clock"></i> ไตรมาส</label>
                        <select name="filter_quarter" id="filter_quarter" class="form-control static-value"
                            onchange="this.form.submit()"
                            style="padding: 8px 12px; height: auto; cursor: pointer; border: 1px solid #cbd5e1 !important; appearance: auto;">
                            @foreach ($availableQuarters as $q)
                                <option value="{{ $q }}" {{ $q == (session('filter_quarter') ?? $assessment->quarter) ? 'selected' : '' }}>ไตรมาสที่
                                    {{ $q }}
                                </option>
                            @endforeach
                        </select>
                    </div>
                </form>

                @if(auth()->user()->User_rank_id == 1)
                    <div class="form-group">
                        <label style="visibility: hidden;">ยืนยันข้อมูล</label>
                        <form action="{{ route('admin.salt-assessment.confirm', $assessment->id) }}" method="POST" style="display:contents;" onsubmit="return confirmToggleConfirmation(event, {{ $assessment->confirmed_at ? 'true' : 'false' }});">
                            @csrf
                            @if($assessment->confirmed_at)
                                <button type="submit"
                                    style="background: linear-gradient(135deg, #94a3b8 0%, #64748b 100%); color: #fff; border: none; border-radius: 8px; padding: 0 16px; white-space: nowrap; font-family: 'Noto Serif Thai', sans-serif; font-size: 0.85rem; font-weight: 600; box-shadow: 0 4px 10px rgba(100,116,139,0.3); height: 41px; display: flex; align-items: center; justify-content: center; gap: 8px; transition: all 0.2s ease; cursor: pointer;">
                                    <i class="fas fa-rotate-left"></i> ยกเลิกการยืนยัน
                                </button>
                            @else
                                <button type="submit"
                                    style="background: linear-gradient(135deg, #34d399 0%, #10b981 100%); color: #fff; border: none; border-radius: 8px; padding: 0 16px; white-space: nowrap; font-family: 'Noto Serif Thai', sans-serif; font-size: 0.85rem; font-weight: 600; box-shadow: 0 4px 10px rgba(16,185,129,0.3); height: 41px; display: flex; align-items: center; justify-content: center; gap: 8px; transition: all 0.2s ease; cursor: pointer;">
                                    <i class="fas fa-check-circle"></i> ยืนยันข้อมูล
                                </button>
                            @endif
                        </form>
                    </div>
                @endif

                <div class="form-group">
                    <label style="visibility: hidden;">ล้างตัวกรอง</label>
                    <a href="{{ route('admin.salt-assessment.show', $assessment->id) }}"
                        style="background: linear-gradient(135deg, #22d3ee 0%, #06b6d4 100%); color: #fff; border-radius: 8px; padding: 0 16px; white-space: nowrap; font-family: 'Noto Serif Thai', sans-serif; font-size: 0.85rem; font-weight: 600; box-shadow: 0 4px 10px rgba(6,182,212,0.3); height: 41px; display: flex; align-items: center; justify-content: center; gap: 8px; transition: all 0.2s ease; text-decoration: none;"
                        onmouseover="this.style.transform='translateY(-1px)'; this.style.boxShadow='0 6px 14px rgba(6,182,212,0.4)';"
                        onmouseout="this.style.transform='translateY(0)'; this.style.boxShadow='0 4px 10px rgba(6,182,212,0.3)';">
                        <i class="fas fa-sync-alt"></i> ล้างตัวกรอง
                    </a>
                </div>
            </div>

            @if (session('error'))
                <div
                    style="background: #fef2f2; color: #ef4444; border: 1px solid #fca5a5; padding: 12px 20px; border-radius: 12px; margin-top: 15px; margin-bottom: 0; font-weight: 600; font-size: 0.9rem;">
                    <i class="fas fa-exclamation-circle" style="margin-right: 8px;"></i> {{ session('error') }}
                </div>
            @endif

            @if (session('success'))
                <div
                    style="background: #ecfdf5; color: #047857; border: 1px solid #6ee7b7; padding: 12px 20px; border-radius: 12px; margin-top: 15px; margin-bottom: 0; font-weight: 600; font-size: 0.9rem;">
                    <i class="fas fa-check-circle" style="margin-right: 8px;"></i> {{ session('success') }}
                </div>
            @endif

            <div style="display: flex; align-items: center; justify-content: flex-end; flex-wrap: wrap; gap: 12px; margin-top: 15px;">
                @if($assessment->confirmed_at)
                    <span style="display: inline-flex; align-items: center; gap: 6px; background: #ecfdf5; color: #047857; border: 1px solid #6ee7b7; padding: 5px 12px; border-radius: 999px; font-size: 0.78rem; font-weight: 700;">
                        <i class="fas fa-check-circle"></i>
                        ยืนยันข้อมูลแล้ว โดย {{ $assessment->confirmedBy->name ?? '-' }}
                        เมื่อ {{ \Carbon\Carbon::parse($assessment->confirmed_at)->addYears(543)->format('d/m/Y H:i') }} น.
                    </span>
                @else
                    <span style="display: inline-flex; align-items: center; gap: 6px; background: #fff7ed; color: #c2410c; border: 1px solid #fdba74; padding: 5px 12px; border-radius: 999px; font-size: 0.78rem; font-weight: 700;">
                        <i class="fas fa-hourglass-half"></i> ยังไม่ได้ยืนยันข้อมูล
                    </span>
                @endif
                <span style="display: inline-flex; align-items: center; gap: 6px; color: #94a3b8; font-size: 0.76rem; font-style: italic; font-weight: 500; letter-spacing: 0.2px;">
                    <i class="fas fa-calendar-check" style="color: #cbd5e1;"></i>
                    บันทึกเมื่อ {{ \Carbon\Carbon::parse($assessment->created_at)->addYears(543)->format('d/m/Y H:i') }} น.
                </span>
            </div>
        </div>

        <div class="report-card" style="padding: 28px;">
            <div style="display: flex; align-items: center; justify-content: space-between; gap: 12px; flex-wrap: wrap; margin-bottom: 20px;">
                <div style="display: flex; align-items: center; gap: 14px;">
                    <div style="width: 52px; height: 52px; border-radius: 15px; background: linear-gradient(135deg, #f06292 0%, #ec407a 100%); display: flex; align-items: center; justify-content: center; flex-shrink: 0; box-shadow: 0 6px 14px rgba(236,64,122,0.3);">
                        <i class="fas fa-user-check" style="color: #fff; font-size: 1.25rem;"></i>
                    </div>
                    <div>
                        <div style="font-size: 1.05rem; font-weight: 700; color: #1e293b; line-height: 1.3;">ข้อมูลผู้ตอบแบบประเมิน</div>
                        <div style="font-size: 0.82rem; color: #64748b; font-weight: 500; margin-top: 1px;">รายละเอียดผู้บันทึกข้อมูลในระบบ</div>
                    </div>
                </div>
                @if($importReporter)
                    <span style="font-size: 0.7rem; font-weight: 700; letter-spacing: 0.3px; color: #ec407a; background: #fce4ec; border: 1px solid #f8bbd0; border-radius: 999px; padding: 4px 12px; flex-shrink: 0;">
                        <i class="fas fa-file-word"></i> จากไฟล์ที่นำเข้า
                    </span>
                @endif
            </div>
            <div style="border-top: 1px solid #f6e3ea; margin-bottom: 20px;"></div>
            <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(210px, 1fr)); gap: 14px;">
                <div style="display: flex; align-items: center; gap: 12px; background: #fdf5f8; border: 1px solid #fbe4ec; border-radius: 16px; padding: 14px 16px;">
                    <div style="width: 44px; height: 44px; border-radius: 12px; background: #fce4ec; display: flex; align-items: center; justify-content: center; flex-shrink: 0;">
                        <i class="fas fa-id-card" style="color: #ec407a; font-size: 1.05rem;"></i>
                    </div>
                    <div style="min-width: 0;">
                        <div style="font-size: 0.74rem; color: #94a3b8; font-weight: 600; margin-bottom: 2px;">ชื่อ-สกุล</div>
                        <div style="font-size: 0.95rem; color: #0f172a; font-weight: 700;">{{ $reporterName ?: '-' }}</div>
                    </div>
                </div>
                <div style="display: flex; align-items: center; gap: 12px; background: #fdf5f8; border: 1px solid #fbe4ec; border-radius: 16px; padding: 14px 16px;">
                    <div style="width: 44px; height: 44px; border-radius: 12px; background: #fce4ec; display: flex; align-items: center; justify-content: center; flex-shrink: 0;">
                        <i class="fas fa-briefcase" style="color: #d81b60; font-size: 1.05rem;"></i>
                    </div>
                    <div style="min-width: 0;">
                        <div style="font-size: 0.74rem; color: #94a3b8; font-weight: 600; margin-bottom: 2px;">ตำแหน่ง</div>
                        <div style="font-size: 0.95rem; color: #0f172a; font-weight: 700;">{{ $reporterPosition ?: '-' }}</div>
                    </div>
                </div>
                <div style="display: flex; align-items: center; gap: 12px; background: #fdf5f8; border: 1px solid #fbe4ec; border-radius: 16px; padding: 14px 16px;">
                    <div style="width: 44px; height: 44px; border-radius: 12px; background: #fce4ec; display: flex; align-items: center; justify-content: center; flex-shrink: 0;">
                        <i class="fas fa-phone" style="color: #c2185b; font-size: 1.05rem;"></i>
                    </div>
                    <div style="min-width: 0;">
                        <div style="font-size: 0.74rem; color: #94a3b8; font-weight: 600; margin-bottom: 2px;">เบอร์ติดต่อ</div>
                        <div style="font-size: 0.95rem; color: #0f172a; font-weight: 700;">{{ $reporterPhone ?: '-' }}</div>
                    </div>
                </div>
                <div style="display: flex; align-items: center; gap: 12px; background: #fdf5f8; border: 1px solid #fbe4ec; border-radius: 16px; padding: 14px 16px;">
                    <div style="width: 44px; height: 44px; border-radius: 12px; background: #fce4ec; display: flex; align-items: center; justify-content: center; flex-shrink: 0;">
                        <i class="fas fa-envelope" style="color: #ad1457; font-size: 1.05rem;"></i>
                    </div>
                    <div style="min-width: 0;">
                        <div style="font-size: 0.74rem; color: #94a3b8; font-weight: 600; margin-bottom: 2px;">E-MAIL</div>
                        <div style="font-size: 0.95rem; color: #0f172a; font-weight: 700; word-break: break-all;">{{ $reporterEmail ?: '-' }}</div>
                    </div>
                </div>
            </div>
        </div>

        <div class="report-card">
            @php
                $sections = [
                    1 => 'จัดทำบันทึกความเข้าใจ (MOU) หรือข้อตกลงความร่วมมือ หรือคำสั่งคณะกรรมการ/คณะทำงานขับเคลื่อนการดำเนินงานเฝ้าระวังและการดำเนินงานลดการบริโภคเกลือและโซเดียมร่วมกับหน่วยงานเครือข่ายระดับจังหวัด',
                    2 => 'การสำรวจปริมาณโซเดียมในอาหารด้วยเครื่องวัดความเค็ม (Salt meter) (สำหรับจังหวัดที่ยังไม่ได้ดำเนินการ)',
                    3 => 'จัดทำแผนปฏิบัติการลดการบริโภคเกลือและโซเดียมระดับจังหวัด ภายใต้กลยุทธ์ 5 ด้าน',
                    4 => 'การประเมินความตระหนักรู้ความเสี่ยง การบริโภคเกลือและโซเดียมระดับจังหวัด (เป้าหมายจังหวัดละ 500 คน)',
                ];
            @endphp

            @foreach ($sections as $num => $label)
                <div class="report-section">
                    <div class="section-title">
                        <div class="section-number">{{ $num }}</div>
                        <label class="section-label">{{ $label }}</label>
                    </div>
                    <div class="report-detail-box">{{ trim($assessment->{'ans_' . $num . '_detail'}) ?: '-' }}</div>
                    @if ($assessment->{'ans_' . $num . '_file'})
                        <a href="{{ asset('storage/' . $assessment->{'ans_' . $num . '_file'}) }}" target="_blank"
                            class="file-attachment">
                            <i class="fas fa-paperclip"></i> ไฟล์แนบ:
                            {{ basename($assessment->{'ans_' . $num . '_file'}) }}
                        </a>
                    @endif
                </div>
            @endforeach

            <div class="report-section">
                <div class="section-title" style="margin-bottom: 20px;">
                    <div class="section-number">5</div>
                    <label class="section-label">๕. การดำเนินงานตามแผนการดำเนินงานลด การบริโภคเกลือและโซเดียมระดับจังหวัด
                        ภายใต้กลยุทธ์ 5 ด้าน</label>
                </div>

                <div style="padding-left: 35px;">
                    @php
                        $sub5 = [
                            '1' =>
                                'การส่งเสริมให้ผู้บริโภค/ประชาชนมีความรู้และความตระหนักถึงความเสี่ยงต่อสุขภาพผ่านสื่อสารมวลชน/social media',
                            '2' => 'การปรับลดปริมาณเกลือและโซเดียม ในผลิตภัณฑ์อาหาร',
                            '3' => 'การปรับลดปริมาณเกลือและโซเดียม ในอาหารปรุงสุกที่จำหน่าย',
                            '4' =>
                                'การปรับสิ่งแวดล้อมที่เอื้อต่อการมีสุขภาพดีภายในและบริเวณโดยรอบโรงเรียน/โรงพยาบาล/สถานที่ทำงาน',
                            '5' => 'การดำเนินงานป้องกันควบคุมโรคไต ในชุมชน ผ่านกลไก พชอ. ตามแนวทางที่กำหนด',
                        ];
                    @endphp

                    @foreach ($sub5 as $id => $label)
                        <div style="margin-bottom: 20px; padding-bottom: 15px; border-bottom: 1px solid #f1f5f9;">
                            <label class="section-label"
                                style="font-size: 0.95rem; display: block; margin-bottom: 8px; color: #475569;">
                                5.{{ $id }} {{ $label }}
                            </label>
                            <div class="report-detail-box" style="min-height: 60px;">{{ trim($assessment->{'ans_5_' . $id . '_detail'}) ?: '-' }}</div>
                            @if ($assessment->{'ans_5_' . $id . '_file'})
                                <a href="{{ asset('storage/' . $assessment->{'ans_5_' . $id . '_file'}) }}" target="_blank"
                                    class="file-attachment">
                                    <i class="fas fa-paperclip"></i> ไฟล์แนบ:
                                    {{ basename($assessment->{'ans_5_' . $id . '_file'}) }}
                                </a>
                            @endif
                        </div>
                    @endforeach
                </div>
            </div>

            <div class="report-section">
                <div class="section-title">
                    <div class="section-number"><i class="fas fa-exclamation-triangle" style="font-size: 0.8rem;"></i></div>
                    <label class="section-label">ปัญหา อุปสรรค</label>
                </div>
                <div class="report-detail-box">{{ trim($assessment->problems) ?: '-' }}</div>
            </div>

            <div class="report-section">
                <div class="section-title">
                    <div class="section-number"><i class="fas fa-lightbulb" style="font-size: 0.8rem;"></i></div>
                    <label class="section-label">ข้อเสนอแนะ/โอกาสพัฒนา</label>
                </div>
                <div class="report-detail-box">{{ trim($assessment->suggestions) ?: '-' }}</div>
            </div>
        </div>
    </div>
@endsection

@section('extra_js')
    <script>
        // Non-native confirmation dialog for the ยืนยัน/ยกเลิกการยืนยัน
        // toggle button, using the SweetAlert2 modal already loaded in the
        // admin layout (same pattern as confirmDelete() on the list pages)
        // instead of the browser's native confirm(), which can silently
        // fail to submit on some browsers/extensions/popup-blocker setups.
        function confirmToggleConfirmation(e, isCurrentlyConfirmed) {
            e.preventDefault();
            const form = e.currentTarget;

            Swal.fire({
                title: isCurrentlyConfirmed ? 'ยกเลิกการยืนยันข้อมูล?' : 'ยืนยันข้อมูล?',
                text: isCurrentlyConfirmed
                    ? 'ยกเลิกการยืนยันข้อมูลนี้ใช่หรือไม่?'
                    : 'ยืนยันว่าตรวจสอบข้อมูลนี้เรียบร้อยแล้วใช่หรือไม่?',
                icon: 'question',
                showCancelButton: true,
                confirmButtonColor: isCurrentlyConfirmed ? '#64748b' : '#10b981',
                cancelButtonColor: '#94a3b8',
                confirmButtonText: isCurrentlyConfirmed
                    ? '<i class="fas fa-rotate-left mr-2"></i> ยกเลิกการยืนยัน'
                    : '<i class="fas fa-check-circle mr-2"></i> ใช่, ยืนยันข้อมูล',
                cancelButtonText: 'ปิด',
                reverseButtons: true
            }).then((result) => {
                if (result.isConfirmed) {
                    form.submit();
                }
            });

            return false;
        }
    </script>
@endsection
