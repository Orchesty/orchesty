<?php declare(strict_types=1);

namespace Hanaboso\PipesFramework\Metrics\Document;

use DateTime;
use Doctrine\ODM\MongoDB\Mapping\Annotations as ODM;

/**
 * Class MonolithMetricsFields
 *
 * @package Hanaboso\PipesFramework\Metrics\Document
 *
 * @ODM\EmbeddedDocument()
 * @ODM\Index(name="createdIndex", keys={"created"="desc"})
 */
class MonolithMetricsFields
{

    /**
     * @var float
     *
     * @ODM\Field(type="float", name="fpm_cpu_kernel_time")
     */
    private float $kernelTime;

    /**
     * @var float
     *
     * @ODM\Field(type="float", name="fpm_cpu_user_time")
     */
    private float $userTime;

    /**
     * @var DateTime
     *
     * @ODM\Field(type="date")
     */
    private DateTime $created;

    /**
     * @return float
     */
    public function getKernelTime(): float
    {
        return $this->kernelTime;
    }

    /**
     * @return float
     */
    public function getUserTime(): float
    {
        return $this->userTime;
    }

    /**
     * @return DateTime
     */
    public function getCreated(): DateTime
    {
        return $this->created;
    }

}
