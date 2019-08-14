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
 */
class MonolithMetricsFields
{

    /**
     * @var int
     *
     * @ODM\Field(type="int", name="fpm_cpu_kernel_time")
     */
    private $kernelTime;

    /**
     * @var int
     *
     * @ODM\Field(type="int", name="fpm_cpu_user_time")
     */
    private $userTime;

    /**
     * @var DateTime
     *
     * @ODM\Field(type="date")
     */
    private $created;

    /**
     * @return int
     */
    public function getKernelTime(): int
    {
        return $this->kernelTime;
    }

    /**
     * @return int
     */
    public function getUserTime(): int
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
