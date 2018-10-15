<?php declare(strict_types=1);

namespace Tests;

use CleverConnectors\AppBundle\Document\SystemInstall;
use CleverConnectors\AppBundle\Repository\SystemInstallRepository;
use Doctrine\Common\Persistence\ObjectManager;
use Hanaboso\CommonsBundle\Crypt\CryptManager;
use Hanaboso\CommonsBundle\Process\ProcessDto;
use Hanaboso\PipesFramework\Configurator\Document\Node;
use Hanaboso\PipesFramework\Configurator\Document\Topology;
use Nette\Utils\Json;
use Nette\Utils\Strings;

/**
 * Class ConnectorTestCaseAbstract
 *
 * @package Tests
 */
abstract class ConnectorTestCaseAbstract extends DatabaseTestCaseAbstract
{

    /**
     * @var SystemInstallRepository|ObjectManager
     */
    protected $systemInstallRepository;

    /**
     *
     */
    protected function setUp(): void
    {
        parent::setUp();
        $this->systemInstallRepository = $this->dm->getRepository(SystemInstall::class);
    }

    /**
     * @param string $file
     *
     * @return string
     */
    protected function getRequest(string $file): string
    {
        $class     = Strings::substring(get_called_class(), strrpos(get_called_class(), '\\') + 1);
        $namespace = str_replace(
            '\\',
            '/',
            Strings::substring(str_replace([$class, 'Tests\\'], '', get_called_class()), 0, -1)
        );
        $path      = sprintf('%s/%s/../data/%s', __DIR__, $namespace, $file);

        if (!file_exists($path)) {
            file_put_contents($path, '{"key": "Example content"}');
            $this->fail('Please change file content');
        }

        return file_get_contents($path);
    }

    /**
     * @param array $settings
     * @param array $data
     * @param array $headers
     * @param bool  $includeSystemInstall
     *
     * @return ProcessDto
     */
    protected function prepareConnectorProcessDto(
        array $settings,
        array $data = [],
        array $headers = [],
        bool $includeSystemInstall = FALSE
    ): ProcessDto
    {
        $random = random_int(0, 99999);

        $topology = (new Topology())->setName(sprintf('Topology %s', $random));
        $this->persistAndFlush($topology);

        $node = (new Node())
            ->setName(sprintf('Node %s', $random))
            ->setTopology($topology->getId());
        $this->persistAndFlush($node);

        $systemInstall = (new SystemInstall())
            ->setUser(sprintf('User %s', $random))
            ->setToken(sprintf('Token %s', $random))
            ->setSystem(sprintf('System %s', $random))
            ->setSettings($settings);
        $this->persistAndFlush($systemInstall);

        return (new ProcessDto())
            ->setData(Json::encode($includeSystemInstall ? array_merge([
                'system_install' => [
                    '_id'               => $systemInstall->getId(),
                    'user'              => $systemInstall->getUser(),
                    'token'             => $systemInstall->getToken(),
                    'system'            => $systemInstall->getSystem(),
                    'encryptedSettings' => CryptManager::encrypt($settings),
                ],
            ], $data) : $data))
            ->setHeaders(array_merge([
                'pf-guid'       => $systemInstall->getUser(),
                'pf-token'      => $systemInstall->getToken(),
                'pf-system-key' => $systemInstall->getSystem(),
            ], $headers));
    }

}