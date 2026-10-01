<!DOCTYPE html>
<html>

<head>
    <style>
        body {
            font-family: 'IBM Plex Sans Thai', sans-serif;
            line-height: 1.6;
            color: #334155;
        }

        .container {
            max-width: 600px;
            margin: 0 auto;
            padding: 20px;
            border: 1px solid #e2e8f0;
            border-radius: 20px;
        }

        .header {
            text-align: center;
            margin-bottom: 30px;
        }

        .header h1 {
            color: #f06292;
        }

        .content {
            margin-bottom: 30px;
        }

        .btn {
            display: inline-block;
            padding: 14px 28px;
            background-color: #f06292;
            color: #ffffff !important;
            text-decoration: none;
            border-radius: 50px;
            font-weight: bold;
        }

        .footer {
            font-size: 0.85rem;
            color: #64748b;
            text-align: center;
            border-top: 1px solid #e2e8f0;
            padding-top: 20px;
        }
    </style>
</head>

<body>
    <div class="container">
        <div class="header">
            <h1>สร้างรหัสผ่านใหม่</h1>
        </div>
        <div class="content">
            <p>สวัสดีครับ,</p>
            <p>คุณได้รับอีเมลนี้เนื่องจากมีการร้องขอสร้างรหัสผ่านใหม่สำหรับบัญชีของคุณในระบบ <strong>Salt & Sodium Smart
                    Monitor</strong></p>
            <p style="text-align: center; margin: 40px 0;">
                <a href="{{ route('password.reset', ['token' => $token, 'email' => $email]) }}"
                    class="btn">คลิกที่นี่เพื่อสร้างรหัสผ่านใหม่</a>
            </p>
            <p>ลิงก์นี้จะมีอายุการใช้งาน 60 นาที</p>
            <p>หากคุณไม่ได้ร้องขอเปลี่ยนรหัสผ่าน ไม่ต้องดำเนินการใดๆ ครับ</p>
        </div>
        <div class="footer">
            <p>ระบบ Salt & Sodium Smart Monitor<br>สำนักงานป้องกันควบคุมโรคที่ ๙ นครราชสีมา</p>
        </div>
    </div>
</body>

</html>