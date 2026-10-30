<?php

namespace LivingReport\Edit;

use LivingReport\FrenchDate;

final readonly class IssueState
{
    public function __construct(
        public bool $closed,
        // Closed as completed, not as "not planned" or duplicate
        public bool $completed,
        public ?\DateTimeImmutable $closedAt,
    ) {
    }

    /**
     * Closed as completed: what a `done_when` waits for.
     */
    public function isFinished(): bool
    {
        return $this->completed;
    }

    public function isClosed(): bool
    {
        return $this->closed;
    }

    /**
     * "#1234 fermée le 28 octobre"
     */
    public function describe(int $number): string
    {
        return \sprintf('#%d %s%s', $number, match (true) {
            $this->completed => 'fermée',
            $this->closed => 'fermée sans être faite',
            default => 'ouverte',
        }, FrenchDate::on($this->closedAt));
    }
}
