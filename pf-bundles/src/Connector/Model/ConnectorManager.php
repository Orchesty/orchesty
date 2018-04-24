<?php declare(strict_types=1);

/**
 * Created by PhpStorm.
 * User: david.horacek
 * Date: 8/18/17
 * Time: 2:02 PM
 */

namespace Hanaboso\PipesFramework\Connector\Model;

use Hanaboso\CommonsBundle\Process\ProcessDto;
use Hanaboso\PipesFramework\Connector\ConnectorInterface;
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