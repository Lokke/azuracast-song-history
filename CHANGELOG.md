# Changelog

All notable changes to this project will be documented in this file.

The format is based on [Keep a Changelog](https://keepachangelog.com/en/1.0.0/),
and this project adheres to [Semantic Versioning](https://semver.org/spec/v2.0.0.html).

## [0.0.5] - 2025-09-16

### Improved

- **Simplified Configuration**: Enter only server URL (e.g., `funkturm.radio-endstation.de`)
- **Automatic API Detection**: Plugin builds `/api/nowplaying` path automatically
- **Station Selection**: Admin interface now shows available radio stations for selection
- **Better AzuraCast Integration**: Uses full nowplaying API response structure
- **Enhanced Error Handling**: Better feedback when stations cannot be loaded

### Technical Changes

- Updated admin interface with station dropdown
- Improved API class to handle AzuraCast response format
- Added automatic station discovery from nowplaying endpoint
- Simplified URL configuration (no more full API paths needed)

## [0.0.4] - 2025-09-16

### Fixed

- Fixed fatal error: Corrected admin class name from `AzuraCast_Song_History_Admin` to `AzuraCast_Admin`
- Fixed fatal error: Corrected widget class name from `AzuraCast_Song_History_Widget` to `AzuraCast_Widget`
- Plugin activation now works properly in WordPress admin

## [0.0.3] - 2025-09-16

### Fixed

- Fixed fatal error: Corrected class name from `AzuraCast_Song_History_Shortcode` to `AzuraCast_Shortcodes`
- Plugin now properly instantiates the shortcode class

## [0.0.2] - 2025-09-16

### Fixed

- Fixed fatal error: Corrected filename from `class-shortcode.php` to `class-shortcodes.php` in includes

## [0.0.1] - 2024-12-19

### Added

- Initial release of AzuraCast Song History WordPress Plugin
- AzuraCast API integration with caching system
- WordPress widget for sidebar display
- Multiple shortcodes for flexible content embedding
- Comprehensive admin interface with settings
- Responsive design with multiple layout options
- Auto-refresh functionality via AJAX
- Error handling and fallback mechanisms
- Database caching for offline capability
- Translation-ready with text domains
- GPL v3 or later license

### Features

- Real-time song history display
- Customizable appearance and layout
- Artist and song information display
- Optional artwork display
- Admin settings page
- Widget configuration options
- Shortcode support with parameters
- AJAX auto-refresh
- Responsive CSS styling
- Dark mode support

[0.0.5]: https://github.com/Lokke/azuracast-song-history/releases/tag/v0.0.5
[0.0.4]: https://github.com/Lokke/azuracast-song-history/releases/tag/v0.0.4
[0.0.3]: https://github.com/Lokke/azuracast-song-history/releases/tag/v0.0.3
[0.0.2]: https://github.com/Lokke/azuracast-song-history/releases/tag/v0.0.2
[0.0.1]: https://github.com/Lokke/azuracast-song-history/releases/tag/v0.0.1