# Three-Table Relationship: Pricing + Catalog Integration

## ER Diagram

```
┌─────────────────────────────────┐
│   catalog_products              │  (EXISTING - Catalog PDFs & Images)
│─────────────────────────────────│
│ PK: product_id (e.g., "1000L")  │
│     product_name                 │
│     page_reference              │
│     pdf_file                    │
│     image_files                 │
│     width_mm, height_mm         │
│     gender_variant              │
└─────────────────────────────────┘
                  ▲
                  │
                  │ JOIN ON: base_code = product_id
                  │
┌─────────────────────────────────┐
│   products                      │  (NEW - Base Product Master)
│─────────────────────────────────│
│ PK: product_id (INT)            │
│ UK: base_code (e.g., "1000L")   │◄─┐
│     description                  │  │
│     labor_cost                   │  │
│     labor_hours                  │  │
│     stone_cost                   │  │
│     markup_percent               │  │
│     category                     │  │
└─────────────────────────────────┘  │
                                      │
                                      │ FOREIGN KEY: product_id
                                      │
                  ┌───────────────────┘
                  │
┌─────────────────────────────────┐
│   product_variants              │  (NEW - Metal Variants)
│─────────────────────────────────│
│ PK: variant_id                  │
│ FK: product_id → products       │
│ UK: full_item_code              │
│     (e.g., "1000L/10K")         │
│     metal_type (10K, 14K, etc)  │
│     gold_grams                   │
│     selling_price                │
│     total_cost                   │
│     sales_month_1..12            │
└─────────────────────────────────┘
```

## Complete Query Example

Get pricing for all metal variants of an item WITH catalog images/PDFs:

```sql
SELECT 
    -- Base product info
    p.base_code,
    p.description,
    p.labor_cost,
    p.labor_hours,
    p.markup_percent,
    
    -- Variant-specific pricing
    pv.full_item_code,
    pv.metal_type,
    pv.metal_variant,
    pv.gold_grams,
    pv.selling_price,
    pv.total_cost,
    pv.total_sales,
    
    -- Catalog data (images, PDF)
    cp.pdf_file,
    cp.page_reference,
    cp.image_files,
    cp.width_mm,
    cp.height_mm,
    cp.gender_variant
    
FROM products p

-- Get all metal variants
INNER JOIN product_variants pv 
    ON pv.product_id = p.product_id

-- Get catalog images/PDF (if available)
LEFT JOIN catalog_products cp 
    ON p.base_code = cp.product_id

WHERE p.base_code = '1000L'
ORDER BY 
    pv.metal_type,
    pv.metal_variant;
```

## Benefits of This Structure

1. **No Data Duplication**
   - Base product description stored ONCE in `products`
   - Labor cost, markup, category stored ONCE
   - Only metal-specific data in `product_variants`

2. **Easy Price Updates**
   - Update labor rate → affects ALL products
   - Update markup → affects all variants of ONE product
   - Update gold price → recalculate only gold costs

3. **Catalog Integration**
   - Pricing data automatically links to catalog images
   - One query gets: price + cost + image + PDF reference

4. **Efficient Queries**
   - Search by base_code: finds all metal variants
   - Compare prices: 10K vs 14K for same item
   - Sales analysis: total sales across all metals

## Example Data Flow

**CSV Input:**
```
1000L/10K|10KTT CELTIC COILS WED|1,696.75|1,013.52|0.00|28.00|10K||10.000|...
1000L/14K|14KTT CELTIC COILS WED|2,340.50|1,443.62|0.00|28.00|14K||10.000|...
1000L/STER|STER CELTIC COILS WED|848.25|523.17|0.00|28.00|STER||0.000|...
```

**Database Result:**

`products` table (1 row):
```
product_id: 1
base_code: "1000L"
description: "CELTIC COILS WED"
labor_cost: 28.00
labor_hours: 1.000
markup_percent: 55.00
```

`product_variants` table (3 rows):
```
variant_id: 1, product_id: 1, full_item_code: "1000L/10K", metal_type: "10K", gold_grams: 10.000, selling_price: 1696.75
variant_id: 2, product_id: 1, full_item_code: "1000L/14K", metal_type: "14K", gold_grams: 10.000, selling_price: 2340.50
variant_id: 3, product_id: 1, full_item_code: "1000L/STER", metal_type: "STER", gold_grams: 0.000, selling_price: 848.25
```

`catalog_products` link (existing):
```
product_id: "1000L"
pdf_file: "page_01b.pdf"
page_reference: "page_01b"
image_files: "1000L_main.jpg,1000L_detail.jpg"
```

**Single Query Result:**
All variants WITH images and PDF reference in one result set!
