<?php

namespace LivingReport;

use LivingReport\Markdown\FrenchTypography;
use LivingReport\Markdown\ReportMarkdown;
use LivingReport\Model\Chip;
use LivingReport\Model\Report;
use Twig\Environment;
use Twig\Loader\FilesystemLoader;
use Twig\TwigFilter;
use Twig\TwigFunction;

final readonly class ReportRenderer
{
    public function render(Report $report, \DateTimeImmutable $updatedAt): string
    {
        $markdown = new ReportMarkdown($report->github, $report->anchors());

        $twig = new Environment(new FilesystemLoader(\dirname(__DIR__) . '/templates'), ['strict_variables' => true, 'autoescape' => 'html']);
        $twig->addFilter(new TwigFilter('md', $markdown->block(...), ['is_safe' => ['html']]));
        $twig->addFilter(new TwigFilter('md_inline', $markdown->inline(...), ['is_safe' => ['html']]));
        $twig->addFilter(new TwigFilter('typo', FrenchTypography::apply(...)));
        $twig->addFilter(new TwigFilter('date_long', FrenchDate::long(...)));
        $twig->addFilter(new TwigFilter('date_short', FrenchDate::short(...)));
        $twig->addFilter(new TwigFilter('date_day_month', FrenchDate::dayMonth(...)));
        $twig->addFunction(new TwigFunction('date_range', FrenchDate::range(...)));
        $twig->addFunction(new TwigFunction('chip_link', static fn (Chip $chip): ?string => $chip->link($report->github)));

        return $twig->render('report.html.twig', ['report' => $report, 'updated_at' => $updatedAt]);
    }
}
