<?php

namespace App\Services\Blog;

use App\Services\CloudinaryService;
use DOMDocument;
use DOMElement;
use DOMXPath;
use Highlight\Highlighter;
use Illuminate\Support\Str;
use Symfony\Component\HtmlSanitizer\HtmlSanitizer;
use Symfony\Component\HtmlSanitizer\HtmlSanitizerConfig;

/**
 * Turns raw editor/webhook HTML into render-ready HTML, once, at save time.
 * Readers never pay for any of this: the public page just echoes body_html.
 */
class ContentProcessor
{
    /** Content column max width in CSS px — keep in sync with resources/css/blog.css. */
    public const CONTENT_WIDTH = 720;

    private const IMAGE_WIDTHS = [480, 768, 1200, 1600];

    private const ALLOWED_STYLES = ['color', 'background-color', 'font-size', 'text-align', 'font-weight', 'font-style', 'text-decoration'];

    private const AUTODETECT_LANGUAGES = ['php', 'javascript', 'typescript', 'python', 'bash', 'shell', 'json', 'xml', 'css', 'sql', 'yaml', 'go', 'rust', 'java'];

    public function __construct(private CloudinaryService $cloudinary) {}

    public function process(string $html): ProcessedContent
    {
        $doc = $this->load($this->sanitizer()->sanitize($html));
        $xpath = new DOMXPath($doc);
        $root = $doc->getElementById('__root');

        $this->removeEmptyParagraphs($xpath);
        $this->normalizeStyles($xpath);
        $toc = $this->processHeadings($doc, $xpath);
        $faq = $this->extractFaq($xpath);
        $this->processImages($doc, $xpath);
        $this->processEmbeds($doc, $xpath);
        $this->processLinks($xpath);
        $this->wrapTables($doc, $xpath);
        $this->highlightCode($doc, $xpath);

        $text = trim(preg_replace('/\s+/u', ' ', $root->textContent));
        $wordCount = $text === '' ? 0 : count(preg_split('/\s+/u', $text));

        $firstParagraph = null;
        foreach ($xpath->query('//p') as $p) {
            $pText = trim(preg_replace('/\s+/u', ' ', $p->textContent));
            if (mb_strlen($pText) >= 40) {
                $firstParagraph = $pText;
                break;
            }
        }

        $out = '';
        foreach ($root->childNodes as $child) {
            $out .= $doc->saveHTML($child);
        }

        return new ProcessedContent(
            html: $out,
            toc: $toc,
            faq: count($faq) >= 2 ? $faq : [],
            wordCount: $wordCount,
            readTimeMinutes: max(1, (int) ceil($wordCount / 200)),
            firstParagraph: $firstParagraph,
        );
    }

    private function sanitizer(): HtmlSanitizer
    {
        $config = (new HtmlSanitizerConfig)
            ->allowSafeElements()
            ->allowElement('iframe', ['src', 'title'])
            ->allowElement('figure')
            ->allowElement('figcaption')
            ->allowElement('mark', ['style'])
            ->allowAttribute('data-type', ['div'])
            ->allowAttribute('data-variant', ['div'])
            ->allowAttribute('data-label', ['div'])
            ->allowAttribute('data-video-id', ['div'])
            ->allowAttribute('data-caption', ['img'])
            ->allowAttribute('width', ['img'])
            ->allowAttribute('height', ['img'])
            ->allowAttribute('style', ['span', 'p', 'mark', 'h2', 'h3', 'h4', 'td', 'th', 'li'])
            ->allowAttribute('class', ['code', 'pre'])
            ->allowAttribute('colspan', ['td', 'th'])
            ->allowAttribute('rowspan', ['td', 'th'])
            ->allowAttribute('start', ['ol'])
            ->allowLinkSchemes(['https', 'http', 'mailto'])
            ->allowMediaSchemes(['https', 'http'])
            ->allowRelativeLinks()
            ->allowRelativeMedias()
            ->withMaxInputLength(5_000_000);

        return new HtmlSanitizer($config);
    }

    private function load(string $html): DOMDocument
    {
        $doc = new DOMDocument('1.0', 'UTF-8');
        $previous = libxml_use_internal_errors(true);
        $doc->loadHTML(
            '<?xml encoding="utf-8"?><div id="__root">'.$html.'</div>',
            LIBXML_HTML_NOIMPLIED | LIBXML_HTML_NODEFDTD | LIBXML_NONET
        );
        libxml_clear_errors();
        libxml_use_internal_errors($previous);

        return $doc;
    }

    private function removeEmptyParagraphs(DOMXPath $xpath): void
    {
        foreach (iterator_to_array($xpath->query('//p')) as $p) {
            if (trim($p->textContent) === '' && $xpath->query('.//img|.//iframe', $p)->length === 0) {
                $p->parentNode->removeChild($p);
            }
        }
    }

    /**
     * Whitelist inline CSS properties/values. Pure-black text color is dropped:
     * it is the default anyway and would be unreadable in dark mode.
     */
    private function normalizeStyles(DOMXPath $xpath): void
    {
        foreach (iterator_to_array($xpath->query('//*[@style]')) as $el) {
            $kept = [];
            foreach (explode(';', $el->getAttribute('style')) as $decl) {
                [$prop, $value] = array_pad(array_map('trim', explode(':', $decl, 2)), 2, '');
                $prop = strtolower($prop);
                if (! in_array($prop, self::ALLOWED_STYLES, true) || $value === '') {
                    continue;
                }
                if (! preg_match('/^[#a-z0-9(),.%\s-]+$/i', $value) || str_contains(strtolower($value), 'url')) {
                    continue;
                }
                if ($prop === 'color' && preg_match('/^(#000|#000000|black|rgb\(0,\s*0,\s*0\))$/i', $value)) {
                    continue;
                }
                $kept[] = "{$prop}: {$value}";
            }
            $kept ? $el->setAttribute('style', implode('; ', $kept)) : $el->removeAttribute('style');
        }
    }

    /**
     * One H1 per page (the title), so body H1s become H2s. H2/H3 get stable
     * ids for the table of contents and deep links.
     *
     * @return array<int, array{level: int, id: string, text: string}>
     */
    private function processHeadings(DOMDocument $doc, DOMXPath $xpath): array
    {
        foreach (iterator_to_array($xpath->query('//h1')) as $h1) {
            $this->rename($doc, $h1, 'h2');
        }

        $toc = [];
        $used = [];
        foreach ($xpath->query('//h2|//h3') as $h) {
            $text = trim(preg_replace('/\s+/u', ' ', $h->textContent));
            if ($text === '') {
                continue;
            }
            $base = Str::slug($text) ?: 'section';
            $id = $base;
            for ($i = 2; isset($used[$id]); $i++) {
                $id = "{$base}-{$i}";
            }
            $used[$id] = true;
            $h->setAttribute('id', $id);
            $toc[] = ['level' => (int) substr($h->nodeName, 1), 'id' => $id, 'text' => $text];
        }

        return $toc;
    }

    /**
     * Question headings (ending in "?") followed by answer text become FAQ pairs
     * for FAQPage schema.
     *
     * @return array<int, array{question: string, answer: string}>
     */
    private function extractFaq(DOMXPath $xpath): array
    {
        $pairs = [];
        foreach ($xpath->query('//h2|//h3') as $h) {
            $question = trim(preg_replace('/\s+/u', ' ', $h->textContent));
            if (! str_ends_with($question, '?')) {
                continue;
            }
            $answer = [];
            for ($n = $h->nextSibling; $n && count($answer) < 3; $n = $n->nextSibling) {
                if ($n instanceof DOMElement && preg_match('/^h[1-6]$/', $n->nodeName)) {
                    break;
                }
                $t = trim(preg_replace('/\s+/u', ' ', $n->textContent));
                if ($t !== '') {
                    $answer[] = $t;
                }
            }
            if ($answer) {
                $pairs[] = ['question' => $question, 'answer' => Str::limit(implode(' ', $answer), 1000)];
            }
            if (count($pairs) >= 10) {
                break;
            }
        }

        return $pairs;
    }

    /**
     * Native lazy loading (src stays in the HTML, so every crawler sees it),
     * intrinsic width/height (no layout shift), responsive srcset from
     * Cloudinary and <figure>/<figcaption> for captions.
     */
    private function processImages(DOMDocument $doc, DOMXPath $xpath): void
    {
        foreach (iterator_to_array($xpath->query('//img')) as $img) {
            $src = $img->getAttribute('src');
            if ($src === '') {
                $img->parentNode->removeChild($img);

                continue;
            }

            $caption = trim($img->getAttribute('data-caption') ?: $img->getAttribute('title'));
            $img->removeAttribute('data-caption');
            $img->removeAttribute('title');

            [$w, $h] = [(int) $img->getAttribute('width'), (int) $img->getAttribute('height')];
            if (! $w || ! $h) {
                [$w, $h] = $this->probeSize($src);
            }

            if ($this->cloudinary->isCloudinaryUrl($src)) {
                $widths = array_values(array_filter(self::IMAGE_WIDTHS, fn ($cw) => ! $w || $cw <= max($w, self::IMAGE_WIDTHS[0])));
                $img->setAttribute('src', $this->cloudinary->transform($src, 'c_limit,w_1200'));
                $img->setAttribute('srcset', implode(', ', array_map(
                    fn ($cw) => $this->cloudinary->transform($src, "c_limit,w_{$cw}")." {$cw}w",
                    $widths
                )));
                $img->setAttribute('sizes', '(min-width: '.(self::CONTENT_WIDTH + 40).'px) '.self::CONTENT_WIDTH.'px, 100vw');
            }

            if ($w && $h) {
                if ($w > 1200) {
                    $h = (int) round($h * 1200 / $w);
                    $w = 1200;
                }
                $img->setAttribute('width', (string) $w);
                $img->setAttribute('height', (string) $h);
            }

            if (trim($img->getAttribute('alt')) === '') {
                $img->setAttribute('alt', $caption ?: $this->altFromFilename($src));
            }
            $img->setAttribute('loading', 'lazy');
            $img->setAttribute('decoding', 'async');

            $parent = $img->parentNode;
            if ($parent->nodeName === 'figure') {
                continue;
            }

            if ($caption === '' && $parent->nodeName !== 'p') {
                continue;
            }

            $figure = $doc->createElement('figure');
            // An image alone in a <p> (pasted HTML) is lifted out; <figure> can't live inside <p>.
            $replace = ($parent->nodeName === 'p' && trim($parent->textContent) === '' && $parent->childNodes->length === 1) ? $parent : $img;
            if ($replace === $img && $parent->nodeName === 'p') {
                continue; // inline image inside text: leave it inline
            }
            $replace->parentNode->replaceChild($figure, $replace);
            $figure->appendChild($img);
            if ($caption !== '') {
                $figcaption = $doc->createElement('figcaption');
                $figcaption->appendChild($doc->createTextNode($caption));
                $figure->appendChild($figcaption);
            }
        }
    }

    /** @return array{0: int, 1: int} */
    private function probeSize(string $src): array
    {
        if (! str_starts_with($src, 'https://') && ! str_starts_with($src, 'http://')) {
            return [0, 0];
        }

        try {
            $context = stream_context_create(['http' => ['timeout' => 4], 'https' => ['timeout' => 4]]);
            $data = @file_get_contents($src, false, $context, 0, 2_000_000);
            $size = $data ? @getimagesizefromstring($data) : false;

            return $size ? [(int) $size[0], (int) $size[1]] : [0, 0];
        } catch (\Throwable) {
            return [0, 0];
        }
    }

    private function altFromFilename(string $src): string
    {
        $name = pathinfo(parse_url($src, PHP_URL_PATH) ?: '', PATHINFO_FILENAME);

        return Str::of($name)->replaceMatches('/[_-]+/', ' ')->replaceMatches('/\b[a-z0-9]{16,}\b/i', '')->squish()->ucfirst()->toString();
    }

    /**
     * YouTube embeds render as an iframe whose srcdoc is just a thumbnail and a
     * play button: ~0 KB until the reader clicks, then the real player loads
     * inline. The real embed URL stays in src so search engines detect the video.
     */
    private function processEmbeds(DOMDocument $doc, DOMXPath $xpath): void
    {
        foreach (iterator_to_array($xpath->query('//div[@data-type="youtube"]|//iframe')) as $node) {
            if (! $node->parentNode) {
                continue;
            }
            if ($node->nodeName === 'iframe' && $node->parentNode instanceof DOMElement && $node->parentNode->getAttribute('data-type') === 'youtube') {
                continue; // handled with its wrapper
            }

            $id = null;
            if ($node->nodeName === 'div') {
                $id = $node->getAttribute('data-video-id') ?: null;
                $iframe = $xpath->query('.//iframe', $node)->item(0);
                $id ??= $iframe ? $this->youtubeId($iframe->getAttribute('src')) : null;
            } else {
                $id = $this->youtubeId($node->getAttribute('src'));
            }

            if (! $id) {
                $node->parentNode->removeChild($node);

                continue;
            }

            $wrapper = $doc->createElement('div');
            $wrapper->setAttribute('class', 'yt-embed');
            $iframe = $doc->createElement('iframe');
            $embed = "https://www.youtube-nocookie.com/embed/{$id}";
            $iframe->setAttribute('src', $embed);
            $iframe->setAttribute('srcdoc', $this->youtubeSrcdoc($id, $embed));
            $iframe->setAttribute('title', 'YouTube video');
            $iframe->setAttribute('loading', 'lazy');
            $iframe->setAttribute('allow', 'accelerometer; autoplay; clipboard-write; encrypted-media; gyroscope; picture-in-picture; web-share');
            $iframe->setAttribute('allowfullscreen', '');
            $iframe->setAttribute('referrerpolicy', 'strict-origin-when-cross-origin');
            $wrapper->appendChild($iframe);
            $node->parentNode->replaceChild($wrapper, $node);
        }
    }

    private function youtubeId(string $url): ?string
    {
        return preg_match('~(?:youtube(?:-nocookie)?\.com/(?:embed/|watch\?v=|shorts/)|youtu\.be/)([A-Za-z0-9_-]{11})~', $url, $m) ? $m[1] : null;
    }

    private function youtubeSrcdoc(string $id, string $embed): string
    {
        return '<style>*{margin:0;padding:0;overflow:hidden}html,body{height:100%;background:#000}'
            .'img{position:absolute;inset:0;width:100%;height:100%;object-fit:cover}'
            .'span{position:absolute;inset:0;margin:auto;width:68px;height:48px;border-radius:12px;background:#f00;color:#fff;font:24px/48px sans-serif;text-align:center}'
            .'a:hover span{background:#c00}</style>'
            ."<a href=\"{$embed}?autoplay=1\"><img src=\"https://i.ytimg.com/vi/{$id}/hqdefault.jpg\" alt=\"Play video\" loading=\"lazy\"><span>&#9654;</span></a>";
    }

    private function processLinks(DOMXPath $xpath): void
    {
        $appHost = parse_url((string) config('app.url'), PHP_URL_HOST);

        foreach ($xpath->query('//a[@href]') as $a) {
            $host = parse_url($a->getAttribute('href'), PHP_URL_HOST);
            if ($host && $host !== $appHost) {
                $a->setAttribute('target', '_blank');
                $a->setAttribute('rel', 'noopener');
            }
        }
    }

    private function wrapTables(DOMDocument $doc, DOMXPath $xpath): void
    {
        foreach (iterator_to_array($xpath->query('//table')) as $table) {
            $wrap = $doc->createElement('div');
            $wrap->setAttribute('class', 'table-wrap');
            $table->parentNode->replaceChild($wrap, $table);
            $wrap->appendChild($table);
        }
    }

    /** Server-side syntax highlighting: no highlighter JS for readers. */
    private function highlightCode(DOMDocument $doc, DOMXPath $xpath): void
    {
        $highlighter = new Highlighter;
        $highlighter->setAutodetectLanguages(self::AUTODETECT_LANGUAGES);

        foreach (iterator_to_array($xpath->query('//pre')) as $pre) {
            $code = $xpath->query('./code', $pre)->item(0);
            $source = ($code ?? $pre)->textContent;
            $lang = $code && preg_match('/language-([a-z0-9_+-]+)/i', $code->getAttribute('class'), $m) ? strtolower($m[1]) : null;

            try {
                $result = $lang ? $highlighter->highlight($lang, $source) : $highlighter->highlightAuto($source);
            } catch (\Throwable) {
                $result = null;
            }

            $newCode = $doc->createElement('code');
            $newCode->setAttribute('class', 'hljs'.($result?->language ? ' language-'.$result->language : ''));
            if ($result) {
                $fragment = $doc->createDocumentFragment();
                if (@$fragment->appendXML($result->value)) {
                    $newCode->appendChild($fragment);
                } else {
                    $newCode->appendChild($doc->createTextNode($source));
                }
            } else {
                $newCode->appendChild($doc->createTextNode($source));
            }

            while ($pre->firstChild) {
                $pre->removeChild($pre->firstChild);
            }
            $pre->removeAttribute('class');
            $pre->appendChild($newCode);
        }
    }

    private function rename(DOMDocument $doc, DOMElement $el, string $tag): DOMElement
    {
        $new = $doc->createElement($tag);
        foreach (iterator_to_array($el->attributes) as $attr) {
            $new->setAttribute($attr->nodeName, $attr->nodeValue);
        }
        while ($el->firstChild) {
            $new->appendChild($el->firstChild);
        }
        $el->parentNode->replaceChild($new, $el);

        return $new;
    }
}
