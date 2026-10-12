@extends('layouts.admin')

@section('title', 'บันทึกการใช้งานระบบ')

@section('header_title')
    <div class="page-header">
        <div class="page-header-accent"></div>
        <div class="page-header-text">
            <span class="page-header-title">บันทึกการใช้งานระบบ</span>
            <small class="page-header-subtitle">
                <i class="fas fa-circle-info"></i>
                ประวัติการเข้าใช้งานและการแก้ไขข้อมูลสำคัญ เก็บย้อนหลัง {{ \App\Support\ActivityLog::RETENTION_DAYS }} วัน
            </small>
        </div>
    </div>
@endsection

@section('content')
    @php
        $thMonths = ['ม.ค.', 'ก.พ.', 'มี.ค.', 'เม.ย.', 'พ.ค.', 'มิ.ย.', 'ก.ค.', 'ส.ค.', 'ก.ย.', 'ต.ค.', 'พ.ย.', 'ธ.ค.'];
        $thDate = function ($value) use ($thMonths) {
            if (!$value) {
                return '-';
            }
            // เวลาใน activity_logs เก็บเป็น UTC (ค่า timezone ของแอปคือ UTC) - แสดงเป็นเวลาประเทศไทย
            $d = \Illuminate\Support\Carbon::parse($value, 'UTC')->setTimezone('Asia/Bangkok');
            return $d->day . ' ' . $thMonths[$d->month - 1] . ' ' . ($d->year + 543) . ' ' . $d->format('H:i');
        };
        $rankLabels = [1 => 'สคร.', 2 => 'สสจ.', 3 => 'สสอ.', 4 => 'รพ.สต.', 5 => 'รพ.'];
    @endphp

    <style>
        .al-font { font-family: 'Noto Serif Thai', 'TH Sarabun New', 'TH Sarabun PS', sans-serif; }
        .al-back { display: inline-flex; align-items: center; gap: 8px; margin-bottom: 14px; font-size: .82rem; font-weight: 700; color: #4f46e5; text-decoration: none; }
        .al-back:hover { text-decoration: underline; color: #3730a3; }
        .al-stats { display: grid; grid-template-columns: repeat(3, minmax(0, 1fr)); gap: 12px; margin-bottom: 18px; }
        .al-stat { background: #fff; border: 1px solid #eef2f7; border-radius: 14px; padding: 14px 18px; box-shadow: 0 4px 16px rgba(15, 23, 42, .05); }
        .al-stat-num { font-size: 1.5rem; font-weight: 800; color: #1f2937; line-height: 1.1; font-variant-numeric: tabular-nums; }
        .al-stat-label { font-size: .78rem; color: #6b7280; margin-top: 2px; }
        .al-stat.is-warn .al-stat-num { color: #b91c1c; }
        .al-card { background: #fff; border: 1px solid #eef2f7; border-radius: 16px; box-shadow: 0 4px 16px rgba(15, 23, 42, .06); overflow: hidden; }
        .al-tabs { display: flex; gap: 6px; padding: 12px 16px 0; border-bottom: 1px solid #f1f5f9; }
        .al-tab { padding: 10px 16px; font-size: .85rem; font-weight: 700; color: #6b7280; text-decoration: none; border-bottom: 3px solid transparent; margin-bottom: -1px; }
        .al-tab:hover { color: #4338ca; }
        .al-tab.is-active { color: #4338ca; border-bottom-color: #4f46e5; }
        .al-filter { display: flex; flex-wrap: wrap; gap: 10px; align-items: flex-end; padding: 14px 16px; border-bottom: 1px solid #f1f5f9; background: #fafbff; }
        .al-filter label { display: block; font-size: .72rem; font-weight: 700; color: #6b7280; margin-bottom: 3px; }
        .al-filter input, .al-filter select { height: 38px; padding: 0 10px; border: 1px solid #d1d5db; border-radius: 10px; font-size: .82rem; background: #fff; color: #1f2937; }
        .al-filter input[type=text] { min-width: 220px; }
        .al-btn { height: 38px; padding: 0 16px; border: 0; border-radius: 10px; background: #4f46e5; color: #fff; font-size: .82rem; font-weight: 700; cursor: pointer; }
        .al-btn:hover { background: #4338ca; }
        .al-btn-ghost { display: inline-flex; align-items: center; height: 38px; padding: 0 14px; border-radius: 10px; border: 1px solid #d1d5db; background: #fff; color: #4b5563; font-size: .82rem; font-weight: 700; text-decoration: none; }
        .al-table { width: 100%; border-collapse: collapse; }
        .al-table th { text-align: left; padding: 10px 16px; font-size: .72rem; font-weight: 700; color: #6b7280; background: #f8fafc; border-bottom: 1px solid #eef2f7; white-space: nowrap; }
        .al-table td { padding: 12px 16px; font-size: .82rem; color: #1f2937; border-bottom: 1px solid #f1f5f9; vertical-align: top; }
        .al-table tr:last-child td { border-bottom: 0; }
        .al-muted { color: #6b7280; font-size: .75rem; }
        .al-time { white-space: nowrap; font-variant-numeric: tabular-nums; }
        .al-badge { display: inline-block; padding: 3px 10px; border-radius: 999px; font-size: .72rem; font-weight: 700; white-space: nowrap; }
        .al-b-green { background: #dcfce7; color: #166534; }
        .al-b-gray { background: #f1f5f9; color: #475569; }
        .al-b-red { background: #fee2e2; color: #991b1b; }
        .al-b-amber { background: #fef3c7; color: #92400e; }
        .al-b-blue { background: #dbeafe; color: #1e40af; }
        .al-b-indigo { background: #e0e7ff; color: #3730a3; }
        .al-b-purple { background: #f3e8ff; color: #6b21a8; }
        .al-rank { display: inline-block; margin-left: 6px; padding: 1px 8px; border-radius: 999px; background: #eef2ff; color: #4338ca; font-size: .68rem; font-weight: 700; }
        .al-details summary { cursor: pointer; font-size: .78rem; font-weight: 700; color: #4f46e5; list-style: none; }
        .al-details summary::-webkit-details-marker { display: none; }
        .al-details summary::before { content: '▸ '; }
        .al-details[open] summary::before { content: '▾ '; }
        .al-recent { margin: 8px 0 0; padding: 0; list-style: none; }
        .al-recent li { display: flex; flex-wrap: wrap; gap: 4px 14px; padding: 5px 0; border-top: 1px dashed #e5e7eb; font-size: .76rem; color: #374151; }
        .al-recent li:first-child { border-top: 0; }
        .al-fail { margin-left: 8px; padding: 2px 8px; border-radius: 999px; background: #fee2e2; color: #991b1b; font-size: .68rem; font-weight: 700; }
        .al-empty { padding: 40px 16px; text-align: center; color: #9ca3af; font-size: .85rem; }
        .al-pager { padding: 12px 16px; display: flex; justify-content: center; }
        @media (max-width: 760px) {
            .al-stats { grid-template-columns: 1fr; }
            .al-filter input[type=text] { min-width: 0; width: 100%; }
            .al-table-wrap { overflow-x: auto; }
            /* ตารางผู้ใช้งานบนมือถือ: เรียงแต่ละคนเป็นการ์ดแนวตั้ง ไม่ต้องเลื่อนซ้าย-ขวา */
            .al-table.is-users thead { display: none; }
            .al-table.is-users, .al-table.is-users tbody, .al-table.is-users tr, .al-table.is-users td { display: block; width: 100%; }
            .al-table.is-users tr { padding: 10px 0; border-bottom: 1px solid #f1f5f9; }
            .al-table.is-users td { border-bottom: 0; padding: 4px 16px; }
            .al-table.is-users td.al-time { white-space: normal; }
        }
    </style>

    <div class="al-font">
        <a class="al-back" href="{{ route('admin.system-management.index') }}"><i class="fas fa-arrow-left"></i> กลับไปหน้าจัดการระบบ</a>

        <div class="al-stats">
            <div class="al-stat">
                <div class="al-stat-num">{{ number_format($stats['total']) }}</div>
                <div class="al-stat-label">เหตุการณ์ที่บันทึกไว้ทั้งหมด</div>
            </div>
            <div class="al-stat">
                <div class="al-stat-num">{{ number_format($stats['logins_today']) }}</div>
                <div class="al-stat-label">เข้าสู่ระบบสำเร็จวันนี้ (ครั้ง)</div>
            </div>
            <div class="al-stat {{ $stats['failed_24h'] > 0 ? 'is-warn' : '' }}">
                <div class="al-stat-num">{{ number_format($stats['failed_24h']) }}</div>
                <div class="al-stat-label">เข้าสู่ระบบ/กรอก OTP ไม่สำเร็จ ใน 24 ชั่วโมงที่ผ่านมา</div>
            </div>
        </div>

        <div class="al-card">
            <div class="al-tabs">
                <a class="al-tab {{ $tab === 'users' ? 'is-active' : '' }}" href="{{ route('admin.system-management.activity-logs', ['tab' => 'users']) }}">ผู้ใช้งานแต่ละคน</a>
                <a class="al-tab {{ $tab === 'events' ? 'is-active' : '' }}" href="{{ route('admin.system-management.activity-logs', ['tab' => 'events']) }}">เหตุการณ์ทั้งหมด</a>
            </div>

            <form class="al-filter" id="alFilterForm" method="GET" action="{{ route('admin.system-management.activity-logs') }}">
                <input type="hidden" name="tab" value="{{ $tab }}">
                <div>
                    <label for="al-q">{{ $tab === 'users' ? 'ค้นหาชื่อ / อีเมล' : 'ค้นหาชื่อ / อีเมล / รายละเอียด / IP' }}</label>
                    <input type="text" id="al-q" name="q" value="{{ $q }}" placeholder="พิมพ์คำค้นหา">
                </div>
                @if ($tab === 'events')
                    <div>
                        <label for="al-event">ประเภทเหตุการณ์</label>
                        <select id="al-event" name="event">
                            <option value="">ทั้งหมด</option>
                            @foreach (\App\Support\ActivityLog::EVENTS as $key => $meta)
                                <option value="{{ $key }}" {{ $event === $key ? 'selected' : '' }}>{{ $meta[0] }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div>
                        <label for="al-from">ตั้งแต่วันที่</label>
                        <input type="date" id="al-from" name="from" value="{{ $from }}">
                    </div>
                    <div>
                        <label for="al-to">ถึงวันที่</label>
                        <input type="date" id="al-to" name="to" value="{{ $to }}">
                    </div>
                @endif
                <noscript><button type="submit" class="al-btn"><i class="fas fa-magnifying-glass"></i> ค้นหา</button></noscript>
                <a class="al-btn-ghost" href="{{ route('admin.system-management.activity-logs', ['tab' => $tab]) }}">ล้างตัวกรอง</a>
            </form>

            @if ($tab === 'users')
                <div class="al-table-wrap">
                    <table class="al-table is-users">
                        <thead>
                            <tr>
                                <th>ผู้ใช้งาน</th>
                                <th>เข้าใช้ล่าสุด</th>
                                <th>การเข้าใช้สำเร็จ 5 ครั้งล่าสุด</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse ($users as $u)
                                @php($recent = $recentLogins[$u->id] ?? collect())
                                <tr>
                                    <td>
                                        <strong>{{ $u->name ?: $u->email }}</strong>
                                        <span class="al-rank">{{ $rankLabels[(int) $u->User_rank_id] ?? '-' }}</span>
                                        <div class="al-muted">{{ $u->email }}</div>
                                    </td>
                                    <td class="al-time">
                                        @if ($u->last_login_at)
                                            {{ $thDate($u->last_login_at) }}
                                        @else
                                            <span class="al-muted">ยังไม่เคยเข้าใช้</span>
                                        @endif
                                        @if (!empty($failed7d[$u->id]))
                                            <span class="al-fail" title="ไม่สำเร็จใน 7 วันที่ผ่านมา">ไม่สำเร็จ {{ $failed7d[$u->id] }} ครั้ง / 7 วัน</span>
                                        @endif
                                    </td>
                                    <td>
                                        @if ($recent->isEmpty())
                                            <span class="al-muted">-</span>
                                        @else
                                            <details class="al-details">
                                                <summary>ดู {{ $recent->count() }} ครั้งล่าสุด</summary>
                                                <ul class="al-recent">
                                                    @foreach ($recent as $r)
                                                        <li>
                                                            <span class="al-time">{{ $thDate($r->created_at) }}</span>
                                                            <span>IP {{ $r->ip ?: '-' }}</span>
                                                            <span class="al-muted">{{ \App\Support\ActivityLog::device($r->user_agent) }}</span>
                                                        </li>
                                                    @endforeach
                                                </ul>
                                            </details>
                                        @endif
                                    </td>
                                </tr>
                            @empty
                                <tr><td colspan="3" class="al-empty">ไม่พบผู้ใช้งานตามเงื่อนไขที่ค้นหา</td></tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
                @if ($users->hasPages())
                    <div class="al-pager">{{ $users->links('pagination::bootstrap-4') }}</div>
                @endif
            @else
                <div class="al-table-wrap">
                    <table class="al-table is-events">
                        <thead>
                            <tr>
                                <th>เวลา</th>
                                <th>ผู้ใช้งาน</th>
                                <th>เหตุการณ์</th>
                                <th>รายละเอียด</th>
                                <th>IP / อุปกรณ์</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse ($logs as $log)
                                @php($ev = \App\Support\ActivityLog::EVENTS[$log->event] ?? [$log->event, 'gray'])
                                <tr>
                                    <td class="al-time">{{ $thDate($log->created_at) }}</td>
                                    <td>
                                        <strong>{{ $log->actor_name ?: '-' }}</strong>
                                        @if ($log->user_rank)
                                            <span class="al-rank">{{ $rankLabels[(int) $log->user_rank] ?? '-' }}</span>
                                        @endif
                                        @if ($log->user_email)
                                            <div class="al-muted">{{ $log->user_email }}</div>
                                        @endif
                                    </td>
                                    <td><span class="al-badge al-b-{{ $ev[1] }}">{{ $ev[0] }}</span></td>
                                    <td>{{ $log->description ?: '-' }}</td>
                                    <td>
                                        <div>{{ $log->ip ?: '-' }}</div>
                                        <div class="al-muted">{{ \App\Support\ActivityLog::device($log->user_agent) }}</div>
                                    </td>
                                </tr>
                            @empty
                                <tr><td colspan="5" class="al-empty">ยังไม่มีเหตุการณ์ที่ตรงกับเงื่อนไข</td></tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
                @if ($logs->hasPages())
                    <div class="al-pager">{{ $logs->links('pagination::bootstrap-4') }}</div>
                @endif
            @endif
        </div>
    </div>

    <script>
        // ค้นหาอัตโนมัติ: เลือกตัวกรอง/วันที่แล้วแสดงผลทันที พิมพ์คำค้นหาแล้วรอ 0.4 วินาทีค่อยค้น (ไม่ต้องกดปุ่ม)
        (function () {
            var form = document.getElementById('alFilterForm');
            if (!form) return;
            var q = document.getElementById('al-q');
            var timer = null;

            function submitNow() {
                clearTimeout(timer);
                try { sessionStorage.setItem('alFocusQ', document.activeElement === q ? '1' : ''); } catch (e) {}
                form.submit();
            }

            if (q) {
                q.addEventListener('input', function () {
                    clearTimeout(timer);
                    timer = setTimeout(submitNow, 400);
                });
                q.addEventListener('keydown', function (e) {
                    if (e.key === 'Enter') { e.preventDefault(); submitNow(); }
                });
                try {
                    if (sessionStorage.getItem('alFocusQ') === '1') {
                        sessionStorage.removeItem('alFocusQ');
                        q.focus();
                        q.setSelectionRange(q.value.length, q.value.length);
                    }
                } catch (e) {}
            }

            form.querySelectorAll('select, input[type=date]').forEach(function (el) {
                el.addEventListener('change', submitNow);
            });
        })();
    </script>
@endsection
