<?php

namespace LivingReport\Model;

final readonly class HistoryEntry
{
    public function __construct(
        public \DateTimeImmutable $date,
        public string $text,
    ) {
    }
}
