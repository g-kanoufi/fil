<?php

use App\Http\Controllers\Api\V1\DocumentDownloadController;
use App\Http\Controllers\DocumentExportDownloadController;
use App\Http\Controllers\WidgetDemoController;
use Illuminate\Support\Facades\Route;

/** Legacy staff URLs (bookmarks, notification merge tags). */
Route::get('/app', fn () => redirect('/', 301));
Route::get('/app/login', fn () => redirect('/login', 301));
Route::get('/app/{any}', function (string $any) {
    return redirect('/'.ltrim($any, '/'), 301);
})->where('any', '.+');

Route::view('/login', 'app')->name('login');

Route::view('/portal/login', 'app')->name('portal.login');
Route::view('/portal/{any?}', 'app')
    ->where('any', '.*')
    ->name('portal.spa');

Route::middleware(['auth', 'staff'])->group(function (): void {
    Route::redirect('/embed-demo', '/settings/widget/demo')->name('widget.demo');
    Route::get('/embed-demo/inline', [WidgetDemoController::class, 'inline'])->name('widget.demo.inline');
    Route::get('/embed-demo/frame', [WidgetDemoController::class, 'frame'])->name('widget.demo.frame');
});

Route::middleware(['auth', 'staff'])->group(function (): void {
    Route::get('/documents/{document}/download', DocumentDownloadController::class)
        ->name('documents.download');

    Route::get('/document-exports/{exportId}/download', DocumentExportDownloadController::class)
        ->name('document-exports.download');

    Route::view('/{any?}', 'app')
        ->where('any', '.*')
        ->name('spa');
});
