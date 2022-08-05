<?php declare(strict_types=1);

namespace PipesPhpSdkTests\Unit\Application\Model\Form;

use Exception;
use Hanaboso\PipesPhpSdk\Application\Base\ApplicationInterface;
use Hanaboso\PipesPhpSdk\Application\Model\Form\Field;
use Hanaboso\PipesPhpSdk\Application\Model\Form\Form;
use PipesPhpSdkTests\KernelTestCaseAbstract;

/**
 * Class FormTest
 *
 * @package PipesPhpSdkTests\Unit\Application\Model\Form
 */
final class FormTest extends KernelTestCaseAbstract
{

    /**
     * @covers \Hanaboso\PipesPhpSdk\Application\Model\Form\Form::addField
     * @covers \Hanaboso\PipesPhpSdk\Application\Model\Form\Form::toArray
     * @covers \Hanaboso\PipesPhpSdk\Application\Model\Form\Form::getFields
     *
     * @throws Exception
     */
    public function testForm(): void
    {
        $field1 = new Field(Field::TEXT, 'username', 'Username');
        $field2 = new Field(Field::TEXT, 'passwd', 'Password');
        $form   = (new Form('testKey', 'testPublicName'))->addField($field1)->addField($field2);

        self::assertEquals(2, count($form->toArray()[ApplicationInterface::FIELDS]));
        self::assertEquals(2, count($form->getFields()));
    }

}
