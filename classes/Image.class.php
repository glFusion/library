<?php
/**
 * Class to handle images
 *
 * @author      Lee Garner <lee@leegarner.com>
 * @copyright   Copyright (c) 2009-2018 Lee Garner <lee@leegarner.com>
 * @package     library
 * @version     0.0.1
 * @license     http://opensource.org/licenses/gpl-2.0.php
 *              GNU Public License v2 or later
 * @filesource
 */
namespace Library;

/**
 * Image-handling class.
 * @package library
 */
class Image extends \upload
{
    /** Path to actual image (without filename)
     * @var string */
    var $pathImage;

    /** Path to image thumbnail (without filename)
     * @var string */
    //var $pathThumb;

    /** ID of the current ad
     * @var string */
    var $item_id;

    /** Array of the names of successfully uploaded files
     * @var array */
    var $goodfiles = array();

    /**
     * Constructor.
     *
     * @param   string  $item_id    Associated library item ID
     * @param   string  $varname    Form field name
     */
    public function __construct($item_id, $varname='photo')
    {
        global $_CONF_LIB, $_CONF;

        $this->setContinueOnError(true);
        $this->setLogFile('/tmp/warn.log');
        $this->setDebug(true);
        parent::__construct();

        // Before anything else, check the upload directory
        if (!$this->setPath($_CONF_LIB['image_dir'])) {
            return;
        }
        $this->item_id = trim($item_id);
        $this->pathImage = $_CONF_LIB['image_dir'];
        //$this->pathThumb = $this->pathImage . '/thumbs';
        $this->setAllowedMimeTypes(array(
                'image/pjpeg' => '.jpg,.jpeg',
                'image/jpeg'  => '.jpg,.jpeg',
        ));
        $this->setMaxFileSize($_CONF_LIB['max_image_size']);
        $this->setMaxDimensions(
                $_CONF_LIB['img_max_width'],
                $_CONF_LIB['img_max_height']
        );
        $this->setAutomaticResize(true);
        $this->setFieldName($varname);

        $filenames = array();
        for ($i = 0; $i < count($_FILES[$varname]['name']); $i++) {
            $filenames[] = $this->item_id . '_' . rand(100,999) . '.jpg';
        }
        $this->setFileNames($filenames);
    }


    /**
     * Upload the files.
     * Calls the parent uploader to handle the upload and then sets the
     * image information in the DB.
     */
    public function uploadFiles()
    {
        global $_TABLES;

        parent::uploadFiles();
        foreach ($this->_uploadedFiles as $idx=>$filepath) {
            $filename = isset($this->_fileNames[$idx]) ? $this->_fileNames[$idx] : '';
            if (!empty($filename)) {
                $sql = "INSERT INTO {$_TABLES['library.images']}
                            (item_id, filename)
                        VALUES (
                            '{$this->item_id}', '".
                            DB_escapeString($filename)."'
                        )";
                $result = DB_query($sql);
                if (!$result) {
                    $this->addError("uploadFiles() : Failed to insert {$filename}");
                }
            }
        }
    }


    /**
     * Delete the current image from disk.
     */
    public function Delete()
    {
        // If we're deleting from disk also, get the filename and
        // delete it and its thumbnail from disk.
        if ($this->filename == '') {
            return;
        }
        $this->_deleteOneImage($this->pathImage);
    }


    /**
     * Delete a single image using the current name and supplied path
     *
     * @param   string  $imgpath    Path to file
     */
    private function _deleteOneImage($imgpath)
    {
        if (file_exists($imgpath . '/' . $this->filename))
            unlink($imgpath . '/' . $this->filename);
    }


    /**
     * Handles the physical file upload and storage.
     * If the image isn't validated, the upload doesn't happen.
     *
     * @param   array   $file   $_FILES array
     */
    public function Upload($file)
    {
        if (!is_array($file))
            return "Invalid file given to Upload()";

        $msg = $this->Validate($file);
        if ($msg != '')
            return $msg;

        $this->filename = $this->item_id . '.' . rand(10,99) . $this->filetype;

        if (!@move_uploaded_file($file['tmp_name'],
                $this->pathImage . '/' . $this->filename)) {
            return 'upload_failed_msg';
        }

        // Create the display and thumbnail versions.  Errors here
        // aren't good, but aren't fatal.
        $this->ReSize('thumb');
        $this->ReSize('disp');
    }


    /**
     * Validate the uploaded image, checking for size constraints and other errors
     * @param   array   $file   $_FILES array
     * @return  boolean         True if valid, False otherwise
     */
    private function Validate($file)
    {
        if (!is_array($file))
            return;

        $msg = '';
        // verify that the image is a jpeg or other acceptable format.
        // Don't trust user input for the mime-type
        if (function_exists('exif_imagetype')) {
            switch (exif_imagetype($file['tmp_name'])) {
            case IMAGETYPE_JPEG:
                $this->filetype = 'jpg';
                $filetype_mime = 'image/jpeg';
                break;
            default:    // other
                $msg .= 'upload_invalid_filetype';
                break;
            }
        } else {
            return "System Error: Missing exif_imagetype function";
        }

        // Now check for error messages in the file upload: too large, etc.
        switch ($file['error']) {
        case UPLOAD_ERR_OK:
            if ($file['size'] > $_CONF['max_image_size']) {
                $msg .= "<li>upload_too_big'</li>\n";
            }
            break;
        case UPLOAD_ERR_INI_SIZE:
        case UPLOAD_ERR_FORM_SIZE:
            $msg = "<li>upload_too_big</li>\n";
            break;
        case UPLOAD_ERR_NO_FILE:
            $msg = "<li>upload_missing_msg</li>\n";
            break;
        default:
            $msg = "<li>upload_failed_msg</li>\n";
            break;
        }
        return $msg;
    }

}   // class Image

?>
