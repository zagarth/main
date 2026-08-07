/**
 * Search Modal JavaScript Functions
 * Handles both retailer and product search functionality
 */

// Open the search modal
function openSearchModal() {
    document.getElementById('search-modal').style.display = 'block';
}

// Close the search modal
function closeSearchModal() {
    document.getElementById('search-modal').style.display = 'none';
}

// Switch between search tabs
function switchSearchTab(tabName) {
    // Update tab buttons
    const retailersTab = document.getElementById('retailers-tab');
    const productsTab = document.getElementById('products-tab');
    
    // Update tab content visibility
    const retailersContent = document.getElementById('retailers-content');
    const productsContent = document.getElementById('products-content');
    
    if (tabName === 'retailers') {
        // Activate retailers tab
        retailersTab.style.background = 'linear-gradient(145deg, #0066CC, #004499)';
        retailersTab.style.color = 'white';
        productsTab.style.background = 'transparent';
        productsTab.style.color = '#666';
        
        // Show retailers content, hide products
        retailersContent.style.display = 'block';
        productsContent.style.display = 'none';
    } else if (tabName === 'products') {
        // Activate products tab
        productsTab.style.background = 'linear-gradient(145deg, #0066CC, #004499)';
        productsTab.style.color = 'white';
        retailersTab.style.background = 'transparent';
        retailersTab.style.color = '#666';
        
        // Show products content, hide retailers
        productsContent.style.display = 'block';
        retailersContent.style.display = 'none';
    }
}

// Search retailers by text input
function searchRetailers() {
    const searchTerm = document.getElementById('location-search').value.toLowerCase().trim();
    const resultsDiv = document.getElementById('retailer-results');
    
    if (searchTerm === '') {
        resultsDiv.innerHTML = '<p style="text-align: center; color: #666; font-style: italic;">Start typing to search for retailers...</p>';
        return;
    }
    
    // Check if retailer locations are available (from index.php)
    if (typeof retailerLocations === 'undefined') {
        resultsDiv.innerHTML = '<p style="text-align: center; color: #999;">Retailer search is only available on the main page. <a href="/" style="color: #0066CC;">Go to main page</a></p>';
        return;
    }
    
    const matchedRetailers = retailerLocations.filter(retailer => {
        return retailer.name.toLowerCase().includes(searchTerm) ||
               retailer.city.toLowerCase().includes(searchTerm) ||
               retailer.province.toLowerCase().includes(searchTerm) ||
               retailer.address.toLowerCase().includes(searchTerm);
    });
    
    // Show results count header
    let html = `<div style="margin-bottom: 10px; padding: 8px; background: #f8f9fa; border-radius: 6px; text-align: center; color: #666; font-size: 14px;">
        Found <strong style="color: #0066CC;">${matchedRetailers.length}</strong> retailer${matchedRetailers.length !== 1 ? 's' : ''} matching "${searchTerm}"
    </div>`;
    
    if (matchedRetailers.length === 0) {
        resultsDiv.innerHTML = html + '<p style="text-align: center; color: #999; padding: 20px;">No retailers found matching your search.</p>';
        return;
    }
    
    // Show all matching results (no limit)
    matchedRetailers.forEach(retailer => {
        html += `
            <div style="padding: 10px; border-bottom: 1px solid #eee; cursor: pointer; transition: all 0.3s;" 
                 onclick="selectRetailer(${retailer.lat}, ${retailer.lng}, '${retailer.name.replace(/'/g, "\\'")}')"
                 onmouseover="this.style.background='#f0f8ff'; this.style.paddingLeft='15px'" 
                 onmouseout="this.style.background='transparent'; this.style.paddingLeft='10px'">
                <div style="font-weight: 600; color: #0066CC; margin-bottom: 3px;">${retailer.name}</div>
                <div style="font-size: 13px; color: #666; margin-bottom: 2px;">${retailer.address}</div>
                <div style="font-size: 12px; color: #999; display: flex; gap: 15px;">
                    <span>📍 ${retailer.city}, ${retailer.province}</span>
                    ${retailer.phone ? `<span>📞 ${retailer.phone}</span>` : ''}
                </div>
            </div>
        `;
    });
    
    resultsDiv.innerHTML = html;
    resultsDiv.scrollTop = 0;
}

// Filter retailers by province
function filterByProvince(province) {
    if (typeof retailerLocations === 'undefined') {
        document.getElementById('retailer-results').innerHTML = '<p style="text-align: center; color: #999;">Retailer search is only available on the main page. <a href="/" style="color: #0066CC;">Go to main page</a></p>';
        return;
    }
    
    const matchedRetailers = retailerLocations.filter(retailer => 
        retailer.province === province
    );
    
    // Show results with province header
    let html = `<div style="margin-bottom: 10px; padding: 10px; background: linear-gradient(145deg, #0066CC, #004499); color: white; border-radius: 6px; text-align: center; font-weight: bold;">
        <div style="font-size: 16px; margin-bottom: 3px;">${getProvinceName(province)}</div>
        <div style="font-size: 13px; opacity: 0.9;">${matchedRetailers.length} retailer${matchedRetailers.length !== 1 ? 's' : ''}</div>
    </div>`;
    
    if (matchedRetailers.length === 0) {
        html += '<p style="text-align: center; color: #999; padding: 20px;">No retailers found in this province.</p>';
    } else {
        matchedRetailers.forEach(retailer => {
            html += `
                <div style="padding: 10px; border-bottom: 1px solid #eee; cursor: pointer; transition: all 0.3s;" 
                     onclick="selectRetailer(${retailer.lat}, ${retailer.lng}, '${retailer.name.replace(/'/g, "\\'")}')"
                     onmouseover="this.style.background='#f0f8ff'; this.style.paddingLeft='15px'" 
                     onmouseout="this.style.background='transparent'; this.style.paddingLeft='10px'">
                    <div style="font-weight: 600; color: #0066CC; margin-bottom: 3px;">${retailer.name}</div>
                    <div style="font-size: 13px; color: #666; margin-bottom: 2px;">${retailer.address}</div>
                    <div style="font-size: 12px; color: #999; display: flex; gap: 15px;">
                        <span>📍 ${retailer.city}</span>
                        ${retailer.phone ? `<span>📞 ${retailer.phone}</span>` : ''}
                    </div>
                </div>
            `;
        });
    }
    
    document.getElementById('retailer-results').innerHTML = html;
    document.getElementById('retailer-results').scrollTop = 0;
    document.getElementById('location-search').value = province;
}

// Clear search and reset
function clearSearch() {
    document.getElementById('location-search').value = '';
    document.getElementById('retailer-results').innerHTML = '<p style="text-align: center; color: #666; font-style: italic; padding: 40px 20px;">Start typing to search for retailers...<br><br>Or click <strong style="color: #0066CC; cursor: pointer;" onclick="showAllRetailersList()">View All List</strong> to see all retailers grouped by province.</p>';
}

// Show all retailers grouped by province with pagination
let currentlyDisplayedRetailers = 0;
const retailersPerPage = 50;

function showAllRetailersList() {
    if (typeof retailerLocations === 'undefined') {
        document.getElementById('retailer-results').innerHTML = '<p style="text-align: center; color: #999;">Retailer list is only available on the main page. <a href="/" style="color: #0066CC;">Go to main page</a></p>';
        return;
    }
    
    // Group retailers by province
    const grouped = {};
    retailerLocations.forEach(retailer => {
        if (!grouped[retailer.province]) {
            grouped[retailer.province] = [];
        }
        grouped[retailer.province].push(retailer);
    });
    
    // Sort provinces alphabetically
    const provinces = Object.keys(grouped).sort();
    
    // Reset pagination
    currentlyDisplayedRetailers = 0;
    
    // Build HTML with province groupings
    let html = `<div style="margin-bottom: 15px; padding: 10px; background: linear-gradient(135deg, #0066CC, #004499); color: white; border-radius: 8px; text-align: center; font-weight: bold;">
        <div style="font-size: 18px; margin-bottom: 5px;">All Authorized Retailers</div>
        <div style="font-size: 13px; opacity: 0.9;">${retailerLocations.length} locations across Canada</div>
    </div>`;
    
    html += '<div id="retailer-list-container">';
    
    provinces.forEach(province => {
        const provinceRetailers = grouped[province];
        html += `
            <div style="margin-bottom: 20px;">
                <div style="background: linear-gradient(145deg, #f8f9fa, #e9ecef); padding: 10px 15px; border-left: 4px solid #0066CC; font-weight: bold; color: #333; margin-bottom: 10px; border-radius: 4px; display: flex; justify-content: space-between; align-items: center;">
                    <span style="font-size: 16px;">${getProvinceName(province)}</span>
                    <span style="background: #0066CC; color: white; padding: 3px 10px; border-radius: 12px; font-size: 12px;">${provinceRetailers.length}</span>
                </div>
                <div class="province-retailers">`;
        
        provinceRetailers.forEach(retailer => {
            html += `
                <div style="padding: 10px; border-bottom: 1px solid #eee; cursor: pointer; transition: all 0.3s;" 
                     onclick="selectRetailer(${retailer.lat}, ${retailer.lng}, '${retailer.name.replace(/'/g, "\\'")}')"
                     onmouseover="this.style.background='#f0f8ff'; this.style.paddingLeft='15px'" 
                     onmouseout="this.style.background='transparent'; this.style.paddingLeft='10px'">
                    <div style="font-weight: 600; color: #0066CC; margin-bottom: 3px;">${retailer.name}</div>
                    <div style="font-size: 13px; color: #666; margin-bottom: 2px;">${retailer.address}</div>
                    <div style="font-size: 12px; color: #999; display: flex; gap: 15px;">
                        <span>📍 ${retailer.city}</span>
                        ${retailer.phone ? `<span>📞 ${retailer.phone}</span>` : ''}
                    </div>
                </div>
            `;
        });
        
        html += `</div></div>`;
    });
    
    html += '</div>';
    
    const resultsDiv = document.getElementById('retailer-results');
    resultsDiv.innerHTML = html;
    resultsDiv.scrollTop = 0; // Scroll to top
    
    // Update search placeholder
    document.getElementById('location-search').value = '';
    document.getElementById('location-search').placeholder = 'Search all retailers...';
}

// Helper function to get full province name
function getProvinceName(code) {
    const provinceNames = {
        'AB': 'Alberta',
        'BC': 'British Columbia',
        'MB': 'Manitoba',
        'NB': 'New Brunswick',
        'NL': 'Newfoundland and Labrador',
        'NT': 'Northwest Territories',
        'NS': 'Nova Scotia',
        'NU': 'Nunavut',
        'ON': 'Ontario',
        'PE': 'Prince Edward Island',
        'QC': 'Quebec',
        'SK': 'Saskatchewan',
        'YT': 'Yukon'
    };
    return provinceNames[code] || code;
}

// Select a specific retailer and show on map (only works on index page)
function selectRetailer(lat, lng, name) {
    closeSearchModal();
    if (typeof retailerMap !== 'undefined' && retailerMap) {
        retailerMap.setView([lat, lng], 12);
        // Find and open the popup for this retailer
        retailerMap.eachLayer(function(marker) {
            if (marker.getLatLng) {
                const markerLatLng = marker.getLatLng();
                if (Math.abs(markerLatLng.lat - lat) < 0.001 && Math.abs(markerLatLng.lng - lng) < 0.001) {
                    marker.openPopup();
                }
            }
        });
        // Scroll to map
        const mapElement = document.getElementById('canada-map');
        if (mapElement) {
            mapElement.scrollIntoView({ behavior: 'smooth' });
        }
    } else {
        // Redirect to main page with retailer info
        window.location.href = `/?retailer=${encodeURIComponent(name)}`;
    }
}

// Note: findnearme() and viewAllCanada() functions are defined in index.php
// If not available (on other pages), fallback to homepage navigation

// Product search functionality
let currentProductCategory = null;

// Search products by text input
function searchProducts() {
    const searchTerm = document.getElementById('product-search').value.trim();
    const resultsDiv = document.getElementById('product-results');
    
    if (searchTerm === '' || searchTerm.length < 2) {
        resultsDiv.innerHTML = '<p style="text-align: center; color: #666; font-style: italic;">Enter at least 2 characters to search for products...</p>';
        return;
    }
    
    // Show loading state
    resultsDiv.innerHTML = '<p style="text-align: center; color: #666; font-style: italic;">Searching products...</p>';
    
    // Prepare search parameters
    const searchData = new FormData();
    searchData.append('term', searchTerm);
    if (currentProductCategory) {
        searchData.append('category', currentProductCategory);
    }
    
    // Perform the search
    fetch('/search_by_category.php', {
        method: 'POST',
        body: searchData
    })
    .then(response => response.json())
    .then(data => {
        displayProductResults(data);
    })
    .catch(error => {
        console.error('Product search error:', error);
        resultsDiv.innerHTML = '<p style="text-align: center; color: #f00;">Search failed. Please try again.</p>';
    });
}

// Filter products by category
function filterByCategory(category) {
    currentProductCategory = category;
    document.getElementById('product-search').placeholder = `Search in ${category.replace('_', ' ')} category...`;
    
    // Trigger search if there's already a search term
    const searchTerm = document.getElementById('product-search').value.trim();
    if (searchTerm.length >= 2) {
        searchProducts();
    } else {
        document.getElementById('product-results').innerHTML = '<p style="text-align: center; color: #666; font-style: italic;">Enter product ID or name to search in ' + category.replace('_', ' ') + ' category...</p>';
    }
}

// Clear product search
function clearProductSearch() {
    currentProductCategory = null;
    document.getElementById('product-search').value = '';
    document.getElementById('product-search').placeholder = 'Enter product ID (e.g., 5424M, Celtic) or pattern name';
    document.getElementById('product-results').innerHTML = '<p style="text-align: center; color: #666; font-style: italic;">Start typing to search for products...</p>';
}

// Display product search results
function displayProductResults(products) {
    const resultsDiv = document.getElementById('product-results');
    
    if (!products || products.length === 0) {
        resultsDiv.innerHTML = '<p style="text-align: center; color: #999;">No products found matching your search.</p>';
        return;
    }
    
    let html = '';
    products.slice(0, 8).forEach(product => { // Limit to 8 results for better display
        // Determine if product has images
        const hasImages = product.has_images === '1' || product.has_images === 1;
        const hasPageReference = product.page_reference && product.page_reference.trim() !== '';
        const displayName = product.product_name || product.pattern || product.product_id;
        
        // Determine action type
        let actionText = 'View';
        let actionIcon = '📸';
        if (!hasImages) {
            if (hasPageReference) {
                actionText = 'PDF';
                actionIcon = '📄';
            } else {
                actionText = 'Details';
                actionIcon = '💍';
            }
        }
        
        html += `
            <div style="padding: 10px; border-bottom: 1px solid #ddd; cursor: pointer; transition: background 0.3s; display: flex; align-items: center;" 
                 onclick="openProductFromSearch('${product.product_id}', ${hasImages}, '${hasPageReference ? product.page_reference : ''}')"
                 onmouseover="this.style.background='#f0f0f0'" 
                 onmouseout="this.style.background='transparent'">
                <div style="width: 50px; height: 50px; background: ${hasImages ? '#e8f4fd' : (hasPageReference ? '#fff2e6' : '#f0f0f0')}; border-radius: 8px; display: flex; align-items: center; justify-content: center; margin-right: 12px; flex-shrink: 0;">
                    ${actionIcon}
                </div>
                <div style="flex: 1;">
                    <div style="font-weight: bold; color: #0066CC; font-size: 14px;">${product.product_id}</div>
                    <div style="font-size: 12px; color: #666; margin-top: 2px;">${displayName}</div>
                    <div style="font-size: 11px; color: #999; margin-top: 2px;">${product.category.replace('_', ' ')}${hasPageReference ? ` • Page ${product.page_reference}` : ''}</div>
                </div>
                <div style="color: ${hasImages ? '#28a745' : (hasPageReference ? '#ff6600' : '#6c757d')}; font-size: 12px; font-weight: 600;">
                    ${actionText}
                </div>
            </div>
        `;
    });
    
    if (products.length > 8) {
        html += `<div style="text-align: center; padding: 10px; color: #666; font-style: italic;">... and ${products.length - 8} more results</div>`;
    }
    
    resultsDiv.innerHTML = html;
}

// PDF Support Detection Function
function checkBrowserPDFSupport() {
    // Check for PDF plugin support
    if (navigator.mimeTypes && navigator.mimeTypes['application/pdf']) {
        return true;
    }
    
    // Check for PDF.js support (modern browsers)
    if (typeof window.pdfjsLib !== 'undefined') {
        return true;
    }
    
    // Check if browser can handle PDFs natively
    const isChrome = navigator.userAgent.indexOf('Chrome') > -1;
    const isFirefox = navigator.userAgent.indexOf('Firefox') > -1;
    const isEdge = navigator.userAgent.indexOf('Edg') > -1;
    const isSafari = navigator.userAgent.indexOf('Safari') > -1 && navigator.userAgent.indexOf('Chrome') === -1;
    
    // Most modern browsers support PDF viewing
    return isChrome || isFirefox || isEdge || isSafari;
}

// Open product modal from search results
function openProductFromSearch(productId, hasImages, pageReference) {
    // Close search modal first
    closeSearchModal();

    // Always prefer the unified ProductModal. It handles images, PDFs, and
    // plain-band configurators consistently across entrypoints.
    if (typeof ProductModal !== 'undefined' && ProductModal.open) {
        ProductModal.open(productId);
        return;
    }

    // Final fallback if the modal script is unavailable.
    window.location.href = `unified_detail.php?product=${productId}`;
}

// Connect search button to open modal when DOM is loaded
document.addEventListener('DOMContentLoaded', function() {
    const searchButton = document.getElementById('search-button');
    if (searchButton) {
        searchButton.addEventListener('click', function(e) {
            e.preventDefault();
            openSearchModal();
        });
        console.log('Search modal functionality connected');
    } else {
        console.log('Search button not found - search functionality may not work');
    }
});