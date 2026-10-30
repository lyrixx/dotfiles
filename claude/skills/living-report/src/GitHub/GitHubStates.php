<?php

namespace LivingReport\GitHub;

use LivingReport\Edit\IssueState;
use LivingReport\Edit\PullRequestState;
use LivingReport\Model\PullRequestStatus;

/**
 * Reads the state of many pull requests and issues in one GraphQL call, through the gh command
 * line.
 */
final readonly class GitHubStates
{
    /**
     * @param list<int> $numbers
     *
     * @return array<int, PullRequestState|IssueState>
     */
    public function fetch(string $repository, array $numbers): array
    {
        if ([] === $numbers) {
            return [];
        }

        [$owner, $name] = explode('/', $repository, 2);
        // Aliased: "state" is a PullRequestState on one and an IssueState on the other
        $fields = implode("\n", array_map(static fn (int $number): string => \sprintf('n%d: issueOrPullRequest(number: %d) { ... on PullRequest { number pullRequestState: state isDraft mergedAt closedAt } ... on Issue { number issueState: state stateReason closedAt } }', $number, $number), $numbers));
        $query = \sprintf('query { repository(owner: %s, name: %s) { %s } }', json_encode($owner), json_encode($name), $fields);

        $process = proc_open(['gh', 'api', 'graphql', '-f', 'query=' . $query], [1 => ['pipe', 'w'], 2 => ['pipe', 'w']], $pipes);
        if (false === $process) {
            throw new \RuntimeException('Could not run gh.');
        }
        $output = stream_get_contents($pipes[1]);
        $errors = stream_get_contents($pipes[2]);
        if (0 !== proc_close($process)) {
            throw new \RuntimeException(\sprintf('gh could not read the pull requests and issues: %s', trim((string) $errors)));
        }

        $states = [];
        foreach (json_decode((string) $output, true, flags: \JSON_THROW_ON_ERROR)['data']['repository'] ?? [] as $reference) {
            if (!isset($reference['number'])) {
                continue;
            }
            $closedAt = $reference['mergedAt'] ?? $reference['closedAt'];
            $closedAt = null === $closedAt ? null : new \DateTimeImmutable($closedAt);
            $states[$reference['number']] = isset($reference['issueState'])
                ? new IssueState('CLOSED' === $reference['issueState'], 'COMPLETED' === $reference['stateReason'], $closedAt)
                : new PullRequestState(
                    match (true) {
                        'MERGED' === $reference['pullRequestState'] => PullRequestStatus::Merged,
                        'CLOSED' === $reference['pullRequestState'] => PullRequestStatus::Closed,
                        $reference['isDraft'] => PullRequestStatus::Draft,
                        default => PullRequestStatus::Open,
                    },
                    $closedAt,
                );
        }

        return $states;
    }
}
