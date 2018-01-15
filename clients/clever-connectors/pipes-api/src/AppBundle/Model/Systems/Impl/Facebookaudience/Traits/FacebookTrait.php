<?php declare(strict_types=1);

namespace CleverConnectors\AppBundle\Model\Systems\Impl\Facebookaudience\Traits;

use CleverConnectors\AppBundle\Document\SystemInstall;
use CleverConnectors\AppBundle\Model\Systems\SystemInterface;
use CleverConnectors\AppBundle\Utils\HeadersUtils;
use Hanaboso\PipesFramework\Commons\Process\ProcessDto;
use Hanaboso\PipesFramework\Commons\Transport\Curl\CurlException;

/**
 * Trait FacebookTrait
 *
 * @package CleverConnectors\AppBundle\Model\Systems\Impl\Facebookaudience\Traits
 */
trait FacebookTrait
{

    /**
     * 4 - application request limit reached
     * 17 - user request limit reached
     * 100 - invalid parameter
     * 190 - invalid access token
     *
     * @param CurlException   $exception
     * @param SystemInstall   $systemInstall
     * @param SystemInterface $system
     * @param ProcessDto      $dto
     *
     * @return ProcessDto
     * @throws CurlException
     */
    protected function logConnectorError(
        CurlException $exception,
        SystemInstall $systemInstall,
        SystemInterface $system,
        ?ProcessDto $dto = NULL
    ): ?ProcessDto
    {
        $response = $exception->getResponse();

        if (isset($response)) {
            $httpCode = $response->getStatusCode();
            if ($response->getStatusCode() == 400) {
                $data      = json_decode($response->getBody()->getContents(), TRUE);
                $errorCode = isset($data['error']['code']) ? $data['error']['code'] : NULL;
                if (in_array($errorCode, [4, 17])) {
                    if ($dto) {
                        return HeadersUtils::setLimitHeaderToDto($dto);
                    } else {
                        $httpCode = 429;
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