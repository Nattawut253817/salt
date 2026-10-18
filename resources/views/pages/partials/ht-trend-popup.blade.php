{{-- popup "สถานการณ์แนวโน้ม" ของ HT: ใช้ร่วมกันที่หน้าแรก (#sec-ht) และ /new-ht-cases
     รับ $htTrend (ข้อมูลจาก MainController::buildHtTrendPop), $htTrendColors (สีรายจังหวัด), $htTrendScope (ชื่อขอบเขต)
     ปุ่มเปิดต้องมี id="hmTrendOpen" และ class="hm-trend-btn" --}}
<style>
        /* ===== ปุ่ม + popup "สถานการณ์แนวโน้ม" (การ์ด HT) ===== */
        .hm-trend-btn {
            margin-left: auto; display: inline-flex; align-items: center; gap: 7px;
            font: inherit; font-size: .8rem; font-weight: 800; line-height: 1.5; color: #fff; cursor: pointer;
            background: var(--a, #0d9488); border: 1.5px solid var(--a, #0d9488); border-radius: 999px; padding: 3px 14px 3px 11px;
            box-shadow: 0 6px 14px -8px var(--a, #0d9488); transition: transform .15s ease, box-shadow .15s ease;
        }
        .hm-trend-btn:hover { transform: translateY(-1px); box-shadow: 0 9px 18px -8px var(--a, #0d9488); }
        .hm-trend-btn:focus-visible { outline: 3px solid color-mix(in srgb, var(--a, #0d9488) 40%, #fff); outline-offset: 2px; }
        .hm-trend-btn + .hm-more { margin-left: 0; }
        @media (max-width: 640px) { .hm-trend-btn { margin-left: 0; } }
        .hm-tr-modal {
            position: fixed; inset: 0; z-index: 2000; display: flex; align-items: center; justify-content: center;
            padding: 20px; background: rgba(15, 23, 42, .55); backdrop-filter: blur(3px); animation: hmTrFade .18s ease-out;
        }
        .hm-tr-modal[hidden] { display: none; }
        .hm-tr-sheet {
            --a: #0d9488; width: min(1100px, 100%); max-height: calc(100vh - 40px); overflow: auto; outline: none;
            background: #fff; border-radius: 20px; box-shadow: 0 30px 80px -20px rgba(15, 23, 42, .5);
            font-family: 'Sarabun', sans-serif; animation: hmTrPop .22s cubic-bezier(.2, .8, .2, 1);
        }
        @keyframes hmTrFade { from { opacity: 0; } to { opacity: 1; } }
        @keyframes hmTrPop { from { opacity: 0; transform: translateY(14px) scale(.985); } to { opacity: 1; transform: none; } }
        @media (prefers-reduced-motion: reduce) { .hm-tr-modal, .hm-tr-sheet { animation: none; } }
        .hm-tr-head {
            position: sticky; top: 0; z-index: 3; display: flex; align-items: center; gap: 12px; flex-wrap: wrap;
            padding: 16px 20px 12px 22px; background: linear-gradient(180deg, #f0fdfa, #fff); border-bottom: 1px solid #e8edf3;
        }
        .hm-tr-ttl { flex: 1 1 260px; min-width: 0; }
        .hm-tr-ttl h4 { margin: 0; font-size: 1.15rem; font-weight: 800; color: #0f172a; }
        .hm-tr-ttl p { margin: 2px 0 0; font-size: .8rem; color: #64748b; }
        .hm-tr-chip { display: inline-flex; align-items: center; gap: 6px; font-size: .82rem; font-weight: 800; border-radius: 999px; padding: 4px 12px; white-space: nowrap; }
        .hm-tr-chip.up { color: #b91c1c; background: #fee2e2; }
        .hm-tr-chip.down { color: #15803d; background: #dcfce7; }
        .hm-tr-chip.flat { color: #475569; background: #f1f5f9; }
        .hm-tr-chip.info { color: #0f766e; background: #ccfbf1; }
        .hm-tr-x {
            flex: none; width: 36px; height: 36px; border: 0; border-radius: 50%; background: #f1f5f9; color: #475569;
            font-size: 1.3rem; line-height: 1; cursor: pointer; transition: background .15s;
        }
        .hm-tr-x:hover { background: #e2e8f0; }
        .hm-tr-x:focus-visible, .hm-tr-tab:focus-visible, .hm-tr-seg button:focus-visible { outline: 3px solid #5eead4; outline-offset: 2px; }
        .hm-tr-body { display: grid; grid-template-columns: minmax(0, 1.5fr) minmax(290px, 1fr); gap: 20px; padding: 16px 22px 22px; }
        .hm-tr-ctl { display: flex; align-items: center; gap: 10px; flex-wrap: wrap; margin-bottom: 8px; }
        .hm-tr-tabs, .hm-tr-seg { display: inline-flex; background: #f1f5f9; border-radius: 999px; padding: 3px; gap: 2px; }
        .hm-tr-tab, .hm-tr-seg button {
            border: 0; background: transparent; font: inherit; font-size: .82rem; font-weight: 700; color: #64748b;
            border-radius: 999px; padding: 5px 15px; cursor: pointer; transition: background .15s, color .15s;
        }
        .hm-tr-tab[aria-selected="true"], .hm-tr-seg button[aria-pressed="true"] { background: #fff; color: #0f766e; box-shadow: 0 1px 4px rgba(15, 23, 42, .14); }
        .hm-tr-seg { margin-left: auto; }
        .hm-tr-seg[hidden] { display: none; }
        .hm-tr-chart { display: flex; flex-direction: column; min-width: 0; }
        .hm-tr-plot { width: 100%; flex: 1 1 420px; min-height: 0; }
        .hm-tr-plot[hidden] { display: none; }
        .ht-ref-blur { filter: blur(1.5px); }
        .ht-ref-knob { filter: drop-shadow(0 1px 1.5px rgba(127, 29, 29, .55)); }
        .ht-ref-chip { filter: drop-shadow(0 2px 3px rgba(127, 29, 29, .38)); }
        .hm-tr-foot { margin: 6px 2px 0; font-size: .75rem; color: #64748b; }
        .hm-tr-live { margin: 3px 2px 0; display: flex; align-items: center; gap: 6px; font-size: .72rem; color: #94a3b8; }
        .hm-tr-live::before { content: ''; flex: none; width: 6px; height: 6px; border-radius: 50%; background: #10b981; }
        .hm-tr-live.off::before { background: #cbd5e1; }
        .hm-tr-live.fresh { color: #0f766e; }
        .hm-tr-list.swap .hm-tr-in { animation: hmTrIn .28s ease both; }
        .hm-tr-in.pin { border-color: var(--tc); box-shadow: 0 0 0 2px var(--tb); }
        @keyframes hmTrIn { from { opacity: .35; transform: translateY(3px); } to { opacity: 1; transform: none; } }
        @media (prefers-reduced-motion: reduce) { .hm-tr-list.swap .hm-tr-in { animation: none; } }
        .hm-tr-ins { align-self: stretch; display: flex; flex-direction: column; background: #f8fafc; border: 1px solid #e8edf3; border-radius: 16px; padding: 16px; }
        .hm-tr-ins h5 { margin: 0 0 12px; font-size: 1rem; font-weight: 800; color: #0f172a; }
        .hm-tr-list { flex: 1; display: flex; flex-direction: column; gap: 10px; list-style: none; margin: 0; padding: 0; }
        .hm-tr-in { --tc: #0d9488; --tb: #ccfbf1; --tf: #0f766e; position: relative; flex: 1 0 auto; display: flex; gap: 12px; align-items: center; overflow: hidden;
            background: #fff; border: 1px solid #e8edf3; border-radius: 12px; padding: 11px 14px; box-shadow: 0 1px 2px rgba(15, 23, 42, .04); }
        .hm-tr-in.up { --tc: #dc2626; --tb: #fee2e2; --tf: #b91c1c; }
        .hm-tr-in.down { --tc: #16a34a; --tb: #dcfce7; --tf: #15803d; }
        .hm-tr-in.flat { --tc: #94a3b8; --tb: #f1f5f9; --tf: #475569; }
        .hm-tr-ic { flex: none; width: 28px; height: 28px; border-radius: 50%; display: grid; place-items: center; font-size: .7rem; font-style: normal; font-weight: 800; line-height: 1; background: var(--tb); color: var(--tf); }
        .hm-tr-ti { display: block; font-size: .95rem; font-weight: 800; color: #0f172a; margin-bottom: 3px; }
        .hm-tr-tx { display: block; font-size: .84rem; line-height: 1.7; color: #64748b; }
        .hm-tr-tx strong { font-weight: 800; font-size: .95rem; color: #0f172a; font-variant-numeric: tabular-nums; padding: 0 3px; margin: 0 -1px; border-radius: 3px;
            background: linear-gradient(transparent 52%, color-mix(in srgb, var(--tc) 26%, transparent) 52%); }
        .hm-tr-tx .yr { font-weight: 800; color: #0f172a; }
        .hm-tr-tx .vb { font-weight: 800; font-size: .9rem; }
        .hm-tr-tx .pc { display: inline-block; font-size: .82rem; font-weight: 800; line-height: 1.35; padding: 0 8px; border-radius: 999px; white-space: nowrap; font-variant-numeric: tabular-nums; }
        .hm-tr-tx .up { color: #b91c1c; } .hm-tr-tx .down { color: #15803d; } .hm-tr-tx .flat { color: #475569; }
        .hm-tr-tx .pc.up { background: #fee2e2; } .hm-tr-tx .pc.down { background: #dcfce7; } .hm-tr-tx .pc.flat { background: #eef2f6; }
        @media (max-width: 860px) {
            .hm-tr-modal { padding: 0; align-items: flex-end; }
            .hm-tr-sheet { max-height: 94vh; border-radius: 20px 20px 0 0; }
            .hm-tr-body { grid-template-columns: 1fr; padding: 14px 16px 20px; }
            .hm-tr-plot { flex: none; height: 290px; }
            .hm-tr-in { flex: none; }
            .hm-tr-seg { margin-left: 0; }
        }
        /* ปุ่ม + ป้ายอัปเดต ท้ายแถวตัวกรองของ /new-ht-cases */
        .awr-filter-actions {
            flex: 0 0 auto; display: flex; align-items: center; gap: 8px; margin-left: 8px; padding-left: 14px;
            border-left: 1px solid #e2e8f0;
        }
        .awr-filter-bar .awr-filter-item:has(+ .awr-filter-clear) { border-right: none; }
        .awr-filter-actions .hm-trend-btn { margin-left: 0; padding: 0 16px 0 13px; height: 38px; font-size: .84rem; white-space: nowrap; font-family: 'Sarabun', sans-serif; }
        .ht-upd-chip {
            display: inline-flex; align-items: center; gap: 7px; height: 38px; padding: 0 14px 0 11px; border-radius: 999px; white-space: nowrap;
            background: color-mix(in srgb, #0d9488 10%, #fff); border: 1.5px solid color-mix(in srgb, #0d9488 34%, #fff);
            color: #0d9488; font-size: .8rem; line-height: 1.2; font-family: 'Sarabun', sans-serif;
        }
        .ht-upd-chip .l { font-weight: 600; color: #475569; }
        .ht-upd-chip b { font-weight: 800; }
        @media (max-width: 1000px) {
            .awr-filter-actions { flex: 1 1 100%; margin: 6px 0 0; padding: 10px 0 0; border-left: none; border-top: 1px solid #e2e8f0; flex-wrap: wrap; justify-content: flex-end; }
        }
        @media (max-width: 640px) {
            .awr-filter-actions { justify-content: flex-start; }
            .ht-upd-chip { white-space: normal; height: auto; min-height: 38px; padding-top: 4px; padding-bottom: 4px; }
        }
        /* ===== โหมดฝังในหน้า (การ์ดแทนที่ popup) : ส่ง $htTrendInline = true ===== */
        .hm-card.hm-tr-inline { padding: 0; overflow: hidden; }
        .hm-tr-inline .hm-tr-sheet { width: auto; max-height: none; overflow: visible; background: transparent; box-shadow: none; border-radius: 0; animation: none; }
        .hm-tr-inline .hm-tr-head { position: static; border-radius: 16px 16px 0 0; padding: 10px 20px 9px 22px; }
        .hm-tr-inline .hm-tr-ttl h4 { margin: 0; font-size: 1.15rem; font-weight: 800; color: #0f172a; }
        .hm-tr-inline .hm-tr-body { padding: 12px 20px 14px; gap: 16px; }
        .hm-tr-inline .hm-tr-plot { flex: 1 1 300px; }
        .hm-tr-inline .hm-tr-ins { padding: 12px; }
        .hm-tr-inline .hm-tr-ins h5 { margin-bottom: 8px; }
        .hm-tr-inline .hm-tr-list { gap: 7px; }
        .hm-tr-inline .hm-tr-in { padding: 7px 12px; gap: 10px; }
        .hm-tr-inline .hm-tr-ic { width: 24px; height: 24px; }
        .hm-tr-inline .hm-tr-ti { margin-bottom: 1px; }
        .hm-tr-inline .hm-tr-tx { line-height: 1.55; }
        .hm-tr-inline .hm-tr-foot { margin-top: 2px; }
        .hm-tr-inline .hm-tr-ctl { display: none; } /* โหมดฝัง: แสดงเฉพาะรายเดือน ไม่มีแท็บรายปี/ปุ่มตัวชี้วัด */
        @media (max-width: 860px) { .hm-tr-inline .hm-tr-body { padding: 12px 14px 16px; } }
</style>
@php $htTrInline = !empty($htTrendInline); @endphp
                <div class="{{ $htTrInline ? 'hm-card hm-tr-inline' : 'hm-tr-modal' }}" id="hmTrendModal" data-feed="{{ route('api.ht-trend') }}" data-province="{{ $htTrendProvince ?? 'all' }}" @if (!$htTrInline) role="dialog" aria-modal="true" aria-labelledby="hmTrendTitle" hidden @endif>
                    <div class="hm-tr-sheet" @if (!$htTrInline) tabindex="-1" @endif>
                        <div class="hm-tr-head">
                            <div class="hm-tr-ttl">
                                <h4 id="hmTrendTitle">สถานการณ์แนวโน้มอัตราป่วยรายใหม่ HT</h4>
                                <p id="hmTrSub" data-scope="{{ $htTrendScope }}">ข้อมูลปีงบประมาณ {{ $htTrend['years'][0] }}{{ count($htTrend['years']) > 1 ? '–' . end($htTrend['years']) : '' }} · {{ $htTrendScope }}</p>
                            </div>
                            {{-- ป้าย "แนวโน้ม…%" ถูกเอาออก: ไม่ระบุปีที่ใช้เทียบ ทำให้ตีความตัวเลขไม่ได้ (ข้อมูลยังคำนวณใน summary เผื่อนำกลับมาใช้) --}}
                            @if (!$htTrInline)
                                <button type="button" class="hm-tr-x" id="hmTrendClose" aria-label="ปิดหน้าต่าง">&times;</button>
                            @endif
                        </div>
                        <div class="hm-tr-body">
                            <div class="hm-tr-chart">
                                <div class="hm-tr-ctl">
                                    <div class="hm-tr-tabs" role="tablist" aria-label="มุมมองแนวโน้ม">
                                        <button type="button" class="hm-tr-tab" role="tab" id="hmTrTabYear" data-view="year" aria-selected="true">รายปี</button>
                                        <button type="button" class="hm-tr-tab" role="tab" id="hmTrTabMonth" data-view="month" aria-selected="false">รายเดือน</button>
                                    </div>
                                    <div class="hm-tr-seg" id="hmTrSeg" role="group" aria-label="ตัวชี้วัด">
                                        <button type="button" data-metric="rate" aria-pressed="true">อัตราต่อแสนประชากร</button>
                                        <button type="button" data-metric="cases" aria-pressed="false">จำนวนผู้ป่วยใหม่</button>
                                    </div>
                                </div>
                                <div id="hmTrendYear" class="hm-tr-plot" role="img" aria-label="กราฟแนวโน้มอัตราป่วยรายใหม่ HT รายปี"></div>
                                <div id="hmTrendMonth" class="hm-tr-plot" role="img" aria-label="กราฟผู้ป่วยใหม่ HT รายเดือน เทียบปีล่าสุดกับปีก่อน" hidden></div>
                                <p class="hm-tr-foot" id="hmTrFoot"></p>
                                <p class="hm-tr-live" id="hmTrLive" role="status" aria-live="polite" hidden></p>
                            </div>
                            <aside class="hm-tr-ins" aria-label="การวิเคราะห์">
                                <h5>การวิเคราะห์จากข้อมูลจริง</h5>
                                @php
                                    $hmEm = function ($raw) {
                                        $t = e((string) $raw);
                                        $re = '/(ปี(?:งบ)?\s\d{4}|(?<=→)\d{4})|(\(?[+\-]\d[\d,]*(?:\.\d+)?%\)?)|(เพิ่มขึ้น|ลดลง|ใกล้เคียงเดิม)|(\d{1,3}(?:,\d{3})+(?:\.\d+)?|\d+\.\d+|\d+(?=\s?(?:คน|ต่อแสน|จังหวัด)))|((?<=เดือน)(?:ม\.ค\.|ก\.พ\.|มี\.ค\.|เม\.ย\.|พ\.ค\.|มิ\.ย\.|ก\.ค\.|ส\.ค\.|ก\.ย\.|ต\.ค\.|พ\.ย\.|ธ\.ค\.)|(?<=คือ)[ก-๙]+(?=\s[+\-]\d))/u';
                                        return preg_replace_callback($re, function ($m) {
                                            if (($m[1] ?? '') !== '') { return '<span class="yr">' . $m[1] . '</span>'; }
                                            if (($m[2] ?? '') !== '') { $g = trim($m[2], '()'); $v = (float) str_replace(',', '', $g); return '<span class="pc ' . (abs($v) < 2 ? 'flat' : ($v > 0 ? 'up' : 'down')) . '">' . ltrim($g, '-') . '</span>'; }
                                            if (($m[3] ?? '') !== '') { return '<span class="vb ' . ($m[3] === 'เพิ่มขึ้น' ? 'up' : ($m[3] === 'ลดลง' ? 'down' : 'flat')) . '">' . $m[3] . '</span>'; }
                                            return '<strong>' . $m[0] . '</strong>';
                                        }, $t);
                                    };
                                @endphp
                                <ul class="hm-tr-list" id="hmTrList" aria-live="polite">
                                    @foreach ($htTrend['insights'] as $in)
                                        <li class="hm-tr-in {{ $in['tone'] }}">
                                            <span class="hm-tr-ic" aria-hidden="true">{{ ['up' => '▲', 'down' => '▼', 'flat' => '●', 'info' => 'i'][$in['tone']] }}</span>
                                            <div><span class="hm-tr-ti">{{ $in['title'] }}</span><span class="hm-tr-tx">{!! $hmEm($in['text']) !!}</span></div>
                                        </li>
                                    @endforeach
                                </ul>
                            </aside>
                        </div>
                    </div>
                </div>
        <script>
            // popup "สถานการณ์แนวโน้ม" ของการ์ด HT: สร้างกราฟครั้งแรกตอนเปิด (ต้องรู้ความกว้างจริงก่อน)
            document.addEventListener('DOMContentLoaded', function () {
                let D = @json($htTrend);
                const PC = @json($htTrendColors);
                const INL = @json($htTrInline); // true = ฝังเป็นการ์ดในหน้า (ไม่มีปุ่มเปิด/ปิด)
                const modal = document.getElementById('hmTrendModal');
                const openBtn = document.getElementById('hmTrendOpen');
                if (!D || !modal || (!INL && !openBtn) || typeof Highcharts === 'undefined') { return; }
                if (!INL) { document.body.appendChild(modal); } // กัน position:fixed ถูก section ครอบ
                const sheet = modal.querySelector('.hm-tr-sheet');
                const closeBtn = document.getElementById('hmTrendClose');
                const yearEl = document.getElementById('hmTrendYear');
                const monthEl = document.getElementById('hmTrendMonth');
                const seg = document.getElementById('hmTrSeg');
                const foot = document.getElementById('hmTrFoot');
                const listEl = document.getElementById('hmTrList');
                const liveEl = document.getElementById('hmTrLive');
                const FEED = modal.dataset.feed || '', PROV = modal.dataset.province || 'all';
                const hiddenYears = {};   // ปีที่ผู้ใช้ซ่อนจากกราฟ (คลิกคำอธิบายกราฟ) -> ข้อความวิเคราะห์คำนวณใหม่ตามปีที่ยังแสดง
                let pinIdx = null;        // เดือนที่ผู้ใช้คลิกเลือกบนกราฟ
                let building = false;
                const tabs = modal.querySelectorAll('.hm-tr-tab');
                const state = { view: INL ? 'month' : 'year', metric: 'rate' };
                let yearChart = null, monthChart = null;
                const nf = (v, d) => Highcharts.numberFormat(v, d, '.', ',');
                const TEAL = '#0d9488', INDIGO = '#4338ca';

                function tipShell(head, rows, extra) {
                    return '<div style="font-family:Sarabun,sans-serif;min-width:244px;border-radius:14px;overflow:hidden;background:#fff;">'
                        + '<div style="display:flex;align-items:center;gap:8px;padding:10px 14px;background:linear-gradient(180deg,#ecfdf9,#f7fffd);border-bottom:1px solid #dcefea;font-weight:800;font-size:14.5px;color:#0f172a;">' + head + '</div>'
                        + '<div style="padding:7px 14px 6px;">' + rows + '</div>'
                        + (extra ? '<div style="padding:9px 14px 10px;border-top:1px solid #eef2f6;background:#f8fafc;">' + extra + '</div>' : '')
                        + '</div>';
                }
                // delta: undefined = ไม่มีคอลัมน์เปรียบเทียบ, '' = เว้นช่องไว้ให้แถวตรงกัน
                function tipRow(color, name, val, unit, delta, strong) {
                    return '<div style="display:flex;align-items:center;gap:9px;padding:5px 7px;margin:1px -7px;border-radius:9px;font-size:13px;color:#475569;' + (strong ? 'background:#f0fdfa;' : '') + '">'
                        + '<span style="width:11px;height:11px;border-radius:3px;background:' + color + ';flex:none;"></span>'
                        + '<span style="flex:1;font-weight:' + (strong ? '800' : '500') + ';color:' + (strong ? '#0f172a' : '#475569') + ';">' + name + '</span>'
                        + '<b style="font-size:15.5px;color:#0f172a;font-variant-numeric:tabular-nums;text-align:right;">' + val + '</b>'
                        + (unit ? '<small style="color:#94a3b8;font-size:11px;">' + unit + '</small>' : '')
                        + (delta === undefined ? '' : '<span style="min-width:66px;text-align:right;">' + delta + '</span>')
                        + '</div>';
                }
                function badge(text, tone) {
                    const c = tone === 'up' ? ['#b91c1c', '#fee2e2'] : (tone === 'down' ? ['#15803d', '#dcfce7'] : ['#475569', '#eef2f6']);
                    return '<span style="display:inline-block;font-size:11.5px;font-weight:800;line-height:1;padding:4px 8px;border-radius:999px;white-space:nowrap;color:' + c[0] + ';background:' + c[1] + ';">' + text + '</span>';
                }
                const tooltipBase = {
                    shared: true, useHTML: true, hideDelay: 120, backgroundColor: '#ffffff', borderWidth: 0, borderRadius: 14, padding: 0,
                    shadow: { color: 'rgba(15, 23, 42, 0.22)', offsetX: 0, offsetY: 8, width: 18 },
                    style: { fontFamily: 'Sarabun, sans-serif' }
                };
                const axisCommon = {
                    gridLineColor: '#eef2f6',
                    labelStyle: { color: '#64748b', fontSize: '12px' }
                };

                function niceTop(rawMax) {
                    const stepRaw = Math.max(rawMax, 1) * 1.1 / 4;
                    const pw = Math.pow(10, Math.floor(Math.log10(stepRaw)));
                    const f = [1, 1.2, 1.5, 2, 2.5, 3, 4, 5, 6, 8, 10].filter(function (k) { return stepRaw <= k * pw; })[0] || 10;
                    return { step: f * pw, max: f * pw * 4 };
                }
                const pill = { backgroundColor: 'rgba(255,255,255,.92)', borderRadius: 8, padding: 3, borderWidth: 0, shadow: false };

                // กราฟรายปี: แกน X = จังหวัด, แท่งในกลุ่ม = ปี (เข้มขึ้นเมื่อเป็นปีล่าสุด)
                // ตอบคำถามหลักได้ในกราฟเดียว: จังหวัดไหนสูง/ต่ำ, แต่ละจังหวัดขึ้นหรือลงจากปีก่อนเท่าไร (ป้าย ▲▼ ใต้ชื่อจังหวัด),
                // และอยู่เหนือหรือต่ำกว่าค่ารวมเขต (เส้นประแดง). ปีล่าสุดที่ข้อมูลยังไม่ครบปีจะไม่แสดงป้ายเปลี่ยนแปลง เพราะเทียบกับปีเต็มไม่ได้
                function yearShade(i, n) {
                    const a = [155, 229, 218], b = [15, 118, 110], t = n <= 1 ? 1 : i / (n - 1);
                    const c = a.map(function (v, k) { return Math.round(v + (b[k] - v) * t); });
                    return 'rgb(' + c.join(',') + ')';
                }
                function shortNum(v) { return v >= 10000 ? nf(v / 1000, 1) + 'k' : nf(v, 0); }
                function buildYear() {
                    if (yearChart) { yearChart.destroy(); yearChart = null; }
                    const m = state.metric, isRate = m === 'rate', dec = isRate ? 2 : 0;
                    const unit = isRate ? 'ต่อแสนประชากร' : 'คน';
                    const n = D.years.length, multi = D.provinces.length > 1;
                    const canCompare = n >= 2 && !D.partial_year;
                    const narrow = yearEl.offsetWidth < 520; // มือถือ: ย่อป้ายและแสดงตัวเลขเฉพาะปีล่าสุด
                    const lab = function (v) { return isRate ? nf(v, 0) : shortNum(v); };
                    const change = function (vs) {
                        if (!canCompare) { return null; }
                        const a = vs[n - 2], b = vs[n - 1];
                        return (a === null || b === null || !(a > 0)) ? null : (b - a) / a * 100;
                    };
                    const yoy = function (vs, k) {
                        if (k < 1 || (D.partial_year && k === n - 1)) { return null; }
                        const a = vs[k - 1], b = vs[k];
                        return (a === null || b === null || !(a > 0)) ? null : (b - a) / a * 100;
                    };
                    const pillChip = function (pc) {
                        if (pc === null) { return ''; }
                        const flat = Math.abs(pc) < 0.5, up = pc > 0;
                        return badge(flat ? '● ทรงตัว' : (up ? '▲ ' : '▼ ') + nf(Math.abs(pc), 1) + '%', flat ? 'flat' : (up ? 'up' : 'down'));
                    };
                    const chip = function (pc) {
                        if (pc === null) { return ''; }
                        const flat = Math.abs(pc) < 0.5, up = pc > 0;
                        return '<span style="font-size:11.5px;font-weight:800;color:' + (flat ? '#64748b' : (up ? '#b91c1c' : '#15803d')) + ';">'
                            + (flat ? '● ทรงตัว' : (up ? '▲ ' : '▼ ') + nf(Math.abs(pc), 1) + '%') + '</span>';
                    };
                    const cats = multi ? D.provinces.map(function (p) { return p.name; }) : D.years;
                    const series = [];
                    if (multi) {
                        D.years.forEach(function (y, k) {
                            series.push({
                                type: 'column', name: 'ปี ' + y, color: yearShade(k, n), borderWidth: 0, borderRadius: 3,
                                data: D.provinces.map(function (p) { return p[m][k]; }),
                                dataLabels: {
                                    enabled: !narrow || k === n - 1, crop: false, overflow: 'allow', allowOverlap: true, y: -2,
                                    style: { color: k === n - 1 ? '#0f172a' : '#64748b', fontSize: '10.5px', fontWeight: k === n - 1 ? '800' : '600', textOutline: 'none' },
                                    formatter: function () { return this.y === null ? '' : lab(this.y); }
                                }
                            });
                        });
                    } else {
                        series.push({
                            type: 'column', name: D.provinces[0].name, color: yearShade(n - 1, n), borderWidth: 0, borderRadius: 3, data: D.provinces[0][m],
                            dataLabels: {
                                enabled: true, crop: false, overflow: 'allow', y: -2,
                                style: { color: '#0f172a', fontSize: '11.5px', fontWeight: '800', textOutline: 'none' },
                                formatter: function () { return this.y === null ? '' : lab(this.y); }
                            }
                        });
                    }
                    const all = [];
                    D.provinces.forEach(function (p) { all.push.apply(all, p[m]); });
                    const top = niceTop(Math.max.apply(null, all.filter(function (v) { return v !== null; }).concat([1])));
                    const refV = D.overall.rate[n - 1];
                    const showRef = isRate && multi && refV !== null;
                    const showChip = showRef && !narrow;
                    if (showRef) {
                        // รายการในคำอธิบายกราฟ; ตัวเส้นจริงวาดเองใน drawRef ให้มีมิติ (เงา แสงขอบ ปุ่มหัวเส้น ป้ายค่าที่ขอบขวา)
                        series.push({
                            type: 'line', name: 'รวมเขต 10 · ปี ' + D.years[n - 1], data: [], color: '#b91c1c', dashStyle: 'ShortDash', lineWidth: 3,
                            marker: { enabled: false }, enableMouseTracking: false
                        });
                    }
                    function drawRef(ch) {
                        if (ch.htRef) { ch.htRef.destroy(); ch.htRef = null; }
                        if (!showRef) { return; }
                        const r = ch.renderer, x1 = ch.plotLeft, x2 = ch.plotLeft + ch.plotWidth;
                        const y = Math.round(ch.yAxis[0].toPixels(refV));
                        const dash = { 'stroke-dasharray': '7 6', 'stroke-linecap': 'round' };
                        const g = r.g('ht-ref').attr({ zIndex: 5 }).add();
                        // แสงเรืองบางๆ รอบเส้น
                        r.rect(x1, y - 9, x2 - x1, 18, 0).attr({ fill: { linearGradient: { x1: 0, y1: 0, x2: 0, y2: 1 }, stops: [[0, 'rgba(185,28,28,0)'], [0.5, 'rgba(185,28,28,0.15)'], [1, 'rgba(185,28,28,0)']] } }).add(g);
                        // เงาใต้เส้น -> ตัวเส้น -> ไฮไลต์ขอบบน ให้ดูนูนเหมือนท่อ
                        r.path([['M', x1, y + 3], ['L', x2, y + 3]]).attr(Object.assign({ stroke: 'rgba(127,29,29,0.38)', 'stroke-width': 3 }, dash)).addClass('ht-ref-blur').add(g);
                        r.path([['M', x1, y], ['L', x2, y]]).attr(Object.assign({ stroke: '#b91c1c', 'stroke-width': 2.6 }, dash)).add(g);
                        r.path([['M', x1, y - 0.9], ['L', x2, y - 0.9]]).attr(Object.assign({ stroke: 'rgba(255,255,255,0.5)', 'stroke-width': 0.9 }, dash)).add(g);
                        r.circle(x1, y, 4.5).attr({ fill: '#b91c1c', stroke: '#ffffff', 'stroke-width': 2 }).addClass('ht-ref-knob').add(g);
                        if (!showChip) { ch.htRef = g; return; }
                        // ป้ายค่ารวมเขตที่ขอบขวา (อยู่นอกพื้นที่แท่ง จึงไม่ทับตัวเลข)
                        r.path([['M', x2 + 1, y], ['L', x2 + 9, y - 5], ['L', x2 + 9, y + 5], ['Z']]).attr({ fill: '#b91c1c' }).addClass('ht-ref-chip').add(g);
                        const lab = r.label('รวมเขต<br/><span style="font-size:13px;font-weight:800">' + nf(refV, 0) + '</span>', x2 + 8, y - 15, 'rect')
                            .attr({ fill: '#b91c1c', r: 7, padding: 5 })
                            .css({ color: '#ffffff', fontFamily: 'Sarabun, sans-serif', fontSize: '10.5px', fontWeight: '600' })
                            .addClass('ht-ref-chip').add(g);
                        lab.attr({ y: y - lab.getBBox().height / 2 });
                        ch.htRef = g;
                    }
                    const totalsTxt = (!isRate && multi)
                        ? 'รวมเขต 10 · ' + D.years.map(function (y, k) { return 'ปี ' + y + ': <b>' + nf(D.overall.cases[k], 0) + '</b>'; }).join(' · ') + ' คน'
                        : null;
                    yearChart = Highcharts.chart(yearEl, {
                        chart: { backgroundColor: 'transparent', spacingTop: 10, spacingRight: showChip ? 66 : 14, spacingLeft: 6, style: { fontFamily: 'Sarabun, sans-serif' }, events: { render: function () { drawRef(this); } } },
                        title: { text: null }, credits: { enabled: false }, exporting: { enabled: false },
                        subtitle: totalsTxt ? { text: totalsTxt, useHTML: true, align: 'left', x: 6, style: { color: '#b91c1c', fontSize: '12.5px', fontWeight: '600' } } : { text: null },
                        legend: { align: 'center', verticalAlign: 'bottom', itemDistance: 16, symbolRadius: 3, enabled: multi, itemStyle: { fontWeight: '600', color: '#334155', fontSize: '12.5px' } },
                        xAxis: {
                            categories: cats, lineColor: '#94a3b8', tickLength: 0, crosshair: { color: 'rgba(100,116,139,.08)' },
                            labels: {
                                useHTML: true, y: narrow ? 20 : 26, rotation: 0,
                                formatter: function () {
                                    if (!multi) { return '<b style="font-size:13px;color:#334155;font-family:Sarabun,sans-serif;">ปี ' + this.value + '</b>'; }
                                    const p = D.provinces[this.pos];
                                    return '<div style="text-align:center;line-height:1.4;font-family:Sarabun,sans-serif;"><b style="font-size:' + (narrow ? '10.5' : '13') + 'px;color:#334155;">' + this.value + '</b><br>' + (chip(change(p[m])) || '<span style="font-size:11px;color:#94a3b8;">&nbsp;</span>') + '</div>';
                                }
                            }
                        },
                        yAxis: {
                            title: { text: null }, min: 0, max: top.max, tickInterval: top.step, allowDecimals: false, gridLineColor: '#e9eef3',
                            labels: { style: axisCommon.labelStyle, x: -6, formatter: function () { return nf(this.value, 0); } }
                        },
                        tooltip: Object.assign({}, tooltipBase, {
                            // วางข้างกลุ่มแท่งที่ชี้ (ไม่ทับแท่งนั้นและป้ายใต้แกน X)
                            positioner: function (w, h, pt) {
                                const ch = this.chart, hp = ch.hoverPoints && ch.hoverPoints[0];
                                const slot = ch.plotWidth / cats.length;
                                const cx = ch.plotLeft + (hp ? (hp.index + 0.5) * slot : pt.plotX);
                                const half = slot * 0.46 + 6;
                                let x = cx > ch.plotLeft + ch.plotWidth / 2 ? cx - half - w : cx + half;
                                x = Math.max(4, Math.min(x, ch.chartWidth - w - 4));
                                return { x: x, y: Math.max(4, Math.min(ch.plotTop, ch.chartHeight - h - 4)) };
                            },
                            formatter: function () {
                                const idx = this.points[0].point.index;
                                const p = multi ? D.provinces[idx] : D.provinces[0];
                                const vs = p[m];
                                const dot = multi ? ((PC && PC[p.name]) || TEAL) : TEAL;
                                const head = '<span style="width:11px;height:11px;border-radius:50%;background:' + dot + ';flex:none;"></span>'
                                    + '<span style="flex:1;">' + (multi ? p.name : 'ปีงบประมาณ ' + this.x) + '</span>'
                                    + '<small style="font-weight:600;font-size:11px;color:#64748b;">' + (isRate ? 'ต่อแสนประชากร' : 'คน') + '</small>';
                                const rows = this.points.slice().reverse().map(function (pt) {
                                    const k = multi ? pt.series.index : pt.point.index;
                                    const d = pt.y === null ? '<span style="font-size:11px;color:#94a3b8;">ไม่มีเป้าหมาย</span>' : pillChip(yoy(vs, k));
                                    return tipRow(pt.color, multi ? pt.series.name : 'ปี ' + this.x, pt.y === null ? '—' : nf(pt.y, dec), '', d, n > 1 && k === n - 1);
                                }, this).join('');
                                const last = vs[n - 1];
                                let extra = '';
                                if (multi && isRate && refV !== null && last !== null) {
                                    const d = (last - refV) / refV * 100, flat = Math.abs(d) < 0.5, hi = d > 0;
                                    extra += '<div style="display:flex;align-items:center;gap:8px;font-size:12.5px;color:#475569;">'
                                        + '<span style="display:inline-block;width:22px;height:0;border-top:2.5px dashed #b91c1c;flex:none;"></span>'
                                        + '<span style="flex:1;">เทียบรวมเขต ปี ' + D.years[n - 1] + '</span>'
                                        + badge(flat ? '● ใกล้เคียง' : (hi ? '▲ สูงกว่า ' : '▼ ต่ำกว่า ') + nf(Math.abs(d), 1) + '%', flat ? 'flat' : (hi ? 'up' : 'down')) + '</div>';
                                } else if (multi && !isRate && last !== null && D.overall.cases[n - 1] > 0) {
                                    extra += '<div style="display:flex;align-items:center;gap:8px;font-size:12.5px;color:#475569;">'
                                        + '<span style="flex:1;">สัดส่วนของรวมเขต 10 ปี ' + D.years[n - 1] + '</span>'
                                        + '<b style="font-size:15px;color:#0f172a;font-variant-numeric:tabular-nums;">' + nf(last / D.overall.cases[n - 1] * 100, 1) + '%</b></div>';
                                }
                                if (multi && D.partial_year && D.partial_label) {
                                    extra += '<div style="margin-top:' + (extra ? '6' : '0') + 'px;font-size:11.5px;color:#94a3b8;">ปี ' + D.partial_year + ' มีข้อมูลถึง ' + D.partial_label + ' จึงยังไม่เทียบกับปีก่อน</div>';
                                }
                                return tipShell(head, rows, extra);
                            }
                        }),
                        plotOptions: {
                            column: { groupPadding: 0.1, pointPadding: 0.02 },
                            series: { animation: { duration: 700 }, clip: false, states: { hover: { brightness: 0.05 }, inactive: { opacity: 0.55 } } }
                        },
                        series: series
                    });
                }

                function buildMonth() {
                    if (monthChart) { monthChart.destroy(); monthChart = null; }
                    const series = [];
                    // ทุกปีที่มีข้อมูลรายเดือน (เก่า -> ใหม่): ปีล่าสุดเด่นสุด, ปีก่อนหน้ามีพื้นที่สี, ปีที่เก่ากว่าเป็นเส้นบาง
                    const S = (D.month_series && D.month_series.length) ? D.month_series : [D.month_prev, D.month_latest].filter(Boolean);
                    const L = S[S.length - 1], P = S.length > 1 ? S[S.length - 2] : null;
                    const OLD = ['#ea580c', '#0284c7', '#9333ea'];
                    // ป้ายตัวเลขเฉพาะจุดสำคัญของปีล่าสุด: เดือนสูงสุด และเดือนล่าสุดที่มีข้อมูล
                    let maxI = -1, maxV = -1;
                    L.values.forEach(function (v, i) { if (v !== null && v > maxV) { maxV = v; maxI = i; } });
                    const keyIdx = {}; if (maxI >= 0) { keyIdx[maxI] = true; } if (D.last_idx !== null) { keyIdx[D.last_idx] = true; }
                    const Ldata = L.values.map(function (v, i) {
                        if (v === null || !keyIdx[i]) { return v; }
                        return Object.assign({ y: v, dataLabels: Object.assign({
                            enabled: true, y: -10, crop: false, overflow: 'allow',
                            style: { color: '#0f766e', fontSize: '12.5px', fontWeight: '800', textOutline: 'none' },
                            formatter: function () { return nf(this.y, 0); }
                        }, pill) }, i === maxI ? { marker: { enabled: true, radius: 6.5, fillColor: '#fff', lineWidth: 3.5, lineColor: TEAL } } : {});
                    });
                    S.slice(0, Math.max(S.length - 2, 0)).forEach(function (o, k) {
                        series.push({
                            type: 'spline', name: 'ปี ' + o.year, data: o.values, color: OLD[k % OLD.length], lineWidth: 3, zIndex: 4, dashStyle: 'Dash',
                            marker: { enabled: false, symbol: 'diamond', radius: 4 }
                        });
                    });
                    if (P) {
                        series.push({
                            type: 'areaspline', name: 'ปี ' + P.year, data: P.values, color: INDIGO, lineWidth: 3, zIndex: 3,
                            fillColor: { linearGradient: { x1: 0, y1: 0, x2: 0, y2: 1 }, stops: [[0, 'rgba(67,56,202,.24)'], [1, 'rgba(67,56,202,.02)']] },
                            marker: { enabled: false, symbol: 'diamond', radius: 4.5 }
                        });
                    }
                    series.push({
                        type: 'areaspline', name: 'ปี ' + L.year, data: Ldata, color: TEAL, lineWidth: 4.5, zIndex: 2,
                        fillColor: { linearGradient: { x1: 0, y1: 0, x2: 0, y2: 1 }, stops: [[0, 'rgba(13,148,136,.36)'], [1, 'rgba(13,148,136,.04)']] },
                        marker: { enabled: false, symbol: 'circle', radius: 5, fillColor: '#fff', lineWidth: 3, lineColor: TEAL }
                    });
                    const bands = [];
                    if (D.last_idx !== null && D.last_idx < 11) {
                        bands.push({
                            from: D.last_idx + 0.5, to: 11.5, color: 'rgba(100,116,139,.09)',
                            label: { text: 'ยังไม่มีข้อมูล', align: 'center', verticalAlign: 'top', y: 14, style: { color: '#64748b', fontSize: '11px', fontWeight: '700' } }
                        });
                    }
                    const allV = S.reduce(function (acc, o) { return acc.concat(o.values); }, []).filter(function (v) { return v !== null; });
                    const top = niceTop(Math.max.apply(null, allV.concat([1])));
                    monthChart = Highcharts.chart(monthEl, {
                        chart: { backgroundColor: 'transparent', spacingTop: 8, spacingRight: 14, style: { fontFamily: 'Sarabun, sans-serif' } },
                        title: { text: null }, credits: { enabled: false }, exporting: { enabled: false },
                        legend: { align: 'left', verticalAlign: 'top', symbolRadius: 6, symbolHeight: 10, symbolWidth: 10, itemDistance: 16, itemStyle: { fontWeight: '600', color: '#334155', fontSize: '12.5px' } },
                        xAxis: {
                            categories: D.month_labels, tickmarkPlacement: 'on', lineColor: '#cbd5e1', tickLength: 0, plotBands: bands,
                            crosshair: { color: 'rgba(13,148,136,.35)', width: 1.5, dashStyle: 'ShortDash', zIndex: 1 },
                            labels: { style: { color: '#475569', fontSize: '12px', fontWeight: '600' }, y: 22, step: 1, allowOverlap: true, autoRotation: [-35] }
                        },
                        yAxis: {
                            title: { text: null }, min: 0, max: top.max, tickInterval: top.step, allowDecimals: false,
                            gridLineColor: '#e9eef3', gridLineDashStyle: 'Dot',
                            labels: { style: axisCommon.labelStyle, x: -6, formatter: function () { return nf(this.value, 0); } }
                        },
                        tooltip: Object.assign({}, tooltipBase, {
                            formatter: function () {
                                // แสดงข้อมูล "ครบทุกปี" เสมอ แม้ปีใดถูกซ่อนจากคำอธิบายกราฟ (ปีที่ซ่อนแสดงจางๆ) และเทียบกับปีก่อนหน้าให้ทุกแถว
                                const idx = this.points[0].point.index;
                                const vis = {};
                                this.points[0].series.chart.series.forEach(function (sr) { vis[sr.name] = sr.visible; });
                                const info = S.map(function (o, k) {
                                    const v = o.values[idx];
                                    const prev = k > 0 ? S[k - 1].values[idx] : null;
                                    return { year: o.year, v: (v === undefined ? null : v), prev: (prev === undefined ? null : prev), shown: vis['ปี ' + o.year] !== false, color: k === S.length - 1 ? TEAL : (k === S.length - 2 ? INDIGO : OLD[(k) % OLD.length]) };
                                });
                                const rows = info.slice().reverse().map(function (r, i) {
                                    let d = '';
                                    if (r.v !== null && r.prev !== null) {
                                        const df = r.v - r.prev;
                                        d = badge(df === 0 ? '● เท่ากัน' : (df > 0 ? '▲ ' : '▼ ') + nf(Math.abs(df), 0), df === 0 ? 'flat' : (df > 0 ? 'up' : 'down'));
                                    }
                                    const row = tipRow(r.shown ? r.color : '#cbd5e1', 'ปี ' + r.year + (r.shown ? '' : ' <small style="font-size:10.5px;color:#94a3b8;">(ซ่อนในกราฟ)</small>'), r.v === null ? '—' : nf(r.v, 0), r.v === null ? '' : 'คน', d, false);
                                    return r.shown ? row : '<div style="opacity:.55;">' + row + '</div>';
                                }).join('');
                                // ส่วนเปรียบเทียบ: สองปีล่าสุดที่ยังเปิดแสดงอยู่ในกราฟ (ตามที่ผู้ใช้เลือก)
                                const pick = info.filter(function (r) { return r.shown && r.v !== null; });
                                let extra = '';
                                if (pick.length >= 2) {
                                    const cur = pick[pick.length - 1], prv = pick[pick.length - 2];
                                    const df = cur.v - prv.v, up = df > 0, flat = df === 0;
                                    const pc = prv.v > 0 ? df / prv.v * 100 : null;
                                    extra = '<div style="display:flex;align-items:center;gap:8px;font-size:12.5px;color:#475569;">'
                                        + '<span style="flex:1;">ปี ' + cur.year + ' เทียบ ปี ' + prv.year + '</span>'
                                        + badge(flat ? '● เท่ากัน' : (up ? '▲ สูงกว่า ' : '▼ ต่ำกว่า ') + nf(Math.abs(df), 0) + ' คน' + (pc !== null && !flat ? ' (' + (up ? '+' : '') + nf(Math.abs(pc), 1) + '%)' : ''), flat ? 'flat' : (up ? 'up' : 'down')) + '</div>';
                                }
                                return tipShell('เดือน' + this.x, rows, extra);
                            }
                        }),
                        plotOptions: {
                            series: {
                                animation: { duration: 700 }, connectNulls: false, pointPlacement: 'on', cursor: 'pointer',
                                events: { show: onLegendToggle, hide: onLegendToggle },
                                point: { events: { click: function () { pinMonth(this.index); } } },
                                states: { hover: { halo: { size: 9, opacity: 0.18 } }, inactive: { opacity: 0.35 } },
                                marker: { states: { hover: { enabled: true, radius: 5.5 } } }
                            }
                        },
                        series: series
                    });
                    building = true;
                    monthChart.series.forEach(function (sr) { if (hiddenYears[sr.name.replace('ปี ', '')]) { sr.setVisible(false, false); } });
                    building = false;
                    monthChart.redraw();
                    drawPin();
                }

                // ================= ข้อความวิเคราะห์ที่ปรับตามการคลิก + อัปเดตตามข้อมูลล่าสุด =================
                const ICON = { up: '▲', down: '▼', flat: '●', info: 'i' };
                const MONTH_RE = ['ม\\.ค\\.', 'ก\\.พ\\.', 'มี\\.ค\\.', 'เม\\.ย\\.', 'พ\\.ค\\.', 'มิ\\.ย\\.', 'ก\\.ค\\.', 'ส\\.ค\\.', 'ก\\.ย\\.', 'ต\\.ค\\.', 'พ\\.ย\\.', 'ธ\\.ค\\.'].join('|');
                // ไฮไลต์ตัวเลข/ปี/คำว่าเพิ่มขึ้น-ลดลง เหมือนฝั่งเซิร์ฟเวอร์ ($hmEm)
                const EM_RE = new RegExp('(ปี(?:งบ)?\\s\\d{4})|(\\(?[+\\-]\\d[\\d,]*(?:\\.\\d+)?%\\)?)|(เพิ่มขึ้น|ลดลง|ใกล้เคียงเดิม)|(\\d{1,3}(?:,\\d{3})+(?:\\.\\d+)?|\\d+\\.\\d+|\\d+(?=\\s?(?:คน|ต่อแสน|จังหวัด)))|(เดือน)(' + MONTH_RE + ')', 'g');
                const esc = (t) => String(t).replace(/[&<>"]/g, (c) => ({ '&': '&amp;', '<': '&lt;', '>': '&gt;', '"': '&quot;' }[c]));
                function emph(raw) {
                    return esc(raw).replace(EM_RE, function (m, yr, pc, vb, num, mo, mname) {
                        if (yr) { return '<span class="yr">' + yr + '</span>'; }
                        if (pc) { const g = pc.replace(/[()]/g, ''); const v = parseFloat(g.replace(/,/g, '')); return '<span class="pc ' + (Math.abs(v) < 2 ? 'flat' : (v > 0 ? 'up' : 'down')) + '">' + g.replace(/^-/, '') + '</span>'; }
                        if (vb) { return '<span class="vb ' + (vb === 'เพิ่มขึ้น' ? 'up' : (vb === 'ลดลง' ? 'down' : 'flat')) + '">' + vb + '</span>'; }
                        if (num) { return '<strong>' + num + '</strong>'; }
                        return mo + '<strong>' + mname + '</strong>';
                    });
                }
                function renderInsights(list, animate) {
                    listEl.innerHTML = list.map(function (x) {
                        return '<li class="hm-tr-in ' + x.tone + (x.pin ? ' pin' : '') + '"><span class="hm-tr-ic" aria-hidden="true">' + ICON[x.tone] + '</span>'
                            + '<div><span class="hm-tr-ti">' + esc(x.title) + '</span><span class="hm-tr-tx">' + emph(x.text) + '</span></div></li>';
                    }).join('');
                    if (animate) { listEl.classList.remove('swap'); void listEl.offsetWidth; listEl.classList.add('swap'); }
                }
                const sgn = (p) => (p > 0 ? '+' : '') + nf(p, 1) + '%';
                const pctOf = (n, o) => (o > 0 ? (n - o) / o * 100 : null);
                const toneOf = (p) => (p === null ? 'info' : (Math.abs(p) < 2 ? 'flat' : (p > 0 ? 'up' : 'down')));
                const verbOf = (p) => (Math.abs(p) < 2 ? 'ใกล้เคียงเดิม' : (p > 0 ? 'เพิ่มขึ้น' : 'ลดลง'));
                const lastNonNull = (vals) => { let k = -1; vals.forEach(function (v, i) { if (v !== null && v !== undefined) { k = i; } }); return k; };
                const sumTo = (vals, k) => vals.slice(0, k).reduce(function (a, v) { return a + (v || 0); }, 0);

                function visibleSeries() {
                    const S = (D.month_series && D.month_series.length) ? D.month_series : [D.month_prev, D.month_latest].filter(Boolean);
                    return S.filter(function (o) { return !hiddenYears[o.year]; });
                }
                // คำนวณข้อความวิเคราะห์จาก "ปีที่ยังแสดงอยู่" (ปีที่เลือกล่าสุด = ปีหลัก, ก่อนหน้าหนึ่งปี = ปีเทียบ) - ถ้าแสดงครบทุกปีจะได้ผลเดียวกับที่เซิร์ฟเวอร์คำนวณ
                function computeInsights() {
                    const V = visibleSeries(), ML = D.month_labels, out = [];
                    const total = (D.month_series || []).length;
                    if (!V.length) { return [{ tone: 'info', title: 'ยังไม่ได้เลือกปี', text: 'คลิกชื่อปีใต้หัวกราฟเพื่อเปิดแสดงข้อมูล ข้อความวิเคราะห์จะคำนวณใหม่ตามปีที่เลือก' }]; }
                    const cur = V[V.length - 1], prev = V.length > 1 ? V[V.length - 2] : null;
                    const last = lastNonNull(cur.values), k = last + 1, complete = last === 11;
                    const Y = 'ปีงบ ' + cur.year;
                    if (pinIdx !== null && cur.values[pinIdx] !== null && cur.values[pinIdx] !== undefined) {
                        const v = cur.values[pinIdx], pv = prev ? prev.values[pinIdx] : null;
                        const p = (pv !== null && pv !== undefined) ? pctOf(v, pv) : null;
                        out.push({ tone: p === null ? 'info' : toneOf(p), pin: true, title: 'เดือนที่เลือก',
                            text: Y + ' เดือน' + ML[pinIdx] + ' มีผู้ป่วยใหม่ ' + nf(v, 0) + ' คน'
                                + (p !== null ? ' เทียบปีงบ ' + prev.year + ' (' + nf(pv, 0) + ' คน) ' + verbOf(p) + ' (' + sgn(p) + ')' : '') });
                    }
                    if (prev && k > 0) {
                        const ytd = sumTo(cur.values, k), ytdP = sumTo(prev.values, k), p = pctOf(ytd, ytdP);
                        if (p !== null) {
                            out.push({ tone: toneOf(p), title: 'เทียบช่วงเดียวกัน',
                                text: Y + ' ' + (complete ? 'ตลอดปี' : 'สะสมถึง' + ML[last]) + ' มีผู้ป่วยใหม่ ' + nf(ytd, 0) + ' คน เทียบช่วงเดียวกันของปีงบ ' + prev.year + ' (' + nf(ytdP, 0) + ' คน) ' + verbOf(p) + ' (' + sgn(p) + ')' });
                        }
                    }
                    const rep = cur.values.slice(0, k);
                    if (last >= 2) {
                        const maxV = Math.max.apply(null, rep), maxI = rep.indexOf(maxV);
                        if (maxV > 0) {
                            out.push({ tone: 'info', title: 'เดือนที่พบมากสุด', text: Y + ' พบผู้ป่วยใหม่สูงสุดในเดือน' + ML[maxI] + ' ' + nf(maxV, 0) + ' คน (เฉลี่ย ' + nf(rep.reduce(function (a, b) { return a + b; }, 0) / rep.length, 0) + ' คนต่อเดือน)' });
                        }
                        let up = null, down = null;
                        for (let i = 1; i < rep.length; i++) {
                            const d = rep[i] - rep[i - 1];
                            if (d > 0 && (up === null || d > up.d)) { up = { i: i, d: d }; }
                            if (d < 0 && (down === null || d < down.d)) { down = { i: i, d: d }; }
                        }
                        [[up, 'up', 'เดือนที่เพิ่มขึ้นมากสุด', 'เพิ่มขึ้น'], [down, 'down', 'เดือนที่ลดลงมากสุด', 'ลดลง']].forEach(function (c) {
                            if (!c[0]) { return; }
                            const pp = pctOf(rep[c[0].i], rep[c[0].i - 1]);
                            out.push({ tone: c[1], title: c[2], text: Y + ' เดือน' + ML[c[0].i] + ' ' + c[3] + ' ' + nf(Math.abs(c[0].d), 0) + ' คน เทียบกับก่อนหน้า' + (pp !== null ? ' (' + sgn(pp) + ')' : '') });
                        });
                    }
                    if (!complete && last >= 0) {
                        out.push({ tone: 'info', title: 'ข้อควรระวัง', text: Y + ' มีข้อมูลถึงเดือน' + ML[last] + ' เท่านั้น ตัวเลขรายปีของปีนี้จึงยังต่ำกว่าปีเต็ม ไม่ควรเทียบตรงกับปีก่อน (ดูการเทียบช่วงเดียวกันแทน)' });
                    }
                    if (!prev) {
                        out.push({ tone: 'info', title: total < 2 ? 'ข้อมูลยังน้อย' : 'แสดงปีเดียว', text: total < 2
                            ? 'ตอนนี้มีข้อมูลเพียง ' + Y + ' จึงยังเทียบแนวโน้มข้ามปีไม่ได้ เมื่อมีข้อมูลปีถัดไประบบจะคำนวณให้อัตโนมัติ'
                            : 'ตอนนี้แสดง ' + Y + ' ปีเดียว คลิกชื่อปีอื่นใต้หัวกราฟเพื่อเปิดแสดงและเทียบช่วงเดียวกัน' });
                    }
                    return out;
                }
                function refreshInsights(animate) {
                    const untouched = pinIdx === null && !Object.keys(hiddenYears).some(function (y) { return hiddenYears[y]; });
                    renderInsights(untouched && D.insights && D.insights.length ? D.insights : computeInsights(), animate);
                }
                function onLegendToggle() {
                    if (building) { return; }
                    setTimeout(function () {
                        if (!monthChart) { return; }
                        monthChart.series.forEach(function (sr) { hiddenYears[sr.name.replace('ปี ', '')] = !sr.visible; });
                        refreshInsights(true);
                    }, 0);
                }
                function drawPin() {
                    if (!monthChart) { return; }
                    monthChart.xAxis[0].removePlotBand('hmPin');
                    if (pinIdx !== null) {
                        monthChart.xAxis[0].addPlotBand({ id: 'hmPin', from: pinIdx - 0.5, to: pinIdx + 0.5, color: 'rgba(13,148,136,.11)', zIndex: 0 });
                    }
                }
                function pinMonth(i) {
                    pinIdx = (pinIdx === i) ? null : i; // คลิกเดือนเดิมซ้ำ = ยกเลิก
                    drawPin();
                    refreshInsights(true);
                }

                // ---- ตรวจข้อมูลใหม่อัตโนมัติ (ทุก 60 วินาที / เมื่อกลับมาที่แท็บ): ถ้าข้อมูลใน DB เปลี่ยน กราฟ + ข้อความ + ป้ายหัวการ์ดอัปเดตเอง
                let feedStamp = '', lastCheck = 0;
                const hhmm = () => new Date().toLocaleTimeString('th-TH', { hour: '2-digit', minute: '2-digit' });
                function liveNote(ok, fresh) {
                    liveEl.hidden = false;
                    liveEl.classList.toggle('off', !ok);
                    liveEl.classList.toggle('fresh', !!fresh);
                    liveEl.textContent = !ok ? 'ตรวจข้อมูลใหม่ไม่สำเร็จ จะลองอีกครั้งอัตโนมัติ'
                        : (fresh ? 'ข้อมูลเพิ่งอัปเดตเมื่อ ' + hhmm() + ' น.' : 'อัปเดตอัตโนมัติ · ตรวจล่าสุด ' + hhmm() + ' น.');
                }
                function applyData(t) {
                    D = t;
                    const sub = document.getElementById('hmTrSub');
                    if (sub) { sub.textContent = 'ข้อมูลปีงบประมาณ ' + D.years[0] + (D.years.length > 1 ? '–' + D.years[D.years.length - 1] : '') + ' · ' + sub.dataset.scope; }
                    const chip = document.getElementById('hmTrChip');
                    if (chip && D.summary) {
                        chip.className = 'hm-tr-chip ' + D.summary.tone;
                        chip.textContent = ICON[D.summary.tone] + ' ' + D.summary.label + ' ' + D.summary.pct;
                    }
                    if (pinIdx !== null && (!D.month_labels || pinIdx >= D.month_labels.length)) { pinIdx = null; }
                    if (yearChart) { buildYear(); }
                    if (monthChart) { buildMonth(); }
                    refreshInsights(true);
                    setFoot();
                }
                async function checkFeed(force) {
                    if (!FEED || (document.hidden && !force)) { return; }
                    lastCheck = Date.now();
                    try {
                        const r = await fetch(FEED + '?province=' + encodeURIComponent(PROV) + '&stamp=' + encodeURIComponent(feedStamp), { headers: { Accept: 'application/json' }, cache: 'no-store', credentials: 'same-origin' });
                        if (!r.ok) { throw new Error(r.status); }
                        const j = await r.json();
                        feedStamp = j.stamp;
                        let fresh = false;
                        if (j.changed && j.trend && JSON.stringify(j.trend) !== JSON.stringify(D)) { applyData(j.trend); fresh = true; }
                        liveNote(true, fresh);
                    } catch (e) { liveNote(false); }
                }
                if (FEED) {
                    setTimeout(function () { checkFeed(true); }, 1500);
                    setInterval(checkFeed, 60000);
                    document.addEventListener('visibilitychange', function () { if (!document.hidden && Date.now() - lastCheck > 15000) { checkFeed(true); } });
                }

                function setFoot() {
                    foot.textContent = state.view === 'year'
                        ? (state.metric === 'rate'
                            ? 'อัตรา = ผู้ป่วยใหม่ ÷ ประชากรเป้าหมาย × 100,000 · แท่งเข้มขึ้นคือปีล่าสุด · เส้นประแดงคืออัตรารวมเขต 10 ไว้เทียบกับแต่ละจังหวัด · ▲▼ ใต้ชื่อจังหวัดคือการเปลี่ยนแปลงจากปีก่อน'
                            : 'แท่งเข้มขึ้นคือปีล่าสุด · ▲▼ ใต้ชื่อจังหวัดคือการเปลี่ยนแปลงจากปีก่อน · ตัวเลขบนแท่งย่อ k = พันคน (ชี้ที่แท่งเพื่อดูตัวเลขเต็ม)')
                        : 'ผู้ป่วยใหม่รายเดือนตามปีงบประมาณ (ต.ค.–ก.ย.) · คลิกชื่อปีเพื่อซ่อน/แสดงและวิเคราะห์ใหม่ · คลิกบนกราฟเพื่อดูรายละเอียดของเดือนนั้น' + (D.last_idx !== null && D.last_idx < 11 ? ' · แถบเทาคือเดือนที่ยังไม่มีข้อมูล' : '');
                }
                function showView(v) {
                    state.view = v;
                    tabs.forEach(function (t) { t.setAttribute('aria-selected', t.dataset.view === v ? 'true' : 'false'); });
                    yearEl.hidden = v !== 'year';
                    monthEl.hidden = v !== 'month';
                    seg.hidden = v !== 'year';
                    if (v === 'year') { if (!yearChart) { buildYear(); } else { yearChart.reflow(); } }
                    else { if (!monthChart) { buildMonth(); } else { monthChart.reflow(); } }
                    setFoot();
                }
                tabs.forEach(function (t) { t.addEventListener('click', function () { showView(t.dataset.view); }); });
                seg.querySelectorAll('button').forEach(function (b) {
                    b.addEventListener('click', function () {
                        state.metric = b.dataset.metric;
                        seg.querySelectorAll('button').forEach(function (x) { x.setAttribute('aria-pressed', x === b ? 'true' : 'false'); });
                        buildYear();
                        setFoot();
                    });
                });

                function reflowAll() {
                    if (yearChart) { yearChart.reflow(); }
                    if (monthChart) { monthChart.reflow(); }
                }
                if (INL) {
                    // ฝังในหน้า: สร้างกราฟทันที และปรับขนาดใหม่เมื่อความกว้างการ์ดเปลี่ยน (เช่น แสดงจากที่ซ่อน / ย่อ-ขยายหน้าต่าง)
                    showView(state.view);
                    if (typeof ResizeObserver !== 'undefined') {
                        let w0 = modal.clientWidth, raf = 0;
                        new ResizeObserver(function () {
                            if (modal.clientWidth === w0) { return; }
                            w0 = modal.clientWidth;
                            cancelAnimationFrame(raf);
                            raf = requestAnimationFrame(reflowAll);
                        }).observe(modal);
                    } else {
                        window.addEventListener('resize', reflowAll);
                    }
                    return;
                }

                let lastFocus = null;
                function openModal() {
                    lastFocus = document.activeElement;
                    modal.hidden = false;
                    document.documentElement.style.overflow = 'hidden';
                    showView(state.view);
                    sheet.focus();
                }
                function closeModal() {
                    modal.hidden = true;
                    document.documentElement.style.overflow = '';
                    if (lastFocus && lastFocus.focus) { lastFocus.focus(); }
                }
                openBtn.addEventListener('click', openModal);
                closeBtn.addEventListener('click', closeModal);
                modal.addEventListener('mousedown', function (e) { if (e.target === modal) { closeModal(); } });
                document.addEventListener('keydown', function (e) {
                    if (modal.hidden) { return; }
                    if (e.key === 'Escape') { e.preventDefault(); closeModal(); return; }
                    if (e.key === 'Tab') { // วนโฟกัสอยู่ใน popup
                        const f = Array.prototype.filter.call(sheet.querySelectorAll('button'), function (el) { return el.offsetParent !== null; });
                        if (!f.length) { return; }
                        const first = f[0], last = f[f.length - 1];
                        if (e.shiftKey && (document.activeElement === first || document.activeElement === sheet)) { e.preventDefault(); last.focus(); }
                        else if (!e.shiftKey && document.activeElement === last) { e.preventDefault(); first.focus(); }
                    }
                });
                window.addEventListener('resize', function () {
                    if (modal.hidden) { return; }
                    if (yearChart) { yearChart.reflow(); }
                    if (monthChart) { monthChart.reflow(); }
                });
            });
        </script>
