<?php declare(strict_types=1);

namespace CleverConnectors\AppBundle\Model\CM\SubscriberConnector;

/**
 * Created by PhpStorm.
 * User: radekj
 * Date: 21.9.17
 * Time: 17:49
 */

use CleverConnectors\AppBundle\Enum\CleverFieldsEnum;
use CleverConnectors\AppBundle\Exceptions\CleverConnectorsException;
use CleverConnectors\AppBundle\Model\CM\CMAuthorization;
use CleverConnectors\AppBundle\Utils\CMHeaders;
use GuzzleHttp\Psr7\Uri;
use Hanaboso\PipesFramework\Commons\Process\ProcessDto;
use Hanaboso\PipesFramework\Commons\Transport\Curl\Dto\RequestDto;
use Hanaboso\PipesFramework\Commons\Transport\CurlManagerInterface;
use Hanaboso\PipesFramework\Connector\ConnectorInterface;
use Hanaboso\PipesFramework\Connector\Exception\ConnectorException;
use Psr\Log\LoggerAwareInterface;
use Psr\Log\LoggerInterface;
use Throwable;

/**
 * Class CMSubscriberConnectorAbstract
 *
 * @package CleverConnectors\AppBundle\Model\CM\SubscriberConnector
 */
abstract class CMSubscriberConnectorAbstract extends CMAuthorization implements ConnectorInterface, LoggerAwareInterface
{

    /**
     * @var CurlManagerInterface
     */
    protected $curl;

    /**
     * @var LoggerInterface
     */
    protected $logger;

    /**
     * CMSubscriberConnectorAbstract constructor.
     *
     * @param CurlManagerInterface $curl
     */
    function __construct(CurlManagerInterface $curl)
    {
        $this->curl = $curl;
    }

    /**
     * @return string
     */
    public function getId(): string
    {
        return 'CleverMonitorsSubscription';
    }

    /**
     * @param ProcessDto $dto
     *
     * @return ProcessDto
     * @throws ConnectorException
     */
    public function processEvent(ProcessDto $dto): ProcessDto
    {
        throw new ConnectorException(
            'CMSubscriberConnector has no support for webhooks!',
            ConnectorException::CONNECTOR_DOES_NOT_HAVE_PROCESS_EVENT
        );
    }

    /**
     * @param LoggerInterface $logger
     *
     * @return ConnectorInterface
     */
    public function setLogger(LoggerInterface $logger): ConnectorInterface
    {
        $this->logger = $logger;

        return $this;
    }

    /**
     * @param ProcessDto $dto
     * @param string     $method
     * @param int[]      $statusCode
     * @param string     $email
     *
     * @return ProcessDto
     * @throws CleverConnectorsException
     * @throws ConnectorException
     */
    public function processCMAction(ProcessDto $dto, string $method, array $statusCode, string $email = ''): ProcessDto
    {
        $user   = CMHeaders::get(CMHeaders::GUID, $dto->getHeaders());
        $token  = CMHeaders::get(CMHeaders::TOKEN, $dto->getHeaders());
        $system = CMHeaders::get(CMHeaders::SYSTEM_KEY, $dto->getHeaders());

        if (!isset($user) || !isset($token) || !isset($system)) {
            throw new CleverConnectorsException(
                'User or Token or System is missing in header.',
                CleverConnectorsException::MISSING_DATA
            );
        }

        $req = new RequestDto($method, new Uri($this->getUrl($email)));

        $req->setHeaders($this->getAuthorizationHeaders($user, $token));
        $req->setBody($this->getDataAsJson($dto, $system));

        try {
            $res = $this->curl->send($req);
        } catch (Throwable $e) {
            $this->logger->error(sprintf('CM %s subscription failed.', $method),
                ['body' => $req->getBody(), 'exception' => $e]);
            throw new ConnectorException(
                sprintf('%s subscription failed.', $method),
                ConnectorException::CONNECTOR_FAILED_TO_PROCESS
            );
        }

        if (!in_array($res->getStatusCode(), $statusCode)) {
            $this->logger->error(sprintf('CM %s subscription failed.', $method),
                ['body' => $req->getBody(), 'exception' => $res->getBody()]);
            throw new ConnectorException(
                sprintf('%s subscription returned [%s] status code.', $method, $res->getStatusCode()),
                ConnectorException::CONNECTOR_FAILED_TO_PROCESS
            );
        }

        $data = $res->getBody();
        $data = str_replace(PHP_EOL, '', $data);
        $dto->setData($data);

        return $dto;
    }

    /**
     * @param string $email
     *
     * @return string
     */
    protected function getUrl(string $email = ''): string
    {
        if ($email != '') {
            $email = '/' . $email;
        }

        return sprintf('https://api.dev.clevermonitor.com/v1.2/subscribers/email%s', $email);
    }

    /**
     * @param ProcessDto $dto
     * @param string     $system
     *
     * @return array
     */
    protected function getData(ProcessDto $dto, string $system): array
    {
        $data = json_decode($dto->getData(), TRUE);

        $data[CleverFieldsEnum::SYSTEM_KEY] = $system;

        //@TODO: až bude implementováno u C-M, tak smazat
        if (array_key_exists(CleverFieldsEnum::FOREIGN_ID, $data)) {
            unset($data[CleverFieldsEnum::FOREIGN_ID]);
        }

        if (array_key_exists(CleverFieldsEnum::SYSTEM_KEY, $data)) {
            unset($data[CleverFieldsEnum::SYSTEM_KEY]);
        }

        if (array_key_exists(CleverFieldsEnum::SEND_OPTIN, $data)) {
            unset($data[CleverFieldsEnum::SEND_OPTIN]);
        }

        // -----------------------------------------------

        return $data;
    }

    /**
     * @param ProcessDto $dto
     * @param string     $system
     *
     * @return string
     */
    private function getDataAsJson(ProcessDto $dto, string $system): string
    {
        // Do not remove: Encode multibyte Unicode characters literally
        return json_encode($this->getData($dto, $system), JSON_UNESCAPED_UNICODE);
    }

}