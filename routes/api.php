<?php

use App\Http\Controllers\Api\V1\AdminAuditLogController;
use App\Http\Controllers\Api\V1\AdminDepartmentController;
use App\Http\Controllers\Api\V1\AdminHospitalController;
use App\Http\Controllers\Api\V1\AdminIcd10CodeController;
use App\Http\Controllers\Api\V1\AdminNotificationController;
use App\Http\Controllers\Api\V1\AdminPatientController;
use App\Http\Controllers\Api\V1\AdminPermissionController;
use App\Http\Controllers\Api\V1\AdminReferralController;
use App\Http\Controllers\Api\V1\AdminRoleController;
use App\Http\Controllers\Api\V1\AdminStaffController;
use App\Http\Controllers\Api\V1\AuthController;
use App\Http\Controllers\Api\V1\HospitalReferralController;
use App\Http\Controllers\Api\V1\NotificationController;
use App\Http\Controllers\Api\V1\ReportingController;
use App\Http\Controllers\Api\V1\StaffReferralController;
use App\Http\Middleware\EnsureApiKey;
use App\Http\Middleware\EnsureRole;
use Illuminate\Support\Facades\Route;

Route::prefix('v1')->group(function () {
    // Public routes
    Route::post('/auth/login', [AuthController::class, 'login']);

    // Hospital routes (API Key authentication)
    Route::middleware([EnsureApiKey::class])->prefix('hospital')->group(function () {
        Route::post('/referrals', [HospitalReferralController::class, 'store']);
    });

    // Authenticated routes (Sanctum)
    Route::middleware(['auth:sanctum'])->group(function () {
        Route::post('/auth/logout', [AuthController::class, 'logout']);
        Route::get('/auth/user', [AuthController::class, 'user']);

        // Staff routes
        Route::prefix('staff')->middleware([EnsureRole::class.':doctor,coordinator'])->group(function () {
            Route::get('/referrals', [StaffReferralController::class, 'index']);
            Route::get('/referrals/{id}', [StaffReferralController::class, 'show']);
            Route::post('/referrals/{id}/acknowledge', [StaffReferralController::class, 'acknowledge']);
            Route::post('/referrals/{id}/complete', [StaffReferralController::class, 'complete']);
            Route::post('/referrals/{id}/update-status', [StaffReferralController::class, 'updateStatus']);
        });

        // Admin routes
        Route::prefix('admin')->middleware([EnsureRole::class.':admin'])->group(function () {
            // Referrals
            Route::get('/referrals', [AdminReferralController::class, 'index']);
            Route::get('/referrals/{referral}', [AdminReferralController::class, 'show']);
            Route::post('/referrals/{referral}/assign', [AdminReferralController::class, 'assign']);
            Route::post('/referrals/{referral}/cancel', [AdminReferralController::class, 'cancel']);

            // Hospitals
            Route::apiResource('hospitals', AdminHospitalController::class);
            Route::post('/hospitals/{hospital}/regenerate-api-key', [AdminHospitalController::class, 'regenerateApiKey']);

            // Patients
            Route::apiResource('patients', AdminPatientController::class);

            // Staff
            Route::apiResource('staff', AdminStaffController::class);

            // Roles & Permissions
            Route::apiResource('roles', AdminRoleController::class);
            Route::apiResource('permissions', AdminPermissionController::class);
            Route::post('/users/{user}/assign-role', [AdminStaffController::class, 'assignRole']);
            Route::post('/users/{user}/assign-permission', [AdminStaffController::class, 'assignPermission']);

            // ICD-10 Codes & Departments
            Route::apiResource('icd10-codes', AdminIcd10CodeController::class);
            Route::apiResource('departments', AdminDepartmentController::class);
            Route::get('/departments/{department}/icd10-codes', [AdminDepartmentController::class, 'getIcd10Codes']);
            Route::post('/departments/{department}/icd10-codes', [AdminDepartmentController::class, 'syncIcd10Codes']);

            // Audit Logs
            Route::get('/audit-logs', [AdminAuditLogController::class, 'index']);
            Route::get('/audit-logs/{auditLog}', [AdminAuditLogController::class, 'show']);

            // Notifications
            Route::get('/notifications', [AdminNotificationController::class, 'index']);
            Route::get('/notifications/{notification}', [AdminNotificationController::class, 'show']);

            // Reports
            Route::get('/reports/statistics', [ReportingController::class, 'statistics']);
        });

        // Common routes
        Route::get('/notifications', [NotificationController::class, 'index']);
        Route::post('/notifications/{notification}/acknowledge', [NotificationController::class, 'acknowledge']);
    });
});
