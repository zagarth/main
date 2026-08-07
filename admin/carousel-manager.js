/**
 * Carousel Management JavaScript
 * Handles carousel category selection and management from admin interface
 */

// Global state
let carouselCategories = [];
let currentCarouselConfig = null;

/**
 * Open the carousel management modal
 */
function openCarouselManager() {
    document.getElementById('carouselModal').style.display = 'block';
    loadCategories();
    loadCurrentConfig();
}

/**
 * Close the carousel management modal
 */
function closeCarouselManager() {
    document.getElementById('carouselModal').style.display = 'none';
}

/**
 * Load available categories from the database
 */
async function loadCategories() {
    const select = document.getElementById('carouselCategorySelect');
    select.innerHTML = '<option value="">Loading categories...</option>';
    
    try {
        const response = await fetch('carousel_filter_manager.php?action=categories');
        const data = await response.json();
        
        if (data.error) {
            throw new Error(data.error);
        }
        
        carouselCategories = data.categories || [];
        
        // Populate dropdown
        select.innerHTML = '<option value="">Choose a category...</option>';
        carouselCategories.forEach(category => {
            const option = document.createElement('option');
            option.value = category.name;
            option.textContent = `${category.display_name} (${category.count} items)`;
            select.appendChild(option);
        });
        
    } catch (error) {
        console.error('Error loading categories:', error);
        select.innerHTML = '<option value="">Error loading categories</option>';
        showMessage('Error loading categories: ' + error.message, 'error');
    }
}

/**
 * Load current carousel configuration
 */
async function loadCurrentConfig() {
    try {
        const response = await fetch('carousel_filter_manager.php?action=get');
        const data = await response.json();
        
        currentCarouselConfig = data;
        
        if (data.active && data.filter) {
            // Set the dropdown to current selection
            const select = document.getElementById('carouselCategorySelect');
            select.value = data.filter;
            loadCarouselPreview();
        }
        
    } catch (error) {
        console.error('Error loading current config:', error);
    }
}

/**
 * Load preview of selected category
 */
async function loadCarouselPreview() {
    const selectedCategory = document.getElementById('carouselCategorySelect').value;
    const previewDiv = document.getElementById('carouselPreview');
    const previewTitle = document.getElementById('previewTitle');
    const previewCount = document.getElementById('previewCount');
    const previewGrid = document.getElementById('previewGrid');
    const setBtn = document.getElementById('setCarouselBtn');
    
    if (!selectedCategory) {
        previewDiv.style.display = 'none';
        setBtn.disabled = true;
        return;
    }
    
    try {
        showMessage(`Loading preview for ${selectedCategory}...`);
        
        const response = await fetch(`carousel_filter_manager.php?action=items&collection=catalog&filter=${selectedCategory}`);
        const data = await response.json();
        
        if (data.error) {
            throw new Error(data.error);
        }
        
        const items = data.items || [];
        
        // Update preview
        const categoryInfo = carouselCategories.find(cat => cat.name === selectedCategory);
        previewTitle.textContent = categoryInfo ? categoryInfo.display_name : selectedCategory;
        previewCount.textContent = `${items.length} items available`;
        
        // Generate preview grid
        previewGrid.innerHTML = '';
        const maxPreview = Math.min(items.length, 12);
        
        for (let i = 0; i < maxPreview; i++) {
            const item = items[i];
            const previewItem = document.createElement('div');
            previewItem.style.cssText = `
                text-align: center; 
                padding: 10px; 
                border: 1px solid #ccc; 
                border-radius: 6px; 
                background: white;
            `;
            
            previewItem.innerHTML = `
                <div style="width: 80px; height: 60px; margin: 0 auto 8px; border: 1px solid #eee; border-radius: 4px; overflow: hidden;">
                    <img src="../${item.admin_relative_path || item.relative_path}" 
                         alt="${item.name}" 
                         style="width: 100%; height: 100%; object-fit: cover;"
                         onerror="this.src='data:image/svg+xml;base64,PHN2ZyB3aWR0aD0iMTAwIiBoZWlnaHQ9IjEwMCIgeG1sbnM9Imh0dHA6Ly93d3cudzMub3JnLzIwMDAvc3ZnIj48cmVjdCB3aWR0aD0iMTAwIiBoZWlnaHQ9IjEwMCIgZmlsbD0iI2VlZSIvPjx0ZXh0IHg9IjUwIiB5PSI1MCIgZm9udC1mYW1pbHk9IkFyaWFsIiBmb250LXNpemU9IjEwIiBmaWxsPSIjOTk5IiB0ZXh0LWFuY2hvcj0ibWlkZGxlIiBkeT0iLjNlbSI+Tm8gSW1hZ2U8L3RleHQ+PC9zdmc+'" />
                </div>
                <div style="font-size: 11px; font-weight: bold; color: #333; line-height: 1.2;">${item.name}</div>
                <div style="font-size: 9px; color: #666;">${item.product_id || ''}</div>
            `;
            
            previewGrid.appendChild(previewItem);
        }
        
        if (items.length > maxPreview) {
            const moreItem = document.createElement('div');
            moreItem.style.cssText = `
                display: flex; 
                align-items: center; 
                justify-content: center; 
                text-align: center; 
                padding: 10px; 
                border: 2px dashed #ccc; 
                border-radius: 6px; 
                background: #f9f9f9; 
                color: #666;
                font-size: 12px;
            `;
            moreItem.textContent = `+${items.length - maxPreview} more items`;
            previewGrid.appendChild(moreItem);
        }
        
        previewDiv.style.display = 'block';
        setBtn.disabled = false;
        
        showMessage(`Preview loaded: ${items.length} items found`, 'success');
        
    } catch (error) {
        console.error('Error loading preview:', error);
        showMessage('Error loading preview: ' + error.message, 'error');
        previewDiv.style.display = 'none';
        setBtn.disabled = true;
    }
}

/**
 * Set the selected category as active carousel
 */
async function setCarouselCategory() {
    const selectedCategory = document.getElementById('carouselCategorySelect').value;
    
    if (!selectedCategory) {
        showMessage('Please select a category first', 'error');
        return;
    }
    
    try {
        showMessage(`Setting carousel to ${selectedCategory}...`);
        
        const formData = new FormData();
        formData.append('action', 'set');
        formData.append('collection', 'catalog_products');
        formData.append('filter', selectedCategory);
        
        const response = await fetch('carousel_filter_manager.php', {
            method: 'POST',
            body: formData
        });
        
        const result = await response.json();
        
        if (result.success) {
            currentCarouselConfig = result.data;
            showMessage(`Carousel successfully set to ${selectedCategory}!`, 'success');
            
            // Update status in main page
            updateCarouselStatus(selectedCategory);
        } else {
            throw new Error(result.message || 'Failed to set carousel');
        }
        
    } catch (error) {
        console.error('Error setting carousel:', error);
        showMessage('Error setting carousel: ' + error.message, 'error');
    }
}

/**
 * Clear the current carousel
 */
async function clearCarousel() {
    if (!confirm('Are you sure you want to clear the current carousel?')) {
        return;
    }
    
    try {
        showMessage('Clearing carousel...');
        
        const response = await fetch('carousel_filter_manager.php?action=clear');
        const result = await response.json();
        
        if (result.success) {
            currentCarouselConfig = null;
            document.getElementById('carouselCategorySelect').value = '';
            document.getElementById('carouselPreview').style.display = 'none';
            document.getElementById('setCarouselBtn').disabled = true;
            
            showMessage('Carousel cleared successfully', 'success');
            updateCarouselStatus('None');
        } else {
            throw new Error(result.message || 'Failed to clear carousel');
        }
        
    } catch (error) {
        console.error('Error clearing carousel:', error);
        showMessage('Error clearing carousel: ' + error.message, 'error');
    }
}

/**
 * Preview the current active carousel
 */
async function previewCarousel() {
    try {
        const response = await fetch('carousel_filter_manager.php?action=get');
        const config = await response.json();
        
        if (config.active && config.filter) {
            showMessage(`Current carousel: ${config.filter}`, 'info');
            window.open('../index.php', '_blank');
        } else {
            showMessage('No active carousel set', 'warning');
        }
        
    } catch (error) {
        console.error('Error previewing carousel:', error);
        showMessage('Error loading carousel info: ' + error.message, 'error');
    }
}

/**
 * Update carousel status display on main page
 */
function updateCarouselStatus(category) {
    // This could update a status indicator on the main admin page
    console.log('Carousel status updated:', category);
}

/**
 * Show message to user
 */
function showMessage(message, type = 'info') {
    // Create or update a message display
    let messageDiv = document.getElementById('carouselMessage');
    if (!messageDiv) {
        messageDiv = document.createElement('div');
        messageDiv.id = 'carouselMessage';
        messageDiv.style.cssText = `
            position: fixed; 
            top: 20px; 
            right: 20px; 
            padding: 12px 16px; 
            border-radius: 6px; 
            color: white; 
            font-size: 14px; 
            z-index: 10000;
            max-width: 300px;
        `;
        document.body.appendChild(messageDiv);
    }
    
    // Set color based on type
    const colors = {
        'success': '#28a745',
        'error': '#dc3545',
        'warning': '#ffc107',
        'info': '#17a2b8'
    };
    
    messageDiv.style.backgroundColor = colors[type] || colors.info;
    messageDiv.textContent = message;
    messageDiv.style.display = 'block';
    
    // Auto hide after 3 seconds
    setTimeout(() => {
        messageDiv.style.display = 'none';
    }, 3000);
}

/**
 * Close modal when clicking outside of it
 */
window.onclick = function(event) {
    const modal = document.getElementById('carouselModal');
    if (event.target === modal) {
        closeCarouselManager();
    }
}