<?php

    require 'commons/config.php';

    /*
        Imports files into the IRODS server from the path specified
        and tags with filename, timestamp, size and directoryname
    */
    function importFilesIntoIROD($rootDirPath) {
        // check if the filepath is valid
        if(is_null($rootDirPath) || strlen($rootDirPath) == 0) {
            echo "Invalid filepath ". $srcFilePath. ". Please specify a correct root directory path .";
            return;
        }

        $then = microtime(true);
        $fileCount=0;

        // Read all the sub-directories and their corresponding files to a maxdepth of 1
        $rootDirFiles = scandir($rootDirPath);
        foreach($rootDirFiles as $rootDirFile) {
            $rootDirFilePath = $rootDirPath. DIRECTORY_SEPARATOR. $rootDirFile;
            // If directory - create similiar directory in IRODS and write all files in
            // sub-directory inside the corresponding collection in IRODS
            if(is_dir($rootDirFilePath)) {
                if(!isValidDir($rootDirFilePath)) {
                    continue;
                }
                $subDirFiles = scandir($rootDirFilePath);
                foreach($subDirFiles as $subDirFile) {
                    $subDirFilePath = $rootDirFilePath. DIRECTORY_SEPARATOR. $subDirFile;
                    if(is_file($subDirFilePath)) {
                        if(isValidFile($subDirFilePath)) {
                            // TODO : Upload this file to IRODS under apt subdirectory.
                            $fileCount++;
                        }
                    }

                }
            }
            // If file - simply write this file to root images collection in IRODS
            else {
                // TODO : Upload this file to IRODS server under root directory.
                $fileCount++;
            }
        }

        $now = microtime(true);
        $runtime = $now - $then;

        echo "\nWrote $fileCount files in $runtime seconds to IRODS server."; 

    }

    /*
        Checks if the directory name is valid. A valid directory name should have
        12 dashes in it.
    */
    function isValidDir($dirPath)
    {
        $isValidDir = False;

        $dirName = basename($dirPath);
        // TODO : Need a strong regex here.
        if(strpos($dirName, "-")) {
            $isValidDir = True;
        }

        return $isValidDir;
    }

    /*
        Checks if the file is valid. Ignore thumbs.db and symbolic link files.
    */
    function isValidFile($filePath) 
    {
        $isValidFile = True;
        if(is_link($filePath)) {
            $isValidFile = False;
        }
        else {
            $fileName = basename($filePath);
            if($fileName == "Thumbs.db") {
                $isValidFile = False;
            }
            else {
                $fileType = mime_content_type($filePath);
                $searchVal = strpos($fileType, "image");
                if($searchVal === False) {
                    $isValidFile = False;
                }
            }
        }

        return $isValidFile;
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

    // Test import of digital image files here
    $dir = "/mnt/irods_data";
    echo "\nTesting import of files from directory : ". $dir;
    importFilesIntoIROD($dir);
?>
