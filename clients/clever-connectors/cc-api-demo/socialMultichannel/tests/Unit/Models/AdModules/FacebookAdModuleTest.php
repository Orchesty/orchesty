<?php declare(strict_types=1);

namespace Tests\Unit\Models\AdModules;

use Exception;
use Tests\ContainerTestCaseAbstract;
use Tests\PrivateTrait;

/**
 * Class FacebookAdModule
 *
 * @package Tests\Unit\Models\AdModules
 */
final class FacebookAdModuleTest extends ContainerTestCaseAbstract
{

    use PrivateTrait;

    /**
     * @covers FacebookAdModule::validateData()
     *
     * @throws Exception
     */
    public function testValidateData(): void
    {
        $mod = $this->container->getService('social_multichannel.module.fb');

        $data = [
            'name'               => 'Namae',
            'page_id'            => 'adset',
            'audience_id'        => 'audi',
            'distribution_list'  => 'list',
            'campaign_objective' => 'LINK_CLICKS',
            'ad_data'            => [],
            'billing_event'      => 'LINK_CLICKS',
            'bid_amount'         => 1,
            'daily_budget'       => 2500,
        ];

        self::assertEquals([
            'name'               => 'Namae',
            'page_id'            => 'adset',
            'audience_id'        => 'audi',
            'distribution_list'  => 'list',
            'status'             => 'PAUSED',
            'ad_data'            => [],
            'billing_event'      => 'LINK_CLICKS',
            'campaign_objective' => 'LINK_CLICKS',
            'bid_amount'         => 1,
            'daily_budget'       => 2500,
        ], $this->invokeMethod($mod, 'validateData', [$data]));
    }

}