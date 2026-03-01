# All-in-One WP Migration Manager

![WordPress Plugin Version](https://img.shields.io/badge/WordPress-5.6%2B-blue)
![Plugin Version](https://img.shields.io/badge/Version-4.0.0-green)
![PHP Version](https://img.shields.io/badge/PHP-7.4%2B-purple)
![License](https://img.shields.io/badge/License-GPL%20v2-orange)

A complete management solution for the All-in-One WP Migration plugin. Manage extension versions, export/import settings, schedule auto-backups, track activity, and receive email notifications — all from a modern, AJAX-powered admin interface.

---

## Features

### Overview Tab
- Real-time stats: total backups, settings count, extensions installed
- System status: WP version, PHP version, extensions file health, cron schedule
- Recent activity feed from the audit log
- Full backup management (download, restore, revert, notes, delete)
- Inline changelog viewer

### Extensions Tab
- Live search filter across all 22+ extensions
- Version control per extension with inline inputs
- Installed/not-installed badge detection
- Bulk update multiple extensions in one click
- Backup before update, revert from backup

### Settings Tab
- Export AI1WM settings as a portable JSON file
  - Optional sensitive-value redaction
  - Optional export metadata header
- Drag-and-drop JSON import
- **Dry-run preview** — see added/changed/removed settings before writing
- **Selective restore** — pick exactly which keys to import via checkbox list
- Automatic backup before every import
- Current Settings viewer with live search filter
- Download or restore any saved settings backup

### Activity Log Tab
- Full audit trail of every plugin action
- Filter by action type and date range
- Paginated table (25 per page) with user, action badge, description, and JSON context detail modal
- Clear all entries with one click

### Plugin Options Tab
- Configurable max backups per type (1–50, default 5)
- Scheduled auto-backup: disabled / daily / weekly / monthly
- Email notifications with per-event controls (backup created, import complete, backup failed, export complete)
- Activity log retention: 30 / 60 / 90 days or keep forever

---

## What's New in v4.0.0

- Complete architectural rewrite — modular MVC structure with PSR-style autoloader
- AJAX-powered interface — zero page reloads for all operations
- Toast notification system replacing all page refreshes
- Dark sidebar navigation with professional card-based layout
- Full Activity Log with custom database table
- WP-Cron scheduled backups (daily / weekly / monthly)
- Email notification system via `wp_mail()`
- Diff viewer modal for settings comparison
- Selective restore — import only the keys you choose
- Dry-run import — preview changes before applying
- Configurable backup limit (replaces hard-coded 5)
- Backup notes — annotate any backup for later reference
- Individual backup downloads (JSON file, no temp files)
- Extension installed/not-installed detection
- Dashboard widget with quick status summary
- WP-CLI integration with 6 subcommands
- Full i18n support with `.pot` file

---

## Requirements

| Requirement | Minimum |
|---|---|
| WordPress | 5.6 |
| PHP | 7.4 |
| All-in-One WP Migration | Any version with extensions file |
| Role | Administrator (`manage_options`) |

Tested up to WordPress 6.7.

---

## Installation

### Method 1: Upload ZIP via WordPress Admin

1. Download `ai1wm-manager-4.0.0.zip` from [Releases](https://github.com/nurkamol/ai1wm-manager/releases)
2. Go to **Plugins → Add New → Upload Plugin**
3. Choose the ZIP file and click **Install Now**
4. Click **Activate**

### Method 2: Manual (FTP/SSH)

1. Extract the ZIP into `/wp-content/plugins/ai1wm-manager/`
2. Activate via **Plugins** in the WordPress admin

---

## Usage

Navigate to **Tools → AI1WM Manager** in your WordPress admin panel.

### Extension Management

1. Open the **Extensions** tab
2. Use the search bar to find a specific extension
3. Click **Backup Versions** before making any changes
4. Enter new version numbers in the inputs you want to update
5. Check the extensions to update, then click **Update Selected**
6. To undo, click **Revert** on any backup in the list

### Settings Export

1. Open the **Settings** tab
2. Toggle **Redact sensitive values** if exporting for sharing
3. Click **Export Settings** — a JSON file downloads immediately
4. Or click **Create DB Backup** to save a snapshot in the database

### Settings Import

1. Open the **Settings** tab
2. Drag and drop (or click) to select your JSON file
3. Click **Preview Changes** to run a dry-run diff — review what will be added, changed, or removed
4. Click **Import Now** — a backup is automatically created first
5. In the confirmation modal, uncheck any keys you do not want to import, then confirm

### Scheduling Auto-Backups

1. Open **Plugin Options** tab
2. Under **Scheduled Auto-Backup**, choose Daily, Weekly, or Monthly
3. The next scheduled run time is shown immediately
4. Backups are stored as Settings Backups and subject to the backup limit setting

### Email Notifications

1. Open **Plugin Options** tab
2. Enable **Send email notifications**
3. Set the recipient email address
4. Choose which events trigger a notification

### WP-CLI

```bash
# List all extensions and their current versions
wp ai1wm-manager list-extensions

# Backup extension versions (optional note)
wp ai1wm-manager backup-extensions --note="Before bulk update"

# Backup settings
wp ai1wm-manager backup-settings --note="Pre-migration snapshot"

# Export settings to file
wp ai1wm-manager export-settings --file=/tmp/ai1wm-settings.json
wp ai1wm-manager export-settings --file=/tmp/ai1wm-settings.json --redact --no-metadata

# List saved backups
wp ai1wm-manager list-backups
wp ai1wm-manager list-backups --type=settings
wp ai1wm-manager list-backups --type=extensions

# View recent activity log entries
wp ai1wm-manager activity-log
wp ai1wm-manager activity-log --count=50
```

---

## Supported Extensions

| Extension | Prefix | Storage |
|---|---|---|
| Microsoft Azure | AI1WMZE | Azure Blob Storage |
| Backblaze B2 | AI1WMAE | B2 Cloud Storage |
| Box | AI1WMBE | Box Cloud Storage |
| Dropbox | AI1WMDE | Dropbox |
| FTP | AI1WMFE | FTP / SFTP / FTPS |
| Google Drive | AI1WMGE | Google Drive |
| Google Cloud | AI1WMCE | Google Cloud Storage |
| Multisite | AI1WMME | WordPress Multisite |
| OneDrive | AI1WMOE | Microsoft OneDrive |
| Amazon S3 | AI1WMSE | AWS S3 |
| Unlimited | AI1WMUE | Remove size limits |
| URL | AI1WMLE | URL-based import |
| Direct | AI1WMXE | Direct transfer |
| Mega | AI1WMNE | MEGA Cloud |
| pCloud | AI1WMPE | pCloud Storage |
| Wasabi | AI1WMWE | Wasabi Hot Cloud |
| Storj | AI1WMTE | Storj Decentralized |
| Backblaze | AI1WMRE | Backblaze (alt) |
| WebDAV | AI1WMVE | WebDAV servers |
| Scheduled | AI1WMYE | Scheduled backups |
| Migrate Guru | AI1WMHE | Migrate Guru |
| History | AI1WMIE | Backup history |

---

## File Structure

```
ai1wm-manager/
├── ai1wm-manager.php               # Bootstrap, constants, autoloader
├── includes/
│   ├── class-installer.php         # DB table, activation/deactivation, migration
│   ├── class-core.php              # Plugin singleton, admin menu, hooks
│   ├── class-extensions-manager.php
│   ├── class-backup-manager.php
│   ├── class-settings-manager.php
│   ├── class-activity-log.php
│   ├── class-scheduler.php
│   ├── class-notifications.php
│   ├── class-ajax-handler.php
│   ├── class-dashboard-widget.php
│   └── class-cli.php
├── admin/
│   ├── class-admin-page.php
│   └── views/
│       ├── page-overview.php
│       ├── page-extensions.php
│       ├── page-settings.php
│       ├── page-activity-log.php
│       └── page-plugin-settings.php
├── assets/
│   ├── css/admin.css
│   └── js/admin.js
└── languages/
    └── ai1wm-manager.pot
```

---

## Security

- All AJAX endpoints verify `check_ajax_referer()` + `current_user_can('manage_options')`
- Dangerous WordPress core options are blocked from import (`siteurl`, `home`, `auth_key`, `secure_auth_key`, etc.)
- Site-specific values (paths, timestamps, license keys) are excluded from exports automatically
- File uploads validated for type (`.json` only), size (10 MB max), and JSON structure
- Backup keys validated against expected prefix patterns before any DB operation
- No external HTTP requests — all operations are local

---

## Troubleshooting

**Extensions not found / "Extensions file not found"**
- Ensure All-in-One WP Migration is installed and activated
- The extensions file is read from `wp-content/plugins/all-in-one-wp-migration/lib/vendor/servmask/extensions/class-ai1wm-extensions.php`

**Import fails validation**
- Verify the JSON was exported by this plugin or is valid AI1WM settings JSON
- File must be under 10 MB
- The file must have a `.json` extension

**Scheduled backup not running**
- Confirm WP-Cron is working: `wp cron event list`
- Some hosting providers disable WP-Cron — use a real cron job calling `wp-cron.php`

**Email notifications not arriving**
- Verify `wp_mail()` works on your host (some require SMTP configuration)
- Check spam folders; sender is your site's default `wp_mail` sender

**Activity log table missing**
- Deactivate and reactivate the plugin — `register_activation_hook` creates the table via `dbDelta()`

---

## Contributing

1. Fork the repository
2. Create your feature branch (`git checkout -b feature/my-feature`)
3. Commit your changes
4. Push and open a Pull Request

---

## License

GPL v2 or later. See [LICENSE](LICENSE).

---

## Author

**Nurkamol Vakhidov**
- GitHub: [@nurkamol](https://github.com/nurkamol)

---

## Acknowledgments

- ServMask team for the excellent All-in-One WP Migration plugin
- WordPress community for hooks, WP-CLI, and `dbDelta()`

---

See [CHANGELOG.md](CHANGELOG.md) for full version history.
