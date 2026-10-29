<?php

namespace LivingReport\Model;

final readonly class Table
{
    /**
     * @param list<string>       $columns
     * @param list<list<string>> $rows    Markdown cells
     */
    public function __construct(
        public array $columns,
        public array $rows,
    ) {
    }
}
