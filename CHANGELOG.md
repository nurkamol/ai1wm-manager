# Changelog

All notable changes to the All-in-One WP Migration Manager plugin will be documented in this file.

The format is based on [Keep a Changelog](https://keepachangelog.com/en/1.0.0/), and this project adheres to [Semantic Versioning](https://semver.org/spec/v2.0.0.html).

## [4.0.0] - 2026-03-01

### Added
- **Modular Architecture**: Complete rewrite from single file to 20-file MVC structure
- **AJAX Operations**: All actions are now AJAX-powered — no page reloads, toast notifications
- **Activity / Audit Log**: Custom DB table tracking every plugin action with filters and pagination
- **Scheduled Auto-Backups**: WP-Cron integration with daily/weekly/monthly options
- **Email Notifications**: Admin email alerts for backup creation, import, and failures
- **Diff View Before Restore**: Color-coded modal showing added/changed/removed settings before committing
- **Selective Restore**: Restore individual settings from a backup using a checkbox list
- **Dry-Run Import**: Preview what will change before actually importing a JSON file
- **WordPress Dashboard Widget**: Quick status summary with link to manager
- **WP-CLI Support**: `wp ai1wm-manager backup-extensions|backup-settings|export-settings|list-extensions|list-backups|activity-log`
- **Extension Search & Filter**: Real-time JS search across the extensions grid
- **Backup Notes / Labels**: Add editable notes to any backup
- **Extension Compatibility Check**: Warns if an extension folder isn't installed before version update
- **Configurable Backup Limit**: Set max backups per type in Plugin Options (default: 5)
- **Individual Backup Download**: Download any stored backup as a JSON file
- **Settings Search**: Filter current settings list by option key
- **i18n Support**: All user-facing strings wrapped in translation functions

### Changed
- **UI Redesign**: Professional dark sidebar layout with cards, modals, and toast notifications
- **PHP Requirement**: Bumped to PHP 7.4+
- **WordPress Requirement**: Bumped to WordPress 5.6+, tested up to 6.7
- **Version**: 4.0.0

### Removed
- Single-file architecture replaced by modular MVC structure

---

## [3.1.1] - 2025-07-26

### Fixed
- **WordPress Core Conflict**: Resolved "Security check failed" error when updating plugins or performing bulk operations
- **Action Handler Isolation**: Plugin now only processes its own actions, avoiding interference with WordPress core functionality
- **Nonce Verification Scope**: Limited nonce checks to plugin-specific pages and actions only

### Changed
- **Hook Priority**: Moved admin_init hook to lower priority (20) to prevent conflicts
- **Page-Specific Processing**: Actions only processed when on plugin's admin page
- **Cleanup Timing**: Backup cleanup only runs on plugin pages to avoid conflicts

## [3.1.0] - 2025-07-26

### Added
- **Individual Backup Management**: Remove specific backups with confirmation dialogs
- **Bulk Backup Removal**: Clear all backups at once with safety confirmations
- **Visual Backup List**: Organized cards showing timestamps and backup types
- **Smart Data Filtering**: Automatic exclusion of site-specific data from exports/display
- **Total Backup Counter**: Real-time backup count in system status

### Changed
- **Export Filtering**: Site-specific options (backup paths, security keys, timestamps) now excluded
- **Import Safety**: Enhanced protection against importing problematic site-specific data
- **Data Portability**: Exports contain only clean, transferable settings

### Security
- **Data Isolation**: Site-specific security keys and paths completely isolated from exports
- **Safe Exports**: Automatic filtering prevents exposure of sensitive data
- **Enhanced Import Protection**: Better validation against dangerous options

## [3.0.0] - 2025-07-25

### Added
- **Major Plugin Unification**: Combined extension manager and settings manager into one comprehensive solution
- **Tabbed Interface**: Clean navigation with Overview, Extensions, and Settings tabs
- **Interactive Dashboard**: Clickable feature cards for easy navigation
- **Enhanced Security**: Improved data validation and sanitization throughout
- **Smart JSON Cleanup**: Automatic removal of bloated extension metadata from exports
- **Visual Status Indicators**: Real-time system status display
- **Modern UI Design**: Grid layouts, hover effects, and improved visual feedback
- **Comprehensive Backup System**: Unified backup strategy for both extensions and settings
- **Auto-cleanup Features**: Automatic removal of duplicate and orphaned backup entries

### Changed
- **Plugin Name**: Renamed from "All-in-One WP Migration Complete Manager" to "All-in-One WP Migration Manager"
- **Class Structure**: Simplified class naming and internal organization
- **CSS Classes**: Updated all styling classes to match new naming convention
- **Option Names**: Streamlined database option naming for better organization
- **JavaScript Implementation**: Switched from jQuery to vanilla JavaScript for better reliability
- **Tab Functionality**: Completely rewritten tab switching mechanism
- **Export Format**: Optimized JSON output to match clean format standards

### Fixed
- **Tab Navigation**: Resolved issues with Extensions and Settings tabs not switching properly
- **Feature Card Interaction**: Fixed clickable cards not responding correctly
- **JavaScript Loading**: Eliminated jQuery dependency issues
- **Backup Duplicates**: Resolved problems with multiple backup entries
- **JSON Bloat**: Fixed export files containing unnecessary extension metadata
- **CSS Conflicts**: Improved styling isolation and consistency
- **Form Validation**: Enhanced security checks and data validation

### Improved
- **Performance**: Faster loading times and reduced resource usage
- **User Experience**: More intuitive interface with better visual cues
- **Code Quality**: Cleaner, more maintainable codebase
- **Documentation**: Enhanced inline documentation and code comments
- **Security**: Strengthened nonce verification and capability checks
- **Compatibility**: Better WordPress version compatibility

### Removed
- **jQuery Dependency**: Eliminated reliance on jQuery for core functionality
- **Bloated Metadata**: Removed unnecessary extension marketing data from exports
- **Redundant Code**: Cleaned up duplicate functions and unused methods
- **Legacy Options**: Removed outdated database options and settings

## [2.0.0] - 2025-07-24

### Added
- **Settings Manager**: Complete settings export/import functionality
- **Enhanced Security Features**: Data redaction and secure import/export operations
- **Metadata Support**: Export information tracking and validation
- **Automatic Backups**: Backup creation before settings import
- **File Validation**: Proper JSON file validation and size limits
- **Import Safety**: Dangerous option filtering during import process

### Changed
- **Export Format**: Improved JSON structure with metadata support
- **Security Model**: Enhanced data protection and validation
- **Backup Strategy**: More comprehensive backup and restore system

### Fixed
- **Import Reliability**: Resolved issues with settings import failures
- **Data Integrity**: Fixed problems with corrupted setting values
- **File Handling**: Improved file upload and processing

## [1.0.1] - 2025-01-15

### Added
- **Extension Version Manager**: Manage All-in-One WP Migration extension versions
- **Backup and Restore**: Create backups before updating extension versions
- **Bulk Updates**: Update multiple extensions simultaneously
- **Version Tracking**: Track current and target versions for all extensions
- **Revert Functionality**: Restore previous extension versions from backups

### Changed
- **Version Detection**: Improved extension version detection algorithms
- **UI Layout**: Enhanced table layout for better version management

### Fixed
- **File Permissions**: Resolved issues with extension file modifications
- **Version Parsing**: Fixed problems with version number detection
- **Backup Storage**: Corrected backup data storage and retrieval

## [1.0.0] - 2024-12-01

### Added
- **Initial Release**: Basic extension version management
- **Core Functionality**: Extension backup and version update capabilities
- **Admin Interface**: WordPress admin panel integration
- **Security Features**: Basic nonce verification and capability checks

---

## Legend

- `Added` for new features
- `Changed` for changes in existing functionality
- `Deprecated` for soon-to-be removed features
- `Removed` for now removed features
- `Fixed` for any bug fixes
- `Security` for vulnerability fixes

## Version Support

| Version | WordPress | PHP | Support Status |
|---------|-----------|-----|----------------|
| 3.1.x   | 5.0+      | 7.0+ | ✅ Active      |
| 3.0.x   | 5.0+      | 7.0+ | 🔄 Maintenance |
| 2.0.x   | 5.0+      | 7.0+ | ❌ End of Life |
| 1.0.x   | 5.0+      | 7.0+ | ❌ End of Life |

## Migration Notes

### From v3.0.x to v3.1.0
- **Automatic Migration**: Plugin handles migration automatically
- **Enhanced Security**: New filtering applies immediately
- **No Action Required**: All existing data and functionality preserved
- **Backup Management**: New backup management features available immediately

### From v2.x to v3.0.0
- **Automatic Migration**: Plugin handles migration automatically
- **Backup Cleanup**: Old backup formats are automatically cleaned up
- **No Action Required**: Update will preserve all existing settings and data

### From v1.x to v2.0.0
- **Settings Backup**: Create manual backup before upgrading
- **Configuration Review**: Review imported settings after upgrade
- **Feature Migration**: Extension management features carry over seamlessly

For detailed upgrade instructions and troubleshooting, please visit our [GitHub repository](https://github.com/nurkamol/ai1wm-manager).

### Added
- **Major Plugin Unification**: Combined extension manager and settings manager into one comprehensive solution
- **Tabbed Interface**: Clean navigation with Overview, Extensions, and Settings tabs
- **Interactive Dashboard**: Clickable feature cards for easy navigation
- **Enhanced Security**: Improved data validation and sanitization throughout
- **Smart JSON Cleanup**: Automatic removal of bloated extension metadata from exports
- **Visual Status Indicators**: Real-time system status display
- **Modern UI Design**: Grid layouts, hover effects, and improved visual feedback
- **Comprehensive Backup System**: Unified backup strategy for both extensions and settings
- **Auto-cleanup Features**: Automatic removal of duplicate and orphaned backup entries

### Changed
- **Plugin Name**: Renamed from "All-in-One WP Migration Complete Manager" to "All-in-One WP Migration Manager"
- **Class Structure**: Simplified class naming and internal organization
- **CSS Classes**: Updated all styling classes to match new naming convention
- **Option Names**: Streamlined database option naming for better organization
- **JavaScript Implementation**: Switched from jQuery to vanilla JavaScript for better reliability
- **Tab Functionality**: Completely rewritten tab switching mechanism
- **Export Format**: Optimized JSON output to match clean format standards

### Fixed
- **Tab Navigation**: Resolved issues with Extensions and Settings tabs not switching properly
- **Feature Card Interaction**: Fixed clickable cards not responding correctly
- **JavaScript Loading**: Eliminated jQuery dependency issues
- **Backup Duplicates**: Resolved problems with multiple backup entries
- **JSON Bloat**: Fixed export files containing unnecessary extension metadata
- **CSS Conflicts**: Improved styling isolation and consistency
- **Form Validation**: Enhanced security checks and data validation

### Improved
- **Performance**: Faster loading times and reduced resource usage
- **User Experience**: More intuitive interface with better visual cues
- **Code Quality**: Cleaner, more maintainable codebase
- **Documentation**: Enhanced inline documentation and code comments
- **Security**: Strengthened nonce verification and capability checks
- **Compatibility**: Better WordPress version compatibility

### Removed
- **jQuery Dependency**: Eliminated reliance on jQuery for core functionality
- **Bloated Metadata**: Removed unnecessary extension marketing data from exports
- **Redundant Code**: Cleaned up duplicate functions and unused methods
- **Legacy Options**: Removed outdated database options and settings

## [2.0.0] - 2025-07-24

### Added
- **Settings Manager**: Complete settings export/import functionality
- **Enhanced Security Features**: Data redaction and secure import/export operations
- **Metadata Support**: Export information tracking and validation
- **Automatic Backups**: Backup creation before settings import
- **File Validation**: Proper JSON file validation and size limits
- **Import Safety**: Dangerous option filtering during import process

### Changed
- **Export Format**: Improved JSON structure with metadata support
- **Security Model**: Enhanced data protection and validation
- **Backup Strategy**: More comprehensive backup and restore system

### Fixed
- **Import Reliability**: Resolved issues with settings import failures
- **Data Integrity**: Fixed problems with corrupted setting values
- **File Handling**: Improved file upload and processing

## [1.0.1] - 2025-01-15

### Added
- **Extension Version Manager**: Manage All-in-One WP Migration extension versions
- **Backup and Restore**: Create backups before updating extension versions
- **Bulk Updates**: Update multiple extensions simultaneously
- **Version Tracking**: Track current and target versions for all extensions
- **Revert Functionality**: Restore previous extension versions from backups

### Changed
- **Version Detection**: Improved extension version detection algorithms
- **UI Layout**: Enhanced table layout for better version management

### Fixed
- **File Permissions**: Resolved issues with extension file modifications
- **Version Parsing**: Fixed problems with version number detection
- **Backup Storage**: Corrected backup data storage and retrieval

## [1.0.0] - 2024-12-01

### Added
- **Initial Release**: Basic extension version management
- **Core Functionality**: Extension backup and version update capabilities
- **Admin Interface**: WordPress admin panel integration
- **Security Features**: Basic nonce verification and capability checks

---

## Legend

- `Added` for new features
- `Changed` for changes in existing functionality
- `Deprecated` for soon-to-be removed features
- `Removed` for now removed features
- `Fixed` for any bug fixes
- `Security` for vulnerability fixes

## Version Support

| Version | WordPress | PHP | Support Status |
|---------|-----------|-----|----------------|
| 3.0.x   | 5.0+      | 7.0+ | ✅ Active      |
| 2.0.x   | 5.0+      | 7.0+ | 🔄 Maintenance |
| 1.0.x   | 5.0+      | 7.0+ | ❌ End of Life |

## Migration Notes

### From v2.x to v3.0.0
- **Automatic Migration**: Plugin handles migration automatically
- **Backup Cleanup**: Old backup formats are automatically cleaned up
- **No Action Required**: Update will preserve all existing settings and data

### From v1.x to v2.0.0
- **Settings Backup**: Create manual backup before upgrading
- **Configuration Review**: Review imported settings after upgrade
- **Feature Migration**: Extension management features carry over seamlessly

For detailed upgrade instructions and troubleshooting, please visit our [GitHub repository](https://github.com/nurkamol/ai1wm-manager).