<?php

use App\Http\Controllers\Api\V1\AgentConfigController;
use App\Http\Controllers\Api\V1\AgentReportController;
use App\Http\Controllers\Binary\ApiUploadBinaryController;
use App\Http\Middleware\VerifyAgentSignature;
use Illuminate\Support\Facades\Route;

// CI uploads compiled agent binary here (authenticated via bearer token)
Route::post('/binary', ApiUploadBinaryController::class);

Route::prefix('v1')->middleware(VerifyAgentSignature::class)->group(function () {
    Route::get('agents/{agentId}/config', AgentConfigController::class);
    Route::post('agents/{agentId}/reports', AgentReportController::class);
});
