<?php

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

Route::post('/attendance/upload', function (Request $request) {
    $data = $request->input('attendance', []);

    if (empty($data)) {
        return response()->json(['message' => 'No data received'], 400);
    }

    foreach ($data as $record) {
        // Save into your attendance table
        \App\Models\AttendanceRecord::updateOrCreate(
            [
                'device_uid' => $record['id'],
                'timestamp' => $record['timestamp'],
            ],
            [
                'status' => 'present', // customize
                'user_id' => 1,        // map your user if needed
            ]
        );
    }

    return response()->json(['message' => 'Data uploaded successfully'], 200);
});
Route::middleware('auth:sanctum')->get('/user', function (Request $request) {
    return $request->user();
});

// API route to get users for employee selection
Route::get('/users', function () {
    $users = \App\Models\User::select('id', 'name')
        ->orderBy('name')
        ->get();
    
    return response()->json($users);
});

// Email Sync Routes
Route::prefix('emails')->group(function () {
    Route::get('/sync', [\App\Http\Controllers\EmailSyncController::class, 'sync'])->name('emails.sync');
    Route::get('/status', [\App\Http\Controllers\EmailSyncController::class, 'status'])->name('emails.status');
});
