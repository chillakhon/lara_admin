<?php

namespace Database\Seeders;

use App\Models\LeadType;
use Illuminate\Database\Seeder;

class LeadTypeSeeder extends Seeder
{
    public function run()
    {
        $types = [
            [
                'name' => 'Обратный звонок',
                'code' => 'callback',
                'description' => 'Заявка на обратный звонок',
                'required_fields' => ['phone', 'name'],
                'is_active' => true
            ],
            [
                'name' => 'Консультация',
                'code' => 'consultation',
                'description' => 'Заявка на консультацию',
                'required_fields' => ['phone', 'email', 'name', 'message'],
                'is_active' => true
            ],
            // Другие типы лидов...
        ];

        foreach ($types as $type) {
            LeadType::create($type);
        }
    }
}
