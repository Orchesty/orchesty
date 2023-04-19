<?php declare(strict_types=1);

namespace PipesPhpSdkTests\Integration\Application;

use Hanaboso\CommonsBundle\Process\ProcessDtoAbstract;
use Hanaboso\CommonsBundle\Transport\Curl\CurlException;
use Hanaboso\CommonsBundle\Transport\Curl\Dto\RequestDto;
use Hanaboso\PipesPhpSdk\Application\Base\ApplicationInterface;
use Hanaboso\PipesPhpSdk\Application\Document\ApplicationInstall;
use Hanaboso\PipesPhpSdk\Application\Model\Form\Field;
use Hanaboso\PipesPhpSdk\Application\Model\Form\Form;
use Hanaboso\PipesPhpSdk\Application\Model\Form\FormStack;
use Hanaboso\PipesPhpSdk\Application\Utils\SynchronousAction;
use Hanaboso\PipesPhpSdk\Authorization\Base\Basic\BasicApplicationAbstract;
use Hanaboso\PipesPhpSdk\Authorization\Base\Basic\BasicApplicationInterface;
use Symfony\Component\HttpFoundation\Request;

/**
 * Class TestNullApplication
 *
 * @package PipesPhpSdkTests\Integration\Application
 */
final class TestNullApplication extends BasicApplicationAbstract
{

    /**
     * @return string
     */
    public function getName(): string
    {
        return 'null-key';
    }

    /**
     * @return string
     */
    public function getPublicName(): string
    {
        return 'Null';
    }

    /**
     * @return string
     */
    public function getDescription(): string
    {
        return 'Application for test purposes';
    }

    /**
     * @SynchronousAction()
     * @return string
     */
    public function testSynchronous(): string
    {
        return 'ok';
    }

    /**
     * @SynchronousAction()
     * @param Request $r
     *
     * @return mixed[]
     */
    public function returnBody(Request $r): array
    {
        return $r->request->all();
    }

    /**
     * @param ProcessDtoAbstract $dto
     * @param ApplicationInstall $applicationInstall
     * @param string             $method
     * @param string|null        $url
     * @param string|null        $data
     *
     * @return RequestDto
     * @throws CurlException
     */
    public function getRequestDto(
        ProcessDtoAbstract $dto,
        ApplicationInstall $applicationInstall,
        string $method,
        ?string $url = NULL,
        ?string $data = NULL,
    ): RequestDto
    {
        $applicationInstall;

        $request = new RequestDto($this->getUri($url), $method, $dto);
        $request->setHeaders(
            [
                'Accept'       => 'application/json',
                'Content-Type' => 'application/vnd.shoptet.v1.0',
            ],
        );
        if (isset($data)) {
            $request->setBody($data);
        }

        return $request;
    }

    /**
     * @return FormStack
     */
    public function getFormStack(): FormStack
    {
        $form = new Form(ApplicationInterface::AUTHORIZATION_FORM,'testPublicName');
        $form
            ->addField(new Field(Field::TEXT, BasicApplicationInterface::USER, 'Username', NULL, TRUE))
            ->addField(new Field(Field::PASSWORD, BasicApplicationInterface::PASSWORD, 'Password', NULL, TRUE))
            ->addField(new Field(Field::TEXT, ApplicationInterface::TOKEN, 'Token', NULL, TRUE));

        $formStack = new FormStack();

        return $formStack->addForm($form);
    }

}
