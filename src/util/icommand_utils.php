<?php
    /*  
        Contains various wrapper commands over icommands to make system calls to 
        perform various IRODS operations like writing files etc.
    */

    /*
        Copies file to IRODS server in bulk mode.
        @srcFilePath    -   Path of source file
        @irodsDirPath   -   Destination path on IRODS server. If NULL, then it is copied to the home
                            directory on IRODS server.
    */
    function icmdWriteFileToIRODS($srcFilePath, $irodsDirPath=null)
    {   
        if(!file_exists($srcFilePath)) {
            echo "Source file " . $srcFilePath . " does not exist !!";
            return;
        }

        if(!isset($irodsDirPath)) {
            $irodsDirPath = "";
        }

        $writeCmd = "iput -bfPQ " . $srcFilePath . " " . $irodsDirPath;
        return executeICommand($writeCmd);   
    }   
    
    /*
        Creates a new IRODS directory
     */
    function icmdCreateDirectory($irodsDirPath)
    {
        $createCmd = "imkdir -p " . $irodsDirPath;
        return executeICommand($createCmd);
    }
        
    /*  
     * Executes an icommand on system
     */
    function executeICommand($command)
    {   
        echo "\nICommand to execute : " . $command;
        $output=null;
        $isSuccess = true;
        try {
            exec($command, $output);
        }   
        catch(Exception $e) {
            echo "ICommand " . $command . " failed. Reason " . $e; 
            $isSuccess = false;
        }   

        return $isSuccess;
    }   

?>

