<?php declare(strict_types=1);
/**
 * Created by PhpStorm.
 * User: venca
 * Date: 10/4/17
 * Time: 8:16 AM
 */

namespace Hanaboso\PipesFramework\RabbitMq\Impl\Batch;

/**
 * Class MessageDto
 *
 * @package Hanaboso\PipesFramework\RabbitMq\Impl\Batch
 */
class SuccessMessage
{

    /**
     * @var int
     */
    private $sequenceId;

    /**
     * @var string
     */
    private $data = '';

    /**
     * @var array
     */
    private $setting = [];

    /**
     * MessageDto constructor.
     *
     * @param int $sequenceId
     */
    public function __construct(int $sequenceId)
    {
        $this->sequenceId = $sequenceId;
    }

    /**
     * @return int
     */
    public function getSequenceId(): int
    {
        return $this->sequenceId;
    }

    /**
     * @return string
     */
    public function getData(): string
    {
        return $this->data;
    }

    /**
     * @param string $data
     *
     * @return SuccessMessage
     */
    public function setData(string $data): SuccessMessage
    {
        $this->data = $data;

        return $this;
    }

    /**
     * @return string
     */
    public function getSetting(): string
    {
        return json_encode($this->setting);
    }

    /**
     * @param array $setting
     *
     * @return SuccessMessage
     */
    public function setSetting(array $setting): SuccessMessage
    {
        $this->setting = $setting;

        return $this;
    }

}