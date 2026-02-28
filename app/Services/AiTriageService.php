<?php

namespace App\Services;

use App\Models\AiTriageLog;
use App\Models\Referral;
use App\Repositories\ReferralRepository;
use Illuminate\Support\Facades\Log;
use Laravel\Ai\AiManager;

class AiTriageService
{
    private ReferralRepository $referralRepository;

    private AuditService $auditService;

    private DepartmentSuggestionService $departmentSuggestionService;

    private AiManager $aiManager;

    private int $maxRetries = 3;

    private int $retryDelay = 5; // seconds

    public function __construct(
        ReferralRepository $referralRepository,
        AuditService $auditService,
        DepartmentSuggestionService $departmentSuggestionService,
        AiManager $aiManager
    ) {
        $this->referralRepository = $referralRepository;
        $this->auditService = $auditService;
        $this->departmentSuggestionService = $departmentSuggestionService;
        $this->aiManager = $aiManager;
    }

    public function triageReferral(Referral $referral): void
    {
        // Load ICD-10 codes with their relationships for department suggestions
        $referral->load(['icd10Codes.icd10Code.departments']);

        $inputData = [
            'diagnosis_codes' => $referral->icd10Codes->pluck('code')->toArray(),
            'clinical_notes' => $referral->clinical_notes,
        ];

        $log = AiTriageLog::create([
            'referral_id' => $referral->id,
            'input_data' => $inputData,
            'status' => 'retrying',
            'retry_count' => 0,
        ]);

        $this->attemptTriage($referral, $log, 0);
    }

    private function attemptTriage(Referral $referral, AiTriageLog $log, int $attempt): void
    {
        try {
            // Build the prompt for AI triage assessment
            $prompt = $this->buildTriagePrompt(
                $log->input_data['diagnosis_codes'],
                $log->input_data['clinical_notes'],
                $referral
            );

            // Get the provider name from config
            $providerName = config('ai.default', 'openai');
            $provider = $this->aiManager->textProvider($providerName);

            // Create an agent with instructions
            $agent = new \Laravel\Ai\AnonymousAgent(
                'You are a medical triage AI assistant. Analyze referral information and provide structured JSON responses only. Be precise and accurate in your medical assessments.',
                [],
                []
            );

            // Create agent prompt
            $agentPrompt = new \Laravel\Ai\Prompts\AgentPrompt(
                $agent,
                $prompt,
                [],
                $provider,
                $provider->defaultTextModel(),
                30 // timeout in seconds
            );

            // Get AI response
            $response = $provider->prompt($agentPrompt);

            // Get the response content (TextResponse has a public $text property)
            $content = $response->text;
            $data = $this->parseAiResponse($content);

            $log->update([
                'output_data' => [
                    'raw_response' => $content,
                    'parsed_data' => $data,
                ],
                'status' => 'success',
            ]);

            $oldStatus = $referral->status;
            $oldUrgency = $referral->urgency;
            $oldDepartment = $referral->department;

            $referral->update([
                'urgency' => $data['urgency'] ?? $referral->urgency,
                'department' => $data['suggested_department'] ?? $referral->department,
                'ai_confidence_score' => $data['confidence_score'] ?? null,
                'processed_at' => now(),
                'status' => 'triaged',
            ]);

            // Log status change
            if ($oldStatus !== 'triaged') {
                $this->auditService->logChange(
                    $referral,
                    'status_changed',
                    'status',
                    $oldStatus,
                    'triaged'
                );
            }

            // Log urgency change if it changed
            if ($oldUrgency !== $referral->urgency) {
                $this->auditService->logChange(
                    $referral,
                    'urgency_changed',
                    'urgency',
                    $oldUrgency,
                    $referral->urgency
                );
            }

            // Log department change if it changed
            if ($oldDepartment !== $referral->department) {
                $this->auditService->logChange(
                    $referral,
                    'department_changed',
                    'department',
                    $oldDepartment,
                    $referral->department
                );
            }

            // Trigger notification after successful triage
            event(new \App\Events\ReferralTriaged($referral));
        } catch (\Exception $e) {
            $log->update([
                'retry_count' => $attempt + 1,
                'error_message' => $e->getMessage(),
            ]);

            if ($attempt < $this->maxRetries) {
                $log->update(['status' => 'retrying']);

                // Queue retry
                dispatch(new \App\Jobs\RetryAiTriage($referral, $log, $attempt + 1))
                    ->delay(now()->addSeconds($this->retryDelay * ($attempt + 1)));
            } else {
                $log->update(['status' => 'failed']);

                // Fallback: set to routine urgency and default department
                $oldStatus = $referral->status;
                $oldUrgency = $referral->urgency;
                $oldDepartment = $referral->department;

                $referral->update([
                    'urgency' => 'routine',
                    'department' => 'general',
                    'processed_at' => now(),
                    'status' => 'triaged',
                ]);

                // Log status change
                if ($oldStatus !== 'triaged') {
                    $this->auditService->logChange(
                        $referral,
                        'status_changed',
                        'status',
                        $oldStatus,
                        'triaged',
                        ['reason' => 'AI triage failed, using fallback values']
                    );
                }

                // Log urgency change if it changed
                if ($oldUrgency !== 'routine') {
                    $this->auditService->logChange(
                        $referral,
                        'urgency_changed',
                        'urgency',
                        $oldUrgency,
                        'routine',
                        ['reason' => 'AI triage failed, using fallback']
                    );
                }

                // Log department change if it changed
                if ($oldDepartment !== 'general') {
                    $this->auditService->logChange(
                        $referral,
                        'department_changed',
                        'department',
                        $oldDepartment,
                        'general',
                        ['reason' => 'AI triage failed, using fallback']
                    );
                }
            }
        }
    }

    public function retryTriage(Referral $referral, AiTriageLog $log, int $attempt): void
    {
        $this->attemptTriage($referral, $log, $attempt);
    }

    /**
     * Build the prompt for AI triage assessment
     */
    private function buildTriagePrompt(array $diagnosisCodes, string $clinicalNotes, ?Referral $referral = null): string
    {
        $codesList = implode(', ', $diagnosisCodes);

        // Get department suggestions based on ICD-10 code mappings
        $departmentContext = '';
        if ($referral) {
            $suggestions = $this->departmentSuggestionService->suggestDepartmentsForReferral($referral);
            if ($suggestions->isNotEmpty()) {
                $departmentContext = "\n\nSuggested Departments (based on ICD-10 code mappings):\n";
                foreach ($suggestions->take(3) as $suggestion) {
                    $dept = $suggestion['department'];
                    $confidence = number_format($suggestion['confidence'] * 100, 1);
                    $primary = $suggestion['is_primary'] ? ' (PRIMARY)' : '';
                    $departmentContext .= "- {$dept->name} (Code: {$dept->code}) - Confidence: {$confidence}%{$primary}\n";
                }
            }
        }

        // Get list of available departments for the prompt
        $availableDepartments = \App\Models\Department::where('is_active', true)
            ->pluck('name')
            ->map(fn ($name) => strtolower($name))
            ->implode(', ');

        return <<<PROMPT
Analyze the following medical referral and provide a JSON response with:
1. urgency: one of "routine", "urgent", or "emergency"
2. suggested_department: department name from available departments (use exact name, lowercase)
3. confidence_score: a decimal between 0 and 1 indicating confidence in the assessment
4. reasoning: brief explanation of the assessment

Diagnosis Codes (ICD-10): {$codesList}
{$departmentContext}
Available Departments: {$availableDepartments}

Clinical Notes:
{$clinicalNotes}

Respond ONLY with valid JSON in this exact format:
{
    "urgency": "routine|urgent|emergency",
    "suggested_department": "department_name_from_available_list",
    "confidence_score": 0.0-1.0,
    "reasoning": "brief explanation"
}
PROMPT;
    }

    /**
     * Parse AI response and extract structured data
     */
    private function parseAiResponse(string $content): array
    {
        // Try to extract JSON from the response
        // AI might return JSON wrapped in markdown code blocks or with extra text
        $jsonMatch = [];
        if (preg_match('/```json\s*(\{.*?\})\s*```/s', $content, $jsonMatch)) {
            $content = $jsonMatch[1];
        } elseif (preg_match('/\{.*\}/s', $content, $jsonMatch)) {
            $content = $jsonMatch[0];
        }

        $data = json_decode($content, true);

        if (! is_array($data)) {
            // Fallback: try to extract values using regex
            $urgency = $this->extractValue($content, 'urgency', ['routine', 'urgent', 'emergency']);
            $department = $this->extractValue($content, 'suggested_department', ['cardiology', 'neurology', 'orthopedics', 'general']);
            $confidence = $this->extractConfidenceScore($content);

            return [
                'urgency' => $urgency ?? 'routine',
                'suggested_department' => $department ?? 'general',
                'confidence_score' => $confidence ?? 0.5,
                'reasoning' => 'Parsed from AI response',
            ];
        }

        return [
            'urgency' => $data['urgency'] ?? 'routine',
            'suggested_department' => $data['suggested_department'] ?? 'general',
            'confidence_score' => isset($data['confidence_score']) ? (float) $data['confidence_score'] : 0.5,
            'reasoning' => $data['reasoning'] ?? 'AI assessment',
        ];
    }

    /**
     * Extract a value from AI response using regex
     */
    private function extractValue(string $content, string $key, array $allowedValues): ?string
    {
        // Try JSON-like patterns
        if (preg_match('/"?'.$key.'"?\s*[:=]\s*"([^"]+)"/i', $content, $matches)) {
            $value = strtolower(trim($matches[1]));
            if (in_array($value, $allowedValues)) {
                return $value;
            }
        }

        // Try to find the value in the content
        foreach ($allowedValues as $value) {
            if (stripos($content, $value) !== false) {
                return $value;
            }
        }

        return null;
    }

    /**
     * Extract confidence score from AI response
     */
    private function extractConfidenceScore(string $content): ?float
    {
        // Try to find confidence_score in JSON format
        if (preg_match('/"confidence_score"\s*:\s*([0-9.]+)/i', $content, $matches)) {
            $score = (float) $matches[1];
            // Normalize if it's a percentage (0-100) to decimal (0-1)
            if ($score > 1) {
                $score = $score / 100;
            }

            return min(1.0, max(0.0, $score));
        }

        // Try to find any decimal between 0 and 1
        if (preg_match('/\b0?\.\d+\b/', $content, $matches)) {
            return (float) $matches[0];
        }

        return null;
    }
}
