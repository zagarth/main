# Complete User Management System - Setup Guide

This system provides role-based access control with business users and administrators.

## 🎯 System Overview

### Database Structure
1. **`users`** table - Login credentials and roles
2. **`clients`** table - Business information (JOIN with users)
3. **`orders`** table - Order tracking for businesses
4. **`order_items`** table - Detailed order line items

### User Roles
- **Admin**: Full access to admin panel, client management, user creation
- **Business**: Access to their own dashboard, view orders, manage profile

## 📁 New Files Created

### Database
- `database/05_create_users_tables.sql` - Creates users, orders, and order_items tables

### Core System
- `includes/db_config.php` - Database connection and helper functions
- `login.php` - Universal login page with role-based routing

### User Area
- `user/dashboard.php` - Business user dashboard (orders, profile info)

### Admin Area  
- `admin/user_management.php` - Create user accounts for businesses

## 🚀 Installation Steps

### 1. Run Database Scripts

```bash
# Run all scripts in order
sudo mysql < database/01_create_database.sql
sudo mysql < database/02_create_tables.sql
sudo mysql < database/03_create_users.sql
sudo mysql < database/04_import_sample_data.sql
sudo mysql < database/05_create_users_tables.sql

# Import client data
php database/import_clients.php
```

### 2. Test Default Admin Login

**URL**: `http://yourserver.com/login.php`

**Default Admin**:
- Username: `admin`
- Password: `Admin2025!`

**⚠️ IMPORTANT**: Change this password immediately after first login!

### 3. Create Business User Accounts

1. Login as admin
2. Go to **User Management** from dashboard
3. Select a client from the dropdown
4. Create username, email, and password
5. User can now login and access their dashboard

## 🔐 How It Works

### Login Flow
```
User enters credentials
    ↓
login.php verifies against users table
    ↓
Role Check:
    ├── Admin → /admin/dashboard.php
    └── Business → /user/dashboard.php
```

### Data Relationships
```
users table
    ├── user_id (primary key)
    ├── client_id (foreign key → clients.client_id)
    ├── username, email, password_hash
    ├── role (admin or business)
    └── status (active, inactive, pending)

clients table (business information)
    ├── client_id (primary key)
    ├── business_name, address, city, province
    └── phone, email, website

orders table
    ├── order_id (primary key)
    ├── client_id (foreign key → clients.client_id)
    └── order details...
```

### JOIN Example
When a business user logs in, the system JOINs:
```sql
SELECT u.*, c.business_name, c.city, c.province 
FROM users u
LEFT JOIN clients c ON u.client_id = c.client_id
WHERE u.username = :username
```

This gives the user their login info + business details in one query.

## 👥 User Features

### Business User Dashboard (`/user/dashboard.php`)
- ✅ View business information (from clients table)
- ✅ View account details
- ✅ View order history
- ✅ Track order status
- ✅ Statistics (total orders, pending, value)

### Admin Dashboard (`/admin/dashboard.php`)
- ✅ System statistics
- ✅ Quick links to all admin tools
- ✅ Client database access
- ✅ User management
- ✅ Order management

### Admin - User Management (`/admin/user_management.php`)
- ✅ Create user accounts for clients
- ✅ View all business users
- ✅ See last login times
- ✅ Only show clients without accounts in dropdown

## 🛡️ Security Features

1. **Password Hashing**: Uses PHP's `password_hash()` with bcrypt
2. **Session Management**: Secure session handling
3. **Role-Based Access**: Automatic routing based on role
4. **SQL Injection Protection**: PDO prepared statements
5. **XSS Protection**: `htmlspecialchars()` on all output

## 📊 Database Helper Functions

Located in `includes/db_config.php`:

```php
verifyUser($username, $password)      // Authenticate user
getUserById($user_id)                  // Get user + client info (JOIN)
getClientOrders($client_id, $limit)    // Get orders for a client
createUser($client_id, $username, ...)// Create new user account
```

## 🔄 Workflow Example

### Creating a New Business User

1. **Admin logs in** → `/login.php` → Routed to `/admin/dashboard.php`
2. **Admin clicks** "User Management"
3. **Selects client** from dropdown (clients WITHOUT users shown)
4. **Creates account**:
   - Username: `jewelrystore1`
   - Email: `info@jewelrystore.com`
   - Password: `TempPass123!`
5. **System creates** entry in `users` table:
   - Links to `client_id`
   - Hashes password
   - Sets role = 'business'
6. **Business can now login**
7. **On login**, they're routed to `/user/dashboard.php`
8. **Dashboard shows** their info via JOIN:
   ```sql
   SELECT u.*, c.* FROM users u
   LEFT JOIN clients c ON u.client_id = c.client_id
   WHERE u.user_id = ?
   ```

## 🎨 Customization

### Change Login Page Logo
Edit `login.php`:
```html
<h1>🏭 Cadman Manufacturing</h1>
```

### Add More User Dashboard Features
Edit `user/dashboard.php` - add sections like:
- Order form
- Invoice download
- Profile editing
- Support tickets

### Add Order Management
Create `admin/order_management.php` to:
- View all orders
- Update order status
- Add tracking numbers
- Generate invoices

## 📧 Next Steps

1. **Email Integration**: Send welcome emails when users are created
2. **Password Reset**: Allow users to reset forgotten passwords
3. **Profile Editing**: Let users update their email/password
4. **Order Placement**: Connect to shopping cart system
5. **Notifications**: Email users when order status changes

## 🔑 Default Credentials Summary

**Database Users** (SQL level):
- Admin: `cadman_admin` / `Admin2025!Cadman`
- Viewer: `cadman_viewer` / `View2025!Cadman`

**Website Users** (login.php):
- Admin: `admin` / `Admin2025!`
- Business: Created via user management

**Change ALL passwords before production!**
