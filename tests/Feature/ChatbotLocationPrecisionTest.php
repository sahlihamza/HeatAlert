<?php

namespace Tests\Feature;

use App\Services\GeminiChatbotService;
use Tests\TestCase;

class ChatbotLocationPrecisionTest extends TestCase
{
    public function test_chatbot_answers_specifically_for_hammamet(): void
    {
        $service = app(GeminiChatbotService::class);
        $result = $service->chat('Météo de Hammamet');

        $this->assertIsArray($result);
        $this->assertNotEmpty($result['reply']);

        // Vérifier que la réponse cible Hammamet
        $this->assertStringContainsString('Hammamet', $result['reply']);

        // Vérifier qu'elle ne cite PAS de villes hors-sujet
        $this->assertStringNotContainsString('Sidi Bouzid', $result['reply']);
        $this->assertStringNotContainsString('Jendouba', $result['reply']);

        // Vérifier qu'un graphique de température pour Hammamet est fourni
        $this->assertNotNull($result['chart']);
        $this->assertEquals('line', $result['chart']['type']);
        $this->assertStringContainsString('Hammamet', $result['chart']['title']);
    }

    public function test_chatbot_endpoint_with_hammamet(): void
    {
        $response = $this->postJson('/chatbot/ask', [
            'message' => 'Quelle est la meteo a Hammamet aujourd hui ?',
        ]);

        $response->assertStatus(200);
        $response->assertJson([
            'success' => true,
        ]);
        $reply = $response->json('reply');
        $this->assertStringContainsString('Hammamet', $reply);
    }
}
