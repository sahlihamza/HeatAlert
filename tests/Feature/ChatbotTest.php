<?php

namespace Tests\Feature;

use App\Models\User;
use App\Services\GeminiChatbotService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ChatbotTest extends TestCase
{
    public function test_chatbot_service_generates_response_with_chart(): void
    {
        $service = app(GeminiChatbotService::class);
        $result = $service->chat('Donne-moi les statistiques et graphiques des alertes météo');

        $this->assertIsArray($result);
        $this->assertArrayHasKey('reply', $result);
        $this->assertArrayHasKey('chart', $result);
        $this->assertArrayHasKey('suggestions', $result);
        $this->assertNotEmpty($result['reply']);
        $this->assertNotNull($result['chart']);
        $this->assertEquals('doughnut', $result['chart']['type'] ?? null);
    }

    public function test_chatbot_ask_endpoint_responds_successfully(): void
    {
        $response = $this->postJson('/chatbot/ask', [
            'message' => 'Quels sont les feux récents détectés par la NASA ?',
        ]);

        $response->assertStatus(200);
        $response->assertJsonStructure([
            'success',
            'reply',
            'chart',
            'suggestions',
            'timestamp',
        ]);
        $response->assertJson([
            'success' => true,
        ]);
    }

    public function test_chatbot_quick_stats_endpoint(): void
    {
        $response = $this->getJson('/chatbot/stats');

        $response->assertStatus(200);
        $response->assertJsonStructure([
            'success',
            'stats',
            'date',
        ]);
    }
}
