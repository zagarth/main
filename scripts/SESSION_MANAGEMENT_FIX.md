# Session Management Fix - Contact Form

## Problem Identified
- Contact form was showing "Security validation failed" error when opened from navigation menu
- Session values were being lost or reset when navigating between pages
- CSRF token wasn't being generated properly due to session not being initialized

## Root Causes
1. **Session not started in navigation**: The navigation.php didn't start a session before rendering the contact modal
2. **Inconsistent session handling**: Different files had different session initialization code
3. **Browser caching**: Pages were being cached, causing stale session data
4. **Session regeneration conflicts**: Multiple places trying to regenerate session IDs

## Solutions Implemented

### 1. Centralized Session Manager (`session_manager.php`)
Created a unified session management system with:

**Security Features:**
- ✅ HttpOnly cookies (prevents XSS attacks)
- ✅ SameSite=Lax (CSRF protection)
- ✅ Strict mode (prevents session fixation)
- ✅ No URL-based sessions (security)
- ✅ Session ID regeneration every 30 minutes
- ✅ Absolute 2-hour session timeout
- ✅ IP change logging (hijacking detection)

**Stability Features:**
- ✅ Prevents duplicate session starts
- ✅ Consistent configuration across all files
- ✅ Cache control headers to prevent stale data
- ✅ Helper functions for safe session access

**Configuration:**
```php
Session timeout: 2 hours (7200 seconds)
ID regeneration: Every 30 minutes (1800 seconds)
Cookie lifetime: Browser session (expires on close)
Cache control: No-cache, no-store
```

### 2. Updated Files

**contact_modal.php:**
- Now includes `session_manager.php`
- Removes duplicate session initialization code
- Session properly started before CSRF token generation

**packemail.php:**
- Now includes `session_manager.php`
- Removed duplicate session configuration
- Consistent with modal's session handling

**track_contact_source.php:**
- Already had proper session configuration
- No changes needed

### 3. Helper Functions Added

```php
getSession($key, $default)     // Safely get session value
setSession($key, $value)       // Safely set session value  
hasSession($key)               // Check if session key exists
unsetSession($key)             // Remove session variable
debugSession()                 // Debug output (when MAIL_DEBUG=true)
```

## Testing Checklist

### Basic Functionality
- [ ] Open navigation menu → Click Contact → Form opens
- [ ] Verify no "Security validation failed" error
- [ ] Fill form and submit → Email sent successfully
- [ ] Check email includes tracking data

### Session Persistence
- [ ] Click Contact from nav menu
- [ ] Navigate to different page
- [ ] Click Contact again → Same session
- [ ] Verify CSRF token doesn't change unnecessarily
- [ ] Submit form → Works correctly

### Security
- [ ] Open Contact, wait 31 minutes → Session ID regenerates
- [ ] Open Contact, wait 2+ hours → New session created
- [ ] Inspect cookies → HttpOnly and SameSite flags present
- [ ] Check browser cache → Pages not cached

### Cross-Page Navigation
- [ ] Homepage → Click Contact → Works
- [ ] About page → Click Contact → Works  
- [ ] Collection page → Click Contact → Works
- [ ] Submit from each → All work

## Benefits

### For Users
- 🎯 Contact form works reliably from any page
- 🎯 No confusing security errors
- 🎯 Form data persists while filling it out
- 🎯 Smooth navigation between pages

### For Security
- 🔒 Protection against XSS attacks
- 🔒 Protection against CSRF attacks
- 🔒 Protection against session fixation
- 🔒 Protection against session hijacking
- 🔒 Automatic session cleanup

### For Development
- 📝 Single source of truth for session config
- 📝 Easy to debug with helper functions
- 📝 Consistent behavior across all pages
- 📝 Easy to maintain and update

## Migration Path for Other Pages

To add proper session management to other pages:

```php
<?php
// At the top of any PHP file that needs session
require_once __DIR__ . '/session_manager.php';

// Session is now available - use helper functions
$userName = getSession('user_name', 'Guest');
setSession('last_page', 'About');

// Or use $_SESSION directly (it's already started)
$_SESSION['custom_data'] = 'value';
?>
```

## Files Modified
1. ✅ `/var/www/html/homesite/session_manager.php` - NEW
2. ✅ `/var/www/html/homesite/contact_modal.php` - UPDATED
3. ✅ `/var/www/html/homesite/packemail.php` - UPDATED

## Files Unchanged (Already Correct)
- `/var/www/html/homesite/track_contact_source.php` - Already has session config
- `/var/www/html/homesite/contact_form.php` - Doesn't directly manage session
- `/var/www/html/homesite/mail_config.php` - Configuration only

## Rollback Instructions

If issues occur, restore previous versions:

```bash
# Restore contact_modal.php (remove session_manager include)
# Restore packemail.php (restore original session code)
# Remove session_manager.php
```

## Monitoring

Watch these logs for session issues:
```bash
# Session activity
tail -f /tmp/contact_tracking.log

# Mail sending
tail -f /tmp/cadman_mail.log

# PHP errors
tail -f /var/log/apache2/error.log | grep -i session
```

## Version History
- **v1.0** (2025-10-09): Initial centralized session manager implementation
- Fixed "Security validation failed" error
- Fixed session loss during page navigation
- Fixed browser caching issues
