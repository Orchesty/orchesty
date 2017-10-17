<?php declare(strict_types=1);
/**
 * Created by PhpStorm.
 * User: venca
 * Date: 10/4/17
 * Time: 8:16 AM
 */

namespace Hanaboso\PipesFramework\RabbitMq\Impl\Batch;

use InvalidArgumentException;

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
    private $headers = [];

    /**
     * MessageDto constructor.
     *
     * @param int $sequenceId
     */
    public function __construct(int $sequenceId)
    {
        if ($sequenceId < 0) {
            throw new InvalidArgumentException('Sequence ID must be grater or equal to 0.');
        }
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
     * @return array
     */
    public function getHeaders(): array
    {
        return $this->headers;
    }

    /**
     * @param string $key
     * @param string $value
     *
     * @return SuccessMessage
     */
    public function addHeaders(string $key, string $value): SuccessMessage
    {
        $this->headers[$key] = $value;

        return $this;
    }

}