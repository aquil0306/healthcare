<?php

namespace App\Repositories;

use App\Models\Patient;

class PatientRepository extends BaseRepository
{
    public function __construct(Patient $model)
    {
        parent::__construct($model);
    }

    public function findByNationalId(string $nationalId): ?Patient
    {
        // Since national_id is encrypted, we need to retrieve all and decrypt
        // For production, consider using a hash index column for searching
        $patients = $this->model->all();
        
        foreach ($patients as $patient) {
            if ($patient->national_id === $nationalId) {
                return $patient;
            }
        }
        
        return null;
    }

    public function findOrCreateByNationalId(array $data): Patient
    {
        $patient = $this->findByNationalId($data['national_id']);
        
        if (!$patient) {
            $patient = $this->create($data);
        }
        
        return $patient;
    }
}

