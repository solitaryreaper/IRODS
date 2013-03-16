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

        $writeCmd = "iput -bPQV " . $srcFilePath;
        executeICmd($writeCmd);   
    }   
    
    /*
        Creates a new IRODS directory
     */
    function icmdCreateDirectory($dirName, $parentIRODSDirPath)
    {
        $createCmd = "imkdir -p " . $parentIRODSDirPath . $dirName;
        executeICommand($createCmd);
    }
        
    /*  
     * Executes an icommand on system
     */
    function executeICommand($command)
    {   
        $output=null;
        try {
            exec($command, $output);
        }   
        catch(Exception $e) {
            echo "Icommand " . $command . " failed. Reason " . $e; 
        }   

        print_r($output);
        return $output;
    }   

?>

