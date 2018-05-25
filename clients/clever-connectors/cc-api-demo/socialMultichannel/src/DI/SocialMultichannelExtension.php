<?php declare(strict_types=1);

namespace CleverCore\SocialMultichannel\DI;

use CleverCore\SocialMultichannel\Enums\AdTypeEnum;
use CleverCore\SocialMultichannel\Enums\AdTypeEnumProxy;
use CleverCore\SocialMultichannel\Enums\AudienceSourceEnumProxy;
use CleverCore\SocialMultichannel\Handlers\FacebookaudienceHandler;
use CleverCore\SocialMultichannel\Listeners\AudienceListener;
use CleverCore\SocialMultichannel\Models\AdFacade;
use CleverCore\SocialMultichannel\Models\AdModuleLoader;
use CleverCore\SocialMultichannel\Models\AdModules\FacebookAdModule;
use CleverCore\SocialMultichannel\Models\AudienceFacade;
use CleverCore\SocialMultichannel\Models\PipesSender;
use CleverCore\SocialMultichannel\Presenters\FacebookaudiencePresenter;
use Doctrine\ORM\Mapping\Driver\AnnotationDriver;
use Nette\DI\CompilerExtension;

/**
 * Class SocialMultichannelExtension
 *
 * @package CleverCore\SocialMultichannel
 */
class SocialMultichannelExtension extends CompilerExtension
{

    public const NAME = 'social_multichannel';

    /**
     *
     */
    public function loadConfiguration(): void
    {
        $builder = $this->getContainerBuilder();

        $builder->addDefinition($this->prefix('ad.module.loader'))
            ->setFactory(AdModuleLoader::class);

        $backend = getenv('BASE_URI') ?? '';
        $builder->addDefinition($this->prefix('pipes.sender'))
            ->setFactory(PipesSender::class)
            ->setArguments([$backend]);

        $builder->addDefinition($this->prefix('ad.facade'))
            ->setFactory(AdFacade::class);

        $builder->addDefinition($this->prefix('audience.facade'))
            ->setFactory(AudienceFacade::class);

        $builder->addDefinition($this->prefix('audience.listener'))
            ->setFactory(AudienceListener::class)
            ->addTag('kdyby.subscriber');

        $modules = [AdTypeEnum::FB => FacebookAdModule::class];
        foreach ($modules as $key => $module) {
            $builder->addDefinition($this->prefix(sprintf('module.%s', $key)))
                ->setFactory($module);
        }

        $builder->addDefinition($this->prefix('handler'))
            ->setFactory(FacebookaudienceHandler::class);

        $builder->addDefinition($this->prefix('presenter'))
            ->setFactory(FacebookaudiencePresenter::class);
    }

    /**
     *
     */
    public function beforeCompile(): void
    {
        $builder = $this->getContainerBuilder();

        $builder
            ->getDefinition('doctrine.app.metadataDriver')
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

        $connection = $builder->getDefinition('doctrine.app.connection');
        $setup      = $connection->getSetup();

        $setup[0]->arguments[0]['AdTypeEnum'] = 'AdTypeEnum';
        $setup[1]->arguments[0]['AdTypeEnum'] = AdTypeEnumProxy::class;

        $setup[0]->arguments[0]['AudienceSourceEnum'] = 'AudienceSourceEnum';
        $setup[1]->arguments[0]['AudienceSourceEnum'] = AudienceSourceEnumProxy::class;

        $connection->setSetup($setup);

    }

}