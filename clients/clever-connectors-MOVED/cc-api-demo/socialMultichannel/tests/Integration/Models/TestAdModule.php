<?php declare(strict_types=1);

namespace Tests\Integration\Models;

use CleverCore\SocialMultichannel\Enums\AdTypeEnum;
use CleverCore\SocialMultichannel\Models\AdModules\FacebookAdModule;

/**
 * Class TestAdModule
 *
 * @package Tests\Integration\Models
 */
final class TestAdModule extends FacebookAdModule
{

    protected const TYPE   = AdTypeEnum::FB;
    protected const SYSTEM = 'fc';

}