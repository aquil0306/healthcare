<?php

/**
 * @OA\OpenApi(
 *
 *     @OA\Info(
 *         title="Healthcare Referral Management API",
 *         version="1.0.0",
 *         description="API for managing healthcare referrals with AI-assisted triage, notifications, and audit logging",
 *
 *         @OA\Contact(
 *             email="support@healthcare.com"
 *         )
 *     ),
 *
 *     @OA\Server(
 *         url="http://localhost:8000",
 *         description="Local API Server"
 *     ),
 *     @OA\Server(
 *         url="https://api.healthcare.com",
 *         description="Production API Server"
 *     ),
 *
 *     @OA\Components(
 *         securitySchemes={
 *
 *             @OA\SecurityScheme(
 *                 securityScheme="apiKey",
 *                 type="apiKey",
 *                 in="header",
 *                 name="X-API-Key",
 *                 description="Hospital API Key Authentication"
 *             ),
 *             @OA\SecurityScheme(
 *                 securityScheme="sanctum",
 *                 type="http",
 *                 scheme="bearer",
 *                 bearerFormat="JWT",
 *                 description="Staff/Admin Token Authentication"
 *             )
 *         },
 *
 *         @OA\Schema(
 *             schema="Referral",
 *             type="object",
 *
 *             @OA\Property(property="id", type="integer", example=123),
 *             @OA\Property(property="patient_id", type="integer", example=45),
 *             @OA\Property(property="hospital_id", type="integer", example=2),
 *             @OA\Property(property="urgency", type="string", enum={"routine", "urgent", "emergency"}, example="urgent"),
 *             @OA\Property(property="status", type="string", enum={"submitted", "assigned", "acknowledged", "completed", "cancelled"}, example="assigned"),
 *             @OA\Property(property="clinical_notes", type="string", example="Patient presents with chest pain"),
 *             @OA\Property(property="department", type="string", nullable=true, example="cardiology"),
 *             @OA\Property(property="ai_confidence_score", type="number", format="float", nullable=true, example=0.95),
 *             @OA\Property(property="processed_at", type="string", nullable=true, example="2 hours ago"),
 *             @OA\Property(property="processed_at_raw", type="string", format="date-time", nullable=true, example="2024-01-15T10:30:00Z"),
 *             @OA\Property(property="assigned_staff_id", type="integer", nullable=true, example=5),
 *             @OA\Property(property="cancellation_reason", type="string", nullable=true),
 *             @OA\Property(property="acknowledged_at", type="string", nullable=true, example="1 hour ago"),
 *             @OA\Property(property="acknowledged_at_raw", type="string", format="date-time", nullable=true),
 *             @OA\Property(property="external_referral_id", type="string", nullable=true, example="EXT-2024-001234"),
 *             @OA\Property(property="created_at", type="string", example="3 hours ago"),
 *             @OA\Property(property="created_at_raw", type="string", format="date-time", example="2024-01-15T08:00:00Z"),
 *             @OA\Property(property="updated_at", type="string", example="1 hour ago"),
 *             @OA\Property(property="updated_at_raw", type="string", format="date-time", example="2024-01-15T10:30:00Z"),
 *             @OA\Property(property="patient", ref="#/components/schemas/Patient"),
 *             @OA\Property(property="hospital", ref="#/components/schemas/Hospital"),
 *             @OA\Property(property="assigned_staff", ref="#/components/schemas/Staff")
 *         ),
 *
 *         @OA\Schema(
 *             schema="Patient",
 *             type="object",
 *
 *             @OA\Property(property="id", type="integer", example=45),
 *             @OA\Property(property="first_name", type="string", example="John"),
 *             @OA\Property(property="last_name", type="string", example="Doe"),
 *             @OA\Property(property="date_of_birth", type="string", format="date", example="1990-01-15"),
 *             @OA\Property(property="date_of_birth_human", type="string", example="34 years ago"),
 *             @OA\Property(property="national_id", type="string", example="123456789"),
 *             @OA\Property(property="insurance_number", type="string", example="INS123456"),
 *             @OA\Property(property="created_at", type="string", example="1 month ago"),
 *             @OA\Property(property="created_at_raw", type="string", format="date-time", example="2023-12-15T10:00:00Z")
 *         ),
 *
 *         @OA\Schema(
 *             schema="Hospital",
 *             type="object",
 *
 *             @OA\Property(property="id", type="integer", example=2),
 *             @OA\Property(property="name", type="string", example="City General Hospital"),
 *             @OA\Property(property="code", type="string", example="CGH001"),
 *             @OA\Property(property="status", type="string", enum={"active", "suspended"}, example="active"),
 *             @OA\Property(property="api_key", type="string", nullable=true, example="abc123..."),
 *             @OA\Property(property="created_at", type="string", example="2 months ago"),
 *             @OA\Property(property="created_at_raw", type="string", format="date-time", example="2023-11-15T10:00:00Z")
 *         ),
 *
 *         @OA\Schema(
 *             schema="Staff",
 *             type="object",
 *
 *             @OA\Property(property="id", type="integer", example=5),
 *             @OA\Property(property="first_name", type="string", example="Jane"),
 *             @OA\Property(property="last_name", type="string", example="Smith"),
 *             @OA\Property(property="role", type="string", enum={"doctor", "coordinator", "admin"}, example="doctor"),
 *             @OA\Property(property="department_id", type="integer", nullable=true, example=1)
 *         )
 *     )
 * )
 */
