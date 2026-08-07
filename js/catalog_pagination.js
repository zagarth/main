/* /js/catalog_pagination.js
 * Shared listing pagination + image rotation for catalog pages.
 * Mirrors the behaviour from Bands.php (Show All, sliding 3-page window,
 * dynamic items-per-page based on viewport).
 *
 * Usage on a listing page:
 *   <div class="gallery-grid" id="jewelry-gallery"> … .paginated-item … </div>
 *   <?php include 'includes/pagination_controls.php'; ?>
 *   <script src="/js/catalog_pagination.js" defer></script>
 *   <script>document.addEventListener('DOMContentLoaded', () => CatalogPagination.init());</script>
 */
(function (global) {
    'use strict';

    let currentPage = 1;
    let itemsPerPage = 12;
    let totalPages = 1;
    let allItems = [];

    function calculateItemsPerPage() {
        const container = document.querySelector('.gallery-container');
        const grid = document.querySelector('.gallery-grid');
        if (!container || !grid) return 12;

        const viewportWidth = window.innerWidth;
        const containerStyle = window.getComputedStyle(container);
        const containerPadding = parseInt(containerStyle.paddingLeft) + parseInt(containerStyle.paddingRight);
        const availableWidth = Math.min(1200, viewportWidth - 40) - containerPadding;

        // Match Bands.php item min width
        const minItemWidth = 260;
        const gap = 20;
        const columns = Math.max(1, Math.floor((availableWidth + gap) / (minItemWidth + gap)));

        // "Enough to fill one row" baseline then bump to two rows on tall viewports
        const viewportHeight = window.innerHeight;
        const rows = viewportHeight >= 900 ? 3 : (viewportHeight >= 650 ? 2 : 1);
        return Math.max(columns, Math.min(24, columns * rows));
    }

    function updatePagination() {
        totalPages = Math.max(1, Math.ceil(allItems.length / itemsPerPage));
        if (currentPage > totalPages) currentPage = totalPages;
        if (currentPage < 1) currentPage = 1;

        allItems.forEach(it => { it.style.display = 'none'; it.classList.remove('visible'); });
        const start = (currentPage - 1) * itemsPerPage;
        const slice = allItems.slice(start, start + itemsPerPage);
        slice.forEach((it, i) => {
            it.style.display = '';
            setTimeout(() => it.classList.add('visible'), i * 60);
        });
        renderControls();
    }

    function renderControls() {
        const prev = document.getElementById('prev-page');
        const next = document.getElementById('next-page');
        const pageInfo = document.getElementById('page-info');
        const itemsInfo = document.getElementById('items-info');
        const pageNums = document.getElementById('page-numbers');
        const wrapper = document.getElementById('pagination-controls');

        if (prev) {
            prev.disabled = false;
            prev.style.opacity = '1';
            if (currentPage <= 1) {
                prev.innerHTML = '<span class="btn-icon">👁</span><span class="btn-text">Show All</span>';
                prev.onclick = showAll;
            } else {
                prev.innerHTML = '<span class="btn-icon">‹</span><span class="btn-text">Previous</span>';
                prev.onclick = () => { currentPage--; updatePagination(); scrollToTop(); };
            }
        }
        if (next) {
            next.disabled = currentPage >= totalPages;
            next.style.opacity = currentPage >= totalPages ? '0.5' : '1';
            next.onclick = () => { if (currentPage < totalPages) { currentPage++; updatePagination(); scrollToTop(); } };
        }
        if (pageNums) {
            pageNums.innerHTML = '';
            if (totalPages > 1) {
                let s = Math.max(1, currentPage - 1);
                let e = Math.min(totalPages, s + 2);
                if (e - s < 2) s = Math.max(1, e - 2);
                for (let i = s; i <= e; i++) {
                    const b = document.createElement('button');
                    b.textContent = i;
                    b.className = 'page-number-btn' + (i === currentPage ? ' active' : '');
                    b.onclick = () => { currentPage = i; updatePagination(); scrollToTop(); };
                    pageNums.appendChild(b);
                }
            }
        }
        if (pageInfo) pageInfo.textContent = `Page ${currentPage} of ${totalPages}`;
        if (itemsInfo) {
            const startN = (currentPage - 1) * itemsPerPage + 1;
            const endN = Math.min(currentPage * itemsPerPage, allItems.length);
            itemsInfo.textContent = `Showing ${startN}-${endN} of ${allItems.length} items`;
        }
        if (wrapper) wrapper.style.display = totalPages <= 1 ? 'none' : 'flex';
    }

    function showAll() {
        allItems.forEach((it, i) => {
            it.style.display = '';
            setTimeout(() => it.classList.add('visible'), i * 30);
        });
        const prev = document.getElementById('prev-page');
        const next = document.getElementById('next-page');
        const pageInfo = document.getElementById('page-info');
        const itemsInfo = document.getElementById('items-info');
        const pageNums = document.getElementById('page-numbers');
        if (prev) {
            prev.innerHTML = '<span class="btn-icon">📄</span><span class="btn-text">Show Pages</span>';
            prev.onclick = () => { currentPage = 1; updatePagination(); };
        }
        if (next) { next.disabled = true; next.style.opacity = '0.5'; }
        if (pageNums) pageNums.innerHTML = '';
        if (pageInfo) pageInfo.textContent = 'Showing All Items';
        if (itemsInfo) itemsInfo.textContent = `Showing all ${allItems.length} items`;
    }

    function scrollToTop() {
        const g = document.getElementById('jewelry-gallery');
        if (g) g.scrollIntoView({ behavior: 'smooth', block: 'start' });
    }

    function initImageRotation() {
        document.querySelectorAll('.rotating-image-container').forEach(c => {
            let variants;
            try { variants = JSON.parse(c.dataset.variants || '[]'); } catch { variants = []; }
            if (variants.length <= 1) return;
            c.addEventListener('click', function (e) {
                e.preventDefault();
                let cur = (parseInt(c.dataset.currentVariant) || 0) + 1;
                cur = cur % variants.length;
                const img = c.querySelector('.rotating-image');
                if (img) img.src = (c.dataset.imageDir || '') + '/' + variants[cur];
                c.dataset.currentVariant = cur;
            });
        });
    }

    let resizeTimer;
    window.addEventListener('resize', () => {
        clearTimeout(resizeTimer);
        resizeTimer = setTimeout(() => {
            const next = calculateItemsPerPage();
            if (Math.abs(next - itemsPerPage) >= 2) {
                itemsPerPage = next;
                currentPage = 1;
                updatePagination();
            }
        }, 250);
    });

    global.CatalogPagination = {
        init: function () {
            allItems = Array.from(document.querySelectorAll('.paginated-item'));
            if (!allItems.length) return;
            itemsPerPage = calculateItemsPerPage();
            updatePagination();
            initImageRotation();
        }
    };
    // pagination_controls.php uses onclick="prevPage()" / nextPage() — provide globals
    global.prevPage = function () {
        const b = document.getElementById('prev-page');
        if (b && b.onclick) b.onclick();
    };
    global.nextPage = function () {
        const b = document.getElementById('next-page');
        if (b && b.onclick) b.onclick();
    };
})(window);
