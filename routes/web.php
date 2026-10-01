<?php

use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| Web Routes
|--------------------------------------------------------------------------
|
| Here is where you can register web routes for your application. These
| routes are loaded by the RouteServiceProvider and all of them will
| be assigned to the "web" middleware group. Make something great!
|
*/

use App\Http\Controllers\MainController;
use App\Http\Controllers\AuthController;
use App\Http\Controllers\AdminController;
use App\Http\Controllers\HiController;
use App\Http\Controllers\AwarenessAssessmentController;
use App\Http\Controllers\SurveyQuestionSettingsController;
use App\Models\KidneyAssessment;
use App\Models\ReducedSodiumProduct;
use App\Models\ReducedSodiumMenu;

// Fallback file server for storage/app/public.
// Normally PHP's built-in dev server serves these files directly through the
// public/storage symlink (created by `php artisan storage:link`) without ever
// reaching Laravel's router. On this host that symlink isn't being followed
// correctly (a known quirk on some Windows setups), so uploaded PDFs/images
// 404 before Laravel gets a chance to handle them. This route is the fix:
// if the static file/symlink lookup fails, the request falls through to
// Laravel, which serves the file straight from storage/app/public instead.
// If the symlink starts working again this route simply never gets hit.
Route::get('/storage/{path}', function (string $path) {
    $base = storage_path('app/public');
    $full = $base . DIRECTORY_SEPARATOR . str_replace(['/', '\\'], DIRECTORY_SEPARATOR, $path);

    // Reject any attempt to escape the public storage folder (path traversal).
    if (strpos($path, '..') !== false) {
        abort(404);
    }

    if (!is_file($full)) {
        // TEMP DIAGNOSTIC: tell us exactly why the lookup failed instead of
        // just 404ing (always on, not gated by APP_DEBUG, so we can rule out
        // a debug-mode mismatch too). Will be removed once the real cause is
        // confirmed.
        return response()->json([
            'route_was_reached' => true,
            'requested_path' => $path,
            'resolved_full_path' => $full,
            'base_dir' => $base,
            'base_dir_exists' => is_dir($base),
            'full_is_file' => is_file($full),
            'full_file_exists' => file_exists($full),
            'realpath_full' => realpath($full),
            'app_debug' => config('app.debug'),
            'dir_listing_of_target_folder' => is_dir(dirname($full)) ? array_slice(scandir(dirname($full)), 0, 20) : 'PARENT_DIR_NOT_FOUND: ' . dirname($full),
        ], 404);
    }

    return response()->file($full);
})->where('path', '.*')->name('storage.local-fallback');

Route::get('/', [MainController::class, 'index'])->name('home');
Route::get('/awareness', [MainController::class, 'awareness'])->name('awareness');
Route::get('/food-survey', [MainController::class, 'foodSurvey'])->name('food-survey');
Route::get('/reduced-sodium-menu', [MainController::class, 'reducedSodiumMenu'])->name('reduced-sodium-menu');
Route::get('/reduced-sodium-products', [MainController::class, 'reducedSodiumProducts'])->name('reduced-sodium-products');
Route::get('/kidney-dhb', [MainController::class, 'kidneyDHB'])->name('kidney-dhb');
Route::get('/kidney-dhb-report', [MainController::class, 'kidneyDHBReport'])->name('kidney-dhb-report');
Route::get('/new-ht-cases', [MainController::class, 'newHTCases'])->name('new-ht-cases');
Route::get('/consumption-report', [MainController::class, 'consumptionReport'])->name('consumption-report');
Route::get('/staff', [MainController::class, 'staff'])->name('staff');
Route::get('/login', [MainController::class, 'staff'])->name('login');

Route::post('/register', [AuthController::class, 'register'])->name('register');
Route::post('/login', [AuthController::class, 'login'])->name('login.post');
Route::post('/logout', [AuthController::class, 'logout'])->name('logout');
Route::get('/login/otp', [AuthController::class, 'showLoginOtpForm'])->name('login.otp.form');
Route::post('/login/otp', [AuthController::class, 'verifyLoginOtp'])->name('login.otp.verify');
Route::post('/login/otp/resend', [AuthController::class, 'resendLoginOtp'])->name('login.otp.resend');
Route::get('/get-districts/{province_id}', [AuthController::class, 'getDistricts'])->name('get-districts');
Route::get('/get-hospitals/{province_id}/{district_id}', [AuthController::class, 'getHospitals'])->name('get-hospitals');
Route::get('/get-subdistricts/{province_id}/{district_id}', [AuthController::class, 'getSubdistricts'])->name('get-subdistricts');
Route::get('/get-subdistrict-hospitals/{province_id}/{district_id}/{tambon_id?}', [AuthController::class, 'getSubdistrictHospitals'])->name('get-subdistrict-hospitals');


// Password Reset Routes (OTP flow: email -> OTP code (5 min) -> reset password)
Route::get('/forgot-password', [AuthController::class, 'showForgotPasswordForm'])->name('password.request');
Route::post('/forgot-password', [AuthController::class, 'sendResetLinkEmail'])->name('password.email');
Route::get('/forgot-password/otp', [AuthController::class, 'showOtpForm'])->name('password.otp.form');
Route::post('/forgot-password/otp', [AuthController::class, 'verifyOtp'])->name('password.otp.verify');
Route::get('/reset-password/{token}', [AuthController::class, 'showResetPasswordForm'])->name('password.reset');
Route::post('/reset-password', [AuthController::class, 'resetPassword'])->name('password.update');

//เกลือ
Route::get('/report-progress', [MainController::class, 'reportProgress'])->name('report-progress');

// Admin Routes
Route::prefix('admin')->middleware('auth')->group(function () {
    Route::get('/', function () {
        $user = auth()->user();
        if ($user->User_rank_id == 5) {
            return redirect()->route('admin.sodium-menus');
        } elseif (in_array($user->User_rank_id, [3, 4])) {
            return redirect()->route('admin.kidney-dhb-list');
        }
        return redirect()->route('admin.salt-assessment-list');
    })->name('admin.dashboard');
    Route::get('/report-progress', [AdminController::class, 'reportProgress'])->name('admin.report-progress');
    Route::get('/salt-assessment-list', [AdminController::class, 'saltAssessmentList'])->name('admin.salt-assessment-list');
    Route::get('/salt-assessment/{id}', [AdminController::class, 'showSaltAssessment'])->name('admin.salt-assessment.show');
    Route::post('/salt-assessment/{id}/confirm', [AdminController::class, 'confirmSaltAssessment'])->name('admin.salt-assessment.confirm');
    Route::get('/salt-assessment/{id}/export-excel', [AdminController::class, 'exportSaltAssessmentExcel'])->name('admin.salt-assessment.export-excel');
    Route::get('/salt-assessment/{id}/export-pdf', [AdminController::class, 'exportSaltAssessmentPdf'])->name('admin.salt-assessment.export-pdf');
    Route::get('/salt-assessment/{id}/export-word', [AdminController::class, 'exportSaltAssessmentWord'])->name('admin.salt-assessment.export-word');
    Route::delete('/salt-assessment/{id}', [AdminController::class, 'destroySaltAssessment'])->name('admin.salt-assessment.destroy');
    Route::post('/report-progress', [AdminController::class, 'storeReportProgress'])->name('admin.report-progress.store');
    Route::get('/get-assessment-data', [AdminController::class, 'getAssessmentData'])->name('admin.get-assessment-data');
    Route::post('/report-progress/remove-file', [AdminController::class, 'removeReportFile'])->name('admin.report-progress.remove-file');
    Route::post('/report-progress/parse-word', [AdminController::class, 'parseSaltAssessmentWord'])->name('admin.report-progress.parse-word');

    // Kidney DHB (พชอ.ไต)
    Route::get('/kidney-dhb-list', [AdminController::class, 'kidneyDHBList'])->name('admin.kidney-dhb-list');
    Route::get('/kidney-dhb-export', [AdminController::class, 'exportKidneyDHBExcel'])->name('admin.kidney-dhb.export-excel');
    Route::get('/kidney-dhb/resolve-target-agency', [AdminController::class, 'resolveKidneyDhbTargetAgency'])->name('admin.kidney-dhb.resolve-target-agency');
    Route::get('/kidney-dhb/{id}', [AdminController::class, 'showKidneyDHB'])->name('admin.kidney-dhb.show');
    Route::post('/kidney-dhb/{id}/confirm', [AdminController::class, 'confirmKidneyDHB'])->name('admin.kidney-dhb.confirm');
    Route::get('/admin/kidney-dhb/{id}/export-pdf', [AdminController::class, 'exportKidneyDHBPdf'])->name('admin.kidney-dhb.export-pdf');
    Route::get('/admin/kidney-dhb/{id}/export-word', [AdminController::class, 'exportKidneyDHBWord'])->name('admin.kidney-dhb.export-word');
    Route::delete('/kidney-dhb/{id}', [AdminController::class, 'destroyKidneyDHB'])->name('admin.kidney-dhb.destroy');
    Route::get('/kidney-dhb', [AdminController::class, 'kidneyDHB'])->name('admin.kidney-dhb');
    Route::post('/kidney-dhb', [AdminController::class, 'storeKidneyDHB'])->name('admin.kidney-dhb.store');
    Route::get('/get-kidney-dhb-data', [AdminController::class, 'getKidneyDHBData'])->name('admin.get-kidney-dhb-data');
    Route::post('/kidney-dhb/parse-word', [AdminController::class, 'parseKidneyDhbWord'])->name('admin.kidney-dhb.parse-word');

    // Reduced Sodium Products (ผลิตภัณฑ์ลดโซเดียม)
    Route::get('/sodium-products', [AdminController::class, 'sodiumProducts'])->name('admin.sodium-products');
    Route::get('/sodium-products/bulk-delete-count', [AdminController::class, 'getSodiumProductsDeleteCount'])->name('admin.sodium-products.bulk-delete-count');
    Route::post('/sodium-products/bulk-delete', [AdminController::class, 'bulkDeleteSodiumProducts'])->name('admin.sodium-products.bulk-delete');
    Route::post('/sodium-products/bulk-delete-selected', [AdminController::class, 'bulkDeleteSodiumProductsByIds'])->name('admin.sodium-products.bulk-delete-selected');
    Route::get('/sodium-products/export', [AdminController::class, 'exportSodiumProductsExcel'])->name('admin.sodium-products.export');
    Route::get('/sodium-products/template', [AdminController::class, 'downloadSodiumProductsTemplate'])->name('admin.sodium-products.template');
    Route::post('/sodium-products', [AdminController::class, 'storeSodiumProducts'])->name('admin.sodium-products.store');
    Route::post('/sodium-products/import', [AdminController::class, 'importSodiumProducts'])->name('admin.sodium-products.import');
    Route::patch('/sodium-products/{id}', [AdminController::class, 'updateSodiumProduct'])->name('admin.sodium-products.update');
    Route::delete('/sodium-products/{id}', [AdminController::class, 'destroySodiumProduct'])->name('admin.sodium-products.destroy');

    // Reduced Sodium Menus (เมนูลดโซเดียม)
    Route::get('/sodium-menus', [AdminController::class, 'sodiumMenus'])->name('admin.sodium-menus');
    Route::get('/sodium-menus/export', [AdminController::class, 'exportSodiumMenusExcel'])->name('admin.sodium-menus.export');
    Route::get('/sodium-menus/template', [AdminController::class, 'downloadSodiumMenusTemplate'])->name('admin.sodium-menus.template');
    Route::get('/sodium-menus/bulk-delete-count', [AdminController::class, 'getSodiumMenusDeleteCount'])->name('admin.sodium-menus.bulk-delete-count');
    Route::post('/sodium-menus/bulk-delete', [AdminController::class, 'bulkDeleteSodiumMenus'])->name('admin.sodium-menus.bulk-delete');
    Route::post('/sodium-menus/bulk-delete-selected', [AdminController::class, 'bulkDeleteSodiumMenusByIds'])->name('admin.sodium-menus.bulk-delete-selected');
    Route::post('/sodium-menus', [AdminController::class, 'storeSodiumMenu'])->name('admin.sodium-menus.store');
    Route::post('/sodium-menus/import', [AdminController::class, 'importSodiumMenus'])->name('admin.sodium-menus.import');
    Route::patch('/sodium-menus/{id}', [AdminController::class, 'updateSodiumMenu'])->name('admin.sodium-menus.update');
    Route::delete('/sodium-menus/{id}', [AdminController::class, 'destroySodiumMenu'])->name('admin.sodium-menus.destroy');
    // Awareness Assessment (การประเมินความตระหนักรู้)
    Route::get('/awareness', [AwarenessAssessmentController::class, 'index'])->name('admin.awareness');
    Route::post('/awareness/import', [AwarenessAssessmentController::class, 'store'])->name('admin.awareness.import');
    Route::delete('/awareness/delete-filtered', [AwarenessAssessmentController::class, 'deleteFiltered'])->name('admin.awareness.delete-filtered');
    Route::get('/awareness/interpretation', [AwarenessAssessmentController::class, 'interpretation'])->name('admin.awareness.interpretation');
    Route::get('/awareness/interpretation/export', [AwarenessAssessmentController::class, 'exportInterpretation'])->name('admin.awareness.interpretation.export');
    Route::get('/awareness/settings/criteria', [SurveyQuestionSettingsController::class, 'criteria'])->name('admin.awareness.settings.criteria');
    Route::post('/awareness/settings/criteria', [SurveyQuestionSettingsController::class, 'updateCriteria'])->name('admin.awareness.settings.criteria.update');
    Route::post('/awareness/settings/criteria/pass-method', [SurveyQuestionSettingsController::class, 'updatePassMethod'])->name('admin.awareness.settings.criteria.pass-method.update');
    Route::post('/awareness/settings/criteria/behavior', [SurveyQuestionSettingsController::class, 'updateBehavior'])->name('admin.awareness.settings.criteria.behavior.update');
    Route::get('/awareness/settings/dashboard', [SurveyQuestionSettingsController::class, 'dashboard'])->name('admin.awareness.settings.dashboard');
    Route::post('/awareness/settings/dashboard/panel', [SurveyQuestionSettingsController::class, 'updatePanelMembership'])->name('admin.awareness.settings.dashboard.panel.update');
    Route::post('/awareness/settings/dashboard/demographic', [SurveyQuestionSettingsController::class, 'updateDemographicMapping'])->name('admin.awareness.settings.dashboard.demographic.update');
    Route::post('/awareness/settings/dashboard/reset-mappings', [SurveyQuestionSettingsController::class, 'resetQuestionMappings'])->name('admin.awareness.settings.dashboard.reset');
    Route::get('/awareness/settings/scoring', [SurveyQuestionSettingsController::class, 'scoring'])->name('admin.awareness.settings.scoring');
    Route::post('/awareness/settings/scoring', [SurveyQuestionSettingsController::class, 'updateScoreRole'])->name('admin.awareness.settings.scoring.update');
    Route::post('/awareness/settings/scoring/category', [SurveyQuestionSettingsController::class, 'updateScoreCategory'])->name('admin.awareness.settings.scoring.category.update');

    // HI (อัตราป่วยรายใหม่ HT)
    Route::get('/hi', [HiController::class, 'index'])->name('admin.hi.index');
    Route::post('/hi/import', [HiController::class, 'import'])->name('admin.hi.import');
    Route::delete('/hi/delete-filtered', [HiController::class, 'deleteFiltered'])->name('admin.hi.delete-filtered');

    // Dashboard Integrated (Iframe)
    Route::get('/reduced-sodium-menu-dashboard', [MainController::class, 'adminReducedSodiumMenu'])->name('admin.reduced-sodium-menu-dashboard');
    Route::get('/reduced-sodium-products-dashboard', [MainController::class, 'adminReducedSodiumProducts'])->name('admin.reduced-sodium-products-dashboard');
    Route::get('/new-ht-cases-dashboard', [MainController::class, 'adminNewHtCases'])->name('admin.new-ht-cases-dashboard');

    // User Management
    Route::get('/users', [\App\Http\Controllers\UserManagementController::class, 'index'])->name('admin.users.index');
    Route::get('/users/export-excel', [\App\Http\Controllers\UserManagementController::class, 'exportExcel'])->name('admin.users.export-excel');
    Route::post('/users/{id}/toggle-approval', [\App\Http\Controllers\UserManagementController::class, 'toggleApproval'])->name('admin.users.toggle-approval');
    Route::get('/users/{id}/edit', [\App\Http\Controllers\UserManagementController::class, 'edit'])->name('admin.users.edit');
    Route::put('/users/{id}', [\App\Http\Controllers\UserManagementController::class, 'update'])->name('admin.users.update');
    Route::post('/users/{id}', [\App\Http\Controllers\UserManagementController::class, 'destroy'])->name('admin.users.destroy');

    // Fiscal Year Management
    Route::get('/fiscal-years', [\App\Http\Controllers\FiscalYearController::class, 'index'])->name('admin.fiscal-years.index');
    Route::post('/fiscal-years', [\App\Http\Controllers\FiscalYearController::class, 'store'])->name('admin.fiscal-years.store');
    Route::delete('/fiscal-years/{id}', [\App\Http\Controllers\FiscalYearController::class, 'destroy'])->name('admin.fiscal-years.destroy');

    // Excel Template Management
    Route::get('/excel-templates', [\App\Http\Controllers\ExcelTemplateController::class, 'index'])->name('admin.excel-templates.index');
});
