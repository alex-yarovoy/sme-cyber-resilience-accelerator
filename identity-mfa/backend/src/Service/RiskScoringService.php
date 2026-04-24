<?php

namespace App\Service;

use App\Entity\User;

class RiskScoringService
{
    public function score(User $user, string $ip, ?string $userAgent = null, ?array $context = []): int
    {
        $score = 0;

        // Geo-velocity: country change since last successful session increases risk
        if (($context['country_changed'] ?? false) === true) {
            $score += 40;
        }

        // Hosting / datacenter ASN detection signal from upstream context
        if (($context['is_hosting_asn'] ?? false) === true) {
            $score += 30;
        }

        // New device or browser
        if (($context['new_device'] ?? false) === true) {
            $score += 20;
        }

        // Time-of-day anomaly
        if (($context['time_anomaly'] ?? false) === true) {
            $score += 10;
        }

        return $score; // 0..100
    }

    public function needStepUp(int $score): bool
    {
        return $score >= 50;
    }
}


