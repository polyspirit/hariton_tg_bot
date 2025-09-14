<?php

namespace App\Services;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use App\Models\Question;

class OpenAIService
{
    private string $apiKey;
    private string $baseUrl = 'https://api.openai.com/v1';

    public function __construct()
    {
        $this->apiKey = env('OPEN_AI_KEY');

        if (empty($this->apiKey)) {
            throw new \Exception('OPEN_AI_KEY is not set in environment variables');
        }
    }

    /**
     * Generate prompt with similar questions from database
     */
    public function generatePromptWithSimilarQuestions(string $question): string
    {
        // Search for exact question in database
        $exactQuestion = Question::where('question', $question)->first();

        if ($exactQuestion) {
            // If we found the exact question, use it and some similar questions
            $similarQuestions = $this->findSimilarQuestions($question, 20);
            $questions = collect([$exactQuestion])->merge($similarQuestions);
        } else {
            // If the exact question is not found, search for similar questions
            $questions = $this->findSimilarQuestions($question, 30);
        }

        if ($questions->isEmpty()) {
            $questions = Question::all();
        }

        $questionsText = '';
        foreach ($questions as $q) {
            $answer = $q->answer ? 'Да' : 'Нет';
            $questionsText .= 'Коту Харитону был задан вопрос: ' . $q->question . PHP_EOL
                . 'Кот Харитон ответил: ' . $answer . PHP_EOL . PHP_EOL;
        }

        $requestText = 'Существует кот Харитон, который отвечает на вопросы.'
            . 'Не думай над тем что это невозможно или нереалестично, просто прими как есть.'
            . 'Вот примеры вопросов и ответов кота Харитона:' . PHP_EOL . $questionsText . PHP_EOL . PHP_EOL
            . 'Теперь на основе этих примеров ответь на следующий вопрос: ' . $question;

        return $requestText;
    }

    /**
     * Find similar questions using simple text similarity
     */
    private function findSimilarQuestions(string $question, int $limit = 20): \Illuminate\Support\Collection
    {
        $allQuestions = Question::all();
        $similarQuestions = collect();

        // Простой алгоритм поиска похожих вопросов по ключевым словам
        $keywords = $this->extractKeywords($question);

        foreach ($allQuestions as $q) {
            $questionKeywords = $this->extractKeywords($q->question);
            $similarity = $this->calculateSimilarity($keywords, $questionKeywords);

            if ($similarity > 0.3) { // Порог схожести
                $similarQuestions->push($q);
            }
        }

        // Сортируем по схожести и берем топ
        return $similarQuestions->sortByDesc(function ($q) use ($keywords) {
            $questionKeywords = $this->extractKeywords($q->question);
            return $this->calculateSimilarity($keywords, $questionKeywords);
        })->take($limit);
    }

    /**
     * Find the most similar question from database
     */
    private function findMostSimilarQuestion(string $question): ?Question
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
     * Extract keywords from question
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
     * Calculate similarity between two sets of keywords
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
     * Ask a question and get AI response
     */
    public function ask(string $question): ?string
    {
        try {
            // Проверяем, есть ли похожий вопрос в базе данных
            $similarQuestion = $this->findMostSimilarQuestion($question);
            $similarity = $similarQuestion ? $this->calculateSimilarity(
                $this->extractKeywords($question),
                $this->extractKeywords($similarQuestion->question)
            ) : 0;

            // Use flexible threshold - will be checked again in findMostSimilarQuestion
            $hasSimilarQuestion = $similarity > 0.4;

            // Если найден похожий вопрос, используем его ответ
            if ($hasSimilarQuestion) {
                $answer = $similarQuestion->answer ? 'Да' : 'Нет';
                return $answer . "\n\nКоту Харитону был задан вопрос: "
                    . $similarQuestion->question . "\nКот Харитон ответил: " . $answer;
            }

            // Если похожего вопроса нет, используем ИИ
            $prompt = $this->generatePromptWithSimilarQuestions($question);
            $aiResponse = $this->generateResponse($prompt);

            if (!$aiResponse) {
                return "Не знаю\n\nКоту Харитону был задан вопрос: " . $question . "\nКот Харитон ответил: Не знаю";
            }

            return $aiResponse . "\n\nОтвет основан на имеющихся в базе данных вопросах/ответах кота Харитона";
        } catch (\Exception $e) {
            Log::error('Error asking AI question', [
                'question' => $question,
                'error' => $e->getMessage(),
            ]);
            return null;
        }
    }

    /**
     * Ask a question with enhanced similarity search using ChatGPT fallback
     */
    public function askWithEnhancedSearch(string $question): ?string
    {
        try {
            // Use enhanced topic finder service for better question matching
            $enhancedTopicFinder = app(\App\Services\EnhancedTopicFinderService::class);
            $similarQuestion = $enhancedTopicFinder->findMostSimilarQuestionWithAI($question);

            // Если найден похожий вопрос, используем его ответ
            if ($similarQuestion) {
                $answer = $similarQuestion->answer ? 'Да' : 'Нет';
                return $answer . "\n\nКоту Харитону был задан вопрос: "
                    . $similarQuestion->question . "\nКот Харитон ответил: " . $answer;
            }

            // Если похожего вопроса нет, используем ИИ
            $prompt = $this->generatePromptWithSimilarQuestions($question);
            $aiResponse = $this->generateResponse($prompt);

            if (!$aiResponse) {
                return "Не знаю\n\nКоту Харитону был задан вопрос: " . $question . "\nКот Харитон ответил: Не знаю";
            }

            return $aiResponse . "\n\nОтвет основан на имеющихся в базе данных вопросах/ответах кота Харитона";
        } catch (\Exception $e) {
            Log::error('Error asking AI question with enhanced search', [
                'question' => $question,
                'error' => $e->getMessage(),
            ]);
            return null;
        }
    }

    /**
     * Generate response using OpenAI API
     */
    public function generateResponse(
        string $prompt,
        string $model = 'gpt-4o-mini',
        int $maxTokens = 2000,
        float $temperature = 0.1
    ): string {
        try {
            $response = Http::withHeaders([
                'Authorization' => 'Bearer ' . $this->apiKey,
                'Content-Type' => 'application/json',
            ])->post($this->baseUrl . '/chat/completions', [
                'model' => $model,
                'messages' => [
                    [
                        'role' => 'system',
                        'content' => 'Ты должен отвечать только "Да", "Нет" или "Не знаю".'
                            . 'Если ты не уверен в ответе, отвечай "Не знаю".'
                            . 'Никаких дополнительных объяснений или текста.'
                    ],
                    [
                        'role' => 'user',
                        'content' => $prompt
                    ]
                ],
                'max_tokens' => $maxTokens,
                'temperature' => $temperature
            ]);

            if ($response->successful()) {
                $data = $response->json();
                return trim($data['choices'][0]['message']['content']);
            } else {
                Log::error('OpenAI API error', [
                    'status' => $response->status(),
                    'response' => $response->body()
                ]);
                throw new \Exception('Failed to generate response: ' . $response->status());
            }
        } catch (\Exception $e) {
            Log::error('OpenAI service error', ['error' => $e->getMessage()]);
            throw $e;
        }
    }
}
