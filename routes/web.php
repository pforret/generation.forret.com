<?php

use App\Http\Controllers\GenerationController;
use App\Http\Controllers\ProfileController;
use App\Models\Generation;
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
    return view('welcome',[ "generations" => Generation::all()]);
});

Route::get('/dashboard', function () {
    return view('dashboard');
})->middleware(['auth', 'verified'])->name('dashboard');

Route::middleware('auth')->group(function () {
    Route::get('/profile', [ProfileController::class, 'edit'])->name('profile.edit');
    Route::patch('/profile', [ProfileController::class, 'update'])->name('profile.update');
    Route::delete('/profile', [ProfileController::class, 'destroy'])->name('profile.destroy');
});

Route::resource('generation', GenerationController::class)->parameters([
    'generation' => 'generation:slug'
])->only([
    'index', 'show'
])->names([
    'index' =>  'generation.index',
    'show' => 'generation.show'
]);

Route::resource('person', \App\Http\Controllers\PersonController::class)->only([
    'index', 'show'
])->names([
    'index' =>  'person.index',
    'show' => 'person.show'
]);

require __DIR__.'/auth.php';
