<?php declare(strict_types=1);

namespace CleverConnectors\AppBundle\Model\Systems\Impl\Facebookaudience\Filter;

use CleverConnectors\AppBundle\Document\SystemInstall;
use CleverConnectors\AppBundle\Model\Systems\Impl\Facebookaudience\FacebookaudienceSystem;
use Hanaboso\PipesFramework\Commons\Process\ProcessDto;

/**
 * Class FacebookaudienceGetSubscribersFilter
 *
 * @package CleverConnectors\AppBundle\Model\Systems\Impl\Facebookaudience\Filter
 */
class FacebookaudienceGetSubscribersFilter extends FacebookaudienceFilterAbstract
{

    /**
     * @param ProcessDto $dto
     *
     * @return ProcessDto
     */
    public function process(ProcessDto $dto): ProcessDto
    {
        $settings = $this->getSettings($dto);

        if ($settings[SystemInstall::DISTRIBUTION_LIST] == FacebookaudienceSystem::ALL) {
            return $dto;
        } else {
            return $this->setHeadersToStop($dto);
        }
    }

}