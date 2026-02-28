<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Requests\StoreReferralRequest;
use App\Repositories\PatientRepository;
use App\Repositories\ReferralRepository;
use App\Services\AiTriageService;
use App\Services\AuditService;
use Illuminate\Http\JsonResponse;

/**
 * @OA\Tag(
 *     name="Hospital",
 *     description="Hospital API endpoints for submitting referrals"
 * )
 */
class HospitalReferralController extends Controller
{
    public function __construct(
        private PatientRepository $patientRepository,
        private ReferralRepository $referralRepository,
        private AiTriageService $aiTriageService,
        private AuditService $auditService
    ) {}

    /**
     * @OA\Post(
     *     path="/api/v1/hospital/referrals",
     *     operationId="submitReferral",
     *     tags={"Hospital"},
     *     summary="Submit a new referral",
     *     description="Submit a new patient referral. The system will automatically triage the referral using AI and assign it to the appropriate department.",
     *     security={{"apiKey": {}}},
     *
     *     @OA\RequestBody(
     *         required=true,
     *         description="Referral submission data",
     *
     *         @OA\JsonContent(
     *             required={"patient", "urgency", "diagnosis_codes", "clinical_notes"},
     *
     *             @OA\Property(
     *                 property="patient",
     *                 type="object",
     *                 required={"first_name", "last_name", "date_of_birth", "national_id", "insurance_number"},
     *                 description="Patient information",
     *                 @OA\Property(property="first_name", type="string", maxLength=255, example="John", description="Patient's first name"),
     *                 @OA\Property(property="last_name", type="string", maxLength=255, example="Doe", description="Patient's last name"),
     *                 @OA\Property(property="date_of_birth", type="string", format="date", example="1990-01-15", description="Patient's date of birth (YYYY-MM-DD)"),
     *                 @OA\Property(property="national_id", type="string", maxLength=255, example="123456789", description="Patient's national ID number"),
     *                 @OA\Property(property="insurance_number", type="string", maxLength=255, example="INS123456", description="Patient's insurance number")
     *             ),
     *             @OA\Property(property="urgency", type="string", enum={"routine", "urgent", "emergency"}, example="urgent", description="Urgency level of the referral"),
     *             @OA\Property(
     *                 property="diagnosis_codes",
     *                 type="array",
     *                 minItems=1,
     *                 description="Array of ICD-10 diagnosis codes",
     *
     *                 @OA\Items(type="string", maxLength=20, example="I10", description="ICD-10 code")
     *             ),
     *
     *             @OA\Property(property="clinical_notes", type="string", example="Patient presents with chest pain and shortness of breath. Requires immediate cardiology consultation.", description="Clinical notes and observations"),
     *             @OA\Property(property="external_referral_id", type="string", maxLength=255, example="EXT-2024-001234", description="External referral ID from the hospital system (optional, used for duplicate detection)")
     *         )
     *     ),
     *
     *     @OA\Response(
     *         response=201,
     *         description="Referral created successfully",
     *
     *         @OA\JsonContent(
     *
     *             @OA\Property(property="success", type="boolean", example=true),
     *             @OA\Property(property="message", type="string", example="Referral submitted successfully"),
     *             @OA\Property(
     *                 property="data",
     *                 type="object",
     *                 @OA\Property(property="referral_id", type="integer", example=123, description="ID of the created referral"),
     *                 @OA\Property(property="status", type="string", example="submitted", description="Current status of the referral")
     *             )
     *         )
     *     ),
     *
     *     @OA\Response(
     *         response=409,
     *         description="Duplicate referral",
     *
     *         @OA\JsonContent(
     *
     *             @OA\Property(property="success", type="boolean", example=false),
     *             @OA\Property(property="message", type="string", example="Duplicate referral already exists"),
     *             @OA\Property(property="referral_id", type="integer", example=100, description="ID of the existing referral")
     *         )
     *     ),
     *
     *     @OA\Response(
     *         response=422,
     *         description="Validation error",
     *
     *         @OA\JsonContent(
     *
     *             @OA\Property(property="message", type="string", example="The patient.first_name field is required."),
     *             @OA\Property(
     *                 property="errors",
     *                 type="object",
     *                 @OA\Property(
     *                     property="patient.first_name",
     *                     type="array",
     *
     *                     @OA\Items(type="string", example="The patient.first_name field is required.")
     *                 )
     *             )
     *         )
     *     ),
     *
     *     @OA\Response(
     *         response=401,
     *         description="Unauthorized - Invalid or missing API key",
     *
     *         @OA\JsonContent(
     *
     *             @OA\Property(property="message", type="string", example="Unauthorized")
     *         )
     *     )
     * )
     */
    public function store(StoreReferralRequest $request): JsonResponse
    {
        $hospital = $request->get('hospital');
        $data = $request->validated();

        if (isset($data['external_referral_id'])) {
            $existing = $this->referralRepository->findByExternalId(
                $hospital->id,
                $data['external_referral_id']
            );

            if ($existing) {
                return response()->json([
                    'success' => false,
                    'message' => 'Duplicate referral already exists',
                    'referral_id' => $existing->id,
                ], 409);
            }
        }

        $patient = $this->patientRepository->findOrCreateByNationalId($data['patient']);

        $referral = $this->referralRepository->create([
            'patient_id' => $patient->id,
            'hospital_id' => $hospital->id,
            'urgency' => $data['urgency'],
            'clinical_notes' => $data['clinical_notes'],
            'external_referral_id' => $data['external_referral_id'] ?? null,
            'status' => 'submitted',
        ]);

        foreach ($data['diagnosis_codes'] as $code) {
            $referral->icd10Codes()->create(['code' => $code]);
        }

        $this->auditService->logChange($referral, 'created', null, null, $referral->toArray());
        $this->aiTriageService->triageReferral($referral);

        return response()->json([
            'success' => true,
            'message' => 'Referral submitted successfully',
            'data' => [
                'referral_id' => $referral->id,
                'status' => $referral->status,
            ],
        ], 201);
    }
}
