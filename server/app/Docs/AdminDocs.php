<?php

namespace App\Docs;

use OpenApi\Annotations as OA;

/**
 * @OA\Schema(
 *   schema="ProposalSummary",
 *   type="object",
 *   @OA\Property(property="id", type="integer", example=1),
 *   @OA\Property(property="moderator", type="object",
 *     @OA\Property(property="id", type="integer", example=2),
 *     @OA\Property(property="name", type="string", example="John Moderator")
 *   ),
 *   @OA\Property(property="created_at", type="string", format="date-time"),
 *   @OA\Property(property="status", type="string", enum={"pending", "approved", "rejected", "applied", "failed"}, example="pending"),
 *   @OA\Property(property="summary", type="object",
 *     @OA\Property(property="subjects", type="object",
 *       @OA\Property(property="create", type="integer", example=2),
 *       @OA\Property(property="update", type="integer", example=1),
 *       @OA\Property(property="delete", type="integer", example=0)
 *     ),
 *     @OA\Property(property="chapters", type="object",
 *       @OA\Property(property="create", type="integer", example=5),
 *       @OA\Property(property="update", type="integer", example=3),
 *       @OA\Property(property="delete", type="integer", example=1)
 *     ),
 *     @OA\Property(property="lessons", type="object",
 *       @OA\Property(property="create", type="integer", example=10),
 *       @OA\Property(property="update", type="integer", example=8),
 *       @OA\Property(property="delete", type="integer", example=2)
 *     )
 *   )
 * )
 *
 * @OA\Schema(
 *   schema="ProposalDetail",
 *   type="object",
 *   @OA\Property(property="id", type="integer", example=1),
 *   @OA\Property(property="moderator", type="object",
 *     @OA\Property(property="id", type="integer", example=2),
 *     @OA\Property(property="name", type="string", example="John Moderator"),
 *     @OA\Property(property="email", type="string", format="email", example="john@moderator.com")
 *   ),
 *   @OA\Property(property="scope", type="object",
 *     @OA\Property(property="grade_id", type="integer", example=3),
 *     @OA\Property(property="tables", type="array", @OA\Items(type="string"))
 *   ),
 *   @OA\Property(property="excel_snapshot", type="object"),
 *   @OA\Property(property="db_snapshot", type="object"),
 *   @OA\Property(property="diff_json", type="object"),
 *   @OA\Property(property="status", type="string", example="pending"),
 *   @OA\Property(property="excel_path", type="string", nullable=true),
 *   @OA\Property(property="created_at", type="string", format="date-time"),
 *   @OA\Property(property="updated_at", type="string", format="date-time"),
 *   @OA\Property(property="decided_by", type="object", nullable=true,
 *     @OA\Property(property="id", type="integer", example=1),
 *     @OA\Property(property="name", type="string", example="Admin User")
 *   )
 * )
 *
 * @OA\Schema(
 *   schema="DecisionRequest",
 *   type="object",
 *   required={"action"},
 *   @OA\Property(property="action", type="string", enum={"approve", "reject"}, example="approve")
 * )
 */
class AdminDocs
{
    /**
     * @OA\Get(
     *   path="/api/v0.1/admin/proposals",
     *   summary="Get paginated list of change proposals",
     *   tags={"Admin"},
     *   security={{"bearerAuth":{}}},
     *   @OA\Parameter(
     *     name="status",
     *     in="query",
     *     @OA\Schema(type="string", enum={"pending", "approved", "rejected", "applied", "failed"}),
     *     description="Filter proposals by status (defaults to pending)"
     *   ),
     *   @OA\Parameter(
     *     name="per_page",
     *     in="query",
     *     @OA\Schema(type="integer", minimum=1, maximum=100, default=15),
     *     description="Number of proposals per page"
     *   ),
     *   @OA\Response(
     *     response=200,
     *     description="Proposals retrieved successfully",
     *     @OA\JsonContent(
     *       @OA\Property(property="data", type="array", @OA\Items(ref="#/components/schemas/ProposalSummary")),
     *       @OA\Property(property="meta", type="object",
     *         @OA\Property(property="current_page", type="integer", example=1),
     *         @OA\Property(property="last_page", type="integer", example=3),
     *         @OA\Property(property="per_page", type="integer", example=15),
     *         @OA\Property(property="total", type="integer", example=42)
     *       )
     *     )
     *   ),
     *   @OA\Response(
     *     response=403,
     *     description="Access denied - user is not an admin",
     *     @OA\JsonContent(
     *       @OA\Property(property="message", type="string", example="Access denied")
     *     )
     *   ),
     *   @OA\Response(
     *     response=422,
     *     description="Validation error",
     *     @OA\JsonContent(
     *       @OA\Property(property="message", type="string", example="The status must be one of: pending, approved, rejected, applied, failed."),
     *       @OA\Property(property="errors", type="object")
     *     )
     *   )
     * )
     */
    public function index() {}

    /**
     * @OA\Get(
     *   path="/api/v0.1/admin/proposals/{id}",
     *   summary="Get detailed information about a specific proposal",
     *   tags={"Admin"},
     *   security={{"bearerAuth":{}}},
     *   @OA\Parameter(
     *     name="id",
     *     in="path",
     *     required=true,
     *     @OA\Schema(type="integer", example=1),
     *     description="Proposal ID"
     *   ),
     *   @OA\Response(
     *     response=200,
     *     description="Proposal details retrieved successfully",
     *     @OA\JsonContent(ref="#/components/schemas/ProposalDetail")
     *   ),
     *   @OA\Response(
     *     response=403,
     *     description="Access denied - user is not an admin",
     *     @OA\JsonContent(
     *       @OA\Property(property="message", type="string", example="Access denied")
     *     )
     *   ),
     *   @OA\Response(
     *     response=404,
     *     description="Proposal not found",
     *     @OA\JsonContent(
     *       @OA\Property(property="message", type="string", example="Not found")
     *     )
     *   )
     * )
     */
    public function show() {}

    /**
     * @OA\Post(
     *   path="/api/v0.1/admin/proposals/{id}/decision",
     *   summary="Approve or reject a change proposal",
     *   tags={"Admin"},
     *   security={{"bearerAuth":{}}},
     *   @OA\Parameter(
     *     name="id",
     *     in="path",
     *     required=true,
     *     @OA\Schema(type="integer", example=1),
     *     description="Proposal ID"
     *   ),
     *   @OA\RequestBody(
     *     required=true,
     *     @OA\JsonContent(ref="#/components/schemas/DecisionRequest")
     *   ),
     *   @OA\Response(
     *     response=200,
     *     description="Decision applied successfully",
     *     @OA\JsonContent(
     *       @OA\Property(property="id", type="integer", example=1),
     *       @OA\Property(property="status", type="string", enum={"approved", "rejected", "applied", "failed"}, example="applied")
     *     )
     *   ),
     *   @OA\Response(
     *     response=409,
     *     description="Conflict - proposal already decided or drift detected",
     *     @OA\JsonContent(
     *       @OA\Property(property="message", type="string", example="Proposal has already been decided"),
     *       @OA\Property(property="current_status", type="string", example="approved"),
     *       @OA\Property(property="error", type="string", enum={"drift_detected"}, example="drift_detected")
     *     )
     *   ),
     *   @OA\Response(
     *     response=403,
     *     description="Access denied - user is not an admin",
     *     @OA\JsonContent(
     *       @OA\Property(property="message", type="string", example="Access denied")
     *     )
     *   ),
     *   @OA\Response(
     *     response=404,
     *     description="Proposal not found",
     *     @OA\JsonContent(
     *       @OA\Property(property="message", type="string", example="Not found")
     *     )
     *   ),
     *   @OA\Response(
     *     response=422,
     *     description="Validation error",
     *     @OA\JsonContent(
     *       @OA\Property(property="message", type="string", example="Action must be either approve or reject"),
     *       @OA\Property(property="errors", type="object")
     *     )
     *   ),
     *   @OA\Response(
     *     response=500,
     *     description="Failed to apply changes",
     *     @OA\JsonContent(
     *       @OA\Property(property="message", type="string", example="Failed to apply proposal changes"),
     *       @OA\Property(property="error", type="string", example="Database transaction failed")
     *     )
     *   )
     * )
     */
    public function decision() {}
}
