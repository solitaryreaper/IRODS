#ifndef _AvuMetaData_h_
#define _AvuMetaData_h_

#include "rodsClient.h"
#include "parseCommandLine.h"
#include "rodsPath.h"
#include "cpUtil.h"
#include <string>
#include <vector>

using namespace std;

class AuvMetaData {
public:
   string att;
   string val;
   string unit;
};

int  query_user_metadata(rcComm_t *conn, string& fileName, vector<AuvMetaData>& userMetas); 
void auv_array_rm_dups(vector<AuvMetaData>& newAuvArray, const vector<AuvMetaData>& oldAuvArray);

#endif

