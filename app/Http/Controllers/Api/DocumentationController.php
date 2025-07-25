<?php

namespace App\Http\Controllers\Api;

use Illuminate\Http\Request;
use OpenApi\Annotations as OA;

/**
 * @OA\Info(
 *     version="1.0.0",
 *     title="Smart Irrigation System API",
 *     description="API documentation for the Smart Irrigation System",
 *     @OA\Contact(
 *         email="support@smartirrigation.com"
 *     ),
 *     @OA\License(
 *         name="MIT",
 *         url="https://opensource.org/licenses/MIT"
 *     )
 * )
 * @OA\Server(
 *     url="/api/v1",
 *     description="API Server"
 * )
 * @OA\SecurityScheme(
 *     type="http",
 *     scheme="bearer",
 *     securityScheme="bearerAuth",
 *     bearerFormat="JWT"
 * )
 */
class DocumentationController
{
    // This controller is used only for OpenAPI documentation
}
