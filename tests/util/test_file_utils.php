<?php
    
    // Test cases for file_utils.php. These are not true test cases but placeholder files to test the
    // functionality of function in an ad-hoc manner

    require_once(__DIR__."/../../src/commons/config.php");
    require_once(__DIR__."/../../src/util/file_utils.php");

    // Define the data directorys here. Change this to apt local directories where data is stored.
    define("SRCIMAGESDIR", __DIR__ . "/../../data/images/");
    define("SRCVIDEOSDIR", __DIR__ . "/../../data/videos/");

    define("IRODSIMAGESDIR", "/spaldingZone/home/irods_user/doane/images/");
    define("IRODSVIDEOSDIR", "/spaldingZone/home/irods_user/doane/videos/");


    function testWriteFileIntoIRODS($irodsConn, $srcFilePath, $irodsDirPath)
    {
        $resOp = writeToIRODS($irodsConn, $srcFilePath, $irodsDirPath, "csirods1RescGroup", false);

        $isTestPassed = false;
        if($resOp) {
            $isTestPassed = true;
        }

        return ($isTestPassed ? "True" : "False");
    }

    function testAddMetadataToFile($irodsConn, $srcFilePath)
    {
        $retVal = addMetadataToFile($irodsConn, $srcFilePath);

        $isTestPassed = false;
        if($retVal) {
            $isTestPassed = true;
        }

        return ($isTestPassed ? true : false);
    }

    function testGetFileNameFromPath($srcFilePath)
    {
        $resFileName = getFileNameFromPath($srcFilePath);
        $expFileName = "music.jpg";

        $isTestPassed = false;
        if($resFileName == $expFileName) {
            $isTestPassed = true;
        }

        return ($isTestPassed ? "True" : "False");
    }

    function testGetSrcFilePathFromName($irodsConn, $fileName)
    {
        $srcFilePath = getSrcFilePathFromName($irodsConn, $fileName);
        
        $isTestPassed = false;
        if(isset($srcFilePath)) {
            $isTestPassed = true;
        }

        return ($isTestPassed ? "True" : "False");
    }

    function testGetFileLocInIRODS($irodsConn, $fileName)
    {
        $isTestPassed = false;
        $filePath = getFileLocInIRODS($irodsConn, $fileName);
        
        if(isset($filePath)) {
            $isTestPassed = true;
            echo "IRODS path of file " . $fileName . " is " . $filePath;
        }

        return ($isTestPassed ? "True" : "False");
    }

    function testReadFromIRODS($irodsConn, $fileName)
    {
        $isTestPassed = false;
        $fileContents = readFromIRODS($irodsConn, $fileName);

        if(isset($fileContents)) {
            $isTestPassed = true;
            echo "\n File contents for " . $fileName . " is : " . $fileContents; 
        }

        return ($isTestPassed ? "True" : "False");
    }

    function testReadMetadataForFile($irodsConn, $fileName)
    {
        $metadata = readMetadataForFile($irodsConn, $fileName);
        print_r($metadata);

        $isTestPassed = false;
        if(!empty($metadata)) {
            $isTestPassed = true;
        }

        return ($isTestPassed ? "True" : "False");
    }

    function testListFiles($irodsConn, $dirPath)
    {
        $files = listFiles($irodsConn, $dirPath);
        print_r($files);

        $isTestPassed = false;
        if(!empty($files)) {
            $isTestPassed = true;
        }

        return ($isTestPassed ? "True" : "False");
    }

    function testFindIRODSFilesByMeta($irodsConn, $metadata)
    {
        $isTestPassed = false;
        $files = findIRODSFilesByMeta($irodsConn, $metadata);
        //print_r($files);

        if(!empty($files)) {
            $isTestPassed = true;
            foreach($files as $file) {
                echo "\n File : ". $file;
            }
        }

        return ($isTestPassed ? "True" : "False");
    }

    function testIsFileModifiedAtSource($irodsConn, $fileName)
    {
        $isFileModified = isFileModifiedAtSource($irodsConn, $fileName);
        return ($isFileModified ? "False" : "True"); // Testfile should not have been modified
    }

    $irodsConn = new RODSAccount("198.51.254.78", 1247, "irods_user", "irods_user");
    
    //$testFile = "dance.jpg";
    //$testFilePath = SRCIMAGESDIR . $testFile;

    // Run all the tests here - Uncomment the tests that you want to run
    //$isTestPassed = testGetFileNameFromPath($testFilePath);
    //echo "\n ** getFileNameFromPath test result : " . $isTestPassed . "\n";

    //$isTestPassed = testGetSrcFilePathFromName($irodsConn, $testFile);
    //echo "\n ** getSrcFilePathFromName test result : " . $isTestPassed . "\n";

    # Insert image into IRODS
    //$imageFilePath = SRCIMAGESDIR . "testimage3.png";
    $imageFilePath = "/mnt/doane/share/doane/RIL_Data/447-3-sm-130-71-34-448-3-sm-82-106-108/Scan-110309-0116.tif";
    $irodsDirPath = "/spaldingZone/home/irods_user/doane/images/mytes/";
    echo "Image : " . $imageFilePath;
    $isTestPassed = testWriteFileIntoIRODS($irodsConn, $imageFilePath, $irodsDirPath);
    echo "\n ** writeFileIntoIRODS test result : " . $isTestPassed . "\n";

    # Insert Video into IRODS
    #$videoFilePath = SRCVIDEOSDIR . "media.mpg";
    #$isTestPassed = testWriteFileIntoIRODS($irodsConn, $videoFilePath, IRODSVIDEOSDIR);
    #echo "\n ** writeFileIntoIRODS test result : " . $isTestPassed . "\n";

    // TODO : Not working
    //$isTestPassed = testAddMetadataToFile($irodsConn, $srcFilePath);
    //echo "\n ** addMetadataToFile test result : " . $isTestPassed;

    //$isTestPassed = testGetFileLocInIRODS($irodsConn, $testFile);
    //echo "\n ** getFileLocInIRODS test result : " . $isTestPassed . "\n";
   
    // TODO : Not working. 
    //$isTestPassed = testReadFromIRODS($irodsConn, "testfile");
    //echo "\n ** readFromIRODS test result : " . $isTestPassed . "\n";

    //$isTestPassed = testReadMetadataForFile($irodsConn, $testFile);
    //echo "\n ** readMetadataForFile test result : " . $isTestPassed . "\n";

    //$dirPath = "/spaldingZone/home/irods_user";
    //$isTestPassed = testListFiles($irodsConn, $dirPath);
    //echo "\n ** listFiles test result : " . $isTestPassed . "\n";

    //$metadata = new RODSMeta("path", $testFilePath, NULL, NULL, "=");
    //$isTestPassed = testFindIRODSFilesByMeta($irodsConn, $metadata);
    //echo "\n ** findIRODSFilesByMeta test result : " . $isTestPassed . "\n";

    //$isTestPassed = testIsFileModifiedAtSource($irodsConn, $testFile);
    //echo "\n ** isFileModifiedAtSource test result : " . $isTestPassed . "\n";
?>
