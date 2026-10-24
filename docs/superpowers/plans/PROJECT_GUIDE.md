# คู่มือทำความเข้าใจโปรเจกต์ Salt

เอกสารนี้เป็นแผนที่สำหรับพัฒนาต่อ โดยอิงจาก source code, routes, migrations และ configuration ที่มีใน repository ณ วันที่ 24 ตุลาคม 2026 ไม่ใช่คู่มือ Laravel ทั่วไป และไม่ถือว่า README ปัจจุบันเป็นคู่มือระบบ (README ยังเป็นข้อความตั้งต้นของ Laravel)

## 1. ภาพรวม

ระบบนี้เป็นเว็บแอป Laravel สำหรับติดตามงานลดโซเดียมและข้อมูลสุขภาพระดับจังหวัด/อำเภอ มีหน้าสาธารณะสำหรับ dashboard/report และหลังบ้านสำหรับบันทึก นำเข้า ตรวจสอบ ยืนยัน และส่งออกข้อมูล

เทคโนโลยีที่เห็นจาก dependency/configuration:

- PHP `^8.3`, Laravel `^13.0`
- ฐานข้อมูลตั้งค่า default เป็น MySQL ใน `config/database.php` และ `.env.example`; มี PostgreSQL driver และ compatibility helper สำหรับ SQL ที่ต้องรองรับหลาย driver (`app/Support/DbCompat.php`); SQLite ถูกกล่าวถึงใน tests แต่ไม่ได้เปิดใช้ใน `phpunit.xml`
- Excel ใช้ `maatwebsite/excel` และมี imports/exports เฉพาะงาน
- Frontend เป็น Blade + JavaScript/CSS, Vite; charts ใช้ Chart.js และ Highcharts ที่เก็บไว้ใน `public/vendor`
- Authentication ใช้ session ของ Laravel; OTP และ reset password อยู่ใน `AuthController`

โครงทางเดินคำขอโดยทั่วไป:

```text
Browser
  -> routes/web.php
  -> middleware (auth / super.admin / log.admin / page.cache / page.view)
  -> Controller
  -> Model + Service/Import/Export/Support
  -> Database / storage/app/public
  -> Blade view + inline JavaScript
```

## 2. เริ่มอ่านอย่างไร

ลำดับที่แนะนำสำหรับผู้พัฒนาคนใหม่:

1. `composer.json`, `.env.example`, `config/database.php` เพื่อรู้ PHP/framework, package และค่าการเชื่อมต่อ (ห้ามคัดลอกค่า secret จาก `.env` ลงเอกสารหรือ commit)
2. `routes/web.php` เพื่อดูหน้าและ action ทั้ง public/admin พร้อม middleware และ route name
3. `app/Http/Kernel.php`, `app/Http/Middleware/` เพื่อเข้าใจ authentication, สิทธิ์ super admin, activity log และ cache
4. Controller ของฟีเจอร์ที่จะแก้ แล้วตาม service/import/export/model ที่ controller เรียก
5. migrations ของตารางที่เกี่ยวข้อง เพื่อดู schema ตามลำดับเวลา อย่าเดา schema จาก Model เพียงอย่างเดียว
6. Blade view ที่ route ส่งไป และ partial ที่ view นั้น include; JS จำนวนมากอยู่ใน Blade โดยตรง
7. Tests ใน `tests/Unit` และ `tests/Feature` (มีจำนวนน้อยเมื่อเทียบกับขนาดแอป)

เมื่อจะหา feature ให้ค้น route name หรือ URL ใน `routes/web.php` ก่อน จากนั้นค้นชื่อ controller method ด้วย `rg` แล้วตาม `view(...)`, `Model::`, service/import/export ที่พบ

## 3. โครงสร้าง repository

| ตำแหน่ง | หน้าที่ / ข้อควรรู้ |
|---|---|
| `app/Http/Controllers/` | Request orchestration และ business logic; `AdminController.php` (~6,300 บรรทัด) กับ `MainController.php` (~4,000 บรรทัด) ใหญ่มากและรวมหลายฟีเจอร์ |
| `app/Http/Middleware/` | auth, rank-1 super admin, admin activity, public page cache/view count, server timing |
| `app/Models/` | Eloquent models; ชื่อ column เดิมหลายตัวเป็น Pascal/mixed case เช่น `User_rank_id`, `Province_id` |
| `app/Services/` | logic ที่แยกเป็นหน่วย เช่น scoring, Word parsing/export, รูปภาพไต |
| `app/Imports/`, `app/Exports/` | mapping/validation และ Excel import/export; บาง format มี legacy และ V2 แยกกัน |
| `app/Support/` | helper ข้ามฟีเจอร์ เช่น DB compatibility, cache, settings, logging, streaming XLSX |
| `routes/` | `web.php` เป็นแหล่ง route หลัก รวม public และ admin; `api.php` แยกไว้แต่ไม่ใช่แกน UI ที่เห็นใน routes ปัจจุบัน |
| `resources/views/` | Blade templates; หน้า dashboard/report และ admin บางหน้ามี JS/CSS inline จำนวนมาก |
| `resources/js`, `resources/css`, `vite.config.js`, `package.json` | Vite scaffolding มีขนาดเล็ก; โค้ดหน้าเว็บส่วนใหญ่ไม่ได้อยู่ใน `resources/js/app.js` |
| `database/migrations/` | schema evolution ก.พ. ถึง ต.ค. 2026; ต้องอ่าน migration chain ทั้งหมดเพื่อเข้าใจ schema ล่าสุด |
| `database/seeders/`, `database/factories/` | ข้อมูลตั้งต้นและ factory; มี seeder สำหรับโรงพยาบาลส่งเสริมสุขภาพตำบล |
| `tests/` | PHPUnit; มีตัวอย่างและ unit test เฉพาะ support บางตัว ไม่ได้ครอบคลุม feature ทั้งหมด |
| `public/vendor/` | ไฟล์ไลบรารี chart/map ฝั่ง client ที่ vendored ใน repository |
| `storage/` | uploaded/public files, logs และ runtime data; เนื้อหา runtime ไม่ควรถือเป็น source code |
| `vendor/` | Composer dependencies; ปกติไม่แก้ไฟล์ในนี้ |
| `docs/` | เอกสารโครงการ (เพิ่มคู่มือนี้และแผนไว้ที่ `docs/superpowers/plans/`) |
| `_to_delete/`, `Claude outputs/`, `.taste-skill/`, `.vscode/` | พบใน root; ไม่เห็นเป็นส่วน runtime จาก routes/autoload ที่ตรวจ จึงควรตรวจเจ้าของ/ความหมายก่อนลบหรือพึ่งพา |

## 4. แผนที่โมดูลและไฟล์หลัก

| โมดูล | จุดเริ่ม / Controller | Data, logic และ UI ที่เกี่ยวข้อง |
|---|---|---|
| หน้าแรกและ dashboards | public routes -> `MainController` | `resources/views/pages/home.blade.php`; แสดงตัวชี้วัดและกราฟหลายชุด |
| Awareness | `MainController::awareness`, `AwarenessAssessmentController`, `SurveyQuestionSettingsController` | `AwarenessScoreCalculator`, `AwarenessPassResolver`, `AwarenessPanelBreakdown`, `AwarenessYearGrid`; Models `SodiumSurvey`, `SurveyYearMapping`, `DemographicFieldMapping`, `AwarenessPassSetting`, `AwarenessAssessment*`; views `pages/awareness.blade.php`, `admin/awareness/*`; imports/interpretation exports |
| ประเมิน พชอ.ไต | `MainController` public + `AdminController` admin | `KidneyAssessment`, `OperatingAreaPin`, `KidneyAssessmentImage`, location tables; `KidneyDhbWordParser`, `KidneyImageService`, `KidneyDHBExport`; views `pages/kidney-dhb*`, `pages/partials/kidney-dhb-content.blade.php`, `admin/kidney-dhb*`, `admin/operating-areas*` |
| ประเมิน/รายงานเกลือ | `AdminController` | `SaltAssessment`; parser/export: `SaltAssessmentWordParser`, `AssessmentWordExportService`, `SaltAssessmentExport`; views `admin/salt-assessment-*`, `pages/report-progress*` |
| ผลิตภัณฑ์ลดโซเดียม | public `MainController`, admin `AdminController` | `ReducedSodiumProduct`, `ReducedSodiumProductImport`, `ReducedSodiumProductsExport`, template sheets; views `pages/reduced-sodium-products*`, `admin/sodium-products.blade.php` |
| เมนูลดโซเดียม | public `MainController`, admin `AdminController` | `ReducedSodiumMenu`; importer แบบ legacy/V2 และ `ReducedSodiumMenuLegacyRowMapper`; views `pages/reduced-sodium-menu*`, `admin/reduced-sodium-menus.blade.php` |
| HT รายใหม่ / HI | public `MainController`, admin `HiController` | `Hi`, `HiImport`; views `pages/new-ht-cases*`, `admin/hi/*`; API trend อยู่ route `/api/ht-trend` ใน `web.php` |
| การใช้เกลือ/แบบสำรวจ | `MainController::foodSurvey` และส่วน report | `SodiumSurvey`, `SodiumSurveyImport`, survey mapping models; `pages/food-survey.blade.php`, `pages/consumption-report.blade.php` |
| ผู้ใช้และสิทธิ์ | `AuthController`, `UserManagementController`, `SuperAdminOnly` | `User`, จังหวัด/อำเภอ/ตำบล/สถานพยาบาล; login/OTP/register/reset; admin user management |
| ปีงบประมาณ | `FiscalYearController` | `FiscalYear`; config ของ module ถูกกำหนดใน `moduleDefinitions()` |
| ระบบและ audit | `SystemManagementController`, `ActivityLog`, `SystemSettings` | audit log, toggles, page views, OTP setting, super-admin grants |

## 5. Routing, authentication และสิทธิ์

- Public routes อยู่ต้น `routes/web.php`; routes ใน admin group ใช้ prefix `/admin` และ middleware `auth`, `log.admin`
- Middleware alias อยู่ `app/Http/Kernel.php`: `super.admin` คือ `SuperAdminOnly`, `log.admin` คือ `LogAdminActivity`, `page.view` และ `page.cache` ใช้กับหน้า public บางหน้า
- SuperAdmin ต้องเป็น rank 1 และ email อยู่ใน config หรือได้รับ grant; `User::isSuperAdmin()` เป็นจุดอ่านหลัก ส่วนรายการ root admin มาจาก `SUPER_ADMIN_EMAILS` ใน environment/config
- rank มีผลกับขอบเขตข้อมูลด้วย ไม่ใช่แค่เมนู: โค้ด admin หลายจุด scope ตามจังหวัด/อำเภอ/หน่วยงาน โดย rank 2 รวมตามจังหวัดและ rank 3+ ตามอำเภอ (ตรวจ logic รายฟีเจอร์ก่อนแก้)
- หน้า `/admin` redirect ตาม rank: rank 5 ไปเมนู sodium, rank 3/4 ไป kidney, อื่น ๆ ไป salt list
- มี OTP สำหรับ login และ password reset; มี throttling บาง endpoint; runtime switch อ่านผ่าน `SystemSettings` ซึ่งเก็บ JSON ที่ `storage/app/system_settings.json`
- ข้อสังเกตจาก route: export Kidney PDF/Word ถูกประกาศเป็น `/admin/admin/kidney-dhb/{id}/export-*` ภายใน group ที่มี prefix `/admin`; URL จริงจึงอาจมี `/admin/admin/...` นี่เป็นพฤติกรรม route ที่ควรตรวจหากแก้ลิงก์
- `/storage/{path}` เป็น fallback เฉพาะกรณี static symlink เสิร์ฟไม่ได้ มี comment ว่า diagnostic JSON ตอบ 404 เมื่อไฟล์หาย; ระวังอย่าทำให้ path traversal หรือข้อมูล path ภายในรั่ว

## 6. ข้อมูลและความสัมพันธ์สำคัญ

ดู migrations ตั้งแต่ต้น ไม่ใช่ migration ล่าสุดรายการเดียว ตารางหลักที่เห็น ได้แก่:

- ผู้ใช้และ geography: `users`, `provinces`, `districts`, `tambon`, `village`, `subdistrict_hospitals`, `his`
- งานประเมิน: `kidney_assessments`, `salt_assessments`, `kidney_assessment_images`, `kidney_operating_area_pins`, `kidney_operating_area_selections`
- dashboard ลดโซเดียม: `reduced_sodium_products`, `reduced_sodium_menus`, `sodium_surveys`
- Awareness survey: `survey_templates`, `survey_sections`, `survey_questions`, `survey_responses`, `survey_answers`, `survey_year_mappings`, `demographic_field_mappings`, `awareness_pass_settings`, และ assessment tables แยกปี/รุ่น
- บริหารระบบ: `fiscal_years`, `page_views`, `activity_logs`, `personal_access_tokens`, `password_reset_tokens`, `failed_jobs`

รูปแบบข้อมูลที่ควรรู้ก่อนเปลี่ยน schema:

- `users` เป็น schema เก่าที่ต่อเติมด้วย migrations หลายชุด; ชื่อคอลัมน์เดิม mixed case และ ID จังหวัด/อำเภอบางจุดเป็น string แต่ geography tables ใช้ integer
- มี FK ไม่ครบทุกความสัมพันธ์; ตัวอย่าง `KidneyAssessment::operatingAreaPin()` ระบุว่าเป็น FK-less; บาง workflow ใช้ snapshot fields เพื่อรักษาชื่อ/พิกัดขณะส่งรายงาน
- Unique constraint ของ kidney assessment เคยเปลี่ยนให้สัมพันธ์กับ operating area; อ่าน migrations `2026_09_15_*` และ `2026_10_03_*` ก่อนแก้ duplicate behavior
- Fiscal year ใช้ปี พ.ศ. ใน UI/ข้อมูลบางส่วน; ตรวจการแปลงปีและชื่อ field ทุกครั้งก่อนเขียน query หรือ import
- มี migrations จำนวนมากที่เพิ่ม index เพื่อ list/filter/report; อย่าลบหรือเปลี่ยน index โดยไม่ดู query ที่รองรับ

## 7. Flow สำคัญ

### 7.1 Awareness

ข้อมูลแบบสำรวจอยู่ใน `sodium_surveys` โดยคำตอบเก็บเป็น `survey_data`; การตีความ dashboard ไม่ใช่ค่าคงที่ต่อปี เพราะ `survey_year_mappings` กำหนด mapping คำถาม, เกณฑ์ผ่าน, บทบาทคะแนน, polarity, panel และ demographic mapping ส่วน `AwarenessPassSetting` เลือกวิธีตัดสินผ่านตามปีงบประมาณ

เวลาปรับสูตร/รายงาน ให้ตามลำดับ: `AwarenessPassResolver` -> `AwarenessScoreCalculator` หรือ question criteria -> panel/year-grid services -> caller ใน public/admin controller -> view/export. ตัว resolver ถูกออกแบบให้หน้า dashboard/list ใช้กฎผ่านชุดเดียวกัน และมีการ cache mapping ต่อ batch/page เพื่อลด query

### 7.2 Kidney DHB

มีสองแนวคิดพื้นที่: operating-area pins ที่ตั้งไว้ล่วงหน้าสำหรับหน่วยงาน กับ assessment ที่อ้าง pin หรือเก็บ location snapshot ของตัวเอง. ผู้ใช้เลือกพื้นที่ก่อนส่งรายงานได้; super admin จัดการ pins/ปล่อยพื้นที่/แก้พื้นที่; controller ตรวจสิทธิ์และความเป็นเจ้าของ; รายงานมีสถานะอ่าน/ยืนยัน, fiscal year/quarter, ไฟล์ milestone และภาพประกอบ

ก่อนแก้ flow นี้ให้อ่าน `AdminController` กลุ่ม kidney (โดยเฉพาะ `kidneyDHB`, `storeKidneyDHB`, `getKidneyDHBData`, `kidneyDHBList`, `showKidneyDHB`, `updateKidneyDHB`, `canManageKidneyAssessment`, methods operating-area) พร้อม `KidneyAreaList`, `KidneyDhbWordParser`, `KidneyImageService` และ migrations ต.ค. 2026. หน้า `pages/partials/kidney-dhb-content.blade.php` ใหญ่มากและมี interaction ฝั่ง browser

### 7.3 นำเข้า/ส่งออก Excel และ Word

Controller รับ upload แล้วส่งต่อ Import class หรือ parser; export ใช้ Maatwebsite Excel sheet classes หรือ Word/PDF service. Product/menu รองรับ template/download/import/export; menu มี legacy และ V2 mapper จึงต้องรักษาความเข้ากันได้ของไฟล์เก่า. `XlsxRowStream` มีไว้ stream อ่าน XLSX โดยตรงเพื่อลด memory usage. ตรวจ file mapping, header row, encoding, duplicate handling และ transaction behavior ก่อนเปลี่ยน format

### 7.4 Caching และ invalidation

`PublicPageCache` ใช้ cache สำหรับ public pages บางหน้า; `AdminCache` cache จังหวัด/ค่าที่ distinct; `AdminController` มีจุดล้าง sodium filter cache; composer ใน `AppServiceProvider` เพิ่ม badge counts ให้ทุก admin page. หลังเปลี่ยนข้อมูลต้องตรวจว่ามี invalidate ที่ถูกต้อง และ badge มี data scope ตรงกับ list

## 8. Frontend และ view conventions

- Layouts: `resources/views/layouts/layout.blade.php` สำหรับ public, `admin.blade.php` สำหรับ admin, `blank.blade.php` สำหรับหน้า minimal
- Feature pages อยู่ `resources/views/pages/` และ `resources/views/admin/`; shared fragments อยู่ `partials/`
- หลายหน้าเป็น Blade ขนาดใหญ่มากและมี inline JS; `resources/js/app.js` มีเพียง entry point บรรทัดเดียว จึงอย่าคาดหวังว่าจะพบ behavior ทั้งหมดใน `resources/js`
- Chart/map assets รวมใน `public/vendor/chartjs` และ `public/vendor/highcharts`; ภาษา/แผนที่ประเทศไทยมีไฟล์เฉพาะ
- `*.blade.php.unused` เช่น awareness settings เป็นไฟล์ที่ไม่ได้ลงท้าย `.blade.php` และไม่ถูก render ปกติ อย่าเข้าใจว่าเป็นหน้าที่ใช้งาน

## 9. การพัฒนาในเครื่อง

ค่าจริงขึ้นกับ environment ของทีม; ใช้ `.env.example` เป็นรายการตัวแปรอ้างอิงและห้ามเผยแพร่ `.env`. ถ้ายังไม่มี `.env` ให้สร้างจากตัวอย่าง; โปรเจกต์มี `vendor/` อยู่แล้ว แต่คำสั่งมาตรฐานของ Laravel คือ:

```powershell
composer install
Copy-Item .env.example .env
php artisan key:generate
php artisan migrate
php artisan storage:link
php artisan serve
```

Frontend build scripts ใน `package.json` มีเพียง `dev`/`build` ผ่าน Vite; หลังติดตั้ง Node dependencies แล้วใช้ `npm run dev` สำหรับพัฒนา หรือ `npm run build` สำหรับ build. ทดสอบ PHP ใช้ PHPUnit config จาก `phpunit.xml` และ `vendor/bin/phpunit` หรือ `php artisan test`. `DB_CONNECTION` ใน `.env.example` ระบุ MySQL; ค่า local/production อาจต่างกัน

คำสั่งอ่านสถานะที่มีประโยชน์:

```powershell
php artisan route:list
php artisan migrate:status
php artisan test
```

ไม่ควรรัน migrate/seed บนฐานข้อมูลที่มีข้อมูลจริงโดยไม่ตรวจ environment และ backup ก่อน

## 10. Tests และช่องว่างที่เห็น

- `tests/Unit/Support/`: มี `SystemSettingsTest`, `DbCompatTest`, และ example test; มี Feature example
- ไม่มี test suite ที่ชัดเจนครอบคลุม role scoping, import format ทั้งเก่า/ใหม่, Awareness calculation ทุก mapping, kidney-area ownership, public cache invalidation หรือการส่งออกเอกสาร
- `phpunit.xml` ไม่ได้เปิดค่า SQLite test DB (บรรทัดถูก comment) จึงต้องตรวจ `.env.testing`/environment ก่อนคาดหวังว่า feature tests จะบูต DB ได้
- ยังไม่ได้รัน tests ในการจัดทำคู่มือนี้; ใช้เอกสารเป็นแผนที่ ไม่ใช่หลักฐานว่าระบบผ่าน test

## 11. จุดที่ต้องระวังเมื่อพัฒนาต่อ

1. **Authorization และ data scoping:** ทุก query ที่รับ ID จาก request ต้องเช็กว่า user rank/หน่วยงานเข้าถึง record นั้นได้; การซ่อนปุ่มใน Blade ไม่ใช่ authorization
2. **ฐานข้อมูลหลาย engine:** raw SQL ต้องดู `DbCompat`; PostgreSQL แพ้เรื่อง mixed-case identifier และ type mismatch ที่ MySQL อาจแปลงให้อัตโนมัติ
3. **Legacy columns / data shape:** ชื่อคอลัมน์, ปี พ.ศ., ค่า null และชื่อจังหวัดแบบข้อความมีผลกับ import/filter; อย่าปรับรูปแบบโดยดูเฉพาะข้อมูลใหม่
4. **Large reports/imports:** ใช้ chunk/stream และ index; หลีกเลี่ยงโหลดทุกแถว/สร้าง N+1 queries
5. **Duplicate & concurrency:** pins/การเลือกพื้นที่/assessment มี unique constraints และ workflow หลายระดับ; ใช้ transaction และตรวจ race behavior
6. **Uploads:** ตรวจ MIME/ขนาด/extension, ใช้ disk `public`, ทำความสะอาดไฟล์เมื่อ record ถูกลบ; รูปไตมีการ resize ฝั่ง service และข้อจำกัดขึ้นกับ GD
7. **Cache consistency:** ข้อมูล admin/public ใช้คนละ key/TTL; ตรวจการล้าง cache ทุกครั้งที่เพิ่ม/แก้/ลบข้อมูล
8. **Views ที่ยาวมาก:** แก้ JS/CSS inline อย่างระวัง เพราะ selector/id อาจถูกผูกหลายส่วน; แยก refactor เป็นอีกงานแทนการจัดโครงสร้างทั้งหน้าโดยไม่จำเป็น
9. **Route naming/path:** ใช้ route name ใน Blade; ตรวจ route ที่ซ้อน prefix หรือ URL ที่ผิดสังเกตจากรายการ route ก่อนอ้างอิง
10. **Sensitive configuration:** `.env` เป็นไฟล์จริงใน workspace; ไม่เปิดเผยค่า, ไม่ใส่ลง log/guide และตรวจ `git status` ก่อน commit เสมอ

## 12. แผนการเรียนรู้แบบลงมือ

เริ่มจากงานที่เสี่ยงต่ำและเห็น flow ครบ: เลือก public page เช่นผลิตภัณฑ์ลดโซเดียม -> route -> `MainController` -> model/query -> Blade. ต่อด้วย admin list/import/export ของฟีเจอร์เดียวกัน. หลังเข้าใจ patterns แล้วค่อยศึกษา Awareness (mapping/scoring) และ Kidney DHB (สิทธิ์/พื้นที่/ไฟล์) ซึ่งมี state และเงื่อนไขมากกว่า

สำหรับการแก้แต่ละครั้ง ให้บันทึก: route name, ผู้ใช้/role ที่ทำได้, ตารางที่อ่าน/เขียน, validation, side effects, cache invalidation, view/export ที่ใช้ และวิธีตรวจผลด้วย test หรือข้อมูลทดลองที่ไม่ใช่ production

## 13. ขอบเขตการสำรวจ

ได้ตรวจ repository structure, routes, dependency/configuration, middleware, core support/service classes, migration filenames, controller methods, model inventory และ view inventory เพื่อทำแผนที่ทั้งระบบ. เนื่องจาก controller/view บางไฟล์ยาวหลายพันบรรทัด เอกสารนี้จัดกลุ่มตาม feature และระบุไฟล์/จุดเริ่มที่ควรอ่านต่อ; ไม่ได้ถอดความทุก statement หรือยืนยันพฤติกรรม runtime ทุกเส้นทาง. ควรใช้ source และ migrations เป็นข้อมูลจริงเมื่อแก้โค้ด
