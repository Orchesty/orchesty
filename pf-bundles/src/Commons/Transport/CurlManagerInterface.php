<?php declare(strict_types=1);

namespace Hanaboso\PipesFramework\Commons\Transport;

use Hanaboso\PipesFramework\Commons\Transport\Curl\Dto\RequestDto;
use Hanaboso\PipesFramework\Commons\Transport\Curl\Dto\ResponseDto;

/**
 * Interface CurlManagerInterface
 *
 * @package Hanaboso\PipesFramework\Commons\Transport
 */
interface CurlManagerInterface
{

    /**
     * @param RequestDto $dto
     * @param array      $options
     *
     * @return ResponseDto
     */
    public function send(RequestDto $dto, array $options = []): ResponseDto;

}