<?php

namespace LivingReport\Model;

final readonly class VigilanceSection extends Section
{
    /**
     * @param ?array{string, string} $tally
     * @param list<VigilanceRow>     $rows
     */
    public function __construct(
        string $id,
        string $title,
        ?string $intro,
        ?string $count,
        ?array $tally,
        private array $rows,
    ) {
        parent::__construct($id, $title, $intro, $count, $tally);
    }

    public function type(): string
    {
        return 'vigilance';
    }

    /**
     * @return list<VigilanceRow>
     */
    public function rows(): array
    {
        return self::openFirst($this->rows, static fn (VigilanceRow $row): bool => $row->done);
    }

    protected function totalCount(): int
    {
        return \count($this->rows);
    }

    protected function doneCount(): int
    {
        return \count(array_filter($this->rows, static fn (VigilanceRow $row): bool => $row->done));
    }
}
