<?php

namespace LivingReport\Model;

final readonly class ChecklistItem
{
    /**
     * @param list<string> $options Markdown
     * @param list<Note>   $notes
     */
    public function __construct(
        public string $id,
        public ?string $num,
        public string $title,
        public bool $done,
        public ?string $text,
        public array $options,
        public ?string $reco,
        public array $notes,
    ) {
    }
}
