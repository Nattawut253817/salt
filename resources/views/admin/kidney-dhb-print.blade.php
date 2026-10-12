<!DOCTYPE html>
<html lang="th">

<head>
    <meta charset="UTF-8">
    @php
        $unit_name = '-';
        if ($assessment->user) {
            if ($assessment->user->User_rank_id == 2) {
                $unit_name = 'สำนักงานสาธารณสุขจังหวัด' . ($assessment->user->province->province_name ?? '');
            } elseif ($assessment->user->User_rank_id == 3) {
                $unit_name = 'สำนักงานสาธารณสุขอำเภอ' . ($assessment->user->district->district_name ?? '');
            } elseif ($assessment->user->User_rank_id == 4) {
                $unit_name = $assessment->user->subdistrictHospital ? $assessment->user->subdistrictHospital->hospital_name : $assessment->user->Con_name;
            } elseif ($assessment->user->User_rank_id == 5) {
                $unit_name = 'โรงพยาบาล' . ($assessment->user->hospital ? $assessment->user->hospital->hos_name : $assessment->user->Con_name);
            } else {
                $unit_name = $assessment->user->name;
            }
        }

        // Word-import reports carry their own reporter name/position/
        // phone/email (recorded under reporter_metadata->_import_reporter
        // at import time - see AdminController@storeKidneyDHB) which is
        // historical data for whoever actually filled out that quarter's
        // report. Prefer it here over the current account holder's own
        // registered info, which is what $assessment->user always is.
        $importReporter = $assessment->reporter_metadata['_import_reporter'] ?? null;
        $reporterName = $importReporter['name'] ?? ($assessment->user->name ?? null);
        $reporterPosition = $importReporter['position'] ?? ($assessment->user->User_position ?? null);
        $reporterPhoneRaw = $importReporter['phone'] ?? ($assessment->user->phone ?? null);
        $reporterEmail = $importReporter['email'] ?? ($assessment->user->email ?? null);

        $phoneDigits = preg_replace('/\D/', '', (string) ($reporterPhoneRaw ?? ''));
        $phoneForPrint = strlen($phoneDigits) === 10
            ? substr($phoneDigits, 0, 3) . '-' . substr($phoneDigits, 3)
            : ($reporterPhoneRaw ?? '-');

        // Stamped at export time (not stored on the record), once at
        // the end of the document (see .export-date-stamp below). The
        // app's own clock (config('app.timezone')) runs on UTC, so this
        // is converted to Thailand's local time explicitly rather than
        // showing the raw UTC hour.
        $thaiMonthNames = ['', 'มกราคม', 'กุมภาพันธ์', 'มีนาคม', 'เมษายน', 'พฤษภาคม', 'มิถุนายน', 'กรกฎาคม', 'สิงหาคม', 'กันยายน', 'ตุลาคม', 'พฤศจิกายน', 'ธันวาคม'];
        $exportedAt = now('Asia/Bangkok');
        $exportedAtThai = $exportedAt->day . ' ' . $thaiMonthNames[$exportedAt->month] . ' ' . ($exportedAt->year + 543) . ' เวลา ' . $exportedAt->format('H:i') . ' น.';
    @endphp
    <title>รายงานความก้าวหน้าผลการดำเนินงาน พชอ.ไต - {{ $unit_name }}</title>
    <link
        href="https://fonts.googleapis.com/css2?family=Sarabun:wght@300;400;500;600;700;800&display=swap"
        rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        body {
            font-family: 'TH SarabunPSK', 'TH Sarabun New', 'Sarabun', 'TH Sarabun PS', sans-serif;
            font-size: 16pt;
            line-height: 1.25;
            color: #000;
            background: #f1f5f9;
        }

        .no-print-area {
            background: #fff;
            padding: 12px 0;
            box-shadow: 0 1px 3px rgba(0, 0, 0, 0.1);
            position: sticky;
            top: 0;
            z-index: 1000;
            margin-bottom: 25px;
        }

        .print-container {
            max-width: 900px;
            margin: 0 auto;
            display: flex;
            justify-content: flex-end;
            padding: 0 20px;
        }

        .btn-print {
            padding: 11px 30px;
            cursor: pointer;
            background: linear-gradient(135deg, #4f46e5 0%, #4338ca 100%);
            color: white;
            border: none;
            border-radius: 10px;
            /* UI chrome only (hidden while printing) - lead with the
               web-loaded Sarabun so the label always renders crisp even
               on machines without TH SarabunPSK installed. */
            font-family: 'Sarabun', 'TH SarabunPSK', 'TH Sarabun New', 'TH Sarabun PS', sans-serif;
            font-weight: 600;
            font-size: 16px;
            letter-spacing: 0.3px;
            line-height: 1;
            display: flex;
            align-items: center;
            gap: 10px;
            transition: all 0.3s ease;
            box-shadow: 0 4px 12px rgba(79, 70, 229, 0.3);
        }

        .btn-print:hover {
            transform: translateY(-2px);
            box-shadow: 0 6px 15px rgba(79, 70, 229, 0.4);
            background: linear-gradient(135deg, #4338ca 0%, #3730a3 100%);
        }

        .btn-print i {
            font-size: 15px;
        }

        .a4-page {
            width: 210mm;
            min-height: 297mm;
            padding: 20mm;
            margin: 0 auto;
            background: white;
            position: relative;
        }

        @page {
            size: A4;
            margin: 20mm 15mm;
            /* Top/Bottom 20mm, Left/Right 15mm applied to ALL pages */
        }

        @media print {
            body {
                margin: 0;
                padding: 0;
                background-color: white;
                -webkit-print-color-adjust: exact;
                print-color-adjust: exact;
                font-size: 16pt;
            }

            .a4-page {
                width: 100%;
                max-width: 100%;
                height: auto;
                margin: 0;
                padding: 0;
                box-sizing: border-box;
                box-shadow: none;
                page-break-after: auto;
                border: none;
            }

            .no-print-area {
                display: none !important;
            }

            .no-print {
                display: none !important;
            }

            /*
             * KEY FIX: Use separate borders so each cell owns its borders independently.
             * This prevents missing bottom borders at page breaks.
             */
            table {
                border-collapse: separate !important;
                border-spacing: 0 !important;
                width: 100% !important;
            }

            th, td {
                border-right: 0.1px solid #000 !important;
                border-bottom: 0.1px solid #000 !important;
                -webkit-print-color-adjust: exact !important;
                print-color-adjust: exact !important;
                /* Paint each page-fragment of a split cell as its own
                   complete, closed box (full border on every side of
                   every fragment) instead of Chromium's default "slice"
                   painting, which omits the border-bottom of a fragment
                   that gets cut off mid-content by a page break - the
                   cause of the "dangling open border" look reported when
                   a long answer splits across two pages. */
                box-decoration-break: clone;
                -webkit-box-decoration-break: clone;
            }

            th:first-child, td:first-child {
                border-left: 0.1px solid #000 !important;
            }

            thead tr th {
                border-top: 0.1px solid #000 !important;
            }

            tbody tr:first-child td {
                border-top: 0.1px solid #000 !important;
            }

            th {
                background-color: #f0f4f8 !important;
            }

            thead {
                display: table-header-group;
            }

            /* Let a row split across the page boundary instead of jumping
               whole to the next page - the separate-border setup above
               already gives every cell its own border, so a split row
               still renders complete borders on both page fragments.
               This is what lets a page fill up before breaking instead
               of leaving a large blank gap ahead of a long row. */
            tr {
                page-break-inside: auto;
            }

            .section-title {
                page-break-after: avoid;
            }

            /* กล่องข้อความข้อ 2/3: ครบ 4 ด้านในทุกชิ้นที่ถูกตัดข้ามหน้า (เหมือนเซลล์ตารางหลัก) */
            table.text-box td,
            table.text-box td:first-child {
                border: 0.1px solid #000 !important;
                box-decoration-break: clone;
                -webkit-box-decoration-break: clone;
            }

            table.text-box {
                margin-bottom: 0 !important;
            }

            .content-pre {
                white-space: normal;
                line-height: 1.25 !important;
                text-align: left !important;
                font-weight: normal !important;
                /* Keep at least 3 lines together on either side of a
                   page split so a row split never strands a single
                   stray line by itself at the top/bottom of a page. */
                orphans: 3;
                widows: 3;
            }
        }

        h1,
        h2,
        h3,
        h4,
        h5,
        h6 {
            margin: 0;
            padding: 0;
            font-weight: bold;
        }

        .text-center {
            text-align: center;
        }

        .text-right {
            text-align: right;
        }

        .font-bold {
            font-weight: 500;
            font-size: 16pt;
        }

        .mb-2 {
            margin-bottom: 8px;
        }

        .mb-4 {
            margin-bottom: 16px;
        }

        .mt-4 {
            margin-top: 16px;
        }

        .checkbox-group {
            display: flex;
            justify-content: center;
            gap: 20px;
            margin: 15px 0;
        }

        .checkbox-item {
            display: flex;
            align-items: center;
            gap: 5px;
        }

        .square-box {
            width: 16px;
            height: 16px;
            border: 1px solid #000;
            display: inline-block;
            position: relative;
        }

        .square-box.checked::after {
            content: "✓";
            position: absolute;
            top: -3px;
            left: 1px;
            font-size: 12px;
            font-weight: bold;
        }

        .info-grid {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 10px;
            margin-bottom: 20px;
        }

        .area-multi {
            margin-bottom: 5px;
            font-size: 16pt;
            font-weight: 700;
            page-break-inside: avoid;
            break-inside: avoid;
        }
        .area-multi .area-multi-title { white-space: normal; }
        .area-multi .area-multi-title small { font-size: 13pt; font-weight: 400; }
        .area-multi ol { margin: 2px 0 0; padding-left: 28pt; }
        .area-multi li { font-size: 16pt; font-weight: 400; line-height: 1.35; color: #000; border-bottom: 1px dotted #333; margin-bottom: 3px; padding-bottom: 1px; }
        .area-multi ol { margin-right: 0; }
        .area-multi li .am-issue { font-size: 14pt; }
        .info-row {
            margin-bottom: 5px;
            font-size: 16pt;
            font-weight: 700;
        }

        .d-flex {
            display: flex;
            align-items: baseline;
        }

        .flex-grow-1 {
            flex-grow: 1;
            /* Without this a flex child's default min-width:auto stops it
               from ever shrinking below its content's natural width - so a
               long agency name/email/hospital name would push the row past
               the page edge instead of wrapping onto a second line. */
            min-width: 0;
        }

        .dotted-line {
            border-bottom: 1px dotted #333;
            display: inline-block;
            text-align: left;
            padding: 0 5px;
            margin: 0 5px;
            font-size: 16pt;
            font-weight: 400;
            color: #000;
            white-space: normal;
            overflow-wrap: anywhere;
            word-break: break-word;
        }

        table {
            width: 100%;
            border-collapse: collapse;
            margin-bottom: 20px;
            /* Fixed layout so column widths stay consistent row to row
               (matching a Word table's default behavior) instead of the
               browser re-measuring per row based on that row's content. */
            table-layout: fixed;
        }

        th,
        td {
            border: 0.1px solid #000;
            padding: 10px 12px;
            vertical-align: top;
            overflow-wrap: anywhere;
            word-break: break-word;
        }

        th {
            background-color: #f0f4f8;
            font-weight: 700;
            text-align: center;
            font-size: 16pt;
        }

        /* กล่องข้อความยาวของข้อ 2/3 (ปัญหา อุปสรรค / ข้อเสนอแนะ): ใช้ตารางช่องเดียวแทน div ที่มีขอบ
           เพราะ div ที่ตกหน้าใหม่ Chromium จะ "ตัด" ขอบบน/ล่างของแต่ละชิ้น ทำให้กรอบขาดและไม่สมดุล
           ตารางใช้กลไกเดียวกับตารางหลัก (border-collapse: separate + box-decoration-break: clone)
           ทุกหน้าจึงได้กรอบปิดครบ 4 ด้านและมีระยะห่างบน-ล่างเท่ากัน */
        table.text-box {
            width: 100%;
            margin-bottom: 20px;
            border-collapse: separate;
            border-spacing: 0;
            table-layout: fixed;
        }

        table.text-box td {
            padding: 12px 15px;
            border: 0.1px solid #000;
            vertical-align: top;
        }

        .section-title {
            font-weight: 700;
            font-size: 16pt;
            margin-top: 22px;
            margin-bottom: 10px;
        }

        .content-pre {
            white-space: normal;
            font-family: 'TH SarabunPSK', 'TH Sarabun New', 'Sarabun', 'TH Sarabun PS', sans-serif;
            text-align: left !important;
            font-weight: 300 !important;
            font-size: 16pt;
            line-height: 1.25;
            color: #1a1a1a;
            overflow-wrap: anywhere;
            word-break: break-word;
        }

        .official-tag {
            float: right;
            display: inline-block;
            border: 1px solid #334155;
            background: #eef1fb;
            color: #1e293b;
            border-radius: 3px;
            padding: 6px 14px;
            font-size: 14pt;
            font-weight: 600;
            margin-bottom: 14px;
            letter-spacing: 0.2px;
        }

        .header-block {
            text-align: center;
            margin-bottom: 18px;
            clear: both;
        }

        .header-block h4 {
            font-size: 16pt;
            line-height: 1.4;
        }

        .divider {
            width: 100%;
            max-width: 480px;
            margin: 14px auto 18px;
            border-top: 1.5px solid #1e293b;
        }

        .divider::after {
            content: '';
            display: block;
            height: 0;
            border-top: 0.75px solid #94a3b8;
            margin-top: 3px;
        }

        .export-date-stamp {
            /* In normal document flow (not position: fixed) on
               purpose - this makes it render once, wherever the end
               of the content naturally falls, i.e. only on the last
               printed page, rather than repeating on every page. */
            text-align: right;
            margin-top: 24px;
            font-size: 10pt;
            font-weight: 400;
            color: #64748b;
        }

        /* คอลัมน์ "ภาพกิจกรรม": ภาพเรียงลงในคอลัมน์เดียว กว้างเต็มช่อง สัดส่วน 4:3
           รองรับ 1-3 ภาพ — 1 ภาพจะอยู่กึ่งกลางช่อง, 2-3 ภาพเรียงต่อกันเท่ากัน */
        td.kimg-cell {
            padding: 6px;
            vertical-align: middle;
            background: #fcfcfd;
        }
        .kimg-print-col {
            display: flex;
            flex-direction: column;
            justify-content: center;
            gap: 6px;
        }
        .kimg-print-item {
            margin: 0;
            width: 100%;
            aspect-ratio: 4 / 3;
            border: 0.5px solid #94a3b8;
            display: flex;
            align-items: center;
            justify-content: center;
            background: #fff;
            overflow: hidden;
            page-break-inside: avoid;
            break-inside: avoid;
        }
        .kimg-print-item img {
            max-width: 100%;
            max-height: 100%;
            width: auto;
            height: auto;
            object-fit: contain;
            display: block;
        }
        .kimg-print-empty {
            display: block;
            text-align: center;
            color: #94a3b8;
        }

        @media print {
            .export-date-stamp {
                color: #475569;
                page-break-before: avoid;
            }
        }
    </style>
</head>

<body>

    <div class="no-print-area no-print">
        <div class="print-container">
            <button onclick="window.print()" class="btn-print">
                <i class="fas fa-print"></i> พิมพ์ / บันทึกเป็น PDF
            </button>
        </div>
    </div>

    <div class="a4-page">

        <div class="official-tag">แบบรายงานสำนักงานสาธารณสุขจังหวัด</div>
        <div style="clear: both;"></div>

        <div class="header-block">
            <h4>แบบรายงานความก้าวหน้าผลการดำเนินงานป้องกันควบคุมโรคไม่ติดต่อเรื้อรัง/โรคไตในชุมชนผ่านกลไก</h4>
            <h4>คณะกรรมการพัฒนาคุณภาพชีวิตระดับอำเภอ(พชอ.)</h4>
        </div>

        <div class="checkbox-group">
            <div class="checkbox-item">
                <div class="square-box {{ $assessment->quarter == 1 ? 'checked' : '' }}"></div> รอบ 3 เดือน
            </div>
            <div class="checkbox-item">
                <div class="square-box {{ $assessment->quarter == 2 ? 'checked' : '' }}"></div> รอบ 6 เดือน
            </div>
            <div class="checkbox-item">
                <div class="square-box {{ $assessment->quarter == 3 ? 'checked' : '' }}"></div> รอบ 9 เดือน
            </div>
            <div class="checkbox-item">
                <div class="square-box {{ $assessment->quarter == 4 ? 'checked' : '' }}"></div> รอบ 12 เดือน
            </div>
        </div>

        <div class="divider"></div>

        <div class="info-row d-flex">
            <span style="white-space: nowrap;">ผู้รายงาน ชื่อ-สกุล</span>
            <span class="dotted-line flex-grow-1">{{ $reporterName ?? '-' }}</span>
            <span style="white-space: nowrap; margin-left: 10px;">ตำแหน่ง</span>
            <span class="dotted-line flex-grow-1">{{ $reporterPosition ?? '-' }}</span>
        </div>
        <div class="info-row d-flex">
            <span style="white-space: nowrap;">เบอร์ติดต่อ</span>
            <span class="dotted-line flex-grow-1">{{ $phoneForPrint }}</span>
            <span style="white-space: nowrap; margin-left: 10px;">E-mail</span>
            <span class="dotted-line flex-grow-1">{{ $reporterEmail ?? '-' }}</span>
        </div>
        <div class="info-row d-flex">
            <span style="white-space: nowrap;">หน่วยงาน</span>
            <span class="dotted-line flex-grow-1">
                @if($assessment->user)
                    @if($assessment->user->User_rank_id == 2)
                        สำนักงานสาธารณสุขจังหวัด{{ $assessment->user->province->province_name ?? '-' }}
                    @elseif($assessment->user->User_rank_id == 3)
                        สำนักงานสาธารณสุขอำเภอ{{ $assessment->user->district->district_name ?? '-' }}
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
            </span>
            <span style="white-space: nowrap; margin-left: 10px;">อำเภอ</span>
            <span class="dotted-line flex-grow-1">{{ $assessment->user->district->district_name ?? '-' }}</span>
        </div>
        @php $pdAreas = $assessment->area_list ?? []; @endphp
        @if(count($pdAreas) > 1)
        {{-- รายงานฉบับเดียวครอบคลุมหลายพื้นที่: แสดงเป็นรายการ พื้นที่ + ประเด็น ของแต่ละพื้นที่ --}}
        <div class="area-multi">
            <div class="area-multi-title">พื้นที่การดำเนินงาน <small>(ครอบคลุม {{ count($pdAreas) }} พื้นที่)</small></div>
            <ol>
                @foreach($pdAreas as $ar)
                    <li>{{ $ar['place'] ?: '-' }}@if(!empty($ar['issue'])) <span class="am-issue">— ประเด็น: {{ $ar['issue'] }}</span>@endif</li>
                @endforeach
            </ol>
        </div>
        @else
        <div class="info-row d-flex">
            <span style="white-space: nowrap;">พื้นที่การดำเนินงาน</span>
            <span class="dotted-line flex-grow-1">{{ ($assessment->area_place ?? $assessment->operating_area) ?: '-' }}</span>
        </div>
        @if(!empty($assessment->area_issue))
        <div class="info-row d-flex">
            <span style="white-space: nowrap;">ประเด็น</span>
            <span class="dotted-line flex-grow-1">{{ $assessment->area_issue }}</span>
        </div>
        @endif
        @endif

        <div class="section-title">1. ความก้าวหน้าและผลการดำเนินงาน</div>

        <table>
            <thead>
                <tr>
                    <th style="width: 22%;">กิจกรรม</th>
                    <th style="width: 46%;">ผลการดำเนินงาน</th>
                    <th style="width: 32%;">ภาพกิจกรรม</th>
                </tr>
            </thead>
            <tbody>
                <tr>
                    <td class="font-bold">1. การขับเคลื่อนการดำเนินงานป้องกันควบคุมโรคไม่ติดต่อเรื้อรัง(ระดับอำเภอ)</td>
                    <td>
                        <div class="content-pre">{!! nl2br(e($assessment->category_1 ?? '-')) !!}</div>
                    </td>
                    <td class="kimg-cell">@include('admin.partials.kidney-images-print', ['cat' => '1'])</td>
                </tr>
                <tr>
                    <td class="font-bold">2. การจัดการข้อมูลเฝ้าระวัง</td>
                    <td>
                        <div class="content-pre">{!! nl2br(e($assessment->category_2 ?? '-')) !!}</div>
                    </td>
                    <td class="kimg-cell">@include('admin.partials.kidney-images-print', ['cat' => '2'])</td>
                </tr>
                <tr>
                    <td class="font-bold">3. การกำหนดประเด็นปัญหา เป้าหมาย พร้อมทั้งแผนงานและกิจกรรม</td>
                    <td>
                        <div class="content-pre">{!! nl2br(e($assessment->category_3 ?? '-')) !!}</div>
                    </td>
                    <td class="kimg-cell">@include('admin.partials.kidney-images-print', ['cat' => '3'])</td>
                </tr>
                <tr>
                    <td class="font-bold">4.
                        สนับสนุนการสร้างนโยบายสาธารณะที่เกี่ยวข้องกับการป้องกันควบคุมโรคไม่ติดต่อเรื้อรัง/ โรคไตในชุมชน
                    </td>
                    <td>
                        <div class="content-pre">{!! nl2br(e($assessment->category_4 ?? '-')) !!}</div>
                    </td>
                    <td class="kimg-cell">@include('admin.partials.kidney-images-print', ['cat' => '4'])</td>
                </tr>
                <tr>
                    <td class="font-bold">5. การจัดการสิ่งแวดล้อมที่เอื้อต่อสุขภาพ</td>
                    <td>
                        <div class="content-pre">{!! nl2br(e($assessment->category_5 ?? '-')) !!}</div>
                    </td>
                    <td class="kimg-cell">@include('admin.partials.kidney-images-print', ['cat' => '5'])</td>
                </tr>
                <tr>
                    <td class="font-bold">6.
                        การสร้างความเข้มแข็งของชุมชนในการลดปัจจัยเสี่ยงของการเกิดโรคไม่ติดต่อเรื้อรัง/โรคไตในชุมชน</td>
                    <td>
                        <div class="content-pre">{!! nl2br(e($assessment->category_6 ?? '-')) !!}</div>
                    </td>
                    <td class="kimg-cell">@include('admin.partials.kidney-images-print', ['cat' => '6'])</td>
                </tr>
                <tr>
                    <td class="font-bold">7. การจัดบริการเชิงรุกในชุมชน</td>
                    <td>
                        <div class="content-pre">{!! nl2br(e($assessment->category_7 ?? '-')) !!}</div>
                    </td>
                    <td class="kimg-cell">@include('admin.partials.kidney-images-print', ['cat' => '7'])</td>
                </tr>
                <tr>
                    <td class="font-bold" style="background-color: #f9f9f9; vertical-align: middle;">
                        8. การประเมินผลลัพธ์การดำเนินงาน ประกอบด้วย
                    </td>
                    <td>
                        <div class="content-pre">
                            <b>8.1 ร้อยละของผู้ป่วยโรคเบาหวานและ/หรือความดันโลหิตสูง ได้รับการค้นหาและคัดกรองโรคไตเรื้อรัง</b><br>
                            {!! nl2br(e($assessment->category_8_1 ?? '-')) !!}
                        </div>
                    </td>
                    <td class="kimg-cell">@include('admin.partials.kidney-images-print', ['cat' => '8_1'])</td>
                </tr>
                <tr>
                    {{-- Same shaded cell repeated instead of a rowspan="3"
                         on the row above: a rowspan cell whose group is
                         split by a page break loses its border-bottom in
                         Chromium's print/PDF renderer (reproduced and
                         confirmed independent of border width) - an
                         ordinary per-row cell page-fragments normally. --}}
                    <td style="background-color: #f9f9f9;">&nbsp;</td>
                    <td>
                        <div class="content-pre">
                            <b>8.2 การประเมินความตระหนักรู้การลดการบริโภคเกลือโซเดียมของประชาชนในพื้นที่</b><br>
                            {!! nl2br(e($assessment->category_8_2 ?? '-')) !!}
                        </div>
                    </td>
                    <td class="kimg-cell">@include('admin.partials.kidney-images-print', ['cat' => '8_2'])</td>
                </tr>
                <tr>
                    <td style="background-color: #f9f9f9;">&nbsp;</td>
                    <td>
                        <div class="content-pre">
                            <b>8.3 นวัตกรรม/บุคคลต้นแบบ/ภูมิปัญญาท้องถิ่น/งานวิจัยที่สนับสนุนการลดการบริโภคเกลือโซเดียมและ/หรือการป้องกันและชะลอภาวะไตเรื้อรัง</b><br>
                            {!! nl2br(e($assessment->category_8_3 ?? '-')) !!}
                        </div>
                    </td>
                    <td class="kimg-cell">@include('admin.partials.kidney-images-print', ['cat' => '8_3'])</td>
                </tr>
            </tbody>
        </table>

        <div class="section-title">2. ปัญหา อุปสรรค</div>
        <table class="text-box">
            <tbody>
                <tr>
                    <td>
                        <div class="content-pre" style="line-height: 1.8;">
                            {!! nl2br(e(trim($assessment->problems_obstacles ?? '-'))) !!}
                        </div>
                    </td>
                </tr>
            </tbody>
        </table>

        <div class="section-title">3. ข้อเสนอแนะ/โอกาสพัฒนา</div>
        <table class="text-box">
            <tbody>
                <tr>
                    <td>
                        <div class="content-pre" style="line-height: 1.8;">
                            {!! nl2br(e(trim($assessment->recommendations_opportunities ?? '-'))) !!}
                        </div>
                    </td>
                </tr>
            </tbody>
        </table>

        <div class="export-date-stamp">ออกรายงานเมื่อ {{ $exportedAtThai }}</div>

    </div>

    <script>
        window.onload = function () {
            // setTimeout(() => window.print(), 500); // Optional: Auto-print
        };
    </script>
</body>

</html>