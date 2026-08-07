<?php
/**
 * ToolPage — shell wrapper for lift-and-shift tool pages
 * (e.g. School shoulder customizer, future configurators).
 *
 * Usage in a tool page:
 *   require __DIR__ . '/../../includes/catalog/ToolPage.php';
 *   ToolPage::renderShellTop([
 *       'title'        => 'Custom School Shoulders | Cadman',
 *       'description'  => 'Build your custom school shoulders…',
 *       'breadcrumbs'  => $bc,                  // Breadcrumbs instance
 *       'canonical'    => 'https://…/corporate-service/school/shoulders/',
 *   ]);
 *   // ...byte-for-byte original tool markup + scripts...
 *   ToolPage::renderShellBottom();
 */

require_once __DIR__ . '/Breadcrumbs.php';

class ToolPage
{
    public static function renderShellTop(array $opts): void
    {
        $title       = $opts['title']        ?? 'Cadman Manufacturing';
        $description = $opts['description']  ?? '';
        $canonical   = $opts['canonical']    ?? '';
        /** @var Breadcrumbs|null $bc */
        $bc          = $opts['breadcrumbs']  ?? null;
        $bodyClass   = $opts['body_class']   ?? '';
        $extraHead   = $opts['extra_head']   ?? '';

        // Reuse the site's existing navigation shell (cart, hamburger, header chrome)
        // navigation.php emits the full <header>+<nav>; tool pages just sit inside main.
        $navInclude = __DIR__ . '/../../navigation.php';

        echo '<!DOCTYPE html>' . "\n";
        echo '<html lang="en">' . "\n<head>\n";
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
        if ($bc) echo $bc->renderJsonLd() . "\n";
        echo $extraHead;
        echo "</head>\n<body" . ($bodyClass ? ' class="' . htmlspecialchars($bodyClass) . '"' : '') . ">\n";

        if (is_file($navInclude)) {
            include $navInclude;
        }

        echo '<main class="catalog-main tool-page">' . "\n";
        if ($bc) echo $bc->renderHtml() . "\n";
    }

    public static function renderShellBottom(): void
    {
        echo "</main>\n";
        $footer = __DIR__ . '/../../footer.php';
        if (is_file($footer)) include $footer;
        echo "</body>\n</html>";
    }
}
