<?php

namespace App\Services;

use App\Models\Question;
use App\Models\QuestionTopic;
use Illuminate\Support\Facades\Log;

class EnhancedTopicFinderService
{
    private OpenAIService $openAIService;

    public function __construct(OpenAIService $openAIService)
    {
        $this->openAIService = $openAIService;
    }

    /**
     * Find the most similar question using ChatGPT when simple search fails
     */
    public function findMostSimilarQuestionWithAI(string $question): ?Question
    {
        try {
            // First attempt: use existing simple search
            $similarQuestion = $this->findMostSimilarQuestionSimple($question);
            $similarity = $similarQuestion ? $this->calculateSimilarity(
                $this->extractKeywords($question),
                $this->extractKeywords($similarQuestion->question)
            ) : 0;

            // If we found a good match, return it
            if ($similarity > 0.4) {
                return $similarQuestion;
            }

            // Second attempt: use ChatGPT for better matching
            Log::info('Simple search failed, using ChatGPT for question matching', [
                'question' => $question,
                'similarity' => $similarity
            ]);

            return $this->findMostSimilarQuestionWithChatGPT($question);
        } catch (\Exception $e) {
            Log::error('Error in enhanced topic finder', [
                'question' => $question,
                'error' => $e->getMessage(),
            ]);
            return null;
        }
    }

    /**
     * Find the most similar question using ChatGPT
     */
    private function findMostSimilarQuestionWithChatGPT(string $question): ?Question
    {
        try {
            // Get all questions from database
            $allQuestions = Question::all();

            if ($allQuestions->isEmpty()) {
                return null;
            }

            // Create list of questions for ChatGPT
            $questionsList = $allQuestions->pluck('question')->implode("\n");

            // Create prompt for ChatGPT
            $prompt = "На какой вопрос из списка вопросов больше всего похож этот вопрос: \"{$question}\"? " .
                "Вот список вопросов:\n{$questionsList}\n\nОтвечай только цитатой вопроса, ничего не объясняй.";

            // Get ChatGPT response
            $response = $this->openAIService->ask($prompt);

            if (!$response) {
                return null;
            }

            // Clean response (remove extra whitespace, quotes, etc.)
            $response = trim($response, " \t\n\r\0\x0B\"'");

            // Find matching question in database
            foreach ($allQuestions as $q) {
                if (strcasecmp(trim($q->question), trim($response)) === 0) {
                    Log::info('ChatGPT found matching question', [
                        'original_question' => $question,
                        'found_question' => $q->question,
                        'question_id' => $q->id
                    ]);
                    return $q;
                }
            }

            // If no exact match found, try partial matching
            foreach ($allQuestions as $q) {
                if (
                    strpos(strtolower($response), strtolower($q->question)) !== false
                    || strpos(strtolower($q->question), strtolower($response)) !== false
                ) {
                    Log::info('ChatGPT found partial matching question', [
                        'original_question' => $question,
                        'found_question' => $q->question,
                        'question_id' => $q->id
                    ]);
                    return $q;
                }
            }

            Log::warning('ChatGPT could not find matching question', [
                'original_question' => $question,
                'chatgpt_response' => $response
            ]);

            return null;
        } catch (\Exception $e) {
            Log::error('Error using ChatGPT for question matching', [
                'question' => $question,
                'error' => $e->getMessage(),
            ]);
            return null;
        }
    }

    /**
     * Simple question similarity search (copied from OpenAIService)
     */
    private function findMostSimilarQuestionSimple(string $question): ?Question
    {
        $allQuestions = Question::all();
        $mostSimilar = null;
        $highestSimilarity = 0;

        $keywords = $this->extractKeywords($question);

        foreach ($allQuestions as $q) {
            $questionKeywords = $this->extractKeywords($q->question);
            $similarity = $this->calculateSimilarity($keywords, $questionKeywords);

            if ($similarity > $highestSimilarity) {
                $highestSimilarity = $similarity;
                $mostSimilar = $q;
            }
        }

        // First attempt: strict similarity threshold
        if ($highestSimilarity > 0.7) {
            return $mostSimilar;
        }

        // Second attempt: more lenient search if nothing found
        if ($highestSimilarity > 0.4) {
            return $mostSimilar;
        }

        return null;
    }

    /**
     * Extract keywords from question (copied from OpenAIService)
     */
    private function extractKeywords(string $question): array
    {
        // Убираем знаки препинания и приводим к нижнему регистру
        $cleanQuestion = preg_replace('/[^\p{L}\p{N}\s]/u', ' ', mb_strtolower($question));
        $words = preg_split('/\s+/', $cleanQuestion);

        $stopWords = [
            'вопрос',
            'что',
            'как',
            'где',
            'когда',
            'почему',
            'зачем',
            'кто',
            'какой',
            'какая',
            'какие',
            'это',
            'есть',
            'ли',
            'а',
            'и',
            'или',
            'но',
            'на',
            'в',
            'с',
            'по',
            'для',
            'от',
            'до',
            'из',
            'к',
            'у',
            'о',
            'об',
            'про',
            'со',
            'во',
            'за',
            'под',
            'над',
            'перед',
            'после',
            'между',
            'через',
            'без',
            'при',
            'около',
            'вокруг',
            'внутри',
            'снаружи',
            'вверху',
            'внизу',
            'справа',
            'слева',
            'существует',
            'существует ли',
            'есть ли',
            'бывает ли',
            'встречается ли'
        ];

        $keywords = array_filter($words, function ($word) use ($stopWords) {
            return !in_array($word, $stopWords) && mb_strlen($word) > 2;
        });

        // Add check for exact match of keywords
        $importantWords = ['нло', 'бог', 'бога', 'боги', 'инопланетяне', 'пришельцы', 'космос', 'вселенная'];
        $hasImportantWords = false;
        foreach ($importantWords as $importantWord) {
            if (in_array($importantWord, $keywords)) {
                $hasImportantWords = true;
                break;
            }
        }

        return $hasImportantWords ? $keywords : [];
    }

    /**
     * Calculate similarity between two sets of keywords (copied from OpenAIService)
     */
    private function calculateSimilarity(array $keywords1, array $keywords2): float
    {
        if (empty($keywords1) || empty($keywords2)) {
            return 0.0;
        }

        $intersection = array_intersect($keywords1, $keywords2);
        $union = array_unique(array_merge($keywords1, $keywords2));

        return count($intersection) / count($union);
    }

    /**
     * Find the best topic using AI (enhanced version)
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
