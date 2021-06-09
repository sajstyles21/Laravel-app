<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Api\InviteController;

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
        Route::post('logout', 'InviteController@logout')->name('logout');
    });
    Route::post('send_invite', [InviteController::class,'sendInvite']);
    Route::post('get_user_data', [InviteController::class,'getUserData'])->name('get-user-data');
    Route::post('confirm_pin', [InviteController::class,'confirmPin'])->name('pin-confirm');
});
