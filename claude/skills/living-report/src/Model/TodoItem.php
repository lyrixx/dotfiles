<?php

namespace LivingReport\Model;

use LivingReport\Link\LinkedElement;

final readonly class TodoItem
{
    /**
     * @param list<string>        $doneWhen What closes it: #1234, D16, 3c or an id
     * @param list<LinkedElement> $after    The elements of its `after` still open
     */
    public function __construct(
        public ?string $id,
        public string $who,
        public string $text,
        public bool $done,
        public array $doneWhen,
        public array $after,
        public ?\DateTimeImmutable $notBefore,
    ) {
    }

    public function isBlocked(\DateTimeImmutable $today): bool
    {
        return !$this->done && ([] !== $this->after || $this->waitsForDate($today));
    }

    public function waitsForDate(\DateTimeImmutable $today): bool
    {
        return null !== $this->notBefore && $this->notBefore->format('Y-m-d') > $today->format('Y-m-d');
    }

    public function chip(\DateTimeImmutable $today): Chip
    {
        return match (true) {
            $this->done => new Chip(Tone::Ok, 'Fait'),
            $this->isBlocked($today) => new Chip(Tone::Muted, $this->who),
            default => new Chip(Tone::Warn, $this->who),
        };
    }
}
