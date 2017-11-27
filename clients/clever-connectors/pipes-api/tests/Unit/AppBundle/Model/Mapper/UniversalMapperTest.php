<?php declare(strict_types=1);

/**
 * Created by PhpStorm.
 * User: radek.jirsa
 * Date: 23.11.17
 * Time: 15:49
 */

namespace Tests\Unit\AppBundle\Model\Mapper;

use CleverConnectors\AppBundle\Document\MapTemplate;
use CleverConnectors\AppBundle\Enum\TypeEnum;
use CleverConnectors\AppBundle\Model\Mapper\Exception\MapperException;
use CleverConnectors\AppBundle\Model\Mapper\UniversalMapper;
use CleverConnectors\AppBundle\Model\MapTemplate\MapField;
use CleverConnectors\AppBundle\Model\Systems\Dto\ActionDto;
use DateTime;
use Hanaboso\PipesFramework\Commons\Process\ProcessDto;
use PHPUnit\Framework\TestCase;
use Tests\PrivateTrait;

/**
 * Class UniversalMapperTest
 *
 * @package Tests\Unit\AppBundle\Model\Mapper
 */
final class UniversalMapperTest extends TestCase
{

    use PrivateTrait;

    /**
     * @covers \CleverConnectors\AppBundle\Model\Mapper\UniversalMapper::process()
     * @covers \CleverConnectors\AppBundle\Model\Mapper\UniversalMapper::decodeData()
     * @covers \CleverConnectors\AppBundle\Model\Mapper\UniversalMapper::encodeData()
     *
     * @covers \CleverConnectors\AppBundle\Model\Mapper\UniversalMapper::walkMapTemplate()
     * @covers \CleverConnectors\AppBundle\Model\Mapper\UniversalMapper::getDataFromInputFields()
     * @covers \CleverConnectors\AppBundle\Model\Mapper\UniversalMapper::reformatOutputData()
     *
     * @covers \CleverConnectors\AppBundle\Model\Mapper\UniversalMapper::formatText()
     * @covers \CleverConnectors\AppBundle\Model\Mapper\UniversalMapper::formatUrl()
     * @covers \CleverConnectors\AppBundle\Model\Mapper\UniversalMapper::formatDate()
     * @covers \CleverConnectors\AppBundle\Model\Mapper\UniversalMapper::formatBool()
     * @covers \CleverConnectors\AppBundle\Model\Mapper\UniversalMapper::formatNumber()
     *
     * @covers \CleverConnectors\AppBundle\Model\Mapper\UniversalMapper::isEmptyAndNotAllowed()
     * @covers \CleverConnectors\AppBundle\Model\Mapper\UniversalMapper::setAllowedEmptyValues()
     */
    public function testProcessEmptyData(): void
    {
        $template = $this->getMap();
        $dto      = $this->getDto('{}');
        $mapper   = new UniversalMapper();

        $res = $mapper->process($template, $dto);
        self::assertEquals('[]', $res->getData());

        $res  = $mapper
            ->setAllowedEmptyValues(TRUE)
            ->process($template, $dto);
        $data = json_decode($res->getData(), TRUE);
        self::assertTrue(is_array($data));
        self::assertArrayHasKey(TypeEnum::TEXT, $data);
        self::assertArrayHasKey(TypeEnum::URL, $data);
        self::assertArrayHasKey(TypeEnum::DATE, $data);
        self::assertArrayHasKey(TypeEnum::BOOL, $data);
        self::assertArrayHasKey(TypeEnum::NUMBER, $data);
    }

    /**
     * @covers \CleverConnectors\AppBundle\Model\Mapper\UniversalMapper::process()
     * @covers \CleverConnectors\AppBundle\Model\Mapper\UniversalMapper::decodeData()
     */
    public function testProcessParseError(): void
    {
        $template = $this->getMap();
        $dto      = $this->getDto('{');
        $mapper   = new UniversalMapper();

        $this->expectException(MapperException::class);
        $this->expectExceptionCode(MapperException::PARSE_ERROR);
        $mapper->process($template, $dto);
    }

    /**
     * @covers \CleverConnectors\AppBundle\Model\Mapper\UniversalMapper::process()
     * @covers \CleverConnectors\AppBundle\Model\Mapper\UniversalMapper::reformatOutputData()
     */
    public function testProcessFieldTypeError(): void
    {
        $badField = new MapField(TypeEnum::TEXT, new TypeEnum(TypeEnum::TEXT));

        $this->setProperty($badField, 'type', 'abc');

        $template = $this->getMap();
        $template->addField($badField);
        $dto    = $this->getDto('{}');
        $mapper = new UniversalMapper();

        $this->expectException(MapperException::class);
        $this->expectExceptionCode(MapperException::BAD_FIELD_TYPE);
        $mapper
            ->setAllowedEmptyValues(TRUE)
            ->process($template, $dto);
    }

    /**
     * @covers \CleverConnectors\AppBundle\Model\Mapper\UniversalMapper::process()
     * @covers \CleverConnectors\AppBundle\Model\Mapper\UniversalMapper::getDataFromInputFields()
     */
    public function testProcessItemTypeError(): void
    {
        $badField = new MapField(TypeEnum::TEXT, new TypeEnum(TypeEnum::TEXT));

        $this->setProperty($badField, 'items', [['abc']]);

        $template = $this->getMap();
        $template->addField($badField);
        $dto    = $this->getDto('{}');
        $mapper = new UniversalMapper();

        $this->expectException(MapperException::class);
        $this->expectExceptionCode(MapperException::BAD_ITEMS_FORMAT);
        $mapper
            ->setAllowedEmptyValues(TRUE)
            ->process($template, $dto);
    }

    /**
     * @covers \CleverConnectors\AppBundle\Model\Mapper\UniversalMapper::process()
     * @covers \CleverConnectors\AppBundle\Model\Mapper\UniversalMapper::getDataFromInputFields()
     * @covers \CleverConnectors\AppBundle\Model\Mapper\UniversalMapper::getDataWithFlatKey()
     */
    public function testProcessItemFlat(): void
    {
        $date = new DateTime('2016-02-26T00:00:00+01:00');

        $data = [
            'string'    => 'string',
            'uri'       => 'http://example.com',
            'date_from' => $date->format(DateTime::W3C),
            'boolean'   => TRUE,
            'int'       => 10101,
        ];

        $template = $this->getMap(
            ['string'],
            ['uri'],
            ['date_from'],
            ['boolean'],
            ['int']
        );

        $dto    = $this->getDto(json_encode($data));
        $mapper = new UniversalMapper();

        $res  = $mapper
            ->setAllowedEmptyValues(TRUE)
            ->process($template, $dto);
        $data = json_decode($res->getData(), TRUE);
        self::assertTrue(is_array($data));
        self::assertArrayHasKey(TypeEnum::TEXT, $data);
        self::assertArrayHasKey(TypeEnum::URL, $data);
        self::assertArrayHasKey(TypeEnum::DATE, $data);
        self::assertArrayHasKey(TypeEnum::BOOL, $data);
        self::assertArrayHasKey(TypeEnum::NUMBER, $data);

        self::assertEquals('string', $data[TypeEnum::TEXT]);
        self::assertEquals('http://example.com', $data[TypeEnum::URL]);
        self::assertEquals($date->format(DateTime::ISO8601), $data[TypeEnum::DATE]);
        self::assertEquals(TRUE, $data[TypeEnum::BOOL]);
        self::assertEquals(10101, $data[TypeEnum::NUMBER]);
    }

    /**
     * @covers \CleverConnectors\AppBundle\Model\Mapper\UniversalMapper::process()
     * @covers \CleverConnectors\AppBundle\Model\Mapper\UniversalMapper::getDataFromInputFields()
     * @covers \CleverConnectors\AppBundle\Model\Mapper\UniversalMapper::getDataWithFlatKey()
     */
    public function testProcessItemFlatNonExist(): void
    {
        $data     = ['text' => 'string'];
        $template = $this->getMap(['bad_key']);

        $dto    = $this->getDto(json_encode($data));
        $mapper = new UniversalMapper();

        $this->expectException(MapperException::class);
        $this->expectExceptionCode(MapperException::MISSING_KEY);
        $mapper
            ->setAllowedEmptyValues(TRUE)
            ->process($template, $dto);
    }

    /**
     * @covers \CleverConnectors\AppBundle\Model\Mapper\UniversalMapper::process()
     * @covers \CleverConnectors\AppBundle\Model\Mapper\UniversalMapper::getDataFromInputFields()
     * @covers \CleverConnectors\AppBundle\Model\Mapper\UniversalMapper::getDataWithInnerKey()
     */
    public function testProcessItemInner(): void
    {
        $date = new DateTime('2016-02-26T00:00:00+01:00');

        $data = [
            'inner'    => ['string' => 'string'],
            'uri'       => 'http://example.com',
            'date_from' => $date->format(DateTime::W3C),
            'boolean'   => TRUE,
            'int'       => 10101,
        ];

        $template = $this->getMap(
            ['inner.string'],
            ['uri'],
            ['date_from'],
            ['boolean'],
            ['int']
        );

        $dto    = $this->getDto(json_encode($data));
        $mapper = new UniversalMapper();

        $res  = $mapper
            ->setAllowedEmptyValues(TRUE)
            ->process($template, $dto);
        $data = json_decode($res->getData(), TRUE);
        self::assertTrue(is_array($data));
        self::assertArrayHasKey(TypeEnum::TEXT, $data);
        self::assertArrayHasKey(TypeEnum::URL, $data);
        self::assertArrayHasKey(TypeEnum::DATE, $data);
        self::assertArrayHasKey(TypeEnum::BOOL, $data);
        self::assertArrayHasKey(TypeEnum::NUMBER, $data);

        self::assertEquals('string', $data[TypeEnum::TEXT]);
        self::assertEquals('http://example.com', $data[TypeEnum::URL]);
        self::assertEquals($date->format(DateTime::ISO8601), $data[TypeEnum::DATE]);
        self::assertEquals(TRUE, $data[TypeEnum::BOOL]);
        self::assertEquals(10101, $data[TypeEnum::NUMBER]);
    }

    /**
     * @covers \CleverConnectors\AppBundle\Model\Mapper\UniversalMapper::process()
     * @covers \CleverConnectors\AppBundle\Model\Mapper\UniversalMapper::getDataFromInputFields()
     * @covers \CleverConnectors\AppBundle\Model\Mapper\UniversalMapper::getDataWithInnerKey()
     */
    public function testProcessItemInnerNonExist(): void
    {
        $data     = ['inner'    => ['string' => 'string'],];
        $template = $this->getMap(['inner.bad_key']);

        $dto    = $this->getDto(json_encode($data));
        $mapper = new UniversalMapper();

        $this->expectException(MapperException::class);
        $this->expectExceptionCode(MapperException::MISSING_KEY);
        $mapper
            ->setAllowedEmptyValues(TRUE)
            ->process($template, $dto);
    }


    /**
     * ----------------------------------------- HELPERS -------------------------------
     */

    /**
     * @param array $text
     * @param array $url
     * @param array $date
     * @param array $bool
     * @param array $number
     *
     * @return MapTemplate
     */
    private function getMap($text = [], $url = [], $date = [], $bool = [], $number = []): MapTemplate
    {
        $textField = new MapField(TypeEnum::TEXT, new TypeEnum(TypeEnum::TEXT));
        $textField = $this->fillItems($textField, $text);

        $urlField = new MapField(TypeEnum::URL, new TypeEnum(TypeEnum::URL));
        $urlField = $this->fillItems($urlField, $url);

        $dateField = new MapField(TypeEnum::DATE, new TypeEnum(TypeEnum::DATE));
        $dateField = $this->fillItems($dateField, $date);

        $boolField = new MapField(TypeEnum::BOOL, new TypeEnum(TypeEnum::BOOL));
        $boolField = $this->fillItems($boolField, $bool);

        $numField = new MapField(TypeEnum::NUMBER, new TypeEnum(TypeEnum::NUMBER));
        $numField = $this->fillItems($numField, $number);

        $actionDto = new ActionDto('action', MapTemplate::DIRECTION_IN);
        $map       = new MapTemplate();
        $map
            ->setAction($actionDto)
            ->setDirection($actionDto)
            ->addField($textField)
            ->addField($urlField)
            ->addField($dateField)
            ->addField($boolField)
            ->addField($numField);

        return $map;
    }

    /**
     * @param string $data
     *
     * @return ProcessDto
     */
    private function getDto(string $data): ProcessDto
    {
        $dto = new ProcessDto();
        $dto
            ->setHeaders([])
            ->setData($data);

        return $dto;
    }

    /**
     * @param MapField $field
     * @param array    $items
     *
     * @return MapField
     */
    private function fillItems(MapField $field, array $items = []): MapField
    {
        foreach ($items as $item) {
            $field->addItem($item);
        }

        return $field;
    }

}