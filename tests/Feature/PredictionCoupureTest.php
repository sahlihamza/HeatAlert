<?php

namespace Tests\Feature;

use App\Models\PredictionCoupure;
use App\Models\User;
use App\Models\Zone;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

class PredictionCoupureTest extends TestCase
{
    use RefreshDatabase;

    public function test_predictions_page_lists_zones_and_empty_state(): void
    {
        $user = User::factory()->create(['role' => User::ROLE_ADMIN]);
        $zone = Zone::factory()->create(['nom' => 'Zone Test ML']);

        $response = $this->actingAs($user)->get(route('admin.predictions.index'));

        $response->assertOk();
        $response->assertSee('Prévisions de coupures');
        $response->assertSee('Zone Test ML');
        $response->assertSee('Aucune prédiction disponible');
    }

    public function test_prediction_is_saved_from_fastapi_response(): void
    {
        config(['services.heatalert_ml.url' => 'http://ml.test']);
        Http::fake([
            'http://ml.test/predict' => Http::response([
                'probability' => 0.2,
                'risk_level' => null,
                'risk_thresholds_defined' => false,
                'target_window_hours' => 24,
                'target_window_start' => '2026-10-18T12:00:00+00:00',
                'target_window_end_exclusive' => '2026-10-19T12:00:00+00:00',
                'model_version' => 'random_forest:test',
                'warnings' => ['Prototype expérimental'],
            ]),
        ]);

        $user = User::factory()->create(['role' => User::ROLE_ADMIN]);
        $zone = Zone::factory()->create();

        $response = $this->actingAs($user)->postJson(
            route('admin.zones.prediction', $zone),
            ['reference_at' => '2026-10-18 12:00:00']
        );

        $response->assertCreated()->assertJsonPath('success', true);
        $this->assertDatabaseHas('prediction_coupures', [
            'zone_id' => $zone->id,
            'probability' => 0.2,
            'model_version' => 'random_forest:test',
        ]);
    }

    public function test_prediction_service_error_is_returned_without_saving(): void
    {
        config(['services.heatalert_ml.url' => 'http://ml.test']);
        Http::fake(['http://ml.test/predict' => Http::response([], 503)]);

        $user = User::factory()->create(['role' => User::ROLE_ADMIN]);
        $zone = Zone::factory()->create();

        $response = $this->actingAs($user)->postJson(
            route('admin.zones.prediction', $zone)
        );

        $response->assertStatus(503)->assertJsonPath('success', false);
        $this->assertDatabaseCount('prediction_coupures', 0);
    }

    public function test_invalid_reference_date_is_rejected(): void
    {
        $user = User::factory()->create(['role' => User::ROLE_ADMIN]);
        $zone = Zone::factory()->create();

        $response = $this->actingAs($user)->postJson(
            route('admin.zones.prediction', $zone),
            ['reference_at' => 'not-a-date']
        );

        $response->assertUnprocessable();
    }
}
