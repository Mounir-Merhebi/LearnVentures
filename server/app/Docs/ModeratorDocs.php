<?php

namespace App\Docs;

use OpenApi\Annotations as OA;

/**
 * @OA\Schema(
 *   schema="ChangeProposal",
 *   type="object",
 *   @OA\Property(property="id", type="integer", example=1),
 *   @OA\Property(property="moderator_id", type="integer", example=2),
 *   @OA\Property(property="scope", type="object",
 *     @OA\Property(property="grade_id", type="integer", example=3),
 *     @OA\Property(property="tables", type="array", @OA\Items(type="string", enum={"users", "subjects", "chapters", "lessons"}))
 *   ),
 *   @OA\Property(property="excel_hash", type="string", example="a1b2c3d4e5f6789012345678901234567890123456789012345678901234567890"),
 *   @OA\Property(property="excel_path", type="string", nullable=true, example="/storage/uploads/proposal_123.xlsx"),
 *   @OA\Property(property="status", type="string", enum={"pending", "approved", "rejected"}, example="pending"),
 *   @OA\Property(property="admin_decision", type="string", nullable=true),
 *   @OA\Property(property="decided_at", type="string", format="date-time", nullable=true),
 *   @OA\Property(property="created_at", type="string", format="date-time"),
 *   @OA\Property(property="updated_at", type="string", format="date-time")
 * )
 *
 * @OA\Schema(
 *   schema="BaselineData",
 *   type="object",
 *   @OA\Property(property="scope", type="object",
 *     @OA\Property(property="grade_id", type="integer", example=3),
 *     @OA\Property(property="tables", type="array", @OA\Items(type="string"))
 *   ),
 *   @OA\Property(property="data", type="object",
 *     @OA\Property(property="users", type="array", @OA\Items(type="object")),
 *     @OA\Property(property="subjects", type="array", @OA\Items(type="object")),
 *     @OA\Property(property="chapters", type="array", @OA\Items(type="object")),
 *     @OA\Property(property="lessons", type="array", @OA\Items(type="object"))
 *   )
 * )
 *
 * @OA\Schema(
 *   schema="ProposalCreateRequest",
 *   type="object",
 *   required={"scope", "excel_hash", "excel_snapshot", "db_snapshot", "diff_json"},
 *   @OA\Property(property="scope", type="object",
 *     @OA\Property(property="grade_id", type="integer", example=3),
 *     @OA\Property(property="tables", type="array", @OA\Items(type="string", enum={"users", "subjects", "chapters", "lessons"}))
 *   ),
 *   @OA\Property(property="excel_hash", type="string", example="a1b2c3d4e5f6789012345678901234567890123456789012345678901234567890"),
 *   @OA\Property(property="excel_path", type="string", nullable=true, example="/storage/uploads/proposal_123.xlsx"),
 *   @OA\Property(property="excel_snapshot", type="object",
 *     @OA\Property(property="subjects", type="array", @OA\Items(type="object")),
 *     @OA\Property(property="chapters", type="array", @OA\Items(type="object")),
 *     @OA\Property(property="lessons", type="array", @OA\Items(type="object"))
 *   ),
 *   @OA\Property(property="db_snapshot", type="object",
 *     @OA\Property(property="subjects", type="array", @OA\Items(type="object")),
 *     @OA\Property(property="chapters", type="array", @OA\Items(type="object")),
 *     @OA\Property(property="lessons", type="array", @OA\Items(type="object"))
 *   ),
 *   @OA\Property(property="diff_json", type="object",
 *     @OA\Property(property="subjects", type="object",
 *       @OA\Property(property="create", type="array", @OA\Items(type="object")),
 *       @OA\Property(property="update", type="array", @OA\Items(type="object")),
 *       @OA\Property(property="delete", type="array", @OA\Items(type="object"))
 *     ),
 *     @OA\Property(property="chapters", type="object",
 *       @OA\Property(property="create", type="array", @OA\Items(type="object")),
 *       @OA\Property(property="update", type="array", @OA\Items(type="object")),
 *       @OA\Property(property="delete", type="array", @OA\Items(type="object"))
 *     ),
 *     @OA\Property(property="lessons", type="object",
 *       @OA\Property(property="create", type="array", @OA\Items(type="object")),
 *       @OA\Property(property="update", type="array", @OA\Items(type="object")),
 *       @OA\Property(property="delete", type="array", @OA\Items(type="object"))
 *     )
 *   )
 * )
 */
class ModeratorDocs
{
    /**
     * @OA\Get(
     *   path="/api/v0.1/mod/excel/baseline",
     *   summary="Get baseline data for Excel moderation",
     *   tags={"Moderator"},
     *   security={{"bearerAuth":{}}},
     *   @OA\Parameter(
     *     name="scope",
     *     in="query",
     *     required=true,
     *     @OA\Schema(
     *       type="object",
     *       @OA\Property(property="grade_id", type="integer", example=3),
     *       @OA\Property(property="tables", type="array", @OA\Items(type="string", enum={"users", "subjects", "chapters", "lessons"}))
     *     ),
     *     description="Scope definition for baseline data"
     *   ),
     *   @OA\Response(
     *     response=200,
     *     description="Baseline data retrieved successfully",
     *     @OA\JsonContent(ref="#/components/schemas/BaselineData")
     *   ),
     *   @OA\Response(
     *     response=400,
     *     description="Failed to generate baseline",
     *     @OA\JsonContent(
     *       @OA\Property(property="message", type="string", example="Failed to generate baseline"),
     *       @OA\Property(property="error", type="string", example="Database connection error")
     *     )
     *   ),
     *   @OA\Response(
     *     response=403,
     *     description="Access denied - user is not a moderator",
     *     @OA\JsonContent(
     *       @OA\Property(property="message", type="string", example="Access denied")
     *     )
     *   ),
     *   @OA\Response(
     *     response=422,
     *     description="Validation error",
     *     @OA\JsonContent(
     *       @OA\Property(property="message", type="string", example="The scope.grade_id field is required."),
     *       @OA\Property(property="errors", type="object")
     *     )
     *   )
     * )
     */
    public function baseline() {}

    /**
     * @OA\Post(
     *   path="/api/v0.1/mod/proposals",
     *   summary="Create a new change proposal",
     *   tags={"Moderator"},
     *   security={{"bearerAuth":{}}},
     *   @OA\RequestBody(
     *     required=true,
     *     @OA\JsonContent(ref="#/components/schemas/ProposalCreateRequest")
     *   ),
     *   @OA\Response(
     *     response=201,
     *     description="Proposal created successfully",
     *     @OA\JsonContent(
     *       @OA\Property(property="id", type="integer", example=1),
     *       @OA\Property(property="status", type="string", example="pending")
     *     )
     *   ),
     *   @OA\Response(
     *     response=409,
     *     description="Duplicate proposal",
     *     @OA\JsonContent(
     *       @OA\Property(property="message", type="string", example="Proposal with this Excel hash already exists"),
     *       @OA\Property(property="existing_id", type="integer", example=5)
     *     )
     *   ),
     *   @OA\Response(
     *     response=403,
     *     description="Access denied - user is not a moderator",
     *     @OA\JsonContent(
     *       @OA\Property(property="message", type="string", example="Access denied")
     *     )
     *   ),
     *   @OA\Response(
     *     response=422,
     *     description="Validation error",
     *     @OA\JsonContent(
     *       @OA\Property(property="message", type="string", example="Excel hash must be a valid SHA256 hex string"),
     *       @OA\Property(property="errors", type="object")
     *     )
     *   ),
     *   @OA\Response(
     *     response=500,
     *     description="Failed to create proposal",
     *     @OA\JsonContent(
     *       @OA\Property(property="message", type="string", example="Failed to create proposal"),
     *       @OA\Property(property="error", type="string", example="Database error")
     *     )
     *   )
     * )
     */
    public function storeProposal() {}
}
