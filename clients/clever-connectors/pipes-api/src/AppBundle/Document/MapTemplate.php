<?php declare(strict_types=1);

namespace CleverConnectors\AppBundle\Document;

use CleverConnectors\AppBundle\Document\Traits\IdTrait;
use CleverConnectors\AppBundle\Exceptions\CleverConnectorsException;
use CleverConnectors\AppBundle\Model\MapTemplate\MapField;
use CleverConnectors\AppBundle\Model\Systems\Dto\ActionDto;
use Doctrine\ODM\MongoDB\Mapping\Annotations as ODM;
use Hanaboso\CommonsBundle\Exception\EnumException;
use Nette\Utils\Json;

/**
 * Class MapTemplate
 *
 * @package CleverConnectors\AppBundle\Document
 *
 * @ODM\UniqueIndex(keys={"systemInstall"="asc", "action"="asc", "direction"="asc"})
 * @ODM\Document(repositoryClass="CleverConnectors\AppBundle\Repository\MapTemplateRepository")
 *
 * @ODM\HasLifecycleCallbacks
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
     * @var string
     *
     * @ODM\Field(type="string")
     */
    protected $jsonFields = '[]';

    /**
     * @var MapField[]
     */
    protected $fields = [];

    /**
     * @param ActionDto $dto
     *
     * @return MapTemplate
     */
    public function setAction(ActionDto $dto): MapTemplate
    {
        $this->action = $dto->getAction();

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
     * @param ActionDto $dto
     *
     * @return MapTemplate
     * @throws CleverConnectorsException
     */
    public function setDirection(ActionDto $dto): MapTemplate
    {
        $direction = $dto->getDirection();
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
     * @param array $fields
     *
     * @return MapTemplate
     */
    public function setFields(array $fields): MapTemplate
    {
        $this->fields = $fields;

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
            '_id'       => $this->getId(),
            'action'    => $this->getAction(),
            'direction' => $this->getDirection(),
            'fields'    => $out,
        ];
    }

    /**
     * @ODM\PreFlush()
     */
    public function encode(): void
    {
        $fields = [];
        foreach ($this->fields as $field) {
            $fields[] = $field->toArray();
        }
        $this->jsonFields = Json::encode($fields);
    }

    /**
     * @ODM\PostLoad()
     * @throws EnumException
     */
    public function decode(): void
    {
        foreach (Json::decode($this->jsonFields, TRUE) as $field) {
            $mapField = MapField::from($field);
            if ($mapField) {
                $this->addField($mapField);
            }
        }
    }

}