<?php

namespace Tests\Feature;

use App\Services\GeminiChatbotService;
use Tests\TestCase;

class ChatbotEquipmentOverheatingTest extends TestCase
{
    public function test_chatbot_provides_protocol_when_equipment_overheats(): void
    {
        $service = app(GeminiChatbotService::class);
        $result = $service->chat('que je fait lorsque mon equippement a surchauffee a cause de temperature');

        $this->assertIsArray($result);
        $this->assertNotEmpty($result['reply']);

        // Vérifier les points clés de sécurité
        $this->assertStringContainsString('Coupure', $result['reply']);
        $this->assertStringContainsString('Choc Thermique', $result['reply']);
        $this->assertStringContainsString('Ventilation', $result['reply']);
        $this->assertStringContainsString('198', $result['reply']);

        // Vérifier le graphique comparatif des seuils de température
        $this->assertNotNull($result['chart']);
        $this->assertEquals('bar', $result['chart']['type']);
    }

    public function test_chatbot_endpoint_responds_for_overheating(): void
    {
        $response = $this->postJson('/chatbot/ask', [
            'message' => 'mon appareil surchauffe avec la chaleur que faire ?',
        ]);

        $response->assertStatus(200);
        $response->assertJson(['success' => true]);
        $reply = $response->json('reply');
        $this->assertStringContainsString('Surchauffe', $reply);
    }
}
