def apply(text, old, new, label, count=1):
    n = text.count(old)
    assert n == count, f"{label}: expected {count} occurrence(s), found {n}"
    return text.replace(old, new, count)

path = "resources/views/layouts/admin.blade.php"
with open(path, encoding="utf-8") as f:
    c = f.read()

# ---------------------------------------------------------------
# 0. Load Sarabun alongside the existing Kanit / IBM Plex Sans Thai
#    families, so the sidebar menu can use it without falling back
#    to a system default.
# ---------------------------------------------------------------
old_font_url = (
    "family=Kanit:wght@300;400;500;600;700;800&family=IBM+Plex+Sans+Thai:"
    "wght@100;200;300;400;500;600;700&display=swap"
)
new_font_url = (
    "family=Kanit:wght@300;400;500;600;700;800&family=IBM+Plex+Sans+Thai:"
    "wght@100;200;300;400;500;600;700&family=Sarabun:wght@400;500;600;700;800&display=swap"
)
c = apply(c, old_font_url, new_font_url, "load Sarabun webfont")

# ---------------------------------------------------------------
# 1. Sidebar menu text: back to its original size, switched to
#    Sarabun, and bolder.
# ---------------------------------------------------------------
old_section = """        .nav-section-title {
            padding: 22px 12px 10px 12px;
            font-size: 0.78rem;
            font-weight: 700;
            color: #94a3b8;
            text-transform: uppercase;
            letter-spacing: 0.9px;
            display: flex;
            align-items: center;
            gap: 8px;
        }"""

new_section = """        .nav-section-title {
            padding: 22px 12px 10px 12px;
            font-size: 0.72rem;
            font-weight: 800;
            font-family: 'Sarabun', sans-serif;
            color: #94a3b8;
            text-transform: uppercase;
            letter-spacing: 0.9px;
            display: flex;
            align-items: center;
            gap: 8px;
        }"""

c = apply(c, old_section, new_section, "revert size + Sarabun + bolder nav section title")

old_link = """        .nav-link {
            display: flex;
            align-items: center;
            gap: 10px;
            padding: 10px 12px;
            color: var(--sidebar-text);
            text-decoration: none !important;
            border-radius: 12px;
            font-weight: 500;
            line-height: 1.35;
            transition: background 0.2s ease, color 0.2s ease, transform 0.2s ease, box-shadow 0.2s ease;
            position: relative;
        }"""

new_link = """        .nav-link {
            display: flex;
            align-items: center;
            gap: 10px;
            padding: 10px 12px;
            color: var(--sidebar-text);
            text-decoration: none !important;
            border-radius: 12px;
            font-weight: 600;
            font-family: 'Sarabun', sans-serif;
            line-height: 1.35;
            transition: background 0.2s ease, color 0.2s ease, transform 0.2s ease, box-shadow 0.2s ease;
            position: relative;
        }"""

c = apply(c, old_link, new_link, "Sarabun + bolder nav link")

old_link_span = """        .nav-link span:not(.nav-badge) {
            font-size: 0.94rem;
            flex: 1;
            min-width: 0;
        }"""

new_link_span = """        .nav-link span:not(.nav-badge) {
            font-size: 0.85rem;
            flex: 1;
            min-width: 0;
        }"""

c = apply(c, old_link_span, new_link_span, "revert nav link label size")

old_logout = """        .btn-logout {
            width: 100%;
            padding: 11px;
            border-radius: 12px;
            border: none;
            background: #fef2f2;
            color: #ef4444;
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 10px;
            cursor: pointer;
            font-weight: 600;
            transition: all 0.2s;
            font-size: 0.92rem;
        }"""

new_logout = """        .btn-logout {
            width: 100%;
            padding: 11px;
            border-radius: 12px;
            border: none;
            background: #fef2f2;
            color: #ef4444;
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 10px;
            cursor: pointer;
            font-weight: 700;
            font-family: 'Sarabun', sans-serif;
            transition: all 0.2s;
            font-size: 0.85rem;
        }"""

c = apply(c, old_logout, new_logout, "revert size + Sarabun + bolder logout button")

# ---------------------------------------------------------------
# 2. Notification badge: back to its original (smaller) size too.
# ---------------------------------------------------------------
old_badge = """        .nav-badge {
            background-color: #ef4444;
            color: white;
            font-size: 0.7rem;
            font-weight: 700;
            padding: 2px 7px;
            border-radius: 10px;
            min-width: 19px;
            height: 19px;
            text-align: center;
            display: flex;
            align-items: center;
            justify-content: center;
            font-family: 'Inter', sans-serif;
            flex-shrink: 0;
            margin-left: 8px;
        }"""

new_badge = """        .nav-badge {
            background-color: #ef4444;
            color: white;
            font-size: 0.65rem;
            font-weight: 700;
            padding: 2px 7px;
            border-radius: 10px;
            min-width: 18px;
            height: 18px;
            text-align: center;
            display: flex;
            align-items: center;
            justify-content: center;
            font-family: 'Inter', sans-serif;
            flex-shrink: 0;
            margin-left: 8px;
        }"""

c = apply(c, old_badge, new_badge, "revert badge size")

with open(path, "w", encoding="utf-8") as f:
    f.write(c)

print("patched ok")
