<?php
/**
 * Breadcrumbs — render visible HTML trail + matching schema.org BreadcrumbList JSON-LD.
 *
 * Usage:
 *   $bc = new Breadcrumbs();
 *   $bc->add('Wedding', '/wedding/');
 *   $bc->add('Wedding Bands', '/wedding/bands/');
 *   $bc->add('14K Yellow 4mm Comfort Fit'); // last crumb omits URL
 *   echo $bc->renderHtml();
 *   echo $bc->renderJsonLd();
 */

class Breadcrumbs
{
    /** @var array<int, array{label:string,url:?string}> */
    private array $items = [];

    public function add(string $label, ?string $url = null): self
    {
        $this->items[] = ['label' => $label, 'url' => $url];
        return $this;
    }

    public function renderHtml(): string
    {
        if (!$this->items) return '';
        $parts = ['<nav class="catalog-breadcrumbs" aria-label="Breadcrumb"><ol>'];
        $last = count($this->items) - 1;
        foreach ($this->items as $i => $it) {
            $label = htmlspecialchars($it['label'], ENT_QUOTES, 'UTF-8');
            if ($i === $last || empty($it['url'])) {
                $parts[] = '<li aria-current="page">' . $label . '</li>';
            } else {
                $url = htmlspecialchars($it['url'], ENT_QUOTES, 'UTF-8');
                $parts[] = '<li><a href="' . $url . '">' . $label . '</a></li>';
            }
        }
        $parts[] = '</ol></nav>';
        return implode('', $parts);
    }

    public function renderJsonLd(string $baseUrl = ''): string
    {
        if (!$this->items) return '';
        if ($baseUrl === '') {
            $scheme = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') ? 'https' : 'http';
            $baseUrl = $scheme . '://' . ($_SERVER['HTTP_HOST'] ?? 'localhost');
        }
        $list = [];
        foreach ($this->items as $i => $it) {
            $entry = [
                '@type'    => 'ListItem',
                'position' => $i + 1,
                'name'     => $it['label'],
            ];
            if (!empty($it['url'])) {
                $entry['item'] = rtrim($baseUrl, '/') . $it['url'];
            }
            $list[] = $entry;
        }
        $data = [
            '@context'        => 'https://schema.org',
            '@type'           => 'BreadcrumbList',
            'itemListElement' => $list,
        ];
        return '<script type="application/ld+json">'
            . json_encode($data, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE)
            . '</script>';
    }
}
