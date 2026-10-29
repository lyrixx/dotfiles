<?php

namespace LivingReport\Model;

/**
 * A step of a card: a numbered sub-lot, or a line of a lot without numbers.
 */
final readonly class Sublot
{
    /**
     * @param list<Chip> $statusChips
     * @param list<Note> $notes
     */
    public function __construct(
        public ?string $num,
        public string $text,
        public bool $done,
        public bool $dropped,
        public array $statusChips,
        public ?string $statusText,
        public array $notes,
    ) {
    }
}
