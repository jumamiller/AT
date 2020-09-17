<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\AfricastalkingController;
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
Route::get('/sms',[AfricastalkingController::class,'sms']);
Route::get('/airtime',[AfricastalkingController::class,'airtime']);
Route::get('/content',[AfricastalkingController::class,'content']);
Route::get('/voice',[AfricastalkingController::class,'voice']);
Route::get('token',[AfricastalkingController::class,'token']);
