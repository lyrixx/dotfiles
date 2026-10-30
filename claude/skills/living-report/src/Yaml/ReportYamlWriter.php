<?php

namespace LivingReport\Yaml;

/**
 * Writes a report back to YAML in one canonical form, so that the edit commands and the hand edits
 * keep a stable, readable file: plain scalars when safe, literal blocks for long or risky
 * Markdown (a " #" would start a comment), chips on one line.
 */
final class ReportYamlWriter
{
    // The state of an element goes right under the keys that name it, before its prose: a
    // "done: true" left at the bottom reads, in an excerpt, as the first line of the next element.
    private const array NAME_KEYS = ['id', 'num', 'pr', 'title', 'type', 'who'];
    private const array STATE_KEYS = ['done', 'dropped'];
    // The links of a to-do follow its state: what closes it, what it waits for
    private const array LINK_KEYS = ['after', 'done_when', 'not_before'];
    private const array TONES = ['crit', 'info', 'muted', 'ok', 'warn'];

    /**
     * @param array<mixed> $report
     */
    public function dump(array $report): string
    {
        return ltrim($this->map($report, '')) . "\n";
    }

    private function value(mixed $value, string $indent): string
    {
        return match (true) {
            \is_bool($value) => $value ? 'true' : 'false',
            \is_int($value) => (string) $value,
            $value instanceof \DateTimeInterface => $value->format('Y-m-d'),
            \is_string($value) => $this->scalar($value, $indent),
            \is_array($value) && self::isChip($value) => $this->chip($value),
            \is_array($value) && array_is_list($value) => $this->list($value, $indent),
            \is_array($value) => "\n" . $this->map($value, $indent),
            default => throw new \LogicException(\sprintf('A report holds no %s.', get_debug_type($value))),
        };
    }

    /**
     * @param list<mixed> $list
     */
    private function list(array $list, string $indent): string
    {
        $lines = [];
        foreach ($list as $item) {
            $lines[] = \is_array($item) && !array_is_list($item) && !self::isChip($item)
                ? $indent . '- ' . ltrim($this->map($item, $indent . '  '))
                : $indent . '- ' . $this->value($item, $indent . '  ');
        }

        return "\n" . implode("\n", $lines);
    }

    /**
     * @param array<mixed> $map
     */
    private function map(array $map, string $indent): string
    {
        $lines = [];
        foreach (self::stateFirst($map) as $key => $value) {
            if ([] === $value) {
                continue;
            }
            $lines[] = \is_array($value) && !self::isChip($value)
                ? $indent . self::key((string) $key) . ':' . $this->value($value, $indent . '  ')
                : $indent . self::key((string) $key) . ': ' . $this->value($value, $indent);
        }

        return implode("\n", $lines);
    }

    private function scalar(string $value, string $indent): string
    {
        if ('' === $value) {
            return "''";
        }

        $risky = str_contains($value, "\n") || str_contains($value, ': ') || str_contains($value, ' #') || str_ends_with($value, ':') || str_ends_with($value, ' ')
            || preg_match('/^[-?:,\[\]{}#&*!|>\'"%@`\\\\ ]/', $value)
            || preg_match('/^(true|false|null|yes|no|on|off|~|\d[\d.e+-]*|\d{4}-\d{2}-\d{2}.*)$/i', $value);
        if (!$risky) {
            return $value;
        }
        if (!str_contains($value, "\n") && mb_strlen($value) < 60 && !str_contains($value, "'")) {
            return "'" . $value . "'";
        }

        return "|-\n" . implode("\n", array_map(static fn (string $line): string => '' === $line ? '' : $indent . '  ' . $line, explode("\n", $value)));
    }

    /**
     * @param array<mixed> $chip
     */
    private function chip(array $chip): string
    {
        $parts = [];
        foreach ($chip as $key => $text) {
            $parts[] = $key . ': ' . "'" . str_replace("'", "''", (string) $text) . "'";
        }

        return '{' . implode(', ', $parts) . '}';
    }

    /**
     * {ok: 'Mergée #1211'}, with an optional href.
     *
     * @param array<mixed> $value
     */
    private static function isChip(array $value): bool
    {
        $keys = array_keys($value);

        return [] !== $value && !array_is_list($value) && 1 === \count(array_intersect($keys, self::TONES)) && [] === array_diff($keys, [...self::TONES, 'href']);
    }

    /**
     * The name keys, then the state, then the links, then the rest, each in the order of the file.
     * Only an element has a boolean "done" or links: the "done" of the status under the lede is a
     * text.
     *
     * @param array<mixed> $map
     *
     * @return array<mixed>
     */
    private static function stateFirst(array $map): array
    {
        if (!\is_bool($map['done'] ?? $map['dropped'] ?? null) && [] === array_intersect_key($map, array_flip(self::LINK_KEYS))) {
            return $map;
        }

        return array_intersect_key($map, array_flip(self::NAME_KEYS)) + array_intersect_key($map, array_flip(self::STATE_KEYS)) + array_intersect_key($map, array_flip(self::LINK_KEYS)) + $map;
    }

    private static function key(string $key): string
    {
        return preg_match('/^[\p{L}\p{N}_][\p{L}\p{N}_ .\'-]*$/u', $key) && !preg_match('/^(true|false|null|yes|no|on|off)$/i', $key)
            ? $key
            : "'" . str_replace("'", "''", $key) . "'";
    }
}
