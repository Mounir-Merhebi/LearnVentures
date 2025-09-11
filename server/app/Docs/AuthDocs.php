<?php

namespace App\Docs;

use OpenApi\Annotations as OA;

/**
 * @OA\Info(title="LearnVentures API", version="0.1.0")
 * @OA\Server(url="http://localhost:8002", description="Local")
 *
 * @OA\Schema(
 *   schema="User",
 *   type="object",
 *   @OA\Property(property="id", type="integer", example=1),
 *   @OA\Property(property="name", type="string", example="John Doe"),
 *   @OA\Property(property="email", type="string", format="email", example="john@example.com"),
 *   @OA\Property(property="role", type="string", enum={"Student", "Instructor", "Moderator", "Admin"}, example="Student"),
 *   @OA\Property(property="hobbies", type="string", nullable=true, example="Reading, Gaming"),
 *   @OA\Property(property="preferences", type="string", nullable=true, example="Visual learning"),
 *   @OA\Property(property="bio", type="string", nullable=true, example="A passionate learner"),
 *   @OA\Property(property="token", type="string", example="eyJ0eXAiOiJKV1QiLCJhbGciOiJIUzI1NiJ9...")
 * )
 *
 * @OA\Schema(
 *   schema="ApiResponse",
 *   type="object",
 *   @OA\Property(property="success", type="boolean"),
 *   @OA\Property(property="message", type="string"),
 *   @OA\Property(property="payload", type="object", nullable=true)
 * )
 */
class AuthDocs
{
    /**
     * @OA\Post(
     *   path="/api/v0.1/guest/login",
     *   summary="User login",
     *   tags={"Authentication"},
     *   @OA\RequestBody(
     *     required=true,
     *     @OA\JsonContent(
     *       required={"email","password"},
     *       @OA\Property(property="email", type="string", format="email", example="john@example.com"),
     *       @OA\Property(property="password", type="string", format="password", example="password123", minLength=1)
     *     )
     *   ),
     *   @OA\Response(
     *     response=200,
     *     description="Successful login",
     *     @OA\JsonContent(
     *       @OA\Property(property="success", type="boolean", example=true),
     *       @OA\Property(property="message", type="string", example="Login successful"),
     *       @OA\Property(property="payload", ref="#/components/schemas/User")
     *     )
     *   ),
     *   @OA\Response(
     *     response=401,
     *     description="Invalid credentials",
     *     @OA\JsonContent(
     *       @OA\Property(property="success", type="boolean", example=false),
     *       @OA\Property(property="message", type="string", example="Invalid credentials"),
     *       @OA\Property(property="payload", type="null")
     *     )
     *   ),
     *   @OA\Response(
     *     response=500,
     *     description="Login failed",
     *     @OA\JsonContent(
     *       @OA\Property(property="success", type="boolean", example=false),
     *       @OA\Property(property="message", type="string", example="Login failed: Internal server error"),
     *       @OA\Property(property="payload", type="null")
     *     )
     *   )
     * )
     */
    public function login() {}

    /**
     * @OA\Post(
     *   path="/api/v0.1/guest/register",
     *   summary="User registration",
     *   tags={"Authentication"},
     *   @OA\RequestBody(
     *     required=true,
     *     @OA\JsonContent(
     *       required={"name","email","password"},
     *       @OA\Property(property="name", type="string", maxLength=255, example="John Doe"),
     *       @OA\Property(property="email", type="string", format="email", maxLength=255, example="john@example.com"),
     *       @OA\Property(property="password", type="string", minLength=8, maxLength=20, example="password123"),
     *       @OA\Property(property="hobbies", type="string", nullable=true, example="Reading, Gaming"),
     *       @OA\Property(property="preferences", type="string", nullable=true, example="Visual learning"),
     *       @OA\Property(property="bio", type="string", nullable=true, example="A passionate learner"),
     *       @OA\Property(property="role", type="string", nullable=true, enum={"Student", "Instructor", "Moderator", "Admin"}, example="Student")
     *     )
     *   ),
     *   @OA\Response(
     *     response=201,
     *     description="Registration successful",
     *     @OA\JsonContent(
     *       @OA\Property(property="success", type="boolean", example=true),
     *       @OA\Property(property="message", type="string", example="Registration successful"),
     *       @OA\Property(property="payload", ref="#/components/schemas/User")
     *     )
     *   ),
     *   @OA\Response(
     *     response=400,
     *     description="Registration failed",
     *     @OA\JsonContent(
     *       @OA\Property(property="success", type="boolean", example=false),
     *       @OA\Property(property="message", type="string", example="Registration failed: Email already exists"),
     *       @OA\Property(property="payload", type="null")
     *     )
     *   ),
     *   @OA\Response(
     *     response=500,
     *     description="Internal server error",
     *     @OA\JsonContent(
     *       @OA\Property(property="success", type="boolean", example=false),
     *       @OA\Property(property="message", type="string", example="Registration failed: Internal server error"),
     *       @OA\Property(property="payload", type="null")
     *     )
     *   )
     * )
     */
    public function register() {}
}
