<?php declare(strict_types=1);

namespace CleverConnectors\AppBundle\Model\CM\CustomFieldsConnector;

use CleverConnectors\AppBundle\Exceptions\CleverConnectorsException;
use CleverConnectors\AppBundle\Model\CM\CMAuthorization;
use CleverConnectors\AppBundle\Traits\LoggerTrait;
use CleverConnectors\AppBundle\Utils\CMHeaders;
use GuzzleHttp\Psr7\Uri;
use Hanaboso\CommonsBundle\Process\ProcessDto;
use Hanaboso\CommonsBundle\Transport\Curl\CurlException;
use Hanaboso\CommonsBundle\Transport\Curl\CurlManager;
use Hanaboso\CommonsBundle\Transport\Curl\Dto\RequestDto;
use Hanaboso\CommonsBundle\Transport\CurlManagerInterface;
use Hanaboso\PipesFramework\Connector\ConnectorInterface;
use Hanaboso\PipesFramework\Connector\Exception\ConnectorException;
use Psr\Log\LoggerAwareInterface;

/**
 * Created by PhpStorm.
 * User: radek.jirsa
 * Date: 24.4.18
 * Time: 16:52
 */
class CMGetCustomFieldsConnector extends CMAuthorization implements ConnectorInterface, LoggerAwareInterface
{

    use LoggerTrait;

    private const URL_PART = '?source=1&count=%s&offset=%s';
    private const LIMIT    = 50;

    public const FIELD_ID = 'field_id';
    public const NAME     = 'name';

    /**
     * @var CurlManagerInterface
     */
    protected $curl;

    /**
     * CMGetDistributionsConnector constructor.
     *
     * @param CurlManagerInterface $curl
     */
    public function __construct(CurlManagerInterface $curl)
    {
        $this->curl = $curl;
    }

    /**
     * @return string
     */
    public function getId(): string
    {
        return 'clevermonitors-get-custom-fields-connector';
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
            'CMGetCustomFieldsConnector has no support for webhooks!',
            ConnectorException::CONNECTOR_DOES_NOT_HAVE_PROCESS_EVENT
        );
    }

    /**
     * @param ProcessDto $dto
     *
     * @return ProcessDto
     * @throws CleverConnectorsException
     * @throws CurlException
     */
    public function processAction(ProcessDto $dto): ProcessDto
    {
        $dto->setData(json_encode($this->getCustomFieldsArray($dto)));

        return $dto;
    }

    /**
     * @param ProcessDto $dto
     *
     * @return array
     * @throws CleverConnectorsException
     * @throws CurlException
     */
    public function getCustomFieldsArray(ProcessDto $dto): array
    {
        $user  = CMHeaders::get(CMHeaders::GUID, $dto->getHeaders());
        $token = CMHeaders::get(CMHeaders::TOKEN, $dto->getHeaders());

        if (!isset($user) || !isset($token)) {
            throw new CleverConnectorsException(
                'User or Token is missing in header.',
                CleverConnectorsException::MISSING_DATA
            );
        }
        $req = new RequestDto(CurlManager::METHOD_GET, new Uri(''));
        $req->setDebugInfo(CMHeaders::debugInfo($dto->getHeaders()));
        $req->setHeaders($this->getAuthorizationHeaders($user, $token));

        return $this->getLists($req);
    }

    /**
     * @param RequestDto $req
     *
     * @return array
     * @throws CleverConnectorsException
     * @throws CurlException
     */
    private function getLists(RequestDto $req): array
    {
        $page  = 0;
        $lists = [];
        while (TRUE) {
            $res = $this->curl->send(RequestDto::from($req, new Uri($this->getUrl($page++))));

            if (in_array($res->getStatusCode(), [200, 204])) {
                $data = json_decode($res->getBody(), TRUE);
                if (empty($data)) {
                    break;
                }

                $lists = array_merge($lists, $data);
                if (count($data) < self::LIMIT) {
                    break;
                }
            } else {
                throw new CleverConnectorsException(
                    sprintf('Request to CM custom fields failed. Code: [%s], Message: [%s].',
                        $res->getStatusCode(), $res->getBody()
                    ),
                    CleverConnectorsException::REQUEST_FAILED
                );
            }
        }

        return $this->processLists($lists);
    }

    /**
     * @param int $page
     *
     * @return string
     */
    private function getUrl(int $page = 0): string
    {
        return sprintf('%s/fields' . self::URL_PART, $this->getBaseUrl(), self::LIMIT, self::LIMIT * $page);
    }

    /**
     * @param array $lists
     *
     * @return array
     */
    private function processLists(array $lists): array
    {
        $ret = [];
        foreach ($lists as $list) {
            if (array_key_exists(self::FIELD_ID, $list) && array_key_exists(self::NAME, $list)) {
                $ret[] = [
                    self::FIELD_ID => $list[self::FIELD_ID],
                    self::NAME     => $list[self::NAME],
                ];
            }
        }

        return $ret;
    }

}