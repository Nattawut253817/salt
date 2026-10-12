{{-- รูปภาพประกอบของแต่ละข้อ (คอลัมน์ "ภาพกิจกรรม" ในหน้า print/PDF): $images[cat] = collection ของ KidneyAssessmentImage (สูงสุด 3) --}}
@php $kimgList = $images[$cat] ?? []; @endphp
@if(count($kimgList))
    <div class="kimg-print-col kimg-n{{ count($kimgList) }}">
        @foreach($kimgList as $kimg)
            <figure class="kimg-print-item">
                <img src="{{ $kimg->url }}" alt="ภาพกิจกรรม">
            </figure>
        @endforeach
    </div>
@else
    <span class="kimg-print-empty">-</span>
@endif
