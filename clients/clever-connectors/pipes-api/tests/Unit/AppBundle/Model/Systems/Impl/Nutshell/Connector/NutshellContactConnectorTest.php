<?php declare(strict_types=1);

namespace Tests\Unit\AppBundle\Model\Systems\Impl\Nutshell\Connector;

use CleverConnectors\AppBundle\Model\Systems\Impl\Nutshell\Connector\NutshellContactConnector;
use Hanaboso\PipesFramework\Commons\Process\ProcessDto;
use Nette\Utils\Json;
use Tests\ConnectorTestCaseAbstract;

/**
 * Class NutshellContactConnectorTest
 *
 * @package Tests\Unit\AppBundle\Model\Systems\Impl\Nutshell\Connector
 */
final class NutshellContactConnectorTest extends ConnectorTestCaseAbstract
{

    /**
     *
     */
    public function testProcessEvent(): void
    {
        $result = Json::decode((new NutshellContactConnector())->processEvent(
            (new ProcessDto())->setData($this->getRequest('NutshellWebhookResponse.json'))->setHeaders([])
        )->getData(), TRUE);

        $this->assertEquals([
            'meta'     =>
                [
                ],
            'links'    =>
                [
                    'events.comments'        =>
                        [
                            'href' => 'https://app.nutshell.com/rest/comments?parent_id={events.id}',
                            'type' => 'comments',
                        ],
                    'events.actor'           =>
                        [
                            'href' => 'https://app.nutshell.com/rest/{events.actorType}/{events.actor}',
                            'type' => 'actors',
                        ],
                    'events.payloads'        =>
                        [
                            'href' => 'https://app.nutshell.com/rest/{events.payloadType}/{events.payloads}',
                            'type' => 'payloads',
                        ],
                    'payloads.accounts'      =>
                        [
                            'href' => 'https://app.nutshell.com/rest/accounts/{payloads.accounts}',
                            'type' => 'accounts',
                        ],
                    'payloads.creator'       =>
                        [
                            'href' => 'https://app.nutshell.com/rest/creators/{payloads.creator}',
                            'type' => 'creators',
                        ],
                    'payloads.owner'         =>
                        [
                            'href' => 'https://app.nutshell.com/rest/owners/{payloads.owner}',
                            'type' => 'owners',
                        ],
                    'payloads.territory'     =>
                        [
                            'href' => 'https://app.nutshell.com/rest/territories/{payloads.territory}',
                            'type' => 'territories',
                        ],
                    'payloads.tags'          =>
                        [
                            'href' => 'https://app.nutshell.com/rest/tags/{payloads.tags}',
                            'type' => 'tags',
                        ],
                    'payloads.files'         =>
                        [
                            'href' => 'https://app.nutshell.com/rest/contacts/{contacts.id}/files',
                            'type' => 'files',
                        ],
                    'payloads.relatedFiles'  =>
                        [
                            'href' => 'https://app.nutshell.com/rest/contacts/{contacts.id}/relatedfiles',
                            'type' => 'files',
                        ],
                    'payloads.followup'      =>
                        [
                            'href' => 'https://app.nutshell.com/rest/followups/{payloads.followup}',
                            'type' => 'followups',
                        ],
                    'payloads.recurringTask' =>
                        [
                            'href' => 'https://app.nutshell.com/rest/tasks/{payloads.recurringTask}',
                            'type' => 'tasks',
                        ],
                ],
            'events'   =>
                [
                    0 =>
                        [
                            'id'          => '99-events',
                            'type'        => 'events',
                            'createdTime' => 1508393703,
                            'actorType'   => 'origins',
                            'payloadType' => 'contacts',
                            'action'      => 'create',
                            'changes'     =>
                                [
                                ],
                            'links'       =>
                                [
                                    'comments' =>
                                        [
                                        ],
                                    'actor'    => '39-origins',
                                    'payloads' =>
                                        [
                                            0 => '1-contacts',
                                        ],
                                ],
                        ],
                ],
            'actors'   =>
                [
                    0 =>
                        [
                            'id'           => '39-origins',
                            'type'         => 'origins',
                            'name'         => 'Test API',
                            'modifiedTime' => 1508336214,
                            'avatarUrl'    => 'https://app.nutshell.com/avatar/origins/39/270648/51214e4b244dd753ecd2f056550f415a84103b57e80cbd884bd5218251663108/1508336214',
                            'initials'     => NULL,
                            'htmlUrl'      => NULL,
                        ],
                ],
            'payloads' =>
                [
                    0 =>
                        [
                            'id'          => '1-contacts',
                            'type'        => 'contacts',
                            'name'        => 'User01 User01',
                            'description' => NULL,
                            'emails'      =>
                                [
                                    0 =>
                                        [
                                            'isPrimary' => TRUE,
                                            'name'      => 'email',
                                            'value'     => 'User01@User01.com',
                                        ],
                                ],
                            'addresses'   =>
                                [
                                ],
                            'phones'      =>
                                [
                                ],
                            'urls'        =>
                                [
                                ],
                            'jobTitle'    => '',
                            'href'        => 'https://app.nutshell.com/rest/contacts/1-contacts',
                            'avatarUrl'   => 'https://app.nutshell.com/avatar/contacts/1/270648/a9f0b70a8e60cb3d08c0e366fa3e22f9e36f407d299292bd766c1165ed74c23/1508393703',
                            'initials'    => 'UU',
                            'htmlUrl'     => 'https://app.nutshell.com/person/1-user01-user01',
                            'links'       =>
                                [
                                    'accounts'      =>
                                        [
                                        ],
                                    'creator'       => NULL,
                                    'owner'         => NULL,
                                    'territory'     => NULL,
                                    'tags'          =>
                                        [
                                        ],
                                    'files'         =>
                                        [
                                        ],
                                    'relatedFiles'  =>
                                        [
                                        ],
                                    'followup'      => NULL,
                                    'recurringTask' => NULL,
                                ],
                        ],
                ],
        ], $result);
    }

}