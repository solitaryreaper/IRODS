Usage instructions
==================
To create RPM packages for iRODS you need to have an RPM build environment setup already. The method for doing this varies between distributions so refer to information specific to your flavour of Linux. Once that has been setup copy the contents of the SOURCES and SPECS directory into the corresponding place in your build environm copy the contents of the SOURCES and SPECS directory from this repository into the corresponding place in your build environment. Place the irods2.2.tgz in the SOURCES directory. Also download from http://code.google.com/p/extrods/downloads/list the extrods-1.1.0.1beta.tar.gz file and place that in the SOURCES directory too.

Build instructions
==================
The RPM is configurable via command-line parameters supplied to the rpmbuild command:
	--with icat	- Compiles ICAT-enabled server binaries
	--with postgres	- Compiles an ICAT-enabled server with Postgres backend
	--with oracle	- Compiles an ICAT-enabled server with Oracle backend
	--with globus	- Compiles GSI authentication support into servers and clients
	--with EPEL_GSI	- Enables support for building against EPEL GSI libraries (which do not include Globus flavour in their names)
	--with kerberos - Compile kerberos v5 support for authentication, currently not compatible with Globus
	--with modules  - Compile in custom modules (details on this below)
	--with ads		- Compile support for accessing the ATLAS datastore as used by STFC in the UK (needs external libs)
	--with syslog	- Enable syslog support for logging by iRODS servers

	--define "oraHome /path/to/your/oracle"			- Changes the location of your Oracle libraries
		(Default: /usr/lib/oracle/10.2.0.3/client)
	--define "pgHome /path/to/your/postgres"		- Changes the location of your Postgres libraries
		(Default: /usr)
	--define "globusLocation /path/to/your/globus"		- Changes the location of your Globus libraries
		(Default: /opt/globus)
	--define "globusFlavour <flavour>"			- Changes the Globus flavour to compile against
		(Default: gcc32pthr)
	--define "clientInstallDir /where/you/want/them"	- Changes where the client commands are installed
		(Default: /usr/local)

To build the RPMs:
	rpmbuild -bb <options> irods.spec

This will unpack the sources, apply any necessary patches, and compile the code according to your chosen configuration. Compiled binary packages will be placed in a subdirectory relevant to your architecture under RPMS/ in your RPM build tree.

Using the software once installed
=================================
A start/stop script, /opt/irods/server/bin/irodsctl, is included to start and stop individual iRODS projects. Projects are defined by files created in /etc/irods. The contents of these files are documented in an example file installed to /etc/irods/default (delete or rename this).

The name of the config file is assumed to be the name of the iRODS install, and is used thus:

/opt/irods/server/bin/irodsctl <config file name> start | stop

This script should be run as root, it drops privileges to the chosen RUN_AS_USER before executing iRODS. The name of the project is included in the command that is run so that process control can be performed. Make sure the chosen RUN_AS_USER is a member of the 'irods' group which has been automatically added by the RPM.

Postgres DB configuration
=========================
1. Create a DB user (assumed 'irods' here):
	createuser irods -P
2. Create a schema:
	createdb -O irods ICAT
3. Add the tables to the schema:
	psql -U irods -h localhost -f /opt/irods/schema/icatCoreTables.sql
	psql -U irods -h localhost -f /opt/irods/schema/icatCoreInserts.sql
	psql -U irods -h localhost -f /opt/irods/schema/icatSysTables.sql
	psql -U irods -h localhost -f /opt/irods/schema/icatSysInserts.sql
4. Create the ODBC configuration by editing the file /etc/odbc.ini and adding:
[PostgreSQL]
Driver=</path/to/your/postgres/odbc/driver>
Debug=0
CommLog=0
Servername=127.0.0.1
ReadOnly=no
Port=5432
Database=ICAT

(PostgreSQL can be changed in line with the ODBC_DSN setting in the config file above).	

Added features
==============
- Configurable ODBC DSN name for Postgres builds via the ODBC_DSN setting (Defaults to "PostgreSQL")


Source RPMs
===========
To create a source RPM do:
	rpmbuild -bs irods.spec

This can then be copied to another machine and installed:
	rpm -i irods-2.1-1.src.rpm

Then follow instructions as above.


Custom modules
==============
Custom modules can be added to the compiled RPMs in the following way:
Package up your modules in a gzipped tar file called modules.tar.gz and with the structure:
modules/
modules/custom_code
...etc

Put the modules.tar.gz in the SOURCES/ directory prior to compilation and specify --with modules on the build command line. The modules.tar.gz file will be unpacked into the iRODS source code.

Changes from previous release
=============================
Filesystem hierarchy standard tweaks - iRODS environments and logs are now written to /var/opt/irods/<config file name>/log
Many more build options - Added several ways to customise the build
Meaningful name for the RPMs - Now the name encodes to some degree the options chosen at build time
iRODS web client setup - An RPM for the iRODS web client (installs to /srv/irods) is built

Issues
======
- To build against Oracle Instant Client:
	ln -s /usr/include/oracle/<version>/client to /usr/lib/oracle/<version>/client/rdbms/public
- Since RPM does extensive dependency checking you may have to install with --nodeps if you have non-RPM installs of Globus and/or Oracle
- Upgrading between iRODS versions will likely require an update to the ICAT schema. This must be performed manually.
