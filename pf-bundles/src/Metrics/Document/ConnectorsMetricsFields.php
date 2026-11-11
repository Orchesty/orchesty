<?php declare(strict_types=1);

namespace Hanaboso\PipesFramework\Metrics\Document;

use DateTime;
use Doctrine\ODM\MongoDB\Mapping\Annotations as ODM;

/**
 * Class ConnectorsMetricsFields
 *
 * @package Hanaboso\PipesFramework\Metrics\Document
 */
#[ODM\EmbeddedDocument]
class ConnectorsMetricsFields
{

    public const string CREATED = 'created';

    /**
     * @var int
     */
    #[ODM\Field(name: 'sent_request_total_duration', type: 'int')]
    private int $totalDuration;

    /**
     * @var DateTime
     */
    #[ODM\Field(type: 'date')]
    private DateTime $created;

    /**
     * ConnectorsMetricsFields constructor.
     *
     * @param int $totalDuration
     */
    public function __construct(int $totalDuration)
    {
        $this->totalDuration = $totalDuration;
        $this->created       = new DateTime();
    }

    /**
     * @return int
     */
    public function getTotalDuration(): int
    {
        return $this->totalDuration;
    }

    /**
     * @return DateTime
     */
    public function getCreated(): DateTime
    {
        return $this->created;
    }

}
