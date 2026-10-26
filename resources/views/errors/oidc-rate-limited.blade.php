<!DOCTYPE html>
<html lang="th">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>ระบบยืนยันตัวตนถูกจำกัดชั่วคราว</title>
    <style>
        body { font-family: 'Sarabun', sans-serif; display: flex; align-items: center;
               justify-content: center; min-height: 100vh; margin: 0;
               background: #f8fafc; color: #1e293b; }
        .card { background: #fff; border-radius: 16px; padding: 40px 48px; max-width: 480px;
                text-align: center; box-shadow: 0 4px 24px rgba(0,0,0,.08); }
        h1 { font-size: 1.5rem; margin-bottom: 8px; color: #0f172a; }
        p  { color: #64748b; line-height: 1.6; }
        .badge { display: inline-block; background: #fef3c7; color: #92400e;
                 border-radius: 99px; padding: 4px 16px; font-size: .85rem;
                 font-weight: 700; margin-top: 16px; }
        .retry-btn { display: inline-block; margin-top: 24px; padding: 10px 28px;
                     background: #2563eb; color: #fff; border-radius: 8px;
                     text-decoration: none; font-weight: 600; font-size: .95rem; }
    </style>
    @if($retryAfter > 0)
    <meta http-equiv="refresh" content="{{ $retryAfter + 3 }}">
    @endif
</head>
<body>
    <div class="card">
        <h1>⏳ กรุณารอสักครู่</h1>
        <p>ระบบยืนยันตัวตนกำลังรับคำขอจำนวนมาก กรุณารอ
           <strong>{{ $retryAfter }}</strong> วินาที แล้วลองใหม่อีกครั้ง</p>
        <div class="badge">Rate-limited (429) — ไม่ได้ออกจากระบบ</div>
        <br>
        <a href="{{ url()->current() }}" class="retry-btn">ลองใหม่</a>
    </div>
</body>
</html>
