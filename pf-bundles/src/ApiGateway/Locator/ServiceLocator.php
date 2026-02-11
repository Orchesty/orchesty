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
use Hanaboso\Utils\String\Base64;
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

    public const array USER_TASK_LIST      = ['user-task'];
    public const string CUSTOM_ACTION_PATH = '%sapi/topologies/%s/nodes/%s/run-by-name';

    private const string ITEMS          = 'items';
    private const string KEY            = 'key';
    private const string URL            = 'url';
    private const string NAME           = 'name';
    private const string LOGO           = 'logo';
    private const string DESCRIPTION    = 'description';
    private const string INSTALLABLE    = 'installable';
    private const string INSTALLED      = 'installed';
    private const string ACTIVATED      = 'activated';
    private const string AUTHORIZED     = 'authorized';
    private const string ENABLED        = 'enabled';
    private const string IS_INSTALLABLE = 'isInstallable';
    private const string APPLICATIONS   = 'applications';

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

    /*
     * --------------------------------------------- APP Store -----------------------------------------
     */

    /**
     * @param string $user
     *
     * @return mixed[]
     */
    public function getApplications(string $user): array
    {
        /** @var array<string, mixed[]> $applications */
        $applications = [];

        foreach ($this->getSdks() as $sdk) {
            $sdkName                = $sdk->getName();
            $applications[$sdkName] = [
                self::APPLICATIONS => [],
                self::NAME         => $sdkName,
                self::URL          => $sdk->getUrl(),
            ];

            $availableApplications = $this->doRequest('applications', $sdkName);
            $installedApplications = $this->doRequest(sprintf('applications/users/%s', $user), $sdkName);

            foreach ($availableApplications[self::ITEMS] ?? [] as $application) {
                $applications[$sdkName][self::APPLICATIONS][$application[self::KEY]] = [
                    self::ACTIVATED    => FALSE,
                    self::AUTHORIZED   => FALSE,
                    self::DESCRIPTION  => $application[self::DESCRIPTION],
                    self::INSTALLABLE  => $application[self::IS_INSTALLABLE],
                    self::INSTALLED    => FALSE,
                    self::KEY          => $application[self::KEY],
                    self::LOGO         => $application[self::LOGO],
                    self::NAME         => $application[self::NAME],
                ];
            }

            foreach ($installedApplications[self::ITEMS] ?? [] as $application) {
                if (!array_key_exists($application[self::KEY], $applications[$sdkName][self::APPLICATIONS])) {
                    continue;
                }

                $applications[$sdkName][self::APPLICATIONS][$application[self::KEY]][self::INSTALLED]  = TRUE;
                $applications[$sdkName][self::APPLICATIONS][$application[self::KEY]][self::ACTIVATED]  = $application[self::ENABLED];
                $applications[$sdkName][self::APPLICATIONS][$application[self::KEY]][self::AUTHORIZED] = $application[self::AUTHORIZED];
            }

            $applications[$sdkName][self::APPLICATIONS] = array_values($applications[$sdkName][self::APPLICATIONS]);
        }

        return array_values($applications);
    }

    /**
     * @param string $sdk
     * @param string $exclude
     *
     * @return mixed[]
     */
    public function getApps(string $sdk, string $exclude = ''): array
    {
        $res = $this->doRequest('applications', $sdk);
        if ($res === [] || !isset($res['items'])) {
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
     * @param string $sdk
     *
     * @return mixed[]
     */
    public function getApp(string $key, string $sdk): array
    {
        return $this->doRequest(sprintf('applications/%s', $key), $sdk);
    }

    /**
     * @param string $user
     * @param string $sdk
     * @param string $exclude
     *
     * @return mixed[]
     */
    public function getUserApps(string $user, string $sdk, string $exclude = ''): array
    {
        $res = $this->doRequest(sprintf('applications/users/%s', $user), $sdk);
        if ($res === [] || !isset($res['items'])) {
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
     * @param string $sdk
     * @param string $customActionPath
     *
     * @return mixed[]
     * @throws Throwable
     */
    public function getAppDetail(
        string $key,
        string $user,
        string $sdk,
        string $customActionPath = self::CUSTOM_ACTION_PATH,
    ): array {
        $res = $this->doRequest(sprintf('applications/%s/users/%s', $key, $user), $sdk);

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
     * @param string $sdk
     *
     * @return mixed[]
     */
    public function installApp(string $key, string $user, string $sdk): array
    {
        try {
            $resp = $this->doRequest(
                sprintf('applications/%s/users/%s/install', $key, $user),
                $sdk,
                CurlManager::METHOD_POST,
                [],
                FALSE,
                [],
                TRUE,
            );

            $this->doRequest(
                sprintf('applications/%s/sync/afterInstallCallback', $key),
                $sdk,
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
     * @param string $sdk
     *
     * @return mixed[]
     */
    public function uninstallApp(string $key, string $user, string $sdk): array
    {
        try {
            $this->changeState($key, $user, $sdk, ['enabled' => FALSE]);

            $resp = $this->doRequest(
                sprintf('applications/%s/users/%s/uninstall', $key, $user),
                $sdk,
                CurlManager::METHOD_DELETE,
                [],
                FALSE,
                [],
                TRUE,
            );

            $this->doRequest(
                sprintf('applications/%s/sync/afterUninstallCallback', $key),
                $sdk,
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
     * @param string  $sdk
     * @param mixed[] $data
     *
     * @return mixed[]
     */
    public function changeState(string $key, string $user, string $sdk, array $data): array
    {
        $resp = $this->doRequest(
            sprintf('applications/%s/users/%s/changeState', $key, $user),
            $sdk,
            CurlManager::METHOD_PUT,
            $data,
        );

        $action = ($data['enabled'] ?? FALSE) === FALSE ? 'afterDisableCallback' : 'afterEnableCallback';

        $this->doRequest(
            sprintf('applications/%s/sync/%s', $key, $action),
            $sdk,
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
     * @param string  $sdk
     * @param mixed[] $data
     *
     * @return mixed[]
     */
    public function updateApp(string $key, string $user, string $sdk, array $data): array
    {
        return $this->doRequest(
            sprintf('applications/%s/users/%s/settings', $key, $user),
            $sdk,
            CurlManager::METHOD_PUT,
            $data,
        );
    }

    /**
     * @param string  $key
     * @param string  $user
     * @param string  $sdk
     * @param mixed[] $data
     *
     * @return mixed[]
     */
    public function updateAppPassword(string $key, string $user, string $sdk, array $data): array
    {
        return $this->doRequest(
            sprintf('applications/%s/users/%s/password', $key, $user),
            $sdk,
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
            $this->getSdkNameByInstalledApplication($key),
        );
    }

    /**
     * @param mixed[] $query
     *
     * @return mixed[]
     */
    public function authorizationQueryToken(array $query): array
    {
        $key = explode(':', Base64::base64UrlDecode($query['state']))[1];

        return $this->doRequest(
            sprintf('applications/authorize/token%s', $this->queryToString($query)),
            $this->getSdkNameByInstalledApplication($key),
        );
    }

    /**
     * @param string $key
     * @param string $user
     * @param string $sdk
     * @param string $redirect
     */
    public function authorize(string $key, string $user, string $sdk, string $redirect): void
    {
        $url = $this->doRequest(
            sprintf('applications/%s/users/%s/authorize?redirect_url=%s', $key, $user, $redirect),
            $sdk,
        );
        if (!isset($url['authorizeUrl'])) {
            throw new LogicException(sprintf('App %s is not found!', $key));
        }

        $this->redirect->make($url['authorizeUrl']);
    }

    /**
     * @param string $key
     * @param string $sdk
     *
     * @return mixed[]
     */
    public function listSyncActions(string $key, string $sdk): array
    {
        return $this->doRequest(
            sprintf('applications/%s/sync/list', $key),
            $sdk,
        );
    }

    /**
     * @param Request $request
     * @param string  $key
     * @param string  $sdk
     * @param string  $method
     *
     * @return string
     */
    public function runSyncActions(Request $request, string $key, string $sdk, string $method): string
    {
        return $this->doRequest(
            sprintf('applications/%s/sync/%s%s', $key, $method, $this->queryToString($request->query->all())),
            $sdk,
            $request->getMethod(),
            $request->request->all(),
            FALSE,
            [],
            TRUE,
            TRUE,
        )[0];
    }

    /*
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

    /*
     * -------------------------------------------- Webhooks -----------------------------------------
     */

    /**
     * @param string  $key
     * @param string  $user
     * @param string  $sdk
     * @param mixed[] $body
     *
     * @return mixed[]
     */
    public function subscribeWebhook(string $key, string $user, string $sdk, array $body): array
    {
        return $this->doRequest(
            sprintf('webhook/applications/%s/users/%s/subscribe', $key, $user),
            $sdk,
            CurlManager::METHOD_POST,
            $body,
        );
    }

    /**
     * @param string  $key
     * @param string  $user
     * @param string  $sdk
     * @param mixed[] $body
     *
     * @return mixed[]
     */
    public function unSubscribeWebhook(string $key, string $user, string $sdk, array $body): array
    {
        return $this->doRequest(
            sprintf('webhook/applications/%s/users/%s/unsubscribe', $key, $user),
            $sdk,
            CurlManager::METHOD_POST,
            $body,
        );
    }

    /**
     * @param string $key
     * @return string
     */
    public function getSdkNameByInstalledApplication(string $key): string
    {
        foreach ($this->getSdks() as $sdk) {
            $hasApplication = array_find(
                $this->doRequest('applications', $sdk->getName())['items'] ?? [],
                static fn(array $application): bool => $application['key'] === $key,
            ) !== NULL;

            if ($hasApplication) {
                return $sdk->getName();
            }
        }

        return $this->getSdks()[0]->getName();
    }

    /*
     * --------------------------------------------- HELPERS -----------------------------------------
     */

    /**
     * @param string  $url
     * @param string  $sdkName
     * @param string  $method
     * @param mixed[] $body
     * @param bool    $multiple
     * @param mixed[] $headers
     * @param bool    $allowThrowException
     * @param bool    $allowOriginalResponse
     *
     * @return mixed[]
     * @throws Throwable
     */
    private function doRequest(
        string $url,
        string $sdkName,
        string $method = CurlManager::METHOD_GET,
        array $body = [],
        bool $multiple = FALSE,
        array $headers = [],
        bool $allowThrowException = FALSE,
        bool $allowOriginalResponse = FALSE,
    ): array
    {
        $out = [];
        $sdk = array_values(array_filter(
            $this->getSdks(),
            static fn(Sdk $sdk): bool => $sdk->getName() === $sdkName,
        ))[0];
        try {
            $ip = $sdk->getUrl();

            $dto = new RequestDto(
                new Uri(sprintf('%s/%s', $ip, $url)),
                $method,
                new ProcessDto(),
                '',
                $headers,
            );
            if ($body !== []) {
                $dto->setBody(Json::encode($body));
            }

            $res = $this->curlManager->send($dto);
            if (in_array($res->getStatusCode(), [200, 201], TRUE)) {
                if($allowOriginalResponse){
                    $out[] = $res->getBody();
                }else if ($res->getJsonBody() !== []) {
                    if (!$multiple) {
                        $out = array_merge($res->getJsonBody(), ['host' => $ip]);
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
            // @phpstan-ignore-next-line
            if (empty($action['url'])) {
                $action['url'] = sprintf($path, $this->backendHost, $action['topologyName'], $action['nodeName']);

            }
            unset($action['topologyName'], $action['nodeName']);

            $actions[] = $action;
        }

        return $actions;
    }

}
