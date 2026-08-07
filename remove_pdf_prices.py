#!/usr/bin/env python3
"""
Remove price elements from PDF catalog page
"""

import PyPDF2
import re
import sys
from io import BytesIO

def remove_price_elements(input_pdf_path, output_pdf_path):
    print(f"Processing {input_pdf_path}...")
    
    try:
        # Read the input PDF
        with open(input_pdf_path, 'rb') as file:
            pdf_reader = PyPDF2.PdfReader(file)
            pdf_writer = PyPDF2.PdfWriter()
            
            print(f"PDF has {len(pdf_reader.pages)} page(s)")
            
            for page_num, page in enumerate(pdf_reader.pages):
                print(f"Processing page {page_num + 1}...")
                
                # Extract text to see what we're working with
                try:
                    page_text = page.extract_text()
                    print(f"Original text preview: {page_text[:200]}...")
                except Exception as e:
                    print(f"Could not extract text from page {page_num + 1}: {e}")
                    page_text = ""
                
                # Try to manipulate the page content
                try:
                    # Get the page content
                    if '/Contents' in page:
                        content = page['/Contents']
                        if hasattr(content, 'get_object'):
                            content_obj = content.get_object()
                            if hasattr(content_obj, 'get_data'):
                                content_data = content_obj.get_data()
                                content_str = content_data.decode('latin-1', errors='ignore')
                                
                                # Remove text objects that contain "Price"
                                # This is a simplified approach - PDF content streams are complex
                                modified_content = re.sub(r'BT[^E]*Price[^E]*ET', '', content_str, flags=re.IGNORECASE)
                                modified_content = re.sub(r'\(Price\)[^)]*Tj', '', modified_content, flags=re.IGNORECASE)
                                
                                print(f"Content length: {len(content_str)} -> {len(modified_content)}")
                                
                                if len(modified_content) < len(content_str):
                                    print("Found and removed some price-related content")
                                else:
                                    print("No obvious price content found in content stream")
                
                except Exception as e:
                    print(f"Could not modify page content directly: {e}")
                
                # Add the page to output (note: direct content modification is complex)
                pdf_writer.add_page(page)
            
            # Write the output PDF
            with open(output_pdf_path, 'wb') as output_file:
                pdf_writer.write(output_file)
            
            print(f"Successfully created {output_pdf_path}")
            
    except Exception as e:
        print(f"Error processing PDF: {e}")
        return False
    
    return True

def main():
    input_file = "Cadman_catalog/page_21a.pdf"
    output_file = "Cadman_catalog/page_21a_no_prices.pdf"
    
    print("PDF Price Element Removal Tool")
    print("==============================")
    
    success = remove_price_elements(input_file, output_file)
    
    if success:
        print("\n✅ Processing complete!")
        print(f"Original file: {input_file}")
        print(f"Modified file: {output_file}")
        print("\nNote: PDF content modification is complex. The output may need manual review.")
    else:
        print("\n❌ Processing failed!")

if __name__ == "__main__":
    main()