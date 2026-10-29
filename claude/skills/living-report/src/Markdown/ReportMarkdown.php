<?php

namespace LivingReport\Markdown;

use League\CommonMark\Environment\Environment;
use League\CommonMark\Event\DocumentParsedEvent;
use League\CommonMark\Extension\Attributes\AttributesExtension;
use League\CommonMark\Extension\CommonMark\CommonMarkCoreExtension;
use League\CommonMark\Extension\CommonMark\Node\Inline\Link;
use League\CommonMark\Extension\Strikethrough\StrikethroughExtension;
use League\CommonMark\MarkdownConverter;
use League\CommonMark\Node\Inline\Text;
use League\CommonMark\Node\Node;

/**
 * The Markdown of a report: CommonMark, "{.class}" on a block, ~~struck~~, "#123" and "D14"
 * linked, French non-breaking spaces. Raw HTML passes through: the YAML is written by us.
 */
final readonly class ReportMarkdown
{
    private MarkdownConverter $converter;

    /**
     * @param array<string, string> $anchors The id of each numbered checklist item, by its number
     */
    public function __construct(?string $githubRepository, array $anchors)
    {
        $environment = new Environment(['html_input' => 'allow', 'allow_unsafe_links' => false]);
        $environment->addExtension(new CommonMarkCoreExtension());
        $environment->addExtension(new AttributesExtension());
        $environment->addExtension(new StrikethroughExtension());
        if (null !== $githubRepository) {
            $environment->addInlineParser(new GitHubReferenceParser($githubRepository));
        }
        $environment->addInlineParser(new AnchorReferenceParser($anchors));
        $environment->addEventListener(DocumentParsedEvent::class, static function (DocumentParsedEvent $event): void {
            self::unwrapNestedLinks($event->getDocument());
            self::applyTypography($event->getDocument());
        });

        $this->converter = new MarkdownConverter($environment);
    }

    public function block(string $markdown): string
    {
        return trim($this->converter->convert($markdown)->getContent());
    }

    /**
     * A title or a cell: one paragraph, rendered without its <p>.
     */
    public function inline(string $markdown): string
    {
        $html = $this->block($markdown);
        if (str_starts_with($html, '<p>') && str_ends_with($html, '</p>') && 1 === substr_count($html, '<p>')) {
            return substr($html, 3, -4);
        }

        return $html;
    }

    /**
     * "[the comment on #1219](…)": the reference inside a link must not become a link itself.
     */
    private static function unwrapNestedLinks(Node $document): void
    {
        $nested = [];
        foreach ($document->iterator() as $node) {
            if ($node instanceof Link && self::hasLinkAncestor($node)) {
                $nested[] = $node;
            }
        }

        foreach ($nested as $link) {
            foreach ($link->children() as $child) {
                $link->insertBefore($child);
            }
            $link->detach();
        }
    }

    private static function hasLinkAncestor(Node $node): bool
    {
        for ($parent = $node->parent(); null !== $parent; $parent = $parent->parent()) {
            if ($parent instanceof Link) {
                return true;
            }
        }

        return false;
    }

    private static function applyTypography(Node $document): void
    {
        foreach ($document->iterator() as $node) {
            if ($node instanceof Text) {
                $node->setLiteral(FrenchTypography::apply($node->getLiteral()));
            }
        }
    }
}
