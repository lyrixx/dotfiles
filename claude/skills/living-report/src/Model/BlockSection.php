<?php

namespace LivingReport\Model;

final readonly class BlockSection extends Section
{
    /**
     * @param ?array{string, string} $tally
     * @param list<Block>            $blocks
     */
    public function __construct(
        string $id,
        string $title,
        ?string $intro,
        ?string $count,
        ?array $tally,
        public array $blocks,
    ) {
        parent::__construct($id, $title, $intro, $count, $tally);
    }

    public function type(): string
    {
        return 'blocks';
    }
}
