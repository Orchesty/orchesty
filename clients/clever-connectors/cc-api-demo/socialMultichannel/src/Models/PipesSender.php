<?php declare(strict_types=1);

namespace CleverCore\SocialMultichannel\Models;

use CcApi\Curl\CurlSender;
use CcApi\Curl\Exception\CurlException;
use GuzzleHttp\Psr7\Request;

/**
 * Class PipesSender
 *
 * @package CleverCore\SocialMultichannel\Models
 */
final class PipesSender
{

    private const CREATE_AD_URL = '%s/system/%s/user/%s/action/createAudience';
    private const DELETE_AD_URL = '%s/system/%s/user/%s/action/deleteAd';

    /**
     * @var CurlSender
     */
    private $curl;

    /**
     * @var string
     */
    private $backend;

    /**
     * PipesSender constructor.
     *
     * @param string     $backend
     * @param CurlSender $curl
     */
    public function __construct(string $backend, CurlSender $curl)
    {
        $this->curl    = $curl;
        $this->backend = rtrim($backend, '/');
    }

    /**
     * @param string $system
     * @param string $userId
     * @param array  $data
     *
     * @throws CurlException
     */
    public function createAd(string $system, string $userId, array $data): void
    {
        $req = new Request(CurlSender::POST, sprintf(
            static::CREATE_AD_URL,
            $this->backend,
            $system,
            $userId
        ), [
            'Content-Type' => 'application/json',
            'Accept'       => 'application/json',
        ], json_encode($data));

        $this->curl->send($req);
    }

    /**
     * @param string $system
     * @param string $userId
     * @param array  $data
     *
     * @throws CurlException
     */
    public function removeMirror(string $system, string $userId, array $data): void
    {
        $req = new Request(CurlSender::POST, sprintf(
            static::DELETE_AD_URL,
            $this->backend,
            $system,
            $userId
        ), [
            'Content-Type' => 'application/json',
            'Accept'       => 'application/json',
        ], json_encode($data));

        $this->curl->send($req);
    }

}