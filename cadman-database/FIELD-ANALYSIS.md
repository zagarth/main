# CSV to Database Field Analysis

## Current Item Code Pattern

**Item codes follow this pattern:**
```
BASE_ITEM/METAL_VARIANT
```

**Examples:**
- `1000L/10K`, `1000L/10KW`, `1000L/10KY`, `1000L/14K`, `1000L/18K`, `1000L/STER`
- `100/10K`, `100/14K`, `100/GF`, `100/STER`
- `050DT/10K`, `050DT/10KB`

**Metal Variants:**
- `10K`, `10KB`, `10KW`, `10KY` (10 karat: standard, bulk, white, yellow)
- `14K`, `14KB` (14 karat: standard, bulk)
- `18K` (18 karat)
- `STER`, `STERB` (sterling: standard, bulk)
- `GF`, `GFB` (gold filled: standard, bulk)

---

## IP-EXP01.csv Field Mapping (37 fields)

| Field | CSV Column | Data Loader Property | Type | Category | Notes |
|-------|------------|---------------------|------|----------|-------|
| 0 | Item Code | `itemCode` | VARCHAR(20) | **VARIANT** | Full code with metal type |
| 1 | Description | `description` | VARCHAR(50) | **BASE** | Product description |
| 2 | Price | `price` | DECIMAL(10,2) | **VARIANT** | Selling price (varies by metal) |
| 3 | Cost | `cost` | DECIMAL(10,2) | **VARIANT** | Total cost (varies by metal) |
| 4 | Material Cost | `materialCost` | DECIMAL(10,2) | **VARIANT** | Material cost component |
| 5 | Labor Cost | `laborCost` | DECIMAL(10,2) | **BASE** | Labor cost ($28.00) |
| 6 | Metal Hi | `metalHi` | VARCHAR(4) | **VARIANT** | Primary metal type (10K, 14K, etc) |
| 7 | Metal Lo | `metalLo` | VARCHAR(4) | **VARIANT** | Secondary metal (usually empty) |
| 8 | Gold Grams | `goldGrams` | DECIMAL(10,3) | **VARIANT** | Gold weight (varies by karat) |
| 9 | Gold Cost | `goldCost` | DECIMAL(10,2) | **VARIANT** | Calculated gold cost |
| 10 | Sterling Grams | `sterlingGrams` | DECIMAL(10,3) | **VARIANT** | Sterling weight |
| 11 | Sterling Cost | `sterlingCost` | DECIMAL(10,2) | **VARIANT** | Calculated sterling cost |
| 12 | Stone Cost | `stoneCost` | DECIMAL(10,2) | **BASE** | Stone/gem cost |
| 13 | Star Cost | `starCost` | DECIMAL(10,2) | **BASE** | Star component cost |
| 14 | Stone Setting Cost | `stoneSettingCost` | DECIMAL(10,2) | **BASE** | Setting labor cost |
| 15-26 | Sales Month 1-12 | `salesHistory[0-11]` | INTEGER | **VARIANT** | 12-month sales units |
| 27 | Markup % | `markup` | DECIMAL(5,2) | **BASE** | Item markup percentage (50, 55, etc) |
| 28 | Sales Tax % | `salesTax` | DECIMAL(5,2) | **BASE** | Sales tax percentage |
| 29 | Info 1 | `info1` | VARCHAR(30) | **BASE** | Additional info field |
| 30 | Info 2 | `info2` | VARCHAR(30) | **BASE** | Additional info field |
| 31 | Category | `category` | VARCHAR(10) | **BASE** | Product category code |
| 32 | Group | `group` | VARCHAR(5) | **BASE** | Product group |
| 33 | Labor Hours | `laborHours` | DECIMAL(10,3) | **BASE** | Labor hours (0 in export, calculated) |
| 34 | Previous Price | `previousPrice` | DECIMAL(10,2) | **VARIANT** | Previous selling price |
| 35 | Price Change Date | `priceChangeDate` | VARCHAR(8) | **VARIANT** | Date of last price change (YYYYMMDD) |
| 36 | Cost Change Date | `costChangeDate` | VARCHAR(8) | **VARIANT** | Date of last cost change (YYYYMMDD) |

**Additional calculated fields in data-loader.js:**
- `totalSales` - Sum of 12-month sales history

---

## Proposed Database Schema

### Table 1: `products` (Base Product Master)

Base product without metal variants - one row per unique item.

```sql
CREATE TABLE products (
    product_id INT PRIMARY KEY AUTO_INCREMENT,
    base_code VARCHAR(20) NOT NULL UNIQUE,  -- e.g., "1000L", "100", "050DT"
    description VARCHAR(50) NOT NULL,
    
    -- Base costs (metal-independent)
    labor_cost DECIMAL(10,2) DEFAULT 0,
    labor_hours DECIMAL(10,3) DEFAULT 0,
    stone_cost DECIMAL(10,2) DEFAULT 0,
    star_cost DECIMAL(10,2) DEFAULT 0,
    stone_setting_cost DECIMAL(10,2) DEFAULT 0,
    
    -- Pricing configuration
    markup_percent DECIMAL(5,2) DEFAULT 50,
    sales_tax_percent DECIMAL(5,2) DEFAULT 0,
    
    -- Categorization
    category VARCHAR(10),
    group_code VARCHAR(5),
    info_1 VARCHAR(30),
    info_2 VARCHAR(30),
    
    -- Metadata
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    
    INDEX idx_base_code (base_code),
    INDEX idx_category (category)
);
```

### Table 2: `product_variants` (Metal Type Variants)

One row per metal variant (10K, 14K, STER, etc).

```sql
CREATE TABLE product_variants (
    variant_id INT PRIMARY KEY AUTO_INCREMENT,
    product_id INT NOT NULL,
    full_item_code VARCHAR(20) NOT NULL UNIQUE,  -- e.g., "1000L/10K"
    metal_type VARCHAR(10) NOT NULL,             -- "10K", "14K", "STER", "GF"
    metal_variant VARCHAR(5),                    -- "B" for bulk, "W" for white, etc
    
    -- Metal-specific weights and costs
    gold_grams DECIMAL(10,3) DEFAULT 0,
    gold_cost DECIMAL(10,2) DEFAULT 0,
    sterling_grams DECIMAL(10,3) DEFAULT 0,
    sterling_cost DECIMAL(10,2) DEFAULT 0,
    material_cost DECIMAL(10,2) DEFAULT 0,
    
    -- Pricing
    total_cost DECIMAL(10,2) DEFAULT 0,
    selling_price DECIMAL(10,2) DEFAULT 0,
    previous_price DECIMAL(10,2) DEFAULT 0,
    price_change_date DATE,
    cost_change_date DATE,
    
    -- Sales tracking (12-month history)
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
    
    -- Metadata
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    
    FOREIGN KEY (product_id) REFERENCES products(product_id) ON DELETE CASCADE,
    INDEX idx_full_item_code (full_item_code),
    INDEX idx_product_id (product_id),
    INDEX idx_metal_type (metal_type)
);
```

---

## Item Code Parsing Logic

To extract base code from full item code:

```javascript
function parseItemCode(fullCode) {
    const parts = fullCode.split('/');
    return {
        baseCode: parts[0].trim(),
        metalType: parts[1] ? parts[1].replace(/[BWY]$/, '').trim() : '',
        metalVariant: parts[1] ? (parts[1].match(/[BWY]$/) || [''])[0] : ''
    };
}

// Examples:
// "1000L/10K" → base: "1000L", metal: "10K", variant: ""
// "1000L/10KW" → base: "1000L", metal: "10K", variant: "W"
// "050DT/10KB" → base: "050DT", metal: "10K", variant: "B"
```

---

## Migration Strategy

### Phase 1: Analysis ✓
- Compare CSV fields to database schema
- Identify BASE vs VARIANT data
- Design normalized tables

### Phase 2: Database Setup (NEXT)
- Create MySQL/MariaDB database
- Create tables with schema above
- Add indexes for performance

### Phase 3: Data Import
- Parse CSV and split item codes
- Extract base product data (deduplicated)
- Insert variants linked to base products
- Verify data integrity

### Phase 4: API Layer
- Create PHP endpoints to read from database
- Replace CSV loading with database queries
- Maintain compatibility with existing frontend

### Phase 5: Testing
- Verify all pricing calculations still work
- Test search and filtering
- Compare results to CSV-based system

---

## Key Decisions Needed

1. **Database Choice**: MySQL/MariaDB or SQLite?
   - MySQL/MariaDB: Better for production, multi-user
   - SQLite: Simpler setup, single file

2. **Sales History Storage**: Separate table or columns?
   - **Current proposal**: 12 columns (simpler queries)
   - Alternative: `sales_history` table with month/year rows

3. **Metal Variant Encoding**: Separate field or parse from code?
   - **Current proposal**: Parse and store separately
   - Benefit: Can query "all 10K variants" easily

4. **Backward Compatibility**: Keep CSV files?
   - **Recommended**: Keep CSVs as backup/import source
   - Database becomes primary source
   - CSV export for COBOL system updates
