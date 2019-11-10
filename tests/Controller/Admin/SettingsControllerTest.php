<?php

declare(strict_types=1);

namespace App\Tests\Controller\Admin;

use App\Entity\Settings;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;
use Symfony\Component\HttpFoundation\File\UploadedFile;
use Symfony\Component\HttpFoundation\Response;

final class SettingsControllerTest extends WebTestCase
{
    private const PHP_AUTH_USER = 'admin';
    private const PHP_AUTH_PW = 'admin';

    public function testAdminEditSettings()
    {
        $client = static::createClient([], [
            'PHP_AUTH_USER' => self::PHP_AUTH_USER,
            'PHP_AUTH_PW' => self::PHP_AUTH_PW,
        ]);

        $title = $client->getContainer()->get('doctrine')
            ->getRepository(Settings::class)->findOneBy(['setting_name' => 'title'])->getSettingValue();

        $crawler = $client->request('GET', '/admin/setting');

        $form = $crawler->selectButton('Save changes')->form([
            'settings[title]' => $title.' - Test title',
            'settings[items_per_page]' => '3',
        ]);

        $client->submit($form);
        $this->assertSame(Response::HTTP_FOUND, $client->getResponse()->getStatusCode());
    }

    public function testPublicSettings()
    {
        $client = static::createClient();

        $crawler = $client->request('GET', '/');

        $this->assertContains('Test title', $crawler->html());
        $this->assertCount(3, $crawler->filter('.property-box-img'));
    }

    public function testChangeBackSettings()
    {
        $client = static::createClient([], [
                'PHP_AUTH_USER' => self::PHP_AUTH_USER,
                'PHP_AUTH_PW' => self::PHP_AUTH_PW,
            ]);

        $title = $client->getContainer()->get('doctrine')
                ->getRepository(Settings::class)->findOneBy(['setting_name' => 'title'])->getSettingValue();

        $crawler = $client->request('GET', '/admin/setting');

        $form = $crawler->selectButton('Save changes')->form([
                'settings[title]' => mb_substr($title, 0, -13),
                'settings[items_per_page]' => '6',
            ]);

        $client->submit($form);
        $this->assertSame(Response::HTTP_FOUND, $client->getResponse()->getStatusCode());
    }

    public function testChangedBackSettings()
    {
        $client = static::createClient();

        $crawler = $client->request('GET', '/');

        $this->assertNotContains('Test title', $crawler->html());
        $this->assertCount(6, $crawler->filter('.property-box-img'));
    }

    public function testUploadHeaderImage()
    {
        $client = static::createClient([], [
            'PHP_AUTH_USER' => self::PHP_AUTH_USER,
            'PHP_AUTH_PW' => self::PHP_AUTH_PW,
        ]);

        $crawler = $client->request('GET', '/admin/setting/header_image');
        $this->assertSelectorTextContains('html', 'Header image');

        $image = new UploadedFile(
            __DIR__.'/../../../public/uploads/images/full/demo/1.jpeg',
            'bg.jpg',
            'image/jpeg',
            null
        );

        $form = $crawler->filter('.js-photo-dropzone')->form();
        $form['file']->upload($image);
        $client->submit($form);
        $this->assertTrue($client->getResponse()->isSuccessful(), 'response status is 2xx');
    }

    public function testDeleteHeaderImage()
    {
        $client = static::createClient([], [
            'PHP_AUTH_USER' => self::PHP_AUTH_USER,
            'PHP_AUTH_PW' => self::PHP_AUTH_PW,
        ]);

        $crawler = $client->request('GET', '/admin/setting/header_image');
        $this->assertSelectorExists('html', '.gallery>ul>li>button.remove');
        $client->submit($crawler->filter('#delete-form')->form());

        $this->assertSame(Response::HTTP_FOUND, $client->getResponse()->getStatusCode());
    }

    public function testDeletedHeaderImage()
    {
        $client = static::createClient();

        $this->assertEmpty($client->getContainer()->get('doctrine')
            ->getRepository(Settings::class)->findOneBy([
                'setting_name' => 'header_image',
            ])->getSettingValue());
    }
}