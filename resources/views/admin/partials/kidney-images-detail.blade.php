{{-- รูปภาพประกอบของข้อหนึ่ง ในหน้ารายละเอียด (อ่านอย่างเดียว) - คลิกเพื่อดูภาพเต็ม.
     $kimgs[cat] = collection ของ KidneyAssessmentImage ; $cat = '1'..'7' | '8_1' ... --}}
@php $kimgList = $kimgs[$cat] ?? []; @endphp
@if(count($kimgList))
    <div class="kdimg-grid">
        @foreach($kimgList as $kimg)
            <a href="{{ $kimg->url }}" class="kdimg-item" target="_blank" rel="noopener" data-kdimg>
                <img src="{{ $kimg->url }}" alt="รูปภาพประกอบ" loading="lazy">
            </a>
        @endforeach
    </div>
@endif

@once
<style>
    .kdimg-grid { display: grid; grid-template-columns: repeat(3, minmax(0, 1fr)); gap: 10px; margin: 10px 0 4px; }
    .kdimg-item {
        display: block; aspect-ratio: 4 / 3; border-radius: 12px; overflow: hidden;
        background: #0f172a; border: 1px solid #e2e8f0; cursor: zoom-in;
    }
    .kdimg-item img { width: 100%; height: 100%; object-fit: contain; display: block; transition: transform .2s; }
    .kdimg-item:hover img { transform: scale(1.03); }
    @media (max-width: 520px) { .kdimg-grid { gap: 6px; } }
</style>
@endonce
