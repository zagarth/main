"""
Archive.org Collection Downloader
Downloads and organizes content from Internet Archive for game development
"""

import os
import requests
import json
import time
from pathlib import Path
from typing import List, Dict, Optional
import argparse
from urllib.parse import quote

class ArchiveOrgDownloader:
    """Download content from Archive.org collections"""
    
    BASE_URL = "https://archive.org"
    SEARCH_API = f"{BASE_URL}/advancedsearch.php"
    METADATA_API = f"{BASE_URL}/metadata"
    DOWNLOAD_API = f"{BASE_URL}/download"
    
    def __init__(self, output_dir: str = "archive_downloads", polite_delay: float = 1.0):
        self.output_dir = Path(output_dir)
        self.output_dir.mkdir(parents=True, exist_ok=True)
        self.polite_delay = polite_delay  # Delay between requests in seconds
        self.session = requests.Session()
        self.session.headers.update({
            'User-Agent': 'GameDevDataPipeline/1.0 (Educational/Research; +https://github.com/zagarth/main)'
        })
    
    def search_collection(self, query: str, collection: Optional[str] = None, 
                         mediatype: Optional[str] = None, max_results: int = 100) -> List[Dict]:
        """
        Search Archive.org
        
        mediatypes: texts, audio, movies, software, image, data, web
        collections: e.g., 'opensource', 'gutenberg', 'prelinger'
        """
        params = {
            'q': query,
            'output': 'json',
            'rows': max_results,
            'page': 1
        }
        
        if collection:
            params['q'] += f' AND collection:{collection}'
        
        if mediatype:
            params['q'] += f' AND mediatype:{mediatype}'
        
        print(f"Searching: {params['q']}")
        
        response = self.session.get(self.SEARCH_API, params=params)
        response.raise_for_status()
        time.sleep(self.polite_delay)  # Be polite
        
        data = response.json()
        docs = data.get('response', {}).get('docs', [])
        
        print(f"Found {len(docs)} items")
        return docs
    
    def get_item_metadata(self, identifier: str) -> Dict:
        """Get detailed metadata for an item"""
        url = f"{self.METADATA_API}/{identifier}"
        response = self.session.get(url)
        response.raise_for_status()
        time.sleep(self.polite_delay)  # Be polite
        return response.json()
    
    def get_item_files(self, identifier: str) -> List[Dict]:
        """Get list of files for an item"""
        metadata = self.get_item_metadata(identifier)
        return metadata.get('files', [])
    
    def download_file(self, identifier: str, filename: str, output_path: Optional[Path] = None):
        """Download a specific file from an item"""
        if output_path is None:
            output_path = self.output_dir / identifier / filename
        
        output_path.parent.mkdir(parents=True, exist_ok=True)
        
        if output_path.exists():
            print(f"Already exists: {output_path}")
            return
        
        url = f"{self.DOWNLOAD_API}/{identifier}/{quote(filename)}"
        print(f"Downloading: {filename}")
        self.session
        response = requests.get(url, stream=True)
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
                        print(f"\r  Progress: {percent:.1f}%", end="")
        
        time.sleep(self.polite_delay)  # Be polite between downloads
        print(f"\n  Saved: {output_path}")
    
    def download_item(self, identifier: str, file_formats: Optional[List[str]] = None,
                     exclude_formats: Optional[List[str]] = None):
        """
        Download all files from an item
        
        file_formats: e.g., ['pdf', 'txt', 'epub'] - only download these
        exclude_formats: e.g., ['xml', 'marc'] - skip these
        """
        print(f"\nProcessing item: {identifier}")
        
        files = self.get_item_files(identifier)
        
        # Filter by format
        if file_formats:
            files = [f for f in files if f.get('format', '').lower() in [fmt.lower() for fmt in file_formats]]
        
        if exclude_formats:
            files = [f for f in files if f.get('format', '').lower() not in [fmt.lower() for fmt in exclude_formats]]
        
        print(f"  Downloading {len(files)} file(s)")
        
        for file_info in files:
            filename = file_info.get('name')
            if filename:
                try:
                    self.download_file(identifier, filename)
                except Exception as e:
                    print(f"  Error downloading {filename}: {e}")
    
    def download_collection_items(self, query: str, collection: Optional[str] = None,
                                  mediatype: Optional[str] = None, max_items: int = 10,
                                  file_formats: Optional[List[str]] = None):
        """Download multiple items from a search/collection"""
        items = self.search_collection(query, collection, mediatype, max_items)
        
        print(f"\nDownloading {len(items)} items...")
        
        for i, item in enumerate(items, 1):
            identifier = item.get('identifier')
            title = item.get('title', 'Unknown')
            
            print(f"\n[{i}/{len(items)}] {title}")
            
            if identifier:
                self.download_item(identifier, file_formats)
    
    def save_search_results(self, results: List[Dict], filename: str = "search_results.json"):
        """Save search results metadata for later use"""
        output_path = self.output_dir / filename
        
        with open(output_path, 'w', encoding='utf-8') as f:
            json.dump(results, f, indent=2, ensure_ascii=False)
        
        print(f"Search results saved: {output_path}")


def print_header():
    """Print elegant header"""
    print("\n" + "="*70)
    print("  ARCHIVE.ORG GAME CONTENT DOWNLOADER")
    print("  Download historical texts, images, audio for game development")
    print("="*70 + "\n")


def print_menu():
    """Display main menu"""
    print("\n┌─────────────────────────────────────────┐")
    print("│         MAIN MENU                       │")
    print("├─────────────────────────────────────────┤")
    print("│ 1. Browse Popular Collections           │")
    print("│ 2. Search by Keyword                    │")
    print("│ 3. Search Specific Collection           │")
    print("│ 4. Bulk Download Entire Collection      │")
    print("│ 5. Advanced Search                      │")
    print("│ 6. Exit                                 │")
    print("└─────────────────────────────────────────┘")


def show_collections():
    """Show collections with numbered selection"""
    collections = {
        'Texts & Books': {
            '1': ('gutenberg', 'Project Gutenberg - Classic literature'),
            '2': ('opensource', 'Open source books and documents'),
            '3': ('americana', 'American historical documents'),
            '4': ('librivoxaudio', 'Audiobooks (spoken texts)'),
        },
        'Images & Art': {
            '5': ('georgeeastman', 'Historical photographs'),
            '6': ('biodiversity', 'Natural history illustrations'),
            '7': ('smithsonian', 'Smithsonian collections'),
        },
        'Audio & Music': {
            '8': ('audio', 'General audio collections'),
            '9': ('opensource_audio', 'CC-licensed audio'),
            '10': ('78rpm', 'Historical recordings'),
        },
        'Video & Film': {
            '11': ('prelinger', 'Historical film footage'),
            '12': ('opensource_movies', 'Open source films'),
        }
    }
    
    print("\n" + "─"*70)
    print("  POPULAR COLLECTIONS FOR GAME DEVELOPMENT")
    print("─"*70)
    
    for category, items in collections.items():
        print(f"\n📂 {category}:")
        for num, (coll_id, desc) in items.items():
            print(f"  [{num}] {desc}")
    
    print("\n  [0] Back to main menu")
    print("─"*70)
    
    return collections


def get_search_params():
    """Interactive search parameter collection"""
    print("\n┌─────────────────────────────────────────┐")
    print("│       SEARCH PARAMETERS                 │")
    print("└─────────────────────────────────────────┘\n")
    
    query = input("🔍 Search query (keywords): ").strip()
    if not query:
        print("❌ Query cannot be empty!")
        return None
    
    print("\n📁 Media type (optional):")
    print("  1. Texts/Books")
    print("  2. Images")
    print("  3. Audio")
    print("  4. Video/Movies")
    print("  5. Software/Games")
    print("  [Enter] Skip")
    
    mediatype_map = {
        '1': 'texts',
        '2': 'image',
        '3': 'audio',
        '4': 'movies',
        '5': 'software'
    }
    
    media_choice = input("\nChoice: ").strip()
    mediatype = mediatype_map.get(media_choice)
    
    max_results = input("\n📊 Maximum results (default 10): ").strip()
    max_results = int(max_results) if max_results.isdigit() else 10
    
    print("\n📄 File formats to download (optional):")
    print("  Examples: pdf, txt, epub, mp3, jpg, png")
    print("  [Enter for all formats]")
    formats_input = input("\nFormats (space-separated): ").strip()
    formats = formats_input.split() if formats_input else None
    
    return {
        'query': query,
        'mediatype': mediatype,
        'max_results': max_results,
        'formats': formats
    }


def display_results(results: List[Dict], show_full: bool = False):
    """Display search results in elegant format"""
    print("\n" + "="*70)
    print(f"  FOUND {len(results)} ITEMS - Starting download...")
    print("="*70)
    
    if show_full:
        for i, item in enumerate(results, 1):
            title = item.get('title', 'Unknown')[:55]
            item_id = item.get('identifier', 'unknown')
            mediatype = item.get('mediatype', 'unknown')
            
            print(f"\n┌─ [{i}] {title}")
            print(f"│  🆔 {item_id}")
            print(f"│  📁 {mediatype}")
            
            if 'description' in item:
                desc = item['description']
                if isinstance(desc, str):
                    desc = desc[:100] + "..." if len(desc) > 100 else desc
                    print(f"│  📝 {desc}")
            
            print("└" + "─"*68)
    else:
        # Just show titles in compact format
        for i, item in enumerate(results, 1):
            title = item.get('title', 'Unknown')[:60]
            print(f"  [{i}] {title}")
    
    print()


def confirm_collection_download(query: str, collection: str, count: int) -> bool:
    """Single confirmation for entire collection download"""
    print("\n" + "="*70)
    print(f"  📦 COLLECTION: {collection or 'General'}")
    print(f"  🔍 QUERY: {query}")
    print(f"  📊 ITEMS: {count}")
    print("="*70)
    
    while True:
        response = input("\n  Download this entire collection? (y/n): ").strip().lower()
        if response in ['y', 'yes']:
            return True
        elif response in ['n', 'no']:
            return False
        print("  Please enter 'y' or 'n'")


def interactive_mode():
    """Interactive menu-driven interface"""
    print_header()
    
    output_dir = input("📂 Output directory (default: archive_downloads): ").strip()
    output_dir = output_dir if output_dir else "archive_downloads"
    
    downloader = ArchiveOrgDownloader(output_dir)
    
    while True:
        print_menu()
        choice = input("\n➤ Select option (1-6): ").strip()
        
        if choice == '6':
            print("\n✨ Thank you for using Archive.org Downloader!\n")
            break
        
        elif choice == '1':
            # Browse collections
            collections_dict = show_collections()
            coll_choice = input("\n➤ Select collection (0-12): ").strip()
            
            if coll_choice == '0':
                continue
            
            # Find collection
            collection_id = None
            for category, items in collections_dict.items():
                if coll_choice in items:
                    collection_id = items[coll_choice][0]
                    break
            
            if not collection_id:
                print("❌ Invalid selection!")
                continue
            
            query = input("\n🔍 Search within collection (or Enter for all): ").strip()
            query = query if query else "*"
            
            max_results = input("📊 Maximum items (default 20): ").strip()
            max_results = int(max_results) if max_results.isdigit() else 20
            
            results = downloader.search_collection(query, collection=collection_id, max_results=max_results)
            
            if results:
                if confirm_collection_download(query, collection_id, len(results)):
                    display_results(results, show_full=False)
                    downloader.download_collection_items(query, collection=collection_id, max_items=len(results))
                    print("\n✅ Download complete!")
            else:
                print("\n❌ No results found!")
        
        elif choice == '2':
            # Simple search
            params = get_search_params()
            if not params:
                continue
            
            results = downloader.search_collection(
                params['query'],
                mediatype=params['mediatype'],
                max_results=params['max_results']
            )
            
            if results:
                if confirm_collection_download(params['query'], params.get('mediatype', 'All'), len(results)):
                    display_results(results, show_full=False)
                    downloader.download_collection_items(
                        params['query'],
                        mediatype=params['mediatype'],
                        max_items=len(results),
                        file_formats=params['formats']
                    )
                    print("\n✅ Download complete!")
            else:
                print("\n❌ No results found!")
        
        elif choice == '3':
            # Search specific collection
            collection = input("\n📁 Collection name (e.g., gutenberg): ").strip()
            params = get_search_params()
            if not params:
                continue
            
            results = downloader.search_collection(
                params['query'],
                collection=collection,
                mediatype=params['mediatype'],
                max_results=params['max_results']
            )
            
            if results:
                if confirm_collection_download(params['query'], collection, len(results)):
                    display_results(results, show_full=False)
                    downloader.download_collection_items(
                        params['query'],
                        collection=collection,
                        mediatype=params['mediatype'],
                        max_items=len(results),
                        file_formats=params['formats']
                    )
                    print("\n✅ Download complete!")
            else:
                print("\n❌ No results found!")
        
        elif choice == '4':
            # Bulk download entire collection
            print("\n" + "="*70)
            print("  📦 BULK COLLECTION DOWNLOAD")
            print("="*70)
            
            collections_dict = show_collections()
            coll_choice = input("\n➤ Select collection (0-12): ").strip()
            
            if coll_choice == '0':
                continue
            
            # Find collection
            collection_id = None
            for category, items in collections_dict.items():
                if coll_choice in items:
                    collection_id = items[coll_choice][0]
                    break
            
            if not collection_id:
                print("❌ Invalid selection!")
                continue
            
            max_items = input("\n📊 Maximum items to download (or 'all' for everything): ").strip()
            if max_items.lower() == 'all':
                max_items = 10000  # Large number
            else:
                max_items = int(max_items) if max_items.isdigit() else 1000
            
            print("\n📄 File formats (optional, press Enter for all):")
            print("  Examples: pdf txt epub mp3 jpg png")
            formats_input = input("Formats (space-separated): ").strip()
            formats = formats_input.split() if formats_input else None
            
            print(f"\n⚠️  This will download up to {max_items} items from '{collection_id}'")
            if input("Continue? (y/n): ").strip().lower() == 'y':
                print("\n🔍 Scanning collection...")
                results = downloader.search_collection('*', collection=collection_id, max_results=max_items)
                
                if results:
                    print(f"\n✅ Found {len(results)} items in collection")
                    if confirm_collection_download('*', collection_id, len(results)):
                        downloader.download_collection_items('*', collection=collection_id, 
                                                            max_items=len(results), file_formats=formats)
                        print("\n✅ Bulk download complete!")
                else:
                    print("\n❌ No items found in collection!")
        
        elif choice == '5':
            # Advanced search
            print("\n🔧 Advanced Search - Manual Query")
            query = input("Full query string: ").strip()
            if query:
                results = downloader.search_collection(query, max_results=50)
                if results:
                    if confirm_collection_download(query, 'Advanced', len(results)):
                        display_results(results, show_full=False)
                        downloader.download_collection_items(query, max_items=len(results))
                        print("\n✅ Download complete!")
        
        else:
            print("\n❌ Invalid option! Please select 1-5")
        
        input("\nPress Enter to continue...")


def main():
    parser = argparse.ArgumentParser(description="Download content from Archive.org")
    parser.add_argument("-q", "--query", help="Search query")
    parser.add_argument("-c", "--collection", help="Collection name")
    parser.add_argument("-t", "--mediatype", help="Media type: texts, audio, movies, software, image")
    parser.add_argument("-o", "--output", default="archive_downloads", help="Output directory")
    parser.add_argument("-m", "--max", type=int, default=10, help="Maximum items")
    parser.add_argument("-f", "--formats", nargs='+', help="File formats")
    parser.add_argument("--interactive", action='store_true', help="Interactive mode with menus")
    
    args = parser.parse_args()
    
    # Interactive mode if no query provided or --interactive flag
    if args.interactive or not args.query:
        interactive_mode()
        return
    
    # Command-line mode
    downloader = ArchiveOrgDownloader(args.output)
    
    results = downloader.search_collection(
        args.query,
        args.collection,
        args.mediatype,
        args.max
    )
    
    if not results:
        print("❌ No results found!")
        return
    
    downloader.save_search_results(results)
    display_results(results, show_full=True)
    
    if confirm_collection_download(args.query, args.collection or 'General', len(results)):
        print("\nStarting download...")
        downloader.download_collection_items(
            args.query,
            args.collection,
            args.mediatype,
            args.max,
            args.formats
        )
        print("\n✅ Download complete!")


if __name__ == "__main__":
    main()
