<?php
/**
 * ****************************************************************
 * Function : upload_single_file
 * Purpose : Upload any type of file.
 * Created : 08-Aug-2019
 * Author : Pavan Sengar
 *
 * @param string $file_name:
 * @param string $upload_path:
 *            represent which directory you want to upload documents
 * @param string $file_type:
 *            it's represent upload file extension ie(pdf|doc|docx|PDF|DOC|DOCX)
 * @param string $file_size:
 *            represent upload file size in MB. Default size is 2MB.
 * @param boolean $isAlterUploadFileName:
 *            If we want to alter(insert timestamp) name of upload file then we have to set true
 * @param boolean $isHashDirectoryStructure:
 *            Its represent hash directory structure
 * @return array****************************************************************
 */
if (! function_exists('upload_single_file')) {

    function upload_single_file($file_name, $upload_path, $file_type, $file_size = 2, $isAlterUploadFileName = false, $isHashDirectoryStructure = false)
    {
        $obj = & get_instance();
        $timeStamp = time();
        $file_size = (int) $file_size;

        // $mb_file_size = 1048576; // 1 MB -> 1048576 byte
        $mb_file_size = 1024; // 1 KB -> 1024 byte
        $size_in_kb = (int) ((int) $mb_file_size * (int) $file_size);

        $config['upload_path'] = $upload_path;
        $config['allowed_types'] = $file_type;
        $config['max_size'] = $size_in_kb;

        $resume_upload_folder = $upload_path;
        if ($isHashDirectoryStructure === true) {
            $resume_upload_folder = manage_directory($upload_path);
            $config['upload_path'] = $resume_upload_folder;
        }
        /*
         * if (!file_exists($resume_upload_folder.'/.htaccess')) {
         * copy('upload/.htaccess', $resume_upload_folder.'/.htaccess');
         * }
         */
        if ($isAlterUploadFileName === true) {
            $path_parts = pathinfo($_FILES[$file_name]["name"]);
            $extension = $path_parts['extension'];
            $filename = strtolower($path_parts['filename']);
            $altfilename = getValidUploadFileName($filename) . "-" . $timeStamp . "." . $extension;
            $config['file_name'] = $altfilename;
        }

        $obj->load->library('upload', $config);
        if (! $obj->upload->do_upload($file_name)) {
            return $error = array(
                'error' => $obj->upload->display_errors(),
                'isUpload' => "false"
            );
        } else {
            $data = array(
                'upload_data' => $obj->upload->data()
            );
            $data['isUpload'] = "true";
            $data['full_file_path'] = $data['upload_data']['full_path'];
            $file_relative_path = $resume_upload_folder . $data['upload_data']['orig_name'];
            if ($isHashDirectoryStructure === true) {
                $file_relative_path = $resume_upload_folder . "/" . $data['upload_data']['orig_name'];
            }
            $data['file_relative_path'] = $file_relative_path;

            $data['file_name_wext'] = $data['upload_data']['client_name'];
            $data['cv_file_name'] = $data['upload_data']['orig_name'];
            $data['file_ext'] = ltrim($data['upload_data']['file_ext'], '.');
            $data['file_size'] = $data['upload_data']['file_size'];

            if (file_exists($data['upload_data']['full_path'])) {
                // chmod($data['upload_data']['full_path'], 0644);
            }
            // echo substr(sprintf('%o', fileperms($data['upload_data']['full_path'])), -4);
            return $data;
        }
    }
}

/**
 * ****************************************************************
 * Function : manage_directory
 * Purpose : Manage Directory Structure
 * Created : 08-Aug-2019
 * Author : Pavan
 * You candefine DIRECTORY_PERMISSION to 0777 in constant file
 * ****************************************************************
 */
if (! function_exists('manage_directory')) {

    function manage_directory($directory_name)
    {
        $random_number = $generate_hash = $parent_directory = $child = "";

        // Please define these given value in constant file.
        /*
         * define('HASHCODE_RANDOM_DIGIT', 4);
         * define('GENERATE_PARENT_FOLDER__FIRST_DIGIT', 2);
         * define('GENERATE_CHILD_FOLDER_NEXT_DIGIT', 2);
         *
         */
        $random_number = n_digit_random(HASHCODE_RANDOM_DIGIT);
        $generate_hash = generate_md5_hash($random_number);
        $parent_directory = substr($generate_hash, 0, GENERATE_PARENT_FOLDER__FIRST_DIGIT);
        $child = substr($generate_hash, GENERATE_PARENT_FOLDER__FIRST_DIGIT, GENERATE_CHILD_FOLDER_NEXT_DIGIT);
        // PARENT DIRECTORY
        if (! is_dir(ROOT_PATH . $directory_name . $parent_directory)) {
            mkdir(ROOT_PATH . $directory_name . $parent_directory, DIRECTORY_PERMISSION, true);
        }
        // CHILD DIRECTORY
        if (! is_dir(ROOT_PATH . $directory_name . $parent_directory . '/' . $child)) {
            mkdir(ROOT_PATH . $directory_name . $parent_directory . '/' . $child, DIRECTORY_PERMISSION, true);
        }
        return $directory_name . $parent_directory . '/' . $child;
    }
}

/**
 * ****************************************************************
 * Function : n_digit_random
 * Purpose : Generate random number
 * Created : 08-Aug-2019
 * Author : Pavan
 * ****************************************************************
 */
if (! function_exists('n_digit_random')) {

    function n_digit_random($digits)
    {
        $temp = "";
        for ($i = 0; $i < $digits; $i ++) {
            $temp .= rand(1, 9);
        }
        return (int) $temp;
    }
}
/**
 * ****************************************************************
 * Function : generate_md5_hash
 * Purpose : Generate md5 Hash
 * Created : 08-Aug-2019
 * Author : Pavan
 * ****************************************************************
 */
if (! function_exists('generate_md5_hash')) {

    function generate_md5_hash($digits)
    {
        return md5($digits);
    }
}

/**
 * ****************************************************************
 * Function : getValidUploadFileName
 * Purpose : This function is return valid file name
 * Created : 08-Aug-2019
 * Author : Pavan
 * ****************************************************************
 */
if (! function_exists('getValidUploadFileName')) {

    function getValidUploadFileName($fname)
    {
        $fname = trim($fname);
        $pattern = "[?() ??'\"\/&#\,\;\:\.$]";
        $valid_file = preg_replace("/$pattern/", "-", $fname);
        $valid_file = strtolower($valid_file);

        $valid_file = str_replace("[", "_", $valid_file);
        $valid_file = str_replace("]", "_", $valid_file);
        $valid_file = str_replace("{", "_", $valid_file);
        $valid_file = str_replace("}", "_", $valid_file);
        $valid_file = str_replace(" ", "_", $valid_file);

        $valid_file = str_replace("--", "-", $valid_file);
        $valid_file = str_replace(">", "", $valid_file);
        $valid_file = str_replace("<", "", $valid_file);
        $valid_file = str_replace("=", "-", $valid_file);
        $valid_file = str_replace("'", "-", $valid_file);
        $valid_file = str_replace('"', "-", $valid_file);
        $valid_file = str_replace("~", "-", $valid_file);
        $valid_file = str_replace("^", "-", $valid_file);
        $valid_file = str_replace("@", "", $valid_file);
        $valid_file = str_replace("!", "-", $valid_file);
        $valid_file = str_replace("&", "-", $valid_file);
        $valid_file = str_replace("*", "-", $valid_file);
        $valid_file = str_replace("+", "-", $valid_file);
        $valid_file = str_replace("?", "-", $valid_file);
        $valid_file = str_replace("(", "", $valid_file);
        $valid_file = str_replace(")", "", $valid_file);
        $valid_file = str_replace(",", "-", $valid_file);
        $valid_file = str_replace(":", "-", $valid_file);
        $valid_file = str_replace(";", "-", $valid_file);
        $valid_file = str_replace("$", "-", $valid_file);
        $valid_file = str_replace("#", "-", $valid_file);
        $valid_file = str_replace("%", "-", $valid_file);
        $valid_file = str_replace("__", "-", $valid_file);
        $valid_file = str_replace("__", "-", $valid_file);
        $valid_file = str_replace("__", "-", $valid_file);
        $valid_file = str_replace("--", "-", $valid_file);
        if (substr($valid_file, - 1) == '-') {
            $valid_file = substr($valid_file, 0, - 1);
        }
        return strtolower($valid_file);
    }
}
?>
