<?php declare(strict_types=1);

namespace HbPFConnectorsTests;

/**
 * Class MockCurlMethod
 *
 * @package HbPFConnectorsTests
 */
final class MockCurlMethod
{

    /**
     * MockCurlMethod constructor.
     *
     * @param int     $code
     * @param string  $fileName
     * @param mixed[] $headers
     */
    public function __construct(private int $code, private string $fileName, private array $headers = [])
    {
    }

    /**
     * @return int
     */
    public function getCode(): int
    {
        return $this->code;
    }

    /**
     * @return string
     */
    public function getFileName(): string
    {
        return $this->fileName;
    }

    /**
     * @return mixed[]
     */
    public function getHeaders(): array
    {
        return $this->headers;
    }

}
