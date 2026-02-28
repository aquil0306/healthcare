<?php

namespace Database\Seeders;

use App\Models\Icd10Code;
use Illuminate\Database\Seeder;

class Icd10CodeSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $categories = [
            [
                'category' => 'A00-B99',
                'category_description' => 'Certain infectious and parasitic diseases',
                'codes' => [
                    ['code' => 'A00', 'description' => 'Cholera'],
                    ['code' => 'A01', 'description' => 'Typhoid and paratyphoid fevers'],
                    ['code' => 'A02', 'description' => 'Other salmonella infections'],
                    ['code' => 'B00', 'description' => 'Herpesviral infections'],
                ],
            ],
            [
                'category' => 'C00-D49',
                'category_description' => 'Neoplasms',
                'codes' => [
                    ['code' => 'C00', 'description' => 'Malignant neoplasm of lip'],
                    ['code' => 'C01', 'description' => 'Malignant neoplasm of base of tongue'],
                    ['code' => 'C50', 'description' => 'Malignant neoplasm of breast'],
                ],
            ],
            [
                'category' => 'D50-D89',
                'category_description' => 'Diseases of the blood and blood-forming organs and certain disorders involving the immune mechanism',
                'codes' => [
                    ['code' => 'D50', 'description' => 'Iron deficiency anemia'],
                    ['code' => 'D51', 'description' => 'Vitamin B12 deficiency anemia'],
                ],
            ],
            [
                'category' => 'E00-E89',
                'category_description' => 'Endocrine, nutritional and metabolic diseases',
                'codes' => [
                    ['code' => 'E10', 'description' => 'Type 1 diabetes mellitus'],
                    ['code' => 'E11', 'description' => 'Type 2 diabetes mellitus'],
                    ['code' => 'E78', 'description' => 'Disorders of lipoprotein metabolism'],
                ],
            ],
            [
                'category' => 'F01-F99',
                'category_description' => 'Mental, Behavioral and Neurodevelopmental disorders',
                'codes' => [
                    ['code' => 'F32', 'description' => 'Major depressive disorder, single episode'],
                    ['code' => 'F41', 'description' => 'Other anxiety disorders'],
                ],
            ],
            [
                'category' => 'G00-G99',
                'category_description' => 'Diseases of the nervous system',
                'codes' => [
                    ['code' => 'G93', 'description' => 'Other disorders of brain'],
                    ['code' => 'G40', 'description' => 'Epilepsy and recurrent seizures'],
                ],
            ],
            [
                'category' => 'H00-H59',
                'category_description' => 'Diseases of the eye and adnexa',
                'codes' => [
                    ['code' => 'H40', 'description' => 'Glaucoma'],
                ],
            ],
            [
                'category' => 'H60-H95',
                'category_description' => 'Diseases of the ear and mastoid process',
                'codes' => [
                    ['code' => 'H90', 'description' => 'Conductive and sensorineural hearing loss'],
                ],
            ],
            [
                'category' => 'I00-I99',
                'category_description' => 'Diseases of the circulatory system',
                'codes' => [
                    ['code' => 'I10', 'description' => 'Essential (primary) hypertension'],
                    ['code' => 'I20', 'description' => 'Angina pectoris'],
                    ['code' => 'I21', 'description' => 'ST elevation (STEMI) and non-ST elevation (NSTEMI) myocardial infarction'],
                    ['code' => 'I50', 'description' => 'Heart failure'],
                ],
            ],
            [
                'category' => 'J00-J99',
                'category_description' => 'Diseases of the respiratory system',
                'codes' => [
                    ['code' => 'J44', 'description' => 'Other chronic obstructive pulmonary disease'],
                    ['code' => 'J18', 'description' => 'Pneumonia, unspecified organism'],
                ],
            ],
            [
                'category' => 'K00-K95',
                'category_description' => 'Diseases of the digestive system',
                'codes' => [
                    ['code' => 'K59', 'description' => 'Other functional intestinal disorders'],
                    ['code' => 'K25', 'description' => 'Gastric ulcer'],
                ],
            ],
            [
                'category' => 'L00-L99',
                'category_description' => 'Diseases of the skin and subcutaneous tissue',
                'codes' => [
                    ['code' => 'L50', 'description' => 'Urticaria'],
                ],
            ],
            [
                'category' => 'M00-M99',
                'category_description' => 'Diseases of the musculoskeletal system and connective tissue',
                'codes' => [
                    ['code' => 'M25', 'description' => 'Other joint disorders'],
                    ['code' => 'M79', 'description' => 'Other soft tissue disorders'],
                ],
            ],
            [
                'category' => 'N00-N99',
                'category_description' => 'Diseases of the genitourinary system',
                'codes' => [
                    ['code' => 'N18', 'description' => 'Chronic kidney disease (CKD)'],
                    ['code' => 'N39', 'description' => 'Other disorders of urinary system'],
                ],
            ],
            [
                'category' => 'O00-O9A',
                'category_description' => 'Pregnancy, childbirth and the puerperium',
                'codes' => [
                    ['code' => 'O80', 'description' => 'Encounter for full-term uncomplicated delivery'],
                ],
            ],
            [
                'category' => 'P00-P96',
                'category_description' => 'Certain conditions originating in the perinatal period',
                'codes' => [
                    ['code' => 'P07', 'description' => 'Disorders of newborn related to short gestation and low birth weight'],
                ],
            ],
            [
                'category' => 'Q00-QA0',
                'category_description' => 'Congenital malformations, deformations, chromosomal abnormalities, and genetic disorders',
                'codes' => [
                    ['code' => 'Q21', 'description' => 'Congenital malformations of cardiac septa'],
                ],
            ],
            [
                'category' => 'R00-R99',
                'category_description' => 'Symptoms, signs and abnormal clinical and laboratory findings, not elsewhere classified',
                'codes' => [
                    ['code' => 'R50', 'description' => 'Fever of other and unknown origin'],
                    ['code' => 'R06', 'description' => 'Abnormalities of breathing'],
                ],
            ],
            [
                'category' => 'S00-T88',
                'category_description' => 'Injury, poisoning and certain other consequences of external causes',
                'codes' => [
                    ['code' => 'S72', 'description' => 'Fracture of femur'],
                ],
            ],
            [
                'category' => 'U00-U85',
                'category_description' => 'Codes for special purposes',
                'codes' => [
                    ['code' => 'U07', 'description' => 'Emergency use of U07'],
                ],
            ],
            [
                'category' => 'V00-Y99',
                'category_description' => 'External causes of morbidity',
                'codes' => [
                    ['code' => 'V89', 'description' => 'Person injured in unspecified motor-vehicle accident'],
                ],
            ],
            [
                'category' => 'Z00-Z99',
                'category_description' => 'Factors influencing health status and contact with health services',
                'codes' => [
                    ['code' => 'Z00', 'description' => 'Encounter for general examination without complaint'],
                ],
            ],
        ];

        foreach ($categories as $categoryData) {
            foreach ($categoryData['codes'] as $codeData) {
                Icd10Code::firstOrCreate(
                    ['code' => $codeData['code']],
                    [
                        'description' => $codeData['description'],
                        'category' => $categoryData['category'],
                        'category_description' => $categoryData['category_description'],
                        'is_active' => true,
                    ]
                );
            }
        }

        $this->command->info('ICD-10 codes seeded successfully!');
    }
}
