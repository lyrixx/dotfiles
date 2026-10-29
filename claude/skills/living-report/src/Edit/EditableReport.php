<?php

namespace LivingReport\Edit;

use LivingReport\Yaml\ReportYamlWriter;
use Symfony\Component\Yaml\Yaml;

/**
 * The YAML of a report as data, for the edit commands: state changes are made here, the prose is
 * edited by hand.
 */
final class EditableReport
{
    // "Draft #1234", "Mergée #1211": the chips that tell the state of a pull request
    private const string PULL_REQUEST_CHIP = '/^(Draft|Ouverte|Mergée|Fermée) #(\d+)$/u';

    /**
     * @param array<mixed> $data
     */
    private function __construct(
        private readonly string $path,
        private array $data,
    ) {
    }

    public static function load(string $path): self
    {
        $data = Yaml::parseFile($path, Yaml::PARSE_DATETIME);
        if (!\is_array($data)) {
            throw new \RuntimeException(\sprintf('The report "%s" must be a YAML mapping.', $path));
        }

        return new self($path, $data);
    }

    public function save(): void
    {
        file_put_contents($this->path, new ReportYamlWriter()->dump($this->data));
    }

    /**
     * D14 or 1a (a number), #1234 (the card of a pull request), an id, or else a piece of the
     * title or text, which must match one element only.
     */
    public function find(string $selector): Target
    {
        $targets = $this->targets();
        $byKey = array_values(array_filter($targets, fn (Target $target): bool => \in_array($selector, $this->keys($target), true)));
        if (1 === \count($byKey)) {
            return $byKey[0];
        }

        $needle = mb_strtolower($selector);
        $byText = array_values(array_filter($targets, static fn (Target $target): bool => str_contains(mb_strtolower($target->label), $needle)));
        if (1 === \count($byText)) {
            return $byText[0];
        }

        $candidates = [] === $byKey ? $byText : $byKey;
        if ([] === $candidates) {
            throw new \RuntimeException(\sprintf('Nothing in the report matches "%s".', $selector));
        }

        throw new \RuntimeException(\sprintf("\"%s\" matches %d elements, be more precise:\n%s", $selector, \count($candidates), implode("\n", array_map(fn (Target $target): string => '  - ' . $target->describe() . $this->hint($target), $candidates))));
    }

    /**
     * @return array<mixed>
     */
    public function element(Target $target): array
    {
        return $this->get($target->path);
    }

    /**
     * @param ?array{string, string} $note Label and text
     */
    public function markDone(Target $target, ?array $note): void
    {
        $element = $this->get($target->path);
        if (TargetKind::Card === $target->kind && isset($element['status'])) {
            throw new \RuntimeException(\sprintf('The %s follows its pull request: run report:sync-prs, or change its status by hand.', $target->describe()));
        }

        $this->set([...$target->path, 'done'], true);
        if (null !== $note) {
            $this->addNote($target, ...$note);
        }
    }

    public function addNote(Target $target, string $label, string $text): void
    {
        if (!$target->kind->hasNotes()) {
            throw new \RuntimeException(\sprintf('A %s has no notes: edit its text by hand.', $target->kind->label()));
        }

        $notes = $this->get([...$target->path, 'notes']) ?? [];
        $notes[] = [$label => $text];
        $this->set([...$target->path, 'notes'], $notes);
    }

    /**
     * Appends a sentence to the history entry of that day, created in date order if missing.
     */
    public function addHistory(\DateTimeImmutable $date, string $text): void
    {
        $history = $this->data['history'] ?? [];
        foreach ($history as $index => $entry) {
            if ($entry['date'] instanceof \DateTimeInterface && $entry['date']->format('Y-m-d') === $date->format('Y-m-d')) {
                $history[$index]['text'] = rtrim($entry['text']) . ' ' . $text;
                $this->data['history'] = $history;

                return;
            }
        }

        $history[] = ['date' => $date, 'text' => $text];
        usort($history, static fn (array $a, array $b): int => $a['date'] <=> $b['date']);
        $this->data['history'] = $history;
    }

    /**
     * @return list<int>
     */
    public function pullRequestNumbers(): array
    {
        $numbers = [];
        foreach ($this->targets() as $target) {
            $element = $this->get($target->path);
            if (TargetKind::Card === $target->kind && isset($element['pr']) && \in_array($element['status'] ?? null, ['draft', 'open', 'merged', 'closed'], true)) {
                $numbers[] = $element['pr'];
            }
        }
        $this->walkChips(static function (array $chip) use (&$numbers): array {
            if (preg_match(self::PULL_REQUEST_CHIP, (string) current($chip), $match)) {
                $numbers[] = (int) $match[2];
            }

            return $chip;
        });

        $numbers = array_values(array_unique($numbers));
        sort($numbers);

        return $numbers;
    }

    /**
     * @param array<int, PullRequestState> $states
     *
     * @return list<string> What changed, to review the prose around it
     */
    public function applyPullRequestStates(array $states): array
    {
        $changes = [];
        foreach ($this->targets() as $target) {
            $element = $this->get($target->path);
            $state = $states[$element['pr'] ?? 0] ?? null;
            if (TargetKind::Card !== $target->kind || null === $state || !\in_array($element['status'] ?? null, ['draft', 'open', 'merged', 'closed'], true) || $element['status'] === $state->status->value) {
                continue;
            }
            $changes[] = \sprintf('#%d %s → %s%s : %s', $element['pr'], $element['status'], $state->status->value, $state->when(), $target->describe());
            $this->set([...$target->path, 'status'], $state->status->value);
        }

        $this->walkChips(static function (array $chip, string $where) use ($states, &$changes): array {
            if (!preg_match(self::PULL_REQUEST_CHIP, (string) current($chip), $match) || null === $state = $states[(int) $match[2]] ?? null) {
                return $chip;
            }
            $new = $state->status->chip((int) $match[2]);
            if ($new->text === current($chip)) {
                return $chip;
            }
            $changes[] = \sprintf('#%s %s → %s%s : puce de %s', $match[2], current($chip), $new->text, $state->when(), $where);

            return [$new->tone->value => $new->text, ...array_diff_key($chip, array_flip(['crit', 'info', 'muted', 'ok', 'warn']))];
        });

        return $changes;
    }

    /**
     * @return list<Target>
     */
    private function targets(): array
    {
        $targets = [];
        foreach ($this->data['sections'] ?? [] as $s => $section) {
            $base = ['sections', $s];
            foreach ($section['items'] ?? [] as $i => $item) {
                $targets[] = new Target([...$base, 'items', $i], TargetKind::ChecklistItem, (string) ($item['title'] ?? ''));
            }
            foreach ($section['rows'] ?? [] as $i => $row) {
                $targets[] = new Target([...$base, 'rows', $i], TargetKind::VigilanceRow, (string) ($row['text'] ?? ''));
            }
            foreach ($section['cards'] ?? [] as $i => $card) {
                array_push($targets, ...$this->cardTargets([...$base, 'cards', $i], $card));
            }
            foreach ($section['blocks'] ?? [] as $b => $block) {
                foreach ($block['todo'] ?? [] as $i => $item) {
                    $targets[] = new Target([...$base, 'blocks', $b, 'todo', $i], TargetKind::TodoItem, (string) ($item['text'] ?? ''));
                }
                foreach ($block['cards'] ?? [] as $i => $card) {
                    array_push($targets, ...$this->cardTargets([...$base, 'blocks', $b, 'cards', $i], $card));
                }
            }
        }

        return $targets;
    }

    /**
     * @param list<int|string> $path
     * @param array<mixed>     $card
     *
     * @return list<Target>
     */
    private function cardTargets(array $path, array $card): array
    {
        $targets = [new Target($path, TargetKind::Card, (string) ($card['title'] ?? ''))];
        foreach ($card['sublots'] ?? [] as $i => $sublot) {
            $targets[] = new Target([...$path, 'sublots', $i], TargetKind::Sublot, (string) ($sublot['text'] ?? ''));
        }

        return $targets;
    }

    /**
     * @return list<string>
     */
    private function keys(Target $target): array
    {
        $element = $this->get($target->path);

        return array_values(array_filter([
            isset($element['id']) ? (string) $element['id'] : null,
            isset($element['num']) ? (string) $element['num'] : null,
            TargetKind::Card === $target->kind && isset($element['pr']) ? '#' . $element['pr'] : null,
        ]));
    }

    private function hint(Target $target): string
    {
        $keys = $this->keys($target);

        return [] === $keys ? '' : ' (' . implode(', ', $keys) . ')';
    }

    /**
     * Calls $update on every chip of the report, with where it sits, and keeps what it returns.
     *
     * @param \Closure(array<string, string>, string): array<string, string> $update
     */
    private function walkChips(\Closure $update): void
    {
        foreach ($this->targets() as $target) {
            $element = $this->get($target->path);
            foreach (['chips', 'status', 'type'] as $key) {
                $chips = $element[$key] ?? null;
                if (!\is_array($chips)) {
                    continue;
                }
                if ('type' === $key) {
                    $this->set([...$target->path, $key], $update($chips, $target->describe()));
                    continue;
                }
                foreach ($chips as $index => $chip) {
                    if (\is_array($chip)) {
                        $this->set([...$target->path, $key, $index], $update($chip, $target->describe()));
                    }
                }
            }
        }
    }

    /**
     * @param list<int|string> $path
     */
    private function get(array $path): mixed
    {
        $value = $this->data;
        foreach ($path as $key) {
            if (!\is_array($value) || !\array_key_exists($key, $value)) {
                return null;
            }
            $value = $value[$key];
        }

        return $value;
    }

    /**
     * @param list<int|string> $path
     */
    private function set(array $path, mixed $value): void
    {
        $node = &$this->data;
        foreach ($path as $key) {
            $node = &$node[$key];
        }
        $node = $value;
    }
}
