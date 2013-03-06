%{!?globusLocation: %define globusLocation /usr/local/globus}
%{!?globusFlavour: %define globusFlavour gcc32pthr}
%{!?clientInstallDir: %define clientInstallDir /usr/local}
%{!?oraHome: %define oraHome /usr/lib/oracle/11.1/client}
%{!?pgHome: %define pgHome /usr}

%{?_with_postgres: %{?_with_oracle: %{error: Both --with postgres and --with oracle specified}}}

%define ICATpackageName %{name}-%{?_with_oracle:oraICAT}%{?_with_postgres:pgICAT}%{?_with_globus:-GSI}%{?_with_ads:-ADS}
%define serverpackageName %{name}-server%{?_with_globus:-GSI}%{?_with_ads:-ADS}
%define clientpackageName %{name}-client%{?_with_globus:-GSI}

%define irodsHome /opt/irods/server

Summary:	IRODS - Integrated Rule-Oriented Data System
Name:		irods
Group:		System Environment/Daemons
Version:	2.5
Release:	2
License:	BSD
Source0:	irods%{version}.tgz
Source1:	RPM-scripts.tar.gz
Source2:	extrods-1.1.0.1beta.tar.gz
%if %{?_with_modules:1}0
Source3:	modules.tar.gz
%endif

Patch0:		iRODS-RPM.patch
Patch1:		iRODS-postgres.patch
Patch2:		enable-syslog.patch

%if %{?_with_ads:1}0
Patch3:		ads-driver.patch
%endif

%if %{?_with_kerberos:1}0
Patch4:		kerberos.patch
%endif

%if %{?_with_EPEL_GSI:1}0
Patch5:		serverMakefile.patch
Patch6:		clientMakefile.patch
%endif

Patch7:		RPMirodsctl.patch
Patch8:		irods_2.5_patch_1
Patch9:		irods_2.5_patch_2

URL:		http://www.irods.org
Packager:	Kev O'Neill - STFC
BuildRoot:	/tmp/SRB-root

%description
iRODS, the Integrated Rule-Oriented Data System, is a data grid software system developed by the Data Intensive Cyber Environments (DICE) group (developers of the SRB, the Storage Resource Broker), and collaborators. The iRODS system is based on expertise gained through nearly a decade of applying the SRB technology in support of Data Grids, Digital Libraries, Persistent Archives, and Real-time Data Systems. iRODS management policies (sets of assertions these communities make about their digital collections) are characterized in iRODS Rules and state information. At the iRODS core, a Rule Engine interprets the Rules to decide how the system is to respond to various requests and conditions. iRODS is open source under a BSD license.

%if %{?_with_icat:1}0
%package -n %{ICATpackageName}
%if %{?_with_oracle:1}0
Summary:	The IRODS ICAT Server (Oracle backend database)
%else
Summary:	The IRODS ICAT Server (Postgres backend database)
%endif
#####################################################################
Group:		System Environment/Daemons
Conflicts:	irods-server, irods-server-GSI
%if %{?_with_globus:1}0
Conflicts:	irods-pgICAT, irods-oraICAT
Requires:	irods-client-GSI
%else
Conflicts:	irods-pgICAT-GSI, irods-oraICAT-GSI
Requires:	irods-client
%endif
%description -n	%{ICATpackageName}
iRODS, the Integrated Rule-Oriented Data System, is a data grid software system developed by the Data Intensive Cyber Environments (DICE) group (developers of the SRB, the Storage Resource Broker), and collaborators. The iRODS system is based on expertise gained through nearly a decade of applying the SRB technology in support of Data Grids, Digital Libraries, Persistent Archives, and Real-time Data Systems. iRODS management policies (sets of assertions these communities make about their digital collections) are characterized in iRODS Rules and state information. At the iRODS core, a Rule Engine interprets the Rules to decide how the system is to respond to various requests and conditions. iRODS is open source under a BSD license.

This is the ICAT server portion.
%else
%package -n %{serverpackageName}
Summary:	The IRODS Server
#####################################################################
Group:		System Environment/Daemons
Conflicts:	irods-pgICAT, irods-oraICAT, irods-pgICAT-GSI, irods-oraICAT-GSI
%if %{?_with_globus:1}0
Conflicts:	irods-server
%else
Conflicts:	irods-server-GSI
%endif
%description -n %{serverpackageName}
iRODS, the Integrated Rule-Oriented Data System, is a data grid software system developed by the Data Intensive Cyber Environments (DICE) group (developers of the SRB, the Storage Resource Broker), and collaborators. The iRODS system is based on expertise gained through nearly a decade of applying the SRB technology in support of Data Grids, Digital Libraries, Persistent Archives, and Real-time Data Systems. iRODS management policies (sets of assertions these communities make about their digital collections) are characterized in iRODS Rules and state information. At the iRODS core, a Rule Engine interprets the Rules to decide how the system is to respond to various requests and conditions. iRODS is open source under a BSD license.

%endif

%package -n %{clientpackageName}
%if %{?_with_globus:1}0
Summary:        The IRODS Client (GSI-enabled)
Conflicts:	irods-client
%else
Summary:        The IRODS Client
Conflicts:	irods-client-GSI
%endif
#####################################################################
Group:		Applications/System
%description -n %{clientpackageName}
iRODS, the Integrated Rule-Oriented Data System, is a data grid software system developed by the Data Intensive Cyber Environments (DICE) group (developers of the SRB, the Storage Resource Broker), and collaborators. The iRODS system is based on expertise gained through nearly a decade of applying the SRB technology in support of Data Grids, Digital Libraries, Persistent Archives, and Real-time Data Systems. iRODS management policies (sets of assertions these communities make about their digital collections) are characterized in iRODS Rules and state information. At the iRODS core, a Rule Engine interprets the Rules to decide how the system is to respond to various requests and conditions. iRODS is open source under a BSD license.

This is the client software.

#####################################################################
%package web-interface
Summary:	A web-based IRODS client
Group:		Productivity/Networking/Web/Frontends
Requires:	php, httpd
%description web-interface
A web-based IRODS client written in PHP, which utilises AJAX for the interface.

%prep
#####################################################################
%setup -n iRODS
%setup -T -D -a 1 -n iRODS
%setup -T -D -a 2 -n iRODS
%if %{?_with_modules:1}0
%setup -T -D -a 3 -n iRODS
%endif

%patch0 -p0
%patch1 -p0

%if %{?_with_syslog:1}0
%patch2 -p0
%endif

%if %{?_with_ads:1}0
%patch3 -p0
%endif

%if %{?_with_kerberos:1}0
%patch4 -p0
%endif


%if %{?_with_EPEL_GSI:1}0
%patch5 -p0
%patch6 -p0
%endif

%patch7 -p0
echo "Patch #8:"
patch -p2 -l  -s < ../../SOURCES/irods_2.5_patch_1
echo "Patch #9:"
patch -p2 -l  -s < ../../SOURCES/irods_2.5_patch_2

%build
#####################################################################

# Extract the extjs libraries for the web client
unzip extrods-1.1.0.1beta/clients/web/extjs.zip 

%if %{?_with_icat:1}0
# Got to write our own irods.config file in order to specify DB home
%if %{?_with_postgres:1}0
echo -e "\
\$DATABASE_TYPE = 'postgres';\n\
\$DATABASE_HOME = '%{pgHome}';\n\
\$DATABASE_LIB = 'lib';\n\
" > config/irods.config
%else
echo -e "\
\$DATABASE_TYPE = 'oracle';\n\
\$DATABASE_HOME = '%{oraHome}';\n\
\$DATABASE_LIB = 'lib';\n\
" > config/irods.config
%endif
%endif

# Fix from Geoff Quigley to properly enable GSI
# Allows for GSI SSL libs that are not named according to the Globus flavour (e.g. VDT)
%if %{?_with_globus:1}0
echo  "\$GSI_AUTH=1;" >>config/irods.config
%endif

# Then run the perl configure script to write build files
perl scripts/perl/configure.pl \
 --enable-parallel --enable-file64bit \
%if %{?_with_globus:1}0
 --enable-gsi --globus-location=%{globusLocation} --gsi-install-type=%{globusFlavour} \
%endif
%if %{?_with_icat:1}0
%if %{?_with_oracle:1}0
 --enable-icat --enable-oracat\
%else
 --enable-icat --enable-psgcat --enable-newodbc\
%endif
%endif

%{__make}

%install
#####################################################################
# install root files
mkdir -p $RPM_BUILD_ROOT/%{clientInstallDir}/bin
cp clients/icommands/bin/i* $RPM_BUILD_ROOT/%{clientInstallDir}/bin

mkdir -p $RPM_BUILD_ROOT/etc/init.d
mkdir -p $RPM_BUILD_ROOT/etc/irods
mkdir -p $RPM_BUILD_ROOT/etc/httpd/conf.d
mkdir -p $RPM_BUILD_ROOT/var/opt/irods
mkdir -p $RPM_BUILD_ROOT/var/run/irods
mkdir -p $RPM_BUILD_ROOT/%{irodsHome}/bin
mkdir -p $RPM_BUILD_ROOT/%{irodsHome}/schema
mkdir -p $RPM_BUILD_ROOT/%{irodsHome}/config
mkdir -p $RPM_BUILD_ROOT/%{irodsHome}/config/reConfigs
mkdir -p $RPM_BUILD_ROOT/%{irodsHome}/config/packedRei
mkdir -p $RPM_BUILD_ROOT/srv/irods/web/extjs
mkdir -p $RPM_BUILD_ROOT/srv/irods/prods

cp server/icat/src/*.sql $RPM_BUILD_ROOT/%{irodsHome}/schema
cp -r server/bin/i* $RPM_BUILD_ROOT/%{irodsHome}/bin
cp -r server/config/server.config $RPM_BUILD_ROOT/%{irodsHome}/config
cp -r server/config/rda.config $RPM_BUILD_ROOT/%{irodsHome}/config
cp -r server/config/HostAccessControl $RPM_BUILD_ROOT/%{irodsHome}/config
cp -r server/config/irodsHost $RPM_BUILD_ROOT/%{irodsHome}/config
cp -r server/config/reConfigs/* $RPM_BUILD_ROOT/%{irodsHome}/config/reConfigs
#cp -r server/config/packedRei/* $RPM_BUILD_ROOT/%{irodsHome}/config/packedRei

cp etc/init.d/irods $RPM_BUILD_ROOT/etc/init.d
cp etc/irods/default $RPM_BUILD_ROOT/etc/irods
cp etc/httpd/conf.d/irods.conf $RPM_BUILD_ROOT/etc/httpd/conf.d

cp -r extrods-1.1.0.1beta/clients/web/* $RPM_BUILD_ROOT/srv/irods/web
cp -r ext-1.1.1/* $RPM_BUILD_ROOT/srv/irods/web/extjs
cp -r extrods-1.1.0.1beta/clients/prods/* $RPM_BUILD_ROOT/srv/irods/prods
%if %{?_with_icat:1}0
%post -n %{ICATpackageName}
%else
%post -n %{serverpackageName}
%endif
#####################################################################
# Add the 'irods' group and make sure the various directories can be written by it
[ `getent group irods` ] || groupadd irods
chgrp -R irods /var/opt/irods /var/run/irods
chmod g+w /var/opt/irods /var/run/irods
%clean
#####################################################################
rm -rf $RPM_BUILD_ROOT

%if %{?_with_icat:1}0
%files -n %{ICATpackageName}
%else
%files -n %{serverpackageName}
%endif
#####################################################################
%{irodsHome}/bin
%{irodsHome}/schema
%{irodsHome}/config
/etc/init.d/irods
/etc/irods
/var/opt/irods
/var/run/irods

%files -n %{clientpackageName}
#####################################################################
%{clientInstallDir}/bin

%files web-interface
/srv/irods/prods
/srv/irods/web
/etc/httpd/conf.d/irods.conf
