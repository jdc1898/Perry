<?php

use App\Http\Controllers\Api\V1\AgentConfigController;
use App\Http\Controllers\Api\V1\AgentReportController;
use App\Http\Middleware\VerifyAgentSignature;
use Illuminate\Support\Facades\Route;

Route::prefix('v1')->middleware(VerifyAgentSignature::class)->group(function () {
    Route::get('agents/{agentId}/config',   [AgentConfigController::class, 'show']);
    Route::post('agents/{agentId}/reports', [AgentReportController::class, 'store']);
});
