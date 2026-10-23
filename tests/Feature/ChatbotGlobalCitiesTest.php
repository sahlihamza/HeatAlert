<?php

namespace Tests\Feature;

use App\Services\GeminiChatbotService;
use Tests\TestCase;

class ChatbotGlobalCitiesTest extends TestCase
{
    public function test_chatbot_responds_directly_to_global_city_without_redirection(): void
    {
        $service = app(GeminiChatbotService::class);
        $result = $service->chat('Donne-moi la météo et les statistiques de Paris');

        $this->assertIsArray($result);
        $this->assertNotEmpty($result['reply']);

        // Vérifier que la réponse cite Paris et la France
        $this->assertStringContainsString('Paris', $result['reply']);
        $this->assertStringContainsString('France', $result['reply']);

        // Vérifier l'interdiction de dire "consultez la météo mondiale"
        $this->assertStringNotContainsStringIgnoringCase('consultez météo mondiale', $result['reply']);
        $this->assertStringNotContainsStringIgnoringCase('consultez la météo mondiale', $result['reply']);
        $this->assertStringNotContainsStringIgnoringCase('consultez notre page', $result['reply']);

        // Vérifier qu'un graphique de température pour Paris est fourni
        $this->assertNotNull($result['chart']);
        $this->assertEquals('line', $result['chart']['type']);
        $this->assertStringContainsString('Paris', $result['chart']['title']);
    }

    public function test_chatbot_endpoint_responds_for_tokyo(): void
    {
        $response = $this->postJson('/chatbot/ask', [
            'message' => 'Quel temps fait-il à Tokyo ?',
        ]);

        $response->assertStatus(200);
        $response->assertJson(['success' => true]);

        $reply = $response->json('reply');
        $this->assertStringContainsString('Tokyo', $reply);
        $this->assertStringNotContainsStringIgnoringCase('consultez météo mondiale', $reply);
    }
}
