<?php

declare(strict_types=1);

namespace App\Services;

use DOMDocument;
use DOMElement;
use DOMNode;

/**
 * Sanitasi HTML konten editor dengan whitelist tag & atribut (berbasis DOMDocument).
 * Dipakai saat migrasi konten lama dan saat admin menyimpan konten (Fase 7).
 *
 * - Tag tidak dikenal dibuka (isinya dipertahankan); tag berbahaya dibuang beserta isinya.
 * - Atribut style/class/on* dibuang; tampilan diatur CSS situs.
 * - Link hanya http(s), mailto, tel, relatif; target=_blank diberi rel="noopener noreferrer".
 * - iframe hanya YouTube, diubah ke youtube-nocookie.com dan lazy.
 * - h1 → h2 (satu h1 per halaman), h5/h6 → h4; paragraf kosong dibuang.
 */
final class HtmlSanitizer
{
    /** @var array<string, list<string>> tag => atribut yang diizinkan */
    private const ALLOWED = [
        'p' => [], 'br' => [], 'hr' => [],
        'strong' => [], 'b' => [], 'em' => [], 'i' => [], 'u' => [], 's' => [], 'small' => [], 'sub' => [], 'sup' => [],
        'h2' => [], 'h3' => [], 'h4' => [],
        'ul' => [], 'ol' => [], 'li' => [],
        'blockquote' => [], 'figure' => [], 'figcaption' => [],
        'a' => ['href', 'title', 'target'],
        'img' => ['src', 'alt', 'width', 'height'],
        'table' => [], 'thead' => [], 'tbody' => [], 'tr' => [],
        'th' => ['colspan', 'rowspan'], 'td' => ['colspan', 'rowspan'],
        'iframe' => ['src', 'title'],
    ];

    private const RENAME = ['h1' => 'h2', 'h5' => 'h4', 'h6' => 'h4'];

    private const DROP_WITH_CONTENT = [
        'script', 'style', 'noscript', 'template', 'object', 'embed', 'applet', 'form', 'input', 'button',
        'select', 'textarea', 'svg', 'math', 'link', 'meta', 'base', 'head', 'title', 'frame', 'frameset',
    ];

    public function clean(string $html): string
    {
        if (trim($html) === '') {
            return '';
        }

        $doc = new DOMDocument('1.0', 'UTF-8');
        $previous = libxml_use_internal_errors(true);
        $doc->loadHTML(
            '<!doctype html><html><head><meta http-equiv="Content-Type" content="text/html; charset=utf-8"></head><body>'
            . $html . '</body></html>',
            LIBXML_NONET | LIBXML_NOERROR | LIBXML_NOWARNING
        );
        libxml_clear_errors();
        libxml_use_internal_errors($previous);

        $body = $doc->getElementsByTagName('body')->item(0);
        if ($body === null) {
            return '';
        }
        $this->cleanChildren($body);

        $out = '';
        foreach ($body->childNodes as $child) {
            $out .= $doc->saveHTML($child);
        }

        return trim((string) preg_replace("/\n{3,}/", "\n\n", $out));
    }

    private function cleanChildren(DOMNode $parent): void
    {
        // Salin dulu: daftar childNodes berubah saat node dibuang/dibuka
        foreach (iterator_to_array($parent->childNodes) as $node) {
            if ($node->nodeType === XML_COMMENT_NODE || $node->nodeType === XML_PI_NODE) {
                $parent->removeChild($node);
                continue;
            }
            if (!$node instanceof DOMElement) {
                continue;
            }

            $tag = strtolower($node->tagName);
            if (isset(self::RENAME[$tag])) {
                $node = $this->rename($node, self::RENAME[$tag]);
                $tag = self::RENAME[$tag];
            }

            if (in_array($tag, self::DROP_WITH_CONTENT, true)) {
                $parent->removeChild($node);
                continue;
            }

            $this->cleanChildren($node);

            if (!isset(self::ALLOWED[$tag])) {
                $this->unwrap($node);
                continue;
            }

            if (!$this->cleanAttributes($node, $tag)) {
                $parent->removeChild($node);
                continue;
            }

            if ($tag === 'p' && $this->isEmpty($node)) {
                $parent->removeChild($node);
            }
        }
    }

    /** @return bool false bila elemen harus dibuang (mis. img tanpa src valid, iframe non-YouTube) */
    private function cleanAttributes(DOMElement $el, string $tag): bool
    {
        $allowed = self::ALLOWED[$tag];
        foreach (iterator_to_array($el->attributes) as $attr) {
            if (!in_array(strtolower($attr->name), $allowed, true)) {
                $el->removeAttribute($attr->name);
            }
        }

        switch ($tag) {
            case 'a':
                $href = $this->safeUrl($el->getAttribute('href'), true);
                if ($href === null) {
                    $el->removeAttribute('href');
                } else {
                    $el->setAttribute('href', $href);
                }
                if ($el->hasAttribute('target')) {
                    if ($el->getAttribute('target') === '_blank') {
                        $el->setAttribute('rel', 'noopener noreferrer');
                    } else {
                        $el->removeAttribute('target');
                    }
                }
                return true;

            case 'img':
                $src = $this->safeUrl($el->getAttribute('src'), false);
                if ($src === null) {
                    return false;
                }
                $el->setAttribute('src', $src);
                $el->setAttribute('alt', $el->getAttribute('alt'));
                $el->setAttribute('loading', 'lazy');
                $el->setAttribute('decoding', 'async');
                foreach (['width', 'height'] as $dim) {
                    if ($el->hasAttribute($dim) && !ctype_digit($el->getAttribute($dim))) {
                        $el->removeAttribute($dim);
                    }
                }
                return true;

            case 'iframe':
                $id = self::youtubeId($el->getAttribute('src'));
                if ($id === null) {
                    return false;
                }
                $el->setAttribute('src', 'https://www.youtube-nocookie.com/embed/' . $id);
                $el->setAttribute('title', $el->getAttribute('title') ?: 'Video YouTube');
                $el->setAttribute('loading', 'lazy');
                $el->setAttribute('allowfullscreen', '');
                return true;

            case 'th':
            case 'td':
                foreach (['colspan', 'rowspan'] as $span) {
                    if ($el->hasAttribute($span) && !ctype_digit($el->getAttribute($span))) {
                        $el->removeAttribute($span);
                    }
                }
                return true;
        }

        return true;
    }

    /** Ambil ID video dari URL YouTube (watch?v=, youtu.be/, embed/, shorts/). */
    public static function youtubeId(string $url): ?string
    {
        $url = trim($url);
        if (preg_match('~(?:youtube(?:-nocookie)?\.com/(?:watch\?(?:.*&)?v=|embed/|shorts/|live/)|youtu\.be/)([A-Za-z0-9_-]{11})~', $url, $m)) {
            return $m[1];
        }

        return null;
    }

    private function safeUrl(string $url, bool $allowContactSchemes): ?string
    {
        $url = trim(preg_replace('/[\x00-\x20]+/', ' ', $url) ?? '');
        if ($url === '') {
            return null;
        }
        if ($url[0] === '/' || $url[0] === '#') {
            return str_starts_with($url, '//') ? 'https:' . $url : $url;
        }

        $scheme = strtolower((string) parse_url($url, PHP_URL_SCHEME));
        $allowed = $allowContactSchemes ? ['http', 'https', 'mailto', 'tel'] : ['http', 'https'];

        return in_array($scheme, $allowed, true) ? $url : null;
    }

    private function isEmpty(DOMElement $el): bool
    {
        if ($el->getElementsByTagName('img')->length > 0 || $el->getElementsByTagName('iframe')->length > 0) {
            return false;
        }

        return trim(str_replace("\u{00A0}", ' ', $el->textContent)) === '';
    }

    private function unwrap(DOMElement $el): void
    {
        $parent = $el->parentNode;
        while ($el->firstChild !== null) {
            $parent?->insertBefore($el->firstChild, $el);
        }
        $parent?->removeChild($el);
    }

    private function rename(DOMElement $el, string $tag): DOMElement
    {
        $new = $el->ownerDocument->createElement($tag);
        while ($el->firstChild !== null) {
            $new->appendChild($el->firstChild);
        }
        $el->parentNode?->replaceChild($new, $el);

        return $new;
    }
}
