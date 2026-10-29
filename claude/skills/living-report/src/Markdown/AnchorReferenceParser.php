<?php

namespace LivingReport\Markdown;

use League\CommonMark\Extension\CommonMark\Node\Inline\Link;
use League\CommonMark\Parser\Inline\InlineParserInterface;
use League\CommonMark\Parser\Inline\InlineParserMatch;
use League\CommonMark\Parser\InlineParserContext;

/**
 * "D14" links to the numbered checklist item of the report that bears it.
 */
final readonly class AnchorReferenceParser implements InlineParserInterface
{
    /**
     * @param array<string, string> $anchors The id of each item, by its number
     */
    public function __construct(
        private array $anchors,
    ) {
    }

    public function getMatchDefinition(): InlineParserMatch
    {
        return InlineParserMatch::regex('D\d{1,3}(?![\w-])')->caseSensitive();
    }

    public function parse(InlineParserContext $inlineContext): bool
    {
        $cursor = $inlineContext->getCursor();
        $num = $inlineContext->getFullMatch();
        if (!isset($this->anchors[$num]) || (null !== ($previous = $cursor->peek(-1)) && preg_match('/[\w\/#-]/u', $previous))) {
            return false;
        }

        $cursor->advanceBy($inlineContext->getFullMatchLength());
        $inlineContext->getContainer()->appendChild(new Link('#' . $this->anchors[$num], $num));

        return true;
    }
}
