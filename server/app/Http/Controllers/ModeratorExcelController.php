<?php

namespace App\Http\Controllers;

use App\Models\ChangeProposal;
use App\Services\BaselineSerializer;
use App\Http\Requests\StoreProposalRequest;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;

class ModeratorExcelController extends Controller
{
    public function __construct(
        private BaselineSerializer $baselineSerializer
    ) {}

    /**
     * Get baseline data for the specified scope
     */
    public function baseline(Request $request): JsonResponse
    {
        $request->validate([
            'scope' => 'required|array',
            'scope.grade_id' => 'integer|exists:grades,id',
            'scope.tables' => 'required|array',
            'scope.tables.*' => 'string|in:users,subjects,chapters,lessons',
        ]);

        try {
            $result = $this->baselineSerializer->serialize($request->input('scope'));

            return response()->json($result, 200);
        } catch (\Exception $e) {
            return response()->json([
                'message' => 'Failed to generate baseline',
                'error' => $e->getMessage()
            ], 400);
        }
    }

    /**
     * Store a new change proposal
     */
    public function storeProposal(StoreProposalRequest $request): JsonResponse
    {
        try {
            // Check for duplicate excel_hash
            $existingProposal = ChangeProposal::where('excel_hash', $request->input('excel_hash'))->first();
            if ($existingProposal) {
                return response()->json([
                    'message' => 'Proposal with this Excel hash already exists',
                    'existing_id' => $existingProposal->id
                ], 409);
            }

            // Create the proposal
            $proposal = ChangeProposal::create([
                'moderator_id' => auth()->id(),
                'scope' => $request->input('scope'),
                'excel_hash' => $request->input('excel_hash'),
                'excel_path' => $request->input('excel_path'),
                'excel_snapshot' => $request->input('excel_snapshot'),
                'db_snapshot' => $request->input('db_snapshot'),
                'diff_json' => $request->input('diff_json'),
                'status' => 'pending',
            ]);

            return response()->json([
                'id' => $proposal->id,
                'status' => $proposal->status
            ], 201);

        } catch (\Exception $e) {
            return response()->json([
                'message' => 'Failed to create proposal',
                'error' => $e->getMessage()
            ], 500);
        }
    }
}
