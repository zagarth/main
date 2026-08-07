# Ladys Stoneset Auto-Processing System

An automated content management system for the Ladys Stoneset jewelry collection that automatically generates thumbnails and detail pages when new images are added.

## Features

✅ **Automatic Image Detection** - Scans for new images in Gems and Pearls directories  
✅ **Thumbnail Generation** - Creates 240x240 thumbnails with proper aspect ratio  
✅ **Detail Page Creation** - Generates complete detail pages with pricing and descriptions  
✅ **Web Dashboard** - Real-time monitoring and control interface  
✅ **File Watcher** - Optional background monitoring for instant processing  
✅ **API Integration** - RESTful API for programmatic access  
✅ **Smart Pricing** - Automatic price calculation based on item patterns  

## Quick Start

1. **Run Setup**:
   ```bash
   ./ladys_stoneset_setup.sh
   ```

2. **Add New Images**:
   - Copy images to `ladys_stoneset_php/Gems/` or `ladys_stoneset_php/Pearls/`
   - Supported formats: JPG, PNG, GIF, WEBP

3. **Process Images**:
   ```bash
   php ladys_stoneset_auto_processor.php
   ```

4. **Monitor Progress**:
   - Open `ladys_stoneset_dashboard.html` in your browser

## File Structure

```
ladys_stoneset_php/
├── Gems/                          # Source images for gems
├── Pearls/                        # Source images for pearls
├── thumbs/                        # Auto-generated thumbnails
│   ├── Gems/
│   └── Pearls/
└── ladys_stoneset_php_*_detail.php # Auto-generated detail pages

ladys_stoneset_auto_processor.php   # Main processing engine
ladys_stoneset_dashboard.html       # Web management interface
ladys_stoneset_watcher.sh           # File monitoring script
ladys_stoneset_setup.sh             # Installation script
```

## Processing Workflow

1. **Image Detection**: System scans for new images
2. **Thumbnail Creation**: Generates 240x240 thumbnails
3. **Detail Page Generation**: Creates complete product pages
4. **Price Calculation**: Auto-assigns prices based on patterns:
   - Gems: $1,250-$1,750 (premium for 2000+ series, custom pieces)
   - Pearls: $950-$1,150 (premium for custom pieces)

## Web Interfaces

### Dashboard (`ladys_stoneset_dashboard.html`)
- Collection statistics and status
- Manual processing controls
- Real-time progress monitoring
- File upload interface
- Processing logs

### API Endpoints (`ladys_stoneset_auto_processor.php`)
- `?action=status` - Get collection status (JSON)
- `?action=process` - Process new images (JSON)
- Default - Process and show results (HTML)

## File Watcher (Optional)

For automatic processing when files are added:

1. **Install Dependencies**:
   ```bash
   sudo apt-get install inotify-tools
   ```

2. **Start Monitoring**:
   ```bash
   ./ladys_stoneset_watcher.sh
   ```

3. **Background Operation**:
   ```bash
   nohup ./ladys_stoneset_watcher.sh > watcher.log 2>&1 &
   ```

## Supported Image Naming

### Gems Collection
- Standard items: `1898.png`, `2002.png`, etc.
- Premium series: `2000+` numbers get higher pricing
- Custom pieces: `C71.jpg`, `C297.jpg` (custom pricing)
- Alternates: `1898_alt1.png`, `1898_alt2.png`

### Pearls Collection  
- Standard items: `1902C.png`
- Custom pieces: `C223C_chocolatepearl_72dpi.jpg`
- Alternates: `item_alt1.ext`, `item_alt2.ext`

## Automatic Features

### Dynamic Collection Display
- Main collection page automatically detects new items
- No code changes needed for new products
- Proper category filtering and display

### Smart Detail Pages
- Auto-generated product descriptions
- Category-specific features and pricing
- Image gallery with alternate views
- SEO-optimized titles and meta tags

### Thumbnail Management
- Automatic generation with proper aspect ratios
- Fallback to full images if thumbnails missing
- White background for consistency

## Troubleshooting

### Common Issues

**No thumbnails generated:**
- Check PHP GD extension: `php -m | grep gd`
- Verify directory permissions: `chmod 755 ladys_stoneset_php/thumbs/`

**Detail pages not created:**
- Check file permissions in ladys_stoneset_php directory
- Verify PHP can write files

**File watcher not working:**
- Install inotify-tools: `sudo apt-get install inotify-tools`
- Check script permissions: `chmod +x ladys_stoneset_watcher.sh`

**API not responding:**
- Verify web server is running
- Check PHP error logs
- Ensure proper file paths

### Logs and Debugging

- Dashboard processing log shows real-time status
- File watcher logs to `ladys_stoneset_watcher.log`
- PHP errors logged to standard error log

## Integration

### With Existing Collection
The system integrates seamlessly with the existing `Ladys_Stoneset.php` collection page:
- Uses same pricing functions
- Follows same detail page format
- Maintains consistent styling
- Preserves all existing functionality

### With Other Collections
The system can be adapted for other collections by:
- Copying the auto-processor pattern
- Updating directory paths and category names
- Adjusting pricing calculations
- Modifying detail page templates

## Security Notes

- File upload validation by extension
- Directory permissions set to 755
- No executable file uploads allowed
- Web-accessible files properly sanitized

## Performance

- Efficient image scanning with minimal memory usage
- Thumbnail generation with optimized quality settings
- Lazy loading of images in web interface
- Minimal impact on main collection page performance
