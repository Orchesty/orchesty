<?php declare(strict_types=1);

/**
 * Created by PhpStorm.
 * User: radek.jirsa
 * Date: 8.8.18
 * Time: 8:45
 */

namespace CleverConnectors\AppBundle\Model\Systems\Impl\SalesforceApp\Connector;

use CleverConnectors\AppBundle\Document\SystemInstall;
use CleverConnectors\AppBundle\Model\Systems\Impl\SalesforceApp\SalesforceAppSystem;
use CleverConnectors\AppBundle\Traits\LoggerTrait;
use CleverConnectors\AppBundle\Utils\HeadersUtils;
use GuzzleHttp\Psr7\Response;
use Hanaboso\CommonsBundle\Process\ProcessDto;
use Hanaboso\CommonsBundle\Transport\Curl\CurlManager;
use Hanaboso\CommonsBundle\Transport\Curl\Dto\RequestDto;
use Hanaboso\CommonsBundle\Transport\Curl\Dto\ResponseDto;
use Nette\Utils\Strings;
use Psr\Log\LoggerAwareInterface;
use Psr\Log\NullLogger;
use Throwable;

/**
 * Class SalesForceAppConnectorAbstract
 *
 * @package CleverConnectors\AppBundle\Model\Systems\Impl\SalesforceApp\Connector
 */
abstract class SalesForceAppConnectorAbstract implements LoggerAwareInterface
{

    use LoggerTrait;

    /**
     * @var CurlManager
     */
    protected $curl;

    /**
     * @var SalesforceAppSystem
     */
    protected $system;

    /**
     * SalesForceAppConnectorAbstract constructor.
     *
     * @param CurlManager         $curl
     * @param SalesforceAppSystem $system
     */
    public function __construct(CurlManager $curl, SalesforceAppSystem $system)
    {
        $this->curl   = $curl;
        $this->system = $system;
        $this->logger = new NullLogger();
    }

    /**
     * @param ProcessDto    $dto
     * @param SystemInstall $systemInstall
     * @param RequestDto    $requestDto
     *
     * @return ResponseDto|null
     */
    protected function sendRequest(ProcessDto $dto, SystemInstall $systemInstall, RequestDto $requestDto): ?ResponseDto
    {
        try {
            $response = $this->curl->send($requestDto);
        } catch (Throwable $t) {
            $response = new Response(500, [], $t->getMessage());
        }

        if ($response->getStatusCode() !== 200) {
            $this->logError($response->getStatusCode(), $this->system, $systemInstall);

            if (Strings::contains($response->getBody(), 'REQUEST_LIMIT_EXCEEDED')) {
                HeadersUtils::setLimitHeaderToDto($dto);
            } elseif (Strings::contains($response->getBody(), 'INVALID_SESSION_ID')) {
                HeadersUtils::setStopHeaderToDto($dto);
            } else {
                HeadersUtils::setStopHeaderToDto($dto);
            }

            return NULL;
        }

        return $response;
    }

}