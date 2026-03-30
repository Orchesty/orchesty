<?php declare(strict_types=1);

namespace Hanaboso\PipesFrameworkEnterprise\HbPFEnterpriseConfiguratorBundle\Handler;

use GuzzleHttp\Psr7\Uri;
use Hanaboso\CommonsBundle\Process\ProcessDto;
use Hanaboso\CommonsBundle\Transport\Curl\CurlManager;
use Hanaboso\CommonsBundle\Transport\Curl\Dto\RequestDto;
use Hanaboso\Utils\String\Json;
use Throwable;

/**
 * Class CloudMemberSyncService
 *
 * @package Hanaboso\PipesFrameworkEnterprise\HbPFEnterpriseConfiguratorBundle\Handler
 */
final class CloudMemberSyncService
{

    /**
     * CloudMemberSyncService constructor.
     *
     * @param CurlManager $curlManager
     * @param string      $cloudUrl
     * @param string      $instanceId
     * @param string      $instanceSecret
     */
    public function __construct(
        private readonly CurlManager $curlManager,
        private readonly string $cloudUrl,
        private readonly string $instanceId,
        private readonly string $instanceSecret,
    )
    {
    }

    /**
     * @return bool
     */
    public function isEnabled(): bool
    {
        return $this->cloudUrl !== '' && $this->instanceId !== '' && $this->instanceSecret !== '';
    }

    /**
     * @param string      $email
     * @param string|null $name
     */
    public function syncMemberAdd(string $email, ?string $name = NULL): void
    {
        if (!$this->isEnabled()) {
            return;
        }

        $this->syncMembers([['email' => $email, 'name' => $name ?? $email, 'action' => 'add']]);
    }

    /**
     * @param string $email
     */
    public function syncMemberRemove(string $email): void
    {
        if (!$this->isEnabled()) {
            return;
        }

        $this->syncMembers([['email' => $email, 'action' => 'remove']]);
    }

    /**
     * @param string $query
     *
     * @return mixed[]
     */
    public function searchAccountUsers(string $query = ''): array
    {
        if (!$this->isEnabled()) {
            return [];
        }

        $params = http_build_query(array_filter([
            'instanceId'     => $this->instanceId,
            'instanceSecret' => $this->instanceSecret,
            'q'              => $query,
        ], static fn(string $v): bool => $v !== ''));

        $url = sprintf('%s/api/public/account-users?%s', rtrim($this->cloudUrl, '/'), $params);

        $dto = new RequestDto(
            new Uri($url),
            CurlManager::METHOD_GET,
            new ProcessDto(),
        );

        try {
            $response = $this->curlManager->send($dto);

            if ($response->getStatusCode() !== 200) {
                return [];
            }

            return $response->getJsonBody()['users'] ?? [];
        } catch (Throwable) {
            return [];
        }
    }

    /**
     * @param string      $email
     * @param string|null $message
     *
     * @return mixed[]|null
     */
    public function createCloudInvite(string $email, ?string $message = NULL): ?array
    {
        if (!$this->isEnabled()) {
            return NULL;
        }

        $url = sprintf('%s/api/public/instance-invite', rtrim($this->cloudUrl, '/'));

        $body = Json::encode(array_filter([
            'email'          => $email,
            'instanceId'     => $this->instanceId,
            'instanceSecret' => $this->instanceSecret,
            'message'        => $message,
        ], static fn(mixed $v): bool => $v !== NULL && $v !== ''));

        $dto = new RequestDto(
            new Uri($url),
            CurlManager::METHOD_POST,
            new ProcessDto(),
        );
        $dto->setBody($body);
        $dto->setHeaders(['Content-Type' => 'application/json']);

        try {
            $response = $this->curlManager->send($dto);

            if ($response->getStatusCode() >= 200 && $response->getStatusCode() < 300) {
                return $response->getJsonBody();
            }

            return NULL;
        } catch (Throwable) {
            return NULL;
        }
    }

    /**
     * @param mixed[] $members
     */
    private function syncMembers(array $members): void
    {
        $url = sprintf('%s/api/public/instance-member-sync', rtrim($this->cloudUrl, '/'));

        $body = Json::encode([
            'instanceId'     => $this->instanceId,
            'instanceSecret' => $this->instanceSecret,
            'members'        => $members,
        ]);

        $dto = new RequestDto(
            new Uri($url),
            CurlManager::METHOD_POST,
            new ProcessDto(),
        );
        $dto->setBody($body);
        $dto->setHeaders(['Content-Type' => 'application/json']);

        try {
            $this->curlManager->send($dto);
        } catch (Throwable) {
            // Fire-and-forget
        }
    }

}
