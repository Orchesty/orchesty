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

    /**
     * @var mixed[]|null
     */
    #[ODM\Field(type: 'hash', nullable: TRUE)]
    private ?array $requestBody = NULL;

    #[ODM\Field(type: 'string', nullable: TRUE)]
    private ?string $userAgent = NULL;

    /**
     * AuditLog constructor.
     */
    public function __construct()
    {
        $this->timestamp = new DateTime();
    }

    /**
     * @return DateTime
     */
    public function getTimestamp(): DateTime
    {
        return $this->timestamp;
    }

    /**
     * @param DateTime $timestamp
     *
     * @return self
     */
    public function setTimestamp(DateTime $timestamp): self
    {
        $this->timestamp = $timestamp;

        return $this;
    }

    /**
     * @return string
     */
    public function getUserId(): string
    {
        return $this->userId;
    }

    /**
     * @param string $userId
     *
     * @return self
     */
    public function setUserId(string $userId): self
    {
        $this->userId = $userId;

        return $this;
    }

    /**
     * @return string
     */
    public function getUserEmail(): string
    {
        return $this->userEmail;
    }

    /**
     * @param string $userEmail
     *
     * @return self
     */
    public function setUserEmail(string $userEmail): self
    {
        $this->userEmail = $userEmail;

        return $this;
    }

    /**
     * @return string
     */
    public function getAction(): string
    {
        return $this->action;
    }

    /**
     * @param string $action
     *
     * @return self
     */
    public function setAction(string $action): self
    {
        $this->action = $action;

        return $this;
    }

    /**
     * @return string
     */
    public function getResource(): string
    {
        return $this->resource;
    }

    /**
     * @param string $resource
     *
     * @return self
     */
    public function setResource(string $resource): self
    {
        $this->resource = $resource;

        return $this;
    }

    /**
     * @return string
     */
    public function getResourceId(): string
    {
        return $this->resourceId;
    }

    /**
     * @param string $resourceId
     *
     * @return self
     */
    public function setResourceId(string $resourceId): self
    {
        $this->resourceId = $resourceId;

        return $this;
    }

    /**
     * @return string|null
     */
    public function getResourceName(): ?string
    {
        return $this->resourceName;
    }

    /**
     * @param string|null $resourceName
     *
     * @return self
     */
    public function setResourceName(?string $resourceName): self
    {
        $this->resourceName = $resourceName;

        return $this;
    }

    /**
     * @return string
     */
    public function getMethod(): string
    {
        return $this->method;
    }

    /**
     * @param string $method
     *
     * @return self
     */
    public function setMethod(string $method): self
    {
        $this->method = $method;

        return $this;
    }

    /**
     * @return string
     */
    public function getPath(): string
    {
        return $this->path;
    }

    /**
     * @param string $path
     *
     * @return self
     */
    public function setPath(string $path): self
    {
        $this->path = $path;

        return $this;
    }

    /**
     * @return string
     */
    public function getIp(): string
    {
        return $this->ip;
    }

    /**
     * @param string $ip
     *
     * @return self
     */
    public function setIp(string $ip): self
    {
        $this->ip = $ip;

        return $this;
    }

    /**
     * @return int
     */
    public function getStatusCode(): int
    {
        return $this->statusCode;
    }

    /**
     * @param int $statusCode
     *
     * @return self
     */
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
     *
     * @return self
     */
    public function setRequestBody(?array $requestBody): self
    {
        $this->requestBody = $requestBody;

        return $this;
    }

    /**
     * @return string|null
     */
    public function getUserAgent(): ?string
    {
        return $this->userAgent;
    }

    /**
     * @param string|null $userAgent
     *
     * @return self
     */
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
            'action'      => $this->action,
            'id'          => $this->id,
            'ip'          => $this->ip,
            'note'        => sprintf('%s %s', $this->method, $this->path),
            'object'      => $this->resourceName
                ? sprintf('%s: %s', $resourceLabel, $this->resourceName)
                : $resourceLabel,
            'objectId'    => $this->resourceId,
            'requestBody' => $this->requestBody,
            'statusCode'  => $this->statusCode,
            'timestamp'   => $this->timestamp->format('c'),
            'user'        => $this->userEmail,
            'userAgent'   => $this->userAgent,
            'userId'      => $this->userId,
        ];
    }

}
