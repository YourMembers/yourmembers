<?php

class ym_dl_file_upload {

	var $the_file;
	var $the_temp_file;
	var $upload_dir;
	var $replace;
	var $do_filename_check;
	var $max_length_filename = 100;
	var $extensions;
	var $ext_string;
	var $http_error;
	var $rename_file; // if this var is true the file copy get a new name
	var $file_copy; // the new name
	var $message = array();
	var $create_directory = true;

	function ym_dl_file_upload() {
		$this->rename_file = false;
		$this->ext_string = "";
	}

	function show_error_string() {
		$msg_string = "";
		foreach ($this->message as $value) {
			$msg_string .= $value."<br />\n";
		}
		return $msg_string;
	}

	function set_file_name($new_name = "") { // this "conversion" is used for unique/new filenames
		if ($this->rename_file) {
			if ($this->the_file == "") return;
			$name = ($new_name == "") ? strtotime("now") : $new_name;
			sleep(3);
			$name = $name.$this->get_extension($this->the_file);
		} else {
			$name = str_replace(" ", "_", $this->the_file); // space will result in problems on linux systems
		}
		return $name;
	}

	function upload($to_name = "") {
		$new_name = $this->set_file_name($to_name);
		
		if ($this->check_file_name($new_name)) {
			if (is_uploaded_file($this->the_temp_file)) {
				$this->file_copy = $new_name;
				if ($this->move_upload($this->the_temp_file, $this->file_copy)) {
					$this->message[] = $this->error_text($this->http_error);
					if ($this->rename_file) $this->message[] = $this->error_text(16);
					return true;
				}
			} else {
				$this->message[] = $this->error_text($this->http_error);
				return false;
			}
		} else {
			return false;
		}
	}

	function check_file_name($the_name) {
		if ($the_name != "") {
			if (strlen($the_name) > $this->max_length_filename) {
				$this->message[] = $this->error_text(13);
				return false;
			} else {
				if ($this->do_filename_check == "y") {
					if (preg_match("/^[a-z0-9_]*\.(.){1,5}$/i", $the_name)) {
						return true;
					} else {
						$this->message[] = $this->error_text(12);
						return false;
					}
				} else {
					return true;
				}
			}
		} else {
			$this->message[] = $this->error_text(10);
			return false;
		}
	}

	function get_extension($from_file) {
		$ext = strtolower(strrchr($from_file,"."));
		return $ext;
	}

	//	function validateExtension() {
	//		$extension = $this->get_extension($this->the_file);
	//		$ext_array = $this->extensions;
	//
	//		if (count($ext_array)) {
	//			if (in_array($extension, $ext_array)) {
	//				return true;
	//			} else {
	//				return false;
	//			}
	//		} else {
	//			return true;
	//		}
	//	}

	// this method is only used for detailed error reporting
	function show_extensions() {
		$this->ext_string = implode(" ", $this->extensions);
	}

	function move_upload($tmp_file, $new_file) {
		if ($this->existing_file($new_file)) {
			$newfile = $this->upload_dir.$new_file;
			if ($this->check_dir($this->upload_dir)) {
				if (move_uploaded_file($tmp_file, $newfile)) {
					umask(0);
					chmod($newfile , 0644);
					return true;
				} else {
					return false;
				}
			} else {
				$this->message[] = $this->error_text(14);
				return false;
			}
		} else {
			$this->message[] = $this->error_text(15);
			return false;
		}
	}

	function check_dir($directory) {
		if (!is_dir($directory)) {
			if ($this->create_directory) {
				umask(0);
				@mkdir($directory, 0777, 1);
				return true;
			} else {
				return false;
			}
		} else {
			return true;
		}
	}

	function existing_file($file_name) {
		if ($this->replace == "y") {
			return true;
		} else {
			if (file_exists($this->upload_dir.$file_name)) {
				return false;
			} else {
				return true;
			}
		}
	}

	function get_uploaded_file_info($name) {
		$str = "File name: ".basename($name)."\n";
		$str .= "File size: ".filesize($name)." bytes\n";
		if (function_exists("mime_content_type")) {
			$str .= "Mime type: ".mime_content_type($name)."\n";
		}
		if ($img_dim = getimagesize($name)) {
			$str .= "Image dimensions: x = ".$img_dim[0]."px, y = ".$img_dim[1]."px\n";
		}
		return $str;
	}

	function del_temp_file($file) {
		$delete = @unlink($file);
		clearstatcache();
		if (@file_exists($file)) {
			$filesys = eregi_replace("/","\\",$file);
			$delete = @system("del $filesys");
			clearstatcache();
			if (@file_exists($file)) {
				$delete = @chmod ($file, 0644);
				$delete = @unlink($file);
				$delete = @system("del $filesys");
			}
		}
	}

	// this function creates a file field and if $show_alternate is true it will show a text field if the given file already exists
	// there is also a submit button to remove the text field value
	function create_file_field($element, $label = "", $length = 25, $show_replace = true, $replace_label = "Replace old file?", $file_path = "", $file_name = "", $show_alternate = false, $alt_length = 30, $alt_btn_label = "Delete image") {
		$field = ($label != "") ? "<label>".$label."</label>\n" : "";
		$file_field = "<input type=\"file\" name=\"".$element."\" size=\"".$length."\" />\n";
		$file_field .= ($show_replace) ? "<span>".$replace_label."</span><input type=\"checkbox\" name=\"replace\" value=\"y\" />" : "";
		if ($file_name != "" && $show_alternate) {
			$field .= "<input type=\"text\" name=\"".$element."\" size=\"".$alt_length."\" value=\"".$file_name."\" readonly=\"readonly\"";
			$field .= (!@file_exists($file_path.$file_name)) ? " title=\"".sprintf($this->error_text(17), $file_name)."\" />\n" : " />\n";
			$field .= "<input type=\"checkbox\" name=\"del_img\" value=\"y\" /><span>".$alt_btn_label."</span>\n";
		} else {
			$field .= $file_field;
		}
		return $field;
	}

	function error_text($err_num) {
		// start http errors
		$error[0] = __('File',"ym").": <b>".$this->the_file."</b> ".__('successfully uploaded!',"ym");
		$error[1] = __("The uploaded file exceeds the max. upload filesize directive in the server configuration.","ym");
		$error[2] = __("The uploaded file exceeds the MAX_FILE_SIZE directive that was specified in the html form.","ym");
		$error[3] = __("The uploaded file was only partially uploaded","ym");
		$error[4] = __("No file was uploaded","ym");
		// end  http errors
		$error[10] = __("Please select a file for upload.","ym");
		$error[11] = __("Only files with the following extensions are allowed:","ym")." <b>".$this->ext_string."</b>";
		$error[12] = __("Sorry, the filename contains invalid characters. Use only alphanumerical chars and separate parts of the name (if needed) with an underscore. <br>A valid filename ends with one dot followed by the extension.","ym");
		$error[13] = __("The filename exceeds the maximum length of ","ym").$this->max_length_filename.__("characters.","ym");
		$error[14] = __("Sorry, the upload directory doesn't exist!","ym");
		$error[15] = __("Sorry, a file with this name already exists: ","ym") . $this->the_file;
		$error[16] = __("The uploaded file is renamed to","ym")." <b>".$this->file_copy."</b>";
		$error[17] = __("The file %s does not exist.","ym");

		return $error[$err_num];
	}
}
