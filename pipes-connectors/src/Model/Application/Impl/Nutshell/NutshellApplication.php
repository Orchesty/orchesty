<?php declare(strict_types=1);

namespace Hanaboso\HbPFConnectors\Model\Application\Impl\Nutshell;

use Hanaboso\CommonsBundle\Process\ProcessDtoAbstract;
use Hanaboso\CommonsBundle\Transport\Curl\CurlException;
use Hanaboso\CommonsBundle\Transport\Curl\Dto\RequestDto;
use Hanaboso\PipesPhpSdk\Application\Base\ApplicationInterface;
use Hanaboso\PipesPhpSdk\Application\Document\ApplicationInstall;
use Hanaboso\PipesPhpSdk\Application\Model\Form\Field;
use Hanaboso\PipesPhpSdk\Application\Model\Form\Form;
use Hanaboso\PipesPhpSdk\Application\Model\Form\FormStack;
use Hanaboso\PipesPhpSdk\Authorization\Base\Basic\BasicApplicationAbstract;

/**
 * Class NutshellApplication
 *
 * @package Hanaboso\HbPFConnectors\Model\Application\Impl\Nutshell
 */
final class NutshellApplication extends BasicApplicationAbstract
{

    public const BASE_URL = 'https://app.nutshell.com/api/v1/json';

    /**
     * @return string
     */
    public function getName(): string
    {
        return 'nutshell';
    }

    /**
     * @return string
     */
    public function getPublicName(): string
    {
        return 'Nutshell';
    }

    /**
     * @return string
     */
    public function getDescription(): string
    {
        return 'Nutshell v1';
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
        $request = new RequestDto($this->getUri($url), $method, $dto);
        $request->setHeaders(
            [
                'Content-Type'  => 'application/json',
                'Accept'        => 'application/json',
                'Authorization' => sprintf('Basic %s', $this->getToken($applicationInstall)),
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
        $form = new Form(ApplicationInterface::AUTHORIZATION_FORM, 'Authorization settings');
        $form
            ->addField(new Field(Field::TEXT, BasicApplicationAbstract::USER, 'Username', NULL, TRUE))
            ->addField(new Field(Field::TEXT, BasicApplicationAbstract::PASSWORD, 'API Key', NULL, TRUE));

        $formStack = new FormStack();
        $formStack->addForm($form);

        return $formStack;
    }

    /**
     * @param ApplicationInstall $applicationInstall
     *
     * @return string
     */
    private function getToken(ApplicationInstall $applicationInstall): string
    {
        return base64_encode(
            sprintf(
                '%s:%s',
                $applicationInstall->getSettings(
                )[ApplicationInterface::AUTHORIZATION_FORM][BasicApplicationAbstract::USER],
                $applicationInstall->getSettings(
                )[ApplicationInterface::AUTHORIZATION_FORM][BasicApplicationAbstract::PASSWORD],
            ),
        );
    }

}
