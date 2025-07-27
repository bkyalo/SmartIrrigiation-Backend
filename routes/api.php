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
    
    // Public routes
    Route::apiResource('tanks', TankController::class)
        ->only(['index', 'show'])
        ->names('api.tanks');
        
    Route::apiResource('plots', PlotController::class)
        ->only(['index', 'show'])
        ->names('api.plots');
    
    // Public Irrigation routes
    Route::prefix('irrigation')->group(function () {
        // Start manual irrigation for a plot
        Route::post('plots/{plot}/start', [\App\Http\Controllers\Api\IrrigationController::class, 'startManualIrrigation']);
        
        // Schedule one-time irrigation
        Route::post('plots/{plot}/schedule', [\App\Http\Controllers\Api\IrrigationController::class, 'scheduleOneTimeIrrigation']);
        
        // Schedule recurring irrigation
        Route::post('plots/{plot}/schedule-recurring', [\App\Http\Controllers\Api\IrrigationController::class, 'scheduleRecurringIrrigation']);
        
        // Get upcoming irrigation events for a plot
        Route::get('plots/{plot}/upcoming', [\App\Http\Controllers\Api\IrrigationController::class, 'getUpcomingEvents']);
        
        // Get past irrigation events for a plot
        Route::get('plots/{plot}/history', [\App\Http\Controllers\Api\IrrigationController::class, 'getPastEvents']);
        
        // Cancel a scheduled irrigation event
        Route::post('events/{event}/cancel', [\App\Http\Controllers\Api\IrrigationController::class, 'cancelEvent']);
    });
    
    // Protected routes (require authentication)
    Route::middleware(['auth:sanctum'])->group(function () {
        // Current authenticated user
        Route::get('/user', function (Request $request) {
            return $request->user();
        });
        
        // Protected Tank routes
        Route::apiResource('tanks', TankController::class)->except(['index', 'show']);
        
        // Protected Plot routes
        Route::apiResource('plots', PlotController::class)
            ->except(['index', 'show'])
            ->names('api.plots');
        
        // Valve routes
        Route::apiResource('valves', ValveController::class);
        
        // Pump routes
        Route::apiResource('pumps', PumpController::class);
        
        // Sensor routes
        Route::apiResource('sensors', SensorController::class);
        
        // Sensor Reading routes
        Route::apiResource('sensor-readings', SensorReadingController::class)->except(['update', 'destroy']);
        
        // Irrigation Event routes
        Route::apiResource('irrigation-events', IrrigationEventController::class)
            ->except(['update'])
            ->names('api.irrigation-events');
        Route::post('irrigation-events/{irrigationEvent}/start', [IrrigationEventController::class, 'start']);
        Route::post('irrigation-events/{irrigationEvent}/complete', [IrrigationEventController::class, 'complete']);
        Route::post('irrigation-events/{irrigationEvent}/cancel', [IrrigationEventController::class, 'cancel']);
        Route::post('irrigation-events/{irrigationEvent}/stop', [IrrigationEventController::class, 'stop']);
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
