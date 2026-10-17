@extends('layouts.admin')

@section('title', 'เลือกพื้นที่ดำเนินงาน - พชอ.ไต')

@section('header_title')
    <div class="page-header">
        <div class="page-header-accent"></div>
        <div class="page-header-text">
            <span class="page-header-title">เลือกพื้นที่ดำเนินงาน</span>
            <small class="page-header-subtitle">
                <i class="fas fa-circle-info"></i>
                {{ auth()->user()->isSuperAdmin() ? 'เลือกพื้นที่ที่มีอยู่หรือปักพื้นที่ใหม่' : 'ปักพื้นที่ใหม่' }} แล้วกดบันทึก พื้นที่จะผูกกับหน่วยงานของคุณ
            </small>
        </div>
    </div>
@endsection

@php
    // Computed here (not inline in @json) - Blade splits directive arguments on commas.
    $kaPinsForJs = collect($pins)->map(function ($p) use ($chosenPinIds, $lockedPins, $ownReportedPins) {
        return [
            'id' => $p['id'],
            'name' => $p['display_name'],
            'breadcrumb' => $p['breadcrumb'],
            'province' => $p['province_name'],
            'province_id' => $p['province_id'],
            'fiscal_year' => $p['fiscal_year'],
            'selected' => in_array((int) $p['id'], $chosenPinIds ?? [], true),
            'locked' => ($lockedPins ?? [])[(int) $p['id']] ?? null,
            'agency' => $p['agency_label'] ?? null,
            'reported_own' => in_array((int) $p['id'], $ownReportedPins ?? [], true),
            'lat' => (float) $p['latitude'],
            'lng' => (float) $p['longitude'],
        ];
    })->values();
    $kaHasPins = $kaPinsForJs->isNotEmpty();
    // "เลือกจากพื้นที่ที่มีอยู่" ใช้ได้เฉพาะ SuperAdmin - หน่วยงานอื่นปักพื้นที่ใหม่ได้อย่างเดียว
    $kaCanPickExisting = (bool) auth()->user()->isSuperAdmin();
    $kaDefaultMode = ($kaHasPins && $kaCanPickExisting) ? 'existing' : 'new';
@endphp

@section('content')

    @include('partials.flash-alert')

    <div class="ka-card">
        <div class="ka-head">
            <div class="ka-title"><i class="fas fa-map-location-dot"></i> พื้นที่ดำเนินงานของหน่วยงาน</div>
            <span class="ka-count" id="ka_count">{{ number_format($kaPinsForJs->count()) }} พื้นที่</span>
        </div>

        <div class="ka-tabs" role="tablist">
            @if ($kaCanPickExisting)
                <button type="button" id="kaTabExisting" class="ka-tab{{ $kaDefaultMode === 'existing' ? ' active' : '' }}" data-mode="existing" role="tab">
                    <i class="fas fa-list-check"></i> เลือกจากพื้นที่ที่มีอยู่
                </button>
            @endif
            <button type="button" id="kaTabNew" class="ka-tab{{ $kaDefaultMode === 'new' ? ' active' : '' }}" data-mode="new" role="tab">
                <i class="fas fa-map-pin"></i> ปักหน่วยงานพื้นที่ใหม่
            </button>
        </div>

        {{-- Tab 1: browse the areas already saved --}}
        <div id="kaPanelExisting" style="{{ $kaDefaultMode === 'existing' ? '' : 'display:none;' }}">
            <div class="ka-field">
                <label for="kaSavedSelect">พื้นที่ที่บันทึกไว้แล้ว</label>
                <select class="ka-input" id="kaSavedSelect">
                    <option value="">-- เลือกพื้นที่เพื่อดูตำแหน่งบนแผนที่ --</option>
                    @foreach ($kaPinsForJs as $p)
                        <option value="{{ $p['id'] }}">{{ $p['name'] }}@if ($p['breadcrumb']) ({{ $p['breadcrumb'] }})@endif · ปีงบ {{ $p['fiscal_year'] }}@if ($p['selected']) · ✓ บันทึกแล้ว@if ($p['reported_own']) · มีรายงานแล้ว @endif @elseif ($p['locked'] === 'display') · ดำเนินงานโดย {{ $p['agency'] ?: 'หน่วยงานอื่น' }} · ดูอย่างเดียว @elseif ($p['locked'] === 'reported') · มีหน่วยงานรายงานแล้ว · ดูอย่างเดียว @elseif ($p['locked']) · หน่วยงานอื่น · ดูอย่างเดียว @elseif ($p['reported_own']) · มีรายงานของหน่วยงานท่านแล้ว @endif</option>
                    @endforeach
                </select>
            </div>
            <p class="ka-note">
                <i class="fas fa-circle-check"></i>
                เลือกพื้นที่แล้วกด <b>บันทึกพื้นที่นี้ให้หน่วยงาน</b> พื้นที่จะแสดงในรายการด้านล่างของหน่วยงาน
            </p>
            {{-- Shown once a saved area is picked: save it for the agency (stays on this page). --}}
            <div class="ka-pick" id="kaPickRow" style="display:none;">
                <form id="kaPickForm" method="POST" action="">
                    @csrf
                    <button type="submit" class="ka-btn">
                        <i class="fas fa-floppy-disk"></i> <span id="kaPickLabel">บันทึกพื้นที่นี้ให้หน่วยงาน</span>
                    </button>
                </form>
                <form id="kaDropForm" method="POST" action="" style="display:none;">
                    @csrf
                    <button type="submit" class="ka-btn ka-btn-ghost">
                        <i class="fas fa-circle-minus"></i> ยกเลิกการเลือกพื้นที่นี้
                    </button>
                </form>
                <span class="ka-chip" id="kaPickDone" style="display:none;"><i class="fas fa-circle-check"></i> บันทึกให้หน่วยงานแล้ว</span>
                <span class="ka-pick-name" id="kaPickName"></span>
            </div>
            {{-- เลือกหลายพื้นที่พร้อมกัน (SuperAdmin) --}}
            <div class="ka-multi" id="kaMulti">
                <div class="ka-multi-head">
                    <b><i class="fas fa-list-check"></i> เลือกหลายพื้นที่พร้อมกัน</b>
                    <div class="ka-multi-tools">
                        <button type="button" class="ka-mini" id="kaMultiAll">ติ๊กทั้งหมดที่แสดง</button>
                        <button type="button" class="ka-mini" id="kaMultiClear">ล้างที่ติ๊ก</button>
                    </div>
                </div>
                <ul class="ka-multi-list" id="kaMultiList"></ul>
                <form class="ka-multi-foot" id="kaMultiForm" method="POST" action="{{ route('admin.kidney-dhb.areas.select-many') }}">
                    @csrf
                    <span id="kaMultiInputs"></span>
                    <button type="submit" class="ka-btn" id="kaMultiBtn" disabled>
                        <i class="fas fa-floppy-disk"></i> บันทึกพื้นที่ที่ติ๊กให้หน่วยงาน
                    </button>
                    <span class="ka-multi-count" id="kaMultiCount">ยังไม่ได้ติ๊กพื้นที่</span>
                </form>
            </div>
            <p class="ka-empty" id="kaNoPins" style="{{ $kaHasPins ? 'display:none;' : '' }}">
                ยังไม่มีพื้นที่ที่บันทึกไว้ -
                <button type="button" class="ka-link" id="kaGoNew">ปักหน่วยงานพื้นที่ใหม่</button> ได้เลย
            </p>
        </div>

        {{-- Tab 2: pin a new area and save it --}}
        <form id="kaForm" action="{{ route('admin.kidney-dhb.areas.store') }}" method="POST"
            style="{{ $kaDefaultMode === 'new' ? '' : 'display:none;' }}">
            @csrf
            <input type="hidden" name="province_id" id="ka_in_province">
            <input type="hidden" name="district_id" id="ka_in_district">
            <input type="hidden" name="tambon_id" id="ka_in_tambon">
            <input type="hidden" name="village_id" id="ka_in_village">
            <input type="hidden" name="village_name_snapshot" id="ka_in_village_name">
            <input type="hidden" name="latitude" id="ka_in_lat">
            <input type="hidden" name="longitude" id="ka_in_lng">

            <div class="ka-grid">
                <div class="ka-field">
                    <label for="ka_fiscal_year">ปีงบประมาณ</label>
                    <select class="ka-input" name="fiscal_year" id="ka_fiscal_year" required>
                        @foreach ($fiscalYears as $year)
                            <option value="{{ $year }}" {{ (string) $year === (string) old('fiscal_year', $defaultFiscalYear) ? 'selected' : '' }}>{{ $year }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="ka-field">
                    <label for="ka_province">จังหวัด</label>
                    <select class="ka-input" id="ka_province" @if (!empty($lockedProvinceId)) disabled title="จังหวัดล็อกตามจังหวัดของผู้ใช้งาน" @endif>
                        @if (empty($lockedProvinceId))<option value="">-- เลือกจังหวัด --</option>@endif
                        @foreach ($provinces as $province)
                            <option value="{{ $province->province_id }}"
                                data-lat="{{ optional($provinceCoords->get($province->province_id))->lat }}"
                                data-lng="{{ optional($provinceCoords->get($province->province_id))->lng }}">{{ $province->province_name }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="ka-field">
                    <label for="ka_district">อำเภอ</label>
                    <select class="ka-input" id="ka_district" disabled><option value="">-- เลือกอำเภอ --</option></select>
                </div>
                <div class="ka-field">
                    <label for="ka_tambon">ตำบล</label>
                    <select class="ka-input" id="ka_tambon" disabled><option value="">-- เลือกตำบล --</option></select>
                </div>
                <div class="ka-field">
                    <label for="ka_village">ชุมชน/หมู่บ้าน</label>
                    <select class="ka-input" id="ka_village" disabled><option value="">-- เลือกชุมชน/หมู่บ้าน --</option></select>
                </div>
            </div>

            <div class="ka-field ka-note-field">
                <label for="ka_label">หมายเหตุ / ชื่อพื้นที่ (ถ้ามี)</label>
                <input type="text" class="ka-input ka-note-input" name="label" id="ka_label" maxlength="255"
                    value="{{ old('label') }}" placeholder="เช่น จุดรณรงค์ลดเค็มชุมชน...">
            </div>

            {{-- หน่วยงานดำเนินงานได้หลายพื้นที่: เพิ่มทีละพื้นที่เข้ารายการ แล้วกดบันทึกครั้งเดียว --}}
            <input type="hidden" name="areas_json" id="ka_areas_json" value="">
            <div class="ka-draft" id="ka_draft_box" style="display:none;">
                <div class="ka-draft-head">
                    <span><i class="fas fa-layer-group"></i> พื้นที่ที่จะบันทึก</span>
                    <span class="ka-draft-count" id="ka_draft_count">0 พื้นที่</span>
                </div>
                <p class="ka-draft-note">เลือกพื้นที่ถัดไปต่อได้เลย แล้วกด "บันทึกทั้งหมด" ครั้งเดียว</p>
                <ul class="ka-draft-list" id="ka_draft_list"></ul>
            </div>

            <div class="ka-save-row">
                <button type="button" id="ka_add_btn" class="ka-btn ka-btn-add" disabled>
                    <i class="fas fa-plus"></i> เพิ่มพื้นที่นี้เข้ารายการ
                </button>
                <button type="submit" id="ka_save_btn" class="ka-btn" disabled>
                    <i class="fas fa-floppy-disk"></i> <span id="ka_save_label">บันทึกพื้นที่ดำเนินงาน</span>
                </button>
            </div>
            <p class="ka-note" style="margin-top:8px;">ดำเนินงานมากกว่า 1 พื้นที่: กด "เพิ่มพื้นที่นี้เข้ารายการ" ทีละพื้นที่ แล้วบันทึกทั้งหมดพร้อมกัน</p>
        </form>

        <div id="ka_map" class="ka-map"></div>
        <div id="ka_legend" class="ka-legend"></div>
        <p class="ka-hint" id="kaHintExisting" style="{{ $kaDefaultMode === 'existing' ? '' : 'display:none;' }}">
            <i class="fas fa-circle-info"></i>
            หมุดแยกสีตามจังหวัด คลิกหมุดหรือเลือกจากรายการด้านบนเพื่อดูรายละเอียด · คลิกชื่อจังหวัดด้านล่างเพื่อกรอง
        </p>
        <p class="ka-hint" id="kaHintNew" style="{{ $kaDefaultMode === 'new' ? '' : 'display:none;' }}">
            <i class="fas fa-circle-info"></i>
            เลือกจังหวัด อำเภอ ตำบล หรือชุมชน/หมู่บ้านระดับใดก็ได้ หมุดสีแดงจะเลื่อนไปตำแหน่งโดยประมาณให้ทันที
            (ไม่ต้องเลือกจนถึงหมู่บ้าน) · คลิกบนแผนที่หรือลากหมุดสีแดงเพื่อปรับตำแหน่งจริง แล้วกดบันทึก
        </p>
    </div>

    <div class="ka-card">
        <div class="ka-head">
            <div class="ka-title"><i class="fas fa-table"></i> รายการพื้นที่ที่บันทึกแล้ว</div>
            @if (!empty($agencyLabel))
                <span class="ka-chip" style="background:#e0e7ff;color:#3730a3;"><i class="fas fa-building"></i> หน่วยงาน {{ $agencyLabel }}</span>
            @endif
        </div>
        @if ($kaHasPins)
            <div class="ka-table-wrap">
                <table class="ka-table" id="kaTable">
                    <thead>
                        <tr>
                            <th>ปีงบประมาณ</th>
                            <th>พื้นที่</th>
                            <th>จังหวัด</th>
                            <th>พิกัด</th>
                            <th>สถานะ</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach ($kaPinsForJs as $p)
                            <tr data-id="{{ $p['id'] }}" data-province-id="{{ $p['province_id'] }}" tabindex="0">
                                <td>{{ $p['fiscal_year'] }}</td>
                                <td>
                                    <div class="ka-row-name">{{ $p['name'] }}</div>
                                    @if ($p['breadcrumb'])
                                        <div class="ka-row-sub">{{ $p['breadcrumb'] }}</div>
                                    @endif
                                </td>
                                <td>{{ $p['province'] }}</td>
                                <td class="ka-coords">{{ number_format($p['lat'], 5) }}, {{ number_format($p['lng'], 5) }}</td>
                                <td>{!! $p['selected'] ? '<span class="ka-chip">บันทึกให้หน่วยงานแล้ว' . ($p['reported_own'] ? ' · มีรายงานแล้ว' : '') . '</span>' : ($p['locked'] ? '<span class="ka-chip off"><i class="fas fa-lock"></i> ' . ($p['locked'] === 'display' ? 'ดำเนินงานโดย ' . e($p['agency'] ?: 'หน่วยงานอื่น') . ' · ดูอย่างเดียว' : ($p['locked'] === 'reported' ? 'มีหน่วยงานรายงานแล้ว · ดูอย่างเดียว' : 'หน่วยงานอื่น · ดูอย่างเดียว')) . '</span>' : '<span class="ka-chip off">ยังไม่ได้เลือก' . ($p['reported_own'] ? ' · มีรายงานของท่านแล้ว' : '') . '</span>') !!}</td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        @else
            <p class="ka-empty">ยังไม่มีพื้นที่ที่บันทึกไว้ - เริ่มจากแท็บ "ปักหน่วยงานพื้นที่ใหม่" ด้านบน</p>
        @endif
    </div>
@endsection

@section('extra_css')
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/leaflet/1.9.4/leaflet.min.css" />
    <style>
        .ka-card { background:#fff; border:1px solid #eef1f6; border-radius:16px; box-shadow:0 4px 20px rgba(15,23,42,.06); padding:20px 22px; margin-bottom:20px; }
        .ka-head { display:flex; align-items:center; justify-content:space-between; gap:12px; margin-bottom:14px; flex-wrap:wrap; }
        .ka-title { font-family:'Noto Serif Thai',sans-serif; font-weight:700; font-size:.94rem; color:#1e293b; display:flex; align-items:center; gap:8px; }
        .ka-title i { color:#4f46e5; }
        .ka-count { font-size:.76rem; font-weight:600; color:#4338ca; background:#eef2ff; border:1px solid #e0e7ff; border-radius:999px; padding:3px 12px; white-space:nowrap; }

        .ka-tabs { display:flex; gap:10px; margin-bottom:16px; flex-wrap:wrap; }
        .ka-tab { display:inline-flex; align-items:center; gap:8px; border:1px solid #e2e8f0; background:#f8fafc; color:#475569; border-radius:999px; padding:9px 18px; font:inherit; font-size:.85rem; font-weight:600; cursor:pointer; transition:background .15s, color .15s, border-color .15s; }
        .ka-tab:hover { background:#eef2ff; color:#4338ca; }
        .ka-tab.active { background:linear-gradient(135deg,#6366f1,#4338ca); color:#fff; border-color:transparent; box-shadow:0 6px 16px rgba(67,56,202,.28); }
        .ka-tab:focus-visible, .ka-link:focus-visible, .ka-btn:focus-visible { outline:2px solid #4f46e5; outline-offset:2px; }

        .ka-grid { display:grid; grid-template-columns:repeat(auto-fit,minmax(180px,1fr)); gap:14px; }
        .ka-field label { display:block; font-size:.78rem; color:#64748b; margin-bottom:4px; font-weight:600; }
        .ka-input { width:100%; border:1px solid #e2e8f0; border-radius:10px; padding:9px 12px; font-size:.85rem; background:#f8fafc; }
        .ka-input:disabled { opacity:.55; cursor:not-allowed; }
        .ka-save-row { margin-top:14px; display:flex; align-items:flex-end; gap:14px; flex-wrap:wrap; }
        .ka-note-field { margin-top:14px; }
        .ka-note-input { border-radius:999px; padding:10px 16px; background:#f8fafc; border-color:#dbe4ef; }
        .ka-note-input::placeholder { color:#94a3b8; }
        .ka-note-input:focus { outline:2px solid #4f46e5; outline-offset:1px; background:#fff; }
        .ka-btn { display:inline-flex; align-items:center; gap:8px; border:none; border-radius:10px; padding:10px 20px; font-family:'Noto Serif Thai',sans-serif; font-size:.85rem; font-weight:600; cursor:pointer; color:#fff; background:linear-gradient(135deg,#4f46e5,#4338ca); }
        .ka-pick { margin-top:14px; display:flex; align-items:center; gap:14px; flex-wrap:wrap; }
        .ka-chip { display:inline-block; font-size:.72rem; font-weight:700; padding:2px 10px; border-radius:999px; background:#dcfce7; color:#166534; white-space:nowrap; }
        .ka-chip.off { background:#f1f5f9; color:#64748b; }
        .ka-pick-name { font-size:.84rem; font-weight:600; color:#334155; }
        .ka-btn-ghost { background:#fff; color:#b91c1c; border:1px solid #fecaca; }
        .ka-btn-add { background:#fff; color:#4338ca; border:1.5px solid #c7d2fe; }
        .ka-btn-add:hover:not(:disabled) { background:#eef2ff; }
        .ka-btn:disabled { opacity:.5; cursor:not-allowed; }
        .ka-draft { margin-top:14px; border:1px solid #dbe4f3; border-radius:14px; background:#f7faff; padding:12px 16px; }
        .ka-draft-head { display:flex; align-items:center; justify-content:space-between; gap:10px; flex-wrap:wrap; font-size:.88rem; font-weight:700; color:#1e3a8a; }
        .ka-draft-count { font-size:.76rem; font-weight:700; color:#1d4ed8; background:#dbeafe; border-radius:999px; padding:2px 12px; }
        .ka-draft-note { margin:4px 0 8px; font-size:.78rem; color:#64748b; line-height:1.6; }
        .ka-draft-list { list-style:none; margin:0; padding:0; display:grid; gap:6px; }
        .ka-draft-item { display:flex; align-items:center; gap:10px; background:#fff; border:1px solid #e2e8f0; border-radius:10px; padding:8px 12px; }
        .ka-draft-no { flex:none; width:24px; height:24px; border-radius:50%; background:#4f46e5; color:#fff; font-size:.75rem; font-weight:700; display:flex; align-items:center; justify-content:center; }
        .ka-draft-text { flex:1; min-width:0; font-size:.84rem; color:#1e293b; line-height:1.45; word-break:break-word; }
        .ka-draft-text small { display:block; color:#64748b; font-size:.74rem; }
        .ka-draft-del { flex:none; border:none; background:none; color:#b91c1c; cursor:pointer; font-size:.95rem; padding:4px 6px; border-radius:8px; }
        .ka-draft-del:hover { background:#fee2e2; }
        .ka-multi { margin-top:16px; border:1px solid #e2e8f0; border-radius:12px; background:#fff; overflow:hidden; }
        .ka-multi-head { display:flex; align-items:center; justify-content:space-between; gap:8px 12px; flex-wrap:wrap; padding:10px 14px; background:#f8fafc; border-bottom:1px solid #e2e8f0; }
        .ka-multi-head b { font-size:.86rem; color:#1e293b; }
        .ka-multi-tools { display:flex; gap:6px; flex-wrap:wrap; }
        .ka-mini { border:1px solid #c7d2fe; background:#fff; color:#4338ca; border-radius:999px; padding:3px 12px; font-size:.74rem; font-weight:600; cursor:pointer; font-family:inherit; }
        .ka-mini:hover { background:#eef2ff; }
        .ka-multi-list { max-height:280px; overflow:auto; margin:0; padding:4px 0; list-style:none; }
        .ka-multi-list li { margin:0; }
        .ka-multi-item { display:flex; align-items:flex-start; gap:10px; padding:8px 14px; cursor:pointer; font-size:.84rem; line-height:1.4; }
        .ka-multi-item:hover { background:#f8fafc; }
        .ka-multi-item input { margin-top:3px; width:16px; height:16px; accent-color:#4f46e5; flex:none; }
        .ka-multi-item small { display:block; color:#64748b; font-size:.74rem; }
        .ka-multi-item.is-off { opacity:.6; cursor:default; }
        .ka-multi-foot { display:flex; align-items:center; gap:12px; flex-wrap:wrap; padding:10px 14px; border-top:1px solid #e2e8f0; background:#f8fafc; }
        .ka-multi-count { font-size:.82rem; font-weight:600; color:#334155; }
        .ka-multi-empty { padding:14px; font-size:.82rem; color:#64748b; }
        .ka-pick form { margin:0; }
        .ka-btn:disabled { background:#cbd5e1; cursor:not-allowed; }
        .ka-note { margin:10px 2px 0; font-size:.8rem; color:#475569; }
        .ka-note i { color:#16a34a; margin-right:4px; }
        .ka-empty { margin:12px 2px 0; font-size:.85rem; color:#64748b; }
        .ka-link { border:none; background:none; padding:0; font:inherit; color:#4f46e5; font-weight:700; text-decoration:underline; cursor:pointer; }

        .ka-map { width:100%; height:420px; border-radius:14px; border:1px solid #e2e8f0; margin-top:16px; }
        .ka-hint { font-size:.78rem; color:#64748b; margin:10px 2px 0; }
        .ka-legend { display:flex; flex-wrap:wrap; gap:8px 10px; margin-top:12px; }
        .ka-legend-item { display:inline-flex; align-items:center; gap:7px; padding:4px 11px 4px 9px; border-radius:999px; border:1px solid transparent; font:inherit; font-size:.8rem; font-weight:700; color:#334155; cursor:pointer; }
        .ka-legend-item .ka-dot { width:9px; height:9px; border-radius:50%; flex:none; }
        .ka-legend-item .ka-n { font-size:.72rem; font-weight:800; opacity:.75; font-variant-numeric:tabular-nums; }
        .ka-legend-item.is-active { box-shadow:0 0 0 2px var(--c); background:#fff !important; }
        .ka-legend-item.is-empty { opacity:.45; cursor:default; }
        .ka-legend-item:focus-visible { outline:2px solid #4f46e5; outline-offset:2px; }

        .oa-pin-icon { background:transparent; border:none; }
        .oa-pin-icon.oa-draft svg { filter:drop-shadow(0 4px 6px rgba(185,28,28,.35)); }
        .ka-popup { min-width:180px; font-size:.8rem; color:#1e293b; }
        .ka-popup b { display:block; font-size:.88rem; }
        .ka-popup span { display:block; margin-top:3px; font-size:.74rem; color:#64748b; }

        .ka-table-wrap { border:1px solid #eef1f6; border-radius:12px; overflow:auto; }
        .ka-table { width:100%; min-width:560px; border-collapse:collapse; }
        .ka-table thead th { text-align:left; background:#eef2ff; color:#4338ca; font-size:.74rem; font-weight:600; padding:11px 16px; border-bottom:1px solid #e0e7ff; white-space:nowrap; }
        .ka-table tbody td { padding:12px 16px; border-bottom:1px solid #eef1f6; font-size:.84rem; color:#334155; vertical-align:top; }
        .ka-table tbody tr { cursor:pointer; }
        .ka-table tbody tr:last-child td { border-bottom:none; }
        .ka-table tbody tr:hover td, .ka-table tbody tr:focus-visible td { background:#f8fafc; }
        .ka-row-name { font-weight:600; color:#1e293b; }
        .ka-row-sub { font-size:.74rem; color:#94a3b8; margin-top:2px; }
        .ka-coords { font-family:ui-monospace,Consolas,monospace; font-size:.76rem; color:#475569; white-space:nowrap; }

        @media (max-width:640px) { .ka-card { padding:16px; } .ka-map { height:320px; } .ka-tab { flex:1 1 100%; justify-content:center; } }
    </style>
@endsection

@section('extra_js')
    <script src="https://cdnjs.cloudflare.com/ajax/libs/leaflet/1.9.4/leaflet.min.js"></script>
    {{-- District polygons of เขต 10, tinted per province under the pins (same data file as admin.operating-areas.index). --}}
    <script>window.Highcharts = window.Highcharts || { maps: {} };</script>
    <script src="{{ asset('vendor/highcharts/th-districts-r10.js') }}"></script>
    <script>
    (function () {
        const pins = @json($kaPinsForJs);
        const ownLoc = @json($userOwnLocation);
        const lockedProvince = @json(!empty($lockedProvinceId));
        const $ = id => document.getElementById(id);
        const esc = v => String(v == null ? '' : v).replace(/[&<>"']/g, c => ({ '&': '&amp;', '<': '&lt;', '>': '&gt;', '"': '&quot;', "'": '&#39;' }[c]));
        const rgba = (hex, a) => { const n = parseInt(hex.slice(1), 16); return 'rgba(' + (n >> 16) + ',' + ((n >> 8) & 255) + ',' + (n & 255) + ',' + a + ')'; };
        let mode = '{{ $kaDefaultMode }}';
        const canPickExisting = @json($kaCanPickExisting);

        const map = L.map('ka_map', { scrollWheelZoom: true, zoomSnap: 0.5, zoomDelta: 0.5, minZoom: 6, maxZoom: 18 }).setView([15.9, 104.5], 7);
        L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', { maxZoom: 18, attribution: '&copy; OpenStreetMap contributors' }).addTo(map);

        const provinceColors = {
            'อุบลราชธานี': { fill: '#2563eb', stroke: '#1e40af' },
            'ศรีสะเกษ': { fill: '#d97706', stroke: '#92400e' },
            'ยโสธร': { fill: '#7c3aed', stroke: '#5b21b6' },
            'อำนาจเจริญ': { fill: '#16a34a', stroke: '#15803d' },
            'มุกดาหาร': { fill: '#db2777', stroke: '#9d174d' },
        };
        const defaultColor = { fill: '#6b7280', stroke: '#374151' };
        const colorOf = n => provinceColors[n] || defaultColor;

        const districtGeo = window.Highcharts && Highcharts.maps && Highcharts.maps['countries/th/th-districts-r10'];
        if (districtGeo) {
            L.geoJSON(districtGeo, {
                interactive: false,
                style: f => { const c = colorOf(f.properties.province_name); return { color: c.fill, weight: 1, opacity: .55, fillColor: c.fill, fillOpacity: .12, lineJoin: 'round' }; }
            }).addTo(map);
        }

        function pinIcon(fill, stroke, cls) {
            const svg = '<svg width="30" height="40" viewBox="0 0 34 46" xmlns="http://www.w3.org/2000/svg">' +
                '<ellipse cx="17" cy="41" rx="8" ry="2.6" fill="#0f172a" opacity="0.25"/>' +
                '<path d="M17 2C9.8 2 4 7.8 4 15c0 10.2 13 27 13 27s13-16.8 13-27C30 7.8 24.2 2 17 2z" fill="' + fill + '" stroke="' + stroke + '" stroke-width="1.5" stroke-linejoin="round"/>' +
                '<circle cx="17" cy="15" r="5.5" fill="#fff"/></svg>';
            return L.divIcon({ html: svg, className: 'oa-pin-icon' + (cls ? ' ' + cls : ''), iconSize: [30, 40], iconAnchor: [15, 38], popupAnchor: [0, -34] });
        }
        const iconCache = {};
        const provinceIcon = n => iconCache[n || '_'] || (iconCache[n || '_'] = pinIcon(colorOf(n).fill, colorOf(n).stroke));
        const draftIcon = pinIcon('#dc2626', '#7f1d1d', 'oa-draft');

        // ---- Saved pins (tab 1) ------------------------------------------------
        const savedLayer = L.layerGroup().addTo(map);
        let markerById = {};
        let provFilter = '';
        const baseUrl = '{{ url('/admin/kidney-dhb-areas') }}';

        function popupHtml(p) {
            return '<div class="ka-popup"><b>' + esc(p.name) + '</b>' +
                (p.breadcrumb ? '<span>' + esc(p.breadcrumb) + '</span>' : '') +
                '<span>ปีงบประมาณ ' + esc(p.fiscal_year) + '</span></div>';
        }

        function renderLegend() {
            const counts = {};
            const idByName = {};
            pins.forEach(p => { counts[p.province] = (counts[p.province] || 0) + 1; if (p.province) idByName[p.province] = String(p.province_id); });
            const names = Object.keys(provinceColors).concat(Object.keys(counts).filter(n => n && !provinceColors[n]));
            $('ka_legend').innerHTML = names.map(n => {
                const c = colorOf(n), cnt = counts[n] || 0, id = idByName[n] || '';
                const active = id && provFilter === id;
                return '<button type="button" class="ka-legend-item' + (cnt ? '' : ' is-empty') + (active ? ' is-active' : '') + '" data-province-id="' + esc(id) + '"' +
                    ' aria-pressed="' + (active ? 'true' : 'false') + '" style="--c:' + c.fill + ';border-color:' + rgba(c.stroke, .35) + ';background:' + rgba(c.fill, .1) + ';">' +
                    '<span class="ka-dot" style="background:' + c.fill + '"></span>' + esc(n) + ' <span class="ka-n">' + cnt + '</span></button>';
            }).join('');
        }

        // ---- เลือกหลายพื้นที่ (ติ๊กแล้วบันทึกครั้งเดียว) ----
        const multiPicked = new Set();
        const visiblePins = () => pins.filter(p => !provFilter || String(p.province_id) === provFilter);
        const pickable = p => !p.selected && !p.locked;
        function updateMulti() {
            const n = multiPicked.size;
            $('kaMultiCount').textContent = n ? ('ติ๊กแล้ว ' + n + ' พื้นที่') : 'ยังไม่ได้ติ๊กพื้นที่';
            $('kaMultiBtn').disabled = n === 0;
            $('kaMultiBtn').lastChild.textContent = n > 1 ? (' บันทึก ' + n + ' พื้นที่ให้หน่วยงาน') : ' บันทึกพื้นที่ที่ติ๊กให้หน่วยงาน';
            $('kaMultiInputs').innerHTML = Array.from(multiPicked).map(id => '<input type="hidden" name="pin_ids[]" value="' + esc(id) + '">').join('');
        }
        function renderMulti() {
            const list = visiblePins();
            // drop ticks that are no longer pickable / hidden by the province filter
            Array.from(multiPicked).forEach(id => { const p = pins.find(x => String(x.id) === String(id)); if (!p || !pickable(p) || !list.includes(p)) multiPicked.delete(id); });
            if (!list.length) { $('kaMultiList').innerHTML = '<li class="ka-multi-empty">ไม่มีพื้นที่ที่บันทึกไว้</li>'; updateMulti(); return; }
            $('kaMultiList').innerHTML = list.map(p => {
                const ok = pickable(p);
                const note = p.selected ? 'บันทึกให้หน่วยงานแล้ว' : (p.locked ? 'ดูอย่างเดียว (หน่วยงานอื่น)' : '');
                return '<li><label class="ka-multi-item' + (ok ? '' : ' is-off') + '">' +
                    '<input type="checkbox" value="' + esc(p.id) + '"' + (ok ? '' : ' disabled') + (multiPicked.has(p.id) ? ' checked' : '') + '>' +
                    '<span>' + esc(p.name) + (p.breadcrumb ? ' <small>' + esc(p.breadcrumb) + ' · ปีงบ ' + esc(p.fiscal_year) + '</small>' : ' <small>ปีงบ ' + esc(p.fiscal_year) + '</small>') +
                    (note ? '<small>' + note + '</small>' : '') + '</span></label></li>';
            }).join('');
            updateMulti();
        }
        $('kaMultiList').addEventListener('change', function (e) {
            const cb = e.target.closest('input[type=checkbox]');
            if (!cb) return;
            const id = Number(cb.value);
            if (cb.checked) multiPicked.add(id); else multiPicked.delete(id);
            updateMulti();
            const mk = markerById[id];
            if (cb.checked && mk) { map.panTo(mk.getLatLng()); mk.openPopup(); }
        });
        $('kaMultiAll').addEventListener('click', function () {
            visiblePins().filter(pickable).forEach(p => multiPicked.add(p.id));
            renderMulti();
        });
        $('kaMultiClear').addEventListener('click', function () { multiPicked.clear(); renderMulti(); });

        function showPick(p) {
            const row = $('kaPickRow');
            if (!p) { row.style.display = 'none'; return; }
            row.style.display = '';
            $('kaPickForm').action = baseUrl + '/' + encodeURIComponent(p.id) + '/select';
            $('kaPickForm').style.display = p.selected ? 'none' : '';
            $('kaPickDone').style.display = p.selected ? '' : 'none';
            $('kaDropForm').action = baseUrl + '/' + encodeURIComponent(p.id) + '/deselect';
            $('kaDropForm').style.display = p.selected ? '' : 'none';
            $('kaPickName').textContent = p.name + (p.breadcrumb ? ' (' + p.breadcrumb + ')' : '');
        }

        function renderPins(fit) {
            savedLayer.clearLayers();
            $('kaSavedSelect').value = '';
            showPick(null);
            markerById = {};
            const pts = [];
            pins.filter(p => !provFilter || String(p.province_id) === provFilter).forEach(p => {
                if (isNaN(p.lat) || isNaN(p.lng)) return;
                const m = L.marker([p.lat, p.lng], { icon: provinceIcon(p.province) }).addTo(savedLayer).bindPopup(popupHtml(p));
                m.on('click', () => selectSaved(p.id, false));
                markerById[p.id] = m;
                pts.push([p.lat, p.lng]);
            });
            document.querySelectorAll('#kaTable tbody tr').forEach(tr => {
                tr.style.display = (!provFilter || tr.dataset.provinceId === provFilter) ? '' : 'none';
            });
            renderLegend();
            renderMulti();
            if (fit) {
                if (pts.length) map.fitBounds(pts, { padding: [30, 30], maxZoom: provFilter ? 11 : 10 });
                else map.setView([15.9, 104.5], 7);
            }
        }

        function selectSaved(id, scroll) {
            const p = pins.find(x => String(x.id) === String(id));
            if (!p) return;
            if (!markerById[id]) { provFilter = ''; renderPins(false); }
            $('kaSavedSelect').value = String(id);
            const mk = markerById[id];
            if (mk) { map.flyTo(mk.getLatLng(), Math.max(map.getZoom(), 12)); mk.openPopup(); }
            // Another agency has already reported on this area this fiscal year -
            // warn and don't offer the save button (the server refuses it too).
            if (p.locked && !p.selected) {
                showPick(null);
                Swal.fire({
                    icon: 'warning',
                    title: 'ไม่สามารถเลือกพื้นที่นี้ได้',
                    text: p.locked === 'display'
                        ? 'พื้นที่นี้มีการดำเนินงาน พชอ.ไต โดย ' + (p.agency || 'หน่วยงานอื่น') + ' แล้ว (หมุดแสดงผล) ดูข้อมูลได้อย่างเดียว เลือกไม่ได้'
                        : (p.locked === 'reported'
                            ? 'พื้นที่นี้มีหน่วยงานอื่นรายงานผลในปีงบประมาณนี้แล้ว ดูข้อมูลได้อย่างเดียว เลือกไม่ได้'
                            : 'พื้นที่นี้เป็นพื้นที่ดำเนินงานของหน่วยงานอื่น สามารถดูข้อมูลได้อย่างเดียว เลือกไม่ได้'),
                    footer: esc(p.name) + ' · ปีงบประมาณ ' + esc(p.fiscal_year),
                    confirmButtonText: 'ตกลง',
                    confirmButtonColor: '#4f46e5'
                }).then(() => { $('kaSavedSelect').value = ''; showPick(null); });
                return;
            }
            showPick(p);
            if (scroll) $('ka_map').scrollIntoView({ behavior: 'smooth', block: 'center' });
        }

        $('kaSavedSelect').addEventListener('change', function () { if (this.value) selectSaved(this.value, false); else { map.closePopup(); showPick(null); } });
        $('ka_legend').addEventListener('click', function (e) {
            if (mode !== 'existing') return;
            const b = e.target.closest('.ka-legend-item');
            if (!b || b.classList.contains('is-empty') || !b.dataset.provinceId) return;
            provFilter = (provFilter === b.dataset.provinceId) ? '' : b.dataset.provinceId;
            renderPins(true);
        });
        document.querySelectorAll('#kaTable tbody tr').forEach(tr => {
            const go = () => { if (!canPickExisting) return; setMode('existing'); selectSaved(tr.dataset.id, true); };
            tr.addEventListener('click', go);
            tr.addEventListener('keydown', e => { if (e.key === 'Enter' || e.key === ' ') { e.preventDefault(); go(); } });
        });

        // ---- New pin (tab 2) ---------------------------------------------------
        const sel = { p: $('ka_province'), d: $('ka_district'), t: $('ka_tambon'), v: $('ka_village') };
        const inp = { p: $('ka_in_province'), d: $('ka_in_district'), t: $('ka_in_tambon'), v: $('ka_in_village'), vn: $('ka_in_village_name'), lat: $('ka_in_lat'), lng: $('ka_in_lng') };
        let draft = null;
        let geoToken = 0;      // bumped on every pick so a slow name lookup can't overwrite a newer one
        let prefillToken = 0;  // bumped on every manual pick so the own-location prefill stops
        let labelEdited = false;
        const geoCache = {};

        // พื้นที่ที่เพิ่มเข้ารายการแล้ว (ยังไม่บันทึก) - หน่วยงานดำเนินงานได้หลายพื้นที่
        const drafts = [];
        const formComplete = () => !!($('ka_fiscal_year').value && inp.p.value && inp.d.value && inp.lat.value && inp.lng.value);
        const areaKey = d => [d.fiscal_year, d.province_id, d.district_id, d.tambon_id || '', d.village_id || '', d.label || ''].join('|');
        const formInDrafts = () => formComplete() && drafts.some(d => areaKey(d) === areaKey(currentAreaItem()));
        // ช่องในฟอร์มนับเป็น 1 พื้นที่ก็ต่อเมื่อครบและยังไม่อยู่ในรายการที่เพิ่มไว้ (ไม่นับซ้ำ)
        const formCounts = () => formComplete() && !formInDrafts();
        function updateSave() {
            const ok = formComplete();
            const total = drafts.length + (formCounts() ? 1 : 0);
            $('ka_add_btn').disabled = !ok || formInDrafts();
            $('ka_save_btn').disabled = total === 0;
            $('ka_save_label').textContent = total > 1 ? 'บันทึกทั้งหมด (' + total + ' พื้นที่)' : 'บันทึกพื้นที่ดำเนินงาน';
        }
        function optText(s) { return (s.value && s.options[s.selectedIndex]) ? s.options[s.selectedIndex].textContent.trim() : ''; }
        function currentAreaItem() {
            const crumbs = [optText(sel.v), optText(sel.t) && ('ตำบล' + optText(sel.t)), optText(sel.d) && ('อำเภอ' + optText(sel.d))].filter(Boolean);
            const label = $('ka_label').value.trim();
            return {
                fiscal_year: $('ka_fiscal_year').value,
                province_id: inp.p.value, district_id: inp.d.value, tambon_id: inp.t.value, village_id: inp.v.value,
                village_name_snapshot: inp.vn.value, latitude: inp.lat.value, longitude: inp.lng.value, label: label,
                _title: crumbs[0] || optText(sel.p), _sub: crumbs.slice(1).join(' ') + (label ? (crumbs.length > 1 ? ' · ' : '') + 'ประเด็น: ' + label : '')
            };
        }
        // หมุดของแต่ละพื้นที่ในรายการ (เลือกกี่พื้นที่ ปักกี่หมุด) - หมายเลขตรงกับลำดับในรายการ
        const draftLayer = L.layerGroup().addTo(map);
        const numIcon = n => L.divIcon({
            html: '<svg width="30" height="40" viewBox="0 0 34 46" xmlns="http://www.w3.org/2000/svg">' +
                '<ellipse cx="17" cy="41" rx="8" ry="2.6" fill="#0f172a" opacity="0.25"/>' +
                '<path d="M17 2C9.8 2 4 7.8 4 15c0 10.2 13 27 13 27s13-16.8 13-27C30 7.8 24.2 2 17 2z" fill="#4f46e5" stroke="#3730a3" stroke-width="1.5" stroke-linejoin="round"/>' +
                '<circle cx="17" cy="15" r="8" fill="#fff"/><text x="17" y="19.5" text-anchor="middle" font-size="12" font-weight="700" fill="#3730a3" font-family="sans-serif">' + n + '</text></svg>',
            className: 'oa-pin-icon', iconSize: [30, 40], iconAnchor: [15, 38], popupAnchor: [0, -34]
        });
        function renderDraftMarkers() {
            draftLayer.clearLayers();
            drafts.forEach(function (d, i) {
                const lat = parseFloat(d.latitude), lng = parseFloat(d.longitude);
                if (isNaN(lat) || isNaN(lng)) return;
                L.marker([lat, lng], { icon: numIcon(i + 1), keyboard: false }).addTo(draftLayer)
                    .bindTooltip((i + 1) + '. ' + d._title, { direction: 'top', offset: [0, -34] });
            });
        }
        // ให้เห็นหมุดทุกพื้นที่ในรายการพร้อมกัน
        function fitDraftMarkers() {
            const pts = drafts.map(d => [parseFloat(d.latitude), parseFloat(d.longitude)]).filter(c => !isNaN(c[0]) && !isNaN(c[1]));
            if (pts.length > 1) map.fitBounds(pts, { padding: [50, 50], maxZoom: 11 });
            else if (pts.length === 1) map.flyTo(pts[0], Math.max(map.getZoom(), 10));
        }
        function renderDrafts() {
            renderDraftMarkers();
            const box = $('ka_draft_box'), list = $('ka_draft_list');
            box.style.display = drafts.length ? '' : 'none';
            $('ka_draft_count').textContent = drafts.length + ' พื้นที่';
            list.innerHTML = '';
            drafts.forEach(function (d, i) {
                const li = document.createElement('li');
                li.className = 'ka-draft-item';
                li.innerHTML = '<span class="ka-draft-no">' + (i + 1) + '</span><span class="ka-draft-text">' + esc(d._title) +
                    (d._sub ? '<small>' + esc(d._sub) + '</small>' : '') + '</span>' +
                    '<button type="button" class="ka-draft-del" title="เอาพื้นที่นี้ออกจากรายการ" data-i="' + i + '"><i class="fas fa-xmark"></i></button>';
                list.appendChild(li);
            });
            updateSave();
        }
        $('ka_draft_list').addEventListener('click', function (e) {
            const b = e.target.closest('.ka-draft-del');
            if (!b) return;
            drafts.splice(parseInt(b.dataset.i, 10), 1);
            renderDrafts();
            fitDraftMarkers();
        });
        $('ka_add_btn').addEventListener('click', function () {
            if (!formComplete()) return;
            const item = currentAreaItem();
            if (drafts.some(d => areaKey(d) === areaKey(item))) {
                if (window.Swal) { Swal.fire({ icon: 'info', text: 'พื้นที่นี้อยู่ในรายการแล้ว', confirmButtonText: 'ตกลง' }); } else { alert('พื้นที่นี้อยู่ในรายการแล้ว'); }
                return;
            }
            drafts.push(item);
            // เตรียมกรอกพื้นที่ถัดไป: คงจังหวัด/อำเภอไว้ ล้างตำบล หมู่บ้าน และหมายเหตุ
            inp.t.value = inp.v.value = inp.vn.value = '';
            sel.t.value = '';
            resetSel(sel.v, '-- เลือกชุมชน/หมู่บ้าน --');
            if (sel.t.options.length > 1) sel.t.disabled = false;
            $('ka_label').value = ''; labelEdited = false;
            removeDraft();
            renderDrafts();
            fitDraftMarkers();
        });
        $('kaForm').addEventListener('submit', function () {
            // ไม่มีรายการที่เพิ่มไว้ = บันทึกพื้นที่เดียวจากช่องในฟอร์มเหมือนเดิม
            if (!drafts.length) { $('ka_areas_json').value = ''; return; }
            const all = drafts.slice();
            if (formCounts()) all.push(currentAreaItem());
            $('ka_areas_json').value = JSON.stringify(all.map(function (d) {
                return { fiscal_year: d.fiscal_year, province_id: d.province_id, district_id: d.district_id, tambon_id: d.tambon_id || null,
                    village_id: d.village_id || null, village_name_snapshot: d.village_name_snapshot || null,
                    latitude: d.latitude, longitude: d.longitude, label: d.label || null };
            }));
        });
        function resetSel(s, ph) { s.innerHTML = '<option value="">' + ph + '</option>'; s.disabled = true; }
        function removeDraft() { if (draft) { map.removeLayer(draft); draft = null; } geoToken++; inp.lat.value = ''; inp.lng.value = ''; updateSave(); }

        function setDraft(lat, lng, zoom) {
            if (draft) draft.setLatLng([lat, lng]);
            else {
                draft = L.marker([lat, lng], { icon: draftIcon, draggable: true }).addTo(map);
                draft.on('dragend', function () {
                    const pos = draft.getLatLng();
                    inp.lat.value = pos.lat.toFixed(6); inp.lng.value = pos.lng.toFixed(6); updateSave();
                });
            }
            inp.lat.value = lat.toFixed(6); inp.lng.value = lng.toFixed(6);
            if (zoom) map.flyTo([lat, lng], zoom);
            updateSave();
        }

        function placeQuery() {
            const parts = [];
            const add = (s, prefix) => { if (s.value) { const o = s.options[s.selectedIndex]; if (o) parts.push(prefix + o.textContent.trim()); } };
            add(sel.v, ''); add(sel.t, 'ตำบล'); add(sel.d, 'อำเภอ'); add(sel.p, 'จังหวัด');
            return parts.join(' ');
        }

        // Stored coordinates first; otherwise look the place up by its Thai name.
        function placeFromOption(option, zoom) {
            const token = ++geoToken;
            const lat = option ? parseFloat(option.dataset.lat) : NaN;
            const lng = option ? parseFloat(option.dataset.lng) : NaN;
            if (option && option.dataset.lat && option.dataset.lng && !isNaN(lat) && !isNaN(lng)) { setDraft(lat, lng, zoom); return; }
            const q = placeQuery();
            if (!q) return;
            const lookup = Object.prototype.hasOwnProperty.call(geoCache, q)
                ? Promise.resolve(geoCache[q])
                : fetch('https://nominatim.openstreetmap.org/search?format=jsonv2&limit=1&countrycodes=th&accept-language=th&q=' + encodeURIComponent(q))
                    .then(r => r.json())
                    .then(rows => {
                        const r = rows && rows[0];
                        const pt = r ? { lat: parseFloat(r.lat), lng: parseFloat(r.lon) } : null;
                        geoCache[q] = (pt && !isNaN(pt.lat) && !isNaN(pt.lng)) ? pt : null;
                        return geoCache[q];
                    })
                    .catch(() => null);
            lookup.then(pt => { if (token === geoToken && pt) setDraft(pt.lat, pt.lng, zoom); });
        }

        function suggestLabel() {
            return; // หมายเหตุ/ชื่อพื้นที่เป็นช่องเสริม - ไม่เติมให้อัตโนมัติ (ชื่อพื้นที่ใช้ชื่อหมู่บ้าน/ตำบล/อำเภอที่เลือกอยู่แล้ว)
            if (labelEdited) return;
            const name = s => (s.value && s.options[s.selectedIndex]) ? s.options[s.selectedIndex].textContent.trim() : '';
            const v = name(sel.v), t = name(sel.t), d = name(sel.d);
            const text = v || (t ? 'ตำบล' + t : (d ? 'อำเภอ' + d : ''));
            if (text) $('ka_label').value = text;
        }
        $('ka_label').addEventListener('input', () => { labelEdited = true; });

        const getJson = url => fetch(url, { headers: { 'Accept': 'application/json' } }).then(r => r.json());
        function fillOptions(s, rows, idKey, nameKey, ph, latKey, lngKey) {
            s.innerHTML = '<option value="">' + ph + '</option>';
            rows.forEach(r => {
                const o = document.createElement('option');
                o.value = r[idKey]; o.textContent = r[nameKey];
                o.dataset.lat = r[latKey] ?? ''; o.dataset.lng = r[lngKey] ?? '';
                s.appendChild(o);
            });
            s.disabled = false;
        }
        const loadDistricts = pid => getJson(APP_BASE + '/admin/operating-areas/get-districts/' + pid).then(r => fillOptions(sel.d, r, 'district_id', 'district_name', '-- เลือกอำเภอ --', 'lat', 'lng'));
        const loadTambons = (pid, did) => getJson(APP_BASE + '/admin/operating-areas/get-tambons/' + pid + '/' + did).then(r => fillOptions(sel.t, r, 'tambon_id', 'tambon_name', '-- เลือกตำบล --', 'lat', 'lng'));
        const loadVillages = (pid, did, tid) => getJson(APP_BASE + '/admin/operating-areas/get-villages/' + pid + '/' + did + '/' + tid).then(r => fillOptions(sel.v, r, 'village_id', 'village_name', '-- เลือกชุมชน/หมู่บ้าน --', 'latitude', 'longitude'));

        sel.p.addEventListener('change', function () {
            prefillToken++;
            inp.p.value = this.value; inp.d.value = inp.t.value = inp.v.value = inp.vn.value = '';
            resetSel(sel.d, '-- เลือกอำเภอ --'); resetSel(sel.t, '-- เลือกตำบล --'); resetSel(sel.v, '-- เลือกชุมชน/หมู่บ้าน --');
            if (!this.value) { removeDraft(); return; }
            placeFromOption(this.options[this.selectedIndex], 8);
            updateSave();
            loadDistricts(this.value);
        });
        sel.d.addEventListener('change', function () {
            prefillToken++;
            inp.d.value = this.value; inp.t.value = inp.v.value = inp.vn.value = '';
            resetSel(sel.t, '-- เลือกตำบล --'); resetSel(sel.v, '-- เลือกชุมชน/หมู่บ้าน --');
            suggestLabel();
            if (!this.value) { updateSave(); return; }
            placeFromOption(this.options[this.selectedIndex], 10);
            updateSave();
            loadTambons(sel.p.value, this.value);
        });
        sel.t.addEventListener('change', function () {
            prefillToken++;
            inp.t.value = this.value; inp.v.value = inp.vn.value = '';
            resetSel(sel.v, '-- เลือกชุมชน/หมู่บ้าน --');
            suggestLabel();
            if (!this.value) return;
            placeFromOption(this.options[this.selectedIndex], 12);
            loadVillages(sel.p.value, sel.d.value, this.value);
        });
        sel.v.addEventListener('change', function () {
            prefillToken++;
            inp.v.value = this.value;
            const o = this.options[this.selectedIndex];
            inp.vn.value = this.value ? o.textContent : '';
            suggestLabel();
            if (this.value) placeFromOption(o, 14);
        });
        $('ka_fiscal_year').addEventListener('change', updateSave);
        map.on('click', function (e) { if (mode === 'new') setDraft(e.latlng.lat, e.latlng.lng); });

        // Start the cascade from the office's own location (stops at ตำบล) - never overrides a pick already made.
        async function prefillOwnLocation() {
            if (!ownLoc || !ownLoc.province_id || (sel.p.value && !lockedProvince)) return;
            const my = ++prefillToken;
            sel.p.value = String(ownLoc.province_id);
            if (sel.p.value !== String(ownLoc.province_id)) return;
            inp.p.value = sel.p.value;
            let deepest = sel.p.options[sel.p.selectedIndex], zoom = 8;
            try {
                await loadDistricts(ownLoc.province_id);
                if (my !== prefillToken) return;
                if (ownLoc.district_id) {
                    sel.d.value = String(ownLoc.district_id);
                    if (sel.d.value === String(ownLoc.district_id)) {
                        inp.d.value = sel.d.value; deepest = sel.d.options[sel.d.selectedIndex]; zoom = 10;
                        await loadTambons(ownLoc.province_id, ownLoc.district_id);
                        if (my !== prefillToken) return;
                        if (ownLoc.tambon_id) {
                            sel.t.value = String(ownLoc.tambon_id);
                            if (sel.t.value === String(ownLoc.tambon_id)) {
                                inp.t.value = sel.t.value; deepest = sel.t.options[sel.t.selectedIndex]; zoom = 12;
                                await loadVillages(ownLoc.province_id, ownLoc.district_id, ownLoc.tambon_id);
                                if (my !== prefillToken) return;
                            }
                        }
                    }
                }
            } catch (e) { /* keep whatever was filled so far */ }
            if (my !== prefillToken) return;
            suggestLabel();
            placeFromOption(deepest, zoom);
        }

        // ---- Tabs --------------------------------------------------------------
        function setMode(m) {
            mode = m;
            if ($('kaTabExisting')) $('kaTabExisting').classList.toggle('active', m === 'existing');
            $('kaTabNew').classList.toggle('active', m === 'new');
            $('kaPanelExisting').style.display = m === 'existing' ? '' : 'none';
            $('kaForm').style.display = m === 'new' ? '' : 'none';
            $('kaHintExisting').style.display = m === 'existing' ? '' : 'none';
            $('kaHintNew').style.display = m === 'new' ? '' : 'none';
            $('ka_legend').style.display = m === 'existing' ? '' : 'none';
            if (m === 'existing') {
                prefillToken++;
                removeDraft();
                if (!map.hasLayer(savedLayer)) savedLayer.addTo(map);
                renderPins(true);
            } else {
                // Only the red pin being placed is shown - never the saved ones.
                map.closePopup();
                showPick(null);
                map.removeLayer(savedLayer);
                $('ka_label').value = ''; labelEdited = false;
                sel.p.value = ''; inp.p.value = inp.d.value = inp.t.value = inp.v.value = inp.vn.value = '';
                resetSel(sel.d, '-- เลือกอำเภอ --'); resetSel(sel.t, '-- เลือกตำบล --'); resetSel(sel.v, '-- เลือกชุมชน/หมู่บ้าน --');
                removeDraft();
                prefillOwnLocation();
            }
            setTimeout(() => map.invalidateSize(), 0);
        }
        if ($('kaTabExisting')) $('kaTabExisting').addEventListener('click', () => { if (mode !== 'existing') setMode('existing'); });
        $('kaTabNew').addEventListener('click', () => { if (mode !== 'new') setMode('new'); });
        $('kaGoNew').addEventListener('click', () => setMode('new'));

        if (mode === 'existing') { renderPins(true); $('ka_legend').style.display = ''; }
        else { map.removeLayer(savedLayer); $('ka_legend').style.display = 'none'; prefillOwnLocation(); }
        updateSave();
    })();
    </script>
@endsection
