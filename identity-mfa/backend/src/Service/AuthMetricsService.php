<?php

namespace App\Service;

class AuthMetricsService
{
    private int $loginFailedTotal = 0;
    private int $mfaFailedTotal = 0;

    public function recordLoginFailure(): void
    {
        ++$this->loginFailedTotal;
    }

    public function recordMfaFailure(): void
    {
        ++$this->mfaFailedTotal;
    }

    public function renderPrometheus(): string
    {
        $lines = [
            '# HELP app_auth_login_failed_total Failed login attempts.',
            '# TYPE app_auth_login_failed_total counter',
            'app_auth_login_failed_total '.$this->loginFailedTotal,
            '# HELP app_auth_mfa_failed_total Failed MFA verification attempts.',
            '# TYPE app_auth_mfa_failed_total counter',
            'app_auth_mfa_failed_total '.$this->mfaFailedTotal,
        ];

        return implode("\n", $lines)."\n";
    }
}
