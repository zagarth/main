# Game Development Data Pipeline

Tools for downloading and processing open knowledge resources for game development.

## 🎮 What This Does

Transform public domain content (Wikipedia, Archive.org) into game-ready data for:
- **World-building** - Historical facts, locations, cultures
- **NPC Dialogue** - Real knowledge snippets
- **In-Game Encyclopedia** - Searchable content database
- **Procedural Generation** - Names, descriptions, lore

## 📦 Tools

### 1. Wikipedia Dump Processor
**Files:** `wiki_game_processor.py`, `game_wiki_query.py`

Download and process Wikipedia database dumps into game-friendly formats.

```powershell
# Process Wikipedia dumps
python wiki_game_processor.py "path/to/dumps" -m 10000

# Query the database
python game_wiki_query.py wiki_game.db --stats
python game_wiki_query.py wiki_game.db -s "castle"
python game_wiki_query.py wiki_game.db -r 10
```

### 2. Archive.org Content Downloader
**Files:** `archive_org_downloader.py`, `archive_content_processor.py`

Download and process historical texts, images, audio from Internet Archive.

```powershell
# Interactive mode (recommended)
python archive_org_downloader.py --interactive

# Command-line mode
python archive_org_downloader.py -q "mythology" -t texts -m 50

# Process downloaded content
python archive_content_processor.py archive_downloads --categorize
```

### 3. Wikipedia Dump Downloader
**File:** `download_dump.py` (external)

Recursively downloads entire Wikipedia dump directories.

```powershell
python download_dump.py "https://dumps.wikimedia.org/enwiki/latest/" output_dir
```

## 🚀 Quick Start

### 1. Install Dependencies
```powershell
pip install requests beautifulsoup4 lxml
pip install PyPDF2 ebooklib  # Optional: for PDF/EPUB processing
```

### 2. Download Data
```powershell
# Option A: Interactive menu-driven
python archive_org_downloader.py

# Option B: Command-line
python archive_org_downloader.py -q "medieval history" -t texts -m 20
```

### 3. Process Data
```powershell
# Process texts into game-ready format
python archive_content_processor.py archive_downloads --categorize
```

### 4. Use in Your Game
```python
from game_wiki_query import WikiGameQuery

query = WikiGameQuery("wiki_game.db")
article = query.search_by_title("Dragon")
npc_dialogue = article['summary']
```

## 📊 Output Formats

- **JSON** - Easy integration with Unity, Godot, Unreal
- **SQLite** - Fast runtime queries, efficient storage
- **Categorized** - Auto-organized by game themes

## 🎯 Game Use Cases

| Use Case | Tool | Example |
|----------|------|---------|
| In-game codex/encyclopedia | wiki_game_processor.py | Tooltip hover info |
| NPC knowledge/dialogue | archive_content_processor.py | Random historical facts |
| Location generation | Both | Procedural city names + descriptions |
| Quest text | archive_content_processor.py | Historical event inspiration |
| Educational content | Both | Quiz questions, learning games |

## 🗂️ Project Structure

```
Home_workspace/
├── archive_org_downloader.py      # Download from Archive.org
├── archive_content_processor.py   # Process downloaded content
├── wiki_game_processor.py         # Process Wikipedia dumps
├── game_wiki_query.py            # Query Wikipedia database
├── download_dump.py              # Download Wikipedia dumps
└── README.md                     # This file
```

## 📝 License

Tools are open source. Downloaded content follows respective licenses:
- Wikipedia: CC BY-SA 3.0
- Archive.org: Varies by item (check individual licenses)

## 🔧 Requirements

- Python 3.7+
- `requests`, `beautifulsoup4`, `lxml`
- Optional: `PyPDF2`, `ebooklib` (for advanced processing)

## 🤝 Contributing

This is a personal game dev toolkit. Fork and adapt for your needs!
