<?php
if (!defined('ABSPATH')) {
    exit;
}

class WC_CMI_Updater {
    private $plugin_slug;
    private $version;
    private $cache_key;
    private $cache_allowed;

    public function __construct() {
        $this->plugin_slug = plugin_basename(WC_CMI_PLUGIN_DIR . 'wc-cmi-gateway.php');
        $this->version = WC_CMI_VERSION;
        $this->cache_key = 'wc_cmi_updater';
        $this->cache_allowed = false;

        add_filter('plugins_api', array($this, 'info'), 20, 3);
        add_filter('site_transient_update_plugins', array($this, 'update'));
        add_action('upgrader_process_complete', array($this, 'purge'), 10, 2);
    }

    /**
     * Add our self-hosted updater to return response
     */
    public function update($transient) {
        if (empty($transient->checked)) {
            return $transient;
        }

        $remote = $this->fetch_remote_info();

        if ($remote && version_compare($this->version, $remote->version, '<')) {
            $response = new stdClass();
            $response->slug = $this->plugin_slug;
            $response->plugin = $this->plugin_slug;
            $response->new_version = $remote->version;
            $response->tested = $remote->tested;
            $response->requires_php = $remote->requires_php;
            $response->package = $remote->download_url;
            $response->url = $remote->homepage;

            $transient->response[$this->plugin_slug] = $response;
        }

        return $transient;
    }

    /**
     * Add our self-hosted description to the filter
     */
    public function info($res, $action, $args) {
        // Check if this plugins API is about this plugin
        if (!isset($args->slug) || $args->slug != dirname($this->plugin_slug)) {
            return $res;
        }

        $remote = $this->fetch_remote_info();

        if (!$remote) {
            return $res;
        }

        $res = new stdClass();

        $res->name = $remote->name;
        $res->slug = dirname($this->plugin_slug);
        $res->version = $remote->version;
        $res->tested = $remote->tested;
        $res->requires = $remote->requires;
        $res->requires_php = $remote->requires_php;
        $res->author = $remote->author;
        $res->author_profile = $remote->author_profile;
        $res->download_link = $remote->download_url;
        $res->trunk = $remote->download_url;
        $res->last_updated = $remote->last_updated;
        $res->sections = array(
            'description' => $remote->sections->description,
            'installation' => $remote->sections->installation,
            'changelog' => $remote->sections->changelog
        );

        if (!empty($remote->banners)) {
            $res->banners = array(
                'low' => $remote->banners->low,
                'high' => $remote->banners->high
            );
        }

        return $res;
    }

    /**
     * Fetch remote info from your update server
     */
    private function fetch_remote_info() {
        $remote = get_transient($this->cache_key);

        if (false === $remote || !$this->cache_allowed) {
            $remote = wp_remote_get(
                'https://your-update-server.com/wp-json/wc-cmi/v1/update-check',
                array(
                    'timeout' => 10,
                    'headers' => array(
                        'Accept' => 'application/json'
                    )
                )
            );

            if (
                is_wp_error($remote)
                || 200 !== wp_remote_retrieve_response_code($remote)
                || empty(wp_remote_retrieve_body($remote))
            ) {
                return false;
            }

            set_transient($this->cache_key, $remote, DAY_IN_SECONDS);
        }

        $remote = json_decode(wp_remote_retrieve_body($remote));

        return $remote;
    }

    /**
     * Purge the cache after an update
     */
    public function purge($upgrader, $options) {
        if (
            $this->cache_allowed
            && 'update' === $options['action']
            && 'plugin' === $options['type']
        ) {
            // Clean the cache when new plugin version is installed
            delete_transient($this->cache_key);
        }
    }
}
