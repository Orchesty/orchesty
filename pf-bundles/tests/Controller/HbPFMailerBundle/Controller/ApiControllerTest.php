<?php declare(strict_types=1);

namespace Tests\Controller\HbPFMailerBundle\Controller;

use Hanaboso\PipesFramework\Mailer\Exception\MailerException;
use Hanaboso\PipesFramework\Mailer\MessageBuilder\MessageBuilderException;
use Tests\ControllerTestCaseAbstract;

/**
 * Class ApiControllerTest
 *
 * @package Tests\Controller\HbPFMailerBundle\Controller
 */
class ApiControllerTest extends ControllerTestCaseAbstract
{

    /**
     *
     */
    public function testSend(): void
    {
        $response = $this->sendPost('/api/mailer/generic/send', [
            'from'    => 'email@example.com',
            'to'      => 'email@example.com',
            'subject' => 'Subject',
            'content' => 'Content',
        ]);

        $this->assertEquals(200, $response->status);
        $this->assertEquals('OK', $response->content->status);
    }

    /**
     *
     */
    public function testSendInvalidData(): void
    {
        $response = $this->sendPost('/api/mailer/generic/send', [
            'from'    => '',
            'to'      => '',
            'subject' => '',
            'content' => '',
        ]);

        $this->assertEquals(500, $response->status);
        $this->assertEquals(MessageBuilderException::class, $response->content->type);
        $this->assertEquals(MessageBuilderException::INVALID_DATA, $response->content->error_code);
    }

    /**
     *
     */
    public function testSendNotFoundMailer(): void
    {
        $response = $this->sendPost('/api/mailer/unknown/send', []);

        $this->assertEquals(500, $response->status);
        $this->assertEquals(MailerException::class, $response->content->type);
        $this->assertEquals(MailerException::BUILDER_SERVICE_NOT_FOUND, $response->content->error_code);
    }

    /**
     *
     */
    public function testSendTest(): void
    {
        $response = $this->sendPost('/api/mailer/generic/send/test', [
            'from'    => 'email@example.com',
            'to'      => 'email@example.com',
            'subject' => 'Subject',
            'content' => 'Content',
        ]);

        $this->assertEquals(200, $response->status);
        $this->assertEquals('OK', $response->content->status);
    }

    /**
     *
     */
    public function testSendTestInvalidData(): void
    {
        $response = $this->sendPost('/api/mailer/generic/send', [
            'from'    => '',
            'to'      => '',
            'subject' => '',
            'content' => '',
        ]);

        $this->assertEquals(500, $response->status);
        $this->assertEquals(MessageBuilderException::class, $response->content->type);
        $this->assertEquals(MessageBuilderException::INVALID_DATA, $response->content->error_code);
    }

    /**
     *
     */
    public function testSendTestNotFoundMailer(): void
    {
        $response = $this->sendPost('/api/mailer/unknown/send', []);

        $this->assertEquals(500, $response->status);
        $this->assertEquals(MailerException::class, $response->content->type);
        $this->assertEquals(MailerException::BUILDER_SERVICE_NOT_FOUND, $response->content->error_code);
    }

}