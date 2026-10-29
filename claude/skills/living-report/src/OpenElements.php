<?php

namespace LivingReport;

use LivingReport\Model\BlockSection;
use LivingReport\Model\Card;
use LivingReport\Model\CardSection;
use LivingReport\Model\ChecklistItem;
use LivingReport\Model\ChecklistSection;
use LivingReport\Model\Chip;
use LivingReport\Model\Report;
use LivingReport\Model\Section;
use LivingReport\Model\TodoItem;
use LivingReport\Model\VigilanceRow;
use LivingReport\Model\VigilanceSection;

/**
 * What is still open, as text, with the counters of the HTML: "where are we" and "what now" are
 * answered from the model, not from an excerpt of the YAML, where a "done: true" is easily read as
 * part of the next element.
 */
final readonly class OpenElements
{
    public function render(Report $report): string
    {
        $lines = [];
        if (null !== $report->status->next) {
            $lines[] = 'Prochaine étape · ' . self::flat($report->status->next);
        }
        foreach ($report->status->waiting as $waiting) {
            $lines[] = 'En attente · ' . self::flat($waiting);
        }

        foreach ($report->sections as $section) {
            $count = $section->count();
            $open = $this->section($section);
            $lines[] = '';
            $lines[] = $section->title . (null === $count ? '' : ' (' . $count . ')');
            array_push($lines, ...self::indent([] === $open ? ['Rien d\'ouvert.'] : $open));
        }

        return ltrim(implode("\n", $lines), "\n");
    }

    /**
     * @return list<string>
     */
    private function section(Section $section): array
    {
        return match (true) {
            $section instanceof BlockSection => $this->blocks($section),
            $section instanceof CardSection => $this->cards($section->cards()),
            $section instanceof ChecklistSection => array_values(array_map(
                static fn (ChecklistItem $item): string => self::line($item->num ?? $item->id, $item->title),
                array_filter($section->items(), static fn (ChecklistItem $item): bool => !$item->done),
            )),
            $section instanceof VigilanceSection => array_values(array_map(
                static fn (VigilanceRow $row): string => self::line($row->type->text, $row->text, ...array_map(static fn (Chip $chip): string => $chip->text, $row->status), ...array_filter([$row->detail])),
                array_filter($section->rows(), static fn (VigilanceRow $row): bool => !$row->done),
            )),
        };
    }

    /**
     * @return list<string>
     */
    private function blocks(BlockSection $section): array
    {
        $lines = [];
        foreach ($section->blocks as $block) {
            $open = [
                ...array_map(
                    static fn (TodoItem $item): string => self::line($item->who, $item->text),
                    array_filter($block->todo(), static fn (TodoItem $item): bool => !$item->done),
                ),
                ...$this->cards($block->cards),
            ];
            if ([] !== $open) {
                array_push($lines, $block->title, ...self::indent($open));
            }
        }

        return $lines;
    }

    /**
     * An open card, then its open sub-lots.
     *
     * @param list<Card> $cards
     *
     * @return list<string>
     */
    private function cards(array $cards): array
    {
        $lines = [];
        foreach ($cards as $card) {
            if ($card->isDone()) {
                continue;
            }
            $lines[] = self::line(null === $card->pullRequest ? null : '#' . $card->pullRequest, $card->status?->value, $card->title);
            foreach ($card->sublots as $sublot) {
                if (!$sublot->done && !$sublot->dropped) {
                    $lines[] = '  ' . self::line($sublot->num, $sublot->text);
                }
            }
        }

        return $lines;
    }

    private static function line(?string ...$parts): string
    {
        return implode(' · ', array_map(self::flat(...), array_filter($parts, static fn (?string $part): bool => null !== $part)));
    }

    private static function flat(string $markdown): string
    {
        return mb_strimwidth(trim((string) preg_replace('/\s+/u', ' ', $markdown)), 0, 100, '…');
    }

    /**
     * @param list<string> $lines
     *
     * @return list<string>
     */
    private static function indent(array $lines): array
    {
        return array_map(static fn (string $line): string => '  ' . $line, $lines);
    }
}
