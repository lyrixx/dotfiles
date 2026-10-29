<?php

namespace LivingReport\Model;

/**
 * The three boxes under the lede: what is done, what comes next, what we wait for.
 */
final readonly class Status
{
    /**
     * @param list<string> $waiting Markdown
     */
    public function __construct(
        public ?string $done,
        public ?string $next,
        public array $waiting,
        public ?string $more,
    ) {
    }
}
