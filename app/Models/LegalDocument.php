<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class LegalDocument extends Model
{
    /** The legal pages the site publishes, in the order they appear in tabs. */
    public const KEYS = ['privacy', 'terms', 'cookies'];

    protected $fillable = [
        'key',
        'title',
        'last_updated',
        'sections',
    ];

    protected function casts(): array
    {
        return [
            'sections' => 'array',
        ];
    }

    public static function getByKey(string $key): self
    {
        return static::where('key', $key)->first()
            ?? new static(['key' => $key] + (static::defaults($key) ?? [
                'title' => ucfirst($key),
                'last_updated' => date('F d, Y'),
                'sections' => [],
            ]));
    }

    /**
     * The default text of a legal page, from resources/legal/{key}.php.
     *
     * @return array{title: string, last_updated: string, sections: array<int, array{title: string, content: string}>}|null
     */
    public static function defaults(string $key): ?array
    {
        $path = resource_path("legal/{$key}.php");

        return in_array($key, self::KEYS, true) && is_file($path) ? require $path : null;
    }

    public function renderSectionContent(?string $content): string
    {
        if (blank($content)) {
            return '';
        }

        // Content that already contains HTML is shown as written (only admins can edit it).
        if (strip_tags($content) !== $content) {
            return $content;
        }

        return static::formatPlainText($content);
    }

    /**
     * Turn the plain text typed in the admin legal editor into safe HTML:
     * a blank line starts a new paragraph, lines starting with "- " become a bulleted list,
     * "1. " a numbered list, **text** is bold, [text](/path or https://…) is a link,
     * and bare URLs and email addresses become links.
     */
    public static function formatPlainText(string $text): string
    {
        $html = '';

        foreach (preg_split('/\R{2,}/', trim($text)) as $block) {
            $lines = [];
            $listType = null;
            $items = [];

            $flushLines = function () use (&$lines, &$html) {
                if ($lines) {
                    $html .= '<p>'.implode('<br>', $lines).'</p>';
                    $lines = [];
                }
            };
            $flushList = function () use (&$listType, &$items, &$html) {
                if ($items) {
                    $html .= "<{$listType}>".implode('', array_map(fn ($item) => "<li>{$item}</li>", $items))."</{$listType}>";
                    $items = [];
                }
                $listType = null;
            };

            foreach (preg_split('/\R/', trim($block)) as $line) {
                $line = trim($line);

                if (preg_match('/^[-•]\s+(.+)$/u', $line, $match)) {
                    $type = 'ul';
                } elseif (preg_match('/^\d+[.)]\s+(.+)$/', $line, $match)) {
                    $type = 'ol';
                } else {
                    $flushList();
                    $lines[] = static::formatInline($line);

                    continue;
                }

                $flushLines();
                if ($listType !== $type) {
                    $flushList();
                    $listType = $type;
                }
                $items[] = static::formatInline($match[1]);
            }

            $flushLines();
            $flushList();
        }

        return $html;
    }

    private static function formatInline(string $text): string
    {
        $text = e($text);

        // [link text](https://… | /path | mailto:…)
        $text = preg_replace_callback(
            '/\[([^\]]+)\]\(((?:https?:\/\/|\/|mailto:)[^\s)]+)\)/',
            fn ($m) => '<a href="'.$m[2].'"'.(str_starts_with($m[2], 'http') ? ' target="_blank" rel="noopener"' : '').'>'.$m[1].'</a>',
            $text,
        );

        // **bold**
        $text = preg_replace('/\*\*(.+?)\*\*/', '<strong>$1</strong>', $text);

        // Bare URLs (not ones already inside a link)
        $text = preg_replace(
            '/(?<!href=")(?<!">)\bhttps?:\/\/[^\s<]*[^\s<.,;:!?)]/',
            '<a href="$0" target="_blank" rel="noopener">$0</a>',
            $text,
        );

        // Email addresses (not ones already inside a link)
        return preg_replace(
            '/(?<![\w.:\/@-])[A-Za-z0-9._%+-]+@[A-Za-z0-9.-]+\.[A-Za-z]{2,}(?![^<]*<\/a>)/',
            '<a href="mailto:$0">$0</a>',
            $text,
        );
    }
}
