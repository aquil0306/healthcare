<?php

namespace Database\Seeders;

use App\Models\Department;
use App\Models\Icd10Code;
use Illuminate\Database\Seeder;

class Icd10DepartmentMappingSeeder extends Seeder
{
    /**
     * Run the database seeds.
     * 
     * This seeder creates mappings between ICD-10 codes and departments.
     * You can customize these mappings based on your hospital's structure.
     */
    public function run(): void
    {
        // Get departments (assuming they exist)
        $cardiology = Department::where('code', 'CARD')->orWhere('name', 'like', '%cardiology%')->first();
        $neurology = Department::where('code', 'NEURO')->orWhere('name', 'like', '%neurology%')->first();
        $orthopedics = Department::where('code', 'ORTHO')->orWhere('name', 'like', '%orthopedic%')->first();
        $general = Department::where('code', 'GEN')->orWhere('name', 'like', '%general%')->first();
        $emergency = Department::where('code', 'ER')->orWhere('name', 'like', '%emergency%')->first();

        if (!$cardiology || !$neurology || !$orthopedics || !$general) {
            $this->command->warn('Required departments not found. Please run DepartmentSeeder first.');
            return;
        }

        // Cardiology ICD-10 codes (I00-I99 - Diseases of the circulatory system)
        $cardiacCodes = Icd10Code::where('code', 'like', 'I%')
            ->orWhere('category', 'like', 'I%')
            ->get();
        
        foreach ($cardiacCodes as $code) {
            $code->departments()->syncWithoutDetaching([
                $cardiology->id => [
                    'priority' => 1,
                    'is_primary' => true,
                ],
            ]);
        }

        // Neurology ICD-10 codes (G00-G99 - Diseases of the nervous system)
        $neuroCodes = Icd10Code::where('code', 'like', 'G%')
            ->orWhere('category', 'like', 'G%')
            ->get();
        
        foreach ($neuroCodes as $code) {
            $code->departments()->syncWithoutDetaching([
                $neurology->id => [
                    'priority' => 1,
                    'is_primary' => true,
                ],
            ]);
        }

        // Orthopedics ICD-10 codes (M00-M99 - Diseases of the musculoskeletal system)
        $orthoCodes = Icd10Code::where('code', 'like', 'M%')
            ->orWhere('category', 'like', 'M%')
            ->get();
        
        foreach ($orthoCodes as $code) {
            $code->departments()->syncWithoutDetaching([
                $orthopedics->id => [
                    'priority' => 1,
                    'is_primary' => true,
                ],
            ]);
        }

        // Specific code examples with multiple department mappings
        // Example: I21.9 (Acute myocardial infarction) - Primary: Cardiology, Secondary: Emergency
        $miCode = Icd10Code::where('code', 'I21.9')->first();
        if ($miCode && $emergency) {
            $miCode->departments()->sync([
                $cardiology->id => ['priority' => 1, 'is_primary' => true],
                $emergency->id => ['priority' => 2, 'is_primary' => false],
            ]);
        }

        // Example: G43.9 (Migraine) - Primary: Neurology
        $migraineCode = Icd10Code::where('code', 'G43.9')->first();
        if ($migraineCode) {
            $migraineCode->departments()->sync([
                $neurology->id => ['priority' => 1, 'is_primary' => true],
            ]);
        }

        $this->command->info('ICD-10 code to department mappings created successfully!');
        $this->command->info('Total mappings: ' . \DB::table('icd10_code_department')->count());
    }
}

