<?php

use App\Http\Controllers\ProfileController;
use App\Http\Controllers\ScheduleViewController;
use App\Http\Controllers\PDFExportController;
use App\Http\Controllers\Admin\DosenController;
use App\Http\Controllers\Admin\MataKuliahController;
use App\Http\Controllers\Admin\RuanganController;
use App\Http\Controllers\Admin\KelasController;
use App\Http\Controllers\Admin\SemesterController;
use App\Http\Controllers\Admin\JadwalController;
use App\Http\Controllers\Admin\UserController;
use App\Http\Controllers\Admin\SetupController;
use Illuminate\Support\Facades\Route;
use Inertia\Inertia;

// 1. Public / Guest Routes
Route::get('/', [ScheduleViewController::class, 'index'])->name('welcome');
Route::get('/jadwal-saya', [ScheduleViewController::class, 'guestKrs'])->name('guest.krs');
Route::get('/export/pdf/jadwal', [PDFExportController::class, 'exportFull'])->name('export.pdf.full');
Route::get('/export/pdf/personal', [PDFExportController::class, 'exportStudent'])->name('export.pdf.student');

// 2. Redirect Hub after Login
Route::get('/dashboard', [ScheduleViewController::class, 'dashboardRedirect'])
    ->middleware(['auth', 'verified'])
    ->name('dashboard');

// 3. Shared Authenticated Profile Routes
Route::middleware('auth')->group(function () {
    Route::get('/profile', [ProfileController::class, 'edit'])->name('profile.edit');
    Route::patch('/profile', [ProfileController::class, 'update'])->name('profile.update');
    Route::delete('/profile', [ProfileController::class, 'destroy'])->name('profile.destroy');
});

// 4. Lecturer Routes (auth + can:dosen)
Route::middleware(['auth', 'can:dosen'])->group(function () {
    Route::get('/dosen/dashboard', [ScheduleViewController::class, 'dosenDashboard'])->name('dosen.dashboard');
    Route::post('/dosen/preferensi', [ScheduleViewController::class, 'storePreferensiDosen'])->name('dosen.preferensi.store');
    Route::delete('/dosen/preferensi/{preferensi}', [ScheduleViewController::class, 'destroyPreferensiDosen'])->name('dosen.preferensi.destroy');
    Route::get('/export/pdf/dosen', [PDFExportController::class, 'exportDosen'])->name('export.pdf.dosen');
});

// 5. Administrator Routes (auth + can:admin)
Route::middleware(['auth', 'can:admin'])->prefix('admin')->name('admin.')->group(function () {
    // Setup Phase (Tahap 1 Admin)
    Route::get('/setup', [SetupController::class, 'index'])->name('setup.index');
    Route::post('/setup/pengaturan', [SetupController::class, 'updatePengaturan'])->name('setup.updatePengaturan');
    Route::post('/setup/release', [SetupController::class, 'toggleRelease'])->name('setup.toggleRelease');
    Route::post('/setup/kelas-dibuka', [SetupController::class, 'storeKelasDibuka'])->name('setup.storeKelasDibuka');
    Route::delete('/setup/kelas-dibuka/{kelasDibuka}', [SetupController::class, 'destroyKelasDibuka'])->name('setup.destroyKelasDibuka');

    // Semester Management
    Route::get('/semester', [SemesterController::class, 'index'])->name('semester.index');
    Route::post('/semester', [SemesterController::class, 'store'])->name('semester.store');
    Route::post('/semester/{semester}/active', [SemesterController::class, 'setActive'])->name('semester.setActive');
    Route::get('/semester/{semester}/archive', [SemesterController::class, 'viewArchive'])->name('semester.viewArchive');
    Route::delete('/semester/{semester}', [SemesterController::class, 'destroy'])->name('semester.destroy');

    // Master Data CRUD Resources
    Route::resource('master/dosen', DosenController::class)->except(['create', 'edit', 'show']);
    Route::resource('master/matkul', MataKuliahController::class)->except(['create', 'edit', 'show'])->parameters([
        'matkul' => 'matkul'
    ]);
    Route::resource('master/ruangan', RuanganController::class)->except(['create', 'edit', 'show']);
    Route::resource('master/kelas', KelasController::class)->except(['create', 'edit', 'show']);

    // User Account Management
    Route::resource('pengguna', UserController::class)->except(['create', 'edit', 'show']);

    // Schedule Management & Auto-Generation
    Route::get('/jadwal', [JadwalController::class, 'index'])->name('jadwal');
    Route::post('/jadwal/generate-auto', [JadwalController::class, 'generateAuto'])->name('jadwal.generateAuto');
    Route::post('/jadwal/publish', [JadwalController::class, 'togglePublishSchedule'])->name('jadwal.togglePublish');
    Route::post('/jadwal', [JadwalController::class, 'store'])->name('jadwal.store');
    Route::put('/jadwal/{jadwal}', [JadwalController::class, 'update'])->name('jadwal.update');
    Route::delete('/jadwal/{jadwal}', [JadwalController::class, 'destroy'])->name('jadwal.destroy');
    Route::post('/validate-schedule', [JadwalController::class, 'checkConflictApi'])->name('jadwal.validate');
});

require __DIR__.'/auth.php';
