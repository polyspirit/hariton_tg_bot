<?php

namespace Database\Seeders;

use App\Models\QuestionTopic;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class QuestionTopicSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // Create 10 sample question topics
        QuestionTopic::factory(10)->create();
    }
}
