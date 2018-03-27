<?php declare(strict_types=1);

namespace CleverCore\SocialMultichannel\DI;

use CleverCore\SocialMultichannel\Enums\AdTypeEnum;
use CleverCore\SocialMultichannel\Enums\AudienceSourceEnum;
use CleverCore\SocialMultichannel\Models\AdFacade;
use CleverCore\SocialMultichannel\Models\AdModuleLoader;
use Doctrine\ORM\Mapping\Driver\AnnotationDriver;
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

        $builder->addDefinition($this->prefix('ad.module.loader'))
            ->setFactory(AdModuleLoader::class);

        $builder->addDefinition($this->prefix('ad.facade'))
            ->setFactory(AdFacade::class);
    }

    /**
     *
     */
    public function beforeCompile(): void
    {
        $builder = $this->getContainerBuilder();

        $builder
            ->getDefinition('doctrine.default.metadataDriver')
            ->addSetup('addDriver', [
                $builder
                    ->addDefinition($this->prefix('entities'))
                    ->setFactory(AnnotationDriver::class)
                    ->setArguments([
                        $builder->getDefinitionByType('Doctrine\Common\Annotations\Reader'),
                        [__DIR__ . '/../Entities'],
                    ]),
                mb_substr(__NAMESPACE__, 0, -3),
            ]);

        $connection = $builder->getDefinition('doctrine.default.connection');
        $setup      = $connection->getSetup();

        $setup[0]->arguments[0]['AdTypeEnum'] = 'AdTypeEnum';
        $setup[1]->arguments[0]['AdTypeEnum'] = AdTypeEnum::class;

        $setup[0]->arguments[0]['AudienceSourceEnum'] = 'AudienceSourceEnum';
        $setup[1]->arguments[0]['AudienceSourceEnum'] = AudienceSourceEnum::class;

        $connection->setSetup($setup);

    }

}