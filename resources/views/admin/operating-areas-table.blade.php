{{--
    AJAX-swapped partial for admin.operating-areas: the "พื้นที่ดำเนินงานที่บันทึกแล้ว"
    table, its pagination (15/page), and the "แสดง X–Y จาก Z รายการ" footer.
    Re-rendered in place (#oa-table-container, see operating-areas.blade.php)
    whenever the province filter changes or a pagination link is clicked -
    mirrors admin.kidney-dhb-table's own filter/pagination convention. The
    map above (built from $mapPins, not $pins) is never affected by this -
    see AdminController::operatingAreasIndex()'s own docblock on why.
--}}
{{-- Carries the current (filtered) total out to #oa_count_badge, which
     lives outside this swapped partial (same row as the title/filter in
     operating-areas.blade.php) - see this file's own extra_js. --}}
@php
    $isSuperAdmin = $isSuperAdmin ?? false;
    $pinSelectors = $pinSelectors ?? [];
    $pinReported = $pinReported ?? [];
@endphp
<span id="oa-count-carrier" data-total="{{ $pins->total() }}" hidden></span>

<div class="oa-table-wrap">
    <div class="table-responsive">
        <table class="oa-pins-table">
        <caption class="sr-only">รายการพื้นที่ดำเนินงานที่บันทึกไว้ พร้อมพิกัด หมายเหตุ และผู้บันทึก</caption>
        <thead>
            <tr>
                <th scope="col">ปีงบประมาณ</th>
                <th scope="col">ที่ตั้ง</th>
                <th scope="col">พิกัด</th>
                <th scope="col">หมายเหตุ</th>
                <th scope="col">บันทึกโดย</th>
                @if ($isSuperAdmin)
                    <th scope="col">หน่วยงานที่เลือก</th>
                @endif
                <th scope="col" class="oa-col-actions {{ $isSuperAdmin ? 'oa-col-actions-wide' : '' }}"><span class="sr-only">จัดการ</span></th>
            </tr>
        </thead>
        <tbody>
            @forelse ($pins as $pin)
                <tr>
                    <td class="oa-fiscal-year">{{ $pin->fiscal_year ?? '-' }}</td>
                    <td>
                        <div class="oa-location">
                            <svg class="oa-location-pin" width="12" height="16" viewBox="0 0 34 46" aria-hidden="true" focusable="false">
                                <path d="M17 2C9.8 2 4 7.8 4 15c0 10.2 13 27 13 27s13-16.8 13-27C30 7.8 24.2 2 17 2z"
                                    fill="#ef4444" stroke="#b91c1c" stroke-width="2" stroke-linejoin="round" />
                                <circle cx="17" cy="15" r="6" fill="#fff" />
                            </svg>
                            <div class="oa-location-text">
                                <div class="oa-location-path">
                                    <span>{{ $pin->province->province_name ?? '-' }}</span>
                                    @if ($pin->district)
                                        <span class="oa-sep">&rsaquo;</span><span>{{ $pin->district->district_name }}</span>
                                    @endif
                                    @if ($pin->tambon)
                                        <span class="oa-sep">&rsaquo;</span><span>{{ $pin->tambon->tambon_name }}</span>
                                    @endif
                                    @if ($pin->village_name_snapshot || $pin->village)
                                        <span class="oa-sep">&rsaquo;</span><span>{{ $pin->village_name_snapshot ?? $pin->village->village_name }}</span>
                                    @endif
                                </div>
                                <div class="oa-level-chip">
                                    @if ($pin->village_name_snapshot || $pin->village)
                                        <i class="fas fa-house"></i> ครบถึงชุมชน/หมู่บ้าน
                                    @elseif ($pin->tambon)
                                        <i class="fas fa-signs-post"></i> ระดับตำบล
                                    @else
                                        <i class="fas fa-map"></i> ระดับอำเภอ
                                    @endif
                                </div>
                            </div>
                        </div>
                    </td>
                    <td class="oa-coords">
                        <span>{{ number_format($pin->latitude, 6) }}</span>
                        <span>{{ number_format($pin->longitude, 6) }}</span>
                    </td>
                    <td class="oa-note">{{ $pin->label ?? '–' }}</td>
                    <td class="oa-meta">
                        <div class="oa-meta-name">{{ $pin->creator->name ?? '-' }}</div>
                        <div class="oa-meta-date">{{ $pin->created_at->format('d/m/Y H:i') }}</div>
                    </td>
                    @if ($isSuperAdmin)
                        @php
                            $oaSelectors = $pinSelectors[$pin->id] ?? [];
                            $oaReported = isset($pinReported[$pin->id]);
                        @endphp
                        <td class="oa-selectors">
                            @if (!empty($pin->display_only))
                                <span class="oa-sel-chip oa-sel-display"><i class="fas fa-building-flag"></i> {{ $pin->agency_label ?: '-' }}</span>
                                <span class="oa-sel-chip oa-sel-reported" title="หมุดแสดงผลอย่างเดียว ไม่ผูกกับบัญชีหน่วยงานและไม่เข้าแบบรายงาน"><i class="fas fa-eye"></i> หมุดแสดงผล</span>
                            @else
                            @forelse ($oaSelectors as $oaAgency)
                                <span class="oa-sel-chip"><i class="fas fa-circle-check"></i> {{ $oaAgency }}</span>
                            @empty
                                <span class="oa-sel-none">ยังไม่มีผู้เลือก</span>
                            @endforelse
                            @if ($oaReported)
                                <span class="oa-sel-chip oa-sel-reported"><i class="fas fa-file-circle-check"></i> มีรายงานแล้ว</span>
                            @endif
                            @endif
                        </td>
                    @endif
                    <td class="oa-col-actions {{ $isSuperAdmin ? 'oa-col-actions-wide' : '' }}">
                        @if ($isSuperAdmin)
                            <button type="button" class="btn-oa-edit"
                                data-url="{{ route('admin.operating-areas.update', $pin->id) }}"
                                data-fiscal-year="{{ $pin->fiscal_year }}"
                                data-label="{{ $pin->label }}"
                                data-lat="{{ $pin->latitude }}"
                                data-lng="{{ $pin->longitude }}"
                                data-display="{{ !empty($pin->display_only) ? 1 : 0 }}"
                                data-agency="{{ $pin->agency_label }}"
                                data-path="{{ collect([optional($pin->province)->province_name, optional($pin->district)->district_name, optional($pin->tambon)->tambon_name, $pin->village_name_snapshot ?? optional($pin->village)->village_name])->filter()->implode(' / ') }}"
                                title="แก้ไขพื้นที่ดำเนินงานนี้" aria-label="แก้ไขพื้นที่ดำเนินงานนี้">
                                <i class="fas fa-pen"></i> แก้ไข
                            </button>
                        @endif
                        @if ($isSuperAdmin && !empty($oaSelectors) && !$oaReported)
                            <button type="button" class="btn-oa-release"
                                data-url="{{ route('admin.operating-areas.release', $pin->id) }}"
                                data-name="{{ $pin->label ?: ($pin->village_name_snapshot ?? optional($pin->village)->village_name ?? optional($pin->tambon)->tambon_name ?? optional($pin->district)->district_name ?? optional($pin->province)->province_name) }}"
                                data-agencies="{{ implode(', ', $oaSelectors) }}"
                                title="ยกเลิกการเลือกพื้นที่นี้ของหน่วยงาน และคืนพื้นที่ให้ว่าง">
                                <i class="fas fa-circle-minus"></i> ยกเลิกพื้นที่นี้
                            </button>
                        @endif
                        <form action="{{ route('admin.operating-areas.destroy', $pin->id) }}" method="POST"
                            class="oa-delete-form" onsubmit="return confirmDeleteOperatingArea(event)">
                            @csrf
                            @method('DELETE')
                            <button type="submit" class="btn-icon-danger" title="ลบ" aria-label="ลบพื้นที่ดำเนินงานนี้">
                                <i class="fas fa-trash-can"></i>
                            </button>
                        </form>
                    </td>
                </tr>
            @empty
                <tr class="oa-empty-row">
                    <td colspan="{{ $isSuperAdmin ? 7 : 6 }}">
                        <div class="oa-empty-state">
                            <i class="fas fa-map-location-dot"></i>
                            @if (request('filter_fiscal_year') || request('filter_province_id'))
                                <p>ไม่พบพื้นที่ดำเนินงานตามตัวกรองที่เลือก</p>
                                <span>ลองเลือก "ทั้งหมด" ในตัวกรองปีงบประมาณ/จังหวัด หรือเลือกค่าอื่น</span>
                            @else
                                <p>ยังไม่มีพื้นที่ดำเนินงานที่บันทึกไว้</p>
                                <span>เลือกพื้นที่และปักหน่วยงานในส่วนด้านบนเพื่อเริ่มบันทึกรายการแรก</span>
                            @endif
                        </div>
                    </td>
                </tr>
            @endforelse
        </tbody>
        </table>
    </div>
</div>

<div class="oa-table-footer">
    <div class="oa-footer-hint">
        @if ($pins->total() > 0)
            แสดง {{ $pins->firstItem() }}–{{ $pins->lastItem() }} จาก {{ number_format($pins->total()) }} รายการ
        @endif
    </div>
    <div id="oa-pagination-links">
        {{ $pins->links('pagination::bootstrap-4') }}
    </div>
</div>
