<?php declare(strict_types=1);

namespace CleverCore\Commons\Curl;

/**
 * Class Headers
 *
 * @package CleverCore\Commons\Curl
 */
class Headers
{

    /**
     * @var array
     */
    private $headers = [];

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
     * @return Headers
     */
    public function addHeader(string $key, string $value): self
    {
        $this->headers[$key] = $value;

        return $this;
    }

}