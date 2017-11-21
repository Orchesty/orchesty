<?php declare(strict_types=1);

namespace CleverConnectors\AppBundle\Document;

use CleverConnectors\AppBundle\Document\Traits\IdTrait;
use CleverConnectors\AppBundle\Enum\DataLayoutActionEnum;
use CleverConnectors\AppBundle\Model\DataLayout\LayoutField;
use Doctrine\ODM\MongoDB\Mapping\Annotations as ODM;

/**
 * Class DataLayout
 *
 * @package CleverConnectors\AppBundle\Document
 *
 * @ODM\Document(repositoryClass="CleverConnectors\AppBundle\Repository\DataLayoutRepository")
 */
class DataLayout
{

    use IdTrait;

    /**
     * @var string
     *
     * @ODM\Field(type="string")
     */
    protected $action;

    /**
     * @var string
     *
     * @ODM\Field(type="string")
     */
    protected $systemInstall;

    /**
     * @var LayoutField[]|array
     *
     * @ODM\Field(type="collection")
     */
    protected $fields;

    /**
     * DataLayout constructor.
     */
    public function __construct()
    {
        $this->fields = [];
    }

    /**
     * @param DataLayoutActionEnum $action
     *
     * @return DataLayout
     */
    public function setAction(DataLayoutActionEnum $action): DataLayout
    {
        $this->action = $action->getValue();

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
     * @param SystemInstall $systemInstall
     *
     * @return DataLayout
     */
    public function setSystemInstall(SystemInstall $systemInstall): DataLayout
    {
        $this->systemInstall = $systemInstall->getId();

        return $this;
    }

    /**
     * @return string
     */
    public function getSystemInstall(): string
    {
        return $this->systemInstall;
    }

    /**
     * @param LayoutField $field
     *
     * @return DataLayout
     */
    public function addField(LayoutField $field): DataLayout
    {
        $this->fields[] = $field;

        return $this;
    }

    /**
     * @return LayoutField[]|array
     */
    public function getFields(): array
    {
        return $this->fields;
    }

}