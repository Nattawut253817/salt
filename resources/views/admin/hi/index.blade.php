@extends('layouts.admin')

@section('title', 'อัตราป่วยรายใหม่ HT')

@section('header_title')
    <div class="page-header">
        <div class="page-header-accent"></div>
        <div class="page-header-text">
            <span class="page-header-title">อัตราป่วยรายใหม่ HT </span>
            <small class="page-header-subtitle">
                <i class="fas fa-circle-info"></i>
                ระบบจัดการและนำเข้าข้อมูลอัตราป่วยรายใหม่รายเดือน
            </small>
        </div>
    </div>
@endsection

@section('content')
    <div class="row">
        <!-- Filter Card -->
        <div class="col-lg-12 mb-4">
            <div class="list-header"
                style="display: flex; justify-content: space-between; align-items: flex-end; gap: 16px; flex-wrap: wrap;">
                <form id="filter-form" style="display: flex; gap: 15px; align-items: flex-end; flex: 1; min-width: 350px; flex-wrap: wrap;">
                    <div style="flex: 1 1 150px; min-width: 0;">
                        <label
                            style="display: block; margin-bottom: 8px; font-family: 'Noto Serif Thai', sans-serif; font-size: 0.85rem; font-weight: 600; color: #475569; letter-spacing: 0.3px;">
                            <i class="fas fa-calendar-alt" style="margin-right: 6px; color: #6366f1;"></i>ปีงบประมาณ
                        </label>
                        <select class="form-control filter-input" name="year"
                            style="border-radius: 10px; border: 2px solid #e2e8f0; font-family: 'Noto Serif Thai', sans-serif; font-size: 0.8rem; padding: 10px 16px; background-color: white; height: auto; box-shadow: 0 1px 3px rgba(0,0,0,0.05); transition: all 0.2s ease; font-weight: 500; width: 100%; box-sizing: border-box;">
                            <option value="">ทั้งหมด</option>
                            @foreach($years as $y)
                                <option value="{{ $y }}" {{ request('year') == $y ? 'selected' : '' }}>{{ $y }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div style="flex: 1 1 150px; min-width: 0;">
                        <label
                            style="display: block; margin-bottom: 8px; font-family: 'Noto Serif Thai', sans-serif; font-size: 0.85rem; font-weight: 600; color: #475569; letter-spacing: 0.3px;">
                            <i class="fas fa-map-marker-alt" style="margin-right: 6px; color: #6366f1;"></i>จังหวัด
                        </label>
                        <select class="form-control filter-input" name="province_id"
                            style="border-radius: 10px; border: 2px solid #e2e8f0; font-family: 'Noto Serif Thai', sans-serif; font-size: 0.8rem; padding: 10px 16px; background-color: white; height: auto; box-shadow: 0 1px 3px rgba(0,0,0,0.05); transition: all 0.2s ease; font-weight: 500; width: 100%; box-sizing: border-box;">
                            <option value="">ทั้งหมด</option>
                            @foreach($provinces as $province)
                                <option value="{{ $province->province_id }}" {{ request('province_id') == $province->province_id ? 'selected' : '' }}>
                                    {{ $province->province_name }}
                                </option>
                            @endforeach
                        </select>
                    </div>
                    <div style="flex: 1 1 150px; min-width: 0;">
                        <label
                            style="display: block; margin-bottom: 8px; font-family: 'Noto Serif Thai', sans-serif; font-size: 0.85rem; font-weight: 600; color: #475569; letter-spacing: 0.3px;">
                            <i class="fas fa-map-pin" style="margin-right: 6px; color: #6366f1;"></i>อำเภอ
                        </label>
                        <select class="form-control filter-input" name="district" id="filter-district"
                            style="border-radius: 10px; border: 2px solid #e2e8f0; font-family: 'Noto Serif Thai', sans-serif; font-size: 0.8rem; padding: 10px 16px; background-color: white; height: auto; box-shadow: 0 1px 3px rgba(0,0,0,0.05); transition: all 0.2s ease; font-weight: 500; width: 100%; box-sizing: border-box;">
                            <option value="">ทั้งหมด</option>
                        </select>
                    </div>
                    <div style="flex: 1 1 150px; min-width: 0;">
                        <label
                            style="display: block; margin-bottom: 8px; font-family: 'Noto Serif Thai', sans-serif; font-size: 0.85rem; font-weight: 600; color: #475569; letter-spacing: 0.3px;">
                            <i class="fas fa-calendar-day" style="margin-right: 6px; color: #6366f1;"></i>เดือน
                        </label>
                        <select class="form-control filter-input" name="month" id="filter-month"
                            style="border-radius: 10px; border: 2px solid #e2e8f0; font-family: 'Noto Serif Thai', sans-serif; font-size: 0.8rem; padding: 10px 16px; background-color: white; height: auto; box-shadow: 0 1px 3px rgba(0,0,0,0.05); transition: all 0.2s ease; font-weight: 500; width: 100%; box-sizing: border-box;">
                            <option value="">ทั้งหมด</option>
                            <option value="m10_oct" {{ request('month') == 'm10_oct' ? 'selected' : '' }}>ตุลาคม</option>
                            <option value="m11_nov" {{ request('month') == 'm11_nov' ? 'selected' : '' }}>พฤศจิกายน</option>
                            <option value="m12_dec" {{ request('month') == 'm12_dec' ? 'selected' : '' }}>ธันวาคม</option>
                            <option value="m01_jan" {{ request('month') == 'm01_jan' ? 'selected' : '' }}>มกราคม</option>
                            <option value="m02_feb" {{ request('month') == 'm02_feb' ? 'selected' : '' }}>กุมภาพันธ์</option>
                            <option value="m03_mar" {{ request('month') == 'm03_mar' ? 'selected' : '' }}>มีนาคม</option>
                            <option value="m04_apr" {{ request('month') == 'm04_apr' ? 'selected' : '' }}>เมษายน</option>
                            <option value="m05_may" {{ request('month') == 'm05_may' ? 'selected' : '' }}>พฤษภาคม</option>
                            <option value="m06_jun" {{ request('month') == 'm06_jun' ? 'selected' : '' }}>มิถุนายน</option>
                            <option value="m07_jul" {{ request('month') == 'm07_jul' ? 'selected' : '' }}>กรกฎาคม</option>
                            <option value="m08_aug" {{ request('month') == 'm08_aug' ? 'selected' : '' }}>สิงหาคม</option>
                            <option value="m09_sep" {{ request('month') == 'm09_sep' ? 'selected' : '' }}>กันยายน</option>
                        </select>
                    </div>
                </form>

                <div style="display: flex; gap: 12px; justify-content: flex-end; align-items: center; flex-shrink: 0;">
                    <div style="display: flex; gap: 6px;">
                        <button type="button" id="clear-filters" class="btn"
                            style="background: linear-gradient(135deg, #22d3ee 0%, #06b6d4 100%); color: #fff; border: none; border-radius: 50%; width: 44px; height: 44px; padding: 0; display: flex; align-items: center; justify-content: center; font-size: 1rem; box-shadow: 0 4px 10px rgba(6,182,212,0.3); transition: all 0.2s ease;"
                            title="ล้างตัวกรอง"
                            onmouseover="this.style.transform='translateY(-1px) rotate(-30deg)'; this.style.boxShadow='0 6px 14px rgba(6,182,212,0.4)';"
                            onmouseout="this.style.transform='translateY(0) rotate(0deg)'; this.style.boxShadow='0 4px 10px rgba(6,182,212,0.3)';">
                            <i class="fas fa-sync-alt"></i>
                        </button>
                        <button type="button" id="delete-filtered" class="btn-toggle-form"
                            style="background: linear-gradient(135deg, #fff5f5 0%, #fed7d7 100%); color: #c53030; border: 2px solid #feb2b2; border-radius: 50%; width: 44px; height: 44px; padding: 0; display: flex; align-items: center; justify-content: center; font-size: 1rem; box-shadow: 0 2px 4px rgba(155,44,44,0.1); flex-shrink: 0;"
                            title="ลบข้อมูลที่กรอง">
                            <i class="fas fa-trash-alt"></i>
                        </button>
                    </div>
                    <button type="button" class="btn-toggle-form" data-toggle="modal" data-target="#importHiModal"
                        style="background: linear-gradient(135deg, #eff6ff 0%, #dbeafe 100%); color: #1d4ed8; border: 2px solid #93c5fd; border-radius: 10px; padding: 10px 18px; white-space: nowrap; font-family: 'Noto Serif Thai', sans-serif; font-size: 0.8rem; font-weight: 600; box-shadow: 0 2px 4px rgba(29,78,216,0.1); height: 44px; display: flex; align-items: center; gap: 8px; transition: all 0.2s ease;"
                        title="นำเข้าไฟล์ Excel"
                        onmouseover="this.style.transform='translateY(-1px)'; this.style.boxShadow='0 4px 8px rgba(29,78,216,0.15)';"
                        onmouseout="this.style.transform='translateY(0)'; this.style.boxShadow='0 2px 4px rgba(29,78,216,0.1)';">
                        <i class="fas fa-file-excel"></i> Upload file excel
                    </button>
                    <a href="{{ route('admin.new-ht-cases-dashboard') }}"
                        style="background: linear-gradient(135deg, #eef2ff 0%, #e0e7ff 100%); color: #4338ca; border: 2px solid #c7d2fe; border-radius: 10px; padding: 10px 18px; white-space: nowrap; font-family: 'Noto Serif Thai', sans-serif; font-size: 0.8rem; font-weight: 600; box-shadow: 0 2px 4px rgba(67,56,202,0.1); height: 44px; display: flex; align-items: center; gap: 8px; transition: all 0.2s ease; text-decoration: none;"
                        title="ดูภาพรวม/แดชบอร์ด"
                        onmouseover="this.style.transform='translateY(-1px)'; this.style.boxShadow='0 4px 8px rgba(67,56,202,0.15)';"
                        onmouseout="this.style.transform='translateY(0)'; this.style.boxShadow='0 2px 4px rgba(67,56,202,0.1)';">
                        <i class="fas fa-chart-pie"></i> ภาพรวม
                    </a>
                </div>
            </div>
        </div>
    </div>

    <!-- Data Table Card -->
    <div class="border-0 shadow-none bg-transparent position-relative">
        <!-- Loading Overlay -->
        <div id="table-loader" class="position-absolute w-100 h-100 d-none align-items-center justify-content-center"
            style="background: rgba(255,255,255,0.7); z-index: 10; border-radius: 12px;">
            <div class="text-center">
                <div class="spinner-border text-primary" role="status"></div>
                <div class="mt-2 font-weight-bold text-primary">กำลังโหลดข้อมูล...</div>
            </div>
        </div>

        <!-- Success/Error Messages -->
        @if(session('import_result') || session('error'))
            <script>
                document.addEventListener('DOMContentLoaded', function () {
                    @if(session('import_result'))
                    @php $r = session('import_result'); @endphp
                    Swal.fire({
                        html: `<div style="width:60px; height:60px; border-radius:50%; background:linear-gradient(135deg,#34d399,#059669); display:flex; align-items:center; justify-content:center; margin:0 auto 14px; box-shadow:0 8px 20px rgba(5,150,105,0.3);">
                                <i class="fas fa-check" style="color:#fff; font-size:1.5rem;"></i>
                            </div>
                            <div style="font-size:1.2rem; font-weight:800; color:#1e293b; margin-bottom:4px;">นำเข้าข้อมูลสำเร็จ!</div>
                            <p style="color:#94a3b8; margin:0 0 22px; font-size:0.85rem; font-weight:500;">สรุปผลการนำเข้าข้อมูล Excel</p>
                            <div style="display:grid; grid-template-columns:1fr 1fr; gap:14px;">
                                <div style="background:linear-gradient(180deg,#f0f9ff 0%,#ffffff 65%); border:1px solid #e0f2fe; border-top:3px solid #0ea5e9; border-radius:14px; padding:18px 14px 16px; text-align:center; box-shadow:0 4px 14px rgba(14,165,233,0.1);">
                                    <div style="width:34px; height:34px; border-radius:10px; background:linear-gradient(135deg,#38bdf8,#0ea5e9); display:flex; align-items:center; justify-content:center; margin:0 auto 10px; box-shadow:0 4px 10px rgba(14,165,233,0.35);">
                                        <i class="fas fa-plus" style="color:#fff; font-size:0.8rem;"></i>
                                    </div>
                                    <div style="font-size:1.7rem; font-weight:800; color:#0369a1; line-height:1.2;">{{ $r['imported'] }}</div>
                                    <div style="font-size:0.72rem; color:#64748b; font-weight:600; margin-top:4px;">นำเข้าใหม่</div>
                                </div>
                                <div style="background:linear-gradient(180deg,#fffbeb 0%,#ffffff 65%); border:1px solid #fef3c7; border-top:3px solid #f59e0b; border-radius:14px; padding:18px 14px 16px; text-align:center; box-shadow:0 4px 14px rgba(245,158,11,0.1);">
                                    <div style="width:34px; height:34px; border-radius:10px; background:linear-gradient(135deg,#fbbf24,#f59e0b); display:flex; align-items:center; justify-content:center; margin:0 auto 10px; box-shadow:0 4px 10px rgba(245,158,11,0.35);">
                                        <i class="fas fa-sync-alt" style="color:#fff; font-size:0.8rem;"></i>
                                    </div>
                                    <div style="font-size:1.7rem; font-weight:800; color:#b45309; line-height:1.2;">{{ $r['updated'] }}</div>
                                    <div style="font-size:0.72rem; color:#64748b; font-weight:600; margin-top:4px;">อัปเดต (ค่าเปลี่ยน)</div>
                                </div>
                                <div style="background:linear-gradient(180deg,#f8fafc 0%,#ffffff 65%); border:1px solid #f1f5f9; border-top:3px solid #94a3b8; border-radius:14px; padding:18px 14px 16px; text-align:center; box-shadow:0 4px 14px rgba(100,116,139,0.1);">
                                    <div style="width:34px; height:34px; border-radius:10px; background:linear-gradient(135deg,#94a3b8,#64748b); display:flex; align-items:center; justify-content:center; margin:0 auto 10px; box-shadow:0 4px 10px rgba(100,116,139,0.35);">
                                        <i class="fas fa-forward" style="color:#fff; font-size:0.8rem;"></i>
                                    </div>
                                    <div style="font-size:1.7rem; font-weight:800; color:#475569; line-height:1.2;">{{ $r['skipped'] }}</div>
                                    <div style="font-size:0.72rem; color:#64748b; font-weight:600; margin-top:4px;">ข้าม (เหมือนเดิม)</div>
                                </div>
                                <div style="background:linear-gradient(180deg,#fff1f2 0%,#ffffff 65%); border:1px solid #ffe4e6; border-top:3px solid #e11d48; border-radius:14px; padding:18px 14px 16px; text-align:center; box-shadow:0 4px 14px rgba(225,29,72,0.1);">
                                    <div style="width:34px; height:34px; border-radius:10px; background:linear-gradient(135deg,#fb7185,#e11d48); display:flex; align-items:center; justify-content:center; margin:0 auto 10px; box-shadow:0 4px 10px rgba(225,29,72,0.35);">
                                        <i class="fas fa-retweet" style="color:#fff; font-size:0.8rem;"></i>
                                    </div>
                                    <div style="font-size:1.7rem; font-weight:800; color:#be123c; line-height:1.2;">{{ $r['replaced'] }}</div>
                                    <div style="font-size:0.72rem; color:#64748b; font-weight:600; margin-top:4px;">บันทึกแทน</div>
                                </div>
                            </div>`,
                        confirmButtonColor: '#28a745',
                        confirmButtonText: '<i class="fas fa-check"></i> ตกลง',
                        showClass: { popup: 'animate__animated animate__fadeInDown' },
                        customClass: { popup: 'swal-import-popup', confirmButton: 'swal-confirm-premium' },
                        buttonsStyling: true,
                        width: '460px',
                        padding: '2em 1.8em',
                    });
                    @endif

                    @if(session('error'))
                        Swal.fire({
                            icon: 'error',
                            title: 'เกิดข้อผิดพลาด',
                            html: '<div style="font-size: 1rem;">{{ session('error') }}</div>',
                            confirmButtonColor: '#dc3545',
                            confirmButtonText: '<i class="fas fa-times"></i> ปิด'
                        });
                    @endif
                });
            </script>
        @endif

        @if($errors->any())
            <script>
                document.addEventListener('DOMContentLoaded', function () {
                    Swal.fire({
                        icon: 'error',
                        title: 'นำเข้าข้อมูลไม่สำเร็จ',
                        html: `<div style="text-align:left; font-size:0.9rem;">
                            <ul style="padding-left:18px; margin:0;">
                                @foreach($errors->all() as $error)
                                    <li>{{ $error }}</li>
                                @endforeach
                            </ul>
                        </div>`,
                        confirmButtonColor: '#dc3545',
                        confirmButtonText: '<i class="fas fa-times"></i> ปิด'
                    }).then(function () {
                        $('#importHiModal').modal('show');
                    });
                });
            </script>
        @endif


        <div class="card-body p-0" id="hi-table-container">
            @include('admin.hi.table')
        </div>
    </div>
    <!-- Import Excel Modal -->
    <div class="modal fade" id="importHiModal" tabindex="-1" role="dialog" aria-labelledby="importHiModalLabel"
        aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered" style="max-width: 560px;" role="document">
            <div class="modal-content" style="border-radius: 12px; border: none;">
                <div class="modal-header align-items-center"
                    style="background: linear-gradient(135deg, #4f46e5 0%, #3730a3 100%); border-radius: 12px 12px 0 0; border-bottom: none; padding: 18px 24px;">
                    <h6 class="modal-title mb-0 font-weight-bold" id="importHiModalLabel" style="color: #fff;">
                        <i class="fas fa-file-import"></i> นำเข้าข้อมูล (Excel)
                    </h6>
                    <button type="button" class="close" data-dismiss="modal" aria-label="Close"
                        style="color: #fff; opacity: 0.85; text-shadow: none;">
                        <span aria-hidden="true">&times;</span>
                    </button>
                </div>
                <div class="card-body" style="padding: 24px 26px 26px;">
                    <form id="hiImportForm" action="{{ route('admin.hi.import') }}" method="POST" enctype="multipart/form-data">
                        @csrf

                        <div class="imp-step">
                            <div class="imp-num" id="hiStep1Num">1</div>
                            <div class="imp-step-body">
                                <div class="imp-step-title">เลือกปีงบประมาณและจังหวัด</div>
                                <div class="imp-2col">
                                    <div>
                                        <label class="imp-label">ปีงบประมาณ <span class="text-danger">*</span></label>
                                        <select class="form-control form-control-sm imp-select" name="year" id="hiImportYearSelect" required shadow-sm
                                            onchange="updateHiStep1Done()">
                                            <option value="">เลือกปี</option>
                                            @foreach($years as $y)
                                                <option value="{{ $y }}" {{ old('year') == $y ? 'selected' : '' }}>{{ $y }}</option>
                                            @endforeach
                                        </select>
                                    </div>
                                    <div>
                                        <label class="imp-label">จังหวัด <span class="text-danger">*</span></label>
                                        <select class="form-control form-control-sm imp-select" name="province_id" id="hiImportProvinceSelect" required
                                            onchange="updateHiStep1Done()">
                                            <option value="">เลือกจังหวัด</option>
                                            @foreach($provinces as $province)
                                                <option value="{{ $province->province_id }}" {{ old('province_id') == $province->province_id ? 'selected' : '' }}>{{ $province->province_name }}</option>
                                            @endforeach
                                        </select>
                                    </div>
                                </div>
                            </div>
                        </div>
                        <script>
                            function updateHiStep1Done() {
                                var year = document.getElementById('hiImportYearSelect').value;
                                var province = document.getElementById('hiImportProvinceSelect').value;
                                document.getElementById('hiStep1Num').classList.toggle('imp-num-done', year !== '' && province !== '');
                            }
                            updateHiStep1Done();
                        </script>

                        <div class="imp-step">
                            <div class="imp-num imp-num-done">2</div>
                            <div class="imp-step-body">
                                <div class="imp-step-title">การจัดการเมื่อพบข้อมูลซ้ำ</div>
                                <div style="display: flex; flex-direction: column; gap: 10px;">

                                    <!-- Skip Option -->
                                    <label for="dup_skip" style="display:flex; align-items:flex-start; gap:12px; padding:12px 14px; border:2px solid #e2e8f0; border-radius:10px; cursor:pointer; transition:border-color .2s;" class="dup-option-label" data-for="dup_skip">
                                        <input type="radio" id="dup_skip" name="duplicate_action" value="skip" {{ old('duplicate_action', 'skip') == 'skip' ? 'checked' : '' }}
                                            style="margin-top:3px; accent-color:#4e73df; width:16px; height:16px; flex-shrink:0;">
                                        <div>
                                            <div style="font-weight:700; color:#4e73df; font-size:0.9rem;">
                                                <i class="fas fa-fast-forward mr-1"></i> ข้ามข้อมูลที่ซ้ำกัน
                                            </div>
                                            <div style="font-size:0.78rem; color:#64748b; margin-top:3px;">
                                                ระบบจะไม่นำเข้าแถวที่ตรวจพบว่าซ้ำกับในระบบอยู่แล้ว<br>
                                                <span style="color:#f59e0b;">หากค่าในแถวมีการเปลี่ยนแปลง ระบบจะอัปเดตให้อัตโนมัติ</span>
                                            </div>
                                        </div>
                                    </label>

                                    <!-- Replace Option -->
                                    <label for="dup_replace" style="display:flex; align-items:flex-start; gap:12px; padding:12px 14px; border:2px solid #e2e8f0; border-radius:10px; cursor:pointer; transition:border-color .2s;" class="dup-option-label" data-for="dup_replace">
                                        <input type="radio" id="dup_replace" name="duplicate_action" value="replace" {{ old('duplicate_action') == 'replace' ? 'checked' : '' }}
                                            style="margin-top:3px; accent-color:#e74a3b; width:16px; height:16px; flex-shrink:0;">
                                        <div>
                                            <div style="font-weight:700; color:#e74a3b; font-size:0.9rem;">
                                                <i class="fas fa-sync-alt mr-1"></i> บันทึกแทนข้อมูลที่ซ้ำกัน
                                            </div>
                                            <div style="font-size:0.78rem; color:#64748b; margin-top:3px;">
                                                ระบบจะลบข้อมูลเดิมที่ซ้ำกันออก แล้วแทนที่ด้วยข้อมูลใหม่จากไฟล์
                                            </div>
                                        </div>
                                    </label>

                                </div>
                            </div>
                        </div>

                        <div class="imp-step imp-step-last">
                            <div class="imp-num" id="hiStep3Num">3</div>
                            <div class="imp-step-body">
                                <div class="imp-step-title">อัปโหลดไฟล์ Excel</div>
                                <div style="font-size: 0.72rem; color: #94a3b8; font-weight: 500; margin: 2px 0 8px;">รองรับเฉพาะไฟล์ .xlsx เท่านั้น</div>
                                <div class="imp-dropzone" onclick="document.getElementById('hiFileInput').click()">
                                    <i class="fas fa-cloud-upload-alt"></i>
                                    <div>
                                        <b id="hiFileLabel">คลิกเพื่อเลือกไฟล์</b>
                                        <span>.xlsx เท่านั้น</span>
                                    </div>
                                </div>
                                <input type="file" id="hiFileInput" name="file" class="d-none" required
                                    accept=".xlsx,application/vnd.openxmlformats-officedocument.spreadsheetml.sheet"
                                    onchange="validateExcelFile(this, 'hiFileLabel', 'hiStep3Num', 'คลิกเพื่อเลือกไฟล์', '#28a745')">
                            </div>
                        </div>

                        <button type="submit" class="imp-submit">
                            <i class="fas fa-upload"></i> นำเข้าข้อมูล
                        </button>
                    </form>
                </div>
            </div>
        </div>
    </div>

    <!-- Loading Overlay (shown while the import form submits) -->
    <div id="hiLoadingOverlay" class="hi-loading-overlay">
        <div class="hi-loading-card">
            <div class="hi-upload-progress-track">
                <div class="hi-upload-progress-fill"></div>
            </div>
            <h5 class="mt-4 font-weight-bold text-primary" style="letter-spacing: 1px;">กำลังบันทึกข้อมูล...</h5>
            <p class="text-muted mb-0">กรุณารอสักครู่ ห้ามปิดหน้าจอจนกว่าจะเสร็จสิ้น</p>
        </div>
    </div>

@endsection

@section('extra_css')
    <style>
        .stats-frame {
            background: #fff;
            border: 1px solid #eef1f6;
            border-radius: 18px;
            box-shadow: 0 4px 20px rgba(15, 23, 42, 0.06);
            padding: 20px;
            margin-bottom: 20px;
        }

        .table-container {
            background: #fff;
            border: 1px solid #eef1f6;
            border-radius: 18px;
            box-shadow: 0 4px 20px rgba(15, 23, 42, 0.06);
            overflow: hidden;
        }

        .hi-table-title-bar {
            display: flex;
            align-items: center;
            justify-content: space-between;
            flex-wrap: wrap;
            gap: 10px;
            padding: 18px 20px;
            border-bottom: 1px solid #eef1f6;
        }

        .list-header {
            background: #fff;
            border: 1px solid #eef1f6;
            border-radius: 18px;
            box-shadow: 0 4px 20px rgba(15, 23, 42, 0.06);
            padding: 20px;
        }

        .btn-toggle-form {
            display: inline-flex;
            align-items: center;
            cursor: pointer;
        }

        .hi-section-title {
            font-size: 0.92rem;
            letter-spacing: 0.01em;
        }

        .hi-section-title i {
            font-size: 0.82rem;
            margin-right: 2px;
        }

        .hi-filter-label {
            font-size: 0.72rem !important;
            letter-spacing: 0.01em;
        }

        .filter-input {
            font-size: 0.82rem;
            border-color: #e2e8f0;
            transition: border-color .15s ease, box-shadow .15s ease;
        }

        .filter-input:focus {
            border-color: #4e73df;
            box-shadow: 0 0 0 3px rgba(78, 115, 223, 0.12);
        }

        .hi-btn-modern {
            font-size: 0.78rem;
            transition: transform .15s ease, box-shadow .15s ease;
        }

        .hi-btn-modern:hover {
            transform: translateY(-1px);
        }

        .hi-province-card {
            transition: transform .15s ease, box-shadow .15s ease;
        }

        .hi-province-card:hover {
            transform: translateY(-2px);
            box-shadow: 0 8px 16px rgba(15, 23, 42, 0.08);
        }

        .table th {
            font-weight: 700;
            font-size: 0.78rem;
            color: #64748b;
            border-bottom: 2px solid #e2e8f0;
            padding: 12px 8px;
        }

        .table td {
            vertical-align: middle;
            padding: 12px 8px;
        }

        .pagination {
            margin-bottom: 0;
        }

        .form-control-sm,
        .btn-sm {
            border-radius: 6px;
        }

        .filter-actions-row {
            padding-top: 12px;
            margin-top: 4px;
            border-top: 1px solid #eef1f5;
        }

        .table tbody tr:nth-child(even) {
            background-color: #fafbfc;
        }

        .table thead th.th-month-group {
            background-color: #eef2ff;
            color: #4338ca;
            border-left: 2px solid #e2e8f0 !important;
        }

        .table thead th.th-month-first {
            border-left: 2px solid #e2e8f0 !important;
        }

        /* Duplicate action card highlight */
        .dup-option-label.active-skip  { border-color: #4e73df !important; background: #f0f4ff; }
        .dup-option-label.active-replace { border-color: #e74a3b !important; background: #fff5f5; }

        /* Import modal - numbered steps */
        .imp-step { display: flex; gap: 14px; margin-bottom: 22px; position: relative; }
        .imp-step:not(.imp-step-last)::before {
            content: ""; position: absolute; left: 15px; top: 34px; bottom: -22px; width: 2px; background: #e2e8f0;
        }
        .imp-num {
            width: 32px; height: 32px; border-radius: 50%; background: #eef2ff; color: #4f46e5;
            display: flex; align-items: center; justify-content: center; font-weight: 800; font-size: 0.9rem;
            flex-shrink: 0; z-index: 1; border: 2px solid #fff; box-shadow: 0 0 0 2px #e2e8f0;
        }
        .imp-step-body { flex: 1; padding-top: 4px; }
        .imp-step-title { font-size: 0.92rem; font-weight: 700; color: #1e293b; margin-bottom: 10px; }
        .imp-2col { display: grid; grid-template-columns: 1fr 1fr; gap: 14px; }
        .imp-label { font-weight: 700; color: #475569; font-size: 0.82rem; display: block; margin-bottom: 6px; }
        .imp-select {
            border-radius: 10px !important; border: 2px solid #e2e8f0 !important; font-weight: 600;
            padding: 8px 12px !important; height: auto !important;
        }
        .imp-dropzone {
            border: 2px dashed #cbd5e1; border-radius: 14px; padding: 16px; text-align: center; cursor: pointer;
            background: #f8fafc; display: flex; align-items: center; justify-content: center; gap: 12px; transition: .2s;
        }
        .imp-dropzone:hover { border-color: #6366f1; background: #f5f6ff; }
        .imp-dropzone i { font-size: 1.4rem; color: #6366f1; }
        .imp-dropzone b { font-size: 0.85rem; color: #334155; display: block; }
        .imp-dropzone span { font-size: 0.74rem; color: #94a3b8; }
        .imp-submit {
            width: 100%; border: none; border-radius: 12px; padding: 13px; margin-top: 4px;
            background: linear-gradient(135deg, #4f46e5 0%, #3730a3 100%); color: #fff;
            font-family: 'Noto Serif Thai', sans-serif; font-size: 0.95rem; font-weight: 700; cursor: pointer;
            display: flex; align-items: center; justify-content: center; gap: 8px;
            box-shadow: 0 8px 18px rgba(79,70,229,0.3); transition: .2s;
        }
        .imp-submit:hover { transform: translateY(-1px); box-shadow: 0 10px 22px rgba(79,70,229,0.4); }

        .imp-num-done {
            background: linear-gradient(135deg, #34d399 0%, #059669 100%) !important;
            color: #fff !important;
            box-shadow: 0 0 0 2px #fff, 0 0 0 4px #a7f3d0 !important;
        }

        .swal-import-popup {
            border-radius: 20px !important;
            background: linear-gradient(160deg, #fdfefe 0%, #f3f5f8 100%) !important;
        }
        .swal-confirm-premium {
            border-radius: 12px !important;
            padding: 11px 34px !important;
            font-weight: 700 !important;
            font-size: 0.9rem !important;
            box-shadow: 0 6px 16px rgba(0,0,0,0.15) !important;
            transition: all .2s ease !important;
        }
        .swal-confirm-premium:hover {
            transform: translateY(-1px);
            box-shadow: 0 8px 20px rgba(0,0,0,0.22) !important;
        }

        /* Loading overlay for the import form submit */
        .hi-loading-overlay {
            display: none;
            position: fixed;
            top: 0;
            left: var(--sidebar-width);
            width: calc(100% - var(--sidebar-width));
            height: 100%;
            background: rgba(255, 255, 255, 0.8);
            backdrop-filter: blur(5px);
            flex-direction: column;
            align-items: center;
            justify-content: center;
            z-index: 10000;
        }

        @media (max-width: 768px) {
            .hi-loading-overlay {
                left: 0;
                width: 100%;
            }
        }

        .hi-loading-card {
            background: rgba(255, 255, 255, 0.92);
            border-radius: 24px;
            padding: 44px 54px;
            box-shadow: 0 20px 50px rgba(79, 70, 229, 0.18);
            display: flex;
            flex-direction: column;
            align-items: center;
            max-width: 90vw;
        }

        .hi-upload-progress-track {
            width: 260px;
            height: 14px;
            background: #e2e8f0;
            border-radius: 999px;
            overflow: hidden;
            box-shadow: inset 0 1px 3px rgba(0, 0, 0, 0.08);
            position: relative;
        }

        .hi-upload-progress-fill {
            position: absolute;
            top: 0;
            left: -40%;
            width: 40%;
            height: 100%;
            background: linear-gradient(90deg, #4f46e5, #818cf8);
            border-radius: 999px;
            box-shadow: 0 0 10px rgba(79, 70, 229, 0.45);
            animation: hiProgressIndeterminate 1.3s ease-in-out infinite;
        }

        @keyframes hiProgressIndeterminate {
            0% {
                left: -40%;
            }

            60% {
                left: 100%;
            }

            100% {
                left: 100%;
            }
        }
    </style>
@endsection

@section('extra_js')
    <script>
        console.log('=== HI Filter Script Loaded ===');

        // Reject anything that isn't a real .xlsx file (belt-and-suspenders
        // alongside the <input accept> filter, which some browsers/OSes let
        // users bypass via an "all files" option in the picker).
        function validateExcelFile(input, statusId, stepNumId, defaultLabel, confirmColor) {
            const file = input.files[0];
            if (!file) return;

            if (!/\.xlsx$/i.test(file.name)) {
                input.value = '';
                document.getElementById(statusId).textContent = defaultLabel;
                const stepNum = document.getElementById(stepNumId);
                if (stepNum) stepNum.classList.remove('imp-num-done');
                Swal.fire({
                    icon: 'warning',
                    title: 'รองรับเฉพาะไฟล์ .xlsx',
                    text: 'กรุณาเลือกไฟล์ Excel นามสกุล .xlsx เท่านั้น',
                    confirmButtonColor: confirmColor
                });
                return;
            }

            document.getElementById(statusId).textContent = file.name;
            const stepNum = document.getElementById(stepNumId);
            if (stepNum) stepNum.classList.add('imp-num-done');
        }

        $(document).ready(function () {
            console.log('Document ready - jQuery version:', $.fn.jquery);

            // Show the loading overlay once the import form actually
            // submits (native "required" validation already passed).
            document.getElementById('hiImportForm').addEventListener('submit', function () {
                document.getElementById('hiLoadingOverlay').style.display = 'flex';
                // Hide the modal (and its full-screen backdrop) so only
                // our own status card shows, with the sidebar left clear.
                setTimeout(function () {
                    $('#importHiModal').modal('hide');
                }, 100);
            });

            // Highlight selected duplicate-action card
            function updateDupCards() {
                $('.dup-option-label').removeClass('active-skip active-replace');
                var val = $('input[name="duplicate_action"]:checked').val();
                if (val === 'skip')    $('.dup-option-label[data-for="dup_skip"]').addClass('active-skip');
                if (val === 'replace') $('.dup-option-label[data-for="dup_replace"]').addClass('active-replace');
            }
            updateDupCards();
            $('input[name="duplicate_action"]').on('change', updateDupCards);


            let searchTimer;

            function fetchData(url) {
                if (!url) {
                    url = "{{ route('admin.hi.index') }}";
                }

                console.log('Fetching data from:', url);
                $('#table-loader').removeClass('d-none').addClass('d-flex');

                let formData = $('#filter-form').serialize();
                console.log('Form data:', formData);

                $.ajax({
                    url: url,
                    type: 'GET',
                    data: formData,
                    headers: {
                        'X-Requested-With': 'XMLHttpRequest'
                    },
                    success: function (response) {
                        console.log('Success! Response length:', response.length);
                        $('#hi-table-container').html(response);

                        let total = $('#hi-total-count').val() || 0;
                        console.log('Total count:', total);
                        $('#total-badge').text('ทั้งหมด ' + parseInt(total).toLocaleString() + ' รายการ');

                        $('#table-loader').removeClass('d-flex').addClass('d-none');
                    },
                    error: function (xhr, status, error) {
                        console.error('AJAX Error:', status, error);
                        console.error('Status code:', xhr.status);
                        console.error('Response:', xhr.responseText);
                        $('#table-loader').removeClass('d-flex').addClass('d-none');
                        Swal.fire({ icon: 'error', title: 'เกิดข้อผิดพลาด', text: error, confirmButtonColor: '#ef4444', confirmButtonText: 'ตกลง' });
                    }
                });
            }

            // Province change handler
            $('select[name="province_id"]').on('change', function () {
                console.log('Province changed to:', $(this).val());

                let provinceId = $(this).val();
                let districtSelect = $('#filter-district');

                districtSelect.html('<option value="">ทั้งหมด</option>');

                if (provinceId) {
                    console.log('Loading districts for province:', provinceId);
                    $.get("{{ url('/get-districts') }}/" + provinceId, function (data) {
                        console.log('Districts received:', data.length);
                        $.each(data, function (index, district) {
                            districtSelect.append('<option value="' + district.district_name + '">' + district.district_name + '</option>');
                        });
                    }).fail(function (xhr, status, error) {
                        console.error('Failed to load districts:', error);
                    });
                }

                fetchData();
            });

            // Year change handler
            $('select[name="year"]').on('change', function () {
                console.log('Year changed to:', $(this).val());
                fetchData();
            });

            // District change handler
            $('#filter-district').on('change', function () {
                console.log('District changed to:', $(this).val());
                fetchData();
            });

            // Month change handler
            $('#filter-month').on('change', function () {
                console.log('Month changed to:', $(this).val());
                fetchData();
            });

            // Search input handler (if exists)
            $('input[name="search"]').on('input', function () {
                console.log('Search input:', $(this).val());
                clearTimeout(searchTimer);
                searchTimer = setTimeout(function () {
                    fetchData();
                }, 500);
            });

            // Pagination handler
            $(document).on('click', '.pagination a', function (e) {
                e.preventDefault();
                console.log('Pagination clicked');
                let url = $(this).attr('href');
                fetchData(url);
                window.scrollTo(0, 0);
            });

            // Clear filters button
            $('#clear-filters').on('click', function () {
                console.log('Clearing filters');
                $('#filter-form')[0].reset();
                $('#filter-district').html('<option value="">ทั้งหมด</option>');
                fetchData();
            });

            // Delete filtered data button
            $('#delete-filtered').on('click', function () {
                console.log('Delete filtered clicked');

                let formData = $('#filter-form').serialize();
                let totalCount = $('#hi-total-count').val() || 0;

                if (totalCount == 0) {
                    Swal.fire({
                        icon: 'warning',
                        title: 'ไม่มีข้อมูลที่จะลบ',
                        text: 'กรุณาเลือกเงื่อนไขการกรองก่อน',
                        confirmButtonText: 'ตกลง',
                        confirmButtonColor: '#6c757d'
                    });
                    return;
                }

                Swal.fire({
                    title: 'ยืนยันการลบข้อมูล',
                    html: `
                                <div style="text-align: left; padding: 10px;">
                                    <p style="font-size: 1rem; margin-bottom: 10px;">คุณต้องการลบข้อมูลทั้งหมดที่กรองได้หรือไม่?</p>
                                    <div style="background: #f8f9fa; padding: 15px; border-radius: 8px; margin: 15px 0;">
                                        <div style="display: flex; align-items: center; margin-bottom: 8px;">
                                            <i class="fas fa-database" style="color: #dc3545; margin-right: 10px;"></i>
                                            <span style="font-weight: bold;">จำนวนข้อมูลที่จะถูกลบ:</span>
                                        </div>
                                        <div style="font-size: 1.5rem; font-weight: bold; color: #dc3545; text-align: center; margin-top: 5px;">
                                            ${parseInt(totalCount).toLocaleString()} รายการ
                                        </div>
                                    </div>
                                    <div style="background: #fff3cd; border-left: 4px solid #ffc107; padding: 12px; border-radius: 4px; margin-top: 15px;">
                                        <i class="fas fa-exclamation-triangle" style="color: #856404; margin-right: 8px;"></i>
                                        <strong style="color: #856404;">คำเตือน:</strong>
                                        <span style="color: #856404;"> การกระทำนี้ไม่สามารถยกเลิกได้!</span>
                                    </div>
                                </div>
                            `,
                    icon: 'warning',
                    showCancelButton: true,
                    confirmButtonColor: '#dc3545',
                    cancelButtonColor: '#6c757d',
                    confirmButtonText: '<i class="fas fa-trash-alt"></i> ยืนยันการลบ',
                    cancelButtonText: '<i class="fas fa-times"></i> ยกเลิก',
                    reverseButtons: true,
                    width: '500px'
                }).then((result) => {
                    if (result.isConfirmed) {
                        console.log('Deleting with params:', formData);

                        // Show loading
                        Swal.fire({
                            title: 'กำลังลบข้อมูล...',
                            html: 'กรุณารอสักครู่',
                            allowOutsideClick: false,
                            allowEscapeKey: false,
                            didOpen: () => {
                                Swal.showLoading();
                            }
                        });

                        $.ajax({
                            url: "{{ route('admin.hi.delete-filtered') }}",
                            type: 'DELETE',
                            data: formData,
                            headers: {
                                'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content'),
                                'X-Requested-With': 'XMLHttpRequest'
                            },
                            success: function (response) {
                                console.log('Delete success:', response);
                                Swal.fire({
                                    icon: 'success',
                                    title: 'สำเร็จ!',
                                    html: `
                                                <div style="font-size: 1.1rem;">
                                                    ${response.message}
                                                </div>
                                            `,
                                    confirmButtonColor: '#28a745',
                                    confirmButtonText: 'ตกลง'
                                }).then(() => {
                                    fetchData(); // Refresh the table
                                });
                            },
                            error: function (xhr, status, error) {
                                console.error('Delete error:', xhr.responseText);
                                let errorMsg = 'เกิดข้อผิดพลาด';
                                try {
                                    let response = JSON.parse(xhr.responseText);
                                    errorMsg = response.message || errorMsg;
                                } catch (e) {
                                    errorMsg += ': ' + error;
                                }
                                Swal.fire({
                                    icon: 'error',
                                    title: 'เกิดข้อผิดพลาด',
                                    text: errorMsg,
                                    confirmButtonColor: '#dc3545',
                                    confirmButtonText: 'ปิด'
                                });
                            }
                        });
                    }
                });
            });

            console.log('All event handlers attached');
        });
    </script>
@endsection
