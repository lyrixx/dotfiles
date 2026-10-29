<?php

namespace LivingReport\Model;

final readonly class Card
{
    /**
     * @param list<Chip>    $chips
     * @param list<string>  $meta    Markdown, one span each
     * @param list<Sublot>  $sublots
     * @param list<Note>    $notes
     */
    public function __construct(
        public ?string $id,
        public string $title,
        public ?int $pullRequest,
        public ?PullRequestStatus $status,
        public array $chips,
        private bool $done,
        public array $meta,
        public ?string $text,
        public array $sublots,
        public array $notes,
    ) {
    }

    public function isDone(): bool
    {
        return $this->status?->isDone() ?? $this->done;
    }

    /**
     * @return list<Chip>
     */
    public function headChips(): array
    {
        if (null === $this->status) {
            return $this->chips;
        }

        return [$this->status->chip($this->pullRequest), ...$this->chips];
    }

    /**
     * Numbered sub-lots are an ordered list; a lot without numbers is a plain one.
     */
    public function hasNumberedSublots(): bool
    {
        foreach ($this->sublots as $sublot) {
            if (null !== $sublot->num) {
                return true;
            }
        }

        return false;
    }
}
