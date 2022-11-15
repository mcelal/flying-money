<?php

use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| Web Routes
|--------------------------------------------------------------------------
|
| Here is where you can register web routes for your application. These
| routes are loaded by the RouteServiceProvider within a group which
| contains the "web" middleware group. Now create something great!
|
*/

Route::get('/', function () {
    return view('welcome');
});

Route::get('auth/google', [\App\Http\Controllers\API\SocialAuthController::class, 'googleRedirect']);
Route::get('auth/google/callback', [\App\Http\Controllers\API\SocialAuthController::class, 'loginWithGoogle']);

Route::middleware([
    'auth:sanctum',
    config('jetstream.auth_session'),
    'verified',
    \App\Http\Middleware\CheckUserDocumentId::class
])->group(function () {
    Route::get('/dashboard', function () {
        $tcmb = new \App\Services\TcmbService();
        $salary = \Illuminate\Support\Facades\Auth::user()->salary;

        $summary = [
            'USD' => $tcmb->calcSalary($salary, 'USD'),
            'EUR' => $tcmb->calcSalary($salary, 'EUR'),
            'GBP' => $tcmb->calcSalary($salary, 'GBP'),
        ];

        return view('dashboard', compact('summary'));
    })->name('dashboard');

    Route::get('/set-document', function () {
        return "belge id kaydet \n<br> <a href='".\route('dashboard')."'>Dashboard</a>";
    })->name('set-document');

    Route::get('/sheet', function () {
        $sheet = new \App\Services\SheetsServices();
        $sheet->readSheet();

        $sheet->createPage(\Illuminate\Support\Str::random(3));
    });
});
