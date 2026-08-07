<?php
// Legacy catalog_detail_modal retired.
// Keep a tiny compatibility shim for old includes/callers.

function renderCatalogDetailModal() {
    ?>
    <script>
    function openCatalogModal(productId, searchTerm = '') {
        if (window.ProductModal && typeof ProductModal.open === 'function') {
            ProductModal.open(productId);
            return;
        }
        window.location.href = 'unified_detail.php?product=' + encodeURIComponent(productId);
    }
    </script>
    <?php
}
