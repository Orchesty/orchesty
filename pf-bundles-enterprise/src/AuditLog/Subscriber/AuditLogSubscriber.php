<?php declare(strict_types=1);

namespace Hanaboso\PipesFrameworkEnterprise\AuditLog\Subscriber;

use Doctrine\ODM\MongoDB\DocumentManager;
use Hanaboso\PipesFrameworkEnterprise\AuditLog\Document\AuditLog;
use Hanaboso\PipesFrameworkEnterprise\AuditLog\Enum\AuditActionEnum;
use Hanaboso\UserBundle\Document\User;
use Hanaboso\Utils\String\Json;
use Symfony\Bundle\SecurityBundle\Security;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\HttpKernel\Event\TerminateEvent;
use Symfony\Component\HttpKernel\KernelEvents;
use Throwable;

/**
 * Class AuditLogSubscriber
 *
 * @package Hanaboso\PipesFrameworkEnterprise\AuditLog\Subscriber
 */
final class AuditLogSubscriber implements EventSubscriberInterface
{

    private const array SENSITIVE_KEYS = [
        'password',
        'token',
        'secret',
        'authorization',
        'access_token',
        'refresh_token',
        'currentPassword',
        'newPassword',
    ];

    private const int MAX_BODY_SIZE = 8_192;

    private const array SKIP_PATHS = [
        '/api/user/check_logged',
        '/api/user/whoami',
        '/api/user/me/groups',
        '/api/audit-logs',
        '/api/user/login',
        '/api/user/logout',
    ];

    private const array RESOURCE_PREFIX_MAP = [
        '/api/apiTokens'      => 'api_token',
        '/api/applications'   => 'application',
        '/api/audit/entities' => 'audit_entity',
        '/api/categories'     => 'category',
        '/api/dashboards'     => 'dashboard',
        '/api/group'          => 'group',
        '/api/logs'           => 'logs',
        '/api/metrics'        => 'metrics',
        '/api/nodes'          => 'node',
        '/api/permissions'    => 'permissions',
        '/api/processes'      => 'process',
        '/api/sdks'           => 'sdk',
        '/api/topologies'     => 'topology',
        '/api/user'           => 'user',
        '/api/user-task'      => 'user_task',
    ];

    /**
     * AuditLogSubscriber constructor.
     *
     * @param Security        $security
     * @param DocumentManager $dm
     */
    public function __construct(private readonly Security $security, private readonly DocumentManager $dm)
    {
    }

    /**
     * @param TerminateEvent $event
     */
    public function onKernelTerminate(TerminateEvent $event): void
    {
        try {
            $this->doLog($event);
        } catch (Throwable) {
        }
    }

    /**
     * @return array<string, string>
     */
    public static function getSubscribedEvents(): array
    {
        return [
            KernelEvents::TERMINATE => 'onKernelTerminate',
        ];
    }

    /**
     * @param TerminateEvent $event
     */
    private function doLog(TerminateEvent $event): void
    {
        $request  = $event->getRequest();
        $response = $event->getResponse();
        $method   = $request->getMethod();

        if (in_array($method, ['GET', 'HEAD', 'OPTIONS'], TRUE)) {
            return;
        }

        $statusCode = $response->getStatusCode();
        if ($statusCode < 200 || $statusCode >= 300) {
            return;
        }

        $path = $request->getPathInfo();

        foreach (self::SKIP_PATHS as $skipPath) {
            if (str_starts_with($path, $skipPath)) {
                return;
            }
        }

        $user = $this->security->getUser();
        if (!$user instanceof User) {
            return;
        }

        $action   = $this->resolveAction($method, $path);
        $resource = $this->resolveResource($path);

        $body = $request->request->all();

        $auditLog = new AuditLog();
        $auditLog
            ->setUserId($user->getId())
            ->setUserEmail($user->getEmail())
            ->setAction($action)
            ->setResource($resource)
            ->setResourceId($this->extractResourceId($path))
            ->setResourceName($this->extractResourceName($path, $body))
            ->setMethod($method)
            ->setPath($path)
            ->setIp($request->getClientIp() ?? '')
            ->setStatusCode($statusCode)
            ->setRequestBody($this->sanitizeBody($body))
            ->setUserAgent($request->headers->get('User-Agent'));

        $this->dm->persist($auditLog);
        $this->dm->flush();
    }

    /**
     * @param string $method
     * @param string $path
     *
     * @return string
     */
    private function resolveAction(string $method, string $path): string
    {
        if (preg_match('#/run$#', $path)) {
            return AuditActionEnum::EXECUTED;
        }

        if (preg_match('#/publish$#', $path)) {
            return AuditActionEnum::PUBLISHED;
        }

        return match ($method) {
            'POST'   => AuditActionEnum::CREATED,
            'PUT', 'PATCH' => AuditActionEnum::UPDATED,
            'DELETE' => AuditActionEnum::DELETED,
            default  => AuditActionEnum::UPDATED,
        };
    }

    /**
     * @param string $path
     *
     * @return string
     */
    private function resolveResource(string $path): string
    {
        $bestPrefix   = '';
        $bestResource = 'unknown';

        foreach (self::RESOURCE_PREFIX_MAP as $prefix => $resource) {
            if (str_starts_with($path, $prefix) && strlen($prefix) > strlen($bestPrefix)) {
                $bestPrefix   = $prefix;
                $bestResource = $resource;
            }
        }

        return $bestResource;
    }

    /**
     * @param string $path
     *
     * @return string
     */
    private function extractResourceId(string $path): string
    {
        if (preg_match('#/([a-f0-9]{24})(?:/|$)#', $path, $m)) {
            return $m[1];
        }

        $segments = explode('/', trim($path, '/'));
        $last     = end($segments);

        if ($last !== '' && !str_contains($last, '.')) {
            return $last;
        }

        return '';
    }

    /**
     * @param string  $path
     * @param mixed[] $body
     *
     * @return string|null
     */
    private function extractResourceName(string $path, array $body): ?string
    {
        $path;

        if (isset($body['name']) && is_string($body['name'])) {
            return $body['name'];
        }

        if (isset($body['email']) && is_string($body['email'])) {
            return $body['email'];
        }

        return NULL;
    }

    /**
     * @param mixed[] $body
     *
     * @return mixed[]|null
     */
    private function sanitizeBody(array $body): ?array
    {
        if ($body === []) {
            return NULL;
        }

        $sanitized = $this->redactSensitive($body);

        $encoded = Json::encode($sanitized);
        if (strlen($encoded) > self::MAX_BODY_SIZE) {
            return ['_truncated' => TRUE, '_size' => strlen($encoded)];
        }

        return $sanitized;
    }

    /**
     * @param mixed[] $data
     *
     * @return mixed[]
     */
    private function redactSensitive(array $data): array
    {
        foreach ($data as $key => $value) {
            if (is_string($key) && in_array(strtolower($key), self::SENSITIVE_KEYS, TRUE)) {
                $data[$key] = '***';
            } elseif (is_array($value)) {
                $data[$key] = $this->redactSensitive($value);
            }
        }

        return $data;
    }

}
