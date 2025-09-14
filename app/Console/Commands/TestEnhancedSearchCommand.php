<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Services\OpenAIService;
use App\Services\EnhancedTopicFinderService;

class TestEnhancedSearchCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'ai:test-enhanced-search {question?}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Test enhanced search functionality with ChatGPT fallback';

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
            $openAIService = app(OpenAIService::class);
            $enhancedTopicFinderService = app(EnhancedTopicFinderService::class);

            $this->info("Тестирование улучшенного поиска для вопроса: \"{$question}\"");
            $this->newLine();

            // Test 1: Original search
            $this->info("1. Тестирование оригинального поиска...");
            $originalResponse = $openAIService->ask($question);
            $this->line("Ответ: " . ($originalResponse ?: 'null'));
            $this->newLine();

            // Test 2: Enhanced search
            $this->info("2. Тестирование улучшенного поиска...");
            $enhancedResponse = $openAIService->askWithEnhancedSearch($question);
            $this->line("Ответ: " . ($enhancedResponse ?: 'null'));
            $this->newLine();

            // Test 3: Direct enhanced topic finder
            $this->info("3. Тестирование прямого поиска похожих вопросов...");
            $similarQuestion = $enhancedTopicFinderService->findMostSimilarQuestionWithAI($question);

            if ($similarQuestion) {
                $this->info("✅ Найден похожий вопрос:");
                $this->line("ID: {$similarQuestion->id}");
                $this->line("Вопрос: {$similarQuestion->question}");
                $this->line("Ответ: " . ($similarQuestion->answer ? 'Да' : 'Нет'));
            } else {
                $this->warn("❌ Похожий вопрос не найден");
            }

            return 0;
        } catch (\Exception $e) {
            $this->error('Ошибка: ' . $e->getMessage());
            return 1;
        }
    }
}
