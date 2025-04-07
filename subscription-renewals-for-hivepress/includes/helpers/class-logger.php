<?php
namespace HivePress\SubscriptionRenewals\Helpers;

defined('ABSPATH') || exit;

class Logger {
    protected $log_file;
    protected $max_file_size = 5242880; // 5MB

    public function __construct() {
        $upload_dir = wp_upload_dir();
        $this->log_file = $upload_dir['basedir'] . '/hpsr-logs.txt';

        if (!is_dir(dirname($this->log_file))) {
            wp_mkdir_p(dirname($this->log_file));
        }

        global $wp_filesystem;
        if (!$wp_filesystem) {
            require_once ABSPATH . 'wp-admin/includes/file.php';
            WP_Filesystem();
        }

        $htaccess_file = dirname($this->log_file) . '/.htaccess';
        if ($wp_filesystem && !$wp_filesystem->exists($htaccess_file)) {
            $wp_filesystem->put_contents($htaccess_file, "Deny from all\n", FS_CHMOD_FILE);
        }

        if ($wp_filesystem && !$wp_filesystem->exists($this->log_file)) {
            $wp_filesystem->put_contents($this->log_file, "=== Subscription Renewals Log ===\n", FS_CHMOD_FILE);
        }
    }

    public function log($message, $level = 'info', $force = false) {
        $options = get_option('hpsr_settings', []);
        if (!$force && $level === 'debug' && empty($options['debug_mode'])) {
            return false;
        }

        $this->maybe_rotate_log();
        $timestamp = current_time('Y-m-d H:i:s');
        $entry = "[$timestamp] [$level] $message\n";

        global $wp_filesystem;
        if ($wp_filesystem && $wp_filesystem->exists($this->log_file)) {
            $content = $wp_filesystem->get_contents($this->log_file);
            return $wp_filesystem->put_contents($this->log_file, $content . $entry, FS_CHMOD_FILE);
        }
        return false;
    }

    public function get_logs($lines = 0) {
        global $wp_filesystem;
        if (!$wp_filesystem || !$wp_filesystem->exists($this->log_file)) {
            return '';
        }

        $content = $wp_filesystem->get_contents($this->log_file);
        if (!$content || $lines <= 0) {
            return $content;
        }

        $log_lines = explode("\n", $content);
        return implode("\n", $lines >= count($log_lines) ? $log_lines : array_slice($log_lines, -$lines));
    }

    public function clear_logs() {
        global $wp_filesystem;
        if (!$wp_filesystem || !$wp_filesystem->exists($this->log_file)) {
            return false;
        }

        $timestamp = current_time('Y-m-d H:i:s');
        $header = "=== Subscription Renewals Log Cleared at $timestamp ===\n";
        $result = $wp_filesystem->put_contents($this->log_file, $header, FS_CHMOD_FILE);
        $result && $this->log("Log file cleared by admin.", 'info', true);
        return $result;
    }

    protected function maybe_rotate_log() {
        global $wp_filesystem;
        if (!$wp_filesystem || !$wp_filesystem->exists($this->log_file) || $wp_filesystem->size($this->log_file) <= $this->max_file_size) {
            return;
        }

        $timestamp = current_time('Y-m-d-H-i-s');
        $backup_file = $this->log_file . '.' . $timestamp . '.bak';
        $wp_filesystem->move($this->log_file, $backup_file, true);
        $wp_filesystem->put_contents($this->log_file, "=== Subscription Renewals Log (Rotated at $timestamp) ===\n", FS_CHMOD_FILE);

        $backups = glob($this->log_file . '.*.bak');
        if (count($backups) > 5) {
            usort($backups, function($a, $b) {
                return filemtime($a) - filemtime($b);
            });
            foreach (array_slice($backups, 0, count($backups) - 5) as $file) {
                $wp_filesystem->delete($file);
            }
        }
    }
}