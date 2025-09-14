<?php

namespace App\Services;

use App\Models\QuestionTopic;
use Illuminate\Support\Facades\Log;

class TopicFinderService
{
    private OpenAIService $openAIService;

    public function __construct(OpenAIService $openAIService)
    {
        $this->openAIService = $openAIService;
    }

    /**
     * Find the best topic using AI
     */
    public function findBestTopic(string $question): ?QuestionTopic
    {
        try {
            // Get all topics
            $topics = QuestionTopic::all();
            $topicList = $topics->pluck('topic')->implode(', ');

            // Create prompt for AI
            $prompt = "К какой из представленных категорий ты причислил бы этот вопрос: " .
                "\"{$question}\"? Отвечай одним из вариантов списка, ничего дополнительно не объясняй.\n\n" .
                "Список категорий: {$topicList}";

            // Get AI response
            $response = $this->openAIService->ask($prompt);

            if (!$response) {
                return null;
            }

            // Clean response (remove extra whitespace, quotes, etc.)
            $response = trim($response, " \t\n\r\0\x0B\"'");

            // Remove any additional text after the topic name
            $lines = explode("\n", $response);
            $response = trim($lines[0]);

            // Find matching topic
            foreach ($topics as $topic) {
                if (strcasecmp($topic->topic, $response) === 0) {
                    return $topic;
                }
            }

            // If no exact match found, return null (will be treated as "Другое")
            return null;
        } catch (\Exception $e) {
            Log::error('Error finding topic with AI', [
                'question' => $question,
                'error' => $e->getMessage(),
            ]);
            return null;
        }
    }

    /**
     * Find the best topic with fallback to "Другое"
     */
    public function findBestTopicWithFallback(string $question): QuestionTopic
    {
        $topic = $this->findBestTopic($question);

        // If no topic found, use "Другое" topic
        if (!$topic) {
            $topic = QuestionTopic::where('topic', 'Другое')->first();
        }

        return $topic;
    }
}
