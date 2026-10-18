{!! '<?xml version="1.0" encoding="UTF-8"?>' !!}

<urlset xmlns="http://www.sitemaps.org/schemas/sitemap/0.9" xmlns:image="http://www.google.com/schemas/sitemap-image/1.1">
@foreach ($urls as $url)
    <url>
        <loc>{{ $url['loc'] }}</loc>
@if ($url['lastmod'])
        <lastmod>{{ $url['lastmod'] }}</lastmod>
@endif
        <changefreq>{{ $url['changefreq'] }}</changefreq>
        <priority>{{ $url['priority'] }}</priority>
@if ($url['image'])
        <image:image>
            <image:loc>{{ $url['image']['loc'] }}</image:loc>
            <image:title>{{ $url['image']['title'] }}</image:title>
@if ($url['image']['caption'])
            <image:caption>{{ $url['image']['caption'] }}</image:caption>
@endif
        </image:image>
@endif
    </url>
@endforeach
</urlset>
