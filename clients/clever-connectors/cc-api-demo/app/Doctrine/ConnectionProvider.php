<?php declare(strict_types=1);

namespace App\Doctrine;

use CleverExtensions\Doctrine\Interfaces\IConnectionsProvider;

/**
 * Class ConnectionProvider
 *
 * @package App\Doctrine
 */
final class ConnectionProvider implements IConnectionsProvider
{

    /**
     * @param array  $staticDbConfig
     * @param string $key
     *
     * @return array
     */
    public static function getConnections(array $staticDbConfig, string $key): array
    {
        return ['app' => []];
    }

}
