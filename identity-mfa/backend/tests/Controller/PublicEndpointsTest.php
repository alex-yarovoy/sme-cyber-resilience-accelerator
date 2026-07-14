<?php

namespace App\Tests\Controller;

use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;

class PublicEndpointsTest extends WebTestCase
{
    public function testHealthEndpoint(): void
    {
        $client = static::createClient();
        $client->request('GET', '/api/health');

        $this->assertResponseIsSuccessful();
        $payload = json_decode($client->getResponse()->getContent(), true, 512, JSON_THROW_ON_ERROR);
        $this->assertSame('ok', $payload['status']);
    }

    public function testMetricsEndpoint(): void
    {
        $client = static::createClient();
        $client->request('GET', '/api/metrics');

        $this->assertResponseIsSuccessful();
        $this->assertStringContainsString('app_auth_login_failed_total', $client->getResponse()->getContent());
    }
}
