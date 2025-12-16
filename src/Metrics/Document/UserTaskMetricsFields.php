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
     * UserTaskMetricsFields constructor.
     *
     * @param int $messages
     */
    public function __construct(int $messages)
    {
        $this->created  = new DateTime();
        $this->messages = $messages;
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

}
