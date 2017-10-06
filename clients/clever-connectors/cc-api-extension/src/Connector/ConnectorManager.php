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
use CcApi\Connector\Exception\ConnectorException;
use CcApi\Curl\CurlSender;
use CcApi\Curl\Exception\CurlException;
use CcApi\Curl\Headers;
use CcApi\Curl\Query;
use Exception;
use GuzzleHttp\Psr7\Request;
use GuzzleHttp\Psr7\Uri;
use Nette\Utils\Json;
use Psr\Http\Message\ResponseInterface;

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
     * @param ResponseInterface $response
     *
     * @return array
     * @throws ConnectorException
     */
    private function parseBody(ResponseInterface $response): array
    {
        try {
            return Json::decode($response->getBody()->getContents(), Json::FORCE_ARRAY);
        } catch (Exception $e) {
            throw new ConnectorException(
                sprintf('Parser error: %s', $e->getMessage()),
                ConnectorException::PARSER_ERROR,
                $e
            );
        }
    }

    /**
     * @return Headers
     */
    private function getDefaultHeaders(): Headers
    {
        $headers = new Headers();
        $headers
            ->addHeader('Accept', 'application/json')
            ->addHeader('Content-Type', 'application/json');

        return $headers;
    }

    /**
     * @param null|string $group
     * @param null|string $user
     *
     * @return iterable
     * @throws ConnectorException
     */
    public function getAllSystems(?string $group = NULL, ?string $user = NULL): iterable
    {
        $query = new Query();
        if ($group !== NULL) {
            $query->addQuery('group', $group);
        }

        if ($user !== NULL) {
            $query->addQuery('user', $user);
        }

        $request = new Request(
            CurlSender::GET,
            new Uri(sprintf('/systems?%s', $query->getQueryAsString())),
            $this->getDefaultHeaders()->getHeaders()
        );

        try {
            $response = $this->curlSender->send($request);
        } catch (CurlException $e) {
            throw new ConnectorException(
                sprintf('Connector error: %s', $e->getMessage()),
                ConnectorException::REQUEST_ERROR,
                $e
            );
        }

        $systems = [];
        foreach ($this->parseBody($response) as $item) {
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