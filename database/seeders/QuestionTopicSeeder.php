<?php

namespace Database\Seeders;

use App\Models\QuestionTopic;
use Illuminate\Database\Seeder;

class QuestionTopicSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $topics = [
            'Бог и религия',
            'Инопланетяне и НЛО',
            'Будущее и предсказания',
            'Смерть и загробная жизнь',
            'Правительство и заговоры',
            'Здоровье и медицина',
            'Войны и конфликты',
            'Мистика и магия',
            'Политика и лидеры',
            'Экономика и деньги',
            'Наука и технологии',
            'Семья и отношения',
            'Природа и экология',
            'Культура и искусство',
            'История и археология',
            'Личные вопросы'
        ];

        foreach ($topics as $topic) {
            QuestionTopic::firstOrCreate(
                ['topic' => $topic],
                ['topic' => $topic]
            );
        }
    }
}
