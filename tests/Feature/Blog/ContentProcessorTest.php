<?php

namespace Tests\Feature\Blog;

use App\Services\Blog\ContentProcessor;
use App\Services\Blog\ProcessedContent;
use Tests\Concerns\ParsesHtml;
use Tests\TestCase;

class ContentProcessorTest extends TestCase
{
    use ParsesHtml;

    private function process(string $html): ProcessedContent
    {
        return app(ContentProcessor::class)->process($html);
    }

    public function test_headings_get_ids_and_a_toc_is_built(): void
    {
        $out = $this->process('<h2>Getting Started</h2><p>Intro text.</p><h3>Install It</h3><p>More.</p><h2>Wrap Up!</h2>');

        $this->assertStringContainsString('<h2 id="getting-started">', $out->html);
        $this->assertStringContainsString('<h3 id="install-it">', $out->html);
        $this->assertSame([
            ['level' => 2, 'id' => 'getting-started', 'text' => 'Getting Started'],
            ['level' => 3, 'id' => 'install-it', 'text' => 'Install It'],
            ['level' => 2, 'id' => 'wrap-up', 'text' => 'Wrap Up!'],
        ], $out->toc);
    }

    public function test_duplicate_headings_get_unique_ids(): void
    {
        $out = $this->process('<h2>Setup</h2><p>a</p><h2>Setup</h2><p>b</p><h3>Setup</h3><p>c</p>');

        $this->assertSame(['setup', 'setup-2', 'setup-3'], array_column($out->toc, 'id'));
        $ids = $this->dom($out->html)->query('//*[@id]');
        $this->assertSame(3, $ids->length);
    }

    public function test_h1_becomes_h2(): void
    {
        $out = $this->process('<h1>Top Level</h1><p>Body text.</p>');

        $this->assertStringNotContainsString('<h1', $out->html);
        $this->assertStringContainsString('<h2 id="top-level">Top Level</h2>', $out->html);
        $this->assertSame(2, $out->toc[0]['level']);
    }

    public function test_images_are_lazy_decoded_async_and_have_alt_text(): void
    {
        $out = $this->process('<p>Text before.</p><img src="/img/team_photo-2024.png" width="800" height="400"><p>After.</p>');
        $img = $this->dom($out->html)->query('//img')->item(0);

        $this->assertSame('lazy', $img->getAttribute('loading'));
        $this->assertSame('async', $img->getAttribute('decoding'));
        $this->assertSame('800', $img->getAttribute('width'));
        $this->assertSame('400', $img->getAttribute('height'));
        $this->assertSame('Team photo 2024', $img->getAttribute('alt'));
    }

    public function test_cloudinary_images_get_a_srcset(): void
    {
        $out = $this->process('<img src="https://res.cloudinary.com/demo/image/upload/v1/blog/pic.jpg" alt="Pic" width="2000" height="1000">');
        $img = $this->dom($out->html)->query('//img')->item(0);

        $this->assertStringContainsString('f_auto,q_auto', $img->getAttribute('src'));
        $this->assertStringContainsString('1600w', $img->getAttribute('srcset'));
        $this->assertNotSame('', $img->getAttribute('sizes'));
        // Width is capped at 1200 and the height keeps the aspect ratio.
        $this->assertSame('1200', $img->getAttribute('width'));
        $this->assertSame('600', $img->getAttribute('height'));
    }

    public function test_data_caption_becomes_figure_with_figcaption(): void
    {
        $out = $this->process('<img src="/a.png" alt="A" width="10" height="10" data-caption="A fine caption">');
        $xpath = $this->dom($out->html);

        $this->assertSame(1, $xpath->query('//figure/img')->length);
        $this->assertSame('A fine caption', trim($xpath->query('//figure/figcaption')->item(0)->textContent));
        $this->assertSame(0, $xpath->query('//img[@data-caption]')->length);
    }

    public function test_image_alone_in_a_paragraph_is_lifted_into_a_figure(): void
    {
        $out = $this->process('<p><img src="/a.png" alt="A" width="10" height="10" data-caption="Cap"></p>');
        $xpath = $this->dom($out->html);

        $this->assertSame(1, $xpath->query('//figure/figcaption')->length);
        $this->assertSame(0, $xpath->query('//p//figure')->length);
    }

    public function test_dangerous_markup_is_stripped(): void
    {
        $out = $this->process(
            '<p onclick="steal()" onmouseover="x()">Click <a href="javascript:alert(1)">here</a> or '
            .'<a href="  JaVaScRiPt:alert(2)">there</a> or <a href="https://example.org/ok">fine</a></p>'
            .'<script>alert("x")</script><style>body{display:none}</style>'
            .'<img src="x" onerror="alert(3)"><iframe src="https://evil.example/frame"></iframe>'
            .'<form action="/x"><input name="a"></form><object data="x"></object>'
        );

        $html = strtolower($out->html);
        foreach (['<script', 'alert(', 'onclick', 'onmouseover', 'onerror', 'javascript:', '<style', '<form', '<input', '<object', 'evil.example'] as $needle) {
            $this->assertStringNotContainsString($needle, $html, "Output still contains {$needle}");
        }
        $this->assertStringContainsString('https://example.org/ok', $out->html);
        $this->assertStringContainsString('Click', $out->html);
    }

    public function test_external_links_open_in_a_new_tab_with_noopener(): void
    {
        $out = $this->process('<p><a href="https://example.org/x">out</a> <a href="/blog">in</a></p>');
        $xpath = $this->dom($out->html);

        $this->assertSame('_blank', $xpath->query("//a[@href='https://example.org/x']")->item(0)->getAttribute('target'));
        $this->assertStringContainsString('noopener', $xpath->query("//a[@href='https://example.org/x']")->item(0)->getAttribute('rel'));
        $this->assertSame('', $xpath->query("//a[@href='/blog']")->item(0)->getAttribute('target'));
    }

    public function test_youtube_embeds_are_replaced_with_a_lightweight_facade(): void
    {
        $out = $this->process('<iframe src="https://www.youtube.com/embed/dQw4w9WgXcQ"></iframe><iframe src="https://evil.example/x"></iframe>');
        $xpath = $this->dom($out->html);

        $this->assertSame(1, $xpath->query('//iframe')->length);
        $iframe = $xpath->query('//div[@class="yt-embed"]/iframe')->item(0);
        $this->assertSame('https://www.youtube-nocookie.com/embed/dQw4w9WgXcQ', $iframe->getAttribute('src'));
        $this->assertSame('lazy', $iframe->getAttribute('loading'));
    }

    public function test_tables_are_wrapped_for_horizontal_scrolling(): void
    {
        $out = $this->process('<table><tr><td>1</td></tr></table>');

        $this->assertSame(1, $this->dom($out->html)->query('//div[@class="table-wrap"]/table')->length);
    }

    public function test_code_blocks_are_highlighted_on_the_server(): void
    {
        $out = $this->process('<pre><code class="language-php">&lt;?php echo "hi";</code></pre>');

        $this->assertStringContainsString('hljs', $out->html);
        $this->assertStringContainsString('hljs-', $out->html);
        $this->assertStringNotContainsString('<script', $out->html);
    }

    public function test_inline_styles_are_whitelisted(): void
    {
        $out = $this->process('<p style="color: #ff0000; position: fixed; background: url(http://x/y.png)">Red</p><p style="color:#000">Black</p>');
        $xpath = $this->dom($out->html);

        $this->assertSame('color: #ff0000', $xpath->query('//p')->item(0)->getAttribute('style'));
        $this->assertSame('', $xpath->query('//p')->item(1)->getAttribute('style'), 'Pure black is dropped for dark mode');
    }

    public function test_metadata_word_count_read_time_and_first_paragraph(): void
    {
        $words = implode(' ', array_fill(0, 450, 'word'));
        $out = $this->process("<p>Short.</p><p>This paragraph is definitely long enough to be an excerpt candidate.</p><p>{$words}</p>");

        $this->assertSame(3, $out->readTimeMinutes);
        $this->assertGreaterThan(450, $out->wordCount);
        $this->assertSame('This paragraph is definitely long enough to be an excerpt candidate.', $out->firstParagraph);
    }

    public function test_empty_paragraphs_are_removed(): void
    {
        $out = $this->process('<p></p><p>   </p><p>Real content here.</p>');

        $this->assertSame(1, $this->dom($out->html)->query('//p')->length);
    }

    public function test_question_headings_produce_faq_pairs_only_with_two_or_more(): void
    {
        $one = $this->process('<h2>What is it?</h2><p>A thing.</p>');
        $this->assertSame([], $one->faq);

        $two = $this->process('<h2>What is it?</h2><p>A thing.</p><h2>Why use it?</h2><p>Because.</p>');
        $this->assertCount(2, $two->faq);
        $this->assertSame('What is it?', $two->faq[0]['question']);
        $this->assertSame('A thing.', $two->faq[0]['answer']);
    }
}
