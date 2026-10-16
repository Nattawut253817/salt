{{-- พื้นที่ดำเนินงาน + ประเด็น ของ 1 หน่วย (หน่วยงาน + พื้นที่หลัก)
     รายงานที่ครอบคลุมหลายพื้นที่แสดงครบทุกพื้นที่ ต้องส่ง $data และ $areaColorIdx --}}
@php $mbAreas = $data['area_list'] ?? []; @endphp
@if(count($mbAreas) > 1)
    <div class="area-multi-badges">
        <div class="area-multi-head"><span class="area-multi-count"><i class="fas fa-layer-group"></i> {{ count($mbAreas) }} พื้นที่</span></div>
        <div class="area-multi-grid">
            @foreach($mbAreas as $ar)
                <div class="area-multi-item">
                    <span class="area-badge area-badge-{{ $areaColorIdx }}" title="{{ $ar['place'] ?: '-' }}">
                        <i class="fas fa-map-marker-alt"></i><span class="area-badge-text">{{ $ar['place'] ?: '-' }}</span>
                    </span>
                    @if(!empty($ar['issue']))
                        <div class="area-issue-line">หน่วยงาน / ประเด็น: {{ $ar['issue'] }}</div>
                    @endif
                </div>
            @endforeach
        </div>
    </div>
@else
    <span class="area-badge area-badge-{{ $areaColorIdx }}">
        <i class="fas fa-map-marker-alt"></i>{{ $data['area_place'] ?? $data['operating_area'] }}
    </span>
    @if(!empty($data['area_issue']))
        <div class="area-issue-line">หน่วยงาน / ประเด็น: {{ $data['area_issue'] }}</div>
    @endif
@endif
