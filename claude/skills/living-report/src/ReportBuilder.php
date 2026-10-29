<?php

namespace LivingReport;

final readonly class ReportBuilder
{
    /**
     * @return string The path of the HTML
     */
    public function build(string $source, ?string $target = null): string
    {
        $target ??= preg_replace('/\.ya?ml$/', '', $source) . '.html';
        $report = new ReportLoader()->load($source);
        file_put_contents($target, new ReportRenderer()->render($report, new \DateTimeImmutable('now', new \DateTimeZone('Europe/Paris'))));

        return $target;
    }
}
