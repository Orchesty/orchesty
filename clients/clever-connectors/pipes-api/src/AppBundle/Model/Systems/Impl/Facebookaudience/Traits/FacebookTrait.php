<?php declare(strict_types=1);

namespace CleverConnectors\AppBundle\Model\Systems\Impl\Facebookaudience\Traits;

use CleverConnectors\AppBundle\Document\SystemInstall;
use CleverConnectors\AppBundle\Model\Systems\SystemInterface;
use CleverConnectors\AppBundle\Utils\HeadersUtils;
use Clue\React\Buzz\Message\ResponseException;
use Hanaboso\CommonsBundle\Process\ProcessDto;
use Hanaboso\CommonsBundle\Transport\Curl\CurlException;
use Hanaboso\PipesFramework\RabbitMq\Impl\Batch\SuccessMessage;

/**
 * Trait FacebookTrait
 *
 * Facebook API error codes
 * 4 - application request limit reached
 * 17 - user request limit reached
 * 100 - invalid parameter
 * 190 - invalid access token
 *
 * @package CleverConnectors\AppBundle\Model\Systems\Impl\Facebookaudience\Traits
 */
trait FacebookTrait
{

    /**
     * @param CurlException   $exception
     * @param SystemInstall   $systemInstall
     * @param SystemInterface $system
     * @param ProcessDto      $dto
     *
     * @return ProcessDto|null
     * @throws CurlException
     */
    protected function logConnectorError(
        CurlException $exception,
        SystemInstall $systemInstall,
        SystemInterface $system,
        ?ProcessDto $dto = NULL
    ): ?ProcessDto
    {
        $throw = isset($dto) ? FALSE : TRUE;
        $this->processErrorCode($exception, $systemInstall, $system, $throw);

        return HeadersUtils::setLimitHeaderToDto($dto);
    }

    /**
     * @param ResponseException $exception
     * @param SystemInstall     $systemInstall
     * @param SystemInterface   $system
     * @param int               $i
     *
     * @return SuccessMessage
     * @throws CurlException
     */
    protected function logBatchConnectorError(
        ResponseException $exception,
        SystemInstall $systemInstall,
        SystemInterface $system,
        int $i
    ): SuccessMessage
    {
        $this->processErrorCode($exception, $systemInstall, $system, FALSE);

        return HeadersUtils::setLimitHeaderToMessage(new SuccessMessage($i));
    }

    /**
     * @param CurlException|ResponseException $exception
     * @param SystemInstall                   $systemInstall
     * @param SystemInterface                 $system
     * @param bool                            $throw
     *
     * @return int|null
     * @throws CurlException
     */
    protected function processErrorCode(
        $exception,
        SystemInstall $systemInstall,
        SystemInterface $system,
        bool $throw = TRUE
    ): ?int
    {
        $response = $exception->getResponse();

        if (isset($response)) {
            $httpCode = $response->getStatusCode();
            if ($response->getStatusCode() == 400) {
                $data      = json_decode($response->getBody()->getContents(), TRUE);
                $errorCode = $data['error']['code'] ?? NULL;
                if (in_array($errorCode, [4, 17])) {
                    if ($throw) {
                        $httpCode = 429;
                    } else {
                        return 429;
                    }
                } elseif ($errorCode == 100) {
                    $httpCode = 400;
                } elseif ($errorCode == 190) {
                    $httpCode = 401;
                } else {
                    $httpCode = 500;
                }
            }

            // uses LoggerTrait
            $this->logError($httpCode, $system, $systemInstall);
        }

        throw $exception;
    }

}