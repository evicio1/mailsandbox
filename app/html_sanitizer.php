<?php
// app/html_sanitizer.php

function sanitizeHtml($html) {
    if (empty(trim($html))) {
        return '';
    }

    // Basic regex cleanup for things that might break DOMDocument
    $html = preg_replace('/<script\b[^>]*>(.*?)<\/script>/is', '', $html);
    $html = preg_replace('/<style\b[^>]*>(.*?)<\/style>/is', '', $html);
    $html = preg_replace('/<iframe\b[^>]*>(.*?)<\/iframe>/is', '', $html);
    $html = preg_replace('/<object\b[^>]*>(.*?)<\/object>/is', '', $html);
    $html = preg_replace('/<embed\b[^>]*>(.*?)<\/embed>/is', '', $html);

    // Suppress warnings for malformed HTML
    $ext_dom = new DOMDocument();
    libxml_use_internal_errors(true);
    
    // Add meta tag to ensure UTF-8 processing
    $htmlWrapper = '<?xml encoding="utf-8" ?>' . $html;
    $ext_dom->loadHTML($htmlWrapper, LIBXML_NOBLANKS | LIBXML_NOERROR);
    libxml_clear_errors();

    $xpath = new DOMXPath($ext_dom);

    // Remove event handlers (on*) and javascript: links
    $nodes = $xpath->query('//@*[starts-with(name(), "on")] | //a[starts-with(@href, "javascript:")]');
    foreach ($nodes as $node) {
        $node->parentNode->removeAttributeNode($node);
    }
    
    // Attempting to just extract the body if exists
    $body = $ext_dom->getElementsByTagName('body')->item(0);
    if ($body) {
        $cleanHtml = '';
        foreach ($body->childNodes as $child) {
            $cleanHtml .= $ext_dom->saveXML($child);
        }
        return $cleanHtml;
    }

    return $html;
}
