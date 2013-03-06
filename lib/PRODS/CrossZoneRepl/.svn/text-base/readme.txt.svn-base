The program is used to replicate datasets, including files and user-defined metadata, from one zone to 
another designated zone.

1. Build the software: a short version
     (1) run 'configure'
     (2) run 'make install'
     This will install the software in the directory specified in the 'configure'.

     Example:
        > ./configure --irods_src_home=/usr/local/iRODS --install_dir=/opt
        > make install
        The software will be installed in the directory '/opt/CrossZoneRepl'.

2. Edit the configuration file for the cross-zone file replication service, 'crosszonerepl.cfg'.
     (1) add the irods source collection info: irods server, user account, top source collection for replication.
     (2) add the irods destination info: irds server, user account, top iRODS collection for file replication.
     Note: A sample 'crosszonerepl.cfg' is provided in the package.

3. Manually test the program
     Run the 'run_CrossZoneRepl.sh' in the installation directory.
     Example: $ cd /opt/CrossZoneRepl
              $ run_CrossZoneRepl.sh
              $ more irods_repl.log   --> to check the log file.
     
4. Deployment of the cross-zone data replication service

     Method #1: The program can be deployed as a UNIX cron job that scans a designated source iRODS collection and 
        its child collections periodically to replicate new files into the specified destination collection in 
    	another iRODS zone.

     Method #2: The program can also be deployed inside iRODS as an micro service for an external program that runs 
        periodically to do cross-zone data replication. 

Please send your questions to Dr. Bing Zhu at: bing_zhu@yahoo.com.
