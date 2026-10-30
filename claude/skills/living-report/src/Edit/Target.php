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

    /**
     * Every element of the YAML data, in the order of the file.
     *
     * @param array<mixed> $data
     *
     * @return list<self>
     */
    public static function all(array $data): array
    {
        $targets = [];
        foreach ($data['sections'] ?? [] as $s => $section) {
            $base = ['sections', $s];
            foreach ($section['items'] ?? [] as $i => $item) {
                $targets[] = new self([...$base, 'items', $i], TargetKind::ChecklistItem, (string) ($item['title'] ?? ''));
            }
            foreach ($section['rows'] ?? [] as $i => $row) {
                $targets[] = new self([...$base, 'rows', $i], TargetKind::VigilanceRow, (string) ($row['text'] ?? ''));
            }
            foreach ($section['cards'] ?? [] as $i => $card) {
                array_push($targets, ...self::cardTargets([...$base, 'cards', $i], $card));
            }
            foreach ($section['blocks'] ?? [] as $b => $block) {
                foreach ($block['todo'] ?? [] as $i => $item) {
                    $targets[] = new self([...$base, 'blocks', $b, 'todo', $i], TargetKind::TodoItem, (string) ($item['text'] ?? ''));
                }
                foreach ($block['cards'] ?? [] as $i => $card) {
                    array_push($targets, ...self::cardTargets([...$base, 'blocks', $b, 'cards', $i], $card));
                }
            }
        }

        return $targets;
    }

    /**
     * @param array<mixed> $data The data this target was found in
     *
     * @return array<mixed>
     */
    public function in(array $data): array
    {
        foreach ($this->path as $key) {
            $data = $data[$key];
        }

        return $data;
    }

    /**
     * The card of a sub-lot.
     */
    public function card(): self
    {
        if (TargetKind::Sublot !== $this->kind) {
            throw new \LogicException(\sprintf('Only a sub-lot has a card, not the %s.', $this->describe()));
        }

        return new self(\array_slice($this->path, 0, -2), TargetKind::Card, '');
    }

    public function describe(): string
    {
        return \sprintf('%s « %s »', $this->kind->label(), mb_strimwidth($this->label, 0, 70, '…'));
    }

    /**
     * @param list<int|string> $path
     * @param array<mixed>     $card
     *
     * @return list<self>
     */
    private static function cardTargets(array $path, array $card): array
    {
        $targets = [new self($path, TargetKind::Card, (string) ($card['title'] ?? ''))];
        foreach ($card['sublots'] ?? [] as $i => $sublot) {
            $targets[] = new self([...$path, 'sublots', $i], TargetKind::Sublot, (string) ($sublot['text'] ?? ''));
        }

        return $targets;
    }
}
