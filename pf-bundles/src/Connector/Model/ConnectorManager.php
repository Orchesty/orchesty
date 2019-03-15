<?php declare(strict_types=1);

namespace Hanaboso\PipesFramework\Connector\Model;

use Hanaboso\CommonsBundle\Process\ProcessDto;
use Hanaboso\PipesFramework\Connector\ConnectorInterface;
use Hanaboso\PipesFramework\Connector\Exception\ConnectorException;
use Symfony\Component\HttpFoundation\Request;

/**
 * Class ConnectorManager
 *
 * @package Hanaboso\PipesFramework\Connector\Model
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

        return $conn->processAction($dto);
    }

}
