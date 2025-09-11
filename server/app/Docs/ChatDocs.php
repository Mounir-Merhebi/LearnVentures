<?php

namespace App\Docs;

use OpenApi\Annotations as OA;

/**
 * @OA\Schema(
 *   schema="ChatSession",
 *   type="object",
 *   @OA\Property(property="id", type="integer", example=1),
 *   @OA\Property(property="user_id", type="integer", example=1),
 *   @OA\Property(property="grade_id", type="integer", example=3),
 *   @OA\Property(property="started_at", type="string", format="date-time"),
 *   @OA\Property(property="ended_at", type="string", format="date-time", nullable=true)
 * )
 *
 * @OA\Schema(
 *   schema="ChatMessage",
 *   type="object",
 *   @OA\Property(property="id", type="integer", example=1),
 *   @OA\Property(property="session_id", type="integer", example=1),
 *   @OA\Property(property="role", type="string", enum={"user", "assistant"}, example="user"),
 *   @OA\Property(property="content", type="string", example="What is photosynthesis?"),
 *   @OA\Property(property="created_at", type="string", format="date-time")
 * )
 *
 * @OA\Schema(
 *   schema="ChatResponse",
 *   type="object",
 *   @OA\Property(property="message_id", type="integer", example=2),
 *   @OA\Property(property="response", type="string", example="Photosynthesis is the process by which plants convert light energy into chemical energy..."),
 *   @OA\Property(property="context_chunks_used", type="integer", example=3),
 *   @OA\Property(property="grade_scope", type="string", example="Grade 10 Science")
 * )
 */
class ChatDocs
{
    /**
     * @OA\Post(
     *   path="/api/v0.1/chat/sessions",
     *   summary="Create a new chat session",
     *   tags={"Chat"},
     *   security={{"bearerAuth":{}}},
     *   @OA\RequestBody(
     *     required=true,
     *     @OA\JsonContent(
     *       required={"grade_id"},
     *       @OA\Property(property="grade_id", type="integer", example=3, description="Grade ID for which the chat session is created")
     *     )
     *   ),
     *   @OA\Response(
     *     response=201,
     *     description="Chat session created successfully",
     *     @OA\JsonContent(
     *       @OA\Property(property="success", type="boolean", example=true),
     *       @OA\Property(property="message", type="string", example="Chat session created successfully"),
     *       @OA\Property(
     *         property="data",
     *         type="object",
     *         @OA\Property(property="session_id", type="integer", example=1),
     *         @OA\Property(property="grade_id", type="integer", example=3),
     *         @OA\Property(property="started_at", type="string", format="date-time")
     *       )
     *     )
     *   ),
     *   @OA\Response(
     *     response=403,
     *     description="Access denied - user not enrolled in grade",
     *     @OA\JsonContent(
     *       @OA\Property(property="success", type="boolean", example=false),
     *       @OA\Property(property="message", type="string", example="You are not enrolled in this grade or your enrollment is not accepted")
     *     )
     *   ),
     *   @OA\Response(
     *     response=422,
     *     description="Validation error",
     *     @OA\JsonContent(
     *       @OA\Property(property="message", type="string", example="The grade_id field is required."),
     *       @OA\Property(property="errors", type="object")
     *     )
     *   )
     * )
     */
    public function createSession() {}

    /**
     * @OA\Post(
     *   path="/api/v0.1/chat/messages",
     *   summary="Send a message and get AI response",
     *   tags={"Chat"},
     *   security={{"bearerAuth":{}}},
     *   @OA\RequestBody(
     *     required=true,
     *     @OA\JsonContent(
     *       required={"session_id","message"},
     *       @OA\Property(property="session_id", type="integer", example=1, description="Chat session ID"),
     *       @OA\Property(property="message", type="string", maxLength=1000, example="What is photosynthesis?", description="User's message/question")
     *     )
     *   ),
     *   @OA\Response(
     *     response=200,
     *     description="Message processed successfully or out of scope response",
     *     @OA\JsonContent(
     *       oneOf={
     *         @OA\Schema(
     *           @OA\Property(property="success", type="boolean", example=true),
     *           @OA\Property(property="data", ref="#/components/schemas/ChatResponse")
     *         ),
     *         @OA\Schema(
     *           @OA\Property(property="success", type="boolean", example=true),
     *           @OA\Property(property="data", type="object",
     *             @OA\Property(property="message_id", type="integer", example=2),
     *             @OA\Property(property="response", type="string", example="Out of scope for this grade."),
     *             @OA\Property(property="context_chunks_used", type="integer", example=0)
     *           )
     *         )
     *       }
     *     )
     *   ),
     *   @OA\Response(
     *     response=404,
     *     description="Chat session not found",
     *     @OA\JsonContent(
     *       @OA\Property(property="success", type="boolean", example=false),
     *       @OA\Property(property="message", type="string", example="Chat session not found or access denied")
     *     )
     *   ),
     *   @OA\Response(
     *     response=422,
     *     description="Validation error",
     *     @OA\JsonContent(
     *       @OA\Property(property="message", type="string", example="The message field is required."),
     *       @OA\Property(property="errors", type="object")
     *     )
     *   )
     * )
     */
    public function sendMessage() {}
}
