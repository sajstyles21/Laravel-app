<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Api\InviteController;
use App\Http\Controllers\Api\UserController;

/*
|--------------------------------------------------------------------------
| API Routes
|--------------------------------------------------------------------------
|
| Here is where you can register API routes for your application. These
| routes are loaded by the RouteServiceProvider within a group which
| is assigned the "api" middleware group. Enjoy building your API!
|
*/

Route::get('documentation', function () {
    return view('vendor.l5-swagger.index');
});

Route::namespace('Api')->group(function () {
    Route::middleware(['auth:api'])->group(function () {
        Route::post('profile_update', [UserController::class,'profileUpdate']);
        Route::post('logout', [UserController::class,'logout']);
    });
    Route::post('login', [UserController::class,'login']);
    Route::post('send_invite', [InviteController::class,'sendInvite']);
    Route::get('get_user_data/{token}', [InviteController::class,'getUserData'])->name('get-user-data');
    Route::get('confirm_pin/{token}', [InviteController::class,'confirmPin'])->name('confirm-pin');
});
