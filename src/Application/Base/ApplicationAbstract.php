<?php declare(strict_types=1);

namespace Hanaboso\PipesFramework\Application\Base;

use Hanaboso\PipesFramework\Application\Document\ApplicationInstall;
use Hanaboso\PipesFramework\Application\Model\Form\Field;

/**
 * Class ApplicationAbstract
 *
 * @package Hanaboso\PipesFramework\Application\Base
 */
abstract class ApplicationAbstract implements ApplicationInterface
{

    public const FORM = 'form';

    /**
     * @return array
     */
    public function toArray(): array
    {
        return [
            'name'               => $this->getName(),
            'authorization_type' => $this->getAuthorizationType(),
            'application_type'   => $this->getApplicationType(),
            'key'                => $this->getKey(),
            'description'        => $this->getDescription(),
        ];
    }

    /**
     * @param ApplicationInstall $applicationInstall
     *
     * @return array
     */
    public function getApplicationForm(ApplicationInstall $applicationInstall): array
    {
        $settings = $applicationInstall->getSettings()[self::FORM] ?? [];
        $form     = $this->getSettingsForm();

        /** @var Field $field */
        foreach ($form->getFields() as &$field) {
            if (array_key_exists($field->getKey(), $settings)) {
                if ($field->getType() === Field::PASSWORD) {
                    $field->setValue(TRUE);
                    continue;
                }

                $field->setValue($settings[$field->getKey()]);
            }
        }

        return $form->toArray();
    }

    /**
     * @param ApplicationInstall $applicationInstall
     * @param array              $settings
     *
     * @return ApplicationInstall
     */
    public function setApplicationSettings(ApplicationInstall $applicationInstall, array $settings): ApplicationInstall
    {
        $preparedSetting = [];
        foreach ($this->getSettingsForm()->getFields() as $field) {
            if (array_key_exists($field->getKey(), $settings)) {
                $preparedSetting[$field->getKey()] = $settings[$field->getKey()];
            }
        }

        return $applicationInstall->setSettings([self::FORM => $preparedSetting]);
    }

}