/**
 * Data Loader Module
 * Loads all CSV exports from Cadman Manufacturing database
 * - IC-EXP01.csv: Inventory Control (539 products)
 * - AR-EXP01.csv: Accounts Receivable (785 customers)
 * - IP-EXP01.csv: Inventory Pricing (15,234 pricing records)
 * - SA-EXP01.csv: Sales Analysis (2,779 transactions)
 * - BM-EXP01.csv: Bill of Materials (29,684 components)
 * - SY-EXP01.csv: System Settings (to be exported)
 */

class DataLoader {
    constructor() {
        this.data = {
            inventory: [],
            customers: [],
            pricing: [],
            sales: [],
            billOfMaterials: [],
            systemSettings: null,
            loadStatus: {
                inventory: false,
                customers: false,
                pricing: false,
                sales: false,
                billOfMaterials: false,
                systemSettings: false
            }
        };
        
        this.indexes = {
            inventoryByPart: new Map(),
            customersByCode: new Map(),
            pricingByItem: new Map(),
            salesByItem: new Map(),
            bomByItem: new Map()
        };
        
        // Cache settings
        this.cacheKey = 'cadmanDatabase';
        this.cacheExpiry = 24 * 60 * 60 * 1000; // 24 hours
        this.cacheVersion = '1.0';
    }
    
    /**
     * Check if cached data exists and is valid
     */
    hasCachedData() {
        try {
            const cached = localStorage.getItem(this.cacheKey);
            if (!cached) return false;
            
            const data = JSON.parse(cached);
            const now = new Date().getTime();
            
            return data.version === this.cacheVersion && 
                   data.timestamp && 
                   (now - data.timestamp) < this.cacheExpiry;
        } catch (e) {
            console.warn('Cache check failed:', e);
            return false;
        }
    }
    
    /**
     * Load data from cache with support for partial caches
     */
    loadFromCache() {
        try {
            const cached = JSON.parse(localStorage.getItem(this.cacheKey));
            
            if (cached.isPartial) {
                // Load partial cache and merge with existing data structure
                this.data.pricing = cached.data.pricing || [];
                this.data.customers = cached.data.customers || [];
                this.data.systemSettings = cached.data.systemSettings || null;
                
                // Set load status for cached items
                this.data.loadStatus.pricing = cached.data.loadStatus.pricing || { loaded: false, count: 0 };
                this.data.loadStatus.customers = cached.data.loadStatus.customers || { loaded: false, count: 0 };
                this.data.loadStatus.systemSettings = cached.data.loadStatus.systemSettings || { loaded: false };
                
                this.buildIndexes();
                console.log('✅ Partial database loaded from cache');
                console.log(`📊 Cached: ${cached.data.pricing?.length || 0} pricing, ${cached.data.customers?.length || 0} customers`);
                console.log(`📈 Full dataset: ${cached.fullDataCounts.pricing} pricing, ${cached.fullDataCounts.customers} customers`);
                return 'partial';
            } else if (cached.isMinimal) {
                // Load minimal cache
                this.data.customers = cached.data.customers || [];
                this.data.systemSettings = cached.data.systemSettings || null;
                this.data.loadStatus.customers = cached.data.loadStatus.customers || { loaded: false, count: 0 };
                this.data.loadStatus.systemSettings = cached.data.loadStatus.systemSettings || { loaded: false };
                
                this.buildIndexes();
                console.log('✅ Minimal database loaded from cache (customers + settings)');
                return 'minimal';
            } else if (cached.isEmergency) {
                // Load emergency cache
                this.data.systemSettings = cached.data.systemSettings || null;
                this.data.loadStatus.systemSettings = cached.data.loadStatus.systemSettings || { loaded: false };
                
                console.log('🆘 Emergency cache loaded (settings only)');
                return 'emergency';
            } else {
                // Full cache
                this.data = cached.data;
                this.buildIndexes();
                console.log('✅ Full database loaded from cache');
                return 'full';
            }
        } catch (e) {
            console.warn('Cache load failed:', e);
            return false;
        }
    }
    
    /**
     * Save data to cache with size checking and selective caching
     */
    saveToCache() {
        try {
            const availableStorage = this.getAvailableStorage();
            
            // First try full cache
            const fullCacheData = {
                version: this.cacheVersion,
                timestamp: new Date().getTime(),
                data: this.data,
                isPartial: false
            };
            
            const fullSize = this.getStorageSize(fullCacheData);
            console.log(`💾 Cache size: ${(fullSize / 1024 / 1024).toFixed(2)}MB`);
            
            // If full cache is too large, use selective caching
            if (fullSize > availableStorage || fullSize > 4 * 1024 * 1024) { // 4MB limit
                console.log('📦 Full cache too large, using selective caching...');
                const selectiveCacheData = this.createSelectiveCache();
                const selectiveSize = this.getStorageSize(selectiveCacheData);
                
                console.log(`📦 Selective cache size: ${(selectiveSize / 1024 / 1024).toFixed(2)}MB`);
                
                if (selectiveSize < availableStorage && selectiveSize < 4 * 1024 * 1024) {
                    localStorage.setItem(this.cacheKey, JSON.stringify(selectiveCacheData));
                    console.log('💾 Selective database cache saved successfully');
                    console.log('📊 Cached: Top 5000 pricing records, all customers, settings');
                } else {
                    console.warn('⚠️ Even selective cache too large, skipping cache');
                    // Cache just settings and customers (minimal cache)
                    const minimalCache = {
                        version: this.cacheVersion,
                        timestamp: new Date().getTime(),
                        data: {
                            customers: this.data.customers,
                            systemSettings: this.data.systemSettings,
                            loadStatus: {
                                customers: { loaded: true, count: this.data.customers.length },
                                systemSettings: { loaded: true }
                            }
                        },
                        isMinimal: true
                    };
                    localStorage.setItem(this.cacheKey, JSON.stringify(minimalCache));
                    console.log('💾 Minimal cache saved (customers + settings only)');
                }
            } else {
                // Full cache fits
                localStorage.setItem(this.cacheKey, JSON.stringify(fullCacheData));
                console.log('💾 Full database cached successfully');
            }
        } catch (e) {
            console.warn('❌ Cache save failed:', e.message);
            
            // Last resort: try to save just system settings
            try {
                const emergencyCache = {
                    version: this.cacheVersion,
                    timestamp: new Date().getTime(),
                    data: {
                        systemSettings: this.data.systemSettings,
                        loadStatus: {
                            systemSettings: { loaded: true }
                        }
                    },
                    isEmergency: true
                };
                localStorage.setItem(this.cacheKey, JSON.stringify(emergencyCache));
                console.log('🆘 Emergency cache saved (settings only)');
            } catch (emergencyError) {
                console.error('💥 All caching failed:', emergencyError.message);
                // Clear any partial data that might be causing issues
                try {
                    localStorage.removeItem(this.cacheKey);
                } catch (clearError) {
                    console.error('Failed to clear cache:', clearError.message);
                }
            }
        }
    }
    
    /**
     * Clear cache
     */
    clearCache() {
        localStorage.removeItem(this.cacheKey);
        console.log('🗑️ Database cache cleared');
    }
    
    /**
     * Get storage size in bytes
     */
    getStorageSize(obj) {
        return new Blob([JSON.stringify(obj)]).size;
    }
    
    /**
     * Get available localStorage space (rough estimate)
     */
    getAvailableStorage() {
        const testKey = 'storageTest';
        let maxSize = 0;
        let testSize = 1024 * 1024; // Start with 1MB
        
        try {
            // Quick test - if we can't store 1MB, localStorage is nearly full
            const testData = 'x'.repeat(testSize);
            localStorage.setItem(testKey, testData);
            localStorage.removeItem(testKey);
            return 5 * 1024 * 1024; // Assume 5MB available if test passes
        } catch (e) {
            return 0; // No space available
        }
    }
    
    /**
     * Create selective cache with only essential data
     */
    createSelectiveCache() {
        // Only cache the most frequently accessed data
        const essentialData = {
            pricing: this.data.pricing.slice(0, 5000), // Top 5000 items only
            customers: this.data.customers,
            systemSettings: this.data.systemSettings,
            loadStatus: {
                pricing: { loaded: true, count: this.data.pricing.length },
                customers: { loaded: true, count: this.data.customers.length },
                systemSettings: { loaded: true }
            }
        };
        
        return {
            version: this.cacheVersion,
            timestamp: new Date().getTime(),
            data: essentialData,
            isPartial: true,
            fullDataCounts: {
                pricing: this.data.pricing.length,
                customers: this.data.customers.length,
                inventory: this.data.inventory.length,
                sales: this.data.sales.length,
                billOfMaterials: this.data.billOfMaterials.length
            }
        };
    }
    
    /**
     * Load all data from database APIs or cache
     */
    async loadAll(basePath = '../cobol/', forceRefresh = false) {
        // Try to load from cache first unless forcing refresh
        if (!forceRefresh && this.hasCachedData()) {
            const cacheResult = this.loadFromCache();
            if (cacheResult) {
                let cacheType = typeof cacheResult === 'string' ? cacheResult : 'unknown';
                return {
                    success: [`Loaded from ${cacheType} cache`],
                    failed: [],
                    cached: true,
                    cacheType: cacheType
                };
            }
        }
        
        console.log('🔄 Loading fresh database from server...');
        const results = {
            success: [],
            failed: [],
            cached: false
        };
        
        // Load inventory from database API
        try {
            await this.loadInventory();
            results.success.push('Inventory');
        } catch (e) {
            results.failed.push({ file: 'Inventory', error: e.message });
            this.data.loadStatus.inventory = false;
        }
        
        // Load customers from database API
        try {
            await this.loadCustomers();
            results.success.push('Customers');
        } catch (e) {
            results.failed.push({ file: 'Customers', error: e.message });
            this.data.loadStatus.customers = false;
        }
        
        // Load pricing from database API
        try {
            await this.loadPricing();
            results.success.push('Pricing');
        } catch (e) {
            results.failed.push({ file: 'Pricing', error: e.message });
        }
        
        // Load sales/orders from database API
        try {
            await this.loadSales();
            results.success.push('Sales');
        } catch (e) {
            results.failed.push({ file: 'Sales', error: e.message });
            this.data.loadStatus.sales = false;
        }
        
        // Load bill of materials from database API
        try {
            await this.loadBillOfMaterials();
            results.success.push('Bill of Materials');
        } catch (e) {
            results.failed.push({ file: 'Bill of Materials', error: e.message });
            this.data.loadStatus.billOfMaterials = false;
        }
        
        // Load system settings from database
        try {
            await this.loadSystemSettings();
            results.success.push('System Settings');
        } catch (e) {
            console.warn('Could not load system settings from database, using defaults:', e.message);
            // Use default system settings as fallback
            this.data.systemSettings = {
                companyCode: 'CADMAN',
                companyName: 'Cadman Manufacturing',
                goldPrice: 7300.00,
                labourRate: 28.00,  // British spelling for pricing calculator
                laborRate: 28.00,   // American spelling for display
                sterlingGF: 130.00, // Sterling gold factor
                marketMarkup: 0.00,  // Set to 0 - we use base margin instead
                baseMargin: 8.00,   // Base margin percentage
                lastUpdated: new Date().toISOString().split('T')[0],
                fiscalYearStart: '01-01',
                taxRate: 0
            };
            this.data.loadStatus.systemSettings = true;
            results.success.push('System Settings (defaults)');
        }
        
        // Build search indexes
        this.buildIndexes();
        
        // Save to cache if we loaded fresh data
        if (results.success.length > 0) {
            this.saveToCache();
        }
        
        return results;
    }
    
    /**
     * Build search indexes from loaded data
     */
    buildIndexes() {
        // Clear existing indexes
        this.indexes.inventoryByPart.clear();
        this.indexes.customersByCode.clear();
        this.indexes.pricingByItem.clear();
        this.indexes.salesByItem.clear();
        this.indexes.bomByItem.clear();
        
        // Build indexes
        if (this.data.inventory) {
            this.data.inventory.forEach(item => {
                this.indexes.inventoryByPart.set(item.partNumber, item);
            });
        }
        
        if (this.data.customers) {
            this.data.customers.forEach(customer => {
                this.indexes.customersByCode.set(customer.customerCode, customer);
            });
        }
        
        if (this.data.pricing) {
            this.data.pricing.forEach(item => {
                this.indexes.pricingByItem.set(item.itemCode, item);
            });
        }
        
        console.log('🔍 Search indexes built');
    }
    
    /**
     * Load system settings from database API
     */
    async loadSystemSettings() {
        const apiUrl = '/cadman-database/api/get_settings.php';
        
        try {
            const response = await fetch(apiUrl);
            if (!response.ok) {
                throw new Error(`API error! status: ${response.status}`);
            }
            
            const result = await response.json();
            
            if (!result.success || !result.settings) {
                throw new Error('Invalid API response');
            }
            
            console.log(`Loaded ${result.count} system settings from database`);
            
            // Map database settings to our format
            const settings = result.settings;
            this.data.systemSettings = {
                companyCode: settings.company_code?.value || 'CADMAN',
                companyName: settings.company_name?.value || 'Cadman Manufacturing',
                goldPrice: settings.gold_price?.value || 7300.00,
                labourRate: settings.labor_rate?.value || 28.00,  // British spelling
                laborRate: settings.labor_rate?.value || 28.00,   // American spelling
                sterlingGF: settings.sterling_gf?.value || 130.00,
                marketMarkup: 0.00,  // Not used - we use base margin
                baseMargin: settings.base_margin?.value || 8.00,
                lastUpdated: new Date().toISOString().split('T')[0],
                fiscalYearStart: settings.fiscal_year_start?.value || '01-01',
                taxRate: settings.sales_tax_rate?.value || 0
            };
            
            this.data.loadStatus.systemSettings = true;
            console.log('System settings:', this.data.systemSettings);
        } catch (error) {
            console.error('Failed to load system settings from database:', error);
            throw error;
        }
    }
    
    /**
     * Load inventory data from database API
     */
    async loadInventory() {
        const apiUrl = '/cadman-database/api/get_inventory.php?limit=10000';
        
        try {
            const response = await fetch(apiUrl);
            if (!response.ok) {
                throw new Error(`API error! status: ${response.status}`);
            }
            
            const result = await response.json();
            
            if (!result.success || !result.data) {
                throw new Error('Invalid API response');
            }
            
            console.log(`API returned ${result.count} inventory items`);
            
            // Transform database records to inventory format
            for (const row of result.data) {
                const item = {
                    partNumber: row.part_number || '',
                    description: row.description || '',
                    class: row.class || '',
                    cost: row.cost || 0,
                    materialCost: row.material_cost || 0,
                    metalHi: row.metal_hi || '',
                    metalLo: row.metal_lo || '',
                    group: row.group_code || '',
                    goldGrams: row.gold_grams || 0,
                    goldCost: row.gold_cost || 0,
                    sterlingGrams: row.sterling_grams || 0,
                    sterlingCost: row.sterling_cost || 0,
                    onHand: 0,
                    available: 0,
                    onOrder: 0,
                    salesAccount: '',
                    costAccount: '',
                    vendor: '',
                    vendorPart: '',
                    uom: '',
                    weight: 0,
                    package: '',
                    notes: ''
                };
                
                this.data.inventory.push(item);
                this.indexes.inventoryByPart.set(item.partNumber, item);
            }
            
            this.data.loadStatus.inventory = true;
            console.log(`Loaded ${this.data.inventory.length} inventory items from database`);
        } catch (error) {
            console.error('Failed to load inventory from database:', error);
            throw error;
        }
    }
    
    /**
     * Load IC-EXP01.csv - Inventory Control (DEPRECATED - now loads from database)
     */
    async loadInventoryFromCSV(filePath) {
        const csvText = await this.fetchFile(filePath);
        const lines = csvText.trim().split('\n');
        
        // Skip header
        for (let i = 1; i < lines.length; i++) {
            const values = lines[i].split('|');
            if (values.length < 5) continue;
            
            const item = {
                partNumber: values[0]?.trim() || '',
                description: values[1]?.trim() || '',
                class: values[2]?.trim() || '',
                cost: parseFloat(values[3]) || 0,
                materialCost: parseFloat(values[4]) || 0,
                metalHi: values[5]?.trim() || '',
                metalLo: values[6]?.trim() || '',
                group: values[7]?.trim() || '',
                goldGrams: parseFloat(values[8]) || 0,
                goldCost: parseFloat(values[9]) || 0,
                sterlingGrams: parseFloat(values[10]) || 0,
                sterlingCost: parseFloat(values[11]) || 0,
                onHand: parseInt(values[12]) || 0,
                allocated: parseInt(values[13]) || 0,
                available: parseInt(values[14]) || 0,
                onOrder: parseInt(values[15]) || 0,
                vendor: values[16]?.trim() || '',
                location: values[17]?.trim() || ''
            };
            
            this.data.inventory.push(item);
            this.indexes.inventoryByPart.set(item.partNumber, item);
        }
        
        this.data.loadStatus.inventory = true;
        console.log(`Loaded ${this.data.inventory.length} inventory items`);
    }
    
    /**
     * Load customers from database API
     */
    async loadCustomers() {
        const apiUrl = '/cadman-database/api/get_customers.php?limit=10000';
        
        try {
            const response = await fetch(apiUrl);
            if (!response.ok) {
                throw new Error(`API error! status: ${response.status}`);
            }
            
            const result = await response.json();
            
            if (!result.success || !result.data) {
                throw new Error('Invalid API response');
            }
            
            console.log(`API returned ${result.data.length} customers`);
            
            // Transform database records to customer format
            for (const row of result.data) {
                const customer = {
                    customerCode: row.customer_code || '',
                    name: row.business_name || '',
                    contactName: row.contact_name || '',
                    address: row.address || '',
                    city: row.city || '',
                    province: row.province || '',
                    state: row.province || '', // Same as province
                    postalCode: row.postal_code || '',
                    zip: row.postal_code || '', // Same as postal code
                    country: row.country || 'Canada',
                    phone: row.phone || '',
                    email: row.email || '',
                    website: row.website || '',
                    latitude: row.latitude || null,
                    longitude: row.longitude || null,
                    clientType: row.client_type || 'Retailer',
                    status: row.status || 'Active',
                    terms: row.terms || '',
                    discount: parseFloat(row.discount_percent || 0) || 0,
                    priceLevel: parseInt(row.price_level || 1, 10) || 1,
                    notes: row.notes || '',
                    balance: parseFloat(row.balance || 0) || 0,
                    creditLimit: parseFloat(row.credit_limit || 0) || 0,
                    ytdSales: parseFloat(row.ytd_sales || 0) || 0
                };
                
                this.data.customers.push(customer);
                this.indexes.customersByCode.set(customer.customerCode, customer);
            }
            
            this.data.loadStatus.customers = true;
            console.log(`Loaded ${this.data.customers.length} customers from database`);
        } catch (error) {
            console.error('Failed to load customers from database:', error);
            throw error;
        }
    }
    
    /**
     * Load sales/orders from database API  
     */
    async loadSales() {
        const apiUrl = '/cadman-database/api/get_sales.php?limit=10000';
        
        try {
            const response = await fetch(apiUrl);
            if (!response.ok) {
                throw new Error(`API error! status: ${response.status}`);
            }
            
            const result = await response.json();
            
            if (!result.success || !result.data) {
                throw new Error('Invalid API response');
            }
            
            console.log(`API returned ${result.count} sales transactions`);
            
            // Transform database records to sales format
            for (const row of result.data) {
                const price = parseFloat(row.selling_price) || 0;
                const quantity = parseInt(row.quantity) || 1;
                const cost = parseFloat(row.cost) || 0;
                const extendedPrice = price * quantity;
                const extendedCost = cost * quantity;
                const profit = extendedPrice - extendedCost;
                
                const sale = {
                    customerCode: row.customer_code || '',
                    itemCode: row.item_code || '',
                    invoiceDate: row.invoice_date || '',
                    invoiceNumber: row.invoice_number || '',
                    description: row.description || '',
                    shipDate: row.ship_date || '',
                    price: price,
                    quantity: quantity,
                    cost: cost,
                    extendedPrice: extendedPrice,
                    extendedCost: extendedCost,
                    profit: profit,
                    profitPercent: extendedPrice > 0 ? (profit / extendedPrice * 100) : 0,
                    salesRep: row.sales_rep || '',
                    orderNumber: row.invoice_number || '' // alias for compatibility
                };
                
                this.data.sales.push(sale);
                
                // Index by item code for quick lookup
                if (!this.indexes.salesByItem.has(sale.itemCode)) {
                    this.indexes.salesByItem.set(sale.itemCode, []);
                }
                this.indexes.salesByItem.get(sale.itemCode).push(sale);
            }
            
            this.data.loadStatus.sales = true;
            console.log(`Loaded ${this.data.sales.length} sales transactions from database`);
        } catch (error) {
            console.error('Failed to load sales from database:', error);
            throw error;
        }
    }
    
    /**
     * Load AR-EXP01.csv - Accounts Receivable (Customers) - DEPRECATED
     */
    async loadCustomersFromCSV(filePath) {
        const csvText = await this.fetchFile(filePath);
        const lines = csvText.trim().split('\n');
        
        for (let i = 1; i < lines.length; i++) {
            const values = lines[i].split('|');
            if (values.length < 5) continue;
            
            const customer = {
                customerCode: values[0]?.trim() || '',
                name: values[1]?.trim() || '',
                address1: values[2]?.trim() || '',
                address2: values[3]?.trim() || '',
                city: values[4]?.trim() || '',
                state: values[5]?.trim() || '',
                zip: values[6]?.trim() || '',
                phone: values[7]?.trim() || '',
                contact: values[8]?.trim() || '',
                terms: values[9]?.trim() || '',
                creditLimit: parseFloat(values[10]) || 0,
                balance: parseFloat(values[11]) || 0,
                ytdSales: parseFloat(values[12]) || 0,
                lastSaleDate: values[13]?.trim() || '',
                salesRep: values[14]?.trim() || '',
                discount: parseFloat(values[15]) || 0,
                taxExempt: values[16]?.trim() === 'Y',
                status: values[17]?.trim() || ''
            };
            
            this.data.customers.push(customer);
            this.indexes.customersByCode.set(customer.customerCode, customer);
        }
        
        this.data.loadStatus.customers = true;
        console.log(`Loaded ${this.data.customers.length} customers`);
    }
    
    /**
     * Load pricing data from database API
     */
    async loadPricing(filePath) {
        // Fetch from database API instead of CSV
        const apiUrl = '/cadman-database/api/get_products.php?limit=20000';
        
        try {
            const response = await fetch(apiUrl);
            if (!response.ok) {
                throw new Error(`API error! status: ${response.status}`);
            }
            
            const result = await response.json();
            
            if (!result.success || !result.data) {
                throw new Error('Invalid API response');
            }
            
            console.log(`API returned ${result.count} pricing records`);
            
            // Transform database records to pricing format
            for (const row of result.data) {
                const item = {
                    itemCode: row.full_item_code || '',
                    description: row.variant_description || row.base_description || '',
                    price: row.selling_price || 0,
                    cost: row.total_cost || 0,
                    materialCost: row.material_cost || 0,
                    laborCost: row.labor_cost || 0,
                    laborHours: row.labor_hours || 0,
                    metalHi: row.metal_type || '',
                    metalLo: row.metal_variant || '',
                    goldGrams: row.gold_grams || 0,
                    goldCost: row.gold_cost || 0,
                    sterlingGrams: row.sterling_grams || 0,
                    sterlingCost: row.sterling_cost || 0,
                    stoneCost: row.stone_cost || 0,
                    starCost: row.star_cost || 0,
                    stoneSettingCost: row.stone_setting_cost || 0,
                    // 12-month sales history
                    salesHistory: [
                        row.sales_month_1 || 0, row.sales_month_2 || 0,
                        row.sales_month_3 || 0, row.sales_month_4 || 0,
                        row.sales_month_5 || 0, row.sales_month_6 || 0,
                        row.sales_month_7 || 0, row.sales_month_8 || 0,
                        row.sales_month_9 || 0, row.sales_month_10 || 0,
                        row.sales_month_11 || 0, row.sales_month_12 || 0
                    ],
                    markup: row.markup_percent || 0,
                    salesTax: row.sales_tax_percent || 0,
                    info1: row.info_1 || '',
                    info2: row.info_2 || '',
                    category: row.category || '',
                    group: row.group_code || '',
                    previousPrice: row.previous_price || 0,
                    priceChangeDate: row.price_change_date || '',
                    costChangeDate: row.cost_change_date || '',
                    // Catalog integration
                    pdfFile: row.pdf_file || '',
                    imageFiles: row.image_files || '',
                    pageReference: row.page_reference || '',
                    // Additional fields
                    baseCode: row.base_code || '',
                    baseDescription: row.base_description || '',
                    salesAccount: '',
                    costAccount: '',
                    vendor: '',
                    vendorPart: '',
                    uom: '',
                    weight: 0,
                    package: '',
                    notes: ''
                };
                
                // Calculate total sales for sorting
                item.totalSales = item.salesHistory.reduce((sum, val) => sum + val, 0);
                
                // Store original database price
                item.dbPrice = row.selling_price || 0;
                item.dbCost = row.total_cost || 0;
                
                this.data.pricing.push(item);
                this.indexes.pricingByItem.set(item.itemCode, item);
            }
            
            this.data.loadStatus.pricing = true;
            console.log(`Loaded ${this.data.pricing.length} pricing records from database`);
        } catch (error) {
            console.error('Failed to load pricing from database:', error);
            throw error;
        }
    }
    
    /**
     * Recalculate prices using current system settings
     * Called after loading to update prices with current gold price, labor rates, etc.
     */
    recalculateAllPrices(pricingCalculator) {
        if (!pricingCalculator) return;
        
        // Get base margin from system settings
        const baseMargin = this.data.systemSettings?.baseMargin || 8.0;
        
        let recalculated = 0;
        let debugLogged = false;
        
        for (const item of this.data.pricing) {
            // Recalculate gold cost with current gold price
            const goldCost = pricingCalculator.calculateGoldCost(item.goldGrams, item.metalHi);
            const sterlingCost = pricingCalculator.calculateSterlingCost(item.sterlingGrams);
            const laborCost = pricingCalculator.calculateLaborCost(item.laborHours);
            
            // Recalculate total cost
            const totalCost = 
                item.materialCost +
                laborCost +
                goldCost +
                sterlingCost +
                item.stoneCost +
                item.starCost +
                item.stoneSettingCost;
            
            // Recalculate selling price with item markup + base margin
            let sellingPrice = totalCost * (1 + item.markup / 100) * (1 + baseMargin / 100);
            
            // Apply sales tax if present
            if (item.salesTax > 0) {
                sellingPrice = sellingPrice * (1 + item.salesTax / 100);
            }
            
            // Round to quarter
            sellingPrice = pricingCalculator.roundToQuarter(sellingPrice);
            
            // DEBUG: Log first F120 item
            if (!debugLogged && item.itemCode && item.itemCode.startsWith('F120')) {
                console.log(`=== RECALC DEBUG: ${item.itemCode} ===`);
                console.log(`Gold: ${item.goldGrams}g @ $${pricingCalculator.settings.goldPrice}/oz → $${goldCost.toFixed(2)}`);
                console.log(`Labor: ${item.laborHours}h @ $${pricingCalculator.settings.labourRate}/h → $${laborCost.toFixed(2)}`);
                console.log(`Material: $${item.materialCost}, Stone: $${item.stoneCost}, Star: $${item.starCost}, Setting: $${item.stoneSettingCost}`);
                console.log(`Total Cost: $${totalCost.toFixed(2)}`);
                console.log(`Markup: ${item.markup}%, Base Margin: ${baseMargin}%`);
                console.log(`Pre-round: $${(totalCost * (1 + item.markup / 100) * (1 + baseMargin / 100)).toFixed(2)}`);
                console.log(`FINAL PRICE: $${sellingPrice.toFixed(2)}`);
                debugLogged = true;
            }
            
            // Update item with recalculated values
            item.goldCost = goldCost;
            item.sterlingCost = sterlingCost;
            item.laborCost = laborCost;
            item.cost = totalCost;
            item.price = sellingPrice;
            
            recalculated++;
        }
        
        console.log(`Recalculated ${recalculated} prices with current gold price and settings`);
    }
    
    /**
     * Load bill of materials data from database API
     */
    async loadBillOfMaterials() {
        const apiUrl = '/cadman-database/api/get_bom.php?limit=100000';
        
        try {
            const response = await fetch(apiUrl);
            if (!response.ok) {
                throw new Error(`API error! status: ${response.status}`);
            }
            
            const result = await response.json();
            
            if (!result.success || !result.data) {
                throw new Error('Invalid API response');
            }
            
            console.log(`API returned ${result.count} BOM records`);
            
            // Transform database records to BOM format
            for (const row of result.data) {
                const bom = {
                    itemCode: row.item_code || '',
                    partNumber: row.component_part || '',
                    class: row.class || '',
                    quantity: row.quantity || 0,
                    sequence: 0,
                    description: '',
                    metalType: '',
                    cost: 0
                };
                
                // Look up part in inventory for metal type and cost
                const part = this.indexes.inventoryByPart.get(bom.partNumber);
                if (part) {
                    bom.metalType = part.metalHi + (part.metalLo || '');
                    bom.cost = part.cost;
                    bom.description = part.description;
                }
                
                this.data.billOfMaterials.push(bom);
                
                // Index by item code
                if (!this.indexes.bomByItem.has(bom.itemCode)) {
                    this.indexes.bomByItem.set(bom.itemCode, []);
                }
                this.indexes.bomByItem.get(bom.itemCode).push(bom);
            }
            
            this.data.loadStatus.billOfMaterials = true;
            console.log(`Loaded ${this.data.billOfMaterials.length} BOM components from database`);
        } catch (error) {
            console.error('Failed to load BOM from database:', error);
            throw error;
        }
    }
    
    /**
     * Load BM-EXP01.csv - Bill of Materials (DEPRECATED - now loads from database)
     */
    async loadBillOfMaterialsFromCSV(filePath) {
        const csvText = await this.fetchFile(filePath);
        const lines = csvText.trim().split('\n');
        
        for (let i = 1; i < lines.length; i++) {
            const values = lines[i].split('|');
            if (values.length < 4) continue;
            
            const bom = {
                itemCode: values[0]?.trim() || '',
                partNumber: values[1]?.trim() || '',
                class: values[2]?.trim() || '',
                quantity: parseFloat(values[3]) || 0,
                sequence: parseInt(values[4]) || 0,
                description: values[5]?.trim() || '',
                // Get metal type from inventory when available
                metalType: '',
                cost: 0
            };
            
            // Look up part in inventory for metal type and cost
            const part = this.indexes.inventoryByPart.get(bom.partNumber);
            if (part) {
                bom.metalType = part.metalHi + (part.metalLo || '');
                bom.cost = part.cost;
                bom.description = bom.description || part.description;
            }
            
            this.data.billOfMaterials.push(bom);
            
            // Index by item code
            if (!this.indexes.bomByItem.has(bom.itemCode)) {
                this.indexes.bomByItem.set(bom.itemCode, []);
            }
            this.indexes.bomByItem.get(bom.itemCode).push(bom);
        }
        
        this.data.loadStatus.billOfMaterials = true;
        console.log(`Loaded ${this.data.billOfMaterials.length} BOM components`);
    }
    
    /**
     * Load system settings from database API
     */
    async loadSystemSettings() {
        const apiUrl = '/cadman-database/api/get_settings.php';
        
        try {
            const response = await fetch(apiUrl);
            if (!response.ok) {
                throw new Error(`HTTP error! status: ${response.status}`);
            }
            const result = await response.json();
            
            if (!result.success) {
                throw new Error(result.message || 'Failed to load settings');
            }
            
            // Map database settings to our format
            this.data.systemSettings = {
                companyCode: 'CADMAN',
                companyName: 'Cadman Manufacturing',
                goldPrice: result.settings.gold_price?.value || 7300.00,
                labourRate: result.settings.labor_rate?.value || 28.00,  // British spelling for pricing calculator
                laborRate: result.settings.labor_rate?.value || 28.00,   // American spelling for display
                sterlingGF: result.settings.sterling_gf?.value || 130.00,
                marketMarkup: 0.00,  // We use base margin instead
                baseMargin: result.settings.base_margin?.value || 8.00,
                lastUpdated: new Date().toISOString().split('T')[0],
                fiscalYearStart: '01-01',
                taxRate: 0
            };
            
            this.data.loadStatus.systemSettings = true;
            console.log('Loaded system settings from database:', this.data.systemSettings);
        } catch (error) {
            console.error('Failed to load system settings from database:', error);
            throw error;
        }
    }
    
    /**
     * Fetch file content (works with file:// protocol or http/https)
     */
    async fetchFile(filePath) {
        try {
            const response = await fetch(filePath);
            if (!response.ok) {
                throw new Error(`HTTP error! status: ${response.status}`);
            }
            return await response.text();
        } catch (error) {
            // Fallback for local file access
            console.error(`Error loading ${filePath}:`, error);
            throw new Error(`Could not load ${filePath}: ${error.message}`);
        }
    }
    
    /**
     * Get item by code (search pricing first, then inventory)
     */
    getItem(itemCode) {
        return this.indexes.pricingByItem.get(itemCode) || 
               this.indexes.inventoryByPart.get(itemCode) ||
               null;
    }
    
    /**
     * Get customer by code
     */
    getCustomer(customerCode) {
        return this.indexes.customersByCode.get(customerCode) || null;
    }
    
    /**
     * Get BOM components for an item
     */
    getBOM(itemCode) {
        return this.indexes.bomByItem.get(itemCode) || [];
    }
    
    /**
     * Get sales history for an item
     */
    getSalesHistory(itemCode) {
        return this.indexes.salesByItem.get(itemCode) || [];
    }
    
    /**
     * Search items by description or code (cached data only)
     */
    searchItems(query) {
        const lowerQuery = query.toLowerCase();
        return this.data.pricing.filter(item => 
            item.itemCode.toLowerCase().includes(lowerQuery) ||
            item.description.toLowerCase().includes(lowerQuery)
        );
    }
    
    /**
     * Live search items directly from database (bypasses cache limitations)
     */
    async searchItemsLive(query, limit = 100) {
        if (!query || query.length < 2) {
            return [];
        }
        
        try {
            const apiUrl = `./api/search_products.php?q=${encodeURIComponent(query)}&limit=${limit}`;
            const response = await fetch(apiUrl);
            
            if (!response.ok) {
                throw new Error(`API error! status: ${response.status}`);
            }
            
            const result = await response.json();
            
            if (!result.success) {
                throw new Error(result.message || 'Search failed');
            }
            
            console.log(`🔍 Live search found ${result.data.length} items for "${query}"`);
            return result.data;
            
        } catch (error) {
            console.error('Live search failed:', error);
            // Fallback to cached search
            return this.searchItems(query);
        }
    }
    
    /**
     * Get single item by code from database (live lookup)
     */
    async getItemLive(itemCode) {
        try {
            const apiUrl = `./api/get_item.php?item_code=${encodeURIComponent(itemCode)}`;
            const response = await fetch(apiUrl);
            
            if (!response.ok) {
                throw new Error(`API error! status: ${response.status}`);
            }
            
            const result = await response.json();
            
            if (!result.success) {
                throw new Error(result.message || 'Item not found');
            }
            
            return result.data;
            
        } catch (error) {
            console.error('Live item lookup failed:', error);
            // Fallback to cached lookup
            return this.getItem(itemCode);
        }
    }
    
    /**
     * Search customers by name or code
     */
    searchCustomers(query) {
        const lowerQuery = query.toLowerCase();
        return this.data.customers.filter(customer =>
            customer.customerCode.toLowerCase().includes(lowerQuery) ||
            customer.name.toLowerCase().includes(lowerQuery)
        );
    }
    
    /**
     * Get top selling items
     */
    getTopSellers(limit = 50) {
        return [...this.data.pricing]
            .sort((a, b) => (b.totalSales || 0) - (a.totalSales || 0))
            .slice(0, limit);
    }
    
    /**
     * Get items by category
     */
    getItemsByCategory(category) {
        return this.data.pricing.filter(item => item.category === category);
    }
    
    /**
     * Get items by metal type
     */
    getItemsByMetal(metalType) {
        return this.data.pricing.filter(item => 
            item.metalHi === metalType || 
            (item.metalHi + item.metalLo) === metalType
        );
    }
    
    /**
     * Get all categories
     */
    getCategories() {
        const categories = new Set();
        this.data.pricing.forEach(item => {
            if (item.category) categories.add(item.category);
        });
        return Array.from(categories).sort();
    }
    
    /**
     * Get all metal types
     */
    getMetalTypes() {
        const metals = new Set();
        this.data.pricing.forEach(item => {
            if (item.metalHi) metals.add(item.metalHi + (item.metalLo || ''));
        });
        return Array.from(metals).sort();
    }
    
    /**
     * Get the full pricing count (for display when using Smart Cache)
     */
    getFullPricingCount() {
        try {
            const cached = localStorage.getItem(this.cacheKey);
            if (cached) {
                const data = JSON.parse(cached);
                if (data.fullDataCounts && data.fullDataCounts.pricing) {
                    return data.fullDataCounts.pricing;
                }
            }
        } catch (e) {
            console.warn('Could not get full pricing count from cache:', e);
        }
        
        // Fallback to current loaded count
        return this.data.pricing.length;
    }
    
    /**
     * Get load status summary
     */
    getLoadStatus() {
        return {
            inventory: {
                loaded: this.data.loadStatus.inventory,
                count: this.data.inventory.length
            },
            customers: {
                loaded: this.data.loadStatus.customers,
                count: this.data.customers.length
            },
            pricing: {
                loaded: this.data.loadStatus.pricing,
                count: this.data.pricing.length
            },
            sales: {
                loaded: this.data.loadStatus.sales,
                count: this.data.sales.length
            },
            billOfMaterials: {
                loaded: this.data.loadStatus.billOfMaterials,
                count: this.data.billOfMaterials.length
            },
            systemSettings: {
                loaded: this.data.loadStatus.systemSettings,
                settings: this.data.systemSettings
            }
        };
    }
}

// Export for use in web interface
if (typeof module !== 'undefined' && module.exports) {
    module.exports = DataLoader;
}
