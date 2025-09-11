<?php

namespace Tests\Feature;

use App\Models\User;
use App\Models\ChangeProposal;
use App\Models\Subject;
use App\Models\Grade;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Foundation\Testing\WithFaker;
use Tests\TestCase;
use Illuminate\Support\Facades\DB;

class ExcelModerationTest extends TestCase
{
    use RefreshDatabase;

    private $moderator;
    private $admin;
    private $grade;

    protected function setUp(): void
    {
        parent::setUp();

        // Create test users
        $this->moderator = User::factory()->create([
            'role' => 'Moderator',
            'email' => 'moderator@test.com'
        ]);

        $this->admin = User::factory()->create([
            'role' => 'Admin',
            'email' => 'admin@test.com'
        ]);

        // Create test grade
        $this->grade = Grade::factory()->create([
            'name' => 'Grade 7',
            'level' => 7
        ]);
    }

    /**
     * Test duplicate excel_hash prevents duplicate proposals
     */
    public function test_duplicate_excel_hash_is_rejected()
    {
        $this->actingAs($this->moderator, 'api');

        $payload = [
            'scope' => [
                'grade_id' => $this->grade->id,
                'tables' => ['subjects']
            ],
            'excel_hash' => 'a665a45920422f9d417e4867efdc4fb8a04a1f3fff1fa07e998e86f7f7a27ae3',
            'excel_snapshot' => ['subjects' => []],
            'db_snapshot' => ['subjects' => []],
            'diff_json' => ['subjects' => ['create' => [], 'update' => [], 'delete' => []]]
        ];

        // Create first proposal
        $response = $this->postJson('/api/v0.1/mod/proposals', $payload);
        $response->assertStatus(201);

        // Try to create duplicate
        $response = $this->postJson('/api/v0.1/mod/proposals', $payload);
        $response->assertStatus(409);
        $response->assertJson(['message' => 'Proposal with this Excel hash already exists']);
    }

    /**
     * Test proposal approval applies changes atomically
     */
    public function test_proposal_approval_applies_changes_atomically()
    {
        $this->actingAs($this->admin, 'api');

        // Create a proposal with create operations
        $proposal = ChangeProposal::create([
            'moderator_id' => $this->moderator->id,
            'scope' => [
                'grade_id' => $this->grade->id,
                'tables' => ['subjects']
            ],
            'excel_hash' => 'a665a45920422f9d417e4867efdc4fb8a04a1f3fff1fa07e998e86f7f7a27ae3',
            'excel_snapshot' => ['subjects' => []],
            'db_snapshot' => ['subjects' => []],
            'diff_json' => [
                'subjects' => [
                    'create' => [
                        [
                            'title' => 'Test Subject',
                            'grade_id' => $this->grade->id,
                            'instructor_id' => $this->moderator->id,
                            'description' => 'Test description'
                        ]
                    ],
                    'update' => [],
                    'delete' => []
                ]
            ],
            'status' => 'pending'
        ]);

        // Verify subject doesn't exist yet
        $this->assertDatabaseMissing('subjects', ['title' => 'Test Subject']);

        // Approve the proposal
        $response = $this->postJson("/api/v0.1/admin/proposals/{$proposal->id}/decision", [
            'action' => 'approve'
        ]);

        $response->assertStatus(200);
        $response->assertJson(['status' => 'applied']);

        // Verify subject was created
        $this->assertDatabaseHas('subjects', [
            'title' => 'Test Subject',
            'grade_id' => $this->grade->id,
            'description' => 'Test description'
        ]);

        // Verify proposal status
        $proposal->refresh();
        $this->assertEquals('applied', $proposal->status);
        $this->assertEquals($this->admin->id, $proposal->decided_by);
    }

    /**
     * Test proposal rejection leaves database unchanged
     */
    public function test_proposal_rejection_leaves_database_unchanged()
    {
        $this->actingAs($this->admin, 'api');

        // Create initial subject
        $subject = Subject::create([
            'title' => 'Original Subject',
            'grade_id' => $this->grade->id,
            'instructor_id' => $this->moderator->id,
            'description' => 'Original description'
        ]);

        // Create a proposal with update and delete operations
        $proposal = ChangeProposal::create([
            'moderator_id' => $this->moderator->id,
            'scope' => [
                'grade_id' => $this->grade->id,
                'tables' => ['subjects']
            ],
            'excel_hash' => 'b665a45920422f9d417e4867efdc4fb8a04a1f3fff1fa07e998e86f7f7a27ae3',
            'excel_snapshot' => ['subjects' => []],
            'db_snapshot' => ['subjects' => []],
            'diff_json' => [
                'subjects' => [
                    'create' => [],
                    'update' => [
                        [
                            'id' => $subject->id,
                            'title' => 'Updated Subject',
                            'description' => 'Updated description'
                        ]
                    ],
                    'delete' => []
                ]
            ],
            'status' => 'pending'
        ]);

        // Reject the proposal
        $response = $this->postJson("/api/v0.1/admin/proposals/{$proposal->id}/decision", [
            'action' => 'reject'
        ]);

        $response->assertStatus(200);
        $response->assertJson(['status' => 'rejected']);

        // Verify subject was NOT updated
        $this->assertDatabaseHas('subjects', [
            'id' => $subject->id,
            'title' => 'Original Subject',
            'description' => 'Original description'
        ]);

        // Verify proposal status
        $proposal->refresh();
        $this->assertEquals('rejected', $proposal->status);
        $this->assertEquals($this->admin->id, $proposal->decided_by);
    }

    /**
     * Test decision idempotency - can't decide on already decided proposal
     */
    public function test_decision_idempotency_prevents_double_decision()
    {
        $this->actingAs($this->admin, 'api');

        $proposal = ChangeProposal::create([
            'moderator_id' => $this->moderator->id,
            'scope' => [
                'grade_id' => $this->grade->id,
                'tables' => ['subjects']
            ],
            'excel_hash' => 'c665a45920422f9d417e4867efdc4fb8a04a1f3fff1fa07e998e86f7f7a27ae3',
            'excel_snapshot' => ['subjects' => []],
            'db_snapshot' => ['subjects' => []],
            'diff_json' => ['subjects' => ['create' => [], 'update' => [], 'delete' => []]],
            'status' => 'pending'
        ]);

        // First decision - approve
        $response = $this->postJson("/api/v0.1/admin/proposals/{$proposal->id}/decision", [
            'action' => 'approve'
        ]);
        $response->assertStatus(200);

        // Second decision attempt - should fail
        $response = $this->postJson("/api/v0.1/admin/proposals/{$proposal->id}/decision", [
            'action' => 'reject'
        ]);
        $response->assertStatus(409);
        $response->assertJson(['message' => 'Proposal has already been decided']);
    }

    /**
     * Test moderator role authorization for baseline endpoint
     */
    public function test_moderator_can_access_baseline()
    {
        $this->actingAs($this->moderator, 'api');

        $response = $this->getJson('/api/v0.1/mod/excel/baseline?scope[grade_id]=' . $this->grade->id . '&scope[tables][]=subjects');

        $response->assertStatus(200);
        $response->assertJsonStructure([
            'scope',
            'snapshot' => [
                'subjects'
            ]
        ]);
    }

    /**
     * Test student role is denied access to moderator endpoints
     */
    public function test_student_cannot_access_moderator_endpoints()
    {
        $student = User::factory()->create(['role' => 'Student']);

        $this->actingAs($student, 'api');

        $response = $this->getJson('/api/v0.1/mod/excel/baseline');
        $response->assertStatus(403);

        $response = $this->postJson('/api/v0.1/mod/proposals', []);
        $response->assertStatus(403);
    }

    /**
     * Test admin role authorization for admin endpoints
     */
    public function test_admin_can_access_admin_endpoints()
    {
        $this->actingAs($this->admin, 'api');

        $response = $this->getJson('/api/v0.1/admin/proposals');
        $response->assertStatus(200);
    }

    /**
     * Test moderator role is denied access to admin endpoints
     */
    public function test_moderator_cannot_access_admin_endpoints()
    {
        $this->actingAs($this->moderator, 'api');

        $response = $this->getJson('/api/v0.1/admin/proposals');
        $response->assertStatus(403);

        $response = $this->postJson('/api/v0.1/admin/proposals/1/decision', ['action' => 'approve']);
        $response->assertStatus(403);
    }
}
