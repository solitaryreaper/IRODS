<?php

    require 'commons/config.php';

    /*
        Imports files into the IRODS server from the path specified
        and tags with filename, timestamp, size and directoryname
    */
    function importFilesIntoIROD($srcFilePath) {
        // check if the filepath is valid
        if(is_null($srcFilePath) || strlen($srcFilePath) == 0) {
            echo "Invalid filepath ". $srcFilePath. ". Please specify a correct filepath";
            return;
        }

        // iterate through all the files in the directory
        $files = array();
        $files = readFiles($srcFilePath, $files);
        
        echo "\n";
        foreach($files as $file=>$hash) {
            echo $file . "\n" ;
        }
        echo "\n";

    }

    /*
        Reads all the files recursively from the directory
    */

    function readFiles($dirPath, $allFiles) {
        $currDirFiles = scandir($dirPath);
        foreach($currDirFiles as $file) {
            if(!in_array($file, array(".", ".."))) {
                $filePath = $dirPath. DIRECTORY_SEPARATOR. $file;
                if(is_dir($filePath)) {
                    $allFiles = readFiles($filePath, $allFiles);
                }
                else {
                    $fileStats = stat($filePath);
                    $fileSize = $fileStats["size"];
                    $fileModTime = $fileStats["mtime"];
                    $fileAttrs = $file. "_".  $fileSize. "_" . $fileModTime. "_". $dirPath; 
                    $allFiles[$filePath] = md5($fileAttrs);
                    echo $fileAttrs . "\n";
                }
            }
        }

        return $allFiles;
    }

    // Test import of files here
    $dir = "/home/shishir/Data/Work/PA/IRODS/code/src";
    echo "Testing import of files from directory : ". $dir;
    importFilesIntoIROD($dir);
?>
