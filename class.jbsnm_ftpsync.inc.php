<?php

/**
 * JBSNM FTPSync
 *
 * @author Juergen Schwind
 * @copyright Copyright (c) JBS New Media GmbH - Juergen Schwind (https://jbs-newmedia.com)
 * @package JBSNM FTPSync
 * @link https://jbs-newmedia.com
 * @license http://www.gnu.org/licenses/gpl.html GNU General Public License
 */
class JBSNM_FTPSync {

	/**
	 *
	 * @var string
	 */
	const version='1.0.0';

	/**
	 *
	 * @var string
	 */
	var $conn_id='';

	/**
	 *
	 * @var array
	 */
	var $log=[];

	/**
	 *
	 * @var integer
	 */
	var $error_number=0;

	/**
	 *
	 * @var string
	 */
	var $error_info='';

	/**
	 *
	 * @return string
	 */
	public function getVersion(): string {
		return $this->version;
	}

	/**
	 *
	 * @return int
	 */
	public function getErrorNumber(): int {
		return $this->error_number;
	}

	/**
	 *
	 * @return string
	 */
	public function getErrorInfo(): string {
		return $this->error_info;
	}

	/**
	 *
	 * @param string $ftp_server
	 * @param string $ftp_user
	 * @param string $ftp_pass
	 * @param bool $ftp_secure
	 * @param int $ftp_port
	 * @param int $ftp_timeout
	 * @return boolean
	 */
	public function connect(string $ftp_server, string $ftp_user, string $ftp_pass, bool $ftp_secure=true, int $ftp_port=21, int $ftp_timeout=90) {
		if ($ftp_secure===true) {
			$this->conn_id=@ftp_ssl_connect($ftp_server, $ftp_port, $ftp_timeout);
		} else {
			$this->conn_id=@ftp_connect($ftp_server, $ftp_port, $ftp_timeout);
		}

		if (!$this->conn_id) {
			$this->error_number=1001;
			$this->error_info='Couldn\'t connect to '.$ftp_server;
			return false;
		}

		if (!@ftp_login($this->conn_id, $ftp_user, $ftp_pass)) {
			$this->error_number=1002;
			$this->error_info='Couldn\'t connect as '.$ftp_user;
			return false;
		}

		return true;
	}

	/**
	 *
	 * @param boolean $ftp_pasv
	 * @return bool
	 */
	public function setPasv($ftp_pasv=true): bool {
		if (!$this->conn_id) {
			$this->error_number=1004;
			$this->error_info='Not connected';
			return false;
		}
		return ftp_pasv($this->conn_id, $ftp_pasv);
	}

	/**
	 *
	 * @param string $remote_dir
	 * @param string $backup_dir
	 * @return bool|NULL
	 */
	public function sync(string $remote_dir, string $backup_dir): bool {
		if (!$this->conn_id) {
			$this->error_number=1004;
			$this->error_info='Not connected';
			return false;
		}
		$this->log=[];
		$remote_files=ftp_rawlist($this->conn_id, $remote_dir);

		if ($remote_files==[]) {
			$this->error_number=1003;
			$this->error_info='Couldn\'t sync any files';
			return false;
		}

		if (!is_dir($backup_dir)) {
			mkdir($backup_dir, 0777, true);
		}

		foreach ($remote_files as $remote_file) {
			$chunks=preg_split("/\s+/", $remote_file);
			if (count($chunks)>9) {
				$_chunks=$chunks;
				array_splice($_chunks, 0, 8);
				$chunks[8]=implode(' ', $_chunks);
			}
			list($item['rights'], $item['number'], $item['user'], $item['group'], $item['size'], $item['month'], $item['day'], $item['time'], $item['name'])=$chunks;
			$item['type']=$chunks[0]{0}==='d'?'directory':'file';
			if (in_array($item['name'], ['.', '..'])) {
				continue;
			}
			if ($item['type']=='directory') {
				$this->sync($remote_dir.'/'.$item['name'], $backup_dir.'/'.$item['name']);
			} else {
				$remote_file=$remote_dir.'/'.$item['name'];
				$backup_file=$backup_dir.'/'.$item['name'];
				if (file_exists($backup_file)) {
					$remote_time=ftp_mdtm($this->conn_id, $remote_file);
					$backup_time=filemtime($backup_file);
					$remote_size=ftp_size($this->conn_id, $remote_file);
					$backup_size=filesize($backup_file);
					if (($remote_size==$backup_size)&&($remote_time==$backup_time)) {
						continue;
					} else {
						@unlink($backup_size);
					}
				}
				$output=ftp_get($this->conn_id, $backup_file, $remote_file, FTP_BINARY);
				touch($backup_file, ftp_mdtm($this->conn_id, $remote_file));
				if ($output==1) {
				} else {
					$this->log[]='Couldn\'t sync file "'.$remote_file.'"';
				}
			}
		}
		return true;
	}

	/**
	 *
	 * @return array
	 */
	public function getLog(): array {
		return $this->log;
	}

	/**
	 *
	 * @return bool
	 */
	public function close(): bool {
		if (!$this->conn_id) {
			$this->error_number=1004;
			$this->error_info='Not connected';
			return false;
		}
		return ftp_close($this->conn_id);
	}

}

?>
