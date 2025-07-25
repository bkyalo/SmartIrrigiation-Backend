<?php

use App\Http\Controllers\Api\ApprovalRequestController;
use App\Http\Controllers\Api\IrrigationEventController;
use App\Http\Controllers\Api\PlotController;
use App\Http\Controllers\Api\PumpController;
use App\Http\Controllers\Api\ScheduleController;
use App\Http\Controllers\Api\SensorController;
use App\Http\Controllers\Api\SensorReadingController;
use App\Http\Controllers\Api\TankController;
use App\Http\Controllers\Api\ValveController;
use App\Http\Controllers\Auth\AuthenticatedSessionController;
use App\Http\Controllers\Api\Auth\RegisterController;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| API Routes
|--------------------------------------------------------------------------
|
| Here is where you can register API routes for your application. These
| routes are loaded by the RouteServiceProvider and all of them will
| be assigned to the "api" middleware group. Make something great!
|
*/

// API versioning
Route::prefix('v1')->group(function () {
    // Public routes (if any)
    Route::get('/status', function () {
        return response()->json([
            'status' => 'ok',
            'version' => '1.0.0',
            'timestamp' => now()->toDateTimeString(),
        ]);
    });

    // Authentication routes
    Route::post('/login', [AuthenticatedSessionController::class, 'store']);
    Route::post('/register', [RegisterController::class, 'register']);
    Route::post('/logout', [AuthenticatedSessionController::class, 'destroy'])
        ->middleware('auth:sanctum');
    
    // Protected routes (require authentication)
    Route::middleware(['auth:sanctum'])->group(function () {
        // Current authenticated user
        Route::get('/user', function (Request $request) {
            return $request->user();
        });
        
        // Tank routes
        Route::apiResource('tanks', TankController::class);
        
        // Plot routes
        Route::apiResource('plots', PlotController::class);
        
        // Valve routes
        Route::apiResource('valves', ValveController::class);
        
        // Pump routes
        Route::apiResource('pumps', PumpController::class);
        
        // Sensor routes
        Route::apiResource('sensors', SensorController::class);
        
        // Sensor Reading routes
        Route::apiResource('sensor-readings', SensorReadingController::class)->except(['update', 'destroy']);
        
        // Irrigation Event routes
        Route::apiResource('irrigation-events', IrrigationEventController::class)->except(['update']);
        Route::post('irrigation-events/{irrigationEvent}/start', [IrrigationEventController::class, 'start']);
        Route::post('irrigation-events/{irrigationEvent}/complete', [IrrigationEventController::class, 'complete']);
        Route::post('irrigation-events/{irrigationEvent}/cancel', [IrrigationEventController::class, 'cancel']);
        Route::get('irrigation-events/stats/plot/{plot}', [IrrigationEventController::class, 'stats']);
        
        // Schedule routes
        Route::apiResource('schedules', ScheduleController::class);
        Route::post('schedules/{schedule}/pause', [ScheduleController::class, 'pause']);
        Route::post('schedules/{schedule}/resume', [ScheduleController::class, 'resume']);
        Route::post('schedules/{schedule}/complete', [ScheduleController::class, 'complete']);
        Route::get('schedules/{schedule}/next-run', [ScheduleController::class, 'nextRun']);
        
        // Approval Request routes
        Route::apiResource('approval-requests', ApprovalRequestController::class)->except(['update']);
        Route::post('approval-requests/{approvalRequest}/approve', [ApprovalRequestController::class, 'approve']);
        Route::post('approval-requests/{approvalRequest}/reject', [ApprovalRequestController::class, 'reject']);
        Route::post('approval-requests/{approvalRequest}/cancel', [ApprovalRequestController::class, 'cancel']);
        Route::get('approval-requests/stats', [ApprovalRequestController::class, 'stats']);
        Route::get('approval-requests/action-types', [ApprovalRequestController::class, 'actionTypes']);
    });
});
