<?php declare(strict_types=1);

namespace Hanaboso\PipesFramework\Metrics\Document;

use DateTime;
use Doctrine\ODM\MongoDB\Mapping\Annotations as ODM;

/**
 * Class ProcessesMetricsFields
 *
 * @package Hanaboso\PipesFramework\Metrics\Document
 */
#[ODM\EmbeddedDocument]
class ProcessesMetricsFields
{

    public const string CREATED = 'created';
    // TODO unused metrics: fail_count, ok_count

    /**
     * @var bool
     */
    #[ODM\Field(name: 'result', type: 'bool')]
    private bool $success;

    /**
     * @var int
     */
    #[ODM\Field(name: 'duration', type: 'int')]
    private int $duration;

    /**
     * @var DateTime
     */
    #[ODM\Field(type: 'date')]
    private DateTime $created;

    /**
     * @return bool
     */
    public function isSuccess(): bool
    {
        return $this->success;
    }

    /**
     * @return int
     */
    public function getDuration(): int
    {
        return $this->duration;
    }

    /**
     * @return DateTime
     */
    public function getCreated(): DateTime
    {
        return $this->created;
    }

}
