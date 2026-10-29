<?php

namespace LivingReport\Model;

/**
 * A titled part of a free section: text, then a progress line, a to-do list, cards or a table.
 */
final readonly class Block
{
    /**
     * @param list<TodoItem> $todo
     * @param list<Card>     $cards
     */
    public function __construct(
        public ?string $id,
        public string $title,
        public ?string $text,
        public ?string $progress,
        public ?string $todoTitle,
        private array $todo,
        public array $cards,
        public ?Table $table,
    ) {
    }

    /**
     * @return list<TodoItem>
     */
    public function todo(): array
    {
        return [
            ...array_values(array_filter($this->todo, static fn (TodoItem $item): bool => !$item->done)),
            ...array_values(array_filter($this->todo, static fn (TodoItem $item): bool => $item->done)),
        ];
    }
}
