<?php
    /*
      Contains the configuration settings to launch the IRODS scripts
    */

    // Base file for PhP IRODS API
    require_once(__DIR__."/../../lib/PRODS/clients/prods/src/Prods.inc.php");
    require_once(__DIR__."/../../lib/PRODS/clients/prods/src/ProdsStreamer.class.php");
  
    // Create a default connection to IRODS server
    $irodsConn = new RODSAccount("198.51.254.78", 1247, "irods_user", "irods_user");
?>
