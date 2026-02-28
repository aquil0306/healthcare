<?php

namespace App\Services;

use App\Models\Department;
use App\Models\Icd10Code;
use App\Models\Referral;
use Illuminate\Support\Collection;

class DepartmentSuggestionService
{
    /**
     * Suggest departments based on ICD-10 codes in a referral
     * 
     * @param Referral $referral
     * @return Collection Collection of Department models with suggestion metadata
     */
    public function suggestDepartmentsForReferral(Referral $referral): Collection
    {
        $icd10Codes = $referral->icd10Codes()->with('icd10Code')->get();
        
        if ($icd10Codes->isEmpty()) {
            return collect();
        }

        $departmentScores = [];
        
        foreach ($icd10Codes as $referralIcd10Code) {
            $icd10Code = $referralIcd10Code->icd10Code;
            
            if (!$icd10Code) {
                continue;
            }

            // Get all departments for this ICD-10 code
            $departments = $icd10Code->departments()->get();
            
            foreach ($departments as $department) {
                $departmentId = $department->id;
                
                if (!isset($departmentScores[$departmentId])) {
                    $departmentScores[$departmentId] = [
                        'department' => $department,
                        'score' => 0,
                        'is_primary' => false,
                        'matched_codes' => [],
                    ];
                }
                
                // Calculate score based on priority and primary status
                $priority = $department->pivot->priority ?? 1;
                $isPrimary = $department->pivot->is_primary ?? false;
                
                // Primary departments get higher score
                $score = $isPrimary ? 10 : (11 - $priority); // Lower priority number = higher score
                
                $departmentScores[$departmentId]['score'] += $score;
                
                if ($isPrimary) {
                    $departmentScores[$departmentId]['is_primary'] = true;
                }
                
                $departmentScores[$departmentId]['matched_codes'][] = [
                    'code' => $icd10Code->code,
                    'description' => $icd10Code->description,
                    'priority' => $priority,
                    'is_primary' => $isPrimary,
                ];
            }
        }
        
        // Sort by score (descending) and return departments
        usort($departmentScores, function ($a, $b) {
            if ($a['score'] === $b['score']) {
                // If scores are equal, prioritize primary departments
                return $b['is_primary'] <=> $a['is_primary'];
            }
            return $b['score'] <=> $a['score'];
        });
        
        return collect($departmentScores)->map(function ($item) {
            return [
                'department' => $item['department'],
                'score' => $item['score'],
                'is_primary' => $item['is_primary'],
                'matched_codes' => $item['matched_codes'],
                'confidence' => min(1.0, $item['score'] / 10.0), // Normalize to 0-1
            ];
        });
    }

    /**
     * Get the best suggested department for a referral
     * 
     * @param Referral $referral
     * @return Department|null
     */
    public function getBestDepartmentForReferral(Referral $referral): ?Department
    {
        $suggestions = $this->suggestDepartmentsForReferral($referral);
        
        if ($suggestions->isEmpty()) {
            return null;
        }
        
        return $suggestions->first()['department'] ?? null;
    }

    /**
     * Validate if a department is appropriate for a referral's ICD-10 codes
     * 
     * @param Referral $referral
     * @param Department|int $department
     * @return bool
     */
    public function isValidDepartmentForReferral(Referral $referral, $department): bool
    {
        $departmentId = $department instanceof Department ? $department->id : $department;
        
        $icd10Codes = $referral->icd10Codes()->with('icd10Code')->get();
        
        foreach ($icd10Codes as $referralIcd10Code) {
            $icd10Code = $referralIcd10Code->icd10Code;
            
            if (!$icd10Code) {
                continue;
            }
            
            // Check if this department handles this ICD-10 code
            $handlesCode = $icd10Code->departments()
                ->where('departments.id', $departmentId)
                ->exists();
            
            if ($handlesCode) {
                return true;
            }
        }
        
        return false;
    }

    /**
     * Get all departments that can handle any of the referral's ICD-10 codes
     * 
     * @param Referral $referral
     * @return Collection
     */
    public function getValidDepartmentsForReferral(Referral $referral): Collection
    {
        $icd10Codes = $referral->icd10Codes()->with('icd10Code')->get();
        $departmentIds = collect();
        
        foreach ($icd10Codes as $referralIcd10Code) {
            $icd10Code = $referralIcd10Code->icd10Code;
            
            if (!$icd10Code) {
                continue;
            }
            
            $departmentIds = $departmentIds->merge(
                $icd10Code->departments()->pluck('departments.id')
            );
        }
        
        return Department::whereIn('id', $departmentIds->unique())->get();
    }
}

