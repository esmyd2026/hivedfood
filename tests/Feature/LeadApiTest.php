<?php

namespace Tests\Feature;

use App\Mail\LeadInternalMail;
use App\Models\BusinessType;
use App\Models\Lead;
use App\Models\SiteSetting;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Mail;
use Tests\TestCase;

class LeadApiTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        Mail::fake();
    }

    /** @return array<string, mixed> */
    private function payload(array $overrides = []): array
    {
        return array_merge([
            'name' => 'Carlos Ruiz',
            'company' => 'Café Central',
            'email' => 'Carlos@CafeCentral.test',
            'phone' => '987654321',
            'message' => 'Quiero una demostración para mi cafetería.',
            'source' => 'demo_request',
            'accepts_marketing' => true,
            'elapsed' => 7500,
        ], $overrides);
    }

    public function test_a_lead_is_stored_from_the_contact_form(): void
    {
        $response = $this->postJson('/api/leads', $this->payload());

        $response->assertCreated();
        $response->assertJsonStructure(['message', 'data' => ['id']]);

        $lead = Lead::query()->firstOrFail();

        $this->assertSame('Carlos Ruiz', $lead->name);
        $this->assertSame('carlos@cafecentral.test', $lead->email, 'The email is normalised to lowercase.');
        $this->assertSame('demo_request', $lead->source);
        $this->assertSame('new', $lead->status);
        $this->assertTrue($lead->accepts_marketing);
    }

    public function test_the_business_type_relation_is_stored(): void
    {
        $type = BusinessType::query()->create([
            'name' => 'Cafetería',
            'slug' => 'cafeteria',
            'is_active' => true,
        ]);

        $this->postJson('/api/leads', $this->payload(['business_type_id' => $type->id]))->assertCreated();

        $this->assertSame($type->id, Lead::query()->value('business_type_id'));
    }

    public function test_required_fields_are_validated(): void
    {
        $response = $this->postJson('/api/leads', ['email' => 'nope', 'elapsed' => 7500]);

        $response->assertUnprocessable();
        $response->assertJsonValidationErrors(['name', 'email']);
        $this->assertDatabaseCount('leads', 0);
    }

    public function test_the_honeypot_blocks_bots(): void
    {
        $response = $this->postJson('/api/leads', $this->payload(['website' => 'spam']));

        $response->assertUnprocessable();
        $response->assertJsonValidationErrors('website');
        $this->assertDatabaseCount('leads', 0);
    }

    public function test_an_unknown_source_is_rejected(): void
    {
        $this->postJson('/api/leads', $this->payload(['source' => 'inventado']))
            ->assertJsonValidationErrors('source');
    }

    public function test_the_internal_notification_is_sent_when_an_address_is_configured(): void
    {
        SiteSetting::query()->create([
            'key' => 'notification_email',
            'type' => 'string',
            'group' => 'contact',
            'value' => 'comercial@hivedfood.test',
        ]);
        SiteSetting::flush();

        $this->postJson('/api/leads', $this->payload())->assertCreated();

        Mail::assertSent(LeadInternalMail::class);
    }

    public function test_no_notification_is_attempted_without_a_configured_address(): void
    {
        $this->postJson('/api/leads', $this->payload())->assertCreated();

        Mail::assertNothingSent();
    }

    public function test_the_submission_rate_is_limited(): void
    {
        for ($i = 0; $i < 10; $i++) {
            $this->postJson('/api/leads', $this->payload(['email' => "lead{$i}@test.test"]))->assertCreated();
        }

        $this->postJson('/api/leads', $this->payload(['email' => 'extra@test.test']))
            ->assertStatus(429);
    }
}
