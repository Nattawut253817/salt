                <tr class="row-pending-area row-pending-{{ $pt['status'] }}">
                    <td class="text-center align-middle"><div class="fy-cell"><span class="fy-year">{{ $pt['fiscal_year'] }}</span></div></td>
                    <td class="align-middle">
                        @if($pt['status'] === 'selected')
                            <div class="org-name" title="{{ implode(', ', $pt['agencies']) }}">{{ implode(', ', $pt['agencies']) }}</div>
                        @elseif($pt['status'] === 'display')
                            <div class="org-name" title="หน่วยงานที่ดำเนินงาน (พิมพ์เอง)">{{ $pt['agency_label'] ?: '-' }}</div>
                        @else
                            <span class="text-muted">&mdash;</span>
                        @endif
                    </td>
                    <td class="align-middle area-name">
                        @if(count($pt['areas'] ?? []) > 1)
                            <span class="area-count" title="ปักพร้อมกัน - รายงานเป็นแบบประเมินเดียว"><i class="fas fa-layer-group"></i> {{ count($pt['areas']) }} พื้นที่</span>
                            <div class="area-grid">
                            @foreach($pt['areas'] as $ar)
                                <div class="area-row">
                                    <div class="area-place">{{ $ar['place_name'] ?: '-' }}</div>
                                    @if(!empty($ar['issue']))<div class="area-issue" title="หน่วยงาน / ประเด็น">หน่วยงาน / ประเด็น: {{ $ar['issue'] }}</div>@endif
                                    @if(!empty($ar['breadcrumb']))<div class="pa-note" style="color:#64748b;font-weight:400;">{{ $ar['breadcrumb'] }}</div>@endif
                                </div>
                            @endforeach
                            </div>
                        @else
                        <span class="area-count" title="รายงานนี้มี 1 พื้นที่"><i class="fas fa-location-dot"></i> 1 พื้นที่</span>
                        <div class="area-grid"><div class="area-row">
                        <div class="area-place">{{ ($pt['place_name'] ?? $pt['display_name']) ?: '-' }}</div>
                        @if(!empty($pt['issue']))<div class="area-issue" title="หน่วยงาน / ประเด็น">หน่วยงาน / ประเด็น: {{ $pt['issue'] }}</div>@endif
                        @if($pt['breadcrumb'])<div class="pa-note" style="color:#64748b;font-weight:400;">{{ $pt['breadcrumb'] }}</div>@endif
                        </div></div>
                        @endif
                        <div class="pa-note">
                            @if($pt['status'] === 'selected') เลือกพื้นที่แล้ว · ยังไม่มีรายงานผล
                            @elseif($pt['status'] === 'display') หมุดแสดงผล · ดำเนินงานโดย {{ $pt['agency_label'] ?: 'หน่วยงานอื่น' }}
                            @else ยังไม่มีผู้เลือก · ยังไม่มีข้อมูล
                            @endif
                        </div>
                    </td>
                    <td class="text-center align-middle text-muted grp-start pa-msg" colspan="14"><small>
                        @if($pt['status'] === 'selected') ยังไม่มีข้อมูล &mdash; รอหน่วยงานที่เลือกรายงานผลการดำเนินงาน
                        @elseif($pt['status'] === 'display') ไม่มีรายงานในระบบ &mdash; แสดงเฉพาะว่าพื้นที่นี้มีการดำเนินงาน พชอ.ไต
                        @else ยังไม่มีข้อมูล &mdash; ยังไม่มีหน่วยงานเลือกพื้นที่นี้
                        @endif
                    </small></td>
                    <td class="text-center align-middle grp-start col-actions">
                        @if($pt['own'])
                            <div class="row-actions">
                                <a href="{{ route('admin.kidney-dhb', ['pin' => $pt['id'], 'fiscal_year' => $pt['fiscal_year']]) }}"
                                    class="btn-action btn-view btn-report trigger-loader" title="รายงานผลการดำเนินงาน" aria-label="รายงานผลการดำเนินงาน">
                                    <i class="fas fa-file-lines"></i><span>รายงานผล</span>
                                </a>
                                @if($pt['status'] === 'selected')
                                <form action="{{ auth()->user()->isSuperAdmin() ? route('admin.operating-areas.release', $pt['id']) : route('admin.kidney-dhb.areas.deselect', $pt['id']) }}" method="POST" style="display:inline;"
                                    data-area="{{ count($pt['areas'] ?? []) > 1 ? collect($pt['areas'])->pluck('place_name')->implode(', ') : ($pt['place_name'] ?? $pt['display_name']) }}" onsubmit="return confirmRemoveArea(event)">
                                    @csrf
                                    <input type="hidden" name="back" value="list">
                                    @if(auth()->user()->isSuperAdmin())<input type="hidden" name="delete_pin" value="1">@endif
                                    @if(count($pt['group_ids'] ?? []) > 1)<input type="hidden" name="also" value="{{ implode(',', $pt['group_ids']) }}">@endif
                                    <button type="submit" class="btn-action btn-delete" title="ลบพื้นที่นี้" aria-label="ลบพื้นที่นี้">
                                        <i class="fas fa-trash-alt"></i>
                                    </button>
                                </form>
                                @endif
                            </div>
                        @elseif(in_array($pt['status'], ['selected', 'free'], true) && auth()->user()->isSuperAdmin())
                            <div class="row-actions">
                                <form action="{{ route('admin.operating-areas.release', $pt['id']) }}" method="POST" style="display:inline;"
                                    data-area="{{ count($pt['areas'] ?? []) > 1 ? collect($pt['areas'])->pluck('place_name')->implode(', ') : ($pt['place_name'] ?? $pt['display_name']) }}" onsubmit="return confirmRemoveArea(event)">
                                    @csrf
                                    <input type="hidden" name="back" value="list">
                                    @if(auth()->user()->isSuperAdmin())<input type="hidden" name="delete_pin" value="1">@endif
                                    @if(count($pt['group_ids'] ?? []) > 1)<input type="hidden" name="also" value="{{ implode(',', $pt['group_ids']) }}">@endif
                                    <button type="submit" class="btn-action btn-delete" title="ลบพื้นที่นี้" aria-label="ลบพื้นที่นี้">
                                        <i class="fas fa-trash-alt"></i>
                                    </button>
                                </form>
                            </div>
                        @else
                            <span class="text-muted">&mdash;</span>
                        @endif
                    </td>
                </tr>
