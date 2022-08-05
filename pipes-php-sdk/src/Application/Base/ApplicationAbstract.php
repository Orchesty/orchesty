<?php declare(strict_types=1);

namespace Hanaboso\PipesPhpSdk\Application\Base;

use Exception;
use GuzzleHttp\Psr7\Uri;
use Hanaboso\CommonsBundle\Enum\ApplicationTypeEnum;
use Hanaboso\PipesPhpSdk\Application\Document\ApplicationInstall;
use Hanaboso\PipesPhpSdk\Application\Model\Form\Field;
use Hanaboso\Utils\File\File;

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
        foreach ($this->getFormStack()->getForms() as $form){
            foreach ($form->getFields() as $field) {
                if (array_key_exists($form->getKey(), $settings) &&
                    array_key_exists($field->getKey(), $settings[$form->getKey()])) {
                    $currentForm = $preparedSetting[$form->getKey()] ?? NULL;
                    if ($currentForm) {
                        $preparedSetting[$form->getKey()][$field->getKey()] =
                            $settings[$form->getKey()][$field->getKey()];
                    }
                    else {
                        $preparedSetting[$form->getKey()] = [
                            $field->getKey() => $settings[$form->getKey()][$field->getKey()]];
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
        ];
    }

}
