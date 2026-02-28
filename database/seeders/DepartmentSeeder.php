<?php

namespace Database\Seeders;

use App\Models\Department;
use Illuminate\Database\Seeder;

class DepartmentSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $departments = [
            [
                'name' => 'Cardiology',
                'code' => 'CARD',
                'description' => 'Cardiology department specializing in heart and cardiovascular system disorders',
            ],
            [
                'name' => 'Neurology',
                'code' => 'NEURO',
                'description' => 'Neurology department specializing in disorders of the nervous system',
            ],
            [
                'name' => 'Orthopedics',
                'code' => 'ORTHO',
                'description' => 'Orthopedics department specializing in musculoskeletal system disorders',
            ],
            [
                'name' => 'Emergency Medicine',
                'code' => 'ER',
                'description' => 'Emergency department for acute and urgent medical conditions',
            ],
            [
                'name' => 'Internal Medicine',
                'code' => 'IM',
                'description' => 'Internal medicine department for general adult medical conditions',
            ],
            [
                'name' => 'Pediatrics',
                'code' => 'PEDS',
                'description' => 'Pediatrics department specializing in medical care for infants, children, and adolescents',
            ],
            [
                'name' => 'Oncology',
                'code' => 'ONCO',
                'description' => 'Oncology department specializing in cancer diagnosis and treatment',
            ],
            [
                'name' => 'Pulmonology',
                'code' => 'PULM',
                'description' => 'Pulmonology department specializing in respiratory system disorders',
            ],
            [
                'name' => 'Gastroenterology',
                'code' => 'GI',
                'description' => 'Gastroenterology department specializing in digestive system disorders',
            ],
            [
                'name' => 'Endocrinology',
                'code' => 'ENDO',
                'description' => 'Endocrinology department specializing in hormonal and metabolic disorders',
            ],
            [
                'name' => 'Psychiatry',
                'code' => 'PSYCH',
                'description' => 'Psychiatry department specializing in mental health disorders',
            ],
            [
                'name' => 'Nephrology',
                'code' => 'NEPH',
                'description' => 'Nephrology department specializing in kidney disorders',
            ],
            [
                'name' => 'Dermatology',
                'code' => 'DERM',
                'description' => 'Dermatology department specializing in skin disorders',
            ],
            [
                'name' => 'Ophthalmology',
                'code' => 'OPHTH',
                'description' => 'Ophthalmology department specializing in eye disorders',
            ],
            [
                'name' => 'Otolaryngology',
                'code' => 'ENT',
                'description' => 'Otolaryngology (ENT) department specializing in ear, nose, and throat disorders',
            ],
            [
                'name' => 'Urology',
                'code' => 'URO',
                'description' => 'Urology department specializing in urinary tract and male reproductive system disorders',
            ],
            [
                'name' => 'Obstetrics and Gynecology',
                'code' => 'OBGYN',
                'description' => 'Obstetrics and gynecology department specializing in women\'s reproductive health',
            ],
            [
                'name' => 'Radiology',
                'code' => 'RAD',
                'description' => 'Radiology department for medical imaging and diagnostic procedures',
            ],
            [
                'name' => 'Anesthesiology',
                'code' => 'ANES',
                'description' => 'Anesthesiology department for perioperative care and pain management',
            ],
            [
                'name' => 'General Surgery',
                'code' => 'GS',
                'description' => 'General surgery department for surgical procedures',
            ],
        ];

        foreach ($departments as $department) {
            Department::firstOrCreate(
                ['name' => $department['name']],
                [
                    'code' => $department['code'],
                    'description' => $department['description'],
                    'is_active' => true,
                ]
            );
        }

        $this->command->info('Departments seeded successfully!');
    }
}
