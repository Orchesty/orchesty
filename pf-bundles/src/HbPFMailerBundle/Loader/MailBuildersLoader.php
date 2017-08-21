<?php declare(strict_types=1);

/**
 * Created by PhpStorm.
 * User: radek.jirsa
 * Date: 21.8.17
 * Time: 15:07
 */

namespace Hanaboso\PipesFramework\HbPFMailerBundle\Loader;

use Hanaboso\PipesFramework\Mailer\Exception\MailerException;
use Hanaboso\PipesFramework\Mailer\MessageBuilder\MessageBuilderInterface;
use Psr\Container\ContainerInterface;

/**
 * Class MailBuildersLoader
 *
 * @package Hanaboso\PipesFramework\HbPFMailerBundle\Loader
 */
class MailBuildersLoader
{

    private const BUILDER_PREFIX = 'hbpf.mail_builder';

    /**
     * @var ContainerInterface
     */
    private $container;

    /**
     * MailBuildersLoader constructor.
     *
     * @param ContainerInterface $container
     */
    public function __construct(ContainerInterface $container)
    {
        $this->container = $container;
    }

    /**
     * @param string $builder
     *
     * @return MessageBuilderInterface
     * @throws MailerException
     */
    public function getBuilder(string $builder): MessageBuilderInterface
    {

        $name = sprintf('%s.%s', self::BUILDER_PREFIX, $builder);

        if ($this->container->has($name)) {
            /** @var MessageBuilderInterface $authorization */
            $authorization = $this->container->get($name);
        } else {
            throw new MailerException(
                sprintf('MailerBuilder for [%s] was not found.', $builder),
                MailerException::BUILDER_SERVICE_NOT_FOUND
            );
        }

        return $authorization;

    }

}