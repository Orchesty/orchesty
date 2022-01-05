<?php declare(strict_types=1);

namespace PipesFrameworkTests;

use Closure;

/**
 * Trait LoginJwtTestTrait
 *
 * @package PipesFrameworkTests
 */
trait LoginJwtTestTrait
{

    /**
     * @param string       $jwt
     * @param string       $path
     * @param mixed[]      $responseReplacements
     * @param mixed[]      $requestHttpReplacements
     * @param mixed[]      $requestBodyReplacements
     * @param mixed[]      $requestHeadersReplacements
     * @param Closure|null $bodyCallback
     */
    protected function assertResponseLogged(
        string $jwt,
        string $path,
        array $responseReplacements = [],
        array $requestHttpReplacements = [],
        array $requestBodyReplacements = [],
        array $requestHeadersReplacements = [],
        ?Closure $bodyCallback = NULL,
    ): void
    {
        $this->assertResponse(
            $path,
            $responseReplacements,
            $requestHttpReplacements,
            $requestBodyReplacements,
            array_merge($requestHeadersReplacements, [self::$AUTHORIZATION => $jwt]),
            $bodyCallback,
        );
    }

}
