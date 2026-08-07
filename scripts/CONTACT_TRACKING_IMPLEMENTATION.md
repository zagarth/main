# Contact Form Source Tracking - Implementation Guide

## Overview
Complete source tracking system to identify where users access the contact form, helping understand which pages and CTAs drive conversions.

## Implementation Date
October 9, 2025

## Components

### 1. Backend Tracking Script
**File:** `track_contact_source.php`
- Receives AJAX POST requests with tracking data
- Stores data in `$_SESSION['contact_source']`
- Logs all events to `/tmp/contact_tracking.log`
- Returns JSON success response

**Session Data Stored:**
- `page`: Source page name (e.g., "Homepage", "About Page")
- `section`: Specific CTA identifier (e.g., "Corporate Services Card")
- `url`: Full page URL where user clicked
- `timestamp`: ISO timestamp of click
- `ip_address`: Client IP
- `user_agent`: Browser user agent

### 2. JavaScript Functions
**File:** `contact_modal.php`

**openContactModal(prefilledMessage)**
- Opens contact modal
- Optionally prefills message textarea
- Prevents background scrolling

**openContactModalWithTracking(sourcePage, sourceSection, prefilledMessage)**
- Sends AJAX tracking request
- Logs console success/error messages
- Opens modal with optional prefilled message

### 3. Email Integration
**File:** `packemail.php`
- Reads `$_SESSION['contact_source']` when form submitted
- Adds tracking section to email with blue highlight
- Displays: Source Page, Source Section, Page URL (clickable), Timestamp
- Clears tracking data after email sent

## Tracked Locations

### Navigation Menu (All Pages)
- **Desktop Contact Link**: `Navigation Menu / Desktop Contact Link`
- **Mobile Contact Link**: `Navigation Menu / Mobile Contact Link`

### Homepage (index.php)
1. **Corporate Services Card**: `Homepage / Corporate Services Card`
   - Prefilled: "I am interested in Corporate Services..."
   
2. **Custom Engagement Card**: `Homepage / Custom Engagement Card`
   - Prefilled: "I am interested in Custom Engagement rings..."
   
3. **Special Request Card**: `Homepage / Special Request Card`
   - Prefilled: "I have a special request for custom jewelry..."
   
4. **Become a Retailer CTA**: `Homepage / Become a Retailer CTA`
   - Prefilled: "I am interested in becoming an authorized Cadman Manufacturing retailer..."

### About Page (about.php)
- **Get Started CTA**: `About Page / CTA - Ready to Create`

## Email Format Example

When a user clicks a tracked link and submits the form, the email includes:

```
New Contact Form Submission

Name:           John Doe
Email:          john@example.com
Message:        I am interested in Custom Engagement rings...
Date:           2025-10-09 14:32:15
IP Address:     192.168.1.100

━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━
Source Tracking Information (highlighted in blue)
━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━

Source Page:    Homepage
Source Section: Custom Engagement Card
Page URL:       https://cadman.ca/index.php
Clicked At:     2025-10-09T14:31:45.123Z
```

## Testing the System

### 1. Test Tracking Recording
```bash
# Click any tracked button in browser
# Then check the log:
tail -f /tmp/contact_tracking.log
```

Expected output:
```
[2025-10-09 14:31:45] Contact form accessed - Page: Homepage, Section: Custom Engagement Card, URL: https://cadman.ca/index.php, IP: 192.168.1.100
```

### 2. Test Email Integration
1. Click any tracked button/link
2. Fill out and submit contact form
3. Check email - should include tracking section

### 3. Verify Session Storage
Add to any page temporarily:
```php
<?php
session_start();
echo '<pre>';
print_r($_SESSION['contact_source']);
echo '</pre>';
?>
```

## Future Enhancement Opportunities

### Product/Item Detail Pages
Add tracking to individual product "Request Quote" buttons:
```javascript
openContactModalWithTracking(
    'Product Detail Page',
    'Item: <?php echo $itemName; ?> (<?php echo $itemCode; ?>)',
    'I am interested in <?php echo $itemName; ?>. Please provide more information.'
)
```

### Collection Pages
Track which collection drives interest:
```javascript
openContactModalWithTracking(
    'Celtic Collection',
    'Collection View Contact',
    'I am interested in items from the Celtic collection.'
)
```

### Gallery Image Clicks
Track specific images that drive engagement:
```javascript
openContactModalWithTracking(
    'Family Collection',
    'Image: <?php echo $imageAlt; ?>',
    'I saw this design and would like more information.'
)
```

## Analytics Value

### Business Intelligence Gained
1. **Top Converting Pages**: Which pages drive most contact form submissions
2. **CTA Effectiveness**: Which call-to-action buttons perform best
3. **User Journey**: What content interests users before they contact
4. **Product Interest**: Which specific items/collections generate inquiries
5. **Marketing ROI**: Track which campaigns/pages justify investment

### Example Insights
- "70% of retailer inquiries come from homepage map section"
- "Custom engagement card drives 45% of all homepage contacts"
- "About page CTA has 3x higher conversion than navigation link"
- "Mobile navigation contact gets more clicks but lower form completion"

## Security Considerations

### Data Collected
- Only collects: page name, section identifier, URL, timestamp, IP, user agent
- No sensitive personal data in tracking
- Stored temporarily in session only
- Cleared after email sent

### Privacy Compliance
- Tracking tied to legitimate business need (conversion optimization)
- No third-party data sharing
- Session-based (no persistent cookies for tracking)
- IP addresses already collected for security (rate limiting)

## Maintenance

### Log Management
```bash
# View tracking activity
tail -100 /tmp/contact_tracking.log

# Clear old logs (if needed)
> /tmp/contact_tracking.log

# Set up log rotation (optional)
sudo nano /etc/logrotate.d/cadman-tracking
```

### Session Cleanup
Sessions auto-clear on:
- Email successfully sent (manual unset in packemail.php)
- Session timeout (PHP default)
- Server restart

## Troubleshooting

### Tracking Not Recording
1. Check JavaScript console for AJAX errors
2. Verify track_contact_source.php is accessible
3. Check PHP session is started
4. Verify /tmp/ is writable

### Email Missing Tracking Data
1. Verify tracking was clicked before form opened
2. Check session hasn't expired (long delay between click and submit)
3. Verify packemail.php reads session correctly

### Log File Issues
```bash
# Check if file exists and permissions
ls -la /tmp/contact_tracking.log

# Make writable if needed
chmod 666 /tmp/contact_tracking.log
```

## Version History
- **v1.0** (2025-10-09): Initial implementation with navigation + homepage tracking
- Prefilled message support added
- Email integration with highlighted tracking section
