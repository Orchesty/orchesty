<?php declare(strict_types=1);

namespace Hanaboso\PipesFrameworkEnterprise\AuditLog\Document;

use DateTime;
use Doctrine\ODM\MongoDB\Mapping\Annotations as ODM;
use Hanaboso\CommonsBundle\Database\Traits\Document\IdTrait;

/**
 * Class AuditLog
 *
 * @package Hanaboso\PipesFrameworkEnterprise\AuditLog\Document
 */
#[ODM\Document(
    collection: 'AuditLog',
    repositoryClass: 'Hanaboso\PipesFrameworkEnterprise\AuditLog\Repository\AuditLogRepository',
)]
#[ODM\Index(keys: ['timestamp' => 'desc'], name: 'IK_audit_log_timestamp')]
#[ODM\Index(keys: ['userId' => 'asc', 'timestamp' => 'desc'], name: 'IK_audit_log_user')]
#[ODM\Index(keys: ['resource' => 'asc', 'timestamp' => 'desc'], name: 'IK_audit_log_resource')]
class AuditLog
{

    use IdTrait;

    #[ODM\Field(type: 'date')]
    private DateTime $timestamp;

    #[ODM\Field(type: 'string')]
    private string $userId;

    #[ODM\Field(type: 'string')]
    private string $userEmail;

    #[ODM\Field(type: 'string')]
    private string $action;

    #[ODM\Field(type: 'string')]
    private string $resource;

    #[ODM\Field(type: 'string')]
    private string $resourceId = '';

    #[ODM\Field(type: 'string', nullable: TRUE)]
    private ?string $resourceName = NULL;

    #[ODM\Field(type: 'string')]
    private string $method;

    #[ODM\Field(type: 'string')]
    private string $path;

    #[ODM\Field(type: 'string')]
    private string $ip = '';

    #[ODM\Field(type: 'int')]
    private int $statusCode;

    /** @var mixed[]|null */
    #[ODM\Field(type: 'hash', nullable: TRUE)]
    private ?array $requestBody = NULL;

    #[ODM\Field(type: 'string', nullable: TRUE)]
    private ?string $userAgent = NULL;

    public function __construct()
    {
        $this->timestamp = new DateTime();
    }

    public function getTimestamp(): DateTime
    {
        return $this->timestamp;
    }

    public function setTimestamp(DateTime $timestamp): self
    {
        $this->timestamp = $timestamp;

        return $this;
    }

    public function getUserId(): string
    {
        return $this->userId;
    }

    public function setUserId(string $userId): self
    {
        $this->userId = $userId;

        return $this;
    }

    public function getUserEmail(): string
    {
        return $this->userEmail;
    }

    public function setUserEmail(string $userEmail): self
    {
        $this->userEmail = $userEmail;

        return $this;
    }

    public function getAction(): string
    {
        return $this->action;
    }

    public function setAction(string $action): self
    {
        $this->action = $action;

        return $this;
    }

    public function getResource(): string
    {
        return $this->resource;
    }

    public function setResource(string $resource): self
    {
        $this->resource = $resource;

        return $this;
    }

    public function getResourceId(): string
    {
        return $this->resourceId;
    }

    public function setResourceId(string $resourceId): self
    {
        $this->resourceId = $resourceId;

        return $this;
    }

    public function getResourceName(): ?string
    {
        return $this->resourceName;
    }

    public function setResourceName(?string $resourceName): self
    {
        $this->resourceName = $resourceName;

        return $this;
    }

    public function getMethod(): string
    {
        return $this->method;
    }

    public function setMethod(string $method): self
    {
        $this->method = $method;

        return $this;
    }

    public function getPath(): string
    {
        return $this->path;
    }

    public function setPath(string $path): self
    {
        $this->path = $path;

        return $this;
    }

    public function getIp(): string
    {
        return $this->ip;
    }

    public function setIp(string $ip): self
    {
        $this->ip = $ip;

        return $this;
    }

    public function getStatusCode(): int
    {
        return $this->statusCode;
    }

    public function setStatusCode(int $statusCode): self
    {
        $this->statusCode = $statusCode;

        return $this;
    }

    /**
     * @return mixed[]|null
     */
    public function getRequestBody(): ?array
    {
        return $this->requestBody;
    }

    /**
     * @param mixed[]|null $requestBody
     */
    public function setRequestBody(?array $requestBody): self
    {
        $this->requestBody = $requestBody;

        return $this;
    }

    public function getUserAgent(): ?string
    {
        return $this->userAgent;
    }

    public function setUserAgent(?string $userAgent): self
    {
        $this->userAgent = $userAgent;

        return $this;
    }

    /**
     * @return mixed[]
     */
    public function toArray(): array
    {
        $resourceLabel = ucfirst(str_replace('_', ' ', $this->resource));

        return [
            'id'          => $this->id,
            'timestamp'   => $this->timestamp->format('c'),
            'user'        => $this->userEmail,
            'userId'      => $this->userId,
            'object'      => $this->resourceName
                ? sprintf('%s: %s', $resourceLabel, $this->resourceName)
                : $resourceLabel,
            'objectId'    => $this->resourceId,
            'action'      => $this->action,
            'note'        => sprintf('%s %s', $this->method, $this->path),
            'requestBody' => $this->requestBody,
            'userAgent'   => $this->userAgent,
            'ip'          => $this->ip,
            'statusCode'  => $this->statusCode,
        ];
    }

}
