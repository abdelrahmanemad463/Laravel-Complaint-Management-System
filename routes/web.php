<?php
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\{AuthController,LocaleController,DashboardController,CustomerController,ComplaintController,MasterDataController,ReportController,UserController,RoleController,AuditLogController};
Route::get('/login',[AuthController::class,'showLogin'])->name('login');
Route::post('/login',[AuthController::class,'login'])->name('login.store');
Route::post('/logout',[AuthController::class,'logout'])->middleware('auth')->name('logout');
Route::get('/locale/{locale}',LocaleController::class)->name('locale');
Route::middleware('auth')->group(function () {
    Route::get('/',DashboardController::class)->name('dashboard');
    Route::get('customers/search',[CustomerController::class,'search'])->name('customers.search');
    Route::resource('customers',CustomerController::class)->only(['index','create','store','show','edit','update']);
    Route::get('complaints/branches/search',[ComplaintController::class,'searchBranches'])->name('complaints.branches.search');
    Route::get('complaints/export',[ComplaintController::class,'export'])->name('complaints.export')->middleware('permission:complaint.export');
    Route::resource('complaints',ComplaintController::class)->only(['index','create','store','show','edit','update']);
    Route::get('reports/branches',[ReportController::class,'branches'])->name('reports.branches')->middleware('permission:report.view');
    Route::prefix('visitors')->name('visitors.')->group(function () {
        Route::get('/', [\App\Http\Controllers\Visitors\VisitController::class,'home'])->name('home');
        Route::get('open', [\App\Http\Controllers\Visitors\VisitController::class,'open'])->name('open');
        Route::get('create', [\App\Http\Controllers\Visitors\VisitController::class,'create'])->name('create');
        Route::post('/', [\App\Http\Controllers\Visitors\VisitController::class,'store'])->name('store');
        Route::get('reports', [\App\Http\Controllers\Visitors\VisitorReportController::class,'index'])->name('reports');
        Route::get('reports/dashboard', [\App\Http\Controllers\Visitors\VisitorReportController::class,'dashboard'])->name('reports.dashboard')->middleware('permission:dashboard.visitors.view');
        Route::get('reports/{visit}/pdf', [\App\Http\Controllers\Visitors\VisitorReportController::class,'pdf'])->name('reports.pdf');
        Route::get('reports/{visit}', [\App\Http\Controllers\Visitors\VisitorReportController::class,'show'])->name('reports.show');
        Route::get('master-data', [\App\Http\Controllers\Visitors\VisitorMasterDataController::class,'index'])->name('master-data');
        Route::get('master-data/template', [\App\Http\Controllers\Visitors\VisitorMasterDataController::class,'template'])->name('master-data.template');
        Route::get('master-data/template-example', [\App\Http\Controllers\Visitors\VisitorMasterDataController::class,'templateExample'])->name('master-data.template-example');
        Route::get('master-data/download', [\App\Http\Controllers\Visitors\VisitorMasterDataController::class,'download'])->name('master-data.download');
        Route::post('master-data/import', [\App\Http\Controllers\Visitors\VisitorMasterDataController::class,'import'])->name('master-data.import');
        Route::get('master-data/import/{import}/preview', [\App\Http\Controllers\Visitors\VisitorMasterDataController::class,'preview'])->name('master-data.import.preview');
        Route::post('master-data/import/{import}/confirm', [\App\Http\Controllers\Visitors\VisitorMasterDataController::class,'confirm'])->name('master-data.import.confirm');
        Route::post('master-data/import/{import}/cancel', [\App\Http\Controllers\Visitors\VisitorMasterDataController::class,'cancel'])->name('master-data.import.cancel');

        // Follow-up / Violations workflow
        Route::get('violations', [\App\Http\Controllers\Visitors\VisitorViolationController::class,'index'])->name('violations');
        Route::get('violations/{capaAction}', [\App\Http\Controllers\Visitors\VisitorViolationController::class,'show'])->name('violations.show');
        Route::post('violations/{capaAction}/resolve', [\App\Http\Controllers\Visitors\VisitorViolationController::class,'resolve'])->name('violations.resolve');
        Route::post('violations/{capaAction}/approve', [\App\Http\Controllers\Visitors\VisitorViolationController::class,'approve'])->name('violations.approve');
        Route::post('violations/{capaAction}/reject', [\App\Http\Controllers\Visitors\VisitorViolationController::class,'reject'])->name('violations.reject');

        Route::post('{visit}/submit', [\App\Http\Controllers\Visitors\VisitController::class,'submit'])->name('submit');
        Route::get('{visit}', [\App\Http\Controllers\Visitors\VisitController::class,'show'])->name('show');
        Route::put('items/{visitItem}', [\App\Http\Controllers\Visitors\VisitItemController::class,'update'])->name('items.update');
        Route::post('items/{visitItem}/photo', [\App\Http\Controllers\Visitors\VisitPhotoController::class,'store'])->name('items.photo');
        Route::get('photos/{photo}', [\App\Http\Controllers\Visitors\VisitPhotoController::class,'serve'])->name('photos.serve');
    });
    Route::resource('users', UserController::class)->only(['index','create','store','edit','update']);
    Route::get('roles',[RoleController::class,'index'])->name('roles.index');
    Route::get('roles/create',[RoleController::class,'create'])->name('roles.create');
    Route::post('roles',[RoleController::class,'store'])->name('roles.store');
    Route::delete('roles/{role}',[RoleController::class,'destroy'])->name('roles.destroy');
    Route::get('roles/{role}/edit',[RoleController::class,'edit'])->name('roles.edit');
    Route::put('roles/{role}',[RoleController::class,'update'])->name('roles.update');
    Route::get('audit-logs',[AuditLogController::class,'index'])->name('audit-logs.index');
    Route::prefix('master-data')->name('master.')->middleware('permission:branch.view')->group(function () {
        Route::get('{type}',[MasterDataController::class,'index'])->name('index');
        Route::get('{type}/create',[MasterDataController::class,'create'])->name('create');
        Route::post('{type}',[MasterDataController::class,'store'])->name('store');
        Route::get('{type}/{item}/edit',[MasterDataController::class,'edit'])->name('edit');
        Route::put('{type}/{item}',[MasterDataController::class,'update'])->name('update');
        Route::delete('{type}/{item}',[MasterDataController::class,'destroy'])->name('destroy');
    });
});
