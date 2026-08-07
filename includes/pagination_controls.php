<?php
/**
 * Reusable Pagination Controls Include
 * 
 * Displays pagination with navigation buttons and limited page numbers
 * Page info appears below the controls
 */
?>

<div id="pagination-controls" class="pagination-container">
    <!-- Main pagination controls -->
    <div class="pagination-nav">
        <button id="prev-page" onclick="prevPage()" class="pagination-btn prev-btn">
            <span class="btn-icon">‹</span>
            <span class="btn-text">Previous</span>
        </button>
        
        <!-- Page numbers (max 3 between buttons) -->
        <div id="page-numbers" class="page-numbers">
            <!-- Dynamically populated by JavaScript -->
        </div>
        
        <button id="next-page" onclick="nextPage()" class="pagination-btn next-btn">
            <span class="btn-text">Next</span>
            <span class="btn-icon">›</span>
        </button>
    </div>
    
    <!-- Page info below controls -->
    <div class="pagination-info">
        <div id="page-info" class="page-info">
            Page 1 of 1
        </div>
        <div id="items-info" class="items-info">
            Showing 1-12 of 24 items
        </div>
    </div>
</div>

<style>
.pagination-container {
    display: flex;
    flex-direction: column;
    align-items: center;
    margin: 2rem 0;
    gap: 1rem;
}

.pagination-nav {
    display: flex;
    align-items: center;
    gap: 0.5rem;
    background: rgba(255, 255, 255, 0.9);
    padding: 0.5rem;
    border-radius: 12px;
    box-shadow: 0 2px 8px rgba(0, 0, 0, 0.1);
    backdrop-filter: blur(10px);
}

.pagination-btn {
    display: flex;
    align-items: center;
    gap: 0.5rem;
    padding: 0.75rem 1.25rem;
    background: linear-gradient(135deg, #FFD700, #FFA500);
    color: #333;
    border: none;
    border-radius: 8px;
    cursor: pointer;
    font-weight: 600;
    font-size: 0.9rem;
    transition: all 0.3s ease;
    box-shadow: 0 2px 4px rgba(0, 0, 0, 0.1);
}

.pagination-btn:hover:not(:disabled) {
    background: linear-gradient(135deg, #FFA500, #FF8C00);
    transform: translateY(-2px);
    box-shadow: 0 4px 8px rgba(0, 0, 0, 0.2);
}

.pagination-btn:disabled {
    background: #e0e0e0;
    color: #999;
    cursor: not-allowed;
    transform: none;
    box-shadow: none;
}

.pagination-btn .btn-icon {
    font-size: 1.2rem;
    font-weight: bold;
}

.page-numbers {
    display: flex;
    align-items: center;
    gap: 0.25rem;
    margin: 0 1rem;
}

.page-number-btn {
    padding: 0.5rem 0.75rem;
    background: transparent;
    color: #666;
    border: 1px solid #ddd;
    border-radius: 6px;
    cursor: pointer;
    font-weight: 500;
    transition: all 0.3s ease;
    min-width: 40px;
    text-align: center;
}

.page-number-btn:hover {
    background: #f0f0f0;
    border-color: #FFD700;
    color: #333;
}

.page-number-btn.active {
    background: linear-gradient(135deg, #FFD700, #FFA500);
    color: #333;
    border-color: #FFD700;
    font-weight: 700;
}

.page-ellipsis {
    padding: 0.5rem 0.25rem;
    color: #999;
    font-weight: bold;
}

.pagination-info {
    display: flex;
    flex-direction: column;
    align-items: center;
    gap: 0.25rem;
    color: #666;
    font-size: 0.9rem;
}

.page-info {
    font-weight: 600;
    color: #333;
}

.items-info {
    font-size: 0.85rem;
    color: #777;
}

/* Responsive design */
@media (max-width: 768px) {
    .pagination-nav {
        padding: 0.4rem;
        gap: 0.3rem;
    }
    
    .pagination-btn {
        padding: 0.6rem 1rem;
        font-size: 0.85rem;
    }
    
    .pagination-btn .btn-text {
        display: none;
    }
    
    .page-numbers {
        margin: 0 0.5rem;
    }
    
    .page-number-btn {
        padding: 0.4rem 0.6rem;
        min-width: 35px;
        font-size: 0.85rem;
    }
}

@media (max-width: 480px) {
    .pagination-container {
        margin: 1.5rem 0;
    }
    
    .pagination-nav {
        padding: 0.3rem;
        gap: 0.2rem;
    }
    
    .pagination-btn {
        padding: 0.5rem 0.8rem;
    }
    
    .page-numbers {
        margin: 0 0.3rem;
        gap: 0.15rem;
    }
    
    .page-number-btn {
        padding: 0.35rem 0.5rem;
        min-width: 30px;
        font-size: 0.8rem;
    }
    
    .pagination-info {
        font-size: 0.8rem;
    }
}
</style>

<script>
// PaginationControls: lightweight class for pages to manage shared pagination UI
if (typeof window.PaginationControls === 'undefined') {
    window.PaginationControls = class PaginationControls {
        constructor(options = {}) {
            this.prevId = options.prevId || 'prev-page';
            this.nextId = options.nextId || 'next-page';
            this.pageNumbersId = options.pageNumbersId || 'page-numbers';
            this.pageInfoId = options.pageInfoId || 'page-info';
            this.itemsInfoId = options.itemsInfoId || 'items-info';
            this.containerId = options.containerId || 'pagination-controls';

            this.prevBtn = document.getElementById(this.prevId);
            this.nextBtn = document.getElementById(this.nextId);
            this.pageNumbers = document.getElementById(this.pageNumbersId);
            this.pageInfo = document.getElementById(this.pageInfoId);
            this.itemsInfo = document.getElementById(this.itemsInfoId);
            this.container = document.getElementById(this.containerId);

            this.onPrev = options.onPrev || function() { if (window.prevPage) window.prevPage(); };
            this.onNext = options.onNext || function() { if (window.nextPage) window.nextPage(); };
            this.onGoTo = options.onGoTo || function(n) { if (window.goToPage) window.goToPage(n); };
            this.onShowAll = options.onShowAll || null;

            // attach basic handlers
            if (this.prevBtn) {
                this.prevBtn.addEventListener('click', (e) => {
                    if (this._prevMode === 'showAll' && typeof this.onShowAll === 'function') {
                        this.onShowAll();
                    } else {
                        this.onPrev();
                    }
                });
            }

            if (this.nextBtn) {
                this.nextBtn.addEventListener('click', (e) => {
                    this.onNext();
                });
            }

            this._prevMode = 'previous';
        }

        // Update UI: filteredCount = total filtered items, currentPage, itemsPerPage
        update({ filteredCount = 0, currentPage = 1, itemsPerPage = 6 } = {}) {
            const totalPages = Math.max(1, Math.ceil(filteredCount / itemsPerPage));

            // update page info text
            if (this.pageInfo) this.pageInfo.textContent = `Page ${currentPage} of ${totalPages}`;

            // update items info text
            if (this.itemsInfo) {
                const startItem = Math.min(filteredCount, (currentPage - 1) * itemsPerPage + 1);
                const endItem = Math.min(filteredCount, currentPage * itemsPerPage);
                if (filteredCount === 0) {
                    this.itemsInfo.textContent = `Showing 0 of 0 items`;
                } else {
                    this.itemsInfo.textContent = `Showing ${startItem}-${endItem} of ${filteredCount} items`;
                }
            }

            // update prev/next disabled state
            if (this.prevBtn) {
                this.prevBtn.disabled = false;
                // On first page, expose a 'Show All' affordance but don't change behavior here
                if (currentPage <= 1) {
                    this.prevBtn.style.opacity = '1';
                    this.prevBtn.innerHTML = '<span class="btn-icon">👁</span><span class="btn-text">Show All</span>';
                    this._prevMode = 'showAll';
                } else {
                    this.prevBtn.style.opacity = '1';
                    this.prevBtn.innerHTML = '<span class="btn-icon">‹</span><span class="btn-text">Previous</span>';
                    this._prevMode = 'previous';
                }
            }

            if (this.nextBtn) {
                this.nextBtn.disabled = currentPage >= totalPages;
                this.nextBtn.style.opacity = currentPage >= totalPages ? '0.5' : '1';
            }

            // render page numbers (max 3 visible)
            if (this.pageNumbers) {
                this.pageNumbers.innerHTML = '';
                if (totalPages > 1) {
                    let startPage = Math.max(1, currentPage - 1);
                    let endPage = Math.min(totalPages, startPage + 2);
                    if (endPage - startPage < 2) startPage = Math.max(1, endPage - 2);

                    for (let i = startPage; i <= endPage; i++) {
                        const btn = document.createElement('button');
                        btn.textContent = i;
                        btn.className = 'page-number-btn' + (i === currentPage ? ' active' : '');
                        btn.addEventListener('click', () => this.onGoTo(i));
                        this.pageNumbers.appendChild(btn);
                    }
                }
            }

            // show/hide container if only one page
            if (this.container) {
                this.container.style.display = (totalPages <= 1) ? 'none' : 'flex';
            }
        }

        // Utility to quickly switch modes when pages want to show all items
        setShowAllMode() {
            if (this.prevBtn) this.prevBtn.innerHTML = '<span class="btn-icon">📄</span><span class="btn-text">Show Pages</span>';
            if (this.nextBtn) { this.nextBtn.disabled = true; this.nextBtn.style.opacity = '0.5'; }
            if (this.pageNumbers) this.pageNumbers.innerHTML = '';
            if (this.pageInfo) this.pageInfo.textContent = 'Showing All Items';
            if (this.itemsInfo) this.itemsInfo.textContent = '';
        }
    };

    // Helper to create a single shared instance per page
    window.createPaginationControls = function(options) {
        window.paginationControlsInstance = new window.PaginationControls(options);
        return window.paginationControlsInstance;
    };

    // Backwards-compatible global wrappers that call the instance if present
    if (!window.prevPage) {
        window.prevPage = function() {
            if (window.paginationControlsInstance && typeof window.paginationControlsInstance.onPrev === 'function') {
                window.paginationControlsInstance.onPrev();
            }
        };
    }
    if (!window.nextPage) {
        window.nextPage = function() {
            if (window.paginationControlsInstance && typeof window.paginationControlsInstance.onNext === 'function') {
                window.paginationControlsInstance.onNext();
            }
        };
    }
    if (!window.goToPage) {
        window.goToPage = function(n) {
            if (window.paginationControlsInstance && typeof window.paginationControlsInstance.onGoTo === 'function') {
                window.paginationControlsInstance.onGoTo(n);
            }
        };
    }
}
</script>
