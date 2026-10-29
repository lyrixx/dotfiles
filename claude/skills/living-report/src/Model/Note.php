<?php

namespace LivingReport\Model;

/**
 * A dated or labelled line under an item: what happened to it.
 */
final readonly class Note
{
    public function __construct(
        public string $label,
        public string $text,
    ) {
    }
}
