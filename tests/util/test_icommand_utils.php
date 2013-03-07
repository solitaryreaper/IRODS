<?php
    // Test cases for file_utils.php
    require_once(__DIR__."/../../src/commons/config.php");
    require_once(__DIR__."/../../src/util/icommand_utils.php");

    function testIcmdWriteFileToIRODS($srcFilePath, $irodsDirPath)
    {
        icmdWriteFileToIRODS($srcFilePath, $irodsDirPath);
    }

    $srcFilePath  = __DIR__ . "/../../data/images/testimage1.png";
    $irodsDirPath = null;
    testIcmdWriteFileToIRODS($srcFilePath, $irodsDirPath);
?>

