/* The program is used to make replication across iRODS zones from UAL to OCUL.
 * Author: Bing Zhu
 * Date Created: May 17, 2010
 * Version 1.1
 */

#include "utils.h"

using namespace std;

string getcurtime() {
   time_t rawtime;
   struct tm * timeinfo;
   time(&rawtime);
   timeinfo = localtime(&rawtime);
   string timestr = asctime(timeinfo);
   int n = timestr.length();
   if((n > 0) && (timestr[n-1] == '\n')) {
        timestr = timestr.substr(0, n-1);
   }
   return timestr;
}

string stringtrim(string& str) {
   string drop = " ";
   std::string r = str.erase(str.find_last_not_of(drop)+1);
   r = r.erase(0, r.find_first_not_of(drop));
   return r;
}
