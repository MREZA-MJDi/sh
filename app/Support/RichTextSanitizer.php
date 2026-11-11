<?php

namespace App\Support;

use DOMDocument;
use DOMElement;
use DOMNode;

final class RichTextSanitizer
{
    private const ALLOWED_TAGS = [
        'p', 'br', 'div', 'span', 'strong', 'b', 'em', 'i', 'u', 's',
        'ul', 'ol', 'li', 'blockquote', 'h2', 'h3', 'h4', 'pre', 'code',
        'a', 'table', 'thead', 'tbody', 'tr', 'th', 'td', 'hr',
    ];

    private const ALLOWED_STYLES = [
        'color', 'background-color', 'font-family', 'font-size',
        'font-weight', 'font-style', 'text-decoration', 'text-align',
    ];

    public function sanitize(?string $html): string
    {
        $html = trim((string) $html);
        if ($html === '') {
            return '';
        }

        $previous = libxml_use_internal_errors(true);
        $document = new DOMDocument('1.0', 'UTF-8');
        $document->loadHTML('<?xml encoding="UTF-8"><div id="rich-root">'.$html.'</div>', LIBXML_HTML_NOIMPLIED | LIBXML_HTML_NODEFDTD);
        libxml_clear_errors();
        libxml_use_internal_errors($previous);

        $root = $document->getElementsByTagName('div')->item(0);
        if (! $root) {
            return e(strip_tags($html));
        }

        $this->cleanChildren($root);

        $result = '';
        foreach ($root->childNodes as $child) {
            $result .= $document->saveHTML($child);
        }

        return $result;
    }

    private function cleanChildren(DOMNode $parent): void
    {
        foreach (iterator_to_array($parent->childNodes) as $node) {
            if ($node instanceof DOMElement) {
                $tag = strtolower($node->tagName);
                if (! in_array($tag, self::ALLOWED_TAGS, true)) {
                    if (in_array($tag, ['script', 'style', 'iframe', 'object', 'embed', 'svg', 'math', 'form', 'input', 'button'], true)) {
                        $parent->removeChild($node);
                    } else {
                        while ($node->firstChild) {
                            $parent->insertBefore($node->firstChild, $node);
                        }
                        $parent->removeChild($node);
                    }
                    continue;
                }

                $this->cleanAttributes($node);
                $this->cleanChildren($node);
            } elseif (! in_array($node->nodeType, [XML_TEXT_NODE, XML_COMMENT_NODE], true)) {
                $parent->removeChild($node);
            } elseif ($node->nodeType === XML_COMMENT_NODE) {
                $parent->removeChild($node);
            }
        }
    }

    private function cleanAttributes(DOMElement $element): void
    {
        foreach (iterator_to_array($element->attributes) as $attribute) {
            $name = strtolower($attribute->name);
            $value = trim($attribute->value);

            if ($name === 'style') {
                $styles = [];
                foreach (explode(';', $value) as $declaration) {
                    if (! str_contains($declaration, ':')) {
                        continue;
                    }
                    [$property, $styleValue] = array_map('trim', explode(':', $declaration, 2));
                    $property = strtolower($property);
                    if (! in_array($property, self::ALLOWED_STYLES, true)) {
                        continue;
                    }
                    if (preg_match('/url\s*\(|expression\s*\(|[<>]|\\\\/i', $styleValue)) {
                        continue;
                    }
                    if ($property === 'font-size' && ! preg_match('/^(?:[0-9]{1,2}(?:\.[0-9])?)(?:px|pt|rem|em|%)$/', $styleValue)) {
                        continue;
                    }
                    if ($property === 'text-align' && ! in_array($styleValue, ['right', 'left', 'center', 'justify'], true)) {
                        continue;
                    }
                    if ($property === 'font-weight' && ! in_array($styleValue, ['normal', 'bold', '400', '500', '600', '700', '800'], true)) {
                        continue;
                    }
                    $styles[] = $property.': '.$styleValue;
                }
                if ($styles === []) {
                    $element->removeAttribute('style');
                } else {
                    $element->setAttribute('style', implode('; ', $styles));
                }
                continue;
            }

            if ($name === 'href' && strtolower($element->tagName) === 'a') {
                if (! preg_match('/^(https?:|mailto:|tel:)/i', $value)) {
                    $element->removeAttribute('href');
                } else {
                    $element->setAttribute('rel', 'nofollow noopener noreferrer');
                    $element->setAttribute('target', '_blank');
                }
                continue;
            }

            if ($name === 'colspan' && in_array(strtolower($element->tagName), ['td', 'th'], true) && ctype_digit($value) && (int) $value <= 12) {
                continue;
            }

            if ($name === 'rowspan' && in_array(strtolower($element->tagName), ['td', 'th'], true) && ctype_digit($value) && (int) $value <= 20) {
                continue;
            }

            $element->removeAttributeNode($attribute);
        }
    }
}
