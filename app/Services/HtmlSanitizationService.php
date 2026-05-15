<?php

namespace App\Services;

use HTMLPurifier;
use HTMLPurifier_Config;

class HtmlSanitizationService
{
    private HTMLPurifier $purifier;

    public function __construct()
    {
        $config = HTMLPurifier_Config::createDefault();

        // Only allow safe HTML tags for rich text content.
        $config->set('HTML.Allowed', 'p,br,strong,em,u,ul,ol,li,h1,h2,h3,h4,h5,h6,blockquote,a,div,span,img');

        // Allow safe attributes (include img.src to satisfy required attributes for img).
        $config->set('HTML.AllowedAttributes', 'href,title,class,id,target,rel,src,alt,width,height');

        // Disable javascript: protocol and data: scheme entirely.
        $config->set('URI.AllowedSchemes', ['http' => true, 'https' => true, 'mailto' => true]);

        // Auto-link relative URLs as https (safe default for production).
        $config->set('URI.DefaultScheme', 'https');

        // Prevent javascript: execution via event handlers.
        $config->set('Attr.AllowedFrameTargets', ['_blank', '_self', '_parent', '_top']);

        $this->purifier = new HTMLPurifier($config);
    }

    /**
     * Sanitize user-provided HTML content.
     * Removes script tags, dangerous attributes, and event handlers.
     */
    public function sanitize(string $dirtyHtml): string
    {
        return $this->purifier->purify($dirtyHtml);
    }
}
