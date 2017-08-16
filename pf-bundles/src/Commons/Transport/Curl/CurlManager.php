<?php declare(strict_types=1);

namespace Hanaboso\PipesFramework\Commons\Transport\Curl;

use Exception;
use GuzzleHttp\Psr7\Request;
use Hanaboso\PipesFramework\Commons\Transport\Curl\Dto\RequestDto;
use Hanaboso\PipesFramework\Commons\Transport\Curl\Dto\ResponseDto;
use Hanaboso\PipesFramework\Commons\Transport\CurlManagerInterface;

/**
 * Class CurlManager
 *
 * @package Hanaboso\PipesFramework\Commons\Transport\Curl
 */
final class CurlManager implements CurlManagerInterface
{

    public const METHOD_GET     = 'GET';
    public const METHOD_POST    = 'POST';
    public const METHOD_HEAD    = 'HEAD';
    public const METHOD_PUT     = 'PUT';
    public const METHOD_DELETE  = 'DELETE';
    public const METHOD_OPTIONS = 'OPTIONS';
    public const METHOD_PATCH   = 'PATCH';

    /**
     * @var CurlClientFactory
     */
    private $curlClientFactory;

    /**
     * CurlManager constructor.
     *
     * @param CurlClientFactory $curlClientFactory
     */
    public function __construct(CurlClientFactory $curlClientFactory)
    {
        $this->curlClientFactory = $curlClientFactory;
    }

    /**
     * @return array
     */
    public static function getMethods(): array
    {
        return [
            self::METHOD_GET,
            self::METHOD_POST,
            self::METHOD_HEAD,
            self::METHOD_PUT,
            self::METHOD_DELETE,
            self::METHOD_OPTIONS,
            self::METHOD_PATCH,
        ];
    }

    /**
     * @param RequestDto $dto
     * @param array      $options
     *
     * @return ResponseDto
     * @throws CurlException
     */
    public function send(RequestDto $dto, array $options = []): ResponseDto
    {
        try {
            $request = new Request($dto->getMethod(), $dto->getUri(), $dto->getHeaders(), $dto->getBody());

            $client      = $this->curlClientFactory->create();
            $psrResponse = $client->send($request, $options);

            $response = new ResponseDto(
                $psrResponse->getStatusCode(),
                $psrResponse->getReasonPhrase(),
                $psrResponse->getBody()->getContents(),
                $psrResponse->getHeaders()
            );

            unset($psrResponse);

            // TODO log request and response

        } catch (Exception $exception) {

            // TODO log request and error

            throw new CurlException(
                sprintf('CurlManager::send() failed: %s', $exception->getMessage()),
                CurlException::REQUEST_FAILED
            );
        }

        return $response;
    }

}