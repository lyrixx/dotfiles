<?php

namespace LivingReport\Link;

use LivingReport\Edit\Target;

/**
 * An element of the report that a link names: `after`, `done_when`, or a box under the lede.
 */
final readonly class LinkedElement
{
    public function __construct(
        // As the link writes it: D16, 3c, an id
        public string $key,
        public Target $target,
        // What "Après …" shows: D16, 3c, or a title or the first sentence of a to-do, in quotes
        public string $name,
        public ?string $anchor,
        public bool $finished,
    ) {
    }
}
