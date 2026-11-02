<?php

namespace LivingReport\Edit;

use LivingReport\FrenchDate;
use LivingReport\Model\Chip;
use LivingReport\Model\Tone;

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

    public function chip(int $number): Chip
    {
        return match (true) {
            $this->completed => new Chip(Tone::Ok, \sprintf('Issue fermée #%d', $number)),
            $this->closed => new Chip(Tone::Muted, \sprintf('Issue écartée #%d', $number)),
            default => new Chip(Tone::Info, \sprintf('Issue ouverte #%d', $number)),
        };
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
        }, $this->when());
    }

    /**
     * " le 28 octobre", for a closed one.
     */
    public function when(): string
    {
        return FrenchDate::on($this->closedAt);
    }
}
