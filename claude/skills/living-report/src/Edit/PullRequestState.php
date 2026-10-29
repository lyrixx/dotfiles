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
     * " le 28 octobre", for a merged or closed one.
     */
    public function when(): string
    {
        return null === $this->closedAt ? '' : ' le ' . FrenchDate::dayMonth($this->closedAt->setTimezone(new \DateTimeZone('Europe/Paris')));
    }
}
