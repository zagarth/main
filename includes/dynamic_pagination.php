<?php
/**
 * Dynamic Pagination Include
 * 
 * Provides reusable pagination functionality for collection pages
 * Includes both PHP helper functions and JavaScript initialization
 */

// PHP Helper function for pagination controls
function renderPaginationControls($containerId = 'gallery-container', $gridId = 'gallery-grid') {
    echo '<div class="pagination-controls">
        <div class="pagination-info">
            <span id="pagination-status">Loading items...</span>
        </div>
        <div class="pagination-buttons">
            <button id="prev-page" class="pagination-btn" disabled>← Previous</button>
            <span id="page-numbers"></span>
            <button id="next-page" class="pagination-btn">Next →</button>
        </div>
        <div class="items-per-page-selector">
            <label for="items-per-page">Items per page:</label>
            <select id="items-per-page">
                <option value="auto" selected>Auto (Dynamic)</option>
                <option value="6">6 items</option>
                <option value="12">12 items</option>
                <option value="18">18 items</option>
                <option value="24">24 items</option>
            </select>
        </div>
    </div>';
}

// PHP Helper function to add pagination data attributes
function addPaginationAttributes($containerId = 'gallery-container', $gridId = 'gallery-grid') {
    echo 'data-container-id="' . htmlspecialchars($containerId) . '" ';
    echo 'data-grid-id="' . htmlspecialchars($gridId) . '" ';
    echo 'data-pagination-enabled="true"';
}

// JavaScript initialization function
function initializePaginationScript($containerId = 'gallery-container', $gridId = 'gallery-grid') {
    echo "<script>
    // Initialize pagination for this page
    document.addEventListener('DOMContentLoaded', function() {
        if (typeof window.initializeDynamicPagination === 'function') {
            window.initializeDynamicPagination('{$containerId}', '{$gridId}');
        } else {
            console.warn('Dynamic pagination script not loaded');
        }
    });
    </script>";
}
?>
