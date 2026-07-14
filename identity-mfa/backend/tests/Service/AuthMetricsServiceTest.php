<?php

namespace App\Tests\Service;

use App\Service\AuthMetricsService;
use PHPUnit\Framework\TestCase;

class AuthMetricsServiceTest extends TestCase
{
    public function testPrometheusOutputIncludesCounters(): void
    {
        $metrics = new AuthMetricsService();
        $metrics->recordLoginFailure();
        $metrics->recordMfaFailure();
        $metrics->recordMfaFailure();

        $body = $metrics->renderPrometheus();

        $this->assertStringContainsString('app_auth_login_failed_total 1', $body);
        $this->assertStringContainsString('app_auth_mfa_failed_total 2', $body);
    }
}
