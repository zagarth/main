# Client Database Setup Instructions

This setup creates a SQL database for managing Cadman Manufacturing client information.

## 📁 Files Created

### SQL Scripts (database/)
1. **01_create_database.sql** - Creates the CadmanClients database
2. **02_create_tables.sql** - Creates the clients table with indexes
3. **03_create_users.sql** - Creates admin and viewer database users
4. **04_import_sample_data.sql** - Sample data insert statements

### PHP Scripts
1. **database/import_clients.php** - Imports all retailers from JSON file
2. **admin/client_database.php** - Admin web interface to view clients

## 🔧 Installation Steps

### For Development (MySQL/MariaDB on this server):

1. **Install MariaDB** (if not already installed):
   ```bash
   sudo apt-get install -y mariadb-server mariadb-client
   sudo systemctl start mariadb
   sudo systemctl enable mariadb
   ```

2. **Secure MySQL installation**:
   ```bash
   sudo mysql_secure_installation
   ```

3. **Run SQL scripts as root**:
   ```bash
   sudo mysql < database/01_create_database.sql
   sudo mysql < database/02_create_tables.sql
   sudo mysql < database/03_create_users.sql
   ```

4. **Import client data**:
   ```bash
   php database/import_clients.php
   ```

5. **Access admin panel**:
   - URL: `http://yourserver.com/admin/client_database.php`
   - Password: `CadmanAdmin2025!`

### For Production (SQL Server):

1. **Modify SQL scripts**:
   - Uncomment the SQL Server sections in each .sql file
   - Comment out the MySQL/MariaDB sections

2. **Run on SQL Server Management Studio**:
   - Execute 01_create_database.sql
   - Execute 02_create_tables.sql (SQL Server version)
   - Execute 03_create_users.sql (SQL Server version)

3. **Update PHP connection** in `admin/client_database.php`:
   ```php
   // Change from MySQL to SQL Server
   $pdo = new PDO(
       "sqlsrv:Server=your_server;Database=CadmanClients",
       "cadman_viewer",
       "View2025!Cadman"
   );
   ```

## 👥 Database Users

### Admin User
- **Username**: `cadman_admin`
- **Password**: `Admin2025!Cadman`
- **Permissions**: Full access (SELECT, INSERT, UPDATE, DELETE)

### Viewer User (used by web interface)
- **Username**: `cadman_viewer`
- **Password**: `View2025!Cadman`
- **Permissions**: Read-only (SELECT only)

## 📊 Database Schema

### `clients` Table
| Column | Type | Description |
|--------|------|-------------|
| client_id | INT | Primary key (auto-increment) |
| business_name | VARCHAR(255) | Business name |
| contact_name | VARCHAR(255) | Contact person |
| address | VARCHAR(500) | Street address |
| city | VARCHAR(100) | City |
| province | VARCHAR(50) | Province/State |
| postal_code | VARCHAR(20) | Postal/ZIP code |
| country | VARCHAR(100) | Country (default: Canada) |
| phone | VARCHAR(50) | Phone number |
| email | VARCHAR(255) | Email address |
| website | VARCHAR(255) | Website URL |
| latitude | DECIMAL(10,8) | GPS latitude |
| longitude | DECIMAL(11,8) | GPS longitude |
| client_type | VARCHAR(50) | Type (default: Retailer) |
| status | VARCHAR(20) | Active/Inactive |
| notes | TEXT | Additional notes |
| created_at | TIMESTAMP | Record creation time |
| updated_at | TIMESTAMP | Last update time |

## 🎯 Features

### Admin Panel (`/admin/client_database.php`)
- ✅ Password-protected access
- ✅ View all clients in sortable table
- ✅ Filter by province, status, or search term
- ✅ Statistics dashboard (total clients, provinces, cities)
- ✅ Mobile-responsive design
- ✅ Read-only database access (viewer user)

### Import Script (`database/import_clients.php`)
- ✅ Imports all retailers from canadian_retailers.json
- ✅ Parses specialties and services into notes field
- ✅ Shows progress and error reporting
- ✅ Prevents duplicate imports

## 🔒 Security Notes

**IMPORTANT**: Change these default passwords before deploying to production!

```sql
-- Update admin password
ALTER USER 'cadman_admin'@'localhost' IDENTIFIED BY 'YourNewSecurePassword';

-- Update viewer password
ALTER USER 'cadman_viewer'@'localhost' IDENTIFIED BY 'YourNewSecurePassword';
```

Also update the passwords in:
- `database/import_clients.php`
- `admin/client_database.php`

## 📝 Next Steps

1. Run the installation steps above
2. Import client data
3. Access the admin panel
4. Customize the admin password in `client_database.php`
5. Add additional features as needed (edit, delete, export, etc.)
