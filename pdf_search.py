#!/usr/bin/env python3
import sys
import re
try:
    import PyPDF2
except ImportError:
    print("PyPDF2 not available, trying pdfplumber...")
    try:
        import pdfplumber
    except ImportError:
        print("Neither PyPDF2 nor pdfplumber available, trying pdftotext...")
        import subprocess
        import os

def extract_text_pdftotext(pdf_path):
    """Extract text using pdftotext command line tool"""
    try:
        result = subprocess.run(['pdftotext', pdf_path, '-'], 
                              capture_output=True, text=True)
        if result.returncode == 0:
            return result.stdout
        else:
            print(f"pdftotext error: {result.stderr}")
            return None
    except FileNotFoundError:
        print("pdftotext not found. Please install poppler-utils.")
        return None

def search_in_pdf(pdf_path, search_term):
    """Search for a term in a PDF file"""
    print(f"Searching for '{search_term}' in {pdf_path}")
    
    # Try pdftotext first (most reliable for text extraction)
    text = extract_text_pdftotext(pdf_path)
    
    if text:
        # Search for the term (case insensitive)
        pattern = re.compile(re.escape(search_term), re.IGNORECASE)
        matches = pattern.finditer(text)
        
        match_count = 0
        for match in matches:
            match_count += 1
            start = match.start()
            end = match.end()
            
            # Get context around the match (100 chars before and after)
            context_start = max(0, start - 100)
            context_end = min(len(text), end + 100)
            context = text[context_start:context_end]
            
            print(f"\nMatch {match_count} found:")
            print(f"Position: {start}-{end}")
            print(f"Context: ...{context}...")
            print("-" * 50)
        
        if match_count == 0:
            print(f"'{search_term}' not found in the PDF.")
        else:
            print(f"\nTotal matches found: {match_count}")
            
        # Also show a sample of the extracted text to verify extraction worked
        print(f"\nFirst 200 characters of extracted text:")
        print(text[:200])
        print("...")
        
    else:
        print("Could not extract text from PDF")

if __name__ == "__main__":
    pdf_file = "Cadman_catalog/page_06a.pdf"
    search_term = "5007L"
    
    if len(sys.argv) > 1:
        search_term = sys.argv[1]
    if len(sys.argv) > 2:
        pdf_file = sys.argv[2]
    
    search_in_pdf(pdf_file, search_term)