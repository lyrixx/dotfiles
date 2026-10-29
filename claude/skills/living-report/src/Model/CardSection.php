<?php

namespace LivingReport\Model;

final readonly class CardSection extends Section
{
    public const string AUTO_COUNT = 'auto';

    /**
     * @param ?array{string, string} $tally
     * @param list<Card>             $cards
     */
    public function __construct(
        string $id,
        string $title,
        ?string $intro,
        private ?string $countFormat,
        ?array $tally,
        private array $cards,
    ) {
        parent::__construct($id, $title, $intro, self::AUTO_COUNT === $countFormat ? null : $countFormat, $tally);
    }

    public function type(): string
    {
        return 'cards';
    }

    /**
     * @return list<Card>
     */
    public function cards(): array
    {
        return self::openFirst($this->cards, static fn (Card $card): bool => $card->isDone());
    }

    /**
     * "auto" counts the branches by state: "1 ouverte · 16 mergées, 1 fermée, 1 arrêtée".
     */
    public function count(): ?string
    {
        if (self::AUTO_COUNT !== $this->countFormat) {
            return parent::count();
        }

        $byStatus = [];
        foreach ($this->cards as $card) {
            if (null !== $card->status) {
                $status = PullRequestStatus::Draft === $card->status ? PullRequestStatus::Open : $card->status;
                $byStatus[$status->value] = ($byStatus[$status->value] ?? 0) + 1;
            }
        }

        $words = static fn (array $statuses): array => array_values(array_filter(array_map(
            static fn (PullRequestStatus $status): ?string => isset($byStatus[$status->value]) ? $byStatus[$status->value] . ' ' . $status->word($byStatus[$status->value]) : null,
            $statuses,
        )));

        $open = [($byStatus['open'] ?? 0) . ' ' . PullRequestStatus::Open->word($byStatus['open'] ?? 0), ...$words([PullRequestStatus::Local])];
        $done = $words([PullRequestStatus::Merged, PullRequestStatus::Closed, PullRequestStatus::Stopped]);

        return implode(', ', $open) . ([] === $done ? '' : ' · ' . implode(', ', $done));
    }

    protected function totalCount(): int
    {
        return \count($this->cards);
    }

    protected function doneCount(): int
    {
        return \count(array_filter($this->cards, static fn (Card $card): bool => $card->isDone()));
    }
}
