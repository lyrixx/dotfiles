<?php

namespace LivingReport\Edit;

use LivingReport\FrenchDate;
use LivingReport\Link\ReportIndex;
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
     * @param array<mixed>          $data
     * @param array<string, Target> $blockedAtLoad The open to-dos an element of their `after` holds back
     */
    private function __construct(
        private readonly string $path,
        private array $data,
        private readonly array $blockedAtLoad,
    ) {
    }

    public static function load(string $path): self
    {
        $data = Yaml::parseFile($path, Yaml::PARSE_DATETIME);
        if (!\is_array($data)) {
            throw new \RuntimeException(\sprintf('The report "%s" must be a YAML mapping.', $path));
        }

        return new self($path, $data, self::blocked($data));
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
        $targets = Target::all($this->data);
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
        return $target->in($this->data);
    }

    /**
     * @param ?array{string, string} $note Label and text
     *
     * @return list<string> What it closed or unblocked in turn
     */
    public function markDone(Target $target, ?array $note): array
    {
        $element = $target->in($this->data);
        if (TargetKind::Card === $target->kind && isset($element['status'])) {
            throw new \RuntimeException(\sprintf('The %s follows its pull request: run report:sync, or change its status by hand.', $target->describe()));
        }

        $this->set([...$target->path, 'done'], true);
        if (null !== $note) {
            $this->addNote($target, ...$note);
        }

        return $this->settle(null)[0];
    }

    public function addNote(Target $target, string $label, string $text): void
    {
        // A to-do has no notes: the note becomes the last paragraph of its text
        if (TargetKind::TodoItem === $target->kind) {
            $this->set([...$target->path, 'text'], \sprintf("%s\n\n%s : %s", rtrim((string) $this->get([...$target->path, 'text'])), $label, $text));

            return;
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
     * The pull requests the cards and chips follow, and the pull requests and issues the open
     * to-dos wait for.
     *
     * @return list<int>
     */
    public function gitHubNumbers(): array
    {
        $numbers = [];
        foreach (Target::all($this->data) as $target) {
            $element = $target->in($this->data);
            if (TargetKind::Card === $target->kind && isset($element['pr']) && \in_array($element['status'] ?? null, ['draft', 'open', 'merged', 'closed'], true)) {
                $numbers[] = $element['pr'];
            }
            if (TargetKind::TodoItem === $target->kind && true !== ($element['done'] ?? false)) {
                array_push($numbers, ...array_filter(array_map(ReportIndex::gitHubNumber(...), ReportIndex::keys($element['done_when'] ?? null))));
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
     * Applies what GitHub says: the cards and chips follow their pull request, the to-dos it
     * finishes close.
     *
     * @param array<int, PullRequestState|IssueState> $states
     *
     * @return list<string> What changed, what it closed or unblocked in turn, then the open prose that
     *                      cites it, to review
     */
    public function sync(array $states): array
    {
        [$changes, $changed] = $this->applyPullRequestStates(array_filter($states, static fn (PullRequestState|IssueState $state): bool => $state instanceof PullRequestState));
        [$settled, $closing] = $this->settle($states);

        return [...$changes, ...$settled, ...$this->openMentions(array_values(array_unique([...$changed, ...$closing])))];
    }

    /**
     * Where the prose still open says this text, case aside: what to review once it changed
     * outside the report (a deployment, a decision taken in a conversation).
     *
     * @return list<string>
     */
    public function mentions(string $text): array
    {
        return array_map(static fn (array $place): string => $place[0], $this->openProse('/' . preg_quote($text, '/') . '/iu'));
    }

    /**
     * @param array<int, PullRequestState> $states
     *
     * @return array{list<string>, list<int>} What changed, and the numbers that changed
     */
    private function applyPullRequestStates(array $states): array
    {
        $changes = [];
        $changed = [];
        foreach (Target::all($this->data) as $target) {
            $element = $target->in($this->data);
            $state = $states[$element['pr'] ?? 0] ?? null;
            if (TargetKind::Card !== $target->kind || null === $state || !\in_array($element['status'] ?? null, ['draft', 'open', 'merged', 'closed'], true) || $element['status'] === $state->status->value) {
                continue;
            }
            $changes[] = \sprintf('#%d %s → %s%s : %s', $element['pr'], $element['status'], $state->status->value, $state->when(), $target->describe());
            $changed[] = $element['pr'];
            $this->set([...$target->path, 'status'], $state->status->value);
        }

        $this->walkChips(static function (array $chip, string $where) use ($states, &$changes, &$changed): array {
            if (!preg_match(self::PULL_REQUEST_CHIP, (string) current($chip), $match) || null === $state = $states[(int) $match[2]] ?? null) {
                return $chip;
            }
            $new = $state->status->chip((int) $match[2]);
            if ($new->text === current($chip)) {
                return $chip;
            }
            $changes[] = \sprintf('#%s %s → %s%s : puce de %s', $match[2], current($chip), $new->text, $state->when(), $where);
            $changed[] = (int) $match[2];

            return [$new->tone->value => $new->text, ...array_diff_key($chip, array_flip(['crit', 'info', 'muted', 'ok', 'warn']))];
        });

        return [$changes, $changed];
    }

    /**
     * Closes the open to-dos whose done_when is all finished, until none is left (a to-do it
     * closes may finish another), takes the to-dos done out of the boxes under the lede, and says
     * what it did, what it unblocked, and what still needs a hand.
     *
     * @param ?array<int, PullRequestState|IssueState> $states Null when GitHub was not read: its #1234 stay unfinished
     *
     * @return array{list<string>, list<int>} The messages, and the numbers that closed a to-do
     */
    private function settle(?array $states): array
    {
        $today = new \DateTimeImmutable('now', new \DateTimeZone('Europe/Paris'));
        $messages = [];
        $closing = [];
        do {
            $closed = false;
            $index = ReportIndex::of($this->data);
            foreach ($this->openTodos() as $target) {
                $keys = ReportIndex::keys($target->in($this->data)['done_when'] ?? null);
                $reasons = [];
                $elements = [];
                foreach ($keys as $key) {
                    $number = ReportIndex::gitHubNumber($key);
                    $state = null === $number ? null : $states[$number] ?? null;
                    $element = null === $number ? $index->find($key) : null;
                    if (!(null === $number ? ($element?->finished ?? false) : ($state?->isFinished() ?? false))) {
                        continue 2;
                    }
                    if (null === $state) {
                        $elements[] = $element->name;
                    } else {
                        $reasons[] = $state->describe((int) $number);
                    }
                }
                if ([] !== $elements) {
                    $reasons[] = \sprintf('%s, le %s', implode(', ', $elements), FrenchDate::dayMonth($today));
                }
                if ([] === $reasons) {
                    continue;
                }
                $this->set([...$target->path, 'done'], true);
                $this->addNote($target, 'Fait', implode(' ; ', $reasons) . '.');
                $messages[] = \sprintf('Fait (%s) : %s', implode(' ; ', $reasons), $target->describe());
                array_push($closing, ...array_filter(array_map(ReportIndex::gitHubNumber(...), $keys)));
                $closed = true;
            }
        } while ($closed);

        foreach ($this->openTodos() as $target) {
            foreach (array_filter(array_map(ReportIndex::gitHubNumber(...), ReportIndex::keys($target->in($this->data)['done_when'] ?? null))) as $number) {
                $state = $states[$number] ?? null;
                if (null !== $states && null === $state) {
                    $messages[] = \sprintf('#%d introuvable sur GitHub : %s', $number, $target->describe());
                } elseif (null !== $state && $state->isClosed() && !$state->isFinished()) {
                    $messages[] = \sprintf('%s, à revoir : %s reste ouvert', $state->describe($number), $target->describe());
                }
            }
        }

        foreach (array_diff_key($this->blockedAtLoad, self::blocked($this->data)) as $target) {
            $element = $target->in($this->data);
            if (true === ($element['done'] ?? false)) {
                continue;
            }
            $notBefore = $element['not_before'] ?? null;
            $messages[] = $notBefore instanceof \DateTimeInterface && $notBefore->format('Y-m-d') > $today->format('Y-m-d')
                ? \sprintf('Débloqué, mais pas avant le %s : %s', FrenchDate::dayMonth(\DateTimeImmutable::createFromInterface($notBefore)), $target->describe())
                : 'Débloqué : ' . $target->describe();
        }

        return [[...$messages, ...$this->clearStatus()], $closing];
    }

    /**
     * Takes the lines of the to-dos done out of the boxes under the lede.
     *
     * @return list<string>
     */
    private function clearStatus(): array
    {
        $index = ReportIndex::of($this->data);
        $isDone = static fn (mixed $line): bool => \is_array($line) && isset($line['todo']) && true === $index->find((string) $line['todo'])?->finished;

        $messages = [];
        $waiting = [];
        foreach ($this->data['status']['waiting'] ?? [] as $line) {
            if ($isDone($line)) {
                $messages[] = 'Retiré de « En attente » : ' . $line['text'];
            } else {
                $waiting[] = $line;
            }
        }
        if (isset($this->data['status']['waiting'])) {
            $this->data['status']['waiting'] = $waiting;
        }
        if ($isDone($this->data['status']['next'] ?? null)) {
            $messages[] = 'La prochaine étape est faite, à remplacer dans status.next : ' . $this->data['status']['next']['text'];
            unset($this->data['status']['next']);
        }

        return $messages;
    }

    /**
     * @return list<Target>
     */
    private function openTodos(): array
    {
        return array_values(array_filter(Target::all($this->data), fn (Target $target): bool => TargetKind::TodoItem === $target->kind && true !== ($target->in($this->data)['done'] ?? false)));
    }

    /**
     * The open to-dos an element of their `after` holds back, by their path.
     *
     * @param array<mixed> $data
     *
     * @return array<string, Target>
     */
    private static function blocked(array $data): array
    {
        $index = ReportIndex::of($data);
        $blocked = [];
        foreach (Target::all($data) as $target) {
            $element = $target->in($data);
            if (TargetKind::TodoItem !== $target->kind || true === ($element['done'] ?? false)) {
                continue;
            }
            foreach (ReportIndex::keys($element['after'] ?? null) as $key) {
                if (!($index->find($key)?->finished ?? false)) {
                    $blocked[implode('/', $target->path)] = $target;
                }
            }
        }

        return $blocked;
    }

    /**
     * Where the prose still open cites one of these pull requests or issues. A "Relire et merger
     * #1234" left open after its merge is found here.
     *
     * @param list<int> $numbers
     *
     * @return list<string>
     */
    private function openMentions(array $numbers): array
    {
        if ([] === $numbers) {
            return [];
        }

        return array_map(static fn (array $place): string => \sprintf('cite %s : %s', implode(', ', $place[1]), $place[0]), $this->openProse('/#(' . implode('|', $numbers) . ')(?!\d)/'));
    }

    /**
     * Where the prose still open matches: the boxes under the lede, the intros, the texts of the
     * blocks and the open elements.
     *
     * @return list<array{string, list<string>}> Where, and what matched
     */
    private function openProse(string $pattern): array
    {
        $places = [];
        foreach (['done' => 'Fait', 'next' => 'Prochaine étape', 'waiting' => 'En attente'] as $key => $box) {
            $places[] = [\sprintf('encadré « %s »', $box), $this->data['status'][$key] ?? null];
        }
        foreach ($this->data['sections'] ?? [] as $section) {
            $places[] = [\sprintf('intro de « %s »', $section['title'] ?? ''), $section['intro'] ?? null];
            foreach ($section['blocks'] ?? [] as $block) {
                $places[] = [\sprintf('bloc « %s »', $block['title'] ?? ''), [$block['text'] ?? null, $block['progress'] ?? null]];
            }
        }
        foreach (Target::all($this->data) as $target) {
            $element = $target->in($this->data);
            if (true === ($element['done'] ?? false) || true === ($element['dropped'] ?? false) || \in_array($element['status'] ?? null, ['merged', 'closed', 'stopped'], true)) {
                continue;
            }
            // Its sublots are targets of their own
            unset($element['sublots']);
            $places[] = [$target->describe(), $element];
        }

        $matching = [];
        foreach ($places as [$where, $prose]) {
            if (preg_match_all($pattern, json_encode($prose, \JSON_UNESCAPED_UNICODE | \JSON_UNESCAPED_SLASHES | \JSON_THROW_ON_ERROR), $matches)) {
                $matching[] = [$where, array_values(array_unique($matches[0]))];
            }
        }

        return $matching;
    }

    /**
     * @return list<string>
     */
    private function keys(Target $target): array
    {
        $element = $target->in($this->data);

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
        foreach (Target::all($this->data) as $target) {
            $element = $target->in($this->data);
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
