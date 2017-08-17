<?php declare(strict_types=1);

namespace Hanaboso\PipesFramework\Commons\Source;

use GuzzleHttp\Psr7\Uri;
use Hanaboso\PipesFramework\Commons\Transport\Curl\CurlManager;
use Hanaboso\PipesFramework\Commons\Transport\Curl\Dto\RequestDto;

/**
 * Class SourceService
 *
 * @package Hanaboso\PipesFramework\Commons\Source
 */
class SourceService implements SourceServiceInterface
{

    /**
     * @var CurlManager
     */
    private $curl;

    /**
     * SourceService constructor.
     *
     * @param CurlManager $curl
     */
    function __construct(CurlManager $curl)
    {
        $this->curl = $curl;
    }

    /**
     * @param string $id
     * @param mixed  $data
     *
     * @return mixed
     */
    public function receiveData(string $id, $data)
    {
        $dto = new RequestDto('POST', new Uri('http://requestb.in/139w65j1'));
        $dto
            ->setBody(json_encode($data))
            ->setHeaders([
                'Accept'       => 'application/json',
                'Content-Type' => 'application/json',
            ]);

        $response = $this->curl->send($dto);

        return $response->getBody();
    }

}