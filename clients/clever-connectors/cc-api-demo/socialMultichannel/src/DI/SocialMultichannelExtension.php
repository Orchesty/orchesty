<?php declare(strict_types=1);

namespace CleverCore\SocialMultichannel\DI;

use CleverCore\SocialMultichannel\Enums\AdTypeEnum;
use CleverCore\SocialMultichannel\Enums\AudienceSourceEnum;
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

        $setup[0]->arguments[0]['DirectorySourceEnum'] = 'AdTypeEnum';
        $setup[1]->arguments[0]['DirectorySourceEnum'] = AdTypeEnum::class;

        $setup[0]->arguments[0]['DirectorySourceEnum'] = 'AudienceSourceEnum';
        $setup[1]->arguments[0]['DirectorySourceEnum'] = AudienceSourceEnum::class;

        $connection->setSetup($setup);

    }
}