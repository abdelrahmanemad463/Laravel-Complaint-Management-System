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
    Route::get('complaints/export',[ComplaintController::class,'export'])->name('complaints.export')->middleware('permission:complaint.export');
    Route::resource('complaints',ComplaintController::class)->only(['index','create','store','show','edit','update']);
    Route::get('reports/branches',[ReportController::class,'branches'])->name('reports.branches')->middleware('permission:report.view');
    Route::resource('users', UserController::class)->only(['index','create','store','edit','update']);
    Route::get('roles',[RoleController::class,'index'])->name('roles.index');
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
