<?php declare(strict_types=1);

namespace CleverConnectors\AppBundle\Document;

use CleverConnectors\AppBundle\Document\Traits\IdTrait;
use CleverConnectors\AppBundle\Enum\DataLayoutActionEnum;
use CleverConnectors\AppBundle\Enum\TypeEnum;
use CleverConnectors\AppBundle\Model\DataLayout\LayoutField;
use Doctrine\ODM\MongoDB\Mapping\Annotations as ODM;
use Nette\Utils\Json;

/**
 * Class DataLayout
 *
 * @package CleverConnectors\AppBundle\Document
 *
 * @ODM\Document(repositoryClass="CleverConnectors\AppBundle\Repository\DataLayoutRepository")
 *
 * @ODM\HasLifecycleCallbacks
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
     * @var string
     *
     * @ODM\Field(type="string")
     */
    protected $jsonFields = '[]';

    /**
     * @var LayoutField[]
     */
    protected $fields = [];

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

    /**
     * @param LayoutField[] $fields
     *
     * @return DataLayout
     */
    public function setFields(array $fields): DataLayout
    {
        $this->fields = $fields;

        return $this;
    }

    /**
     * @return array
     */
    public function toArray(): array
    {
        $out    = [];
        $fields = $this->getFields();
        if ($fields) {
            foreach ($fields as $field) {
                $out[] = $field->toArray();
            }
        }

        return [
            'action' => $this->getAction(),
            'fields' => $out,
        ];
    }

    /**
     * @ODM\PreFlush
     */
    public function encode(): void
    {
        $fields = [];
        foreach ($this->fields as $field) {
            $fields[] = $field->toArray();
        }
        $this->jsonFields = Json::encode($fields, TRUE);
    }

    /**
     * @ODM\PostLoad
     */
    public function decode(): void
    {
        foreach (Json::decode($this->jsonFields, TRUE) as $field) {
            $this->addField(new LayoutField($field['key'], new TypeEnum($field['type'])));
        }
    }

}