<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;

/**
 * @OA\Info(
 *     title="Healthcare Referral Management API",
 *     version="1.0.0",
 *     description="API for managing healthcare referrals with AI-assisted triage, notifications, and audit logging",
 *
 *     @OA\Contact(
 *         email="support@healthcare.com"
 *     )
 * )
 *
 * @OA\Server(
 *     url="http://localhost:8000",
 *     description="Local API Server"
 * )
 * @OA\Server(
 *     url="https://api.healthcare.com",
 *     description="Production API Server"
 * )
 *
 * @OA\SecurityScheme(
 *     securityScheme="apiKey",
 *     type="apiKey",
 *     in="header",
 *     name="X-API-Key",
 *     description="Hospital API Key Authentication"
 * )
 * @OA\SecurityScheme(
 *     securityScheme="sanctum",
 *     type="http",
 *     scheme="bearer",
 *     bearerFormat="JWT",
 *     description="Staff/Admin Token Authentication"
 * )
 */
class BaseController extends Controller
{
    //
}
