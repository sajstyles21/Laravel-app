<?php

use OpenApi\Annotations as OA;

/**
* @OA\Info(
*      version="1.0.0",
*      title="Laravel App",
*      description="Laravel Apis",
*      @OA\Contact(
 *     name="Suraj Sharma",
 *      email="sharmasuraj41@gmail.com"
 *      )
* )
*
*/


/**
 * @OA\Server(url=API_HOST)
 */

/**
 * @OA\Parameter(
 *      name="X-localization",
*          in="header",
*          description="Language (e.g. `en`, `ar`) (default will be: `en`)",
*          @OA\Schema(type="string", enum={"en", "ar"})
 * )
**/

/**
 * @OA\Schema(
 *     schema="ApiModel",
 *     required={"statusCode", "msg", "data"},
 *     @OA\Property(
 *         property="statusCode",
 *         type="integer",
 *         format="int32"
 *     ),
 *     @OA\Property(
 *         property="msg",
 *         type="string"
 *     ),
 *     @OA\Property(
 *         property="data",
 *         type="object"
 *     ),
 *     example={"statusCode":200, "message": "successful", "data": null}
 * )
 */

/**
  * @OA\SecurityScheme(
 *     securityScheme="Bearer",
 *     name="Authorization",
 *     type="apiKey",
 *     bearerFormat="JWT",
 *     in="header"
 * )
 */


 /**
  * @OA\Schema(
  *   schema="pageRequestSchema",
  *   @OA\Property(
  *     property="page",
  *     type="integer",
  *     description="(Optional) Pagination page number. Default is 1"
  *   ),
  *   @OA\Property(
  *     property="limit",
  *     type="integer",
  *     description="(Optional) Pagination page limit. Default is 8"
  *   )
  * )
  * 
  *   @OA\Parameter(
  *     parameter="page",
  *     name="page",
  *     in="query",
  *     @OA\Schema(
  *       type="integer"
  *     ),
  *     description="(Optional) Pagination page number. Default is 1"
  *   )
  *
  *   @OA\Parameter(
  *     parameter="limit",
  *     name="limit",
  *     in="query",
  *     @OA\Schema(
  *       type="integer"
  *     ),
  *     description="(Optional) Pagination page limit. Default is 8"
  *   )
  */