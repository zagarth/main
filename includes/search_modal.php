<?php
/**
 * Reusable Search Modal Component
 * Includes both retailer and product search functionality
 */
?>

<!-- Enhanced Search Modal with Tabs -->
<div id="search-modal" style="display: none; position: fixed; top: 0; left: 0; width: 100%; height: 100%; background: rgba(0,0,0,0.7); z-index: 2000; backdrop-filter: blur(5px);">
    <div style="position: absolute; top: 50%; left: 50%; transform: translate(-50%, -50%); background: white; border-radius: 15px; padding: 30px; max-width: 500px; width: 90%; box-shadow: 0 20px 40px rgba(0,0,0,0.3);">
        <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 20px;">
            <h3 style="margin: 0; color: #0066CC; font-size: 24px; font-weight: bold;">🔍 Search</h3>
            <button onclick="closeSearchModal()" style="background: none; border: none; font-size: 24px; cursor: pointer; color: #666; padding: 5px; border-radius: 50%; transition: all 0.3s ease;" onmouseover="this.style.background='#f0f0f0'" onmouseout="this.style.background='none'">×</button>
        </div>
        
        <!-- Tab Navigation -->
        <div class="search-tabs" style="display: flex; margin-bottom: 20px; border-radius: 8px; background: #f8f9fa; padding: 4px;">
            <button id="retailers-tab" class="tab-btn active" onclick="switchSearchTab('retailers')" style="flex: 1; background: linear-gradient(145deg, #0066CC, #004499); color: white; border: none; padding: 12px; border-radius: 6px; cursor: pointer; font-weight: 600; transition: all 0.3s ease; margin-right: 4px;">📍 Find Retailers</button>
            <button id="products-tab" class="tab-btn" onclick="switchSearchTab('products')" style="flex: 1; background: transparent; color: #666; border: none; padding: 12px; border-radius: 6px; cursor: pointer; font-weight: 600; transition: all 0.3s ease;">💍 Find Products</button>
        </div>
        
        <!-- Retailer Search Tab Content -->
        <div id="retailers-content" class="tab-content">
            <div style="margin-bottom: 20px;">
                <label style="display: block; margin-bottom: 8px; font-weight: 600; color: #333;">Search by City or Province:</label>
                <input type="text" id="location-search" placeholder="Enter city name or province (e.g., Toronto, ON, BC)" style="width: 100%; padding: 12px; border: 2px solid #ddd; border-radius: 8px; font-size: 16px; transition: border-color 0.3s ease;" onfocus="this.style.borderColor='#0066CC'" onblur="this.style.borderColor='#ddd'" oninput="searchRetailers()">
            </div>
            
            <div style="margin-bottom: 20px;">
                <label style="display: block; margin-bottom: 8px; font-weight: 600; color: #333;">Quick Filters:</label>
                <div style="display: flex; flex-wrap: wrap; gap: 8px;">
                    <button onclick="filterByProvince('BC')" style="background: linear-gradient(145deg, #0066CC, #004499); color: white; border: none; padding: 8px 16px; border-radius: 20px; cursor: pointer; font-size: 14px; transition: all 0.3s ease;" onmouseover="this.style.transform='translateY(-2px)'" onmouseout="this.style.transform='translateY(0)'">BC</button>
                    <button onclick="filterByProvince('AB')" style="background: linear-gradient(145deg, #0066CC, #004499); color: white; border: none; padding: 8px 16px; border-radius: 20px; cursor: pointer; font-size: 14px; transition: all 0.3s ease;" onmouseover="this.style.transform='translateY(-2px)'" onmouseout="this.style.transform='translateY(0)'">AB</button>
                    <button onclick="filterByProvince('ON')" style="background: linear-gradient(145deg, #0066CC, #004499); color: white; border: none; padding: 8px 16px; border-radius: 20px; cursor: pointer; font-size: 14px; transition: all 0.3s ease;" onmouseover="this.style.transform='translateY(-2px)'" onmouseout="this.style.transform='translateY(0)'">ON</button>
                    <button onclick="filterByProvince('QC')" style="background: linear-gradient(145deg, #0066CC, #004499); color: white; border: none; padding: 8px 16px; border-radius: 20px; cursor: pointer; font-size: 14px; transition: all 0.3s ease;" onmouseover="this.style.transform='translateY(-2px)'" onmouseout="this.style.transform='translateY(0)'">QC</button>
                    <button onclick="clearSearch()" style="background: linear-gradient(145deg, #6c757d, #495057); color: white; border: none; padding: 8px 16px; border-radius: 20px; cursor: pointer; font-size: 14px; transition: all 0.3s ease;" onmouseover="this.style.transform='translateY(-2px)'" onmouseout="this.style.transform='translateY(0)'">Clear</button>
                </div>
            </div>
            
            <div style="margin-bottom: 20px;">
                <div style="display: flex; gap: 10px;">
                    <button onclick="if (typeof findnearme === 'function') { findnearme(); closeSearchModal(); } else { alert('Please navigate to the homepage to use location features.'); }" style="flex: 1; background: linear-gradient(145deg, #28a745, #1e7e34); color: white; border: none; padding: 12px; border-radius: 8px; cursor: pointer; font-weight: 600; transition: all 0.3s ease;" onmouseover="this.style.transform='translateY(-2px)'" onmouseout="this.style.transform='translateY(0)'">📍 Find Nearest</button>
                    <button onclick="showAllRetailersList()" style="flex: 1; background: linear-gradient(145deg, #0066CC, #004499); color: white; border: none; padding: 12px; border-radius: 8px; cursor: pointer; font-weight: 600; transition: all 0.3s ease;" onmouseover="this.style.transform='translateY(-2px)'" onmouseout="this.style.transform='translateY(0)'">📋 View All List</button>
                </div>
            </div>
            
            <div id="retailer-results" style="max-height: 200px; overflow-y: auto; border: 1px solid #eee; border-radius: 8px; padding: 10px; background: #f9f9f9;">
                <p style="text-align: center; color: #666; font-style: italic;">Start typing to search for retailers...</p>
            </div>
        </div>
        
        <!-- Product Search Tab Content -->
        <div id="products-content" class="tab-content" style="display: none;">
            <div style="margin-bottom: 20px;">
                <label style="display: block; margin-bottom: 8px; font-weight: 600; color: #333;">Search by Product ID or Name:</label>
                <input type="text" id="product-search" placeholder="Enter product ID (e.g., 5424M, Celtic) or pattern name" style="width: 100%; padding: 12px; border: 2px solid #ddd; border-radius: 8px; font-size: 16px; transition: border-color 0.3s ease;" onfocus="this.style.borderColor='#0066CC'" onblur="this.style.borderColor='#ddd'" oninput="searchProducts()">
            </div>
            
            <div style="margin-bottom: 20px;">
                <label style="display: block; margin-bottom: 8px; font-weight: 600; color: #333;">Category Filters:</label>
                <div style="display: flex; flex-wrap: wrap; gap: 8px;">
                    <button onclick="filterByCategory('celtic_bands')" style="background: linear-gradient(145deg, #0066CC, #004499); color: white; border: none; padding: 8px 16px; border-radius: 20px; cursor: pointer; font-size: 14px; transition: all 0.3s ease;" onmouseover="this.style.transform='translateY(-2px)'" onmouseout="this.style.transform='translateY(0)'">Celtic</button>
                    <button onclick="filterByCategory('plain_bands')" style="background: linear-gradient(145deg, #0066CC, #004499); color: white; border: none; padding: 8px 16px; border-radius: 20px; cursor: pointer; font-size: 14px; transition: all 0.3s ease;" onmouseover="this.style.transform='translateY(-2px)'" onmouseout="this.style.transform='translateY(0)'">Bands</button>
                    <button onclick="filterByCategory('accessories')" style="background: linear-gradient(145deg, #0066CC, #004499); color: white; border: none; padding: 8px 16px; border-radius: 20px; cursor: pointer; font-size: 14px; transition: all 0.3s ease;" onmouseover="this.style.transform='translateY(-2px)'" onmouseout="this.style.transform='translateY(0)'">Accessories</button>
                    <button onclick="clearProductSearch()" style="background: linear-gradient(145deg, #6c757d, #495057); color: white; border: none; padding: 8px 16px; border-radius: 20px; cursor: pointer; font-size: 14px; transition: all 0.3s ease;" onmouseover="this.style.transform='translateY(-2px)'" onmouseout="this.style.transform='translateY(0)'">Clear</button>
                </div>
            </div>
            
            <div id="product-results" style="max-height: 200px; overflow-y: auto; border: 1px solid #eee; border-radius: 8px; padding: 10px; background: #f9f9f9;">
                <p style="text-align: center; color: #666; font-style: italic;">Start typing to search for products...</p>
            </div>
        </div>
    </div>
</div>