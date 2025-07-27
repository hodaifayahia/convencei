<?php

use App\Http\Controllers\ProfileController;
use Illuminate\Foundation\Application;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\ServiceController;
use App\Http\Controllers\CompanyController;
use App\Http\Controllers\ConventionController;
use App\Http\Controllers\PatientController;
use App\Http\Controllers\FicheNavetteController;
use App\Http\Controllers\DashboardController;
use Illuminate\Http\Request; // Added
use Illuminate\Support\Facades\Log; // Added

use Inertia\Inertia;

Route::get('/', function () {
    return Inertia::render('Welcome', [
        'canLogin' => Route::has('login'),
        'canRegister' => Route::has('register'),
        'laravelVersion' => Application::VERSION,
        'phpVersion' => PHP_VERSION,
    ]);
});

Route::middleware(['auth', 'verified'])->group(function () { // Applied 'verified' middleware to the group
    Route::get('/dashboard', [DashboardController::class, 'index'])->name('dashboard');

    Route::get('/profile', [ProfileController::class, 'edit'])->name('profile.edit');
    Route::patch('/profile', [ProfileController::class, 'update'])->name('profile.update');
    Route::delete('/profile', [ProfileController::class, 'destroy'])->name('profile.destroy');

    Route::resource('services', ServiceController::class)
        ->only(['index', 'store', 'update', 'destroy']);

    Route::resource('companies', CompanyController::class)
        ->only(['index', 'store', 'update', 'destroy']);

    Route::resource('conventions', ConventionController::class)
        ->only(['index', 'store', 'update', 'destroy']);

        Route::get('/conventions/search', [ConventionController::class, 'search'])->name('conventions.search');
    // If you need the 'edit' route for conventions, and the resource is 'only', keep this:
    // Route::get('/conventions/{convention}/edit', [ConventionController::class, 'edit'])->name('conventions.edit');

    
    // Dedicated search route for patients, using GET for search queries
    Route::get('/patients/search', [PatientController::class, 'search'])->name('patients.search');
    Route::resource('patients', PatientController::class); // Now inherits 'auth' and 'verified' from the group
    

    Route::get('/fiches-navette/{ficheNavette}/show-and-print', [FicheNavetteController::class, 'showAndPrint'])
        ->name('fichesnavette.show_and_print');

    Route::get('/fiches-navette/{ficheNavette}/ticket-pdf', [FicheNavetteController::class, 'generateTicketPdf'])
        ->name('fichesnavette.ticket-pdf');

    Route::post('conventions/import', [ConventionController::class, 'import'])
        ->name('conventions.import');

    Route::resource('fichesnavette', FicheNavetteController::class);

    Route::put('/fichesnavette/{ficheNavette}/status', [FicheNavetteController::class, 'updateStatus'])->name('fichesnavette.updateStatus');

    Route::get('/patients/{patient}/fiches-navette', [FicheNavetteController::class, 'indexForPatient'])
        ->name('patients.fichesnavette.index');

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