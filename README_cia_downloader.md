# CIA Reading Room Downloader

Download declassified documents from CIA's public reading room with optional Tor routing.

## Features

- 🔒 **Optional Tor routing** for anonymity
- ⏱️ **Respectful rate limiting** (3+ second delays)
- 📄 **Downloads PDFs + HTML**
- ♻️ **Resumable** - skips existing files
- 📊 **Saves metadata** in JSON format

## Installation

```powershell
# Install dependencies
pip install requests beautifulsoup4 lxml

# For Tor support (optional)
pip install requests[socks]
```

## Tor Setup (Optional)

### Windows:
1. Download Tor Browser: https://www.torproject.org/download/
2. Run Tor Browser (keeps Tor service running on port 9050)
3. Use `--tor` flag

### Or install Tor service:
```powershell
choco install tor
tor
```

## Usage

### Basic Download (No Tor)
```powershell
# Download all documents (first 10 pages)
python cia_reading_room_downloader.py

# Search for specific topic
python cia_reading_room_downloader.py -q "UFO" -p 5 -m 50

# Download MKUltra documents
python cia_reading_room_downloader.py -q "MKUltra" -p 20
```

### With Tor Anonymity
```powershell
# Make sure Tor is running first!
python cia_reading_room_downloader.py -q "classified" --tor -p 10

# Slower, extra cautious
python cia_reading_room_downloader.py --tor -d 5.0 -m 200
```

## Command Line Options

```
-q, --query      Search query (empty for all documents)
-o, --output     Output directory (default: cia_documents)
-p, --pages      Maximum pages to download (default: 10)
-m, --max        Maximum documents to download (default: 100)
--tor            Use Tor for anonymity (requires Tor running)
-d, --delay      Delay between requests in seconds (default: 3.0)
```

## Output Structure

```
cia_documents/
├── Document_Title_1.pdf
├── Document_Title_1.html
├── Document_Title_2.pdf
├── Document_Title_2.html
└── documents_metadata.json
```

## Important Notes

⚠️ **Be Respectful:**
- Default 3-second delay between requests
- Government sites must be scraped carefully
- Don't hammer the server with rapid requests

⚠️ **Legal:**
- These are PUBLIC declassified documents
- Archiving for research/educational purposes
- Do not use for illegal activities

⚠️ **Tor:**
- Tor adds anonymity but is slower
- Ensure Tor is running before using `--tor`
- Test with: `curl --socks5-hostname 127.0.0.1:9050 https://check.torproject.org`

## Resuming Interrupted Downloads

If download is interrupted (Ctrl+C), simply run the same command again:
- Existing files are automatically skipped
- Downloads continue from where they left off

## Examples

### Download UFO documents
```powershell
python cia_reading_room_downloader.py -q "UFO" -p 10
```

### Download Cold War documents with Tor
```powershell
python cia_reading_room_downloader.py -q "Cold War" --tor -p 15 -m 150
```

### Download everything (careful!)
```powershell
python cia_reading_room_downloader.py -p 100 -m 1000 -d 5.0
```

## Troubleshooting

**"Tor connection failed":**
- Make sure Tor Browser or Tor service is running
- Check port 9050 is not blocked
- Try running without `--tor` flag first

**"Rate limited / Blocked":**
- Increase delay: `-d 5.0` or higher
- Use Tor: `--tor`
- Wait a few hours and try again

**"No documents found":**
- CIA's site structure may have changed
- Check https://www.cia.gov/readingroom/ manually
- May need to update CSS selectors in code

## Data Processing

After downloading, process with:
```powershell
python archive_content_processor.py cia_documents --categorize
```

This will organize documents by themes for your game!
