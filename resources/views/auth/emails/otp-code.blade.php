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

        .otp-box {
            text-align: center;
            margin: 40px 0;
        }

        .otp-code {
            display: inline-block;
            padding: 18px 32px;
            background-color: #fff1f5;
            border: 2px dashed #f06292;
            border-radius: 20px;
            color: #f06292;
            font-size: 2.2rem;
            font-weight: 800;
            letter-spacing: 10px;
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
            <h1>รหัส OTP สำหรับสร้างรหัสผ่านใหม่</h1>
        </div>
        <div class="content">
            <p>สวัสดีครับ,</p>
            <p>คุณได้รับอีเมลนี้เนื่องจากมีการร้องขอสร้างรหัสผ่านใหม่สำหรับบัญชีของคุณในระบบ <strong>Salt & Sodium Smart
                    Monitor</strong></p>
            <p>กรุณากรอกรหัส OTP ด้านล่างนี้ในหน้าที่ระบบแสดงไว้ เพื่อยืนยันตัวตนก่อนตั้งรหัสผ่านใหม่</p>
            <div class="otp-box">
                <span class="otp-code">{{ $otp }}</span>
            </div>
            <p style="text-align: center; color: #ef4444; font-weight: 700;">รหัสนี้จะหมดอายุใน 5 นาที</p>
            <p>หากคุณไม่ได้ร้องขอเปลี่ยนรหัสผ่าน ไม่ต้องดำเนินการใดๆ ครับ และไม่ควรแชร์รหัสนี้ให้ผู้อื่น</p>
        </div>
        <div class="footer">
            <p>ระบบ Salt & Sodium Smart Monitor<br>สำนักงานป้องกันควบคุมโรคที่ ๑๐ จังหวัดอุบลราชธานี</p>
        </div>
    </div>
</body>

</html>
