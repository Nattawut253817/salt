@extends('layouts.layout')

@section('title', 'พชอ.ไต - Salt & Sodium Smart Monitor')
@section('header_title', 'แบบรายงานความก้าวหน้าผลการดำเนินงานป้องกันควบคุมโรคไม่ติดต่อเรื้อรัง/โรคไตในชุมชนผ่านกลไก
คณะกรรมการพัฒนาคุณภาพชีวิตระดับอำเภอ(พชอ.) 
')

@section('content')
    <div class="card">
        <div class="card-title">
            <i class="fas fa-clipboard-list"></i> ความก้าวหน้าและผลการดำเนินงาน
        </div>

        <form method="POST" action="#" style="padding: 15px;">
            @csrf

            <!-- Category 1 -->
            <div class="form-section">
                <label class="form-label">
                    <span class="category-number">1</span>
                    การขับเคลื่อนการดำเนินงานNCD (ระดับอำเภอ)
                </label>
                <textarea class="form-textarea" name="category_1" rows="5"
                    placeholder="กรุณากรอกรายละเอียดการขับเคลื่อนการดำเนินงานป้องกันควบคุมโรคไม่ติดต่อเรื้อรังในระดับอำเภอ"></textarea>
            </div>

            <!-- Category 2 -->
            <div class="form-section">
                <label class="form-label">
                    <span class="category-number">2</span>
                    การจัดการข้อมูลเฝ้าระวัง
                </label>
                <textarea class="form-textarea" name="category_2" rows="5"
                    placeholder="กรุณากรอกรายละเอียดการจัดการข้อมูลเฝ้าระวังโรคไม่ติดต่อเรื้อรังและโรคไต"></textarea>
            </div>

            <!-- Category 3 -->
            <div class="form-section">
                <label class="form-label">
                    <span class="category-number">3</span>
                    การกำหนดประเด็นปัญหา เป้าหมาย พร้อมทั้งแผนงานและกิจกรรม
                </label>
                <textarea class="form-textarea" name="category_3" rows="5"
                    placeholder="กรุณากรอกรายละเอียดการกำหนดประเด็นปัญหา เป้าหมาย แผนงาน และกิจกรรมการดำเนินงาน"></textarea>
            </div>

            <!-- Category 4 -->
            <div class="form-section">
                <label class="form-label">
                    <span class="category-number">4</span>
                    การสนับสนุนการสร้างนโยบายสาธารณะ
                </label>
                <textarea class="form-textarea" name="category_4" rows="5"
                    placeholder="กรุณากรอกรายละเอียดการสนับสนุนการสร้างนโยบายสาธารณะด้านการป้องกันควบคุมโรค"></textarea>
            </div>

            <!-- Category 5 -->
            <div class="form-section">
                <label class="form-label">
                    <span class="category-number">5</span>
                    การจัดการสิ่งแวดล้อมที่เอื้อต่อสุขภาพ
                </label>
                <textarea class="form-textarea" name="category_5" rows="5"
                    placeholder="กรุณากรอกรายละเอียดการจัดการสิ่งแวดล้อมที่เอื้อต่อสุขภาพในชุมชน"></textarea>
            </div>

            <!-- Category 6 -->
            <div class="form-section">
                <label class="form-label">
                    <span class="category-number">6</span>
                    การสร้างความเข้มแข็งของชุมชน
                </label>
                <textarea class="form-textarea" name="category_6" rows="5"
                    placeholder="กรุณากรอกรายละเอียดผลลัพธ์ที่ได้รับจากการดำเนินงานของ พชอ.ไต"></textarea>
            </div>

            <!-- Category 7 -->
            <div class="form-section">
                <label class="form-label">
                    <span class="category-number">7</span>
                    การจัดบริการเชิงรุกในชุมชน
                </label>
                <textarea class="form-textarea" name="category_7" rows="5"
                    placeholder="กรุณากรอกรายละเอียดการเชื่อมโยงการทำงานร่วมกับภาคีเครือข่ายต่างๆ"></textarea>
            </div>

            <!-- Category 8 with 3 sub-items -->
            <div class="form-section category-8">
                <label class="form-label">
                    <span class="category-number">8</span>
                    การประเมินผลลัพธ์
                </label>
                <p class="sub-description">
                    มีการประเมินผลลัพธ์การดำเนินงาน ประกอบด้วย
                </p>

                <div class="sub-items-container">
                    <div class="sub-item">
                        <label class="sub-label">
                            <i class="fas fa-angle-double-right sub-icon"></i>
                            ร้อยละของผู้ป่วยโรคเบาหวานและ/หรือความดันโลหิตสูง ได้รับการค้นหาและคัดกรองโรคไตเรื้อรัง
                        </label>
                        <textarea class="form-textarea" name="category_8_1" rows="4"
                            placeholder="กรุณากรอกร้อยละและรายละเอียดของผู้ป่วยที่ได้รับการค้นหาและคัดกรองโรคไตเรื้อรัง"></textarea>
                    </div>

                    <div class="sub-item">
                        <label class="sub-label">
                            <i class="fas fa-angle-double-right sub-icon"></i>
                            การประเมินความตระหนักรู้การลดการบริโภคเกลือโซเดียมของประชาชนในพื้นที่
                        </label>
                        <textarea class="form-textarea" name="category_8_2" rows="4"
                            placeholder="กรุณากรอกรายละเอียดการประเมินความตระหนักรู้ของประชาชนในการลดการบริโภคเกลือโซเดียม"></textarea>
                    </div>

                    <div class="sub-item">
                        <label class="sub-label">
                            <i class="fas fa-angle-double-right sub-icon"></i>
                            นวัตกรรม/บุคคลต้นแบบ/ภูมิปัญญาท้องถิ่น/งานวิจัยที่สนับสนุนการลดการบริโภคเกลือโซเดียมและ/หรือการป้องกันและชะลอภาวะไตเรื้อรัง
                        </label>
                        <textarea class="form-textarea" name="category_8_3" rows="4"
                            placeholder="กรุณากรอกรายละเอียดนวัตกรรม บุคคลต้นแบบ ภูมิปัญญาท้องถิ่น หรืองานวิจัยที่เกี่ยวข้อง"></textarea>
                    </div>
                </div>
            </div>



            <!-- Main Section 2: Problems and Obstacles -->
            <div class="main-section-header">
                <h3 class="section-title">
                    <i class="fas fa-exclamation-triangle"></i>
                    ปัญหา อุปสรรค
                </h3>
            </div>
            <div class="form-section main-section-content">
                <textarea class="form-textarea" name="problems_obstacles" rows="6"
                    placeholder="กรุณากรอกรายละเอียดปัญหาและอุปสรรคที่พบในการดำเนินงาน"></textarea>
            </div>

            <!-- Main Section 3: Recommendations/Development Opportunities -->
            <div class="main-section-header">
                <h3 class="section-title">
                    <i class="fas fa-lightbulb"></i>
                    ข้อเสนอแนะ/โอกาสพัฒนา
                </h3>
            </div>
            <div class="form-section main-section-content">
                <textarea class="form-textarea" name="recommendations_opportunities" rows="6"
                    placeholder="กรุณากรอกข้อเสนอแนะและโอกาสในการพัฒนาการดำเนินงาน"></textarea>
            </div>

            <!-- Action Buttons -->
            <div class="form-actions">
                <button type="submit" class="btn-submit">
                    <i class="fas fa-save"></i> บันทึกรายงาน
                </button>
                <button type="reset" class="btn-reset">
                    <i class="fas fa-sync-alt"></i> ล้างข้อมูล
                </button>
                <button type="button" class="btn-print" onclick="window.print()">
                    <i class="fas fa-print"></i> พิมพ์รายงาน
                </button>
            </div>
        </form>
    </div>

    <style>
        /* Government-appropriate color scheme */
        :root {
            --gov-primary: #2563eb;
            /* Professional Blue */
            --gov-secondary: #1e40af;
            /* Dark Blue */
            --gov-accent: #3b82f6;
            /* Light Blue */
            --gov-text: #1e293b;
            /* Dark Slate */
            --gov-text-light: #475569;
            /* Medium Slate */
            --gov-border: #cbd5e1;
            /* Light Border */
            --gov-bg: #f8fafc;
            /* Very Light Gray */
            --gov-success: #059669;
            /* Green */
        }

        .form-section {
            margin-bottom: 30px;
            padding: 25px;
            background: white;
            border-radius: 8px;
            border: 2px solid var(--gov-border);
            border-left: 5px solid var(--gov-primary);
            box-shadow: 0 1px 3px rgba(0, 0, 0, 0.05);
            transition: all 0.3s ease;
        }

        .form-section:hover {
            box-shadow: 0 4px 12px rgba(37, 99, 235, 0.08);
        }

        .form-section.category-8 {
            border-left-color: var(--gov-success);
            background: #f0fdf4;
        }

        .form-label {
            display: flex;
            align-items: center;
            font-size: 1.1rem;
            font-weight: 700;
            color: var(--gov-text);
            margin-bottom: 15px;
            line-height: 1.6;
        }

        .category-number {
            display: inline-flex;
            align-items: center;
            justify-content: center;
            min-width: 36px;
            height: 36px;
            background: linear-gradient(135deg, var(--gov-primary), var(--gov-secondary));
            color: white;
            border-radius: 8px;
            font-weight: 800;
            font-size: 1.1rem;
            margin-right: 12px;
            box-shadow: 0 2px 6px rgba(37, 99, 235, 0.2);
        }

        /* Main section headers */
        .main-section-header {
            margin: 40px 0 25px 0;
            padding: 20px 25px;
            background: linear-gradient(135deg, #1e40af, #2563eb);
            border-radius: 8px;
            box-shadow: 0 4px 12px rgba(30, 64, 175, 0.2);
        }

        .section-title {
            margin: 0;
            color: white;
            font-size: 1.3rem;
            font-weight: 700;
            display: flex;
            align-items: center;
            gap: 12px;
        }

        .section-title i {
            font-size: 1.4rem;
        }

        .main-section-content {
            border-left-color: #2563eb;
            border-left-width: 4px;
        }

        .sub-description {
            margin: 15px 0;
            padding: 12px;
            background: #dbeafe;
            border-left: 3px solid var(--gov-primary);
            color: var(--gov-text);
            font-size: 1rem;
            font-weight: 600;
            border-radius: 4px;
        }

        .form-textarea {
            width: 100%;
            padding: 16px;
            border: 2px solid var(--gov-border);
            border-radius: 6px;
            font-family: 'Sarabun', sans-serif;
            font-size: 1rem;
            color: var(--gov-text);
            background: #ffffff;
            resize: vertical;
            transition: all 0.3s ease;
            line-height: 1.6;
        }

        .form-textarea:focus {
            outline: none;
            border-color: var(--gov-primary);
            box-shadow: 0 0 0 4px rgba(37, 99, 235, 0.1);
            background: #f8fafc;
        }

        .form-textarea::placeholder {
            color: #94a3b8;
            font-style: normal;
        }

        .sub-items-container {
            margin-top: 20px;
        }

        .sub-item {
            margin-bottom: 25px;
            padding: 20px;
            background: white;
            border-radius: 6px;
            border: 1px solid #d1fae5;
            box-shadow: 0 1px 3px rgba(5, 150, 105, 0.05);
        }

        .sub-label {
            display: flex;
            align-items: flex-start;
            font-size: 1rem;
            font-weight: 600;
            color: var(--gov-text);
            margin-bottom: 12px;
            line-height: 1.7;
        }

        .sub-icon {
            color: var(--gov-success);
            margin-right: 10px;
            margin-top: 4px;
            font-size: 0.9rem;
            flex-shrink: 0;
        }

        .form-actions {
            margin-top: 40px;
            padding-top: 30px;
            border-top: 2px solid var(--gov-border);
            text-align: center;
            display: flex;
            justify-content: center;
            gap: 15px;
            flex-wrap: wrap;
        }

        .btn-submit,
        .btn-reset,
        .btn-print {
            padding: 14px 32px;
            font-size: 1.05rem;
            font-weight: 600;
            border: none;
            border-radius: 6px;
            cursor: pointer;
            transition: all 0.3s ease;
            font-family: 'Sarabun', sans-serif;
            display: inline-flex;
            align-items: center;
            gap: 8px;
        }

        .btn-submit {
            background: linear-gradient(135deg, var(--gov-primary), var(--gov-secondary));
            color: white;
            box-shadow: 0 4px 12px rgba(37, 99, 235, 0.25);
        }

        .btn-submit:hover {
            transform: translateY(-2px);
            box-shadow: 0 6px 16px rgba(37, 99, 235, 0.35);
        }

        .btn-reset {
            background: #64748b;
            color: white;
            box-shadow: 0 4px 12px rgba(100, 116, 139, 0.2);
        }

        .btn-reset:hover {
            background: #475569;
            transform: translateY(-2px);
            box-shadow: 0 6px 16px rgba(100, 116, 139, 0.3);
        }

        .btn-print {
            background: var(--gov-success);
            color: white;
            box-shadow: 0 4px 12px rgba(5, 150, 105, 0.2);
        }

        .btn-print:hover {
            background: #047857;
            transform: translateY(-2px);
            box-shadow: 0 6px 16px rgba(5, 150, 105, 0.3);
        }

        /* Responsive Design */
        @media (max-width: 768px) {
            .form-section {
                padding: 18px;
            }

            .form-label {
                font-size: 1rem;
                flex-direction: column;
                align-items: flex-start;
            }

            .category-number {
                margin-bottom: 10px;
            }

            .form-textarea {
                font-size: 0.95rem;
            }

            .form-actions {
                flex-direction: column;
            }

            .btn-submit,
            .btn-reset,
            .btn-print {
                width: 100%;
                justify-content: center;
            }
        }

        /* Print Styles */
        @media print {

            .btn-submit,
            .btn-reset,
            .btn-print {
                display: none;
            }

            .form-section {
                page-break-inside: avoid;
                box-shadow: none;
            }
        }
    </style>
@endsection