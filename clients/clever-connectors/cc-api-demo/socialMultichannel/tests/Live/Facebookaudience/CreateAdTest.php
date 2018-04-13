<?php declare(strict_types=1);

namespace Tests\Live\Facebookaudience;

use Tests\ContainerTestCaseAbstract;

/**
 * Class CreateAdTest
 *
 * @package Tests\Integration
 */
class CreateAdTest extends ContainerTestCaseAbstract
{

    /**
     *
     */
    public function testDemo(): void
    {
        $img = file_get_contents('./box.png');
        $img = base64_encode($img);

        $ch = curl_init('https://graph.facebook.com/v2.12/act_103654000491411/adimages');
        curl_setopt_array($ch, [
            CURLOPT_POST           => 1,
            CURLOPT_RETURNTRANSFER => TRUE,
            CURLOPT_POSTFIELDS     => [
                'bytes'        => $img,
                'access_token' => 'EAAC0qZAlHZCD8BACWoov11lkXZAOcRzmM33Ct97MRrGDA2tvty0zXQ1pUbl0HdNqInijsECadkwRL7CV2ljGq3QLXZASXNKFKp0ROezQ1EsGMFD2tZCyZAnl2ZCwDii0IoO7ZCQXsyoAcvSMCQvIZC7GvPKbz7Kbe29FzNPENoJvQsntj3oI8CJ9VD0xhsh0bZBCOWR4ZBkc4abrBgUUnrTX6BSkfsJnZCpanRAZD',
            ],
        ]);
        $res = curl_exec($ch);
        curl_close($ch);
        $imgHash = json_decode($res, TRUE)['images']['bytes']['hash'];

        $ch = curl_init('https://graph.facebook.com/v2.12/act_103654000491411/adcreatives');
        curl_setopt_array($ch, [
            CURLOPT_POST           => 1,
            CURLOPT_RETURNTRANSFER => TRUE,
            CURLOPT_POSTFIELDS     => [
                'name'              => 'dfgfsdg',
                'access_token'      => 'EAAC0qZAlHZCD8BACWoov11lkXZAOcRzmM33Ct97MRrGDA2tvty0zXQ1pUbl0HdNqInijsECadkwRL7CV2ljGq3QLXZASXNKFKp0ROezQ1EsGMFD2tZCyZAnl2ZCwDii0IoO7ZCQXsyoAcvSMCQvIZC7GvPKbz7Kbe29FzNPENoJvQsntj3oI8CJ9VD0xhsh0bZBCOWR4ZBkc4abrBgUUnrTX6BSkfsJnZCpanRAZD',
                'title'             => 'adf dfgfsdg',
                'body'              => 'Hello, adff dfgfsdg',
                'object_story_spec' => json_encode([
                    'link_data' => [
                        'image_hash' => '925e86d2d195a193cb2446c294960ea0',
                        'link'       => 'http://example.com',
                        'message'    => 'adf dfgfsdg',
                    ],
                    'page_id'   => '448171238945439',
                ]),
                'image_hash'        => $imgHash,
            ],
        ]);
        $res = curl_exec($ch);
        curl_close($ch);

        $adcreative = json_decode($res, TRUE)['id'];

        $ch = curl_init('https://graph.facebook.com/v2.12/act_103654000491411/adsets');
        curl_setopt_array($ch, [
            CURLOPT_POST           => 1,
            CURLOPT_RETURNTRANSFER => TRUE,
            CURLOPT_POSTFIELDS     => [
                'access_token'      => 'EAAC0qZAlHZCD8BACWoov11lkXZAOcRzmM33Ct97MRrGDA2tvty0zXQ1pUbl0HdNqInijsECadkwRL7CV2ljGq3QLXZASXNKFKp0ROezQ1EsGMFD2tZCyZAnl2ZCwDii0IoO7ZCQXsyoAcvSMCQvIZC7GvPKbz7Kbe29FzNPENoJvQsntj3oI8CJ9VD0xhsh0bZBCOWR4ZBkc4abrBgUUnrTX6BSkfsJnZCpanRAZD',
                'name'              => 'someNmae',
                'billing_event'     => 'LINK_CLICKS',
                'targeting'         => '{
                    "custom_audiences":[120330000252930708],
                    "publisher_platforms": ["facebook"]
                }',
                'campaign_id'       => 120330000253356108,
                'bid_amount'        => 1,
                'daily_budget'      => 2500,
                'optimization_goal' => 'LINK_CLICKS',
                'promoted_object'   => '{
                    "page_id":"448171238945439",
                }',
            ],
        ]);
        $res = curl_exec($ch);
        curl_close($ch);

        $adset = json_decode($res, TRUE)['id'];

        $ch = curl_init('https://graph.facebook.com/v2.12/act_103654000491411/ads');
        curl_setopt_array($ch, [
            CURLOPT_POST           => 1,
            CURLOPT_RETURNTRANSFER => TRUE,
            CURLOPT_POSTFIELDS     => [
                'name'         => 'dfgfsdg ad',
                'access_token' => 'EAAC0qZAlHZCD8BACWoov11lkXZAOcRzmM33Ct97MRrGDA2tvty0zXQ1pUbl0HdNqInijsECadkwRL7CV2ljGq3QLXZASXNKFKp0ROezQ1EsGMFD2tZCyZAnl2ZCwDii0IoO7ZCQXsyoAcvSMCQvIZC7GvPKbz7Kbe29FzNPENoJvQsntj3oI8CJ9VD0xhsh0bZBCOWR4ZBkc4abrBgUUnrTX6BSkfsJnZCpanRAZD',
                'creative'     => '{"creative_id": "' . $adcreative . '"}',
                'adset_id'     => $adset,
                'status'       => 'PAUSED',
            ],
        ]);
        $res = curl_exec($ch);
        curl_close($ch);
        // {"id":"120330000253360708"}
    }

    /**
     *
     */
    public function testCampaign(): void
    {
        $ch = curl_init('https://graph.facebook.com/v2.12/act_103654000491411/campaigns');
        curl_setopt_array($ch, [
            CURLOPT_POST           => 1,
            CURLOPT_RETURNTRANSFER => TRUE,
            CURLOPT_POSTFIELDS     => [
                'name'         => 'asndalf',
                'access_token' => 'EAAC0qZAlHZCD8BACWoov11lkXZAOcRzmM33Ct97MRrGDA2tvty0zXQ1pUbl0HdNqInijsECadkwRL7CV2ljGq3QLXZASXNKFKp0ROezQ1EsGMFD2tZCyZAnl2ZCwDii0IoO7ZCQXsyoAcvSMCQvIZC7GvPKbz7Kbe29FzNPENoJvQsntj3oI8CJ9VD0xhsh0bZBCOWR4ZBkc4abrBgUUnrTX6BSkfsJnZCpanRAZD',
                'status'       => 'ACTIVE',
                'objective'    => 'LINK_CLICKS',
            ],
        ]);
        $res = curl_exec($ch);
        curl_close($ch);
        // {"id":"120330000253356108"}
    }

    /**
     *
     */
    public function testImage(): void
    {
        $img = file_get_contents('./box.png');
        $img = base64_encode($img);

        $ch = curl_init('https://graph.facebook.com/v2.11/act_103654000491411/adimages');
        curl_setopt_array($ch, [
            CURLOPT_POST           => 1,
            CURLOPT_RETURNTRANSFER => TRUE,
            CURLOPT_POSTFIELDS     => [
                'bytes'        => $img,
                'access_token' => 'EAAC0qZAlHZCD8BACWoov11lkXZAOcRzmM33Ct97MRrGDA2tvty0zXQ1pUbl0HdNqInijsECadkwRL7CV2ljGq3QLXZASXNKFKp0ROezQ1EsGMFD2tZCyZAnl2ZCwDii0IoO7ZCQXsyoAcvSMCQvIZC7GvPKbz7Kbe29FzNPENoJvQsntj3oI8CJ9VD0xhsh0bZBCOWR4ZBkc4abrBgUUnrTX6BSkfsJnZCpanRAZD',
            ],
        ]);
        $res = curl_exec($ch);
        curl_close($ch);
        // {"images":{"bytes":{"hash":"925e86d2d195a193cb2446c294960ea0","url":"https:\/\/scontent.xx.fbcdn.net\/v\/t45.1600-4\/30119708_120330000253355108_1292331860952612864_n.png?_nc_cat=0&oh=03967266fe63afd91daa4751f84dff3a&oe=5B5F05EC"}}}
    }

    /**
     *
     */
    public function testAudience(): void
    {
        $ch = curl_init('https://graph.facebook.com/v2.12/act_103654000491411/customaudiences');
        curl_setopt_array($ch, [
            CURLOPT_POST           => 1,
            CURLOPT_RETURNTRANSFER => TRUE,
            CURLOPT_POSTFIELDS     => [
                'access_token' => 'EAAC0qZAlHZCD8BACWoov11lkXZAOcRzmM33Ct97MRrGDA2tvty0zXQ1pUbl0HdNqInijsECadkwRL7CV2ljGq3QLXZASXNKFKp0ROezQ1EsGMFD2tZCyZAnl2ZCwDii0IoO7ZCQXsyoAcvSMCQvIZC7GvPKbz7Kbe29FzNPENoJvQsntj3oI8CJ9VD0xhsh0bZBCOWR4ZBkc4abrBgUUnrTX6BSkfsJnZCpanRAZD',
                'name'         => 'someNmae',
                'subtype'      => 'CUSTOM',
            ],
        ]);
        $res = curl_exec($ch);
        curl_close($ch);
        // {"id":"120330000252930708"}
    }

    /**
     *
     */
    public function testAdset(): void
    {
        $ch = curl_init('https://graph.facebook.com/v2.11/act_103654000491411/adsets');
        curl_setopt_array($ch, [
            CURLOPT_POST           => 1,
            CURLOPT_RETURNTRANSFER => TRUE,
            CURLOPT_POSTFIELDS     => [
                'access_token'      => 'EAAC0qZAlHZCD8BACWoov11lkXZAOcRzmM33Ct97MRrGDA2tvty0zXQ1pUbl0HdNqInijsECadkwRL7CV2ljGq3QLXZASXNKFKp0ROezQ1EsGMFD2tZCyZAnl2ZCwDii0IoO7ZCQXsyoAcvSMCQvIZC7GvPKbz7Kbe29FzNPENoJvQsntj3oI8CJ9VD0xhsh0bZBCOWR4ZBkc4abrBgUUnrTX6BSkfsJnZCpanRAZD',
                'name'              => 'someNmae',
                'billing_event'     => 'LINK_CLICKS',
                'targeting'         => '{"custom_audiences":[120330000252930708]}',
                'campaign_id'       => 120330000253356108,
                'bid_amount'        => 1,
                'daily_budget'      => 2500,
                'optimization_goal' => 'LINK_CLICKS',
                'promoted_object'   => '{
                    "page_id":"448171238945439",
                }',
            ],
        ]);
        $res = curl_exec($ch);
        curl_close($ch);
        // {"id":"120330000253356608"}
    }

    /**
     *
     */
    public function testCreative(): void
    {
        $ch = curl_init('https://graph.facebook.com/v2.11/act_103654000491411/adcreatives');
        curl_setopt_array($ch, [
            CURLOPT_POST           => 1,
            CURLOPT_RETURNTRANSFER => TRUE,
            CURLOPT_POSTFIELDS     => [
                'access_token'      => 'EAAC0qZAlHZCD8BACWoov11lkXZAOcRzmM33Ct97MRrGDA2tvty0zXQ1pUbl0HdNqInijsECadkwRL7CV2ljGq3QLXZASXNKFKp0ROezQ1EsGMFD2tZCyZAnl2ZCwDii0IoO7ZCQXsyoAcvSMCQvIZC7GvPKbz7Kbe29FzNPENoJvQsntj3oI8CJ9VD0xhsh0bZBCOWR4ZBkc4abrBgUUnrTX6BSkfsJnZCpanRAZD',
                'name'              => 'someNmae',
                'object_story_spec' => json_encode([
                    'link_data' => [
                        'image_hash' => '925e86d2d195a193cb2446c294960ea0',
                        'link'       => 'http://example.com',
                        'message'    => 'fg dfgfsdg',
                    ],
                    'page_id'   => '448171238945439',
                ]),
            ],
        ]);
        $res = curl_exec($ch);
        curl_close($ch);
        // {"id":"120330000253357208"}
    }

    /**
     *
     */
    public function testCreative2(): void
    {
        $ch = curl_init('https://graph.facebook.com/v2.11/act_103654000491411/adcreatives');
        curl_setopt_array($ch, [
            CURLOPT_POST           => 1,
            CURLOPT_RETURNTRANSFER => TRUE,
            CURLOPT_POSTFIELDS     => [
                'access_token'      => 'EAAC0qZAlHZCD8BACWoov11lkXZAOcRzmM33Ct97MRrGDA2tvty0zXQ1pUbl0HdNqInijsECadkwRL7CV2ljGq3QLXZASXNKFKp0ROezQ1EsGMFD2tZCyZAnl2ZCwDii0IoO7ZCQXsyoAcvSMCQvIZC7GvPKbz7Kbe29FzNPENoJvQsntj3oI8CJ9VD0xhsh0bZBCOWR4ZBkc4abrBgUUnrTX6BSkfsJnZCpanRAZD',
                'name'              => 'someNmae',
                'object_story_spec' => json_encode([
                    'link_data' => [
                        'child_attachments' => [
                            [
                                'description' => 'dfgfsdg desc',
                                'image_hash'  => '925e86d2d195a193cb2446c294960ea0',
                                'link'        => 'http://example.com',
                                'name'        => 'dfgfsdg',
                            ],
                            [
                                'description' => 'dfgfsdg desc 2',
                                'image_hash'  => '925e86d2d195a193cb2446c294960ea0',
                                'link'        => 'http://example2.com',
                                'name'        => 'dfgfsdg 2',
                            ],
                        ],
                        'link'              => 'http://example.com',
                    ],
                    'page_id'   => '448171238945439',
                ]),
            ],
        ]);
        $res = curl_exec($ch);
        curl_close($ch);
        // {"id":"120330000253357008"}
    }

    /**
     *
     */
    public function testAd(): void
    {
        $ch = curl_init('https://graph.facebook.com/v2.11/act_103654000491411/ads');
        curl_setopt_array($ch, [
            CURLOPT_POST           => 1,
            CURLOPT_RETURNTRANSFER => TRUE,
            CURLOPT_POSTFIELDS     => [
                'name'         => 'Facebook is SHIT',
                'status'       => 'PAUSED',
                'adset_id'     => 120330000253356608,
                'access_token' => 'EAAC0qZAlHZCD8BACWoov11lkXZAOcRzmM33Ct97MRrGDA2tvty0zXQ1pUbl0HdNqInijsECadkwRL7CV2ljGq3QLXZASXNKFKp0ROezQ1EsGMFD2tZCyZAnl2ZCwDii0IoO7ZCQXsyoAcvSMCQvIZC7GvPKbz7Kbe29FzNPENoJvQsntj3oI8CJ9VD0xhsh0bZBCOWR4ZBkc4abrBgUUnrTX6BSkfsJnZCpanRAZD',
                'creative'     => '{"creative_id": "120330000253357008"}',
            ],
        ]);
        $res = curl_exec($ch);
        curl_close($ch);
    }

}