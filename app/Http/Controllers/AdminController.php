<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\SaltAssessment;
use App\Models\KidneyAssessment;
use App\Models\ReducedSodiumProduct;
use App\Models\ReducedSodiumMenu;
use App\Models\FiscalYear;
use App\Models\User;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\DB;
use Maatwebsite\Excel\Facades\Excel;
use App\Imports\ReducedSodiumMenuImport;
use App\Imports\ReducedSodiumMenuImportV2;
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
            ? \App\Models\Province::orderBy('province_name', 'asc')->get()
            : collect();

        return view('admin.report-progress', [
            'fiscal_year' => $request->query('fiscal_year'),
            'quarter' => $request->query('quarter'),
            'agencyName' => $agencyName,
            'canImportWord' => $canImportWord,
            'provinces' => $provinces,
        ]);
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
        $record = SaltAssessment::whereIn('user_id', $agencyUserIds)
            ->where('fiscal_year', $request->fiscal_year)
            ->where('quarter', $request->quarter)
            ->first();

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
        if ($request->quarter > 1) {
            $prevAssessments = SaltAssessment::whereIn('user_id', $agencyUserIds)
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
            SaltAssessment::updateOrCreate(
                [
                    'id' => $record->id ?? null, // Use ID if exists to force update the same record
                ],
                $data
            );

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
        $allAssessments = SaltAssessment::with('user')
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
                for ($q = 1; $q < $targetQuarter; $q++) {
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
        $exists = SaltAssessment::where('user_id', $scopeUser->id)
            ->where('fiscal_year', $request->fiscal_year)
            ->where('quarter', $targetQuarter)
            ->exists();

        return response()->json([
            'exists' => $exists,
            'data' => $cumulativeData,
            'milestones' => $milestones,
            // App timezone (config/app.php) is UTC, so a bare date('H:i:s')
            // here would show the server's UTC clock instead of Thailand
            // local time - this is what the "ซิงค์ล่าสุด" pill displays,
            // so it needs to be explicitly converted for that audience.
            'server_time' => now('Asia/Bangkok')->format('H:i:s')
        ]);
    }

    /**
     * Get all user IDs in the same agency as the given user.
     * - Rank 2 (สสจ.): same Province_id
     * - Rank 3+ (สสอ./others): same District_id
     */
    private function getAgencyUserIds($user): array
    {
        if (!$user)
            return [];
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

        // Word-import agency picker: cascades ประเภทหน่วยงาน -> จังหวัด ->
        // อำเภอ -> โรงพยาบาล/ชื่อหน่วยงาน, the same shape as the public
        // registration form's own picker (resources/views/pages/staff.blade.php),
        // then resolves the exact target User account through
        // resolveKidneyDhbTargetAgency() below - so it needs the province
        // list up front, the same way staff() passes $provinces to that
        // form. Only built for Level 1 users - nobody else can see or use it.
        $provinces = $canImportWord
            ? \App\Models\Province::orderBy('province_name', 'asc')->get()
            : collect();

        return view('admin.kidney-dhb', [
            'fiscal_year' => $request->query('fiscal_year'),
            'quarter' => $request->query('quarter'),
            'agencyName' => $agencyName,
            'operatingAreas' => $operatingAreas,
            'operatingArea' => $request->query('operating_area'),
            'provinces' => $provinces,
            'canImportWord' => $canImportWord,
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
                $newVal = trim(str_replace("\r\n", "\n", $request->input($f) ?? ''));
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


        try {
            KidneyAssessment::updateOrCreate(
                [
                    'id' => $record->id ?? null,
                ],
                $data
            );

            $redirectParams = [
                'fiscal_year' => $request->fiscal_year,
                'quarter' => $request->quarter,
            ];
            if ($operatingArea !== null) {
                $redirectParams['operating_area'] = $operatingArea;
            }

            return redirect()->route('admin.kidney-dhb', $redirectParams)
                ->with('success', 'บันทึกข้อมูล พชอ.ไต เรียบร้อยแล้ว');
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

        return response()->json([
            'exists' => (bool) $selectedAssessment,
            'is_template' => $isFromTemplate,
            'data' => $cumulativeData,
            'milestones' => $milestones,
            'global_milestones' => $globalMilestones,
            'disabled_fields' => $disabledFields,
            'reporter_name' => $reporterName,
            'updated_at' => $updatedAt,
            'respondent_info' => $respondentInfo,
            // Same Bangkok-time conversion as above - see comment there.
            'server_time' => now('Asia/Bangkok')->format('H:i:s')
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

        try {
            $parser = new KidneyDhbWordParser();
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
                if (!empty($u->email)) {
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
                    'account_email' => $u->email ?: null,
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
        $totalProducts = $statsQuery->count();
        $avgSodium = $statsQuery->avg('sodium_amount') ?: 0;

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
        $dbTypes = ReducedSodiumProduct::whereNotNull('product_type')->distinct()->pluck('product_type')->toArray();
        $productTypes = collect($defaultTypes)->merge($dbTypes)->unique()->sort()->values();

        $defaultStandards = [
            'มาตรฐาน อย.',
            'GHP / GMP',
            'HACCP',
            'มาตรฐานผลิตภัณฑ์ชุมชน (มผช.)',
            'มาตรฐานผลิตภัณฑ์อินทรีย์',
            'มาตรฐานฮาลาล'
        ];
        $dbStandards = ReducedSodiumProduct::whereNotNull('standard_certification')->distinct()->pluck('standard_certification')->toArray();
        $standards = collect($defaultStandards)->merge($dbStandards)->unique()->sort()->values();

        // ปีงบประมาณ options for the filter dropdown - always Buddhist year.
        // Rows with a real fiscal_year (set by storeSodiumProducts() and
        // the "นำเข้า Excel" importer) contribute it directly; anything
        // older only has update_date to go by, so its (Gregorian) year is
        // converted to Buddhist here - matching scopeProductsByFiscalYear().
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
        $years = FiscalYear::selectableYearsFor('sodium_products', $fiscalYearValues->merge($legacyYearValues));

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
        return Excel::download(new ReducedSodiumProductsExport($stats, $filters, $provinces), $fileName);
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
        return Excel::download(new ReducedSodiumMenusExport($stats, $filters, $provinces), $fileName);
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
        $statsQuery = clone $query;
        $totalMenus = $statsQuery->count();
        $avgSodiumAfter = $totalSubmissions = $statsQuery->avg('sodium_after') ?: 0;

        // Total Reduction = Sum(sodium_before - sodium_after)
        $totalReduction = $statsQuery->selectRaw('SUM(sodium_before - sodium_after) as total_red')->first()->total_red ?: 0;

        $stats = [
            'total_menus' => $totalMenus,
            'avg_sodium' => $avgSodiumAfter,
            'total_reduction' => $totalReduction
        ];

        $menus = $query->orderBy('update_date', 'desc')->paginate(15)->appends($request->all());
        $realMenuYears = ReducedSodiumMenu::whereNotNull('year')->where('year', '!=', '')->distinct()->pluck('year')->map(fn($y) => trim($y));
        $years = FiscalYear::selectableYearsFor('sodium_menus', $realMenuYears);
        $dbProvinces = ReducedSodiumMenu::whereNotNull('province')
            ->whereNotIn('province', ['', '-', ' '])
            ->distinct()
            ->pluck('province')
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
        $dbKitchenTypes = ReducedSodiumMenu::whereNotNull('kitchen_type')->where('kitchen_type', '!=', '')->distinct()->pluck('kitchen_type')->toArray();
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

        DB::beginTransaction();
        try {
            $import = new ReducedSodiumMenuImportV2(
                $request->fiscal_year,
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
            // Only show the latest quarter per agency/fiscal_year if no specific quarter filter is applied
            $query->whereIn('kidney_assessments.id', function ($q) {
                $q->select('a1.id')
                    ->from('kidney_assessments as a1')
                    ->join(\DB::raw('(SELECT user_id, fiscal_year, MAX(quarter) as max_quarter FROM kidney_assessments GROUP BY user_id, fiscal_year) as a2'), function ($join) {
                        $join->on('a1.user_id', '=', 'a2.user_id')
                            ->on('a1.fiscal_year', '=', 'a2.fiscal_year')
                            ->on('a1.quarter', '=', 'a2.max_quarter');
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

        // Define "Complete" as having all 10 categories filled
        $completeCount = (clone $query)->where(function ($q) {
            foreach (range(1, 7) as $i) {
                $q->whereNotNull("category_$i")->where("category_$i", '!=', '');
            }
            foreach (['1', '2', '3'] as $sub) {
                $q->whereNotNull("category_8_$sub")->where("category_8_$sub", '!=', '');
            }
        })->count();

        $stats = [
            'total' => $totalSubmissions,
            'complete' => $completeCount,
            'in_progress' => $totalSubmissions - $completeCount
        ];

        $assessments = $query->join('users', 'kidney_assessments.user_id', '=', 'users.id')
            ->select('kidney_assessments.*')
            ->orderBy('users.Province_id')
            ->orderBy('users.District_id')
            ->orderBy('users.User_rank_id')
            ->orderBy('users.hos_id')
            ->orderBy('users.sh_id')
            ->orderBy('kidney_assessments.fiscal_year', 'desc')
            ->orderBy('kidney_assessments.quarter', 'asc')
            ->paginate(15);
        $assessments->appends($request->all()); // Keep filters in pagination links

        // Per-quarter confirmation status (Q1-Q4) for each row's own
        // user_id + fiscal_year - mirrors the same grouping this list's own
        // dedup subquery above uses (user_id + fiscal_year, no rank/location
        // aggregation like the salt-assessment list needs), so the columns
        // line up with what's actually being deduped on this page. A
        // quarter counts as "confirmed" if any record for it is confirmed.
        $quarterCache = [];
        foreach ($assessments as $item) {
            // Built as a plain local array, not mutated in place on the
            // model - PHP can't do indirect modification of an overloaded
            // (magic __get/__set) Eloquent attribute like
            // $item->quarterConfirmations[$q] = ... (it silently has no
            // effect and logs an "Indirect modification" notice).
            $quarters = [1 => 'none', 2 => 'none', 3 => 'none', 4 => 'none'];

            $cacheKey = $item->user_id . '-' . $item->fiscal_year;
            if (!array_key_exists($cacheKey, $quarterCache)) {
                $quarterCache[$cacheKey] = KidneyAssessment::where('user_id', $item->user_id)
                    ->where('fiscal_year', $item->fiscal_year)
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

        if ($request->ajax()) {
            return view('admin.kidney-dhb-table', compact('assessments', 'stats'));
        }

        // Fetch Provinces for dropdown
        $provinces = \App\Models\Province::orderBy('province_name', 'asc')->get();

        // ปีงบประมาณ options for the filter dropdown - real distinct years
        // already in the data, plus any admin-enabled years and minus any
        // admin-hidden years from "จัดการปีงบประมาณ" (FiscalYearController).
        $years = FiscalYear::selectableYearsFor('kidney', KidneyAssessment::distinct()->pluck('fiscal_year'));

        return view('admin.kidney-dhb-list', compact('assessments', 'provinces', 'stats', 'years', 'operatingAreas', 'canImportWord'));
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
        $assessment = KidneyAssessment::with(['user.province', 'user.district', 'user.subdistrictHospital', 'user.hospital'])->findOrFail($id);
        $userId = $assessment->user_id;

        // Handle filter form submission
        if ($request->filled('filter_fiscal_year') || $request->filled('filter_quarter')) {
            $targetYear = $request->filter_fiscal_year ?? $assessment->fiscal_year;
            $targetQuarter = $request->filter_quarter ?? $assessment->quarter;
            $agencyUserIds = $this->getAgencyUserIds($assessment->user);

            // Stay within the SAME operating area while switching year/quarter
            // from the detail page - otherwise this could jump to a different
            // area's report that merely happens to share the agency.
            $filteredAssessment = KidneyAssessment::whereIn('user_id', $agencyUserIds)
                ->where('fiscal_year', $targetYear)
                ->where('quarter', $targetQuarter)
                ->where('operating_area', $assessment->operating_area)
                ->first();

            // Fallback: If exact quarter is not found for the requested year, just grab the latest available quarter for that year (same area)
            if (!$filteredAssessment && $targetYear != $assessment->fiscal_year) {
                $filteredAssessment = KidneyAssessment::whereIn('user_id', $agencyUserIds)
                    ->where('fiscal_year', $targetYear)
                    ->where('operating_area', $assessment->operating_area)
                    ->orderBy('quarter', 'desc')
                    ->first();
            }

            if ($filteredAssessment && $filteredAssessment->id != $assessment->id) {
                return redirect()->route('admin.kidney-dhb.show', $filteredAssessment->id);
            } else if (!$filteredAssessment) {
                return redirect()->route('admin.kidney-dhb.show', $assessment->id)
                    ->with('error', 'ไม่พบข้อมูลสำหรับปีงบประมาณและไตรมาสที่เลือก')
                    ->with('filter_year', $targetYear)
                    ->with('filter_quarter', $targetQuarter);
            }
        }

        // Get available options for dropdowns
        $currentYear = date('Y') + 543;
        $availableYears = range($currentYear + 1, $currentYear - 2);
        rsort($availableYears);

        // Available quarters for the CURRENT selected year in the view
        $availableQuarters = [1, 2, 3, 4];

        if (!$assessment->is_read) {
            $assessment->is_read = true;
            $assessment->save();
        }

        return view('admin.kidney-dhb-detail', compact('assessment', 'availableYears', 'availableQuarters'));
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

    public function destroyKidneyDHB($id)
    {
        $assessment = KidneyAssessment::findOrFail($id);
        $assessment->delete();

        return redirect()->route('admin.kidney-dhb-list')->with('success', 'ลบข้อมูลเรียบร้อยแล้ว');
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

        return view('admin.kidney-dhb-print', compact('assessment'));
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

        $provinces = \App\Models\Province::orderBy('province_name', 'asc')->get();

        // ปีงบประมาณ options for the filter dropdown - real distinct years
        // already in the data, plus any admin-enabled years and minus any
        // admin-hidden years from "จัดการปีงบประมาณ" (FiscalYearController).
        $years = FiscalYear::selectableYearsFor('salt_assessment', SaltAssessment::distinct()->pluck('fiscal_year'));

        // Word-import entry point ("นำเข้าไฟล์ Word") is shown on this list
        // only for Level 1 - same rank gate as the actual import feature on
        // admin.report-progress (see AdminController::reportProgress()'s
        // $canImportWord).
        $canImportWord = auth()->user()->User_rank_id == 1;

        return view('admin.salt-assessment-list', compact('assessments', 'provinces', 'stats', 'years', 'canImportWord'));
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
    public function confirmSaltAssessment($id)
    {
        abort_unless(auth()->user()->User_rank_id == 1, 403);

        $assessment = SaltAssessment::findOrFail($id);

        if ($assessment->confirmed_at) {
            $assessment->confirmed_at = null;
            $assessment->confirmed_by = null;
            $assessment->save();

            return redirect()->route('admin.salt-assessment.show', $assessment->id)
                ->with('success', 'ยกเลิกการยืนยันข้อมูลแล้ว');
        }

        $assessment->confirmed_at = now();
        $assessment->confirmed_by = auth()->id();
        $assessment->save();

        return redirect()->route('admin.salt-assessment.show', $assessment->id)
            ->with('success', 'ยืนยันข้อมูลเรียบร้อยแล้ว');
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
}
