#!/usr/bin/env python3
"""
Product Page Verification and Correction Tool
Systematically verifies product locations against actual PDF content
"""

import subprocess
import os
import re
import sys
import json
from typing import Dict, List, Tuple, Optional

class ProductPageVerifier:
    def __init__(self):
        self.catalog_dir = 'Cadman_catalog'
        
    def run_mysql_query(self, query: str, params: List = None) -> List[Tuple]:
        """Run MySQL query using subprocess"""
        try:
            if params:
                # For parameterized queries, we'll format them (basic implementation)
                formatted_query = query
                if params:
                    for param in params:
                        formatted_query = formatted_query.replace('%s', f"'{param}'", 1)
            else:
                formatted_query = query
            
            cmd = ['sudo', 'mysql', '-e', formatted_query, 'CadmanClients']
            result = subprocess.run(cmd, capture_output=True, text=True)
            
            if result.returncode == 0:
                lines = result.stdout.strip().split('\n')
                if len(lines) > 1:  # Skip header
                    return [tuple(line.split('\t')) for line in lines[1:]]
                return []
            else:
                print(f"MySQL error: {result.stderr}")
                return []
                
        except Exception as e:
            print(f"Error running query: {e}")
            return []
    
    def run_mysql_update(self, query: str, params: List = None) -> bool:
        """Run MySQL update using subprocess"""
        try:
            if params:
                formatted_query = query
                for param in params:
                    formatted_query = formatted_query.replace('%s', f"'{param}'", 1)
            else:
                formatted_query = query
            
            cmd = ['sudo', 'mysql', '-e', formatted_query, 'CadmanClients']
            result = subprocess.run(cmd, capture_output=True, text=True)
            
            return result.returncode == 0
            
        except Exception as e:
            print(f"Error running update: {e}")
            return False
    
    def extract_pdf_text(self, pdf_path: str) -> Optional[str]:
        """Extract text from PDF using pdftotext"""
        try:
            result = subprocess.run(['pdftotext', pdf_path, '-'], 
                                  capture_output=True, text=True)
            if result.returncode == 0:
                return result.stdout
            else:
                print(f"Error extracting {pdf_path}: {result.stderr}")
                return None
        except FileNotFoundError:
            print("pdftotext not found. Install poppler-utils.")
            return None
    
    def search_product_in_pdf(self, product_id: str, pdf_path: str) -> bool:
        """Check if product ID exists in PDF"""
        text = self.extract_pdf_text(pdf_path)
        if not text:
            return False
        
        # Search for exact product ID (case insensitive)
        pattern = re.compile(re.escape(product_id), re.IGNORECASE)
        return bool(pattern.search(text))
    
    def get_all_pdf_files(self) -> List[str]:
        """Get list of all catalog PDF files"""
        pdf_files = []
        for file in os.listdir(self.catalog_dir):
            if file.endswith('.pdf') and file.startswith('page_'):
                pdf_files.append(file)
        return sorted(pdf_files)
    
    def find_product_in_catalog(self, product_id: str) -> Optional[str]:
        """Search for product across all catalog pages"""
        print(f"Searching for {product_id} across all catalog pages...")
        
        pdf_files = self.get_all_pdf_files()
        for pdf_file in pdf_files:
            pdf_path = os.path.join(self.catalog_dir, pdf_file)
            if self.search_product_in_pdf(product_id, pdf_path):
                print(f"  ✓ Found {product_id} in {pdf_file}")
                return pdf_file
        
        print(f"  ✗ {product_id} not found in any catalog page")
        return None
    
    def verify_product_assignment(self, product_id: str, assigned_pdf: str) -> bool:
        """Verify if product is actually on assigned page"""
        if not assigned_pdf:
            return False
        
        pdf_path = os.path.join(self.catalog_dir, assigned_pdf)
        if not os.path.exists(pdf_path):
            return False
        
        return self.search_product_in_pdf(product_id, pdf_path)
    
    def correct_product_assignment(self, product_id: str, correct_pdf: str):
        """Update database with correct PDF assignment"""
        page_ref = correct_pdf.replace('page_', '').replace('.pdf', '').upper()
        
        query = f"""UPDATE catalog_products 
                   SET pdf_file = '{correct_pdf}', page_reference = '{page_ref}' 
                   WHERE product_id = '{product_id}'"""
        
        if self.run_mysql_update(query):
            print(f"  ✓ Updated {product_id} → {correct_pdf}")
        else:
            print(f"  ✗ Failed to update {product_id}")
    
    def get_products_to_verify(self, limit: int = None) -> List[Tuple[str, str]]:
        """Get products that need verification"""
        query = """SELECT product_id, pdf_file 
                  FROM catalog_products 
                  WHERE pdf_file IS NOT NULL 
                  ORDER BY product_id"""
        
        if limit:
            query += f" LIMIT {limit}"
        
        results = self.run_mysql_query(query)
        return [(row[0], row[1]) for row in results]
    
    def verify_and_correct_batch(self, limit: int = 50):
        """Verify and correct a batch of products"""
        products = self.get_products_to_verify(limit)
        
        print(f"Verifying {len(products)} products...")
        
        verified_count = 0
        corrected_count = 0
        not_found_count = 0
        
        for product_id, assigned_pdf in products:
            print(f"\nChecking {product_id} (assigned to {assigned_pdf})")
            
            # Check if assignment is correct
            if self.verify_product_assignment(product_id, assigned_pdf):
                print(f"  ✓ {product_id} correctly assigned to {assigned_pdf}")
                verified_count += 1
            else:
                print(f"  ✗ {product_id} NOT found in {assigned_pdf}")
                
                # Search for correct location
                correct_pdf = self.find_product_in_catalog(product_id)
                
                if correct_pdf:
                    if correct_pdf != assigned_pdf:
                        self.correct_product_assignment(product_id, correct_pdf)
                        corrected_count += 1
                else:
                    not_found_count += 1
        
        print(f"\n=== Verification Summary ===")
        print(f"Verified correct: {verified_count}")
        print(f"Corrected: {corrected_count}")
        print(f"Not found: {not_found_count}")
        print(f"Total processed: {len(products)}")
    
    def reset_questionable_mappings(self):
        """Reset mappings that were likely guessed incorrectly"""
        query = """UPDATE catalog_products 
                  SET pdf_file = NULL, page_reference = NULL 
                  WHERE source LIKE 'index_pdf_extract:%'
                  AND pdf_file IS NOT NULL"""
        
        if self.run_mysql_update(query):
            print("Reset questionable mappings from index extracts")
            print("Kept XML-sourced and manually verified mappings")
        else:
            print("Failed to reset mappings")
    
    def verify_specific_product(self, product_id: str):
        """Verify and correct a specific product"""
        print(f"Verifying {product_id}...")
        
        # Get current assignment
        query = f"SELECT pdf_file FROM catalog_products WHERE product_id = '{product_id}'"
        results = self.run_mysql_query(query)
        
        if not results:
            print(f"Product {product_id} not found in database")
            return
        
        current_pdf = results[0][0] if results[0][0] != 'NULL' else None
        print(f"Currently assigned to: {current_pdf or 'None'}")
        
        # Verify current assignment or find correct one
        if current_pdf and self.verify_product_assignment(product_id, current_pdf):
            print(f"✓ {product_id} correctly assigned to {current_pdf}")
        else:
            correct_pdf = self.find_product_in_catalog(product_id)
            if correct_pdf:
                self.correct_product_assignment(product_id, correct_pdf)
            else:
                print(f"✗ {product_id} not found in any catalog page")

def main():
    verifier = ProductPageVerifier()
    
    if len(sys.argv) < 2:
        print("Usage:")
        print("  python3 verify_products.py verify <product_id>  - Verify specific product")
        print("  python3 verify_products.py batch [limit]        - Verify batch of products")
        print("  python3 verify_products.py reset                - Reset questionable mappings")
        return
    
    command = sys.argv[1]
    
    if command == "verify" and len(sys.argv) > 2:
        product_id = sys.argv[2].upper()
        verifier.verify_specific_product(product_id)
    
    elif command == "batch":
        limit = int(sys.argv[2]) if len(sys.argv) > 2 else 50
        verifier.verify_and_correct_batch(limit)
    
    elif command == "reset":
        verifier.reset_questionable_mappings()
    
    else:
        print("Invalid command")

if __name__ == "__main__":
    main()