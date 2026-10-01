@extends('layouts.admin')

@section('title', 'จัดการปีงบประมาณ')

@section('header_title')
    <div class="page-header">
        <div class="page-header-accent"></div>
        <div class="page-header-text">
            <span class="page-header-title">จัดการปีงบประมาณ</span>
            <small class="page-header-subtitle">
                <i class="fas fa-circle-info"></i>
                เพิ่ม/ลบปีงบประมาณที่ใช้ในตัวกรองค้นหาและฟอร์มอัปโหลดไฟล์ ของแต่ละหัวข้อ
            </small>
        </div>
    </div>
@endsection

@section('content')
    <div style="background: linear-gradient(135deg, #eff6ff 0%, #f0f9ff 100%); border: 2px solid #bfdbfe; border-radius: 14px; padding: 16px 20px; margin-bottom: 22px; display: flex; gap: 12px; align-items: flex-start;">
        <i class="fas fa-info-circle" style="color: #2563eb; font-size: 1.1rem; margin-top: 2px;"></i>
        <div style="font-family: 'Noto Serif Thai', 'TH Sarabun New', 'TH Sarabun PS', sans-serif; font-size: 0.85rem; font-weight: 600; color: #1e3a8a; line-height: 1.6;">
            <strong>ปีที่เพิ่มที่นี่จะไปปรากฏในตัวกรองค้นหาและฟอร์มอัปโหลดไฟล์ของหัวข้อนั้นทันที</strong> แม้ยังไม่มีข้อมูลจริงก็ตาม<br>
            ปีที่มีจุด <span style="display:inline-flex; align-items:center; gap:4px; font-weight:700;"><span style="width:8px;height:8px;border-radius:50%;background:#16a34a;display:inline-block;"></span>เขียว</span>
            คือมีข้อมูลจริงอยู่แล้ว - กดไอคอน <i class="fas fa-eye-slash"></i> เพื่อ<strong>ซ่อน</strong>ปีนั้นจากตัวกรอง/อัปโหลดได้โดยไม่ลบข้อมูลเดิม
            (ปีที่ถูกซ่อนจะแสดงเป็นสีเทา กดปุ่มคืนค่าเพื่อนำกลับมาแสดงได้ทุกเมื่อ)
        </div>
    </div>

    <style>
        .fiscal-year-row {
            display: grid;
            grid-template-columns: minmax(220px, 280px) 1fr auto auto;
            align-items: center;
            gap: 18px;
            padding: 16px 22px;
        }

        .fiscal-year-row:not(:last-child) {
            border-bottom: 1px solid #f1f5f9;
        }

        @media (max-width: 900px) {
            .fiscal-year-row {
                grid-template-columns: 1fr;
                row-gap: 10px;
            }
        }
    </style>

    <div style="background: #fff; border-radius: 16px; border: 1px solid #eef2f7; box-shadow: 0 4px 16px rgba(15, 23, 42, 0.06); overflow: hidden;">
        @foreach ($modules as $module)
            <div class="fiscal-year-row">
                <div style="display: flex; align-items: center; gap: 12px; min-width: 0;">
                    <div style="width: 40px; height: 40px; border-radius: 12px; background: {{ $module['color'] }}1a; display: flex; align-items: center; justify-content: center; flex-shrink: 0;">
                        <i class="fas {{ $module['icon'] }}" style="color: {{ $module['color'] }}; font-size: 1rem;"></i>
                    </div>
                    <div style="min-width: 0;">
                        <div style="font-family: 'Noto Serif Thai', 'TH Sarabun New', 'TH Sarabun PS', sans-serif; font-size: 0.92rem; font-weight: 700; color: #1e293b; line-height: 1.3; white-space: nowrap; overflow: hidden; text-overflow: ellipsis;">
                            {{ $module['title'] }}
                        </div>
                        @if ($module['subtitle'])
                            <div style="font-family: 'Noto Serif Thai', 'TH Sarabun New', 'TH Sarabun PS', sans-serif; font-size: 0.7rem; color: #94a3b8; font-weight: 600; margin-top: 1px; white-space: nowrap; overflow: hidden; text-overflow: ellipsis;">
                                {{ $module['subtitle'] }}
                            </div>
                        @endif
                    </div>
                </div>

                <div class="fiscal-year-chip-row" style="display: flex; flex-wrap: wrap; gap: 8px; min-width: 0;">
                    @if ($module['years']->isEmpty())
                        <span style="font-family: 'Noto Serif Thai', 'TH Sarabun New', 'TH Sarabun PS', sans-serif; font-size: 0.78rem; color: #94a3b8; font-weight: 600; font-style: italic;">
                            ยังไม่มีปีงบประมาณในหัวข้อนี้ - กด "เพิ่มปี" เพื่อเริ่มต้น
                        </span>
                    @else
                        @foreach ($module['years'] as $y)
                            @if ($y['is_hidden'])
                                <span class="fiscal-year-chip" style="display: inline-flex; align-items: center; gap: 7px; padding: 6px 10px 6px 14px; border-radius: 999px; background: #f1f5f9; color: #94a3b8; border: 1px dashed #cbd5e1; font-family: 'Noto Serif Thai', 'TH Sarabun New', 'TH Sarabun PS', sans-serif; font-size: 0.8rem; font-weight: 700;">
                                    <i class="fas fa-eye-slash" style="font-size: 0.7rem;" title="ถูกซ่อนจากตัวกรอง/อัปโหลด"></i>
                                    {{ $y['year'] }}
                                    <span style="font-size: 0.65rem; font-weight: 600;">(ซ่อนอยู่)</span>
                                    <button type="button" class="btn-restore-fiscal-year" title="ยกเลิกการซ่อน - นำปีนี้กลับมาแสดง"
                                        data-id="{{ $y['fiscal_year_id'] }}" data-year="{{ $y['year'] }}" data-title="{{ $module['title'] }}"
                                        style="background: rgba(0,0,0,0.06); border: none; border-radius: 50%; width: 17px; height: 17px; padding: 0; display: flex; align-items: center; justify-content: center; color: inherit; font-size: 0.6rem; line-height: 1; cursor: pointer; flex-shrink: 0;">
                                        <i class="fas fa-undo"></i>
                                    </button>
                                </span>
                            @else
                                <span class="fiscal-year-chip" style="display: inline-flex; align-items: center; gap: 7px; padding: 6px 10px 6px 14px; border-radius: 999px; background: {{ $module['color'] }}12; color: {{ $module['color'] }}; border: 1px solid {{ $module['color'] }}33; font-family: 'Noto Serif Thai', 'TH Sarabun New', 'TH Sarabun PS', sans-serif; font-size: 0.8rem; font-weight: 700;">
                                    @if ($y['has_data'])
                                        <span title="มีข้อมูลจริงแล้ว" style="width: 7px; height: 7px; border-radius: 50%; background: #16a34a; flex-shrink: 0;"></span>
                                    @endif
                                    {{ $y['year'] }}
                                    @if ($y['has_data'])
                                        <button type="button" class="btn-hide-fiscal-year" title="ซ่อนปีนี้จากตัวกรอง/อัปโหลด (ไม่ลบข้อมูล)"
                                            data-module="{{ $module['key'] }}" data-year="{{ $y['year'] }}" data-title="{{ $module['title'] }}"
                                            style="background: rgba(0,0,0,0.06); border: none; border-radius: 50%; width: 17px; height: 17px; padding: 0; display: flex; align-items: center; justify-content: center; color: inherit; font-size: 0.55rem; line-height: 1; cursor: pointer; flex-shrink: 0;">
                                            <i class="fas fa-eye-slash"></i>
                                        </button>
                                    @elseif ($y['fiscal_year_id'])
                                        <button type="button" class="btn-remove-fiscal-year" title="ลบปีงบประมาณนี้ออกจากรายการ"
                                            data-id="{{ $y['fiscal_year_id'] }}" data-year="{{ $y['year'] }}" data-title="{{ $module['title'] }}"
                                            style="background: rgba(0,0,0,0.06); border: none; border-radius: 50%; width: 17px; height: 17px; padding: 0; display: flex; align-items: center; justify-content: center; color: inherit; font-size: 0.6rem; line-height: 1; cursor: pointer; flex-shrink: 0;">
                                            <i class="fas fa-times"></i>
                                        </button>
                                    @endif
                                </span>
                            @endif
                        @endforeach
                    @endif
                </div>

                <div style="font-family: 'Noto Serif Thai', 'TH Sarabun New', 'TH Sarabun PS', sans-serif; font-size: 0.76rem; color: #94a3b8; font-weight: 600; white-space: nowrap; text-align: right;">
                    ทั้งหมด {{ $module['years']->count() }} ปี
                </div>

                <button type="button" class="btn-add-fiscal-year" data-module="{{ $module['key'] }}"
                    data-title="{{ $module['title'] }}" data-color="{{ $module['color'] }}"
                    data-max-year="{{ $module['years']->max('year') ?? (date('Y') + 543 - 1) }}"
                    style="background: {{ $module['color'] }}; color: #fff; border: none; border-radius: 999px; padding: 8px 16px; white-space: nowrap; font-family: 'Noto Serif Thai', 'TH Sarabun New', 'TH Sarabun PS', sans-serif; font-size: 0.82rem; font-weight: 700; display: flex; align-items: center; gap: 6px; flex-shrink: 0; box-shadow: 0 2px 8px {{ $module['color'] }}40; transition: opacity 0.15s ease, transform 0.15s ease;"
                    onmouseover="this.style.opacity='0.85'; this.style.transform='translateY(-1px)';"
                    onmouseout="this.style.opacity='1'; this.style.transform='translateY(0)';">
                    <i class="fas fa-plus"></i> เพิ่มปี
                </button>
            </div>
        @endforeach
    </div>

    <!-- Add Fiscal Year Modal -->
    <div class="modal fade" id="addFiscalYearModal" tabindex="-1" role="dialog" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered" role="document" style="max-width: 420px;">
            <div class="modal-content" style="border-radius: 16px; border: none; overflow: hidden; box-shadow: 0 20px 50px rgba(15,23,42,0.2);">
                <form id="addFiscalYearForm">
                    <div id="addFiscalYearHeader" style="padding: 20px 24px; background: #7c3aed;">
                        <div style="font-family: 'Noto Serif Thai', 'TH Sarabun New', 'TH Sarabun PS', sans-serif; font-size: 0.72rem; font-weight: 600; color: rgba(255,255,255,0.8); text-transform: uppercase; letter-spacing: 0.5px;">
                            เพิ่มปีงบประมาณ
                        </div>
                        <div id="addFiscalYearModuleTitle" style="font-family: 'Noto Serif Thai', 'TH Sarabun New', 'TH Sarabun PS', sans-serif; font-size: 1.1rem; font-weight: 700; color: #fff; margin-top: 2px;">
                            &nbsp;
                        </div>
                    </div>
                    <div class="modal-body" style="padding: 24px;">
                        <input type="hidden" name="module" id="addFiscalYearModule">
                        <label style="display: block; margin-bottom: 8px; font-family: 'Noto Serif Thai', 'TH Sarabun New', 'TH Sarabun PS', sans-serif; font-size: 0.85rem; font-weight: 600; color: #475569;">
                            ปีงบประมาณ (พ.ศ.)
                        </label>
                        <input type="number" name="year" id="addFiscalYearInput" class="form-control" required min="2500" max="2700"
                            style="border-radius: 10px; border: 2px solid #e2e8f0; font-family: 'Noto Serif Thai', 'TH Sarabun New', 'TH Sarabun PS', sans-serif; font-size: 1.05rem; font-weight: 700; padding: 12px 14px; height: auto; text-align: center;"
                            placeholder="เช่น 2570">
                        <div style="font-family: 'Noto Serif Thai', 'TH Sarabun New', 'TH Sarabun PS', sans-serif; font-size: 0.75rem; font-weight: 600; color: #94a3b8; margin-top: 8px;">
                            ปีนี้จะปรากฏในตัวกรองค้นหาและฟอร์มอัปโหลดไฟล์ของหัวข้อนี้ทันที
                        </div>
                    </div>
                    <div class="modal-footer" style="padding: 14px 24px; background: #f8fafc; border-top: 1px solid #eef2f7; display: flex; gap: 10px;">
                        <button type="button" class="btn" data-dismiss="modal"
                            style="flex: 1; border-radius: 10px; border: 2px solid #e2e8f0; background: #fff; color: #475569; font-family: 'Noto Serif Thai', 'TH Sarabun New', 'TH Sarabun PS', sans-serif; font-size: 0.85rem; font-weight: 600; padding: 10px;">
                            ยกเลิก
                        </button>
                        <button type="submit" id="addFiscalYearSubmit"
                            style="flex: 1; border-radius: 10px; border: none; background: #7c3aed; color: #fff; font-family: 'Noto Serif Thai', 'TH Sarabun New', 'TH Sarabun PS', sans-serif; font-size: 0.85rem; font-weight: 700; padding: 10px;">
                            <i class="fas fa-plus"></i> เพิ่มปีงบประมาณ
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>
@endsection

@section('extra_js')
    <script>
        $(function () {
            const csrfToken = $('meta[name="csrf-token"]').attr('content');

            // Open the shared "add year" modal, primed for whichever card's
            // button was clicked (title/color/module key + a sensible
            // default next-year suggestion based on that card's own highest
            // year so far).
            $('.btn-add-fiscal-year').on('click', function () {
                const module = $(this).data('module');
                const title = $(this).data('title');
                const color = $(this).data('color');
                const maxYear = parseInt($(this).data('max-year'), 10) || (new Date().getFullYear() + 543 - 1);

                $('#addFiscalYearModule').val(module);
                $('#addFiscalYearModuleTitle').text(title);
                $('#addFiscalYearHeader').css('background', color);
                $('#addFiscalYearSubmit').css('background', color);
                $('#addFiscalYearInput').val(maxYear + 1);

                $('#addFiscalYearModal').modal('show');
            });

            $('#addFiscalYearModal').on('shown.bs.modal', function () {
                $('#addFiscalYearInput').trigger('select');
            });

            $('#addFiscalYearForm').on('submit', function (e) {
                e.preventDefault();

                const $submit = $('#addFiscalYearSubmit');
                $submit.prop('disabled', true);

                $.ajax({
                    url: "{{ route('admin.fiscal-years.store') }}",
                    type: 'POST',
                    data: {
                        module: $('#addFiscalYearModule').val(),
                        year: $('#addFiscalYearInput').val(),
                    },
                    headers: {
                        'X-CSRF-TOKEN': csrfToken,
                        'X-Requested-With': 'XMLHttpRequest'
                    },
                    success: function (response) {
                        $('#addFiscalYearModal').modal('hide');
                        Swal.fire({
                            icon: 'success',
                            title: 'สำเร็จ!',
                            text: response.message,
                            confirmButtonColor: '#28a745',
                            confirmButtonText: 'ตกลง'
                        }).then(() => location.reload());
                    },
                    error: function (xhr) {
                        let errorMsg = 'เกิดข้อผิดพลาด';
                        try {
                            const response = JSON.parse(xhr.responseText);
                            if (response.errors) {
                                errorMsg = Object.values(response.errors).flat().join('\n');
                            } else if (response.message) {
                                errorMsg = response.message;
                            }
                        } catch (e) { /* keep default message */ }

                        Swal.fire({
                            icon: 'error',
                            title: 'เกิดข้อผิดพลาด',
                            text: errorMsg,
                            confirmButtonColor: '#dc3545',
                            confirmButtonText: 'ปิด'
                        });
                    },
                    complete: function () {
                        $submit.prop('disabled', false);
                    }
                });
            });

            // Shared helper: DELETE one fiscal_years row by id (used both by
            // "remove" on a manually-added-only chip and "restore" on a
            // hidden chip - the server tells the two apart by whether the
            // row being deleted had hidden=true and replies accordingly).
            function deleteFiscalYearRow(id, onConfirmOptions) {
                Swal.fire(Object.assign({
                    showCancelButton: true,
                    cancelButtonColor: '#6c757d',
                    cancelButtonText: 'ยกเลิก',
                    reverseButtons: true
                }, onConfirmOptions)).then((result) => {
                    if (!result.isConfirmed) return;

                    $.ajax({
                        url: "{{ url('admin/fiscal-years') }}/" + id,
                        type: 'DELETE',
                        headers: {
                            'X-CSRF-TOKEN': csrfToken,
                            'X-Requested-With': 'XMLHttpRequest'
                        },
                        success: function (response) {
                            Swal.fire({
                                icon: 'success',
                                title: 'สำเร็จ!',
                                text: response.message,
                                confirmButtonColor: '#28a745',
                                confirmButtonText: 'ตกลง'
                            }).then(() => location.reload());
                        },
                        error: function (xhr) {
                            let errorMsg = 'เกิดข้อผิดพลาด';
                            try {
                                const response = JSON.parse(xhr.responseText);
                                errorMsg = response.message || errorMsg;
                            } catch (e) { /* keep default message */ }
                            Swal.fire({
                                icon: 'error',
                                title: 'เกิดข้อผิดพลาด',
                                text: errorMsg,
                                confirmButtonColor: '#dc3545',
                                confirmButtonText: 'ปิด'
                            });
                        }
                    });
                });
            }

            // Manually-added year with no real data yet - deletes the
            // "enabled" row outright, so the year disappears entirely.
            $('.btn-remove-fiscal-year').on('click', function () {
                const id = $(this).data('id');
                const year = $(this).data('year');
                const moduleTitle = $(this).data('title');

                deleteFiscalYearRow(id, {
                    icon: 'warning',
                    title: 'ลบปีงบประมาณนี้?',
                    html: `นำปีงบประมาณ <strong>${year}</strong> ออกจากรายการของ "${moduleTitle}"<br>
                           <span style="font-size: 0.85rem; color: #94a3b8;">(ถ้าปีนี้มีข้อมูลจริงอยู่แล้ว จะยังปรากฏในตัวกรองค้นหาเหมือนเดิม)</span>`,
                    confirmButtonColor: '#dc3545',
                    confirmButtonText: '<i class="fas fa-trash-alt"></i> ยืนยันการลบ'
                });
            });

            // A hidden chip's restore button - deletes the "hidden" override
            // row, so the year (which still has its real data) reappears.
            $('.btn-restore-fiscal-year').on('click', function () {
                const id = $(this).data('id');
                const year = $(this).data('year');
                const moduleTitle = $(this).data('title');

                deleteFiscalYearRow(id, {
                    icon: 'question',
                    title: 'นำปีงบประมาณนี้กลับมาแสดง?',
                    html: `นำปีงบประมาณ <strong>${year}</strong> กลับมาแสดงในตัวกรองค้นหา/อัปโหลดของ "${moduleTitle}"`,
                    confirmButtonColor: '#16a34a',
                    confirmButtonText: '<i class="fas fa-undo"></i> ยืนยัน'
                });
            });

            // A real-data chip's hide button - creates (or updates) a
            // fiscal_years row with hidden=1 for this (module, year). Never
            // touches the underlying data - only removes the year from the
            // dropdowns until it's restored above.
            $('.btn-hide-fiscal-year').on('click', function () {
                const module = $(this).data('module');
                const year = $(this).data('year');
                const moduleTitle = $(this).data('title');

                Swal.fire({
                    icon: 'question',
                    title: 'ซ่อนปีงบประมาณนี้?',
                    html: `ซ่อนปีงบประมาณ <strong>${year}</strong> จากตัวกรองค้นหา/อัปโหลดของ "${moduleTitle}"<br>
                           <span style="font-size: 0.85rem; color: #94a3b8;">(ข้อมูลเดิมของปีนี้จะไม่ถูกลบ - กดคืนค่าเพื่อนำกลับมาแสดงได้ทุกเมื่อ)</span>`,
                    showCancelButton: true,
                    confirmButtonColor: '#d97706',
                    cancelButtonColor: '#6c757d',
                    confirmButtonText: '<i class="fas fa-eye-slash"></i> ซ่อนปีนี้',
                    cancelButtonText: 'ยกเลิก',
                    reverseButtons: true
                }).then((result) => {
                    if (!result.isConfirmed) return;

                    $.ajax({
                        url: "{{ route('admin.fiscal-years.store') }}",
                        type: 'POST',
                        data: { module: module, year: year, hidden: 1 },
                        headers: {
                            'X-CSRF-TOKEN': csrfToken,
                            'X-Requested-With': 'XMLHttpRequest'
                        },
                        success: function (response) {
                            Swal.fire({
                                icon: 'success',
                                title: 'สำเร็จ!',
                                text: response.message,
                                confirmButtonColor: '#28a745',
                                confirmButtonText: 'ตกลง'
                            }).then(() => location.reload());
                        },
                        error: function (xhr) {
                            let errorMsg = 'เกิดข้อผิดพลาด';
                            try {
                                const response = JSON.parse(xhr.responseText);
                                errorMsg = response.message || errorMsg;
                            } catch (e) { /* keep default message */ }
                            Swal.fire({
                                icon: 'error',
                                title: 'เกิดข้อผิดพลาด',
                                text: errorMsg,
                                confirmButtonColor: '#dc3545',
                                confirmButtonText: 'ปิด'
                            });
                        }
                    });
                });
            });
        });
    </script>
@endsection
