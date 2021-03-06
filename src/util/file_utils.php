<?php

    /**
    *   Contains the utility functions for performing all file functions in IRODS
    *   environment.
    */

    require_once(__DIR__.'/../commons/config.php');
    require_once(__DIR__.'/../util/icommand_utils.php');

    // Default IRODS constants
    define("IRODSZONE",		"spaldingZone");
    define("IRODSHOME", 	"/spaldingZone/home/irods_user/");
    define("DOANEIMAGES", 	IRODSHOME. "doane/images/");
    define("DOANEVIDEOS", 	IRODSHOME. "doane/videos/");
    define("IRODSRESC", 	"csirods1RescGroup");
    
    define("SIZE",      	"size");
    define("FILEPATH",  	"path");
    define("TIMESTAMP", 	"mtime");
    define("HASH",      	"hash");
    define("TRUE",      	"True");
    define("FALSE",     	"False");

    /*****************************  IRODS Writer APIs              ***********************/
    /* 
        Writes a file to IRODS Server.
        @irodsConn   - Connection to IRODS server.
        @srcFilePath - Path of file that has to be staged onto IRODS server.
        @irodsDir    - IRODS directory where this file has to be written.
        @irodsResc   - IRODS resource where this file has to be written.
        @isMetadataOnly - If true, only write metadata for the file . Else, 
                          write both data and metadata for the file.
    */
    function writeToIRODS($irodsConn, $srcFilePath, $irodsDir, $irodsResc, $isMetadataOnly) {
        try {
            $fileName = getFileNameFromPath($srcFilePath);
           
            // check if file already present in IRODS server
            $isFilePresent = isFilePresentInIRODS($irodsConn, $srcFilePath);

            // if file aleady present in IRODS and not modified at source, nothing to do
            $isFileModifiedAtSrc = false;
            if($isFilePresent) {
                $isFileModifiedAtSrc = isFileModifiedAtSource($irodsConn, $fileName);
                // Return if this file is already present in IRODS and has not been modified
                if(!$isFileModifiedAtSrc) {
                    echo "\nFile " . $fileName . " is already present in IRODS server !!";
                    return true;
                }
            }

            // Create the collection in IRODS if it doesn't already exists
            $retVal = icmdCreateDirectory($irodsDir);
            if(!$retVal) {
                echo "\nFailed to create the IRODS directory : " . $irodsDir;
                return $retVal;
            }
            $irodsFilePath = $irodsDir . $fileName;
            $irodsFile = new ProdsFile($irodsConn, $irodsFilePath); 

            $retVal = true; 
           
	    echo "\nSrc File Path : " . $srcFilePath . ", IRODS File Path : " . $irodsFilePath; 
            $isFileOpened = false;
            if(!$irodsFile->exists()) {
                $irodsFile->open("w+", $irodsResc);
                $isFileOpened = true;
            }

            // Add data to the file only if NON-META mode
            if(!$isMetadataOnly) {
                $retVal = icmdWriteFileToIRODS($srcFilePath, $irodsFilePath, $irodsResc);
            }

            if($isFileOpened) {
                $irodsFile->close();
            }

            if($retVal) {
                $isMetaAdded = addMetadataToFile($irodsConn, $srcFilePath, $irodsFile, $isFileModifiedAtSrc);
                if(!$isMetaAdded) {
                    echo "\nFailed to add metadata for file : " . $srcFilePath ;
                    $retVal = false;
                }
            }
        } 
        catch(RODSException $e) {
            printException("writeFileIntoIRODS", $e);
            $retVal = false;
        }

        return $retVal;
    }

    /* 
        Adds metadata to IRODS file
        @srcFilePath    - Path of the file at the source whose metadata has to be inserted into ICAT database
        @irodsFile      - Path of the file on the IRODS server
        @isMetaToBeUpdated - A flag to indicate that the metadata for this file has to be updated on the server.
    */
    function addMetadataToFile($irodsConn, $srcFilePath, $irodsFile, $isMetaToBeUpdated)
    {
        try {
            // Add the metadata for all the files
            $fileStats = getSrcFileStats($srcFilePath);
         
            $sizeMeta = new RODSMeta(SIZE, $fileStats[SIZE]);
            $pathMeta = new RODSMeta(FILEPATH, $fileStats[FILEPATH]);
            $timeMeta = new RODSMeta(TIMESTAMP, $fileStats[TIMESTAMP]);
            $hashMeta = new RODSMeta(HASH, $fileStats[HASH]);

            // If metadata for file has to be updated, delete the old metadata and re-insert new metadata.
            if($isMetaToBeUpdated) {
                $fileName = getFileNameFromPath($srcFilePath);
                try {
                    $files = findIRODSFilesByName($irodsConn, $fileName);
                    if(empty($files)) {
                        return false;
                    }

                    $metadatas = $files[0]->getMeta();
                    foreach($metadatas as $rodsmeta) {
                        $irodsFile->rmMeta($rodsmeta);
                    }
                }
                catch(RODSException $e) {
                    printException("removeMetadataToFile", $e);
                    return false;
                }
            }

            $irodsFile->addMeta($sizeMeta);
            $irodsFile->addMeta($pathMeta);
            $irodsFile->addMeta($timeMeta);
            $irodsFile->addMeta($hashMeta);
        }
        catch(RODSException $e) {
            printException("addMetadataToFile", $e);
            return false;
        }

        return true;
    }

    /****************************  IRODS Reader APIs ****************************************/
    /*
        Reads the metadata for a file from IRODS server
        @irodsConn      - Connection to the IRODS server
        @fileName       - Name of the file whose metadata has to be retrieved from ICAT database.
    */
    function readMetadataForFile($irodsConn, $fileName)
    {
        $files = findIRODSFilesByName($irodsConn, $fileName);
        if(empty($files)) {
            return NULL;
        }

        $metadata = array();
        foreach($files as $file) {
            $metadatas = $file->getMeta();
            $fileMetadata = array();
            foreach($metadatas as $meta) {
                $fileMetadata[$meta->name] = $meta->value;
            }

            $metadata[$fileName] = $fileMetadata;
        }

        return $metadata;
    }

    /*
        List all files in an IRODS directory
        @irodsConn      - Connection to the IRODS server
        @dirPath        - Directory on IRODS server for which files have to be listed
    */
    function listFiles($irodsConn, $dirPath)
    {
        $files = array();

        try {
            $irodsDir = new ProdsDir($irodsConn, $dirPath);
            $irodsDirFiles = $irodsDir->getAllChildren();

            if(empty($irodsDirFiles)) {
                return $files;
            }
        
            foreach($irodsDirFiles as $file) {
                $files[] = $file->getName();
            }
        }
        catch(RODSException $e) {
             printException("listFiles", $e);
        }

        return $files;
    }

    /********************* IRODS File Search APIs **********************/
    /*
        Returns the files with matching name from IRODS server 
        @irodsConn      - Connection to the IRODS server
        @$fileName      - Name of the file which has to be searched on IRODS server
    */
    function findIRODSFilesByName($irodsConn, $fileName)
    {
        $files = NULL;

        try {
            $irodsHome = new ProdsDir($irodsConn, IRODSHOME);
            $count=0;
            $files = $irodsHome->findFiles ( array('name' => $fileName, 'recursive' => true, 'descendantOnly' => true), &$count );
        }
        catch(RODSException $e) {
            printException("findIRODSFilesByName", $e);
        }

        return $files;
    }

    /*
        Returns the files with matching data from IRODS server
        @irodsConn      - Connection to the IRODS server
        @metadata       - Metadata that has to be searched in ICAT databaase. For example, search by file path, file size etc.
    */
    function findIRODSFilesByMeta($irodsConn, $metadata)
    {
        $files = NULL;

        try {
            $irodsHome = new ProdsDir($irodsConn, IRODSHOME);
            $count=0;
            $files = $irodsHome->findFiles ( array('recursive' => true, 'descendantOnly' => true, 'metadata'=> array($metadata)), &$count );
        }
        catch(RODSException $e) {
            printException("findIRODSFilesByMeta", $e);
        }

        return $files;
    }

    // Checks if a file is already present in IRODS Server
    function isFilePresentInIRODS($irodsConn, $fileName) {
        $isFilePresent = false;
        $metadata = new RODSMeta(FILEPATH, $fileName, NULL, NULL, "=");
        
        $files = findIRODSFilesByMeta($irodsConn, $metadata);        
        if(!empty($files)) {
            $isFilePresent = true;
        }

        return $isFilePresent;
    }

    // Checks if a file which is stored in IRODS Server has been modified
    // at source and needs to be loaded again
    function isFileModifiedAtSource($irodsConn, $fileName) {
        $isFileModified = false;
        $metadatas = readMetadataForFile($irodsConn, $fileName);
        if(empty($metadatas)) {
            return $isFileModified;
        }

        // Fetch the file path and old hash value
        $filePath = NULL;
        $fileHash = NULL;
        $fileMetadata = $metadatas[$fileName];
        foreach($fileMetadata as $key=>$value) {
            if($key == HASH) {
                $fileHash = $value;
            }
            if($key == FILEPATH) {
                $filePath = $value;
            }
        }

        if(!(isset($filePath) && isset($fileHash))) {
            return $isFileModified;
        }

        // compare the new hash value with the old hash value
        $newStats = getSrcFileStats($filePath);
        foreach($newStats as $key=>$value) {
            if($key == HASH) {
                $newHash = $value;
                if($newHash != $fileHash) {
                    $isFileModified = true;
                }
            }
        }

        return $isFileModified;
    }

    // Gets the location of the file in IRODS server
    function getFileLocInIRODS($irodsConn, $fileName) {
        $files = findIRODSFilesByName($irodsConn, $fileName);
        $paths = array();
        foreach($files as $file) {
            $paths[] = $file->getPath();
        }

        $filePath = NULL;
        // Return the first path value. TODO : Check if this logic is correct.
        if(!empty($paths)) {
            $filePath = $paths[0];
        }

        return $filePath;
    }


    /************************  Utility APIs                  ***********************/

    // Returns the filename from the source path of the file
    function getFileNameFromPath($srcFilePath) {
        return basename($srcFilePath);
    }

    // Returns the source file path of a filename. This can return multiple paths
    // because multiple files can have the same name.
    function getSrcFilePathFromName($irodsConn, $fileName) {
        $srcFilePath = NULL;
        $metadatas = readMetadataForFile($irodsConn, $fileName);
        if(empty($metadatas)) {
            return $srcFilePath;
        }

        $fileMetaData = $metadatas[$fileName];
        foreach($fileMetaData as $key=>$value) {
            if($key == FILEPATH) {
                $srcFilePath = $value;
            }
        }

        echo "\nSource file path in IRODS server for fileame " . $fileName . " is " . $srcFilePath;
        return $srcFilePath;
    }

    // Returns various attributes for a file in filesystem.
    function getSrcFileStats($srcFilePath) {
        $fileStats = stat($srcFilePath);
        $fileAttrs = $srcFilePath. "_". $fileStats["size"]. "_" . $fileStats["mtime"];

        $srcFileStats = array();
        $srcFileStats[SIZE] = $fileStats["size"];
        $srcFileStats[TIMESTAMP] = $fileStats["mtime"];
        
        $srcFileStats[FILEPATH] = $srcFilePath;
        $srcFileStats[HASH] = md5($fileAttrs);

        return $srcFileStats;
    }

    // Utility function to print exceptions
    function printException($apiName, $exception)
    {
        echo "\n$apiName API failed. Cause : " . $exception;
    }
?>
