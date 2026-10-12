{{--
    รูปภาพประกอบต่อข้อ (สูงสุด 3 รูป) - ใช้ซ้ำทุกข้อในฟอร์ม พชอ.ไต
    $cat = '1'..'7' | '8_1' | '8_2' | '8_3'

    ฟอร์มส่ง:  kidney_images[{cat}][{slot}]        ไฟล์ใหม่ (ถูกย่อในเบราว์เซอร์ก่อน)
               kidney_images_remove[{cat}][{slot}] "1" = ลบรูปเดิมในช่องนั้น
    สคริปต์/สไตล์ด้านล่างโหลดครั้งเดียวต่อหน้า.
--}}
@php $kimgFormAttr = !empty($formId) ? ' form="' . e($formId) . '"' : ''; @endphp
<div class="kimg" data-cat="{{ $cat }}">
    <div class="kimg-head">
        <span class="kimg-title"><i class="fas fa-images"></i> รูปภาพประกอบ</span>
        <span class="kimg-count" data-kimg-count>0/3</span>
        <span class="kimg-hint">สูงสุด 3 รูป · JPG, PNG, WEBP · ย่อขนาดให้อัตโนมัติ</span>
    </div>
    <div class="kimg-grid">
        @for($slot = 1; $slot <= 3; $slot++)
            <div class="kimg-slot" data-slot="{{ $slot }}">
                <input type="file" class="kimg-file" name="kidney_images[{{ $cat }}][{{ $slot }}]" accept="image/*" tabindex="-1" hidden{!! $kimgFormAttr !!}>
                <input type="hidden" class="kimg-remove" name="kidney_images_remove[{{ $cat }}][{{ $slot }}]" value="0"{!! $kimgFormAttr !!}>
                <button type="button" class="kimg-add" aria-label="เพิ่มรูปภาพที่ {{ $slot }}">
                    <i class="fas fa-plus"></i>
                    <span>เพิ่มรูป</span>
                </button>
                <div class="kimg-filled" hidden>
                    <img alt="รูปภาพประกอบที่ {{ $slot }}" loading="lazy">
                    <span class="kimg-badge">ใหม่</span>
                    <span class="kimg-meta"></span>
                    <button type="button" class="kimg-del" aria-label="ลบรูปนี้"><i class="fas fa-xmark"></i></button>
                </div>
                <div class="kimg-busy" hidden><i class="fas fa-circle-notch fa-spin"></i></div>
            </div>
        @endfor
    </div>
    <div class="kimg-msg" role="status" hidden></div>
</div>

@once
<style>
    /* คำตอบ (ซ้าย) + กล่องรูปภาพ (ขวา) อยู่แถวเดียวกัน สูงเท่ากัน */
    .answer-row {
        display: grid;
        grid-template-columns: minmax(0, 1fr) minmax(280px, 36%);
        gap: 14px;
        align-items: stretch;
    }
    .answer-row > .report-textarea { height: 100%; margin-bottom: 0 !important; }
    .kimg {
        display: flex; flex-direction: column; box-sizing: border-box; height: 100%;
        padding: 10px 12px 12px; border: 2px solid #edeff2; border-radius: 15px; background: #fcfdfe;
    }
    .kimg-head { display: flex; align-items: center; flex-wrap: wrap; gap: 6px 10px; margin-bottom: 8px; }
    .kimg-title { font-size: .8rem; font-weight: 700; color: #334155; }
    .kimg-title i { color: #2563eb; margin-right: 4px; }
    .kimg-count {
        font-size: .7rem; font-weight: 800; color: #1d4ed8; background: #dbeafe;
        border-radius: 999px; padding: 1px 9px; line-height: 1.6;
    }
    .kimg-hint { font-size: .68rem; color: #94a3b8; margin-left: auto; white-space: nowrap; }
    .kimg-grid { display: grid; grid-template-columns: repeat(3, minmax(0, 1fr)); grid-template-rows: minmax(96px, 1fr); gap: 8px; flex: 1; min-height: 0; }
    .kimg-slot {
        position: relative; height: 100%; min-height: 96px; border-radius: 12px; overflow: hidden;
        background: #f8fafc; border: 1.5px dashed #cbd5e1; transition: border-color .15s, background .15s;
    }
    .kimg-slot.is-drag { border-color: #2563eb; background: #eff6ff; }
    .kimg-slot.has-image { border-style: solid; border-color: #e2e8f0; background: #0f172a; }
    .kimg-add {
        position: absolute; inset: 0; width: 100%; border: 0; background: transparent; cursor: pointer;
        display: flex; flex-direction: column; align-items: center; justify-content: center; gap: 4px;
        color: #64748b; font: inherit; font-size: .74rem; font-weight: 600;
    }
    .kimg-add i { font-size: 1.05rem; color: #2563eb; }
    .kimg-add:hover { color: #1d4ed8; background: #eff6ff; }
    .kimg-add:focus-visible, .kimg-del:focus-visible { outline: 2px solid #2563eb; outline-offset: -2px; }
    .kimg-slot.has-image .kimg-add { display: none; }
    .kimg-filled { position: absolute; inset: 0; cursor: zoom-in; }
    .kimg-filled[hidden], .kimg-busy[hidden], .kimg-msg[hidden] { display: none; }
    .kimg-filled img { width: 100%; height: 100%; object-fit: contain; display: block; background: #0f172a; }
    .kimg-badge {
        position: absolute; left: 6px; top: 6px; font-size: .62rem; font-weight: 800; letter-spacing: .02em;
        color: #fff; background: #f97316; border-radius: 6px; padding: 1px 7px; display: none;
    }
    .kimg-slot.is-new .kimg-badge { display: inline-block; }
    .kimg-meta {
        position: absolute; left: 0; right: 0; bottom: 0; padding: 14px 8px 4px; font-size: .64rem; font-weight: 600;
        color: #e2e8f0; background: linear-gradient(to top, rgba(15,23,42,.78), transparent);
        white-space: nowrap; overflow: hidden; text-overflow: ellipsis; pointer-events: none;
    }
    .kimg-del {
        position: absolute; right: 6px; top: 6px; width: 26px; height: 26px; border: 0; border-radius: 50%;
        background: rgba(15,23,42,.72); color: #fff; cursor: pointer; font-size: .72rem;
        display: inline-flex; align-items: center; justify-content: center; transition: background .15s;
    }
    .kimg-del:hover { background: #e11d48; }
    .kimg-busy {
        position: absolute; inset: 0; display: flex; align-items: center; justify-content: center;
        background: rgba(248,250,252,.85); color: #2563eb; font-size: 1.2rem;
    }
    .kimg-msg { margin-top: 8px; font-size: .76rem; font-weight: 600; color: #b45309; background: #fffbeb; border-radius: 8px; padding: 6px 10px; }
    .kimg-msg.is-error { color: #be123c; background: #fff1f2; }
    @media (max-width: 1100px) {
        .kimg-hint { display: none; }
        .kimg-add span { font-size: .68rem; }
    }
    /* จอแคบ: กล่องรูปไปอยู่ใต้ช่องคำตอบ */
    @media (max-width: 860px) {
        .answer-row { grid-template-columns: minmax(0, 1fr); }
        .answer-row > .report-textarea { height: auto; }
        .kimg-grid { grid-template-rows: none; }
        .kimg-slot { height: auto; min-height: 0; aspect-ratio: 4 / 3; }
    }

    .kimg-lightbox {
        position: fixed; inset: 0; z-index: 99999; display: none; align-items: center; justify-content: center;
        background: rgba(15,23,42,.88); padding: 24px; cursor: zoom-out;
    }
    .kimg-lightbox.open { display: flex; }
    .kimg-lightbox img { max-width: 100%; max-height: 100%; border-radius: 10px; box-shadow: 0 20px 60px rgba(0,0,0,.5); }
</style>

<div class="kimg-lightbox" id="kimgLightbox" role="dialog" aria-label="ดูรูปภาพ"><img alt=""></div>

<script>
(function () {
    const MAX_EDGE = 1600;          // px ด้านยาวสุดหลังย่อ (ตรงกับฝั่งเซิร์ฟเวอร์)
    const QUALITY = 0.82;
    const KEEP_UNDER = 900 * 1024;  // ไฟล์ที่เล็กกว่านี้และไม่เกิน MAX_EDGE ใช้ต้นฉบับ
    const MAX_SOURCE = 40 * 1024 * 1024; // เกินนี้ไม่รับ (ถอดรหัสในเบราว์เซอร์ไม่ไหว)
    const OK_TYPE = /^image\/(jpeg|png|webp|gif|bmp)$/i;
    const OK_EXT = /\.(jpe?g|png|webp|gif|bmp)$/i;

    const lightbox = document.getElementById('kimgLightbox');
    const lightboxImg = lightbox.querySelector('img');
    lightbox.addEventListener('click', () => lightbox.classList.remove('open'));
    document.addEventListener('keydown', e => { if (e.key === 'Escape') lightbox.classList.remove('open'); });
    function openLightbox(src) { lightboxImg.src = src; lightbox.classList.add('open'); }

    function fmtSize(bytes) {
        if (!bytes) return '';
        return bytes >= 1048576 ? (bytes / 1048576).toFixed(1) + ' MB' : Math.max(1, Math.round(bytes / 1024)) + ' KB';
    }

    function decode(file) {
        if (window.createImageBitmap) {
            return createImageBitmap(file, { imageOrientation: 'from-image' }).catch(() => decodeViaImg(file));
        }
        return decodeViaImg(file);
    }
    function decodeViaImg(file) {
        return new Promise((resolve, reject) => {
            const url = URL.createObjectURL(file);
            const img = new Image();
            img.onload = () => { URL.revokeObjectURL(url); resolve(img); };
            img.onerror = () => { URL.revokeObjectURL(url); reject(new Error('decode')); };
            img.src = url;
        });
    }

    /** ย่อ/แปลงเป็น JPEG ในเบราว์เซอร์ - รองรับรูปจากมือถือขนาดหลายสิบ MB */
    async function prepare(file) {
        if (!(OK_TYPE.test(file.type) || (!file.type && OK_EXT.test(file.name)))) throw new Error('type');
        if (file.size > MAX_SOURCE) throw new Error('size');
        const src = await decode(file);
        const w = src.naturalWidth || src.width, h = src.naturalHeight || src.height;
        const scale = Math.min(1, MAX_EDGE / Math.max(w, h));
        if (scale === 1 && file.size <= KEEP_UNDER && /^image\/(jpeg|png|webp)$/i.test(file.type)) {
            if (src.close) src.close();
            return file;
        }
        const canvas = document.createElement('canvas');
        canvas.width = Math.max(1, Math.round(w * scale));
        canvas.height = Math.max(1, Math.round(h * scale));
        const ctx = canvas.getContext('2d');
        ctx.fillStyle = '#fff';
        ctx.fillRect(0, 0, canvas.width, canvas.height);
        ctx.drawImage(src, 0, 0, canvas.width, canvas.height);
        if (src.close) src.close();
        const blob = await new Promise(res => canvas.toBlob(res, 'image/jpeg', QUALITY));
        if (!blob) throw new Error('encode');
        const base = (file.name || 'photo').replace(/\.[^.]+$/, '');
        return new File([blob], base + '.jpg', { type: 'image/jpeg', lastModified: Date.now() });
    }

    class Uploader {
        constructor(root) {
            this.root = root;
            this.cat = root.dataset.cat;
            this.msg = root.querySelector('.kimg-msg');
            this.countEl = root.querySelector('[data-kimg-count]');
            this.picker = document.createElement('input');
            this.picker.type = 'file'; this.picker.accept = 'image/*'; this.picker.multiple = true; this.picker.hidden = true;
            root.appendChild(this.picker);
            this.slots = [...root.querySelectorAll('.kimg-slot')].map(el => ({
                el, existing: null, removed: false, file: null, objectUrl: null,
                input: el.querySelector('.kimg-file'), removeInput: el.querySelector('.kimg-remove'),
                filled: el.querySelector('.kimg-filled'), img: el.querySelector('.kimg-filled img'),
                meta: el.querySelector('.kimg-meta'), busy: el.querySelector('.kimg-busy'),
            }));
            this.target = 0;
            this.bind();
            this.render();
        }
        bind() {
            this.slots.forEach((s, i) => {
                s.el.querySelector('.kimg-add').addEventListener('click', () => { this.target = i; this.picker.value = ''; this.picker.click(); });
                s.el.querySelector('.kimg-del').addEventListener('click', e => { e.stopPropagation(); this.remove(i); });
                s.filled.addEventListener('click', () => { if (s.img.src) openLightbox(s.img.src); });
                s.el.addEventListener('dragover', e => { e.preventDefault(); s.el.classList.add('is-drag'); });
                s.el.addEventListener('dragleave', () => s.el.classList.remove('is-drag'));
                s.el.addEventListener('drop', e => {
                    e.preventDefault(); s.el.classList.remove('is-drag');
                    this.target = i;
                    this.add([...(e.dataTransfer ? e.dataTransfer.files : [])]);
                });
            });
            this.picker.addEventListener('change', () => this.add([...this.picker.files]));
        }
        say(text, isError) {
            this.msg.textContent = text || '';
            this.msg.hidden = !text;
            this.msg.classList.toggle('is-error', !!isError);
        }
        shown(s) { return !!(s.file || (s.existing && !s.removed)); }
        async add(files) {
            files = files.filter(f => f);
            if (!files.length) return;
            this.say('');
            // ลำดับช่อง: เริ่มจากช่องที่กด แล้ววนไปช่องว่างที่เหลือ
            const order = this.slots.map((_, i) => (this.target + i) % this.slots.length);
            const free = order.filter(i => !this.shown(this.slots[i]) && !this.slots[i].el.classList.contains('is-busy'));
            // ถ้าลากวางลงช่องที่มีรูปอยู่แล้ว (ส่งไฟล์เดียว) ให้แทนที่รูปในช่องนั้น
            let targets = free.slice(0, files.length);
            if (files.length === 1 && this.shown(this.slots[this.target])) targets = [this.target];
            if (!targets.length) { this.say('ครบ 3 รูปแล้ว - ลบรูปเดิมก่อนจึงจะเพิ่มรูปใหม่ได้', false); return; }
            const skipped = files.length - targets.length;
            const errors = [];
            await Promise.all(targets.map(async (idx, k) => {
                const s = this.slots[idx];
                s.el.classList.add('is-busy'); s.busy.hidden = false;
                try {
                    const prepared = await prepare(files[k]);
                    this.setFile(s, prepared);
                } catch (err) {
                    errors.push(err.message === 'size' ? files[k].name + ' ใหญ่เกิน 40 MB'
                        : files[k].name + ' ไม่ใช่รูปภาพที่รองรับ (ใช้ JPG, PNG, WEBP)');
                } finally {
                    s.el.classList.remove('is-busy'); s.busy.hidden = true;
                }
            }));
            this.render();
            const notes = [];
            if (skipped > 0) notes.push('เพิ่มได้อีกไม่เกินที่ว่าง - ข้าม ' + skipped + ' รูป');
            if (errors.length) notes.push(errors.join(' · '));
            if (notes.length) this.say(notes.join(' · '), errors.length > 0);
        }
        setFile(s, file) {
            if (s.objectUrl) URL.revokeObjectURL(s.objectUrl);
            s.file = file;
            s.objectUrl = URL.createObjectURL(file);
            const dt = new DataTransfer();
            dt.items.add(file);
            s.input.files = dt.files;
        }
        clearFile(s) {
            if (s.objectUrl) URL.revokeObjectURL(s.objectUrl);
            s.file = null; s.objectUrl = null;
            s.input.value = '';
        }
        remove(i) {
            const s = this.slots[i];
            if (s.file) {
                this.clearFile(s);          // ยกเลิกรูปใหม่ -> รูปเดิม (ถ้ามี) กลับมาแสดง
            } else if (s.existing) {
                s.removed = true;           // ลบรูปเดิมตอนกดบันทึก
            }
            this.say('');
            this.render();
        }
        render() {
            let count = 0;
            this.slots.forEach(s => {
                const showing = this.shown(s);
                if (showing) count++;
                s.el.classList.toggle('has-image', showing);
                s.el.classList.toggle('is-new', !!s.file);
                s.filled.hidden = !showing;
                if (showing) {
                    s.img.src = s.file ? s.objectUrl : s.existing.url;
                    s.meta.textContent = s.file ? (s.file.name + ' · ' + fmtSize(s.file.size))
                        : ((s.existing.name || '') + (s.existing.size ? ' · ' + fmtSize(s.existing.size) : ''));
                } else {
                    s.img.removeAttribute('src');
                    s.meta.textContent = '';
                }
                s.removeInput.value = (s.existing && s.removed && !s.file) ? '1' : '0';
            });
            this.countEl.textContent = count + '/' + this.slots.length;
            document.dispatchEvent(new CustomEvent('kimg:change'));
        }
        reset() {
            this.slots.forEach(s => { this.clearFile(s); s.existing = null; s.removed = false; });
            this.say('');
            this.render();
        }
        load(items) {
            this.reset();
            (items || []).forEach(it => {
                const s = this.slots[(parseInt(it.slot, 10) || 1) - 1];
                if (s) s.existing = it;
            });
            this.render();
        }
    }

    // สคริปต์นี้อยู่กลางฟอร์ม (ก่อนที่ช่องรูปของข้ออื่นจะถูกอ่านเข้า DOM) จึงสร้างตัวจัดการแบบ lazy
    const uploaders = {};
    let inited = false;
    function init() {
        if (inited) return;
        inited = true;
        document.querySelectorAll('.kimg').forEach(root => { uploaders[root.dataset.cat] = new Uploader(root); });

        // กันกดบันทึกขณะยังย่อภาพไม่เสร็จ / รูปรวมใหญ่เกิน - ใช้ได้ทั้งฟอร์มกรอกรายงานและฟอร์มแก้ไขในหน้ารายละเอียด
        ['kidneyForm', 'kdEditForm'].forEach(formId => {
            const form = document.getElementById(formId);
            if (!form) return;
            form.addEventListener('submit', e => {
                // ขนาดรวมของรูปใหม่ต้องไม่เกินเพดาน post_max_size ของ PHP (ค่าเริ่มต้น 8 MB)
                let total = 0;
                document.querySelectorAll('.kimg-file').forEach(i => { if (i.files[0]) total += i.files[0].size; });
                if (total > 7 * 1024 * 1024) {
                    e.preventDefault();
                    e.stopImmediatePropagation();
                    const root = document.querySelector('.kimg-slot.is-new').closest('.kimg');
                    const m = root.querySelector('.kimg-msg');
                    m.textContent = 'รูปใหม่รวมกัน ' + (total / 1048576).toFixed(1) + ' MB เกิน 7 MB - บันทึกทีละข้อ หรือลบบางรูปออกก่อน';
                    m.hidden = false; m.classList.add('is-error');
                    root.scrollIntoView({ behavior: 'smooth', block: 'center' });
                    return;
                }
                const busy = document.querySelector('.kimg-slot.is-busy');
                if (busy) {
                    e.preventDefault();
                    e.stopImmediatePropagation();
                    const m = busy.closest('.kimg').querySelector('.kimg-msg');
                    m.textContent = 'กำลังเตรียมรูปภาพ กรุณารอสักครู่แล้วกดบันทึกอีกครั้ง';
                    m.hidden = false; m.classList.remove('is-error');
                }
            }, true);
        });
    }
    document.addEventListener('DOMContentLoaded', init);

    // ใช้จากสคริปต์ของฟอร์ม: ล้างตอนเปลี่ยนปี/ไตรมาส/พื้นที่ และเติมรูปที่บันทึกไว้
    // จำนวนช่องที่มีการเปลี่ยนแปลง (เพิ่ม/แทนที่รูปใหม่ หรือลบรูปเดิม) - หน้าแก้ไขใช้เปิดปุ่ม "บันทึกการแก้ไข"
    window.kimgDirtyCount = function () {
        init();
        let n = 0;
        Object.values(uploaders).forEach(u => u.slots.forEach(s => { if (s.file || (s.existing && s.removed)) n++; }));
        return n;
    };
    window.kimgReset = function () { init(); Object.values(uploaders).forEach(u => u.reset()); };
    window.kimgSetImages = function (map) {
        init();
        Object.keys(uploaders).forEach(cat => uploaders[cat].load((map || {})[cat] || []));
    };
})();
</script>
@endonce
