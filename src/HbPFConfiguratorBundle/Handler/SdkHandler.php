<?php declare(strict_types=1);

namespace Hanaboso\PipesFramework\HbPFConfiguratorBundle\Handler;

use Doctrine\ODM\MongoDB\DocumentManager;
use Doctrine\ODM\MongoDB\DocumentNotFoundException;
use Doctrine\ODM\MongoDB\MongoDBException;
use Hanaboso\PipesFramework\Configurator\Document\ApiToken;
use Hanaboso\PipesFramework\Configurator\Document\Sdk;
use Hanaboso\PipesFramework\Configurator\Model\SdkManager;
use Hanaboso\PipesFramework\HbPFApiGatewayBundle\Controller\ApplicationController;
use Hanaboso\Utils\Exception\PipesFrameworkException;
use Hanaboso\Utils\System\ControllerUtils;

/**
 * Class SdkHandler
 *
 * @package Hanaboso\PipesFramework\HbPFConfiguratorBundle\Handler
 */
final class SdkHandler
{

    /**
     * SdkHandler constructor.
     *
     * @param SdkManager      $manager
     * @param DocumentManager $dm
     * @param string          $instanceId
     * @param string          $instanceUrlPrefix
     * @param string          $tunnelProxyUrl
     * @param string          $backendUrl
     * @param string          $startingPointUrl
     * @param string          $workerApiUrl
     */
    public function __construct(
        private SdkManager $manager,
        private DocumentManager $dm,
        private string $instanceId,
        private string $instanceUrlPrefix = '',
        private string $tunnelProxyUrl = '',
        private string $backendUrl = '',
        private string $startingPointUrl = '',
        private string $workerApiUrl = '',
    )
    {
    }

    /**
     * @return mixed[]
     */
    public function getAll(): array
    {
        $sdks = $this->manager->getAll();

        return [
            'filter' => [],
            'items'  => array_map(static fn(Sdk $sdk): array => $sdk->toArray(), $sdks),
            'paging' => [
                'itemsPerPage' => 50,
                'lastPage'     => 1,
                'nextPage'     => 1,
                'page'         => 1,
                'previousPage' => 1,
                'total'        => count($sdks),
            ],
        ];
    }

    /**
     * @param string $id
     *
     * @return mixed[]
     * @throws DocumentNotFoundException
     */
    public function getOne(string $id): array
    {
        return $this->get($id)->toArray();
    }

    /**
     * @param mixed[] $data
     *
     * @return mixed[]
     * @throws MongoDBException
     * @throws PipesFrameworkException
     */
    public function create(array $data): array
    {
        $isTunnel = ($data[Sdk::TYPE] ?? Sdk::TYPE_HTTP) === Sdk::TYPE_TUNNEL;
        $required = $isTunnel ? [Sdk::NAME] : [Sdk::NAME, Sdk::URL];
        ControllerUtils::checkParameters($required, $data);

        return $this->manager->create($data)->toArray();
    }

    /**
     * @param string  $id
     * @param mixed[] $data
     *
     * @return mixed[]
     * @throws DocumentNotFoundException
     * @throws MongoDBException
     */
    public function update(string $id, array $data): array
    {
        return $this->manager->update($this->get($id), $data)->toArray();
    }

    /**
     * @param string $id
     *
     * @return mixed[]
     * @throws DocumentNotFoundException
     * @throws MongoDBException
     */
    public function delete(string $id): array
    {
        return $this->manager->delete($this->get($id))->toArray();
    }

    /**
     * @param string $id
     *
     * @return mixed[]
     * @throws DocumentNotFoundException
     */
    public function getTunnelEnv(string $id): array
    {
        $sdk = $this->get($id);

        if ($sdk->getType() !== Sdk::TYPE_TUNNEL) {
            throw new DocumentNotFoundException(sprintf('SDK "%s" is not a tunnel worker.', $id));
        }

        return $this->getEnv($id);
    }

    /**
     * @param string $id
     *
     * @return mixed[]
     * @throws DocumentNotFoundException
     */
    public function getEnv(string $id): array
    {
        $sdk    = $this->get($id);
        $apiKey = $this->dm->getRepository(ApiToken::class)
            ->findOneBy(['user' => ApplicationController::SYSTEM_USER])?->getKey() ?? '';

        $lines = [];

        if ($sdk->getType() === Sdk::TYPE_TUNNEL) {
            $lines[] = '# --- Orchesty Tunnel Configuration ---';
            $lines[] = 'TUNNEL_ENABLED=true';
            $lines[] = sprintf('TUNNEL_WORKER_ID="%s"', $sdk->getName());
            if ($this->tunnelProxyUrl !== '') {
                $lines[] = sprintf('TUNNEL_PROXY_URL=%s', $this->tunnelProxyUrl);
            }
            $lines[] = '';
        }

        $tenant = $this->instanceUrlPrefix !== ''
            ? sprintf('%s-%s', $this->instanceUrlPrefix, $this->instanceId)
            : $this->instanceId;

        $lines[] = '# --- Orchesty Platform Connection ---';
        $lines[] = sprintf('TENANT_ID=%s', $tenant);
        $lines[] = 'CRYPT_SECRET=_CHANGE_ME_';
        $lines[] = sprintf('ORCHESTY_API_KEY=%s', $apiKey);

        // Self-hosted instances expose externally-reachable URLs of platform services so
        // that workers running outside the docker/k8s network can reach them. On cloud
        // these stay empty and the SDK falls back to TENANT_ID-derived URLs, keeping the
        // snippet bit-identical with the legacy output.
        if ($this->backendUrl !== '' || $this->startingPointUrl !== '' || $this->workerApiUrl !== '') {
            $lines[] = '';
            $lines[] = '# --- Orchesty Platform URLs ---';
            if ($this->backendUrl !== '') {
                $lines[] = sprintf('BACKEND_URL=%s', $this->backendUrl);
            }
            if ($this->startingPointUrl !== '') {
                $lines[] = sprintf('STARTING_POINT_URL=%s', $this->startingPointUrl);
            }
            if ($this->workerApiUrl !== '') {
                $lines[] = sprintf('WORKER_API_HOST=%s', $this->workerApiUrl);
            }
        }

        return ['env' => implode("\n", $lines)];
    }

    /**
     * @param string $id
     *
     * @return Sdk
     * @throws DocumentNotFoundException
     */
    private function get(string $id): Sdk
    {
        return $this->manager->getOne($id);
    }

}
