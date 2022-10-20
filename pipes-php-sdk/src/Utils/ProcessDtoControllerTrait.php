<?php declare(strict_types=1);

namespace Hanaboso\PipesPhpSdk\Utils;

use Hanaboso\CommonsBundle\Process\ProcessDtoAbstract;
use Hanaboso\Utils\Exception\PipesFrameworkException;
use Hanaboso\Utils\String\Json;
use Hanaboso\Utils\System\ControllerUtils;
use Hanaboso\Utils\Traits\ControllerTrait;
use Symfony\Component\HttpFoundation\Response;
use Throwable;

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
        return $this->getResponse($this->formatResponseFromDto($dto), 200, []);
    }

    /**
     * @param ProcessDtoAbstract $dto
     * @param Throwable          $e
     *
     * @return Response
     * @throws PipesFrameworkException
     */
    protected function getErrorResponseFromDto(ProcessDtoAbstract $dto, Throwable $e): Response
    {
        $dto->setStopProcess(ProcessDtoAbstract::STOP_AND_FAILED, $e->getMessage());

        return $this->getResponse($this->formatResponseFromDto($dto, $e), 400, []);
    }

    /**
     * @param ProcessDtoAbstract $dto
     * @param Throwable|null     $e
     *
     * @return string
     */
    private function formatResponseFromDto(ProcessDtoAbstract $dto, ?Throwable $e = NULL): string
    {
        return Json::encode([
            ProcessDtoFactory::BODY    => $dto->getBridgeData(),
            ProcessDtoFactory::HEADERS => ControllerUtils::createHeaders($dto->getHeaders(), $e),
        ]);
    }

}
