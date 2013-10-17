<?php

class YourMember_xls {

	private $xls_string = '';
	public $row = 0;
	private $col = 0;
	private $filename = '';

	function __construct($filename = false) {
		if ($filename) {
			$this->filename = $this->check_extension($filename);
		} else {
			$this->filename = 'XLS_download_' . date('dmY') . '.xls';
		}
	}

	function filename($filename) {
		$this->filename = $this->check_extension($filename);
	}

	function check_extension($filename) {
		if (substr(strtolower($filename), strrpos($filename, '.'), 4) != '.xls') {
			$filename .= '.xls';
		}

		return $filename;
	}

	function end_row() {
		$this->row++;
		$this->col = 0;
	}

	function cell($value) {
		if (is_numeric($value)) {
			$this->xls_string .= pack("sssss", 0x203, 14, $this->row, $this->col, 0x0);
			$value = pack("d", $value);
		} else {
			if (is_array($value) || is_object($value)) {
				$value = json_encode($value);
			}
			$l = strlen($value);
			$this->xls_string .= pack("ssssss", 0x204, 8 + $l, $this->row, $this->col, 0x0, $l);
		}

		$this->xls_string .= $value;

		$this->col++;
	}

	function download() {
		header("Pragma: public");
		header("Expires: 0");
		header("Cache-Control: must-revalidate, post-check=0, pre-check=0");
		header("Content-Type: application/force-download");
		header("Content-Type: application/octet-stream");
		header("Content-Type: application/download");
		header("Content-Disposition: attachment; filename=\"" . $this->filename . "\"");
		header("Content-Transfer-Encoding: binary");

		echo pack("ssssss", 0x809, 0x8, 0x0, 0x10, 0x0, 0x0);
		echo $this->xls_string;
		echo pack("ss", 0x0A, 0x00);

		exit;
	}

	function download_from_array($array) {
		if (is_array($array)) {
			foreach ($array as $row) {
				foreach ($row as $data) {
					$this->cell($data);
				}

				$this->end_row();
			}

			$this->download();
		}

		return false;
	}

	function start() {
		return pack("ssssss", 0x809, 0x8, 0x0, 0x10, 0x0, 0x0);
	}
	function generate_data($array) {
		foreach ($array as $row) {
			foreach ($row as $data) {
				$this->cell($data);
			}
			$this->end_row();
		}
		return $this->xls_string;
	}
	function end() {
		return pack("ss", 0x0A, 0x00);
	}

}
