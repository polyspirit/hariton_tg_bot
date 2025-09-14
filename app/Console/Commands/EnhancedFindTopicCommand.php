<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Services\EnhancedTopicFinderService;
use App\Models\QuestionTopic;

class EnhancedFindTopicCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'ai:enhanced-find-topic {question?}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Find the best topic for a question using enhanced AI search with ChatGPT fallback';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $question = $this->argument('question');

        if (!$question) {
            $question = $this->ask('Введите ваш вопрос:');
        }

        if (empty($question)) {
            $this->error('Вопрос не может быть пустым');
            return 1;
        }

        try {
            $enhancedTopicFinderService = app(EnhancedTopicFinderService::class);

            $this->info("Поиск похожего вопроса для: \"{$question}\"");
            $this->newLine();

            // Find most similar question
            $similarQuestion = $enhancedTopicFinderService->findMostSimilarQuestionWithAI($question);

            if ($similarQuestion) {
                $this->info("✅ Найден похожий вопрос:");
                $this->line("ID: {$similarQuestion->id}");
                $this->line("Вопрос: {$similarQuestion->question}");
                $this->line("Ответ: " . ($similarQuestion->answer ? 'Да' : 'Нет'));
                $this->newLine();
            } else {
                $this->warn("❌ Похожий вопрос не найден");
                $this->newLine();
            }

            // Find best topic
            $this->info("Поиск лучшей темы для вопроса...");
            $topic = $enhancedTopicFinderService->findBestTopic($question);

            if ($topic) {
                $this->info("✅ Найденная тема: {$topic->topic}");
            } else {
                $this->warn('Тема не найдена, будет использована "Другое"');
            }

            return 0;
        } catch (\Exception $e) {
            $this->error('Ошибка: ' . $e->getMessage());
            return 1;
        }
    }
}
