<?php

namespace Database\Seeders;

use App\Models\Training;
use Illuminate\Database\Seeder;

class TrainingSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $trainings = [
            [
                'name' => 'SUYNL',
                'code' => 'SUYNL',
                'description' => 'SUYNL Training Program',
                'total_lessons' => 10,
                'is_active' => true,
            ],
            [
                'name' => 'LifeClass',
                'code' => 'LIFECLASS',
                'description' => 'LifeClass Training Program',
                'total_lessons' => 10,
                'is_active' => true,
            ],
            [
                'name' => 'SOL 1',
                'code' => 'SOL1',
                'description' => 'School of Leaders 1',
                'total_lessons' => 13,
                'is_active' => true,
            ],
            [
                'name' => 'SOL 2',
                'code' => 'SOL2',
                'description' => 'School of Leaders 2',
                'total_lessons' => 13,
                'is_active' => true,
            ],
            [
                'name' => 'SOL 3',
                'code' => 'SOL3',
                'description' => 'School of Leaders 3',
                'total_lessons' => 13,
                'is_active' => true,
            ],
        ];

        foreach ($trainings as $training) {
            Training::updateOrCreate(
                ['code' => $training['code']],
                $training
            );
        }
    }
}

