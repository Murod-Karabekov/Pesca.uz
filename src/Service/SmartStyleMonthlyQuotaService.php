<?php

namespace App\Service;

use App\Entity\MembershipPlan;
use App\Entity\User;
use App\Repository\SmartStyleScanHistoryRepository;

/**
 * SmartStyle tahlil/skanlarini tarif bo'yicha oyiga cheklash (Toshkent vaqti bilan kalendary oy).
 */
final class SmartStyleMonthlyQuotaService
{
    private const TIMEZONE = 'Asia/Tashkent';

    public function __construct(
        private readonly SmartStyleScanHistoryRepository $scanHistoryRepository,
    ) {
    }

    /**
     * null = cheksiz (VIP).
     */
    public function getMonthlyLimitForPlan(string $planSlug): ?int
    {
        return match ($planSlug) {
            MembershipPlan::SLUG_FREE => 10,
            MembershipPlan::SLUG_START => 30,
            MembershipPlan::SLUG_PREMIUM => 100,
            MembershipPlan::SLUG_VIP => null,
            default => 10,
        };
    }

    public function currentMonthStart(): \DateTimeImmutable
    {
        $tz = new \DateTimeZone(self::TIMEZONE);

        return (new \DateTimeImmutable('now', $tz))
            ->modify('first day of this month')
            ->setTime(0, 0, 0);
    }

    public function nextMonthStart(): \DateTimeImmutable
    {
        return $this->currentMonthStart()->modify('+1 month');
    }

    public function countUsesThisMonth(User $user): int
    {
        return $this->scanHistoryRepository->countCreatedSince($user, $this->currentMonthStart());
    }

    /**
     * @return array{
     *   limit: int|null,
     *   used: int,
     *   remaining: int|null,
     *   blocked: bool,
     *   plan: string,
     *   nextResetLabel: string|null
     * }
     */
    public function getStateForUser(User $user): array
    {
        $plan = $user->getCurrentPlan();
        $limit = $this->getMonthlyLimitForPlan($plan);
        $used = $this->countUsesThisMonth($user);

        if ($limit === null) {
            return [
                'limit' => null,
                'used' => $used,
                'remaining' => null,
                'blocked' => false,
                'plan' => $plan,
                'nextResetLabel' => null,
            ];
        }

        $remaining = max(0, $limit - $used);

        return [
            'limit' => $limit,
            'used' => $used,
            'remaining' => $remaining,
            'blocked' => $used >= $limit,
            'plan' => $plan,
            'nextResetLabel' => $this->nextMonthStart()->format('d.m.Y'),
        ];
    }

    public function isBlocked(User $user): bool
    {
        return $this->getStateForUser($user)['blocked'];
    }
}
