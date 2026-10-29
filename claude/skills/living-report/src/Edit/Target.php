<?php

namespace LivingReport\Edit;

/**
 * An element of the report an edit command acts on, found by a selector.
 */
final readonly class Target
{
    /**
     * @param list<int|string> $path Keys from the root of the YAML
     */
    public function __construct(
        public array $path,
        public TargetKind $kind,
        public string $label,
    ) {
    }

    public function describe(): string
    {
        return \sprintf('%s « %s »', $this->kind->label(), mb_strimwidth($this->label, 0, 70, '…'));
    }
}
