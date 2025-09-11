<?php

namespace App\Http\Controllers;

use App\Models\ChangeProposal;
use App\Services\BaselineSerializer;
use App\Services\DiffApplier;
use App\Http\Requests\DecisionRequest;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\DB;

class AdminChangeProposalController extends Controller
{
    public function __construct(
        private BaselineSerializer $baselineSerializer,
        private DiffApplier $diffApplier
    ) {}

    /**
     * Get paginated list of change proposals
     */
    public function index(Request $request): JsonResponse
    {
        $request->validate([
            'status' => 'nullable|string|in:pending,approved,rejected,applied,failed',
            'per_page' => 'nullable|integer|min:1|max:100',
        ]);

        $query = ChangeProposal::with(['moderator:id,name,email']);

        if ($request->has('status')) {
            $query->where('status', $request->input('status'));
        } else {
            $query->where('status', 'pending');
        }

        $proposals = $query->orderBy('created_at', 'desc')
            ->paginate($request->input('per_page', 15));

        $data = $proposals->getCollection()->map(function ($proposal) {
            $summary = $this->generateSummary($proposal->diff_json);

            return [
                'id' => $proposal->id,
                'moderator' => [
                    'id' => $proposal->moderator->id,
                    'name' => $proposal->moderator->name,
                ],
                'created_at' => $proposal->created_at->toISOString(),
                'status' => $proposal->status,
                'summary' => $summary,
            ];
        });

        return response()->json([
            'data' => $data,
            'meta' => [
                'current_page' => $proposals->currentPage(),
                'last_page' => $proposals->lastPage(),
                'per_page' => $proposals->perPage(),
                'total' => $proposals->total(),
            ]
        ]);
    }

    /**
     * Get a specific change proposal with full details
     */
    public function show($id): JsonResponse
    {
        $proposal = ChangeProposal::with(['moderator:id,name,email', 'decisionMaker:id,name,email'])
            ->findOrFail($id);

        return response()->json([
            'id' => $proposal->id,
            'moderator' => [
                'id' => $proposal->moderator->id,
                'name' => $proposal->moderator->name,
                'email' => $proposal->moderator->email,
            ],
            'scope' => $proposal->scope,
            'excel_snapshot' => $proposal->excel_snapshot,
            'db_snapshot' => $proposal->db_snapshot,
            'diff_json' => $proposal->diff_json,
            'status' => $proposal->status,
            'excel_path' => $proposal->excel_path,
            'created_at' => $proposal->created_at->toISOString(),
            'updated_at' => $proposal->updated_at->toISOString(),
            'decided_by' => $proposal->decisionMaker ? [
                'id' => $proposal->decisionMaker->id,
                'name' => $proposal->decisionMaker->name,
            ] : null,
        ]);
    }

    /**
     * Approve or reject a change proposal
     */
    public function decision(DecisionRequest $request, $id): JsonResponse
    {
        $proposal = ChangeProposal::findOrFail($id);

        if ($proposal->status !== 'pending') {
            return response()->json([
                'message' => 'Proposal has already been decided',
                'current_status' => $proposal->status
            ], 409);
        }

        $action = $request->input('action');

        if ($action === 'reject') {
            $proposal->update([
                'status' => 'rejected',
                'decided_by' => auth()->id(),
            ]);

            return response()->json([
                'id' => $proposal->id,
                'status' => $proposal->status
            ]);
        }

        // Handle approval
        try {
            // Optional drift check
            if ($this->hasDrift($proposal)) {
                return response()->json([
                    'message' => 'Database has changed since snapshot was taken',
                    'error' => 'drift_detected'
                ], 409);
            }

            // Apply the changes
            DB::transaction(function () use ($proposal) {
                $this->diffApplier->apply($proposal->diff_json);

                $proposal->update([
                    'status' => 'applied',
                    'decided_by' => auth()->id(),
                ]);
            });

            return response()->json([
                'id' => $proposal->id,
                'status' => $proposal->status
            ]);

        } catch (\Exception $e) {
            $proposal->update([
                'status' => 'failed',
            ]);

            return response()->json([
                'message' => 'Failed to apply proposal changes',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Generate summary counts from diff_json
     */
    private function generateSummary(array $diffJson): array
    {
        $summary = [];

        foreach ($diffJson as $table => $operations) {
            $summary[$table] = [
                'create' => count($operations['create'] ?? []),
                'update' => count($operations['update'] ?? []),
                'delete' => count($operations['delete'] ?? []),
            ];
        }

        return $summary;
    }

    /**
     * Check if database has drifted from the stored snapshot
     */
    private function hasDrift(ChangeProposal $proposal): bool
    {
        try {
            $currentSnapshot = $this->baselineSerializer->serialize($proposal->scope);
            return $currentSnapshot['snapshot'] !== $proposal->db_snapshot;
        } catch (\Exception $e) {
            // If we can't generate current snapshot, assume no drift to be safe
            return false;
        }
    }
}
