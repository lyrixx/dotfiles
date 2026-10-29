<?php

namespace LivingReport;

final class FrenchDate
{
    private const array MONTHS = ['janvier', 'février', 'mars', 'avril', 'mai', 'juin', 'juillet', 'août', 'septembre', 'octobre', 'novembre', 'décembre'];
    private const array SHORT_MONTHS = ['janv.', 'févr.', 'mars', 'avr.', 'mai', 'juin', 'juil.', 'août', 'sept.', 'oct.', 'nov.', 'déc.'];
    private const array DAYS = ['dimanche', 'lundi', 'mardi', 'mercredi', 'jeudi', 'vendredi', 'samedi'];

    /**
     * "mercredi 28 octobre 2026"
     */
    public static function long(\DateTimeImmutable $date): string
    {
        return self::DAYS[(int) $date->format('w')] . ' ' . self::dayMonth($date) . ' ' . $date->format('Y');
    }

    /**
     * "28 oct."
     */
    public static function short(\DateTimeImmutable $date): string
    {
        return self::day($date) . ' ' . self::SHORT_MONTHS[(int) $date->format('n') - 1];
    }

    /**
     * "28 octobre"
     */
    public static function dayMonth(\DateTimeImmutable $date): string
    {
        return self::day($date) . ' ' . self::MONTHS[(int) $date->format('n') - 1];
    }

    /**
     * "du 20 au 28 octobre", "du 30 septembre au 2 octobre", years only when they differ.
     */
    public static function range(\DateTimeImmutable $from, \DateTimeImmutable $to): string
    {
        if ($from->format('Y') !== $to->format('Y')) {
            return \sprintf('du %s %s au %s %s', self::dayMonth($from), $from->format('Y'), self::dayMonth($to), $to->format('Y'));
        }
        if ($from->format('n') !== $to->format('n')) {
            return \sprintf('du %s au %s', self::dayMonth($from), self::dayMonth($to));
        }

        return \sprintf('du %s au %s', self::day($from), self::dayMonth($to));
    }

    private static function day(\DateTimeImmutable $date): string
    {
        return '1' === $date->format('j') ? '1er' : $date->format('j');
    }
}
