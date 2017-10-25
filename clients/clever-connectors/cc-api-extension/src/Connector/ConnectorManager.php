<?php declare(strict_types=1);
/**
 * Created by PhpStorm.
 * User: venca
 * Date: 10/4/17
 * Time: 2:34 PM
 */

namespace CcApi\Connector;

use CcApi\ApiEntity\Subscriber;
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
use Psr\Http\Message\RequestInterface;
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
     * @param RequestInterface $request
     *
     * @return ResponseInterface
     * @throws ConnectorException
     */
    private function send(RequestInterface $request): ResponseInterface
    {
        try {
            return $this->curlSender->send($request);
        } catch (CurlException $e) {
            throw new ConnectorException(
                sprintf('Connector error: %s', $e->getMessage()),
                ConnectorException::REQUEST_ERROR,
                $e
            );
        }
    }

    /**
     * @param null|string $group
     * @param null|string $user
     *
     * @return iterable|System[]
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

        $response = $this->send($request);

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
        $request = new Request(
            CurlSender::GET,
            new Uri(sprintf('/systems/%s', $systemKey)),
            $this->getDefaultHeaders()->getHeaders()
        );

        $response = $this->send($request);

        return SystemFactory::create($this->parseBody($response));
    }

    /**
     * @param string $userId
     * @param string $systemKey
     *
     * @return UserSystem
     */
    public function getUserSystem(string $userId, string $systemKey): UserSystem
    {
        $request = new Request(
            CurlSender::GET,
            new Uri(sprintf('/user_systems/user/%s/system/%s', $userId, $systemKey)),
            $this->getDefaultHeaders()->getHeaders()
        );

        $response = $this->send($request);

        return UserSystemFactory::create($this->parseBody($response));
    }

    /**
     * @param string $userId
     *
     * @return iterable|UserSystem[]
     */
    public function getAllUserSystems(string $userId): iterable
    {
        $request = new Request(
            CurlSender::GET,
            new Uri(sprintf('/user_systems/user/%s', $userId)),
            $this->getDefaultHeaders()->getHeaders()
        );

        $response = $this->send($request);

        $userSystems = [];
        foreach ($this->parseBody($response) as $item) {
            $userSystems[] = UserSystemFactory::create($item);
        }

        return $userSystems;
    }

    /**
     * @param string $userId
     * @param string $systemKey
     * @param array  $settings
     */
    public function saveUserSystemSetting(string $userId, string $systemKey, array $settings): void
    {
        $request = new Request(
            CurlSender::POST,
            new Uri(sprintf('/user_systems/user/%s/system/%s/settings', $userId, $systemKey)),
            $this->getDefaultHeaders()->getHeaders(),
            json_encode($settings)
        );

        $this->send($request);
    }

    /**
     * @param string $userId
     * @param string $systemKey
     * @param string $token
     */
    public function installUserSystem(string $userId, string $systemKey, string $token): void
    {
        $request = new Request(
            CurlSender::POST,
            new Uri(sprintf('/user_systems/user/%s/system/%s/install', $userId, $systemKey)),
            $this->getDefaultHeaders()->getHeaders(),
            json_encode(['token' => $token])
        );

        $this->send($request);
    }

    /**
     * @param string $userId
     * @param string $systemKey
     */
    public function uninstallUserSystem(string $userId, string $systemKey): void
    {
        $request = new Request(
            CurlSender::GET,
            new Uri(sprintf('/user_systems/user/%s/system/%s/uninstall', $userId, $systemKey)),
            $this->getDefaultHeaders()->getHeaders()
        );

        $this->send($request);
    }

    /**
     * @param string $userId
     * @param string $systemKey
     */
    public function synchronizeUserSystem(string $userId, string $systemKey): void
    {
        $request = new Request(
            CurlSender::GET,
            new Uri(sprintf('/user_systems/user/%s/system/%s/sync', $userId, $systemKey)),
            $this->getDefaultHeaders()->getHeaders()
        );

        $this->send($request);
    }

    /**
     * @param string $userId
     * @param string $systemKey
     * @param string $password
     */
    public function setUserSystemPassword(string $userId, string $systemKey, string $password): void
    {
        $request = new Request(
            CurlSender::PUT,
            new Uri(sprintf('/user_systems/user/%s/system/%s/set_password', $userId, $systemKey)),
            $this->getDefaultHeaders()->getHeaders(),
            json_encode(['password' => $password])
        );

        $this->send($request);
    }

    /**
     * @param string $userId
     * @param string $systemKey
     * @param string $token
     */
    public function switchUserSystemToken(string $userId, string $systemKey, string $token): void
    {
        $request = new Request(
            CurlSender::PUT,
            new Uri(sprintf('/user_systems/user/%s/system/%s/switch_token', $userId, $systemKey)),
            $this->getDefaultHeaders()->getHeaders(),
            json_encode(['token' => $token])
        );

        $this->send($request);
    }

    /**
     * @param string $userId
     * @param string $systemKey
     * @param string $redirectUrl
     */
    public function authorizeUserSystem(string $userId, string $systemKey, string $redirectUrl): void
    {
        header(sprintf(
            'Location: %s/user_systems/user/%s/system/%s/authorize_redirect/%s',
            $this->curlSender->getConfig('base_uri'),
            $userId,
            $systemKey,
            urlencode($redirectUrl)
        ));
        die;
    }

    /**
     * @param string     $userId
     * @param Subscriber $subscriber
     */
    public function subscribe(string $userId, Subscriber $subscriber): void
    {
        $request = new Request(
            CurlSender::POST,
            new Uri(sprintf('/event/user/%s/create', $userId)),
            $this->getDefaultHeaders()->getHeaders(),
            Json::encode($subscriber->toArray())
        );

        $this->send($request);
    }

    /**
     * @param string     $userId
     * @param Subscriber $subscriber
     */
    public function unSubscribe(string $userId, Subscriber $subscriber): void
    {
        $request = new Request(
            CurlSender::POST,
            new Uri(sprintf('/event/user/%s/unsubscribe', $userId)),
            $this->getDefaultHeaders()->getHeaders(),
            Json::encode($subscriber->toArray())
        );

        $this->send($request);
    }

    /**
     * @param string     $userId
     * @param Subscriber $subscriber
     */
    public function hardBounce(string $userId, Subscriber $subscriber): void
    {
        $request = new Request(
            CurlSender::POST,
            new Uri(sprintf('/event/user/%s/hard_bounce', $userId)),
            $this->getDefaultHeaders()->getHeaders(),
            Json::encode($subscriber->toArray())
        );

        $this->send($request);
    }

}