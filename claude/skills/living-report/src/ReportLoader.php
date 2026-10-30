<?php

namespace LivingReport;

use LivingReport\Edit\TargetKind;
use LivingReport\Link\LinkedElement;
use LivingReport\Link\ReportIndex;
use LivingReport\Model\Block;
use LivingReport\Model\BlockSection;
use LivingReport\Model\Card;
use LivingReport\Model\CardSection;
use LivingReport\Model\ChecklistItem;
use LivingReport\Model\ChecklistSection;
use LivingReport\Model\Chip;
use LivingReport\Model\HistoryEntry;
use LivingReport\Model\Note;
use LivingReport\Model\PullRequestStatus;
use LivingReport\Model\Report;
use LivingReport\Model\Section;
use LivingReport\Model\Status;
use LivingReport\Model\Sublot;
use LivingReport\Model\Table;
use LivingReport\Model\TodoItem;
use LivingReport\Model\Tone;
use LivingReport\Model\VigilanceRow;
use LivingReport\Model\VigilanceSection;
use Symfony\Component\Yaml\Yaml;

/**
 * Reads a report from YAML. Strict: an unknown key or a wrong type stops the build with its path,
 * so a typo never silently drops a piece of the report.
 */
final readonly class ReportLoader
{
    public function load(string $path): Report
    {
        $data = Yaml::parseFile($path);
        if (!\is_array($data)) {
            throw new \RuntimeException(\sprintf('The report "%s" must be a YAML mapping.', $path));
        }

        return $this->report($data, pathinfo($path, \PATHINFO_FILENAME));
    }

    /**
     * @param array<mixed> $data
     */
    private function report(array $data, string $defaultStorageKey): Report
    {
        self::allowKeys($data, 'report', ['aside', 'eyebrow', 'github', 'history', 'key', 'lede', 'sections', 'status', 'title']);
        $index = ReportIndex::of($data);

        return new Report(
            title: self::string($data, 'title', 'report'),
            eyebrow: self::optionalString($data, 'eyebrow', 'report'),
            github: self::optionalString($data, 'github', 'report'),
            storageKey: self::optionalString($data, 'key', 'report') ?? $defaultStorageKey,
            lede: self::optionalString($data, 'lede', 'report'),
            status: $this->status(self::map($data, 'status', 'report'), $index),
            history: $this->each($data, 'history', 'report', $this->historyEntry(...)),
            aside: self::optionalString($data, 'aside', 'report'),
            sections: $this->each($data, 'sections', 'report', fn (array $section, string $path): Section => $this->section($section, $path, $index)),
        );
    }

    /**
     * @param array<mixed> $data
     */
    private function status(array $data, ReportIndex $index): Status
    {
        self::allowKeys($data, 'status', ['done', 'more', 'next', 'waiting']);
        $waiting = [];
        foreach (self::list($data, 'waiting', 'status') as $i => $line) {
            $waiting[] = $this->statusLine($line, \sprintf('status.waiting[%d]', $i), $index);
        }

        return new Status(
            done: self::optionalString($data, 'done', 'status'),
            next: isset($data['next']) ? $this->statusLine($data['next'], 'status.next', $index) : null,
            waiting: $waiting,
            more: self::optionalString($data, 'more', 'status'),
        );
    }

    /**
     * A text, or {todo: <id>, text: <text>} for the line of an open to-do: report:done and
     * report:sync take it out of the box when they close the to-do.
     */
    private function statusLine(mixed $line, string $path, ReportIndex $index): string
    {
        if (\is_string($line)) {
            return $line;
        }
        $line = self::asMap($line, $path);
        self::allowKeys($line, $path, ['text', 'todo']);
        $todo = $index->find(self::string($line, 'todo', $path));
        if (null === $todo || TargetKind::TodoItem !== $todo->target->kind) {
            throw new \RuntimeException(\sprintf('%s.todo must be the id of a to-do.', $path));
        }
        if ($todo->finished) {
            throw new \RuntimeException(\sprintf('%s points to a done to-do: remove it (report:done and report:sync do it).', $path));
        }

        return self::string($line, 'text', $path);
    }

    /**
     * @param array<mixed> $data
     */
    private function historyEntry(array $data, string $path): HistoryEntry
    {
        self::allowKeys($data, $path, ['date', 'text']);

        return new HistoryEntry(self::date($data, 'date', $path), self::string($data, 'text', $path));
    }

    /**
     * @param array<mixed> $data
     */
    private function section(array $data, string $path, ReportIndex $index): Section
    {
        $type = self::string($data, 'type', $path);
        self::allowKeys($data, $path, ['count', 'id', 'intro', 'tally', 'title', 'type', ...match ($type) {
            'blocks' => ['blocks'],
            'cards' => ['cards'],
            'checklist' => ['group', 'items'],
            'vigilance' => ['rows'],
            default => throw new \RuntimeException(\sprintf('The type of %s must be blocks, cards, checklist or vigilance.', $path)),
        }]);
        $id = self::string($data, 'id', $path);
        $title = self::string($data, 'title', $path);
        $intro = self::optionalString($data, 'intro', $path);
        $count = self::optionalString($data, 'count', $path);
        $tally = self::tally($data, $path);

        return match ($type) {
            'blocks' => new BlockSection($id, $title, $intro, $count, $tally, $this->each($data, 'blocks', $path, fn (array $block, string $path): Block => $this->block($block, $path, $index))),
            'cards' => new CardSection($id, $title, $intro, $count, $tally, $this->each($data, 'cards', $path, $this->card(...))),
            'checklist' => new ChecklistSection($id, $title, $intro, $count, $tally, self::string($data, 'group', $path), $this->each($data, 'items', $path, $this->checklistItem(...))),
            'vigilance' => new VigilanceSection($id, $title, $intro, $count, $tally, $this->each($data, 'rows', $path, $this->vigilanceRow(...))),
        };
    }

    /**
     * @param array<mixed> $data
     */
    private function block(array $data, string $path, ReportIndex $index): Block
    {
        self::allowKeys($data, $path, ['cards', 'id', 'progress', 'table', 'text', 'title', 'todo', 'todo_title']);
        $table = isset($data['table']) ? self::asMap($data['table'], "$path.table") : null;

        return new Block(
            id: self::optionalString($data, 'id', $path),
            title: self::string($data, 'title', $path),
            text: self::optionalString($data, 'text', $path),
            progress: self::optionalString($data, 'progress', $path),
            todoTitle: self::optionalString($data, 'todo_title', $path),
            todo: $this->each($data, 'todo', $path, fn (array $item, string $path): TodoItem => $this->todoItem($item, $path, $index)),
            cards: $this->each($data, 'cards', $path, $this->card(...)),
            table: null === $table ? null : $this->table($table, "$path.table"),
        );
    }

    /**
     * @param array<mixed> $data
     */
    private function todoItem(array $data, string $path, ReportIndex $index): TodoItem
    {
        self::allowKeys($data, $path, ['after', 'done', 'done_when', 'id', 'not_before', 'text', 'who']);
        $done = self::bool($data, 'done', $path);

        $after = [];
        foreach (self::links($data, 'after', $path) as $key) {
            if (null !== ReportIndex::gitHubNumber($key)) {
                throw new \RuntimeException(\sprintf('%s.after names %s, but waits for elements of the report only: a pull request or an issue goes in the done_when of the to-do it waits for.', $path, $key));
            }
            $element = $index->find($key) ?? throw new \RuntimeException(\sprintf('%s.after names "%s", which is no num or id of the report.', $path, $key));
            if (!$element->finished) {
                $after[] = $element;
            }
        }

        $doneWhen = self::links($data, 'done_when', $path);
        $elements = [];
        foreach ($doneWhen as $key) {
            if (null === ReportIndex::gitHubNumber($key)) {
                $elements[] = $index->find($key) ?? throw new \RuntimeException(\sprintf('%s.done_when names "%s", which is no #1234, num or id of the report.', $path, $key));
            }
        }
        // GitHub is read by report:sync only: without a #1234, the build can tell it is late
        if (!$done && [] !== $doneWhen && \count($elements) === \count($doneWhen) && [] === array_filter($elements, static fn (LinkedElement $element): bool => !$element->finished)) {
            throw new \RuntimeException(\sprintf('%s is open while everything in its done_when is done: report:sync closes it, with its note.', $path));
        }

        return new TodoItem(
            id: self::optionalString($data, 'id', $path),
            who: self::string($data, 'who', $path),
            text: self::string($data, 'text', $path),
            done: $done,
            doneWhen: $doneWhen,
            after: $after,
            notBefore: isset($data['not_before']) ? self::date($data, 'not_before', $path) : null,
        );
    }

    /**
     * @param array<mixed> $data
     */
    private function table(array $data, string $path): Table
    {
        self::allowKeys($data, $path, ['columns', 'rows']);
        $columns = self::strings($data, 'columns', $path);
        $rows = [];
        foreach (self::list($data, 'rows', $path) as $index => $row) {
            if (!\is_array($row) || !array_is_list($row) || \count($row) !== \count($columns)) {
                throw new \RuntimeException(\sprintf('%s.rows[%d] must be a list of %d cells.', $path, $index, \count($columns)));
            }
            $rows[] = array_map(static fn (mixed $cell): string => (string) $cell, $row);
        }

        return new Table($columns, $rows);
    }

    /**
     * @param array<mixed> $data
     */
    private function card(array $data, string $path): Card
    {
        self::allowKeys($data, $path, ['chips', 'done', 'id', 'meta', 'notes', 'pr', 'status', 'sublots', 'text', 'title']);
        $status = self::optionalString($data, 'status', $path);
        $pullRequest = $data['pr'] ?? null;
        if (null !== $pullRequest && !\is_int($pullRequest)) {
            throw new \RuntimeException(\sprintf('%s.pr must be the number of the pull request.', $path));
        }

        return new Card(
            id: self::optionalString($data, 'id', $path),
            title: self::string($data, 'title', $path),
            pullRequest: $pullRequest,
            status: null === $status ? null : (PullRequestStatus::tryFrom($status) ?? throw new \RuntimeException(\sprintf('%s.status must be one of %s.', $path, implode(', ', array_column(PullRequestStatus::cases(), 'value'))))),
            chips: $this->chips($data, 'chips', $path),
            done: self::bool($data, 'done', $path),
            meta: self::strings($data, 'meta', $path),
            text: self::optionalString($data, 'text', $path),
            sublots: $this->each($data, 'sublots', $path, $this->sublot(...)),
            notes: $this->notes($data, $path),
        );
    }

    /**
     * @param array<mixed> $data
     */
    private function sublot(array $data, string $path): Sublot
    {
        self::allowKeys($data, $path, ['done', 'dropped', 'notes', 'num', 'status', 'status_text', 'text']);

        return new Sublot(
            num: self::optionalString($data, 'num', $path),
            text: self::string($data, 'text', $path),
            done: self::bool($data, 'done', $path),
            dropped: self::bool($data, 'dropped', $path),
            statusChips: $this->chips($data, 'status', $path),
            statusText: self::optionalString($data, 'status_text', $path),
            notes: $this->notes($data, $path),
        );
    }

    /**
     * @param array<mixed> $data
     */
    private function checklistItem(array $data, string $path): ChecklistItem
    {
        self::allowKeys($data, $path, ['done', 'id', 'notes', 'num', 'options', 'reco', 'text', 'title']);

        return new ChecklistItem(
            id: self::string($data, 'id', $path),
            num: self::optionalString($data, 'num', $path),
            title: self::string($data, 'title', $path),
            done: self::bool($data, 'done', $path),
            text: self::optionalString($data, 'text', $path),
            options: self::strings($data, 'options', $path),
            reco: self::optionalString($data, 'reco', $path),
            notes: $this->notes($data, $path),
        );
    }

    /**
     * @param array<mixed> $data
     */
    private function vigilanceRow(array $data, string $path): VigilanceRow
    {
        self::allowKeys($data, $path, ['detail', 'done', 'notes', 'status', 'text', 'type']);

        return new VigilanceRow(
            type: $this->chip(self::map($data, 'type', $path), "$path.type"),
            text: self::string($data, 'text', $path),
            done: self::bool($data, 'done', $path),
            notes: $this->notes($data, $path),
            status: $this->chips($data, 'status', $path),
            detail: self::optionalString($data, 'detail', $path),
        );
    }

    /**
     * A chip is written {<tone>: <text>}, with an optional href. Without href, the first #123 of its
     * text links to GitHub.
     *
     * @param array<mixed> $data
     */
    private function chip(array $data, string $path): Chip
    {
        $href = self::optionalString($data, 'href', $path);
        unset($data['href']);
        if (1 !== \count($data) || null === $tone = Tone::tryFrom((string) array_key_first($data))) {
            throw new \RuntimeException(\sprintf('%s must be written {<tone>: <text>}, the tone being one of %s.', $path, implode(', ', array_column(Tone::cases(), 'value'))));
        }

        return new Chip($tone, (string) $data[$tone->value], $href);
    }

    /**
     * @param array<mixed> $data
     *
     * @return list<Chip>
     */
    private function chips(array $data, string $key, string $path): array
    {
        return $this->each($data, $key, $path, $this->chip(...));
    }

    /**
     * A note is written {<label>: <text>}.
     *
     * @param array<mixed> $data
     *
     * @return list<Note>
     */
    private function notes(array $data, string $path): array
    {
        return $this->each($data, 'notes', $path, static function (array $note, string $path): Note {
            if (1 !== \count($note)) {
                throw new \RuntimeException(\sprintf('%s must be written {<label>: <text>}.', $path));
            }

            return new Note((string) array_key_first($note), (string) $note[array_key_first($note)]);
        });
    }

    /**
     * @template T
     *
     * @param array<mixed>                         $data
     * @param \Closure(array<mixed>, string): T    $build
     *
     * @return list<T>
     */
    private function each(array $data, string $key, string $path, \Closure $build): array
    {
        $items = [];
        foreach (self::list($data, $key, $path) as $index => $item) {
            $itemPath = \sprintf('%s.%s[%d]', $path, $key, $index);
            $items[] = $build(self::asMap($item, $itemPath), $itemPath);
        }

        return $items;
    }

    /**
     * @param array<mixed> $data
     * @param list<string> $allowed
     */
    private static function allowKeys(array $data, string $path, array $allowed): void
    {
        if ([] !== $unknown = array_diff(array_keys($data), $allowed)) {
            throw new \RuntimeException(\sprintf('Unknown key "%s" in %s; allowed: %s.', implode('", "', $unknown), $path, implode(', ', $allowed)));
        }
    }

    /**
     * @return array<mixed>
     */
    private static function asMap(mixed $value, string $path): array
    {
        if (!\is_array($value) || ([] !== $value && array_is_list($value))) {
            throw new \RuntimeException(\sprintf('%s must be a mapping.', $path));
        }

        return $value;
    }

    /**
     * @param array<mixed> $data
     *
     * @return array<mixed>
     */
    private static function map(array $data, string $key, string $path): array
    {
        return self::asMap($data[$key] ?? [], "$path.$key");
    }

    /**
     * @param array<mixed> $data
     *
     * @return list<mixed>
     */
    private static function list(array $data, string $key, string $path): array
    {
        $value = $data[$key] ?? [];
        if (!\is_array($value) || !array_is_list($value)) {
            throw new \RuntimeException(\sprintf('%s.%s must be a list.', $path, $key));
        }

        return $value;
    }

    /**
     * @param array<mixed> $data
     *
     * @return list<string>
     */
    private static function strings(array $data, string $key, string $path): array
    {
        return array_map(static function (mixed $value) use ($key, $path): string {
            if (!\is_string($value)) {
                throw new \RuntimeException(\sprintf('%s.%s must be a list of texts.', $path, $key));
            }

            return $value;
        }, self::list($data, $key, $path));
    }

    /**
     * A link: one key, or a list of keys.
     *
     * @param array<mixed> $data
     *
     * @return list<string>
     */
    private static function links(array $data, string $key, string $path): array
    {
        return \is_array($data[$key] ?? []) ? self::strings($data, $key, $path) : [self::string($data, $key, $path)];
    }

    /**
     * @param array<mixed> $data
     */
    private static function string(array $data, string $key, string $path): string
    {
        return self::optionalString($data, $key, $path) ?? throw new \RuntimeException(\sprintf('%s needs a "%s".', $path, $key));
    }

    /**
     * @param array<mixed> $data
     */
    private static function optionalString(array $data, string $key, string $path): ?string
    {
        $value = $data[$key] ?? null;
        if (\is_int($value) || \is_float($value)) {
            return (string) $value;
        }
        if (null !== $value && !\is_string($value)) {
            throw new \RuntimeException(\sprintf('%s.%s must be a text.', $path, $key));
        }

        return $value;
    }

    /**
     * @param array<mixed> $data
     */
    private static function bool(array $data, string $key, string $path): bool
    {
        $value = $data[$key] ?? false;
        if (!\is_bool($value)) {
            throw new \RuntimeException(\sprintf('%s.%s must be true or false.', $path, $key));
        }

        return $value;
    }

    /**
     * @param array<mixed> $data
     */
    private static function date(array $data, string $key, string $path): \DateTimeImmutable
    {
        $value = $data[$key] ?? null;

        return match (true) {
            // YAML reads an unquoted 2026-10-20 as a UTC timestamp
            \is_int($value) => new \DateTimeImmutable('@' . $value),
            \is_string($value) && false !== $date = \DateTimeImmutable::createFromFormat('!Y-m-d', $value, new \DateTimeZone('UTC')) => $date,
            default => throw new \RuntimeException(\sprintf('%s.%s must be a date, written 2026-10-20.', $path, $key)),
        };
    }

    /**
     * @param array<mixed> $data
     *
     * @return ?array{string, string}
     */
    private static function tally(array $data, string $path): ?array
    {
        $tally = self::strings($data, 'tally', $path);
        if ([] === $tally) {
            return null;
        }
        if (2 !== \count($tally)) {
            throw new \RuntimeException(\sprintf('%s.tally must give the words for one and for many, as in [décision ouverte, décisions ouvertes].', $path));
        }

        return [$tally[0], $tally[1]];
    }
}
