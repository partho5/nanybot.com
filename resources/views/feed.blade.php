{!! '<?xml version="1.0" encoding="UTF-8"?>' !!}

<rss version="2.0" xmlns:atom="http://www.w3.org/2005/Atom" xmlns:content="http://purl.org/rss/1.0/modules/content/" xmlns:dc="http://purl.org/dc/elements/1.1/">
    <channel>
        <title>{{ config('blog.name') }}</title>
        <link>{{ route('blog.index') }}</link>
        <description>{{ config('blog.tagline') }}</description>
        <language>en</language>
        <atom:link href="{{ route('blog.feed') }}" rel="self" type="application/rss+xml" />
@if ($updated)
        <lastBuildDate>{{ $updated->toRssString() }}</lastBuildDate>
@endif
@foreach ($articles as $article)
        <item>
            <title>{{ $article->title }}</title>
            <link>{{ $article->url() }}</link>
            <guid isPermaLink="true">{{ $article->url() }}</guid>
            <pubDate>{{ $article->published_at->toRssString() }}</pubDate>
@if ($article->author)
            <dc:creator>{{ $article->author->name }}</dc:creator>
@endif
@if ($article->category)
            <category>{{ $article->category->name }}</category>
@endif
@foreach ($article->tags as $tag)
            <category>{{ $tag->name }}</category>
@endforeach
            <description>{{ $article->metaDescription() }}</description>
            <content:encoded><![CDATA[{!! str_replace(']]>', ']]]]><![CDATA[>', $article->body_html ?? '') !!}]]></content:encoded>
        </item>
@endforeach
    </channel>
</rss>
