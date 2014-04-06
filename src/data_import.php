<?php

    require 'commons/config.php';
    require 'util/file_utils.php';

    // List of source directories that have alredy been processed.
    //define("DIR_PROCESSED_LIST", "/home/skprasad/IRODS/code/IRODS/src/done_dir.txt");
    define("DIR_PROCESSED_LIST", __DIR__.'/../resources/done_dir.txt');

    /*
        Imports files into the IRODS server from the path specified
        and tags with filename, timestamp, size and directoryname
    */
    function importFilesIntoIROD($irodsConn, $rootDirPath, $irodsRootDirPath, $irodsResc, $isMetaDataOnly) {
        echo "\nProcessed : "  . DIR_PROCESSED_LIST;
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
                if(!isValidDir($rootDirFilePath) || isDirAlreadyProcessed($rootDirFilePath)) {
                    continue;
                }

		        echo "\n Loading images from directory : " . $rootDirFilePath;
                $dirCount++;
                $irodsDirPath = $irodsRootDirPath . basename($rootDirFilePath). "/";
                
                $subDirFiles = scandir($rootDirFilePath);
                foreach($subDirFiles as $subDirFile) {
                    $subDirFilePath = $rootDirFilePath. DIRECTORY_SEPARATOR. $subDirFile;
                    if(is_file($subDirFilePath)) {
                        if(isValidFile($subDirFilePath)) {
                            echo "\n File : " . $subDirFilePath;
                            //writeAndReplFileToIRODS($irodsConn, $subDirFilePath, $irodsDirPath, $irodsResc, $isMetaDataOnly);
                            $fileCount++;
                        }
                    }

                }
            }
            // If file - simply write this file to root images collection in IRODS
            else {
                if(isValidFile($rootDirFilePath)) {
                    $irodsDirPath = $irodsRootDirPath;
                    writeAndReplFileToIRODS($irodsConn, $rootDirFilePath, $irodsDirPath, $irodsDesc, $isMetaDataOnly);
                    $fileCount++;
                }
            }

            echo "\n Processed directory " . $rootDirFilePath . " into IRODS server.";
            addDirToProcessedList($rootDirFilePath);
        }

        $now = microtime(true);
        $runtime = $now - $then;

        echo "\nWrote $fileCount files and $dirCount directories in $runtime seconds to IRODS server."; 

    }

    /*
        Often during processing of large datasets, this script has died prematurely leading to 
        iterating over all the files from scratch to see if they have already been added to IRODS
        server. This takes sufficient time. This solution is a simple hack where I record all the
        processed directories in a file and then just do a lookup on that file to see if that
        directory has already been processed.
    */
    function addDirToProcessedList($dirPath)
    {
        $fp = fopen(DIR_PROCESSED_LIST, "a");
        $out = fwrite($fp, $dirPath . PHP_EOL);
        fclose($fp);
        
        return $out;    	
    }

    /*
        Checks if this directory has already been processed in previous runs.
    */
    function isDirAlreadyProcessed($dirPath)
    {   
        $isDirProcessed = false;
       
        $fp = fopen(DIR_PROCESSED_LIST, "r");
        while ( ! feof( $fp ) ) {
            $line = fgets( $fp);
            $line = trim(preg_replace('/\s+/', ' ', $line));
            if($line == $dirPath) {
                $isDirProcessed = true;
                echo "\n$dirPath has already been processed.";
                break;
            }
        }
        fclose($fp);

        return $isDirProcessed;
    }

    /**
     *   Write file to IRODS and replicate it to destination.
     */
    function writeAndReplFileToIRODS($irodsConn, $srcFilePath, $irodsDirPath, $irodsResc, $isMetaDataOnly)
    {
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

    // Various paramaters to import source files into IRODS server. Import this from command line
    $srcRootDir   = $argv[1]; //"/mnt/doane/share/doane/RIL_Data";
    $irodsRootDir = $argv[2]; //DOANEIMAGES;
    $irodsResc    = $argv[3]; //IRODSRESC;
    $isMetaDataOnly = $argv[4]; //false;
    $irods_server = $argv[5];
    $irods_user = $argv[6];
    $irods_pwd = $argv[7];

    var_dump($argv);

    //$irodsConn = new RODSAccount("198.51.254.78", 1247, "irods_user", "irods_user");
    $irodsConn = new RODSAccount($irods_server, 1247, $irods_user, $irods_pwd);

    echo "\nImporting image files from directory : ". $srcRootDir . " to IRODS server." ;
    importFilesIntoIROD($irodsConn, $srcRootDir, $irodsRootDir, $irodsResc, $isMetaDataOnly);
    echo "\nImported all files from source directory to IRODS server !! ";

?>
