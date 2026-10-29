<?php

namespace LivingReport\Model;

use LivingReport\Markdown\GitHubReferenceParser;

final readonly class Chip
{
    public function __construct(
        public Tone $tone,
        public string $text,
        public ?string $href = null,
    ) {
    }

    /**
     * Without an href, the first "#123" of the text links to GitHub; "#123" as href too.
     */
    public function link(?string $githubRepository): ?string
    {
        $reference = $this->href ?? (preg_match('/(?<![\w&])#\d{3,6}\b/', $this->text, $match) ? $match[0] : null);
        if (null !== $reference && preg_match('/^#(\d{3,6})$/', $reference, $number)) {
            return null === $githubRepository ? null : GitHubReferenceParser::url($githubRepository, (int) $number[1]);
        }

        return $reference;
    }
}
