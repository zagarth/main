/**
 * Collection Filters
 * 
 * Provides filtering functionality for collection galleries
 * Works with pagination system for seamless user experience
 */

// Filter state
let activeFilters = new Set(['all']);
let filterConfig = {};

// Initialize filter system
function initializeFilters(config = {}) {
    filterConfig = {
        animationDuration: 300,
        showItemCount: true,
        multiSelect: false,
        ...config
    };
    
    setupFilterButtons();
    updateFilterCounts();
    
    console.log('Collection filters initialized');
}

function setupFilterButtons() {
    const filterButtons = document.querySelectorAll('[data-filter]');
    
    filterButtons.forEach(button => {
        button.addEventListener('click', function() {
            const filter = this.dataset.filter;
            handleFilterClick(filter, this);
        });
    });
}

function handleFilterClick(filter, buttonElement) {
    if (filterConfig.multiSelect) {
        handleMultiSelectFilter(filter, buttonElement);
    } else {
        handleSingleSelectFilter(filter, buttonElement);
    }
    
    applyFilters();
    updateFilterCounts();
}

function handleSingleSelectFilter(filter, buttonElement) {
    // Clear all active filters
    activeFilters.clear();
    activeFilters.add(filter);
    
    // Update button states
    document.querySelectorAll('[data-filter]').forEach(btn => {
        btn.classList.remove('active');
    });
    buttonElement.classList.add('active');
}

function handleMultiSelectFilter(filter, buttonElement) {
    if (filter === 'all') {
        // If "all" is clicked, clear other filters
        activeFilters.clear();
        activeFilters.add('all');
        
        document.querySelectorAll('[data-filter]').forEach(btn => {
            btn.classList.remove('active');
        });
        buttonElement.classList.add('active');
    } else {
        // Remove "all" if specific filter is selected
        activeFilters.delete('all');
        
        // Toggle this filter
        if (activeFilters.has(filter)) {
            activeFilters.delete(filter);
            buttonElement.classList.remove('active');
        } else {
            activeFilters.add(filter);
            buttonElement.classList.add('active');
        }
        
        // If no filters active, default to "all"
        if (activeFilters.size === 0) {
            activeFilters.add('all');
            document.querySelector('[data-filter="all"]')?.classList.add('active');
        }
        
        // Update "all" button state
        const allButton = document.querySelector('[data-filter="all"]');
        if (allButton) {
            allButton.classList.toggle('active', activeFilters.has('all'));
        }
    }
}

function applyFilters() {
    const items = document.querySelectorAll('.paginated-item');
    let visibleCount = 0;
    
    items.forEach(item => {
        const itemCategories = (item.dataset.category || '').split(' ');
        const itemTags = (item.dataset.tags || '').split(' ');
        const allItemData = [...itemCategories, ...itemTags];
        
        let shouldShow = false;
        
        if (activeFilters.has('all')) {
            shouldShow = true;
        } else {
            // Check if item matches any active filter
            shouldShow = Array.from(activeFilters).some(filter => 
                allItemData.includes(filter)
            );
        }
        
        // Apply filter with animation
        if (shouldShow) {
            item.style.display = 'block';
            item.classList.add('filter-visible');
            item.classList.remove('filter-hidden');
            visibleCount++;
        } else {
            item.classList.add('filter-hidden');
            item.classList.remove('filter-visible');
            
            // Hide after animation
            setTimeout(() => {
                if (item.classList.contains('filter-hidden')) {
                    item.style.display = 'none';
                }
            }, filterConfig.animationDuration);
        }
    });
    
    // Update pagination system
    if (typeof window.setFilter === 'function') {
        const filterString = activeFilters.has('all') ? 'all' : Array.from(activeFilters).join(',');
        window.setFilter(filterString);
    }
    
    // Dispatch custom event for other components
    document.dispatchEvent(new CustomEvent('filtersChanged', {
        detail: {
            activeFilters: Array.from(activeFilters),
            visibleCount: visibleCount
        }
    }));
}

function updateFilterCounts() {
    if (!filterConfig.showItemCount) return;
    
    const filterButtons = document.querySelectorAll('[data-filter]');
    
    filterButtons.forEach(button => {
        const filter = button.dataset.filter;
        const count = getFilterItemCount(filter);
        
        const countElement = button.querySelector('.filter-count') || 
                           createCountElement(button);
        
        countElement.textContent = `(${count})`;
    });
}

function getFilterItemCount(filter) {
    if (filter === 'all') {
        return document.querySelectorAll('.paginated-item').length;
    }
    
    const items = document.querySelectorAll('.paginated-item');
    let count = 0;
    
    items.forEach(item => {
        const itemCategories = (item.dataset.category || '').split(' ');
        const itemTags = (item.dataset.tags || '').split(' ');
        const allItemData = [...itemCategories, ...itemTags];
        
        if (allItemData.includes(filter)) {
            count++;
        }
    });
    
    return count;
}

function createCountElement(button) {
    const countElement = document.createElement('span');
    countElement.className = 'filter-count';
    button.appendChild(countElement);
    return countElement;
}

function clearAllFilters() {
    activeFilters.clear();
    activeFilters.add('all');
    
    document.querySelectorAll('[data-filter]').forEach(btn => {
        btn.classList.remove('active');
    });
    
    const allButton = document.querySelector('[data-filter="all"]');
    if (allButton) {
        allButton.classList.add('active');
    }
    
    applyFilters();
}

function setActiveFilters(filters) {
    activeFilters.clear();
    filters.forEach(filter => activeFilters.add(filter));
    
    // Update button states
    document.querySelectorAll('[data-filter]').forEach(btn => {
        const isActive = activeFilters.has(btn.dataset.filter);
        btn.classList.toggle('active', isActive);
    });
    
    applyFilters();
}

// Export functions for global use
window.initializeFilters = initializeFilters;
window.clearAllFilters = clearAllFilters;
window.setActiveFilters = setActiveFilters;

// Auto-initialize when DOM is ready
document.addEventListener('DOMContentLoaded', function() {
    if (document.querySelector('[data-filter]')) {
        initializeFilters();
    }
});
