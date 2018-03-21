<?php declare(strict_types=1);

namespace CleverCore\SocialMultichannel;

use Nette\DI\CompilerExtension;

/**
 * Class SocialMultichannelExtension
 *
 * @package CleverCore\SocialMultichannel
 */
class SocialMultichannelExtension extends CompilerExtension
{

    /**
     *
     */
    public function loadConfiguration(): void
    {
        $builder = $this->getContainerBuilder();
    }

}