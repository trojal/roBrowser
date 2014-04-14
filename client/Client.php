<?php

/**
* @fileoverview Client - File Manager
* @author Vincent Thibault (alias KeyWorld - Twitter: @robrowser)
* @version 1.5.1
*/


final class Client
{
	/**
	 * @var {string} client path
	 */
	static public $path = '';


	/**
	 * @var {string} data.ini file
	 */
	static public $data_ini = '';


	/**
	 * @var {Array} grf list
	 */
	static private $grfs = array();


	/**
	 * @var {boolean} auto extract mode
	 */
	static public $AutoExtract = false;


	/**
	 * Initialize client file
	 */
	static public function init()
	{
		if (empty(self::$data_ini)) {
			Debug::write('No DATA.INI file defined in configs ?');
			return;
		}

		$path = self::$path . self::$data_ini;

		if (!file_exists($path)) {
			Debug::write('File not found: ' . $path, 'error');
			return;
		}


		if (!is_readable($path)) {
			Debug::write('Can\'t read file: ' . $path, 'error');
			return;
		}

		// Setup GRF context
		$data_ini = parse_ini_file($path, true );
		$grfs     = array();
		$info     = pathinfo($path);
		ksort($data_ini['Data']);

		Debug::write('File ' . $path . ' loaded.', 'success');
		Debug::write('GRFs to use :', 'info');

		// Open GRFs files
		foreach ($data_ini['Data'] as $index => $grf_filename) {
			Debug::write($index . ') ' . $info['dirname'] . '/' . $grf_filename);

			self::$grfs[$index] = new Grf($info['dirname'] . '/' . $grf_filename);
			self::$grfs[$index]->filename = $grf_filename;
		}
	}



	/**
	 * Get a file from client, search it on data folder first and then on grf
	 *
	 * @param {string} file path
	 * @return {boolean} success
	 */
	static public function getFile($path)
	{
		$local_path  = self::$path;
		$local_path .= str_replace('\\', '/', $path );
		$grf_path    = str_replace('/', '\\', $path );

		Debug::write('Searching file ' . $path . '...', 'title');

		// Read data first
		if (file_exists($local_path) && !is_dir($local_path) && is_readable($local_path)) {
			Debug::write('File found at ' . $local_path, 'success');

			// Store file
			if(self::$AutoExtract) {
				return self::store( $path, file_get_contents($local_path) );
			}

			return file_get_contents($local_path);
		}
		else {
			Debug::write('File not found at ' . $local_path);
		}

		foreach (self::$grfs as $grf) {

			// Load GRF just if needed
			if (!$grf->loaded) {
				Debug::write('Loading GRF: ' . $grf->filename, 'info');
				$grf->load();
			}

			// If file is found
			if ($grf->getFile($grf_path, $content)) {
				if (self::$AutoExtract) {
					return self::store( $path, $content );
				}

				return $content;
			}
		}

		return false;
	}



	/**
	 * Storing file in data folder (convert it if needed)
	 *
	 * @param {string} save to path
	 * @param {string} file content
	 * @return {string} content
	 */
	static public function store( $path, $content )
	{
		$path         = utf8_encode($path);
		$current_path = self::$path;
		$local_path   = $current_path . str_replace('\\', '/', $path );
		$directories  = explode('\\', $path );
		array_pop($directories);

		// Creating directories
		foreach ($directories as $dir) {
			$current_path .= $dir . DIRECTORY_SEPARATOR;

			if (!file_exists($current_path)) {

				// Need write access
				if (!is_writable($current_path)) {
					Debug::write('Need write permission at ' . $current_path, 'error');
					return $content;
				}

				mkdir( $current_path );
			}
		}

		// storing bmp images as png
		if (pathinfo($path, PATHINFO_EXTENSION) === 'bmp')  {
			$img  = imagecreatefrombmpstring( $content );
			$path = str_replace('.bmp', '.png', $local_path);
			imagepng($img, $path );
			return file_get_contents( $path );
		}

		// Saving file
		file_put_contents( $local_path, $content);
		return $content;
	}



	/**
	 * Search files (only work in GRF)
	 *
	 * @param {string} regex
	 * @return {Array} file list
	 */
	static public function search($filter) {
		$out = array();

		foreach (self::$grfs as $grf) {

			// Load GRF only if needed
			if (!$grf->loaded) {
				$grf->load();
			}

			// Search
			$list = $grf->search($filter);

			// Merge
			$out  = array_unique( array_merge($out, $list) );
		}

		//sort($out);
		return $out;
	}
}