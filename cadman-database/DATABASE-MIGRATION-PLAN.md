# Database Structure Comparison & Migration Plan

## Current Database: CadmanClients (MySQL)

### Existing Tables (13 total):
1. `catalog_analytics` - Catalog usage tracking
2. `catalog_analytics_summary` - Aggregated analytics  
3. **`catalog_products`** - Catalog PDF items (physical specs, images)
4. `clients` - Customer data
5. `index_pages` - Catalog index pages
6. `jewelry_categories` - Product categories
7. `jewelry_collections` - Product collections
8. `jewelry_item_variants` - Image variants (main, thumbnail, detail, etc)
9. `jewelry_items` - Jewelry gallery items
10. `jewelry_upload_log` - Upload tracking
11. `order_items` - Order line items
12. `orders` - Customer orders
13. `users` - System users

---

## IMPORTANT: `catalog_products` vs Pricing Database

### `catalog_products` - DIFFERENT PURPOSE
**Purpose:** Catalog PDF physical specifications and imagery
**Fields:**
- Physical dimensions: `width_mm`, `height_mm`, `thickness_mm`
- Catalog refs: `page_reference`, `pdf_file`, `image_files`
- Gender variants: `gender_variant` (M/L/unisex)
- Style specs: `profile`, `pattern`, `series`
- **NO PRICING DATA, NO METAL VARIANTS**

### **NEW TABLES NEEDED:** Pricing Calculator (AR12 System)
**Purpose:** Product pricing, costs, metal variants
**Tables to create:**
1. `products` - Base product master (pricing config)
2. `product_variants` - Metal type variants (10K, 14K, STER)

**These are SEPARATE systems:**
- `catalog_products` = Physical catalog (what it looks like)
- `products` + `product_variants` = Pricing (what it costs)

---

## Step 1: Create Pricing Tables in Existing Database

### Table: `products` (Base Product Master)

```sql
CREATE TABLE products (
    product_id INT PRIMARY KEY AUTO_INCREMENT,
    base_code VARCHAR(20) NOT NULL UNIQUE COMMENT 'Base item code without metal (e.g., 1000L, 100, 050DT)',
    description VARCHAR(50) NOT NULL,
    
    -- Base costs (metal-independent)
    labor_cost DECIMAL(10,2) DEFAULT 0 COMMENT 'Labor cost in dollars',
    labor_hours DECIMAL(10,3) DEFAULT 0 COMMENT 'Calculated from labor_cost / labor_rate',
    stone_cost DECIMAL(10,2) DEFAULT 0,
    star_cost DECIMAL(10,2) DEFAULT 0,
    stone_setting_cost DECIMAL(10,2) DEFAULT 0,
    
    -- Pricing configuration
    markup_percent DECIMAL(5,2) DEFAULT 50 COMMENT 'Item markup percentage (50, 55, etc)',
    sales_tax_percent DECIMAL(5,2) DEFAULT 0 COMMENT 'Sales tax percentage',
    
    -- Categorization
    category VARCHAR(10) COMMENT 'Product category code (W60, T60, etc)',
    group_code VARCHAR(5) COMMENT 'Product group',
    info_1 VARCHAR(30) COMMENT 'Additional info field 1',
    info_2 VARCHAR(30) COMMENT 'Additional info field 2',
    
    -- Metadata
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    
    INDEX idx_base_code (base_code),
    INDEX idx_category (category),
    INDEX idx_group (group_code)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
COMMENT='Base products for AR12 pricing calculator - one row per unique item';
```

### Table: `product_variants` (Metal Type Variants)

```sql
CREATE TABLE product_variants (
    variant_id INT PRIMARY KEY AUTO_INCREMENT,
    product_id INT NOT NULL COMMENT 'Foreign key to products table',
    full_item_code VARCHAR(20) NOT NULL UNIQUE COMMENT 'Full code with metal (e.g., 1000L/10K)',
    
    -- Metal type breakdown
    metal_type VARCHAR(10) NOT NULL COMMENT 'Base metal: 10K, 14K, 18K, STER, GF',
    metal_variant VARCHAR(5) COMMENT 'Variant: B=bulk, W=white, Y=yellow',
    metal_hi VARCHAR(4) COMMENT 'Primary metal from CSV field 6',
    metal_lo VARCHAR(4) COMMENT 'Secondary metal from CSV field 7',
    
    -- Metal-specific weights and costs
    gold_grams DECIMAL(10,3) DEFAULT 0,
    gold_cost DECIMAL(10,2) DEFAULT 0,
    sterling_grams DECIMAL(10,3) DEFAULT 0,
    sterling_cost DECIMAL(10,2) DEFAULT 0,
    material_cost DECIMAL(10,2) DEFAULT 0,
    
    -- Pricing
    total_cost DECIMAL(10,2) DEFAULT 0 COMMENT 'Total cost (all components)',
    selling_price DECIMAL(10,2) DEFAULT 0 COMMENT 'Final selling price',
    previous_price DECIMAL(10,2) DEFAULT 0,
    price_change_date DATE COMMENT 'Last price change (from field 35)',
    cost_change_date DATE COMMENT 'Last cost change (from field 36)',
    
    -- Sales tracking (12-month history from CSV fields 15-26)
    sales_month_1 INT DEFAULT 0,
    sales_month_2 INT DEFAULT 0,
    sales_month_3 INT DEFAULT 0,
    sales_month_4 INT DEFAULT 0,
    sales_month_5 INT DEFAULT 0,
    sales_month_6 INT DEFAULT 0,
    sales_month_7 INT DEFAULT 0,
    sales_month_8 INT DEFAULT 0,
    sales_month_9 INT DEFAULT 0,
    sales_month_10 INT DEFAULT 0,
    sales_month_11 INT DEFAULT 0,
    sales_month_12 INT DEFAULT 0,
    total_sales INT GENERATED ALWAYS AS (
        sales_month_1 + sales_month_2 + sales_month_3 + sales_month_4 +
        sales_month_5 + sales_month_6 + sales_month_7 + sales_month_8 +
        sales_month_9 + sales_month_10 + sales_month_11 + sales_month_12
    ) STORED COMMENT 'Computed total 12-month sales',
    
    -- Metadata
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    
    FOREIGN KEY (product_id) REFERENCES products(product_id) ON DELETE CASCADE,
    INDEX idx_full_item_code (full_item_code),
    INDEX idx_product_id (product_id),
    INDEX idx_metal_type (metal_type),
    INDEX idx_metal_variant (metal_variant)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
COMMENT='Metal variant pricing for AR12 calculator - one row per metal type';
```

---

## Step 2: CSV Field Mapping to New Tables

### IP-EXP01.csv → Database Mapping

| CSV Field | CSV Column | → | Table | DB Column |
|-----------|------------|---|-------|-----------|
| 0 | Item Code | → | `product_variants` | `full_item_code` |
| 0 (parsed) | Base Code | → | `products` | `base_code` |
| 1 | Description | → | `products` | `description` |
| 2 | Price | → | `product_variants` | `selling_price` |
| 3 | Cost | → | `product_variants` | `total_cost` |
| 4 | Material Cost | → | `product_variants` | `material_cost` |
| 5 | Labor Cost | → | `products` | `labor_cost` |
| 6 | Metal Hi | → | `product_variants` | `metal_hi` |
| 7 | Metal Lo | → | `product_variants` | `metal_lo` |
| 8 | Gold Grams | → | `product_variants` | `gold_grams` |
| 9 | Gold Cost | → | `product_variants` | `gold_cost` |
| 10 | Sterling Grams | → | `product_variants` | `sterling_grams` |
| 11 | Sterling Cost | → | `product_variants` | `sterling_cost` |
| 12 | Stone Cost | → | `products` | `stone_cost` |
| 13 | Star Cost | → | `products` | `star_cost` |
| 14 | Stone Setting Cost | → | `products` | `stone_setting_cost` |
| 15-26 | Sales Months 1-12 | → | `product_variants` | `sales_month_1..12` |
| 27 | Markup % | → | `products` | `markup_percent` |
| 28 | Sales Tax % | → | `products` | `sales_tax_percent` |
| 29 | Info 1 | → | `products` | `info_1` |
| 30 | Info 2 | → | `products` | `info_2` |
| 31 | Category | → | `products` | `category` |
| 32 | Group | → | `products` | `group_code` |
| 33 | Labor Hours | → | `products` | `labor_hours (calculated)` |
| 34 | Previous Price | → | `product_variants` | `previous_price` |
| 35 | Price Change Date | → | `product_variants` | `price_change_date` |
| 36 | Cost Change Date | → | `product_variants` | `cost_change_date` |

---

## Step 3: Execution Plan

### Phase 1: Create Tables ✓ (Ready to execute)
```bash
# Run SQL scripts to create both tables
mysql -u cadman_admin -p'Admin2025!Cadman' CadmanClients < create_products_table.sql
mysql -u cadman_admin -p'Admin2025!Cadman' CadmanClients < create_product_variants_table.sql
```

### Phase 2: Import CSV Data (Next)
1. Parse IP-EXP01.csv line by line
2. Extract base code from full item code (split on '/')
3. Insert into `products` (deduplicated by base_code)
4. Insert into `product_variants` (linked to product_id)
5. Calculate labor_hours from labor_cost / 28.00
6. Parse dates from YYYYMMDD format

### Phase 3: Verify Data Integrity
- Count products vs variants (should be ~550 base products, 15,233 variants)
- Check foreign key relationships
- Validate pricing calculations match CSV

### Phase 4: Create PHP API
- Read from database instead of CSV files
- Maintain compatibility with existing pricing-calculator.js
- Add caching for performance

---

## Commands to Execute Now

```bash
# Create products table
mysql -u cadman_admin -p'Admin2025!Cadman' CadmanClients <<'EOF'
[SQL from above]
EOF

# Create product_variants table
mysql -u cadman_admin -p'Admin2025!Cadman' CadmanClients <<'EOF'
[SQL from above]
EOF

# Verify tables created
mysql -u cadman_admin -p'Admin2025!Cadman' CadmanClients -e "SHOW TABLES LIKE 'product%';"
```

**Ready to proceed?** The tables are designed to match your CSV structure exactly while normalizing the data to eliminate duplication.
