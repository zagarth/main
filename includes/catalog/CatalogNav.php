<?php
/**
 * CatalogNav — renders the inside of navigation.php's
 *   <div class="desktop-nav"> ... </div>
 *   <div class="mobile-nav">  ... </div>
 *
 * Header chrome (logo, cart, hamburger), the surrounding <nav id="nav"> wrapper,
 * the toggleMobileMenu / closeMobileMenu / toggleAccordion JS, and the
 * Login/Dashboard tail are intentionally NOT rendered here — navigation.php keeps
 * those byte-for-byte. CatalogNav only swaps the item lists.
 *
 * Source of truth: includes/catalog/nav_config.php
 */

class CatalogNav
{
    private array $config;
    private string $currentPath;

    public function __construct(?string $currentPath = null)
    {
        $this->config = require __DIR__ . '/nav_config.php';
        $this->currentPath = $currentPath
            ?? parse_url($_SERVER['REQUEST_URI'] ?? '/', PHP_URL_PATH)
            ?? '/';
    }

    /** Render desktop horizontal links (parents + right-side links). Pass $loginHtml from caller. */
    public function renderDesktopLinks(string $loginHtml = ''): string
    {
        $out = '';
        foreach ($this->config['parents'] as $p) {
            $active = $this->isActive($p['url']) ? ' active' : '';
            $out .= '<a class="navitem' . $active . '" href="' . htmlspecialchars($p['url']) . '" onClick="closeMobileMenu()">'
                . htmlspecialchars($p['label']) . '</a>' . "\n                ";
        }
        foreach ($this->config['links'] as $l) {
            $icon = !empty($l['icon']) ? htmlspecialchars($l['icon']) . ' ' : '';
            $active = !empty($l['url']) && $l['url'] !== '#' && $l['url'] !== '#contact'
                && $this->isActive($l['url']) ? ' active' : '';
            $onclick = !empty($l['onclick'])
                ? ' onClick="event.preventDefault(); ' . htmlspecialchars($l['onclick'], ENT_QUOTES) . ' closeMobileMenu();"'
                : ' onClick="closeMobileMenu()"';
            $out .= '<a class="navitem' . $active . '" href="' . htmlspecialchars($l['url']) . '"' . $onclick . '>'
                . $icon . htmlspecialchars($l['label']) . '</a>' . "\n                ";
        }
        if ($loginHtml !== '') $out .= $loginHtml;
        return rtrim($out);
    }

    /** Render mobile accordion groups (one .nav-group per parent + a "More" group). $loginLiHtml appended inside More <ul>. */
    public function renderMobileGroups(string $loginLiHtml = ''): string
    {
        $out = '';
        foreach ($this->config['parents'] as $p) {
            $out .= '<div class="nav-group">' . "\n";
            $out .= '                    <div class="nav-group-header" onclick="toggleAccordion(this)">' . "\n";
            $out .= '                        <span>' . htmlspecialchars($p['label']) . '</span>' . "\n";
            $out .= '                        <span class="accordion-icon">+</span>' . "\n";
            $out .= '                    </div>' . "\n";
            $out .= '                    <ul class="nav-group-content">' . "\n";

            // First entry: link to the parent landing itself
            $parentActive = $this->isActive($p['url']) ? ' active' : '';
            $out .= '                        <li><a class="navitem' . $parentActive . '" href="' . htmlspecialchars($p['url'])
                . '" onClick="closeMobileMenu()">All ' . htmlspecialchars($p['label']) . '</a></li>' . "\n";

            foreach ($p['children'] as $c) {
                $cActive = $this->isActive($c['url']) ? ' active' : '';
                $out .= '                        <li><a class="navitem' . $cActive . '" href="' . htmlspecialchars($c['url'])
                    . '" onClick="closeMobileMenu()">' . htmlspecialchars($c['label']) . '</a></li>' . "\n";
            }
            $out .= '                    </ul>' . "\n";
            $out .= '                </div>' . "\n                ";
        }

        // "More" group with right-side links (About/Catalog/Contact)
        $out .= '<div class="nav-group">' . "\n";
        $out .= '                    <div class="nav-group-header" onclick="toggleAccordion(this)">' . "\n";
        $out .= '                        <span>More</span>' . "\n";
        $out .= '                        <span class="accordion-icon">+</span>' . "\n";
        $out .= '                    </div>' . "\n";
        $out .= '                    <ul class="nav-group-content">' . "\n";
        foreach ($this->config['links'] as $l) {
            $icon = !empty($l['icon']) ? htmlspecialchars($l['icon']) . ' ' : '';
            $onclick = !empty($l['onclick'])
                ? ' onClick="event.preventDefault(); ' . htmlspecialchars($l['onclick'], ENT_QUOTES) . ' closeMobileMenu();"'
                : ' onClick="closeMobileMenu()"';
            $out .= '                        <li><a class="navitem" href="' . htmlspecialchars($l['url']) . '"' . $onclick . '>'
                . $icon . htmlspecialchars($l['label']) . '</a></li>' . "\n";
        }
        // FAQ retained from original "More" group
        $out .= '                        <li><a class="navitem" href="/FAQ.php" onClick="closeMobileMenu()">FAQ</a></li>' . "\n";
        if ($loginLiHtml !== '') $out .= '                        ' . $loginLiHtml . "\n";
        $out .= '                    </ul>' . "\n";
        $out .= '                </div>';

        return $out;
    }

    private function isActive(string $url): bool
    {
        if ($url === '' || $url === '#' || str_starts_with($url, '#')) return false;
        $path = parse_url($url, PHP_URL_PATH) ?: $url;
        $cur  = rtrim($this->currentPath, '/');
        $tgt  = rtrim($path, '/');
        return $cur === $tgt || ($tgt !== '' && str_starts_with($cur . '/', $tgt . '/'));
    }
}
