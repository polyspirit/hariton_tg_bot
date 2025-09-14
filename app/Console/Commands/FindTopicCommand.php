<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Services\TopicFinderService;
use App\Models\QuestionTopic;

class FindTopicCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'ai:find-topic {question?}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Find the best topic for a question using AI';

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
            $topicFinderService = app(TopicFinderService::class);
            $topic = $topicFinderService->findBestTopic($question);

            if ($topic) {
                $this->info("Найденный топик: {$topic->topic}");
            } else {
                $this->warn('Топик не найден, будет использован "Другое"');
            }

            return 0;
        } catch (\Exception $e) {
            $this->error('Ошибка: ' . $e->getMessage());
            return 1;
        }
    }
}
