#include "AvuMetaData.h"
#include <iostream>
#include <sstream>
#include <fstream>
#include <string>
#include <vector>
using namespace std;

using namespace std;

#define MAX_SQL 300
#define BIG_STR 200

int query_user_metadata(rcComm_t *conn, string& fileName, vector<AuvMetaData>& userMetas) {
   genQueryInp_t genQueryInp;
   genQueryOut_t *genQueryOut;
   int i1a[10];
   int i1b[10];
   int i2a[10];
   char *condVal[10];
   char v1[BIG_STR];
   char v2[BIG_STR];

   int t = fileName.rfind('/');
   string parent_coll = fileName.substr(0, t);
   string data_name = fileName.substr(t+1);

   // cout << "parent_coll=" << parent_coll << endl;
   // cout << "data_name=" << data_name << endl;

   i1a[0]=COL_META_DATA_ATTR_NAME;
   i1b[0]=0;
   i1a[1]=COL_META_DATA_ATTR_VALUE;
   i1b[1]=0;
   i1a[2]=COL_META_DATA_ATTR_UNITS;
   i1b[2]=0;

   memset (&genQueryInp, 0, sizeof (genQueryInp_t));

   genQueryInp.selectInp.inx = i1a;
   genQueryInp.selectInp.value = i1b;
   genQueryInp.selectInp.len = 3;

   i2a[0]=COL_COLL_NAME;
   sprintf(v1,"='%s'", parent_coll.c_str());
   condVal[0]=v1;

   i2a[1]=COL_DATA_NAME;
   sprintf(v2,"='%s'", data_name.c_str());
   condVal[1]=v2;

   genQueryInp.sqlCondInp.inx = i2a;
   genQueryInp.sqlCondInp.value = condVal;
   genQueryInp.sqlCondInp.len=2;

   genQueryInp.condInput.len=0;
   genQueryInp.continueInx=0;

   genQueryInp.maxRows= MAX_SQL_ROWS;
   genQueryInp.continueInx=0;

   bool stop_query = false;
   while(!stop_query) {

      t = rcGenQuery(conn, &genQueryInp, &genQueryOut);
      if(t == CAT_NO_ROWS_FOUND) {
         return 0;
      }

      if(t < 0) {
         return t;
      }
      
      // get the AVUs
      for(int i=0;i<genQueryOut->rowCnt;i++) {
	 AuvMetaData md;
	 md.att = genQueryOut->sqlResult[0].value + i*genQueryOut->sqlResult[0].len;
	 md.val = genQueryOut->sqlResult[1].value + i*genQueryOut->sqlResult[1].len;
	 md.unit = genQueryOut->sqlResult[2].value + i*genQueryOut->sqlResult[2].len;
	 userMetas.push_back(md);
      }
       
      if(genQueryOut->continueInx  > 0) {
         genQueryInp.continueInx=genQueryOut->continueInx;
      }
      else {
         freeGenQueryOut(&genQueryOut);
         stop_query = true;
      }
   }

   return 0;
}

static bool insideAuvArray(const AuvMetaData& auvMeta, const vector<AuvMetaData>& auvMetaArray) {
   for(size_t i=0; i< auvMetaArray.size(); i++) {
      if((auvMeta.att == auvMetaArray[i].att) && (auvMeta.val == auvMetaArray[i].val)
      && (auvMeta.unit == auvMetaArray[i].unit)) {
          return true;
      }
   }

   return false;
}

void auv_array_rm_dups(vector<AuvMetaData>& newAuvArray, const vector<AuvMetaData>& oldAuvArray) {
   if(oldAuvArray.size() <= 0)
     return;

   if(newAuvArray.size() <= 0)
     return;

   bool done = false;
   size_t i = 0;
   while(!done) {
      if(insideAuvArray(newAuvArray[i], oldAuvArray)) {
         newAuvArray.erase(newAuvArray.begin() + i);
      }
      else {
         i = i + 1;
      }
      if(i >= newAuvArray.size()) {
         done = true;
      }
   }
}
