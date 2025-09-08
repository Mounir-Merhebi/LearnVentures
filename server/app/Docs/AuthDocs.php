<?php

namespace App\Docs;

use OpenApi\Annotations as OA;

/**
 * @OA\Info(title="LearnVentures API", version="0.1.0")
 * @OA\Server(url="http://localhost:8000", description="Local")
 */
class AuthDocs
{
    /**
     * @OA\Post(
     *   path="/api/v0.1/guest/login",
     *   summary="User login",
     *   tags={"Auth"},
     *   @OA\RequestBody(
     *     required=true,
     *     @OA\JsonContent(
     *       required={"email","password"},
     *       @OA\Property(property="email", type="string", format="email", example="mounirmerhebi201@gmail.com"),
     *       @OA\Property(property="password", type="string", format="password", example="password123")
     *     )
     *   ),
     *   @OA\Response(
     *     response=200,
     *     description="Successful login",
     *     @OA\JsonContent(
     *       @OA\Property(property="user", type="object"),
     *       @OA\Property(property="token", type="string", example="jwt_token_here")
     *     )
     *   ),
     *   @OA\Response(
     *     response=401,
     *     description="Unauthorized",
     *     @OA\JsonContent(
     *       @OA\Property(property="error", type="string", example="Unauthorized")
     *     )
     *   )
     * )
     */
    public function dummy() {} // no logic; anchor for annotations
}
