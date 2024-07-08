<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\LoginController;
use App\Http\Controllers\AlertController;
use App\Http\Controllers\DashboardController;

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
Route::post('/login',[LoginController::class,'login']);

Route::middleware('auth:sanctum')->group( function () {
    Route::get('/alerts',[AlertController::class,'alerts']);
    Route::post('/showalert',[AlertController::class,'showalert']);
    Route::get('/reportfraud',[AlertController::class,'reportFruad']);
    Route::post('/saveFraudRes',[AlertController::class,'saveFraudRes']);
    Route::get('/alertSetting',[AlertController::class,'alertSetting']);
    Route::post('/saveAlertSetting',[AlertController::class,'saveAlertSetting']);
    Route::get('/dailystates', [DashboardController::class,'dailystates']);
    Route::get('/weeklyReport', [DashboardController::class,'weeklyReport']);
    Route::get('/yearlyReport', [DashboardController::class,'yearlyReport']);
});

Route::middleware('auth:api')->get('/user', function (Request $request) {
    return $request->user();
});
