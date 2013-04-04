<?php

    require 'commons/config.php';
    require 'util/file_utils.php';

    /*
        Imports files into the IRODS server from the path specified
        and tags with filename, timestamp, size and directoryname
    */
    function importFilesIntoIROD($irodsConn, $rootDirPath, $irodsRootDirPath, $irodsResc, $isMetaDataOnly) {
        // check if the filepath is valid
        if(is_null($rootDirPath) || strlen($rootDirPath) == 0) {
            echo "Invalid filepath ". $srcFilePath. ". Please specify a correct root directory path .";
            return;
        }

        $then = microtime(true);
        $fileCount=0;
        $dirCount=0;

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

                $dirCount++;
                $irodsDirPath = $irodsRootDirPath . basename($rootDirFilePath). "/";
                
                $subDirFiles = scandir($rootDirFilePath);
                foreach($subDirFiles as $subDirFile) {
                    $subDirFilePath = $rootDirFilePath. DIRECTORY_SEPARATOR. $subDirFile;
                    if(is_file($subDirFilePath)) {
                        if(isValidFile($subDirFilePath)) {
                            writeAndReplFileToIRODS($irodsConn, $subDirFilePath, $irodsDirPath, $irodsResc, $isMetaDataOnly);
                            $fileCount++;
                        }
                    }

                }
            }
            // If file - simply write this file to root images collection in IRODS
            else {
                if(isValidFile($rootDirFilePath)) {
                    $irodsDirPath = DOANEIMAGES;
                    writeAndReplFileToIRODS($irodsConn, $rootDirFilePath, $irodsDirPath, $irodsDesc, $isMetaDataOnly);
                    $fileCount++;
                }
            }
        }

        $now = microtime(true);
        $runtime = $now - $then;

        echo "\nWrote $fileCount files and $dirCount directories in $runtime seconds to IRODS server."; 

    }

    /**
        Write file to IRODS and replicate it to destination.
    */
    function writeAndReplFileToIRODS($irodsConn, $srcFilePath, $irodsDirPath, $irodsResc, $isMetaDataOnly)
    {
        echo "\n Src Dir : " . $srcFilePath . " , IRODS Dir : " . $irodsDirPath;
        $resOp = writeToIRODS($irodsConn, $srcFilePath, $irodsDirPath, $irodsResc, $isMetaDataOnly);
        if($resOp == false) {
            echo "\n IRODS write failed for " . $srcFilePath;
            //echo "\n Replicating file " . basename($srcFilePath) . " to IRODS.";
            //$resOp = icmdReplFile(IRODSSRCRESC, IRODSDESTRESC, $srcFilePath);
            //if($resOp == true) {
               // echo "\n IRODS replication successful.";
            //}
        }
    }

    /*
        Checks if the directory name is valid. A valid directory should not have
        any sub-directories inside it.
    */
    function isValidDir($dirPath)
    {
        $isValidDir = True;

        // Generate all files in this directory and check if any file is a directory.
        // If there is sub-directory present return false.
        $dirFiles = scandir($dirPath);
        foreach($dirFiles as $dirFile) {
            if(!in_array($dirFile, array(".", ".."))) {
                $filePath = $dirPath. DIRECTORY_SEPARATOR. $dirFile;
                if(is_dir($filePath)) {
                    $isValidDir = False;
                    break;
                }
            }
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

    // Test import of digital image files here
    $srcRootDir   = "/mnt/irods_data";
    $irodsRootDir = DOANEIMAGES;
    $irodsResc    = IRODSRESC;
    $isMetaDataOnly = true;

    echo "\nTesting import of files from directory : ". $srcRootDir;
    $irodsConn = new RODSAccount("localhost", 1247, "irods_user", "irods_user");
    importFilesIntoIROD($irodsConn, $srcRootDir, $irodsRootDir, $irodsResc, $isMetaDataOnly);

?>
