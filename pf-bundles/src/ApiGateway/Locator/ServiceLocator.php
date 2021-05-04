<?php declare(strict_types=1);

namespace Hanaboso\PipesFramework\ApiGateway\Locator;

use Doctrine\ODM\MongoDB\DocumentManager;
use Doctrine\Persistence\ObjectRepository;
use GuzzleHttp\Psr7\Uri;
use Hanaboso\CommonsBundle\Redirect\RedirectInterface;
use Hanaboso\CommonsBundle\Transport\Curl\CurlManager;
use Hanaboso\CommonsBundle\Transport\Curl\Dto\RequestDto;
use Hanaboso\PipesFramework\Configurator\Document\Sdk;
use Hanaboso\PipesFramework\Configurator\Enum\NodeImplementationEnum;
use Hanaboso\PipesFramework\Configurator\Repository\SdkRepository;
use Hanaboso\Utils\String\Json;
use LogicException;
use Psr\Log\LoggerAwareInterface;
use Psr\Log\LoggerInterface;
use Psr\Log\NullLogger;
use Symfony\Component\HttpFoundation\Request;
use Throwable;

/**
 * Class ServiceLocator
 *
 * @package Hanaboso\PipesFramework\ApiGateway\Locator
 */
final class ServiceLocator implements LoggerAwareInterface
{

    /**
     * @var ObjectRepository<Sdk>&SdkRepository
     */
    private $sdkRepository;

    /**
     * @var LoggerInterface
     */
    private LoggerInterface $logger;

    /**
     * ServiceLocator constructor.
     *
     * @param DocumentManager   $dm
     * @param CurlManager       $curlManager
     * @param RedirectInterface $redirect
     */
    public function __construct(
        DocumentManager $dm,
        private CurlManager $curlManager,
        private RedirectInterface $redirect,
    )
    {
        $this->sdkRepository = $dm->getRepository(Sdk::class);
        $this->logger        = new NullLogger();
    }

    /**
     * --------------------------------------------- APP Store -----------------------------------------
     */

    /**
     * @param LoggerInterface $logger
     *
     * @return $this
     */
    public function setLogger(LoggerInterface $logger): ServiceLocator
    {
        $this->logger = $logger;

        return $this;
    }

    /**
     * @return mixed[]
     */
    public function getApps(): array
    {
        return $this->doRequest('applications', CurlManager::METHOD_GET, [], TRUE);
    }

    /**
     * @param string $key
     *
     * @return mixed[]
     */
    public function getApp(string $key): array
    {
        return $this->doRequest(sprintf('applications/%s', $key));
    }

    /**
     * @param string $user
     *
     * @return mixed[]
     */
    public function getUserApps(string $user): array
    {
        $res = $this->doRequest(sprintf('applications/users/%s', $user), CurlManager::METHOD_GET, [], TRUE);
        if (empty($res) || !isset($res['items'])) {
            $res['items'] = [];
        }

        return $res;
    }

    /**
     * @param string $key
     * @param string $user
     *
     * @return mixed[]
     */
    public function getAppDetail(string $key, string $user): array
    {
        return $this->doRequest(sprintf('applications/%s/users/%s', $key, $user));
    }

    /**
     * @param string $key
     * @param string $user
     *
     * @return mixed[]
     */
    public function installApp(string $key, string $user): array
    {
        return $this->doRequest(sprintf('applications/%s/users/%s/install', $key, $user), CurlManager::METHOD_POST);
    }

    /**
     * @param string $key
     * @param string $user
     *
     * @return mixed[]
     */
    public function uninstallApp(string $key, string $user): array
    {
        return $this->doRequest(sprintf('applications/%s/users/%s/uninstall', $key, $user), CurlManager::METHOD_DELETE);
    }

    /**
     * @param string  $key
     * @param string  $user
     * @param mixed[] $data
     *
     * @return mixed[]
     */
    public function updateApp(string $key, string $user, array $data): array
    {
        return $this->doRequest(
            sprintf('applications/%s/users/%s/settings', $key, $user),
            CurlManager::METHOD_PUT,
            $data,
        );
    }

    /**
     * @param string  $key
     * @param string  $user
     * @param mixed[] $data
     *
     * @return mixed[]
     */
    public function updateAppPassword(string $key, string $user, array $data): array
    {
        return $this->doRequest(
            sprintf('applications/%s/users/%s/password', $key, $user),
            CurlManager::METHOD_PUT,
            $data,
        );
    }

    /**
     * @param string  $key
     * @param string  $user
     * @param mixed[] $query
     *
     * @return mixed[]
     */
    public function authorizationToken(string $key, string $user, array $query): array
    {
        return $this->doRequest(
            sprintf('applications/%s/users/%s/authorize/token%s', $key, $user, $this->queryToString($query)),
            CurlManager::METHOD_GET,
        );
    }

    /**
     * @param mixed[] $query
     *
     * @return mixed[]
     */
    public function authorizationQueryToken(array $query): array
    {
        return $this->doRequest(
            sprintf('applications/authorize/token%s', $this->queryToString($query)),
            CurlManager::METHOD_GET,
        );
    }

    /**
     * @param string $key
     * @param string $user
     * @param string $redirect
     */
    public function authorize(string $key, string $user, string $redirect): void
    {
        $url = $this->doRequest(
            sprintf('applications/%s/users/%s/authorize?redirect_url=%s', $key, $user, $redirect),
            CurlManager::METHOD_GET,
        );
        if (!isset($url['authorizeUrl'])) {
            throw new LogicException(sprintf('App %s is not found!', $key));
        }

        $this->redirect->make($url['authorizeUrl']);
    }

    /**
     * @param string $key
     *
     * @return mixed[]
     */
    public function listSyncActions(string $key): array
    {
        return $this->doRequest(
            sprintf('applications/%s/sync/list', $key),
            CurlManager::METHOD_GET,
        );
    }

    /**
     * @param Request $request
     * @param string  $key
     * @param string  $method
     *
     * @return mixed[]
     */
    public function runSyncActions(Request $request, string $key, string $method): array
    {
        return $this->doRequest(
            sprintf('applications/%s/sync/%s%s', $key, $method, $this->queryToString($request->query->all())),
            $request->getMethod(),
            $request->request->all(),
            FALSE,
            $request->headers->all(),
        );
    }

    /**
     * --------------------------------------------- Nodes -----------------------------------------
     */

    /**
     * @return mixed[]
     */
    public function getNodes(): array
    {
        $n = [];
        foreach ($this->getSdks() as $sdk) {
            try {
                $ip   = $sdk->getKey();
                $name = $sdk->getValue();
                $con  = new RequestDto(CurlManager::METHOD_GET, new Uri(sprintf('%s/connector/list', $ip)));
                $cst  = new RequestDto(CurlManager::METHOD_GET, new Uri(sprintf('%s/custom_node/list', $ip)));
                $usr  = new RequestDto(CurlManager::METHOD_GET, new Uri(sprintf('%s/longRunning/list', $ip)));

                $n[$name][NodeImplementationEnum::CONNECTOR] = $this->curlManager->send($con)->getJsonBody();
                $n[$name][NodeImplementationEnum::CUSTOM]    = $this->curlManager->send($cst)->getJsonBody();
                $n[$name][NodeImplementationEnum::USER]      = $this->curlManager->send($usr)->getJsonBody();
            } catch (Throwable $t) {
                $this->logger->error($t->getMessage(), ['Exception' => $t, 'Sdk' => $sdk]);
            }
        }

        return $n;
    }

    /**
     * -------------------------------------------- Webhooks -----------------------------------------
     */

    /**
     * @param string  $key
     * @param string  $user
     * @param mixed[] $body
     *
     * @return mixed[]
     */
    public function subscribeWebhook(string $key, string $user, array $body): array
    {
        return $this->doRequest(
            sprintf('webhook/applications/%s/users/%s/subscribe', $key, $user),
            CurlManager::METHOD_POST,
            $body,
        );
    }

    /**
     * @param string  $key
     * @param string  $user
     * @param mixed[] $body
     *
     * @return mixed[]
     */
    public function unSubscribeWebhook(string $key, string $user, array $body): array
    {
        return $this->doRequest(
            sprintf('webhook/applications/%s/users/%s/unsubscribe', $key, $user),
            CurlManager::METHOD_POST,
            $body,
        );
    }

    /**
     * --------------------------------------------- HELPERS -----------------------------------------
     */

    /**
     * @param string  $url
     * @param string  $method
     * @param mixed[] $body
     * @param bool    $multiple
     * @param mixed[] $headers
     *
     * @return mixed[]
     */
    private function doRequest(
        string $url,
        string $method = CurlManager::METHOD_GET,
        array $body = [],
        bool $multiple = FALSE,
        array $headers = [],
    ): array
    {
        $out = [];
        foreach ($this->getSdks() as $sdk) {
            try {
                $ip = $sdk->getKey();

                $dto = new RequestDto($method, new Uri(sprintf('%s/%s', $ip, $url)), $headers);
                if (!empty($body)) {
                    $dto->setBody(Json::encode($body));
                }

                $res = $this->curlManager->send($dto);
                if ($res->getStatusCode() === 200 && !empty($res->getJsonBody())) {
                    if (!$multiple) {
                        $out = array_merge($res->getJsonBody(), ['host' => $ip]);

                        break;
                    } else {
                        $out = array_merge($out, $res->getJsonBody());
                    }
                }
            } catch (Throwable $t) {
                $this->logger->error($t->getMessage(), ['Exception' => $t, 'Sdk' => $sdk]);
            }
        }

        return $out;
    }

    /**
     * @return Sdk[]
     */
    private function getSdks(): array
    {
        return $this->sdkRepository->findAll();
    }

    /**
     * @param mixed[] $query
     *
     * @return string
     */
    private function queryToString(array $query): string
    {
        $s     = '?';
        $first = TRUE;
        foreach ($query as $key => $item) {

            if (!$first) {
                $s .= '&';
            } else {
                $first = FALSE;
            }

            if (is_array($item)) {
                $item = reset($item);
            }

            $s = sprintf('%s%s=%s', $s, $key, $item);
        }

        return $s;
    }

}
