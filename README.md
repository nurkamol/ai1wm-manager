# All-in-One WP Migration Manager

![WordPress Plugin Version](https://img.shields.io/badge/WordPress-5.0%2B-blue)
![Plugin Version](https://img.shields.io/badge/Version-3.0.0-green)
![License](https://img.shields.io/badge/License-GPL%20v2-orange)

Complete management solution for All-in-One WP Migration plugin - manage extension versions, export/import settings, and configure plugin options with enhanced security and backup features.

## 🚀 Features

### 🧩 Extension Manager
- **Version Control**: Manage and update All-in-One WP Migration extension versions
- **Backup & Restore**: Create backups before making changes and revert if needed
- **Bulk Updates**: Update multiple extensions simultaneously
- **Support for 22+ Extensions**: Including Dropbox, Google Drive, FTP, S3, OneDrive, and more

### ⚙️ Settings Manager
- **Export/Import**: Transfer AI1WM settings between sites
- **Automatic Backups**: Creates backups before imports
- **Data Redaction**: Optional sensitive data protection
- **Clean Format**: Optimized JSON output without bloated metadata
- **Metadata Support**: Include export information for better tracking

### 🔒 Security Features
- **Sensitive Data Protection**: Automatic redaction of passwords, keys, and tokens
- **Safe Import**: Filters dangerous options during import
- **Backup Verification**: Multiple backup layers for safety
- **Nonce Protection**: Secure form submissions
- **File Validation**: Proper file type and size validation

## 📋 Requirements

- WordPress 5.0 or higher
- All-in-One WP Migration plugin installed
- PHP 7.0 or higher
- Administrator privileges

## 🔧 Installation

### Method 1: Manual Installation
1. Download the latest release from [GitHub](https://github.com/nurkamol/ai1wm-manager/releases)
2. Upload the plugin files to `/wp-content/plugins/ai1wm-manager/` directory
3. Activate the plugin through WordPress admin panel

### Method 2: Upload via WordPress Admin
1. Download the plugin ZIP file
2. Go to WordPress Admin → Plugins → Add New → Upload Plugin
3. Choose the ZIP file and click "Install Now"
4. Activate the plugin

## 🎯 Usage

### Accessing the Plugin
Navigate to **Tools → AI1WM Manager** in your WordPress admin panel.

### Extension Management
1. **Create Backup**: Always backup current versions before making changes
2. **Update Extensions**: Select extensions to update and enter new version numbers
3. **Apply Changes**: Update selected extensions
4. **Revert if Needed**: Use backup to restore previous versions

### Settings Management
1. **Export Settings**: Download current AI1WM configuration
   - Choose to redact sensitive data
   - Include metadata for better tracking
2. **Import Settings**: Upload and restore AI1WM configuration
   - Automatic backup creation before import
   - Safe import with dangerous option filtering
3. **Create Backup**: Download complete backup of current settings

## 📊 Supported Extensions

| Extension | Code | Description |
|-----------|------|-------------|
| Microsoft Azure | AI1WMZE | Azure Blob Storage |
| Backblaze B2 | AI1WMAE | B2 Cloud Storage |
| Box | AI1WMBE | Box Cloud Storage |
| Dropbox | AI1WMDE | Dropbox Integration |
| FTP | AI1WMFE | FTP/SFTP/FTPS Support |
| Google Drive | AI1WMGE | Google Drive Storage |
| Google Cloud | AI1WMCE | Google Cloud Storage |
| Multisite | AI1WMME | WordPress Multisite |
| OneDrive | AI1WMOE | Microsoft OneDrive |
| Amazon S3 | AI1WMSE | AWS S3 Storage |
| Unlimited | AI1WMUE | Remove Size Limits |
| URL | AI1WMLE | URL-based Import |
| And more... | | 22+ extensions supported |

## 🛡️ Security

- **Data Redaction**: Automatically removes sensitive information from exports
- **Safe Imports**: Filters out dangerous WordPress options
- **Backup Protection**: Multiple backup layers prevent data loss
- **Secure Processing**: Nonce verification and capability checks
- **File Validation**: Proper file type and size validation

## 🔄 Backup Strategy

The plugin implements a comprehensive backup strategy:

1. **Extension Backups**: File-level backups of extension configurations
2. **Settings Backups**: Database-level backups of AI1WM settings
3. **Automatic Cleanup**: Maintains only the last 5 backups
4. **Import Protection**: Creates backups before any import operation

## 🐛 Troubleshooting

### Common Issues

**Extensions not found:**
- Ensure All-in-One WP Migration plugin is installed and activated
- Check that extensions are properly installed

**Import fails:**
- Verify JSON file format
- Check file size (max 10MB)
- Ensure you have administrator privileges

**Backup not working:**
- Check file permissions on WordPress uploads directory
- Verify sufficient disk space

### Getting Help

1. Check the [Issues](https://github.com/nurkamol/ai1wm-manager/issues) page
2. Create a new issue with detailed information
3. Include WordPress version, plugin version, and error messages

## 🤝 Contributing

Contributions are welcome! Please feel free to submit a Pull Request.

1. Fork the repository
2. Create your feature branch (`git checkout -b feature/AmazingFeature`)
3. Commit your changes (`git commit -m 'Add some AmazingFeature'`)
4. Push to the branch (`git push origin feature/AmazingFeature`)
5. Open a Pull Request

## 📝 License

This project is licensed under the GPL v2 or later.

## 👨‍💻 Author

**Nurkamol Vakhidov**
- Website: [nurkamol.com](https://nurkamol.com)
- GitHub: [@nurkamol](https://github.com/nurkamol)

## 🙏 Acknowledgments

- ServMask team for the excellent All-in-One WP Migration plugin
- WordPress community for continuous support and feedback
- All contributors who help improve this plugin

## 📈 Changelog

See [CHANGELOG.md](CHANGELOG.md) for a detailed history of changes.

---

⭐ **If you find this plugin helpful, please consider giving it a star on GitHub!**