<?php

use App\Http\Controllers\Admin\LearningMaterialInventoryController as AdminLearningMaterialInventoryController;
use App\Http\Controllers\Admin\ResourceController as AdminResourceController;
use App\Http\Controllers\LearningMaterialInventoryController as UserLearningMaterialInventoryController;
use App\Http\Controllers\ProfileController;
use App\Http\Controllers\ResourceController as UserResourceController;
use Illuminate\Support\Facades\Route;

Route::get('/', function () {
    return redirect()->route('resources.index');
});

Route::get('/media/{path}', [UserResourceController::class, 'media'])
    ->where('path', '.*')
    ->name('media.show');

Route::get('/resources', [UserResourceController::class, 'index'])->name('resources.index');
Route::get('/about/organizational-structure', [UserResourceController::class, 'organizationalStructure'])->name('about.organizational-structure');
Route::get('/about/data-privacy', [UserResourceController::class, 'dataPrivacy'])->name('about.data-privacy');
Route::get('/about/citizens-charter', [UserResourceController::class, 'citizensCharter'])->name('about.citizens-charter');

Route::middleware('auth')->group(function () {
    Route::post('/resources/folders/{folder}/open', [UserResourceController::class, 'openFolder'])->name('resources.folders.open');
    Route::get('/resources/download/{file}', [UserResourceController::class, 'download'])->name('resources.download');
    Route::post('/resources/announcements/read-all', [UserResourceController::class, 'markAllAnnouncementsRead'])->name('resources.announcements.read-all');
    Route::post('/resources/announcements/{announcement}/read', [UserResourceController::class, 'markAnnouncementRead'])->name('resources.announcements.read');
    Route::post('/resources/announcements/{announcement}/dismiss', [UserResourceController::class, 'dismissAnnouncement'])->name('resources.announcements.dismiss');

    // PREVIEW ROUTE
    Route::get('/resources/preview/{file}', [UserResourceController::class, 'preview'])->name('resources.preview');
});

Route::middleware(['auth', 'verified'])->group(function () {
    Route::get('/dashboard', function () {
        if (request()->user() && request()->user()->is_admin) {
            return redirect()->route('admin.resources');
        }

        return redirect()->route('resources.index');
    })->name('dashboard');

    Route::get('/materials', [UserLearningMaterialInventoryController::class, 'index'])->name('materials.index');
    Route::post('/materials/{material}/quantity', [UserLearningMaterialInventoryController::class, 'store'])->name('materials.quantity.store');

    // ----------------------------------------------------
    // ADMIN END: Only admins can manage files and folders
    // ----------------------------------------------------
    Route::middleware(['admin'])->prefix('admin')->group(function () {

        // Main Explorer View & New Analytics Dashboard
        Route::get('/resources', [AdminResourceController::class, 'index'])->name('admin.resources');
        Route::get('/announcements', [AdminResourceController::class, 'announcements'])->name('admin.announcements');
        Route::get('/carousel', [AdminResourceController::class, 'carousel'])->name('admin.carousel');
        Route::get('/videos', [AdminResourceController::class, 'videos'])->name('admin.videos');
        Route::get('/analytics', [AdminResourceController::class, 'analytics'])->name('admin.analytics'); // NEW!
        Route::get('/resources/analytics', [AdminResourceController::class, 'analytics'])->name('admin.resources.analytics');
        Route::get('/materials-inventory', [AdminLearningMaterialInventoryController::class, 'index'])->name('admin.materials.inventory');
        Route::delete('/resources', function () {
            return to_route('admin.resources', [], 303);
        });
        Route::get('/users', [AdminResourceController::class, 'users'])->name('admin.users');

        // Folder Management
        Route::post('/folders', [AdminResourceController::class, 'storeFolder'])->name('admin.folders.store');
        Route::delete('/folders/{folder}', [AdminResourceController::class, 'destroyFolder'])->name('admin.folders.destroy');
        Route::patch('/folders/{folder}/toggle-lock', [AdminResourceController::class, 'toggleFolderLock'])->name('admin.folders.toggle-lock');
        Route::patch('/folders/{folder}/unlock-window', [AdminResourceController::class, 'setFolderUnlockWindow'])->name('admin.folders.unlock-window');

        // File Management
        Route::post('/files', [AdminResourceController::class, 'storeFile'])->name('admin.files.store');
        Route::delete('/files/{file}', [AdminResourceController::class, 'destroyFile'])->name('admin.files.destroy');
        Route::patch('/files/{file}/toggle-lock', [AdminResourceController::class, 'toggleFileLock'])->name('admin.files.toggle-lock');
        Route::patch('/files/{file}/unlock-window', [AdminResourceController::class, 'setFileUnlockWindow'])->name('admin.files.unlock-window');

        // Portal Content Management (NEW!)
        Route::post('/carousel', [AdminResourceController::class, 'storeCarousel'])->name('admin.carousel.store');
        Route::patch('/carousel/{carousel}', [AdminResourceController::class, 'updateCarousel'])->name('admin.carousel.update');
        Route::delete('/carousel/{carousel}', [AdminResourceController::class, 'destroyCarousel'])->name('admin.carousel.destroy');
        Route::post('/videos', [AdminResourceController::class, 'storeVideo'])->name('admin.videos.store');
        Route::patch('/videos/{video}', [AdminResourceController::class, 'updateVideo'])->name('admin.videos.update');
        Route::delete('/videos/{video}', [AdminResourceController::class, 'destroyVideo'])->name('admin.videos.destroy');
        Route::post('/announcements', [AdminResourceController::class, 'storeAnnouncement'])->name('admin.announcements.store');
        Route::patch('/announcements/{announcement}', [AdminResourceController::class, 'updateAnnouncement'])->name('admin.announcements.update');
        Route::delete('/announcements/{announcement}', [AdminResourceController::class, 'destroyAnnouncement'])->name('admin.announcements.destroy');
        Route::post('/materials-inventory', [AdminLearningMaterialInventoryController::class, 'store'])->name('admin.materials.inventory.store');
        Route::delete('/materials-inventory/{material}', [AdminLearningMaterialInventoryController::class, 'destroy'])->name('admin.materials.inventory.destroy');

        // User Management
        Route::post('/users', [AdminResourceController::class, 'storeUser'])->name('admin.users.store');
        Route::patch('/users/{user}', [AdminResourceController::class, 'updateUser'])->name('admin.users.update');
        Route::patch('/users/{user}/password', [AdminResourceController::class, 'updateUserPassword'])->name('admin.users.password');
        Route::delete('/users/{user}', [AdminResourceController::class, 'destroyUser'])->name('admin.users.destroy');
    });
});

Route::middleware('auth')->group(function () {
    Route::get('/profile', [ProfileController::class, 'edit'])->name('profile.edit');
    Route::patch('/profile', [ProfileController::class, 'update'])->name('profile.update');
    Route::delete('/profile', [ProfileController::class, 'destroy'])->name('profile.destroy');
});

require __DIR__.'/auth.php';
