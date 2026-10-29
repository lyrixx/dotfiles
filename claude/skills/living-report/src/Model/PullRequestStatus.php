<?php

namespace LivingReport\Model;

enum PullRequestStatus: string
{
    case Closed = 'closed';
    case Draft = 'draft';
    case Local = 'local';
    case Merged = 'merged';
    case Open = 'open';
    case Stopped = 'stopped';

    public function isDone(): bool
    {
        return \in_array($this, [self::Closed, self::Merged, self::Stopped], true);
    }

    public function chip(?int $number): Chip
    {
        return match ($this) {
            self::Closed => new Chip(Tone::Muted, \sprintf('Fermée #%d', $number)),
            self::Draft => new Chip(Tone::Info, \sprintf('Draft #%d', $number)),
            self::Local => new Chip(Tone::Warn, 'Locale, pas commitée'),
            self::Merged => new Chip(Tone::Ok, \sprintf('Mergée #%d', $number)),
            self::Open => new Chip(Tone::Info, \sprintf('Ouverte #%d', $number)),
            self::Stopped => new Chip(Tone::Muted, 'Arrêtée'),
        };
    }

    /**
     * The word of the branch count, agreed with "branche".
     */
    public function word(int $count): string
    {
        $word = match ($this) {
            self::Closed => 'fermée',
            self::Draft, self::Open => 'ouverte',
            self::Local => 'locale',
            self::Merged => 'mergée',
            self::Stopped => 'arrêtée',
        };

        return $count > 1 ? $word . 's' : $word;
    }
}
