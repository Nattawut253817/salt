@php
    // Month columns in fiscal-year order (Thai FY starts October). Shared by
    // both the per-district rows below and the overview/total row in tfoot.
    $hiMonthColumns = ['m10_oct', 'm11_nov', 'm12_dec', 'm01_jan', 'm02_feb', 'm03_mar', 'm04_apr', 'm05_may', 'm06_jun', 'm07_jul', 'm08_aug', 'm09_sep'];

    // Month-over-month change indicator - reuses the same red(increase) /
    // green(decrease) convention already used for HT case trends elsewhere in
    // this app. Shown selectively: only the single biggest increase and the
    // single biggest decrease within a row get a marker, not every
    // month-to-month wobble.
    $hiChangeFlags = function (array $values) {
        $flags = [];
        $maxIncrease = null;
        $maxIncreaseIdx = null;
        $maxDecrease = null;
        $maxDecreaseIdx = null;
        for ($i = 1; $i < count($values); $i++) {
            if ($values[$i] === null || $values[$i - 1] === null) {
                continue;
            }
            $diff = $values[$i] - $values[$i - 1];
            if ($diff > 0 && ($maxIncrease === null || $diff > $maxIncrease)) {
                $maxIncrease = $diff;
                $maxIncreaseIdx = $i;
            }
            if ($diff < 0 && ($maxDecrease === null || $diff < $maxDecrease)) {
                $maxDecrease = $diff;
                $maxDecreaseIdx = $i;
            }
        }
        if ($maxIncreaseIdx !== null) {
            $flags[$maxIncreaseIdx] = 'up';
        }
        if ($maxDecreaseIdx !== null) {
            $flags[$maxDecreaseIdx] = 'down';
        }
        return $flags;
    };
@endphp
<!-- Province Data Status Cards -->
<div class="stats-frame">
<div class="row px-3" style="margin: 0; width: 100%;">
    @php
        $provinceColors = [
            'อุบลราชธานี' => ['bg' => '#eff6ff', 'icon_bg' => '#3b82f6', 'text' => '#1e40af', 'border' => '#dbeafe'],
            'ศรีสะเกษ' => ['bg' => '#f5f3ff', 'icon_bg' => '#8b5cf6', 'text' => '#5b21b6', 'border' => '#ede9fe'],
            'ยโสธร' => ['bg' => '#fff7ed', 'icon_bg' => '#f97316', 'text' => '#9a3412', 'border' => '#ffedd5'],
            'อำนาจเจริญ' => ['bg' => '#f0fdf4', 'icon_bg' => '#22c55e', 'text' => '#166534', 'border' => '#dcfce7'],
            'มุกดาหาร' => ['bg' => '#fdf2f8', 'icon_bg' => '#ec4899', 'text' => '#9d174d', 'border' => '#fce7f3'],
        ];
    @endphp
    @foreach ($stats['province_counts'] as $pName => $count)
        <div class="col" style="padding: 0 5px; min-width: 170px;">
            <div class="hi-province-card"
                style="border-radius: 14px; padding: 11px 12px; background: {{ $provinceColors[$pName]['bg'] }}; border: 1px solid {{ $provinceColors[$pName]['border'] }}; display: flex; align-items: center; gap: 10px;">
                <div
                    style="width: 28px; height: 28px; border-radius: 8px; background: {{ $provinceColors[$pName]['icon_bg'] }}; display: flex; align-items: center; justify-content: center; flex-shrink: 0;">
                    <i class="fas fa-map-marker-alt" style="color: white; font-size: 12px;"></i>
                </div>
                <div style="overflow: hidden;">
                    <div
                        style="font-size: 0.68rem; color: {{ $provinceColors[$pName]['text'] }}; font-weight: 700; white-space: nowrap; overflow: hidden; text-overflow: ellipsis;">
                        {{ $pName }}
                    </div>
                    <div style="font-size: 0.98rem; font-weight: 800; color: #1e293b;">
                        {{ number_format($count) }} <span style="font-size: 0.62rem; font-weight: 600;">ข้อมูล</span>
                    </div>
                </div>
            </div>
        </div>
    @endforeach
</div>
</div>

<div class="table-container">
    <div class="hi-table-title-bar">
        <h6 class="mb-0 font-weight-bold text-primary" style="display: flex; align-items: center; gap: 10px;">
            <span
                style="width: 32px; height: 32px; border-radius: 10px; background: var(--primary-indigo, #4f46e5); color: #fff; display: flex; align-items: center; justify-content: center; font-size: 0.85rem; flex-shrink: 0;">
                <i class="fas fa-table"></i>
            </span>
            ตารางข้อมูล HI
        </h6>
        <span id="total-badge" class="badge badge-info shadow-sm px-3 py-2"
            style="font-size: 0.75rem; border-radius: 20px;">
            ทั้งหมด {{ number_format($hiData->total()) }} รายการ
        </span>
    </div>

    <input type="hidden" id="hi-total-count" value="{{ $hiData->total() }}">
    @if($hiData->count() > 0)
        <div class="hi-legend d-flex align-items-center flex-wrap" style="gap: 18px; padding: 4px 20px 14px; font-size: 0.72rem; color: #64748b;">
            <div class="d-flex align-items-center" style="gap: 5px;">
                <i class="fas fa-caret-up" style="color:#e74c3c;"></i> เดือนที่เพิ่มขึ้นสูงสุดในแถว
            </div>
            <div class="d-flex align-items-center" style="gap: 5px;">
                <i class="fas fa-caret-down" style="color:#27ae60;"></i> เดือนที่ลดลงสูงสุดในแถว
            </div>
        </div>
    @endif
    <div class="table-responsive">
    <table class="table table-hover mb-0" style="font-size: 0.85rem;">
        <thead style="background-color: #f8fafc; border-top: 1px solid #e2e8f0;">
            <tr class="text-center text-nowrap">
                <th rowspan="2" style="vertical-align: middle;">ปีงบ</th>
                <th rowspan="2" style="vertical-align: middle;">จังหวัด</th>
                <th rowspan="2" class="text-left" style="vertical-align: middle;">อำเภอ</th>
                <th rowspan="2" style="vertical-align: middle;">ค่า B</th>
                <th rowspan="2" style="vertical-align: middle;">ค่า A</th>
                <th rowspan="2" style="vertical-align: middle;">อัตราต่อแสน</th>
                <th colspan="12" class="th-month-group">ข้อมูลรายเดือน</th>
            </tr>
            <tr class="text-center text-nowrap">
                <th class="th-month-first">ต.ค.</th>
                <th>พ.ย.</th>
                <th>ธ.ค.</th>
                <th>ม.ค.</th>
                <th>ก.พ.</th>
                <th>มี.ค.</th>
                <th>เม.ย.</th>
                <th>พ.ค.</th>
                <th>มิ.ย.</th>
                <th>ก.ค.</th>
                <th>ส.ค.</th>
                <th>ก.ย.</th>
            </tr>
        </thead>
        <tbody>
            @forelse($hiData as $hi)
                <tr class="text-center">
                    <td class="font-weight-bold text-primary">{{ $hi->year }}</td>
                    <td>{{ $hi->province->province_name ?? '-' }}</td>
                    <td class="text-left">{{ $hi->District_name }}</td>
                    <td>{{ number_format($hi->target_b) }}</td>
                    <td>{{ number_format($hi->total_a) }}</td>
                    <td class="font-weight-bold" style="color: #c2185b;">{{ number_format($hi->rate_per_100k, 2) }}</td>
                    @php
                        $rowMonthValues = array_map(fn ($c) => $hi->{$c}, $hiMonthColumns);
                        $rowFlags = $hiChangeFlags($rowMonthValues);
                    @endphp
                    @foreach($hiMonthColumns as $idx => $col)
                        <td style="{{ $idx === 0 ? 'border-left: 2px solid #e2e8f0;' : '' }}">
                            {{ number_format($rowMonthValues[$idx]) }}
                            @if(($rowFlags[$idx] ?? null) === 'up')
                                <i class="fas fa-caret-up" style="color: #e74c3c; margin-left: 2px;" title="เดือนที่เพิ่มขึ้นสูงสุดในแถวนี้"></i>
                            @elseif(($rowFlags[$idx] ?? null) === 'down')
                                <i class="fas fa-caret-down" style="color: #27ae60; margin-left: 2px;" title="เดือนที่ลดลงสูงสุดในแถวนี้"></i>
                            @endif
                        </td>
                    @endforeach
                </tr>
            @empty
                <tr>
                    <td colspan="18" class="text-center py-5 text-muted italic">
                        <i class="fas fa-inbox fa-3x mb-3 opacity-25"></i><br>
                        ไม่พบข้อมูลในขณะนี้
                    </td>
                </tr>
            @endforelse
        </tbody>
        @if($hiData->count() > 0 && isset($stats['monthly_totals']))
            @php
                $overviewValues = array_map(fn ($c) => $stats['monthly_totals']->{$c} !== null ? (float) $stats['monthly_totals']->{$c} : null, $hiMonthColumns);
                $overviewFlags = $hiChangeFlags($overviewValues);
            @endphp
            <tfoot>
                <tr class="text-center hi-overview-row" style="background-color: #f8fafc; border-top: 2px solid #c7d2fe;">
                    <td colspan="6" class="text-left font-weight-bold" style="color: #4338ca;">
                        <i class="fas fa-layer-group" style="margin-right: 6px;"></i>ภาพรวม (ผลรวมตามตัวกรองปัจจุบัน)
                    </td>
                    @foreach($hiMonthColumns as $idx => $col)
                        <td class="font-weight-bold" style="{{ $idx === 0 ? 'border-left: 2px solid #e2e8f0;' : '' }}">
                            {{ number_format($overviewValues[$idx] ?? 0) }}
                            @if(($overviewFlags[$idx] ?? null) === 'up')
                                <i class="fas fa-caret-up" style="color: #e74c3c; margin-left: 2px;" title="เดือนที่เพิ่มขึ้นสูงสุด"></i>
                            @elseif(($overviewFlags[$idx] ?? null) === 'down')
                                <i class="fas fa-caret-down" style="color: #27ae60; margin-left: 2px;" title="เดือนที่ลดลงสูงสุด"></i>
                            @endif
                        </td>
                    @endforeach
                </tr>
            </tfoot>
        @endif
    </table>
</div>

    <div class="d-flex justify-content-center mt-3 p-3">
        {{ $hiData->appends(request()->except('page'))->links('pagination::bootstrap-4') }}
    </div>
</div>