/* The program is used to make replication across iRODS zones from UAL to OCUL.
 * Author: Bing Zhu
 * Date Created: May 17, 2010
 * Version 1.1
 */

#include <iostream>
#include <sstream>
#include <string>
#include <algorithm>
#include <vector>
#include <fstream>

#include "rodsClient.h"
#include "parseCommandLine.h"
#include "rodsPath.h"
#include "cpUtil.h"
#include "AvuMetaData.h"
#include "utils.h"

int log_fd;
bool Debug = false;

int totalNumFilesReplicated = 0;
int totalNumFilesSkiped = 0;
int totalNumFilesFailedToReplicate = 0;
int totalNumFilesAddedNewMetadata = 0;

using namespace std;

class RodsEnvClass
{
public:
   string irodsHost;
   int    irodsPort;
   string irodsUserName;
   string irodsUserPasswd;
   string irodsDefResource;
   string irodsZone;
   string topCollection;
};


//=============================================================================

#define BUFSIZE 20000

static rcComm_t* connect_irods_server(RodsEnvClass & env);
static void log_error_code(int errCode);
static void print_config_info(RodsEnvClass & sEnv, RodsEnvClass & dEnvs);
static int get_config(string& fname, RodsEnvClass & sEnv, RodsEnvClass & dEnvs);
static void close_irods_obj(rcComm_t *conn, int fd);
static int open_irods_obj(rcComm_t *conn, string & obj_path, int open_flag);
static int read_irods_obj(rcComm_t *conn, int fd, char *buff, int len);
static int create_irods_obj(rcComm_t *conn, string & obj_path, string & resc);
static int write_irods_obj(rcComm_t *conn, int fd, char *buff, int len);
static void write_log(string& str);
static void create_irods_dir(rcComm_t *conn, string & dir_name);
static void debug_msg(string& msg);
static int query_source_collection(rcComm_t *conn, RodsEnvClass& srcEnv, string& topColl, bool recursive, vector<string>& src_files);
// static void print_coll_info(vector<string> & files);
static void construct_dest_files(RodsEnvClass& srcEnv, vector<string>& srcFiles, RodsEnvClass& destEnv, vector<string>& destFiles);
static int replicate_single_file(rcComm_t *srcConn, string& srcFile, rcComm_t *destConn, string& destFile, string& destResc);
static void PrintUsage(void);
static void write_log_and_exit(string& msg);
static void program_start_msg();
static void program_exit_msg();

//  ---------------------------------------
// main function
//  ---------------------------------------
int main(int argc, char **argv) {

   RodsEnvClass srcEnv;
   RodsEnvClass destEnv;

   log_fd = 2;  // stderr

   program_start_msg();

   vector<string> srcFilesToRepl;

   rcComm_t *src_conn, *dest_conn;
   string config_fname;

   string msg;

   int t;

   string src_node_obj_path;

   config_fname = "";
   for(int i=0;i <argc; i++) {
      string ts = argv[i];
      if(ts.find("-configfile=") != (size_t)string::npos) {
         config_fname = ts.substr(12);
      }
   }

   if(config_fname.length() <= 0) {
      PrintUsage();
      msg = "failed to get config file name.\n"; 
      program_exit_msg();
      exit(1);
   }

   // read the content in config file
   if(get_config(config_fname, srcEnv, destEnv) < 0) {
      // error message is already logged.
      program_exit_msg();
      exit(1);
   }
   
   // tetsing the config info
   if(Debug) 
     print_config_info(srcEnv, destEnv);

   //////////////////////////////////////////////////////////////
   // Start to make replicas
   //////////////////////////////////////////////////////////////

   // open connection to source iRODS server
   msg = "connecting src irods server ...\n";
   debug_msg(msg);
   src_conn = connect_irods_server(srcEnv);
   if(src_conn == NULL)
   {
      msg = string("ERROR: failed to connect to the source iRODS server, ") + srcEnv.irodsHost + string(".\n");
      write_log_and_exit(msg);
   }
   msg = "connection to source iRODS server established\n";
   debug_msg(msg);

   // open connection to destination iRODS server
   msg = "connecting dest irods server ...\n";
   debug_msg(msg);
   dest_conn = connect_irods_server(destEnv);
   if(dest_conn == NULL)
   {
      rcDisconnect(src_conn);
      msg = string("ERROR: failed to connect to the destination iRODS server, ") + destEnv.irodsHost + string(".\n");
      write_log_and_exit(msg);
   }
   msg = "connection to destination iRODS server established\n";
   debug_msg(msg);

   // query the source collection
   bool recusive = true;
   if(query_source_collection(src_conn, srcEnv, srcEnv.topCollection, recusive, srcFilesToRepl) < 0) {

       rcDisconnect(src_conn);
       rcDisconnect(dest_conn);

       msg = string("ERROR: failed to query the source collection, ") + srcEnv.topCollection + string(".\n");
       write_log_and_exit(msg);
   }

   vector<string> destFilenamesWithPath;
   construct_dest_files(srcEnv, srcFilesToRepl, destEnv, destFilenamesWithPath);

   /* check info. For Debug only
   cerr << "src top: " << srcEnv.topCollection << endl;
   print_coll_info(srcFilesToRepl);
   cerr << "desc top: " << destEnv.topCollection << endl;
   print_coll_info(destFilenamesWithPath);
   */

   for(size_t i=0; i<srcFilesToRepl.size(); i++) {
      t = replicate_single_file(src_conn, srcFilesToRepl[i], dest_conn, destFilenamesWithPath[i], destEnv.irodsDefResource);
      if(t < 0) {
         log_error_code(t);
      }
   }

   // disconnect from source iRODS
   msg = "disconnecting from the src irods...\n"; 
   debug_msg(msg);
   rcDisconnect(src_conn);
   rcDisconnect(dest_conn);
   
   program_exit_msg();
   return 0;
}

static int add_user_meta(rcComm_t *srcConn, string& srcFile, rcComm_t *destConn, string& destFile, bool addMetaOnly) {

   string msg;
   size_t t;

   vector<AuvMetaData> srcUserMetas;
   t = query_user_metadata(srcConn, srcFile, srcUserMetas);
   if(t < 0) {
      return t;
   }

   vector<AuvMetaData> destUserMetas;
   t = query_user_metadata(destConn, destFile, destUserMetas);
   if(t < 0) {
      return t;
   }

   auv_array_rm_dups(srcUserMetas, destUserMetas);

   if(srcUserMetas.size() <= 0) {
      return 0;
   }

   stringstream ss;
   ss << "INFO: insering " << srcUserMetas.size() << " rows of metadata into dest file," << destFile << ".\n";
   msg = ss.str();
   debug_msg(msg);

   // adding medata into dest file.
   for(size_t i=0; i<srcUserMetas.size(); i++) {
      modAVUMetadataInp_t modAVUMetadataInp;
      memset(&modAVUMetadataInp, 0, sizeof(modAVUMetadataInp_t)); 

      char arg0[4];
      char arg1[4];
      char arg2[2048];   // full obj name
      char arg3[1024];   // att name
      char arg4[2048];   // att value
      char arg5[1024];   // att unit

      strcpy(arg0, "add");
      strcpy(arg1, "-d");
      strcpy(arg2, destFile.c_str());
      strcpy(arg3, srcUserMetas[i].att.c_str());
      strcpy(arg4, srcUserMetas[i].val.c_str());
      if(srcUserMetas[i].unit.length() > 0)
         strcpy(arg5, srcUserMetas[i].unit.c_str());
      else
         arg5[0] = '\0';

      modAVUMetadataInp.arg0 = arg0;
      modAVUMetadataInp.arg1 = arg1;
      modAVUMetadataInp.arg2 = arg2;
      modAVUMetadataInp.arg3 = arg3;
      modAVUMetadataInp.arg4 = arg4;
      modAVUMetadataInp.arg5 = arg5;
      modAVUMetadataInp.arg6 = "";
      modAVUMetadataInp.arg7 = "";
      modAVUMetadataInp.arg8 = "";
      modAVUMetadataInp.arg9 = "";
      t = rcModAVUMetadata(destConn, &modAVUMetadataInp);
      if(t < 0) 
        return t;
   }

   if(addMetaOnly) {
      ++totalNumFilesAddedNewMetadata;
   }

   return 0;
}

static int replicate_single_file(rcComm_t *srcConn, string& srcFile, rcComm_t *destConn, string& destFile, string& destResc) {

   string msg = string("making replica for srcFile=") + srcFile + string("destFile=") + destFile;
   debug_msg(msg);

   int src_fd, dest_fd;
   char readbuff[BUFSIZE+1];
   size_t t;

   // check if the destFile already exist. If file exists, we don't need to re-do file replication.
   dest_fd = open_irods_obj(destConn, destFile, O_RDONLY);
   if(dest_fd >= 0) {
      close_irods_obj(destConn, dest_fd);
      msg = string("INFO: the dest file already exists: ") + destFile + string(".\n");
      ++totalNumFilesSkiped;
      debug_msg(msg);
      t = add_user_meta(srcConn, srcFile, destConn, destFile, true);
      return t;
   }

   // replicate the file to destination
   src_fd = open_irods_obj(srcConn, srcFile, O_RDONLY);
   if(src_fd < 0) {
      msg = string("ERROR: failed to open input file for read in source irods: ") + srcFile + string(".\n");
      write_log(msg);
      ++totalNumFilesFailedToReplicate;
      return src_fd;
   }

   t = destFile.rfind('/');
   if(t == string::npos) {
      msg = string("ERROR: failed to extract parent collection from dest file: ") + destFile + string(".\n");
      write_log(msg);
      ++totalNumFilesFailedToReplicate;
      return 0;
   }
   string parent_coll = destFile.substr(0, t);
   create_irods_dir(destConn, parent_coll);

   // create the obj
   dest_fd = create_irods_obj(destConn, destFile, destResc);
   if(dest_fd < 0) {
      msg = string("ERROR: failed to create file, ") + destFile + string(".\n"); 
      write_log(msg);
      close_irods_obj(srcConn, src_fd);
      ++totalNumFilesFailedToReplicate;
      return dest_fd;
   }

   // read data loop
   int bytesRead;
   while((bytesRead = read_irods_obj(srcConn, src_fd, readbuff, BUFSIZE)) > 0) {
         write_irods_obj(destConn, dest_fd, readbuff, bytesRead);
   }

   // close the objs and disconnect from dest node.
   close_irods_obj(srcConn, src_fd);
   close_irods_obj(destConn, dest_fd);

   t = add_user_meta(srcConn, srcFile, destConn, destFile, false);

   ++totalNumFilesReplicated;

   return t;
}

static void create_irods_dir(rcComm_t *conn, string & dir_name)
{
   string msg;

   msg = string("create_irods_dir(): parent dir=") + dir_name + string(".\n");
   debug_msg(msg);

   collInp_t collCreateInp;
   memset (&collCreateInp, 0, sizeof (collCreateInp));
   addKeyVal(&collCreateInp.condInput, RECURSIVE_OPR__KW, "");
   rstrcpy(collCreateInp.collName, (char *)dir_name.c_str(), MAX_NAME_LEN);
   (void)rcCollCreate(conn, &collCreateInp);
}

static int write_irods_obj(rcComm_t *conn, int fd, char *buff, int len)
{
   int bytesWritten;

   openedDataObjInp_t dataObjWriteInp;
   bytesBuf_t dataObjWriteInpBBuf;

   dataObjWriteInpBBuf.buf = buff;
   dataObjWriteInpBBuf.len = len;
   dataObjWriteInp.l1descInx = fd;

   dataObjWriteInp.len = len;

   bytesWritten = rcDataObjWrite (conn, &dataObjWriteInp, &dataObjWriteInpBBuf);

   return bytesWritten;
}

static int create_irods_obj(rcComm_t *conn, string & obj_path, string & resc)
{
   dataObjInp_t dataObjCreateInp;

   memset (&dataObjCreateInp, 0, sizeof (dataObjCreateInp));

   strcpy(dataObjCreateInp.objPath, (char *)obj_path.c_str());
   addKeyVal(&dataObjCreateInp.condInput, RESC_NAME_KW, (char *)resc.c_str());
   addKeyVal(&dataObjCreateInp.condInput, DATA_TYPE_KW, "generic");
   addKeyVal(&dataObjCreateInp.condInput, RECURSIVE_OPR__KW, "");
   dataObjCreateInp.createMode = 0750;
   dataObjCreateInp.openFlags = O_WRONLY;
   dataObjCreateInp.dataSize = -1;

   int fd = rcDataObjCreate(conn, &dataObjCreateInp);

   return fd;
}

static int read_irods_obj(rcComm_t *conn, int fd, char *buff, int len)
{
   openedDataObjInp_t DataObjReadInp;

   bytesBuf_t DataObjReadOutBBuf;
   DataObjReadOutBBuf.buf = buff;
   DataObjReadOutBBuf.len = len;

   DataObjReadInp.l1descInx = fd;
   DataObjReadInp.len = len;
   int n = rcDataObjRead(conn, &DataObjReadInp, &DataObjReadOutBBuf);

   if(n <= 0)
     return n;

   return n;
}

static int open_irods_obj(rcComm_t *conn, string & obj_path, int open_flag)
{
   int fd = -1;
   dataObjInp_t objOpenInp;
   memset(&objOpenInp, 0, sizeof(objOpenInp));
   objOpenInp.openFlags = open_flag; //O_RDONLY;
   strcpy(objOpenInp.objPath, (char *)obj_path.c_str());
   fd = rcDataObjOpen(conn, &objOpenInp);
   return fd;
}

static void close_irods_obj(rcComm_t *conn, int fd)
{
   openedDataObjInp_t dataObjCloseInp;
   memset (&dataObjCloseInp, 0, sizeof (dataObjCloseInp));
   dataObjCloseInp.l1descInx = fd;
   int t = rcDataObjClose (conn, &dataObjCloseInp);
   if(t < 0)  // just flag the error.
   {
      log_error_code(t);
   }
}

static void write_log(string& str) {
   write(log_fd, (char *)str.c_str(), str.length());
}

static void log_error_code(int errCode)
{
   char *errName = NULL;
   char *errSubName = NULL;
   char buff[2048];
  
   errName = rodsErrorName(errCode, &errSubName);
   sprintf(buff, "%s: %s", errName, errSubName);
   write(log_fd, buff, strlen(buff));
}

static rcComm_t* connect_irods_server(RodsEnvClass & env)
{
   rcComm_t *src_conn = NULL;
   rErrMsg_t errMsg;
   int t;

   src_conn = rcConnect ((char *)env.irodsHost.c_str(), env.irodsPort, (char *)env.irodsUserName.c_str(), 
                         (char *)env.irodsZone.c_str(), NO_RECONN, &errMsg);
   if(src_conn != NULL)
   {
      t = clientLoginWithPassword(src_conn, (char *)env.irodsUserPasswd.c_str()); 
      if(t < 0)
      {
         if(src_conn != NULL)
           freeRcComm(src_conn);

         log_error_code(t);
         return NULL;
      }
   }

   return src_conn;
}

static void print_env(RodsEnvClass & env)
{
   cout << " host:" << env.irodsHost << endl;
   cout << " port:" << env.irodsPort << endl;
   cout << " username:" << env.irodsUserName << endl;
   cout << " password:" << env.irodsUserPasswd << endl;
   cout << " resc:" << env.irodsDefResource << endl;
   cout << " zone:" << env.irodsZone << endl;
}

static void print_config_info(RodsEnvClass & sEnv, RodsEnvClass & dEnvs)
{
   cout << "Debug=" << Debug << endl;;
   cout << "=======the source irods===========\n";
   print_env(sEnv);

   cout << "=======the dest irods=============\n";
   print_env(dEnvs);
}

static int get_config(string& fname, RodsEnvClass& srcEnv, RodsEnvClass& destEnv) {
   string line;
   ifstream fin(fname.c_str());
   if(!fin.is_open())
   {
      cerr << "ERROR: failed to open config file for read : " << fname << endl;
      return -1;
   }

   string s;
   while(fin.good()) {
      getline(fin, line);
      
      line = stringtrim(line);

      if(line[0] == '#')
        continue;

      if(line.length() <= 0)
        continue;

      s = "Debug=";
      if(line.find(s) != string::npos) {
         string val = line.substr(s.length());
	 val = stringtrim(val);
         if(val.compare("yes") == 0)
	   Debug = true;
      }

      s = "srcRodsHost=";
      if(line.find(s) != string::npos) {
         srcEnv.irodsHost = line.substr(s.length());
      }

      s = "srcRodsPort=";
      if(line.find(s) != string::npos) {
         string ts = line.substr(s.length());
         srcEnv.irodsPort = atoi(ts.c_str());
      }

      s = "srcRodsDefResource=";
      if(line.find(s) != string::npos) {
         srcEnv.irodsDefResource = line.substr(s.length());
      }

      s = "srcRodsUserName=";
      if(line.find(s) != string::npos) {
         srcEnv.irodsUserName = line.substr(s.length());
      }

      s = "srcRodsUserPasswd=";
      if(line.find(s) != string::npos) {
         srcEnv.irodsUserPasswd = line.substr(s.length());
      }

      s = "srcRodsZone=";
      if(line.find(s) != string::npos) {
         srcEnv.irodsZone = line.substr(s.length());
      }

      s = "srcRodsSourceCollection=";
      if(line.find(s) != string::npos) {
         srcEnv.topCollection = line.substr(s.length());
      }
      
      s = "destRodsHost=";
      if(line.find(s) != string::npos) {
         destEnv.irodsHost = line.substr(s.length());
      }

      s = "destRodsPort=";
      if(line.find(s) != string::npos) {
         string ts = line.substr(s.length());
         destEnv.irodsPort = atoi(ts.c_str());
      }
    
      s = "destRodsDefResource=";
      if(line.find(s) != string::npos) {
         destEnv.irodsDefResource = line.substr(s.length());
      }

      s = "destRodsUserName=";
      if(line.find(s) != string::npos) {
         destEnv.irodsUserName = line.substr(s.length());
      }

      s = "destRodsUserPasswd=";
      if(line.find(s) != string::npos) {
         destEnv.irodsUserPasswd = line.substr(s.length());
      }

      s = "destRodsZone=";
      if(line.find(s) != string::npos) {
         destEnv.irodsZone = line.substr(s.length());
      }

      s = "destRodsDestinationCollection=";
      if(line.find(s) != string::npos) {
         destEnv.topCollection = line.substr(s.length());
      }
   }
   fin.close();

   return 0;
}

static void debug_msg(string& msg)
{
   if(Debug)
   {
      string dmsg = string("DEBUG: ") + msg;
      write(log_fd, (char *)dmsg.c_str(), dmsg.length());
   }
}

static int query_source_collection(rcComm_t *conn, 
		RodsEnvClass& srcEnv, string& topColl, bool recursive,
		vector<string>& src_files) {

   char query_str[2048];
   if(recursive) {
      sprintf(query_str, "select COLL_NAME, DATA_NAME where COLL_NAME like '%s%%'", topColl.c_str());
   }
   else {
      sprintf(query_str, "select COLL_NAME, DATA_NAME where COLL_NAME = '%s'", topColl.c_str());
   }

   genQueryInp_t genQueryInp;
   memset (&genQueryInp, 0, sizeof (genQueryInp_t));
   int t = fillGenQueryInpFromStrCond(query_str, &genQueryInp);
   if(t < 0) {
      return t;
   }

   genQueryOut_t *genQueryOut = NULL;

   genQueryInp.maxRows= MAX_SQL_ROWS;
   genQueryInp.continueInx=0;
   t = rcGenQuery (conn, &genQueryInp, &genQueryOut);
   if(t < 0) {
      if(t == CAT_NO_ROWS_FOUND) // no file is found
        return 0;

      return t;
   }

   sqlResult_t *collNameStruct, *dataNameStruct;
   string collName, dataName, irodsFilenameWithPath;
   bool loop_stop = false;
   while ((t == 0) && (!loop_stop)) {
      for(int i=0;i<genQueryOut->rowCnt; i++) {
         collNameStruct = getSqlResultByInx (genQueryOut, COL_COLL_NAME);
         dataNameStruct = getSqlResultByInx (genQueryOut, COL_DATA_NAME);

         collName = &collNameStruct->value[collNameStruct->len*i];
         dataName = &dataNameStruct->value[dataNameStruct->len*i];

         irodsFilenameWithPath = collName + "/" + dataName;

         src_files.push_back(irodsFilenameWithPath);
      }

      if(genQueryOut->continueInx == 0) {
         loop_stop = true;
      }
      else {
         genQueryInp.continueInx=genQueryOut->continueInx;
         t = rcGenQuery (conn, &genQueryInp, &genQueryOut);
      }
   }

   freeGenQueryOut(&genQueryOut);
   return 0;
}

/*
static void print_coll_info(vector<string> & files) {
   if(files.size() == 0) {
      cerr << "no files found in the source collection.\n";
   }

   for(size_t i=0; i < files.size(); i++) {
      cerr << files[i] << endl;
   }
}
*/

static void construct_dest_files(RodsEnvClass& srcEnv, vector<string>& srcFiles, RodsEnvClass& destEnv, vector<string>& destFiles) {
   int t = srcEnv.topCollection.length();
   for(size_t i=0; i< srcFiles.size(); i++) {
      string filename = destEnv.topCollection + "/" + srcFiles[i].substr(t+1);
      destFiles.push_back(filename);
   }
}

static void program_start_msg() {
   stringstream ss;
   ss << "The program started at: "  << getcurtime() << endl;
   string msg = ss.str();
   write_log(msg);
}

static void program_exit_msg() {
   stringstream ss;
   ss << "Files replicated: " << totalNumFilesReplicated << endl;
   ss << "Files added new user-defined metadata: " << totalNumFilesAddedNewMetadata << endl;
   ss << "Files skipped (already exist in replcation server): " << totalNumFilesSkiped << endl;
   ss << "Files failed to be replicated: " << totalNumFilesFailedToReplicate << endl;
   ss << "The program exit at: " << getcurtime() << endl << endl;
   string msg = ss.str();
   write_log(msg);
}

static void PrintUsage(void) {
   cerr << "Usage: CrossZoneRepl -configfile=[configfilename]\n";
}

static void write_log_and_exit(string& msg) {
   write_log(msg);
   program_exit_msg();
   exit(0);
}
