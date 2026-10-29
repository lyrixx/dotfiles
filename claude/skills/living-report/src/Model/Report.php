<?php

namespace LivingReport\Model;

final readonly class Report
{
    /**
     * @param list<HistoryEntry> $history
     * @param list<Section>      $sections
     */
    public function __construct(
        public string $title,
        public ?string $eyebrow,
        public ?string $github,
        public string $storageKey,
        public ?string $lede,
        public Status $status,
        public array $history,
        public ?string $aside,
        public array $sections,
    ) {
    }

    /**
     * @return list<array{section: Section, count: int, label: string}>
     */
    public function tally(): array
    {
        $tally = [];
        foreach ($this->sections as $section) {
            if (null !== $entry = $section->tally()) {
                $tally[] = ['section' => $section, ...$entry];
            }
        }

        return $tally;
    }

    /**
     * The anchor of each numbered checklist item ("D14"), for the links written in the text.
     *
     * @return array<string, string>
     */
    public function anchors(): array
    {
        $anchors = [];
        foreach ($this->sections as $section) {
            if ($section instanceof ChecklistSection) {
                foreach ($section->items() as $item) {
                    if (null !== $item->num) {
                        $anchors[$item->num] = $item->id;
                    }
                }
            }
        }

        return $anchors;
    }
}
