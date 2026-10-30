<?php

namespace LivingReport\Link;

use LivingReport\Edit\Target;
use LivingReport\Edit\TargetKind;
use LivingReport\Model\PullRequestStatus;

/**
 * The elements of a report that a link can name: a decision or an action by its num or id (D16), a
 * sub-lot by its num (3c), a card or a to-do by its id. Read from the YAML data, so that the
 * loader and the edit commands resolve a link the same way.
 */
final readonly class ReportIndex
{
    /**
     * @param array<string, list<LinkedElement>> $elements
     */
    private function __construct(
        private array $elements,
    ) {
    }

    /**
     * @param array<mixed> $data
     */
    public static function of(array $data): self
    {
        $elements = [];
        foreach (Target::all($data) as $target) {
            $element = $target->in($data);
            $done = true === ($element['done'] ?? false);
            [$keys, $name, $anchor, $finished] = match ($target->kind) {
                TargetKind::Card => [[$element['id'] ?? null], self::quote($target->label), $element['id'] ?? null, $done || (PullRequestStatus::tryFrom((string) ($element['status'] ?? ''))?->isDone() ?? false)],
                TargetKind::ChecklistItem => [[$element['id'] ?? null, $element['num'] ?? null], $element['num'] ?? self::quote($target->label), $element['id'] ?? null, $done],
                // A sub-lot has no anchor of its own: its card's
                TargetKind::Sublot => [[$element['num'] ?? null], $element['num'] ?? null, $target->card()->in($data)['id'] ?? null, $done],
                TargetKind::TodoItem => [[$element['id'] ?? null], self::quote(self::firstSentence($target->label)), $element['id'] ?? null, $done],
                TargetKind::VigilanceRow => [[], null, null, $done],
            };
            foreach (array_filter($keys, static fn (mixed $key): bool => null !== $key) as $key) {
                $elements[(string) $key][] = new LinkedElement((string) $key, $target, (string) $name, null === $anchor ? null : (string) $anchor, $finished);
            }
        }

        return new self($elements);
    }

    /**
     * A link is written as one key or a list of keys.
     *
     * @return list<string>
     */
    public static function keys(mixed $link): array
    {
        return array_values(array_map(static fn (mixed $key): string => (string) $key, \is_array($link) ? $link : array_filter([$link], static fn (mixed $key): bool => null !== $key)));
    }

    /**
     * The number of a pull request or an issue, written #1234.
     */
    public static function gitHubNumber(string $key): ?int
    {
        return preg_match('/^#(\d+)$/', $key, $match) ? (int) $match[1] : null;
    }

    public function find(string $key): ?LinkedElement
    {
        $elements = $this->elements[$key] ?? [];
        if (\count($elements) > 1) {
            throw new \RuntimeException(\sprintf('A link names "%s", which names %d elements: %s.', $key, \count($elements), implode(', ', array_map(static fn (LinkedElement $element): string => $element->target->describe(), $elements))));
        }

        return $elements[0] ?? null;
    }

    private static function quote(string $text): string
    {
        return '« ' . $text . ' »';
    }

    /**
     * "Déployer le log injector depuis `main`, avec Joel. Il apporte…" gives "Déployer le log
     * injector depuis `main`, avec Joel".
     */
    private static function firstSentence(string $text): string
    {
        $sentence = rtrim(preg_split('/\.\s|\s:\s|\n/u', trim($text), 2)[0], '.');

        return mb_strlen($sentence) <= 70 ? $sentence : preg_replace('/\s+\S*$/u', '', mb_substr($sentence, 0, 70)) . '…';
    }
}
