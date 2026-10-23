<?php

namespace Tests\Feature;

use App\Services\GeminiChatbotService;
use Tests\TestCase;

class ChatbotVoiceAndLanguageTest extends TestCase
{
    public function test_chatbot_responds_to_tunisian_dialect()
    {
        $service = app(GeminiChatbotService::class);
        $result = $service->chat('عسلامة شنوة نعمل في السخانة');

        $this->assertIsArray($result);
        $this->assertNotEmpty($result['reply']);

        // Vérifier la présence de recommandations en arabe / tunisien et des urgences
        $this->assertTrue(
            str_contains($result['reply'], '198') ||
            str_contains($result['reply'], '190') ||
            str_contains($result['reply'], 'الماء') ||
            str_contains($result['reply'], 'سخانة')
        );
    }

    public function test_chatbot_responds_to_power_outage_in_arabic_or_derja()
    {
        $service = app(GeminiChatbotService::class);
        $result = $service->chat('فما قصان ضو في حومتي شنوة نعمل');

        $this->assertIsArray($result);
        $this->assertNotEmpty($result['reply']);

        $this->assertTrue(
            str_contains($result['reply'], 'الضو') ||
            str_contains($result['reply'], 'الكهرباء') ||
            str_contains($result['reply'], 'الثلاجة') ||
            str_contains($result['reply'], '198')
        );
    }

    public function test_chatbot_endpoint_handles_multilingual_voice_text()
    {
        $response = $this->postJson(route('chatbot.ask'), [
            'message' => 'عسلامة شنوة الطقس اليوم في تونس',
        ]);

        $response->assertStatus(200);
        $response->assertJson(['success' => true]);
        $this->assertNotEmpty($response->json('reply'));
    }
}
