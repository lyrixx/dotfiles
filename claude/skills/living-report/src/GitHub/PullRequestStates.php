<?php

namespace LivingReport\GitHub;

use LivingReport\Edit\PullRequestState;
use LivingReport\Model\PullRequestStatus;

/**
 * Reads the state of many pull requests in one GraphQL call, through the gh command line.
 */
final readonly class PullRequestStates
{
    /**
     * @param list<int> $numbers
     *
     * @return array<int, PullRequestState>
     */
    public function fetch(string $repository, array $numbers): array
    {
        if ([] === $numbers) {
            return [];
        }

        [$owner, $name] = explode('/', $repository, 2);
        $fields = implode("\n", array_map(static fn (int $number): string => \sprintf('pr%d: issueOrPullRequest(number: %d) { ... on PullRequest { number state isDraft mergedAt closedAt } }', $number, $number), $numbers));
        $query = \sprintf('query { repository(owner: %s, name: %s) { %s } }', json_encode($owner), json_encode($name), $fields);

        $process = proc_open(['gh', 'api', 'graphql', '-f', 'query=' . $query], [1 => ['pipe', 'w'], 2 => ['pipe', 'w']], $pipes);
        if (false === $process) {
            throw new \RuntimeException('Could not run gh.');
        }
        $output = stream_get_contents($pipes[1]);
        $errors = stream_get_contents($pipes[2]);
        if (0 !== proc_close($process)) {
            throw new \RuntimeException(\sprintf('gh could not read the pull requests: %s', trim((string) $errors)));
        }

        $states = [];
        foreach (json_decode((string) $output, true, flags: \JSON_THROW_ON_ERROR)['data']['repository'] ?? [] as $pullRequest) {
            if (!isset($pullRequest['number'])) {
                continue;
            }
            $states[$pullRequest['number']] = new PullRequestState(
                match (true) {
                    'MERGED' === $pullRequest['state'] => PullRequestStatus::Merged,
                    'CLOSED' === $pullRequest['state'] => PullRequestStatus::Closed,
                    $pullRequest['isDraft'] => PullRequestStatus::Draft,
                    default => PullRequestStatus::Open,
                },
                null === ($closedAt = $pullRequest['mergedAt'] ?? $pullRequest['closedAt']) ? null : new \DateTimeImmutable($closedAt),
            );
        }

        return $states;
    }
}
