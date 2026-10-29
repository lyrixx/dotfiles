<?php

namespace LivingReport\Model;

final readonly class ChecklistSection extends Section
{
    /**
     * @param ?array{string, string} $tally
     * @param string                 $group The key of the checkboxes in the browser storage, and of the counter
     * @param list<ChecklistItem>    $items
     */
    public function __construct(
        string $id,
        string $title,
        ?string $intro,
        public ?string $countFormat,
        ?array $tally,
        public string $group,
        private array $items,
    ) {
        parent::__construct($id, $title, $intro, $countFormat, $tally);
    }

    public function type(): string
    {
        return 'checklist';
    }

    /**
     * @return list<ChecklistItem>
     */
    public function items(): array
    {
        return self::openFirst($this->items, static fn (ChecklistItem $item): bool => $item->done);
    }

    protected function totalCount(): int
    {
        return \count($this->items);
    }

    protected function doneCount(): int
    {
        return \count(array_filter($this->items, static fn (ChecklistItem $item): bool => $item->done));
    }
}
