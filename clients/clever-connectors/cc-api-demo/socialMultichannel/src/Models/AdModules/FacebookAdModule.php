<?php declare(strict_types=1);

namespace CleverCore\SocialMultichannel\Models\AdModules;

use CleverCore\SocialMultichannel\Enums\AdTypeEnum;
use LogicException;

/**
 * Class FacebookAdModule
 *
 * @package CleverCore\SocialMultichannel\Models\AdModules
 */
class FacebookAdModule extends AdModuleAbstract
{

    protected const TYPE   = AdTypeEnum::FB;
    protected const SYSTEM = 'facebookaudience';

    private const STATUSES = ['ACTIVE', 'PAUSED', 'DELETED', 'ARCHIVED', 'PENDING_REVIEW'];

    /**
     * @param array $data
     *
     * @return array
     */
    protected function trimSettings(array $data): array
    {
        return [
            'status' => $data['status'],
        ];
    }

    /**
     * @param array $data
     *
     * @return array
     */
    protected function validateData(array $data): array
    {
        // Base data
        if (!array_key_exists('page_id', $data)
            || !array_key_exists('ad_data', $data)
            || !array_key_exists('name', $data)
            || !array_key_exists('audience_id', $data)
        ) {
            throw new LogicException(
                'Missing one of required fields [name, page_id, ad_data, audience_id].'
            );
        }

        // Campaign
        if (!array_key_exists('campaign_id', $data)
            && !array_key_exists('campaign_objective', $data)
        ) {
            throw new LogicException(
                'Either [campaign_id] or [campaign_objective] muse be specified.'
            );
        }

        // AdCreative
        foreach ($data['ad_data'] as $key => $item) {
            // Ad data check
            if (!array_key_exists('description', $item)
                || !array_key_exists('image_content', $item)
                || !array_key_exists('title', $item)
                || !array_key_exists('link', $item)
            ) {
                throw new LogicException(
                    'For each ad part are required [title, description, image_content, link].'
                );
            }
        }

        // AdSet
        if (!array_key_exists('adset_id', $data)) {
            if (!array_key_exists('billing_event', $data)
                || !array_key_exists('bid_amount', $data)
                || !array_key_exists('daily_budget', $data)
            ) {
                throw new LogicException(
                    'Either [adset_id] or [billing_event, bid_amount, daily_budget] must be specified.'
                );
            }
        }

        $data['status'] = $this->validStatus($data['status'] ?? '');

        return $data;
    }

    /**
     * @param string $status
     *
     * @return string
     */
    private function validStatus(string $status): string
    {
        $status = strtoupper($status);
        if (!in_array($status, self::STATUSES)) {
            $status = 'PAUSED';
        }

        return $status;
    }

}