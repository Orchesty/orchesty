<?php declare(strict_types=1);

namespace CleverConnectors\AppBundle\Model\CM\ListConnector;

use CleverConnectors\AppBundle\Exceptions\CleverConnectorsException;
use CleverConnectors\AppBundle\Utils\CMHeaders;
use GuzzleHttp\Psr7\Uri;
use Hanaboso\CommonsBundle\Process\ProcessDto;
use Hanaboso\CommonsBundle\Transport\Curl\CurlManager;
use Hanaboso\CommonsBundle\Transport\Curl\Dto\RequestDto;

/**
 * Class CMGetDistributionsConnector
 *
 * @package CleverConnectors\AppBundle\Model\CM\ListConnector
 */
class CMGetDistributionsConnector extends CMDistributionListAbstract
{

    private const URL_PART = '?count=%s&offset=%s';
    private const LIMIT    = 50;

    /**
     * @return string
     */
    public function getId(): string
    {
        return 'clevermonitors-get-distributions-connector';
    }

    /**
     * @param ProcessDto $dto
     *
     * @return ProcessDto
     * @throws CleverConnectorsException
     */
    public function processAction(ProcessDto $dto): ProcessDto
    {
        $dto->setData(json_encode($this->getDistributionsArray($dto)));

        return $dto;
    }

    /**
     * @param ProcessDto $dto
     *
     * @return array
     * @throws CleverConnectorsException
     */
    public function getDistributionsArray(ProcessDto $dto): array
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
                    sprintf('Request to CM distribution list failed. Code: [%s], Message: [%s].',
                        $res->getStatusCode(), $res->getBody()
                    ),
                    CleverConnectorsException::REQUEST_FAILED
                );
            }
        }

        return $lists;
    }

    /**
     * @param int $page
     *
     * @return string
     */
    private function getUrl(int $page = 0): string
    {
        return sprintf('%s/lists' . self::URL_PART, $this->getBaseUrl(), self::LIMIT, self::LIMIT * $page);
    }

}