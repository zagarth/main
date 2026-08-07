# Universal Collections Auto-Processor

A comprehensive automated content management system for jew### Management Commands

All management commands are now located in the `/admin` directory:

```bash
cd admin

# Service Management
sudo ./manage_watcher.sh install    # Install the service
sudo ./manage_watcher.sh start      # Start monitoring
sudo ./manage_watcher.sh stop       # Stop monitoring
sudo ./manage_watcher.sh restart    # Restart service
sudo ./manage_watcher.sh status     # Check status
sudo ./manage_watcher.sh logs       # View live logs
sudo ./manage_watcher.sh uninstall  # Remove service
```

### Access Control

The admin system is now secured with login authentication:

- **Login URL**: `http://localhost/homesite/admin/login.php`
- **Default Username**: `cadman_admin`
- **Default Password**: `CadmanMfg2025!Admin`

⚠️ **IMPORTANT**: Change the default password in `/admin/auth.php` that automatically processes uploaded images, generates thumbnails, creates detail pages, and manages pricing across all collections.

## 🎯 Features

- **Automated Image Processing**: Automatically detects new images and processes them
- **Thumbnail Generation**: Creates optimized 240x240 thumbnails with proper scaling
- **Detail Page Creation**: Generates individual product pages with pricing and descriptions
- **Multi-Collection Support**: Handles 8 different jewelry collections simultaneously
- **Real-Time Monitoring**: File watcher service monitors directories for new uploads
- **Web Dashboard**: Beautiful interface for monitoring and controlling all collections
- **RESTful API**: Programmatic access for integration with other systems
- **Comprehensive Logging**: Detailed logs of all processing activities

## 📁 Supported Collections

1. **Accessories** (Chains, Pendants, Earrings)
2. **Bands** (Wedding & Fashion Bands)
3. **Corporate** (Corporate Identity Items)
4. **Engagement** (Engagement Rings)
5. **Family** (Mother & Daughters, Dad, Family Sets)
6. **Ladies Stoneset** (Ladies Rings, Stone Settings)
7. **School** (School Rings, Charisma, Silhouette)
8. **Signet** (Signet Rings)

## 🚀 Quick Start

### 1. Installation
```bash
cd admin
sudo ./setup_universal_processor.sh
```

### 2. Start the System
```bash
cd admin
sudo ./manage_watcher.sh start
```

### 3. Access the Admin Portal
Open your browser to: `http://localhost/homesite/admin/login.php`

Default credentials:
- Username: `cadman_admin`
- Password: `CadmanMfg2025!Admin` (change this!)

### 4. Access the Dashboard
After logging in, access: `http://localhost/homesite/admin/dashboard.php`

### 4. Add Images
Simply drop image files into any collection's `images` folder:
- `/var/www/html/homesite/engagement/images/`
- `/var/www/html/homesite/bands/images/`
- `/var/www/html/homesite/accessories/images/`
- etc.

The system will automatically:
- Create thumbnails
- Generate detail pages
- Update collection displays
- Log all activities

## 📂 Directory Structure

```
/var/www/html/homesite/
├── admin/                                   # 🔒 SECURED ADMIN AREA
│   ├── auth.php                            # Authentication system
│   ├── login.php                           # Admin login page
│   ├── index.php                           # Admin portal home
│   ├── dashboard.php                       # Collections management dashboard
│   ├── universal_collection_processor.php  # Main processor engine
│   ├── universal_collections_watcher.sh    # File monitoring service
│   ├── manage_watcher.sh                   # Service management
│   ├── setup_universal_processor.sh        # Installation script
│   ├── view_logs.php                       # Log viewer
│   ├── log_reader.php                      # Log backend
│   └── collections-watcher.service         # Service definition
│
├── accessories/
│   ├── images/                             # Source images
│   └── thumbs/                             # Generated thumbnails
├── bands/
│   ├── images/
│   └── thumbs/
├── engagement/
│   ├── images/
│   └── thumbs/
│
└── _php/
    ├── accessories/                        # Generated detail pages
    ├── bands/
    ├── engagement/
    └── [other collections]/
```

## 🔧 Management Commands

### Service Management
```bash
sudo ./manage_watcher.sh install    # Install the service
sudo ./manage_watcher.sh start      # Start monitoring
sudo ./manage_watcher.sh stop       # Stop monitoring
sudo ./manage_watcher.sh restart    # Restart service
sudo ./manage_watcher.sh status     # Check status
sudo ./manage_watcher.sh logs       # View live logs
sudo ./manage_watcher.sh uninstall  # Remove service
```

### Testing
```bash
./test_processor.sh                 # Test system functionality
./quick_start.sh                    # Show quick start guide
```

## 🌐 Web Interfaces

### Admin Portal
**Login URL**: `http://localhost/homesite/admin/login.php`
- Secure authentication system
- Session management with timeout
- Activity logging

### Dashboard
**URL**: `http://localhost/homesite/admin/dashboard.php` (requires login)

Features:
- Real-time collection status
- Processing controls for individual or all collections
- Live activity logs
- Collection filtering and statistics
- Progress tracking with visual indicators

### API Endpoints
**Base URL**: `http://localhost/homesite/admin/universal_collection_processor.php` (requires authentication)

#### Get Collection Status
```
GET ?action=status
```
Returns detailed status for all collections including item counts, missing thumbnails, and missing detail pages.

#### Get Available Collections
```
GET ?action=collections
```
Returns list of all supported collections.

#### Process Collections
```
POST ?action=process[&collection=COLLECTION_NAME]
```
Processes all collections or a specific collection. Returns processing results.

#### Process Single Image
```
POST ?action=process_single&collection=COLLECTION&image_path=PATH
```
Processes a single image file for the specified collection.

## 🎨 Collection-Specific Features

### Pricing Algorithms
Each collection has customized pricing based on:
- **Accessories**: $45-85 (Chains), $35-65 (Pendants), $55-95 (Earrings)
- **Bands**: $180-420 based on complexity
- **Engagement**: $350-850 based on setting style
- **Family**: $120-380 for family sets
- **Ladies Stoneset**: $220-520 based on stone count
- **School**: $150-350 based on customization
- **Corporate**: $80-180 for corporate items
- **Signet**: $190-450 based on design complexity

### Category Mapping
Collections are automatically categorized based on filename patterns:
- `*chain*` → Chains
- `*pendant*` → Pendants
- `*earring*` → Earrings
- `*band*` → Bands
- `*ring*` → Rings
- etc.

## 📊 Monitoring & Logging

### Log Files
- **Service Log**: `/var/log/collections_watcher.log`
- **Web Dashboard**: Real-time log display
- **API Responses**: JSON formatted status and error messages

### Status Indicators
- 🟢 **Ready**: All items have thumbnails and detail pages
- 🟡 **Partial**: Some items are missing components
- 🔴 **Needs Work**: Many items need processing
- 🔵 **Processing**: Currently being processed

## 🛠️ Technical Requirements

### System Requirements
- **OS**: Linux (Ubuntu/Debian recommended)
- **Web Server**: Apache2 with PHP support
- **PHP Extensions**: GD library for image processing
- **Tools**: inotify-tools for file monitoring
- **Permissions**: www-data user access to collection directories

### Supported Image Formats
- JPEG (.jpg, .jpeg)
- PNG (.png)
- GIF (.gif)
- BMP (.bmp)
- WebP (.webp)

## 🔒 Security Features

### File Validation
- Only processes valid image files
- Checks file size and format
- Prevents processing of system files
- Validates file paths and extensions

### Service Security
- Runs as www-data user (not root)
- Protected system directories
- Private temporary files
- Read-only access to system files

## 🐛 Troubleshooting

### Common Issues

#### Service Won't Start
```bash
# Check service status
sudo ./manage_watcher.sh status

# Check logs
sudo ./manage_watcher.sh logs

# Restart service
sudo ./manage_watcher.sh restart
```

#### Images Not Processing
1. Check file permissions on image directories
2. Verify inotify-tools is installed
3. Check if file watcher service is running
4. Review logs for error messages

#### Dashboard Not Loading
1. Verify Apache is running
2. Check file permissions on dashboard file
3. Test API endpoint directly
4. Review browser console for errors

#### Missing Thumbnails
```bash
# Test thumbnail generation
./test_processor.sh

# Process all collections manually
curl "http://localhost/homesite/universal_collection_processor.php?action=process"
```

### Log Analysis
```bash
# View recent activity
tail -f /var/log/collections_watcher.log

# Search for errors
grep "ERROR" /var/log/collections_watcher.log

# Count processed files
grep "Successfully processed" /var/log/collections_watcher.log | wc -l
```

## 📈 Performance Optimization

### Batch Processing
- Process multiple files in sequence
- Optimize image operations
- Cache collection configurations
- Minimize database queries

### Resource Management
- Automatic memory cleanup
- Efficient file I/O
- Optimized thumbnail generation
- Background processing for large batches

## 🔄 Maintenance

### Regular Tasks
1. **Log Rotation**: Logs will grow over time, consider rotating them
2. **Backup**: Regular backups of collection directories
3. **Updates**: Keep system packages updated
4. **Monitoring**: Regular checks of service status

### Backup Strategy
```bash
# Backup all collections
tar -czf collections_backup_$(date +%Y%m%d).tar.gz \
  /var/www/html/homesite/*/images/ \
  /var/www/html/homesite/_php/

# Backup configuration
cp /var/www/html/homesite/universal_collection_processor.php \
   ~/backup_processor_$(date +%Y%m%d).php
```

## 🤝 Contributing

### Adding New Collections
1. Add collection to the `$collections` array in `universal_collection_processor.php`
2. Define category mappings and pricing rules
3. Create directory structure
4. Update dashboard if needed
5. Test thoroughly

### Customizing Pricing
Edit the `calculatePrice()` method in the `UniversalCollectionProcessor` class to adjust pricing algorithms.

### Extending Functionality
The system is designed to be modular. New features can be added by:
1. Extending the processor class
2. Adding new API endpoints
3. Updating the dashboard interface
4. Adding new file watcher events

## 📞 Support

For issues or questions:
1. Check the troubleshooting section
2. Review log files for errors
3. Test individual components
4. Verify system requirements

## 📝 License

This software is provided as-is for jewelry collection management. Modify and distribute according to your needs.

---

**Created**: $(date)
**Version**: 1.0.0
**System**: Universal Collections Auto-Processor
