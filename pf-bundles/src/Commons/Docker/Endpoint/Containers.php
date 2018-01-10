<?php declare(strict_types=1);

/**
 * Created by PhpStorm.
 * User: Pavel Severyn
 * Date: 10.10.17
 * Time: 16:16
 */

namespace Hanaboso\PipesFramework\Commons\Docker\Endpoint;

/**
 * Class Containers
 *
 * @package Hanaboso\PipesFramework\Commons\Docker\Endpoint
 */
class Containers extends EndpointAbstract
{

    /**
     * @param array $params
     * @param array $filter
     *
     * @return array
     */
    public function list(array $params = [], array $filter = []): array
    {
        //TODO: refactor
        $endpointUrl = str_replace(
            '{version}',
            $this->dockerClient->getVersion(),
            'http://v{version}/containers/json'
        );

        $queryParams = [];

        $params['headers'] = isset($params['headers']) ? $params['headers'] : [];
        $params['body'] = isset($params['body']) ? $params['body'] : '';

        if (isset($params['all']) && isset($params['all'])) {
            $queryParams[] = 'all=1';
        }

        if (isset($params['limit']) && $params['limit']) {
            $queryParams[] = sprintf('limit=%s', $params['limit']);
        }

        if (isset($params['size']) && isset($params['size'])) {
            $queryParams[] = 'size=1';
        }

        if (count($filter)) {
            $queryParams[] = sprintf('filters=%s', urlencode(json_encode($filter)));
        }

        if (count($queryParams)) {
            $queryParams = implode('&', $queryParams);
            $endpointUrl = sprintf('%s?%s', $endpointUrl, $queryParams);
        }

        //TODO: add catch server and throw app
        $result = $this->dockerClient->send('GET', $endpointUrl, $params['headers'], $params['body']);

        return json_decode($result->getContent(), TRUE);
    }

}
