<?php declare(strict_types=1);

namespace Hanaboso\PipesFramework\Commons\Source;

use GuzzleHttp\Client;

/**
 * Class SourceService
 *
 * @package Hanaboso\PipesFramework\Commons\Source
 */
class SourceService implements SourceServiceInterface
{

    /**
     * @param string $id
     * @param mixed  $data
     *
     * @return mixed
     */
    public function receiveData(string $id, $data)
    {
        $httpClient = new Client();
        $response   = $httpClient->request('POST',
            'http://requestb.in/139w65j1',
            [
                'headers' => [
                    'Accept'       => 'application/json',
                    'Content-Type' => 'application/json',
                ],
                'body'    => json_encode($data),
            ]
        );

        return $response->getBody();
    }

}