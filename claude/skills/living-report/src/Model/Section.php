<?php

namespace LivingReport\Model;

abstract readonly class Section
{
    /**
     * @param ?string                  $count A text, where {open}, {done} and {total} count the items
     * @param ?array{string, string}   $tally The words of the summary under the lede: one, many
     */
    public function __construct(
        public string $id,
        public string $title,
        public ?string $intro,
        private ?string $count,
        private ?array $tally,
    ) {
    }

    abstract public function type(): string;

    public function openCount(): int
    {
        return $this->totalCount() - $this->doneCount();
    }

    public function count(): ?string
    {
        if (null === $this->count) {
            return null;
        }

        return strtr($this->count, [
            '{done}' => (string) $this->doneCount(),
            '{open}' => (string) $this->openCount(),
            '{total}' => (string) $this->totalCount(),
        ]);
    }

    /**
     * @return ?array{count: int, label: string}
     */
    public function tally(): ?array
    {
        if (null === $this->tally) {
            return null;
        }

        $open = $this->openCount();

        // French: zero and one take the singular
        return ['count' => $open, 'label' => $open > 1 ? $this->tally[1] : $this->tally[0]];
    }

    protected function totalCount(): int
    {
        return 0;
    }

    protected function doneCount(): int
    {
        return 0;
    }

    /**
     * Open items first, then the done ones, each group in the order of the file.
     *
     * @template T of object
     *
     * @param list<T>             $items
     * @param \Closure(T): bool   $isDone
     *
     * @return list<T>
     */
    protected static function openFirst(array $items, \Closure $isDone): array
    {
        return [
            ...array_values(array_filter($items, static fn (object $item): bool => !$isDone($item))),
            ...array_values(array_filter($items, $isDone)),
        ];
    }
}
