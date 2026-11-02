<?php

namespace LivingReport\Model;

use LivingReport\Link\LinkedElement;

final readonly class TodoItem
{
    /**
     * @param list<string>        $doneWhen What closes it: #1234, D16, 3c or an id
     * @param list<LinkedElement> $after    The elements of its `after` still open
     * @param list<Chip>          $chips    The pull requests and issues it is linked to, with their state
     */
    public function __construct(
        public ?string $id,
        public string $who,
        public string $text,
        public bool $done,
        public bool $dropped,
        public array $doneWhen,
        public array $after,
        public ?\DateTimeImmutable $notBefore,
        public array $chips,
    ) {
    }

    /**
     * Done, or dropped: nothing to do any more.
     */
    public function isFinished(): bool
    {
        return $this->done || $this->dropped;
    }

    public function isBlocked(\DateTimeImmutable $today): bool
    {
        return !$this->isFinished() && ([] !== $this->after || $this->waitsForDate($today));
    }

    public function waitsForDate(\DateTimeImmutable $today): bool
    {
        return null !== $this->notBefore && $this->notBefore->format('Y-m-d') > $today->format('Y-m-d');
    }

    public function chip(\DateTimeImmutable $today): Chip
    {
        return match (true) {
            $this->done => new Chip(Tone::Ok, 'Fait'),
            $this->dropped => new Chip(Tone::Muted, 'Écarté'),
            $this->isBlocked($today) => new Chip(Tone::Muted, $this->who),
            default => new Chip(Tone::Warn, $this->who),
        };
    }
}
