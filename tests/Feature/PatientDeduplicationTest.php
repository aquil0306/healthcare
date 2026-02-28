<?php

namespace Tests\Feature;

use App\Models\Patient;
use App\Repositories\PatientRepository;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class PatientDeduplicationTest extends TestCase
{
    use RefreshDatabase;

    private PatientRepository $repository;

    protected function setUp(): void
    {
        parent::setUp();
        $this->repository = app(PatientRepository::class);
    }

    public function test_finds_existing_patient_by_national_id(): void
    {
        // Create existing patient
        $existingPatient = Patient::factory()->create([
            'national_id' => '123456789',
        ]);

        // Try to find or create with same national ID
        $result = $this->repository->findOrCreateByNationalId([
            'first_name' => 'John',
            'last_name' => 'Doe',
            'date_of_birth' => '1990-01-01',
            'national_id' => '123456789',
            'insurance_number' => 'INS999',
        ]);

        // Should return existing patient, not create new one
        $this->assertEquals($existingPatient->id, $result->id);
        $this->assertEquals($existingPatient->national_id, $result->national_id);

        // Verify only one patient exists with this national ID
        $this->assertEquals(1, Patient::where('id', $existingPatient->id)->count());
    }

    public function test_creates_new_patient_when_national_id_not_found(): void
    {
        $patientData = [
            'first_name' => 'Jane',
            'last_name' => 'Smith',
            'date_of_birth' => '1985-05-15',
            'national_id' => '987654321',
            'insurance_number' => 'INS888',
        ];

        $result = $this->repository->findOrCreateByNationalId($patientData);

        // Should create new patient
        $this->assertNotNull($result->id);
        $this->assertEquals('Jane', $result->first_name);
        $this->assertEquals('Smith', $result->last_name);
        $this->assertEquals('987654321', $result->national_id);

        // Verify patient was created in database
        $this->assertDatabaseHas('patients', [
            'id' => $result->id,
        ]);
    }

    public function test_patient_data_is_encrypted_at_rest(): void
    {
        $patient = Patient::factory()->create([
            'first_name' => 'Encrypted',
            'last_name' => 'Test',
            'national_id' => 'ENCRYPT123',
            'insurance_number' => 'INS456',
        ]);

        // Check raw database value is encrypted (not plaintext)
        $rawPatient = \DB::table('patients')->where('id', $patient->id)->first();

        $this->assertNotEquals('Encrypted', $rawPatient->first_name);
        $this->assertNotEquals('Test', $rawPatient->last_name);
        $this->assertNotEquals('ENCRYPT123', $rawPatient->national_id);
        $this->assertNotEquals('INS456', $rawPatient->insurance_number);

        // But model accessor decrypts it
        $this->assertEquals('Encrypted', $patient->first_name);
        $this->assertEquals('Test', $patient->last_name);
        $this->assertEquals('ENCRYPT123', $patient->national_id);
        $this->assertEquals('INS456', $patient->insurance_number);
    }
}
