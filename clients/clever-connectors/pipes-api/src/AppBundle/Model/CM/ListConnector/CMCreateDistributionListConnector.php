<?php declare(strict_types=1);

/**
 * Created by PhpStorm.
 * User: radek.jirsa
 * Date: 6.3.18
 * Time: 11:59
 */

namespace CleverConnectors\AppBundle\Model\CM\ListConnector;

use CleverConnectors\AppBundle\Exceptions\CleverConnectorsException;
use CleverConnectors\AppBundle\Utils\CMHeaders;
use GuzzleHttp\Psr7\Uri;
use Hanaboso\PipesFramework\Commons\Process\ProcessDto;
use Hanaboso\PipesFramework\Commons\Transport\Curl\CurlManager;
use Hanaboso\PipesFramework\Commons\Transport\Curl\Dto\RequestDto;

/**
 * Class CMCreateDistributionListConnector
 *
 * @package CleverConnectors\AppBundle\Model\CM\ListConnector
 */
class CMCreateDistributionListConnector extends CMDistributionListAbstract
{

    /**
     * @return string
     */
    public function getId(): string
    {
        return 'clevermonitors-create-distributionlist-connector';
    }

    /**
     * @param ProcessDto $dto
     *
     * @return ProcessDto
     * @throws CleverConnectorsException
     */
    public function processAction(ProcessDto $dto): ProcessDto
    {
        $dto->setData(json_encode($this->createList($dto)));

        return $dto;
    }

    /**
     * @param ProcessDto $dto
     *
     * @return array
     * @throws CleverConnectorsException
     */
    public function createList(ProcessDto $dto): array
    {
        $user  = CMHeaders::get(CMHeaders::GUID, $dto->getHeaders());
        $token = CMHeaders::get(CMHeaders::TOKEN, $dto->getHeaders());

        if (!isset($user) || !isset($token)) {
            throw new CleverConnectorsException(
                'User or Token is missing in header.',
                CleverConnectorsException::MISSING_DATA
            );
        }
        $req = new RequestDto(CurlManager::METHOD_POST, new Uri(self::URL));
        $req
            ->setDebugInfo(CMHeaders::debugInfo($dto->getHeaders()))
            ->setHeaders($this->getAuthorizationHeaders($user, $token))
            ->setBody($dto->getData());

        $res = $this->curl->send($req);

        if ($res->getStatusCode() == 201) {
            $data   = json_decode($res->getBody(), TRUE);
            $output = [];

            if (isset($data['name'])) {
                $output['name'] = $data['name'];
            }

            if (isset($data['list_id'])) {
                $output['id'] = $data['list_id'];
            }

            return $output;
        }

        throw new CleverConnectorsException($res->getBody() . $res->getStatusCode());
    }

}