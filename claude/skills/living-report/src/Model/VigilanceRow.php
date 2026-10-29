<?php

namespace LivingReport\Model;

final readonly class VigilanceRow
{
    /**
     * @param list<Note> $notes
     * @param list<Chip> $status
     */
    public function __construct(
        public Chip $type,
        public string $text,
        public bool $done,
        public array $notes,
        public array $status,
        public ?string $detail,
    ) {
    }
}
