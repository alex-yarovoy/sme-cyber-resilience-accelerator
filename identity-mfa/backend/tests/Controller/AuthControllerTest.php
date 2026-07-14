<?php

namespace App\Tests\Controller;

use App\DataFixtures\AppFixtures;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\Tools\SchemaTool;
use Symfony\Bundle\FrameworkBundle\KernelBrowser;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;

class AuthControllerTest extends WebTestCase
{
    private function createPreparedClient(): KernelBrowser
    {
        $client = static::createClient();
        $this->resetDatabase();

        return $client;
    }

    public function testLoginRejectsInvalidCredentials(): void
    {
        $client = $this->createPreparedClient();
        $client->request(
            'POST',
            '/api/auth/login',
            server: ['CONTENT_TYPE' => 'application/json'],
            content: json_encode(['email' => 'admin@example.com', 'password' => 'wrong-password'], JSON_THROW_ON_ERROR)
        );

        $this->assertResponseStatusCodeSame(401);
    }

    public function testLoginSucceedsForValidUserWithoutMfa(): void
    {
        $client = $this->createPreparedClient();
        $client->request(
            'POST',
            '/api/auth/login',
            server: ['CONTENT_TYPE' => 'application/json'],
            content: json_encode(['email' => 'admin@example.com', 'password' => 'Admin#123456'], JSON_THROW_ON_ERROR)
        );

        $this->assertResponseIsSuccessful();
        $payload = json_decode($client->getResponse()->getContent(), true, 512, JSON_THROW_ON_ERROR);
        $this->assertArrayHasKey('access_token', $payload);
        $this->assertNotEmpty($payload['access_token']);
    }

    public function testFailedLoginIncrementsMetrics(): void
    {
        $client = $this->createPreparedClient();
        $client->request(
            'POST',
            '/api/auth/login',
            server: ['CONTENT_TYPE' => 'application/json'],
            content: json_encode(['email' => 'admin@example.com', 'password' => 'wrong-password'], JSON_THROW_ON_ERROR)
        );

        $client->request('GET', '/api/metrics');
        $this->assertStringContainsString('app_auth_login_failed_total 1', $client->getResponse()->getContent());
    }

    private function resetDatabase(): void
    {
        $container = static::getContainer();
        /** @var EntityManagerInterface $em */
        $em = $container->get(EntityManagerInterface::class);
        $metadata = $em->getMetadataFactory()->getAllMetadata();
        $tool = new SchemaTool($em);
        $tool->dropSchema($metadata);
        $tool->createSchema($metadata);

        $connection = $em->getConnection();
        if (!$connection->createSchemaManager()->tablesExist(['refresh_tokens'])) {
            $connection->executeStatement(
                'CREATE TABLE refresh_tokens (id SERIAL NOT NULL, refresh_token VARCHAR(128) NOT NULL, username VARCHAR(255) NOT NULL, valid TIMESTAMP(0) WITHOUT TIME ZONE NOT NULL, PRIMARY KEY(id))'
            );
            $connection->executeStatement(
                'CREATE UNIQUE INDEX IF NOT EXISTS uniq_refresh_token ON refresh_tokens (refresh_token)'
            );
        }

        $container->get(AppFixtures::class)->load($em);
    }
}
