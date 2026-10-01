@extends('layouts.admin')

@section('title', 'รายละเอียดข้อมูล พชอ.ไต')

@section('header_title')
    <div class="page-header">
        <div class="page-header-accent"></div>
        <div class="page-header-text">
            <span class="page-header-title">รายละเอียดข้อมูล พชอ.ไต</span>
            <small class="page-header-subtitle">
                <i class="fas fa-circle-info"></i>
                แบบรายงานความก้าวหน้าผลการดำเนินงานป้องกันควบคุมโรคไม่ติดต่อเรื้อรัง/โรคไตในชุมชน
            </small>
        </div>
    </div>
@endsection

@section('extra_css')
    <style>
        /* Reuse premium styles from kidney dhb form */
        :root {
            --glass-bg: rgba(255, 255, 255, 0.85);
            --primary-blue: #2563eb;
            --secondary-blue: #eff6ff;
            --accent-blue: #bfdbfe;
            --blue-gradient: linear-gradient(135deg, #2563eb 0%, #1e40af 100%);
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
            background: var(--primary-blue);
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
            padding: 15px 15px;
            border-radius: 8px;
            border: 1px solid #e2e8f0;
            background: #f8fafc;
            font-family: 'Noto Serif Thai', sans-serif;
            font-size: 0.95rem;
            color: #334155;
            line-height: 1.6;
            white-space: pre-wrap;
            /* Preserve line breaks */
        }

        .year-quarter-section {
            display: grid;
            grid-template-columns: 1fr 1fr 1fr;
            gap: 15px;
            margin-bottom: 20px;
            padding-bottom: 15px;
            border-bottom: 1px solid #f1f5f9;
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
            /* margin-bottom: 20px; Remove this for alignment */
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
        // export PDF (resources/views/admin/kidney-dhb-print.blade.php):
        // ถ้าเคยนำเข้าจากไฟล์ Word ให้ใช้ข้อมูลผู้รายงานที่บันทึกไว้ตอนนำเข้า
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
        <div style="background: linear-gradient(135deg, #2563eb 0%, #1e40af 100%); border-radius: 16px; padding: 14px 20px; margin-bottom: 20px; display: flex; align-items: center; justify-content: space-between; gap: 15px; flex-wrap: wrap; box-shadow: 0 6px 16px rgba(37,99,235,0.25);">
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
                    @if(!empty($assessment->operating_area))
                        <div style="color: rgba(255,255,255,0.85); font-size: 0.82rem; font-weight: 600; margin-top: 4px; display: flex; align-items: center; gap: 5px;">
                            <i class="fas fa-map-marker-alt"></i> พื้นที่การดำเนินงาน: {{ $assessment->operating_area }}
                        </div>
                    @endif
                </div>
            </div>

            <div style="display: flex; align-items: center; gap: 8px; flex-shrink: 0;">
                <a href="{{ route('admin.kidney-dhb-list') }}" class="btn-back"
                    style="font-size: 0.78rem; padding: 7px 14px;">
                    <i class="fas fa-arrow-left"></i> กลับหน้ารายการ
                </a>
                <a href="{{ route('admin.kidney-dhb.export-pdf', $assessment->id) }}" target="_blank"
                    class="btn text-white shadow"
                    style="background: linear-gradient(135deg, #f87171 0%, #ef4444 100%); border: none; border-radius: 12px; padding: 7px 14px; font-size: 0.78rem; transition: all 0.3s; font-weight: 500;">
                    <i class="fas fa-file-pdf mr-1"></i> Export PDF
                </a>
            </div>
        </div>

        <div class="report-card">
            <div class="year-quarter-section"
                style="margin-bottom: 0; padding-bottom: 0; border-bottom: none; grid-template-columns: 1fr 1fr auto auto;">
                <form action="{{ route('admin.kidney-dhb.show', $assessment->id) }}" method="GET" style="display: contents;">
                    <div class="form-group">
                        <label for="filter_fiscal_year"><i class="fas fa-calendar-alt"></i> ปีงบประมาณ</label>
                        <select name="filter_fiscal_year" id="filter_fiscal_year" class="form-control static-value"
                            onchange="this.form.submit()"
                            style="padding: 8px 12px; height: auto; cursor: pointer; border: 1px solid #cbd5e1 !important; appearance: auto;">
                            @foreach($availableYears as $year)
                                <option value="{{ $year }}" {{ $year == (session('filter_year') ?? $assessment->fiscal_year) ? 'selected' : '' }}>{{ $year }}
                                </option>
                            @endforeach
                        </select>
                    </div>
                    <div class="form-group">
                        <label for="filter_quarter"><i class="fas fa-clock"></i> ไตรมาส</label>
                        <select name="filter_quarter" id="filter_quarter" class="form-control static-value"
                            onchange="this.form.submit()"
                            style="padding: 8px 12px; height: auto; cursor: pointer; border: 1px solid #cbd5e1 !important; appearance: auto;">
                            @foreach($availableQuarters as $q)
                                <option value="{{ $q }}" {{ $q == (session('filter_quarter') ?? $assessment->quarter) ? 'selected' : '' }}>ไตรมาสที่ {{ $q }}
                                </option>
                            @endforeach
                        </select>
                    </div>
                </form>

                @if(auth()->user()->User_rank_id == 1)
                    <div class="form-group">
                        <label style="visibility: hidden;">ยืนยันข้อมูล</label>
                        <form action="{{ route('admin.kidney-dhb.confirm', $assessment->id) }}" method="POST" style="display:contents;" onsubmit="return confirmToggleConfirmation(event, {{ $assessment->confirmed_at ? 'true' : 'false' }});">
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
                    <label style="visibility: hidden;">รีเซ็ต</label>
                    <a href="{{ route('admin.kidney-dhb.show', $assessment->id) }}"
                        style="background: linear-gradient(135deg, #22d3ee 0%, #06b6d4 100%); color: #fff; border-radius: 8px; padding: 0 16px; white-space: nowrap; font-family: 'Noto Serif Thai', sans-serif; font-size: 0.85rem; font-weight: 600; box-shadow: 0 4px 10px rgba(6,182,212,0.3); height: 41px; display: flex; align-items: center; justify-content: center; gap: 8px; transition: all 0.2s ease; text-decoration: none;"
                        onmouseover="this.style.transform='translateY(-1px)'; this.style.boxShadow='0 6px 14px rgba(6,182,212,0.4)';"
                        onmouseout="this.style.transform='translateY(0)'; this.style.boxShadow='0 4px 10px rgba(6,182,212,0.3)';">
                        <i class="fas fa-sync-alt"></i> รีเซ็ต
                    </a>
                </div>
            </div>

            @if(session('error'))
                <div
                    style="background: #fef2f2; color: #ef4444; border: 1px solid #fca5a5; padding: 12px 20px; border-radius: 12px; margin-top: 15px; margin-bottom: 0; font-weight: 600; font-size: 0.9rem;">
                    <i class="fas fa-exclamation-circle" style="margin-right: 8px;"></i> {{ session('error') }}
                </div>
            @endif

            @if(session('success'))
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
                    <div style="width: 52px; height: 52px; border-radius: 15px; background: #4f46e5; display: flex; align-items: center; justify-content: center; flex-shrink: 0; box-shadow: 0 6px 14px rgba(79,70,229,0.28);">
                        <i class="fas fa-user-check" style="color: #fff; font-size: 1.25rem;"></i>
                    </div>
                    <div>
                        <div style="font-size: 1.05rem; font-weight: 700; color: #1e293b; line-height: 1.3;">ข้อมูลผู้ตอบแบบประเมิน</div>
                        <div style="font-size: 0.82rem; color: #64748b; font-weight: 500; margin-top: 1px;">รายละเอียดผู้บันทึกข้อมูลในระบบ</div>
                    </div>
                </div>
                @if($importReporter)
                    <span style="font-size: 0.7rem; font-weight: 700; letter-spacing: 0.3px; color: #7c3aed; background: #f5f3ff; border: 1px solid #ddd6fe; border-radius: 999px; padding: 4px 12px; flex-shrink: 0;">
                        <i class="fas fa-file-word"></i> จากไฟล์ที่นำเข้า
                    </span>
                @endif
            </div>
            <div style="border-top: 1px solid #eef1f5; margin-bottom: 20px;"></div>
            <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(210px, 1fr)); gap: 14px;">
                <div style="display: flex; align-items: center; gap: 12px; background: #f8fafc; border: 1px solid #eef1f5; border-radius: 16px; padding: 14px 16px;">
                    <div style="width: 44px; height: 44px; border-radius: 12px; background: #eff6ff; display: flex; align-items: center; justify-content: center; flex-shrink: 0;">
                        <i class="fas fa-id-card" style="color: #2563eb; font-size: 1.05rem;"></i>
                    </div>
                    <div style="min-width: 0;">
                        <div style="font-size: 0.74rem; color: #94a3b8; font-weight: 600; margin-bottom: 2px;">ชื่อ-สกุล</div>
                        <div style="font-size: 0.95rem; color: #0f172a; font-weight: 700;">{{ $reporterName ?: '-' }}</div>
                    </div>
                </div>
                <div style="display: flex; align-items: center; gap: 12px; background: #f8fafc; border: 1px solid #eef1f5; border-radius: 16px; padding: 14px 16px;">
                    <div style="width: 44px; height: 44px; border-radius: 12px; background: #fffbeb; display: flex; align-items: center; justify-content: center; flex-shrink: 0;">
                        <i class="fas fa-briefcase" style="color: #d97706; font-size: 1.05rem;"></i>
                    </div>
                    <div style="min-width: 0;">
                        <div style="font-size: 0.74rem; color: #94a3b8; font-weight: 600; margin-bottom: 2px;">ตำแหน่ง</div>
                        <div style="font-size: 0.95rem; color: #0f172a; font-weight: 700;">{{ $reporterPosition ?: '-' }}</div>
                    </div>
                </div>
                <div style="display: flex; align-items: center; gap: 12px; background: #f8fafc; border: 1px solid #eef1f5; border-radius: 16px; padding: 14px 16px;">
                    <div style="width: 44px; height: 44px; border-radius: 12px; background: #ecfdf5; display: flex; align-items: center; justify-content: center; flex-shrink: 0;">
                        <i class="fas fa-phone" style="color: #059669; font-size: 1.05rem;"></i>
                    </div>
                    <div style="min-width: 0;">
                        <div style="font-size: 0.74rem; color: #94a3b8; font-weight: 600; margin-bottom: 2px;">เบอร์ติดต่อ</div>
                        <div style="font-size: 0.95rem; color: #0f172a; font-weight: 700;">{{ $reporterPhone ?: '-' }}</div>
                    </div>
                </div>
                <div style="display: flex; align-items: center; gap: 12px; background: #f8fafc; border: 1px solid #eef1f5; border-radius: 16px; padding: 14px 16px;">
                    <div style="width: 44px; height: 44px; border-radius: 12px; background: #f5f3ff; display: flex; align-items: center; justify-content: center; flex-shrink: 0;">
                        <i class="fas fa-envelope" style="color: #7c3aed; font-size: 1.05rem;"></i>
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
                    1 => 'การขับเคลื่อนการดำเนินงานป้องกันควบคุมโรคไม่ติดต่อเรื้อรัง(ระดับอำเภอ)',
                    2 => 'การจัดการข้อมูลเฝ้าระวัง',
                    3 => 'การกำหนดประเด็นปัญหา เป้าหมาย พร้อมทั้งแผนงานและกิจกรรม',
                    4 => 'สนับสนุนการสร้างนโยบายสาธารณะที่เกี่ยวข้องกับการป้องกันควบคุมโรคไม่ติดต่อเรื้อรัง/ โรคไตในชุมชน',
                    5 => 'การจัดการสิ่งแวดล้อมที่เอื้อต่อสุขภาพ',
                    6 => 'การสร้างความเข้มแข็งของชุมชนในการลดปัจจัยเสี่ยงของการเกิดโรคไม่ติดต่อเรื้อรัง/โรคไตในชุมชน',
                    7 => 'การจัดบริการเชิงรุกในชุมชน'
                ];
            @endphp

            @foreach($sections as $num => $label)
                <div class="report-section">
                    <div class="section-title">
                        <div class="section-number">{{ $num }}</div>
                        <label class="section-label">{{ $label }}</label>
                    </div>
                    <div class="report-detail-box">{{ $assessment->{'category_' . $num} ?? '-' }}</div>
                </div>
            @endforeach

            <!-- Category 8 with Sub-sections -->
            <div class="report-section">
                <div class="section-title" style="margin-bottom: 20px;">
                    <div class="section-number">8</div>
                    <label class="section-label">การประเมินผลลัพธ์การดำเนินงาน ประกอบด้วย</label>
                </div>

                <div style="padding-left: 35px;">
                    @php
                        $sub8 = [
                            '8_1' => 'ร้อยละของผู้ป่วยโรคเบาหวานและ/หรือความดันโลหิตสูง ได้รับการค้นหาและคัดกรองโรคไตเรื้อรัง',
                            '8_2' => 'การประเมินความตระหนักรู้การลดการบริโภคเกลือโซเดียมของประชาชนในพื้นที่',
                            '8_3' => 'นวัตกรรม/บุคคลต้นแบบ/ภูมิปัญญาท้องถิ่น/งานวิจัยที่สนับสนุนการลดการบริโภคเกลือโซเดียมและ/หรือการป้องกันและชะลอภาวะไตเรื้อรัง'
                        ];
                    @endphp

                    @foreach($sub8 as $id => $label)
                        <div style="margin-bottom: 15px; padding-bottom: 15px; border-bottom: 1px solid #f1f5f9;">
                            <label class="section-label"
                                style="font-size: 0.9rem; display: block; margin-bottom: 8px; color: #475569;">{{ str_replace('_', '.', $id) }}
                                {{ $label }}</label>
                            <div class="report-detail-box" style="min-height: 60px;">
                                {{ $assessment->{'category_' . $id} ?? '-' }}
                            </div>
                        </div>
                    @endforeach
                </div>
            </div>

            <!-- Main Section 2: Problems and Obstacles -->
            <div class="report-section">
                <div class="section-title">
                    <div class="section-number"><i class="fas fa-exclamation-triangle" style="font-size: 0.8rem;"></i></div>
                    <label class="section-label">ปัญหา อุปสรรค</label>
                </div>
                <div class="report-detail-box">{{ $assessment->problems_obstacles ?? '-' }}</div>
            </div>

            <!-- Main Section 3: Recommendations/Opportunities -->
            <div class="report-section">
                <div class="section-title">
                    <div class="section-number"><i class="fas fa-lightbulb" style="font-size: 0.8rem;"></i></div>
                    <label class="section-label">ข้อเสนอแนะ/โอกาสพัฒนา</label>
                </div>
                <div class="report-detail-box">{{ $assessment->recommendations_opportunities ?? '-' }}</div>
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
