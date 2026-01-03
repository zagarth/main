"""
Archive.org Content Processor for Game Development
Converts downloaded Archive.org content into game-ready formats
"""

import os
import json
import re
from pathlib import Path
from typing import Dict, List, Optional
import argparse

try:
    import PyPDF2
    HAS_PDF = True
except ImportError:
    HAS_PDF = False

try:
    import ebooklib
    from ebooklib import epub
    HAS_EPUB = True
except ImportError:
    HAS_EPUB = False


class TextProcessor:
    """Process text files from Archive.org"""
    
    def __init__(self, output_dir: str = "processed_data"):
        self.output_dir = Path(output_dir)
        self.output_dir.mkdir(parents=True, exist_ok=True)
    
    def process_txt_file(self, file_path: Path) -> Dict:
        """Extract content from plain text file"""
        with open(file_path, 'r', encoding='utf-8', errors='ignore') as f:
            content = f.read()
        
        # Split into paragraphs
        paragraphs = [p.strip() for p in content.split('\n\n') if p.strip()]
        
        return {
            'title': file_path.stem,
            'source': str(file_path),
            'content': content,
            'paragraphs': paragraphs[:100],  # Limit for performance
            'word_count': len(content.split()),
            'char_count': len(content)
        }
    
    def process_pdf_file(self, file_path: Path) -> Optional[Dict]:
        """Extract text from PDF"""
        if not HAS_PDF:
            print(f"PyPDF2 not installed, skipping {file_path.name}")
            return None
        
        try:
            with open(file_path, 'rb') as f:
                pdf = PyPDF2.PdfReader(f)
                
                text = ""
                for page in pdf.pages:
                    text += page.extract_text() + "\n\n"
                
                return {
                    'title': file_path.stem,
                    'source': str(file_path),
                    'content': text,
                    'page_count': len(pdf.pages),
                    'word_count': len(text.split())
                }
        except Exception as e:
            print(f"Error processing PDF {file_path.name}: {e}")
            return None
    
    def process_epub_file(self, file_path: Path) -> Optional[Dict]:
        """Extract text from EPUB"""
        if not HAS_EPUB:
            print(f"ebooklib not installed, skipping {file_path.name}")
            return None
        
        try:
            book = epub.read_epub(file_path)
            
            title = book.get_metadata('DC', 'title')
            title = title[0][0] if title else file_path.stem
            
            text = ""
            for item in book.get_items():
                if item.get_type() == ebooklib.ITEM_DOCUMENT:
                    content = item.get_content().decode('utf-8', errors='ignore')
                    # Strip HTML tags
                    content = re.sub(r'<[^>]+>', '', content)
                    text += content + "\n\n"
            
            return {
                'title': title,
                'source': str(file_path),
                'content': text,
                'word_count': len(text.split())
            }
        except Exception as e:
            print(f"Error processing EPUB {file_path.name}: {e}")
            return None
    
    def create_game_text_database(self, input_dir: Path, output_file: str = "game_texts.json"):
        """Process all text files and create game-ready database"""
        texts = []
        
        for file_path in input_dir.rglob('*'):
            if not file_path.is_file():
                continue
            
            print(f"Processing: {file_path.name}")
            
            if file_path.suffix == '.txt':
                data = self.process_txt_file(file_path)
            elif file_path.suffix == '.pdf':
                data = self.process_pdf_file(file_path)
            elif file_path.suffix == '.epub':
                data = self.process_epub_file(file_path)
            else:
                continue
            
            if data:
                # Extract useful snippets for game content
                data['snippets'] = self.extract_snippets(data['content'])
                texts.append(data)
        
        # Save
        output_path = self.output_dir / output_file
        with open(output_path, 'w', encoding='utf-8') as f:
            json.dump(texts, f, indent=2, ensure_ascii=False)
        
        print(f"\n✓ Processed {len(texts)} documents")
        print(f"✓ Saved to: {output_path}")
        
        return texts
    
    def extract_snippets(self, text: str, max_snippets: int = 50, 
                        snippet_length: int = 200) -> List[str]:
        """Extract interesting snippets for game dialogue/lore"""
        # Split into sentences
        sentences = re.split(r'[.!?]+', text)
        
        snippets = []
        current_snippet = ""
        
        for sentence in sentences:
            sentence = sentence.strip()
            if not sentence:
                continue
            
            if len(current_snippet) + len(sentence) < snippet_length:
                current_snippet += sentence + ". "
            else:
                if current_snippet:
                    snippets.append(current_snippet.strip())
                current_snippet = sentence + ". "
            
            if len(snippets) >= max_snippets:
                break
        
        return snippets


class MetadataOrganizer:
    """Organize Archive.org content by themes/topics for game development"""
    
    def __init__(self, output_dir: str = "processed_data"):
        self.output_dir = Path(output_dir)
        self.output_dir.mkdir(parents=True, exist_ok=True)
    
    def categorize_by_keywords(self, documents: List[Dict], 
                               categories: Dict[str, List[str]]) -> Dict[str, List[Dict]]:
        """
        Categorize documents by keyword matching
        
        categories example:
        {
            'fantasy': ['dragon', 'magic', 'wizard', 'medieval'],
            'science': ['physics', 'chemistry', 'biology'],
            'history': ['war', 'battle', 'empire', 'ancient']
        }
        """
        categorized = {cat: [] for cat in categories}
        categorized['uncategorized'] = []
        
        for doc in documents:
            content = doc.get('content', '').lower()
            title = doc.get('title', '').lower()
            
            matched = False
            for category, keywords in categories.items():
                if any(keyword.lower() in content or keyword.lower() in title 
                      for keyword in keywords):
                    categorized[category].append(doc)
                    matched = True
                    break
            
            if not matched:
                categorized['uncategorized'].append(doc)
        
        # Save categorized data
        for category, docs in categorized.items():
            if docs:
                output_file = self.output_dir / f"category_{category}.json"
                with open(output_file, 'w', encoding='utf-8') as f:
                    json.dump(docs, f, indent=2, ensure_ascii=False)
                print(f"  {category}: {len(docs)} documents")
        
        return categorized


def main():
    parser = argparse.ArgumentParser(description="Process Archive.org content for games")
    parser.add_argument("input_dir", help="Directory containing downloaded Archive.org content")
    parser.add_argument("-o", "--output", default="processed_data", help="Output directory")
    parser.add_argument("--categorize", action='store_true', help="Categorize by game themes")
    
    args = parser.parse_args()
    
    input_dir = Path(args.input_dir)
    if not input_dir.exists():
        print(f"Error: {input_dir} does not exist!")
        return
    
    # Process texts
    processor = TextProcessor(args.output)
    documents = processor.create_game_text_database(input_dir)
    
    # Categorize if requested
    if args.categorize:
        print("\nCategorizing by game themes...")
        
        # Default game-relevant categories
        categories = {
            'fantasy': ['dragon', 'magic', 'wizard', 'medieval', 'knight', 'castle', 'sword'],
            'scifi': ['space', 'robot', 'alien', 'future', 'technology', 'cybernetic'],
            'history': ['war', 'battle', 'empire', 'ancient', 'civilization', 'revolution'],
            'mythology': ['god', 'myth', 'legend', 'hero', 'monster', 'deity'],
            'nature': ['animal', 'plant', 'forest', 'ocean', 'mountain', 'wildlife'],
            'science': ['physics', 'chemistry', 'biology', 'experiment', 'theory'],
        }
        
        organizer = MetadataOrganizer(args.output)
        organizer.categorize_by_keywords(documents, categories)
    
    print(f"\n✓ Processing complete!")
    print(f"✓ Output directory: {args.output}")
    
    # Print installation instructions if libraries missing
    if not HAS_PDF:
        print("\n⚠ Install PyPDF2 for PDF support: pip install PyPDF2")
    if not HAS_EPUB:
        print("⚠ Install ebooklib for EPUB support: pip install ebooklib")


if __name__ == "__main__":
    main()
