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
use CleverConnectors\AppBundle\Exceptions\CleverConnectorsException;
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
     * @throws MapperException
     * @throws CleverConnectorsException
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
     * @throws MapperException
     * @throws CleverConnectorsException
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
     * @throws MapperException
     * @throws CleverConnectorsException
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
     * @throws MapperException
     * @throws CleverConnectorsException
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
     * @covers \CleverConnectors\AppBundle\Model\Mapper\UniversalMapper::formatEmail()
     * @throws MapperException
     * @throws CleverConnectorsException
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
            'eml'       => 'aa@dd.com',
        ];

        $template = $this->getMap(
            ['string'],
            ['uri'],
            ['date_from'],
            ['boolean'],
            ['int'],
            ['eml']
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
        self::assertArrayHasKey(TypeEnum::EMAIL, $data);

        self::assertEquals('string', $data[TypeEnum::TEXT]);
        self::assertEquals('http://example.com', $data[TypeEnum::URL]);
        self::assertEquals($date->format(DateTime::ISO8601), $data[TypeEnum::DATE]);
        self::assertEquals(TRUE, $data[TypeEnum::BOOL]);
        self::assertEquals(10101, $data[TypeEnum::NUMBER]);
        self::assertEquals('aa@dd.com', $data[TypeEnum::EMAIL]);
    }

    /**
     * @covers \CleverConnectors\AppBundle\Model\Mapper\UniversalMapper::process()
     * @covers \CleverConnectors\AppBundle\Model\Mapper\UniversalMapper::getDataFromInputFields()
     * @covers \CleverConnectors\AppBundle\Model\Mapper\UniversalMapper::getDataWithFlatKey()
     * @throws MapperException
     * @throws CleverConnectorsException
     */
    public function testProcessItemFlatNonExistErr(): void
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
     * @covers \CleverConnectors\AppBundle\Model\Mapper\UniversalMapper::setAllowedMissingKey()
     * @covers \CleverConnectors\AppBundle\Model\Mapper\UniversalMapper::process()
     * @covers \CleverConnectors\AppBundle\Model\Mapper\UniversalMapper::getDataFromInputFields()
     * @covers \CleverConnectors\AppBundle\Model\Mapper\UniversalMapper::getDataWithFlatKey()
     * @throws MapperException
     * @throws CleverConnectorsException
     */
    public function testProcessItemFlatNonExist(): void
    {
        $data     = ['text' => 'string'];
        $template = $this->getMap(['bad_key']);

        $dto    = $this->getDto(json_encode($data));
        $mapper = new UniversalMapper();

        $res  = $mapper
            ->setAllowedEmptyValues(TRUE)
            ->setAllowedMissingKey(TRUE)
            ->process($template, $dto);
        $data = json_decode($res->getData(), TRUE);
        self::assertTrue(is_array($data));
        self::assertArrayHasKey(TypeEnum::TEXT, $data);
        self::assertArrayHasKey(TypeEnum::URL, $data);
        self::assertArrayHasKey(TypeEnum::DATE, $data);
        self::assertArrayHasKey(TypeEnum::BOOL, $data);
        self::assertArrayHasKey(TypeEnum::NUMBER, $data);
        self::assertArrayHasKey(TypeEnum::EMAIL, $data);

        self::assertEquals('', $data[TypeEnum::TEXT]);
        self::assertEquals('', $data[TypeEnum::URL]);
        self::assertNotEmpty($data[TypeEnum::DATE]);
        self::assertEquals(FALSE, $data[TypeEnum::BOOL]);
        self::assertEquals(0, $data[TypeEnum::NUMBER]);
        self::assertEquals('', $data[TypeEnum::EMAIL]);
    }

    /**
     * @covers \CleverConnectors\AppBundle\Model\Mapper\UniversalMapper::process()
     * @covers \CleverConnectors\AppBundle\Model\Mapper\UniversalMapper::getDataFromInputFields()
     * @covers \CleverConnectors\AppBundle\Model\Mapper\UniversalMapper::getDataWithInnerKey()
     * @covers \CleverConnectors\AppBundle\Model\Mapper\UniversalMapper::formatEmail()
     * @throws MapperException
     * @throws CleverConnectorsException
     */
    public function testProcessItemInner(): void
    {
        $date = new DateTime('2016-02-26T00:00:00+01:00');

        $data = [
            'inner'     => ['string' => 'string'],
            'uri'       => 'http://example.com',
            'date_from' => $date->format(DateTime::W3C),
            'boolean'   => TRUE,
            'int'       => 10101,
            'eml'       => 'not a email',
        ];

        $template = $this->getMap(
            ['inner.string'],
            ['uri'],
            ['date_from'],
            ['boolean'],
            ['int'],
            ['eml']
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
        self::assertArrayHasKey(TypeEnum::EMAIL, $data);

        self::assertEquals('string', $data[TypeEnum::TEXT]);
        self::assertEquals('http://example.com', $data[TypeEnum::URL]);
        self::assertEquals($date->format(DateTime::ISO8601), $data[TypeEnum::DATE]);
        self::assertEquals(TRUE, $data[TypeEnum::BOOL]);
        self::assertEquals(10101, $data[TypeEnum::NUMBER]);
        self::assertEquals('', $data[TypeEnum::EMAIL]);
    }

    /**
     * @covers \CleverConnectors\AppBundle\Model\Mapper\UniversalMapper::process()
     * @covers \CleverConnectors\AppBundle\Model\Mapper\UniversalMapper::getDataFromInputFields()
     * @covers \CleverConnectors\AppBundle\Model\Mapper\UniversalMapper::getDataWithInnerKey()
     * @throws MapperException
     * @throws CleverConnectorsException
     */
    public function testProcessItemInnerNonExistErr(): void
    {
        $data     = ['inner' => ['string' => 'string'],];
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
     * @covers \CleverConnectors\AppBundle\Model\Mapper\UniversalMapper::setAllowedMissingKey()
     * @covers \CleverConnectors\AppBundle\Model\Mapper\UniversalMapper::process()
     * @covers \CleverConnectors\AppBundle\Model\Mapper\UniversalMapper::getDataFromInputFields()
     * @covers \CleverConnectors\AppBundle\Model\Mapper\UniversalMapper::getDataWithInnerKey()
     * @throws MapperException
     * @throws CleverConnectorsException
     */
    public function testProcessItemInnerNonExist(): void
    {
        $data     = ['inner' => ['string' => 'string'],];
        $template = $this->getMap(['inner.bad_key']);

        $dto    = $this->getDto(json_encode($data));
        $mapper = new UniversalMapper();

        $res  = $mapper
            ->setAllowedMissingKey(TRUE)
            ->setAllowedEmptyValues(TRUE)
            ->process($template, $dto);
        $data = json_decode($res->getData(), TRUE);
        self::assertTrue(is_array($data));
        self::assertArrayHasKey(TypeEnum::TEXT, $data);
        self::assertArrayHasKey(TypeEnum::URL, $data);
        self::assertArrayHasKey(TypeEnum::DATE, $data);
        self::assertArrayHasKey(TypeEnum::BOOL, $data);
        self::assertArrayHasKey(TypeEnum::NUMBER, $data);
        self::assertArrayHasKey(TypeEnum::EMAIL, $data);

        self::assertEquals('', $data[TypeEnum::TEXT]);
        self::assertEquals('', $data[TypeEnum::URL]);
        self::assertNotEmpty($data[TypeEnum::DATE]);
        self::assertEquals(FALSE, $data[TypeEnum::BOOL]);
        self::assertEquals(0, $data[TypeEnum::NUMBER]);
        self::assertEquals('', $data[TypeEnum::EMAIL]);
    }

    /**
     * @covers \CleverConnectors\AppBundle\Model\Mapper\UniversalMapper::process()
     * @covers \CleverConnectors\AppBundle\Model\Mapper\UniversalMapper::getDataFromInputFields()
     * @covers \CleverConnectors\AppBundle\Model\Mapper\UniversalMapper::getDataWithFlatKey()
     * @covers \CleverConnectors\AppBundle\Model\Mapper\UniversalMapper::formatEmail()
     * @covers \CleverConnectors\AppBundle\Model\Mapper\UniversalMapper::walkMapTemplate()
     * @covers \CleverConnectors\AppBundle\Model\Mapper\UniversalMapper::setDataWithInnerKey()
     * @throws MapperException
     * @throws CleverConnectorsException
     */
    public function testProcessInnerOutput(): void
    {
        $date = new DateTime('2016-02-26T00:00:00+01:00');

        $data = [
            'string'    => 'string',
            'uri'       => 'http://example.com',
            'date_from' => $date->format(DateTime::W3C),
            'boolean'   => TRUE,
            'int'       => 10101,
            'eml'       => 'aa@dd.com',
        ];

        $template = $this->getMap(
            ['string'],
            ['uri'],
            ['date_from'],
            ['boolean'],
            ['int'],
            ['eml']
        );

        $mapField = new MapField('inner.inner.key', new TypeEnum(TypeEnum::TEXT));
        $mapField->addItem('string');
        $template->addField($mapField);

        $mapField = new MapField('inner.inner.key2', new TypeEnum(TypeEnum::TEXT));
        $mapField->addItem('string');
        $template->addField($mapField);

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
        self::assertArrayHasKey(TypeEnum::EMAIL, $data);
        self::assertArrayHasKey('inner', $data);
        self::assertArrayHasKey('inner', $data['inner']);
        self::assertArrayHasKey('key', $data['inner']['inner']);
        self::assertArrayHasKey('key2', $data['inner']['inner']);

        self::assertEquals('string', $data[TypeEnum::TEXT]);
        self::assertEquals('http://example.com', $data[TypeEnum::URL]);
        self::assertEquals($date->format(DateTime::ISO8601), $data[TypeEnum::DATE]);
        self::assertEquals(TRUE, $data[TypeEnum::BOOL]);
        self::assertEquals(10101, $data[TypeEnum::NUMBER]);
        self::assertEquals('aa@dd.com', $data[TypeEnum::EMAIL]);
        self::assertEquals('string', $data['inner']['inner']['key']);
        self::assertEquals('string', $data['inner']['inner']['key2']);
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
     * @param array $email
     *
     * @return MapTemplate
     * @throws CleverConnectorsException
     */
    private function getMap($text = [], $url = [], $date = [], $bool = [], $number = [], $email = []): MapTemplate
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

        $emlField = new MapField(TypeEnum::EMAIL, new TypeEnum(TypeEnum::EMAIL));
        $emlField = $this->fillItems($emlField, $email);

        $actionDto = new ActionDto('action', MapTemplate::DIRECTION_IN);
        $map       = new MapTemplate();
        $map
            ->setAction($actionDto)
            ->setDirection($actionDto)
            ->addField($textField)
            ->addField($urlField)
            ->addField($dateField)
            ->addField($boolField)
            ->addField($numField)
            ->addField($emlField);

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