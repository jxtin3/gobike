<?php

namespace App\Support;

use DOMDocument;
use DOMElement;
use DOMNode;

/**
 * Keeps only simple formatting HTML (paragraphs, lists, bold, italics, headings and links)
 * and strips everything else, such as scripts, styles, event handlers and javascript: links.
 * Used for HTML typed into the admin legal page editor before it is shown on the public site.
 */
class SafeHtml
{
    /** Allowed tags and the attributes each may keep. */
    private const ALLOWED = [
        'p' => [], 'br' => [], 'ul' => [], 'ol' => [], 'li' => [],
        'strong' => [], 'b' => [], 'em' => [], 'i' => [], 'u' => [],
        'h2' => [], 'h3' => [], 'h4' => [], 'blockquote' => [],
        'a' => ['href', 'title', 'target', 'rel'],
    ];

    /** Tags removed together with everything inside them. */
    private const DROP_WITH_CONTENT = ['script', 'style', 'iframe', 'object', 'embed', 'template', 'noscript', 'svg', 'math', 'head', 'title'];

    public static function clean(string $html): string
    {
        if (trim($html) === '') {
            return '';
        }

        $document = new DOMDocument;
        $previous = libxml_use_internal_errors(true);
        $document->loadHTML(
            '<?xml encoding="UTF-8"><div id="safe-html-root">'.$html.'</div>',
            LIBXML_HTML_NOIMPLIED | LIBXML_HTML_NODEFDTD | LIBXML_NONET
        );
        libxml_clear_errors();
        libxml_use_internal_errors($previous);

        $root = $document->getElementById('safe-html-root');

        if (! $root) {
            return e(strip_tags($html));
        }

        self::cleanChildren($root);

        $output = '';
        foreach ($root->childNodes as $child) {
            $output .= $document->saveHTML($child);
        }

        return $output;
    }

    private static function cleanChildren(DOMNode $node): void
    {
        // Copy the list first: children are removed or replaced while looping.
        foreach (iterator_to_array($node->childNodes) as $child) {
            if ($child instanceof DOMElement) {
                self::cleanElement($child);
            } elseif ($child->nodeType === XML_COMMENT_NODE || $child->nodeType === XML_PI_NODE) {
                $node->removeChild($child);
            }
        }
    }

    private static function cleanElement(DOMElement $element): void
    {
        $tag = strtolower($element->tagName);
        $parent = $element->parentNode;

        if (in_array($tag, self::DROP_WITH_CONTENT, true)) {
            $parent->removeChild($element);

            return;
        }

        self::cleanChildren($element);

        if (! array_key_exists($tag, self::ALLOWED)) {
            // Unknown tag: keep its (already cleaned) content, drop the tag itself.
            while ($element->firstChild) {
                $parent->insertBefore($element->firstChild, $element);
            }
            $parent->removeChild($element);

            return;
        }

        foreach (iterator_to_array($element->attributes) as $attribute) {
            if (! in_array(strtolower($attribute->name), self::ALLOWED[$tag], true)) {
                $element->removeAttribute($attribute->name);
            }
        }

        if ($tag === 'a') {
            self::cleanLink($element);
        }
    }

    private static function cleanLink(DOMElement $link): void
    {
        $href = trim($link->getAttribute('href'));

        if ($href !== '' && ! self::isSafeUrl($href)) {
            $link->removeAttribute('href');
        }

        if ($link->hasAttribute('target')) {
            if ($link->getAttribute('target') === '_blank') {
                $link->setAttribute('rel', 'noopener noreferrer');
            } else {
                $link->removeAttribute('target');
            }
        }
    }

    private static function isSafeUrl(string $url): bool
    {
        // Ignore whitespace and control characters browsers skip, e.g. "java\tscript:".
        $normalized = strtolower(preg_replace('/[\x00-\x20]+/', '', html_entity_decode($url)));

        if (preg_match('/^[a-z][a-z0-9+.-]*:/', $normalized) !== 1) {
            return true; // relative link, #anchor or /path
        }

        return str_starts_with($normalized, 'http:')
            || str_starts_with($normalized, 'https:')
            || str_starts_with($normalized, 'mailto:')
            || str_starts_with($normalized, 'tel:');
    }
}
