<?php declare(strict_types=1);
/**
 * Created by PhpStorm.
 * User: venca
 * Date: 10/23/17
 * Time: 9:52 AM
 */

namespace CmStream;

use CmStream\Exception\SubscriberException;
use Exception;
use GuzzleHttp\Psr7\Request;
use GuzzleHttp\Psr7\Uri;

/**
 * Class Subscriber
 *
 * @package CmStream
 */
class Subscriber
{

    /**
     * @var GuzzleClientFactory
     */
    private $clientFactory;

    /**
     * Subscriber constructor.
     *
     * @param GuzzleClientFactory $clientFactory
     */
    public function __construct(GuzzleClientFactory $clientFactory)
    {
        $this->clientFactory = $clientFactory;
    }

    /**
     * @param string $userId
     * @param array  $groups
     *
     * @return string Token for user
     * @throws SubscriberException
     */
    public function subscribe(string $userId, array $groups = []): string
    {
        $request = new Request(
            'post',
            new Uri('/login'),
            [
                'content-type' => 'application/json',
            ], json_encode([
                'userId' => $userId,
                'groups' => $groups,
            ])
        );

        try {
            $response = $this->clientFactory->create()->send($request);

            if ($response->getStatusCode() === 200) {

                $data = json_decode($response->getBody()->getContents(), TRUE);

                if (isset($data['token'])) {
                    return $data['token'];
                } else {
                    throw new SubscriberException('Token is empty.');
                }

            } else {
                throw new SubscriberException(sprintf('Response error: %s', $response->getReasonPhrase()));
            }

        } catch (Exception $e) {
            throw new SubscriberException(sprintf('Curl sender error: %s', $e->getMessage()), $e->getCode(), $e);
        }
    }

    /**
     * @param string $token User token
     *
     * @throws SubscriberException
     */
    public function unsubscribe(string $token): void
    {
        $request = new Request(
            'POST',
            new Uri('/logout'),
            [
                'content-type' => 'application/json',
            ], json_encode([
                'token' => $token,
            ]
        ));

        try {
            $response = $this->clientFactory->create()->send($request);

            if (!$response->getStatusCode() == 200) {
                throw new SubscriberException(sprintf('Response error: %s', $response->getReasonPhrase()));
            }

        } catch (Exception $e) {
            throw new SubscriberException(sprintf('Curl sender error: %s', $e->getMessage()), $e->getCode(), $e);
        }
    }

}