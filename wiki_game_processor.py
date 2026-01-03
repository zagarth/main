"""
Wikipedia Dump Processor for Game Development
Extracts and organizes Wikipedia content into game-friendly formats
"""

import os
import re
import json
import gzip
import xml.etree.ElementTree as ET
from pathlib import Path
from typing import Dict, List, Optional
import argparse

class WikiGameProcessor:
    def __init__(self, dump_dir: str, output_dir: str):
        self.dump_dir = Path(dump_dir)
        self.output_dir = Path(output_dir)
        self.output_dir.mkdir(parents=True, exist_ok=True)
        
        # MediaWiki XML namespaces
        self.ns = {
            'mw': 'http://www.mediawiki.org/xml/export-0.10/',
        }
    
    def find_xml_dumps(self) -> List[Path]:
        """Find all XML dump files in the directory"""
        xml_files = []
        for ext in ['*.xml', '*.xml.gz', '*.xml.bz2']:
            xml_files.extend(self.dump_dir.rglob(ext))
        return xml_files
    
    def clean_wikitext(self, text: str) -> str:
        """Remove MediaWiki markup and get clean text"""
        if not text:
            return ""
        
        # Remove references
        text = re.sub(r'<ref[^>]*>.*?</ref>', '', text, flags=re.DOTALL)
        text = re.sub(r'<ref[^>]*\/>', '', text)
        
        # Remove templates (simplified)
        text = re.sub(r'\{\{[^}]+\}\}', '', text)
        
        # Remove HTML comments
        text = re.sub(r'<!--.*?-->', '', text, flags=re.DOTALL)
        
        # Remove file/image links
        text = re.sub(r'\[\[File:.*?\]\]', '', text, flags=re.IGNORECASE)
        text = re.sub(r'\[\[Image:.*?\]\]', '', text, flags=re.IGNORECASE)
        
        # Convert wiki links to plain text
        text = re.sub(r'\[\[([^|\]]+)\|([^\]]+)\]\]', r'\2', text)  # [[Link|Text]] -> Text
        text = re.sub(r'\[\[([^\]]+)\]\]', r'\1', text)  # [[Link]] -> Link
        
        # Remove external links
        text = re.sub(r'\[http[^\]]+\]', '', text)
        
        # Remove formatting
        text = re.sub(r"'''([^']+)'''", r'\1', text)  # Bold
        text = re.sub(r"''([^']+)''", r'\1', text)    # Italic
        
        # Clean up whitespace
        text = re.sub(r'\n\n+', '\n\n', text)
        text = text.strip()
        
        return text
    
    def extract_infobox(self, text: str) -> Optional[Dict]:
        """Extract infobox data (useful for structured game data)"""
        infobox_match = re.search(r'\{\{Infobox(.*?)\n\}\}', text, re.DOTALL | re.IGNORECASE)
        if not infobox_match:
            return None
        
        infobox_text = infobox_match.group(1)
        data = {}
        
        # Parse key-value pairs
        for line in infobox_text.split('\n'):
            if '=' in line:
                key, value = line.split('=', 1)
                key = key.strip('| ').strip()
                value = self.clean_wikitext(value.strip())
                if key and value:
                    data[key] = value
        
        return data if data else None
    
    def extract_first_paragraph(self, text: str) -> str:
        """Extract first paragraph (good for tooltips/descriptions)"""
        cleaned = self.clean_wikitext(text)
        paragraphs = [p.strip() for p in cleaned.split('\n\n') if p.strip()]
        return paragraphs[0] if paragraphs else ""
    
    def extract_categories(self, text: str) -> List[str]:
        """Extract article categories"""
        categories = re.findall(r'\[\[Category:([^\]]+)\]\]', text, re.IGNORECASE)
        return [cat.split('|')[0].strip() for cat in categories]
    
    def process_article(self, title: str, text: str) -> Dict:
        """Process a single article into game-friendly format"""
        return {
            'title': title,
            'summary': self.extract_first_paragraph(text)[:500],  # Short for tooltips
            'full_text': self.clean_wikitext(text)[:5000],  # Truncate for performance
            'infobox': self.extract_infobox(text),
            'categories': self.extract_categories(text)[:10],  # Limit categories
            'length': len(text),
        }
    
    def parse_xml_dump(self, xml_file: Path, category_filter: Optional[List[str]] = None,
                       max_articles: Optional[int] = None) -> List[Dict]:
        """Parse XML dump and extract articles"""
        articles = []
        print(f"Processing: {xml_file.name}")
        
        # Handle compressed files
        if xml_file.suffix == '.gz':
            import gzip
            file_obj = gzip.open(xml_file, 'rb')
        else:
            file_obj = open(xml_file, 'rb')
        
        try:
            # Use iterparse for memory efficiency with large files
            context = ET.iterparse(file_obj, events=('start', 'end'))
            context = iter(context)
            
            current_page = {}
            in_page = False
            
            for event, elem in context:
                tag = elem.tag.replace('{http://www.mediawiki.org/xml/export-0.10/}', '')
                
                if event == 'start' and tag == 'page':
                    in_page = True
                    current_page = {}
                
                elif event == 'end' and in_page:
                    if tag == 'title':
                        current_page['title'] = elem.text
                    elif tag == 'text':
                        current_page['text'] = elem.text if elem.text else ""
                    elif tag == 'page':
                        in_page = False
                        
                        # Skip redirects and special pages
                        if current_page.get('title', '').startswith(('File:', 'Template:', 'Category:', 'Wikipedia:')):
                            current_page = {}
                            elem.clear()
                            continue
                        
                        if '#REDIRECT' in current_page.get('text', '')[:100]:
                            current_page = {}
                            elem.clear()
                            continue
                        
                        # Process article
                        if 'title' in current_page and 'text' in current_page:
                            article = self.process_article(current_page['title'], current_page['text'])
                            
                            # Apply category filter if specified
                            if category_filter:
                                if any(cat in article['categories'] for cat in category_filter):
                                    articles.append(article)
                            else:
                                articles.append(article)
                            
                            if len(articles) % 100 == 0:
                                print(f"  Extracted {len(articles)} articles...")
                            
                            if max_articles and len(articles) >= max_articles:
                                break
                        
                        current_page = {}
                        elem.clear()
        
        finally:
            file_obj.close()
        
        print(f"  Total extracted: {len(articles)} articles")
        return articles
    
    def save_as_json(self, articles: List[Dict], filename: str):
        """Save articles as JSON (good for game engines like Unity/Godot)"""
        output_path = self.output_dir / filename
        with open(output_path, 'w', encoding='utf-8') as f:
            json.dump(articles, f, indent=2, ensure_ascii=False)
        print(f"Saved: {output_path}")
    
    def save_as_sqlite(self, articles: List[Dict], db_name: str = "wiki_game.db"):
        """Save articles to SQLite (efficient querying in games)"""
        import sqlite3
        
        db_path = self.output_dir / db_name
        conn = sqlite3.connect(db_path)
        cursor = conn.cursor()
        
        # Create tables
        cursor.execute('''
            CREATE TABLE IF NOT EXISTS articles (
                id INTEGER PRIMARY KEY AUTOINCREMENT,
                title TEXT UNIQUE NOT NULL,
                summary TEXT,
                full_text TEXT,
                infobox TEXT,
                length INTEGER
            )
        ''')
        
        cursor.execute('''
            CREATE TABLE IF NOT EXISTS categories (
                article_id INTEGER,
                category TEXT,
                FOREIGN KEY (article_id) REFERENCES articles (id)
            )
        ''')
        
        cursor.execute('CREATE INDEX IF NOT EXISTS idx_title ON articles(title)')
        cursor.execute('CREATE INDEX IF NOT EXISTS idx_category ON categories(category)')
        
        # Insert articles
        for article in articles:
            cursor.execute('''
                INSERT OR REPLACE INTO articles (title, summary, full_text, infobox, length)
                VALUES (?, ?, ?, ?, ?)
            ''', (
                article['title'],
                article['summary'],
                article['full_text'],
                json.dumps(article['infobox']) if article['infobox'] else None,
                article['length']
            ))
            
            article_id = cursor.lastrowid
            
            # Insert categories
            for category in article['categories']:
                cursor.execute('''
                    INSERT INTO categories (article_id, category)
                    VALUES (?, ?)
                ''', (article_id, category))
        
        conn.commit()
        conn.close()
        print(f"Saved: {db_path}")
    
    def create_category_index(self, articles: List[Dict]) -> Dict[str, List[str]]:
        """Create a category index for quick lookups"""
        index = {}
        for article in articles:
            for category in article['categories']:
                if category not in index:
                    index[category] = []
                index[category].append(article['title'])
        return index


def main():
    parser = argparse.ArgumentParser(description="Process Wikipedia dumps for game development")
    parser.add_argument("dump_dir", help="Directory containing Wikipedia dump files")
    parser.add_argument("-o", "--output", default="game_wiki_data", help="Output directory")
    parser.add_argument("-f", "--format", choices=['json', 'sqlite', 'both'], default='both',
                        help="Output format")
    parser.add_argument("-c", "--categories", nargs='+', help="Filter by categories (e.g., 'Cities' 'History')")
    parser.add_argument("-m", "--max", type=int, help="Maximum articles to process")
    
    args = parser.parse_args()
    
    processor = WikiGameProcessor(args.dump_dir, args.output)
    
    # Find XML dumps
    xml_files = processor.find_xml_dumps()
    if not xml_files:
        print("No XML dump files found!")
        return
    
    print(f"Found {len(xml_files)} XML dump file(s)")
    
    # Process dumps
    all_articles = []
    for xml_file in xml_files:
        articles = processor.parse_xml_dump(xml_file, args.categories, args.max)
        all_articles.extend(articles)
        
        if args.max and len(all_articles) >= args.max:
            all_articles = all_articles[:args.max]
            break
    
    if not all_articles:
        print("No articles extracted!")
        return
    
    print(f"\nTotal articles processed: {len(all_articles)}")
    
    # Save in requested format(s)
    if args.format in ['json', 'both']:
        processor.save_as_json(all_articles, "wiki_articles.json")
    
    if args.format in ['sqlite', 'both']:
        processor.save_as_sqlite(all_articles)
    
    # Create category index
    category_index = processor.create_category_index(all_articles)
    print(f"\nFound {len(category_index)} unique categories")
    
    # Save category index
    with open(processor.output_dir / "category_index.json", 'w', encoding='utf-8') as f:
        json.dump(category_index, f, indent=2, ensure_ascii=False)
    
    print("\n✓ Processing complete!")


if __name__ == "__main__":
    main()
