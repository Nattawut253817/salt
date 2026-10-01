<!DOCTYPE html>
<html lang="th">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    @php
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
        // at import time - see AdminController@storeReportProgress) which
        // is historical data for whoever actually filled out that
        // quarter's report. Prefer it here over the current account
        // holder's own registered info, which is what $assessment->user
        // always is. Mirrors the same fix on
        // resources/views/admin/kidney-dhb-print.blade.php.
        $importReporter = $assessment->reporter_metadata['_import_reporter'] ?? null;
        $reporterName = $importReporter['name'] ?? ($assessment->user->name ?? null);
        $reporterPosition = $importReporter['position'] ?? ($assessment->user->User_position ?? null);
        $reporterPhoneRaw = $importReporter['phone'] ?? ($assessment->user->phone ?? null);
        $reporterEmail = $importReporter['email'] ?? ($assessment->user->email ?? null);

        $phoneDigits = preg_replace('/\D/', '', (string) ($reporterPhoneRaw ?? ''));
        $phoneForPrint = strlen($phoneDigits) === 10
            ? substr($phoneDigits, 0, 3) . '-' . substr($phoneDigits, 3)
            : ($reporterPhoneRaw ?? '-');
    @endphp
    <title>แบบรายงาน SDA๐๙๐๒ - {{ $unit_name }}</title>
    <link
        href="https://fonts.googleapis.com/css2?family=Sarabun:wght@300;400;500;600;700;800&display=swap"
        rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        @page {
            size: A4;
            margin: 15mm 15mm;
        }

        body {
            font-family: 'TH SarabunPSK', 'TH Sarabun New', 'Sarabun', 'TH Sarabun PS', sans-serif;
            font-size: 16pt;
            /* Consistent font size - matches TH SarabunPSK 16pt, the standard Thai official-document size */
            line-height: 1.25;
            color: #000;
            background: #f8fafc;
            margin: 0;
            padding: 0;
        }

        @media print {
            body {
                background: white;
                -webkit-print-color-adjust: exact;
                print-color-adjust: exact;
                font-size: 16pt;
            }

            .no-print-area {
                display: none !important;
            }

            .a4-page {
                box-shadow: none !important;
                margin: 0 !important;
                padding: 0 !important;
                width: 100% !important;
                min-height: auto !important;
            }

            /*
             * KEY FIX: Use separate borders so each cell owns its borders independently.
             * This prevents the "missing bottom border at page break" issue that occurs
             * with border-collapse: collapse when a row is split across pages.
             */
            table {
                border-collapse: separate !important;
                border-spacing: 0 !important;
                width: 100% !important;
            }

            /* Each cell gets right + bottom border by default */
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

            /* First column gets left border */
            th:first-child, td:first-child {
                border-left: 0.1px solid #000 !important;
            }

            /* Header row + first data row get top border */
            thead tr th {
                border-top: 0.1px solid #000 !important;
            }

            /* First body row gets top border (if no thead) */
            tbody tr:first-child td {
                border-top: 0.1px solid #000 !important;
            }

            th {
                background-color: #f0f4f8 !important;
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

            /* Repeat table headers on each new page */
            thead {
                display: table-header-group;
            }

            /* Keep section headers attached to the content below */
            .section-header, .note-title {
                page-break-after: avoid;
            }

            .note-box {
                border: 0.1px solid #000 !important;
                page-break-inside: auto;
            }

            .content-cell {
                line-height: 1.30 !important;
                text-align: left !important;
                font-weight: normal !important;
                orphans: 3;
                widows: 3;
            }

            .footer-note {
                page-break-before: avoid;
            }
        }

        /* Sticky Header for Browser View */
        .no-print-area {
            position: sticky;
            top: 0;
            background: white;
            padding: 12px 0;
            box-shadow: 0 2px 10px rgba(0, 0, 0, 0.1);
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
            background: linear-gradient(135deg, #4f46e5 0%, #4338ca 100%);
            color: white;
            border: none;
            padding: 11px 30px;
            border-radius: 10px;
            /* UI chrome only (hidden while printing) - lead with the
               web-loaded Sarabun so the label always renders crisp even
               on machines without TH SarabunPSK installed. */
            font-family: 'Sarabun', 'TH SarabunPSK', 'TH Sarabun New', 'TH Sarabun PS', sans-serif;
            font-weight: 600;
            font-size: 16px;
            letter-spacing: 0.3px;
            line-height: 1;
            cursor: pointer;
            display: flex;
            align-items: center;
            gap: 10px;
            box-shadow: 0 4px 12px rgba(79, 70, 229, 0.3);
            transition: all 0.3s ease;
        }

        .btn-print:hover {
            transform: translateY(-2px);
            box-shadow: 0 6px 15px rgba(79, 70, 229, 0.4);
        }

        .btn-print i {
            font-size: 15px;
        }

        .a4-page {
            width: 210mm;
            min-height: auto;
            margin: 0 auto 20px;
            background: white;
            padding: 15mm 18mm;
            box-shadow: 0 0 10px rgba(0, 0, 0, 0.1);
            box-sizing: border-box;
            position: relative;
        }

        .official-tag {
            float: right;
            border: 1px solid #000;
            padding: 4px 12px;
            font-size: 16pt;
            font-weight: 600;
            margin-bottom: 15px;
        }

        .header-section {
            text-align: center;
            margin-bottom: 20px;
            clear: both;
        }

        .title-main {
            font-size: 16pt;
            font-weight: 700;
            margin-bottom: 5px;
        }

        .title-sub {
            font-size: 16pt;
            font-weight: 600;
            margin-bottom: 12px;
        }

        .checkbox-group {
            display: flex;
            justify-content: center;
            gap: 25px;
            margin: 10px 0;
        }

        .checkbox-item {
            display: flex;
            align-items: center;
            gap: 8px;
            font-size: 16pt;
        }

        .square-box {
            width: 16px;
            height: 16px;
            border: 1px solid #000;
            position: relative;
            display: inline-block;
        }

        .square-box.checked::after {
            content: "✓";
            position: absolute;
            top: -4px;
            left: 2px;
            font-size: 13px;
            font-weight: bold;
        }

        .divider {
            border-top: 1px solid #000;
            margin: 15px 0;
            text-align: center;
        }

        .info-section {
            margin-bottom: 15px;
        }

        .info-row {
            display: flex;
            align-items: baseline;
            margin-bottom: 6px;
            font-size: 16pt;
            font-weight: 700;
        }

        .dotted-line {
            flex-grow: 1;
            /* Without this a flex child's default min-width:auto stops it
               from ever shrinking below its content's natural width - so a
               long agency name/email would push the row past the page
               edge instead of wrapping onto a second line. */
            min-width: 0;
            border-bottom: 1px dotted #000;
            margin: 0 10px;
            padding: 0 5px;
            font-size: 16pt;
            font-weight: 400;
            color: #000;
            overflow-wrap: anywhere;
            word-break: break-word;
        }

        .section-header {
            font-weight: 700;
            font-size: 16pt;
            margin: 20px 0 10px;
            display: flex;
            align-items: center;
            gap: 10px;
        }

        table {
            width: 100%;
            border-collapse: collapse;
            margin-bottom: 15px;
            /* Fixed layout so the step-col/result-col/file-col percentage
               widths below actually hold row to row (matching a Word
               table's default behavior), instead of the browser
               re-measuring each row's columns from its own content. */
            table-layout: fixed;
        }

        th,
        td {
            border: 0.1px solid #000;
            padding: 9px 11px;
            vertical-align: top;
            word-wrap: break-word;
            word-break: break-word;
            font-size: 16pt;
        }

        th {
            background-color: #f0f4f8;
            font-weight: 700;
            text-align: center;
            font-size: 16pt;
        }

        .content-cell {
            white-space: pre-wrap;
            /* Preserve spaces and line breaks */
            word-wrap: break-word;
            /* Break long words */
            vertical-align: top;
            font-size: 16pt;
            text-align: left;
            font-weight: 300 !important;
            padding: 8px;
            /* Add some padding for better readability */
        }

        .step-col { width: 38%; }
        .result-col { width: 50%; }
        .file-col { width: 12%; text-align: center; }

        .note-box {
            border: 0.1px solid #000;
            padding: 12px 16px;
            margin-top: 18px;
        }

        .note-title {
            font-weight: 700;
            text-decoration: underline;
            margin-bottom: 6px;
            font-size: 16pt;
            page-break-after: avoid;
        }

        .section-header {
            font-weight: 700;
            font-size: 16pt;
            margin: 16px 0 8px;
            display: flex;
            align-items: center;
            gap: 10px;
            page-break-after: avoid;
        }

        .footer-note {
            margin-top: 15px;
            font-size: 10px;
            font-style: italic;
            color: #4b5563;
            page-break-before: avoid;
        }
    </style>
</head>

<body>

    <div class="no-print-area">
        <div class="print-container">
            <button onclick="window.print()" class="btn-print">
                <i class="fas fa-print"></i> พิมพ์เอกสาร / บันทึก PDF
            </button>
        </div>
    </div>

    <div class="a4-page">
        <div class="official-tag">แบบรายงานสำนักงานสาธารณสุข</div>

        <div class="header-section">
            <div class="title-main">แบบรายงานผลการดำเนินงาน</div>
            <div class="title-sub">ตัวชี้วัด SDA๐๙๐๒ :
                "ร้อยละเครือข่ายเป้าหมายที่ดำเนินการลดการบริโภคเกลือโซเดียมตามแนวทางที่กำหนด"</div>

            <div class="checkbox-group">
                <div class="checkbox-item">
                    รอบ <div class="square-box {{ $assessment->quarter == 1 ? 'checked' : '' }}"></div> ๓ เดือน
                </div>
                <div class="checkbox-item">
                    <div class="square-box {{ $assessment->quarter == 2 ? 'checked' : '' }}"></div> ๖ เดือน
                </div>
                <div class="checkbox-item">
                    <div class="square-box {{ $assessment->quarter == 3 ? 'checked' : '' }}"></div> ๙ เดือน
                </div>
                <div class="checkbox-item">
                    <div class="square-box {{ $assessment->quarter == 4 ? 'checked' : '' }}"></div> ๑๒ เดือน
                </div>
            </div>

            <div class="title-sub" style="margin-top: 10px;">
                สำนักงานสาธารณสุขจังหวัด{{ $assessment->user->province->province_name ?? '................................' }}
            </div>
            <div class="divider"></div>
        </div>

        <div class="info-section">
            <div class="info-row">
                <span>ผู้รายงาน ชื่อ-สกุล</span>
                <div class="dotted-line">{{ $reporterName ?? '-' }}</div>
                <span>ตำแหน่ง</span>
                <div class="dotted-line">{{ $reporterPosition ?? '-' }}</div>
            </div>
            <div class="info-row">
                <span>เบอร์ติดต่อ</span>
                <div class="dotted-line">{{ $phoneForPrint }}</div>
                <span>E-mail :</span>
                <div class="dotted-line">{{ $reporterEmail ?? '-' }}</div>
            </div>
            <div class="info-row">
                <span>หน่วยงาน</span>
                <div class="dotted-line">
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
                </div>
            </div>
        </div>

        <div class="section-header">
            <i></i> ความก้าวหน้าของการดำเนินงาน
        </div>

        {{-- Single unified table for all steps --}}
        <table>
            <thead>
                <tr>
                    <th class="step-col">ขั้นตอนการดำเนินงาน</th>
                    <th class="result-col">ผลการดำเนินงาน</th>
                    <th class="file-col">เอกสารแนบ</th>
                </tr>
            </thead>
            <tbody>
                <tr>
                    <td><b>๑. จัดทำบันทึกความเข้าใจ (MOU)</b> หรือข้อตกลงความร่วมมือ
                        หรือคำสั่งคณะกรรมการ/คณะทำงานขับเคลื่อนการดำเนินงานเฝ้าระวังร่วมกับหน่วยงานเครือข่ายระดับจังหวัด
                    </td>
                    <td class="content-cell">{{ $assessment->ans_1_detail ?? '-' }}</td>
                    <td style="text-align: center;">{{ $assessment->ans_1_file ? 'มี' : '-' }}</td>
                </tr>
                <tr>
                    <td><b>๒. การสำรวจปริมาณโซเดียมในอาหาร</b> ด้วยเครื่องวัดความเค็ม (Salt meter)
                        (สำหรับจังหวัดที่ยังไม่ได้ดำเนินการ)</td>
                    <td class="content-cell">{{ $assessment->ans_2_detail ?? '-' }}</td>
                    <td style="text-align: center;">{{ $assessment->ans_2_file ? 'มี' : '-' }}</td>
                </tr>
                <tr>
                    <td><b>๓. จัดทำแผนปฏิบัติการลดการบริโภคเกลือและโซเดียม</b> ระดับจังหวัด ภายใต้กลยุทธ์ ๕ ด้าน</td>
                    <td class="content-cell">{{ $assessment->ans_3_detail ?? '-' }}</td>
                    <td style="text-align: center;">{{ $assessment->ans_3_file ? 'มี' : '-' }}</td>
                </tr>
                <tr>
                    <td><b>๔. การประเมินความตระหนักรู้ความเสี่ยง</b> การบริโภคเกลือและโซเดียมระดับจังหวัด
                        (เป้าหมายจังหวัดละ ๕๐๐ คน)</td>
                    <td class="content-cell">{{ $assessment->ans_4_detail ?? '-' }}</td>
                    <td style="text-align: center;">{{ $assessment->ans_4_file ? 'มี' : '-' }}</td>
                </tr>
                <tr>
                    <td>
                        <b>๕. การดำเนินงานตามแผนการดำเนินงานลดการบริโภคเกลือและโซเดียมระดับจังหวัด
                            ภายใต้กลยุทธ์ ๕ ด้าน</b><br>
                        <b>๕.๑ การส่งเสริมให้ผู้บริโภค/ประชาชนมีความรู้</b>
                        และความตระหนักถึงความเสี่ยงต่อสุขภาพผ่านสื่อสารมวลชน/social media</td>
                    <td class="content-cell">{{ $assessment->ans_5_1_detail ?? '-' }}</td>
                    <td style="text-align: center;">{{ $assessment->ans_5_1_file ? 'มี' : '-' }}</td>
                </tr>
                <tr>
                    <td><b>๕.๒ การปรับลดปริมาณเกลือและโซเดียม</b> ในผลิตภัณฑ์อาหาร</td>
                    <td class="content-cell">{{ $assessment->ans_5_2_detail ?? '-' }}</td>
                    <td style="text-align: center;">{{ $assessment->ans_5_2_file ? 'มี' : '-' }}</td>
                </tr>
                <tr>
                    <td><b>๕.๓ การปรับลดปริมาณเกลือและโซเดียม</b> ในอาหารปรุงสุกที่จำหน่าย</td>
                    <td class="content-cell">{{ $assessment->ans_5_3_detail ?? '-' }}</td>
                    <td style="text-align: center;">{{ $assessment->ans_5_3_file ? 'มี' : '-' }}</td>
                </tr>
                <tr>
                    <td><b>๕.๔ การปรับสิ่งแวดล้อมที่เอื้อต่อการมีสุขภาพดี</b>
                        ภายในและบริเวณโดยรอบโรงเรียน/โรงพยาบาล/สถานที่ทำงาน</td>
                    <td class="content-cell">{{ $assessment->ans_5_4_detail ?? '-' }}</td>
                    <td style="text-align: center;">{{ $assessment->ans_5_4_file ? 'มี' : '-' }}</td>
                </tr>
                <tr>
                    <td><b>๕.๕ การดำเนินงานป้องกันควบคุมโรคไต</b> ในชุมชน ผ่านกลไก พชอ. ตามแนวทางที่กำหนด</td>
                    <td class="content-cell">{{ $assessment->ans_5_5_detail ?? '-' }}</td>
                    <td style="text-align: center;">{{ $assessment->ans_5_5_file ? 'มี' : '-' }}</td>
                </tr>
            </tbody>
        </table>

        <div class="note-box">
            <div class="note-title">ปัญหา/อุปสรรค</div>
            <div class="content-cell">{{ trim($assessment->problems ?? '-') }}</div>
        </div>

        <div class="note-box" style="margin-top: 12px;">
            <div class="note-title">ข้อเสนอแนะ/โอกาสพัฒนา</div>
            <div class="content-cell">{{ trim($assessment->suggestions ?? '-') }}</div>
        </div>

        <div class="footer-note">
            <b>หมายเหตุ :</b> กรุณาส่งแบบรายงานผลการดำเนินงานฯ ให้กรมควบคุมโรค หรือหน่วยงานที่รับผิดชอบ
            ตามกำหนดเวลาที่ระบุไว้
        </div>
    </div>

</body>

</html>
