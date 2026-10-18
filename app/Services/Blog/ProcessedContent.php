<?php

namespace App\Services\Blog;

final class ProcessedContent
{
    /**
     * @param  array<int, array{level: int, id: string, text: string}>  $toc
     * @param  array<int, array{question: string, answer: string}>  $faq
     */
    public function __construct(
        public readonly string $html,
        public readonly array $toc,
        public readonly array $faq,
        public readonly int $wordCount,
        public readonly int $readTimeMinutes,
        public readonly ?string $firstParagraph,
    ) {}
}
