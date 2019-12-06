<?php declare(strict_types=1);

namespace Hanaboso\PipesPhpSdk\RabbitMq\Impl\Batch;

use Hanaboso\CommonsBundle\Utils\PipesHeaders;
use InvalidArgumentException;

/**
 * Class SuccessMessage
 *
 * @package Hanaboso\PipesPhpSdk\RabbitMq\Impl\Batch
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
     * @var mixed[]
     */
    private $headers = [];

    /**
     * SuccessMessage constructor.
     *
     * @param int $sequenceId
     */
    public function __construct(int $sequenceId)
    {
        if ($sequenceId < 0) {
            throw new InvalidArgumentException('Sequence ID must be grater or equal to 0.');
        }
        $this->sequenceId = $sequenceId;
        $this->setResultCode(0);
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
     * @return mixed[]
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
    public function addHeader(string $key, string $value): SuccessMessage
    {
        $this->headers[$key] = $value;

        return $this;
    }

    /**
     * @param string $key
     *
     * @return bool
     */
    public function hasHeader(string $key): bool
    {
        return array_key_exists($key, $this->headers);
    }

    /**
     * @param string $key
     *
     * @return string
     */
    public function getHeader(string $key): string
    {
        return $this->headers[$key] ?? '';
    }

    // HELPERS

    /**
     * @param int $code
     *
     * @return SuccessMessage
     */
    public function setResultCode(int $code): SuccessMessage
    {
        return $this->addHeader(PipesHeaders::createKey(PipesHeaders::RESULT_CODE), (string) $code);
    }

    /**
     * @param string $message
     *
     * @return SuccessMessage
     */
    public function setMessage(string $message): SuccessMessage
    {
        return $this->addHeader(PipesHeaders::createKey(PipesHeaders::RESULT_MESSAGE), $message);
    }

}
