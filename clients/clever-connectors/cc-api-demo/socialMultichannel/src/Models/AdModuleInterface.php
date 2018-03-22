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

    public function createAd(array $data): Ad;

    public function updateAd(Ad $ad, array $data): Ad;

    public function deleteAd(Ad $ad): void;

}
