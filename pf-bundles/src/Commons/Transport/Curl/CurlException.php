<?php declare(strict_types=1);

namespace Hanaboso\PipesFramework\Commons\Transport\Curl;

use Hanaboso\PipesFramework\Commons\Exception\PipesFrameworkException;
use Psr\Http\Message\ResponseInterface;
use Throwable;

/**
 * Class CurlException
 *
 * @package Hanaboso\PipesFramework\Commons\Transport\Curl
 */
final class CurlException extends PipesFrameworkException
{

    protected const OFFSET = 300;

    public const INVALID_METHOD = self::OFFSET + 1;
    public const BODY_ON_GET    = self::OFFSET + 2;
    public const REQUEST_FAILED = self::OFFSET + 3;

    /**
     * @var ResponseInterface|null
     */
    private $response;

    /**
     * CurlException constructor.
     *
     * @param string                 $message
     * @param int                    $code
     * @param Throwable|NULL         $previous
     * @param ResponseInterface|NULL $response
     */
    public function __construct(
        $message = "",
        $code = 0,
        ?Throwable $previous = NULL,
        ?ResponseInterface $response = NULL
    )
    {
        parent::__construct($message, $code, $previous);
        $this->response = $response;
    }

    /**
     * @return ResponseInterface|NULL
     */
    public function getResponse(): ?ResponseInterface
    {
        return $this->response;
    }

}