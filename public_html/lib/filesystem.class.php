<?php
class FileSystem {
	function upload($field = '', $dirPath = '', $maxSize = 100000, $allowed = array())
	{
		foreach ($_FILES[$field] as $key => $val){
			$$key = $val;
		}
		
		/*if ((!is_uploaded_file($tmp_name)) || ($error != 0) || ($size == 0) || ($size > $maxSize)){		
			return false;    // file failed basic validation checks
		}*/
		
		if ((is_array($allowed)) && (!empty($allowed)))
			if (!in_array($type, $allowed))  
				return false;    // file is not an allowed type
		
		do $path = rand(1, 9999) . "_" . strtolower(basename($name));		
		while (file_exists($dirPath . DIRECTORY_SEPARATOR . $path));

		if (move_uploaded_file($tmp_name, $dirPath . DIRECTORY_SEPARATOR . $path)){				
			return $path;
		}      

		return false;
	}
	
	function unzip($zipfile, $root, $hedef = '') {
        $zip = zip_open($zipfile);
        while($zip_icerik = zip_read($zip)):
            $zip_dosya = zip_entry_name($zip_icerik);
            if(strpos($zip_dosya, '.')):
                $hedef_yol = $root . $hedef .$zip_dosya;
                touch($hedef_yol);
                $yeni_dosya = fopen($hedef_yol, 'w+');
                fwrite($yeni_dosya, zip_entry_read($zip_icerik));
                fclose($yeni_dosya); 
            else:
                @mkdir($root . $hedef .$zip_dosya);
            endif;
        endwhile;
		
		return TRUE;
    }
	
	function deleteFile($file) {
		unlink($file);
	}
	
	function removeDirectory($directory, $empty = FALSE) {
		// if the path has a slash at the end we remove it here
		if (substr($directory, -1) == '/') {
			$directory = substr($directory, 0, -1);
		}

		// if the path is not valid or is not a directory ...
		if (!file_exists($directory) || !is_dir($directory))
		{
			// ... we return FALSE and exit the function
			return FALSE;

		// ... if the path is not readable
		} else if (!is_readable($directory)) {
			// ... we return FALSE and exit the function
			return FALSE;

		// ... else if the path is readable
		} else {

			// we open the directory
			$handle = opendir($directory);

			// and scan through the items inside
			while (FALSE !== ($item = readdir($handle))) {
				// if the filepointer is not the current directory
				// or the parent directory
				if ($item != '.' && $item != '..') {
					// we build the new path to delete
					$path = $directory . '/'. $item;

					// if the new path is a directory
					if(is_dir($path)) {
						// we call this function with the new path
						self::removeDirectory($path);

					// if the new path is a file
					} else {
						// we remove the file
						unlink($path);
					}
				}
			}
			// close the directory
			closedir($handle);

			// if the option to empty is not set to TRUE
			if ($empty == FALSE) {
				// try to delete the now empty directory
				if(!rmdir($directory)) {
					// return FALSE if not possible
					return FALSE;
				}
			}
			// return success
			return TRUE;
		}
	}
	
	function copyDirectory($source, $target) {
		if (is_dir($source)) {
			@mkdir($target);
			
			$d = dir($source);
			
			while (FALSE !== ($entry = $d->read())) {
				if ($entry != '.' && $entry != '..') {
					$Entry = $source . '/' . $entry; 
					
					if (is_dir($Entry)) {
						self::copyDirectory($Entry, $target . '/' . $entry);
					} else {
						copy($Entry, $target . '/' . $entry);
					}
				}
			}
	 
			$d->close();
		} else {
			copy($source, $target);
		}
	}
}
?>