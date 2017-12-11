<?php declare(strict_types=1);
/**
 * Created by PhpStorm.
 * User: venca
 * Date: 11.12.17
 * Time: 14:19
 */

namespace CcApi\ApiEntity;

/**
 * Class DataLayout
 *
 * @package CcApi\ApiEntity
 */
class DataLayout
{

    /**
     * @var string
     */
    private $id;

    /**
     * @var string
     */
    private $action;

    /**
     * @var array
     */
    private $fields = [];

    /**
     * @return string
     */
    public function getId(): string
    {
        return $this->id;
    }

    /**
     * @param string $id
     *
     * @return DataLayout
     */
    public function setId(string $id): DataLayout
    {
        $this->id = $id;

        return $this;
    }

    /**
     * @return string
     */
    public function getAction(): string
    {
        return $this->action;
    }

    /**
     * @param string $action
     *
     * @return DataLayout
     */
    public function setAction(string $action): DataLayout
    {
        $this->action = $action;

        return $this;
    }

    /**
     * @return array
     */
    public function getFields(): array
    {
        return $this->fields;
    }

    /**
     * @param DataLayoutField $dataLayoutField
     *
     * @return DataLayout
     */
    public function addField(DataLayoutField $dataLayoutField): DataLayout
    {
        $this->fields[] = $dataLayoutField;

        return $this;
    }

}