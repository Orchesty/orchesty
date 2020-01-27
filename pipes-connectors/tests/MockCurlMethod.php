<?php declare(strict_types=1);

namespace HbPFConnectorsTests;

/**
 * Class MockCurlMethod
 *
 * @package HbPFConnectorsTests
 */
class MockCurlMethod
{

    /**
     * @var int
     */
    private $code;

    /**
     * @var string
     */
    private $fileName;

    /**
     * @var mixed[]
     */
    private $headers = [];

    /**
     * MockCurlMethod constructor.
     *
     * @param int     $code
     * @param string  $fileName
     * @param mixed[] $headers
     */
    public function __construct(int $code, string $fileName, array $headers = [])
    {
        $this->code     = $code;
        $this->fileName = $fileName;
        $this->headers  = $headers;
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
