<?php declare(strict_types=1);

namespace Hanaboso\PipesFramework\ApiGateway\Locator;

use Doctrine\ODM\MongoDB\DocumentManager;
use Doctrine\Persistence\ObjectRepository;
use GuzzleHttp\Psr7\Uri;
use Hanaboso\CommonsBundle\Process\ProcessDto;
use Hanaboso\CommonsBundle\Redirect\RedirectInterface;
use Hanaboso\CommonsBundle\Transport\Curl\CurlManager;
use Hanaboso\CommonsBundle\Transport\Curl\Dto\RequestDto;
use Hanaboso\PipesFramework\Configurator\Document\Sdk;
use Hanaboso\PipesFramework\Configurator\Enum\NodeImplementationEnum;
use Hanaboso\PipesFramework\Configurator\Repository\SdkRepository;
use Hanaboso\Utils\String\Json;
use Hanaboso\Utils\Traits\LoggerTrait;
use LogicException;
use Psr\Log\LoggerAwareInterface;
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

    use LoggerTrait;

    public const USER_TASK_LIST     = ['user-task'];
    public const CUSTOM_ACTION_PATH = '%sapi/topologies/%s/nodes/%s/run-by-name';

    /**
     * @var ObjectRepository<Sdk>&SdkRepository
     */
    private SdkRepository $sdkRepository;

    /**
     * ServiceLocator constructor.
     *
     * @param DocumentManager   $dm
     * @param CurlManager       $curlManager
     * @param RedirectInterface $redirect
     * @param string            $backendHost
     */
    public function __construct(
        DocumentManager $dm,
        private readonly CurlManager $curlManager,
        private readonly RedirectInterface $redirect,
        private readonly string $backendHost,
    )
    {
        $this->sdkRepository = $dm->getRepository(Sdk::class);
        $this->logger        = new NullLogger();
    }

    /**
     * --------------------------------------------- APP Store -----------------------------------------
     */

    /**
     * @param string $exclude
     *
     * @return mixed[]
     */
    public function getApps(string $exclude = ''): array
    {
        $res = $this->doRequest('applications');
        if (empty($res) || !isset($res['items'])) {
            $res['items'] = [];
        }

        $this->excludeItem($res, $exclude);
        $res['filter'] = [];
        $res['sorter'] = [];
        $res['paging'] = [
            'itemsPerPage' => 50,
            'lastPage'     => 1,
            'nextPage'     => 1,
            'page'         => 1,
            'previousPage' => 1,
            'total'        => count($res['items']),
        ];

        return $res;
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
     * @param string $exclude
     *
     * @return mixed[]
     */
    public function getUserApps(string $user, string $exclude = ''): array
    {
        $res = $this->doRequest(sprintf('applications/users/%s', $user));
        if (empty($res) || !isset($res['items'])) {
            $res['items'] = [];
        }

        $this->excludeItem($res, $exclude);
        $res['sorter'] = [];
        $res['filter'] = [];
        $res['paging'] = [
            'itemsPerPage' => 50,
            'lastPage'     => 1,
            'nextPage'     => 1,
            'page'         => 1,
            'previousPage' => 1,
            'total'        => count($res['items']),
        ];

        return $res;
    }

    /**
     * @param string $key
     * @param string $user
     * @param string $customActionPath
     *
     * @return mixed[]
     * @throws Throwable
     */
    public function getAppDetail(string $key, string $user, string $customActionPath = self::CUSTOM_ACTION_PATH): array
    {
        $res = $this->doRequest(sprintf('applications/%s/users/%s', $key, $user));

        if (isset($res['customActions'])) {
            $res['customActions'] = $this->prepareCustomActionsUrls($res['customActions'], $customActionPath);
        } else {
            $res['customActions'] = [];
        }

        return $res;
    }

    /**
     * @param string $key
     * @param string $user
     *
     * @return mixed[]
     */
    public function installApp(string $key, string $user): array
    {
        try {
            $resp = $this->doRequest(
                sprintf('applications/%s/users/%s/install', $key, $user),
                CurlManager::METHOD_POST,
                [],
                FALSE,
                [],
                TRUE,
            );

            $this->doRequest(
                sprintf('applications/%s/sync/afterInstallCallback', $key),
                CurlManager::METHOD_POST,
                ['user' => $user, 'name' => $key],
                FALSE,
                [],
                TRUE,
            );

            return $resp;
        } catch (Throwable) {
            return [];
        }
    }

    /**
     * @param string $key
     * @param string $user
     *
     * @return mixed[]
     */
    public function uninstallApp(string $key, string $user): array
    {
        try {
            $this->changeState($key, $user, ['enabled' => FALSE]);

            $resp = $this->doRequest(
                sprintf('applications/%s/users/%s/uninstall', $key, $user),
                CurlManager::METHOD_DELETE,
                [],
                FALSE,
                [],
                TRUE,
            );

            $this->doRequest(
                sprintf('applications/%s/sync/afterUninstallCallback', $key),
                CurlManager::METHOD_POST,
                ['user' => $user, 'name' => $key],
                FALSE,
                [],
                TRUE,
            );

            return $resp;
        } catch (Throwable) {
            return [];
        }
    }

    /**
     * @param string  $key
     * @param string  $user
     * @param mixed[] $data
     *
     * @return mixed[]
     */
    public function changeState(string $key, string $user, array $data): array
    {
        $resp = $this->doRequest(
            sprintf('applications/%s/users/%s/changeState', $key, $user),
            CurlManager::METHOD_PUT,
            $data,
        );

        $action = ($data['enabled'] ?? FALSE) === FALSE ? 'afterDisableCallback' : 'afterEnableCallback';

        $this->doRequest(
            sprintf('applications/%s/sync/%s', $key, $action),
            CurlManager::METHOD_POST,
            ['user' => $user, 'name' => $key],
            FALSE,
            [],
            TRUE,
        );

        return $resp;
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
            [],
            TRUE,
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
                $ip   = $sdk->getUrl();
                $name = $sdk->getName();
                $con  = new RequestDto(
                    new Uri(sprintf('%s/connector/list', $ip)),
                    CurlManager::METHOD_GET,
                    new ProcessDto(),
                );
                $cst  = new RequestDto(
                    new Uri(sprintf('%s/custom-node/list', $ip)),
                    CurlManager::METHOD_GET,
                    new ProcessDto(),
                );
                $btch = new RequestDto(
                    new Uri(sprintf('%s/batch/list', $ip)),
                    CurlManager::METHOD_GET,
                    new ProcessDto(),
                );

                try {
                    $n[$name][NodeImplementationEnum::CONNECTOR->value] = $this->curlManager->send($con)->getJsonBody();
                } catch (Throwable) {
                    $n[$name][NodeImplementationEnum::CONNECTOR->value] = [];
                }

                try {
                    $n[$name][NodeImplementationEnum::CUSTOM->value] = $this->curlManager->send($cst)->getJsonBody();
                } catch (Throwable) {
                    $n[$name][NodeImplementationEnum::CUSTOM->value] = [];
                }

                try {
                    $n[$name][NodeImplementationEnum::BATCH->value] = $this->curlManager->send($btch)->getJsonBody();
                } catch (Throwable) {
                    $n[$name][NodeImplementationEnum::BATCH->value] = [];
                }
            } catch (Throwable $t) {
                $this->logger->error($t->getMessage(), ['Exception' => $t, 'Sdk' => $sdk]);
            }
        }
        $n['backend'][NodeImplementationEnum::USER->value] = self::USER_TASK_LIST;

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
     * @param bool    $allowThrowException
     *
     * @return mixed[]
     * @throws Throwable
     */
    private function doRequest(
        string $url,
        string $method = CurlManager::METHOD_GET,
        array $body = [],
        bool $multiple = FALSE,
        array $headers = [],
        bool $allowThrowException = FALSE,
    ): array
    {
        $out = [];
        foreach ($this->getSdks() as $sdk) {
            try {
                $ip = $sdk->getUrl();

                $dto = new RequestDto(
                    new Uri(sprintf('%s/%s', $ip, $url)),
                    $method,
                    new ProcessDto(),
                    '',
                    $headers,
                );
                if (!empty($body)) {
                    $dto->setBody(Json::encode($body));
                }

                $res = $this->curlManager->send($dto);
                if (in_array($res->getStatusCode(), [200, 201], TRUE)) {
                    if (!empty($res->getJsonBody())) {
                        if (!$multiple) {
                            $out = array_merge($res->getJsonBody(), ['host' => $ip]);

                            break;
                        } else {
                            $out = array_merge($out, $res->getJsonBody());
                        }
                    }
                } else if ($res->getStatusCode() === 404) {
                    throw new LogicException(sprintf('Route not found. Message: %s', $res->getBody()));
                } else {
                    throw new LogicException(
                        sprintf(
                            'Unknown error. Message: %s, Status: %s, URL: %s, Headers: %s',
                            $res->getBody(),
                            $res->getStatusCode(),
                            $dto->getUri(TRUE),
                            Json::encode($dto->getHeaders()),
                        ),
                    );
                }
            } catch (Throwable $t) {
                $this->logger->error($t->getMessage(), ['Exception' => $t, 'Sdk' => $sdk]);

                if ($allowThrowException) {
                    throw $t;
                }
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

        if ($s === '?') {
            $s = '';
        }

        return $s;
    }

    /**
     * @param mixed[] $res
     * @param string  $exclude
     *
     * @return void
     */
    private function excludeItem(array &$res, string $exclude): void
    {
        array_walk($res['items'], static function ($item, $key) use ($res, $exclude): void {
            if ($item['key'] === $exclude) {
                unset($res['items'][$key]);
            }
        });

        $res['items'] = array_values($res['items']);
    }

    /**
     * @param mixed[] $customActions
     * @param string  $path
     *
     * @return mixed[]
     */
    private function prepareCustomActionsUrls(array $customActions, string $path): array
    {
        $actions = [];
        foreach ($customActions as $action) {
            if (empty($action['url'])) {
                $action['url'] = sprintf($path, $this->backendHost, $action['topologyName'], $action['nodeName']);

            }
            unset($action['topologyName'], $action['nodeName']);

            $actions[] = $action;
        }

        return $actions;
    }

}
