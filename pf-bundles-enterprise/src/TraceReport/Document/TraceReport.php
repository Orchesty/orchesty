<?php declare(strict_types=1);

namespace Hanaboso\PipesFrameworkEnterprise\TraceReport\Document;

use DateTime;
use Doctrine\ODM\MongoDB\Mapping\Annotations as ODM;
use Hanaboso\CommonsBundle\Database\Traits\Document\IdTrait;

/**
 * Class TraceReport
 *
 * @package Hanaboso\PipesFrameworkEnterprise\TraceReport\Document
 */
#[ODM\Document(
    collection: 'TraceReport',
    repositoryClass: 'Hanaboso\PipesFrameworkEnterprise\TraceReport\Repository\TraceReportRepository',
)]
#[ODM\Index(keys: ['userId' => 'asc', 'createdAt' => 'desc'], name: 'IK_trace_report_user_created')]
class TraceReport
{

    use IdTrait;

    public const string ID           = 'id';
    public const string TITLE        = 'title';
    public const string CONTENT_HTML = 'contentHtml';
    public const string MESSAGE_ID   = 'messageId';

    #[ODM\Field(type: 'string')]
    private string $userId;

    #[ODM\Field(type: 'string')]
    private string $title;

    #[ODM\Field(type: 'string')]
    private string $contentHtml;

    #[ODM\Field(type: 'string', nullable: TRUE)]
    private ?string $messageId = NULL;

    #[ODM\Field(type: 'date')]
    private DateTime $createdAt;

    #[ODM\Field(type: 'date')]
    private DateTime $updatedAt;

    public function __construct()
    {
        $now             = new DateTime();
        $this->createdAt = $now;
        $this->updatedAt = $now;
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

    public function getTitle(): string
    {
        return $this->title;
    }

    public function setTitle(string $title): self
    {
        $this->title     = $title;
        $this->updatedAt = new DateTime();

        return $this;
    }

    public function getContentHtml(): string
    {
        return $this->contentHtml;
    }

    public function setContentHtml(string $contentHtml): self
    {
        $this->contentHtml = $contentHtml;
        $this->updatedAt   = new DateTime();

        return $this;
    }

    public function getMessageId(): ?string
    {
        return $this->messageId;
    }

    public function setMessageId(?string $messageId): self
    {
        $this->messageId = $messageId;

        return $this;
    }

    public function getCreatedAt(): DateTime
    {
        return $this->createdAt;
    }

    public function getUpdatedAt(): DateTime
    {
        return $this->updatedAt;
    }

    public function touch(): self
    {
        $this->updatedAt = new DateTime();

        return $this;
    }

    /**
     * @return mixed[]
     */
    public function toArray(): array
    {
        return [
            self::ID           => $this->id,
            self::TITLE        => $this->title,
            self::CONTENT_HTML => $this->contentHtml,
            self::MESSAGE_ID   => $this->messageId,
            'userId'           => $this->userId,
            'createdAt'        => $this->createdAt->format('c'),
            'updatedAt'        => $this->updatedAt->format('c'),
        ];
    }

}
