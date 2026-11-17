<?php
/**
 * robots.txt is generated so the sitemap line always carries the site address
 * set in the admin panel. Rename to robots.txt and hard-code the rules if you
 * would rather keep it static.
 */
require_once __DIR__ . '/includes/data.php';
header('Content-Type: text/plain; charset=utf-8');

$base = rtrim(s('site_url'), '/');

echo "User-agent: *\n";
echo "Allow: /\n";
echo "Disallow: /admin/\n";
echo "Disallow: /data/\n";
echo "Disallow: /includes/\n\n";
echo "Sitemap: {$base}/sitemap.xml\n";
