# AzuraCast Song History WordPress Plugin

A WordPress plugin that displays the song history from your AzuraCast radio station on your website. Features customizable widgets, shortcodes, and a responsive design with multiple layout options.

**Author:** Lokke  
**Version:** 0.0.1  
**License:** GPL v3 or later

## Features

- **Widget Support**: Add a sidebar widget to display recent songs
- **Shortcodes**: Embed song history anywhere with `[azuracast_history]`
- **Now Playing**: Show currently playing song with `[azuracast_nowplaying]`
- **Audio Player**: Stream your radio with `[azuracast_player]`
- **Multiple Layouts**: List, Grid, Compact, and Table views
- **Cover Images**: Display album artwork with fallback icons
- **Auto-Refresh**: Real-time updates via AJAX
- **Caching**: Built-in caching for improved performance
- **Responsive Design**: Mobile-friendly layouts
- **Admin Interface**: Easy configuration through WordPress admin

## Requirements

- WordPress 5.0 or higher
- PHP 7.4 or higher
- Active AzuraCast installation with public API access
- cURL extension enabled

## Installation

### Method 1: Manual Installation

1. Download the plugin files
2. Upload the `azuracast-song-history` folder to `/wp-content/plugins/`
3. Activate the plugin through the 'Plugins' menu in WordPress
4. Configure the plugin at Settings → AzuraCast

### Method 2: Upload via WordPress Admin

1. Go to Plugins → Add New → Upload Plugin
2. Choose the plugin ZIP file and click Install Now
3. Activate the plugin
4. Configure the plugin at Settings → AzuraCast

## Configuration

### Basic Setup

1. Navigate to **Settings → AzuraCast** in your WordPress admin
2. Enter your AzuraCast server URL (e.g., `https://radio.example.com`)
3. Optionally specify a Station ID (leave blank to use the first station)
4. Click "Test Connection" to verify the setup
5. Save your settings

### Advanced Settings

- **Stream URL**: Direct link to your audio stream for the player shortcode
- **Default Song Count**: Number of songs to display by default (1-50)
- **Default Layout**: Choose from List, Grid, Compact, or Table
- **Show Cover Images**: Enable/disable album artwork display
- **Show Timestamps**: Display when songs were played
- **Cache Duration**: How long to cache API responses (1-60 minutes)
- **Database Cache**: Store songs locally for offline fallback

## Usage

### Widget

1. Go to **Appearance → Widgets**
2. Find the "AzuraCast Song History" widget
3. Drag it to your desired sidebar
4. Configure the widget settings:
   - Title
   - Number of songs
   - Layout style
   - Show covers/timestamps
   - Auto-refresh options

### Shortcodes

#### Song History

Display a list of recently played songs:

```wordpress
[azuracast_history]
```

With custom options:

```wordpress
[azuracast_history count="15" layout="grid" covers="true" time="true" refresh="true"]
```

#### Now Playing

Show the currently playing song:

```wordpress
[azuracast_nowplaying]
```

With custom layout:

```wordpress
[azuracast_nowplaying layout="card" covers="true" title="Now On Air"]
```

#### Live Moderator

Display the name of the current live moderator (only when someone is broadcasting):

```wordpress
[azuracast_live_moderator]
```

With custom styling:

```wordpress
[azuracast_live_moderator class="my-moderator" style="color: red; font-weight: bold;"]
```

**Note:** This shortcode only displays when a moderator is live. When no one is broadcasting, nothing is shown.

#### Audio Player

Embed a streaming audio player:

```wordpress
[azuracast_player]
```

With custom settings:

```wordpress
[azuracast_player autoplay="false" volume="0.8" controls="true"]
```

### Shortcode Parameters

#### General Parameters (all shortcodes)

| Parameter | Default | Options | Description |
|-----------|---------|---------|-------------|
| `title` | - | text | Custom title |
| `class` | - | CSS class | Additional CSS class |
| `style` | - | CSS | Inline CSS styles |
| `refresh` | false | true, false | Enable auto-refresh |
| `refresh_interval` | 30 | 10-300 | Refresh interval (seconds) |

#### Song History Parameters

| Parameter | Default | Options | Description |
|-----------|---------|---------|-------------|
| `count` | 10 | 1-50 | Number of songs to display |
| `layout` | list | list, grid, compact, table | Display layout |
| `covers` | true | true, false | Show album covers |
| `time` | true | true, false | Show play timestamps |
| `artist` | true | true, false | Show artist names |
| `album` | true | true, false | Show album names |

#### Live Moderator Parameters

| Parameter | Default | Options | Description |
|-----------|---------|---------|-------------|
| `class` | - | CSS class | Additional CSS class |
| `style` | - | CSS | Inline CSS styles |

**Note:** The live moderator shortcode automatically hides when no one is broadcasting.

## Styling

### CSS Classes

The plugin provides numerous CSS classes for customization:

- `.azuracast-song-history-widget` - Widget container
- `.azuracast-song-history-shortcode` - Shortcode container
- `.azuracast-song-item` - Individual song item
- `.azuracast-song-title` - Song title
- `.azuracast-song-artist` - Artist name
- `.azuracast-song-album` - Album name
- `.azuracast-song-time` - Timestamp
- `.azuracast-cover-image` - Album cover image
- `.azuracast-no-cover` - Fallback cover icon

### Layout Classes

- `.azuracast-layout-list` - List layout
- `.azuracast-layout-grid` - Grid layout
- `.azuracast-layout-compact` - Compact layout
- `.azuracast-layout-table` - Table layout

### Custom CSS Example

```css
/* Customize song titles */
.azuracast-song-title {
    color: #0073aa;
    font-weight: bold;
}

/* Style grid layout */
.azuracast-song-grid .azuracast-song-card {
    border: 2px solid #ddd;
    border-radius: 10px;
}

/* Hide timestamps */
.azuracast-song-time {
    display: none;
}
```

## API Integration

### AzuraCast API Endpoints

The plugin uses these AzuraCast API endpoints:

- `/api/nowplaying` - Get current song and history
- `/api/nowplaying/{station_id}` - Get data for specific station
- `/api/status` - Check server status (for connection testing)

### Caching

- **Transient Cache**: 5-minute cache for API responses
- **Database Cache**: Persistent storage for offline fallback
- **Auto-cleanup**: Old entries are automatically removed

### Error Handling

- Graceful fallback to cached data when API is unavailable
- User-friendly error messages
- Automatic retry mechanisms
- Debug information in admin panel

## Troubleshooting

### Common Issues

**Plugin not showing songs:**

1. Check that your AzuraCast URL is correct
2. Verify the station is broadcasting
3. Test the connection in Settings → AzuraCast
4. Check for PHP cURL extension

**Cover images not loading:**

1. Ensure your AzuraCast server is accessible
2. Check image URLs in browser
3. Verify server has image processing enabled

**Auto-refresh not working:**

1. Check JavaScript console for errors
2. Verify AJAX endpoints are accessible
3. Test with WordPress admin user

**Styling issues:**

1. Check for theme CSS conflicts
2. Use browser developer tools
3. Add custom CSS to override defaults

### Debug Information

Access debug information in **Settings → AzuraCast → Tools** tab:

- Plugin version
- WordPress version  
- PHP version
- Cached song count
- Last cache update
- Configuration settings

### Getting Support

1. Check the debug information
2. Test with default WordPress theme
3. Disable other plugins to test for conflicts
4. Provide specific error messages when reporting issues

## Development

### File Structure

```text
azuracast-song-history/
├── azuracast-song-history.php     # Main plugin file
├── includes/
│   ├── class-api.php              # API integration
│   ├── class-widget.php           # Widget functionality
│   └── class-shortcodes.php       # Shortcode handlers
├── admin/
│   └── class-admin.php            # Admin interface
├── assets/
│   ├── css/
│   │   ├── public.css             # Frontend styles
│   │   └── admin.css              # Admin styles
│   └── js/
│       ├── public.js              # Frontend JavaScript
│       └── admin.js               # Admin JavaScript
├── languages/                     # Translation files
├── README.md                      # This file
└── .github/
    └── copilot-instructions.md    # Development guidelines
```

### Hooks and Filters

**Actions:**

- `azuracast_song_history_init` - Plugin initialization
- `azuracast_song_history_activated` - Plugin activation
- `azuracast_song_history_deactivated` - Plugin deactivation

**Filters:**

- `azuracast_song_history_api_response` - Modify API response
- `azuracast_song_history_song_data` - Filter song data
- `azuracast_song_history_cache_duration` - Modify cache duration

### Contributing

1. Fork the repository
2. Create a feature branch
3. Make your changes
4. Test thoroughly
5. Submit a pull request

## License

This plugin is licensed under the GPL v3 or later.

## Changelog

### 0.0.1

- Initial development release
- AzuraCast API integration with caching
- WordPress widget for sidebar display
- Multiple shortcodes for flexible content embedding
- Comprehensive admin interface with settings
- Responsive design with multiple layout options
- Auto-refresh functionality via AJAX
- Error handling and fallback mechanisms
- Database caching for offline capability

## Credits

Developed by [Lokke](https://github.com/Lokke) for WordPress and AzuraCast integration.

---

**Need help?** Check the troubleshooting section above or review the debug information in your WordPress admin panel.