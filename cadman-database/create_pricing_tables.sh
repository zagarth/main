#!/bin/bash
# ==============================================================================
# Create AR12 Pricing Tables in CadmanClients Database
# ==============================================================================
# This script creates two new tables:
#   1. products - Base product master (deduplicated)
#   2. product_variants - Metal type variants (10K, 14K, STER, etc)
#
# These tables will integrate with existing catalog_products via base_code
# ==============================================================================

DB_USER="cadman_admin"
DB_PASS="Admin2025!Cadman"
DB_NAME="CadmanClients"
SQL_DIR="$(dirname "$0")/sql"

echo "=============================================================================="
echo "Creating AR12 Pricing Calculator Tables"
echo "=============================================================================="
echo ""

# Check if SQL files exist
if [ ! -f "$SQL_DIR/01_create_products_table.sql" ]; then
    echo "ERROR: Cannot find $SQL_DIR/01_create_products_table.sql"
    exit 1
fi

if [ ! -f "$SQL_DIR/02_create_product_variants_table.sql" ]; then
    echo "ERROR: Cannot find $SQL_DIR/02_create_product_variants_table.sql"
    exit 1
fi

# Create products table
echo "Creating 'products' table..."
mysql -u "$DB_USER" -p"$DB_PASS" "$DB_NAME" < "$SQL_DIR/01_create_products_table.sql"
if [ $? -eq 0 ]; then
    echo "✓ products table created successfully"
else
    echo "✗ ERROR creating products table"
    exit 1
fi
echo ""

# Create product_variants table
echo "Creating 'product_variants' table..."
mysql -u "$DB_USER" -p"$DB_PASS" "$DB_NAME" < "$SQL_DIR/02_create_product_variants_table.sql"
if [ $? -eq 0 ]; then
    echo "✓ product_variants table created successfully"
else
    echo "✗ ERROR creating product_variants table"
    exit 1
fi
echo ""

# Verify tables were created
echo "Verifying tables..."
mysql -u "$DB_USER" -p"$DB_PASS" "$DB_NAME" -e "SHOW TABLES LIKE 'product%';"
echo ""

# Show table structures
echo "=============================================================================="
echo "Table Structure: products"
echo "=============================================================================="
mysql -u "$DB_USER" -p"$DB_PASS" "$DB_NAME" -e "DESCRIBE products;"
echo ""

echo "=============================================================================="
echo "Table Structure: product_variants"
echo "=============================================================================="
mysql -u "$DB_USER" -p"$DB_PASS" "$DB_NAME" -e "DESCRIBE product_variants;"
echo ""

echo "=============================================================================="
echo "SUCCESS! Tables created and ready for data import"
echo "=============================================================================="
echo ""
echo "Next steps:"
echo "  1. Run import script to load CSV data into tables"
echo "  2. Verify data integrity and foreign key relationships"
echo "  3. Test join queries with catalog_products table"
echo ""
echo "Join example:"
echo "  SELECT p.*, cp.pdf_file, cp.image_files"
echo "  FROM products p"
echo "  LEFT JOIN catalog_products cp ON p.base_code = cp.product_id"
echo "  LIMIT 10;"
echo ""
