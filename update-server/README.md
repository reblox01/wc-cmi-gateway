# CMI Gateway Update Server Deployment Guide

This guide explains how to set up and deploy the update server for the CMI Gateway plugin.

## Requirements

- WordPress installation (can be separate from your main site)
- SSL certificate (required for secure updates)
- PHP 8.1 or higher
- mod_rewrite enabled

## Installation Steps

1. Create a new WordPress installation (or use an existing one)
2. Copy the `update-server` directory to your WordPress plugins directory
3. Activate the plugin
4. Configure your web server:

### Apache Configuration
```apache
<IfModule mod_rewrite.c>
RewriteEngine On
RewriteBase /
RewriteRule ^index\.php$ - [L]
RewriteCond %{REQUEST_FILENAME} !-f
RewriteCond %{REQUEST_FILENAME} !-d
RewriteRule . /index.php [L]
</IfModule>
```

### Nginx Configuration
```nginx
location / {
    try_files $uri $uri/ /index.php?$args;
}
```

## Plugin Package Preparation

1. Create a new release of your plugin
2. Zip the plugin directory excluding:
   - .git directory
   - node_modules
   - tests
   - update-server directory
   - any other development files
3. Name the zip file according to the version (e.g., wc-cmi-gateway-1.0.1.zip)
4. Upload to your server's download directory

## Update Server Configuration

1. Update the version number in update-api.php
2. Update the download_url to point to your zip file
3. Update the banners URLs
4. Update the changelog
5. Test the API endpoint: https://your-domain.com/wp-json/wc-cmi/v1/update-check

## Security Considerations

1. Use HTTPS for all URLs
2. Implement proper authentication if needed
3. Keep your WordPress installation updated
4. Use strong passwords
5. Consider implementing rate limiting

## Testing Updates

1. Install the plugin on a test site
2. Change the version number in the update server
3. Check if WordPress detects the update
4. Test the update process
5. Verify the plugin works after update

## Monitoring

Monitor the following:
- Server uptime
- API response times
- Download success rates
- Error logs
- Update completion rates
