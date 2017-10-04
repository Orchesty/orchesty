<?php declare(strict_types=1);
/**
 * Created by PhpStorm.
 * User: venca
 * Date: 10/4/17
 * Time: 2:34 PM
 */

namespace CcApi\Connector;

use CcApi\ApiEntity\System;
use CcApi\ApiEntity\SystemFactory;
use CcApi\ApiEntity\UserSystem;
use CcApi\ApiEntity\UserSystemFactory;
use CcApi\Curl\CurlSender;
use GuzzleHttp\Psr7\Request;
use GuzzleHttp\Psr7\Uri;
use Nette\Utils\Json;

/**
 * Class ConnectorManager
 *
 * @package CcApi
 */
class ConnectorManager implements ConnectorInterface
{

    /**
     * @var CurlSender
     */
    private $curlSender;

    /**
     * ConnectorManager constructor.
     *
     * @param CurlSender $curlSender
     */
    public function __construct(CurlSender $curlSender)
    {
        $this->curlSender = $curlSender;
    }

    /**
     * @param null|string $group
     * @param null|string $user
     *
     * @return iterable|System[]
     */
    public function getAllSystems(?string $group = NULL, ?string $user = NULL): iterable
    {
        $query['group'] = $group ?? '';
        $query['user']  = $user ?? '';

        $uri     = new Uri(sprintf('/systems?%s', http_build_query($query)));
        $request = new Request(CurlSender::GET, $uri, ['application/json']);

        $response = $this->curlSender->send($request);

        $data = Json::decode($response->getBody()->getContents(), Json::FORCE_ARRAY);

        $systems = [];
        foreach ($data as $item) {
            $systems[] = SystemFactory::create($item);
        }

        return $systems;
    }

    /**
     * @param string $systemKey
     *
     * @return System
     */
    public function getSystem(string $systemKey): System
    {
        $uri     = new Uri(sprintf('/systems/%s', $systemKey));
        $request = new Request(CurlSender::GET, $uri, ['application/json']);

        $response = $this->curlSender->send($request);

        $data = Json::decode($response->getBody()->getContents(), Json::FORCE_ARRAY);

        return SystemFactory::create($data);
    }

    /**
     * @param string $userId
     * @param string $systemKey
     *
     * @return UserSystem
     */
    public function getUserSystem(string $userId, string $systemKey): UserSystem
    {
        $uri     = new Uri(sprintf('/user_systems/user/%s/system/%s', $userId, $systemKey));
        $request = new Request(CurlSender::GET, $uri, ['application/json']);

        $response = $this->curlSender->send($request);

        $data = Json::decode($response->getBody()->getContents(), Json::FORCE_ARRAY);

        return UserSystemFactory::create($data);
    }

}