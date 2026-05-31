<?php

use App\Http\Controllers\Api\V1\DocumentDownloadController;
use App\Http\Controllers\DocumentExportDownloadController;
use App\Http\Controllers\WidgetDemoController;
use Illuminate\Support\Facades\Route;

Route::redirect('/', '/app');

Route::middleware(['auth', 'staff'])->group(function (): void {
    Route::get('/embed-demo', [WidgetDemoController::class, 'show'])->name('widget.demo');
    Route::get('/embed-demo/frame', [WidgetDemoController::class, 'frame'])->name('widget.demo.frame');
});

Route::view('/app/login', 'app')->name('login');

Route::middleware(['auth', 'staff'])->group(function (): void {
    Route::get('/documents/{document}/download', DocumentDownloadController::class)
        ->name('documents.download');

    Route::get('/document-exports/{exportId}/download', DocumentExportDownloadController::class)
        ->name('document-exports.download');

    Route::view('/app/{any?}', 'app')
        ->where('any', '.*')
        ->name('spa');
});
