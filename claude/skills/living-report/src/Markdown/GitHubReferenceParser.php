<?php

namespace LivingReport\Markdown;

use League\CommonMark\Extension\CommonMark\Node\Inline\Link;
use League\CommonMark\Parser\Inline\InlineParserInterface;
use League\CommonMark\Parser\Inline\InlineParserMatch;
use League\CommonMark\Parser\InlineParserContext;

/**
 * "#1234" links to the issue or the pull request: GitHub redirects /issues/ to /pull/.
 */
final readonly class GitHubReferenceParser implements InlineParserInterface
{
    public function __construct(
        private string $repository,
    ) {
    }

    public static function url(string $repository, int $number): string
    {
        return \sprintf('https://github.com/%s/issues/%d', $repository, $number);
    }

    public function getMatchDefinition(): InlineParserMatch
    {
        return InlineParserMatch::regex('#(\d{3,6})(?![\w-])');
    }

    public function parse(InlineParserContext $inlineContext): bool
    {
        $cursor = $inlineContext->getCursor();
        // "C#123", "/pull/123#456", "&#123;" are not references
        if (null !== ($previous = $cursor->peek(-1)) && preg_match('/[\w\/&#]/u', $previous)) {
            return false;
        }

        $cursor->advanceBy($inlineContext->getFullMatchLength());
        $inlineContext->getContainer()->appendChild(new Link(self::url($this->repository, (int) $inlineContext->getSubMatches()[0]), $inlineContext->getFullMatch()));

        return true;
    }
}
