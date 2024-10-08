<?php

use App\Http\Controllers\PdfController;
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

// Route::get('/', function () {
//     return view('welcome');
// });

Route::get('/', [PdfController::class, 'index']);
Route::get('/pdfs/{id}', [PdfController::class, 'show'])->name('pdfs.show');
// Route::get('/api/pdf/{id}/pages', [PdfController::class, 'getPages']);
// Route::get('/api/pdf/{bookPDF}/pages', [PdfController::class, 'getPages']);
Route::get('/api/pdf/{bookPDF}/pages', [PdfController::class, 'getPages']);

// Route::view('/api/pdf/{bookPDF}/pages' , 'welcome');

