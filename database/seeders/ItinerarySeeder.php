<?php

namespace Database\Seeders;

use App\Models\ItineraryActivity;
use Illuminate\Database\Seeder;

class ItinerarySeeder extends Seeder
{
    /**
     * Seed default itinerary categories and activities.
     */
    public function run(): void
    {
        $activities = [
            'Cellgroup',
            'Prayer night',
            'PLAN 40',
            'LIFECLASS',
            'SOL 1',
            'SOL 2',
            'SOL 3',
            'WildSons',
            'CrossOver',
            'Couples',
            'Sunday Service',
            'PID',
            'ILD',
            'OIKOS'
        ];

        foreach ($activities as $index => $name) {
            ItineraryActivity::firstOrCreate(
                [
                    'name' => $name,
                    'category_id' => null,
                    'user_id' => null,
                ],
                [
                    'sort_order' => $index + 1,
                    'is_active' => true,
                ]
            );
        }
    }
}
