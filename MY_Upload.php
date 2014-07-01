<?php if(!defined("BASEPATH")){ exit("No direct script access allowed"); }

	/**
	 * Multi-Upload
	 * 
	 * Extends CodeIgniters native Upload class to add support for multiple
	 * uploads.
	 *
	 * @package		CodeIgniter
	 * @subpackage	Libraries
	 * @category	Uploads
	 * @author		Conveyor Group <steven@conveyorgroup.com>
	 * @link		https://github.com/stvnthomas/CodeIgniter-2.1-Multi-Upload
	 */
		class MY_Upload extends CI_Upload {
			
			
			/**
			 * Properties
			 */
			 	protected $_multi_upload_data			= array();
				protected $_multi_file_name_override	= "";
				
				
			/**
			 * Initialize preferences
			 *
			 * @access	public
			 * @param	array
			 * @return	void
			 */
				public function initialize($config = array()){
					//Upload default settings.
					$defaults = array(
									"max_size"			=> 0,
									"max_width"			=> 0,
									"max_height"		=> 0,
									"max_filename"		=> 0,
									"allowed_types"		=> "",
									"file_temp"			=> "",
									"file_name"			=> "",
									"orig_name"			=> "",
									"file_type"			=> "",
									"file_size"			=> "",
									"file_ext"			=> "",
									"upload_path"		=> "",
									"overwrite"			=> FALSE,
									"encrypt_name"		=> FALSE,
									"is_image"			=> FALSE,
									"image_width"		=> "",
									"image_height"		=> "",
									"image_type"		=> "",
									"image_size_str"	=> "",
									"error_msg"			=> array(),
									"mimes"				=> array(),
									"remove_spaces"		=> TRUE,
									"xss_clean"			=> FALSE,
									"temp_prefix"		=> "temp_file_",
									"client_name"		=> ""
								);
					
					//Set each configuration.
					foreach($defaults as $key => $val){
						if(isset($config[$key])){
							$method = "set_{$key}";
							if(method_exists($this, $method)){
								$this->$method($config[$key]);
							} else {
								$this->$key = $config[$key];
							}
						} else {
							$this->$key = $val;
						}
					}
					
					//Check if file_name was provided.
					if(!empty($this->file_name)){
						//Multiple file upload.
						if(is_array($this->file_name)){
							//Clear file name override.
							$this->_file_name_override = "";
							
							//Set multiple file name override.
							$this->_multi_file_name_override = $this->file_name;
						//Single file upload.
						} else {
							//Set file name override.
							$this->_file_name_override = $this->file_name;
							
							//Clear multiple file name override.
							$this->_multi_file_name_override = "";
						}
					}
				}
				
				
			/**
			 * File MIME Type
			 * 
			 * Detects the (actual) MIME type of the uploaded file, if possible.
			 * The input array is expected to be $_FILES[$field].
			 * 
			 * In the case of multiple uploads, a optional second argument may be
			 * passed specifying which array element of the $_FILES[$field] array
			 * elements should be referenced (name, type, tmp_name, etc).
			 *
			 * @access	protected
			 * @param	$file	array
			 * @param	$count	int
			 * @return	void
			 */
				protected function _file_mime_type($file, $count=0){
					//Mutliple file?
					if(is_array($file["name"])){
						$tmp_name = $file["tmp_name"][$count];
						$type = $file["type"][$count];
					//Single file.
					} else {
						$tmp_name = $file["tmp_name"];
						$type = $file["type"];
					}
					
					//We'll need this to validate the MIME info string (e.g. text/plain; charset=us-ascii).
					$regexp = "/^([a-z\-]+\/[a-z0-9\-\.\+]+)(;\s.+)?$/";
					
					/* Fileinfo Extension - most reliable method.
					 * 
					 * Unfortunately, prior to PHP 5.3 - it's only available as a PECL extension and the
					 * more convenient FILEINFO_MIME_TYPE flag doesn't exist.
					 */
					 	if(function_exists("finfo_file")){
					 		$finfo = finfo_open(FILEINFO_MIME);
							if(is_resource($finfo)){
								$mime = @finfo_file($finfo, $tmp_name);
								finfo_close($finfo);
								
								/* According to the comments section of the PHP manual page,
								 * it is possible that this function returns an empty string
								 * for some files (e.g. if they don't exist in the magic MIME database).
								 */
								 	if(is_string($mime) && preg_match($regexp, $mime, $matches)){
								 		$this->file_type = $matches[1];
										return;
								 	}
							}
					 	}
						
					/* This is an ugly hack, but UNIX-type systems provide a "native" way to detect the file type,
					 * which is still more secure than depending on the value of $_FILES[$field]['type'], and as it
					 * was reported in issue #750 (https://github.com/EllisLab/CodeIgniter/issues/750) - it's better
					 * than mime_content_type() as well, hence the attempts to try calling the command line with
					 * three different functions.
					 *
					 * Notes:
					 *	- the DIRECTORY_SEPARATOR comparison ensures that we're not on a Windows system
					 *	- many system admins would disable the exec(), shell_exec(), popen() and similar functions
					 *	  due to security concerns, hence the function_exists() checks
					 */
					 	if(DIRECTORY_SEPARATOR !== "\\"){
					 		$cmd = "file --brief --mime ".escapeshellarg($tmp_name)." 2>&1";
							
							if(function_exists("exec")){
								/* This might look confusing, as $mime is being populated with all of the output when set in the second parameter.
								 * However, we only neeed the last line, which is the actual return value of exec(), and as such - it overwrites
								 * anything that could already be set for $mime previously. This effectively makes the second parameter a dummy
								 * value, which is only put to allow us to get the return status code.
								 */
									$mime = @exec($cmd, $mime, $return_status);
									if($return_status === 0 && is_string($mime) && preg_match($regexp, $mime, $matches)){
										$this->file_type = $matches[1];
										return;
									}
							}
							
							if((bool)@ini_get("safe_mode") === FALSE && function_exists("shell_exec")){
								$mime = @shell_exec($cmd);
								if(strlen($mime) > 0){
									$mime = explode("\n", trim($mime));
									if(preg_match($regexp, $mime[(count($mime) - 1)], $matches)){
										$this->file_type = $matches[1];
										return;
									}
								}
							}
							
							if(function_exists("popen")){
								$proc = @popen($cmd, "r");
								if(is_resource($proc)){
									$mime = @fread($proc, 512);
									@pclose($proc);
									if($mime !== FALSE){
										$mime = explode("\n", trim($mime));
										if(preg_match($regexp, $mime[(count($mime) - 1)], $matches)){
											$this->file_type = $matches[1];
											return;
										}
									}
								}
							}
					 	}
						
						//Fall back to the deprecated mime_content_type(), if available (still better than $_FILES[$field]["type"])
						if(function_exists("mime_content_type")){
							$this->file_type = @mime_content_type($tmp_name);
							//It's possible that mime_content_type() returns FALSE or an empty string.
							if(strlen($this->file_type) > 0){
								return;
							}
						}
						
						//If all else fails, use $_FILES default mime type.
						$this->file_type = $type;
				}
				
				
			/**
			 * Set Multiple Upload Data
			 *
			 * @access	protected
			 * @return	void
			 */
				protected function set_multi_upload_data(){
					$this->_multi_upload_data[] = array(
						"file_name"			=> $this->file_name,
						"file_type"			=> $this->file_type,
						"file_path"			=> $this->upload_path,
						"full_path"			=> $this->upload_path.$this->file_name,
						"raw_name"			=> str_replace($this->file_ext, "", $this->file_name),
						"orig_name"			=> $this->orig_name,
						"client_name"		=> $this->client_name,
						"file_ext"			=> $this->file_ext,
						"file_size"			=> $this->file_size,
						"is_image"			=> $this->is_image(),
						"image_width"		=> $this->image_width,
						"image_height"		=> $this->image_height,
						"image_type"		=> $this->image_type,
						"image_size_str"	=> $this->image_size_str
					);
				}
				
				
			/**
			 * Get Multiple Upload Data
			 *
			 * @access	public
			 * @return	array
			 */
				public function get_multi_upload_data(){
					return $this->_multi_upload_data;
				}
				
				
			/**
			 * Multile File Upload
			 *
			 * @access	public
			 * @param	string
			 * @return	mixed
			 */
				public function do_multi_upload($field){
					//Clear multi_upload_data.
					$this->_multi_upload_data = array();
					
					//Is $_FILES[$field] set? If not, no reason to continue.
					if(!isset($_FILES[$field])){ return false; }
					
					//Is this really a multi upload?
					if(!is_array($_FILES[$field]["name"])){
						//Fallback to do_upload method.
						return $this->do_upload($field);
					}
					
					//Is the upload path valid?
					if(!$this->validate_upload_path()){
						//Errors will already be set by validate_upload_path() so just return FALSE
						return FALSE;
					}
					
					//Every file will have a separate entry in each of the $_FILES associative array elements (name, type, etc).
					//Loop through $_FILES[$field]["name"] as representative of total number of files. Use count as key in
					//corresponding elements of the $_FILES[$field] elements.
					foreach ($_FILES[$field]["name"] as $i => $v) {
						//Was the file able to be uploaded? If not, determine the reason why.
						if(!is_uploaded_file($_FILES[$field]["tmp_name"][$i])){
							//Determine error number.
							$error = (!isset($_FILES[$field]["error"][$i])) ? 4 : $_FILES[$field]["error"][$i];
							
							//Set error.
							switch($error){
								//UPLOAD_ERR_INI_SIZE
								case 1:
									$this->set_error("upload_file_exceeds_limit");
								break;
								//UPLOAD_ERR_FORM_SIZE
								case 2:
									$this->set_error("upload_file_exceeds_form_limit");
								break;
								//UPLOAD_ERR_PARTIAL
								case 3:
									$this->set_error("upload_file_partial");
								break;
								//UPLOAD_ERR_NO_FILE
								case 4:
									$this->set_error("upload_no_file_selected");
								break;
								//UPLOAD_ERR_NO_TMP_DIR
								case 6:
									$this->set_error("upload_no_temp_directory");
								break;
								//UPLOAD_ERR_CANT_WRITE
								case 7:
									$this->set_error("upload_unable_to_write_file");
								break;
								//UPLOAD_ERR_EXTENSION
								case 8:
									$this->set_error("upload_stopped_by_extension");
								break;
								default:
									$this->set_error("upload_no_file_selected");
								break;
							}
							
							//Return failed upload.
							return FALSE;
						}
						
						//Set current file data as class variables.
						$this->file_temp	= $_FILES[$field]["tmp_name"][$i];
						$this->file_size	= $_FILES[$field]["size"][$i];
						$this->_file_mime_type($_FILES[$field], $i);
						$this->file_type	= preg_replace("/^(.+?);.*$/", "\\1", $this->file_type);
						$this->file_type	= strtolower(trim(stripslashes($this->file_type), '"'));
						$this->file_name	= $this->_prep_filename($_FILES[$field]["name"][$i]);
						$this->file_ext		= $this->get_extension($this->file_name);
						$this->client_name	= $this->file_name;
						
						//Is the file type allowed to be uploaded?
						if(!$this->is_allowed_filetype()){
							$this->set_error("upload_invalid_filetype");
							return FALSE;
						}
						
						//If we're overriding, let's now make sure the new name and type is allowed.
						//Check if a filename was supplied for the current file. Otherwise, use it's given name.
						if(!empty($this->_multi_file_name_override[$i])){
							$this->file_name = $this->_prep_filename($this->_multi_file_name_override[$i]);
							
							//If no extension was provided in the file_name config item, use the uploaded one.
							if(strpos($this->_multi_file_name_override[$i], ".") === FALSE){
								$this->file_name .= $this->file_ext;
							//An extension was provided, lets have it!
							} else {
								$this->file_ext = $this->get_extension($this->_multi_file_name_override[$i]);
							}
							
							if(!$this->is_allowed_filetype(TRUE)){
								$this->set_error("upload_invalid_filetype");
								return FALSE;
							}
						}
						
						//Convert the file size to kilobytes.
						if($this->file_size > 0){
							$this->file_size = round($this->file_size/1024, 2);
						}
						
						//Is the file size within the allowed maximum?
						if(!$this->is_allowed_filesize()){
							$this->set_error("upload_invalid_filesize");
							return FALSE;
						}
						
						//Are the image dimensions within the allowed size?
						//Note: This can fail if the server has an open_basdir restriction.
						if(!$this->is_allowed_dimensions()){
							$this->set_error("upload_invalid_dimensions");
							return FALSE;
						}
						
						//Sanitize the file name for security.
						$this->file_name = $this->clean_file_name($this->file_name);
						
						//Truncate the file name if it's too long
						if($this->max_filename > 0){
							$this->file_name = $this->limit_filename_length($this->file_name, $this->max_filename);
						}
						
						//Remove white spaces in the name
						if($this->remove_spaces == TRUE){
							$this->file_name = preg_replace("/\s+/", "_", $this->file_name);
						}
						
						/* Validate the file name
						 * This function appends an number onto the end of
						 * the file if one with the same name already exists.
						 * If it returns false there was a problem.
						 */
							$this->orig_name = $this->file_name;
							if($this->overwrite == FALSE){
								$this->file_name = $this->set_filename($this->upload_path, $this->file_name);
								if($this->file_name === FALSE){
									return FALSE;
								}
							}
							
						/* Run the file through the XSS hacking filter
						 * This helps prevent malicious code from being
						 * embedded within a file.  Scripts can easily
						 * be disguised as images or other file types.
						 */
							if($this->xss_clean){
								if($this->do_xss_clean() === FALSE){
									$this->set_error("upload_unable_to_write_file");
									return FALSE;
								}
							}
							
						/* Move the file to the final destination
						 * To deal with different server configurations
						 * we'll attempt to use copy() first.  If that fails
						 * we'll use move_uploaded_file().  One of the two should
						 * reliably work in most environments
						 */
							if(!@copy($this->file_temp, $this->upload_path.$this->file_name)){
								if(!@move_uploaded_file($this->file_temp, $this->upload_path.$this->file_name)){
									$this->set_error("upload_destination_error");
									return FALSE;
								}
							}
						
						/* Set the finalized image dimensions
						 * This sets the image width/height (assuming the
						 * file was an image).  We use this information
						 * in the "data" function.
						 */
							$this->set_image_properties($this->upload_path.$this->file_name);
							
						//Set current file data to multi_file_upload_data.
						$this->set_multi_upload_data();
					}
					
					//Return all file upload data.
					return TRUE;
			}
		}
