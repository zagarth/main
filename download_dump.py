import os
import argparse
import requests
from urllib.parse import urljoin, urlparse
from bs4 import BeautifulSoup

def download_file(url, output_path):
    os.makedirs(os.path.dirname(output_path), exist_ok=True)
    with requests.get(url, stream=True) as r:
        r.raise_for_status()
        total = int(r.headers.get("content-length", 0))
        downloaded = 0

        with open(output_path, "wb") as f:
            for chunk in r.iter_content(chunk_size=8192):
                if chunk:
                    f.write(chunk)
                    downloaded += len(chunk)
                    if total:
                        percent = downloaded / total * 100
                        print(f"\rDownloading {output_path}: {percent:.2f}%", end="")
    print("\nDone:", output_path)

def is_same_host(base_url, href):
    """
    Only follow links that stay on the same host/path tree as the base URL.
    This avoids external links like https://mirror.accum.se/about/index.html
    being treated as dump content.
    """
    joined = urljoin(base_url, href)
    base = urlparse(base_url)
    target = urlparse(joined)
    
    # Must be same scheme and host
    if (base.scheme, base.netloc) != (target.scheme, target.netloc):
        return False
    
    # Target path must start with base path to stay within the directory tree
    base_path = base.path.rstrip('/')
    target_path = target.path.rstrip('/')
    
    return target_path.startswith(base_path)

def download_directory(url, output_dir, subdir="", processed_files=None):
    if processed_files is None:
        processed_files = set()
    
    print(f"Scanning directory: {url}")
    r = requests.get(url)
    r.raise_for_status()

    soup = BeautifulSoup(r.text, "html.parser")
    links = soup.find_all("a")

    for link in links:
        href = link.get("href")
        if not href:
            continue

        # Skip parent directory links (any reference with ..)
        if ".." in href or href in ("./", ):
            continue

        # Skip query parameters (like ?C=N;O=D for sorting) and fragments
        if "?" in href or "#" in href:
            continue

        # Skip absolute paths that go outside our directory
        if href.startswith("/"):
            continue

        # Build a safe full URL
        full_url = urljoin(url, href)

        # Only stay within the same directory tree
        if not is_same_host(url, href):
            continue

        # If it looks like a directory (ends with '/'), recurse
        if href.endswith("/"):
            new_subdir = os.path.join(subdir, href.strip("/"))
            download_directory(full_url, output_dir, new_subdir, processed_files)
        else:
            # Filter files - only download actual dump files
            # Skip md5sums- files (metadata)
            if href.startswith("md5sums-"):
                continue
            
            # Only allow specific file extensions
            valid_extensions = ('.bz2', '.gz', '.xml', '.sql', '.7z', '.tar', '.json', '.html')
            
            # Special case: only allow md5sums.txt, skip all other .txt files
            if href.endswith('.txt'):
                if href != "md5sums.txt":
                    continue
            elif not href.endswith(valid_extensions):
                # Skip files that don't have valid dump extensions
                continue
            
            # Download the file
            local_path = os.path.join(output_dir, subdir, href)
            
            # Skip if already processed in this session
            if local_path in processed_files:
                continue
            
            # Mark as processed to avoid duplicates
            processed_files.add(local_path)
            
            if os.path.exists(local_path):
                print("Already exists, skipping:", local_path)
                continue
            print("Downloading file:", full_url)
            download_file(full_url, local_path)

def main():
    parser = argparse.ArgumentParser(description="Download Wikimedia dump directory recursively.")
    parser.add_argument("url", help="Base URL of the dump directory (e.g. https://dumps.wikimedia.org/enwiki/latest/)")
    parser.add_argument("output", nargs="?", default="downloads", help="Output directory (default: downloads)")
    parser.add_argument("subdir", nargs="?", default="", help="Initial subdirectory (optional)")
    args = parser.parse_args()

    download_directory(args.url, args.output, args.subdir)

if __name__ == "__main__":
    main()
