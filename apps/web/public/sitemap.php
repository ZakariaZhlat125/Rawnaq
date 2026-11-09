<?php
// Apache serves /sitemap.xml through this endpoint; never accepts a URL from the request.
$xml = null;
if (function_exists('curl_init')) {
    $curl = curl_init('https://dashboard.sami-care.sa/api/seo-sitemap');
    curl_setopt_array($curl, [CURLOPT_RETURNTRANSFER=>true, CURLOPT_CONNECTTIMEOUT=>3, CURLOPT_TIMEOUT=>6, CURLOPT_FOLLOWLOCATION=>false]);
    $body = curl_exec($curl);
    $status = curl_getinfo($curl, CURLINFO_HTTP_CODE);
    curl_close($curl);
    if ($status === 200 && is_string($body) && !str_contains($body, '<!DOCTYPE') && function_exists('simplexml_load_string')) {
        $old = libxml_use_internal_errors(true);
        $document = simplexml_load_string($body, 'SimpleXMLElement', LIBXML_NONET);
        if ($document && $document->getName() === 'urlset') {
            $valid = true;
            foreach ($document->url as $url) {
                if (!str_starts_with((string)$url->loc, 'https://sami-care.sa/')) { $valid=false; break; }
            }
            if ($valid) $xml = $body;
        }
        libxml_clear_errors(); libxml_use_internal_errors($old);
    }
}
// A valid, public-pages-only fallback keeps crawler requests working during API outages.
if ($xml === null) $xml = file_get_contents(__DIR__.'/sitemap.xml');
header('Content-Type: application/xml; charset=UTF-8');
header('Cache-Control: public, max-age=300');
echo $xml;
