<?php

namespace App\Services;

class QrTextFormatter
{
    public function format(string $content): string
    {
        // Preserve line breaks and literal characters in existing plain-text QR codes.
        if (!preg_match('/<\/?[a-z][^>]*>/i', $content)) {
            return '<p>' . nl2br(htmlspecialchars($content, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8')) . '</p>';
        }

        $config = \HTMLPurifier_Config::createDefault();
        $config->set('Core.Encoding', 'UTF-8');
        $config->set('HTML.Allowed', 'p[style|dir],br,strong,b,em,i,u,s,strike,blockquote,ul,ol,li,a[href|title],span[style|dir],div[style|dir],h1[style|dir],h2[style|dir],h3[style|dir],h4[style|dir],h5[style|dir],h6[style|dir]');
        $config->set('CSS.AllowedProperties', ['color', 'background-color', 'font-size', 'font-family', 'text-align', 'text-decoration', 'font-weight', 'font-style']);
        $config->set('URI.AllowedSchemes', ['http' => true, 'https' => true, 'mailto' => true]);
        $config->set('Cache.DefinitionImpl', null);

        return (new \HTMLPurifier($config))->purify($content);
    }
}
