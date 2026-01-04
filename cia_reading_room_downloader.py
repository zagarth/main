"""
CIA Reading Room Downloader
Downloads declassified documents from CIA's public reading room
Optional Tor routing for anonymity
"""

import os
import requests
import json
import time
import random
from pathlib import Path
from typing import List, Dict, Optional
import argparse
from urllib.parse import urljoin, urlparse, parse_qs
from bs4 import BeautifulSoup
import re

class CIAReadingRoomDownloader:
    """Download declassified documents from CIA Reading Room"""
    
    BASE_URL = "https://www.cia.gov"
    SEARCH_URL = f"{BASE_URL}/readingroom/search/site"
    
    def __init__(min_delay: float = 3.0, max_delay: float = 30.0):
        self.output_dir = Path(output_dir)
        self.output_dir.mkdir(parents=True, exist_ok=True)
        self.min_delay = min_delay  # Minimum delay
        self.max_delay = max_delay  # Maximum delay
        self.polite_delay = polite_delay  # 3 seconds minimum for government sites
        self.use_tor = use_tor
        
        # Setup session
        self.session = requests.Session()
        
        if use_tor:
            print("🔒 Configuring Tor proxy...")
            self.session.proxies = {
                'http': 'socks5h://127.0.0.1:9050',
                'https': 'socks5h://127.0.0.1:9050'
            }
            # Test Tor connection
            try:
                test = self.session.get('https://check.torproject.org', timeout=10)
                if 'Congratulations' in test.text:
                    print("✅ Tor connection successful!")
                else:
                    print("⚠️  Tor may not be working properly")
            except Exception as e:
                print(f"⚠️  Tor connection failed: {e}")
                print("    Make sure Tor is running (default port 9050)")
        
        self.session.headers.update({
            'User-Agent': 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/120.0.0.0 Safari/537.36',
            'Accept': 'text/html,application/xhtml+xml,application/xml;q=0.9,*/*;q=0.8',
            'Accept-Language': 'en-US,en;q=0.5',
            'Accept-Encoding': 'gzip, deflate',
            'DNT': '1',
            'Connection': 'keep-alive',
            'Upgrade-Insecure-Requests': '1'
        })
    calculate_delay(self, content_size: int = 0, base_delay: Optional[float] = None) -> float:
        """
        Calculate human-like delay based on content size
        Simulates reading time - larger content = longer delay
        """
        if base_delay:
            delay = base_delay
        else:
            # Calculate based on content size (bytes)
            # Small page (< 50KB): 3-8 seconds
            # Medium page (50-200KB): 8-15 seconds  
            # Large page (> 200KB): 15-30 seconds
            
            if content_size < 50000:  # < 50KB
                delay = random.uniform(self.min_delay, self.min_delay + 5)
            elif content_size < 200000:  # 50-200KB
                delay = random.uniform(self.min_delay + 5, self.min_delay + 12)
            
            # Calculate delay based on page size
            content_size = len(response.content)
            self.human_delay(content_size, f"Reading search page ({content_size // 1024}KB)"
                delay = random.uniform(self.min_delay + 12, self.max_delay)
        
        # Add small random variation (+/- 20%) for human-like behavior
        variation = delay * random.uniform(-0.2, 0.2)
        final_delay = max(self.min_delay, delay + variation)
        
        return round(final_delay, 1)
    
    def human_delay(self, content_size: int = 0, message: str = ""):
        """Sleep with human-like delay and progress indicator"""
        delay = self.calculate_delay(content_size)
        
        if message:
            print(f"  ⏳ {message} (simulating {delay}s read time)...", end="", flush=True)
        else:
            print(f"  ⏳ Waiting {delay}s...", end="", flush=True)
        
        time.sleep(delay)
        print(" ✓")
    
    def 
    def search_documents(self, query: str = "", page: int = 0, items_per_page: int = 20) -> Dict:
        """
        Search CIA reading room
        query: Search terms (empty for all documents)
        page: Page number (0-indexed)
        """
        params = {
            'page': page
        }
        
        search_url = f"{self.SEARCH_URL}/{query}" if query else self.SEARCH_URL
        
        print(f"🔍 Searching: {search_url} (page {page})")
        
        try:
            response = self.session.get(search_url, params=params, timeout=30)
            response.raise_for_status()
            time.sleep(self.polite_delay)
            
            soup = BeautifulSoup(response.text, 'html.parser')
            
            # Extract document links
            documents = []
            
            # Find all document entries (adjust selectors based on actual site structure)
            results = soup.find_all('div', class_='views-row') or soup.find_all('article')
            
            for result in results:
                doc = {}
                
                # Extract title
                title_elem = result.find('h3') or result.find('h2') or result.find('a')
                if title_elem:
                    doc['title'] = title_elem.get_text(strip=True)
                    link = title_elem.find('a') if title_elem.name != 'a' else title_elem
                    if link:
                        doc['url'] = urljoin(self.BASE_URL, link.get('href', ''))
                
                # Extract description/summary
                desc_elem = result.find('div', class_='field--name-body') or result.find('p')
                if desc_elem:
                    doc['description'] = desc_elem.get_text(strip=True)[:200]
                
                # Extract document type
            # Simulate reading the document page
            content_size = len(response.content)
            self.human_delay(content_size, f"Reading document page ({content_size // 1024}KB)")
            
                type_elem = result.find('div', class_='field--name-field-document-type')
                if type_elem:
                    doc['doc_type'] = type_elem.get_text(strip=True)
                
                # Extract date
                date_elem = result.find('time') or result.find('span', class_='date')
                if date_elem:
                    doc['date'] = date_elem.get_text(strip=True)
                
                if doc.get('title'):
                    documents.append(doc)
            
            # Check pdf_size = self.download_file(pdf_url, pdf_path)
                    
                    # Longer delay after downloading PDF (simulating "reading" time)
                    if pdf_size:
                        self.human_delay(pdf_size, f"Reading PDF ({pdf_size // 1024}KB)"er') or soup.find('ul', class_='pagination')
            has_next = False
            if pager:
                next_link = pager.find('a', rel='next') or pager.find('li', class_='pager-next')
                has_next = next_link is not None
            
            return {
                'documents': documents,
                'page': page,
                'has_next': has_next,
                'count': len(documents)
            }
            
        except Exception as e:
            print(f"❌ Error searching: {e}")
            return {'documents': [], 'page': page, 'has_next': False, 'count': 0}
    
    def download_document_page(self, url: str, title: str) -> Optional[Path]:
        """Download a document detail page"""
        try:
            print(f"📄 Downloading: {title[:60]}")
            
            # Create safe filename
            safe_title = re.sub(r'[<>:"/\\|?*]', '_', title)[:100]
            
            response = self.session.get(url, timeout=30)
            response.raise_for_status()
            
            soup = BeautifulSoup(response.text, 'html.parser')
            
            # Look for PDF links
            pdf_links = soup.find_all('a', href=re.compile(r'\.pdf$', re.I))
            
            if pdf_links:
                for pdf_link in pdf_links:
                    pdf_url = urljoin(self.BASE_URL, pdf_link.get('href'))
                    pdf_filename = f"{safe_title}.pdf"
                    pdf_path = self.output_dir / pdf_filename
                    
                    if pdf_path.exists():
                        print(f"  ✓ Already exists: {pdf_filename}")
                        continue
                    
                    print(f"  ⬇️  Downloading PDF: {pdf_url}")
                    self.download_file(pdf_url, pdf_path)
                    time.sleep(self.polite_delay)

            if not html_path.exists():
                with open(html_path, 'w', encoding='utf-8') as f:
                    f.write(response.text)
                print(f"  ✓ Saved HTML: {html_filename}")
            
            time.sleep(self.polite_delay)
            return html_path -> Optional[int]:
        """Download a file with progress, returns file size"""
        try:
            response = self.session.get(url, stream=True, timeout=60)
            response.raise_for_status()
            
            total = int(response.headers.get('content-length', 0))
            downloaded = 0
            
            with open(output_path, 'wb') as f:
                for chunk in response.iter_content(chunk_size=8192):
                    if chunk:
                        f.write(chunk)
                        downloaded += len(chunk)
                        if total:
                            percent = downloaded / total * 100
                            print(f"\r    Progress: {percent:.1f}%", end="")
            
            print(f"\n    ✓ Saved: {output_path.name}")
            return downloaded  # Return size for delay calculation
            
        except Exception as e:
            print(f"\n    ❌ Download failed: {e}")
            return None total * 100
                            print(f"\r    Progress: {percent:.1f}%", end="")
            
            print(f"\n    ✓ Saved: {output_path.name}")
            
        except Exception as e:
            print(f"\n    ❌ Download failed: {e}")
    
    def bulk_download(self, query: str = "", max_pages: int = 10, max_documents: int = 100):
        """Download multiple pages of documents"""
        all_documents = []
        page = 0
        
        print("\n" + "="*70)
        print(f"  BULK DOWNLOAD: {query if query else 'All Documents'}")
        print(f"  Max Pages: {max_pages} | Max Documents: {max_documents}")
        print("="*70 + "\n")
        
        while page < max_pages and len(all_documents) < max_documents:
            result = self.search_documents(query, page)
            
            if not result['documents']:
                print(f"No more documents found on page {page}")
                break
            
            print(f"\n📄 Page {page}: Found {result['count']} documents")
            all_documents.extend(result['documents'])
            
            # Download each document
            for doc in result['documents']:
                if len(all_documents) >= max_documents:
                    break
                
                if doc📖 Moving to page {page}...")
            # Longer delay between pages (looks more human)
            delay = random.uniform(self.min_delay + 5, self.min_delay + 15)
            print(f"  ⏳ Pausing {delay:.1f}s between pages...", end="", flush=True)
            time.sleep(delay)
            print(" ✓")
            
            if not result['has_next']:
                print("\nReached last page")
                break
            
            page += 1
            print(f"\nMoving to page {page}...")
            time.sleep(self.polite_delay * 2)  # Extra delay between pages
        
        # Save metadata
        metadata_file = self.output_dir / "documents_metadata.json"
        with open(metadata_file, 'w', encoding='utf-8') as f:
            json.dump(all_documents, f, indent=2, ensure_ascii=False)
        -min-delay", type=float, default=3.0, help="Minimum delay (seconds)")
    parser.add_argument("--max-delay", type=float, default=30.0, help="Maximum delay
        print(f"\n✅ Downloaded {len(all_documents)} documents")
        print(f"✅ Metadata saved: {metadata_file}")


def main():
    parser = argparse.ArgumentParser(description="Download CIA Reading Room documents")
    parser.add_argument("-q", "--query", default="", help="Search query (empty for all)")
    parser.add_argument("-o", "--output", default="cia_documents", help="Output directory")
    parser.add_argument("-p", "--pages", type=int, default=10, help="Max pages to download")
    parser.add_argument("-m", "--max", type=int, default=100, help="Max documents to download")
    parser.add_argument("--tor", action='store_true', help="Use Tor for anonymity (requires Tor running)")
    parser.add_argument("-d", "--delay", type=float, default=3.0, help="Delay between requests (seconds)")
    
    args = parser.parse_args()
    
    print("\n" + "Smart delays: {args.min_delay}-{args.max_delay} seconds")
    print("   (Simulates human reading time based on content size)\n")
    
    downloader = CIAReadingRoomDownloader(args.output, args.tor, args.min_delay, args.max_
    
    if args.tor:
        print("\n⚠️  Using Tor - Make sure Tor is running!")
        print("   Install: https://www.torproject.org/download/")
        print("   Default SOCKS5 port: 9050\n")
    
    print(f"\n⏱️  Polite delay: {args.delay} seconds between requests")
    print("   (Government sites require respectful scraping)\n")
    
    downloader = CIAReadingRoomDownloader(args.output, args.tor, args.delay)
    
    try:
        downloader.bulk_download(args.query, args.pages, args.max)
        print("\n✅ Download complete!")
        print(f"📁 Files saved to: {args.output}")
    except KeyboardInterrupt:
        print("\n\n⏸️  Download interrupted by user")
        print("   Run again to resume (existing files will be skipped)")
    except Exception as e:
        print(f"\n❌ Error: {e}")


if __name__ == "__main__":
    main()
