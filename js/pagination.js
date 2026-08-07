/**
 * Dynamic Pagination System
 * 
 * Provides viewport-aware pagination with automatic item calculation
 * and responsive design support for collection galleries
 */

// Global pagination variables
let itemsPerPage = 6;
let currentPage = 1;
let allItems = [];
let currentFilter = 'all';

// Function to calculate optimal items per page based on viewport
function calculateItemsPerPage() {
    const container = document.querySelector('.gallery-container');
    const grid = document.querySelector('.gallery-grid');
    
    if (!container || !grid) return 6; // Fallback
    
    // Get viewport dimensions
    const viewportHeight = window.innerHeight;
    const viewportWidth = window.innerWidth;
    
    // Account for header, filters, pagination controls, etc.
    const headerHeight = 200; // Approximate space for header and filters
    const footerHeight = 150; // Approximate space for pagination and footer
    const availableHeight = viewportHeight - headerHeight - footerHeight;
    
    // Calculate grid layout
    const containerStyle = window.getComputedStyle(container);
    const containerPadding = parseInt(containerStyle.paddingLeft) + parseInt(containerStyle.paddingRight);
    const availableWidth = Math.min(1200, viewportWidth - 40) - containerPadding; // Max width 1200px minus margins
    
    // Item dimensions (from CSS: minmax(260px, 1fr) + gap)
    const minItemWidth = 260;
    const gap = 20;
    
    // Calculate columns that can fit
    const columns = Math.floor((availableWidth + gap) / (minItemWidth + gap));
    
    // Calculate rows that can fit (assuming ~350px per row including gap)
    const estimatedItemHeight = 350; // Item + gap
    const rows = Math.floor(availableHeight / estimatedItemHeight);
    
    // Calculate total items, with reasonable min/max bounds
    const calculatedItems = Math.max(4, Math.min(24, columns * rows));
    
    console.log(`Dynamic pagination: ${viewportWidth}x${viewportHeight} → ${columns}cols × ${rows}rows = ${calculatedItems} items`);
    
    return calculatedItems;
}

function initializePagination(containerId = 'gallery-container', gridId = 'gallery-grid') {
    // Calculate optimal items per page
    itemsPerPage = calculateItemsPerPage();
    
    allItems = Array.from(document.querySelectorAll('.paginated-item'));
    updatePagination();
}

function updatePagination() {
    // Filter items based on current filter
    const filteredItems = currentFilter === 'all' 
        ? allItems 
        : allItems.filter(item => item.dataset.category === currentFilter);
    
    const totalItems = filteredItems.length;
    const totalPages = Math.ceil(totalItems / itemsPerPage);
    
    // Ensure current page is valid
    if (currentPage > totalPages) {
        currentPage = Math.max(1, totalPages);
    }
    
    // Hide all items first
    allItems.forEach(item => {
        item.style.display = 'none';
    });
    
    // Show items for current page
    const startIndex = (currentPage - 1) * itemsPerPage;
    const endIndex = startIndex + itemsPerPage;
    const itemsToShow = filteredItems.slice(startIndex, endIndex);
    
    itemsToShow.forEach(item => {
        item.style.display = 'block';
    });
    
    updatePaginationControls(totalPages, totalItems);
}

function updatePaginationControls() {
    const totalPages = Math.ceil(allItems.length / itemsPerPage);
    const paginationElement = document.getElementById('pagination-controls');
    
    if (!paginationElement || totalPages <= 1) {
        if (paginationElement) paginationElement.style.display = 'none';
        return;
    }
    
    paginationElement.style.display = 'flex';
    
    // Update page info
    const pageInfo = document.getElementById('page-info');
    if (pageInfo) {
        pageInfo.textContent = `Page ${currentPage} of ${totalPages}`;
    }
    
    // Update items info
    const itemsInfo = document.getElementById('items-info');
    if (itemsInfo) {
        const startItem = (currentPage - 1) * itemsPerPage + 1;
        const endItem = Math.min(currentPage * itemsPerPage, allItems.length);
        itemsInfo.textContent = `Showing ${startItem}-${endItem} of ${allItems.length} items`;
    }
    
    // Update navigation buttons
    const prevBtn = document.getElementById('prev-page');
    const nextBtn = document.getElementById('next-page');
    
    if (prevBtn) {
        prevBtn.disabled = currentPage === 1;
        prevBtn.style.opacity = currentPage === 1 ? '0.5' : '1';
    }
    
    if (nextBtn) {
        nextBtn.disabled = currentPage === totalPages;
        nextBtn.style.opacity = currentPage === totalPages ? '0.5' : '1';
    }
    
    // Update page numbers (show max 3 pages between buttons)
    updatePageNumbers(currentPage, totalPages);
}

function updatePageNumbers(current, total) {
    const pageNumbersContainer = document.getElementById('page-numbers');
    if (!pageNumbersContainer) return;
    
    pageNumbersContainer.innerHTML = '';
    
    if (total <= 1) return;
    
    // Calculate which pages to show (max 3 between prev/next)
    let startPage, endPage;
    
    if (total <= 5) {
        // Show all pages if 5 or fewer
        startPage = 1;
        endPage = total;
    } else {
        // Show current page and 1 page on each side (3 total)
        startPage = Math.max(1, current - 1);
        endPage = Math.min(total, current + 1);
        
        // Adjust if we're near the beginning or end
        if (current <= 2) {
            startPage = 1;
            endPage = 3;
        } else if (current >= total - 1) {
            startPage = total - 2;
            endPage = total;
        }
    }
    
    // Add first page and ellipsis if needed
    if (startPage > 1) {
        addPageNumber(1, current === 1);
        if (startPage > 2) {
            addEllipsis();
        }
    }
    
    // Add the main page numbers
    for (let i = startPage; i <= endPage; i++) {
        addPageNumber(i, i === current);
    }
    
    // Add ellipsis and last page if needed
    if (endPage < total) {
        if (endPage < total - 1) {
            addEllipsis();
        }
        addPageNumber(total, current === total);
    }
}

function addPageNumber(pageNum, isActive) {
    const pageNumbersContainer = document.getElementById('page-numbers');
    const pageBtn = document.createElement('button');
    pageBtn.className = `page-number ${isActive ? 'active' : ''}`;
    pageBtn.textContent = pageNum;
    pageBtn.onclick = () => goToPage(pageNum);
    pageNumbersContainer.appendChild(pageBtn);
}

function addEllipsis() {
    const pageNumbersContainer = document.getElementById('page-numbers');
    const ellipsis = document.createElement('span');
    ellipsis.className = 'page-ellipsis';
    ellipsis.textContent = '...';
    pageNumbersContainer.appendChild(ellipsis);
}

function addPageButton(pageNum) {
    const pageNumbers = document.getElementById('page-numbers');
    const button = document.createElement('button');
    button.textContent = pageNum;
    button.className = 'page-number-btn' + (pageNum === currentPage ? ' active' : '');
    button.onclick = () => {
        currentPage = pageNum;
        updatePagination();
    };
    pageNumbers.appendChild(button);
}

function setFilter(filter) {
    currentFilter = filter;
    currentPage = 1; // Reset to first page when filtering
    updatePagination();
}

function setItemsPerPage(count) {
    if (count === 'auto') {
        itemsPerPage = calculateItemsPerPage();
    } else {
        itemsPerPage = parseInt(count);
    }
    currentPage = 1; // Reset to first page
    updatePagination();
}

// Responsive pagination - recalculate on window resize
function initializeResponsivePagination() {
    let resizeTimeout;
    
    window.addEventListener('resize', function() {
        clearTimeout(resizeTimeout);
        resizeTimeout = setTimeout(function() {
            const selector = document.getElementById('items-per-page');
            if (selector && selector.value === 'auto') {
                const newItemsPerPage = calculateItemsPerPage();
                if (newItemsPerPage !== itemsPerPage) {
                    itemsPerPage = newItemsPerPage;
                    updatePagination();
                    console.log(`Pagination updated for new viewport: ${itemsPerPage} items per page`);
                }
            }
        }, 250); // Debounce resize events
    });
}

// Initialize items per page selector
function initializeItemsPerPageSelector() {
    const selector = document.getElementById('items-per-page');
    if (selector) {
        selector.addEventListener('change', function() {
            setItemsPerPage(this.value);
        });
    }
}

// Global initialization function
function initializeDynamicPagination(containerId = 'gallery-container', gridId = 'gallery-grid') {
    console.log(`Initializing dynamic pagination for container: ${containerId}`);
    
    // Wait for DOM to be fully loaded
    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', function() {
            initializeDynamicPagination(containerId, gridId);
        });
        return;
    }
    
    // Initialize all pagination components
    initializePagination(containerId, gridId);
    initializeResponsivePagination();
    initializeItemsPerPageSelector();
    
    console.log(`Dynamic pagination initialized: ${itemsPerPage} items per page`);
}

// Export for global use
window.initializeDynamicPagination = initializeDynamicPagination;
window.setFilter = setFilter;
window.setItemsPerPage = setItemsPerPage;
