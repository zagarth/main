#!/usr/bin/env python3
"""
Extract correct page assignments from index files
"""
import subprocess
import re
import os

def extract_index_mappings():
    """Extract product-to-page mappings from all index files"""
    index_files = []
    for file in os.listdir('Cadman_catalog'):
        if file.startswith('index_page_') and file.endswith('.pdf'):
            index_files.append(file)
    
    print(f"Found {len(index_files)} index files")
    
    all_mappings = {}
    
    for index_file in sorted(index_files):
        print(f"\nProcessing {index_file}...")
        
        # Extract text from index
        result = subprocess.run(['pdftotext', f'Cadman_catalog/{index_file}', '-'], 
                              capture_output=True, text=True)
        
        if result.returncode != 0:
            print(f"  Error extracting text: {result.stderr}")
            continue
        
        text = result.stdout
        mappings = parse_index_text(text)
        
        print(f"  Found {len(mappings)} product mappings")
        
        # Add to all mappings
        for product_id, page in mappings.items():
            if product_id in all_mappings:
                print(f"  WARNING: Duplicate {product_id} - was {all_mappings[product_id]}, now {page}")
            all_mappings[product_id] = page
    
    return all_mappings

def parse_index_text(text):
    """Parse index text to extract product-to-page mappings"""
    mappings = {}
    
    # Look for patterns like:
    # 5006M & 5007L ....... 6A
    # 1T84M & 1T85L ...... 2A
    # 100D ................. 23A
    
    lines = text.split('\n')
    
    for line in lines:
        line = line.strip()
        if not line or 'PAGE' in line.upper() or 'Item No' in line:
            continue
        
        # Pattern: product codes followed by dots and page
        match = re.search(r'([^.]+?)\.+\s*([0-9]+[A-Z])\s*$', line)
        if match:
            product_part = match.group(1).strip()
            page = match.group(2).strip()
            
            # Extract individual product codes
            products = extract_products_from_line(product_part)
            
            for product in products:
                if product:
                    mappings[product.upper()] = page
    
    return mappings

def extract_products_from_line(product_part):
    """Extract individual product codes from a line like '5006M & 5007L' or '100D'"""
    products = []
    
    # Handle different formats:
    # "5006M & 5007L"
    # "100D"
    # "1T84M & 1T85L"
    # "500TM & L"
    
    # Split by & first
    parts = re.split(r'\s*&\s*', product_part)
    
    for part in parts:
        part = part.strip()
        
        # Handle cases like "500TM & L" where L refers to 500TL
        if len(part) == 1 and part.isalpha():  # Single letter like "L"
            # Get the base from previous parts
            for prev_part in reversed(parts[:parts.index(part)]):
                if len(prev_part) > 1:
                    # Extract base number (e.g., "500T" from "500TM")
                    base_match = re.match(r'(\d+[A-Z]*)', prev_part)
                    if base_match:
                        base = base_match.group(1)
                        # Remove trailing M/L if present to get base
                        if base.endswith('M'):
                            base = base[:-1]
                        products.append(base + part)
                        break
        else:
            # Regular product code
            # Extract product codes like "1T84M", "5007L", "100D"
            product_matches = re.findall(r'\b[0-9]+[A-Z]*[0-9]*[A-Z]*\b', part)
            products.extend(product_matches)
    
    return products

def update_database_with_mappings(mappings):
    """Update database with correct page assignments"""
    print(f"\nUpdating database with {len(mappings)} mappings...")
    
    success_count = 0
    error_count = 0
    
    for product_id, page in mappings.items():
        # Convert page format (e.g., "6A" -> "page_06a.pdf")
        pdf_file = f"page_{page.lower().zfill(3 if page[:-1].isdigit() else 2)}.pdf"
        
        # Update database
        query = f"""UPDATE catalog_products 
                   SET pdf_file = '{pdf_file}', page_reference = '{page}' 
                   WHERE product_id = '{product_id}'"""
        
        result = subprocess.run(['sudo', 'mysql', '-e', query, 'CadmanClients'], 
                              capture_output=True, text=True)
        
        if result.returncode == 0:
            success_count += 1
            if success_count <= 10:  # Show first 10
                print(f"  ✓ {product_id} → {pdf_file}")
        else:
            error_count += 1
            if error_count <= 5:  # Show first 5 errors
                print(f"  ✗ {product_id} failed: {result.stderr.strip()}")
    
    print(f"\nSummary:")
    print(f"  Success: {success_count}")
    print(f"  Errors: {error_count}")

if __name__ == "__main__":
    mappings = extract_index_mappings()
    
    # Show some examples
    print(f"\nExample mappings:")
    count = 0
    for product, page in mappings.items():
        print(f"  {product} → page_{page.lower()}.pdf")
        count += 1
        if count >= 10:
            break
    
    # Update database
    if input(f"\nUpdate database with {len(mappings)} mappings? (y/n): ").lower() == 'y':
        update_database_with_mappings(mappings)
    else:
        print("Skipped database update")