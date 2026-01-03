"""
Game Wiki Query Tool
Query processed Wikipedia data for game development
"""

import sqlite3
import json
import random
from pathlib import Path
from typing import List, Dict, Optional

class WikiGameQuery:
    def __init__(self, db_path: str):
        self.db_path = db_path
        self.conn = sqlite3.connect(db_path)
        self.conn.row_factory = sqlite3.Row
    
    def search_by_title(self, title: str) -> Optional[Dict]:
        """Search for an article by exact title"""
        cursor = self.conn.cursor()
        cursor.execute('SELECT * FROM articles WHERE title = ?', (title,))
        row = cursor.fetchone()
        
        if not row:
            return None
        
        return self._row_to_dict(row)
    
    def search_by_keyword(self, keyword: str, limit: int = 10) -> List[Dict]:
        """Search articles by keyword in title or summary"""
        cursor = self.conn.cursor()
        cursor.execute('''
            SELECT * FROM articles 
            WHERE title LIKE ? OR summary LIKE ?
            LIMIT ?
        ''', (f'%{keyword}%', f'%{keyword}%', limit))
        
        return [self._row_to_dict(row) for row in cursor.fetchall()]
    
    def get_by_category(self, category: str, limit: int = 50) -> List[Dict]:
        """Get all articles in a specific category"""
        cursor = self.conn.cursor()
        cursor.execute('''
            SELECT a.* FROM articles a
            JOIN categories c ON a.id = c.article_id
            WHERE c.category = ?
            LIMIT ?
        ''', (category, limit))
        
        return [self._row_to_dict(row) for row in cursor.fetchall()]
    
    def get_random_articles(self, count: int = 10, category: Optional[str] = None) -> List[Dict]:
        """Get random articles (great for procedural generation)"""
        cursor = self.conn.cursor()
        
        if category:
            cursor.execute('''
                SELECT a.* FROM articles a
                JOIN categories c ON a.id = c.article_id
                WHERE c.category = ?
                ORDER BY RANDOM()
                LIMIT ?
            ''', (category, count))
        else:
            cursor.execute('SELECT * FROM articles ORDER BY RANDOM() LIMIT ?', (count,))
        
        return [self._row_to_dict(row) for row in cursor.fetchall()]
    
    def list_categories(self) -> List[tuple]:
        """List all categories with article counts"""
        cursor = self.conn.cursor()
        cursor.execute('''
            SELECT category, COUNT(*) as count 
            FROM categories 
            GROUP BY category 
            ORDER BY count DESC
        ''')
        return [(row['category'], row['count']) for row in cursor.fetchall()]
    
    def get_stats(self) -> Dict:
        """Get database statistics"""
        cursor = self.conn.cursor()
        
        cursor.execute('SELECT COUNT(*) as count FROM articles')
        article_count = cursor.fetchone()['count']
        
        cursor.execute('SELECT COUNT(DISTINCT category) as count FROM categories')
        category_count = cursor.fetchone()['count']
        
        cursor.execute('SELECT AVG(length) as avg FROM articles')
        avg_length = cursor.fetchone()['avg']
        
        return {
            'total_articles': article_count,
            'total_categories': category_count,
            'avg_article_length': int(avg_length) if avg_length else 0
        }
    
    def _row_to_dict(self, row) -> Dict:
        """Convert database row to dictionary"""
        article = dict(row)
        if article.get('infobox'):
            article['infobox'] = json.loads(article['infobox'])
        return article
    
    def close(self):
        """Close database connection"""
        self.conn.close()


def demo_queries():
    """Demo showing how to use the query tool in a game"""
    import argparse
    
    parser = argparse.ArgumentParser(description="Query Wikipedia game database")
    parser.add_argument("database", help="Path to SQLite database")
    parser.add_argument("-s", "--search", help="Search by keyword")
    parser.add_argument("-c", "--category", help="Get articles by category")
    parser.add_argument("-r", "--random", type=int, help="Get random articles")
    parser.add_argument("--list-categories", action='store_true', help="List all categories")
    parser.add_argument("--stats", action='store_true', help="Show database statistics")
    
    args = parser.parse_args()
    
    query = WikiGameQuery(args.database)
    
    try:
        if args.stats:
            stats = query.get_stats()
            print("Database Statistics:")
            print(f"  Total Articles: {stats['total_articles']}")
            print(f"  Total Categories: {stats['total_categories']}")
            print(f"  Avg Article Length: {stats['avg_article_length']} chars")
        
        elif args.list_categories:
            categories = query.list_categories()
            print(f"\nTop Categories ({len(categories)} total):")
            for cat, count in categories[:20]:
                print(f"  {cat}: {count} articles")
        
        elif args.search:
            results = query.search_by_keyword(args.search)
            print(f"\nSearch results for '{args.search}':")
            for article in results:
                print(f"\n• {article['title']}")
                print(f"  {article['summary'][:150]}...")
        
        elif args.category:
            articles = query.get_by_category(args.category, limit=10)
            print(f"\nArticles in category '{args.category}':")
            for article in articles:
                print(f"  • {article['title']}")
        
        elif args.random:
            articles = query.get_random_articles(args.random)
            print(f"\n{args.random} Random Articles:")
            for article in articles:
                print(f"\n• {article['title']}")
                print(f"  {article['summary'][:150]}...")
        
        else:
            print("Use --help for usage information")
    
    finally:
        query.close()


if __name__ == "__main__":
    demo_queries()
