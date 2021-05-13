<?php declare(strict_types=1);

namespace Hanaboso\PipesPhpSdk\RabbitMq\Impl\Batch;

use DateTime;
use Hanaboso\Utils\Date\DateTimeUtils;
use Hanaboso\Utils\System\PipesHeaders;
use InvalidArgumentException;

/**
 * Class SuccessMessage
 *
 * @package Hanaboso\PipesPhpSdk\RabbitMq\Impl\Batch
 */
final class SuccessMessage
{

    /**
     * @var string
     */
    private string $data = '';

    /**
     * @var mixed[]
     */
    private array $headers = [];

    /**
     * SuccessMessage constructor.
     *
     * @param int $sequenceId
     */
    public function __construct(private int $sequenceId)
    {
        if ($sequenceId < 0) {
            throw new InvalidArgumentException('Sequence ID must be grater or equal to 0.');
        }
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

    /**
     * @param string $key
     *
     * @return $this
     */
    public function removeHeader(string $key): SuccessMessage
    {
        if ($this->hasHeader($key)) {
            unset($this->headers[$key]);
        }

        return $this;
    }

    /**
     * @param string        $key
     * @param int           $time
     * @param int           $value
     * @param DateTime|null $lastUpdate
     *
     * @return $this
     */
    public function setLimiter(string $key, int $time, int $value, ?DateTime $lastUpdate = NULL): SuccessMessage
    {
        $this->addHeader(PipesHeaders::createKey(PipesHeaders::LIMIT_KEY), $key);
        $this->addHeader(PipesHeaders::createKey(PipesHeaders::LIMIT_TIME), (string) $time);
        $this->addHeader(PipesHeaders::createKey(PipesHeaders::LIMIT_VALUE), (string) $value);

        if ($lastUpdate) {
            $this->addHeader(
                PipesHeaders::createKey(PipesHeaders::LIMIT_LAST_UPDATE),
                (string) $lastUpdate->getTimestamp(),
            );
        }

        return $this;
    }

    /**
     * @return $this
     */
    public function removeLimiter(): SuccessMessage
    {
        $this->addHeader(PipesHeaders::createKey(PipesHeaders::LIMIT_KEY), 'nolimit|all');
        $this->addHeader(PipesHeaders::createKey(PipesHeaders::LIMIT_TIME), '1');
        $this->addHeader(PipesHeaders::createKey(PipesHeaders::LIMIT_VALUE), '9999999');
        $this->addHeader(
            PipesHeaders::createKey(PipesHeaders::LIMIT_LAST_UPDATE),
            (string) DateTimeUtils::getUtcDateTime()->getTimestamp(),
        );

        return $this;
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
