<?php declare(strict_types=1);
/**
 * Created by PhpStorm.
 * User: venca
 * Date: 6.10.17
 * Time: 9:47
 */

namespace CcApi\Curl;

/**
 * Class Query
 *
 * @package CcApi\Curl
 */
class Query
{

    /**
     * @var array
     */
    private $query = [];

    /**
     * @return array
     */
    public function getQuery(): array
    {
        return $this->query;
    }

    /**
     * @return string
     */
    public function getQueryAsString(): string
    {
        return http_build_query($this->query);
    }

    /**
     * @param string $key
     * @param string $value
     *
     * @return Query
     */
    public function addQuery(string $key, string $value): self
    {
        $this->query[$key] = $value;

        return $this;
    }

}