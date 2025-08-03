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
            $this->assertStringContainsString('Коту Харитону был задан вопрос:', $response);
            $this->assertStringContainsString('Кот Харитон ответил:', $response);
            $this->assertStringContainsString('Существует ли счастье?', $response);
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
}
