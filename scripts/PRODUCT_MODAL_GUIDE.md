# ProductModal Integration Guide

## Quick Start

### 1. Include the ProductModal class
```php
require_once 'classes/ProductModal.php';
```

### 2. Render the modal container (once per page)
```php
// Add this after your existing HTML content but before closing </body>
ProductModal::renderModalContainer();
```

### 3. Trigger modals from JavaScript
```javascript
// Open product modal
ProductModal.open('5424L');

// Open modal with options
ProductModal.open('5424L', {
    highlightConfigurator: true,
    defaultSize: '7'
});
```

## Integration Examples

### Celtic.php Integration
```php
<?php
require_once 'classes/ProductModal.php';
// ... existing Celtic.php content ...

// Before closing </body> tag:
ProductModal::renderModalContainer();
?>
```

### Search Results Integration
```javascript
// Replace existing link clicks with modal triggers
document.querySelectorAll('.product-link').forEach(link => {
    link.addEventListener('click', function(e) {
        e.preventDefault();
        const productId = this.dataset.productId;
        ProductModal.open(productId);
    });
});
```

### Partial Match Thumbnails
```javascript
// For partial matches, create clickable thumbnails
function renderPartialMatches(matches) {
    return matches.map(match => `
        <div class="partial-match-thumbnail" 
             onclick="ProductModal.open('${match.product_id}')">
            <img src="${match.thumbnail}" alt="${match.product_id}">
            <div class="match-info">
                <h4>${match.product_id}</h4>
                <p>${match.category}</p>
            </div>
        </div>
    `).join('');
}
```

## Modal Display Logic

The modal automatically determines the best display mode:

1. **Images Available**: Full modal with image gallery and configurator
2. **PDF Only**: Modal with product info and PDF view button  
3. **Basic Info**: Modal with contact/quote request functionality

## Configurator Categories

Currently supports configurators for:
- `celtic_bands` → `bands_php/celtic_configurator.json`
- `plain_bands` → `bands_php/plain_configurator.json` (to be created)
- `fancy_bands` → `bands_php/fancy_configurator.json` (to be created)  
- `cultural_bands` → `bands_php/cultural_configurator.json` (to be created)

## CSS Customization

The modal includes responsive styles. Customize by adding:

```css
/* Custom modal styles */
.product-modal-content {
    /* Your customizations */
}
```

## API Reference

### PHP Methods

- `ProductModal::renderModalContainer()` - Outputs modal HTML, CSS, and JS
- `ProductModal::getDisplayPriority($product)` - Returns display mode
- `ProductModal::hasConfigurator($product)` - Checks configurator support
- `ProductModal::getConfiguratorPath($category)` - Gets configurator file path

### JavaScript Methods

- `ProductModal.open(productId, options)` - Opens product modal
- `ProductModal.close()` - Closes modal
- `ProductModal.requestQuote(productId)` - Triggers quote request

## Dependencies

- `get_product_modal_data.php` - Product data endpoint (existing)
- `classes/CatalogSearch.php` - Search functionality (existing)
- Configurator JSON files in respective *_php directories

## Migration Path

1. Start with Celtic.php (most complete dataset)
2. Test modal functionality thoroughly
3. Apply to other *_php collection pages
4. Integrate with index.php search
5. Create additional configurator files as needed