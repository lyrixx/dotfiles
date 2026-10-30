<?php

namespace LivingReport\Edit;

use LivingReport\FrenchDate;
use LivingReport\Model\PullRequestStatus;

final readonly class PullRequestState
{
    public function __construct(
        public PullRequestStatus $status,
        public ?\DateTimeImmutable $closedAt,
    ) {
    }

    /**
     * Merged: what a `done_when` waits for.
     */
    public function isFinished(): bool
    {
        return PullRequestStatus::Merged === $this->status;
    }

    public function isClosed(): bool
    {
        return \in_array($this->status, [PullRequestStatus::Closed, PullRequestStatus::Merged], true);
    }

    /**
     * "#1234 mergée le 28 octobre"
     */
    public function describe(int $number): string
    {
        return \sprintf('#%d %s%s', $number, match ($this->status) {
            PullRequestStatus::Closed => 'fermée sans merge',
            PullRequestStatus::Merged => 'mergée',
            default => 'ouverte',
        }, $this->when());
    }

    /**
     * " le 28 octobre", for a merged or closed one.
     */
    public function when(): string
    {
        return FrenchDate::on($this->closedAt);
    }
}
