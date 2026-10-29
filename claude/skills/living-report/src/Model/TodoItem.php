<?php

namespace LivingReport\Model;

final readonly class TodoItem
{
    public function __construct(
        public string $who,
        public string $text,
        public bool $done,
    ) {
    }

    public function chip(): Chip
    {
        return $this->done ? new Chip(Tone::Ok, 'Fait') : new Chip(Tone::Warn, $this->who);
    }
}
