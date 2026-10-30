<?php

defined('CASTOR_USE_CHDIR') || define('CASTOR_USE_CHDIR', true);

use Castor\Attribute\AsArgument;
use Castor\Attribute\AsOption;
use Castor\Attribute\AsTask;
use Castor\Exception\ProblemException;
use LivingReport\Edit\EditableReport;
use LivingReport\GitHub\GitHubStates;
use LivingReport\OpenElements;
use LivingReport\ReportBuilder;
use LivingReport\ReportLoader;
use LivingReport\Yaml\ReportYamlWriter;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Yaml\Yaml;

use function Castor\guard_min_version;
use function Castor\io;

// Before 1.9.1, the autoload of castor.composer.json was resolved from the directory castor runs
// from, not from this one: "Class LivingReport\… not found" as soon as it runs from elsewhere.
guard_min_version('1.9.1');

#[AsTask(namespace: 'report', description: 'Builds the HTML of a living report from its YAML file')]
function build(
    #[AsArgument(description: 'Absolute path of the YAML file')]
    string $file,
    #[AsOption(description: 'Absolute path of the HTML file, next to the YAML by default')]
    ?string $output = null,
): void {
    living_report([$file, $output], static fn (): array => [new ReportBuilder()->build($file, $output)]);
}

#[AsTask(namespace: 'report', description: 'Prints the element a selector finds, to check it before an edit')]
function find(
    #[AsArgument(description: 'Absolute path of the YAML file')]
    string $file,
    #[AsArgument(description: 'D14, 1a, #1234, an id, or a piece of its title or text')]
    string $selector,
): void {
    living_report([$file], static function () use ($file, $selector): array {
        $report = EditableReport::load($file);
        $target = $report->find($selector);

        return [$target->describe(), new ReportYamlWriter()->dump($report->element($target))];
    });
}

#[AsTask(namespace: 'report', description: 'Prints the next step, what we wait for, and every open element with the counters of the HTML')]
function open(
    #[AsArgument(description: 'Absolute path of the YAML file')]
    string $file,
): void {
    living_report([$file], static fn (): array => [new OpenElements()->render(new ReportLoader()->load($file), today())]);
}

#[AsTask(namespace: 'report', description: 'Marks an element done, with an optional note, then builds the HTML')]
function done(
    #[AsArgument(description: 'Absolute path of the YAML file')]
    string $file,
    #[AsArgument(description: 'D14, 1a, #1234, an id, or a piece of its title or text')]
    string $selector,
    #[AsOption(description: '"Fait: Corrigé par #1218." (label "Fait" when none is given); the last paragraph of a to-do')]
    ?string $note = null,
): void {
    edit_report($file, static function (EditableReport $report) use ($selector, $note): array {
        $target = $report->find($selector);

        return ['Fait : ' . $target->describe(), ...$report->markDone($target, null === $note ? null : parse_note($note, 'Fait'))];
    });
}

#[AsTask(namespace: 'report', description: 'Adds a note under an element, then builds the HTML')]
function note(
    #[AsArgument(description: 'Absolute path of the YAML file')]
    string $file,
    #[AsArgument(description: 'D14, 1a, #1234, an id, or a piece of its title or text')]
    string $selector,
    #[AsArgument(description: '"Mesuré: Le 28, sur 90 jours…" (label "Le <day>" when none is given)')]
    string $note,
): void {
    edit_report($file, static function (EditableReport $report) use ($selector, $note): array {
        $target = $report->find($selector);
        $report->addNote($target, ...parse_note($note, 'Le ' . today()->format('j')));

        return ['Note ajoutée : ' . $target->describe()];
    });
}

#[AsTask(namespace: 'report', description: 'Appends a sentence to the history entry of a day, then builds the HTML')]
function history(
    #[AsArgument(description: 'Absolute path of the YAML file')]
    string $file,
    #[AsArgument(description: 'The sentence, in Markdown')]
    string $text,
    #[AsOption(description: 'The day, 2026-10-28; today by default')]
    ?string $date = null,
): void {
    edit_report($file, static function (EditableReport $report) use ($text, $date): array {
        $report->addHistory(null === $date ? today() : new DateTimeImmutable($date), $text);

        return ['Historique complété.'];
    });
}

#[AsTask(namespace: 'report', description: 'Reads the pull requests and issues of the report on GitHub, closes the to-dos they finish, then builds the HTML')]
function sync(
    #[AsArgument(description: 'Absolute path of the YAML file')]
    string $file,
): void {
    edit_report($file, static function (EditableReport $report) use ($file): array {
        $repository = Yaml::parseFile($file)['github'] ?? throw new RuntimeException('The report has no "github" repository.');
        $changes = $report->sync(new GitHubStates()->fetch($repository, $report->gitHubNumbers()));

        return [] === $changes ? ['Rien n\'a changé sur GitHub.'] : ['Ce qui a changé, ce que ça a fermé ou débloqué, puis la prose encore ouverte qui le cite, à relire (avec la meta de la carte et l\'historique) :', ...array_map(static fn (string $change): string => '  - ' . $change, $changes)];
    });
}

#[AsTask(namespace: 'report', description: 'Lists where the prose still open says a text: what to review once it changed outside the report')]
function mentions(
    #[AsArgument(description: 'Absolute path of the YAML file')]
    string $file,
    #[AsArgument(description: '"log injector", "#1234", "D14"…, case aside')]
    string $text,
): void {
    living_report([$file], static function () use ($file, $text): array {
        $places = EditableReport::load($file)->mentions($text);

        return [] === $places ? [\sprintf('Rien d\'ouvert ne dit « %s ».', $text)] : array_map(static fn (string $place): string => '  - ' . $place, $places);
    });
}

/**
 * Saves the YAML once $edit changed it, then builds the HTML.
 *
 * @param Closure(EditableReport): list<string> $edit Returns what changed
 */
function edit_report(string $file, Closure $edit): void
{
    living_report([$file], static function () use ($file, $edit): array {
        $report = EditableReport::load($file);
        $messages = $edit($report);
        $report->save();

        return [...$messages, new ReportBuilder()->build($file)];
    });
}

/**
 * Prints the lines $command returns. Its RuntimeException is a mistake in the YAML or in the
 * arguments: its message is enough, without the trace.
 *
 * @param list<?string>           $paths
 * @param Closure(): list<string> $command
 */
function living_report(array $paths, Closure $command): void
{
    foreach (array_filter($paths) as $path) {
        if (!str_starts_with($path, '/')) {
            throw new ProblemException(sprintf('Give an absolute path, not "%s": castor may run from another directory than yours.', $path));
        }
    }

    try {
        $lines = $command();
    } catch (RuntimeException $e) {
        throw new ProblemException($e->getMessage(), previous: $e);
    }

    io()->writeln($lines, OutputInterface::OUTPUT_RAW);
}

/**
 * "Fait: Corrigé par #1218." gives the label and the text; without "<label>: " (French puts a
 * space before its colon, a label does not), the default label.
 *
 * @return array{string, string}
 */
function parse_note(string $note, string $defaultLabel): array
{
    return preg_match('/^([^:\s][^:]{0,29}(?<!\s)): (.+)$/su', $note, $match) ? [$match[1], $match[2]] : [$defaultLabel, $note];
}

function today(): DateTimeImmutable
{
    return new DateTimeImmutable('today', new DateTimeZone('Europe/Paris'));
}
