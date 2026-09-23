<?php

it('serves robots.txt as plain text through the route', function (): void {
    config()->set('app.env', 'testing');
    config()->set('robotstxt.environments.testing', [
        'paths'    => ['*' => ['disallow' => ['/admin']]],
        'sitemaps' => ['sitemap.xml'],
    ]);

    $this->get('/robots.txt')
        ->assertOk()
        ->assertHeader('Content-Type', 'text/plain; charset=UTF-8')
        ->assertSee("Sitemap: http://localhost/sitemap.xml\n\nUser-agent: *\nDisallow: /admin", false);
});
