<?php declare(strict_types=1);

namespace CleverConnectors\AppBundle\Model\Systems\Impl\Facebookaudience\Connector;

use CleverConnectors\AppBundle\Document\SystemInstall;
use CleverConnectors\AppBundle\Exceptions\CleverConnectorsException;
use CleverConnectors\AppBundle\Model\Systems\Exceptions\SystemException;
use CleverConnectors\AppBundle\Model\Systems\Impl\Facebookaudience\FacebookaudienceSystem;
use CleverConnectors\AppBundle\Utils\CMHeaders;
use GuzzleHttp\Psr7\Uri;
use Hanaboso\CommonsBundle\Process\ProcessDto;
use Hanaboso\CommonsBundle\Transport\Curl\CurlException;
use Hanaboso\CommonsBundle\Transport\Curl\CurlManager;
use Hanaboso\PipesFramework\Authorization\Provider\OAuth2Provider;

/**
 * Class FacebookaudienceGetAdBudgetConnector
 *
 * @package CleverConnectors\AppBundle\Model\Systems\Impl\Facebookaudience\Connector
 */
class FacebookaudienceGetAdBudgetConnector extends FacebookaudienceConnectorAbstract
{

    private const INSIGHT_URL = '%s/%s/insights?fields=adset_id,spend&access_token=%s';
    private const ADSET_URL   = '%s/%s?fields=lifetime_budget,daily_budget,budget_remaining&access_token=%s';

    /**
     * @return string
     */
    public function getId(): string
    {
        return 'facebookaudience-get-adbudget-connector';
    }

    /**
     * @param ProcessDto $dto
     *
     * @return ProcessDto
     * @throws CleverConnectorsException
     * @throws CurlException
     * @throws SystemException
     */
    public function processAction(ProcessDto $dto): ProcessDto
    {
        $res           = [];
        $data          = json_decode($dto->getData(), TRUE);
        $systemInstall = $this->systemInstallRepository->getSystemInstallFromHeaders($dto->getHeaders());

        if (!array_key_exists('ad_id', $data)) {
            throw new CleverConnectorsException(
                'Missing required field [ad_id].',
                CleverConnectorsException:: MISSING_DATA
            );
        }

        try {
            $res = $this->fetchInsights($systemInstall, $data['ad_id'], $dto);
            $res = $this->fetchAdset($systemInstall, $res, $dto);
        } catch (CurlException $e) {
            return $this->logConnectorError($e, $systemInstall, $this->system, $dto);
        }

        return $dto->setData(json_encode($res));
    }

    /**
     * @param SystemInstall $systemInstall
     * @param string        $adId
     *
     * @return array
     * @throws CleverConnectorsException
     * @throws CurlException
     * @throws SystemException
     */
    public function getAdBudget(SystemInstall $systemInstall, string $adId): array
    {
        $res = [];

        try {
            $res = $this->fetchInsights($systemInstall, $adId);
            $res = $this->fetchAdset($systemInstall, $res);
        } catch (CurlException $e) {
            $this->logConnectorError($e, $systemInstall, $this->system);
        }

        return $res;
    }

    /**
     * @param SystemInstall   $systemInstall
     * @param string          $adId
     * @param ProcessDto|null $dto
     *
     * @return array
     * @throws SystemException
     */
    private function fetchInsights(SystemInstall $systemInstall, string $adId, ?ProcessDto $dto = NULL): array
    {
        $requestDto = $this->system->getRequestDto($systemInstall, CurlManager::METHOD_GET);
        $token      = $systemInstall->getSettings()[OAuth2Provider::ACCESS_TOKEN];
        $requestDto->setUri(new Uri(sprintf(self::INSIGHT_URL, $requestDto->getUri(), $adId, $token)));

        if ($dto) {
            $requestDto->setDebugInfo(CMHeaders::debugInfo($dto->getHeaders()));
        }

        $res  = $this->manager->send($requestDto);
        $body = json_decode($res->getBody(), TRUE)['data'][0];

        return [
            'ad_id'    => $adId,
            'adset_id' => $body['adset_id'],
            'spend'    => $body['spend'],
        ];
    }

    /**
     * @param SystemInstall   $systemInstall
     * @param array           $data
     * @param ProcessDto|null $dto
     *
     * @return array
     * @throws CleverConnectorsException
     * @throws SystemException
     */
    private function fetchAdset(SystemInstall $systemInstall, array $data, ?ProcessDto $dto = NULL): array
    {
        $adAccountId = $systemInstall->getSettings()[FacebookaudienceSystem::AD_ACCOUNT] ?? '';

        if (empty($adAccountId)) {
            throw new CleverConnectorsException(
                'Missing Ad Account ID',
                CleverConnectorsException::MISSING_DATA
            );
        }

        $requestDto = $this->system->getRequestDto($systemInstall, CurlManager::METHOD_GET);
        $token      = $systemInstall->getSettings()[OAuth2Provider::ACCESS_TOKEN];
        $requestDto->setUri(new Uri(sprintf(self::ADSET_URL, $requestDto->getUri(), $data['adset_id'], $token)));

        if ($dto) {
            $requestDto->setDebugInfo(CMHeaders::debugInfo($dto->getHeaders()));
        }

        $res  = $this->manager->send($requestDto);
        $body = json_decode($res->getBody(), TRUE);

        $data = array_merge($data, [
            'lifetime_budget'  => $body['lifetime_budget'],
            'daily_budget'     => $body['daily_budget'],
            'budget_remaining' => $body['budget_remaining'],
        ]);

        return $data;
    }

}