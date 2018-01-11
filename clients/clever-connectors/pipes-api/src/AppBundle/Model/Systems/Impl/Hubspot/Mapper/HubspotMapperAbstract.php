<?php declare(strict_types=1);

namespace CleverConnectors\AppBundle\Model\Systems\Impl\Hubspot\Mapper;

use CleverConnectors\AppBundle\Document\SystemInstall;
use CleverConnectors\AppBundle\Exceptions\CleverConnectorsException;
use CleverConnectors\AppBundle\Model\CM\SubscriberConnector\SubscriberObject\CMSubscriber;
use CleverConnectors\AppBundle\Repository\SystemInstallRepository;
use Doctrine\Common\Persistence\ObjectRepository;
use Doctrine\ODM\MongoDB\DocumentManager;
use Hanaboso\PipesFramework\Commons\Process\ProcessDto;
use Hanaboso\PipesFramework\CustomNode\CustomNodeInterface;

/**
 * Class HubspotMapperAbstract
 *
 * @package CleverConnectors\AppBundle\Model\Systems\Impl\Hubspot\Mapper
 */
abstract class HubspotMapperAbstract implements CustomNodeInterface
{

    /**
     * @var bool
     */
    protected $includeList = FALSE;

    /**
     * @var SystemInstallRepository|ObjectRepository
     */
    protected $systemInstallRepository;

    /**
     * HubspotSyncContactMapper constructor.
     *
     * @param DocumentManager $dm
     */
    public function __construct(DocumentManager $dm)
    {
        $this->systemInstallRepository = $dm->getRepository(SystemInstall::class);
    }

    /**
     * @param string $key
     * @param array  $data
     *
     * @throws CleverConnectorsException
     */
    protected function continueAfterDataCheck(string $key, array $data): void
    {
        if (!array_key_exists($key, $data)) {
            throw new CleverConnectorsException(
                sprintf('Missing "%s" field in data.', $key),
                CleverConnectorsException::MISSING_DATA
            );
        }
    }

    /**
     * @param ProcessDto $dto
     * @param array      $data
     *
     * @return CMSubscriber
     * @throws CleverConnectorsException
     */
    protected function fillCMSubscriber(ProcessDto $dto, array $data): CMSubscriber
    {
        $this->continueAfterDataCheck('properties', $data);

        $properties = $data['properties'];
        $email      = $this->getEmail($data);

        $obj = new CMSubscriber();
        $obj->setEmail($email);

        if (array_key_exists('firstname', $properties)) {
            $obj->setFirstName($properties['firstname']['value']);
        }

        if (array_key_exists('lastname', $properties)) {
            $obj->setLastName($properties['lastname']['value']);
        }

        if (array_key_exists('vid', $data)) {
            $obj->setForeignId($data['vid']);
        }

        if ($this->includeList) {
            $systemInstall = $this->systemInstallRepository->getSystemInstallFromHeaders($dto->getHeaders());
            $sett          = $systemInstall->getSettings();
            $obj->setLists([$sett[SystemInstall::SELECT_LIST] ?? NULL]);
        }

        return $obj;
    }

    /**
     * @param array $data
     *
     * @return string
     * @throws CleverConnectorsException
     */
    protected function getEmail(array $data): string
    {
        $this->continueAfterDataCheck('identity-profiles', $data);

        $profiles = $data['identity-profiles'];

        // contact may have more profiles (merged) and a profile may have more identities
        foreach ($profiles as $profile) {
            if ($profile['vid'] == $data['vid']) {
                foreach ($profile['identities'] as $identity) {
                    if (strtolower($identity['type']) == 'email') {
                        return $identity['value'];
                    }
                }
            }
        }

        throw new CleverConnectorsException(
            'Could not find "email" under "identity-profiles".',
            CleverConnectorsException::MISSING_DATA
        );
    }

    /**
     * @param string $property
     * @param mixed  $value
     *
     * @return array
     */
    protected function prepareProperty(string $property, $value): array
    {
        return [
            'property' => $property,
            'value'    => $value,
        ];
    }

}