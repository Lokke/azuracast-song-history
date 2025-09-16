# AzuraCast Song History WordPress Plugin

Display recent song history from your AzuraCast radio station on your WordPress website with customizable widgets and shortcodes.

## Features

- **Widget Support**: Add a sidebar widget to display recent songs
- **Multiple Shortcodes**: Embed song history with `[azuracast_history]`, show now playing with `[azuracast_nowplaying]`, or add an audio player with `[azuracast_player]`
- **Multiple Layouts**: Choose from List, Grid, Compact, or Table views
- **Cover Images**: Display album artwork with fallback icons
- **Auto-Refresh**: Real-time updates via AJAX
- **Responsive Design**: Mobile-friendly layouts
- **Caching System**: Built-in caching for improved performance
- **Admin Interface**: Easy configuration through WordPress admin

## Installation

### Method 1: Download Release

1. Download the latest release from the [Releases page](https://github.com/Lokke/azuracast-song-history/releases)
2. Upload the plugin folder to `/wp-content/plugins/`
3. Activate the plugin through the 'Plugins' menu in WordPress
4. Configure the plugin at **Settings → AzuraCast**

### Method 2: WordPress Admin Upload

1. Download the latest release ZIP file
2. Go to **Plugins → Add New → Upload Plugin** in WordPress admin
3. Choose the ZIP file and click **Install Now**
4. Activate the plugin
5. Configure the plugin at **Settings → AzuraCast**

## Quick Setup

1. Navigate to **Settings → AzuraCast** in WordPress admin
2. Enter your AzuraCast server URL (e.g., `https://radio.example.com`)
3. Optionally specify a Station ID
4. Click "Test Connection" to verify setup
5. Save settings

## Usage Examples

### Widget
Add the "AzuraCast Song History" widget to your sidebar via **Appearance → Widgets**.

### Shortcodes

```wordpress
[azuracast_history]
[azuracast_history count="10" layout="grid" covers="true"]
[azuracast_nowplaying layout="card"]
[azuracast_player autoplay="false"]
```

## Requirements

- WordPress 5.0 or higher
- PHP 7.4 or higher
- AzuraCast installation with public API access
- cURL extension enabled

## Support

- 🐛 [Report Issues](https://github.com/Lokke/azuracast-song-history/issues)
- 💡 [Feature Requests](https://github.com/Lokke/azuracast-song-history/discussions)
- � [View Releases](https://github.com/Lokke/azuracast-song-history/releases)

## License

GPL v3 or later

---

**Author:** [Lokke](https://github.com/Lokke)