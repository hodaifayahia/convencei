<?php

use App\Http\Controllers\ProfileController;
use Illuminate\Foundation\Application;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\ServiceController;
use App\Http\Controllers\CompanyController;
use App\Http\Controllers\ConventionController;

use Inertia\Inertia;

Route::get('/', function () {
    return Inertia::render('Welcome', [
        'canLogin' => Route::has('login'),
        'canRegister' => Route::has('register'),
        'laravelVersion' => Application::VERSION,
        'phpVersion' => PHP_VERSION,
    ]);
});

Route::get('/dashboard', function () {
    return Inertia::render('Dashboard');
})->middleware(['auth', 'verified'])->name('dashboard');

Route::middleware('auth')->group(function () {
    Route::get('/profile', [ProfileController::class, 'edit'])->name('profile.edit');
    Route::patch('/profile', [ProfileController::class, 'update'])->name('profile.update');
    Route::delete('/profile', [ProfileController::class, 'destroy'])->name('profile.destroy');
   

Route::resource('services', ServiceController::class)
    ->only(['index', 'store', 'update', 'destroy'])
    ->middleware(['auth', 'verified']);

Route::resource('companies', CompanyController::class)
    ->only(['index', 'store', 'update', 'destroy'])
    ->middleware(['auth', 'verified']);

// NEW: Resource routes for Conventions
Route::resource('conventions', ConventionController::class)
    ->only(['index', 'store', 'update', 'destroy'])
    ->middleware(['auth', 'verified']);

// NEW: Route for Excel import
Route::post('conventions/import', [ConventionController::class, 'import'])
    ->name('conventions.import')
    ->middleware(['auth', 'verified']);
    Route::get('/conventions/{convention}/edit', [ConventionController::class, 'edit'])->name('conventions.edit');


    // Add this route to your web.php for testing
Route::post('/test-upload', function(Request $request) {
    Log::info('Test upload called', [
        'has_file' => $request->hasFile('file'),
        'all_input' => $request->all(),
        'files' => $request->allFiles()
    ]);
    
    if ($request->hasFile('file')) {
        $file = $request->file('file');
        Log::info('File details', [
            'name' => $file->getClientOriginalName(),
            'size' => $file->getSize(),
            'mime' => $file->getMimeType(),
            'extension' => $file->getClientOriginalExtension(),
            'valid' => $file->isValid()
        ]);
        
        return response()->json([
            'success' => true,
            'message' => 'File uploaded successfully',
            'file_info' => [
                'name' => $file->getClientOriginalName(),
                'size' => $file->getSize(),
                'mime' => $file->getMimeType()
            ]
        ]);
    }
    
    return response()->json([
        'success' => false,
        'message' => 'No file uploaded'
    ], 400);
})->name('test.upload');
});


require __DIR__.'/auth.php';
