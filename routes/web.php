<?php

use App\Http\Controllers\ArchiveController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\DispositionController;
use App\Http\Controllers\DispositionFollowupController;
use App\Http\Controllers\IncomingLetterController;
use App\Http\Controllers\MasterDataController;
use App\Http\Controllers\NotificationController;
use App\Http\Controllers\OutgoingLetterController;
use App\Http\Controllers\ProfileController;
use App\Http\Controllers\PublicLetterVerificationController;
use App\Http\Controllers\ReportExportController;
use App\Http\Controllers\UserController;
use Illuminate\Foundation\Application;
use Illuminate\Support\Facades\Route;
use Inertia\Inertia;

Route::get('/', function () {
    return Inertia::render('Welcome', [
        'canLogin' => Route::has('login'),
        'canRegister' => Route::has('register'),
        'laravelVersion' => Application::VERSION,
        'phpVersion' => PHP_VERSION,
    ]);
});

Route::get('/verify/outgoing-letter/{token}', PublicLetterVerificationController::class)
    ->name('public.outgoing-letters.verify');

Route::get('/dashboard', DashboardController::class)
    ->middleware(['auth', 'verified', 'permission:view dashboard'])
    ->name('dashboard');

Route::middleware(['auth', 'verified'])->group(function () {
    Route::get('/profile', [ProfileController::class, 'edit'])->name('profile.edit');
    Route::patch('/profile', [ProfileController::class, 'update'])->name('profile.update');
    Route::delete('/profile', [ProfileController::class, 'destroy'])->name('profile.destroy');

    Route::get('incoming-letters', [IncomingLetterController::class, 'index'])->middleware('permission:view incoming letters')->name('incoming-letters.index');
    Route::get('reports/incoming-letters.csv', [ReportExportController::class, 'incoming'])->middleware('permission:export reports')->name('reports.incoming-letters.csv');
    Route::get('incoming-letters/create', [IncomingLetterController::class, 'create'])->middleware('permission:create incoming letters')->name('incoming-letters.create');
    Route::get('incoming-letters/{incomingLetter}/edit', [IncomingLetterController::class, 'edit'])->middleware('permission:update incoming letters')->name('incoming-letters.edit');
    Route::post('incoming-letters', [IncomingLetterController::class, 'store'])->middleware('permission:create incoming letters')->name('incoming-letters.store');
    Route::get('incoming-letters/{incomingLetter}/file', [IncomingLetterController::class, 'file'])->middleware(['permission:view incoming letters', 'signed'])->name('incoming-letters.file');
    Route::get('incoming-letters/{incomingLetter}', [IncomingLetterController::class, 'show'])->middleware('permission:view incoming letters')->name('incoming-letters.show');
    Route::match(['put', 'patch'], 'incoming-letters/{incomingLetter}', [IncomingLetterController::class, 'update'])->middleware('permission:update incoming letters')->name('incoming-letters.update');
    Route::delete('incoming-letters/{incomingLetter}', [IncomingLetterController::class, 'destroy'])->middleware('permission:delete incoming letters')->name('incoming-letters.destroy');

    Route::get('outgoing-letters', [OutgoingLetterController::class, 'index'])->middleware('permission:view outgoing letters')->name('outgoing-letters.index');
    Route::get('reports/outgoing-letters.csv', [ReportExportController::class, 'outgoing'])->middleware('permission:export reports')->name('reports.outgoing-letters.csv');
    Route::get('outgoing-letters/approvals', [OutgoingLetterController::class, 'approvals'])->middleware('permission:view outgoing letters')->name('outgoing-letters.approvals');
    Route::get('outgoing-letters/monitor', [OutgoingLetterController::class, 'monitor'])->middleware('permission:view outgoing letters')->name('outgoing-letters.monitor');
    Route::get('outgoing-letters/number-preview', [OutgoingLetterController::class, 'numberPreview'])->middleware('permission:manage outgoing letters')->name('outgoing-letters.number-preview');
    Route::get('outgoing-letters/create', [OutgoingLetterController::class, 'create'])->middleware('permission:manage outgoing letters')->name('outgoing-letters.create');
    Route::get('outgoing-letters/{outgoingLetter}/edit', [OutgoingLetterController::class, 'edit'])->middleware('permission:manage outgoing letters')->name('outgoing-letters.edit');
    Route::post('outgoing-letters', [OutgoingLetterController::class, 'store'])->middleware('permission:manage outgoing letters')->name('outgoing-letters.store');
    Route::get('outgoing-letters/{outgoingLetter}/file', [OutgoingLetterController::class, 'file'])->middleware(['permission:view outgoing letters', 'signed'])->name('outgoing-letters.file');
    Route::get('outgoing-letters/{outgoingLetter}/preview', [OutgoingLetterController::class, 'preview'])->middleware('permission:view outgoing letters')->name('outgoing-letters.preview');
    Route::get('outgoing-letters/{outgoingLetter}/pdf', [OutgoingLetterController::class, 'downloadPdf'])->middleware('permission:view outgoing letters')->name('outgoing-letters.pdf');
    Route::get('outgoing-letters/{outgoingLetter}', [OutgoingLetterController::class, 'show'])->middleware('permission:view outgoing letters')->name('outgoing-letters.show');
    Route::patch('outgoing-letters/{outgoingLetter}/submit-approval', [OutgoingLetterController::class, 'submitApproval'])->middleware('permission:view outgoing letters')->name('outgoing-letters.submit-approval');
    Route::patch('outgoing-letters/{outgoingLetter}/approve', [OutgoingLetterController::class, 'approve'])->middleware('permission:view outgoing letters')->name('outgoing-letters.approve');
    Route::patch('outgoing-letters/{outgoingLetter}/reject', [OutgoingLetterController::class, 'reject'])->middleware('permission:view outgoing letters')->name('outgoing-letters.reject');
    Route::match(['put', 'patch'], 'outgoing-letters/{outgoingLetter}', [OutgoingLetterController::class, 'update'])->middleware('permission:manage outgoing letters')->name('outgoing-letters.update');
    Route::delete('outgoing-letters/{outgoingLetter}', [OutgoingLetterController::class, 'destroy'])->middleware('permission:manage outgoing letters')->name('outgoing-letters.destroy');

    Route::get('dispositions', [DispositionController::class, 'index'])->middleware('permission:view disposition')->name('dispositions.index');
    Route::get('reports/dispositions.csv', [ReportExportController::class, 'dispositions'])->middleware('permission:export reports')->name('reports.dispositions.csv');
    Route::get('dispositions/monitor', [DispositionController::class, 'monitor'])->middleware('permission:view disposition')->name('dispositions.monitor');
    Route::get('dispositions/create', [DispositionController::class, 'create'])->middleware('permission:create disposition')->name('dispositions.create');
    Route::post('dispositions', [DispositionController::class, 'store'])->middleware('permission:create disposition')->name('dispositions.store');
    Route::get('dispositions/{disposition}', [DispositionController::class, 'show'])->middleware('permission:view disposition')->name('dispositions.show');
    Route::patch('dispositions/{disposition}/status', [DispositionController::class, 'updateStatus'])->middleware('permission:update disposition status')->name('dispositions.status');
    Route::post('dispositions/{disposition}/forward', [DispositionController::class, 'forward'])->middleware('permission:create disposition')->name('dispositions.forward');
    Route::post('dispositions/{disposition}/followups', [DispositionFollowupController::class, 'store'])->middleware('permission:create followup')->name('dispositions.followups.store');
    Route::get('disposition-followups/{followup}/file', [DispositionFollowupController::class, 'file'])->middleware(['permission:view disposition', 'signed'])->name('dispositions.followups.file');

    Route::get('archives', ArchiveController::class)->middleware('permission:view archives')->name('archives.index');
    Route::get('reports/archives.csv', [ReportExportController::class, 'archives'])->middleware('permission:export reports')->name('reports.archives.csv');
    Route::post('notifications/read-all', [NotificationController::class, 'readAll'])->name('notifications.read-all');
    Route::post('notifications/{notification}/read', [NotificationController::class, 'read'])->name('notifications.read');
    Route::get('master-data', [MasterDataController::class, 'index'])->middleware('permission:manage master data')->name('master-data.index');
    Route::get('master-data/units', [MasterDataController::class, 'units'])->middleware('permission:manage master data')->name('master-data.units.index');
    Route::get('master-data/positions', [MasterDataController::class, 'positions'])->middleware('permission:manage master data')->name('master-data.positions.index');
    Route::get('master-data/categories', [MasterDataController::class, 'categories'])->middleware('permission:manage master data')->name('master-data.categories.index');
    Route::get('master-data/natures', [MasterDataController::class, 'natures'])->middleware('permission:manage master data')->name('master-data.natures.index');
    Route::get('master-data/archive-classifications', [MasterDataController::class, 'archiveClassifications'])->middleware('permission:manage master data')->name('master-data.archive-classifications.index');
    Route::get('master-data/instruction-templates', [MasterDataController::class, 'instructionTemplates'])->middleware('permission:manage master data')->name('master-data.instruction-templates.index');
    Route::post('master-data/units', [MasterDataController::class, 'storeUnit'])->middleware('permission:manage master data')->name('master-data.units.store');
    Route::match(['put', 'patch'], 'master-data/units/{unit}', [MasterDataController::class, 'updateUnit'])->middleware('permission:manage master data')->name('master-data.units.update');
    Route::delete('master-data/units/{unit}', [MasterDataController::class, 'destroyUnit'])->middleware('permission:manage master data')->name('master-data.units.destroy');
    Route::post('master-data/positions', [MasterDataController::class, 'storePosition'])->middleware('permission:manage master data')->name('master-data.positions.store');
    Route::match(['put', 'patch'], 'master-data/positions/{position}', [MasterDataController::class, 'updatePosition'])->middleware('permission:manage master data')->name('master-data.positions.update');
    Route::delete('master-data/positions/{position}', [MasterDataController::class, 'destroyPosition'])->middleware('permission:manage master data')->name('master-data.positions.destroy');
    Route::post('master-data/categories', [MasterDataController::class, 'storeCategory'])->middleware('permission:manage master data')->name('master-data.categories.store');
    Route::match(['put', 'patch'], 'master-data/categories/{letterCategory}', [MasterDataController::class, 'updateCategory'])->middleware('permission:manage master data')->name('master-data.categories.update');
    Route::delete('master-data/categories/{letterCategory}', [MasterDataController::class, 'destroyCategory'])->middleware('permission:manage master data')->name('master-data.categories.destroy');
    Route::post('master-data/natures', [MasterDataController::class, 'storeNature'])->middleware('permission:manage master data')->name('master-data.natures.store');
    Route::match(['put', 'patch'], 'master-data/natures/{letterNature}', [MasterDataController::class, 'updateNature'])->middleware('permission:manage master data')->name('master-data.natures.update');
    Route::delete('master-data/natures/{letterNature}', [MasterDataController::class, 'destroyNature'])->middleware('permission:manage master data')->name('master-data.natures.destroy');
    Route::post('master-data/archive-classifications', [MasterDataController::class, 'storeArchiveClassification'])->middleware('permission:manage master data')->name('master-data.archive-classifications.store');
    Route::match(['put', 'patch'], 'master-data/archive-classifications/{archiveClassification}', [MasterDataController::class, 'updateArchiveClassification'])->middleware('permission:manage master data')->name('master-data.archive-classifications.update');
    Route::delete('master-data/archive-classifications/{archiveClassification}', [MasterDataController::class, 'destroyArchiveClassification'])->middleware('permission:manage master data')->name('master-data.archive-classifications.destroy');
    Route::post('master-data/instruction-templates', [MasterDataController::class, 'storeInstructionTemplate'])->middleware('permission:manage master data')->name('master-data.instruction-templates.store');
    Route::match(['put', 'patch'], 'master-data/instruction-templates/{instructionTemplate}', [MasterDataController::class, 'updateInstructionTemplate'])->middleware('permission:manage master data')->name('master-data.instruction-templates.update');
    Route::delete('master-data/instruction-templates/{instructionTemplate}', [MasterDataController::class, 'destroyInstructionTemplate'])->middleware('permission:manage master data')->name('master-data.instruction-templates.destroy');

    Route::get('users', [UserController::class, 'index'])->middleware('permission:manage users')->name('users.index');
    Route::get('users/create', [UserController::class, 'create'])->middleware('permission:manage users')->name('users.create');
    Route::post('users', [UserController::class, 'store'])->middleware('permission:manage users')->name('users.store');
    Route::get('users/{user}/edit', [UserController::class, 'edit'])->middleware('permission:manage users')->name('users.edit');
    Route::match(['put', 'patch'], 'users/{user}', [UserController::class, 'update'])->middleware('permission:manage users')->name('users.update');
    Route::patch('users/{user}/status', [UserController::class, 'toggleStatus'])->middleware('permission:manage users')->name('users.status');
    Route::post('users/{user}/reset-password', [UserController::class, 'resetPassword'])->middleware('permission:manage users')->name('users.reset-password');
    Route::delete('users/{user}', [UserController::class, 'destroy'])->middleware('permission:manage users')->name('users.destroy');
});

require __DIR__.'/auth.php';
