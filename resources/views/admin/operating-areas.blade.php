@extends('layouts.admin')

@section('title', 'จัดการพื้นที่ดำเนินงาน')

@section('header_title')
    <div class="page-header">
        <div class="page-header-accent"></div>
        <div class="page-header-text">
            <span class="page-header-title">จัดการพื้นที่ดำเนินงาน</span>
            <small class="page-header-subtitle">
                <i class="fas fa-circle-info"></i>
                เลือกจังหวัด อำเภอ ตำบล และชุมชน/หมู่บ้าน แล้วปักหน่วยงานตำแหน่งพื้นที่ดำเนินงานบนแผนที่
            </small>
        </div>
    </div>
@endsection

@section('content')

    @include('partials.flash-alert')

    <div class="toolbar-card">
        <div class="toolbar-head">
            <div class="toolbar-title"><i class="fas fa-map-pin"></i> เลือกพื้นที่และปักหน่วยงาน</div>
        </div>

        <div class="filter-cluster">
            <div class="filter-cluster-label"><i class="fas fa-location-dot"></i> พื้นที่</div>
            <div class="filter-cluster-fields">
                <div class="filter-field">
                    <label>ปีงบประมาณ</label>
                    <select class="filter-input" id="oa_fiscal_year">
                        <option value="">-- เลือกปีงบประมาณ --</option>
                        @foreach ($fiscalYears as $year)
                            <option value="{{ $year }}" {{ (string) $year === '2569' ? 'selected' : '' }}>{{ $year }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="filter-field">
                    <label>จังหวัด</label>
                    <select class="filter-input" id="oa_province">
                        <option value="">-- เลือกจังหวัด --</option>
                        @foreach ($provinces as $province)
                            <option value="{{ $province->province_id }}"
                                data-lat="{{ optional($provinceCoords->get($province->province_id))->lat }}"
                                data-lng="{{ optional($provinceCoords->get($province->province_id))->lng }}">{{ $province->province_name }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="filter-field">
                    <label>อำเภอ</label>
                    <select class="filter-input" id="oa_district" disabled>
                        <option value="">-- เลือกอำเภอ --</option>
                    </select>
                </div>
                <div class="filter-field">
                    <label>ตำบล</label>
                    <select class="filter-input" id="oa_tambon" disabled>
                        <option value="">-- เลือกตำบล --</option>
                    </select>
                </div>
                <div class="filter-field">
                    <label>ชุมชน/หมู่บ้าน</label>
                    <select class="filter-input" id="oa_village" disabled>
                        <option value="">-- เลือกชุมชน/หมู่บ้าน --</option>
                    </select>
                </div>
            </div>
        </div>

        <hr class="cluster-divider">

        <div class="oa-map-head">
            <div class="oa-map-head-ic"><i class="fas fa-map-pin"></i></div>
            <div>
                <div class="oa-map-head-title">หมุดพื้นที่ดำเนินงาน พชอ.ไต</div>
                <div class="oa-map-head-sub">แยกสีตามจังหวัด · <span id="oa_map_summary"></span></div>
            </div>
        </div>
        <div id="oa_map" class="oa-map"></div>
        <div id="oa_map_legend" class="oa-legend"></div>
        <p class="oa-map-hint">
            <i class="fas fa-circle-info"></i>
            เลือกจังหวัด อำเภอ ตำบล หรือชุมชน/หมู่บ้านระดับใดก็ได้ แผนที่และหมุดสีแดงจะเลื่อนไปยังตำแหน่งโดยประมาณให้อัตโนมัติทันที
            (ไม่จำเป็นต้องเลือกจนถึงชุมชน/หมู่บ้าน หากพื้นที่ดำเนินงานกว้างแค่ระดับอำเภอหรือตำบลก็บันทึกได้)
            คลิกหรือลากหมุดสีแดงบนแผนที่เพื่อปรับตำแหน่งที่ตั้งจริงของพื้นที่ดำเนินงาน · ใช้ล้อเมาส์หรือปุ่ม + / − เพื่อซูม
        </p>

        <form id="oa_form" action="{{ route('admin.operating-areas.store') }}" method="POST" class="oa-save-row">
            @csrf
            <input type="hidden" name="fiscal_year" id="oa_input_fiscal_year">
            <input type="hidden" name="province_id" id="oa_input_province">
            <input type="hidden" name="district_id" id="oa_input_district">
            <input type="hidden" name="tambon_id" id="oa_input_tambon">
            <input type="hidden" name="village_id" id="oa_input_village">
            <input type="hidden" name="village_name_snapshot" id="oa_input_village_name">
            <input type="hidden" name="latitude" id="oa_input_lat">
            <input type="hidden" name="longitude" id="oa_input_lng">

            <div class="filter-field oa-label-field">
                <label>หมายเหตุ / ชื่อพื้นที่ (ถ้ามี)</label>
                <input type="text" name="label" id="oa_label" class="filter-input" maxlength="255"
                    placeholder="เช่น จุดรณรงค์ลดเค็มชุมชน...">
            </div>

            @if ($isSuperAdmin)
                {{-- SuperAdmin: เพิ่ม "หมุดแสดงผล" (ย้อนหลัง/ปักใหม่) - แค่บอกว่าพื้นที่นี้มีการดำเนินงานด้าน พชอ.ไต
                     และหน่วยงานใดดำเนินการ ชื่อหน่วยงานพิมพ์เอง ไม่ผูกบัญชี ไม่ล็อกพื้นที่ ไม่เข้าแบบรายงาน --}}
                <div class="oa-display-block">
                    <label class="oa-display-toggle" for="oa_display_only">
                        <input type="checkbox" name="display_only" id="oa_display_only" value="1" {{ old('display_only') ? 'checked' : '' }}>
                        <span><i class="fas fa-eye"></i> บันทึกเป็นหมุดแสดงผล (พื้นที่ที่ดำเนินงาน พชอ.ไต แล้ว)</span>
                    </label>
                    <input type="text" name="agency_label" id="oa_agency_label" class="filter-input"
                        maxlength="255" value="{{ old('agency_label') }}"
                        placeholder="ชื่อหน่วยงานที่ดำเนินงาน เช่น รพ.สต.บ้านโนนสวรรค์"
                        aria-label="ชื่อหน่วยงานที่ดำเนินงาน" {{ old('display_only') ? '' : 'hidden disabled' }}>
                    @error('agency_label')
                        <span class="oa-display-error">{{ $message }}</span>
                    @enderror
                </div>
            @endif

            {{-- ปักได้หลายพื้นที่: เพิ่มเข้ารายการทีละพื้นที่ แล้วบันทึกทั้งหมดครั้งเดียว --}}
            <input type="hidden" name="areas_json" id="oa_areas_json">
            <div class="oa-draft" id="oa_draft_box" style="display:none;">
                <div class="oa-draft-head">
                    <span><i class="fas fa-layer-group"></i> พื้นที่ที่จะบันทึก</span>
                    <span class="oa-draft-count" id="oa_draft_count">0 พื้นที่</span>
                </div>
                <p class="oa-draft-note">เลือกพื้นที่ถัดไปต่อได้เลย แล้วกด "บันทึกทั้งหมด" ครั้งเดียว</p>
                <ul class="oa-draft-list" id="oa_draft_list"></ul>
            </div>

            <button type="button" id="oa_add_btn" class="btn-pill btn-add" disabled>
                <i class="fas fa-plus"></i> เพิ่มพื้นที่นี้เข้ารายการ
            </button>
            <button type="submit" id="oa_save_btn" class="btn-pill btn-import" disabled>
                <i class="fas fa-map-pin"></i> <span id="oa_save_btn_text">บันทึกหน่วยงานพื้นที่ดำเนินงาน</span>
            </button>
            <p class="oa-draft-hint">ดำเนินงานมากกว่า 1 พื้นที่: กด "เพิ่มพื้นที่นี้เข้ารายการ" ทีละพื้นที่ แล้วบันทึกทั้งหมดพร้อมกัน</p>
        </form>
    </div>

    <div class="toolbar-card">
        <div class="toolbar-head">
            <div class="toolbar-title"><i class="fas fa-table"></i> พื้นที่ดำเนินงานที่บันทึกแล้ว</div>

            <div class="toolbar-head-right">
                {{-- Kept in sync with the filtered/paginated $pins total via
                     a tiny JS bridge (see extra_js) - it lives here, outside
                     #oa-table-container, purely so it sits in the same row
                     as the title/filter instead of inside the swapped
                     table partial. --}}
                <span class="oa-count-badge" id="oa_count_badge">{{ number_format($pins->total()) }} รายการ</span>

                <div class="oa-filter-inline">
                    <label for="oa_filter_fiscal_year"><i class="fas fa-calendar-days"></i> ปีงบประมาณ</label>
                    <select class="filter-input oa-filter-select" id="oa_filter_fiscal_year" name="filter_fiscal_year">
                        <option value="">ทั้งหมด</option>
                        @foreach ($fiscalYears as $year)
                            <option value="{{ $year }}" {{ request('filter_fiscal_year') == $year ? 'selected' : '' }}>
                                {{ $year }}
                            </option>
                        @endforeach
                    </select>
                </div>

                <div class="oa-filter-inline">
                    <label for="oa_filter_province"><i class="fas fa-filter"></i> กรองตามจังหวัด</label>
                    <select class="filter-input oa-filter-select" id="oa_filter_province" name="filter_province_id">
                        <option value="">ทั้งหมด</option>
                        @foreach ($provinces as $province)
                            <option value="{{ $province->province_id }}"
                                {{ request('filter_province_id') == $province->province_id ? 'selected' : '' }}>
                                {{ $province->province_name }}
                            </option>
                        @endforeach
                    </select>
                </div>

                <div class="oa-filter-inline">
                    <label for="oa_filter_district"><i class="fas fa-map"></i> อำเภอ</label>
                    <select class="filter-input oa-filter-select" id="oa_filter_district" name="filter_district_id" disabled>
                        <option value="">ทั้งหมด</option>
                    </select>
                </div>

                <button type="button" id="oa_filter_reset" class="oa-filter-reset" hidden>
                    <i class="fas fa-rotate-left"></i> ล้างตัวกรอง
                </button>

                @if ($isSuperAdmin)
                    <button type="button" id="oa_delete_filtered" class="oa-delete-filtered"
                        title="ลบพื้นที่ดำเนินงานทั้งหมดที่ตรงกับตัวกรองปัจจุบัน (ข้ามพื้นที่ที่มีรายงานแล้ว)">
                        <i class="fas fa-trash-can"></i> ลบข้อมูล
                    </button>
                @endif
            </div>
        </div>

        <div id="oa-table-container">
            @include('admin.operating-areas-table')
        </div>
    </div>
@endsection

@section('extra_css')
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/leaflet/1.9.4/leaflet.min.css" />
    <style>
        .toolbar-card {
            background: #fff;
            border: 1px solid #eef1f6;
            border-radius: 16px;
            box-shadow: 0 4px 20px rgba(15, 23, 42, 0.06);
            padding: 20px 22px;
            margin-bottom: 20px;
        }

        .toolbar-head {
            display: flex;
            align-items: center;
            justify-content: space-between;
            gap: 12px;
            margin-bottom: 16px;
            flex-wrap: wrap;
        }

        .toolbar-title {
            font-family: 'Noto Serif Thai', sans-serif;
            font-weight: 700;
            font-size: 0.94rem;
            color: #1e293b;
            display: flex;
            align-items: center;
            gap: 8px;
        }

        .toolbar-title i {
            color: #4f46e5;
        }

        .filter-cluster-label {
            font-size: 0.8rem;
            font-weight: 700;
            color: #475569;
            margin-bottom: 10px;
            display: flex;
            align-items: center;
            gap: 6px;
        }

        .filter-cluster-fields {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(180px, 1fr));
            gap: 14px;
        }

        .filter-field label {
            display: block;
            font-size: 0.78rem;
            color: #64748b;
            margin-bottom: 4px;
            font-weight: 600;
        }

        .filter-input {
            width: 100%;
            border: 1px solid #e2e8f0;
            border-radius: 10px;
            padding: 9px 12px;
            font-size: 0.85rem;
            background: #f8fafc;
        }

        .filter-input:disabled {
            opacity: 0.55;
            cursor: not-allowed;
        }

        .cluster-divider {
            border: none;
            border-top: 1px dashed #e2e8f0;
            margin: 18px 0;
        }

        .oa-map {
            width: 100%;
            height: 420px;
            border-radius: 14px;
            border: 1px solid #e2e8f0;
            margin-top: 4px;
        }

        .oa-map-hint {
            font-size: 0.78rem;
            color: #64748b;
            margin: 10px 2px 0;
        }

        .oa-save-row {
            margin-top: 16px;
            display: flex;
            align-items: flex-end;
            gap: 14px;
            flex-wrap: wrap;
        }

        .oa-label-field {
            flex: 1;
            min-width: 240px;
        }

        /* SuperAdmin: หมุดแสดงผล (ชื่อหน่วยงานพิมพ์เอง) */
        .oa-display-block {
            flex: 1 1 100%;
            display: flex;
            align-items: center;
            gap: 12px;
            flex-wrap: wrap;
            padding: 10px 14px;
            border: 1px dashed #c7d2fe;
            border-radius: 10px;
            background: #f8faff;
        }

        .oa-display-toggle {
            display: inline-flex;
            align-items: center;
            gap: 8px;
            font-size: 0.86rem;
            font-weight: 600;
            color: #3730a3;
            cursor: pointer;
        }

        .oa-display-toggle input {
            width: 16px;
            height: 16px;
            accent-color: #4f46e5;
        }

        .oa-display-block .filter-input {
            flex: 1;
            min-width: 240px;
        }

        .oa-display-error {
            flex: 1 1 100%;
            font-size: 0.78rem;
            color: #dc2626;
        }

        .oa-sel-chip.oa-sel-display {
            background: #e0e7ff;
            color: #3730a3;
        }

        .btn-pill {
            display: inline-flex;
            align-items: center;
            gap: 8px;
            border: none;
            border-radius: 10px;
            padding: 10px 18px;
            font-family: 'Noto Serif Thai', sans-serif;
            font-size: 0.85rem;
            font-weight: 600;
            cursor: pointer;
        }

        .btn-import {
            background: linear-gradient(135deg, #4f46e5, #4338ca);
            color: #fff;
        }

        .btn-add { background: #fff; color: #4338ca; border: 1.5px solid #c7d2fe; }
        .btn-add:hover:not(:disabled) { background: #eef2ff; }
        .btn-add:disabled { opacity: .5; cursor: not-allowed; }
        .oa-draft { flex: 1 1 100%; border: 1px solid #dbe4f3; border-radius: 14px; background: #f7faff; padding: 12px 16px; }
        .oa-draft-head { display: flex; align-items: center; justify-content: space-between; gap: 10px; flex-wrap: wrap; font-size: .88rem; font-weight: 700; color: #1e3a8a; }
        .oa-draft-count { font-size: .76rem; font-weight: 700; color: #1d4ed8; background: #dbeafe; border-radius: 999px; padding: 2px 12px; }
        .oa-draft-note { margin: 4px 0 8px; font-size: .78rem; color: #64748b; }
        .oa-draft-list { list-style: none; margin: 0; padding: 0; display: grid; gap: 6px; }
        .oa-draft-item { display: flex; align-items: center; gap: 10px; background: #fff; border: 1px solid #e2e8f0; border-radius: 10px; padding: 8px 12px; }
        .oa-draft-no { flex: none; width: 24px; height: 24px; border-radius: 50%; background: #4f46e5; color: #fff; font-size: .75rem; font-weight: 700; display: flex; align-items: center; justify-content: center; }
        .oa-draft-text { flex: 1; min-width: 0; font-size: .84rem; color: #1e293b; line-height: 1.45; overflow-wrap: anywhere; }
        .oa-draft-text small { display: block; color: #64748b; font-size: .74rem; }
        .oa-draft-del { flex: none; border: none; background: none; color: #b91c1c; cursor: pointer; font-size: .95rem; padding: 4px 6px; border-radius: 8px; }
        .oa-draft-del:hover { background: #fee2e2; }
        .oa-draft-hint { flex: 1 1 100%; margin: 0; font-size: .78rem; color: #64748b; }

        .btn-import:disabled {
            background: #cbd5e1;
            cursor: not-allowed;
        }

        .btn-icon-danger {
            border: none;
            background: #fee2e2;
            color: #dc2626;
            border-radius: 8px;
            width: 30px;
            height: 30px;
            cursor: pointer;
        }

        .btn-icon-danger:hover {
            background: #fecaca;
        }

        .btn-icon-danger:focus-visible {
            outline: 2px solid #dc2626;
            outline-offset: 2px;
        }

        .oa-delete-form {
            display: inline;
        }

        .oa-pin-icon {
            background: transparent;
            border: none;
        }

        /* Draft pin (the one being placed): red with a soft pulse so it never
           reads as one of the province-coloured saved pins. */
        .oa-pin-icon.oa-draft svg { filter: drop-shadow(0 4px 6px rgba(185, 28, 28, .35)); }
        .oa-pin-icon.oa-draft::before {
            content: ""; position: absolute; left: 7px; bottom: 0; width: 20px; height: 8px; border-radius: 50%;
            background: rgba(239, 68, 68, .35); animation: oaPulse 1.6s ease-out infinite;
        }
        @keyframes oaPulse { 0% { transform: scale(.6); opacity: .9; } 100% { transform: scale(2); opacity: 0; } }
        @media (prefers-reduced-motion: reduce) { .oa-pin-icon.oa-draft::before { animation: none; } }

        /* Map card header + province legend (same look as the dashboard's pin map) */
        .oa-map-head { display: flex; align-items: center; gap: 12px; margin: 2px 2px 12px; }
        .oa-map-head-ic {
            width: 36px; height: 36px; border-radius: 11px; display: grid; place-items: center;
            background: color-mix(in srgb, #0369a1 12%, #fff); color: #0369a1; font-size: .95rem;
        }
        .oa-map-head-title { font-size: .98rem; font-weight: 800; color: #1e293b; line-height: 1.25; }
        .oa-map-head-sub { font-size: .76rem; color: #94a3b8; font-weight: 600; }
        .oa-legend { display: flex; flex-wrap: wrap; gap: 8px 10px; margin-top: 12px; }
        .oa-legend-item {
            display: inline-flex; align-items: center; gap: 7px; padding: 4px 11px 4px 9px; border-radius: 999px;
            border: 1px solid transparent; font: inherit; font-size: .8rem; font-weight: 700; color: #334155;
            line-height: 1.3; cursor: pointer; transition: box-shadow .15s, transform .15s;
        }
        .oa-legend-item:hover:not(.is-empty) { box-shadow: 0 3px 10px -4px rgba(15, 23, 42, .35); }
        .oa-legend-item:focus-visible { outline: 2px solid #0369a1; outline-offset: 2px; }
        .oa-legend-item .oa-legend-dot { width: 9px; height: 9px; border-radius: 50%; flex: none; }
        .oa-legend-item .oa-legend-n { font-size: .72rem; font-weight: 800; opacity: .75; font-variant-numeric: tabular-nums; }
        .oa-legend-item.is-active { box-shadow: 0 0 0 2px var(--c); background: #fff !important; }
        .oa-legend-item.is-empty { opacity: .45; cursor: default; }
        .oa-filter-reset {
            border: 1px solid #e2e8f0; background: #fff; color: #475569; border-radius: 999px; padding: 5px 12px;
            font: inherit; font-size: .76rem; font-weight: 700; cursor: pointer; white-space: nowrap;
        }
        .oa-filter-reset:hover { background: #f8fafc; color: #1e293b; }
        .oa-filter-reset[hidden] { display: none; }
        .oa-map-popup { min-width: 190px; font-size: .8rem; color: #1e293b; }
        .oa-map-popup-head { display: flex; align-items: center; justify-content: space-between; gap: 8px; }
        .oa-map-popup-prov { font-size: .86rem; font-weight: 800; color: var(--pa, #1e293b); }
        .oa-map-popup-year { flex: none; font-size: .68rem; font-weight: 700; padding: 2px 8px; border-radius: 999px; white-space: nowrap; }
        .oa-map-popup-bc { margin-top: 3px; font-size: .72rem; color: #64748b; }
        .oa-map-popup-ag { margin-top: 5px; font-size: .74rem; color: #3730a3; }
        .oa-map-popup-lb { margin-top: 4px; font-size: .74rem; font-style: italic; color: #64748b; }

        /* --- Saved operating-area pins table ------------------------- */

        .toolbar-head-right {
            display: flex;
            align-items: center;
            gap: 16px;
            flex-wrap: wrap;
        }

        .oa-count-badge {
            display: inline-flex;
            align-items: center;
            font-family: 'IBM Plex Sans Thai', sans-serif;
            font-size: 0.76rem;
            font-weight: 600;
            color: #4338ca;
            background: #eef2ff;
            border: 1px solid #e0e7ff;
            border-radius: 999px;
            padding: 3px 12px;
            white-space: nowrap;
        }

        .oa-filter-inline {
            display: flex;
            align-items: center;
            gap: 8px;
        }

        .oa-filter-inline label {
            display: flex;
            align-items: center;
            gap: 5px;
            font-size: 0.78rem;
            color: #64748b;
            font-weight: 600;
            white-space: nowrap;
            margin: 0;
        }

        /* SuperAdmin: ปุ่ม "ลบข้อมูล" ต่อท้ายตัวกรอง - แคปซูลแดงสูงเท่าช่องตัวกรอง */
        .oa-delete-filtered {
            display: inline-flex;
            align-items: center;
            gap: 6px;
            height: 38px;
            padding: 0 16px;
            border: 1px solid #fca5a5;
            background: #fff;
            color: #dc2626;
            border-radius: 999px;
            font-size: 0.8rem;
            font-weight: 600;
            font-family: inherit;
            cursor: pointer;
            white-space: nowrap;
            transition: background-color .15s, color .15s, border-color .15s;
        }
        .oa-delete-filtered:hover { background: #dc2626; border-color: #dc2626; color: #fff; }
        .oa-delete-filtered:focus-visible { outline: 2px solid #991b1b; outline-offset: 2px; }
        .oa-delete-filtered:disabled { opacity: .6; cursor: wait; }

        .oa-filter-select {
            width: auto;
            min-width: 170px;
        }

        .oa-table-wrap {
            border: 1px solid #eef1f6;
            border-radius: 12px;
            overflow: hidden;
        }

        .oa-pins-table {
            width: 100%;
            min-width: 720px;
            border-collapse: collapse;
            font-family: 'IBM Plex Sans Thai', sans-serif;
        }

        .oa-pins-table thead th {
            text-align: left;
            background: #eef2ff;
            color: #4338ca;
            font-size: 0.74rem;
            font-weight: 600;
            padding: 11px 16px;
            border-bottom: 1px solid #e0e7ff;
            white-space: nowrap;
        }

        .oa-pins-table tbody td {
            padding: 14px 16px;
            border-bottom: 1px solid #eef1f6;
            font-size: 0.84rem;
            color: #334155;
            vertical-align: top;
        }

        .oa-pins-table tbody tr:last-child td {
            border-bottom: none;
        }

        .oa-pins-table tbody tr:hover td {
            background: #f8fafc;
        }

        .oa-location {
            display: flex;
            gap: 10px;
            align-items: flex-start;
        }

        .oa-location-pin {
            flex: none;
            margin-top: 3px;
        }

        .oa-location-path {
            font-family: 'Noto Serif Thai', sans-serif;
            font-weight: 600;
            font-size: 0.88rem;
            color: #1e293b;
            line-height: 1.5;
        }

        .oa-sep {
            color: #cbd5e1;
            margin: 0 5px;
            font-weight: 400;
            font-family: 'IBM Plex Sans Thai', sans-serif;
        }

        .oa-level-chip {
            display: flex;
            align-items: center;
            gap: 5px;
            margin-top: 4px;
            font-size: 0.72rem;
            font-weight: 500;
            color: #64748b;
        }

        .oa-level-chip i {
            font-size: 0.68rem;
            color: #a5b4fc;
            width: 12px;
            text-align: center;
        }

        .oa-coords {
            font-family: ui-monospace, 'SFMono-Regular', Consolas, 'Liberation Mono', monospace;
            font-size: 0.76rem;
            color: #475569;
            white-space: nowrap;
        }

        .oa-coords span {
            display: block;
            font-variant-numeric: tabular-nums;
        }

        .oa-note {
            color: #475569;
            max-width: 220px;
        }

        .oa-meta-name {
            font-weight: 600;
            color: #334155;
        }

        .oa-meta-date {
            font-size: 0.74rem;
            color: #94a3b8;
            margin-top: 2px;
        }

        .oa-col-actions {
            width: 56px;
            text-align: right;
        }

        .oa-col-actions-wide {
            width: auto;
            white-space: nowrap;
        }

        .oa-col-actions-wide .oa-delete-form {
            margin-left: 6px;
            vertical-align: middle;
        }

        /* SuperAdmin: ปุ่ม "ยกเลิกพื้นที่นี้" - แคปซูลขอบแดง พื้นขาว (โทนเดียวกับปุ่มยกเลิกการเลือกพื้นที่ในหน้าเลือกพื้นที่) */
        .btn-oa-release {
            display: inline-flex;
            align-items: center;
            gap: 6px;
            height: 30px;
            padding: 0 12px;
            border: 1px solid #fca5a5;
            background: #fff;
            color: #dc2626;
            border-radius: 999px;
            font-size: 0.78rem;
            font-weight: 600;
            font-family: inherit;
            cursor: pointer;
            vertical-align: middle;
            transition: background-color .15s, color .15s, border-color .15s;
        }

        .btn-oa-release:hover {
            background: #fef2f2;
            border-color: #f87171;
        }

        .btn-oa-release:focus-visible {
            outline: 2px solid #dc2626;
            outline-offset: 2px;
        }

        /* SuperAdmin: ปุ่ม "แก้ไข" - แคปซูลขอบม่วงน้ำเงิน พื้นขาว */
        .btn-oa-edit {
            display: inline-flex;
            align-items: center;
            gap: 6px;
            height: 30px;
            padding: 0 12px;
            margin-right: 6px;
            border: 1px solid #c7d2fe;
            background: #fff;
            color: #4f46e5;
            border-radius: 999px;
            font-size: 0.78rem;
            font-weight: 600;
            font-family: inherit;
            cursor: pointer;
            vertical-align: middle;
            transition: background-color .15s, border-color .15s;
        }
        .btn-oa-edit:hover { background: #eef2ff; border-color: #818cf8; }
        .btn-oa-edit:focus-visible { outline: 2px solid #4f46e5; outline-offset: 2px; }
        .btn-oa-edit:disabled { opacity: .55; cursor: not-allowed; }
        .oa-edit-form { text-align: left; font-size: .9rem; }
        .oa-edit-form .oa-edit-path { color: #64748b; font-size: .82rem; margin-bottom: 10px; }
        .oa-edit-form label { display: block; font-weight: 600; font-size: .8rem; color: #475569; margin: 10px 0 4px; }
        .oa-edit-form input, .oa-edit-form select { width: 100%; height: 38px; padding: 0 10px; border: 1px solid #cbd5e1; border-radius: 8px; font-family: inherit; font-size: .9rem; }
        .oa-edit-form .oa-edit-row { display: grid; grid-template-columns: 1fr 1fr; gap: 10px; }
        .oa-edit-form .oa-edit-hint { color: #94a3b8; font-size: .75rem; margin-top: 10px; }

        .btn-oa-release:disabled {
            opacity: .55;
            cursor: not-allowed;
        }

        .oa-selectors {
            min-width: 150px;
        }

        .oa-sel-chip {
            display: inline-flex;
            align-items: center;
            gap: 5px;
            margin: 0 4px 4px 0;
            padding: 2px 10px;
            border-radius: 999px;
            background: #dcfce7;
            color: #166534;
            font-size: 0.76rem;
            font-weight: 600;
        }

        .oa-sel-chip.oa-sel-reported {
            background: #fef3c7;
            color: #92400e;
        }

        .oa-sel-none {
            color: #94a3b8;
            font-size: 0.78rem;
        }

        .oa-empty-row td {
            padding: 0;
            border-bottom: none;
        }

        .oa-empty-state {
            display: flex;
            flex-direction: column;
            align-items: center;
            gap: 6px;
            text-align: center;
            padding: 48px 20px;
            color: #64748b;
        }

        .oa-empty-state i {
            font-size: 1.6rem;
            color: #c7d2fe;
            margin-bottom: 4px;
        }

        .oa-empty-state p {
            margin: 0;
            font-weight: 600;
            color: #475569;
            font-size: 0.9rem;
        }

        .oa-empty-state span {
            font-size: 0.8rem;
            color: #94a3b8;
        }

        .oa-table-footer {
            display: flex;
            align-items: center;
            justify-content: space-between;
            gap: 12px;
            padding: 16px 4px 2px;
            flex-wrap: wrap;
        }

        .oa-footer-hint {
            font-size: 0.78rem;
            color: #64748b;
            font-family: 'IBM Plex Sans Thai', sans-serif;
        }

        /* Laravel's bundled bootstrap-4 pagination view - just the spacing
           tweaks needed to sit comfortably in this page's own layout; the
           <ul class="pagination">/<li class="page-item"> markup and its
           Bootstrap styling are left as-is, same as admin.kidney-dhb-table's
           own pagination (no per-page override there either). */
        #oa-pagination-links .pagination {
            margin: 0;
        }

        @media (max-width: 640px) {
            .toolbar-head {
                align-items: flex-start;
            }

            .toolbar-head-right {
                width: 100%;
                justify-content: space-between;
            }

            .oa-filter-inline {
                flex: 1 1 auto;
            }

            .oa-filter-select {
                width: 100%;
                min-width: 0;
            }

            .oa-table-footer {
                justify-content: center;
                text-align: center;
            }
        }
    </style>
@endsection

@php
    // Computed here (not inline inside the json() call below) because
    // Blade's json directive splits its argument on every comma, which
    // would mangle a multi-line array literal full of commas.
    //
    // Built from $mapPins (every saved pin), not the paginated/filtered
    // $pins the table below uses - the map is meant to show the whole
    // picture regardless of which page or province the table is
    // currently filtered/paginated to. See operatingAreasIndex()'s own
    // docblock in AdminController.
    $oaSavedPinsForJs = $mapPins->map(function ($pin) {
        return [
            'id' => $pin->id,
            'lat' => (float) $pin->latitude,
            'lng' => (float) $pin->longitude,
            'label' => $pin->label,
            'village' => $pin->village_name_snapshot ?? optional($pin->village)->village_name,
            'tambon' => optional($pin->tambon)->tambon_name,
            'district' => optional($pin->district)->district_name,
            'province' => optional($pin->province)->province_name,
            'province_id' => $pin->province_id,
            'district_id' => $pin->district_id,
            'fiscal_year' => $pin->fiscal_year,
            'display_only' => (bool) ($pin->display_only ?? false),
            'agency_label' => $pin->agency_label ?? null,
        ];
    });
@endphp
@section('extra_js')
    <script src="https://cdnjs.cloudflare.com/ajax/libs/leaflet/1.9.4/leaflet.min.js"></script>
    {{-- District polygons of เขต 10 (WGS84) - tinted per province under the pins.
         The data file registers itself on Highcharts.maps, so give it a bare
         holder when Highcharts itself isn't loaded on this admin page. --}}
    <script>window.Highcharts = window.Highcharts || { maps: {} };</script>
    <script src="{{ asset('vendor/highcharts/th-districts-r10.js') }}"></script>
    <script>
        // Delete button's confirmation popup - wired from each saved pin's
        // own row form via onsubmit="return confirmDeleteOperatingArea(event)"
        // (route admin.operating-areas.destroy). Declared outside the IIFE
        // below so the inline onsubmit handler (global scope) can reach it.
        // Submitting is otherwise irreversible (hard delete, no undo), so
        // this always asks first; returning false stops the native,
        // unconfirmed submit, and form.submit() in the "confirmed" branch
        // re-submits it for real once the person has explicitly agreed.
        // SuperAdmin: ติ๊ก "บันทึกเป็นหมุดแสดงผล" แล้วจึงแสดงช่องชื่อหน่วยงาน (จำเป็นต้องกรอก)
        document.addEventListener('DOMContentLoaded', function () {
            const cb = document.getElementById('oa_display_only');
            const input = document.getElementById('oa_agency_label');
            const btnText = document.getElementById('oa_save_btn_text');
            if (!cb || !input) return;
            function sync(focus) {
                input.hidden = !cb.checked;
                input.disabled = !cb.checked;
                input.required = cb.checked;
                if (btnText) btnText.textContent = cb.checked ? 'บันทึกหมุดแสดงผล' : 'บันทึกหน่วยงานพื้นที่ดำเนินงาน';
                if (focus && cb.checked) input.focus();
            }
            cb.addEventListener('change', function () { sync(true); });
            sync();
        });

        function confirmDeleteOperatingArea(e) {
            e.preventDefault();
            const form = e.currentTarget;

            Swal.fire({
                title: 'ยืนยันการลบพื้นที่ดำเนินงาน?',
                text: 'คุณแน่ใจหรือไม่ว่าต้องการลบพื้นที่ดำเนินงานนี้? ข้อมูลที่ลบแล้วจะไม่สามารถกู้คืนได้',
                icon: 'warning',
                showCancelButton: true,
                confirmButtonColor: '#ef4444',
                cancelButtonColor: '#64748b',
                confirmButtonText: '<i class="fas fa-trash-alt mr-2"></i> ใช่, ยืนยันการลบ',
                cancelButtonText: 'ยกเลิก',
                reverseButtons: true
            }).then((result) => {
                if (result.isConfirmed) {
                    form.submit();
                }
            });
            return false;
        }

        // Province filter + pagination for "พื้นที่ดำเนินงานที่บันทึกแล้ว", loaded
        // via AJAX into #oa-table-container (admin.operating-areas-table) -
        // same convention as admin.kidney-dhb-list's own loadTable()/
        // '.pagination a' delegation, so changing the filter or flipping a
        // page never reloads the whole page (and never re-initializes the
        // Leaflet map below, which only needs to run once).
        $(document).ready(function () {
            const $fy = $('#oa_filter_fiscal_year');
            const $prov = $('#oa_filter_province');
            const $dist = $('#oa_filter_district');
            const $reset = $('#oa_filter_reset');

            function currentFilters() {
                return {
                    filter_fiscal_year: $fy.val() || '',
                    filter_province_id: $prov.val() || '',
                    filter_district_id: $dist.val() || ''
                };
            }

            function loadOaTable(url) {
                if (!url) {
                    url = "{{ route('admin.operating-areas.index') }}";
                }

                $.ajax({
                    url: url,
                    type: 'GET',
                    data: currentFilters(),
                    success: function (data) {
                        $('#oa-table-container').html(data);
                        // Keep the header's count badge (outside this
                        // swapped region) in sync with the just-loaded,
                        // possibly-filtered total.
                        const total = $('#oa-count-carrier').data('total');
                        if (total !== undefined) {
                            $('#oa_count_badge').text(Number(total).toLocaleString('en-US') + ' รายการ');
                        }
                    },
                    error: function () {
                        Swal.fire({
                            icon: 'error',
                            title: 'เกิดข้อผิดพลาด',
                            text: 'ไม่สามารถโหลดข้อมูลได้ กรุณาลองใหม่อีกครั้ง',
                            confirmButtonColor: '#ef4444',
                            confirmButtonText: 'ตกลง'
                        });
                    }
                });
            }

            // One entry point for every filter change: the table (AJAX) and the
            // map above are always driven by the same ปีงบประมาณ + จังหวัด +
            // อำเภอ conditions, combined with AND.
            function applyFilters(fitMap) {
                const f = currentFilters();
                $reset.prop('hidden', !(f.filter_fiscal_year || f.filter_province_id || f.filter_district_id));
                loadOaTable();
                if (window.oaApplyMapFilter) {
                    window.oaApplyMapFilter({ year: f.filter_fiscal_year, province: f.filter_province_id, district: f.filter_district_id }, fitMap !== false);
                }
            }

            // อำเภอ options come from the pins that actually exist in the chosen
            // จังหวัด (no empty choices); the select stays disabled until a
            // จังหวัด is picked.
            function fillDistrictFilter() {
                const provId = $prov.val();
                $dist.empty().append('<option value="">ทั้งหมด</option>');
                if (!provId) {
                    $dist.prop('disabled', true);
                    return;
                }
                const seen = {};
                (window.oaAllPins || []).forEach(function (p) {
                    if (String(p.province_id) === String(provId) && p.district_id && !seen[p.district_id]) {
                        seen[p.district_id] = p.district || ('อำเภอ ' + p.district_id);
                    }
                });
                Object.keys(seen)
                    .sort(function (a, b) { return seen[a].localeCompare(seen[b], 'th'); })
                    .forEach(function (id) { $dist.append($('<option>').val(id).text(seen[id])); });
                $dist.prop('disabled', Object.keys(seen).length === 0);
            }
            window.oaSetProvinceFilter = function (provinceId) {
                $prov.val(provinceId || '');
                fillDistrictFilter();
                applyFilters(true);
            };

            $fy.on('change', function () { applyFilters(true); });
            $prov.on('change', function () { fillDistrictFilter(); applyFilters(true); });
            $dist.on('change', function () { applyFilters(true); });
            $reset.on('click', function () {
                $fy.val(''); $prov.val(''); fillDistrictFilter(); $dist.val('');
                applyFilters(true);
            });

            // SuperAdmin: ลบพื้นที่ทั้งหมดที่ตรงกับตัวกรอง (ปี/จังหวัด/อำเภอ) - ข้ามพื้นที่ที่มีรายงานแล้ว
            // ถามยืนยัน 2 ชั้น: เห็นจำนวนก่อน แล้วพิมพ์คำว่า ลบ
            $('#oa_delete_filtered').on('click', function () {
                const $btn = $(this);
                const token = document.querySelector('meta[name="csrf-token"]').content;
                const url = "{{ route('admin.operating-areas.delete-filtered') }}";
                const parts = [];
                [['oa_filter_fiscal_year', 'ปีงบประมาณ'], ['oa_filter_province', 'จังหวัด'], ['oa_filter_district', 'อำเภอ']].forEach(function (f) {
                    const $el = $('#' + f[0]);
                    if ($el.val()) parts.push(f[1] + ': ' + $.trim($el.find('option:selected').text()));
                });
                const scope = parts.length ? parts.join(' · ') : 'ไม่ได้กรอง (พื้นที่ทั้งหมดในระบบ)';

                $btn.prop('disabled', true);
                $.post(url, Object.assign(currentFilters(), { _token: token, count_only: 1 }), null, 'json')
                    .done(function (res) {
                        $btn.prop('disabled', false);
                        const total = Number(res.count || 0), canDelete = Number(res.deletable || 0), skipped = Number(res.skipped || 0);
                        if (total === 0) {
                            Swal.fire({ icon: 'info', title: 'ไม่มีข้อมูลที่จะลบ', text: 'ไม่พบพื้นที่ที่ตรงกับตัวกรองปัจจุบัน', confirmButtonColor: '#64748b', confirmButtonText: 'ตกลง' });
                            return;
                        }
                        if (canDelete === 0) {
                            Swal.fire({ icon: 'info', title: 'ลบไม่ได้', text: 'พื้นที่ที่ตรงกับตัวกรอง ' + total.toLocaleString() + ' รายการ มีรายงานแล้วทั้งหมด', confirmButtonColor: '#64748b', confirmButtonText: 'ตกลง' });
                            return;
                        }
                        Swal.fire({
                            title: 'ลบพื้นที่ที่กรองได้?',
                            icon: 'warning',
                            html: '<div style="text-align:left;font-size:.92rem;line-height:1.6;">'
                                + '<div style="background:#fef2f2;border:1px solid #fecaca;border-radius:10px;padding:12px 14px;margin-bottom:10px;">'
                                + '<div style="color:#991b1b;font-weight:700;">จะลบ <span style="font-size:1.35rem;">' + canDelete.toLocaleString() + '</span> พื้นที่</div>'
                                + (skipped > 0 ? '<div style="color:#92400e;margin-top:2px;">ข้าม ' + skipped.toLocaleString() + ' พื้นที่ที่มีรายงานแล้ว (ไม่ลบ)</div>' : '')
                                + '<div class="swal-scope" style="color:#7f1d1d;margin-top:4px;"></div></div>'
                                + '<div style="color:#475569;">การเลือกพื้นที่ของหน่วยงานจะถูกปลดด้วย <b style="color:#b91c1c;">กู้คืนไม่ได้</b></div>'
                                + '<div style="margin-top:10px;color:#475569;">พิมพ์คำว่า <b>ลบ</b> เพื่อยืนยัน</div></div>',
                            input: 'text',
                            inputAttributes: { autocomplete: 'off', 'aria-label': 'พิมพ์คำว่า ลบ เพื่อยืนยัน' },
                            inputValidator: function (v) { return $.trim(v) === 'ลบ' ? undefined : 'พิมพ์คำว่า ลบ ให้ตรงเพื่อยืนยัน'; },
                            showCancelButton: true,
                            confirmButtonColor: '#dc2626',
                            cancelButtonColor: '#64748b',
                            confirmButtonText: '<i class="fas fa-trash-can mr-2"></i> ลบพื้นที่',
                            cancelButtonText: 'ยกเลิก',
                            reverseButtons: true,
                            focusCancel: true,
                            didOpen: function (el) { const b = el.querySelector('.swal-scope'); if (b) b.textContent = 'ตัวกรอง: ' + scope; },
                            showLoaderOnConfirm: true,
                            allowOutsideClick: function () { return !Swal.isLoading(); },
                            preConfirm: function () {
                                return $.post(url, Object.assign(currentFilters(), { _token: token, confirm: 1 }), null, 'json')
                                    .then(function (r) { return r; }, function (xhr) {
                                        Swal.showValidationMessage((xhr.responseJSON && xhr.responseJSON.message) || 'ลบไม่สำเร็จ กรุณาลองใหม่อีกครั้ง');
                                        return false;
                                    });
                            }
                        }).then(function (result) {
                            if (!result.isConfirmed || !result.value) return;
                            Swal.fire({ icon: 'success', title: 'ลบแล้ว', text: result.value.message, confirmButtonColor: '#4f46e5', confirmButtonText: 'ตกลง' })
                                .then(function () { window.location.reload(); }); // โหลดหน้าใหม่ให้หมุดบนแผนที่ตรงกับตาราง
                        });
                    })
                    .fail(function (xhr) {
                        $btn.prop('disabled', false);
                        Swal.fire({ icon: 'error', title: 'เกิดข้อผิดพลาด', text: (xhr.responseJSON && xhr.responseJSON.message) || 'ไม่สามารถตรวจสอบจำนวนข้อมูลได้ กรุณาลองใหม่อีกครั้ง', confirmButtonColor: '#ef4444', confirmButtonText: 'ตกลง' });
                    });
            });

            // SuperAdmin: "แก้ไข" - ฟอร์มแก้ปีงบประมาณ / ชื่อพื้นที่ / พิกัด (/ ชื่อหน่วยงานของหมุดแสดงผล) แล้วบันทึกด้วย AJAX
            const oaFiscalYears = @json($fiscalYears);
            $(document).on('click', '#oa-table-container .btn-oa-edit', function () {
                const $btn = $(this);
                const d = $btn.data();
                const isDisplay = String(d.display) === '1';
                const years = Array.from(new Set([].concat(Array.from(oaFiscalYears || []), [d.fiscalYear]).filter(Boolean).map(String))).sort().reverse();

                Swal.fire({
                    title: 'แก้ไขพื้นที่ดำเนินงาน',
                    html: '<div class="oa-edit-form">'
                        + '<div class="oa-edit-path"></div>'
                        + '<label for="oa_ed_fy">ปีงบประมาณ</label><select id="oa_ed_fy"></select>'
                        + '<label for="oa_ed_label">ชื่อพื้นที่/หมายเหตุ</label><input id="oa_ed_label" type="text" maxlength="255">'
                        + '<div class="oa-edit-row"><div><label for="oa_ed_lat">ละติจูด</label><input id="oa_ed_lat" type="number" step="any"></div>'
                        + '<div><label for="oa_ed_lng">ลองจิจูด</label><input id="oa_ed_lng" type="number" step="any"></div></div>'
                        + (isDisplay ? '<label for="oa_ed_agency">หน่วยงานที่ดำเนินงาน (หมุดแสดงผล)</label><input id="oa_ed_agency" type="text" maxlength="255">' : '')
                        + '<div class="oa-edit-hint">ที่ตั้ง (จังหวัด/อำเภอ/ตำบล/หมู่บ้าน) แก้ไม่ได้ เพราะมีรายงานและการเลือกพื้นที่อ้างอิงอยู่ — หากผิดที่ให้ลบแล้วปักใหม่</div>'
                        + '</div>',
                    showCancelButton: true,
                    confirmButtonColor: '#4f46e5',
                    cancelButtonColor: '#64748b',
                    confirmButtonText: '<i class="fas fa-floppy-disk mr-2"></i> บันทึก',
                    cancelButtonText: 'ยกเลิก',
                    reverseButtons: true,
                    didOpen: function (el) {
                        el.querySelector('.oa-edit-path').textContent = d.path || '';
                        const fy = el.querySelector('#oa_ed_fy');
                        years.forEach(function (y) {
                            const o = document.createElement('option');
                            o.value = y; o.textContent = y;
                            if (String(y) === String(d.fiscalYear)) o.selected = true;
                            fy.appendChild(o);
                        });
                        el.querySelector('#oa_ed_label').value = d.label || '';
                        el.querySelector('#oa_ed_lat').value = d.lat;
                        el.querySelector('#oa_ed_lng').value = d.lng;
                        const ag = el.querySelector('#oa_ed_agency');
                        if (ag) ag.value = d.agency || '';
                    },
                    preConfirm: function () {
                        const v = id => (document.getElementById(id) || {}).value;
                        const lat = parseFloat(v('oa_ed_lat')), lng = parseFloat(v('oa_ed_lng'));
                        if (isNaN(lat) || lat < -90 || lat > 90 || isNaN(lng) || lng < -180 || lng > 180) {
                            Swal.showValidationMessage('กรุณาระบุพิกัดให้ถูกต้อง');
                            return false;
                        }
                        if (isDisplay && !String(v('oa_ed_agency') || '').trim()) {
                            Swal.showValidationMessage('กรุณาระบุชื่อหน่วยงานที่ดำเนินงาน');
                            return false;
                        }
                        return $.ajax({
                            url: d.url,
                            type: 'POST',
                            dataType: 'json',
                            data: {
                                _token: document.querySelector('meta[name="csrf-token"]').content,
                                _method: 'PUT',
                                fiscal_year: v('oa_ed_fy'),
                                label: v('oa_ed_label'),
                                latitude: lat,
                                longitude: lng,
                                agency_label: v('oa_ed_agency') || ''
                            }
                        }).then(function (res) { return res; }, function (xhr) {
                            let msg = (xhr.responseJSON && xhr.responseJSON.message) || 'ไม่สามารถบันทึกได้ กรุณาลองใหม่อีกครั้ง';
                            if (xhr.responseJSON && xhr.responseJSON.errors) {
                                msg = Object.values(xhr.responseJSON.errors)[0][0] || msg;
                            }
                            Swal.showValidationMessage(msg);
                            return false;
                        });
                    }
                }).then(function (result) {
                    if (!result.isConfirmed || !result.value) return;
                    Swal.fire({ icon: 'success', title: 'สำเร็จ', text: result.value.message, confirmButtonColor: '#4f46e5', confirmButtonText: 'ตกลง', timer: 1800 });
                    // โหลดตารางหน้าเดิมใหม่ และโหลดหน้าใหม่เพื่อให้หมุดบนแผนที่ตรงกัน
                    setTimeout(function () { window.location.reload(); }, 900);
                });
            });

            // SuperAdmin: "ยกเลิกพื้นที่นี้" - ถามยืนยัน แล้วปลดการเลือกของหน่วยงาน (คืนพื้นที่ให้ว่าง)
            // ด้วย AJAX แล้วโหลดตารางหน้าเดิมใหม่ (ปุ่มถูกสร้างใหม่ทุกครั้งที่สลับตาราง จึงใช้ delegation)
            $(document).on('click', '#oa-table-container .btn-oa-release', function () {
                const $btn = $(this);
                const name = $btn.data('name') || 'พื้นที่นี้';
                const agencies = $btn.data('agencies') || '';

                Swal.fire({
                    title: 'ยกเลิกพื้นที่นี้?',
                    html: 'ปลดการเลือกพื้นที่ <b></b> ของ <b class="oa-rel-agency"></b> และคืนพื้นที่ให้ว่าง'
                        + '<br><small style="color:#64748b">หมุดยังคงอยู่ และหน่วยงานอื่นเลือกพื้นที่นี้ได้อีก (ไม่มีการลบรายงาน)</small>',
                    icon: 'warning',
                    showCancelButton: true,
                    confirmButtonColor: '#ef4444',
                    cancelButtonColor: '#64748b',
                    confirmButtonText: '<i class="fas fa-circle-minus mr-2"></i> ใช่, ยกเลิกพื้นที่นี้',
                    cancelButtonText: 'ไม่ยกเลิก',
                    reverseButtons: true,
                    didOpen: function (el) {
                        // ใส่ชื่อด้วย textContent กันอักขระพิเศษใน HTML
                        const bs = el.querySelectorAll('b');
                        if (bs[0]) bs[0].textContent = name;
                        if (bs[1]) bs[1].textContent = agencies;
                    }
                }).then(function (result) {
                    if (!result.isConfirmed) return;

                    $btn.prop('disabled', true);
                    $.ajax({
                        url: $btn.data('url'),
                        type: 'POST',
                        dataType: 'json',
                        data: { _token: document.querySelector('meta[name="csrf-token"]').content },
                        success: function (res) {
                            Swal.fire({ icon: 'success', title: 'สำเร็จ', text: res.message, confirmButtonColor: '#4f46e5', confirmButtonText: 'ตกลง', timer: 2500 });
                            const page = $('#oa-table-container .pagination .page-item.active .page-link').first().text().trim();
                            loadOaTable(page && /^\d+$/.test(page) ? "{{ route('admin.operating-areas.index') }}?page=" + page : null);
                        },
                        error: function (xhr) {
                            $btn.prop('disabled', false);
                            const msg = (xhr.responseJSON && xhr.responseJSON.message) || 'ไม่สามารถยกเลิกพื้นที่นี้ได้ กรุณาลองใหม่อีกครั้ง';
                            Swal.fire({ icon: 'error', title: 'ยกเลิกไม่สำเร็จ', text: msg, confirmButtonColor: '#ef4444', confirmButtonText: 'ตกลง' });
                        }
                    });
                });
            });

            // Delegated (not bound once on load) because the pagination
            // links are replaced wholesale every time #oa-table-container
            // is swapped in above.
            $(document).on('click', '#oa-table-container .pagination a', function (e) {
                e.preventDefault();
                loadOaTable($(this).attr('href'));
            });
        });

        (function () {
            const csrfToken = document.querySelector('meta[name="csrf-token"]').content;

            // Center roughly on เขตสุขภาพที่ 10 (ศรีสะเกษ/อุบลราชธานี/ยโสธร/อำนาจเจริญ/มุกดาหาร).
            // Wheel + button + double-click + pinch zoom, in half-steps for a smoother feel.
            const map = L.map('oa_map', { scrollWheelZoom: true, zoomSnap: 0.5, zoomDelta: 0.5, minZoom: 6, maxZoom: 18 })
                .setView([15.9, 104.5], 7);
            L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {
                maxZoom: 18,
                attribution: '&copy; OpenStreetMap contributors'
            }).addTo(map);

            // Same province palette used on the dashboard + kidney report maps.
            const provinceColors = {
                'อุบลราชธานี': { fill: '#2563eb', stroke: '#1e40af' },
                'ศรีสะเกษ': { fill: '#d97706', stroke: '#92400e' },
                'ยโสธร': { fill: '#7c3aed', stroke: '#5b21b6' },
                'อำนาจเจริญ': { fill: '#16a34a', stroke: '#15803d' },
                'มุกดาหาร': { fill: '#db2777', stroke: '#9d174d' },
            };
            const defaultColor = { fill: '#6b7280', stroke: '#374151' };
            const colorOf = name => provinceColors[name] || defaultColor;
            const esc = v => String(v == null ? '' : v).replace(/[&<>"']/g, c => ({ '&': '&amp;', '<': '&lt;', '>': '&gt;', '"': '&quot;', "'": '&#39;' }[c]));
            const rgba = (hex, a) => {
                const n = parseInt(hex.slice(1), 16);
                return 'rgba(' + (n >> 16) + ',' + ((n >> 8) & 255) + ',' + (n & 255) + ',' + a + ')';
            };

            // Province areas: district polygons tinted per province with a thin
            // border, non-interactive so clicks still place the draft pin.
            const districtGeo = window.Highcharts && Highcharts.maps && Highcharts.maps['countries/th/th-districts-r10'];
            if (districtGeo) {
                L.geoJSON(districtGeo, {
                    interactive: false,
                    style: f => {
                        const c = colorOf(f.properties.province_name);
                        return { color: c.fill, weight: 1, opacity: .55, fillColor: c.fill, fillOpacity: .12, lineJoin: 'round' };
                    }
                }).addTo(map);
            }

            // Classic map-pin icon, drawn as inline SVG (no external image
            // file) so there's no Leaflet marker-icon CDN path to break.
            // Saved pins take their province's colour; the draft pin (the one
            // being placed/edited) is red and pulses.
            function pinIcon(fill, stroke, extraClass) {
                const svg = '<svg width="30" height="40" viewBox="0 0 34 46" xmlns="http://www.w3.org/2000/svg">' +
                    '<ellipse cx="17" cy="41" rx="8" ry="2.6" fill="#0f172a" opacity="0.25"/>' +
                    '<path d="M17 2C9.8 2 4 7.8 4 15c0 10.2 13 27 13 27s13-16.8 13-27C30 7.8 24.2 2 17 2z" ' +
                    'fill="' + fill + '" stroke="' + stroke + '" stroke-width="1.5" stroke-linejoin="round"/>' +
                    '<circle cx="17" cy="15" r="5.5" fill="#fff"/>' +
                    '</svg>';
                return L.divIcon({
                    html: svg,
                    className: 'oa-pin-icon' + (extraClass ? ' ' + extraClass : ''),
                    iconSize: [30, 40],
                    iconAnchor: [15, 38],
                    popupAnchor: [0, -34]
                });
            }

            const iconCache = {};
            function provinceIcon(name) {
                const k = name || '_';
                if (!iconCache[k]) {
                    const c = colorOf(name);
                    iconCache[k] = pinIcon(c.fill, c.stroke);
                }
                return iconCache[k];
            }
            const draftPinIcon = pinIcon('#ef4444', '#b91c1c', 'oa-draft');

            // Every saved pin (the table is paginated, the map never is) - the
            // filters below decide which of them are drawn.
            const allPins = @json($oaSavedPinsForJs);
            window.oaAllPins = allPins;
            const savedLayer = L.layerGroup().addTo(map);
            const mapFilter = { year: '', province: '', district: '' };

            function passes(p, ignoreLocation) {
                if (mapFilter.year && String(p.fiscal_year) !== String(mapFilter.year)) return false;
                if (!ignoreLocation) {
                    if (mapFilter.province && String(p.province_id) !== String(mapFilter.province)) return false;
                    if (mapFilter.district && String(p.district_id) !== String(mapFilter.district)) return false;
                }
                return true;
            }

            function popupHtml(p) {
                const c = colorOf(p.province);
                const crumb = [p.district, p.tambon, p.village].filter(Boolean).join(' › ');
                return '<div class="oa-map-popup" style="--pa:' + c.fill + ';">' +
                    '<div class="oa-map-popup-head"><span class="oa-map-popup-prov">' + esc(p.province || 'พื้นที่ดำเนินงาน') + '</span>' +
                    (p.fiscal_year ? '<span class="oa-map-popup-year" style="background:' + rgba(c.fill, .14) + ';color:' + c.stroke + ';">ปีงบ ' + esc(p.fiscal_year) + '</span>' : '') + '</div>' +
                    (crumb ? '<div class="oa-map-popup-bc">' + esc(crumb) + '</div>' : '') +
                    (p.label ? '<div class="oa-map-popup-lb">' + esc(p.label) + '</div>' : '') +
                    (p.display_only ? '<div class="oa-map-popup-ag"><b>ดำเนินงาน พชอ.ไต</b> โดย ' + esc(p.agency_label || '-') + '</div>' : '') +
                    '</div>';
            }

            // Province legend: one pill per province with its pin count (under the
            // current ปีงบประมาณ). Click a pill to filter table + map by it; click
            // the active one again to clear.
            function renderLegend() {
                const el = document.getElementById('oa_map_legend');
                if (!el) return;
                const counts = {};
                allPins.forEach(p => { if (passes(p, true)) counts[p.province] = (counts[p.province] || 0) + 1; });
                const names = Object.keys(provinceColors).concat(Object.keys(counts).filter(n => n && !provinceColors[n]));
                const idByName = {};
                document.querySelectorAll('#oa_filter_province option').forEach(o => { if (o.value) idByName[o.textContent.trim()] = o.value; });
                el.innerHTML = names.map(n => {
                    const c = colorOf(n), cnt = counts[n] || 0, id = idByName[n] || '';
                    const active = id && String(mapFilter.province) === String(id);
                    return '<button type="button" class="oa-legend-item' + (cnt ? '' : ' is-empty') + (active ? ' is-active' : '') + '"' +
                        ' data-province-id="' + esc(id) + '" aria-pressed="' + (active ? 'true' : 'false') + '"' + (cnt ? '' : ' aria-disabled="true"') +
                        ' style="--c:' + c.fill + ';border-color:' + rgba(c.stroke, .35) + ';background:' + rgba(c.fill, .1) + ';">' +
                        '<span class="oa-legend-dot" style="background:' + c.fill + '"></span>' + esc(n) +
                        ' <span class="oa-legend-n">' + cnt + '</span></button>';
                }).join('');
            }

            function renderPins(fit) {
                savedLayer.clearLayers();
                const shown = allPins.filter(p => passes(p, false));
                const pts = [];
                shown.forEach(function (p) {
                    if (isNaN(p.lat) || isNaN(p.lng)) return;
                    L.marker([p.lat, p.lng], { icon: provinceIcon(p.province) })
                        .addTo(savedLayer)
                        .bindPopup(popupHtml(p));
                    pts.push([p.lat, p.lng]);
                });
                const summary = document.getElementById('oa_map_summary');
                if (summary) summary.textContent = 'แสดง ' + pts.length.toLocaleString('en-US') + ' จาก ' + allPins.length.toLocaleString('en-US') + ' หมุด';
                renderLegend();
                if (fit) {
                    if (pts.length) map.fitBounds(pts, { padding: [30, 30], maxZoom: mapFilter.district ? 12 : 10 });
                    else map.setView([15.2, 104.5], 7);
                }
            }

            // Called by the table filters (see $(document).ready above).
            window.oaApplyMapFilter = function (f, fit) {
                mapFilter.year = f.year || '';
                mapFilter.province = f.province || '';
                mapFilter.district = f.district || '';
                renderPins(fit);
            };

            document.getElementById('oa_map_legend').addEventListener('click', function (e) {
                const btn = e.target.closest('.oa-legend-item');
                if (!btn || btn.classList.contains('is-empty') || !btn.dataset.provinceId) return;
                const same = String(mapFilter.province) === btn.dataset.provinceId;
                if (window.oaSetProvinceFilter) window.oaSetProvinceFilter(same ? '' : btn.dataset.provinceId);
            });

            renderPins(true);

            // The pin currently being placed/edited before saving. Uses a
            // real L.marker (not circleMarker) specifically so Leaflet's own
            // native dragging can be used instead of hand-rolled mouse events.
            let draftMarker = null;

            function setDraftMarker(lat, lng) {
                if (draftMarker) {
                    draftMarker.setLatLng([lat, lng]);
                } else {
                    draftMarker = L.marker([lat, lng], {
                        icon: draftPinIcon,
                        draggable: true
                    }).addTo(map);

                    draftMarker.on('dragend', function () {
                        const pos = draftMarker.getLatLng();
                        document.getElementById('oa_input_lat').value = pos.lat.toFixed(6);
                        document.getElementById('oa_input_lng').value = pos.lng.toFixed(6);
                        updateSaveButtonState();
                    });
                }
                document.getElementById('oa_input_lat').value = lat.toFixed(6);
                document.getElementById('oa_input_lng').value = lng.toFixed(6);
                updateSaveButtonState();
            }

            map.on('click', function (e) {
                setDraftMarker(e.latlng.lat, e.latlng.lng);
            });

            // tambon_id/village_id are optional - an operating area may only
            // be pinned down to อำเภอ level - so only province/district plus
            // a placed pin are required to enable saving.
            const $in = id => document.getElementById(id);
            const drafts = [];
            const draftLayer = L.layerGroup().addTo(map);
            const formComplete = () => !!($in('oa_input_fiscal_year').value && $in('oa_input_province').value &&
                $in('oa_input_district').value && $in('oa_input_lat').value && $in('oa_input_lng').value);
            const areaKey = d => [d.fiscal_year, d.province_id, d.district_id, d.tambon_id || '', d.village_id || '', d.label || '',
                d.display_only ? '1' : '0', d.display_only ? (d.agency_label || '') : ''].join('|');
            const formInDrafts = () => formComplete() && drafts.some(d => areaKey(d) === areaKey(currentAreaItem()));
            // ช่องในฟอร์มนับเป็น 1 พื้นที่ก็ต่อเมื่อครบและยังไม่อยู่ในรายการที่เพิ่มไว้ (ไม่นับซ้ำ)
            const formCounts = () => formComplete() && !formInDrafts();

            function updateSaveButtonState() {
                const total = drafts.length + (formCounts() ? 1 : 0);
                $in('oa_add_btn').disabled = !formComplete() || formInDrafts();
                $in('oa_save_btn').disabled = total === 0;
                $in('oa_save_btn_text').textContent = total > 1 ? 'บันทึกทั้งหมด (' + total + ' พื้นที่)' : 'บันทึกหน่วยงานพื้นที่ดำเนินงาน';
            }

            const optText = s => (s.value && s.options[s.selectedIndex]) ? s.options[s.selectedIndex].textContent.trim() : '';
            function currentAreaItem() {
                const v = optText($in('oa_village')), t = optText($in('oa_tambon')), d = optText($in('oa_district')), p = optText($in('oa_province'));
                const crumbs = [v, t && ('ตำบล' + t), d && ('อำเภอ' + d)].filter(Boolean);
                const label = ($in('oa_label').value || '').trim();
                const dispEl = $in('oa_display_only');
                const display = !!(dispEl && dispEl.checked);
                const agency = display ? (($in('oa_agency_label').value || '').trim()) : '';
                return {
                    fiscal_year: $in('oa_input_fiscal_year').value,
                    province_id: $in('oa_input_province').value, district_id: $in('oa_input_district').value,
                    tambon_id: $in('oa_input_tambon').value, village_id: $in('oa_input_village').value,
                    village_name_snapshot: $in('oa_input_village_name').value,
                    latitude: $in('oa_input_lat').value, longitude: $in('oa_input_lng').value,
                    label: label, display_only: display, agency_label: agency,
                    _title: crumbs[0] || p,
                    _sub: crumbs.slice(1).join(' ') + (label ? (crumbs.length > 1 ? ' · ' : '') + 'ประเด็น: ' + label : '') +
                        (display ? ((crumbs.length > 1 || label) ? ' · ' : '') + 'หมุดแสดงผล: ' + (agency || '-') : '')
                };
            }

            // หมุดของแต่ละพื้นที่ในรายการ (เลือกกี่พื้นที่ ปักกี่หมุด) - หมายเลขตรงกับลำดับในรายการ
            function numIcon(n) {
                return L.divIcon({
                    html: '<svg width="30" height="40" viewBox="0 0 34 46" xmlns="http://www.w3.org/2000/svg">' +
                        '<ellipse cx="17" cy="41" rx="8" ry="2.6" fill="#0f172a" opacity="0.25"/>' +
                        '<path d="M17 2C9.8 2 4 7.8 4 15c0 10.2 13 27 13 27s13-16.8 13-27C30 7.8 24.2 2 17 2z" fill="#4f46e5" stroke="#3730a3" stroke-width="1.5" stroke-linejoin="round"/>' +
                        '<circle cx="17" cy="15" r="8" fill="#fff"/><text x="17" y="19.5" text-anchor="middle" font-size="12" font-weight="700" fill="#3730a3" font-family="sans-serif">' + n + '</text></svg>',
                    className: 'oa-pin-icon', iconSize: [30, 40], iconAnchor: [15, 38], popupAnchor: [0, -34]
                });
            }
            function renderDraftMarkers() {
                draftLayer.clearLayers();
                drafts.forEach(function (d, i) {
                    const la = parseFloat(d.latitude), lo = parseFloat(d.longitude);
                    if (isNaN(la) || isNaN(lo)) return;
                    L.marker([la, lo], { icon: numIcon(i + 1), keyboard: false }).addTo(draftLayer)
                        .bindTooltip((i + 1) + '. ' + d._title, { direction: 'top', offset: [0, -34] });
                });
            }
            function fitDraftMarkers() {
                const pts = drafts.map(d => [parseFloat(d.latitude), parseFloat(d.longitude)]).filter(c => !isNaN(c[0]) && !isNaN(c[1]));
                if (pts.length > 1) map.fitBounds(pts, { padding: [50, 50], maxZoom: 11 });
                else if (pts.length === 1) map.flyTo(pts[0], Math.max(map.getZoom(), 10));
            }
            function renderDrafts() {
                renderDraftMarkers();
                $in('oa_draft_box').style.display = drafts.length ? '' : 'none';
                $in('oa_draft_count').textContent = drafts.length + ' พื้นที่';
                const list = $in('oa_draft_list');
                list.innerHTML = '';
                drafts.forEach(function (d, i) {
                    const li = document.createElement('li');
                    li.className = 'oa-draft-item';
                    li.innerHTML = '<span class="oa-draft-no">' + (i + 1) + '</span><span class="oa-draft-text">' + esc(d._title) +
                        (d._sub ? '<small>' + esc(d._sub) + '</small>' : '') + '</span>' +
                        '<button type="button" class="oa-draft-del" title="เอาพื้นที่นี้ออกจากรายการ" data-i="' + i + '"><i class="fas fa-xmark"></i></button>';
                    list.appendChild(li);
                });
                updateSaveButtonState();
            }
            $in('oa_draft_list').addEventListener('click', function (e) {
                const b = e.target.closest('.oa-draft-del');
                if (!b) return;
                drafts.splice(parseInt(b.dataset.i, 10), 1);
                renderDrafts();
                fitDraftMarkers();
            });
            function removeDraftMarker() {
                if (draftMarker) { map.removeLayer(draftMarker); draftMarker = null; }
                $in('oa_input_lat').value = ''; $in('oa_input_lng').value = '';
            }
            $in('oa_add_btn').addEventListener('click', function () {
                if (!formComplete()) return;
                const item = currentAreaItem();
                if (item.display_only && !item.agency_label) {
                    Swal.fire({ icon: 'warning', text: 'กรุณาระบุชื่อหน่วยงานที่ดำเนินงาน (หมุดแสดงผล)', confirmButtonText: 'ตกลง' });
                    return;
                }
                if (drafts.some(d => areaKey(d) === areaKey(item))) {
                    Swal.fire({ icon: 'info', text: 'พื้นที่นี้อยู่ในรายการแล้ว', confirmButtonText: 'ตกลง' });
                    return;
                }
                drafts.push(item);
                // เตรียมกรอกพื้นที่ถัดไป: คงจังหวัด/อำเภอไว้ ล้างตำบล ชุมชน หมายเหตุ และหมุดที่กำลังวาง
                $in('oa_input_tambon').value = ''; $in('oa_input_village').value = ''; $in('oa_input_village_name').value = '';
                $in('oa_tambon').value = '';
                resetSelect($in('oa_village'), '-- เลือกชุมชน/หมู่บ้าน --');
                if ($in('oa_tambon').options.length > 1) $in('oa_tambon').disabled = false;
                $in('oa_label').value = '';
                removeDraftMarker();
                renderDrafts();
                fitDraftMarkers();
            });
            $in('oa_form').addEventListener('submit', function () {
                // ไม่มีรายการที่เพิ่มไว้ = บันทึกพื้นที่เดียวจากช่องในฟอร์มเหมือนเดิม
                if (!drafts.length) { $in('oa_areas_json').value = ''; return; }
                const all = drafts.slice();
                if (formCounts()) all.push(currentAreaItem());
                $in('oa_areas_json').value = JSON.stringify(all.map(function (d) {
                    return { fiscal_year: d.fiscal_year, province_id: d.province_id, district_id: d.district_id,
                        tambon_id: d.tambon_id || null, village_id: d.village_id || null,
                        village_name_snapshot: d.village_name_snapshot || null, latitude: d.latitude, longitude: d.longitude,
                        label: d.label || null, display_only: d.display_only ? 1 : 0, agency_label: d.agency_label || null };
                }));
            });
            // ติ๊ก/พิมพ์หมุดแสดงผลหรือหมายเหตุ ก็ต้องคำนวณปุ่มใหม่ (รายการซ้ำหรือไม่)
            ['oa_label', 'oa_agency_label', 'oa_display_only'].forEach(function (id) {
                const el = $in(id); if (el) { el.addEventListener('input', updateSaveButtonState); el.addEventListener('change', updateSaveButtonState); }
            });

            function resetSelect(sel, placeholder) {
                sel.innerHTML = '<option value="">' + placeholder + '</option>';
                sel.disabled = true;
            }

            // If the selected <option> carries a data-lat/data-lng (every
            // level - จังหวัด/อำเภอ/ตำบล/ชุมชน - embeds its own rough center
            // point), fly the map there and move the draft pin along with it.
            function flyToOptionCoords(option, zoom) {
                if (!option || !option.dataset.lat || !option.dataset.lng) {
                    return false;
                }
                const lat = parseFloat(option.dataset.lat);
                const lng = parseFloat(option.dataset.lng);
                if (isNaN(lat) || isNaN(lng)) {
                    return false;
                }
                map.flyTo([lat, lng], zoom);
                setDraftMarker(lat, lng);
                return true;
            }

            const fiscalYearSelect = document.getElementById('oa_fiscal_year');
            const provinceSelect = document.getElementById('oa_province');
            const districtSelect = document.getElementById('oa_district');
            const tambonSelect = document.getElementById('oa_tambon');
            const villageSelect = document.getElementById('oa_village');

            // "2569" is pre-selected on the <select> itself (the "selected"
            // HTML attribute), but the hidden #oa_input_fiscal_year that
            // actually gets submitted starts empty - sync it once on load so
            // the save button isn't stuck disabled until the admin re-touches
            // the dropdown.
            document.getElementById('oa_input_fiscal_year').value = fiscalYearSelect.value;
            updateSaveButtonState();

            fiscalYearSelect.addEventListener('change', function () {
                document.getElementById('oa_input_fiscal_year').value = this.value;
                updateSaveButtonState();
            });

            provinceSelect.addEventListener('change', function () {
                document.getElementById('oa_input_province').value = this.value;
                document.getElementById('oa_input_district').value = '';
                document.getElementById('oa_input_tambon').value = '';
                document.getElementById('oa_input_village').value = '';
                document.getElementById('oa_input_village_name').value = '';
                resetSelect(districtSelect, '-- เลือกอำเภอ --');
                resetSelect(tambonSelect, '-- เลือกตำบล --');
                resetSelect(villageSelect, '-- เลือกชุมชน/หมู่บ้าน --');

                if (!this.value) {
                    updateSaveButtonState();
                    return;
                }

                flyToOptionCoords(this.options[this.selectedIndex], 8);
                updateSaveButtonState();

                fetch(`${APP_BASE}/admin/operating-areas/get-districts/${this.value}`)
                    .then(r => r.json())
                    .then(districts => {
                        districtSelect.innerHTML = '<option value="">-- เลือกอำเภอ --</option>';
                        districts.forEach(d => {
                            const opt = document.createElement('option');
                            opt.value = d.district_id;
                            opt.textContent = d.district_name;
                            opt.dataset.lat = d.lat ?? '';
                            opt.dataset.lng = d.lng ?? '';
                            districtSelect.appendChild(opt);
                        });
                        districtSelect.disabled = false;
                    });
            });

            districtSelect.addEventListener('change', function () {
                document.getElementById('oa_input_district').value = this.value;
                document.getElementById('oa_input_tambon').value = '';
                document.getElementById('oa_input_village').value = '';
                document.getElementById('oa_input_village_name').value = '';
                resetSelect(tambonSelect, '-- เลือกตำบล --');
                resetSelect(villageSelect, '-- เลือกชุมชน/หมู่บ้าน --');

                if (!this.value) {
                    updateSaveButtonState();
                    return;
                }

                flyToOptionCoords(this.options[this.selectedIndex], 10);
                updateSaveButtonState();

                const provinceId = provinceSelect.value;
                fetch(`${APP_BASE}/admin/operating-areas/get-tambons/${provinceId}/${this.value}`)
                    .then(r => r.json())
                    .then(tambons => {
                        tambonSelect.innerHTML = '<option value="">-- เลือกตำบล --</option>';
                        tambons.forEach(t => {
                            const opt = document.createElement('option');
                            opt.value = t.tambon_id;
                            opt.textContent = t.tambon_name;
                            opt.dataset.lat = t.lat ?? '';
                            opt.dataset.lng = t.lng ?? '';
                            tambonSelect.appendChild(opt);
                        });
                        tambonSelect.disabled = false;
                    });
            });

            tambonSelect.addEventListener('change', function () {
                document.getElementById('oa_input_tambon').value = this.value;
                document.getElementById('oa_input_village').value = '';
                document.getElementById('oa_input_village_name').value = '';
                resetSelect(villageSelect, '-- เลือกชุมชน/หมู่บ้าน --');

                if (!this.value) {
                    updateSaveButtonState();
                    return;
                }

                flyToOptionCoords(this.options[this.selectedIndex], 12);
                updateSaveButtonState();

                const provinceId = provinceSelect.value;
                const districtId = districtSelect.value;
                fetch(`${APP_BASE}/admin/operating-areas/get-villages/${provinceId}/${districtId}/${this.value}`)
                    .then(r => r.json())
                    .then(villages => {
                        villageSelect.innerHTML = '<option value="">-- เลือกชุมชน/หมู่บ้าน --</option>';
                        villages.forEach(v => {
                            const opt = document.createElement('option');
                            opt.value = v.village_id;
                            opt.textContent = v.village_name;
                            opt.dataset.lat = v.latitude ?? '';
                            opt.dataset.lng = v.longitude ?? '';
                            villageSelect.appendChild(opt);
                        });
                        villageSelect.disabled = false;
                    });
            });

            villageSelect.addEventListener('change', function () {
                document.getElementById('oa_input_village').value = this.value;
                const selectedOption = this.options[this.selectedIndex];
                document.getElementById('oa_input_village_name').value = this.value ? selectedOption.textContent : '';

                if (!flyToOptionCoords(selectedOption, 14)) {
                    updateSaveButtonState();
                }
            });
        })();
    </script>
@endsection
