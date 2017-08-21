<?php declare(strict_types=1);
/**
 * Created by PhpStorm.
 * User: david.horacek
 * Date: 8/18/17
 * Time: 2:02 PM
 */

namespace Hanaboso\PipesFramework\Connector\Model;

use Hanaboso\PipesFramework\Connector\ConnectorInterface;

/**
 * Class ConnectorManager
 *
 * @package Hanaboso\PipesFramework\Connector\Model
 */
class ConnectorManager
{

    /**
     * @param ConnectorInterface $conn
     * @param array              $data
     *
     * @return string[]
     */
    public function processEvent(ConnectorInterface $conn, array $data): array
    {
        $dto = $conn->processEvent($data);

        return $dto->getData();
    }

}