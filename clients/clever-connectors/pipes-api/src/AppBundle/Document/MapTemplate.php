<?php declare(strict_types=1);

namespace CleverConnectors\AppBundle\Document;

use CleverConnectors\AppBundle\Document\Traits\IdTrait;
use CleverConnectors\AppBundle\Enum\DataLayoutActionEnum;
use CleverConnectors\AppBundle\Exceptions\CleverConnectorsException;
use CleverConnectors\AppBundle\Model\MapTemplate\MapField;
use Doctrine\ODM\MongoDB\Mapping\Annotations as ODM;

/**
 * Class MapTemplate
 *
 * @package CleverConnectors\AppBundle\Document
 *
 * @ODM\Document(repositoryClass="CleverConnectors\AppBundle\Repository\MapTemplateRepository")
 */
class MapTemplate
{

    public const DIRECTION_IN  = 'in';
    public const DIRECTION_OUT = 'out';

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
    protected $direction;

    /**
     * @var string
     *
     * @ODM\Field(type="string")
     */
    protected $systemInstall;

    /**
     * @var MapField[]|array
     *
     * @ODM\Field(type="array")
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
     * @return MapTemplate
     */
    public function setAction(DataLayoutActionEnum $action): MapTemplate
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
     * @param string $direction
     *
     * @return MapTemplate
     * @throws CleverConnectorsException
     */
    public function setDirection(string $direction): MapTemplate
    {
        if (!in_array($direction, [self::DIRECTION_IN, self::DIRECTION_OUT])) {
            throw new CleverConnectorsException(
                sprintf('Invalid direction type "%s".', $direction),
                CleverConnectorsException::INVALID_DIRECTION_TYPE
            );
        }

        $this->direction = $direction;

        return $this;
    }

    /**
     * @return string
     */
    public function getDirection(): string
    {
        return $this->direction;
    }

    /**
     * @param SystemInstall $systemInstall
     *
     * @return MapTemplate
     */
    public function setSystemInstall(SystemInstall $systemInstall): MapTemplate
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
     * @param MapField $field
     *
     * @return MapTemplate
     */
    public function addField(MapField $field): MapTemplate
    {
        $this->fields[] = $field;

        return $this;
    }

    /**
     * @return MapField[]|array
     */
    public function getFields(): array
    {
        return $this->fields;
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
            'action'         => $this->getAction(),
            'direction'      => $this->getDirection(),
            'system_install' => $this->getSystemInstall(),
            'fields'         => $out,
        ];
    }

}