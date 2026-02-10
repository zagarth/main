# Game Development Data Pipeline

Tools for downloading and processing open knowledge resources for game development.

## 🎮 What This Does

Transform public domain content (Wikipedia, Archive.org) into game-ready data for:
- **World-building** - Historical facts, locations, cultures
- **NPC Dialogue** - Real knowledge snippets
- **In-Game Encyclopedia** - Searchable content database
- **Procedural Generation** - Names, descriptions, lore

## 🎨 ESP32 Image Tools

### Pin Mode Image Uploader
**File:** `pin_mode_image_uploader.py`  
**Location:** `E:\Scratch\Home_workspace\`

GUI tool for converting PNG images to RGB565 binary format for ESP32 displays.

**Features:**
- Drag & drop or file browser interface
- Real-time preview with zoom controls
- Automatic aspect ratio preservation
- Transparency handling (composite on black/white background)
- RGB565 format with big-endian byte order
- Direct serial upload to ESP32 device
- Support for 256x256 pixel images (128 KB output)

**Color Format for C Arrays:**
```python
# RGB565 format: 5 bits Red, 6 bits Green, 5 bits Blue
r5 = (r >> 3) & 0x1F  # 8-bit to 5-bit
g6 = (g >> 2) & 0x3F  # 8-bit to 6-bit
b5 = (b >> 3) & 0x1F  # 8-bit to 5-bit

# Pack as RGB565 (big-endian for C arrays)
rgb565 = (r5 << 11) | (g6 << 5) | b5
bytes = struct.pack('>H', rgb565)  # Big-endian 16-bit
```

```powershell
python pin_mode_image_uploader.py
```

### GIF Animation Converter (GUI)
**File:** `gif_converter_gui.py`  
**Location:** `E:\Scratch\Home_workspace\ESP32_IDF_Project\`

GUI tool for converting animated GIFs to sequential RGB565 binary frames for ESP32 animated displays.

**Features:**
- Browse and select GIF files
- Preview animation with frame-by-frame controls
- Play/pause animation preview
- Automatic frame extraction (all frames)
- Aspect ratio preservation and centering
- Transparency handling (composite on black background)
- RGB565 format (BGR565 byte order for ESP32)
- Batch conversion with progress tracking
- Creates info file with frame timing data
- Output: `prefix_000.bin`, `prefix_001.bin`, etc. (128 KB per frame)

**Color Format for Binary Files (ESP32 Display):**
```python
# RGB565 format: 5 bits Red, 6 bits Green, 5 bits Blue
r5 = (r >> 3) & 0x1F  # 8-bit to 5-bit
g6 = (g >> 2) & 0x3F  # 8-bit to 6-bit
b5 = (b >> 3) & 0x1F  # 8-bit to 5-bit

# Pack as BGR565 (little-endian for ESP32 display controller)
rgb565 = (b5 << 11) | (g6 << 5) | r5  # BGR order
bytes = struct.pack('<H', rgb565)  # Little-endian 16-bit
**Color Format:** Same as GUI version (BGR565 little-endian for ESP32)

```powershell
python ESP32_IDF_Project\convert_gif_to_bin.py animation.gif output_prefix
# Creates: output_prefix_000.bin, output_prefix_001.bin, etc.
```

---

**📝 Color Format Summary:**
| Tool | Format | Byte Order | Use Case |
|------|--------|------------|----------|
| pin_mode_image_uploader.py | RGB565 | Big-endian ('>H') | C arrays, static images |
| gif_converter_gui.py | BGR565 | Little-endian ('<H') | Binary files, ESP32 display |
| convert_gif_to_bin.py | BGR565 | Little-endian ('<H') | Binary files, ESP32 display |hon ESP32_IDF_Project\gif_converter_gui.py
```

### GIF Animation Converter (CLI)
**File:** `convert_gif_to_bin.py`  
**Location:** `E:\Scratch\Home_workspace\ESP32_IDF_Project\`

Command-line tool for converting GIF animations to RGB565 frames.

```powershell
python ESP32_IDF_Project\convert_gif_to_bin.py animation.gif output_prefix
# Creates: output_prefix_000.bin, output_prefix_001.bin, etc.
```

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
