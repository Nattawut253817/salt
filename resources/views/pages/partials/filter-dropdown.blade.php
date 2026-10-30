{{-- เมนูตัวกรองแบบกำหนดเอง: ครอบ <select> ตัวกรองเดิม (ค่า, name, onchange ยังทำงานตามเดิม)
     ใช้กับ select ที่มี class: awr-filter-select, cr-select, filter-pill-select --}}
<style>
    .hf-host { position: relative; }
    .hf-host.hf-enhanced select.hf-source { display: none !important; }

    .hf-trigger {
        flex: 1 1 0;
        min-width: 0;
        display: block;
        padding: 0;
        border: none;
        background: transparent;
        font-family: 'Sarabun', sans-serif;
        font-weight: 700;
        font-size: .85rem;
        color: #1a202c;
        text-align: left;
        cursor: pointer;
        overflow: visible;
        white-space: nowrap;
        outline: none;
    }

    .hf-trigger:disabled { cursor: not-allowed; opacity: .7; }

    .hf-menu {
        position: absolute;
        left: 0;
        top: calc(100% + 10px);
        z-index: 1050;
        min-width: max(100%, 190px);
        max-height: 300px;
        overflow-y: auto;
        margin: 0;
        padding: 8px;
        list-style: none;
        background: #ffffff;
        border: 1px solid #e3eaf1;
        border-radius: 18px;
        box-shadow: 0 18px 40px -16px rgba(51, 85, 105, .4), 0 4px 10px rgba(51, 85, 105, .08);
        opacity: 0;
        transform: translateY(-6px);
        visibility: hidden;
        transition: opacity .16s ease, transform .16s ease, visibility .16s;
    }

    .hf-menu.is-open { opacity: 1; transform: none; visibility: visible; }

    .hf-option {
        display: flex;
        align-items: center;
        justify-content: space-between;
        gap: 14px;
        padding: 11px 16px 11px 20px;
        border-radius: 12px;
        font-family: 'Sarabun', sans-serif;
        font-size: .92rem;
        font-weight: 600;
        line-height: 1.4;
        color: #334155;
        cursor: pointer;
        white-space: nowrap;
    }

    .hf-option + .hf-option { margin-top: 2px; }
    .hf-option:hover, .hf-option.is-active { background: #eaf1f6; color: #1e293b; }
    .hf-option[aria-selected="true"] { background: #55707a; color: #ffffff; }
    .hf-option[aria-selected="true"]::after {
        content: "\f00c";
        font-family: "Font Awesome 5 Free", "Font Awesome 6 Free", FontAwesome;
        font-weight: 900;
        font-size: .7rem;
    }

    .hf-host > .fa-chevron-down { transition: transform .2s ease; }
    .hf-host.hf-open > .fa-chevron-down { transform: rotate(180deg); }
</style>
<script>
    (function () {
        function closeAll(except) {
            document.querySelectorAll('.hf-menu.is-open').forEach(function (m) {
                if (m !== except) {
                    m.classList.remove('is-open');
                    m.parentNode.classList.remove('is-open');
                    m.parentNode.classList.remove('hf-open');
                    m._btn.setAttribute('aria-expanded', 'false');
                }
            });
        }

        function enhance(sel) {
            if (sel.multiple || sel.dataset.hfDone) return;
            sel.dataset.hfDone = '1';
            var host = sel.parentElement;
            var btn = document.createElement('button');
            var menu = document.createElement('ul');
            var opts = [];
            var active = 0;

            btn.type = 'button';
            btn.className = 'hf-trigger';
            btn.setAttribute('aria-haspopup', 'listbox');
            btn.setAttribute('aria-expanded', 'false');
            btn.disabled = sel.disabled;
            menu.className = 'hf-menu';
            menu.setAttribute('role', 'listbox');
            menu._btn = btn;
            menu.addEventListener('click', function (e) { e.preventDefault(); e.stopPropagation(); });

            function build() {
                menu.innerHTML = '';
                opts = [];
                Array.prototype.forEach.call(sel.options, function (o, i) {
                    var li = document.createElement('li');
                    li.className = 'hf-option';
                    li.setAttribute('role', 'option');
                    li.textContent = o.textContent.trim();
                    li.addEventListener('click', function (e) {
                        e.preventDefault();
                        e.stopPropagation();
                        choose(i);
                    });
                    menu.appendChild(li);
                    opts.push(li);
                });
                paint();
            }

            function paint() {
                var cur = opts[sel.selectedIndex];
                btn.textContent = cur ? cur.textContent : '';
                opts.forEach(function (li, i) {
                    li.setAttribute('aria-selected', i === sel.selectedIndex ? 'true' : 'false');
                    li.classList.toggle('is-active', i === active);
                });
            }

            function open() {
                if (btn.disabled) return;
                closeAll(menu);
                active = sel.selectedIndex;
                paint();
                menu.classList.add('is-open');
                host.classList.add('hf-open');
                btn.setAttribute('aria-expanded', 'true');
                var cur = opts[active];
                if (cur) menu.scrollTop = Math.max(0, cur.offsetTop - 60);
            }

            function close() {
                menu.classList.remove('is-open');
                host.classList.remove('hf-open');
                btn.setAttribute('aria-expanded', 'false');
            }

            function choose(i) {
                close();
                if (i !== sel.selectedIndex) {
                    sel.selectedIndex = i;
                    paint();
                    sel.dispatchEvent(new Event('change', { bubbles: true }));
                }
                btn.focus();
            }

            btn.addEventListener('click', function (e) {
                e.stopPropagation();
                menu.classList.contains('is-open') ? close() : open();
            });

            btn.addEventListener('keydown', function (e) {
                var isOpen = menu.classList.contains('is-open');
                if (e.key === 'ArrowDown' || e.key === 'ArrowUp') {
                    e.preventDefault();
                    if (!isOpen) { open(); return; }
                    active = Math.min(opts.length - 1, Math.max(0, active + (e.key === 'ArrowDown' ? 1 : -1)));
                    paint();
                    opts[active].scrollIntoView({ block: 'nearest' });
                } else if ((e.key === 'Enter' || e.key === ' ') && isOpen) {
                    e.preventDefault();
                    choose(active);
                } else if (e.key === 'Escape' && isOpen) {
                    e.preventDefault();
                    close();
                }
            });

            sel.classList.add('hf-source');
            host.classList.add('hf-host', 'hf-enhanced');
            sel.parentNode.insertBefore(btn, sel);
            host.appendChild(menu);
            build();

            // ถ้า option ถูกเพิ่ม/เปลี่ยนด้วย JS ให้สร้างรายการใหม่
            new MutationObserver(build).observe(sel, { childList: true });
            sel.addEventListener('change', paint);
        }

        function init() {
            document.querySelectorAll('select.awr-filter-select, select.cr-select, select.filter-pill-select')
                .forEach(enhance);
        }

        document.addEventListener('click', function () { closeAll(null); });
        if (document.readyState === 'loading') document.addEventListener('DOMContentLoaded', init);
        else init();
    })();
</script>
