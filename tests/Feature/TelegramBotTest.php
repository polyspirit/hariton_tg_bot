<?php

namespace Tests\Feature;

use Tests\TestCase;
use App\Services\TelegramBotService;
use App\Services\OpenAIService;
use Illuminate\Foundation\Testing\RefreshDatabase;

class TelegramBotTest extends TestCase
{
    use RefreshDatabase;

    public function testWebhookReturnsOkForValidRequest(): void
    {
        $response = $this->postJson('/telegram/webhook', [
            'update_id' => 123,
            'message' => [
                'message_id' => 1,
                'from' => [
                    'id' => 123456,
                    'first_name' => 'Test User',
                ],
                'chat' => [
                    'id' => 123456,
                ],
                'text' => '/start',
            ],
        ]);

        $response->assertStatus(200);
        $response->assertSee('OK');
    }

    public function testWebhookHandlesMessageWithoutText(): void
    {
        $response = $this->postJson('/telegram/webhook', [
            'update_id' => 124,
            'message' => [
                'message_id' => 2,
                'from' => [
                    'id' => 123456,
                    'first_name' => 'Test User',
                ],
                'chat' => [
                    'id' => 123456,
                ],
            ],
        ]);

        $response->assertStatus(200);
        $response->assertSee('OK');
    }

    public function testWebhookHandlesCallbackQuery(): void
    {
        $response = $this->postJson('/telegram/webhook', [
            'update_id' => 125,
            'callback_query' => [
                'id' => '123',
                'from' => [
                    'id' => 123456,
                    'first_name' => 'Test User',
                ],
                'message' => [
                    'message_id' => 3,
                    'chat' => [
                        'id' => 123456,
                    ],
                ],
                'data' => 'help',
            ],
        ]);

        $response->assertStatus(200);
        $response->assertSee('OK');
    }

    public function testWebhookHandlesInvalidRequest(): void
    {
        $response = $this->postJson('/telegram/webhook', [
            'invalid' => 'data',
        ]);

        $response->assertStatus(200);
        $response->assertSee('OK');
    }

    public function testTelegramServiceCanBeInstantiated(): void
    {
        // Skip if token is not configured
        if (!config('services.telegram.bot_token')) {
            $this->markTestSkipped('Telegram bot token not configured');
        }

        $this->expectNotToPerformAssertions();

        app(TelegramBotService::class);
    }

    public function testOpenaiServiceCanBeInstantiated(): void
    {
        // Skip if OpenAI key is not configured
        if (!env('OPEN_AI_KEY')) {
            $this->markTestSkipped('OpenAI API key not configured');
        }

        $this->expectNotToPerformAssertions();

        app(OpenAIService::class);
    }

    public function testWebhookRouteIsExcludedFromCsrf(): void
    {
        $response = $this->post('/telegram/webhook', [
            'update_id' => 126,
            'message' => [
                'message_id' => 4,
                'from' => [
                    'id' => 123456,
                    'first_name' => 'Test User',
                ],
                'chat' => [
                    'id' => 123456,
                ],
                'text' => '/help',
            ],
        ]);

        // Should not get 419 CSRF error
        $response->assertStatus(200);
    }
}
