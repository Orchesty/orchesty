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

    /**
     * TraceReport constructor.
     */
    public function __construct()
    {
        $now             = new DateTime();
        $this->createdAt = $now;
        $this->updatedAt = $now;
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
    public function getTitle(): string
    {
        return $this->title;
    }

    /**
     * @param string $title
     *
     * @return self
     */
    public function setTitle(string $title): self
    {
        $this->title     = $title;
        $this->updatedAt = new DateTime();

        return $this;
    }

    /**
     * @return string
     */
    public function getContentHtml(): string
    {
        return $this->contentHtml;
    }

    /**
     * @param string $contentHtml
     *
     * @return self
     */
    public function setContentHtml(string $contentHtml): self
    {
        $this->contentHtml = $contentHtml;
        $this->updatedAt   = new DateTime();

        return $this;
    }

    /**
     * @return string|null
     */
    public function getMessageId(): ?string
    {
        return $this->messageId;
    }

    /**
     * @param string|null $messageId
     *
     * @return self
     */
    public function setMessageId(?string $messageId): self
    {
        $this->messageId = $messageId;

        return $this;
    }

    /**
     * @return DateTime
     */
    public function getCreatedAt(): DateTime
    {
        return $this->createdAt;
    }

    /**
     * @return DateTime
     */
    public function getUpdatedAt(): DateTime
    {
        return $this->updatedAt;
    }

    /**
     * @return self
     */
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
            'createdAt'        => $this->createdAt->format('c'),
            'updatedAt'        => $this->updatedAt->format('c'),
            'userId'           => $this->userId,
            self::CONTENT_HTML => $this->contentHtml,
            self::ID           => $this->id,
            self::MESSAGE_ID   => $this->messageId,
            self::TITLE        => $this->title,
        ];
    }

}
