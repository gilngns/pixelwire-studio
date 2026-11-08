{!! '<?xml version="1.0" encoding="UTF-8"?>' !!}
<urlset xmlns="http://www.sitemaps.org/schemas/sitemap/0.9" xmlns:xhtml="http://www.w3.org/1999/xhtml">
@foreach ($pages as $page)
    @foreach (['en', 'id'] as $lang)
    <url>
        <loc>{{ $page }}?lang={{ $lang }}</loc>
        <xhtml:link rel="alternate" hreflang="en" href="{{ $page }}?lang=en"/>
        <xhtml:link rel="alternate" hreflang="id" href="{{ $page }}?lang=id"/>
    </url>
    @endforeach
@endforeach
</urlset>
