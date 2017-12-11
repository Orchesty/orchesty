<?php declare(strict_types=1);
/**
 * Created by PhpStorm.
 * User: venca
 * Date: 11.12.17
 * Time: 14:26
 */

namespace CcApi\ApiEntity;

/**
 * Class DataLayoutField
 *
 * @package CcApi\ApiEntity
 */
class DataLayoutField
{

    /**
     * @var string
     */
    private $key;

    /**
     * @var string
     */
    private $type;

    /**
     * @return string
     */
    public function getKey(): string
    {
        return $this->key;
    }

    /**
     * @param string $key
     *
     * @return DataLayoutField
     */
    public function setKey(string $key): DataLayoutField
    {
        $this->key = $key;

        return $this;
    }

    /**
     * @return string
     */
    public function getType(): string
    {
        return $this->type;
    }

    /**
     * @param string $type
     *
     * @return DataLayoutField
     */
    public function setType(string $type): DataLayoutField
    {
        $this->type = $type;

        return $this;
    }

}