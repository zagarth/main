<?php
/**
 * Shared bootstrap for /catalog/*.php pages.
 * Starts session, sets SHOW_PRICING, loads CatalogQuery + Breadcrumbs + nav.
 */

ini_set('session.cookie_httponly', 1);
ini_set('session.use_only_cookies', 1);
ini_set('session.cookie_secure', (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') ? 1 : 0);
ini_set('session.cookie_samesite', 'Strict');
if (session_status() === PHP_SESSION_NONE) session_start();

// Pricing visibility — must be defined BEFORE site_config.php
if (!defined('SHOW_PRICING')) {
    define('SHOW_PRICING',
        isset($_SESSION['logged_in']) && $_SESSION['logged_in'] === true
        && isset($_SESSION['role'])
        && in_array($_SESSION['role'], ['admin', 'business'], true)
    );
}

require_once __DIR__ . '/../includes/site_config.php';
require_once __DIR__ . '/../includes/catalog/CatalogQuery.php';
require_once __DIR__ . '/../includes/catalog/Breadcrumbs.php';
require_once __DIR__ . '/../includes/catalog/BandsGrouper.php';

$LABELS = require __DIR__ . '/../includes/catalog/subcategory_labels.php';

/** Sanitize a URL segment: lowercase alphanum + hyphen only. */
function sanitize_seg(?string $s, int $max = 64): string {
    if ($s === null) return '';
    $s = strtolower(preg_replace('/[^A-Za-z0-9_-]+/', '', $s) ?? '');
    return substr($s, 0, $max);
}

/** Sanitize an alphanumeric product ID. */
function sanitize_pid(?string $s, int $max = 32): string {
    if ($s === null) return '';
    $s = preg_replace('/[^A-Za-z0-9_-]+/', '', $s) ?? '';
    return substr($s, 0, $max);
}

/** Emit standard page top (DOCTYPE, head, nav include). */
function catalog_render_top(array $opts, ?Breadcrumbs $bc = null): void {
    $title       = $opts['title']       ?? 'Cadman Manufacturing';
    $description = $opts['description'] ?? '';
    $canonical   = $opts['canonical']   ?? '';
    $extraHead   = $opts['extra_head']  ?? '';

    echo "<!DOCTYPE html>\n<html lang=\"en\">\n<head>\n";
    echo '<meta charset="utf-8">' . "\n";
    echo '<meta name="viewport" content="width=device-width, initial-scale=1.0">' . "\n";
    echo '<title>' . htmlspecialchars($title) . "</title>\n";
    if ($description !== '') {
        echo '<meta name="description" content="' . htmlspecialchars($description) . "\">\n";
    }
    if ($canonical !== '') {
        echo '<link rel="canonical" href="' . htmlspecialchars($canonical) . "\">\n";
    }
    echo '<link rel="stylesheet" href="/styles.css">' . "\n";
    echo '<link rel="stylesheet" href="/css/catalog.css">' . "\n";
    echo '<script src="/js/jquery-3.6.0.min.js"></script>' . "\n";
    if ($bc) echo $bc->renderJsonLd() . "\n";
    echo $extraHead;
    echo "</head>\n<body>\n";

    // Search modal (used by nav search button)
    $searchModal = __DIR__ . '/../includes/search_modal.php';
    if (is_file($searchModal)) include $searchModal;

    $navInclude = __DIR__ . '/../navigation.php';
    if (is_file($navInclude)) {
        include_once $navInclude;
        if (function_exists('renderNavigation')) renderNavigation('');
    }

    $topBtn = __DIR__ . '/../topButton.php';
    if (is_file($topBtn)) {
        include_once $topBtn;
        if (function_exists('renderTopButton')) renderTopButton();
    }

    echo '<main class="catalog-main">' . "\n";
    if ($bc) echo $bc->renderHtml() . "\n";
}

function catalog_render_bottom(): void {
    echo "</main>\n";

    // ProductModal container + scripts (matches Bands.php)
    $modalCls = __DIR__ . '/../classes/ProductModal.php';
    if (is_file($modalCls)) {
        require_once $modalCls;
        if (class_exists('ProductModal')) ProductModal::renderModalContainer();
    }

    $footer = __DIR__ . '/../footer.php';
    if (is_file($footer)) include $footer;
    if (function_exists('renderFooter')) renderFooter('catalog');

    echo '<script src="/js/search_modal.js?v=20260604_1" defer></script>' . "\n";
    echo '<script src="/js/catalog_pagination.js" defer></script>' . "\n";
    echo "</body>\n</html>";
}

/** Build the absolute canonical URL for the current request path. */
function catalog_canonical(string $path): string {
    $scheme = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') ? 'https' : 'http';
    return $scheme . '://' . ($_SERVER['HTTP_HOST'] ?? 'localhost') . $path;
}

/**
 * Resolve a catalog PDF URL for a product row.
 * Some DB rows store `pdf_file` with a non-zero-padded page number
 * (e.g. "page_7c.pdf") while the on-disk filename is zero-padded
 * ("page_07c.pdf"). The `page_reference` column is consistently
 * zero-padded ("page_07c"), so prefer it. Falls back to pdf_file
 * after attempting to pad single-digit page numbers.
 * Returns '' if the product has no associated PDF.
 */
function catalog_pdf_url(array $product): string {
    $catalogRoot = dirname(__DIR__) . '/Cadman_catalog/';
    $candidates = [];

    $ref = trim((string)($product['page_reference'] ?? ''));
    if ($ref !== '') {
        // page_reference is e.g. "page_07c" → file "page_07c.pdf"
        if (!preg_match('/\.pdf$/i', $ref)) $ref .= '.pdf';
        $candidates[] = $ref;
    }

    $pdfFile = trim((string)($product['pdf_file'] ?? ''));
    if ($pdfFile !== '') {
        $candidates[] = $pdfFile;
        // Zero-pad single-digit page numbers: "page_7c.pdf" → "page_07c.pdf"
        $padded = preg_replace('/^page_(\d)([a-z]?\.pdf)$/i', 'page_0$1$2', $pdfFile);
        if ($padded !== $pdfFile) $candidates[] = $padded;
    }

    foreach ($candidates as $name) {
        if ($name === '' || $name === '.pdf') continue;
        if (is_file($catalogRoot . $name)) {
            return '/Cadman_catalog/' . rawurlencode($name);
        }
    }
    return '';
}
