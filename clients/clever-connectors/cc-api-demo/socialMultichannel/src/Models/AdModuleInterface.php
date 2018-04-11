<?php declare(strict_types=1);

namespace CleverCore\SocialMultichannel\Models;

use CleverCore\SocialMultichannel\Entities\Ad;

/**
 * Interface AdModuleInterface
 *
 * @package CleverCore\SocialMultichannel\Models
 */
interface AdModuleInterface
{

    /**
     * @param array  $data
     * @param string $userId
     *
     * @return Ad
     */
    public function createAd(array $data, string $userId): Ad;

    /**
     * @param Ad    $ad
     * @param array $data
     *
     * @return Ad
     */
    public function updateAd(Ad $ad, array $data): Ad;

    /**
     * @param Ad $ad
     */
    public function deleteAd(Ad $ad): void;

}
