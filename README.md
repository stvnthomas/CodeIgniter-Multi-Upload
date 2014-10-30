# CodeIgniter Multi-Upload #
Extends native CodeIgniter Upload library to support multiple file uploading. Multiple upload functionality will fallback to CodeIgniters native upload if non-multiple upload is detected.

## Installation ##
Simply copy the **MY_Upload.php** file to your applications library directory.

## Configuration ##
Multiple upload functionality will fallback to CodeIgniters default **do_upload()** method so configuration is backwards compatible between **do_upload()** and the new **do_multi_upload()** method provided by this extension. However, if supplying new names for multiple uploaded items, you must configure them as an array for the **file_name** configuration option. Names will be processed in the same order as the **$_FILES** array is read, meaning the first file uploaded will be renamed to the first name supplied in the **file_name** configuration array and so-on. If the uploaded file count exceeds the configured **file_name** configuration array, those exceeding files will default to their given upload name.

	//HTML
		<form>
			<input type="file" name="files[]" multiple />
			<input type="submit" name="submit" value="submit" />
		</form>
		
	//PHP
		//Configure upload.
		$this->upload->initialize(array(
			"file_name"		=> array("file_1.ext", "file_2.ext", "file_3.ext"),
			"upload_path"	=> "/path/to/upload/to/"
		));
		
		//Perform upload.
		if($this->upload->do_multi_upload("files")) {
			//Code to run upon successful upload.
		}
		
## do_multi_upload($field) ##
The **do_multi_upload()** method is referenced in the same way as CodeIgniters **do_upload()** method but expects **$field** to reference a multiple file **$_FILES** array. If multiple files are not detected, **do_multi_upload** will fallback to CodeIgniters **do_upload()** method.

	//Using multiple inputs.
		//HTML
			<form>
				<input type="file" name="files[]" />
				<input type="file" name="files[]" />
				<input type="file" name="files[]" />
				<input type="submit" name="submit" value="submit" />
			</form>
		
		//PHP
			//Configure upload.
			$this->upload->initialize(array(
				"upload_path"	=> "/path/to/upload/to/"
			));
		
			//Perform upload.
			if($this->upload->do_multi_upload("files")) {
				//Code to run upon successful upload.
			}
			
	//Using HTML5 "multiple" attribute.
		//HTML
			<form>
				<input type="file" name="files[]" multiple />
				<input type="submit" name="submit" value="submit" />
			</form>
		
		//PHP
			//Configure upload.
			$this->upload->initialize(array(
				"upload_path"	=> "/path/to/upload/to/"
			));
		
			//Perform upload.
			if($this->upload->do_multi_upload("files")) {
				//Code to run upon successful upload.
			}		
		

## get_multi_upload_data() ##
The extended library also comes with a get_multi_upload_data() method that will return data about each uploaded file as a multi-dimensional array.

			//Perform upload.
			if($this->upload->do_multi_upload("files")) {
				//Print data for all uploaded files.
				print_r($this->upload->get_multi_upload_data());
			}
			
			//Possible output given files "file_1.jpg", "file_2.jpg" and "file_3.jpg" without renaming.
			Array(
				[0] => Array(
					[file_name] => file_1.jpg
					[file_type] => image/jpeg
					[file_path] => /path/to/file/
					[full_path] => /path/to/file/file_1.jpg
					[raw_name] => file_1
					[orig_name] => file_1.jpg
					[client_name] => file_1.jpg
					[file_ext] => .jpg
					[file_size] => 2182.91
					[is_image] => 1
					[image_width] => 1024
					[image_height] => 768
					[image_type] => jpeg
					[image_size_str] => width="1024" height="768"
				)
				
				[1] => Array(
					[file_name] => file_2.jpg
					[file_type] => image/jpeg
					[file_path] => /path/to/file/
					[full_path] => /path/to/file/file_2.jpg
					[raw_name] => file_2
					[orig_name] => file_2.jpg
					[client_name] => file_2.jpg
					[file_ext] => .jpg
					[file_size] => 67.58
					[is_image] => 1
					[image_width] => 1024
					[image_height] => 768
					[image_type] => jpeg
					[image_size_str] => width="1024" height="768"
				)
				
				[2] => Array(
					[file_name] => file_3.jpg
					[file_type] => image/jpeg
					[file_path] => /path/to/file/
					[full_path] => /path/to/file/file_3.jpg
					[raw_name] => file_3
					[orig_name] => file_3.jpg
					[client_name] => file_3.jpg
					[file_ext] => .jpg
					[file_size] => 517.97
					[is_image] => 1
					[image_width] => 1024
					[image_height] => 768
					[image_type] => jpeg
					[image_size_str] => width="1024" height="768"
				)
			)
