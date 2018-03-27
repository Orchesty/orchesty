<?php declare(strict_types=1);

namespace Tests\Integration\Models;

use CleverCore\SocialMultichannel\Enums\AdTypeEnum;
use CleverCore\SocialMultichannel\Models\AdModules\AdModuleAbstract;

/**
 * Class TestAdModule
 *
 * @package Tests\Integration\Models
 */
final class TestAdModule extends AdModuleAbstract
{

    protected const TYPE = AdTypeEnum::FB;

}