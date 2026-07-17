<?php

namespace Tests\Feature;

use App\Models\User;
use App\Models\AccreditingBody;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AccreditingBodyAreasTest extends TestCase
{
    use RefreshDatabase;

    protected $admin;

    protected function setUp(): void
    {
        parent::setUp();

        $this->admin = User::factory()->create([
            'usertype' => 'QA Admin',
            'username' => 'qa_admin_test',
        ]);
    }

    public function test_admin_can_create_accrediting_body_with_areas()
    {
        $response = $this->actingAs($this->admin)->post(route('accrediting-bodies.store'), [
            'name' => 'Philippine Accrediting Association of Schools, Colleges and Universities',
            'code' => 'PAASCU',
            'type' => 'Local',
            'description' => 'PAASCU description',
            'areas' => [
                'Area 1: Leadership and Governance',
                'Area 2: Quality Assurance Systems',
                'Area 3: Resource Management',
            ],
        ]);

        $response->assertRedirect();
        
        $body = AccreditingBody::where('code', 'PAASCU')->first();
        $this->assertNotNull($body);
        $this->assertEquals('PAASCU', $body->code);
        $this->assertCount(3, $body->areas);
        $this->assertEquals('Area 1: Leadership and Governance', $body->areas[0]);
    }

    public function test_empty_areas_are_filtered_when_creating_accrediting_body()
    {
        $response = $this->actingAs($this->admin)->post(route('accrediting-bodies.store'), [
            'name' => 'Accrediting Association of Chartered Colleges and Universities of the Philippines',
            'code' => 'AACCUP',
            'type' => 'Local',
            'description' => 'AACCUP description',
            'areas' => [
                'Area I: Philosophy and Objectives',
                '   ', // empty string
                'Area II: Faculty',
                null,
            ],
        ]);

        $response->assertRedirect();
        
        $body = AccreditingBody::where('code', 'AACCUP')->first();
        $this->assertNotNull($body);
        $this->assertCount(2, $body->areas);
        $this->assertEquals('Area I: Philosophy and Objectives', $body->areas[0]);
        $this->assertEquals('Area II: Faculty', $body->areas[1]);
    }
}
