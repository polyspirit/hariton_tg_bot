<?php

namespace Tests\Unit;

use Tests\TestCase;
use App\Services\OpenAIService;
use App\Models\Question;
use Illuminate\Foundation\Testing\RefreshDatabase;

class OpenAIServiceTest extends TestCase
{
    use RefreshDatabase;

    public function testGeneratePromptWithSimilarQuestions(): void
    {
        // Skip if OpenAI key is not configured
        if (!env('OPEN_AI_KEY')) {
            $this->markTestSkipped('OpenAI API key not configured');
        }

        // Create test questions
        Question::create([
            'question' => 'Существует ли Бог?',
            'answer' => true,
        ]);

        Question::create([
            'question' => 'Существует ли любовь?',
            'answer' => true,
        ]);

        $service = app(OpenAIService::class);
        $prompt = $service->generatePromptWithSimilarQuestions('Существует ли счастье?');

        // Check that prompt contains the correct format
        $this->assertStringContainsString('Коту Харитону был задан вопрос:', $prompt);
        $this->assertStringContainsString('Кот Харитон ответил:', $prompt);
        $this->assertStringContainsString('Существует ли Бог?', $prompt);
        $this->assertStringContainsString('Существует ли любовь?', $prompt);
    }

    public function testAskMethodReturnsCorrectFormat(): void
    {
        // Skip if OpenAI key is not configured
        if (!env('OPEN_AI_KEY')) {
            $this->markTestSkipped('OpenAI API key not configured');
        }

        // Create test questions
        Question::create([
            'question' => 'Существует ли Бог?',
            'answer' => true,
        ]);

        Question::create([
            'question' => 'Существует ли любовь?',
            'answer' => true,
        ]);

        $service = app(OpenAIService::class);
        $response = $service->ask('Существует ли счастье?');

        // Check that response is in the correct format
        if ($response) {
            $this->assertStringContainsString('Да', $response);
            // Response should contain either the specific question format or the general explanation
            $this->assertTrue(
                str_contains($response, 'Коту Харитону был задан вопрос:') ||
                str_contains($response, 'Ответ основан на имеющихся в базе данных')
            );
        }
    }

    public function testExtractKeywords(): void
    {
        $service = app(OpenAIService::class);

        // Use reflection to access private method
        $reflection = new \ReflectionClass($service);
        $method = $reflection->getMethod('extractKeywords');
        $method->setAccessible(true);

        $keywords = $method->invoke($service, 'Существует ли любовь?');

        $this->assertIsArray($keywords);
        $this->assertContains('существует', $keywords);
        $this->assertContains('любовь?', $keywords);
    }

    public function testCalculateSimilarity(): void
    {
        $service = app(OpenAIService::class);

        // Use reflection to access private method
        $reflection = new \ReflectionClass($service);
        $method = $reflection->getMethod('calculateSimilarity');
        $method->setAccessible(true);

        $similarity = $method->invoke($service, ['любовь', 'счастье'], ['любовь', 'радость']);

        $this->assertIsFloat($similarity);
        $this->assertGreaterThan(0, $similarity);
        $this->assertLessThanOrEqual(1, $similarity);
    }

    public function testAskWithExactQuestionInDatabase(): void
    {
        // Skip if OpenAI key is not configured
        if (!env('OPEN_AI_KEY')) {
            $this->markTestSkipped('OpenAI API key not configured');
        }

        // Create test question that will be found exactly
        Question::create([
            'question' => 'Существует ли тест?',
            'answer' => true,
        ]);

        $service = app(OpenAIService::class);
        $response = $service->ask('Существует ли тест?');

        // Check that response contains the specific question format
        if ($response) {
            $this->assertStringContainsString('Да', $response);
            $this->assertStringContainsString('Коту Харитону был задан вопрос: Существует ли тест?', $response);
            $this->assertStringContainsString('Кот Харитон ответил: Да', $response);
        }
    }

    public function testAskWithNoSimilarQuestion(): void
    {
        // Skip if OpenAI key is not configured
        if (!env('OPEN_AI_KEY')) {
            $this->markTestSkipped('OpenAI API key not configured');
        }

        // Create test question that won't be similar to the asked question
        Question::create([
            'question' => 'Существует ли Бог?',
            'answer' => true,
        ]);

        $service = app(OpenAIService::class);
        $response = $service->ask('Какой цвет у неба?');

        // Check that response contains the general explanation
        if ($response) {
            $this->assertStringContainsString('Ответ основан на имеющихся в базе данных', $response);
        }
    }
}
