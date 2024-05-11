<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\AuthController;
use App\Http\Controllers\ClothesController;
use App\Http\Controllers\StorageController;


//TEST VIEW
Route::prefix('page')->group(function () {
    Route::get('/register', function () {
        return view('test-endpoint.authe');
    })->name('page-register');

    Route::get('/login', function () {
        return view('test-endpoint.logine');
    })->name('page-login');

    Route::get('/testing', function () {
        return view('test-endpoint.test-func');
    })->name('page-test');
});

Route::get('/', function () {
    return view('Guest.home', ['title' => 'Home']);
})->name('home');

Route::get('/login', function () {
    return view('Guest.login', ['title' => 'Login']);
})->name('login');

Route::get('/register', function () {
    return view('Guest.register', ['title' => 'Register']);
})->name('register');

Route::get('/dashboard', function () {
    return view('dashboard', ['title' => 'Dashboard']);
})->name('dashboard');

//user dan admin
Route::get('/dashboard/profile', function () {
    return view('profile', ['title' => 'Profil']);
})->name('profile');

//user
Route::get('/dashboard/edit_profil', function () {
    return view('User.edit_profil', ['title' => 'Edit Profil']);
})->name('Edit Profil');

Route::get('/dashboard/ubah_password', function () {
    return view('edit_profil', ['title' => 'Ubah Password']);
})->name('Ubah Password');

//admin
Route::get('/dashboard/data_pengguna', function () {
    return view('Admin.data_pengguna', ['title' => 'Data Pengguna']);
})->name('Data Pengguna');

Route::get('/dashboard/data_pakaian', function () {
    return view('Clothes.data_pakaian', ['title' => 'Data Pakaian']);
})->name('Data Pakaian');

Route::get('/dashboard/data_pakaian/tambah', function () {
    return view('Clothes.tambah_pakaian', ['title' => 'Tambah Pakaian']);
})->name('Tambah Pakaian');

Route::get('/dashboard/data_pakaian/edit/{id}', function ($id) {
    return view('Clothes.edit_pakaian', ['title' => 'Edit Pakaian', 'id' => $id]);
})->name('Edit Pakaian');


//CONTROLLERS

//Auth
Route::post('/signup', [AuthController::class, 'register']);
Route::post('/signin', [AuthController::class, 'login']);
Route::get('/logout', [AuthController::class, 'logout']);
Route::get('/email/verify/{id}', [AuthController::class, 'mailVerification'])->name('verifyMail');
Route::any('/test', function () {
    return response()->json([
        'data' => 'aaaaaaaaaa'
    ]);
})->middleware('isAdmin');

//ADMIN ===========================================================================================
//Clothes
Route::group([
    'prefix' => 'clothes'
], function () {
    Route::post('/add', [ClothesController::class, 'addClothes']);
    Route::post('/edit/{cloth_id}', [ClothesController::class, 'editClothes']);
    Route::post('/edit/stock/{cloth_id}/{storage_id}', [ClothesController::class, 'editStock']);
    Route::get('/quantity/{id}', [ClothesController::class, 'findClothWithTotalQuantity']);
    Route::get('/delete/{id}', [ClothesController::class, 'deleteClothes']);
    Route::get('/', [ClothesController::class, 'getAllClothes']);
    Route::get('/{id}', [ClothesController::class, 'getClothesbyId']);
});

//Storage
Route::group([
    'prefix' => 'storage'
], function () {
    Route::post('/add', [StorageController::class, 'addStorage']);
    Route::post('/edit/{cloth_id}', [StorageController::class, 'editStorage']);
    Route::get('/delete/{id}', [StorageController::class, 'deleteStorage']);
    Route::get('/', [StorageController::class, 'getAllStorage']);
    Route::get('/{id}', [StorageController::class, 'getStoragebyId']);
});
