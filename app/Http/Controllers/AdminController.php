<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\SaltAssessment;
use App\Models\KidneyAssessment;
use App\Models\ReducedSodiumProduct;
use App\Models\ReducedSodiumMenu;
use App\Models\FiscalYear;
use App\Models\User;
use App\Models\Province;
use App\Models\District;
use App\Models\Tambon;
use App\Models\Village;
use App\Models\OperatingAreaPin;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Maatwebsite\Excel\Facades\Excel;
use App\Imports\ReducedSodiumMenuImport;
use App\Imports\ReducedSodiumMenuImportV2;
use App\Imports\ReducedSodiumMenuImportLegacy;
use App\Imports\ReducedSodiumProductImport;
use App\Exports\ReducedSodiumProductsExport;
use App\Exports\ReducedSodiumMenusExport;
use App\Services\KidneyDhbWordParser;
use App\Services\SaltAssessmentWordParser;
use App\Services\AssessmentWordExportService;

class AdminController extends Controller
{

    public function reportProgress(Request $request)
    {
        $user = auth()->user();
        $agencyName = $user->Con_name;
        $provinceName = $user->province ? $user->province->province_name : '';
        $districtName = $user->district ? $user->district->district_name : '';

        if ($user->User_rank_id == 2) {
            $agencyName = 'สํานักงานสาธารณสุขจังหวัด' . $provinceName;
        } elseif ($user->User_rank_id == 3) {
            $agencyName = 'สํานักงานสาธารณสุขอำเภอ' . $districtName;
        } elseif ($user->User_rank_id == 4) {
            $agencyName = $user->subdistrictHospital ? $user->subdistrictHospital->hospital_name : $user->Con_name;
        } elseif ($user->User_rank_id == 5) {
            $agencyName = $user->hospital ? $user->hospital->hos_name : $user->Con_name;
        }

        // Word-import is restricted to Level 1 (User_rank_id == 1, "สคร.")
        // accounts only - the same rank gate already used for the
        // kidney-dhb Word-import feature (see AdminController::kidneyDHB()'s
        // $canImportWord), since it lets the importer save data under ANY
        // other office's agency.
        $canImportWord = $user->User_rank_id == 1;

        // Word-import agency picker: cascades ประเภทหน่วยงาน ->
        // อำเภอ -> โรงพยาบาล/ชื่อหน่วยงาน, resolved through the existing,
        // fully generic admin.kidney-dhb.resolve-target-agency endpoint -
        // no salt-specific resolver needed. Only built for Level 1 users.
        $provinces = $canImportWord
            ? \App\Support\AdminCache::provinces()
            : collect();

        // เปิดมาจากปุ่ม "ทำแบบประเมิน" ในหน้ารายการ: ระดับ 1 เลือกแก้ไขข้อมูลของหน่วยงานอื่นได้
        // (ใช้กลไก target_user_id เดียวกับการนำเข้า Word) - คนอื่นถูกละเว้นและใช้หน่วยงานตัวเองเสมอ
        $presetTargetId = null;
        $presetTargetLabel = null;
        if ($user->User_rank_id == 1 && $request->filled('target_user_id')) {
            $presetUser = User::with(['province', 'district', 'subdistrictHospital', 'hospital'])->find($request->query('target_user_id'));
            if ($presetUser) {
                $presetTargetId = $presetUser->id;
                if ($presetUser->User_rank_id == 2) {
                    $presetTargetLabel = 'สำนักงานสาธารณสุขจังหวัด' . ($presetUser->province->province_name ?? '');
                } elseif ($presetUser->User_rank_id == 3) {
                    $presetTargetLabel = 'สำนักงานสาธารณสุขอำเภอ' . ($presetUser->district->district_name ?? '');
                } elseif ($presetUser->User_rank_id == 4) {
                    $presetTargetLabel = $presetUser->subdistrictHospital->hospital_name ?? $presetUser->Con_name;
                } elseif ($presetUser->User_rank_id == 5) {
                    $presetTargetLabel = $presetUser->hospital->hos_name ?? $presetUser->Con_name;
                } else {
                    $presetTargetLabel = $presetUser->Con_name ?: $presetUser->name;
                }
            }
        }

        // new=1 (ปุ่ม "ทำแบบประเมิน" ด้านบนหน้ารายการ): ใช้เริ่มแบบประเมินใหม่/ปีงบประมาณใหม่
        // ปีงบประมาณที่หน่วยงานเคยทำแล้วจะไม่ให้เลือกซ้ำ และแจ้งข้อความว่าปีใดทำไปแล้ว (แก้ไขได้จากปุ่มในรายการที่บันทึกแล้ว)
        $newMode = $request->boolean('new') && $user->User_rank_id != 1;
        $availableYears = null;
        $doneYears = [];
        if ($newMode) {
            $doneYears = SaltAssessment::whereIn('user_id', $this->getAgencyUserIds($user))
                ->distinct()->orderBy('fiscal_year', 'desc')->pluck('fiscal_year')
                ->map(fn ($y) => (int) $y)->all();
            $availableYears = array_values(array_diff($this->saltAssessmentYears(), $doneYears));
            if (empty($availableYears) || $this->saltStartBlock($doneYears) !== null) {
                $block = $this->saltStartBlock($doneYears) ?? ['type' => 'all_done', 'years' => $doneYears];
                return redirect()->route('admin.salt-assessment-list')->with(
                    $block['type'] === 'not_open' ? 'salt_not_open_year' : 'salt_done_years',
                    $block['type'] === 'not_open' ? $block['year'] : $doneYears
                );
            }
        }

        return view('admin.report-progress', [
            'availableYears' => $availableYears,
            'formYears' => $this->saltAssessmentYears(),
            'doneYears' => $doneYears,
            'presetTargetId' => $presetTargetId,
            'presetTargetLabel' => $presetTargetLabel,
            'fiscal_year' => $newMode ? $availableYears[0] : $request->query('fiscal_year'),
            'quarter' => $request->query('quarter'),
            'onlySelected' => $request->boolean('only'),
            'agencyName' => $agencyName,
            'canImportWord' => $canImportWord,
            'provinces' => $provinces,
        ]);
    }

    /**
     * ปีงบประมาณทั้งหมดที่ให้เลือกในแบบประเมินลดการบริโภคเกลือ (มาก -> น้อย), ใช้ร่วมกันทั้งฟอร์ม ปุ่มเริ่มทำแบบประเมินใหม่
     * และการเช็ก "ทำครบทุกปีแล้ว" เพื่อให้ปีงบใหม่ที่เปิดแล้วถูกนับเสมอ:
     *  - ปีที่ผู้ดูแลเปิดไว้ใน "จัดการปีงบประมาณ" (module salt_assessment) และปีที่มีข้อมูลจริงอยู่แล้ว
     *  - ไม่เพิ่มปีงบประมาณปัจจุบันให้อัตโนมัติ: ต้องให้ผู้ดูแลเพิ่มในหน้า "จัดการปีงบประมาณ" ก่อนจึงถือว่าเปิด
     *  - ตัดปีที่ผู้ดูแลซ่อนไว้ออก และไม่แสดงต่ำกว่าปี 2568
     */
    private function saltAssessmentYears(): array
    {
        $floor = 2568;
        $norm = fn ($y) => (int) $y;

        // "Open" = exactly what the จัดการปีงบประมาณ page lists for this
        // module: years with real data + admin-enabled years - hidden ones.
        // The current calendar fiscal year is NOT added automatically any
        // more; an admin has to enable it there first.
        $hidden = FiscalYear::where('module', 'salt_assessment')->where('hidden', true)
            ->pluck('year')->map($norm);
        $enabled = FiscalYear::where('module', 'salt_assessment')->where('hidden', false)
            ->pluck('year')->map($norm);
        $real = \App\Support\AdminCache::distinct(\App\Models\SaltAssessment::class, 'fiscal_year')
            ->filter(fn ($y) => $y !== null && $y !== '')->map($norm);

        return $real->merge($enabled)
            ->unique()
            ->filter(fn ($y) => $y >= $floor && !$hidden->contains($y))
            ->sortDesc()
            ->values()
            ->all();
    }

    /**
     * Why the "ทำแบบประเมิน" button has nothing to start (null = it can start).
     *  - not_open: nothing left to do and the current fiscal year is not in
     *    the open list of "จัดการปีงบประมาณ".
     *  - all_done: every open fiscal year already has a record.
     */
    private function saltStartBlock(array $doneYears): ?array
    {
        $current = (int) date('Y') + 543 + ((int) date('n') >= 10 ? 1 : 0);
        $open = $this->saltAssessmentYears();

        if (!empty(array_diff($open, $doneYears))) {
            return null;
        }

        return !in_array($current, $open, true)
            ? ['type' => 'not_open', 'year' => $current]
            : ['type' => 'all_done', 'years' => $doneYears];
    }

    /**
     * Store the salt assessment report.
     */
    public function storeReportProgress(Request $request)
    {
        $request->validate([
            'fiscal_year' => 'required|integer',
            'quarter' => 'required|in:1,2,3,4',
            'target_user_id' => 'nullable|integer|exists:users,id',
            'import_reporter_name' => 'nullable|string|max:255',
            'import_reporter_position' => 'nullable|string|max:255',
            'import_reporter_phone' => 'nullable|string|max:50',
            'import_reporter_email' => 'nullable|string|max:255',
        ]);

        $currentUser = auth()->user();
        // Word-import support: when importing a historical report on
        // behalf of another office (the docx might belong to a different
        // province/district than whoever is at the keyboard right now),
        // target_user_id points the save at THAT office's agency scope
        // instead of the logged-in admin's own. Only a Level 1 user may
        // save on behalf of another office - the same gate the
        // report-progress Word-import UI itself is hidden behind (see
        // AdminController::reportProgress()'s $canImportWord). Mirrors
        // storeKidneyDHB()'s $targetUser.
        $targetUser = ($currentUser->User_rank_id == 1 && $request->filled('target_user_id'))
            ? (User::find($request->target_user_id) ?: $currentUser)
            : $currentUser;
        $agencyUserIds = $this->getAgencyUserIds($targetUser);

        // One query serves both the "quarter already confirmed" gate and the
        // existing-record lookup below (they used to be two separate queries
        // with the same WHERE clause).
        $existingRows = SaltAssessment::whereIn('user_id', $agencyUserIds)
            ->where('fiscal_year', $request->fiscal_year)
            ->where('quarter', $request->quarter)
            ->get();

        // ไตรมาสที่ยืนยันข้อมูลแล้ว ปิดการแก้ไข/บันทึกทับ "ทุกบทบาท" (รวม สคร.) - ต้องยกเลิกการยืนยันก่อนจึงจะแก้ไขได้
        if ($existingRows->contains(fn($row) => $row->confirmed_at !== null)) {
            return redirect()->route('admin.report-progress', array_filter([
                'fiscal_year' => $request->fiscal_year,
                'quarter' => $request->quarter,
                'only' => $request->input('only'),
                'target_user_id' => $currentUser->User_rank_id == 1 ? $request->input('target_user_id') : null,
            ]))->withErrors(['error' => 'ไตรมาส ' . $request->quarter . ' ได้รับการยืนยันข้อมูลแล้ว จึงไม่สามารถแก้ไขได้ '
                . ($currentUser->User_rank_id == 1 ? 'หากต้องแก้ไขให้กด "ยกเลิกการยืนยัน" ก่อน' : 'หากต้องแก้ไขกรุณาติดต่อ สคร. เพื่อยกเลิกการยืนยัน')]);
        }

        $currentName = trim(($currentUser->prefix ?? '') . ($currentUser->User_firstname ?? '') . ' ' . ($currentUser->User_lastname ?? ''));

        // Word-import attribution: a historical report is filled in by
        // whoever the DOCUMENT names as its reporter, not by the Level 1
        // admin who happens to be doing the importing at the keyboard - so
        // when this save carries the parsed reporter's name (only possible
        // for a genuine Level 1 import-on-behalf-of-another-office save,
        // same gate as $targetUser above), that name is what gets written
        // into each changed field's reporter_metadata instead of
        // $currentName, and the full reporter contact block (name/
        // position/phone/email) is kept on the record for reference,
        // alongside who actually performed the import. Mirrors
        // storeKidneyDHB()'s $isImportSave/$attributedName/$importReporterMeta.
        $isImportSave = $currentUser->User_rank_id == 1
            && $request->filled('target_user_id')
            && $request->filled('import_reporter_name');
        $attributedName = $isImportSave
            ? trim($request->input('import_reporter_name'))
            : $currentName;
        $importReporterMeta = $isImportSave ? [
            'name' => trim($request->input('import_reporter_name')),
            'position' => trim((string) $request->input('import_reporter_position', '')) ?: null,
            'phone' => trim((string) $request->input('import_reporter_phone', '')) ?: null,
            'email' => trim((string) $request->input('import_reporter_email', '')) ?: null,
            'imported_by' => $currentName,
            'imported_at' => date('d/m/Y H:i'),
        ] : null;

        // 1. Find ANY existing record from this agency
        $record = $existingRows->first();

        $data = $record ? $record->toArray() : [
            'fiscal_year' => $request->fiscal_year,
            'quarter' => $request->quarter,
            'user_id' => $currentUser->id,
            'reporter_metadata' => []
        ];

        // Ensure array for metadata
        $meta = is_array($data['reporter_metadata'] ?? null) ? $data['reporter_metadata'] : [];
        if ($importReporterMeta !== null) {
            $meta['_import_reporter'] = $importReporterMeta;
        }

        $fields = ['1', '2', '3', '4', '5_1', '5_2', '5_3', '5_4', '5_5'];
        foreach ($fields as $f) {
            $detailKey = "ans_{$f}_detail";
            $fileKey = "ans_{$f}_file";

            // Update Detail if changed/filled
            if ($request->filled($detailKey)) {
                $newVal = $request->input($detailKey);
                // Only update if different from what's there
                if (!isset($record->$detailKey) || $record->$detailKey !== $newVal) {
                    $data[$detailKey] = $newVal;
                    $meta[$detailKey] = $attributedName;
                }
            }

            // Update File if new upload. Removing a file is now handled immediately
            // via a dedicated AJAX endpoint (removeReportFile) as soon as the user
            // clicks "remove", so it no longer needs to be handled here on save.
            if ($request->hasFile($fileKey)) {
                $file = $request->file($fileKey);
                $filename = time() . '_' . $fileKey . '.' . $file->getClientOriginalExtension();
                $path = $file->storeAs('salt_assessments', $filename, 'public');
                $data[$fileKey] = $path;
                $meta[$fileKey] = $attributedName;
            } elseif ($request->filled($fileKey . '_existing_file')) {
                // Keep whatever existing file path the form posted back unchanged.
                $data[$fileKey] = $request->input($fileKey . '_existing_file');
            }
        }

        // Special fields (Problems/Suggestions)
        foreach (['problems', 'suggestions'] as $spec) {
            if ($request->filled($spec)) {
                $newVal = $request->input($spec);
                if (!isset($record->$spec) || $record->$spec !== $newVal) {
                    $data[$spec] = $newVal;
                    $meta[$spec] = $attributedName;
                }
            }
        }

        $data['reporter_metadata'] = $meta;
        // Overall ownership: the target agency (defaults to whoever is
        // logged in, unless this save is a Word import filed on behalf of
        // a different office - see $targetUser above).
        $data['user_id'] = $targetUser->id;

        // --- SMART MERGE (Carry over DETAIL TEXT ONLY from PREVIOUS quarters if current is still empty) ---
        // Files intentionally do NOT carry forward: a PDF stays attached to the
        // quarter it was actually uploaded in. Only the written detail flows
        // forward as a convenience so staff don't have to retype it every quarter.
        $emptyDetailKeys = array_filter($fields, fn($f) => empty($data["ans_{$f}_detail"]));
        if ($request->quarter > 1 && $emptyDetailKeys) {
            $prevAssessments = SaltAssessment::with('user')
                ->whereIn('user_id', $agencyUserIds)
                ->where('fiscal_year', $request->fiscal_year)
                ->where('quarter', '<', $request->quarter)
                ->orderBy('quarter', 'desc')
                ->get();

            foreach ($fields as $f) {
                $dk = "ans_{$f}_detail";

                // If current record still empty for this field after processing request, check previous
                if (empty($data[$dk])) {
                    foreach ($prevAssessments as $prev) {
                        if (!empty($prev->$dk)) {
                            $data[$dk] = $prev->$dk;
                            $meta[$dk] = $prev->reporter_metadata[$dk] ?? ($prev->user ? trim(($prev->user->prefix ?? '') . ($prev->user->User_firstname ?? '') . ' ' . ($prev->user->User_lastname ?? '')) : null);
                            break;
                        }
                    }
                }
            }
        }

        $data['reporter_metadata'] = $meta;
        $data['is_read'] = 0; // Mark as unread on every save/update

        try {
            // Direct update/create: updateOrCreate() issued an extra SELECT
            // by id just to rediscover the row that was already loaded above.
            if ($record) {
                $record->update($data);
            } else {
                SaltAssessment::create($data);
            }

            return redirect()->route('admin.report-progress', [
                'fiscal_year' => $request->fiscal_year,
                'quarter' => $request->quarter
            ])->with('success', 'บันทึกแบบรายงานเรียบร้อยแล้ว');
        } catch (\Exception $e) {
            return redirect()->back()
                ->withInput()
                ->withErrors(['error' => 'เกิดข้อผิดพลาดในการบันทึกข้อมูล: ' . $e->getMessage()]);
        }
    }

    /**
     * Remove an attached PDF from one field of one quarter's record, immediately
     * (AJAX) — no need to also submit/save the whole report form. Only clears the
     * DB reference for that exact quarter's record; it never touches other
     * quarters, since files no longer carry forward between them.
     */
    public function removeReportFile(Request $request)
    {
        $allowedFields = [
            'ans_1_file', 'ans_2_file', 'ans_3_file', 'ans_4_file',
            'ans_5_1_file', 'ans_5_2_file', 'ans_5_3_file', 'ans_5_4_file', 'ans_5_5_file',
        ];

        $request->validate([
            'fiscal_year' => 'required|integer',
            'quarter' => 'required|in:1,2,3,4',
            'field' => 'required|string|in:' . implode(',', $allowedFields),
        ]);

        $currentUser = auth()->user();
        $agencyUserIds = $this->getAgencyUserIds($currentUser);
        $fileKey = $request->input('field');

        if ($this->saltQuarterConfirmed($agencyUserIds, $request->fiscal_year, $request->quarter)) {
            return response()->json([
                'success' => false,
                'message' => 'ไตรมาสนี้ได้รับการยืนยันข้อมูลแล้ว จึงไม่สามารถลบไฟล์ได้',
            ], 403);
        }

        $record = SaltAssessment::whereIn('user_id', $agencyUserIds)
            ->where('fiscal_year', $request->fiscal_year)
            ->where('quarter', $request->quarter)
            ->first();

        if (!$record || empty($record->$fileKey)) {
            // Already gone (or nothing was ever saved for this quarter) — treat as success.
            return response()->json(['success' => true, 'message' => 'ไม่มีไฟล์อยู่แล้ว']);
        }

        $meta = is_array($record->reporter_metadata) ? $record->reporter_metadata : [];
        $meta[$fileKey] = null;

        $record->$fileKey = null;
        $record->reporter_metadata = $meta;
        $record->is_read = 0;
        $record->save();

        return response()->json(['success' => true, 'message' => 'ลบไฟล์เรียบร้อยแล้ว']);
    }

    /**
     * Get existing assessment data by year and quarter (AJAX).
     * Groups by agency: Province_id for rank 2 (สสจ.), District_id for rank 3+ (สสอ.).
     */
    public function getAssessmentData(Request $request)
    {
        $currentUser = auth()->user();

        // Mirrors storeReportProgress()'s target_user_id: once a Word
        // import is active for another office, the year/quarter selects
        // still need to read (and later save) that office's own existing
        // data instead of silently falling back to the logged-in admin's
        // own agency. Same Level 1 gate as storeReportProgress() - a
        // target_user_id from anyone else is ignored and the request
        // reads back their own agency's data instead.
        $scopeUser = ($currentUser->User_rank_id == 1 && $request->filled('target_user_id'))
            ? (User::find($request->target_user_id) ?: $currentUser)
            : $currentUser;

        // --- Determine agency peers based on rank ---
        $agencyUserIds = $this->getAgencyUserIds($scopeUser);

        // 1. Fetch ALL assessments for this agency & year
        $allAssessments = SaltAssessment::with(['user', 'confirmedBy'])
            ->whereIn('user_id', $agencyUserIds)
            ->where('fiscal_year', $request->fiscal_year)
            ->orderBy('quarter', 'asc')
            ->orderBy('updated_at', 'desc')
            ->get()
            ->groupBy('quarter'); // keyed by quarter

        // 2. Define Fields
        $fields = ['1', '2', '3', '4', '5_1', '5_2', '5_3', '5_4', '5_5', 'problems', 'suggestions'];

        $cumulativeData = [];
        $milestones = [];

        // Initialize blank data
        foreach ($fields as $f) {
            $isSpecial = in_array($f, ['problems', 'suggestions']);
            $detailField = $isSpecial ? $f : "ans_{$f}_detail";
            $cumulativeData[$detailField] = '';
            $cumulativeData[$detailField . '_reporter'] = null;
            $cumulativeData[$detailField . '_reporter_q'] = null;

            if (!$isSpecial) {
                $cumulativeData["ans_{$f}_file"] = '';
                $cumulativeData["ans_{$f}_file_url"] = '';
                $cumulativeData["ans_{$f}_file_name"] = '';
                $cumulativeData["ans_{$f}_file_reporter"] = null;
                $cumulativeData["ans_{$f}_file_reporter_q"] = null;
            }
            $milestones[$f] = null;
        }

        // 3. Loop for Milestones (Check WHOLE Year 1-4)
        for ($q = 1; $q <= 4; $q++) {
            if (!isset($allAssessments[$q]))
                continue;
            // Take the most recently updated record per quarter
            $record = $allAssessments[$q]->sortByDesc('updated_at')->first();
            foreach ($fields as $f) {
                if (in_array($f, ['problems', 'suggestions']))
                    continue;
                $hasDetail = !empty($record->{"ans_{$f}_detail"});
                $hasFile = !empty($record->{"ans_{$f}_file"});
                if (($hasDetail || $hasFile) && $milestones[$f] === null) {
                    $milestones[$f] = $q;
                }
            }
        }

        $targetQuarter = (int) $request->quarter;

        // only=1 (เปิดจากปุ่ม "ทำแบบประเมิน" ในตารางรายการ): แสดงเฉพาะข้อมูลที่บันทึกไว้ในไตรมาสนั้นจริง
        // ไม่ดึงข้อความจากไตรมาสก่อนหน้ามาแสดงต่อ (carry-forward)
        $carryUntil = $request->boolean('only') ? 1 : $targetQuarter;

        // 4. Process Cumulative Data (Independent Column Flow)
        foreach ($fields as $f) {
            $isSpecial = in_array($f, ['problems', 'suggestions']);
            $detailField = $isSpecial ? $f : "ans_{$f}_detail";
            $fileField = $isSpecial ? null : "ans_{$f}_file";

            // --- A. Process DETAIL (Text) Flow ---
            if (!$isSpecial) {
                $carried = null;
                $carriedQ = null;
                $carriedReporter = null;
                // Search previous quarters: find WHICH record has this specific field filled
                for ($q = 1; $q < $carryUntil; $q++) {
                    if (!isset($allAssessments[$q]))
                        continue;
                    // Search all records in this quarter for this specific field
                    foreach ($allAssessments[$q]->sortByDesc('updated_at') as $rec) {
                        if (!empty($rec->$detailField)) {
                            $carried = $rec->$detailField;
                            $carriedQ = $q;
                            $carriedReporter = $rec->reporter_metadata[$detailField] ?? ($rec->user ? trim(($rec->user->prefix ?? '') . ($rec->user->User_firstname ?? '') . ' ' . ($rec->user->User_lastname ?? '')) : null);
                            break; // Found the latest one for this field in this quarter
                        }
                    }
                }

                // Search current quarter: find WHICH record has this specific field filled
                $currentRec = null;
                if (isset($allAssessments[$targetQuarter])) {
                    foreach ($allAssessments[$targetQuarter]->sortByDesc('updated_at') as $rec) {
                        if (!empty($rec->$detailField)) {
                            $currentRec = $rec;
                            break;
                        }
                    }
                }

                if ($currentRec) {
                    $cumulativeData[$detailField] = $currentRec->$detailField;
                    $cumulativeData[$detailField . '_reporter'] = $currentRec->reporter_metadata[$detailField] ?? trim(($currentRec->user->prefix ?? '') . ($currentRec->user->User_firstname ?? '') . ' ' . ($currentRec->user->User_lastname ?? ''));
                    $cumulativeData[$detailField . '_reporter_q'] = $targetQuarter;
                } elseif ($carried !== null) {
                    $cumulativeData[$detailField] = $carried;
                    $cumulativeData[$detailField . '_reporter'] = $carriedReporter;
                    $cumulativeData[$detailField . '_reporter_q'] = $carriedQ;
                }
            } else {
                // Special fields: per-quarter only (no carry)
                $currentSpecialRec = null;
                if (isset($allAssessments[$targetQuarter])) {
                    foreach ($allAssessments[$targetQuarter]->sortByDesc('updated_at') as $rec) {
                        if (!empty($rec->$detailField)) {
                            $currentSpecialRec = $rec;
                            break;
                        }
                    }
                }
                $cumulativeData[$detailField] = $currentSpecialRec ? ($currentSpecialRec->$detailField ?? '') : '';
                $cumulativeData[$detailField . '_reporter'] = $currentSpecialRec
                    ? ($currentSpecialRec->reporter_metadata[$detailField] ?? ($currentSpecialRec->user ? trim(($currentSpecialRec->user->prefix ?? '') . ($currentSpecialRec->user->User_firstname ?? '') . ' ' . ($currentSpecialRec->user->User_lastname ?? '')) : null))
                    : null;
                $cumulativeData[$detailField . '_reporter_q'] = $currentSpecialRec ? $targetQuarter : null;
            }

            // --- B. Process FILE Flow ---
            // Files do NOT carry forward from previous quarters (unlike detail
            // text): a PDF only shows up on the quarter it was actually uploaded
            // to, so removing it there is final and it never reappears elsewhere.
            if ($fileField) {
                $currentFileRec = null;
                if (isset($allAssessments[$targetQuarter])) {
                    foreach ($allAssessments[$targetQuarter]->sortByDesc('updated_at') as $rec) {
                        if (!empty($rec->$fileField)) {
                            $currentFileRec = $rec;
                            break;
                        }
                    }
                }

                if ($currentFileRec) {
                    $finalFile = $currentFileRec->$fileField;
                    $finalFileReporter = $currentFileRec->reporter_metadata[$fileField] ?? trim(($currentFileRec->user->prefix ?? '') . ($currentFileRec->user->User_firstname ?? '') . ' ' . ($currentFileRec->user->User_lastname ?? ''));

                    $cumulativeData[$fileField] = $finalFile;
                    $cumulativeData[$fileField . '_url'] = asset('storage/' . $finalFile);
                    $cumulativeData[$fileField . '_name'] = basename($finalFile);
                    $cumulativeData[$fileField . '_reporter'] = $finalFileReporter;
                    $cumulativeData[$fileField . '_reporter_q'] = $targetQuarter;
                }
            }
        }

        // Check if the target agency's own record for this quarter exists
        // (defaults to the logged-in user - see $scopeUser above).
        $ownRecord = SaltAssessment::where('user_id', $scopeUser->id)
            ->where('fiscal_year', $request->fiscal_year)
            ->where('quarter', $targetQuarter)
            ->orderBy('updated_at', 'desc')
            ->first();
        $exists = (bool) $ownRecord;

        // สถานะการยืนยันข้อมูลของทั้ง 4 ไตรมาส (ใช้แสดงแถบยืนยันในหน้าแบบรายงาน)
        $quarterStates = [];
        for ($q = 1; $q <= 4; $q++) {
            $rec = isset($allAssessments[$q])
                ? ($allAssessments[$q]->first(fn ($a) => (bool) $a->confirmed_at) ?? $allAssessments[$q]->first())
                : null;
            $quarterStates[$q] = $this->saltQuarterState($rec);
        }

        return response()->json([
            'exists' => $exists,
            // id ของรายการไตรมาสนี้ (ใช้กับปุ่ม Export PDF ในหน้ากรอกแบบรายงาน) - null ถ้ายังไม่เคยบันทึก
            'assessment_id' => $ownRecord ? $ownRecord->id : null,
            'data' => $cumulativeData,
            'milestones' => $milestones,
            'quarters' => $quarterStates,
            'can_confirm' => $currentUser->User_rank_id == 1,
            // ไตรมาสที่ยืนยันข้อมูลแล้ว ปิดช่องแก้ไขทุกบทบาท (สคร. ปลดล็อกด้วยปุ่ม "ยกเลิกการยืนยัน")
            'locked' => !empty($quarterStates[$targetQuarter]['confirmed']),
            // App timezone (config/app.php) is UTC, so a bare date('H:i:s')
            // here would show the server's UTC clock instead of Thailand
            // local time - this is what the "ซิงค์ล่าสุด" pill displays,
            // so it needs to be explicitly converted for that audience.
            'server_time' => now('Asia/Bangkok')->format('H:i:s')
        ]);
    }

    /**
     * ไตรมาสนี้ของหน่วยงานถูก สคร. (level 1) ยืนยันข้อมูลแล้วหรือไม่ — ถ้าใช่ ผู้ตอบแบบรายงานแก้ไขไม่ได้
     */
    private function saltQuarterConfirmed(array $agencyUserIds, $fiscalYear, $quarter): bool
    {
        return SaltAssessment::whereIn('user_id', $agencyUserIds)
            ->where('fiscal_year', $fiscalYear)
            ->where('quarter', $quarter)
            ->whereNotNull('confirmed_at')
            ->exists();
    }

    /**
     * สถานะการยืนยันของแบบรายงาน 1 ไตรมาส (null = ยังไม่มีข้อมูลที่บันทึกไว้)
     */
    private function saltQuarterState($rec): array
    {
        $confirmedAt = $rec && $rec->confirmed_at
            ? $rec->confirmed_at->copy()->timezone('Asia/Bangkok')->addYears(543)->format('d/m/Y H:i')
            : null;

        return [
            'id' => $rec->id ?? null,
            'exists' => (bool) $rec,
            'confirmed' => (bool) ($rec && $rec->confirmed_at),
            'confirmed_at' => $confirmedAt,
            'confirmed_by' => $rec && $rec->confirmed_at ? ($rec->confirmedBy->name ?? null) : null,
        ];
    }

    /**
     * Get all user IDs in the same agency as the given user.
     * - Rank 2 (สสจ.): same Province_id
     * - Rank 3+ (สสอ./others): same District_id
     */
    private array $agencyIdsMemo = [];

    private function getAgencyUserIds($user): array
    {
        if (!$user)
            return [];
        // คำนวณครั้งเดียวต่อคำขอ (เดิมถูกเรียกซ้ำสิบกว่าครั้งต่อหน้า ครั้งละ 1 query)
        // แคชตามกลุ่มหน่วยงาน (จังหวัด/อำเภอ) ไม่ใช่ตามผู้ใช้ - ผู้ใช้หลายคนในอำเภอเดียวกันใช้ผลเดียวกัน
        $memoKey = $user->User_rank_id == 2 ? 'p' . $user->Province_id
            : ($user->User_rank_id >= 3 ? 'd' . $user->District_id : 'u' . $user->id);
        if (isset($this->agencyIdsMemo[$memoKey])) {
            return $this->agencyIdsMemo[$memoKey];
        }
        return $this->agencyIdsMemo[$memoKey] = $this->computeAgencyUserIds($user);
    }

    private function computeAgencyUserIds($user): array
    {
        $query = \App\Models\User::query();

        if ($user->User_rank_id == 2) {
            // สสจ. — group by province
            $query->where('Province_id', $user->Province_id);
        } elseif ($user->User_rank_id >= 3) {
            // สสอ. and others — group by district
            $query->where('District_id', $user->District_id);
        } else {
            return [$user->id];
        }

        return $query->pluck('id')->toArray();
    }



    /**
     * Show the kidney DHB assessment page.
     */
    public function kidneyDHB(Request $request)
    {
        $user = auth()->user();

        // ระดับ 2-4 ต้องเลือกพื้นที่ดำเนินงานก่อนจึงจะทำแบบประเมินได้ (เปิดรายการเดิมเพื่อแก้ไข
        // ผ่าน ?operating_area= ยังเข้าได้) - ส่งกลับหน้ารายการพร้อมป๊อปอัปแจ้งเตือน
        if (!$request->filled('operating_area') && $this->needsAreaSelection($user)) {
            return redirect()->route('admin.kidney-dhb-list')->with('need_area', true);
        }
        $agencyName = $this->resolveAgencyName($user);

        // Distinct operating areas already reported by this agency (across
        // every fiscal year - a รพ.สต. or ตำบล doesn't change with the
        // reporting year), so the form can offer them in a dropdown instead
        // of asking the user to retype an existing area's name.
        $agencyUserIds = $this->getAgencyUserIds($user);
        $operatingAreas = KidneyAssessment::whereIn('user_id', $agencyUserIds)
            ->whereNotNull('operating_area')
            ->where('operating_area', '!=', '')
            ->distinct()
            ->orderBy('operating_area')
            ->pluck('operating_area');

        // Word-import is restricted to Level 1 (User_rank_id == 1, "สคร.")
        // accounts only - the same rank gate already used everywhere else
        // in this controller (see e.g. exportKidneyDHBExcel below) - since
        // it lets the importer save data under ANY other office's agency.
        $canImportWord = $user->User_rank_id == 1;

        // Province list + rough village-centroid coordinates: originally
        // only loaded for the Word-import agency picker (cascades ประเภท
        // หน่วยงาน -> จังหวัด -> อำเภอ -> โรงพยาบาล/ชื่อหน่วยงาน, resolved to a
        // User account through resolveKidneyDhbTargetAgency() below), now
        // also needed by every rank for the "พื้นที่การดำเนินงาน" field's own
        // ปักหน่วยงานพื้นที่ใหม่ cascade+map - so it's no longer gated behind
        // $canImportWord.
        $provinces = \App\Support\AdminCache::provinces();
        $provinceCoords = $this->operatingAreaVillageCentroids('province_id');

        // Registered operating-area pins (จัดการพื้นที่ดำเนินงาน), scoped to
        // the signed-in user's own province, for the "เลือกจากพื้นที่ที่มีอยู่"
        // half of that same field - see operatingAreaPinsForUser().
        $registeredPins = $this->operatingAreaPinsForUser($user, true);

        // This agency's own previously-used free-text areas (never
        // formally registered as a pin) - see usedOperatingAreasForAgency().
        // Together with $registeredPins above, this is the full list the
        // oaTopAreaSelect quick-jump dropdown offers.
        // Only the areas picked on "เลือกพื้นที่ดำเนินงาน" are offered now, so the
        // free-text "used areas" group is left empty.
        $usedOperatingAreas = collect();

        // Same idea as $registeredPins above, but for the OTHER half of the
        // field - pre-filling "ปักหน่วยงานพื้นที่ใหม่"'s own จังหวัด/อำเภอ/ตำบล
        // cascade with the reporting office's own location.
        $userOwnLocation = $this->operatingAreaUserLocation($user);

        return view('admin.kidney-dhb', [
            'fiscal_year' => $request->query('fiscal_year'),
            'quarter' => $request->query('quarter'),
            'agencyName' => $agencyName,
            'operatingAreas' => $operatingAreas,
            'operatingArea' => $request->query('operating_area'),
            'provinces' => $provinces,
            'provinceCoords' => $provinceCoords,
            'registeredPins' => $registeredPins,
            'usedOperatingAreas' => $usedOperatingAreas,
            'recordedAreas' => $this->recordedAreasForAgency($user),
            // Pin chosen on "เลือกพื้นที่ดำเนินงาน" (button "เลือกพื้นที่นี้") - preselected on load.
            'selectedPinId' => ctype_digit((string) $request->query('pin')) ? (int) $request->query('pin') : null,
            'userOwnLocation' => $userOwnLocation,
            'canImportWord' => $canImportWord,
            // ปีงบประมาณในช่อง "ปีงบประมาณของรายงานนี้" (นำเข้า Word) - ใช้รายการเดียวกับหน้า "จัดการปีงบประมาณ" ของ พชอ.ไต
            'importYears' => FiscalYear::selectableYearsFor('kidney', \App\Support\AdminCache::distinct(\App\Models\KidneyAssessment::class, 'fiscal_year'))->all(),
        ]);
    }

    /**
     * Human-readable agency/office name for a user, matching the labels
     * already used across the kidney-dhb form and the Word-import agency
     * picker (a จังหวัด account, an อำเภอ account, or a facility name).
     */
    private function resolveAgencyName($user)
    {
        $agencyName = $user->Con_name;
        $provinceName = $user->province ? $user->province->province_name : '';
        $districtName = $user->district ? $user->district->district_name : '';

        if ($user->User_rank_id == 2) {
            $agencyName = 'สํานักงานสาธารณสุขจังหวัด' . $provinceName;
        } elseif ($user->User_rank_id == 3) {
            $agencyName = 'สํานักงานสาธารณสุขอำเภอ' . $districtName;
        } elseif ($user->User_rank_id == 4) {
            $agencyName = $user->subdistrictHospital ? $user->subdistrictHospital->hospital_name : $user->Con_name;
        } elseif ($user->User_rank_id == 5) {
            $agencyName = $user->hospital ? $user->hospital->hos_name : $user->Con_name;
        }

        return $agencyName;
    }

    /**
     * Store kidney DHB assessment data.
     */
    public function storeKidneyDHB(Request $request)
    {
        $request->validate([
            'fiscal_year' => 'required|integer',
            'quarter' => 'required|in:1,2,3,4',
            'operating_area' => 'nullable|string|max:255',
            'operating_area_pin_id' => 'nullable|integer|exists:kidney_operating_area_pins,id',
            'operating_area_province_id' => 'nullable|integer',
            'operating_area_district_id' => 'nullable|integer',
            'operating_area_tambon_id' => 'nullable|integer',
            'operating_area_village_id' => 'nullable|integer',
            'operating_area_village_name_snapshot' => 'nullable|string|max:255',
            'operating_area_latitude' => 'nullable|numeric|between:-90,90',
            'operating_area_longitude' => 'nullable|numeric|between:-180,180',
            'extra_pin_ids' => 'nullable|array|max:30',
            'extra_pin_ids.*' => 'integer|exists:kidney_operating_area_pins,id',
            'target_user_id' => 'nullable|integer|exists:users,id',
            'import_reporter_name' => 'nullable|string|max:255',
            'import_reporter_position' => 'nullable|string|max:255',
            'import_reporter_phone' => 'nullable|string|max:50',
            'import_reporter_email' => 'nullable|string|max:255',
        ] + \App\Services\KidneyImageService::validationRules(), \App\Services\KidneyImageService::validationMessages());

        $currentUser = auth()->user();
        // Word-import support: when importing a historical report on
        // behalf of another office (the docx might belong to a different
        // province/district than whoever is at the keyboard right now),
        // target_user_id points the save at THAT office's agency scope
        // instead of the logged-in admin's own. Everything is still
        // attributed (in reporter_metadata below) to $currentUser - that
        // tracks who actually entered it, which is a separate concern
        // from whose agency the data belongs to.
        // Only a Level 1 user may save on behalf of another office - the
        // same gate the kidney-dhb Word-import UI itself is hidden behind
        // (see AdminController::kidneyDHB()'s $canImportWord). Anyone else
        // is saved under their own agency even if target_user_id is present
        // in the request (e.g. a hand-crafted POST).
        $targetUser = ($currentUser->User_rank_id == 1 && $request->filled('target_user_id'))
            ? (User::find($request->target_user_id) ?: $currentUser)
            : $currentUser;
        $agencyUserIds = $this->getAgencyUserIds($targetUser);
        $currentName = trim(($currentUser->prefix ?? '') . ($currentUser->User_firstname ?? '') . ' ' . ($currentUser->User_lastname ?? '')) ?: ($currentUser->name ?? 'ไม่ระบุชื่อ');

        // Word-import attribution: a historical report is filled in by
        // whoever the DOCUMENT names as its reporter, not by the Level 1
        // admin who happens to be doing the importing at the keyboard - so
        // when this save carries the parsed reporter's name (only possible
        // for a genuine Level 1 import-on-behalf-of-another-office save,
        // same gate as $targetUser above), that name is what gets written
        // into each changed field's reporter_metadata instead of
        // $currentName, and the full reporter contact block (name/position/
        // phone/email) is kept on the record for reference, alongside who
        // actually performed the import.
        $isImportSave = $currentUser->User_rank_id == 1
            && $request->filled('target_user_id')
            && $request->filled('import_reporter_name');
        $attributedName = $isImportSave
            ? trim($request->input('import_reporter_name'))
            : $currentName;
        $importReporterMeta = $isImportSave ? [
            'name' => trim($request->input('import_reporter_name')),
            'position' => trim((string) $request->input('import_reporter_position', '')) ?: null,
            'phone' => trim((string) $request->input('import_reporter_phone', '')) ?: null,
            'email' => trim((string) $request->input('import_reporter_email', '')) ?: null,
            'imported_by' => $currentName,
            'imported_at' => date('d/m/Y H:i'),
        ] : null;

        // Normalize the submitted operating area: blank means "no specific
        // area" (same as a legacy single-area report), stored as NULL so it
        // matches the unique index and every lookup below consistently -
        // never as an empty string.
        $operatingArea = trim((string) $request->input('operating_area', ''));
        $operatingArea = $operatingArea === '' ? null : $operatingArea;

        // Find existing record for the agency AND this specific operating
        // area - each area now keeps its own row instead of several areas
        // sharing (and overwriting) one.
        $record = KidneyAssessment::whereIn('user_id', $agencyUserIds)
            ->where('fiscal_year', $request->fiscal_year)
            ->where('quarter', $request->quarter)
            ->where('operating_area', $operatingArea)
            ->first();

        // ไตรมาสที่ สคร. (level 1) ยืนยันข้อมูลแล้ว ผู้ตอบแบบประเมินแก้ไข/บันทึกทับไม่ได้
        if ($record && $record->confirmed_at && $currentUser->User_rank_id != 1) {
            return redirect()->back()->withInput()->with('error',
                'ไตรมาส ' . $request->quarter . ' ได้รับการยืนยันข้อมูลแล้ว จึงไม่สามารถแก้ไขได้ หากต้องแก้ไขกรุณาติดต่อ สคร. เพื่อยกเลิกการยืนยัน');
        }

        $data = $record ? $record->toArray() : [
            'fiscal_year' => $request->fiscal_year,
            'quarter' => $request->quarter,
            'operating_area' => $operatingArea,
            'user_id' => $targetUser->id,
            'reporter_metadata' => []
        ];

        // Ensure array for metadata
        $meta = is_array($data['reporter_metadata'] ?? null) ? $data['reporter_metadata'] : [];

        $allFields = [
            'operating_area',
            'category_1',
            'category_2',
            'category_3',
            'category_4',
            'category_5',
            'category_6',
            'category_7',
            'category_8_1',
            'category_8_2',
            'category_8_3',
            'problems_obstacles',
            'recommendations_opportunities'
        ];

        foreach ($allFields as $f) {
            if ($request->has($f)) {
                // Normalize newlines and trim for accurate comparison
                $newVal = trim(str_replace("\r\n", "\n", preg_replace('/<br\s*\/?>/i', "\n", (string) ($request->input($f) ?? ''))));
                $oldVal = isset($record->$f) ? trim(str_replace("\r\n", "\n", $record->$f ?? '')) : '';

                if ($oldVal !== $newVal) {
                    // FIELD CHANGED: Update value and metadata
                    $data[$f] = $newVal;
                    $meta[$f] = [
                        'name' => $attributedName,
                        'time' => date('d/m/Y H:i')
                    ];
                } elseif (!empty($oldVal) && !isset($meta[$f]) && $record) {
                    // FIELD UNCHANGED but missing legacy metadata:
                    // Backfill with record's current owner/time to "freeze" it from jumping to "just now"
                    $prevReporter = trim(($record->user->prefix ?? '') . ($record->user->User_firstname ?? '') . ' ' . ($record->user->User_lastname ?? '')) ?: ($record->user->name ?? 'ไม่ระบุชื่อ');
                    $meta[$f] = [
                        'name' => $prevReporter,
                        'time' => $record->updated_at ? $record->updated_at->format('d/m/Y H:i') : date('d/m/Y H:i')
                    ];
                }
            }
        }

        // If the area field was edited down to blank, keep that as NULL
        // (not ''), so this record stays consistent with the whereNull-based
        // lookups above and the unique index, instead of silently forking
        // into an unreachable "empty string" area.
        if (array_key_exists('operating_area', $data) && trim((string) $data['operating_area']) === '') {
            $data['operating_area'] = null;
        }

        // Handle category files separately
        $catFields = [
            'category_1',
            'category_2',
            'category_3',
            'category_4',
            'category_5',
            'category_6',
            'category_7',
            'category_8_1',
            'category_8_2',
            'category_8_3'
        ];

        foreach ($catFields as $cat) {
            $fileField = $cat . '_file';

            // 1. Check if uploading NEW file now
            if ($request->hasFile($fileField)) {
                $file = $request->file($fileField);
                $filename = time() . '_' . $fileField . '.' . $file->getClientOriginalExtension();
                $path = $file->storeAs('kidney_assessments', $filename, 'public');
                $data[$fileField] = $path;
                $meta[$fileField] = [
                    'name' => $attributedName,
                    'time' => date('d/m/Y H:i')
                ];
            }
            // 2. Check if using EXISTING file (hidden input from frontend)
            elseif ($request->filled($fileField . '_existing_file')) {
                $path = $request->input($fileField . '_existing_file');
                $data[$fileField] = $path;

                // Backfill metadata for existing file if missing
                if ($record && !empty($path) && !isset($meta[$fileField])) {
                    $prevReporter = trim(($record->user->prefix ?? '') . ($record->user->User_firstname ?? '') . ' ' . ($record->user->User_lastname ?? '')) ?: ($record->user->name ?? 'ไม่ระบุชื่อ');
                    $meta[$fileField] = [
                        'name' => $prevReporter,
                        'time' => $record->updated_at ? $record->updated_at->format('d/m/Y H:i') : date('d/m/Y H:i')
                    ];
                }
            }
            // 3. FALLBACK: null
            else {
                $data[$fileField] = null;
            }
        }

        if ($importReporterMeta !== null) {
            $meta['_import_reporter'] = $importReporterMeta;
        }
        $data['reporter_metadata'] = $meta;
        // Overall ownership: the target agency (defaults to whoever is
        // logged in, unless this save is a Word import filed on behalf of
        // a different office - see $targetUser above).
        $data['user_id'] = $targetUser->id;
        $data['is_read'] = 0; // Mark as unread on every save/update

        // Operating-area location (the "พื้นที่การดำเนินงาน" pin widget):
        // written straight through, like user_id/is_read above, instead of
        // through the per-field changed-metadata loop - the widget's
        // hidden inputs always carry the FULL current pick (whether just
        // restored from this same record or freshly chosen), never a
        // partial diff, so there's nothing to compare against.
        $data['operating_area_pin_id'] = $request->filled('operating_area_pin_id') ? (int) $request->input('operating_area_pin_id') : null;
        // หมุดแสดงผล (SuperAdmin เพิ่มไว้แสดงอย่างเดียว) เอามารายงานไม่ได้
        if ($data['operating_area_pin_id'] && OperatingAreaPin::hasDisplayColumns()
            && OperatingAreaPin::where('id', $data['operating_area_pin_id'])->where('display_only', true)->exists()) {
            throw \Illuminate\Validation\ValidationException::withMessages([
                'operating_area_pin_id' => 'พื้นที่นี้เป็นหมุดแสดงผล ดูข้อมูลได้อย่างเดียว เลือกไปรายงานไม่ได้',
            ]);
        }
        $data['operating_area_province_id'] = $request->filled('operating_area_province_id') ? (int) $request->input('operating_area_province_id') : null;
        $data['operating_area_district_id'] = $request->filled('operating_area_district_id') ? (int) $request->input('operating_area_district_id') : null;
        $data['operating_area_tambon_id'] = $request->filled('operating_area_tambon_id') ? (int) $request->input('operating_area_tambon_id') : null;
        $data['operating_area_village_id'] = $request->filled('operating_area_village_id') ? (int) $request->input('operating_area_village_id') : null;
        $data['operating_area_village_name_snapshot'] = $request->filled('operating_area_village_name_snapshot') ? trim($request->input('operating_area_village_name_snapshot')) : null;
        $data['operating_area_latitude'] = $request->filled('operating_area_latitude') ? $request->input('operating_area_latitude') : null;
        $data['operating_area_longitude'] = $request->filled('operating_area_longitude') ? $request->input('operating_area_longitude') : null;

        // รายงาน 1 ฉบับครอบคลุมได้หลายพื้นที่: พื้นที่หลักอยู่ที่ operating_area* ข้างบน
        // ส่วนพื้นที่อื่นเก็บรหัสหมุดไว้ที่ reporter_metadata['_extra_pin_ids']
        // (ฟอร์มส่ง extra_pin_ids_submitted มาทุกครั้ง - ถ้าไม่ส่ง (เช่นนำเข้า Word) คงค่าเดิมไว้)
        if ($request->has('extra_pin_ids_submitted')) {
            $extraIds = collect((array) $request->input('extra_pin_ids', []))
                ->map(fn($v) => (int) $v)
                ->filter(fn($v) => $v > 0 && $v !== (int) ($data['operating_area_pin_id'] ?? 0))
                ->unique()
                ->values()
                ->all();

            if (!empty($extraIds)) {
                if (empty($data['operating_area_pin_id'])) {
                    throw \Illuminate\Validation\ValidationException::withMessages([
                        'extra_pin_ids' => 'กรุณาเลือกพื้นที่หลักก่อน จึงจะเพิ่มพื้นที่อื่นในรายงานเดียวกันได้',
                    ]);
                }
                $allowedPins = $this->operatingAreaPinsForUser($targetUser, true)
                    ->filter(fn($p) => empty($p['view_only']))
                    ->pluck('id')->map(fn($v) => (int) $v)->all();
                $notAllowed = array_values(array_diff($extraIds, $allowedPins));
                if (!empty($notAllowed)) {
                    throw \Illuminate\Validation\ValidationException::withMessages([
                        'extra_pin_ids' => 'มีพื้นที่ที่ไม่ได้อยู่ในรายการพื้นที่ดำเนินงานของหน่วยงาน หรือเป็นหมุดแสดงผล จึงเลือกรวมในรายงานไม่ได้',
                    ]);
                }
            }

            // พื้นที่ (หลัก/อื่น) ของรายงานนี้ ต้องไม่ซ้ำกับที่อยู่ในรายงานอื่นของหน่วยงานในปี/ไตรมาสเดียวกัน
            // - กันรายงานซ้ำซ้อนพื้นที่เดียวกัน
            $mine = array_values(array_unique(array_filter(array_merge(
                [(int) ($data['operating_area_pin_id'] ?? 0)],
                $extraIds
            ))));
            if (!empty($mine)) {
                $takenBy = [];
                $others = KidneyAssessment::whereIn('user_id', $agencyUserIds)
                    ->where('fiscal_year', $request->fiscal_year)
                    ->where('quarter', $request->quarter)
                    ->when($record, fn($q) => $q->where('id', '!=', $record->id))
                    ->get(['id', 'operating_area', 'operating_area_pin_id', 'reporter_metadata']);
                foreach ($others as $o) {
                    foreach (array_merge([(int) $o->operating_area_pin_id], \App\Support\KidneyAreaList::extraPinIds($o)) as $pid) {
                        if ($pid > 0) {
                            $takenBy[$pid] = $o->operating_area;
                        }
                    }
                }
                $clash = array_values(array_intersect($mine, array_keys($takenBy)));
                if (!empty($clash)) {
                    $names = OperatingAreaPin::whereIn('id', $clash)->get()->map(fn($p) => $p->label ?: ($p->village_name_snapshot ?? ('#' . $p->id)))->implode(', ');
                    throw \Illuminate\Validation\ValidationException::withMessages([
                        'extra_pin_ids' => 'พื้นที่ ' . $names . ' อยู่ในรายงานอื่นของไตรมาส ' . $request->quarter . ' แล้ว จึงเลือกซ้ำไม่ได้',
                    ]);
                }
            }

            if (!empty($extraIds)) {
                $meta[\App\Support\KidneyAreaList::META_KEY] = $extraIds;
            } else {
                unset($meta[\App\Support\KidneyAreaList::META_KEY]);
            }
            $data['reporter_metadata'] = $meta;
        }


        try {
            $savedAssessment = KidneyAssessment::updateOrCreate(
                [
                    'id' => $record->id ?? null,
                ],
                $data
            );

            // รูปภาพประกอบ (สูงสุด 3 รูปต่อข้อ) - เพิ่ม/แทนที่/ลบตามที่ฟอร์มส่งมา
            $imageErrors = app(\App\Services\KidneyImageService::class)->sync($savedAssessment, $request, $currentUser->id);

            $redirectParams = [
                'fiscal_year' => $request->fiscal_year,
                'quarter' => $request->quarter,
            ];
            if ($operatingArea !== null) {
                $redirectParams['operating_area'] = $operatingArea;
            }

            $redirect = redirect()->route('admin.kidney-dhb', $redirectParams)
                ->with('success', 'บันทึกข้อมูล พชอ.ไต เรียบร้อยแล้ว');
            if (!empty($imageErrors)) {
                // บันทึกข้อความสำเร็จ แต่รูปบางส่วนไม่ผ่าน - แจ้งให้ชัดแทนที่จะเงียบ
                $redirect->with('error', 'บันทึกข้อความแล้ว แต่รูปภาพบางส่วนไม่ถูกบันทึก: ' . implode(' | ', $imageErrors));
            }

            return $redirect;
        } catch (\Exception $e) {
            return redirect()->back()
                ->withInput()
                ->withErrors(['error' => 'เกิดข้อผิดพลาดในการบันทึกข้อมูล: ' . $e->getMessage()]);
        }
    }

    /**
     * Get existing kidney DHB data (AJAX).
     */
    public function getKidneyDHBData(Request $request)
    {
        // Mirrors storeKidneyDHB()'s target_user_id: once a Word import is
        // active for another office, the year/quarter selects still need
        // to read (and later save) that office's own existing data instead
        // of silently falling back to the logged-in admin's own agency.
        // Same Level 1 gate as storeKidneyDHB() above - target_user_id from
        // anyone else is ignored and the request reads back their own
        // agency's data instead.
        $scopeUser = (auth()->user()->User_rank_id == 1 && $request->filled('target_user_id'))
            ? (User::find($request->target_user_id) ?: auth()->user())
            : auth()->user();
        $agencyUserIds = $this->getAgencyUserIds($scopeUser);

        // Same blank-means-NULL normalization as storeKidneyDHB(), so the
        // lookup here lines up with how records were actually saved.
        $requestedArea = trim((string) $request->input('operating_area', ''));
        $requestedArea = $requestedArea === '' ? null : $requestedArea;

        // Scoping by operating_area here (in addition to fiscal_year) is what
        // makes the Q1→Q4 cumulative "flow" below count separately per area,
        // instead of blending every area in the district together.
        $allAssessments = KidneyAssessment::with('user')->whereIn('user_id', $agencyUserIds)
            ->where('fiscal_year', $request->fiscal_year)
            ->where('operating_area', $requestedArea)
            ->get()
            ->keyBy('quarter');

        $selectedAssessment = $allAssessments->get($request->quarter);

        $categories = [
            'category_1',
            'category_2',
            'category_3',
            'category_4',
            'category_5',
            'category_6',
            'category_7',
            'category_8_1',
            'category_8_2',
            'category_8_3',
        ];

        // Per-Quarter Fields (Notes) — plus "operating_area" (the area/unit
        // that carried out the work for this specific report), which is
        // also one value per fiscal_year+quarter with no cross-quarter
        // carry-forward, same as the two note fields below.
        $noteFields = [
            'operating_area',
            'problems_obstacles',
            'recommendations_opportunities'
        ];

        $cumulativeData = [];
        $milestones = [];
        $globalMilestones = [];
        $disabledFields = [];
        $isFromTemplate = false;

        // Calculate Global Milestones (Yearly Overview)
        foreach ($course = [1, 2, 3, 4] as $q) {
            if (!isset($allAssessments[$q]))
                continue;
            foreach ($categories as $cat) {
                // Check if Text OR File exists
                $hasText = !empty($allAssessments[$q]->$cat);
                $fileCol = $cat . '_file';
                $hasFile = !empty($allAssessments[$q]->$fileCol);

                if (($hasText || $hasFile) && !isset($globalMilestones[$cat])) {
                    $globalMilestones[$cat] = $q;
                }
            }
        }

        // Process All Fields (Categories + Notes) with Independent Column Flow
        $allFields = array_merge($categories, $noteFields);

        foreach ($allFields as $f) {
            $isNote = in_array($f, $noteFields);
            $textCol = $f;
            $fileCol = $isNote ? null : $f . '_file';

            // Initialize
            $cumulativeData[$textCol] = '';
            if ($fileCol) {
                $cumulativeData[$fileCol] = '';
                $cumulativeData[$fileCol . '_url'] = '';
                $cumulativeData[$fileCol . '_name'] = '';
            }

            // Default: Editable
            $disabledFields[$textCol] = false;
            if ($fileCol)
                $disabledFields[$fileCol] = false;

            // --- A. Process TEXT Flow ---
            if (!$isNote) {
                $winner = null;
                $maxUpdate = -1;
                for ($q = 1; $q <= (int) $request->quarter; $q++) {
                    if (isset($allAssessments[$q]) && !empty($allAssessments[$q]->$textCol)) {
                        $time = $allAssessments[$q]->updated_at ? $allAssessments[$q]->updated_at->timestamp : 0;
                        if ($time >= $maxUpdate) {
                            $winner = $allAssessments[$q];
                            $maxUpdate = $time;
                        }
                    }
                }
                if ($winner) {
                    $cumulativeData[$textCol] = $winner->$textCol;
                    $milestones[$f] = $winner->quarter;
                    if ($winner->quarter != $request->quarter) {
                        $isFromTemplate = true;
                    }

                    // Only provide reporter if field is not empty after trim
                    if (!empty(trim($winner->$textCol ?? ''))) {
                        $m = $winner->reporter_metadata[$textCol] ?? null;
                        if (is_array($m)) {
                            $cumulativeData[$textCol . '_reporter'] = $m['name'] ?? null;
                            $cumulativeData[$textCol . '_updated_at'] = $m['time'] ?? ($winner->updated_at ? $winner->updated_at->format('d/m/Y H:i') : null);
                        } else {
                            $fallbackName = trim(($winner->user->prefix ?? '') . ($winner->user->User_firstname ?? '') . ' ' . ($winner->user->User_lastname ?? '')) ?: ($winner->user->name ?? 'ไม่ระบุชื่อ');
                            $cumulativeData[$textCol . '_reporter'] = $m ?: $fallbackName;
                            $cumulativeData[$textCol . '_updated_at'] = $winner->updated_at ? $winner->updated_at->format('d/m/Y H:i') : null;
                        }
                    }
                }
            } else {
                // Notes (problems/recommendations) do not flow
                if ($selectedAssessment) {
                    $cumulativeData[$textCol] = $selectedAssessment->$textCol ?? '';
                    // Only provide reporter if field is not empty
                    if (!empty(trim($selectedAssessment->$textCol ?? ''))) {
                        $m = $selectedAssessment->reporter_metadata[$textCol] ?? null;
                        if (is_array($m)) {
                            $cumulativeData[$textCol . '_reporter'] = $m['name'] ?? null;
                            $cumulativeData[$textCol . '_updated_at'] = $m['time'] ?? ($selectedAssessment->updated_at ? $selectedAssessment->updated_at->format('d/m/Y H:i') : null);
                        } else {
                            $fallbackName = trim(($selectedAssessment->user->prefix ?? '') . ($selectedAssessment->user->User_firstname ?? '') . ' ' . ($selectedAssessment->user->User_lastname ?? '')) ?: ($selectedAssessment->user->name ?? 'ไม่ระบุชื่อ');
                            $cumulativeData[$textCol . '_reporter'] = $m ?: $fallbackName;
                            $cumulativeData[$textCol . '_updated_at'] = $selectedAssessment->updated_at ? $selectedAssessment->updated_at->format('d/m/Y H:i') : null;
                        }
                    }
                }
            }
            // --- B. Process FILE Flow ---
            if ($fileCol) {
                $fileWinner = null;
                $fileMaxUpdate = -1;
                for ($q = 1; $q <= (int) $request->quarter; $q++) {
                    if (isset($allAssessments[$q]) && !empty($allAssessments[$q]->$fileCol)) {
                        $time = $allAssessments[$q]->updated_at ? $allAssessments[$q]->updated_at->timestamp : 0;
                        if ($time >= $fileMaxUpdate) {
                            $fileWinner = $allAssessments[$q];
                            $fileMaxUpdate = $time;
                        }
                    }
                }
                if ($fileWinner) {
                    $cumulativeData[$fileCol] = $fileWinner->$fileCol;
                    $cumulativeData[$fileCol . '_url'] = asset('storage/' . $fileWinner->$fileCol);
                    $cumulativeData[$fileCol . '_name'] = basename($fileWinner->$fileCol);
                    if ($fileWinner->quarter != $request->quarter) {
                        $isFromTemplate = true;
                    }

                    // Only provide reporter if file field is not empty
                    if (!empty($fileWinner->$fileCol)) {
                        $m = $fileWinner->reporter_metadata[$fileCol] ?? null;
                        if (is_array($m)) {
                            $cumulativeData[$fileCol . '_reporter'] = $m['name'] ?? null;
                            $cumulativeData[$fileCol . '_updated_at'] = $m['time'] ?? ($fileWinner->updated_at ? $fileWinner->updated_at->format('d/m/Y H:i') : null);
                        } else {
                            $fallbackName = trim(($fileWinner->user->prefix ?? '') . ($fileWinner->user->User_firstname ?? '') . ' ' . ($fileWinner->user->User_lastname ?? '')) ?: ($fileWinner->user->name ?? 'ไม่ระบุชื่อ');
                            $cumulativeData[$fileCol . '_reporter'] = $m ?: $fallbackName;
                            $cumulativeData[$fileCol . '_updated_at'] = $fileWinner->updated_at ? $fileWinner->updated_at->format('d/m/Y H:i') : null;
                        }
                    }
                }
            }
        }

        $reporterName = null;
        $updatedAt = null;
        if ($selectedAssessment) {
            $selectedAssessment->load('user');
            $reporterName = trim(($selectedAssessment->user->prefix ?? '') . ($selectedAssessment->user->User_firstname ?? '') . ' ' . ($selectedAssessment->user->User_lastname ?? ''));
            $updatedAt = $selectedAssessment->updated_at ? $selectedAssessment->updated_at->format('d/m/Y H:i') : null;
        }

        // "ข้อมูลผู้ตอบแบบประเมิน" shown under the progress stepper - who
        // will actually read as this record's respondent. Same precedence
        // as the print report (AdminController@exportKidneyDHBPdf /
        // resources/views/admin/kidney-dhb-print.blade.php): an imported
        // Word file's own reporter_metadata->_import_reporter wins when
        // present (import is historical data, not tied to whichever
        // account the row is filed under), otherwise fall back to the
        // record's own account holder, and for a brand new quarter with
        // no saved record yet, preview the currently signed-in/target
        // account's own registered info instead.
        $importReporterForDisplay = $selectedAssessment
            ? ($selectedAssessment->reporter_metadata['_import_reporter'] ?? null)
            : null;
        $respondentSourceUser = $selectedAssessment ? $selectedAssessment->user : $scopeUser;
        $respondentAccountName = $respondentSourceUser
            ? (trim(($respondentSourceUser->prefix ?? '') . ($respondentSourceUser->User_firstname ?? '') . ' ' . ($respondentSourceUser->User_lastname ?? '')) ?: ($respondentSourceUser->name ?? null))
            : null;
        $respondentInfo = [
            'name' => $importReporterForDisplay['name'] ?? $respondentAccountName,
            'position' => $importReporterForDisplay['position'] ?? ($respondentSourceUser->User_position ?? null),
            'phone' => $importReporterForDisplay['phone'] ?? ($respondentSourceUser->phone ?? null),
            'email' => $importReporterForDisplay['email'] ?? ($respondentSourceUser->email ?? null),
            'source' => $importReporterForDisplay ? 'import' : 'account',
        ];

        // Always echo the requested area back verbatim - even for a brand
        // new area with no saved record yet for this specific quarter - so
        // the form's area select/input never resets while switching
        // quarters within the same area.
        $cumulativeData['operating_area'] = $requestedArea ?? '';

        // Operating-area location (pin widget restore): mirror back
        // whatever THIS specific quarter's row has saved, same as
        // operating_area text itself above - no cumulative flow across
        // quarters, since each quarter's pin pick is independent.
        $cumulativeData['operating_area_pin_id'] = $selectedAssessment->operating_area_pin_id ?? null;
        $cumulativeData['operating_area_province_id'] = $selectedAssessment->operating_area_province_id ?? null;
        $cumulativeData['operating_area_district_id'] = $selectedAssessment->operating_area_district_id ?? null;
        $cumulativeData['operating_area_tambon_id'] = $selectedAssessment->operating_area_tambon_id ?? null;
        $cumulativeData['operating_area_village_id'] = $selectedAssessment->operating_area_village_id ?? null;
        $cumulativeData['operating_area_village_name_snapshot'] = $selectedAssessment->operating_area_village_name_snapshot ?? null;
        $cumulativeData['operating_area_latitude'] = $selectedAssessment->operating_area_latitude ?? null;
        $cumulativeData['operating_area_longitude'] = $selectedAssessment->operating_area_longitude ?? null;
        // พื้นที่อื่นที่รายงานฉบับนี้ครอบคลุมเพิ่ม - ไตรมาสที่ยังไม่มีรายงานใช้ค่าของไตรมาสก่อนหน้าเป็นค่าเริ่มต้น
        $extraSource = $selectedAssessment
            ?: $allAssessments->filter(fn($a) => (int) $a->quarter < (int) $request->quarter)->sortByDesc('quarter')->first();
        $cumulativeData['extra_pin_ids'] = $extraSource ? \App\Support\KidneyAreaList::extraPinIds($extraSource) : [];

        return response()->json([
            'exists' => (bool) $selectedAssessment,
            // ไตรมาสที่ สคร. ยืนยันข้อมูลแล้ว: ฝั่งผู้ตอบ (ไม่ใช่ level 1) แก้ไขไม่ได้
            'locked' => (bool) ($selectedAssessment && $selectedAssessment->confirmed_at && auth()->user()->User_rank_id != 1),
            'confirmed_quarters' => auth()->user()->User_rank_id != 1
                ? $allAssessments->filter(fn ($a) => (bool) $a->confirmed_at)->keys()->map(fn ($k) => (int) $k)->values()->all()
                : [],
            'is_template' => $isFromTemplate,
            'data' => $cumulativeData,
            // รูปภาพประกอบของรายงานไตรมาสที่เลือก (ไม่ไหลข้ามไตรมาสเหมือนข้อความ)
            'images' => (object) ($selectedAssessment ? \App\Services\KidneyImageService::payloadFor($selectedAssessment->id) : []),
            'milestones' => $milestones,
            'global_milestones' => $globalMilestones,
            'disabled_fields' => $disabledFields,
            'reporter_name' => $reporterName,
            'updated_at' => $updatedAt,
            'respondent_info' => $respondentInfo,
            // Registered operating-area pins for $scopeUser's own province
            // (or every province for a Level 1 account - see
            // operatingAreaPinsForUser()) - refreshed on every call so
            // switching the Word-import target agency re-scopes the
            // "เลือกจากพื้นที่ที่มีอยู่" dropdown to THAT office's province too.
            'registered_pins' => $this->operatingAreaPinsForUser($scopeUser, true),
            // $scopeUser's agency's own previously-used free-text areas -
            // see usedOperatingAreasForAgency(). Refreshed here too so
            // switching the Word-import target agency re-scopes this list
            // to THAT office's own past reports.
            'used_areas' => [],
            'recorded_areas' => $this->recordedAreasForAgency($scopeUser),
            // $scopeUser's own จังหวัด/อำเภอ/ตำบล, for pre-filling a brand
            // new "ปักหน่วยงานพื้นที่ใหม่" pick - see operatingAreaUserLocation().
            'own_location' => $this->operatingAreaUserLocation($scopeUser),
            // Same Bangkok-time conversion as above - see comment there.
            'server_time' => now('Asia/Bangkok')->format('H:i:s')
        ]);
    }

    /**
     * AJAX: just the "พื้นที่การดำเนินงาน" picker's own context (registered
     * pins + the target office's own location) for a given target_user_id.
     * Applying a Word import deliberately skips getKidneyDHBData()'s full
     * refresh (storeKidneyDHB() + the JS click handler both note why: it
     * would overwrite the fields the import just filled in with whatever
     * the server already has saved), but the operating-area picker still
     * needs rescoping to whichever office the import is being filed under
     * - exactly like switching the target agency normally does - so it can
     * be used to pick/save a pin together with the imported text.
     */
    public function getKidneyDhbOperatingAreaContext(Request $request)
    {
        $scopeUser = (auth()->user()->User_rank_id == 1 && $request->filled('target_user_id'))
            ? (User::find($request->target_user_id) ?: auth()->user())
            : auth()->user();

        return response()->json([
            'registered_pins' => $this->operatingAreaPinsForUser($scopeUser, true),
            'used_areas' => [],
            'recorded_areas' => $this->recordedAreasForAgency($scopeUser),
            'own_location' => $this->operatingAreaUserLocation($scopeUser),
        ]);
    }

    /**
     * Reads an uploaded historical .docx report (matching the fixed
     * admin.kidney-dhb paper-form template) and returns its parsed
     * fields as JSON, for the import-preview UI to show before anything
     * is saved. This never writes to the database - the admin still
     * reviews/edits the pre-filled form and submits through the normal
     * admin.kidney-dhb.store endpoint afterward, same as manual entry.
     */
    public function parseKidneyDhbWord(Request $request)
    {
        if (auth()->user()->User_rank_id != 1) {
            return response()->json([
                'success' => false,
                'message' => 'เฉพาะผู้ใช้งานระดับ Level 1 เท่านั้นที่มีสิทธิ์นำเข้าข้อมูลจากไฟล์ Word',
            ], 403);
        }

        $request->validate([
            'docx_file' => 'required|file|mimes:docx|max:10240',
        ]);

        $uploadedPath = $request->file('docx_file')->getRealPath();
        $uploadedName = $request->file('docx_file')->getClientOriginalName();

        try {
            $parser = new KidneyDhbWordParser();
            // the original file name is a fallback hint for the reporting round
            $result = $parser->parse($uploadedPath, $uploadedName);
            return response()->json(['success' => true] + $result);
        } catch (\Throwable $e) {
            return response()->json([
                'success' => false,
                'message' => 'ไม่สามารถอ่านไฟล์นี้ได้: ' . $e->getMessage(),
            ], 422);
        }
    }

    /**
     * Reads an uploaded historical .docx report (matching the fixed
     * admin.report-progress SDA0902 paper-form template) and returns its
     * parsed fields as JSON, for the import-preview UI to show before
     * anything is saved. This never writes to the database - the admin
     * still reviews/edits the pre-filled form and submits through the
     * normal admin.report-progress.store endpoint afterward, same as
     * manual entry. Mirrors parseKidneyDhbWord() for the พชอ.ไต form.
     */
    public function parseSaltAssessmentWord(Request $request)
    {
        if (auth()->user()->User_rank_id != 1) {
            return response()->json([
                'success' => false,
                'message' => 'เฉพาะผู้ใช้งานระดับ Level 1 เท่านั้นที่มีสิทธิ์นำเข้าข้อมูลจากไฟล์ Word',
            ], 403);
        }

        $request->validate([
            'docx_file' => 'required|file|mimes:docx|max:10240',
        ]);

        $uploadedPath = $request->file('docx_file')->getRealPath();

        try {
            $parser = new SaltAssessmentWordParser();
            $result = $parser->parse($uploadedPath);
            return response()->json(['success' => true] + $result);
        } catch (\Throwable $e) {
            return response()->json([
                'success' => false,
                'message' => 'ไม่สามารถอ่านไฟล์นี้ได้: ' . $e->getMessage(),
            ], 422);
        }
    }

    /**
     * Resolves the cascading ประเภทหน่วยงาน/จังหวัด/อำเภอ/โรงพยาบาล-ชื่อหน่วยงาน
     * picker in the kidney-dhb Word-import modal (same cascade shape as the
     * public registration form's picker in pages/staff.blade.php) down to
     * the actual existing User account(s) at that office, since
     * target_user_id has to name a real row - there is no "office" table of
     * its own to point at. Returns every matching account so the admin can
     * pick the right one when more than one exists (e.g. a office that was
     * re-registered, or shares its facility with a colleague's account).
     */
    public function resolveKidneyDhbTargetAgency(Request $request)
    {
        if (auth()->user()->User_rank_id != 1) {
            return response()->json([
                'success' => false,
                'message' => 'เฉพาะผู้ใช้งานระดับ Level 1 เท่านั้นที่มีสิทธิ์ใช้งานส่วนนี้',
            ], 403);
        }

        $request->validate([
            'rank' => 'required|in:2,3,4,5',
            'province_id' => 'required',
            'district_id' => 'nullable',
            'hos_id' => 'nullable',
            'sh_id' => 'nullable',
            // Not validated as 'email': this is text pulled straight out of
            // a Word document's reporter-contact block, so it can easily be
            // slightly malformed (e.g. two addresses joined with a comma,
            // stray whitespace) even when the document itself is fine.
            // It is only ever used below for a loose case-insensitive
            // string comparison against registered accounts' emails to
            // offer an auto-match - never persisted, never queried as a
            // real email - so a strict 'email' rule here only breaks the
            // whole lookup (this endpoint expects JSON but a failed
            // validate() redirects back, so the resulting HTML page trips
            // up the caller's r.json()) for the exact input it should be
            // most tolerant of: real-world contact details as reporters
            // fill them in.
            'reporter_email' => 'nullable|string|max:255',
        ]);

        $rank = (int) $request->rank;

        $query = User::where('User_rank_id', $rank)
            ->where('Province_id', $request->province_id);

        // สสอ./รพ./รพ.สต. (3/5/4) all narrow to a specific อำเภอ; only
        // สสจ. (2) stops at the province.
        if (in_array($rank, [3, 4, 5], true)) {
            if (!$request->filled('district_id')) {
                return response()->json(['success' => true, 'users' => []]);
            }
            $query->where('District_id', $request->district_id);
        }

        if ($rank === 5) {
            if (!$request->filled('hos_id')) {
                return response()->json(['success' => true, 'users' => []]);
            }
            $query->where('hos_id', $request->hos_id);
        }

        if ($rank === 4) {
            if (!$request->filled('sh_id')) {
                return response()->json(['success' => true, 'users' => []]);
            }
            $query->where('sh_id', $request->sh_id);
        }

        $usersRaw = $query->with(['province', 'district', 'hospital', 'subdistrictHospital'])->get();

        // A Word import is historical data - the office that submitted it
        // on paper may now have more than one active account (a
        // re-registration, or a colleague sharing the same facility), so
        // there is no way to tell purely from rank/province/district/etc
        // which literal account the paper report belongs to. The report
        // itself carries the actual reporter's e-mail though, and that is
        // unique per account, so when it matches one of the candidates
        // exactly we can resolve straight to that account instead of
        // making the admin guess from a list of otherwise-identical office
        // labels.
        $reporterEmail = trim((string) $request->input('reporter_email', ''));
        $autoMatchedUserId = null;
        if ($reporterEmail !== '') {
            $emailMatch = $usersRaw->first(function ($u) use ($reporterEmail) {
                return !empty($u->email) && strcasecmp(trim($u->email), $reporterEmail) === 0;
            });
            if ($emailMatch) {
                $autoMatchedUserId = $emailMatch->id;
            }
        }

        $users = $usersRaw
            ->map(function ($u) {
                // resolveAgencyName() alone can collide (two accounts at the
                // same office share the exact same label) - the email
                // disambiguates which literal account will end up as
                // target_user_id.
                $label = $this->resolveAgencyName($u);
                // อีเมลของบัญชีตัวแทน (สร้างตอนนำเข้า Word) แสดงเฉพาะ SuperAdmin
                $showEmail = !empty($u->email) && (!$u->isImportPlaceholder() || auth()->user()->isSuperAdmin());
                if ($showEmail) {
                    $label .= ' (' . $u->email . ')';
                }
                // The account's OWN registered contact details - offered
                // as an alternative to the Word file's reporter info, in
                // case the admin would rather attribute the import to
                // whoever is actually registered on that account instead
                // of whoever the paper report happens to name.
                $accountName = trim(($u->prefix ?? '') . ($u->User_firstname ?? '') . ' ' . ($u->User_lastname ?? '')) ?: ($u->name ?? null);
                return [
                    'id' => $u->id,
                    'label' => $label,
                    'account_name' => $accountName ?: null,
                    'account_position' => $u->User_position ?: null,
                    'account_phone' => $u->phone ?: null,
                    'account_email' => $showEmail ? $u->email : null,
                ];
            })
            ->sortBy('label')
            ->values();

        return response()->json([
            'success' => true,
            'users' => $users,
            'auto_matched_user_id' => $autoMatchedUserId,
        ]);
    }

    /**
     * นำเข้า Word: หน่วยงานที่เลือก (ประเภท/จังหวัด/อำเภอ/หน่วยบริการ) ยังไม่มีบัญชีผู้ใช้ -
     * ไม่ต้องหยุดการนำเข้า: ใช้บัญชีที่มีอยู่ถ้ามี ไม่เช่นนั้นสร้างบัญชี "ตัวแทนหน่วยงาน" ให้อัตโนมัติ
     * เพื่อให้ข้อมูลถูกบันทึกอยู่ภายใต้หน่วยงานที่เลือกจริง (ชื่อหน่วยงาน/จังหวัด/อำเภอในตารางถูกต้อง)
     * บัญชีที่สร้างไม่อนุมัติ (is_approved = 0) และใช้รหัสผ่านสุ่มที่ไม่มีใครรู้ จึงล็อกอินไม่ได้
     * ผู้ดูแลระบบค่อยอนุมัติ/ตั้งรหัสผ่านให้หน่วยงานภายหลังได้
     */
    public function ensureKidneyDhbTargetAgency(Request $request)
    {
        if (auth()->user()->User_rank_id != 1) {
            return response()->json([
                'success' => false,
                'message' => 'เฉพาะผู้ใช้งานระดับ Level 1 เท่านั้นที่มีสิทธิ์ใช้งานส่วนนี้',
            ], 403);
        }

        $request->validate([
            'rank' => 'required|in:2,3,4,5',
            'province_id' => 'required',
            'district_id' => 'nullable',
            'hos_id' => 'nullable',
            'sh_id' => 'nullable',
        ]);

        $rank = (int) $request->rank;
        $needsDistrict = in_array($rank, [3, 4, 5], true);
        if (($needsDistrict && !$request->filled('district_id'))
            || ($rank === 5 && !$request->filled('hos_id'))
            || ($rank === 4 && !$request->filled('sh_id'))) {
            return response()->json(['success' => false, 'message' => 'กรุณาเลือกหน่วยงานให้ครบก่อนนำเข้า'], 422);
        }

        $districtId = $needsDistrict ? $request->district_id : null;
        $hosId = $rank === 5 ? $request->hos_id : null;
        $shId = $rank === 4 ? $request->sh_id : null;

        // มีบัญชีของหน่วยงานนี้อยู่แล้ว (เงื่อนไขเดียวกับ resolveKidneyDhbTargetAgency) - ใช้บัญชีเดิม
        $query = User::where('User_rank_id', $rank)->where('Province_id', $request->province_id);
        if ($needsDistrict) { $query->where('District_id', $districtId); }
        if ($hosId !== null) { $query->where('hos_id', $hosId); }
        if ($shId !== null) { $query->where('sh_id', $shId); }
        $existing = $query->orderByDesc('is_approved')->orderBy('id')->first();

        if ($existing) {
            $existing->load(['province', 'district', 'hospital', 'subdistrictHospital']);
            return response()->json([
                'success' => true,
                'created' => false,
                'id' => $existing->id,
                'label' => $this->resolveAgencyName($existing),
            ]);
        }

        $email = sprintf('import-r%d-p%s-d%s-h%s-s%s@import.invalid',
            $rank, $request->province_id, $districtId ?? '0', $hosId ?? '0', $shId ?? '0');

        $user = User::firstOrCreate(['email' => $email], [
            'name' => 'บัญชีหน่วยงาน (สร้างจากการนำเข้า Word)',
            'password' => \Illuminate\Support\Facades\Hash::make(bin2hex(random_bytes(24))),
            'User_rank_id' => $rank,
            'Province_id' => $request->province_id,
            'District_id' => $districtId,
            'hos_id' => $hosId,
            'sh_id' => $shId,
            'is_approved' => 0,
        ]);
        $user->load(['province', 'district', 'hospital', 'subdistrictHospital']);
        $label = $this->resolveAgencyName($user);
        if ($label && $user->Con_name !== $label) {
            $user->Con_name = $label;
            $user->save();
        }

        return response()->json([
            'success' => true,
            'created' => $user->wasRecentlyCreated,
            'id' => $user->id,
            'label' => $label,
        ]);
    }

    /**
     * Products may carry their own province_name (set by the "นำเข้า
     * Excel" bulk importer - see ReducedSodiumProductImport - since
     * reduced_sodium_products has no province of its own otherwise) OR
     * only be reachable through the owning user's account (every product
     * added the original way, one at a time by a province-level user).
     * Every "filter products by a chosen province" query needs to match
     * either source, so this is shared by the listing, export,
     * delete-count and bulk-delete actions below.
     */
    private function scopeProductsByProvinceName($query, string $provinceName): void
    {
        $query->where(function ($q) use ($provinceName) {
            $q->where('province_name', $provinceName)
                ->orWhereHas('user.province', function ($q2) use ($provinceName) {
                    $q2->where('province_name', $provinceName);
                });
        });
    }

    /**
     * Non-rank-1 users only ever see products for their own province -
     * same two-source matching as scopeProductsByProvinceName() above,
     * just keyed off the viewing user's own Province_id instead of a
     * request filter.
     */
    private function scopeProductsToOwnProvince($query, $user): void
    {
        $ownProvinceName = $user->province ? $user->province->province_name : null;
        $query->where(function ($q) use ($user, $ownProvinceName) {
            $q->whereHas('user', function ($q2) use ($user) {
                $q2->where('Province_id', $user->Province_id);
            });
            if ($ownProvinceName) {
                $q->orWhere('province_name', $ownProvinceName);
            }
        });
    }

    /**
     * Products may carry their own fiscal_year (a real Buddhist-year
     * column - see the add_fiscal_year_to_reduced_sodium_products_table
     * migration, set explicitly by storeSodiumProducts() and the "นำเข้า
     * Excel" importer) OR, for anything added before that column existed,
     * only be dateable via update_date's (Gregorian) year. $yearBE is
     * always the Buddhist year the "ปีงบประมาณ" filter/dropdown works in.
     */
    private function scopeProductsByFiscalYear($query, string $yearBE): void
    {
        $yearAD = (int) $yearBE - 543;
        $query->where(function ($q) use ($yearBE, $yearAD) {
            $q->where('fiscal_year', (int) $yearBE)
                ->orWhere(function ($q2) use ($yearAD) {
                    $q2->where(function ($q3) {
                        $q3->whereNull('fiscal_year')->orWhere('fiscal_year', 0);
                    })->whereYear('update_date', $yearAD);
                });
        });
    }

    /** ล้างแคชตัวเลือก filter ของเมนู/ผลิตภัณฑ์ลดโซเดียม หลังมีการเพิ่ม/แก้/ลบ/นำเข้า */
    private function forgetSodiumFilterCache(): void
    {
        foreach (['perf.sodium_menus.years', 'perf.sodium_menus.provinces', 'perf.sodium_menus.kitchens',
                  'perf.sodium_products.types', 'perf.sodium_products.standards', 'perf.sodium_products.years'] as $k) {
            \Illuminate\Support\Facades\Cache::forget($k);
        }
    }

    public function sodiumProducts(Request $request)
    {
        $user = auth()->user();
        $query = ReducedSodiumProduct::with(['user.province', 'user.district'])->orderBy('update_date', 'desc');

        // Isolation by Province (if not Rank 1)
        if ($user->User_rank_id != 1) {
            $this->scopeProductsToOwnProvince($query, $user);
        }

        // Apply Filters
        if ($request->filled('year')) {
            $this->scopeProductsByFiscalYear($query, $request->year);
        }
        if ($request->filled('province')) {
            $this->scopeProductsByProvinceName($query, $request->province);
        }
        if ($request->filled('product_type')) {
            $query->where('product_type', $request->product_type);
        }
        if ($request->filled('standard')) {
            $query->where('standard_certification', $request->standard);
        }

        // Calculate Summary Statistics (on filtered results)
        $statsQuery = clone $query;
        $totalProducts = (clone $statsQuery)->reorder()->count();
        $avgSodium = (clone $statsQuery)->reorder()->avg('sodium_amount') ?: 0;

        // Get detailed certification counts
        $standardsCount = (clone $statsQuery)
            ->reorder() // Remove the orderBy('update_date') from the main query to avoid SQL error
            ->whereNotNull('standard_certification')
            ->groupBy('standard_certification')
            ->selectRaw('standard_certification, count(*) as count')
            ->pluck('count', 'standard_certification')
            ->toArray();

        // Ensure "ทางเลือกสุขภาพ" is prioritized or explicitly handled if needed
        $certifiedCount = $standardsCount['ทางเลือกสุขภาพ'] ?? 0;

        $stats = [
            'total' => $totalProducts,
            'certified' => $certifiedCount,
            'avg_sodium' => $avgSodium,
            'standards' => $standardsCount
        ];

        $products = $query->paginate(15)->appends($request->all());

        // Fetch Filter Options with Defaults (Ensures options stay even after deletion)
        $provinces = \App\Models\Province::whereIn('province_name', ['อุบลราชธานี', 'ศรีสะเกษ', 'ยโสธร', 'อำนาจเจริญ', 'มุกดาหาร'])->pluck('province_name');
        $defaultTypes = [
            'กลุ่มเนื้อสัตว์แห้ง',
            'กลุ่มเนื้อสัตว์หมัก',
            'กลุ่มพืชผักและผลไม้หมักดอง',
            'กลุ่มผลิตภัณฑ์จากพืชและผลไม้',
            'กลุ่มแป้ง',
            'กลุ่มเครื่องเทศและเครื่องปรุงรส'
        ];
        $dbTypes = \Illuminate\Support\Facades\Cache::remember('perf.sodium_products.types', 120, fn() =>
            ReducedSodiumProduct::whereNotNull('product_type')->distinct()->pluck('product_type')->toArray());
        $productTypes = collect($defaultTypes)->merge($dbTypes)->unique()->sort()->values();

        $defaultStandards = [
            'มาตรฐาน อย.',
            'GHP / GMP',
            'HACCP',
            'มาตรฐานผลิตภัณฑ์ชุมชน (มผช.)',
            'มาตรฐานผลิตภัณฑ์อินทรีย์',
            'มาตรฐานฮาลาล'
        ];
        $dbStandards = \Illuminate\Support\Facades\Cache::remember('perf.sodium_products.standards', 120, fn() =>
            ReducedSodiumProduct::whereNotNull('standard_certification')->distinct()->pluck('standard_certification')->toArray());
        $standards = collect($defaultStandards)->merge($dbStandards)->unique()->sort()->values();

        // ปีงบประมาณ options for the filter dropdown - always Buddhist year.
        // Rows with a real fiscal_year (set by storeSodiumProducts() and
        // the "นำเข้า Excel" importer) contribute it directly; anything
        // older only has update_date to go by, so its (Gregorian) year is
        // converted to Buddhist here - matching scopeProductsByFiscalYear().
        $yearValues = \Illuminate\Support\Facades\Cache::remember('perf.sodium_products.years', 120, function () {
            $fiscalYearValues = ReducedSodiumProduct::whereNotNull('fiscal_year')
                ->where('fiscal_year', '!=', 0)
                ->distinct()
                ->pluck('fiscal_year');
            $legacyYearValues = ReducedSodiumProduct::where(function ($q) {
                    $q->whereNull('fiscal_year')->orWhere('fiscal_year', 0);
                })
                ->selectRaw('YEAR(update_date) as year')
                ->distinct()
                ->pluck('year')
                ->filter()
                ->map(fn($y) => (int) $y + 543);
            return $fiscalYearValues->merge($legacyYearValues)->values()->all();
        });
        $years = FiscalYear::selectableYearsFor('sodium_products', collect($yearValues));

        if ($request->ajax()) {
            return view('pages.partials.sodium-products-table', compact('products', 'stats'));
        }

        return view('admin.sodium-products', compact('products', 'years', 'provinces', 'productTypes', 'standards', 'stats'));
    }

    /**
     * Store reduced sodium products (Batch of 3).
     */
    public function storeSodiumProducts(Request $request)
    {
        $this->forgetSodiumFilterCache();
        $products = $request->input('products', []);
        $files = $request->file('products', []);
        $savedCount = 0;

        foreach ($products as $index => $productData) {
            // Only process if product name is provided
            if (empty($productData['product_name'])) {
                continue;
            }

            $data = [
                'user_id' => auth()->id(),
                'fiscal_year' => (int) now()->year + 543,
                'product_name' => $productData['product_name'],
                'product_type' => $productData['product_type'] ?? null,
                'sodium_amount_before' => $productData['sodium_amount_before'] ?? null,
                'sodium_amount' => $productData['sodium_amount'] ?? null,
                'standard_certification' => $productData['standard_certification'] ?? null,
                'manufacturer_name' => $productData['manufacturer_name'] ?? null,
                'update_date' => now(),
            ];

            // Handle image upload for this product
            if (isset($files[$index]['product_image'])) {
                $file = $files[$index]['product_image'];
                $filename = time() . '_product_' . $index . '.' . $file->getClientOriginalExtension();
                $path = $file->storeAs('products', $filename, 'public');
                $data['product_image'] = $path;
            }

            ReducedSodiumProduct::create($data);
            $savedCount++;
        }

        if ($savedCount > 0) {
            return redirect()->route('admin.sodium-products')
                ->with('success', 'บันทึกข้อมูลผลิตภัณฑ์รวม ' . $savedCount . ' รายการเรียบร้อยแล้ว');
        }

        return redirect()->back()->withErrors(['error' => 'กรุณากรอกข้อมูลอย่างน้อย 1 รายการ']);
    }

    /**
     * Update a single reduced sodium product.
     */
    public function updateSodiumProduct(Request $request, $id)
    {
        $this->forgetSodiumFilterCache();
        $product = ReducedSodiumProduct::findOrFail($id);

        $request->validate([
            'product_name' => 'required|string|max:255',
            'sodium_amount_before' => 'nullable|numeric',
            'sodium_amount' => 'nullable|numeric',
            'product_image' => 'nullable|image|mimes:jpeg,png,jpg,gif|max:8192',
        ]);

        $data = [
            'product_name' => $request->product_name,
            'product_type' => $request->product_type,
            'sodium_amount_before' => $request->sodium_amount_before,
            'sodium_amount' => $request->sodium_amount,
            'standard_certification' => $request->standard_certification,
            'manufacturer_name' => $request->manufacturer_name,
            'update_date' => now(),
        ];

        if ($request->hasFile('product_image')) {
            // Delete old image if exists
            if ($product->product_image) {
                Storage::disk('public')->delete($product->product_image);
            }

            $file = $request->file('product_image');
            $filename = time() . '_update_' . $product->id . '.' . $file->getClientOriginalExtension();
            $path = $file->storeAs('products', $filename, 'public');
            $data['product_image'] = $path;
        }

        $product->update($data);

        return redirect()->back()->with('success', 'แก้ไขข้อมูลผลิตภัณฑ์เรียบร้อยแล้ว');
    }

    /**
     * Delete a reduced sodium product.
     */
    public function destroySodiumProduct($id)
    {
        $this->forgetSodiumFilterCache();
        $product = ReducedSodiumProduct::findOrFail($id);

        // Delete image file if exists
        if ($product->product_image) {
            Storage::disk('public')->delete($product->product_image);
        }

        $product->delete();

        return redirect()->back()->with('success', 'ลบข้อมูลผลิตภัณฑ์เรียบร้อยแล้ว');
    }

    /**
     * Export reduced sodium products to Excel.
     */
    public function exportSodiumProductsExcel(Request $request)
    {
        $user = auth()->user();
        if ($user->User_rank_id != 1) {
            abort(403, 'Unauthorized action.');
        }

        // 1. Fetch Summary Stats (with current filters)
        $query = ReducedSodiumProduct::query();
        if ($request->filled('year')) {
            $this->scopeProductsByFiscalYear($query, $request->year);
        }
        if ($request->filled('product_type')) {
            $query->where('product_type', $request->product_type);
        }
        if ($request->filled('standard')) {
            $query->where('standard_certification', $request->standard);
        }
        if ($request->filled('province')) {
            $this->scopeProductsByProvinceName($query, $request->province);
        }

        $stats = [
            'total' => (clone $query)->count(),
            'certified' => (clone $query)->where('standard_certification', 'ทางเลือกสุขภาพ')->count(),
            'avg_sodium' => (clone $query)->avg('sodium_amount') ?: 0,
            'standards' => (clone $query)
                ->reorder()
                ->whereNotNull('standard_certification')
                ->groupBy('standard_certification')
                ->selectRaw('standard_certification, count(*) as count')
                ->pluck('count', 'standard_certification')
                ->toArray()
        ];

        // 2. Define Provinces to Export (Health Region 10) - narrow to just
        // the one requested via the export filter modal, when given
        $allProvinces = ['อุบลราชธานี', 'ศรีสะเกษ', 'ยโสธร', 'อำนาจเจริญ', 'มุกดาหาร'];
        $provinces = ($request->filled('province') && in_array($request->province, $allProvinces, true))
            ? [$request->province]
            : $allProvinces;

        // 3. Define Filters to pass down
        $filters = $request->only(['year', 'product_type', 'standard']);

        $fileName = 'สรุปผลิตภัณฑ์ลดโซเดียม_' . date('Ymd_His') . '.xlsx';
        return \App\Support\EarlyDownload::excel($fileName, fn () => new ReducedSodiumProductsExport($stats, $filters, $provinces));
    }

    /**
     * Get the count of sodium products to be deleted based on filters.
     */
    public function getSodiumProductsDeleteCount(Request $request)
    {
        $user = auth()->user();
        if ($user->User_rank_id != 1) {
            return response()->json(['error' => 'Unauthorized'], 403);
        }

        $query = ReducedSodiumProduct::query();

        if ($request->filled('year')) {
            $this->scopeProductsByFiscalYear($query, $request->year);
        }
        if ($request->filled('province')) {
            $this->scopeProductsByProvinceName($query, $request->province);
        }
        if ($request->filled('product_type')) {
            $query->where('product_type', $request->product_type);
        }
        if ($request->filled('standard')) {
            $query->where('standard_certification', $request->standard);
        }

        $count = $query->count();

        $summary = [];
        if ($request->filled('year')) $summary[] = "ปี " . ((int)$request->year + 543);
        if ($request->filled('province')) $summary[] = "จังหวัด " . $request->province;
        if ($request->filled('product_type')) $summary[] = "ประเภท " . $request->product_type;
        if ($request->filled('standard')) $summary[] = "มาตรฐาน " . $request->standard;

        $summaryText = count($summary) > 0 ? implode(', ', $summary) : 'ทุกรายการ';

        return response()->json([
            'count' => $count,
            'summary' => $summaryText
        ]);
    }

    /**
     * Bulk delete sodium products based on filters.
     */
    public function bulkDeleteSodiumProducts(Request $request)
    {
        $this->forgetSodiumFilterCache();
        $user = auth()->user();
        if ($user->User_rank_id != 1) {
            return response()->json(['error' => 'Unauthorized'], 403);
        }

        $query = ReducedSodiumProduct::query();

        if ($request->filled('year')) {
            $this->scopeProductsByFiscalYear($query, $request->year);
        }
        if ($request->filled('province')) {
            $this->scopeProductsByProvinceName($query, $request->province);
        }
        if ($request->filled('product_type')) {
            $query->where('product_type', $request->product_type);
        }
        if ($request->filled('standard')) {
            $query->where('standard_certification', $request->standard);
        }

        $products = $query->get();
        $count = $products->count();

        foreach ($products as $product) {
            if ($product->product_image) {
                Storage::disk('public')->delete($product->product_image);
            }
            $product->delete();
        }

        return response()->json([
            'success' => true,
            'message' => "ลบข้อมูลผลิตภัณฑ์เรียบร้อยแล้ว จำนวน {$count} รายการ"
        ]);
    }

    /**
     * Bulk delete sodium products by an explicit list of IDs - used by the
     * checkbox selection column on the products table (independent of the
     * filter-based bulkDeleteSodiumProducts() above, which deletes
     * everything matching the current year/province/type/standard filters
     * instead of a hand-picked set of rows).
     */
    public function bulkDeleteSodiumProductsByIds(Request $request)
    {
        $this->forgetSodiumFilterCache();
        $request->validate([
            'ids' => 'required|array|min:1',
            'ids.*' => 'integer',
        ]);

        $products = ReducedSodiumProduct::whereIn('id', $request->ids)->get();
        $count = $products->count();

        foreach ($products as $product) {
            if ($product->product_image) {
                Storage::disk('public')->delete($product->product_image);
            }
            $product->delete();
        }

        return response()->json([
            'success' => true,
            'message' => "ลบข้อมูลผลิตภัณฑ์ที่เลือกเรียบร้อยแล้ว จำนวน {$count} รายการ"
        ]);
    }

    /**
     * Downloadable blank template for the "นำเข้า Excel" import modal on
     * the ผลิตภัณฑ์ลดโซเดียม admin page.
     *
     * Serves the fixed .xlsx file kept at storage/app/excel-templates/ as-is
     * (the admin-supplied master copy of this form), rather than generating
     * one on the fly via SodiumProductTemplateExport.
     */
    public function downloadSodiumProductsTemplate(Request $request)
    {
        $user = auth()->user();
        if ($user->User_rank_id != 1) {
            abort(403, 'Unauthorized action.');
        }

        $fileName = 'แบบฟอร์มนำเข้าผลิตภัณฑ์ลดโซเดียม.xlsx';
        $path = storage_path('app/excel-templates/sodium-products-template.xlsx');

        if (!file_exists($path)) {
            abort(404, 'ไม่พบไฟล์แบบฟอร์ม');
        }

        return response()->download($path, $fileName);
    }

    /**
     * Import reduced sodium products from Excel, via the "นำเข้า Excel"
     * modal: the admin picks the fiscal year, province, and a
     * duplicate-handling mode once for the whole file (columns for those
     * two fields may still be present in the sheet for backward
     * familiarity with an earlier version of this template, but are never
     * read - see ReducedSodiumProductImport), then uploads a .xlsx built
     * from downloadSodiumProductsTemplate() above.
     *
     * Any picture pasted into a data row's "รูปภาพสินค้า" cell is pulled
     * out here (PhpSpreadsheet reads embedded drawings; Maatwebsite\Excel's
     * ToCollection import does not) and handed to the importer as raw
     * bytes keyed by Excel row number - ReducedSodiumProductImport itself
     * decides whether to actually write each one to disk (only for rows
     * with a real product name), so each row's photo lands on the right
     * record without leaving orphaned files for blank/invalid rows.
     */
    public function importSodiumProducts(Request $request)
    {
        $this->forgetSodiumFilterCache();
        $user = auth()->user();
        if (!in_array($user->User_rank_id, [1, 2])) {
            abort(403, 'Unauthorized action.');
        }

        // A rank-2 (สสจ.) user is scoped to their own province everywhere
        // else in this section (see scopeProductsToOwnProvince) - enforce
        // the same rule here server-side too, since the import form's
        // province dropdown only offers their own province but a crafted
        // request could still try to submit a different one.
        if ($user->User_rank_id == 2) {
            $ownProvinceName = $user->province ? $user->province->province_name : null;
            if (!$ownProvinceName || $request->input('province') !== $ownProvinceName) {
                abort(403, 'Unauthorized action.');
            }
        }

        ini_set('max_execution_time', 600);
        ini_set('memory_limit', '512M');

        $request->validate([
            'fiscal_year' => 'required|integer',
            'province' => 'required|in:อุบลราชธานี,ศรีสะเกษ,ยโสธร,อำนาจเจริญ,มุกดาหาร',
            'excel_file' => 'required|mimes:xlsx|max:8192',
            'duplicate_action' => 'required|in:skip,replace',
        ]);

        $wantsJson = $request->ajax() || $request->wantsJson();
        $file = $request->file('excel_file');

        \Log::info('Starting Sodium Product Excel Import...', [
            'filename' => $file->getClientOriginalName(),
            'fiscal_year' => $request->fiscal_year,
            'province' => $request->province,
            'duplicate_action' => $request->duplicate_action,
        ]);

        // Pull any pictures pasted into the "รูปภาพสินค้า" column out of the
        // raw file BEFORE Maatwebsite\Excel reads it as plain cell data -
        // ToCollection never sees embedded drawings, only PhpSpreadsheet's
        // own drawing collection does. Only the raw bytes are captured
        // here - nothing is written to storage/app/public yet, because we
        // don't know yet which rows will actually turn out to be real
        // products (have a ชื่อผลิตภัณฑ์). ReducedSodiumProductImport writes
        // each row's picture to disk itself, only once it has confirmed
        // that row is valid, so a picture pasted into a still-blank/test
        // row never becomes an orphaned file.
        $rawRowImages = [];
        try {
            $spreadsheet = \PhpOffice\PhpSpreadsheet\IOFactory::load($file->getRealPath());
            // Always the template's real data sheet ("ข้อมูล") - NOT
            // getActiveSheet(), which just reflects whichever tab happened
            // to be selected when the admin last saved the file and so
            // isn't reliably the data sheet at all. Same sheet-0 assumption
            // ReducedSodiumProductImport::sheets() now enforces for the row
            // data itself.
            $sheet = $spreadsheet->getSheet(0);

            foreach ($sheet->getDrawingCollection() as $drawing) {
                if (!preg_match('/(\d+)/', $drawing->getCoordinates(), $m)) {
                    continue;
                }
                $excelRowNumber = (int) $m[1];

                if ($drawing instanceof \PhpOffice\PhpSpreadsheet\Worksheet\MemoryDrawing) {
                    $gdImage = $drawing->getImageResource();
                    if (!$gdImage) {
                        continue;
                    }
                    ob_start();
                    imagepng($gdImage);
                    $contents = ob_get_clean();
                    $rawRowImages[$excelRowNumber] = ['contents' => $contents, 'extension' => 'png'];
                } elseif (method_exists($drawing, 'getPath') && $drawing->getPath()) {
                    $extension = strtolower(pathinfo($drawing->getPath(), PATHINFO_EXTENSION)) ?: 'png';
                    $contents = @file_get_contents($drawing->getPath());
                    if ($contents !== false) {
                        $rawRowImages[$excelRowNumber] = ['contents' => $contents, 'extension' => $extension];
                    }
                }
            }
        } catch (\Exception $e) {
            // Not fatal - the rest of the import (text columns) still
            // proceeds without photos rather than failing the whole file.
            \Log::warning('Sodium Product Import - image extraction failed: ' . $e->getMessage());
        }

        DB::beginTransaction();
        try {
            $import = new ReducedSodiumProductImport(
                (int) $request->fiscal_year,
                $request->province,
                $request->duplicate_action,
                $rawRowImages
            );
            Excel::import($import, $file);
            DB::commit();

            $result = [
                'imported' => $import->getImportedCount(),
                'updated' => $import->getUpdatedCount(),
                'skipped' => $import->getSkippedCount(),
                'replaced' => $import->getReplacedCount(),
                'invalid' => $import->getInvalidCount(),
            ];

            if ($wantsJson) {
                return response()->json(['success' => true] + $result);
            }
            return redirect()->back()->with('import_result', $result);
        } catch (\Maatwebsite\Excel\Validators\ValidationException $e) {
            DB::rollBack();
            $failures = $e->failures();
            $errorMsg = 'ข้อมูลในไฟล์ไม่ถูกต้อง: ';
            foreach ($failures as $failure) {
                $errorMsg .= "แถวที่ " . $failure->row() . " - " . implode(', ', $failure->errors()) . ". ";
            }
            if ($wantsJson) {
                return response()->json(['success' => false, 'message' => $errorMsg], 422);
            }
            return redirect()->back()->with('error', $errorMsg);
        } catch (\Exception $e) {
            DB::rollBack();
            \Log::error('Sodium Product Import Error: ' . $e->getMessage());
            $errorMsg = 'เกิดข้อผิดพลาดในการนำเข้าข้อมูล: ' . $e->getMessage();
            if ($wantsJson) {
                return response()->json(['success' => false, 'message' => $errorMsg], 500);
            }
            return redirect()->back()->with('error', $errorMsg);
        }
    }

    /**
     * Export reduced sodium menus to Excel.
     */
    public function exportSodiumMenusExcel(Request $request)
    {
        $user = auth()->user();
        if ($user->User_rank_id != 1) {
            abort(403, 'Unauthorized action.');
        }

        // 1. Fetch Summary Stats (with current filters)
        $query = ReducedSodiumMenu::query();
        if ($request->filled('year')) {
            $query->where('year', $request->year);
        }
        if ($request->filled('kitchen_type')) {
            $query->where('kitchen_type', $request->kitchen_type);
        }
        if ($request->filled('province')) {
            $query->where('province', $request->province);
        }

        $totalCount = (clone $query)->count();
        $avgBefore = (clone $query)->avg('sodium_before') ?: 0;
        $avgAfter = (clone $query)->avg('sodium_after') ?: 0;

        $stats = [
            'total' => $totalCount,
            'avg_before' => $avgBefore,
            'avg_after' => $avgAfter,
            'avg_reduction' => $avgBefore > 0 ? (($avgBefore - $avgAfter) / $avgBefore) * 100 : 0,
            'province_counts' => (clone $query)
                ->whereNotNull('province')
                ->where('province', '!=', '')
                ->groupBy('province')
                ->selectRaw('province, count(*) as count')
                ->pluck('count', 'province')
                ->toArray()
        ];

        // 2. Define Provinces to Export - narrow to just the one requested
        // via the export filter modal, when given
        $allProvinces = ['อุบลราชธานี', 'ศรีสะเกษ', 'ยโสธร', 'อำนาจเจริญ', 'มุกดาหาร'];
        $provinces = ($request->filled('province') && in_array($request->province, $allProvinces, true))
            ? [$request->province]
            : $allProvinces;

        // 3. Define Filters to pass down
        $filters = $request->only(['year', 'kitchen_type']);

        $fileName = 'สรุปเมนูลดโซเดียม_' . date('Ymd_His') . '.xlsx';
        return \App\Support\EarlyDownload::excel($fileName, fn () => new ReducedSodiumMenusExport($stats, $filters, $provinces));
    }

    /**
     * Get the count of sodium menus to be deleted based on filters.
     */
    public function getSodiumMenusDeleteCount(Request $request)
    {
        $user = auth()->user();
        if ($user->User_rank_id != 1) {
            return response()->json(['error' => 'Unauthorized'], 403);
        }

        $query = ReducedSodiumMenu::query();

        if ($request->filled('year')) {
            $query->where(DB::raw("TRIM(year)"), trim($request->year));
        }
        if ($request->filled('province')) {
            $query->where(DB::raw("TRIM(province)"), trim($request->province));
        }
        if ($request->filled('kitchen_type')) {
            $query->where(DB::raw("TRIM(kitchen_type)"), trim($request->kitchen_type));
        }

        $count = $query->count();

        $summary = [];
        if ($request->filled('year')) $summary[] = "ปี " . $request->year;
        if ($request->filled('province')) $summary[] = "จังหวัด " . $request->province;
        if ($request->filled('kitchen_type')) $summary[] = "ประเภท " . $request->kitchen_type;

        $summaryText = count($summary) > 0 ? implode(', ', $summary) : 'ทุกรายการ';

        return response()->json([
            'count' => $count,
            'summary' => $summaryText
        ]);
    }

    /**
     * Bulk delete sodium menus based on filters.
     */
    public function bulkDeleteSodiumMenus(Request $request)
    {
        $this->forgetSodiumFilterCache();
        $user = auth()->user();
        if ($user->User_rank_id != 1) {
            return response()->json(['error' => 'Unauthorized'], 403);
        }

        $query = ReducedSodiumMenu::query();

        if ($request->filled('year')) {
            $query->where(DB::raw("TRIM(year)"), trim($request->year));
        }
        if ($request->filled('province')) {
            $query->where(DB::raw("TRIM(province)"), trim($request->province));
        }
        if ($request->filled('kitchen_type')) {
            $query->where(DB::raw("TRIM(kitchen_type)"), trim($request->kitchen_type));
        }

        $menus = $query->get();
        $count = $menus->count();

        foreach ($menus as $menu) {
            if ($menu->product_image) {
                Storage::disk('public')->delete($menu->product_image);
            }
            $menu->delete();
        }

        return response()->json([
            'success' => true,
            'message' => "ลบข้อมูลเรียบร้อยแล้ว จำนวน {$count} รายการ"
        ]);
    }

    /**
     * Bulk delete sodium menus by an explicit list of IDs - used by the
     * checkbox selection column on the menus table (independent of the
     * filter-based bulkDeleteSodiumMenus() above, which deletes everything
     * matching the current year/province/kitchen_type filters instead of a
     * hand-picked set of rows).
     */
    public function bulkDeleteSodiumMenusByIds(Request $request)
    {
        $this->forgetSodiumFilterCache();
        $request->validate([
            'ids' => 'required|array|min:1',
            'ids.*' => 'integer',
        ]);

        $menus = ReducedSodiumMenu::whereIn('id', $request->ids)->get();
        $count = $menus->count();

        foreach ($menus as $menu) {
            if ($menu->product_image) {
                Storage::disk('public')->delete($menu->product_image);
            }
            $menu->delete();
        }

        return response()->json([
            'success' => true,
            'message' => "ลบข้อมูลเมนูที่เลือกเรียบร้อยแล้ว จำนวน {$count} รายการ"
        ]);
    }

    /**
     * Show the reduced sodium menus page.
     */
    public function sodiumMenus(Request $request)
    {
        $query = ReducedSodiumMenu::query();

        // Data Isolation Logic
        $user = auth()->user();
        if ($user->User_rank_id == 1) {
            // Rank 1 (Admin/SKR): Sees All Data
        } elseif ($user->User_rank_id == 2) {
            // Rank 2 (SSJ): Sees All Data in their Province
            $pName = $user->province ? $user->province->province_name : null;
            $pId = $user->Province_id;

            $query->where(function ($q) use ($pName, $pId) {
                if ($pName) {
                    $q->where('province', $pName);
                }
                if ($pId) {
                    $q->orWhere('province', $pId);
                }
            });
        } else {
            // Rank 3+ (District/User): Sees Only Their Own Data
            $query->where('user_id', $user->id);
        }

        if ($request->filled('year')) {
            $query->where('year', $request->year);
        }

        if ($request->filled('province')) {
            $query->where('province', $request->province);
        }

        if ($request->filled('kitchen_type')) {
            $query->where('kitchen_type', $request->kitchen_type);
        }

        // Calculate Summary Statistics
        // 1 query แทน 3 (count + avg + sum)
        $agg = (clone $query)->reorder()
            ->selectRaw('COUNT(*) as total_menus, AVG(sodium_after) as avg_after, SUM(sodium_before - sodium_after) as total_red')
            ->first();
        $totalMenus = (int) ($agg->total_menus ?? 0);
        $avgSodiumAfter = $agg->avg_after ?: 0;
        $totalReduction = $agg->total_red ?: 0;

        $stats = [
            'total_menus' => $totalMenus,
            'avg_sodium' => $avgSodiumAfter,
            'total_reduction' => $totalReduction
        ];

        $menus = $query->orderBy('update_date', 'desc')->paginate(15)->appends($request->all());
        // ตัวเลือก filter (distinct) เปลี่ยนไม่บ่อย - แคช 2 นาทีลดการสแกนตารางทุกครั้งที่เปิด/กรองหน้า
        $realMenuYears = \Illuminate\Support\Facades\Cache::remember('perf.sodium_menus.years', 120, fn() =>
            ReducedSodiumMenu::whereNotNull('year')->where('year', '!=', '')->distinct()->pluck('year')->map(fn($y) => trim($y))->all()
        );
        $realMenuYears = collect($realMenuYears);
        $years = FiscalYear::selectableYearsFor('sodium_menus', $realMenuYears);
        $dbProvinces = collect(\Illuminate\Support\Facades\Cache::remember('perf.sodium_menus.provinces', 120, fn() =>
            ReducedSodiumMenu::whereNotNull('province')
            ->whereNotIn('province', ['', '-', ' '])
            ->distinct()
            ->pluck('province')->all()))
            ->map(function ($p) {
                // Check if the value is numeric (legacy ID)
                if (is_numeric($p)) {
                    // Start with known hotfixes
                    if ($p == '33')
                        return 'ศรีสะเกษ';
                    if ($p == '49')
                        return 'มุกดาหาร';

                    // Try to fetch from DB if possible
                    try {
                        $provinceObj = \App\Models\Province::where('province_id', $p)->first();
                        if ($provinceObj) {
                            return $provinceObj->province_name;
                        }
                    } catch (\Exception $e) {
                        // Fallback
                    }
                }
                return trim($p);
            });

        // Health Region 10 Default Provinces (Ensure these always exist)
        $defaultProvinces = collect(['อุบลราชธานี', 'ศรีสะเกษ', 'ยโสธร', 'อำนาจเจริญ', 'มุกดาหาร']);

        $provinces = $defaultProvinces->merge($dbProvinces)
            ->unique()
            ->sort()
            ->values();

        $defaultKitchenTypes = ['ร้านอาหาร', 'โรงอาหาร', 'ศูนย์พัฒนาเด็กเล็ก', 'โรงพยาบาล', 'อื่นๆ'];
        $dbKitchenTypes = \Illuminate\Support\Facades\Cache::remember('perf.sodium_menus.kitchens', 120, fn() =>
            ReducedSodiumMenu::whereNotNull('kitchen_type')->where('kitchen_type', '!=', '')->distinct()->pluck('kitchen_type')->toArray());
        $kitchenTypes = collect($defaultKitchenTypes)->merge($dbKitchenTypes)->unique()->sort()->values();

        if ($request->ajax()) {
            return view('pages.partials.reduced-sodium-menus-table', compact('menus', 'stats'))->render();
        }

        return view('admin.reduced-sodium-menus', compact('menus', 'years', 'provinces', 'stats', 'kitchenTypes'));
    }

    /**
     * Download the blank Excel template used by the "นำเข้า Excel" import
     * modal on the เมนูลดโซเดียม admin page. Rank-1 (สคร.) only, matching
     * every other Excel button on that page.
     *
     * Serves the fixed .xlsx file kept at storage/app/excel-templates/ as-is
     * (the admin-supplied master copy of this form), rather than generating
     * one on the fly via SodiumMenuTemplateExport.
     */
    public function downloadSodiumMenusTemplate(Request $request)
    {
        $user = auth()->user();
        if ($user->User_rank_id != 1) {
            abort(403, 'Unauthorized action.');
        }

        $fileName = 'แบบฟอร์มนำเข้าเมนูลดโซเดียม.xlsx';
        $path = storage_path('app/excel-templates/sodium-menus-template.xlsx');

        if (!file_exists($path)) {
            abort(404, 'ไม่พบไฟล์แบบฟอร์ม');
        }

        return response()->download($path, $fileName);
    }

    /**
     * Import reduced sodium menus from Excel, via the "นำเข้า Excel" modal:
     * the admin picks the fiscal year, province, and a duplicate-handling
     * mode once for the whole file (columns for those two fields may still
     * be present in the sheet for backward familiarity with the old
     * template, but are never read - see ReducedSodiumMenuImportV2), then
     * uploads a .xlsx built from downloadSodiumMenusTemplate() above.
     *
     * Any picture pasted into a data row's "รูปภาพเมนู" cell is pulled out
     * here (PhpSpreadsheet reads embedded drawings; Maatwebsite\Excel's
     * ToCollection import does not) and handed to the importer as raw
     * bytes keyed by Excel row number - ReducedSodiumMenuImportV2 itself
     * decides whether to actually write each one to disk (only for rows
     * with a real menu name), so each row's photo lands on the right
     * record without leaving orphaned files for blank/invalid rows.
     */
    public function importSodiumMenus(Request $request)
    {
        $this->forgetSodiumFilterCache();
        $user = auth()->user();
        if (!in_array($user->User_rank_id, [1, 2])) {
            abort(403, 'Unauthorized action.');
        }

        // A rank-2 (สสจ.) user is scoped to their own province everywhere
        // else on this page (see sodiumMenus() above) - enforce the same
        // rule here server-side too, since the import form's province
        // dropdown only offers their own province but a crafted request
        // could still try to submit a different one.
        if ($user->User_rank_id == 2) {
            $ownProvinceName = $user->province ? $user->province->province_name : null;
            if (!$ownProvinceName || $request->input('province') !== $ownProvinceName) {
                abort(403, 'Unauthorized action.');
            }
        }

        ini_set('max_execution_time', 600);
        ini_set('memory_limit', '512M');

        $request->validate([
            'fiscal_year' => 'required',
            'province' => 'required',
            'excel_file' => 'required|mimes:xlsx|max:8192',
            'duplicate_action' => 'required|in:skip,replace',
        ]);

        $wantsJson = $request->ajax() || $request->wantsJson();
        $file = $request->file('excel_file');

        // Fiscal years 2567/2568 predate today's "นำเข้า Excel" template
        // and need the legacy per-row year/province importer below - see
        // App\Imports\ReducedSodiumMenuImportLegacy. 2569 onward keeps the
        // existing ReducedSodiumMenuImportV2 path completely unchanged.
        $isLegacyFormat = in_array((string) $request->fiscal_year, ['2567', '2568'], true);

        \Log::info('Starting Sodium Menu Excel Import...', [
            'filename' => $file->getClientOriginalName(),
            'fiscal_year' => $request->fiscal_year,
            'province' => $request->province,
            'duplicate_action' => $request->duplicate_action,
        ]);

        // Pull any pictures pasted into the "รูปภาพเมนู" column out of the
        // raw file BEFORE Maatwebsite\Excel reads it as plain cell data -
        // ToCollection never sees embedded drawings, only PhpSpreadsheet's
        // own drawing collection does. Only the raw bytes are captured
        // here - nothing is written to storage/app/public yet, because we
        // don't know yet which rows will actually turn out to be real
        // menus (have a ชื่อเมนูอาหาร). ReducedSodiumMenuImportV2 writes each
        // row's picture to disk itself, only once it has confirmed that
        // row is valid, so a picture pasted into a still-blank/test row
        // never becomes an orphaned file.
        $rawRowImages = [];
        if (!$isLegacyFormat) {
        try {
            $spreadsheet = \PhpOffice\PhpSpreadsheet\IOFactory::load($file->getRealPath());
            // Always the template's real data sheet ("ข้อมูล") - NOT
            // getActiveSheet(), which just reflects whichever tab happened
            // to be selected when the admin last saved the file (e.g. if
            // they clicked into the "รายชื่ออำเภอ" reference sheet before
            // saving) and so isn't reliably the data sheet at all. Same
            // sheet-0 assumption ReducedSodiumMenuImportV2::sheets() now
            // enforces for the row data itself.
            $sheet = $spreadsheet->getSheet(0);

            foreach ($sheet->getDrawingCollection() as $drawing) {
                if (!preg_match('/(\d+)/', $drawing->getCoordinates(), $m)) {
                    continue;
                }
                $excelRowNumber = (int) $m[1];

                if ($drawing instanceof \PhpOffice\PhpSpreadsheet\Worksheet\MemoryDrawing) {
                    $gdImage = $drawing->getImageResource();
                    if (!$gdImage) {
                        continue;
                    }
                    ob_start();
                    imagepng($gdImage);
                    $contents = ob_get_clean();
                    $rawRowImages[$excelRowNumber] = ['contents' => $contents, 'extension' => 'png'];
                } elseif (method_exists($drawing, 'getPath') && $drawing->getPath()) {
                    $extension = strtolower(pathinfo($drawing->getPath(), PATHINFO_EXTENSION)) ?: 'png';
                    $contents = @file_get_contents($drawing->getPath());
                    if ($contents !== false) {
                        $rawRowImages[$excelRowNumber] = ['contents' => $contents, 'extension' => $extension];
                    }
                }
            }
        } catch (\Exception $e) {
            // Not fatal - the rest of the import (text columns) still
            // proceeds without photos rather than failing the whole file.
            \Log::warning('Sodium Menu Import - image extraction failed: ' . $e->getMessage());
        }
        }

        DB::beginTransaction();
        try {
            if ($isLegacyFormat) {
                $restrictToProvince = ($user->User_rank_id == 2) ? ($ownProvinceName ?? null) : null;
                $import = new ReducedSodiumMenuImportLegacy(
                    $request->fiscal_year,
                    $request->duplicate_action,
                    $restrictToProvince
                );
            } else {
                $import = new ReducedSodiumMenuImportV2(
                    $request->fiscal_year,
                    $request->province,
                    $request->duplicate_action,
                    $rawRowImages
                );
            }
            Excel::import($import, $file);
            DB::commit();

            $result = [
                'imported' => $import->getImportedCount(),
                'updated' => $import->getUpdatedCount(),
                'skipped' => $import->getSkippedCount(),
                'replaced' => $import->getReplacedCount(),
            ];

            if ($wantsJson) {
                return response()->json(['success' => true] + $result);
            }
            return redirect()->back()->with('import_result', $result);
        } catch (\Maatwebsite\Excel\Validators\ValidationException $e) {
            DB::rollBack();
            $failures = $e->failures();
            $errorMsg = 'ข้อมูลในไฟล์ไม่ถูกต้อง: ';
            foreach ($failures as $failure) {
                $errorMsg .= "แถวที่ " . $failure->row() . " - " . implode(', ', $failure->errors()) . ". ";
            }
            if ($wantsJson) {
                return response()->json(['success' => false, 'message' => $errorMsg], 422);
            }
            return redirect()->back()->with('error', $errorMsg);
        } catch (\Exception $e) {
            DB::rollBack();
            \Log::error('Sodium Menu Import Error: ' . $e->getMessage());
            $errorMsg = 'เกิดข้อผิดพลาดในการนำเข้าข้อมูล: ' . $e->getMessage();
            if ($wantsJson) {
                return response()->json(['success' => false, 'message' => $errorMsg], 500);
            }
            return redirect()->back()->with('error', $errorMsg);
        }
    }

    /**
     * Store a new reduced sodium menu.
     */
    public function storeSodiumMenu(Request $request)
    {
        $this->forgetSodiumFilterCache();
        $request->validate([
            'year' => 'required|integer',
            'menus' => 'required|array',
            'menus.*.menu_name' => 'nullable|string|max:255',
            'menus.*.product_image' => 'nullable|image|mimes:jpeg,png,jpg,gif|max:8192',
        ]);

        $user = auth()->user();
        $menus = $request->input('menus', []);
        $files = $request->file('menus', []);
        $savedCount = 0;

        // Map User Rank to Org Type
        $rankMapping = ['1' => 'สคร.', '2' => 'สสจ.', '3' => 'สสอ.', '4' => 'รพ.สต.', '5' => 'รพ.'];
        $orgType = $rankMapping[$user->User_rank_id] ?? '-';

        // Map Org Name based on rank with full prefix
        $orgName = $user->Con_name;
        $provinceName = $user->province ? $user->province->province_name : null;
        $districtName = $user->district ? $user->district->district_name : null;

        if ($user->User_rank_id == 2) {
            // SSJ
            $orgName = 'สำนักงานสาธารณสุขจังหวัด' . ($provinceName ?? '');
        } elseif ($user->User_rank_id == 3) {
            // SSO
            $orgName = 'สำนักงานสาธารณสุขอำเภอ' . ($districtName ?? '');
        } elseif ($user->User_rank_id == 4) {
            // Rph.Sot.
            $orgName = 'รพ.สต.' . $user->Con_name;
        } elseif ($user->User_rank_id == 5) {
            // Hospital
            $orgName = $user->hospital->hos_name ?? $user->Con_name;
        }

        foreach ($menus as $index => $menuData) {
            // Skip empty entries (check menu_name)
            if (empty($menuData['menu_name'])) {
                continue;
            }

            // Handle sodium logic
            $sodiumBefore = $menuData['sodium_before'] ?? null;
            $sodiumAfter = $menuData['sodium_after'] ?? null;

            $data = [
                'year' => $request->year,
                'province' => $provinceName, // Save Name instead of ID
                'district' => $districtName, // Save Name instead of ID
                'org_type' => $orgType,
                'org_name' => $orgName,
                'kitchen_type' => $menuData['kitchen_type'] ?? null,
                'menu_name' => $menuData['menu_name'],
                'sodium_before' => $sodiumBefore,
                'sodium_after' => $sodiumAfter,
                'agency' => $menuData['agency'] ?? null,
                'user_id' => $user->id,
                'update_date' => now(),
            ];

            // Handle image upload for this specific index
            if (isset($files[$index]['product_image'])) {
                $file = $files[$index]['product_image'];
                $filename = time() . '_menu_' . $index . '_' . uniqid() . '.' . $file->getClientOriginalExtension();
                $path = $file->storeAs('menus', $filename, 'public');
                $data['product_image'] = $path;
            }

            ReducedSodiumMenu::create($data);
            $savedCount++;
        }

        if ($savedCount > 0) {
            return redirect()->back()->with('success', 'บันทึกข้อมูลเมนู ' . $savedCount . ' รายการเรียบร้อยแล้ว');
        }

        return redirect()->back()->withErrors(['error' => 'กรุณากรอกข้อมูลอย่างน้อย 1 รายการ']);
    }

    /**
     * Update a reduced sodium menu.
     */
    public function updateSodiumMenu(Request $request, $id)
    {
        $this->forgetSodiumFilterCache();
        $menu = ReducedSodiumMenu::findOrFail($id);

        $request->validate([
            'year' => 'required|integer',
            'menu_name' => 'required|string|max:255',
            'product_image' => 'nullable|image|mimes:jpeg,png,jpg,gif|max:8192',
        ]);

        // Handle sodium logic
        $sodiumBefore = $request->sodium_before;
        $sodiumAfter = $request->sodium_after;

        $data = [
            'year' => $request->year,
            'kitchen_type' => $request->kitchen_type,
            'menu_name' => $request->menu_name,
            'sodium_before' => $sodiumBefore,
            'sodium_after' => $sodiumAfter,
            'agency' => $request->agency,
            'update_date' => now(),
        ];

        // Maintain location/org info if not explicitly provided (since it's read-only in UI)
        if ($request->filled('province'))
            $data['province'] = $request->province;
        if ($request->filled('district'))
            $data['district'] = $request->district;
        if ($request->filled('org_type'))
            $data['org_type'] = $request->org_type;
        if ($request->filled('org_name'))
            $data['org_name'] = $request->org_name;

        if ($request->hasFile('product_image')) {
            if ($menu->product_image) {
                Storage::disk('public')->delete($menu->product_image);
            }

            $file = $request->file('product_image');
            $filename = time() . '_update_menu_' . $menu->id . '.' . $file->getClientOriginalExtension();
            $path = $file->storeAs('menus', $filename, 'public');
            $data['product_image'] = $path;
        }

        $menu->update($data);

        return redirect()->back()->with('success', 'ปรับปรุงข้อมูลเมนูลดโซเดียมเรียบร้อยแล้ว');
    }

    /**
     * Delete a reduced sodium menu.
     */
    public function destroySodiumMenu($id)
    {
        $this->forgetSodiumFilterCache();
        $menu = ReducedSodiumMenu::findOrFail($id);

        if ($menu->product_image) {
            Storage::disk('public')->delete($menu->product_image);
        }

        $menu->delete();

        return redirect()->back()->with('success', 'ลบข้อมูลเมนูลดโซเดียมเรียบร้อยแล้ว');
    }

    /**
     * Display the list of Kidney DHB assessments.
     */
    /**
     * Display the list of Kidney DHB assessments with filters.
     */
    public function kidneyDHBList(Request $request)
    {
        $user = auth()->user();
        // Same Level 1 gate as the kidney-dhb Word-import feature itself
        // (AdminController::kidneyDHB()'s $canImportWord) - controls whether
        // the "นำเข้าไฟล์ Word" button shows on this list page too.
        $canImportWord = $user->User_rank_id == 1;
        $query = KidneyAssessment::with(['user.province', 'user.district', 'user.subdistrictHospital', 'user.hospital']);

        // Data Isolation Logic
        if ($user->User_rank_id == 1) {
            // Rank 1 (Admin/SKR): Sees All Data
        } elseif ($user->User_rank_id == 2) {
            // Rank 2 (SSJ): Sees All Data in their Province
            $query->whereHas('user', function ($q) use ($user) {
                $q->where('Province_id', $user->Province_id);
            });
        } else {
            // Rank 3+ (District/User): Sees All Data in their Agency
            $agencyUserIds = $this->getAgencyUserIds($user);
            $query->whereIn('user_id', $agencyUserIds);
        }

        // Options for the "พื้นที่การดำเนินงาน" filter dropdown - distinct
        // values already entered within this user's visibility scope above,
        // cloned before the fiscal_year/quarter/province/district/org_type
        // filters below so changing those doesn't hide areas from the list.
        $operatingAreas = (clone $query)
            ->whereNotNull('operating_area')
            ->where('operating_area', '!=', '')
            ->distinct()
            ->orderBy('operating_area')
            ->pluck('operating_area');

        // Filter by Fiscal Year
        if ($request->filled('fiscal_year')) {
            $query->where('fiscal_year', $request->fiscal_year);
        }

        // Filter by Quarter
        if ($request->filled('quarter')) {
            $query->where('quarter', $request->quarter);
        } else {
            // Only show the latest quarter per agency / fiscal_year / OPERATING AREA when no
            // specific quarter filter is applied. The area MUST be part of the grouping: one
            // agency can report several operating areas in the same year (the unique key is
            // user + year + quarter + area), so grouping without it hid any newly added area
            // whose latest quarter was lower than another area's latest quarter.
            $query->whereIn('kidney_assessments.id', function ($q) {
                $q->select('a1.id')
                    ->from('kidney_assessments as a1')
                    ->join(\DB::raw("(SELECT user_id, fiscal_year, COALESCE(operating_area, '') AS area_key, MAX(quarter) AS max_quarter FROM kidney_assessments GROUP BY user_id, fiscal_year, COALESCE(operating_area, '')) as a2"), function ($join) {
                        $join->on('a1.user_id', '=', 'a2.user_id')
                            ->on('a1.fiscal_year', '=', 'a2.fiscal_year')
                            ->on('a1.quarter', '=', 'a2.max_quarter')
                            ->whereRaw("COALESCE(a1.operating_area, '') = a2.area_key");
                    });
            });
        }

        // Filter by Province, District, and Org Type (via User relationship)
        if ($request->filled('province_id') || $request->filled('district_id') || $request->filled('org_type')) {
            $query->whereHas('user', function ($q) use ($request) {
                if ($request->filled('province_id')) {
                    $q->where('Province_id', $request->province_id);
                }
                if ($request->filled('district_id')) {
                    $q->where('District_id', $request->district_id);
                }
                if ($request->filled('org_type')) {
                    $q->where('User_rank_id', $request->org_type);
                }
            });
        }

        // Filter by Operating Area (พื้นที่การดำเนินงาน)
        if ($request->filled('operating_area')) {
            $query->where('operating_area', $request->operating_area);
        }

        // Calculate Summary Statistics (Full filtered query, not just current page)
        $statsQuery = clone $query;
        $totalSubmissions = $statsQuery->count();

        // "Complete" = all 10 categories filled, counting data carried forward from
        // earlier quarters (same agency / fiscal year / operating area) - the same
        // cumulative rule the detail page and the per-row icons below use.
        $flowCache = [];
        $completeRecs = (clone $query)->with('user')->get();
        $this->prefetchKidneyPageRecords($completeRecs, $flowCache);
        $completeCount = $completeRecs->filter(function ($rec) use (&$flowCache) {
            return !in_array(false, $this->kidneyFlowFilled($rec, $flowCache), true);
        })->count();

        $stats = [
            'total' => $totalSubmissions,
            'complete' => $completeCount,
            'in_progress' => $totalSubmissions - $completeCount
        ];

        $assessments = $query->join('users', 'kidney_assessments.user_id', '=', 'users.id')
            ->select('kidney_assessments.*');

        if ($request->input('sort') !== 'org') {
            // ค่าเริ่มต้น: รายการที่เพิ่ม/แก้ไขล่าสุดขึ้นก่อน - หาข้อมูลใหม่เจอทันทีโดยไม่ต้องไล่ทีละหน้า
            // (เลือก sort=org เพื่อกลับไปเรียงตามหน่วยงาน/จังหวัดแบบเดิม)
            $assessments->orderBy('kidney_assessments.updated_at', 'desc')->orderBy('kidney_assessments.id', 'desc');
        } else {
            $assessments->orderBy('users.Province_id')
                ->orderBy('users.District_id')
                ->orderBy('users.User_rank_id')
                ->orderBy('users.hos_id')
                ->orderBy('users.sh_id')
                ->orderBy('kidney_assessments.fiscal_year', 'desc')
                ->orderBy('kidney_assessments.operating_area', 'asc')
                ->orderBy('kidney_assessments.quarter', 'asc');
        }
        // สคร. (1) / สสจ. (2): ตารางแสดงทุกพื้นที่ในขอบเขต - มีข้อมูล (แถวรายงาน) แล้วต่อด้วยพื้นที่ที่
        // ยังไม่มีข้อมูล (เลือกแล้วแต่ยังไม่รายงาน / ยังไม่มีผู้เลือก / หมุดแสดงผล) แบ่งหน้าร่วมกัน
        // รายงานมาก่อน แถวยังไม่มีข้อมูลต่อท้าย - ใช้ตัวกรอง "สถานะข้อมูล" เพื่อดูเฉพาะกลุ่มได้
        $statusRaw = $request->input('status', '');
        // 'has' (legacy alias for any report) is still supported for backward compatibility
        $validStatuses = ['has', 'unconfirmed', 'confirmed', 'selected', 'free', 'display'];
        $status = in_array($statusRaw, $validStatuses, true) ? $statusRaw : '';
        // แปลง unconfirmed/confirmed → 'has' (แสดงรายงาน) แล้วกรอง confirmed_at เพิ่มเติม
        $statusGroup = in_array($status, ['unconfirmed', 'confirmed'], true) ? 'has' : $status;
        if ($status === 'unconfirmed') {
            $query->whereNull('kidney_assessments.confirmed_at');
        } elseif ($status === 'confirmed') {
            $query->whereNotNull('kidney_assessments.confirmed_at');
        }
        $pendingTail = collect();
        $pendingHead = collect();
        if (in_array((int) $user->User_rank_id, [1, 2], true)) {
            $perPage = 15;
            $page = max(1, (int) $request->input('page', 1));
            $offset = ($page - 1) * $perPage;
            $pendingAll = $this->pendingAreasForOverview($user, $request, $statusGroup);
            $reportTotal = in_array($statusGroup, ['selected', 'free', 'display'], true)
                ? 0
                : (clone $assessments)->toBase()->getCountForPagination();

            // ลำดับแถว: พื้นที่ที่หน่วยงานนี้เลือกไว้เองแล้วแต่ยังไม่รายงาน (เพิ่มใหม่) ขึ้นก่อน
            // -> แถวรายงาน -> พื้นที่ยังไม่มีข้อมูลอื่นๆ ต่อท้าย - แบ่งหน้าร่วมกันทั้งสามกลุ่ม
            [$headAll, $restAll] = $pendingAll->partition(fn($r) => $r['status'] === 'selected' && !empty($r['own']));
            $headAll = $headAll->values();
            $restAll = $restAll->values();
            $headCount = $headAll->count();

            $pendingHead = $headAll->slice($offset, $perPage)->values();
            $remaining = $perPage - $pendingHead->count();

            $reportRows = collect();
            $reportOffset = max(0, $offset - $headCount);
            if ($remaining > 0 && $reportOffset < $reportTotal) {
                $reportRows = (clone $assessments)->skip($reportOffset)->take(min($remaining, $reportTotal - $reportOffset))->get();
            }
            $need = $remaining - $reportRows->count();
            if ($need > 0) {
                $pendingTail = $restAll->slice(max(0, $offset - $headCount - $reportTotal), $need)->values();
            }

            $assessments = new \Illuminate\Pagination\LengthAwarePaginator(
                $reportRows,
                $headCount + $reportTotal + $restAll->count(),
                $perPage,
                $page,
                ['path' => \Illuminate\Pagination\Paginator::resolveCurrentPath()]
            );
        } else {
            $assessments = $assessments->paginate(15);
        }
        $assessments->appends($request->all()); // Keep filters in pagination links
        $this->attachAreaIssue($assessments->getCollection());

        // Per-quarter confirmation status (Q1-Q4) for each row's own
        // user_id + fiscal_year - mirrors the same grouping this list's own
        // dedup subquery above uses (user_id + fiscal_year, no rank/location
        // aggregation like the salt-assessment list needs), so the columns
        // line up with what's actually being deduped on this page. A
        // quarter counts as "confirmed" if any record for it is confirmed.
        $quarterCache = [];
        $this->prefetchKidneyPageRecords($assessments->getCollection(), $flowCache, $quarterCache);
        foreach ($assessments as $item) {
            // Built as a plain local array, not mutated in place on the
            // model - PHP can't do indirect modification of an overloaded
            // (magic __get/__set) Eloquent attribute like
            // $item->quarterConfirmations[$q] = ... (it silently has no
            // effect and logs an "Indirect modification" notice).
            $quarters = [1 => 'none', 2 => 'none', 3 => 'none', 4 => 'none'];

            // แยกตามพื้นที่ดำเนินงานด้วย: ไตรมาส/การยืนยันของพื้นที่หนึ่ง ต้องไม่ไปปนกับอีกพื้นที่ของหน่วยงานเดียวกัน
            $cacheKey = $item->user_id . '-' . $item->fiscal_year . '-' . (string) $item->operating_area;
            if (!array_key_exists($cacheKey, $quarterCache)) {
                $quarterCache[$cacheKey] = KidneyAssessment::where('user_id', $item->user_id)
                    ->where('fiscal_year', $item->fiscal_year)
                    ->where(function ($q) use ($item) {
                        if ($item->operating_area === null || $item->operating_area === '') {
                            $q->whereNull('operating_area')->orWhere('operating_area', '');
                        } else {
                            $q->where('operating_area', $item->operating_area);
                        }
                    })
                    ->get(['quarter', 'confirmed_at']);
            }

            foreach ($quarterCache[$cacheKey] as $rec) {
                $q = (int) $rec->quarter;
                if ($q < 1 || $q > 4) {
                    continue;
                }
                if ($rec->confirmed_at) {
                    $quarters[$q] = 'confirmed';
                } elseif ($quarters[$q] !== 'confirmed') {
                    $quarters[$q] = 'pending';
                }
            }

            $item->quarterConfirmations = $quarters;
            $item->flowFilled = $this->kidneyFlowFilled($item, $flowCache);
        }

        // Landing on this list is treated as "reviewed": mark every record this
        // user is allowed to see as read, so the sidebar unread badge clears
        // immediately rather than requiring each row to be opened individually.
        // Scoped to the same visibility rule as above, ignoring the fiscal
        // year/quarter/province filters (those only affect what's displayed on
        // this page, not what counts as "reviewed" for the badge).
        $markReadQuery = KidneyAssessment::where('is_read', 0);
        if ($user->User_rank_id == 2) {
            $markReadQuery->whereHas('user', function ($q) use ($user) {
                $q->where('Province_id', $user->Province_id);
            });
        } elseif ($user->User_rank_id >= 3) {
            $markReadQuery->whereIn('user_id', $this->getAgencyUserIds($user));
        }
        $unreadIds = $markReadQuery->pluck('id');
        if ($unreadIds->isNotEmpty()) {
            \DB::table('kidney_assessments')->whereIn('id', $unreadIds)->update(['is_read' => 1]);
        }

        // พื้นที่ที่หน่วยงานเลือก/ปักไว้แล้ว แต่ยังไม่เคยรายงานผล - แสดงเป็นแถวในตารางด้วย
        // (สคร./สสจ. ใช้แถวต่อท้าย $pendingTail ด้านบนแทน)
        $pendingAreas = in_array((int) $user->User_rank_id, [1, 2], true)
            ? collect()
            : $this->pendingChosenAreasForList($user, $request, $assessments);

        if ($request->ajax()) {
            return view('admin.kidney-dhb-table', compact('assessments', 'stats', 'pendingAreas', 'pendingTail', 'pendingHead'));
        }

        // Fetch Provinces for dropdown
        $provinces = \App\Support\AdminCache::provinces();

        // ปีงบประมาณ options for the filter dropdown - real distinct years
        // already in the data, plus any admin-enabled years and minus any
        // admin-hidden years from "จัดการปีงบประมาณ" (FiscalYearController).
        $years = FiscalYear::selectableYearsFor('kidney', \App\Support\AdminCache::distinct(\App\Models\KidneyAssessment::class, 'fiscal_year'));

        $needAreaSelection = $this->needsAreaSelection(auth()->user());

        return view('admin.kidney-dhb-list', compact('assessments', 'provinces', 'stats', 'years', 'operatingAreas', 'canImportWord', 'needAreaSelection', 'pendingAreas', 'pendingTail', 'pendingHead'));
    }

    /**
     * สคร./สสจ.: พื้นที่ (หมุด) ในขอบเขตที่ "ยังไม่มีรายงานผล" ของปีงบประมาณนั้น ใช้เป็นแถวต่อท้ายตารางรายการข้อมูล
     *   selected - มีหน่วยงานเลือกพื้นที่แล้ว แต่ยังไม่รายงาน
     *   free     - ยังไม่มีผู้เลือก (ยังไม่มีข้อมูล)
     *   display  - หมุดแสดงผล (SuperAdmin เพิ่มไว้ ระบุหน่วยงานที่ดำเนินงานเอง)
     * ตัวกรอง: ปีงบประมาณ/จังหวัด/อำเภอ/พื้นที่/ประเภทหน่วยงาน (ผู้เลือก) - ตัวกรองไตรมาสไม่ใช้กับพื้นที่ที่ยังไม่มีรายงาน
     */
    private function pendingAreasForOverview($user, Request $request, string $status)
    {
        if ($status === 'has' || $request->filled('quarter')) {
            return collect();
        }

        $pins = $this->operatingAreaPinsForUser($user)
            ->when($request->filled('fiscal_year'), fn($c) => $c->filter(fn($p) => (string) $p['fiscal_year'] === (string) $request->fiscal_year))
            ->when($request->filled('province_id'), fn($c) => $c->filter(fn($p) => (string) $p['province_id'] === (string) $request->province_id))
            ->when($request->filled('district_id'), fn($c) => $c->filter(fn($p) => (string) $p['district_id'] === (string) $request->district_id))
            ->when($request->filled('operating_area'), fn($c) => $c->filter(fn($p) => trim((string) $p['display_name']) === trim((string) $request->operating_area)))
            ->values();
        if ($pins->isEmpty()) {
            return collect();
        }

        $reported = $this->reportedPinIdsByOwner($user, $pins);
        $reportedIds = array_merge($reported['own'], $reported['other']);

        $selectors = [];
        if (\App\Support\SchemaProbe::hasTable('kidney_operating_area_selections')) {
            $rows = DB::table('kidney_operating_area_selections')
                ->whereIn('pin_id', $pins->pluck('id')->all())
                ->get(['pin_id', 'user_id']);
            $users = User::whereIn('id', $rows->pluck('user_id')->unique()->all())->get()->keyBy('id');
            foreach ($rows as $r) {
                $u = $users->get($r->user_id);
                if (!$u) {
                    continue;
                }
                $selectors[(int) $r->pin_id][] = ['label' => $this->operatingAreaSelectorLabel($u), 'rank' => (int) $u->User_rank_id];
            }
        }
        $ownIds = $this->ownPinIds($user);

        $result = $pins
            ->reject(fn($p) => in_array((int) $p['id'], $reportedIds, true))
            ->map(function ($p) use ($selectors, $ownIds) {
                $sel = $selectors[(int) $p['id']] ?? [];
                return [
                    'id' => $p['id'],
                    'fiscal_year' => $p['fiscal_year'],
                    'display_name' => $p['display_name'],
                    'place_name' => $p['place_name'] ?? $p['display_name'],
                    'issue' => $p['issue'] ?? null,
                    'breadcrumb' => $p['breadcrumb'],
                    'province_name' => $p['province_name'],
                    'status' => !empty($p['view_only']) ? 'display' : (empty($sel) ? 'free' : 'selected'),
                    'agencies' => array_values(array_unique(array_column($sel, 'label'))),
                    'ranks' => array_values(array_unique(array_column($sel, 'rank'))),
                    'agency_label' => $p['agency_label'] ?? null,
                    'own' => in_array((int) $p['id'], $ownIds, true),
                    'group' => $p['group'] ?? null,
                ];
            });

        if ($request->filled('org_type')) {
            $result = $result->filter(fn($r) => in_array((int) $request->org_type, $r['ranks'], true));
        }
        if (in_array($status, ['selected', 'free', 'display'], true)) {
            $result = $result->filter(fn($r) => $r['status'] === $status);
        }

        return $this->mergePendingByGroup(
            $result->sortBy([['province_name', 'asc'], ['breadcrumb', 'asc'], ['display_name', 'asc']])->values(),
            true
        );
    }

    /** ประเด็น = หมายเหตุ/ชื่อพื้นที่ของหมุด เมื่อกรอกไว้และไม่ซ้ำกับชื่อพื้นที่ */
    private function pinIssueText($label, $placeName): ?string
    {
        $label = trim((string) $label);
        if ($label === '' || $label === trim((string) $placeName)) {
            return null;
        }
        return $label;
    }

    /**
     * ใส่ area_place (ชื่อพื้นที่) และ area_issue (ประเด็น) ให้แต่ละรายงาน เพื่อแสดง 2 ข้อมูลในตารางรายการ
     * รายงานที่ไม่ได้ผูกหมุด (ข้อมูลเก่า/พิมพ์เอง) แสดงชื่อพื้นที่เดิมอย่างเดียว
     */
    private function attachAreaIssue($items): void
    {
        \App\Support\KidneyAreaList::attach($items);
    }

    /**
     * พื้นที่ดำเนินงานที่หน่วยงานเลือก/ปักไว้ใน "เลือกพื้นที่ดำเนินงาน" แต่ยังไม่มีรายงานผล
     * (kidney_assessments) - ให้ตารางรายการแสดงเป็นแถว "ยังไม่มีข้อมูล" เพื่อให้เห็นทันทีหลังบันทึก
     * แสดงเฉพาะหน้าแรก และเมื่อไม่ได้ใช้ตัวกรองอื่นนอกจากปีงบประมาณ
     */
    private function pendingChosenAreasForList($user, Request $request, $assessments)
    {
        if (!$user || !in_array($user->User_rank_id, [2, 3, 4, 5])) {
            return collect();
        }
        if ($request->filled('quarter') || $request->filled('province_id') || $request->filled('district_id')
            || $request->filled('org_type') || $request->filled('operating_area')) {
            return collect();
        }
        if (method_exists($assessments, 'currentPage') && $assessments->currentPage() > 1) {
            return collect();
        }

        $chosen = $this->chosenPinIdsForAgency($user);
        if (empty($chosen)) {
            return collect();
        }

        $agencyUserIds = $this->getAgencyUserIds($user);
        $reportedRows = KidneyAssessment::whereIn('user_id', $agencyUserIds)->get(['operating_area_pin_id', 'operating_area', 'reporter_metadata']);
        $reportedPinIds = $reportedRows->pluck('operating_area_pin_id')->filter()->map(fn($v) => (int) $v)->all();
        // พื้นที่ที่รวมอยู่ในรายงานของพื้นที่หลักอื่น ถือว่ามีรายงานแล้วเช่นกัน
        foreach ($reportedRows as $rr) {
            $reportedPinIds = array_merge($reportedPinIds, \App\Support\KidneyAreaList::extraPinIds($rr));
        }
        $reportedNames = $reportedRows->pluck('operating_area')->filter()->map(fn($v) => trim($v))->all();

        return $this->mergePendingByGroup(
            $this->operatingAreaPinsForUser($user)
                ->filter(fn($p) => in_array((int) $p['id'], $chosen, true))
                ->reject(fn($p) => in_array((int) $p['id'], $reportedPinIds, true)
                    || in_array(trim((string) $p['display_name']), $reportedNames, true))
                ->when($request->filled('fiscal_year'), fn($c) => $c->filter(fn($p) => (string) $p['fiscal_year'] === (string) $request->fiscal_year))
                ->values(),
            false
        );
    }

    /**
     * สถานะ "มีข้อมูล" ของแต่ละข้อ (category_1..7, category_8_1..3) แบบสะสม:
     * ข้อมูลที่กรอกไว้ในไตรมาสก่อนหน้า (ภายใน หน่วยงาน + ปีงบประมาณ + พื้นที่ดำเนินงานเดียวกัน)
     * ถือว่า "ไหล" มาถึงไตรมาสของรายการนี้ด้วย - ตรงกับหน้ารายละเอียดและ PDF
     *
     * @return array<string,bool> key = ชื่อคอลัมน์
     */
    /**
     * โหลดรายงานที่ต้องใช้คำนวณ "ข้อมูลไหล" (flow) และสถานะรายไตรมาสของทุกแถวในครั้งเดียว
     * แทนการยิง query ต่อแถว/ต่อพื้นที่ (เดิมหลายสิบ query ต่อหน้า) - เติมค่าลงแคชเดียวกับที่ kidneyFlowFilled() ใช้
     */
    private function prefetchKidneyPageRecords($items, array &$flowCache, ?array &$quarterCache = null): void
    {
        $items = collect($items)->values();
        if ($items->isEmpty()) {
            return;
        }
        $fields = ['category_1', 'category_2', 'category_3', 'category_4', 'category_5', 'category_6', 'category_7',
            'category_8_1', 'category_8_2', 'category_8_3'];

        $agencyIds = [];
        foreach ($items as $it) {
            $uid = $it->user_id;
            if (!isset($agencyIds[$uid])) {
                $agencyIds[$uid] = $it->user ? ($this->getAgencyUserIds($it->user) ?: [$uid]) : [$uid];
            }
        }
        $allIds = collect($agencyIds)->flatten()->unique()->values()->all();
        $years = $items->pluck('fiscal_year')->unique()->values()->all();

        $recs = KidneyAssessment::whereIn('user_id', $allIds)
            ->whereIn('fiscal_year', $years)
            ->get(array_merge(['user_id', 'fiscal_year', 'operating_area', 'quarter', 'confirmed_at'], $fields));

        $norm = fn($a) => ($a === null || $a === '') ? '' : (string) $a;
        foreach ($items as $it) {
            $area = $norm($it->operating_area);
            $same = fn($r) => (string) $r->fiscal_year === (string) $it->fiscal_year && $norm($r->operating_area) === $area;

            $flowKey = $it->user_id . '|' . $it->fiscal_year . '|' . (string) $it->operating_area;
            if (!isset($flowCache[$flowKey])) {
                $ids = $agencyIds[$it->user_id];
                $flowCache[$flowKey] = $recs->filter(fn($r) => in_array($r->user_id, $ids) && $same($r))->values();
            }
            if ($quarterCache !== null) {
                $qKey = $it->user_id . '-' . $it->fiscal_year . '-' . (string) $it->operating_area;
                if (!array_key_exists($qKey, $quarterCache)) {
                    $quarterCache[$qKey] = $recs->filter(fn($r) => (int) $r->user_id === (int) $it->user_id && $same($r))->values();
                }
            }
        }
    }

    private function kidneyFlowFilled(KidneyAssessment $item, array &$cache): array
    {
        $fields = ['category_1', 'category_2', 'category_3', 'category_4', 'category_5', 'category_6', 'category_7',
            'category_8_1', 'category_8_2', 'category_8_3'];

        $key = $item->user_id . '|' . $item->fiscal_year . '|' . (string) $item->operating_area;
        if (!isset($cache[$key])) {
            $ids = $item->user ? ($this->getAgencyUserIds($item->user) ?: [$item->user_id]) : [$item->user_id];
            $cache[$key] = KidneyAssessment::whereIn('user_id', $ids)
                ->where('fiscal_year', $item->fiscal_year)
                ->where(function ($q) use ($item) {
                    if ($item->operating_area === null || $item->operating_area === '') {
                        $q->whereNull('operating_area')->orWhere('operating_area', '');
                    } else {
                        $q->where('operating_area', $item->operating_area);
                    }
                })
                ->get(array_merge(['quarter'], $fields));
        }

        $result = array_fill_keys($fields, false);
        foreach ($cache[$key] as $rec) {
            if ((int) $rec->quarter > (int) $item->quarter) {
                continue;
            }
            foreach ($fields as $f) {
                if (!$result[$f] && trim((string) $rec->{$f}) !== '') {
                    $result[$f] = true;
                }
            }
        }
        return $result;
    }

    /**
     * Export Kidney DHB assessments to Excel.
     */
    public function exportKidneyDHBExcel(Request $request)
    {
        $user = auth()->user();
        $fileName = 'รายงาน_พชอ_ไต_' . date('Ymd_His') . '.xlsx';

        return Excel::download(new \App\Exports\KidneyDHBExport($request), $fileName);
    }

    public function showKidneyDHB(Request $request, $id)
    {
        // $anchor = รายการที่คลิกเข้ามา: เป็นตัวบอกว่าเป็นหน่วยงาน/พื้นที่ดำเนินงานไหน
        // ส่วนปี/ไตรมาสที่แสดงจริงเลือกได้จากตัวกรองด้านบน (ดูได้แม้ยังไม่มีรายงานของไตรมาสนั้น)
        $anchor = KidneyAssessment::with(['user.province', 'user.district', 'user.subdistrictHospital', 'user.hospital'])->findOrFail($id);
        $assessment = $anchor;
        $isNew = false;
        $agencyUserIds = $this->getAgencyUserIds($anchor->user) ?: [$anchor->user_id];

        if ($request->filled('filter_fiscal_year') || $request->filled('filter_quarter')) {
            $targetYear = (int) ($request->filter_fiscal_year ?? $anchor->fiscal_year);
            $targetQuarter = (int) ($request->filter_quarter ?? $anchor->quarter);

            // อยู่ในพื้นที่ดำเนินงานเดียวกันเสมอ - ไม่กระโดดไปรายงานของพื้นที่อื่นที่ agency เดียวกัน
            $found = KidneyAssessment::with(['user.province', 'user.district', 'user.subdistrictHospital', 'user.hospital'])
                ->whereIn('user_id', $agencyUserIds)
                ->where('fiscal_year', $targetYear)
                ->where('quarter', $targetQuarter)
                ->where('operating_area', $anchor->operating_area)
                ->first();

            if ($found) {
                if ($found->id != $anchor->id) {
                    return redirect()->route('admin.kidney-dhb.show', $found->id);
                }
            } else {
                // ยังไม่มีรายงานของปี/ไตรมาสนี้ -> แสดงหน้าว่างที่ข้อมูลไตรมาสก่อนหน้า "ไหล" มาให้
                // (หลักเดียวกับฟอร์ม admin/kidney-dhb) แก้ไขแล้วบันทึกเพื่อสร้างรายงานได้
                $assessment = new KidneyAssessment([
                    'user_id' => $anchor->user_id,
                    'fiscal_year' => $targetYear,
                    'quarter' => $targetQuarter,
                    'operating_area' => $anchor->operating_area,
                    'operating_area_pin_id' => $anchor->operating_area_pin_id,
                    'operating_area_province_id' => $anchor->operating_area_province_id,
                    'operating_area_district_id' => $anchor->operating_area_district_id,
                    'operating_area_tambon_id' => $anchor->operating_area_tambon_id,
                    'operating_area_village_id' => $anchor->operating_area_village_id,
                    'operating_area_village_name_snapshot' => $anchor->operating_area_village_name_snapshot,
                    'operating_area_latitude' => $anchor->operating_area_latitude,
                    'operating_area_longitude' => $anchor->operating_area_longitude,
                ]);
                $assessment->setRelation('user', $anchor->user);
                $isNew = true;
            }
        }
        $anchorId = $anchor->id;

        // Get available options for dropdowns
        $currentYear = date('Y') + 543;
        $availableYears = range($currentYear + 1, $currentYear - 2);
        rsort($availableYears);
        if (!in_array((int) $assessment->fiscal_year, $availableYears)) {
            $availableYears[] = (int) $assessment->fiscal_year;
            rsort($availableYears);
        }

        // Available quarters for the CURRENT selected year in the view
        $availableQuarters = [1, 2, 3, 4];

        if (!$isNew && !$assessment->is_read) {
            $assessment->is_read = true;
            $assessment->save();
        }

        // ผู้ที่แก้ไขได้ = ผู้ที่มองเห็นรายการนี้ในหน้ารายการ (ขอบเขตเดียวกับ
        // kidneyDHBList()). พื้นที่ดำเนินงานแก้จากหน้านี้ไม่ได้ - เพิ่ม/ปรับหมุด
        // ทำที่หน้า จัดการพื้นที่ดำเนินงาน.
        $canEdit = $this->canManageKidneyAssessment(auth()->user(), $anchor);

        // ไตรมาสที่ สคร. (level 1) ยืนยันข้อมูลแล้ว: ผู้ตอบแบบประเมินดูได้อย่างเดียว (ปุ่มแก้ไขถูกปิด)
        $quarterLocked = !$isNew && $assessment->confirmed_at && auth()->user()->User_rank_id != 1;
        if ($quarterLocked) {
            $canEdit = false;
        }

        // สคร. (level 1) มีหน้าที่ดูและยืนยันข้อมูลเท่านั้น: หน้ารายละเอียดนี้เป็นโหมดดูอย่างเดียว
        // (ไม่แสดงปุ่ม "บันทึกข้อมูลไตรมาส" และช่องแก้ไข)
        // ยกเว้น SuperAdmin: แก้ไขได้ทุกรายการ (รวมรายการที่นำเข้าจากไฟล์ Word) เผื่อต้องเพิ่ม/แก้ข้อมูล
        if (auth()->user()->User_rank_id == 1) {
            $canEdit = auth()->user()->isSuperAdmin();
        }

        // ข้อมูล 10 หัวข้อ (ข้อ 1-8.3) "ไหล" สะสมตั้งแต่ไตรมาส 1 ถึงไตรมาสที่เลือก - หลักเดียวกับ
        // getKidneyDHBData()/ฟอร์ม admin/kidney-dhb และหน้า export PDF: หัวข้อละข้อความล่าสุด
        // (updated_at) ที่ไม่ว่างในหน่วยงาน/ปี/พื้นที่เดียวกัน. ปัญหาอุปสรรคและข้อเสนอแนะเป็นรายไตรมาส
        // ไม่ไหล. stepQuarters = ไตรมาสเจ้าของข้อความที่แสดง, stepEarlier = ไม่นับไตรมาสที่เลือก
        // (ให้หน้าจอคำนวณใหม่ได้ทันทีตอนแก้/ลบข้อความในโหมดแก้ไข).
        $stepFields = ['category_1', 'category_2', 'category_3', 'category_4', 'category_5', 'category_6', 'category_7',
            'category_8_1', 'category_8_2', 'category_8_3'];
        $viewQuarter = (int) $assessment->quarter;
        $stepScope = KidneyAssessment::whereIn('user_id', $agencyUserIds)
            ->where('fiscal_year', $assessment->fiscal_year)
            ->where('operating_area', $assessment->operating_area)
            ->where('quarter', '<=', $viewQuarter)
            ->orderBy('quarter', 'asc')
            ->get();
        $stepQuarters = [];
        $stepEarlier = [];
        foreach ($stepFields as $f) {
            foreach (['all' => $stepScope, 'earlier' => $stepScope->filter(function ($r) use ($viewQuarter) {
                return (int) $r->quarter !== $viewQuarter;
            })] as $key => $rows) {
                $winner = null;
                $maxUpdate = -1;
                foreach ($rows as $rec) {
                    if (trim((string) $rec->$f) !== '') {
                        $time = $rec->updated_at ? $rec->updated_at->timestamp : 0;
                        if ($time >= $maxUpdate) {
                            $winner = $rec;
                            $maxUpdate = $time;
                        }
                    }
                }
                if ($key === 'all') {
                    $stepQuarters[$f] = $winner ? (int) $winner->quarter : null;
                    // แสดงข้อความที่ไหลมา (แค่ในหน่วยความจำของคำขอนี้ - ไม่มีการ save ต่อจากนี้)
                    $assessment->$f = $winner ? $winner->$f : null;
                } else {
                    $stepEarlier[$f] = $winner ? (int) $winner->quarter : null;
                }
            }
        }

        // ผู้บันทึก + เวลา ของแต่ละช่อง (มุมล่างขวาของกล่อง) - หลักเดียวกับฟอร์ม: ใช้ reporter_metadata
        // ของ "รายการที่เป็นเจ้าของข้อความที่แสดงอยู่" (ข้อที่ไหลมาจากไตรมาสก่อนก็แสดงผู้บันทึกของไตรมาสนั้น);
        // ไม่มี metadata (ข้อมูลเก่า) ใช้เจ้าของบัญชีของรายการนั้น + เวลา updated_at
        $fmtTime = function ($t) {
            if (preg_match('#^(\d{2})/(\d{2})/(\d{4})(.*)$#', (string) $t, $m)) {
                $y = (int) $m[3];
                return $m[1] . '/' . $m[2] . '/' . ($y < 2400 ? $y + 543 : $y) . $m[4] . ' น.';
            }
            return $t;
        };
        $attrFor = function ($rec, $f) use ($fmtTime) {
            $m = is_array($rec->reporter_metadata) ? ($rec->reporter_metadata[$f] ?? null) : null;
            if (is_array($m) && !empty($m['name'])) {
                return ['name' => $m['name'], 'time' => $fmtTime($m['time'] ?? ($rec->updated_at ? $rec->updated_at->format('d/m/Y H:i') : null))];
            }
            $u = $rec->user;
            $name = $u ? (trim(($u->prefix ?? '') . ($u->User_firstname ?? '') . ' ' . ($u->User_lastname ?? '')) ?: ($u->name ?? null)) : null;
            return ['name' => $name ?: 'ไม่ระบุชื่อ', 'time' => $rec->updated_at ? $fmtTime($rec->updated_at->format('d/m/Y H:i')) : null];
        };
        $fieldAttr = [];
        foreach ($stepFields as $f) {
            if (!empty($stepQuarters[$f])) {
                $owner = $stepScope->firstWhere('quarter', $stepQuarters[$f]);
                if ($owner) {
                    $fieldAttr[$f] = $attrFor($owner, $f);
                }
            }
        }
        if (!$isNew) {
            foreach (['problems_obstacles', 'recommendations_opportunities'] as $f) {
                if (trim((string) $assessment->$f) !== '') {
                    $fieldAttr[$f] = $attrFor($assessment, $f);
                }
            }
        }

        // พื้นที่ของรายการนี้เอง (ใช้วาดหมุดเดียวบนแผนที่ + เป็นค่าเริ่มต้นของฟอร์ม)
        // พื้นที่ที่รายงานครอบคลุมเป็นชุดเดียวกันทุกไตรมาสของปีเดียวกัน: ไตรมาสที่ยังไม่มีรายงาน (หน้าว่างที่ข้อมูลไหลมา)
        // หรือรายงานที่ยังไม่ได้เก็บพื้นที่เพิ่ม จะยืมรายการพื้นที่อื่นจากรายงานของพื้นที่เดียวกันในไตรมาสอื่น
        // (ทำในหน่วยความจำเพื่อแสดงผลเท่านั้น ไม่บันทึกลงฐานข้อมูล)
        if (empty(\App\Support\KidneyAreaList::extraPinIds($assessment))) {
            $sibling = KidneyAssessment::whereIn('user_id', $agencyUserIds)
                ->where('fiscal_year', $assessment->fiscal_year)
                ->where('operating_area', $anchor->operating_area)
                ->orderByRaw('ABS(CAST(quarter AS SIGNED) - ?) asc', [(int) $assessment->quarter])
                ->get()
                ->first(fn ($r) => !empty(\App\Support\KidneyAreaList::extraPinIds($r)));
            if ($sibling) {
                $meta = is_array($assessment->reporter_metadata) ? $assessment->reporter_metadata : [];
                $meta[\App\Support\KidneyAreaList::META_KEY] = \App\Support\KidneyAreaList::extraPinIds($sibling);
                $assessment->reporter_metadata = $meta;
            }
        }

        $areaGeo = $this->kidneyAssessmentAreaGeo($assessment);
        // SuperAdmin: รายการพื้นที่ (หมุด) ที่เปลี่ยนให้รายงานนี้ได้ - เฉพาะหมุดรายงานได้ (ไม่รวมหมุดแสดงผล)
        // (SuperAdmin เห็นหมุดทุกพื้นที่ ไม่จำกัดขอบเขตของบัญชีเจ้าของรายการ)
        $areaPinOptions = auth()->user()->isSuperAdmin()
            ? $this->operatingAreaPinsForUser(auth()->user(), false)->filter(fn($p) => empty($p['view_only']))->values()
            : collect();

        // รูปภาพประกอบของรายงานที่กำลังดู (ไม่ไหลข้ามไตรมาส)
        $kimgs = \App\Services\KidneyImageService::groupedFor($assessment->id);
        // รูปเดิมสำหรับเติมลงกล่องอัปโหลดในโหมดแก้ไข
        $kimgPayload = \App\Services\KidneyImageService::payloadFor($assessment->id);

        // ปุ่ม "ยกเลิก" ในโหมดแก้ไข: ถ้ากำลังดูไตรมาสที่ยังไม่มีรายงาน (หน้าว่าง) ให้กลับไปแสดงไตรมาสที่บันทึกล่าสุดของพื้นที่นี้
        // ไม่ใช่ค้างอยู่ที่หน้าว่างเดิม (ถ้ากำลังดูรายงานที่บันทึกไว้แล้ว จะกลับมาที่รายงานนั้นตามเดิม)
        $cancelUrl = null;
        if ($isNew) {
            $latestSaved = KidneyAssessment::whereIn('user_id', $agencyUserIds)
                ->where('operating_area', $anchor->operating_area)
                ->orderByDesc('fiscal_year')
                ->orderByDesc('quarter')
                ->first(['id']);
            $cancelUrl = route('admin.kidney-dhb.show', $latestSaved ? $latestSaved->id : $anchor->id);
        }

        $this->attachAreaIssue([$assessment]);

        return view('admin.kidney-dhb-detail', compact(
            'cancelUrl',
            'kimgs',
            'kimgPayload',
            'assessment',
            'availableYears',
            'availableQuarters',
            'canEdit',
            'quarterLocked',
            'areaGeo',
            'areaPinOptions',
            'stepQuarters',
            'stepEarlier',
            'fieldAttr',
            'isNew',
            'anchorId'
        ));
    }

    /**
     * SuperAdmin: เปลี่ยนพื้นที่ดำเนินงานของรายงาน พชอ.ไต (เช่น รายการที่นำเข้าจาก Word แล้วเลือกพื้นที่ผิด/ยังไม่ได้เลือก)
     * เปลี่ยนให้ทุกไตรมาสของหน่วยงาน/ปีงบประมาณ/พื้นที่เดียวกัน เพื่อให้ข้อมูลที่ไหลข้ามไตรมาสยังอยู่ชุดเดียวกัน
     */
    public function changeKidneyDhbArea(Request $request, $id)
    {
        abort_unless(auth()->user()->isSuperAdmin(), 403);
        $anchor = KidneyAssessment::with('user')->findOrFail($id);

        if ($request->boolean('new_pin')) {
            // ปักหมุดใหม่ (คลิกบนแผนที่) แล้วใช้เป็นพื้นที่ของรายงานนี้ทันที
            $v = $request->validate([
                'label' => 'nullable|string|max:255',
                'province_id' => 'required|integer|exists:province,province_id',
                'district_id' => 'nullable|integer|exists:district,district_id',
                'tambon_id' => 'nullable|integer|exists:tambon,tambon_id',
                'village_id' => 'nullable|integer|exists:village,village_id',
                'village_name_snapshot' => 'nullable|string|max:255',
                'latitude' => 'required|numeric|between:-90,90',
                'longitude' => 'required|numeric|between:-180,180',
            ]);
            $v['label'] = trim((string) ($v['label'] ?? '')) ?: null;
            $v['fiscal_year'] = (int) $anchor->fiscal_year;
            $v['created_by'] = auth()->id();
            // เลือกแค่จังหวัด (หรือจังหวัด+อำเภอ) ก็ปักได้ - คอลัมน์ district_id บังคับมีค่า จึงใช้ 0 = ไม่ระบุอำเภอ
            $v['district_id'] = $v['district_id'] ?? 0;
            $v['tambon_id'] = $v['tambon_id'] ?? null;
            $v['village_id'] = $v['village_id'] ?? null;
            $pin = OperatingAreaPin::create($v);
            $this->pinListMemo = [];
            $pin->load(['province', 'district', 'tambon', 'village']);
        } else {
            $request->validate(['pin_id' => 'required|integer']);
            $pin = OperatingAreaPin::with(['province', 'district', 'tambon', 'village'])->findOrFail((int) $request->pin_id);
        }
        if (OperatingAreaPin::hasDisplayColumns() && ($pin->display_only ?? false)) {
            return back()->withErrors(['error' => 'พื้นที่นี้เป็นหมุดแสดงผล เลือกไปรายงานไม่ได้']);
        }

        $placeName = $pin->village_name_snapshot
            ?? optional($pin->village)->village_name
            ?? optional($pin->tambon)->tambon_name
            ?? optional($pin->district)->district_name
            ?? optional($pin->province)->province_name;
        $newName = $pin->label ?: $placeName;

        $series = KidneyAssessment::where('user_id', $anchor->user_id)
            ->where('fiscal_year', $anchor->fiscal_year)
            ->where('operating_area', $anchor->operating_area)
            ->get();

        if ($newName !== $anchor->operating_area) {
            $clash = KidneyAssessment::where('user_id', $anchor->user_id)
                ->where('fiscal_year', $anchor->fiscal_year)
                ->where('operating_area', $newName)
                ->whereIn('quarter', $series->pluck('quarter')->all())
                ->exists();
            if ($clash) {
                return back()->withErrors(['error' => 'หน่วยงานนี้มีรายงานของพื้นที่ "' . $newName . '" ในไตรมาสเดียวกันของปีงบประมาณนี้อยู่แล้ว จึงเปลี่ยนไม่ได้']);
            }
        }

        foreach ($series as $row) {
            $row->operating_area = $newName;
            $row->operating_area_pin_id = $pin->id;
            $row->operating_area_province_id = $pin->province_id;
            $row->operating_area_district_id = $pin->district_id;
            $row->operating_area_tambon_id = $pin->tambon_id;
            $row->operating_area_village_id = $pin->village_id;
            $row->operating_area_village_name_snapshot = $pin->village_name_snapshot;
            $row->operating_area_latitude = $pin->latitude;
            $row->operating_area_longitude = $pin->longitude;
            $row->save();
        }

        return redirect()->route('admin.kidney-dhb.show', $anchor->id)
            ->with('success', 'เปลี่ยนพื้นที่ดำเนินงานเป็น "' . $newName . '" เรียบร้อยแล้ว (' . $series->count() . ' ไตรมาส)');
    }

    /**
     * ผู้ใช้นี้แก้ไขรายการ พชอ.ไต นี้ได้หรือไม่ - แก้ได้เฉพาะรายการที่ "หน่วยงานของตัวเองเป็นผู้ตอบ":
     *  - สคร. (rank 1): ทุกรายการ
     *  - สสจ. (rank 2): เฉพาะรายการที่ สสจ. จังหวัดตนเองเป็นผู้ตอบ (บัญชีเจ้าของรายการเป็นระดับ สสจ.
     *    ของจังหวัดเดียวกัน) - รายการที่ สสอ./รพ.สต. ในจังหวัดเป็นผู้ตอบ ดูได้แต่แก้ไม่ได้
     *  - สสอ. (rank 3): เฉพาะรายการที่ สสอ. อำเภอตนเองเป็นผู้ตอบ (ไม่รวม รพ.สต. ในอำเภอ)
     *  - รพ.สต. (rank 4): เฉพาะรายการของ รพ.สต. ตนเอง
     *  - ระดับอื่น: เฉพาะรายการของหน่วยงาน (agency) เดียวกัน
     * (การ "ดู" รายการยังเป็นขอบเขตเดียวกับ kidneyDHBList())
     */
    private function canManageKidneyAssessment($user, KidneyAssessment $assessment): bool
    {
        if (!$user) {
            return false;
        }
        if ($user->User_rank_id == 1) {
            return true;
        }
        if ($user->User_rank_id == 2) {
            $owner = $assessment->user;
            return $owner
                && (int) $owner->User_rank_id === 2
                && $owner->Province_id == $user->Province_id;
        }
        if ($user->User_rank_id == 3) {
            // สสอ.: เฉพาะรายการที่ สสอ. อำเภอตนเองเป็นผู้ตอบ - รายการที่ รพ.สต. ในอำเภอตอบ ดูได้แต่แก้ไม่ได้
            $owner = $assessment->user;
            return $owner
                && (int) $owner->User_rank_id === 3
                && $owner->District_id == $user->District_id;
        }
        if ($user->User_rank_id == 4) {
            // รพ.สต.: เฉพาะรายการของ รพ.สต. ตนเอง (รหัสหน่วยบริการเดียวกัน; ถ้าบัญชีไม่มีรหัส = เฉพาะบัญชีตนเอง)
            $owner = $assessment->user;
            if (!$owner || (int) $owner->User_rank_id !== 4) {
                return false;
            }
            return !empty($user->sh_id) ? $owner->sh_id == $user->sh_id : $owner->id == $user->id;
        }
        return in_array($assessment->user_id, $this->getAgencyUserIds($user));
    }

    /**
     * ข้อมูลตำแหน่ง/ชื่อพื้นที่ที่บันทึกไว้กับรายการนี้ - รูปแบบเดียวกับที่
     * operatingAreaPinsForUser()/usedOperatingAreasForAgency() ส่งให้ JS
     * (display_name/breadcrumb/province_name/lat/lng ...) เพื่อใช้ซ้ำโค้ดเดียวกัน.
     * ถ้ารายการนี้ผูกกับหมุดที่ลงทะเบียนไว้ ใช้พิกัดของหมุดนั้นเป็นหลัก.
     */
    private function kidneyAssessmentAreaGeo(KidneyAssessment $a): array
    {
        $pin = $a->operating_area_pin_id
            ? OperatingAreaPin::with(['province', 'district', 'tambon', 'village'])->find($a->operating_area_pin_id)
            : null;

        $provinceId = $a->operating_area_province_id ?: optional($pin)->province_id;
        $districtId = $a->operating_area_district_id ?: optional($pin)->district_id;
        $tambonId = $a->operating_area_tambon_id ?: optional($pin)->tambon_id;
        $villageId = $a->operating_area_village_id ?: optional($pin)->village_id;
        $villageName = $a->operating_area_village_name_snapshot ?: optional($pin)->village_name_snapshot;

        $provinceName = $provinceId ? Province::where('province_id', $provinceId)->value('province_name') : null;
        $districtName = $districtId ? District::where('district_id', $districtId)->value('district_name') : null;
        $tambonName = $tambonId ? Tambon::where('tambon_id', $tambonId)->value('tambon_name') : null;
        if (!$villageName && $villageId) {
            $villageName = Village::where('village_id', $villageId)->value('village_name');
        }

        $lat = $a->operating_area_latitude !== null ? (float) $a->operating_area_latitude : ($pin ? (float) $pin->latitude : null);
        $lng = $a->operating_area_longitude !== null ? (float) $a->operating_area_longitude : ($pin ? (float) $pin->longitude : null);

        return [
            'name' => $a->operating_area,
            // ชื่อพื้นที่จริง (หมู่บ้าน > ตำบล > อำเภอ > จังหวัด) - ชื่อ "ประเด็น" ที่ตั้งตอนปักหมุดแยกแสดงต่างหาก
            'place_name' => $villageName ?: ($tambonName ?: ($districtName ?: $provinceName)),
            'pin_id' => $a->operating_area_pin_id,
            'province_id' => $provinceId,
            'district_id' => $districtId,
            'tambon_id' => $tambonId,
            'village_id' => $villageId,
            'village_name_snapshot' => $villageName,
            'province_name' => $provinceName,
            'breadcrumb' => collect([$provinceName, $districtName, $tambonName, $villageName])->filter()->implode(' / '),
            'latitude' => $lat,
            'longitude' => $lng,
            // พื้นที่อื่นที่รายงานนี้ครอบคลุม - ปักหมุดบนแผนที่ทุกพื้นที่
            'extras' => $this->kidneyAssessmentExtraAreaGeo($a),
        ];
    }

    /** พื้นที่อื่นนอกพื้นที่หลักของรายงาน (หมุดที่ครอบคลุมเพิ่ม) สำหรับวาดบนแผนที่ */
    private function kidneyAssessmentExtraAreaGeo(KidneyAssessment $a): array
    {
        $ids = \App\Support\KidneyAreaList::extraPinIds($a);
        if (empty($ids)) {
            return [];
        }

        $pins = OperatingAreaPin::with(['province', 'district', 'tambon', 'village'])->whereIn('id', $ids)->get()->keyBy('id');

        return collect($ids)->map(function ($id) use ($pins) {
            $pin = $pins->get($id);
            if (!$pin) {
                return null;
            }
            $place = \App\Support\KidneyAreaList::pinPlace($pin);
            $village = $pin->village_name_snapshot ?: optional($pin->village)->village_name;

            return [
                'pin_id' => (int) $pin->id,
                'name' => $place,
                'issue' => \App\Support\KidneyAreaList::issueText($pin->label, $place),
                'province_name' => optional($pin->province)->province_name,
                'breadcrumb' => collect([optional($pin->province)->province_name, optional($pin->district)->district_name, optional($pin->tambon)->tambon_name, $village])->filter()->implode(' / '),
                'latitude' => $pin->latitude !== null ? (float) $pin->latitude : null,
                'longitude' => $pin->longitude !== null ? (float) $pin->longitude : null,
            ];
        })->filter()->values()->all();
    }

    /**
     * บันทึกการแก้ไขจากหน้ารายละเอียด (admin/kidney-dhb/{id}).
     *
     * {id} = รายการที่คลิกเข้ามา (บอกหน่วยงาน + พื้นที่ดำเนินงาน - แก้จากที่นี่ไม่ได้ เพราะมีหน้า
     * จัดการพื้นที่ดำเนินงานรองรับอยู่แล้ว). fiscal_year/quarter ในคำขอ = รายงานของปี/ไตรมาสที่
     * กำลังแก้ไข: มีอยู่แล้วก็อัปเดต ยังไม่มีก็สร้างใหม่ (คัดลอกพื้นที่/หมุดจาก {id}).
     *
     * ข้อ 1-8.3 "ไหล" จากไตรมาสก่อนหน้า (หลักเดียวกับ getKidneyDHBData()) จึงเทียบกับค่าที่ไหลมา
     * เป็นฐาน - บันทึกลงไตรมาสนี้เฉพาะช่องที่ผู้ใช้แก้จริง ส่วนช่องที่ยังเหมือนที่ไหลมาจะไม่ถูก
     * คัดลอกซ้ำ. ผู้แก้ไขถูกบันทึกลง reporter_metadata เฉพาะช่องที่เปลี่ยน ตามหลักเดียวกับ
     * storeKidneyDHB(). ไฟล์แนบเดิมคงไว้ตามเดิม.
     */
    public function updateKidneyDHB(Request $request, $id)
    {
        $anchor = KidneyAssessment::with('user')->findOrFail($id);
        abort_unless($this->canManageKidneyAssessment(auth()->user(), $anchor), 403);
        // สคร. (level 1) ทั่วไปดูอย่างเดียว - เฉพาะ SuperAdmin เท่านั้นที่บันทึกแก้ไขได้
        abort_if(auth()->user()->User_rank_id == 1 && !auth()->user()->isSuperAdmin(), 403);

        $catFields = ['category_1', 'category_2', 'category_3', 'category_4', 'category_5', 'category_6', 'category_7',
            'category_8_1', 'category_8_2', 'category_8_3'];
        $noteFields = ['problems_obstacles', 'recommendations_opportunities'];
        $rules = [
            'fiscal_year' => 'required|integer|between:2500,2700',
            'quarter' => 'required|in:1,2,3,4',
        ];
        foreach (array_merge($catFields, $noteFields) as $f) {
            $rules[$f] = 'nullable|string';
        }
        $request->validate($rules + \App\Services\KidneyImageService::validationRules(), \App\Services\KidneyImageService::validationMessages());

        // มีการเพิ่ม/แทนที่/ลบรูปภาพมาด้วยหรือไม่ (นับเป็นการแก้ไข แม้ข้อความไม่เปลี่ยน)
        $hasImageWork = false;
        $imgFiles = (array) $request->file('kidney_images', []);
        array_walk_recursive($imgFiles, function ($f) use (&$hasImageWork) {
            if ($f instanceof \Illuminate\Http\UploadedFile) {
                $hasImageWork = true;
            }
        });
        $imgRemoves = (array) $request->input('kidney_images_remove', []);
        array_walk_recursive($imgRemoves, function ($v) use (&$hasImageWork) {
            if ((string) $v === '1') {
                $hasImageWork = true;
            }
        });

        $year = (int) $request->input('fiscal_year');
        $quarter = (int) $request->input('quarter');

        $agencyUserIds = $this->getAgencyUserIds($anchor->user) ?: [$anchor->user_id];
        $rows = KidneyAssessment::whereIn('user_id', $agencyUserIds)
            ->where('fiscal_year', $year)
            ->where('operating_area', $anchor->operating_area)
            ->where('quarter', '<=', $quarter)
            ->orderBy('quarter', 'asc')
            ->get();
        $target = $rows->firstWhere('quarter', $quarter);

        // ไตรมาสที่ สคร. (level 1) ยืนยันข้อมูลแล้ว ผู้ตอบแบบประเมินแก้ไขไม่ได้
        if ($target && $target->confirmed_at && auth()->user()->User_rank_id != 1) {
            return redirect()->route('admin.kidney-dhb.show', $target->id)->with('error',
                'ไตรมาส ' . $quarter . ' ได้รับการยืนยันข้อมูลแล้ว จึงไม่สามารถแก้ไขได้ หากต้องแก้ไขกรุณาติดต่อ สคร. เพื่อยกเลิกการยืนยัน');
        }

        $normalize = function ($v) {
            // a literal "<br>" in typed/imported text is a line break, not content
            return trim(str_replace("\r\n", "\n", preg_replace('/<br\s*\/?>/i', "\n", (string) ($v ?? ''))));
        };

        $currentUser = auth()->user();
        $currentName = trim(($currentUser->prefix ?? '') . ($currentUser->User_firstname ?? '') . ' ' . ($currentUser->User_lastname ?? '')) ?: ($currentUser->name ?? 'ไม่ระบุชื่อ');
        $now = date('d/m/Y H:i');

        $meta = $target && is_array($target->reporter_metadata) ? $target->reporter_metadata : [];
        $data = [];

        foreach (array_merge($catFields, $noteFields) as $f) {
            if (!$request->has($f)) {
                continue;
            }
            $newVal = $normalize($request->input($f));
            $own = $normalize($target ? $target->$f : '');

            if (in_array($f, $catFields)) {
                // ฐาน = ข้อความที่ไหลมาถึงไตรมาสนี้ (ล่าสุดตาม updated_at) - เหมือนที่แสดงบนหน้าจอ
                $base = '';
                $maxUpdate = -1;
                foreach ($rows as $r) {
                    if (trim((string) $r->$f) !== '') {
                        $time = $r->updated_at ? $r->updated_at->timestamp : 0;
                        if ($time >= $maxUpdate) {
                            $base = $normalize($r->$f);
                            $maxUpdate = $time;
                        }
                    }
                }
            } else {
                $base = $own; // ปัญหา/ข้อเสนอแนะเป็นรายไตรมาส ไม่ไหล
            }

            if ($newVal === $base) {
                continue; // ไม่ได้แก้ (ยังเป็นค่าที่ไหลมา/ค่าเดิม)
            }
            if ($newVal === '' && $own === '') {
                continue; // ลบช่องที่ไหลมาจากไตรมาสก่อน - ไตรมาสนี้ไม่มีค่าของตัวเองให้ลบ
            }
            $data[$f] = $newVal === '' ? null : $newVal;
            $meta[$f] = ['name' => $currentName, 'time' => $now];
        }

        if (empty($data) && !$hasImageWork) {
            return ($target
                ? redirect()->route('admin.kidney-dhb.show', $target->id)
                : redirect()->route('admin.kidney-dhb.show', ['id' => $anchor->id, 'filter_fiscal_year' => $year, 'filter_quarter' => $quarter]))
                ->with('success', 'ไม่มีการเปลี่ยนแปลงข้อมูล');
        }

        try {
            if ($target) {
                $target->fill($data);
                $target->reporter_metadata = $meta;
                $target->is_read = 0; // เหมือน storeKidneyDHB(): ทุกการแก้ไข = รายการที่ยังไม่ได้เปิดอ่าน
                $target->save();
                if ($hasImageWork) {
                    $target->touch(); // แก้เฉพาะรูป - ให้เวลาอัปเดตล่าสุดขยับด้วย
                }
                $message = 'บันทึกการแก้ไขข้อมูล พชอ.ไต เรียบร้อยแล้ว';
            } else {
                $target = new KidneyAssessment(array_merge($data, [
                    'user_id' => $anchor->user_id,
                    'fiscal_year' => $year,
                    'quarter' => $quarter,
                    'operating_area' => $anchor->operating_area,
                    'operating_area_pin_id' => $anchor->operating_area_pin_id,
                    'operating_area_province_id' => $anchor->operating_area_province_id,
                    'operating_area_district_id' => $anchor->operating_area_district_id,
                    'operating_area_tambon_id' => $anchor->operating_area_tambon_id,
                    'operating_area_village_id' => $anchor->operating_area_village_id,
                    'operating_area_village_name_snapshot' => $anchor->operating_area_village_name_snapshot,
                    'operating_area_latitude' => $anchor->operating_area_latitude,
                    'operating_area_longitude' => $anchor->operating_area_longitude,
                    'reporter_metadata' => $meta,
                    'is_read' => 0,
                ]));
                $target->save();
                $message = 'สร้างรายงานไตรมาส ' . $quarter . ' ปีงบประมาณ ' . $year . ' เรียบร้อยแล้ว';
            }
        } catch (\Exception $e) {
            return redirect()->back()->withInput()
                ->withErrors(['error' => 'เกิดข้อผิดพลาดในการบันทึกข้อมูล: ' . $e->getMessage()]);
        }

        // รูปภาพประกอบ (สูงสุด 3 รูปต่อข้อ) - เพิ่ม/แทนที่/ลบตามที่ฟอร์มแก้ไขส่งมา
        $imageErrors = $hasImageWork
            ? app(\App\Services\KidneyImageService::class)->sync($target, $request, $currentUser->id)
            : [];

        $redirect = redirect()->route('admin.kidney-dhb.show', $target->id)->with('success', $message);
        if (!empty($imageErrors)) {
            $redirect->with('error', 'บันทึกข้อมูลแล้ว แต่รูปภาพบางส่วนไม่ถูกบันทึก: ' . implode(' | ', $imageErrors));
        }

        return $redirect;
    }

    /**
     * Level 1 (User_rank_id == 1, สคร. เขต 10) toggles whether this
     * specific quarterly record has been reviewed/confirmed. Each record
     * already represents exactly one (agency, fiscal_year, quarter) - see
     * the unique constraint on kidney_assessments - so confirming here is
     * naturally a per-quarter confirmation.
     */
    public function confirmKidneyDHB($id)
    {
        abort_unless(auth()->user()->User_rank_id == 1, 403);

        $assessment = KidneyAssessment::findOrFail($id);

        if ($assessment->confirmed_at) {
            $assessment->confirmed_at = null;
            $assessment->confirmed_by = null;
            $assessment->save();

            return redirect()->route('admin.kidney-dhb.show', $assessment->id)
                ->with('success', 'ยกเลิกการยืนยันข้อมูลแล้ว');
        }

        $assessment->confirmed_at = now();
        $assessment->confirmed_by = auth()->id();
        $assessment->save();

        return redirect()->route('admin.kidney-dhb.show', $assessment->id)
            ->with('success', 'ยืนยันข้อมูลเรียบร้อยแล้ว');
    }

    /**
     * SuperAdmin: ลบข้อมูลทั้งหมดที่ตรงกับตัวกรองของหน้า รายการ พชอ.ไต
     * ลบทุกไตรมาสของรายการที่แสดง (ถ้าเลือกไตรมาสไว้ ลบเฉพาะไตรมาสนั้น) - รูปภาพประกอบถูกลบตามด้วย
     * (model event) - ส่ง count_only=1 เพื่อขอจำนวนก่อนยืนยัน, ส่ง confirm=1 เพื่อลบจริง
     */
    public function deleteFilteredKidneyDHB(Request $request)
    {
        $user = auth()->user();
        abort_unless($user && $user->isSuperAdmin(), 404);

        $query = KidneyAssessment::query();
        $statusRaw = $request->input('status', '');
        if (in_array($statusRaw, ['selected', 'free', 'display'], true)) {
            $query->whereRaw('1 = 0'); // แถวกลุ่มนี้ยังไม่มีรายงาน - ไม่มีข้อมูลให้ลบ
        } elseif ($statusRaw === 'unconfirmed') {
            $query->whereNull('confirmed_at');
        } elseif ($statusRaw === 'confirmed') {
            $query->whereNotNull('confirmed_at');
        }
        if ($request->filled('fiscal_year')) {
            $query->where('fiscal_year', $request->fiscal_year);
        }
        if ($request->filled('quarter')) {
            $query->where('quarter', $request->quarter);
        }
        if ($request->filled('province_id') || $request->filled('district_id') || $request->filled('org_type')) {
            $query->whereHas('user', function ($q) use ($request) {
                if ($request->filled('province_id')) {
                    $q->where('Province_id', $request->province_id);
                }
                if ($request->filled('district_id')) {
                    $q->where('District_id', $request->district_id);
                }
                if ($request->filled('org_type')) {
                    $q->where('User_rank_id', $request->org_type);
                }
            });
        }
        if ($request->filled('operating_area')) {
            $query->where('operating_area', $request->operating_area);
        }

        $count = (clone $query)->count();

        if ($request->boolean('count_only')) {
            return response()->json(['success' => true, 'count' => $count]);
        }
        if (!$request->boolean('confirm')) {
            return response()->json(['success' => false, 'message' => 'ต้องยืนยันก่อนลบข้อมูล'], 422);
        }
        if ($count === 0) {
            return response()->json(['success' => false, 'message' => 'ไม่พบข้อมูลที่ตรงกับเงื่อนไขการกรอง'], 404);
        }

        $deleted = 0;
        try {
            $query->orderBy('id')->chunkById(100, function ($rows) use (&$deleted) {
                foreach ($rows as $row) {
                    $row->delete(); // ผ่าน model เพื่อให้ลบรูปภาพประกอบด้วย
                    $deleted++;
                }
            });
        } catch (\Throwable $e) {
            \Log::error('Kidney delete filtered error: ' . $e->getMessage());
            return response()->json(['success' => false, 'message' => 'ลบไม่สำเร็จ (ลบไปแล้ว ' . number_format($deleted) . ' รายการ): ' . $e->getMessage()], 500);
        }

        return response()->json([
            'success' => true,
            'message' => 'ลบข้อมูล พชอ.ไต เรียบร้อยแล้ว จำนวน ' . number_format($deleted) . ' รายการ',
            'deleted_count' => $deleted,
        ]);
    }

    public function destroyKidneyDHB($id)
    {
        $assessment = KidneyAssessment::with('user')->findOrFail($id);

        // พื้นที่ (หมุด) ที่รายงานนี้ผูกอยู่ - รวมพื้นที่ที่ปักพร้อมกันในรายงานเดียวกัน
        $pinIds = collect([(int) $assessment->operating_area_pin_id])
            ->merge(\App\Support\KidneyAreaList::extraPinIds($assessment))
            ->filter()->unique()->values()->all();
        $agencyUserIds = $assessment->user ? ($this->getAgencyUserIds($assessment->user) ?: [$assessment->user_id]) : [$assessment->user_id];
        // ผู้เลือกพื้นที่อาจอยู่คนละกลุ่ม (จังหวัด/อำเภอ) กับเจ้าของรายงาน เช่น รายงานที่ SuperAdmin กรอกแทน
        // จึงรวมผู้ใช้ในอำเภอ/จังหวัดเดียวกับเจ้าของรายงานด้วย เพื่อให้ปลดการเลือกได้ครบ
        if ($assessment->user) {
            $sameArea = \App\Models\User::query()
                ->where(function ($q) use ($assessment) {
                    $q->where('id', $assessment->user_id);
                    if ($assessment->user->District_id) {
                        $q->orWhere('District_id', $assessment->user->District_id);
                    }
                })
                ->pluck('id')->all();
            $agencyUserIds = array_values(array_unique(array_merge($agencyUserIds, $sameArea)));
        }

        $assessment->delete();

        // ลบพื้นที่ = ลบจริง: เมื่อไม่เหลือรายงานของพื้นที่นี้ (ทุกไตรมาส) ให้ปลดการเลือกพื้นที่ของหน่วยงานด้วย
        // มิฉะนั้นพื้นที่จะย้อนกลับมาเป็นแถว "เลือกพื้นที่แล้ว · ยังไม่มีรายงานผล" ในตาราง
        if ($pinIds && \App\Support\SchemaProbe::hasTable('kidney_operating_area_selections')) {
            $stillReported = KidneyAssessment::whereIn('user_id', $agencyUserIds)
                ->whereIn('operating_area_pin_id', $pinIds)
                ->exists();
            if (!$stillReported) {
                DB::table('kidney_operating_area_selections')
                    ->whereIn('user_id', $agencyUserIds)
                    ->whereIn('pin_id', $pinIds)
                    ->delete();
            }
        }

        // SuperAdmin ลบรายงาน = ลบพื้นที่เลย: ถ้าไม่เหลือรายงานใดของพื้นที่นี้ (ทุกหน่วยงาน) ให้ลบหมุดพื้นที่ด้วย
        // เพื่อให้หายจากหน้าแผนที่/ตารางเปรียบเทียบของหน้าสาธารณะ (หมุดแสดงผลไม่ถูกลบ)
        $pinsRemoved = 0;
        $me = auth()->user();
        if ($pinIds && $me && $me->isSuperAdmin()) {
            $reportedAnywhere = [];
            foreach (KidneyAssessment::get(['operating_area_pin_id', 'reporter_metadata']) as $r) {
                $reportedAnywhere[] = (int) $r->operating_area_pin_id;
                foreach (\App\Support\KidneyAreaList::extraPinIds($r) as $x) {
                    $reportedAnywhere[] = (int) $x;
                }
            }
            $removable = array_values(array_diff($pinIds, $reportedAnywhere));
            if ($removable) {
                if (\App\Support\SchemaProbe::hasTable('kidney_operating_area_selections')) {
                    DB::table('kidney_operating_area_selections')->whereIn('pin_id', $removable)->delete();
                }
                $q = OperatingAreaPin::whereIn('id', $removable);
                if (OperatingAreaPin::hasDisplayColumns()) {
                    $q->where(fn($w) => $w->whereNull('display_only')->orWhere('display_only', false));
                }
                $pinsRemoved = $q->delete();
                $this->pinListMemo = [];
            }
        }

        return redirect()->route('admin.kidney-dhb-list')->with('success', $pinsRemoved ? 'ลบข้อมูลและพื้นที่ดำเนินงานออกจากระบบทั้งหมดเรียบร้อยแล้ว' : 'ลบข้อมูลและพื้นที่ดำเนินงานเรียบร้อยแล้ว');
    }

    public function exportKidneyDHBPdf($id)
    {
        $assessment = KidneyAssessment::with(['user.province', 'user.district', 'user.subdistrictHospital', 'user.hospital'])->findOrFail($id);

        $agencyUserIds = $this->getAgencyUserIds($assessment->user);

        // Resolve Cumulative Data dynamically for PDF - scoped to this exact
        // operating area, so printing one area's report never blends in
        // category progress saved under a different area of the same agency.
        $allAssessments = KidneyAssessment::whereIn('user_id', $agencyUserIds)
            ->where('fiscal_year', $assessment->fiscal_year)
            ->where('operating_area', $assessment->operating_area)
            ->where('quarter', '<=', $assessment->quarter)
            ->orderBy('quarter', 'asc')
            ->get();

        $catFields = [
            'category_1',
            'category_2',
            'category_3',
            'category_4',
            'category_5',
            'category_6',
            'category_7',
            'category_8_1',
            'category_8_2',
            'category_8_3'
        ];

        foreach ($catFields as $cat) {
            $winner = null;
            $maxUpdate = -1;
            foreach ($allAssessments as $rec) {
                if (!empty($rec->$cat)) {
                    $time = $rec->updated_at ? $rec->updated_at->timestamp : 0;
                    if ($time >= $maxUpdate) {
                        $winner = $rec;
                        $maxUpdate = $time;
                    }
                }
            }
            if ($winner) {
                $assessment->$cat = $winner->$cat;
            }
        }

        $assessment->problems_obstacles = trim($assessment->problems_obstacles);
        $assessment->recommendations_opportunities = trim($assessment->recommendations_opportunities);

        // รูปภาพประกอบของรายงานฉบับนี้ (ไม่ไหลข้ามไตรมาส)
        $images = \App\Services\KidneyImageService::groupedFor($assessment->id);

        $this->attachAreaIssue([$assessment]);

        return view('admin.kidney-dhb-print', compact('assessment', 'images'));
    }

    /**
     * Export Kidney DHB report to a native .docx - same content/layout as
     * exportKidneyDHBPdf's print view, built directly with ZipArchive (see
     * AssessmentWordExportService), no external Word library needed.
     */
    public function exportKidneyDHBWord($id)
    {
        $assessment = KidneyAssessment::with(['user.province', 'user.district', 'user.subdistrictHospital', 'user.hospital'])->findOrFail($id);

        $agencyUserIds = $this->getAgencyUserIds($assessment->user);

        // Same cumulative-data resolution as exportKidneyDHBPdf, kept in
        // sync deliberately rather than shared, so the two exports never
        // drift by editing one and forgetting the other.
        $allAssessments = KidneyAssessment::whereIn('user_id', $agencyUserIds)
            ->where('fiscal_year', $assessment->fiscal_year)
            ->where('operating_area', $assessment->operating_area)
            ->where('quarter', '<=', $assessment->quarter)
            ->orderBy('quarter', 'asc')
            ->get();

        $catFields = [
            'category_1',
            'category_2',
            'category_3',
            'category_4',
            'category_5',
            'category_6',
            'category_7',
            'category_8_1',
            'category_8_2',
            'category_8_3'
        ];

        foreach ($catFields as $cat) {
            $winner = null;
            $maxUpdate = -1;
            foreach ($allAssessments as $rec) {
                if (!empty($rec->$cat)) {
                    $time = $rec->updated_at ? $rec->updated_at->timestamp : 0;
                    if ($time >= $maxUpdate) {
                        $winner = $rec;
                        $maxUpdate = $time;
                    }
                }
            }
            if ($winner) {
                $assessment->$cat = $winner->$cat;
            }
        }

        $assessment->problems_obstacles = trim($assessment->problems_obstacles);
        $assessment->recommendations_opportunities = trim($assessment->recommendations_opportunities);

        $reporter = AssessmentWordExportService::resolveReporter($assessment);
        $binary = AssessmentWordExportService::buildKidneyDocx($assessment, $reporter);

        $unitName = AssessmentWordExportService::resolveUnitName($assessment->user);
        $fileName = 'แบบรายงาน_พชอไต_' . str_replace(' ', '_', $unitName) . '_' . date('Ymd_His') . '.docx';

        return response($binary, 200, [
            'Content-Type' => 'application/vnd.openxmlformats-officedocument.wordprocessingml.document',
            'Content-Disposition' => 'attachment; filename="report.docx"; filename*=UTF-8\'\'' . rawurlencode($fileName),
            'Content-Length' => strlen($binary),
        ]);
    }

    /**
     * Display the list of Salt Assessments with filters.
     */
    public function saltAssessmentList(Request $request)
    {
        $query = SaltAssessment::query();

        // Data Isolation: Level 1 (Admin) sees everything, others see only their own agency recordings
        if (auth()->user()->User_rank_id != 1) {
            $agencyUserIds = $this->getAgencyUserIds(auth()->user());
            $query->whereIn('user_id', $agencyUserIds);
        }

        // Eager load for display
        $query->with(['user.province', 'user.district', 'user.subdistrictHospital', 'user.hospital']);

        // Filter by Fiscal Year
        if ($request->filled('fiscal_year')) {
            $query->where('fiscal_year', $request->fiscal_year);
        }

        // Filter by Quarter
        if ($request->filled('quarter')) {
            $query->where('salt_assessments.quarter', $request->quarter);
        } else {
            // No specific quarter chosen: show one row per agency PER
            // FISCAL YEAR (that year's latest quarter) - not collapsed
            // across different fiscal years (each year an agency reported
            // still gets its own row), and not exploded into one row per
            // quarter within the same year either. This is the middle
            // ground the user asked for after seeing the fully-un-deduped
            // list explode one agency's 4 quarters of FY2569 into 4 rows.
            $query->whereIn('salt_assessments.id', function ($q) use ($request) {
                // Picks the row with the HIGHEST QUARTER NUMBER within
                // each (agency, fiscal_year) group - not simply the
                // highest id - via MySQL's classic "greatest-n-per-group"
                // GROUP_CONCAT/SUBSTRING_INDEX trick, since insertion
                // order (id) does not reliably track quarter order (a
                // quarter 1 row can easily be created after a quarter 3
                // row, e.g. via a later correction or a Word import).
                $q->select(\DB::raw("CAST(SUBSTRING_INDEX(GROUP_CONCAT(s2.id ORDER BY s2.quarter DESC, s2.id DESC), ',', 1) AS UNSIGNED)"))
                    ->from('salt_assessments as s2')
                    ->join('users as u2', 's2.user_id', '=', 'u2.id');

                if ($request->filled('fiscal_year')) {
                    $q->where('s2.fiscal_year', $request->fiscal_year);
                }

                $q->groupBy([
                    's2.fiscal_year',
                    'u2.User_rank_id',
                    \DB::raw('CASE
                        WHEN u2.User_rank_id = 5 THEN CAST(u2.hos_id AS CHAR)
                        WHEN u2.User_rank_id = 4 THEN CAST(u2.sh_id AS CHAR)
                        WHEN u2.User_rank_id = 3 THEN CAST(u2.District_id AS CHAR)
                        WHEN u2.User_rank_id = 2 THEN CAST(u2.Province_id AS CHAR)
                        ELSE CAST(u2.id AS CHAR)
                    END')
                ]);
            });
        }

        // Filter by Province and District (via User relationship)
        if ($request->filled('province_id') || $request->filled('district_id')) {
            $query->whereHas('user', function ($q) use ($request) {
                if ($request->filled('province_id')) {
                    $q->where('Province_id', $request->province_id);
                }
                if ($request->filled('district_id')) {
                    $q->where('District_id', $request->district_id);
                }
            });
        }

        // Calculate Summary Statistics (Full filtered query, not just current page)
        $statsQuery = clone $query;
        $totalAssessments = $statsQuery->count();

        // Define "Complete" as having all 4 main steps plus all 5 sub-items of step 5 filled
        $completeCount = (clone $query)->where(function ($q) {
            foreach (range(1, 4) as $i) {
                $q->whereNotNull("ans_{$i}_detail")->where("ans_{$i}_detail", '!=', '');
            }
            foreach (range(1, 5) as $i) {
                $q->whereNotNull("ans_5_{$i}_detail")->where("ans_5_{$i}_detail", '!=', '');
            }
        })->count();

        $stats = [
            'total' => $totalAssessments,
            'complete' => $completeCount,
            'in_progress' => $totalAssessments - $completeCount
        ];

        $assessments = $query->join('users', 'salt_assessments.user_id', '=', 'users.id')
            ->select('salt_assessments.*')
            ->orderBy('salt_assessments.fiscal_year', 'desc')
            ->orderBy('users.Province_id')
            ->orderBy('users.District_id')
            ->orderBy('users.User_rank_id')
            ->orderBy('users.hos_id')
            ->orderBy('users.sh_id')
            ->orderBy('salt_assessments.quarter', 'asc')
            ->paginate(15);
        $assessments->appends($request->all());

        // Landing on this list is treated as "reviewed": mark every record this
        // user is allowed to see as read, so the sidebar unread badge clears
        // immediately rather than requiring each row to be opened individually.
        // Scoped to the same visibility rule as above, ignoring the fiscal
        // year/quarter/province filters (those only affect what's displayed on
        // this page, not what counts as "reviewed" for the badge).
        $markReadQuery = SaltAssessment::where('is_read', 0);
        if (auth()->user()->User_rank_id != 1) {
            $markReadQuery->whereIn('user_id', $agencyUserIds);
        }
        $unreadIds = $markReadQuery->pluck('id');
        if ($unreadIds->isNotEmpty()) {
            \DB::table('salt_assessments')->whereIn('id', $unreadIds)->update(['is_read' => 1]);
        }

        // When showing the aggregate "latest record per agency" view (no specific
        // quarter selected), the base row is just whichever quarter happens to be
        // the most recent DB record for that agency. Detail/file fields entered in
        // OTHER quarters (e.g. someone went back and filled in quarter 1 after a
        // quarter 2 record already existed) would then show as "not done" even
        // though the agency has actually submitted that item for the fiscal year.
        // Backfill DETAIL TEXT ONLY from sibling quarter records so the list
        // reflects the cumulative status for the whole fiscal year, matching how
        // the per-agency report form (getAssessmentData) carries detail text
        // forward across quarters. Files are intentionally excluded from this
        // backfill: a PDF stays attached to the quarter it was actually uploaded
        // to, so the paperclip badge only lights up for that specific quarter.
        $mergeFields = ['1', '2', '3', '4', '5_1', '5_2', '5_3', '5_4', '5_5'];

        // Memoize per-scope lookups: several rows on this page can belong
        // to the same province (rank 2) or district (rank 3+) group -
        // rank 3/4/5 agencies in one district all resolve to the same
        // sibling set, for example. Previously this ran a fresh
        // getAgencyUserIds() query AND a fresh sibling query for every
        // single row (an N+1 pattern - up to 2x the page size in extra
        // queries). Caching by scope collapses repeats down to one query
        // per distinct scope+fiscal_year combination actually seen on
        // this page.
        $siblingIdsCache = [];
        $siblingsCache = [];

        // This loop always runs (not just when no quarter filter is
        // applied) because every row needs its Q1-Q4 confirmation
        // column filled in regardless of the quarter filter. The
        // detail-text backfill below stays gated to the deduped
        // "latest quarter per agency" view only, same as before.
        foreach ($assessments as $item) {
            // Built as a plain local array, not mutated in place on the
            // model - PHP can't do indirect modification of an overloaded
            // (magic __get/__set) Eloquent attribute like
            // $item->quarterConfirmations[$q] = ... (it silently has no
            // effect and logs an "Indirect modification" notice).
            $quarters = [1 => 'none', 2 => 'none', 3 => 'none', 4 => 'none'];

            $scopeUser = $item->user;
            if (!$scopeUser) {
                $item->quarterConfirmations = $quarters;
                continue;
            }

            $scopeKey = $scopeUser->User_rank_id . '-' . ($scopeUser->User_rank_id == 2 ? $scopeUser->Province_id : $scopeUser->District_id);

            if (!array_key_exists($scopeKey, $siblingIdsCache)) {
                $siblingIdsCache[$scopeKey] = $this->getAgencyUserIds($scopeUser);
            }
            $siblingIds = $siblingIdsCache[$scopeKey];

            if (empty($siblingIds)) {
                $item->quarterConfirmations = $quarters;
                continue;
            }

            $siblingsKey = $scopeKey . '-' . $item->fiscal_year;
            if (!array_key_exists($siblingsKey, $siblingsCache)) {
                $siblingsCache[$siblingsKey] = SaltAssessment::whereIn('user_id', $siblingIds)
                    ->where('fiscal_year', $item->fiscal_year)
                    ->orderByDesc('updated_at')
                    ->get();
            }

            $allForYear = $siblingsCache[$siblingsKey];

            // Per-quarter confirmation status for this agency+fiscal_year,
            // across every sibling account that can represent this same
            // reporting unit (see getAgencyUserIds) - not just this row's
            // own user_id, so two accounts covering the same physical
            // office still roll up together. A quarter counts as
            // "confirmed" if ANY sibling record for it has been confirmed.
            foreach ($allForYear as $rec) {
                $q = (int) $rec->quarter;
                if ($q < 1 || $q > 4) {
                    continue;
                }
                if ($rec->confirmed_at) {
                    $quarters[$q] = 'confirmed';
                } elseif ($quarters[$q] !== 'confirmed') {
                    $quarters[$q] = 'pending';
                }
            }

            $item->quarterConfirmations = $quarters;

            if ($request->filled('quarter')) {
                continue;
            }

            $siblings = $allForYear->where('id', '!=', $item->id);

            if ($siblings->isEmpty()) {
                continue;
            }

            foreach ($mergeFields as $f) {
                $detailKey = "ans_{$f}_detail";

                if (empty($item->$detailKey)) {
                    foreach ($siblings as $sib) {
                        if (!empty($sib->$detailKey)) {
                            $item->$detailKey = $sib->$detailKey;
                            break;
                        }
                    }
                }
            }
        }

        if ($request->ajax()) {
            return view('admin.salt-assessment-table', compact('assessments', 'stats'));
        }

        $provinces = \App\Support\AdminCache::provinces();

        // ปีงบประมาณ options for the filter dropdown - real distinct years
        // already in the data, plus any admin-enabled years and minus any
        // admin-hidden years from "จัดการปีงบประมาณ" (FiscalYearController).
        $years = FiscalYear::selectableYearsFor('salt_assessment', \App\Support\AdminCache::distinct(\App\Models\SaltAssessment::class, 'fiscal_year'));

        // Word-import entry point ("นำเข้าไฟล์ Word") is shown on this list
        // only for Level 1 - same rank gate as the actual import feature on
        // admin.report-progress (see AdminController::reportProgress()'s
        // $canImportWord).
        $canImportWord = auth()->user()->User_rank_id == 1;

        // ทำแบบประเมินครบทุกปีงบประมาณแล้วหรือยัง (ผู้ใช้ที่ไม่ใช่ สคร.) - ให้ปุ่ม "ทำแบบประเมิน" เด้งป๊อปอัปแจ้งทันทีโดยไม่ต้องโหลดหน้าใหม่
        // เกณฑ์เดียวกับ reportProgress() โหมด new=1 (ปีใดมีรายการของหน่วยงาน = ทำแล้ว; นับตั้งแต่ปี 2568 ถึงปีปัจจุบัน)
        $allDoneYears = [];
        $notOpenYear = null;
        $currentAgencyUser = auth()->user();
        if ($currentAgencyUser->User_rank_id != 1) {
            $doneYearsAll = SaltAssessment::whereIn('user_id', $this->getAgencyUserIds($currentAgencyUser))
                ->distinct()->orderBy('fiscal_year', 'desc')->pluck('fiscal_year')
                ->map(fn ($y) => (int) $y)->all();
            $block = $this->saltStartBlock($doneYearsAll);
            if ($block && $block['type'] === 'not_open') {
                $notOpenYear = $block['year'];
            } elseif ($block) {
                $allDoneYears = $doneYearsAll;
            }
        }

        return view('admin.salt-assessment-list', compact('assessments', 'provinces', 'stats', 'years', 'canImportWord', 'allDoneYears', 'notOpenYear'));
    }

    public function showSaltAssessment(Request $request, $id)
    {
        $assessment = SaltAssessment::with(['user.province', 'user.district', 'user.subdistrictHospital', 'user.hospital'])->findOrFail($id);
        $userId = $assessment->user_id;

        // Handle filter form submission
        if ($request->filled('filter_fiscal_year') || $request->filled('filter_quarter')) {
            $targetYear = $request->filter_fiscal_year ?? $assessment->fiscal_year;
            $targetQuarter = $request->filter_quarter ?? $assessment->quarter;
            $agencyUserIds = $this->getAgencyUserIds($assessment->user);

            $filteredAssessment = SaltAssessment::whereIn('user_id', $agencyUserIds)
                ->where('fiscal_year', $targetYear)
                ->where('quarter', $targetQuarter)
                ->first();

            // Fallback: If exact quarter is not found for the requested year, just grab the latest available quarter for that year
            if (!$filteredAssessment && $targetYear != $assessment->fiscal_year) {
                $filteredAssessment = SaltAssessment::whereIn('user_id', $agencyUserIds)
                    ->where('fiscal_year', $targetYear)
                    ->orderBy('quarter', 'desc')
                    ->first();
            }

            if ($filteredAssessment && $filteredAssessment->id != $assessment->id) {
                return redirect()->route('admin.salt-assessment.show', $filteredAssessment->id);
            } else if (!$filteredAssessment) {
                return redirect()->route('admin.salt-assessment.show', $assessment->id)
                    ->with('error', 'ไม่พบข้อมูลสำหรับปีงบประมาณและไตรมาสที่เลือก')
                    ->with('filter_year', $targetYear)
                    ->with('filter_quarter', $targetQuarter);
            }
        }

        // Get available options for dropdowns (All possible years and quarters)
        $currentYear = date('Y') + 543;
        $availableYears = range($currentYear + 1, $currentYear - 2); // E.g., 2570 to 2567
        rsort($availableYears);

        $availableQuarters = [1, 2, 3, 4];

        if (!$assessment->is_read) {
            $assessment->is_read = true;
            $assessment->save();
        }

        return view('admin.salt-assessment-detail', compact('assessment', 'availableYears', 'availableQuarters'));
    }

    /**
     * Level 1 (User_rank_id == 1, สคร. เขต 10) toggles whether this
     * specific quarterly record has been reviewed/confirmed. Each record
     * already represents exactly one (agency, fiscal_year, quarter) - see
     * the unique constraint on salt_assessments - so confirming here is
     * naturally a per-quarter confirmation.
     */
    public function confirmSaltAssessment(Request $request, $id)
    {
        abort_unless(auth()->user()->User_rank_id == 1, 403);

        $assessment = SaltAssessment::findOrFail($id);

        if ($assessment->confirmed_at) {
            $assessment->confirmed_at = null;
            $assessment->confirmed_by = null;
            $assessment->save();
            $message = 'ยกเลิกการยืนยันข้อมูลแล้ว';
        } else {
            $assessment->confirmed_at = now();
            $assessment->confirmed_by = auth()->id();
            $assessment->save();
            $message = 'ยืนยันข้อมูลเรียบร้อยแล้ว';
        }

        // เรียกจากหน้าแบบรายงาน (fetch) -> ตอบ JSON เพื่ออัปเดตแถบสถานะโดยไม่โหลดหน้าใหม่
        if ($request->expectsJson()) {
            return response()->json([
                'success' => true,
                'message' => $message,
                'quarter' => (int) $assessment->quarter,
                'state' => $this->saltQuarterState($assessment->fresh('confirmedBy')),
            ]);
        }

        return redirect()->route('admin.salt-assessment.show', $assessment->id)
            ->with('success', $message);
    }

    /**
     * SuperAdmin: ลบข้อมูลทั้งหมดที่ตรงกับตัวกรองของหน้า รายการแบบประเมินลดการบริโภคเกลือ
     * ลบทุกไตรมาสของรายการที่แสดง (ถ้าเลือกไตรมาสไว้ ลบเฉพาะไตรมาสนั้น)
     * count_only=1 ขอจำนวนก่อนยืนยัน, confirm=1 ลบจริง
     */
    public function deleteFilteredSaltAssessment(Request $request)
    {
        $user = auth()->user();
        abort_unless($user && $user->isSuperAdmin(), 404);

        $query = SaltAssessment::query();
        if ($request->filled('fiscal_year')) {
            $query->where('fiscal_year', $request->fiscal_year);
        }
        if ($request->filled('quarter')) {
            $query->where('quarter', $request->quarter);
        }
        if ($request->filled('province_id') || $request->filled('district_id')) {
            $query->whereHas('user', function ($q) use ($request) {
                if ($request->filled('province_id')) {
                    $q->where('Province_id', $request->province_id);
                }
                if ($request->filled('district_id')) {
                    $q->where('District_id', $request->district_id);
                }
            });
        }

        $count = (clone $query)->count();

        if ($request->boolean('count_only')) {
            return response()->json(['success' => true, 'count' => $count]);
        }
        if (!$request->boolean('confirm')) {
            return response()->json(['success' => false, 'message' => 'ต้องยืนยันก่อนลบข้อมูล'], 422);
        }
        if ($count === 0) {
            return response()->json(['success' => false, 'message' => 'ไม่พบข้อมูลที่ตรงกับเงื่อนไขการกรอง'], 404);
        }

        $deleted = 0;
        try {
            $query->orderBy('id')->chunkById(200, function ($rows) use (&$deleted) {
                foreach ($rows as $row) {
                    $row->delete();
                    $deleted++;
                }
            });
        } catch (\Throwable $e) {
            \Log::error('Salt assessment delete filtered error: ' . $e->getMessage());
            return response()->json(['success' => false, 'message' => 'ลบไม่สำเร็จ (ลบไปแล้ว ' . number_format($deleted) . ' รายการ): ' . $e->getMessage()], 500);
        }

        return response()->json([
            'success' => true,
            'message' => 'ลบข้อมูลแบบประเมินลดการบริโภคเกลือเรียบร้อยแล้ว จำนวน ' . number_format($deleted) . ' รายการ',
            'deleted_count' => $deleted,
        ]);
    }

    public function destroySaltAssessment($id)
    {
        $assessment = SaltAssessment::findOrFail($id);
        $assessment->delete();

        return redirect()->route('admin.salt-assessment-list')->with('success', 'ลบข้อมูลเรียบร้อยแล้ว');
    }

    /**
     * Export Salt Assessment to Excel (View).
     */
    public function exportSaltAssessmentExcel($id)
    {
        $assessment = SaltAssessment::with(['user.province', 'user.district', 'user.subdistrictHospital', 'user.hospital'])->findOrFail($id);

        $assessment->problems = trim($assessment->problems);
        $assessment->suggestions = trim($assessment->suggestions);

        $fileName = 'แบบรายงาน_SDA0902_' . date('Ymd_His') . '.xlsx';
        return Excel::download(new \App\Exports\SaltAssessmentExport($assessment), $fileName);
    }

    /**
     * Export Salt Assessment to PDF (View).
     */
    public function exportSaltAssessmentPdf($id)
    {
        $assessment = SaltAssessment::with(['user.province', 'user.district', 'user.subdistrictHospital', 'user.hospital'])->findOrFail($id);

        $assessment->problems = trim($assessment->problems);
        $assessment->suggestions = trim($assessment->suggestions);

        return view('admin.salt-assessment-print', compact('assessment'));
    }

    /**
     * Export Salt Assessment to a native .docx - same content/layout as
     * exportSaltAssessmentPdf's print view, built directly with ZipArchive
     * (see AssessmentWordExportService), no external Word library needed.
     */
    public function exportSaltAssessmentWord($id)
    {
        $assessment = SaltAssessment::with(['user.province', 'user.district', 'user.subdistrictHospital', 'user.hospital'])->findOrFail($id);

        $assessment->problems = trim($assessment->problems);
        $assessment->suggestions = trim($assessment->suggestions);

        $reporter = AssessmentWordExportService::resolveReporter($assessment);
        $binary = AssessmentWordExportService::buildSaltDocx($assessment, $reporter);

        $unitName = AssessmentWordExportService::resolveUnitName($assessment->user);
        $fileName = 'แบบรายงาน_SDA0902_' . str_replace(' ', '_', $unitName) . '_' . date('Ymd_His') . '.docx';

        return response($binary, 200, [
            'Content-Type' => 'application/vnd.openxmlformats-officedocument.wordprocessingml.document',
            'Content-Disposition' => 'attachment; filename="report.docx"; filename*=UTF-8\'\'' . rawurlencode($fileName),
            'Content-Length' => strlen($binary),
        ]);
    }

    /**
     * "จัดการพื้นที่ดำเนินงาน" (Manage Operating Area) - level 1 only, under
     * แบบประเมินพชอ.ไต. Admin cascades จังหวัด -> อำเภอ -> ตำบล ->
     * ชุมชน/หมู่บ้าน, places a map pin, and sees all saved pins as markers
     * plus a data table below. Phase 1 only - no further workflow on top
     * of a saved pin yet, per the user's own request.
     *
     * The "พื้นที่ดำเนินงานที่บันทึกแล้ว" table is paginated (15/page) and can be
     * narrowed by fiscal year and/or province - both reloaded via AJAX into
     * #oa-table-container (admin.operating-areas-table), same convention as
     * kidneyDHBList()/admin.kidney-dhb-table. $mapPins is deliberately a
     * separate, unfiltered/unpaginated query: the map above the table always
     * plots every saved pin, so narrowing the table to one province/year (or
     * flipping to page 2) never makes markers disappear from the map.
     *
     * $fiscalYears intentionally reuses the 'kidney' module key (whose own
     * display name is literally "รายการข้อมูล พชอ.ไต" - see
     * FiscalYear::MODULES) rather than a new module of its own, per the
     * user's own request: this feature lives under the same พชอ.ไต module
     * as admin.kidney-dhb-list, so both pages' fiscal-year dropdowns stay
     * identical and share the same admin-managed enable/hide overrides from
     * "จัดการปีงบประมาณ".
     */
    public function operatingAreasIndex(Request $request)
    {
        $user = auth()->user();
        if (!$user->isSuperAdmin()) {
            abort(403, 'Unauthorized action.');
        }

        $provinces = \App\Support\AdminCache::provinces();
        $provinceCoords = $this->operatingAreaVillageCentroids('province_id');
        $fiscalYears = FiscalYear::selectableYearsFor('kidney', \App\Support\AdminCache::distinct(\App\Models\KidneyAssessment::class, 'fiscal_year'));

        $mapPins = OperatingAreaPin::with(['province', 'district', 'tambon', 'village'])
            ->get();

        $query = OperatingAreaPin::with(['province', 'district', 'tambon', 'village', 'creator'])
            ->orderBy('created_at', 'desc');

        if ($request->filled('filter_fiscal_year')) {
            $query->where('fiscal_year', $request->filter_fiscal_year);
        }

        if ($request->filled('filter_province_id')) {
            $query->where('province_id', $request->filter_province_id);
        }

        // อำเภอ narrows within the chosen จังหวัด - all three filters combine
        // with AND, same as the map above (see oaApplyMapFilter in the view).
        if ($request->filled('filter_district_id')) {
            $query->where('district_id', $request->filter_district_id);
        }

        $pins = $query->paginate(15);
        $pins->appends($request->only(['filter_fiscal_year', 'filter_province_id', 'filter_district_id']));

        // เฉพาะ SuperAdmin: แสดงว่าหน่วยงานไหนเลือกพื้นที่ไว้ / มีรายงานแล้วหรือไม่
        // (ใช้ตัดสินว่าจะแสดงปุ่ม "ยกเลิกพื้นที่นี้" ได้หรือไม่) - ตัวแปรต้องส่งให้ view เสมอ
        $isSuperAdmin = $user->isSuperAdmin();
        $pinOwnership = $isSuperAdmin
            ? $this->operatingAreaPinOwnership($pins->getCollection())
            : ['selectors' => [], 'reported' => []];
        $pinSelectors = $pinOwnership['selectors'];
        $pinReported = $pinOwnership['reported'];

        if ($request->ajax()) {
            return view('admin.operating-areas-table', compact('pins', 'isSuperAdmin', 'pinSelectors', 'pinReported'));
        }

        return view('admin.operating-areas', compact('provinces', 'pins', 'mapPins', 'provinceCoords', 'fiscalYears', 'isSuperAdmin', 'pinSelectors', 'pinReported'));
    }

    /**
     * สำหรับหน้า "พื้นที่ดำเนินงาน" ของ SuperAdmin: ต่อหมุดแต่ละอัน บอกว่า
     *   selectors - [pin id => [ชื่อหน่วยงานที่เลือกไว้, ...]]  (kidney_operating_area_selections)
     *   reported  - [pin id => true] เมื่อมีรายงาน พชอ.ไต ของพื้นที่นี้ในปีงบประมาณเดียวกันแล้ว
     *               (จับคู่ด้วย operating_area_pin_id หรือชื่อพื้นที่ เหมือน reportedPinIdsByOwner)
     */
    private function operatingAreaPinOwnership($pinModels): array
    {
        $pinModels = collect($pinModels);
        $result = ['selectors' => [], 'reported' => []];
        if ($pinModels->isEmpty()) {
            return $result;
        }

        $pinIds = $pinModels->pluck('id')->map(fn($v) => (int) $v)->all();

        if (\App\Support\SchemaProbe::hasTable('kidney_operating_area_selections')) {
            $rows = DB::table('kidney_operating_area_selections')->whereIn('pin_id', $pinIds)->get(['pin_id', 'user_id']);
            $users = User::whereIn('id', $rows->pluck('user_id')->unique()->all())->get()->keyBy('id');
            foreach ($rows as $r) {
                $u = $users->get($r->user_id);
                if (!$u) {
                    continue;
                }
                $label = $this->operatingAreaSelectorLabel($u);
                $result['selectors'][(int) $r->pin_id][$label] = $label;
            }
            foreach ($result['selectors'] as $pid => $labels) {
                $result['selectors'][$pid] = array_values($labels);
            }
        }

        $nameOf = function ($pin) {
            return trim((string) ($pin->label
                ?: ($pin->village_name_snapshot
                    ?? optional($pin->village)->village_name
                    ?? optional($pin->tambon)->tambon_name
                    ?? optional($pin->district)->district_name
                    ?? optional($pin->province)->province_name)));
        };
        $names = $pinModels->map($nameOf)->filter()->unique()->values()->all();

        $reports = KidneyAssessment::where(function ($q) use ($pinIds, $names) {
                $q->whereIn('operating_area_pin_id', $pinIds);
                if (!empty($names)) {
                    $q->orWhereIn('operating_area', $names);
                }
                $q->orWhere('reporter_metadata', 'like', '%"' . \App\Support\KidneyAreaList::META_KEY . '"%');
            })
            ->get(['operating_area_pin_id', 'operating_area', 'fiscal_year', 'reporter_metadata']);

        foreach ($pinModels as $pin) {
            $name = $nameOf($pin);
            $hit = $reports->first(fn($r) => (int) $r->fiscal_year === (int) $pin->fiscal_year
                && ((int) $r->operating_area_pin_id === (int) $pin->id
                    || in_array((int) $pin->id, \App\Support\KidneyAreaList::extraPinIds($r), true)
                    || ($name !== '' && trim((string) $r->operating_area) === $name)));
            if ($hit) {
                $result['reported'][(int) $pin->id] = true;
            }
        }

        return $result;
    }

    /** ชื่อหน่วยงานของผู้ที่เลือกพื้นที่ (ใช้แสดงในตารางของ SuperAdmin) */
    private function operatingAreaSelectorLabel($u): string
    {
        $rank = (int) $u->User_rank_id;
        if ($rank === 2 || $rank === 3) {
            return $this->agencyLabelFor($u);
        }
        if ($rank === 4 || $rank === 5) {
            $prefix = $rank === 4 ? 'รพ.สต.' : 'รพ.';
            $name = trim((string) ($u->Con_name ?: $u->name));
            return $name !== '' && mb_strpos($name, $prefix) !== 0 ? $prefix . $name : ($name ?: $prefix);
        }
        return 'สคร.';
    }

    /**
     * Rough bounding box covering the 5 provinces this feature serves
     * (ศรีสะเกษ/อุบลราชธานี/ยโสธร/อำนาจเจริญ/มุกดาหาร), used only to filter
     * out the small number of known bad/placeholder coordinates in the
     * village reference data (see the village migration's own docblock -
     * e.g. a handful of villages carry Bangkok's coordinates as a
     * leftover default from the source data) before averaging, so they
     * don't drag a จังหวัด/อำเภอ/ตำบล's center point far outside the region.
     */
    private const OA_REGION_LAT_RANGE = [13.8, 17.5];
    private const OA_REGION_LNG_RANGE = [103.5, 105.8];

    /**
     * Rough center point for every จังหวัด/อำเภอ/ตำบล (grouped by
     * $groupColumn, optionally narrowed to one province/district), averaged
     * from this app's own village coordinates. Used only so the admin map
     * on admin.operating-areas.index can fly to roughly the right spot as
     * soon as a level is picked - never trusted as the actual pin location,
     * same spirit as the village table's own lat/lng.
     */
    private function operatingAreaVillageCentroids(string $groupColumn, $provinceId = null, $districtId = null)
    {
        // ค่ากึ่งกลางหมู่บ้านเป็นข้อมูลคงที่ แต่คำนวณด้วย AVG ทั้งตารางหมู่บ้านทุกครั้งที่เปิดหน้า - แคชไว้ 1 วัน
        return \Illuminate\Support\Facades\Cache::remember(
            'adm.village_centroids.' . md5($groupColumn . '|' . $provinceId . '|' . $districtId),
            86400,
            fn () => $this->buildOperatingAreaVillageCentroids($groupColumn, $provinceId, $districtId)
        );
    }

    private function buildOperatingAreaVillageCentroids(string $groupColumn, $provinceId = null, $districtId = null)
    {
        $query = Village::query()
            ->whereBetween('latitude', self::OA_REGION_LAT_RANGE)
            ->whereBetween('longitude', self::OA_REGION_LNG_RANGE);

        if ($provinceId) {
            $query->where('province_id', $provinceId);
        }
        if ($districtId) {
            $query->where('district_id', $districtId);
        }

        return $query->select(
                $groupColumn,
                DB::raw('AVG(latitude) as lat'),
                DB::raw('AVG(longitude) as lng')
            )
            ->groupBy($groupColumn)
            ->get()
            ->keyBy($groupColumn);
    }

    /**
     * Registered operating-area pins (จัดการพื้นที่ดำเนินงาน) available to
     * one user's "เลือกจากพื้นที่ที่มีอยู่" picker on the kidney-dhb form -
     * scoped to $forUser's own reporting area, narrower the lower the
     * rank sits. พชอ.ไต (this module) only recognizes สคร./สสจ./สสอ./
     * รพ.สต. as its own rank ladder - รพ. (rank 5) is deliberately left
     * out of this scoping (unlike getAgencyUserIds()'s broader "Rank 3+:
     * district" grouping used elsewhere), per the user's own request:
     *   - Level 1 (สคร., rank 1): no scope at all - สคร. เขต 10 oversees
     *     every province, so every pin is offered.
     *   - สสจ. (rank 2): own province only.
     *   - สสอ. (rank 3): own อำเภอ.
     *   - รพ.สต. (rank 4): own ตำบล - the narrowest scope, since a
     *     รพ.สต. only ever reports against its own ตำบล.
     *   - รพ. (rank 5) and any other rank: no scope at all, same as
     *     Level 1 - not part of พชอ.ไต's own rank ladder.
     * Each level falls back to the next-broadest scope it actually has
     * data for (e.g. a รพ.สต. account with no Tambon_id set falls back
     * to its District_id) rather than silently showing zero pins.
     *
     * Each entry carries its full hierarchy + coordinates so picking one
     * can restore the map/cascade and fill operating_area's companion
     * columns without a second round trip.
     */
    /** แคชในคำขอเดียว: รายการหมุดทั้งหมดของผู้ใช้ (ไม่รวมกรณี onlySelected) - ฟังก์ชันนี้ถูกเรียกหลายรอบต่อหน้า */
    private array $pinListMemo = [];

    private function operatingAreaPinsForUser($forUser, bool $onlySelected = false)
    {
        if ($onlySelected) {
            return $this->buildOperatingAreaPinsForUser($forUser, true);
        }
        $key = (string) $forUser->id;
        if (!isset($this->pinListMemo[$key])) {
            $this->pinListMemo[$key] = $this->buildOperatingAreaPinsForUser($forUser, false);
        }
        return $this->pinListMemo[$key];
    }

    private function buildOperatingAreaPinsForUser($forUser, bool $onlySelected = false)
    {
        // "หมุดแสดงผล" (SuperAdmin เพิ่มไว้แสดงอย่างเดียว) ขึ้นในรายการด้วย แต่เป็น view_only (เลือก/รายงานต่อไม่ได้)
        $query = OperatingAreaPin::with(['province', 'district', 'tambon', 'village'])
            ->orderBy('created_at', 'desc');

        // The assessment form only offers the areas this agency picked on
        // "เลือกพื้นที่ดำเนินงาน" (plus ones it has already reported on, so an
        // existing record can still be reopened) - see selectedPinIdsForAgency().
        if ($onlySelected) {
            $ids = $this->selectedPinIdsForAgency($forUser);
            if ($ids !== null) {
                $query->where(function ($q) use ($ids) {
                    $q->whereIn('id', $ids);
                    if (OperatingAreaPin::hasDisplayColumns()) {
                        $q->orWhere('display_only', true);
                    }
                });
            }
        }

        // Narrowest column this account is scoped to (null = no scoping).
        $scope = null;
        if ($forUser->User_rank_id == 2) {
            if (!empty($forUser->Province_id)) {
                $scope = ['province_id', $forUser->Province_id];
            }
        } elseif ($forUser->User_rank_id == 3) {
            if (!empty($forUser->District_id)) {
                $scope = ['district_id', $forUser->District_id];
            } elseif (!empty($forUser->Province_id)) {
                $scope = ['province_id', $forUser->Province_id];
            }
        } elseif ($forUser->User_rank_id == 4) {
            if (!empty($forUser->Tambon_id)) {
                $scope = ['tambon_id', $forUser->Tambon_id];
            } elseif (!empty($forUser->District_id)) {
                $scope = ['district_id', $forUser->District_id];
            } elseif (!empty($forUser->Province_id)) {
                $scope = ['province_id', $forUser->Province_id];
            }
        }
        if ($scope) {
            // Pins this account saved itself on "เลือกพื้นที่ดำเนินงาน" always
            // stay visible to it, even when pinned outside its own scope.
            $query->where(function ($q) use ($scope, $forUser) {
                $q->where($scope[0], $scope[1])->orWhere('created_by', $forUser->id);
            });
        }
        // User_rank_id == 1 (สคร.): no filter at all - every pin everywhere.

        $pinList = $query->get()->map(function ($pin) {
            $placeName = $pin->village_name_snapshot
                ?? optional($pin->village)->village_name
                ?? optional($pin->tambon)->tambon_name
                ?? optional($pin->district)->district_name
                ?? optional($pin->province)->province_name;

            $breadcrumb = collect([
                optional($pin->province)->province_name,
                optional($pin->district)->district_name,
                optional($pin->tambon)->tambon_name,
                $pin->village_name_snapshot ?? optional($pin->village)->village_name,
            ])->filter()->implode(' / ');

            return [
                'id' => $pin->id,
                'fiscal_year' => $pin->fiscal_year,
                'label' => $pin->label,
                'display_name' => $pin->label ?: $placeName,
                // พื้นที่ (ชื่อหมู่บ้าน/ตำบล/อำเภอ) + ประเด็น (หมายเหตุ/ชื่อพื้นที่ที่กรอกตอนปักหมุด) แยกกัน ใช้แสดง 2 ข้อมูล
                'place_name' => $placeName,
                'issue' => $this->pinIssueText($pin->label, $placeName),
                'breadcrumb' => $breadcrumb,
                // Plain name alongside province_id - lets the map overview
                // color pins by province (see oaProvinceColors in the
                // blade's <script>) without having to parse it back out
                // of $breadcrumb.
                'province_name' => optional($pin->province)->province_name,
                'province_id' => $pin->province_id,
                'district_id' => $pin->district_id,
                'tambon_id' => $pin->tambon_id,
                'village_id' => $pin->village_id,
                'village_name_snapshot' => $pin->village_name_snapshot,
                'latitude' => (float) $pin->latitude,
                'longitude' => (float) $pin->longitude,
                // หมุดแสดงผล (SuperAdmin เพิ่มไว้): แสดงชื่อหน่วยงานที่ดำเนินงาน - เลือก/รายงานต่อไม่ได้
                'view_only' => (bool) ($pin->display_only ?? false),
                'agency_label' => $pin->agency_label ?? null,
                'created_ts' => $pin->created_at ? $pin->created_at->getTimestamp() : null,
                'created_by' => $pin->created_by,
                'no_cluster' => OperatingAreaPin::hasNoClusterColumn() ? (bool) ($pin->no_cluster ?? false) : false,
            ];
        })->values();

        // พื้นที่ที่ปักพร้อมกัน (บันทึกครั้งเดียว) ถือเป็นชุดเดียวกัน - ใช้รวมเป็นแบบประเมินเดียว
        $groups = $this->pinClusterIds($pinList);

        return $pinList->map(function ($p) use ($groups) {
            $p['group'] = $groups[$p['id']] ?? null;
            return $p;
        })->values();
    }

    /**
     * จัดชุดพื้นที่ที่ปักพร้อมกัน: หมุดของผู้ปักคนเดียวกัน ปีงบประมาณเดียวกัน ที่บันทึกห่างกันไม่เกิน 5 วินาที
     * (การบันทึกหลายพื้นที่ครั้งเดียวจากหน้า "เลือกพื้นที่ดำเนินงาน") = ชุดเดียวกัน
     *
     * @return array<int,string> pin id => รหัสชุด (เฉพาะชุดที่มี 2 พื้นที่ขึ้นไป)
     */
    private function pinClusterIds($pins): array
    {
        $out = [];
        $members = [];
        $last = [];
        $sorted = collect($pins)
            ->filter(fn($p) => empty($p['view_only']) && !empty($p['created_ts']) && empty($p['no_cluster'] ?? false))
            ->sortBy('created_ts');
        foreach ($sorted as $p) {
            $k = ($p['created_by'] ?? 0) . '|' . $p['fiscal_year'];
            if (isset($last[$k]) && ($p['created_ts'] - $last[$k]['ts']) <= 5) {
                $cid = $last[$k]['cid'];
            } else {
                $cid = $k . '|' . $p['created_ts'];
            }
            $last[$k] = ['ts' => $p['created_ts'], 'cid' => $cid];
            $members[$cid][] = (int) $p['id'];
            $out[(int) $p['id']] = $cid;
        }
        foreach ($out as $pid => $cid) {
            if (count($members[$cid]) < 2) {
                unset($out[$pid]);
            }
        }

        return $out;
    }

    /** รวมแถว "ยังไม่มีรายงาน" ของพื้นที่ที่ปักพร้อมกันให้เป็นแถวเดียว (areas = รายการพื้นที่ในแถว) */
    private function mergePendingByGroup($rows, bool $byStatus)
    {
        $out = collect();
        $seen = [];
        foreach (collect($rows) as $r) {
            $brief = [
                'id' => $r['id'],
                'place_name' => $r['place_name'] ?? $r['display_name'],
                'issue' => $r['issue'] ?? null,
                'breadcrumb' => $r['breadcrumb'] ?? null,
            ];
            $g = $r['group'] ?? null;
            if (!$g) {
                $r['areas'] = [$brief];
                $r['group_ids'] = [$r['id']];
                $out->push($r);
                continue;
            }
            $key = $g . '|' . ($byStatus ? (($r['status'] ?? '') . '|' . implode(',', $r['agencies'] ?? [])) : '');
            if (isset($seen[$key])) {
                $i = $seen[$key];
                $row = $out[$i];
                $row['areas'][] = $brief;
                $row['group_ids'][] = $r['id'];
                $out[$i] = $row;
                continue;
            }
            $r['areas'] = [$brief];
            $r['group_ids'] = [$r['id']];
            $seen[$key] = $out->count();
            $out->push($r);
        }

        return $out->values();
    }

    /**
     * True when this (rank 2-4) account has no operating area to assess yet:
     * nothing chosen on "เลือกพื้นที่ดำเนินงาน" and nothing reported before.
     * False when the selections table is missing (nothing to gate on).
     */
    private function needsAreaSelection($user): bool
    {
        if (!$user || !in_array($user->User_rank_id, [2, 3, 4])) {
            return false;
        }

        $ids = $this->selectedPinIdsForAgency($user);

        return $ids !== null && count($ids) === 0;
    }

    /**
     * Pin ids this agency has chosen via "เลือกพื้นที่ดำเนินงาน" (the
     * kidney_operating_area_selections table). Null when that table does not
     * exist yet (migration not run) - callers then skip the filtering.
     */
    private function chosenPinIdsForAgency($forUser): ?array
    {
        if (!\App\Support\SchemaProbe::hasTable('kidney_operating_area_selections')) {
            return null;
        }

        return DB::table('kidney_operating_area_selections')
            ->whereIn('user_id', $this->getAgencyUserIds($forUser))
            ->pluck('pin_id')
            ->map(fn($v) => (int) $v)
            ->unique()
            ->values()
            ->toArray();
    }

    /**
     * Chosen pins + the pins this agency has already filed reports under.
     */
    private function selectedPinIdsForAgency($forUser): ?array
    {
        $chosen = $this->chosenPinIdsForAgency($forUser);
        if ($chosen === null) {
            return null;
        }

        $reported = KidneyAssessment::whereIn('user_id', $this->getAgencyUserIds($forUser))
            ->whereNotNull('operating_area_pin_id')
            ->pluck('operating_area_pin_id')
            ->map(fn($v) => (int) $v)
            ->toArray();

        return array_values(array_unique(array_merge($chosen, $reported)));
    }

    /**
     * Which (fiscal_year, operating_area) pairs this agency has already
     * saved - quarter deliberately ignored: an area that has a record for a
     * fiscal year counts as taken for that whole year. The assessment form
     * uses it to hide such areas from its "พื้นที่ดำเนินงาน" pickers for the
     * fiscal year currently selected (see applyRecordedAreaFilter() in
     * kidney-dhb-content.blade.php). Scoped to the same agency users as
     * usedOperatingAreasForAgency().
     */
    private function recordedAreasForAgency($forUser)
    {
        $agencyUserIds = $this->getAgencyUserIds($forUser);

        $rows = KidneyAssessment::whereIn('user_id', $agencyUserIds)
            ->whereNotNull('operating_area')
            ->where('operating_area', '!=', '')
            ->get(['fiscal_year', 'quarter', 'operating_area', 'reporter_metadata']);

        $list = $rows->map(fn($r) => [
                'fiscal_year' => (int) $r->fiscal_year,
                'quarter' => (int) $r->quarter,
                'area' => trim($r->operating_area),
            ]);

        // พื้นที่ที่รวมอยู่ในรายงานของพื้นที่หลักอื่น: ถือว่ามีข้อมูลแล้วเช่นกัน
        // (extra = true -> ใช้แค่ติดป้าย "มีข้อมูลแล้ว" ไม่ใช้กระโดดไปยังรายงาน)
        $extraIds = $rows->flatMap(fn($r) => \App\Support\KidneyAreaList::extraPinIds($r))->unique()->values();
        if ($extraIds->isNotEmpty()) {
            $extraPins = OperatingAreaPin::with(['province', 'district', 'tambon', 'village'])->whereIn('id', $extraIds)->get()->keyBy('id');
            foreach ($rows as $r) {
                foreach (\App\Support\KidneyAreaList::extraPinIds($r) as $pid) {
                    $pin = $extraPins->get($pid);
                    if (!$pin) {
                        continue;
                    }
                    $name = trim((string) ($pin->label ?: \App\Support\KidneyAreaList::pinPlace($pin)));
                    if ($name === '') {
                        continue;
                    }
                    $list->push([
                        'fiscal_year' => (int) $r->fiscal_year,
                        'quarter' => (int) $r->quarter,
                        'area' => $name,
                        'extra' => true,
                    ]);
                }
            }
        }

        return $list
            ->unique(fn($r) => $r['fiscal_year'] . '|' . $r['quarter'] . '|' . $r['area'] . '|' . (!empty($r['extra']) ? 'x' : ''))
            ->values();
    }

    /**
     * Previously-used, free-text operating areas for $forUser's agency -
     * i.e. names already saved into kidney_assessments.operating_area by
     * some report filed by this office or any other office in the same
     * agency (see getAgencyUserIds()), that were typed into "ปักหน่วยงานพื้นที่
     * ใหม่" and never formally registered as an OperatingAreaPin via the
     * separate "จัดการพื้นที่ดำเนินงาน" admin page. Without this, an agency's
     * own already-saved reports are invisible in the oaTopAreaSelect
     * quick-jump dropdown even though the data is really there - this is
     * exactly the "มีข้อมูลแต่ทำไมไม่ขึ้นแสดงพื้นที่ เพื่อเลือกดูได้ครับ" report.
     *
     * Mirrors operatingAreaPinsForUser()'s return shape (display_name/
     * breadcrumb/province_id/.../latitude/longitude) so the same JS can
     * treat both lists uniformly, except 'id' here is the operating_area
     * NAME itself (there is no numeric pin id - this area never became a
     * registered pin), and 'is_registered_pin' => false marks the origin
     * so the picker knows to restore it via the free-text "new area" path
     * rather than applyRegisteredPinSelection()'s registered-pin path.
     */
    private function usedOperatingAreasForAgency($forUser)
    {
        $agencyUserIds = $this->getAgencyUserIds($forUser);

        // One row per distinct operating_area name - keep only the most
        // recently updated report's own geo snapshot for that name, since
        // an area may have been re-saved with a refined pin over time.
        $rows = KidneyAssessment::whereIn('user_id', $agencyUserIds)
            ->whereNotNull('operating_area')
            ->where('operating_area', '!=', '')
            ->orderBy('updated_at', 'desc')
            ->get([
                'operating_area',
                'operating_area_province_id',
                'operating_area_district_id',
                'operating_area_tambon_id',
                'operating_area_village_id',
                'operating_area_village_name_snapshot',
                'operating_area_latitude',
                'operating_area_longitude',
            ])
            ->unique('operating_area')
            ->values();

        $provinces = Province::whereIn('province_id', $rows->pluck('operating_area_province_id')->filter()->unique())
            ->pluck('province_name', 'province_id');
        $districts = District::whereIn('district_id', $rows->pluck('operating_area_district_id')->filter()->unique())
            ->pluck('district_name', 'district_id');
        $tambons = Tambon::whereIn('tambon_id', $rows->pluck('operating_area_tambon_id')->filter()->unique())
            ->pluck('tambon_name', 'tambon_id');
        $villages = Village::whereIn('village_id', $rows->pluck('operating_area_village_id')->filter()->unique())
            ->pluck('village_name', 'village_id');

        return $rows->map(function ($row) use ($provinces, $districts, $tambons, $villages) {
            $breadcrumb = collect([
                $provinces[$row->operating_area_province_id] ?? null,
                $districts[$row->operating_area_district_id] ?? null,
                $tambons[$row->operating_area_tambon_id] ?? null,
                $row->operating_area_village_name_snapshot ?? ($villages[$row->operating_area_village_id] ?? null),
            ])->filter()->implode(' / ');

            return [
                'id' => $row->operating_area,
                'display_name' => $row->operating_area,
                'breadcrumb' => $breadcrumb,
                // Same addition as operatingAreaPinsForUser()'s own
                // 'province_name' - lets the map overview color pins by
                // province (see oaProvinceColors in the blade's <script>).
                'province_name' => $provinces[$row->operating_area_province_id] ?? null,
                'province_id' => $row->operating_area_province_id,
                'district_id' => $row->operating_area_district_id,
                'tambon_id' => $row->operating_area_tambon_id,
                'village_id' => $row->operating_area_village_id,
                'village_name_snapshot' => $row->operating_area_village_name_snapshot,
                'latitude' => $row->operating_area_latitude !== null ? (float) $row->operating_area_latitude : null,
                'longitude' => $row->operating_area_longitude !== null ? (float) $row->operating_area_longitude : null,
                'is_registered_pin' => false,
            ];
        })->values();
    }

    /**
     * $forUser's own จังหวัด/อำเภอ/ตำบล (whichever of these are actually set
     * on the account - a province/district-level office account usually
     * has no Tambon_id), used to pre-fill the "ปักหน่วยงานพื้นที่ใหม่" cascade on
     * the kidney-dhb form so a reporter doesn't have to re-select their own
     * office's location from scratch every time they add a brand new area.
     * Mirrors operatingAreaPinsForUser()'s $forUser convention (the viewing
     * user on first paint, $scopeUser on every AJAX refresh) so switching
     * the Word-import target agency re-points this at THAT office's
     * location too.
     */
    private function operatingAreaUserLocation($forUser)
    {
        return [
            'province_id' => $forUser->Province_id ?: null,
            'district_id' => $forUser->District_id ?: null,
            'tambon_id' => $forUser->Tambon_id ?: null,
        ];
    }

    /**
     * AJAX: villages for a given จังหวัด/อำเภอ/ตำบล, for the cascading
     * select on admin.operating-areas.index. Mirrors
     * AuthController::getSubdistricts()'s shape/conventions.
     */
    public function getVillages($province_id, $district_id, $tambon_id)
    {
        $villages = Village::where('province_id', $province_id)
            ->where('district_id', $district_id)
            ->where('tambon_id', $tambon_id)
            ->orderBy('village_name', 'asc')
            ->get(['village_id', 'village_name', 'latitude', 'longitude']);

        return response()->json($villages);
    }

    /**
     * AJAX: districts for a given จังหวัด, each carrying a rough center
     * point (see operatingAreaVillageCentroids()) so the admin map can fly
     * there as soon as a district is picked - some operating areas are
     * only this granular (no ตำบล/หมู่บ้าน chosen).
     */
    public function getDistrictsForOperatingArea($province_id)
    {
        $districts = District::where('province_id', $province_id)
            ->orderBy('district_name', 'asc')
            ->get(['district_id', 'district_name']);

        $coords = $this->operatingAreaVillageCentroids('district_id', $province_id);

        $result = $districts->map(function ($d) use ($coords) {
            return [
                'district_id' => $d->district_id,
                'district_name' => $d->district_name,
                'lat' => optional($coords->get($d->district_id))->lat,
                'lng' => optional($coords->get($d->district_id))->lng,
            ];
        });

        return response()->json($result);
    }

    /**
     * AJAX: tambons for a given จังหวัด/อำเภอ, each carrying a rough center
     * point - see getDistrictsForOperatingArea().
     */
    public function getTambonsForOperatingArea($province_id, $district_id)
    {
        $tambons = Tambon::where('province_id', $province_id)
            ->where('district_id', $district_id)
            ->orderBy('tambon_name', 'asc')
            ->get(['tambon_id', 'tambon_name']);

        $coords = $this->operatingAreaVillageCentroids('tambon_id', $province_id, $district_id);

        $result = $tambons->map(function ($t) use ($coords) {
            return [
                'tambon_id' => $t->tambon_id,
                'tambon_name' => $t->tambon_name,
                'lat' => optional($coords->get($t->tambon_id))->lat,
                'lng' => optional($coords->get($t->tambon_id))->lng,
            ];
        });

        return response()->json($result);
    }

    /**
     * Save a pinned operating area (Phase 1: create only). tambon_id is
     * nullable - the operating area may only be pinned down to อำเภอ level,
     * per the user's own request (not every area goes all the way to
     * ตำบล/หมู่บ้าน).
     */
    public function storeOperatingAreaPin(Request $request)
    {
        $user = auth()->user();
        if ($user->User_rank_id != 1) {
            abort(403, 'Unauthorized action.');
        }

        $rules = [
            'fiscal_year' => 'required|integer',
            'province_id' => 'required|integer|exists:province,province_id',
            'district_id' => 'required|integer|exists:district,district_id',
            'tambon_id' => 'nullable|integer|exists:tambon,tambon_id',
            'village_id' => 'nullable|integer|exists:village,village_id',
            'village_name_snapshot' => 'nullable|string|max:255',
            'latitude' => 'required|numeric|between:-90,90',
            'longitude' => 'required|numeric|between:-180,180',
            'label' => 'nullable|string|max:255',
            'display_only' => 'nullable|boolean',
            'agency_label' => 'nullable|string|max:255',
        ];

        // ปักได้หลายพื้นที่ในครั้งเดียว: ฟอร์มส่งทุกพื้นที่มาใน areas_json (เพิ่มเข้ารายการแล้วบันทึกครั้งเดียว)
        // ไม่มี areas_json = บันทึกพื้นที่เดียวจากช่องในฟอร์มเหมือนเดิม
        $isMulti = $request->filled('areas_json');
        $rawItems = [$request->all()];
        if ($isMulti) {
            $decoded = json_decode((string) $request->input('areas_json'), true);
            if (!is_array($decoded) || empty($decoded) || count($decoded) > 30) {
                return back()->withInput()->with('error', 'รายการพื้นที่ไม่ถูกต้อง (เลือกได้ครั้งละไม่เกิน 30 พื้นที่)');
            }
            $rawItems = $decoded;
        }

        $validatedItems = [];
        foreach ($rawItems as $idx => $raw) {
            $v = $isMulti
                ? \Illuminate\Support\Facades\Validator::make((array) $raw, $rules)
                : null;
            $validated = $isMulti ? ($v->fails() ? null : $v->validated()) : $request->validate($rules);
            if ($validated === null) {
                return back()->withInput()->with('error', 'พื้นที่ลำดับที่ ' . ($idx + 1) . ' ข้อมูลไม่ครบถ้วนหรือไม่ถูกต้อง: ' . $v->errors()->first());
            }
            $validated['created_by'] = $user->id;

            // "หมุดแสดงผล": เฉพาะ SuperAdmin - ต้องระบุชื่อหน่วยงานที่ดำเนินงาน (พิมพ์เอง) และไม่ผูกกับบัญชีใด
            $displayOnly = !empty($validated['display_only']) && $user->isSuperAdmin();
            if ($displayOnly) {
                $agency = trim((string) ($validated['agency_label'] ?? ''));
                if ($agency === '') {
                    return back()->withInput()->withErrors(['agency_label' => 'กรุณาระบุชื่อหน่วยงานที่ดำเนินงาน' . ($isMulti ? ' (พื้นที่ลำดับที่ ' . ($idx + 1) . ')' : '')]);
                }
                if (!OperatingAreaPin::hasDisplayColumns()) {
                    return back()->withInput()->with('error', 'ยังไม่ได้อัปเดตฐานข้อมูล กรุณารันคำสั่ง php artisan migrate ก่อน');
                }
                $validated['display_only'] = true;
                $validated['agency_label'] = $agency;
            } else {
                unset($validated['display_only'], $validated['agency_label']);
            }

            $validated['tambon_id'] = $validated['tambon_id'] ?? null;
            $validated['village_id'] = $validated['village_id'] ?? null;

            // ซ้ำกันเองในรายการที่ส่งมา - ข้ามตัวที่ซ้ำ
            $key = implode('|', [$validated['fiscal_year'], $validated['province_id'], $validated['district_id'],
                $validated['tambon_id'], $validated['village_id'], (string) ($validated['label'] ?? ''), $displayOnly ? '1' : '0',
                (string) ($validated['agency_label'] ?? '')]);
            if (isset($validatedItems[$key])) {
                continue;
            }
            $validatedItems[$key] = $validated;
        }

        $items = array_values($validatedItems);
        DB::transaction(function () use ($items) {
            foreach ($items as $item) {
                OperatingAreaPin::create($item);
                $this->pinListMemo = [];
            }
        });

        $count = count($items);
        $displayCount = collect($items)->filter(fn($it) => !empty($it['display_only']))->count();
        if ($count === 1) {
            $message = $displayCount
                ? 'บันทึกหมุดแสดงผล (หน่วยงาน ' . $items[0]['agency_label'] . ') เรียบร้อยแล้ว'
                : 'บันทึกพื้นที่ดำเนินงานเรียบร้อยแล้ว';
        } else {
            $message = 'บันทึกพื้นที่ดำเนินงานจำนวน ' . $count . ' พื้นที่เรียบร้อยแล้ว'
                . ($displayCount ? ' (เป็นหมุดแสดงผล ' . $displayCount . ' พื้นที่)' : '');
        }

        return redirect()->route('admin.operating-areas.index')->with('success', $message);
    }

    /**
     * SuperAdmin: แก้ไขหมุดพื้นที่ดำเนินงานที่บันทึกแล้ว (ปีงบประมาณ / ชื่อพื้นที่ / พิกัด
     * และชื่อหน่วยงานของ "หมุดแสดงผล"). ที่ตั้ง จังหวัด-อำเภอ-ตำบล-หมู่บ้าน ไม่เปลี่ยน
     * เพราะรายงานและการเลือกพื้นที่ของหน่วยงานอ้างอิงหมุดนี้อยู่ (ถ้าผิดที่ให้ลบแล้วปักใหม่)
     */
    public function updateOperatingAreaPin(Request $request, $id)
    {
        $user = auth()->user();
        abort_unless($user && $user->isSuperAdmin(), 404);

        $pin = OperatingAreaPin::findOrFail($id);

        $validated = $request->validate([
            'fiscal_year' => 'required|integer|between:2500,2700',
            'label' => 'nullable|string|max:255',
            'latitude' => 'required|numeric|between:-90,90',
            'longitude' => 'required|numeric|between:-180,180',
            'agency_label' => 'nullable|string|max:255',
        ], [
            'fiscal_year.required' => 'กรุณาเลือกปีงบประมาณ',
            'latitude.required' => 'กรุณาระบุละติจูด',
            'longitude.required' => 'กรุณาระบุลองจิจูด',
            'latitude.between' => 'ละติจูดต้องอยู่ระหว่าง -90 ถึง 90',
            'longitude.between' => 'ลองจิจูดต้องอยู่ระหว่าง -180 ถึง 180',
        ]);

        $label = trim((string) ($validated['label'] ?? ''));
        $label = $label !== '' ? $label : null;

        $data = [
            'fiscal_year' => (int) $validated['fiscal_year'],
            'label' => $label,
            'latitude' => $validated['latitude'],
            'longitude' => $validated['longitude'],
        ];

        $isDisplay = OperatingAreaPin::hasDisplayColumns() && !empty($pin->display_only);
        if ($isDisplay) {
            $agency = trim((string) ($validated['agency_label'] ?? ''));
            if ($agency === '') {
                return response()->json(['success' => false, 'message' => 'กรุณาระบุชื่อหน่วยงานที่ดำเนินงาน'], 422);
            }
            $data['agency_label'] = $agency;
        }

        $dup = OperatingAreaPin::where('id', '!=', $pin->id)
            ->where('fiscal_year', $data['fiscal_year'])
            ->where('province_id', $pin->province_id)
            ->where('district_id', $pin->district_id)
            ->where('tambon_id', $pin->tambon_id)
            ->where('village_id', $pin->village_id)
            ->where('label', $label)
            ->exists();
        if ($dup) {
            return response()->json(['success' => false, 'message' => 'มีพื้นที่เดียวกันนี้ในปีงบประมาณที่เลือกอยู่แล้ว'], 422);
        }

        $pin->update($data);

        return response()->json(['success' => true, 'message' => 'แก้ไขพื้นที่ดำเนินงานเรียบร้อยแล้ว']);
    }

    /**
     * "เลือกพื้นที่ดำเนินงาน" - a page that only shows the area picker (map +
     * จังหวัด/อำเภอ/ตำบล/หมู่บ้าน cascade) so an agency can pin its operating
     * areas ahead of time. Every rank can use it; the pins it saves are the
     * same kidney_operating_area_pins rows the assessment form's
     * "พื้นที่ดำเนินงาน" dropdown lists (see operatingAreaPinsForUser()).
     */
    public function kidneyDhbAreas(Request $request)
    {
        $user = auth()->user();

        // จังหวัดล็อกตามจังหวัดของผู้ใช้งาน (ผู้ใช้ที่ไม่มี Province_id เลือกได้ทุกจังหวัด)
        $lockedProvinceId = $user->Province_id ? (int) $user->Province_id : null;
        $provinces = \App\Support\AdminCache::provinces();
        if ($lockedProvinceId && $provinces->contains('province_id', $lockedProvinceId)) {
            $provinces = $provinces->where('province_id', $lockedProvinceId)->values();
        } else {
            $lockedProvinceId = null;
        }
        $provinceCoords = $this->operatingAreaVillageCentroids('province_id');
        $fiscalYears = FiscalYear::selectableYearsFor('kidney', \App\Support\AdminCache::distinct(\App\Models\KidneyAssessment::class, 'fiscal_year'));
        $pins = $this->operatingAreaPinsForUser($user);

        // Fiscal year starts 1 Oct (Buddhist era); fall back to the first
        // selectable year when the computed one isn't offered.
        $now = now();
        $currentFiscalYear = ($now->year + 543) + ($now->month >= 10 ? 1 : 0);
        $defaultFiscalYear = collect($fiscalYears)->contains($currentFiscalYear)
            ? $currentFiscalYear
            : collect($fiscalYears)->first();

        return view('admin.kidney-dhb-areas', [
            'provinces' => $provinces,
            'lockedProvinceId' => $lockedProvinceId,
            'provinceCoords' => $provinceCoords,
            'fiscalYears' => $fiscalYears,
            'defaultFiscalYear' => $defaultFiscalYear,
            'pins' => $pins,
            'chosenPinIds' => $this->ownPinIds($user),
            'lockedPins' => $this->lockedPinMap($user, $pins),
            'ownReportedPins' => $this->reportedPinIdsByOwner($user, $pins)['own'],
            'needAreaSelection' => $this->needsAreaSelection($user),
            'userOwnLocation' => $this->operatingAreaUserLocation($user),
            'agencyLabel' => $this->agencyLabelFor($user),
        ]);
    }

    /**
     * Ids of the given pins that already have a report in the pin's own
     * fiscal year, filed by ANY agency. A report counts when it points at the
     * pin (operating_area_pin_id) or - for older / free-text reports that never
     * stored the pin id - carries the pin's display name as its
     * operating_area (same matching as the assessment form's own-area
     * filter). Used to warn on "เลือกจากพื้นที่ที่มีอยู่" and to refuse the
     * selection (selectKidneyDhbArea()).
     */
    private function reportedPinIds($user, $pins): array
    {
        $by = $this->reportedPinIdsByOwner($user, $pins);

        return array_values(array_unique(array_merge($by['own'], $by['other'])));
    }

    /**
     * Same matching as reportedPinIds(), split by who filed the report:
     *   'own'   - at least one report for the pin (in its fiscal year) is from this agency itself;
     *   'other' - at least one report for the pin is from another agency.
     * A pin can be in both lists. Only 'other' makes the area view-only.
     *
     * @return array{own: int[], other: int[]}
     */
    private function reportedPinIdsByOwner($user, $pins): array
    {
        $pins = collect($pins);
        if ($pins->isEmpty()) {
            return ['own' => [], 'other' => []];
        }

        $ownUserIds = array_map('intval', $this->ownAgencyUserIds($user));

        $pinIds = $pins->pluck('id')->map(fn($v) => (int) $v)->all();
        $names = $pins->map(fn($p) => trim((string) $p['display_name']))->filter()->unique()->values()->all();

        $rows = KidneyAssessment::where(function ($q) use ($pinIds, $names) {
                $q->whereIn('operating_area_pin_id', $pinIds);
                if (!empty($names)) {
                    $q->orWhereIn('operating_area', $names);
                }
                // รายงานที่ครอบคลุมหลายพื้นที่ (พื้นที่อื่นเก็บใน reporter_metadata)
                $q->orWhere('reporter_metadata', 'like', '%"' . \App\Support\KidneyAreaList::META_KEY . '"%');
            })
            ->get(['operating_area_pin_id', 'operating_area', 'fiscal_year', 'user_id', 'reporter_metadata']);

        $own = [];
        $other = [];
        foreach ($pins as $p) {
            $name = trim((string) $p['display_name']);
            $matches = $rows->filter(fn($r) => (int) $r->fiscal_year === (int) $p['fiscal_year']
                && ((int) $r->operating_area_pin_id === (int) $p['id']
                    || in_array((int) $p['id'], \App\Support\KidneyAreaList::extraPinIds($r), true)
                    || ($name !== '' && trim((string) $r->operating_area) === $name)));

            foreach ($matches as $r) {
                if (in_array((int) $r->user_id, $ownUserIds, true)) {
                    $own[(int) $p['id']] = (int) $p['id'];
                } else {
                    $other[(int) $p['id']] = (int) $p['id'];
                }
            }
        }

        return ['own' => array_values($own), 'other' => array_values($other)];
    }

    /**
     * User ids that count as "this agency itself" on "เลือกพื้นที่ดำเนินงาน".
     * สสจ. (rank 2) is only its own province-level accounts - the สสอ./รพ.สต./รพ.
     * under it are other agencies there (their areas are view only for สสจ.).
     * Every other rank keeps the usual getAgencyUserIds() grouping.
     */
    private function ownAgencyUserIds($user): array
    {
        if ($user && $user->User_rank_id == 2) {
            return \App\Models\User::where('Province_id', $user->Province_id)
                ->where('User_rank_id', 2)
                ->pluck('id')
                ->toArray();
        }

        return $this->getAgencyUserIds($user);
    }

    /** Pin ids the signed-in agency itself has chosen (see ownAgencyUserIds()). */
    private function ownPinIds($user): array
    {
        if (!\App\Support\SchemaProbe::hasTable('kidney_operating_area_selections')) {
            return [];
        }

        return DB::table('kidney_operating_area_selections')
            ->whereIn('user_id', $this->ownAgencyUserIds($user))
            ->pluck('pin_id')
            ->map(fn($v) => (int) $v)
            ->unique()
            ->values()
            ->toArray();
    }

    /**
     * Pins this agency may look at but not select on "เลือกพื้นที่ดำเนินงาน",
     * as [pin id => reason]:
     *   'reported' - the area already has a report in its fiscal year (any agency);
     *   'other'    - another agency has chosen the area for itself.
     * A pin the signed-in agency itself has chosen is never locked (it can
     * still be un-chosen); every other pin with a report is locked.
     */
    private function lockedPinMap($user, $pins): array
    {
        $pins = collect($pins);
        if ($pins->isEmpty()) {
            return [];
        }

        $pinIds = $pins->pluck('id')->map(fn($v) => (int) $v)->all();
        $ownChosen = $this->ownPinIds($user);
        // เฉพาะรายงานของ "หน่วยงานอื่น" เท่านั้นที่ทำให้พื้นที่เป็นดูอย่างเดียว
        // รายงานของหน่วยงานตนเองไม่ล็อก (ยังรายงานต่อ/เลือกกลับมาได้)
        $reported = $this->reportedPinIdsByOwner($user, $pins)['other'];

        $chosenByOthers = [];
        if (\App\Support\SchemaProbe::hasTable('kidney_operating_area_selections')) {
            $chosenByOthers = DB::table('kidney_operating_area_selections')
                ->whereIn('pin_id', $pinIds)
                ->whereNotIn('user_id', $this->ownAgencyUserIds($user))
                ->pluck('pin_id')
                ->map(fn($v) => (int) $v)
                ->unique()
                ->all();
        }

        // หมุดแสดงผล: ดูอย่างเดียวสำหรับทุกคน (ไม่ผูกบัญชี จึงไม่มีใคร "เลือกไว้แล้ว")
        $displayOnly = OperatingAreaPin::hasDisplayColumns()
            ? OperatingAreaPin::whereIn('id', $pinIds)->where('display_only', true)->pluck('id')->map(fn($v) => (int) $v)->all()
            : [];

        $locked = [];
        foreach ($pinIds as $id) {
            if (in_array($id, $displayOnly, true)) {
                $locked[$id] = 'display';
                continue;
            }
            if (in_array($id, $ownChosen, true)) {
                continue;
            }
            if (in_array($id, $reported, true)) {
                $locked[$id] = 'reported';
            } elseif (in_array($id, $chosenByOthers, true)) {
                $locked[$id] = 'other';
            }
        }

        return $locked;
    }

    private function lockedPinMessage(string $reason, ?string $agency = null): string
    {
        if ($reason === 'display') {
            return 'พื้นที่นี้มีการดำเนินงาน พชอ.ไต โดย ' . ($agency ?: 'หน่วยงานอื่น') . ' แล้ว (หมุดแสดงผล) ดูข้อมูลได้อย่างเดียว เลือกไม่ได้';
        }

        return $reason === 'reported'
            ? 'พื้นที่นี้มีหน่วยงานอื่นรายงานผลในปีงบประมาณนี้แล้ว ดูข้อมูลได้อย่างเดียว เลือกไม่ได้'
            : 'พื้นที่นี้เป็นพื้นที่ดำเนินงานของหน่วยงานอื่น สามารถดูข้อมูลได้อย่างเดียว เลือกไม่ได้';
    }

    /**
     * Short name of the agency the signed-in account belongs to - the one the
     * chosen operating areas are tied to (see getAgencyUserIds()).
     */
    private function agencyLabelFor($user): string
    {
        if (!$user) {
            return '';
        }

        if ($user->User_rank_id == 2) {
            $name = optional(Province::where('province_id', $user->Province_id)->first())->province_name;
            return trim('สสจ.' . ($name ?? ''));
        }

        if ($user->User_rank_id >= 3) {
            $name = optional(District::where('district_id', $user->District_id)->first())->district_name;
            return trim('สสอ.' . ($name ?? ''));
        }

        return 'สคร.';
    }

    /**
     * Save a pin from "เลือกพื้นที่ดำเนินงาน" (any rank). Same fields/rules as
     * storeOperatingAreaPin() (level 1's own page), plus a duplicate guard so
     * the same place isn't saved twice for the same fiscal year.
     */
    public function storeKidneyDhbArea(Request $request)
    {
        $user = auth()->user();

        // หน่วยงานดำเนินงานได้หลายพื้นที่: ฟอร์มส่งหลายพื้นที่มาใน areas_json (เพิ่มเข้ารายการแล้วบันทึกครั้งเดียว)
        // - ไม่มี areas_json = บันทึกพื้นที่เดียวจากช่องในฟอร์มเหมือนเดิม
        $rules = [
            'fiscal_year' => 'required|integer',
            'province_id' => 'required|integer|exists:province,province_id',
            'district_id' => 'required|integer|exists:district,district_id',
            'tambon_id' => 'nullable|integer|exists:tambon,tambon_id',
            'village_id' => 'nullable|integer|exists:village,village_id',
            'village_name_snapshot' => 'nullable|string|max:255',
            'latitude' => 'required|numeric|between:-90,90',
            'longitude' => 'required|numeric|between:-180,180',
            'label' => 'nullable|string|max:255',
        ];

        $rawItems = [];
        if ($request->filled('areas_json')) {
            $decoded = json_decode((string) $request->input('areas_json'), true);
            if (!is_array($decoded) || empty($decoded) || count($decoded) > 30) {
                return redirect()->route('admin.kidney-dhb.areas')
                    ->with('error', 'รายการพื้นที่ที่ส่งมาไม่ถูกต้อง (บันทึกได้ครั้งละไม่เกิน 30 พื้นที่)');
            }
            $rawItems = array_values($decoded);
        } else {
            $rawItems = [$request->only(array_keys($rules))];
        }
        $isMulti = $request->filled('areas_json');

        $items = [];
        $seen = [];
        foreach ($rawItems as $idx => $raw) {
            $v = \Illuminate\Support\Facades\Validator::make((array) $raw, $rules);
            if ($v->fails()) {
                if (!$isMulti) {
                    return redirect()->route('admin.kidney-dhb.areas')->withInput()->withErrors($v);
                }
                return redirect()->route('admin.kidney-dhb.areas')
                    ->with('error', 'พื้นที่ลำดับที่ ' . ($idx + 1) . ' ข้อมูลไม่ครบถ้วน: ' . $v->errors()->first());
            }
            $validated = $v->validated();

            if ($user->Province_id && (int) $validated['province_id'] !== (int) $user->Province_id) {
                return redirect()->route('admin.kidney-dhb.areas')
                    ->withInput()
                    ->with('error', 'ปักพื้นที่ได้เฉพาะในจังหวัดของผู้ใช้งานเท่านั้น');
            }

            $label = isset($validated['label']) ? trim((string) $validated['label']) : '';
            $validated['label'] = $label !== '' ? $label : null;
            $validated['tambon_id'] = $validated['tambon_id'] ?? null;
            $validated['village_id'] = $validated['village_id'] ?? null;

            // ซ้ำกันเองในรายการที่ส่งมา - ข้ามตัวที่ซ้ำ
            $key = implode('|', [$validated['fiscal_year'], $validated['province_id'], $validated['district_id'],
                $validated['tambon_id'], $validated['village_id'], (string) $validated['label']]);
            if (isset($seen[$key])) {
                continue;
            }
            $seen[$key] = true;

            $exists = OperatingAreaPin::where('fiscal_year', $validated['fiscal_year'])
                ->where('province_id', $validated['province_id'])
                ->where('district_id', $validated['district_id'])
                ->where('tambon_id', $validated['tambon_id'])
                ->where('village_id', $validated['village_id'])
                ->where('label', $validated['label'])
                ->exists();

            if ($exists) {
                return redirect()->route('admin.kidney-dhb.areas')
                    ->withInput()
                    ->with('error', $isMulti
                        ? 'พื้นที่ลำดับที่ ' . ($idx + 1) . ' ถูกบันทึกไว้แล้วในปีงบประมาณที่เลือก (ไม่มีพื้นที่ใดถูกบันทึก)'
                        : 'พื้นที่นี้ถูกบันทึกไว้แล้วในปีงบประมาณที่เลือก');
            }

            $validated['created_by'] = $user->id;
            $validated['no_cluster'] = $isMulti && OperatingAreaPin::hasNoClusterColumn(); // ปักหลายพื้นที่พร้อมกัน → แยกแบบประเมิน
            $items[] = $validated;
        }

        $pinIds = [];
        DB::transaction(function () use ($items, $user, &$pinIds) {
            foreach ($items as $item) {
                $pin = OperatingAreaPin::create($item);
                $this->pinListMemo = [];
                // A pin you just saved is one you want to use - choose it right away.
                $this->choosePinForUser($user, $pin->id);
                $pinIds[] = $pin->id;
            }
        });

        $count = count($pinIds);

        return redirect()->route('admin.kidney-dhb-list')
            ->with('highlight_pin', $pinIds[0] ?? null)
            ->with('success', 'บันทึกพื้นที่ดำเนินงานให้หน่วยงาน ' . $this->agencyLabelFor($user)
                . ($count > 1 ? ' จำนวน ' . $count . ' พื้นที่' : '') . ' เรียบร้อยแล้ว'
                . ($count > 1 ? ' - เลือกพื้นที่หลักและพื้นที่อื่นรวมในรายงานเดียวกันได้ที่หน้ากรอกรายงาน' : ''));
    }

    private function choosePinForUser($user, int $pinId): void
    {
        if (!\App\Support\SchemaProbe::hasTable('kidney_operating_area_selections')) {
            return;
        }

        $exists = DB::table('kidney_operating_area_selections')
            ->whereIn('user_id', $this->getAgencyUserIds($user))
            ->where('pin_id', $pinId)
            ->exists();

        if (!$exists) {
            DB::table('kidney_operating_area_selections')->insert([
                'user_id' => $user->id,
                'pin_id' => $pinId,
                'created_at' => now(),
                'updated_at' => now(),
            ]);
        }
    }

    /**
     * "บันทึกพื้นที่นี้ให้หน่วยงาน" - choose a saved area for the signed-in
     * agency (tied to it through kidney_operating_area_selections) and stay
     * on the page, where it shows up in the saved list.
     */
    public function selectKidneyDhbArea(Request $request, $id)
    {
        $user = auth()->user();
        // เลือกจากพื้นที่ที่มีอยู่ได้เฉพาะ SuperAdmin - หน่วยงานอื่นต้องปักพื้นที่ใหม่
        if (!$user->isSuperAdmin()) {
            return redirect()->route('admin.kidney-dhb.areas')
                ->with('error', 'การเลือกจากพื้นที่ที่มีอยู่ทำได้เฉพาะ SuperAdmin กรุณาปักพื้นที่ใหม่');
        }
        $visible = $this->operatingAreaPinsForUser($user)->pluck('id')->map(fn($v) => (int) $v);
        if (!$visible->contains((int) $id)) {
            abort(403, 'Unauthorized action.');
        }

        // Reported on already, or owned by another agency: view only.
        $locked = $this->lockedPinMap($user, $this->operatingAreaPinsForUser($user)->where('id', (int) $id));
        if (isset($locked[(int) $id])) {
            return redirect()->route('admin.kidney-dhb.areas')
                ->with('error', $this->lockedPinMessage($locked[(int) $id], optional(OperatingAreaPin::find((int) $id))->agency_label));
        }

        $this->choosePinForUser($user, (int) $id);

        return redirect()->route('admin.kidney-dhb-list')
            ->with('highlight_pin', (int) $id)
            ->with('success', 'บันทึกพื้นที่ดำเนินงานให้หน่วยงาน ' . $this->agencyLabelFor($user) . ' เรียบร้อยแล้ว');
    }

    /**
     * SuperAdmin: เลือกพื้นที่ที่มีอยู่ได้หลายพื้นที่พร้อมกัน (ติ๊กหลายรายการแล้วบันทึกครั้งเดียว)
     * ตรวจสิทธิ์/สถานะล็อกทีละพื้นที่เหมือนการเลือกทีละหมุด พื้นที่ที่เลือกไม่ได้จะถูกข้ามและแจ้งผล
     */
    public function selectManyKidneyDhbAreas(Request $request)
    {
        $user = auth()->user();
        if (!$user->isSuperAdmin()) {
            return redirect()->route('admin.kidney-dhb.areas')
                ->with('error', 'การเลือกจากพื้นที่ที่มีอยู่ทำได้เฉพาะ SuperAdmin กรุณาปักพื้นที่ใหม่');
        }

        $request->validate([
            'pin_ids' => 'required|array|min:1|max:50',
            'pin_ids.*' => 'integer',
        ], [
            'pin_ids.required' => 'กรุณาติ๊กเลือกพื้นที่อย่างน้อย 1 พื้นที่',
            'pin_ids.min' => 'กรุณาติ๊กเลือกพื้นที่อย่างน้อย 1 พื้นที่',
            'pin_ids.max' => 'เลือกได้ครั้งละไม่เกิน 50 พื้นที่',
        ]);

        $ids = collect($request->input('pin_ids', []))->map(fn($v) => (int) $v)->filter()->unique()->values();
        $visiblePins = $this->operatingAreaPinsForUser($user);
        $visible = $visiblePins->pluck('id')->map(fn($v) => (int) $v);
        $locked = $this->lockedPinMap($user, $visiblePins->whereIn('id', $ids->all()));

        $chosen = [];
        $skipped = 0;
        $markNoCluster = count($ids) > 1; // ติ๊กหลายพื้นที่พร้อมกัน → แต่ละพื้นที่มีแบบประเมินแยก
        DB::transaction(function () use ($ids, $visible, $locked, $user, $markNoCluster, &$chosen, &$skipped) {
            foreach ($ids as $pinId) {
                if (!$visible->contains($pinId) || isset($locked[$pinId])) {
                    $skipped++;
                    continue;
                }
                $this->choosePinForUser($user, $pinId);
                $chosen[] = $pinId;
            }
            // mark no_cluster บน pins ที่เพิ่งเลือก (ไม่กระทบ pins ที่ no_cluster=true อยู่แล้ว)
            if ($markNoCluster && !empty($chosen) && OperatingAreaPin::hasNoClusterColumn()) {
                OperatingAreaPin::whereIn('id', $chosen)
                    ->where('no_cluster', false)
                    ->update(['no_cluster' => true]);
            }
        });

        if (empty($chosen)) {
            return redirect()->route('admin.kidney-dhb.areas')
                ->with('error', 'พื้นที่ที่ติ๊กเลือกไม่สามารถเลือกได้ (หน่วยงานอื่นดำเนินงานหรือรายงานแล้ว)');
        }

        return redirect()->route('admin.kidney-dhb-list')
            ->with('highlight_pin', $chosen[0])
            ->with('success', 'บันทึกพื้นที่ดำเนินงานให้หน่วยงาน ' . $this->agencyLabelFor($user)
                . ' จำนวน ' . count($chosen) . ' พื้นที่ เรียบร้อยแล้ว'
                . ($skipped ? ' (ข้าม ' . $skipped . ' พื้นที่ที่เลือกไม่ได้)' : ''));
    }

    /**
     * Take a saved area out of the assessment form's dropdown (the pin
     * itself stays saved). Areas already reported on stay listed.
     */
    public function deselectKidneyDhbArea(Request $request, $id)
    {
        $user = auth()->user();

        if (\App\Support\SchemaProbe::hasTable('kidney_operating_area_selections')) {
            // also = รหัสหมุดอื่นในชุดเดียวกัน (พื้นที่ที่ปักพร้อมกัน) คั่นด้วย comma - ยกเลิกพร้อมกัน
            $ids = collect(explode(',', (string) $request->input('also', '')))
                ->map(fn($v) => (int) $v)->filter()->push((int) $id)->unique()->values()->all();
            DB::table('kidney_operating_area_selections')
                ->whereIn('user_id', $this->getAgencyUserIds($user))
                ->whereIn('pin_id', $ids)
                ->delete();

            // หลังลบ selection แล้ว: pin ที่กลายเป็น orphan (ไม่มี selection เหลือ + ไม่มี report)
            // → ลบออกจาก kidney_operating_area_pins ด้วย เพื่อไม่ให้แสดงแถว 'ยังไม่มีผู้เลือก' ค้างอยู่
            foreach ($ids as $pinId) {
                $pin = \App\Models\OperatingAreaPin::find($pinId);
                if (!$pin || !empty($pin->display_only)) {
                    continue; // ไม่ลบหมุดแสดงผล
                }
                if (DB::table('kidney_operating_area_selections')->where('pin_id', $pinId)->exists()) {
                    continue; // ยังมีหน่วยงานอื่นเลือกอยู่ → ไม่ลบ
                }
                if (DB::table('kidney_assessments')->where('operating_area_pin_id', $pinId)->exists()) {
                    continue; // มีรายงานอยู่แล้ว → ไม่ลบ
                }
                $pin->delete();
            }
            $this->pinListMemo = [];
        }

        if ($request->input('back') === 'list') {
            return redirect()->route('admin.kidney-dhb-list')->with('success', 'ลบพื้นที่ออกจากรายการเรียบร้อยแล้ว');
        }

        return redirect()->route('admin.kidney-dhb.areas')
            ->with('success', 'ยกเลิกการเลือกพื้นที่ของหน่วยงานเรียบร้อยแล้ว');
    }

    /**
     * SuperAdmin เท่านั้น: "ยกเลิกพื้นที่นี้" = ปลดการเลือกของหน่วยงานทุกแห่งที่เลือกพื้นที่นี้ไว้
     * เพื่อคืนพื้นที่ให้ว่าง (หมุดยังอยู่ และหน่วยงานอื่นเลือกใหม่ได้) ไม่ลบรายงาน
     * ถ้าพื้นที่นี้มีรายงานแล้ว จะไม่อนุญาตให้ยกเลิก
     */
    public function releaseOperatingAreaPin(Request $request, $id)
    {
        $user = auth()->user();
        abort_unless($user && $user->isSuperAdmin(), 404);

        $fail = function (string $message, int $status = 422) use ($request) {
            if ($request->ajax() || $request->wantsJson()) {
                return response()->json(['success' => false, 'message' => $message], $status);
            }
            return redirect()->route($request->input('back') === 'list' ? 'admin.kidney-dhb-list' : 'admin.operating-areas.index')->with('error', $message);
        };

        $pin = OperatingAreaPin::with(['province', 'district', 'tambon', 'village'])->findOrFail($id);

        if (!\App\Support\SchemaProbe::hasTable('kidney_operating_area_selections')) {
            return $fail('ยังไม่มีข้อมูลการเลือกพื้นที่ของหน่วยงาน');
        }

        $ownership = $this->operatingAreaPinOwnership(collect([$pin]));
        if (isset($ownership['reported'][(int) $pin->id])) {
            return $fail('พื้นที่นี้มีรายงานแล้ว จึงไม่สามารถยกเลิกการเลือกได้');
        }

        $labels = $ownership['selectors'][(int) $pin->id] ?? [];

        // ลบพื้นที่ทั้งหมด (ปุ่มถังขยะหน้ารายการ พชอ.ไต): ปลดการเลือก + ลบหมุดออกจากระบบ (ยังไม่มีรายงาน)
        // รองรับทั้ง status='selected' และ 'free' (ไม่มีผู้เลือก แต่ pin ค้างอยู่ใน DB)
        // รองรับ also = รหัสหมุดอื่นในกลุ่มเดียวกัน (group_ids) เพื่อลบพร้อมกัน
        if ($request->boolean('delete_pin') && empty($pin->display_only)) {
            $alsoIds = collect(explode(',', (string) $request->input('also', '')))
                ->map(fn($v) => (int) $v)->filter()->push((int) $pin->id)->unique()->values()->all();
            $alsoPins = OperatingAreaPin::whereIn('id', $alsoIds)->where('display_only', false)->get();
            $deletedCount = 0;
            foreach ($alsoPins as $p) {
                $hasReport = DB::table('kidney_assessments')->where('operating_area_pin_id', $p->id)->exists();
                if ($hasReport) {
                    continue; // มีรายงานอยู่แล้ว → ข้ามไป ไม่ลบ
                }
                DB::table('kidney_operating_area_selections')->where('pin_id', $p->id)->delete();
                $p->delete();
                $deletedCount++;
            }
            $this->pinListMemo = [];
            $msg = 'ลบพื้นที่ดำเนินงานออกจากระบบทั้งหมดเรียบร้อยแล้ว' . ($deletedCount > 1 ? " ({$deletedCount} พื้นที่)" : '');
            if ($request->ajax() || $request->wantsJson()) {
                return response()->json(['success' => true, 'message' => $msg]);
            }
            return redirect()->route($request->input('back') === 'list' ? 'admin.kidney-dhb-list' : 'admin.operating-areas.index')->with('success', $msg);
        }

        $deleted = DB::table('kidney_operating_area_selections')->where('pin_id', $pin->id)->delete();
        if (!$deleted) {
            return $fail('ยังไม่มีหน่วยงานเลือกพื้นที่นี้');
        }

        $message = 'ยกเลิกพื้นที่นี้แล้ว (คืนพื้นที่ให้ว่าง)' . (empty($labels) ? '' : ' — ปลดการเลือกของ ' . implode(', ', $labels));

        if ($request->ajax() || $request->wantsJson()) {
            return response()->json(['success' => true, 'message' => $message]);
        }

        if ($request->input('back') === 'list') {
            return redirect()->route('admin.kidney-dhb-list')->with('success', $message);
        }

        return redirect()->route('admin.operating-areas.index')->with('success', $message);
    }

    /**
     * Remove a pinned operating area.
     */
    /**
     * SuperAdmin: ลบหมุดพื้นที่ดำเนินงานทั้งหมดที่ตรงกับตัวกรองของตาราง
     * (ปีงบประมาณ / จังหวัด / อำเภอ) - หมุดที่ "มีรายงานแล้ว" จะไม่ถูกลบ (ข้ามไว้ ไม่ให้รายงานเสียพื้นที่อ้างอิง)
     * หมุดที่ลบจะปลดการเลือกของหน่วยงานด้วย. count_only=1 ขอจำนวนก่อนยืนยัน, confirm=1 ลบจริง
     */
    public function deleteFilteredOperatingAreaPins(Request $request)
    {
        $user = auth()->user();
        abort_unless($user && $user->isSuperAdmin(), 404);

        $query = OperatingAreaPin::with(['province', 'district', 'tambon', 'village']);
        if ($request->filled('filter_fiscal_year')) {
            $query->where('fiscal_year', $request->filter_fiscal_year);
        }
        if ($request->filled('filter_province_id')) {
            $query->where('province_id', $request->filter_province_id);
        }
        if ($request->filled('filter_district_id')) {
            $query->where('district_id', $request->filter_district_id);
        }

        $pins = $query->get();
        $reported = $this->operatingAreaPinOwnership($pins)['reported'];
        $deletable = $pins->reject(fn($p) => isset($reported[(int) $p->id]))->values();
        $skipped = $pins->count() - $deletable->count();

        if ($request->boolean('count_only')) {
            return response()->json([
                'success' => true,
                'count' => $pins->count(),
                'deletable' => $deletable->count(),
                'skipped' => $skipped,
            ]);
        }
        if (!$request->boolean('confirm')) {
            return response()->json(['success' => false, 'message' => 'ต้องยืนยันก่อนลบข้อมูล'], 422);
        }
        if ($deletable->isEmpty()) {
            return response()->json([
                'success' => false,
                'message' => $pins->isEmpty()
                    ? 'ไม่พบพื้นที่ที่ตรงกับตัวกรอง'
                    : 'พื้นที่ที่ตรงกับตัวกรองมีรายงานแล้วทั้งหมด จึงลบไม่ได้',
            ], 404);
        }

        $ids = $deletable->pluck('id')->all();
        try {
            DB::transaction(function () use ($ids) {
                foreach (array_chunk($ids, 500) as $chunk) {
                    if (\App\Support\SchemaProbe::hasTable('kidney_operating_area_selections')) {
                        DB::table('kidney_operating_area_selections')->whereIn('pin_id', $chunk)->delete();
                    }
                    OperatingAreaPin::whereIn('id', $chunk)->delete();
                    $this->pinListMemo = [];
                }
            });
        } catch (\Throwable $e) {
            \Log::error('Operating area delete filtered error: ' . $e->getMessage());
            return response()->json(['success' => false, 'message' => 'ลบไม่สำเร็จ: ' . $e->getMessage()], 500);
        }

        return response()->json([
            'success' => true,
            'message' => 'ลบพื้นที่ดำเนินงานเรียบร้อยแล้ว ' . number_format(count($ids)) . ' รายการ'
                . ($skipped > 0 ? ' (ข้าม ' . number_format($skipped) . ' รายการที่มีรายงานแล้ว)' : ''),
            'deleted_count' => count($ids),
            'skipped' => $skipped,
        ]);
    }

    public function destroyOperatingAreaPin($id)
    {
        $user = auth()->user();
        if ($user->User_rank_id != 1) {
            abort(403, 'Unauthorized action.');
        }

        $pin = OperatingAreaPin::findOrFail($id);
        $pin->delete();
        $this->pinListMemo = [];

        return redirect()->route('admin.operating-areas.index')
            ->with('success', 'ลบพื้นที่ดำเนินงานเรียบร้อยแล้ว');
    }
}
