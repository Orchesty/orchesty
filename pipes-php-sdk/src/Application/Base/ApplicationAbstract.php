<?php declare(strict_types=1);

namespace Hanaboso\PipesPhpSdk\Application\Base;

use Exception;
use GuzzleHttp\Psr7\Uri;
use Hanaboso\CommonsBundle\Enum\ApplicationTypeEnum;
use Hanaboso\PipesPhpSdk\Application\Document\ApplicationInstall;
use Hanaboso\PipesPhpSdk\Application\Exception\ApplicationInstallException;
use Hanaboso\PipesPhpSdk\Application\Model\CustomAction\CustomAction;
use Hanaboso\PipesPhpSdk\Application\Model\Form\Field;
use Hanaboso\PipesPhpSdk\Application\Model\Form\Form;
use Hanaboso\PipesPhpSdk\Application\Model\Form\FormStack;
use Hanaboso\Utils\File\File;
use Symfony\Component\HttpFoundation\Request;

/**
 * Class ApplicationAbstract
 *
 * @package Hanaboso\PipesPhpSdk\Application\Base
 */
abstract class ApplicationAbstract implements ApplicationInterface
{

    /**
     * @var string
     */
    protected $logoFilename = 'logo.svg';

    /**
     * @var string
     */
    protected $infoFilename = '';

    /**
     * @var bool
     */
    protected $isInstallable = TRUE;

    /**
     * @return string|null
     */
    public function getLogo(): ?string
    {
        try {
            if (file_exists($this->logoFilename)) {
                return sprintf(
                    'data:%s;base64, %s',
                    mime_content_type($this->logoFilename),
                    base64_encode(File::getContent($this->logoFilename)),
                );
            }
        } catch (Exception) {

        }

        return NULL;
    }

    /**
     * @return string
     */
    public function getInfo(): string
    {
        try {
            if ($this->infoFilename && file_exists($this->infoFilename)) {
                return File::getContent($this->infoFilename);
            }
        } catch (Exception) {

        }

        return '';
    }

    /**
     * @return string
     */
    public function getApplicationType(): string
    {
        return ApplicationTypeEnum::CRON;
    }

    /**
     * @param ApplicationInstall $applicationInstall
     *
     * @return mixed[]
     */
    public function getApplicationForms(ApplicationInstall $applicationInstall): array
    {
        $settings  = $applicationInstall->getSettings();
        $formStack = $this->getFormStack();
        foreach ($formStack->getForms() as $form) {
            foreach ($form->getFields() as $field) {
                if (array_key_exists($form->getKey(), $settings) &&
                    array_key_exists($field->getKey(), $settings[$form->getKey()])) {
                    if ($field->getType() === Field::PASSWORD) {
                        $field->setValue(TRUE);

                        continue;
                    }

                    $field->setValue($settings[$form->getKey()][$field->getKey()]);
                }
            }
        }

        try {
            self::customFormReplace($formStack, $applicationInstall);
            self::autoInjectLimitForm($formStack, $applicationInstall);
        } catch (Exception) {
            //
        }

        return $formStack->toArray();
    }

    /**
     * @param ApplicationInstall $applicationInstall
     * @param mixed[]            $settings
     *
     * @return ApplicationInstall
     */
    public function saveApplicationForms(ApplicationInstall $applicationInstall, array $settings): ApplicationInstall
    {
        $preparedSetting = [];
        foreach ($this->getFormStack()->getForms() as $form) {
            foreach ($form->getFields() as $field) {
                if (array_key_exists($form->getKey(), $settings) &&
                    array_key_exists($field->getKey(), $settings[$form->getKey()])) {
                    $currentForm = $preparedSetting[$form->getKey()] ?? NULL;
                    if ($currentForm) {
                        $preparedSetting[$form->getKey()][$field->getKey()] =
                            $settings[$form->getKey()][$field->getKey()];
                    } else {
                        $preparedSetting[$form->getKey()] = [
                            $field->getKey() => $settings[$form->getKey()][$field->getKey()],
                        ];
                    }

                }
            }
        }

        if (count($preparedSetting) > 0) {
            $applicationInstall->addSettings($preparedSetting);
        }

        return $applicationInstall;
    }

    /**
     * @param ApplicationInstall $applicationInstall
     * @param string             $formKey
     * @param string             $fieldKey
     * @param string             $password
     *
     * @return ApplicationInstall
     */
    public function savePassword(
        ApplicationInstall $applicationInstall,
        string $formKey,
        string $fieldKey,
        string $password,
    ): ApplicationInstall
    {
        return $applicationInstall->addSettings([$formKey => [$fieldKey => $password]]);
    }

    /**
     * @param string|null $url
     *
     * @return Uri
     */
    public function getUri(?string $url): Uri
    {
        return new Uri(sprintf('%s', ltrim($url ?? '', '/')));
    }

    /**
     * @param Request $req
     *
     * @return void
     */
    public function afterInstallCallback(Request $req): void
    {
        $req;
        // You can find AppInstall by user & name. E.g.: If you want to call topology
    }

    /**
     * @param Request $req
     *
     * @return void
     */
    public function aterUninstallCallback(Request $req): void
    {
        $req;
        // You can find AppInstall by user & name. E.g.: If you want to call topology
    }

    /**
     * @param Request $req
     *
     * @return void
     */
    public function afterEnableCallback(Request $req): void
    {
        $req;
        // You can find AppInstall by user & name. E.g.: If you want to call topology
    }

    /**
     * @param Request $req
     *
     * @return void
     */
    public function afterDisableCallback(Request $req): void
    {
        $req;
        // You can find AppInstall by user & name. E.g.: If you want to call topology
    }

    /**
     * @return array|CustomAction[]
     */
    public function getCustomActions(): array
    {
        return [];
    }

    /**
     * @return mixed[]
     */
    public function toArray(): array
    {
        return [
            'name'               => $this->getPublicName(),
            'authorization_type' => $this->getAuthorizationType(),
            'application_type'   => $this->getApplicationType(),
            'key'                => $this->getName(),
            'description'        => $this->getDescription(),
            'info'               => $this->getInfo(),
            'logo'               => $this->getLogo(),
            'isInstallable'      => $this->isInstallable,
        ];
    }

    /**
     * @param FormStack          $formStack
     * @param ApplicationInstall $applicationInstall
     *
     * @return void
     */
    protected function customFormReplace(FormStack $formStack, ApplicationInstall $applicationInstall): void
    {
        $formStack;
        $applicationInstall;
    }

    /**
     * @param FormStack          $formStack
     * @param ApplicationInstall $applicationInstall
     *
     * @return void
     * @throws ApplicationInstallException
     */
    protected function autoInjectLimitForm(FormStack $formStack, ApplicationInstall $applicationInstall): void
    {
        $limiterForm = $formStack->getForms()[self::LIMITER_FORM] ?? NULL;

        if (!$limiterForm) {
            $limiterForm = new Form(self::LIMITER_FORM, 'Limiter form');
            $formStack->addForm($limiterForm);
        }

        $useLimit = $applicationInstall->getSettings()[self::LIMITER_FORM][self::USE_LIMIT] ?? NULL;
        $limiterForm->addField(
            new Field(
                Field::CHECKBOX,
                self::USE_LIMIT,
                'Use limit',
                $useLimit,
            ),
        );

        $value = $applicationInstall->getSettings()[self::LIMITER_FORM][self::VALUE] ?? NULL;
        $limiterForm->addField(
            new Field(
                Field::NUMBER,
                self::VALUE,
                'Limit per time',
                $value,
            ),
        );

        $time = $applicationInstall->getSettings()[self::LIMITER_FORM][self::TIME] ?? NULL;
        $limiterForm->addField(
            new Field(
                Field::NUMBER,
                self::TIME,
                'Time in seconds',
                $time,
            ),
        );
    }

}
