<?php declare(strict_types=1);

namespace Hanaboso\PipesPhpSdk\Utils;

use Hanaboso\CommonsBundle\Process\ProcessDtoAbstract;
use Hanaboso\Utils\String\Json;
use Hanaboso\Utils\System\ControllerUtils;
use Hanaboso\Utils\Traits\ControllerTrait;
use Symfony\Component\HttpFoundation\Response;

/**
 * Trait ProcessDtoControllerTrait
 *
 * @package Hanaboso\PipesPhpSdk\Utils
 */
trait ProcessDtoControllerTrait
{

    use ControllerTrait;

    /**
     * @param ProcessDtoAbstract $dto
     *
     * @return Response
     */
    protected function getResponseFromDto(ProcessDtoAbstract $dto): Response
    {
        $body = Json::encode([
            ProcessDtoFactory::BODY    => $dto->getBridgeData(),
            ProcessDtoFactory::HEADERS => ControllerUtils::createHeaders($dto->getHeaders()),
        ]);

        return $this->getResponse($body, 200, []);
    }

}
