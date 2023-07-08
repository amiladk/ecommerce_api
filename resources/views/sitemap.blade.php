<?xml version="1.0" encoding="UTF-8"?>
<urlset xmlns="http://www.sitemaps.org/schemas/sitemap/0.9" xmlns:image="http://www.google.com/schemas/sitemap-image/1.1">


<url>
  <loc>https://foyo.lk/</loc>
  <lastmod>2021-06-10</lastmod>
  <changefreq>monthly</changefreq>
  <priority>1</priority>
</url>

<url>
  <loc>https://foyo.lk/shop/catalog</loc>
  <lastmod>2021-06-10</lastmod>
  <changefreq>monthly</changefreq>
  <priority>1</priority>
</url>

<url>
  <loc>https://foyo.lk/site/contact-us</loc>
  <lastmod>2021-06-10</lastmod>
  <changefreq>monthly</changefreq>
  <priority>1</priority>
</url>

<url>
  <loc>https://foyo.lk/site/about-us</loc>
  <lastmod>2021-06-10</lastmod>
  <changefreq>monthly</changefreq>
  <priority>1</priority>
</url>

<url>
  <loc>https://foyo.lk/site/return-policy</loc>
  <lastmod>2021-06-10</lastmod>
  <changefreq>monthly</changefreq>
  <priority>0.7</priority>
</url>

@foreach($data['site_map_category'] as $url => $params )
    <url>
        <loc>https://foyo.lk/shop/catalog/{{$params['loc']}}</loc>
        <lastmod>{{$params['lastmod']}}</lastmod>
        <changefreq>{{$params['changefreq']}}</changefreq>
        <priority>{{$params['priority']}}</priority>

    </url>
@endforeach


@foreach($data['site_map_product'] as $url => $params )
    <url>
        <loc>https://foyo.lk/shop/products/{{$params['loc']}}</loc>
        <lastmod>{{$params['lastmod']}}</lastmod>
        <changefreq>{{$params['changefreq']}}</changefreq>
        <priority>{{$params['priority']}}</priority>

        @foreach($params['images'] as $url_1 => $params_1 )
            <image:image>
                <image:loc>http://webstoresl.s3.ap-southeast-1.amazonaws.com/webstore/product-images/{{$params_1['image']}}</image:loc>
                <image:title>{{$params_1['title']}}</image:title>
            </image:image>

        @endforeach

    </url>
@endforeach



</urlset>