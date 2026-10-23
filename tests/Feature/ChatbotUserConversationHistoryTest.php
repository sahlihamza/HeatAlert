<?php

namespace Tests\Feature;

use App\Models\ChatbotConversation;
use App\Models\ChatbotMessage;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ChatbotUserConversationHistoryTest extends TestCase
{
    public function test_user_can_send_message_and_conversation_is_created()
    {
        $user = User::factory()->create();

        $response = $this->actingAs($user)->postJson(route('chatbot.ask'), [
            'message' => 'Quelle est la température aujourd\'hui ?',
        ]);

        $response->assertStatus(200);
        $response->assertJsonStructure([
            'success',
            'reply',
            'conversation_id',
            'conversation_title',
        ]);

        $convId = $response->json('conversation_id');

        $this->assertDatabaseHas('chatbot_conversations', [
            'id'      => $convId,
            'user_id' => $user->id,
        ]);

        $this->assertDatabaseHas('chatbot_messages', [
            'chatbot_conversation_id' => $convId,
            'role'                    => 'user',
            'content'                 => 'Quelle est la température aujourd\'hui ?',
        ]);

        $this->assertDatabaseHas('chatbot_messages', [
            'chatbot_conversation_id' => $convId,
            'role'                    => 'bot',
        ]);
    }

    public function test_each_user_has_their_own_isolated_conversation_history()
    {
        $userA = User::factory()->create();
        $userB = User::factory()->create();

        // User A creates a conversation
        $convA = ChatbotConversation::create([
            'user_id' => $userA->id,
            'title'   => 'Discussion secrète de User A',
        ]);
        ChatbotMessage::create([
            'chatbot_conversation_id' => $convA->id,
            'role'                    => 'user',
            'content'                 => 'Message de A',
        ]);

        // User B creates a conversation
        $convB = ChatbotConversation::create([
            'user_id' => $userB->id,
            'title'   => 'Discussion publique de User B',
        ]);

        // When User A fetches history, they ONLY see convA
        $responseA = $this->actingAs($userA)->getJson(route('chatbot.conversations'));
        $responseA->assertStatus(200);
        $responseA->assertJsonCount(1, 'conversations');
        $this->assertEquals($convA->id, $responseA->json('conversations.0.id'));

        // When User B fetches history, they ONLY see convB
        $responseB = $this->actingAs($userB)->getJson(route('chatbot.conversations'));
        $responseB->assertStatus(200);
        $responseB->assertJsonCount(1, 'conversations');
        $this->assertEquals($convB->id, $responseB->json('conversations.0.id'));

        // User B cannot access User A's conversation messages
        $forbiddenResponse = $this->actingAs($userB)->getJson(route('chatbot.conversations.show', $convA->id));
        $forbiddenResponse->assertStatus(404);
    }

    public function test_user_can_delete_their_conversation()
    {
        $user = User::factory()->create();
        $conv = ChatbotConversation::create([
            'user_id' => $user->id,
            'title'   => 'Discussion à supprimer',
        ]);
        ChatbotMessage::create([
            'chatbot_conversation_id' => $conv->id,
            'role'                    => 'user',
            'content'                 => 'A supprimer',
        ]);

        $deleteResponse = $this->actingAs($user)->deleteJson(route('chatbot.conversations.delete', $conv->id));
        $deleteResponse->assertStatus(200);
        $deleteResponse->assertJson(['success' => true]);

        $this->assertDatabaseMissing('chatbot_conversations', [
            'id' => $conv->id,
        ]);
        $this->assertDatabaseMissing('chatbot_messages', [
            'chatbot_conversation_id' => $conv->id,
        ]);
    }

    public function test_user_can_view_conversation_messages_and_charts()
    {
        $user = User::factory()->create();
        $conv = ChatbotConversation::create([
            'user_id' => $user->id,
            'title'   => 'Visualisation canicule',
        ]);

        ChatbotMessage::create([
            'chatbot_conversation_id' => $conv->id,
            'role'                    => 'user',
            'content'                 => 'Graphique température',
        ]);

        ChatbotMessage::create([
            'chatbot_conversation_id' => $conv->id,
            'role'                    => 'bot',
            'content'                 => 'Voici le graphique demandé',
            'chart_data'              => [
                'type' => 'line',
                'title' => 'Évolution',
                'labels' => ['08h', '12h', '16h'],
                'datasets' => [['data' => [28, 38, 42]]],
            ],
        ]);

        $response = $this->actingAs($user)->getJson(route('chatbot.conversations.show', $conv->id));
        $response->assertStatus(200);
        $response->assertJson([
            'success' => true,
            'conversation' => ['id' => $conv->id, 'title' => 'Visualisation canicule'],
        ]);
        $response->assertJsonCount(2, 'messages');
        $this->assertEquals('line', $response->json('messages.1.chart.type'));
    }

    public function test_user_can_clear_all_conversations()
    {
        $user = User::factory()->create();
        ChatbotConversation::create(['user_id' => $user->id, 'title' => 'Chat 1']);
        ChatbotConversation::create(['user_id' => $user->id, 'title' => 'Chat 2']);

        $this->assertEquals(2, $user->chatbotConversations()->count());

        $response = $this->actingAs($user)->deleteJson(route('chatbot.conversations.clear'));
        $response->assertStatus(200);
        $response->assertJson(['success' => true]);

        $this->assertEquals(0, $user->chatbotConversations()->count());
    }
}
