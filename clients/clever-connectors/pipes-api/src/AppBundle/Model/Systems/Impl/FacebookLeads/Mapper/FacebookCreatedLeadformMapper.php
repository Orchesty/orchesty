<?php declare(strict_types=1);

/**
 * Created by PhpStorm.
 * User: michal.bartl
 * Date: 12/7/17
 * Time: 11:03 AM
 */

namespace CleverConnectors\AppBundle\Model\Systems\Impl\FacebookLeads\Mapper;

use CleverConnectors\AppBundle\Document\SystemInstall;
use CleverConnectors\AppBundle\Model\CM\SubscriberConnector\SubscriberObject\CMSubscriber;
use CleverConnectors\AppBundle\Model\Systems\Impl\FacebookLeads\FacebookLeadsSystem;
use CleverConnectors\AppBundle\Repository\SystemInstallRepository;
use CleverConnectors\AppBundle\Utils\HeadersUtils;
use Doctrine\Common\Persistence\ObjectRepository;
use Doctrine\ODM\MongoDB\DocumentManager;
use Hanaboso\PipesFramework\Commons\Process\ProcessDto;
use Hanaboso\PipesFramework\CustomNode\CustomNodeInterface;

/**
 * Class FacebookCreatedLeadformMapper
 *
 * @package CleverConnectors\AppBundle\Model\Systems\Impl\FacebookLeads\Mapper
 */
class FacebookCreatedLeadformMapper implements CustomNodeInterface
{

    /**
     * @var SystemInstallRepository|ObjectRepository
     */
    private $systemInstallRepository;

    /**
     * WisepopsCreatedEmailMapper constructor.
     *
     * @param DocumentManager $dm
     */
    public function __construct(DocumentManager $dm)
    {
        $this->systemInstallRepository = $dm->getRepository(SystemInstall::class);
    }

    /**
     * @param ProcessDto $dto
     *
     * @return ProcessDto
     */
    public function process(ProcessDto $dto): ProcessDto
    {
        $data = json_decode($dto->getData(), TRUE);

        if (empty($data) || !array_key_exists('field_data', $data)) {
            return HeadersUtils::setStopHeaderToDto($dto);
        }

        $subscriber = new CMSubscriber();
        $subscriber->setForeignId($data['id']);

        foreach ($data['field_data'] as $rec) {
            switch ($rec['name']) {
                case 'email':
                    $subscriber->setEmail($rec['values'][0]);
                    break;

                case 'first_name':
                    $subscriber->setFirstName($rec['values'][0]);
                    break;

                case 'last_name':
                    $subscriber->setLastName($rec['values'][0]);
                    break;

                case 'full_name':
                    $fullName = explode(' ', $rec['values'][0]);
                    $subscriber->setFirstName($fullName[0]);
                    $subscriber->setLastName($fullName[1]);
                    break;
            }
        }

        $sys  = $this->systemInstallRepository->getSystemInstallFromHeaders($dto->getHeaders());
        $sett = $sys->getSettings();

        $forms = array_key_exists(SystemInstall::FORMS, $sett) ? $forms = $sett[SystemInstall::FORMS] : [];
        $id    = $data['form_id'];

        foreach ($forms as $form) {
            if ($form[FacebookLeadsSystem::FORM_ID] === $id) {
                $subscriber->setLists([$form[FacebookLeadsSystem::FORM_LIST]]);
                break;
            }
        }

        return $dto->setData(json_encode($subscriber->toArray()));
    }

}