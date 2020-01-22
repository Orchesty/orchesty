<?php declare(strict_types=1);

namespace PipesPhpSdkTests\Unit\Application\Model\Form;

use Hanaboso\PipesPhpSdk\Application\Exception\ApplicationInstallException;
use Hanaboso\PipesPhpSdk\Application\Model\Form\Field;
use PipesPhpSdkTests\KernelTestCaseAbstract;

/**
 * Class FieldTest
 *
 * @package PipesPhpSdkTests\Unit\Application\Model\Form
 */
final class FieldTest extends KernelTestCaseAbstract
{

    /**
     * @covers \Hanaboso\PipesPhpSdk\Application\Model\Form\Field
     * @covers \Hanaboso\PipesPhpSdk\Application\Model\Form\Field::getType
     * @covers \Hanaboso\PipesPhpSdk\Application\Model\Form\Field::setValue
     * @covers \Hanaboso\PipesPhpSdk\Application\Model\Form\Field::getValue
     * @covers \Hanaboso\PipesPhpSdk\Application\Model\Form\Field::getLabel
     * @covers \Hanaboso\PipesPhpSdk\Application\Model\Form\Field::setLabel
     * @covers \Hanaboso\PipesPhpSdk\Application\Model\Form\Field::setDescription
     * @covers \Hanaboso\PipesPhpSdk\Application\Model\Form\Field::setRequired
     * @covers \Hanaboso\PipesPhpSdk\Application\Model\Form\Field::setReadOnly
     * @covers \Hanaboso\PipesPhpSdk\Application\Model\Form\Field::setDisabled
     * @covers \Hanaboso\PipesPhpSdk\Application\Model\Form\Field::setChoices
     * @covers \Hanaboso\PipesPhpSdk\Application\Model\Form\Field::getKey
     * @covers \Hanaboso\PipesPhpSdk\Application\Model\Form\Field::getDescription
     * @covers \Hanaboso\PipesPhpSdk\Application\Model\Form\Field::isReadOnly
     * @covers \Hanaboso\PipesPhpSdk\Application\Model\Form\Field::isDisabled
     * @covers \Hanaboso\PipesPhpSdk\Application\Model\Form\Field::getChoices
     * @covers \Hanaboso\PipesPhpSdk\Application\Model\Form\Field::toArray
     * @covers \Hanaboso\PipesPhpSdk\Application\Model\Form\Field::getTypes
     */
    public function testField(): void
    {
        $field = new Field(Field::SELECT_BOX, 'select', 'Select', 'val1');
        $field
            ->setValue('val2')
            ->setLabel('Select2')
            ->setDescription('This is selectbox.')
            ->setRequired(TRUE)
            ->setReadOnly(FALSE)
            ->setDisabled(FALSE)
            ->setChoices(['val1', 'val2', 'val3', 'val4']);

        self::assertEquals('selectbox', $field->getType());
        self::assertEquals('val2', $field->getValue());
        self::assertEquals('select', $field->getKey());
        self::assertEquals('Select2', $field->getLabel());
        self::assertEquals('This is selectbox.', $field->getDescription());
        self::assertEquals(['val1', 'val2', 'val3', 'val4'], $field->getChoices());
        self::assertEquals(9, count($field->toArray()));
        self::assertFalse($field->isReadOnly());
        self::assertFalse($field->isDisabled());
        self::assertTrue($field->isRequired());

        self::expectException(ApplicationInstallException::class);
        new Field('aaa', 'key', 'Key');
    }

}
