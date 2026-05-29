<?php declare(strict_types=1);

namespace Hanaboso\PipesFramework\Metrics\Document;

use DateTime;
use Doctrine\ODM\MongoDB\Mapping\Annotations as ODM;

/**
 * Class UserTaskMetricsFields
 *
 * @package Hanaboso\PipesFramework\Metrics\Document
 */
#[ODM\EmbeddedDocument]
class UserTaskMetricsFields
{

    /**
     * @var DateTime
     */
    #[ODM\Field]
    private DateTime $created;

    /**
     * @var int
     */
    #[ODM\Field]
    private int $messages;

    /**
     * @var int
     */
    #[ODM\Field]
    private int $incoming;

    /**
     * @var int
     */
    #[ODM\Field]
    private int $outgoing;

    /**
     * UserTaskMetricsFields constructor.
     *
     * @param int $messages
     */
    public function __construct(int $messages)
    {
        $this->created  = new DateTime();
        $this->messages = $messages;
        $this->incoming = 0;
        $this->outgoing = 0;
    }

    /**
     * @return DateTime
     */
    public function getCreated(): DateTime
    {
        return $this->created;
    }

    /**
     * @return int
     */
    public function getMessages(): int
    {
        return $this->messages;
    }

    /**
     * @return int
     */
    public function getIncoming(): int
    {
        return $this->incoming;
    }

    /**
     * @return int
     */
    public function getOutgoing(): int
    {
        return $this->outgoing;
    }

}
