#!/usr/bin/env python3
"""
Update database with page_03eee.pdf products
"""

import json

def update_products_from_json(json_file):
    """Generate SQL commands to update database products from JSON file"""
    
    # Read the JSON file
    try:
        with open(json_file, 'r') as f:
            data = json.load(f)
        
        products = data['pages'][0]['detected_products']
        print(f"Found {len(products)} products to update")
        
    except Exception as e:
        print(f"Error reading JSON file: {e}")
        return []
    
    update_commands = []
    
    for product_id in products:
        # First check if product exists
        check_cmd = f"SELECT product_id, page_reference, pdf_file FROM catalog_products WHERE product_id = '{product_id}';"
        
        # Update command (only if product exists and doesn't already have page info)
        update_cmd = f"""UPDATE catalog_products 
SET page_reference = 'page_03eee', 
    pdf_file = 'page_03eee.pdf'
WHERE product_id = '{product_id}' 
AND (page_reference IS NULL OR page_reference = '' OR pdf_file IS NULL OR pdf_file = '');"""
        
        update_commands.append((product_id, check_cmd, update_cmd))
    
    return update_commands

def main():
    json_file = "Cadman_catalog/page_03eee_analysis.json"
    
    commands = update_products_from_json(json_file)
    
    if commands:
        # Write SQL commands to file
        with open('update_page_03eee_products.sql', 'w') as f:
            f.write("-- Check existing products and update page references for page_03eee.pdf\n\n")
            
            # First, let's check which products exist
            f.write("-- Check which products exist:\n")
            for product_id, check_cmd, update_cmd in commands:
                f.write(f"-- {product_id}:\n")
                f.write(check_cmd + "\n")
            
            f.write("\n-- Update commands:\n")
            for product_id, check_cmd, update_cmd in commands:
                f.write(f"-- Update {product_id}:\n")
                f.write(update_cmd + "\n\n")
        
        print(f"SQL commands saved to: update_page_03eee_products.sql")
        print(f"Total products to check: {len(commands)}")

if __name__ == "__main__":
    main()