<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

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
        Route::post('logout', 'UserController@logout')->name('logout');
    });
    Route::post('login', 'UserController@login')->name('login');
});
