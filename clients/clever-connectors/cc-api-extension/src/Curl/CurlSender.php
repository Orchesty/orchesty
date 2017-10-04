<?php declare(strict_types=1);
/**
 * Created by PhpStorm.
 * User: venca
 * Date: 10/4/17
 * Time: 2:01 PM
 */

namespace CcApi\Curl;

use CcApi\Curl\Exception\CurlException;
use Exception;
use Psr\Http\Message\RequestInterface;
use Psr\Http\Message\ResponseInterface;

/**
 * Class CurlService
 *
 * @package CcApi\Curl
 */
class CurlSender
{

    public const GET     = 'GET';
    public const POST    = 'POST';
    public const HEAD    = 'HEAD';
    public const PUT     = 'PUT';
    public const DELETE  = 'DELETE';
    public const OPTIONS = 'OPTIONS';
    public const PATCH   = 'PATCH';

    /**
     * @var ClientFactory
     */
    private $clientFactory;

    /**
     * CurlService constructor.
     *
     * @param ClientFactory $clientFactory
     */
    public function __construct(ClientFactory $clientFactory)
    {
        $this->clientFactory = $clientFactory;
    }

    /**
     * @param RequestInterface $request
     * @param array            $options
     *
     * @return ResponseInterface
     * @throws CurlException
     */
    public function send(RequestInterface $request, array $options = []): ResponseInterface
    {
        try {
            return $this->clientFactory->create()->send($request, $options);
        } catch (Exception $e) {
            throw new CurlException(sprintf('Curl sender error: %s', $e->getMessage()), $e->getCode(), $e);
        }
    }

}