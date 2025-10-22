<?php
/**
 * Helper functions.
 *
 */
class teqb_Base {
	protected $config = [];
	/**
	 * Constructor
	 */
	public function __construct($config = []) {
		// Initialize config
		$this->config = $config;
	}
	
	/**
	 * Helper for using prefixes for all references.
	 */
	public function setPrefix($name) {
		return ((strpos($name, $this->config['prefix']) === 0) ? '' : $this->config['prefix']) . $this->config['prefixSeparator'] . $name;
	}

	/**
	 * Helper for getting prefixed options.
	 */
	public function getOption($name, $default = null) {
		$ret = get_option($this->setPrefix($name));
		if(!$ret && $default) {
			$ret = $default;
		}
		return $ret;
	}
	
	/**
	 * Helper for adding/updating prefixed options.
	 */
	public function setOption($name, $value) {
		return ($this->getOption($name, '') === '') ? 
			add_option($this->setPrefix($name), $value) : 
			update_option($this->setPrefix($name), $value);
	}

	protected function log_debug($message, $context = []) {
		if (!defined('WP_DEBUG') || !WP_DEBUG) {
			return;
		}

		if (!is_string($message)) {
			$message = print_r($message, true);
		}

		if (!empty($context)) {
			$message .= ' ' . wp_json_encode($context);
		}

		$upload_dir = wp_upload_dir();
		if (!empty($upload_dir['basedir'])) {
			$log_dir = trailingslashit($upload_dir['basedir']) . 'teqb-logs';
			if (!file_exists($log_dir)) {
				wp_mkdir_p($log_dir);
			}

			$log_file = trailingslashit($log_dir) . 'quote-builder.log';
			$timestamp = current_time('mysql');
			@file_put_contents($log_file, '[' . $timestamp . '] ' . $message . PHP_EOL, FILE_APPEND);
		} else {
			error_log('TEQB: ' . $message);
		}
	}
	
}
