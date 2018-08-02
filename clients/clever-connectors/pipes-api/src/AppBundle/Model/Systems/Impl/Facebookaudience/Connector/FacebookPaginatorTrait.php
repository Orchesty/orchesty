<?php declare(strict_types=1);

namespace CleverConnectors\AppBundle\Model\Systems\Impl\Facebookaudience\Connector;

use GuzzleHttp\Psr7\Uri;
use Hanaboso\CommonsBundle\Transport\Curl\CurlException;
use Hanaboso\CommonsBundle\Transport\Curl\Dto\RequestDto;
use Hanaboso\CommonsBundle\Transport\CurlManagerInterface;

/**
 * Trait FacebookPaginatorTrait
 *
 * @package CleverConnectors\AppBundle\Model\Systems\Impl\Facebookaudience\Connector
 */
trait FacebookPaginatorTrait
{

    /**
     * @var CurlManagerInterface
     */
    protected $manager;

    /**
     * @param RequestDto $req
     *
     * @return array
     * @throws CurlException
     */
    public function loopThroughPages(RequestDto $req): array
    {
        $res = [];

        while (TRUE) {
            $data = $this->fetchPage($req);
            $this->handleResponse($data, $res);

            if (array_key_exists('paging', $data) && array_key_exists('next', $data['paging'])) {
                $req->setUri(new Uri($data['paging']['next']));
            } else {
                break;
            }
        }

        return $res;
    }

    /**
     * @param array $data
     * @param array $res
     */
    protected function handleResponse(array $data, array &$res): void
    {
        if (is_array($data) && array_key_exists('data', $data)) {
            foreach ($data['data'] as $item) {
                $res[$item['id']] = $item['name'];
            }
        }
    }

    /**
     * @param RequestDto $req
     *
     * @return array
     * @throws CurlException
     */
    protected function fetchPage(RequestDto $req): array
    {
        $response = $this->manager->send($req);

        return json_decode($response->getBody(), TRUE);
    }

}