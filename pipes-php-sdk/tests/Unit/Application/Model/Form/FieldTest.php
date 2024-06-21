<?php declare(strict_types=1);

namespace PipesPhpSdkTests\Unit\Application\Model\Form;

use Hanaboso\PipesPhpSdk\Application\Exception\ApplicationInstallException;
use Hanaboso\PipesPhpSdk\Application\Model\Form\Field;
use PHPUnit\Framework\Attributes\CoversClass;
use PipesPhpSdkTests\KernelTestCaseAbstract;

/**
 * Class FieldTest
 *
 * @package PipesPhpSdkTests\Unit\Application\Model\Form
 */
#[CoversClass(Field::class)]
final class FieldTest extends KernelTestCaseAbstract
{

    /**
     * @return void
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
