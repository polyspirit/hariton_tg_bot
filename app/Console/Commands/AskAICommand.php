<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Services\OpenAIService;

class AskAICommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'ai:ask {question?}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Ask AI a question and get response based on questions from database';

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
            $openAIService = new OpenAIService();

            $prompt = $openAIService->generatePromptWithSimilarQuestions($question);

            $response = $openAIService->generateResponse($prompt);

            $this->info('Ответ от ИИ:');
            $this->line($response);
        } catch (\Exception $e) {
            $this->error('Ошибка: ' . $e->getMessage());
            return 1;
        }

        return 0;
    }
}
