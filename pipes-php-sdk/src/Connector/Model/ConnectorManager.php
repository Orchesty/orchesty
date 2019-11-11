<?php declare(strict_types=1);

namespace Hanaboso\PipesPhpSdk\Connector\Model;

use Hanaboso\CommonsBundle\Process\ProcessDto;
use Hanaboso\CommonsBundle\Utils\PipesHeaders;
use Hanaboso\PipesPhpSdk\Connector\ConnectorInterface;
use Hanaboso\PipesPhpSdk\Connector\Exception\ConnectorException;
use Symfony\Component\HttpFoundation\Request;

/**
 * Class ConnectorManager
 *
 * @package Hanaboso\PipesPhpSdk\Connector\Model
 */
class ConnectorManager
{

    /**
     * @param ConnectorInterface $conn
     * @param Request            $request
     *
     * @return ProcessDto
     * @throws ConnectorException
     */
    public function processEvent(ConnectorInterface $conn, Request $request): ProcessDto
    {
        $dto = new ProcessDto();
        $dto
            ->setData((string) $request->getContent())
            ->setHeaders($request->headers->all());

        return $conn->processEvent($dto);
    }

    /**
     * @param ConnectorInterface $conn
     * @param Request            $request
     *
     * @return ProcessDto
     * @throws ConnectorException
     */
    public function processAction(ConnectorInterface $conn, Request $request): ProcessDto
    {
        $dto = new ProcessDto();
        $dto
            ->setData((string) $request->getContent())
            ->setHeaders($request->headers->all());

        $key = $conn->getApplicationKey();
        if ($key) {
            $headers                                                     = $dto->getHeaders();
            $headers[PipesHeaders::createKey(PipesHeaders::APPLICATION)] = [$key];
            $dto->setHeaders($headers);
        }

        return $conn->processAction($dto);
    }

}
