//	Copyright (c) 2005, Regents of the University of California
//	All rights reserved.
//
//	Redistribution and use in source and binary forms, with or without
//	modification, are permitted provided that the following conditions are
//	met:
//
//	  * Redistributions of source code must retain the above copyright notice,
//	this list of conditions and the following disclaimer.
//	  * Redistributions in binary form must reproduce the above copyright
//	notice, this list of conditions and the following disclaimer in the
//	documentation and/or other materials provided with the distribution.
//	  * Neither the name of the University of California, San Diego (UCSD) nor
//	the names of its contributors may be used to endorse or promote products
//	derived from this software without specific prior written permission.
//
//	THIS SOFTWARE IS PROVIDED BY THE COPYRIGHT HOLDERS AND CONTRIBUTORS "AS
//	IS" AND ANY EXPRESS OR IMPLIED WARRANTIES, INCLUDING, BUT NOT LIMITED TO,
//	THE IMPLIED WARRANTIES OF MERCHANTABILITY AND FITNESS FOR A PARTICULAR
//	PURPOSE ARE DISCLAIMED. IN NO EVENT SHALL THE COPYRIGHT OWNER OR
//	CONTRIBUTORS BE LIABLE FOR ANY DIRECT, INDIRECT, INCIDENTAL, SPECIAL,
//	EXEMPLARY, OR CONSEQUENTIAL DAMAGES (INCLUDING, BUT NOT LIMITED TO,
//	PROCUREMENT OF SUBSTITUTE GOODS OR SERVICES; LOSS OF USE, DATA, OR
//	PROFITS; OR BUSINESS INTERRUPTION) HOWEVER CAUSED AND ON ANY THEORY OF
//	LIABILITY, WHETHER IN CONTRACT, STRICT LIABILITY, OR TORT (INCLUDING
//	NEGLIGENCE OR OTHERWISE) ARISING IN ANY WAY OUT OF THE USE OF THIS
//	SOFTWARE, EVEN IF ADVISED OF THE POSSIBILITY OF SUCH DAMAGE.
import edu.sdsc.grid.io.irods.IRODSFileSystem;
import edu.sdsc.grid.io.srb.SRBFileSystem;
import edu.sdsc.grid.io.local.*;
import edu.sdsc.grid.io.*;


import java.io.*;
import java.net.URI;
import java.util.*;

/**
 * Creates 5000+ files at the source location. Copy them to the remote
 * destination. Then copy them back to a new subdirectory in the source
 * location. Finally compare the md5 checksums to insure the transfer
 * was successful.
 */
public class CopyTest
{
	//following checks the md5sum
	//won't work without the md5sum proxy command
	static final String checksum = "md5sum";

  //Avoid name conflicts at destination:
	static String filePath = "myJargonLargeFilesCopyTest";      
  
	GeneralFile source, remoteDestination, returnDestination;


	/**
	 * Creates 5000+ files at the source location. Copy them to the remote
	 * destination. Then copy them back to a new subdirectory in the source
	 * location. Finally compare the md5 checksums to insure the transfer
	 * was successful.
	 */
	public CopyTest( GeneralFile source, GeneralFile remoteDestination,
          GeneralFile returnDestination )
		throws IOException
	{ 
		if (source.mkdir()) {
			GeneralRandomAccessFile raf = null;
			for (double i=0;i<5015;i++) {
				raf = FileFactory.newRandomAccessFile(
					FileFactory.newFile( source, "f"+i ), "rw" );
				raf.write(i+"\n");
				raf.write(new byte[(int)i]);
				raf.write(i+"\n");
				raf.write(new byte[(int)i]);
				raf.write(i+"\n");
				raf.close();
			}
			raf = FileFactory.newRandomAccessFile(
				FileFactory.newFile( source, "fbig1" ), "rw" );
			for (int i=0;i<10;i++) {
				raf.write(i+" ");
				raf.write( new byte[1000000] );
			}
			raf.close();
			raf = FileFactory.newRandomAccessFile(
				FileFactory.newFile( source, "fbig2" ), "rw" );
			for (int i=0;i<65;i++) {
				raf.write(i+" ");
				raf.write( new byte[1000000] );
			}
			raf.close();
		}

		this.source = source;
    if (remoteDestination != null) {
      this.remoteDestination = remoteDestination;
    }
    else {
      IRODSFileSystem fileSystem = new IRODSFileSystem();
      //Find an empty space. Keep the old ones, just in case.
      this.remoteDestination = FileFactory.newFile( fileSystem, 
        "/tempZone/home/"+fileSystem.getUserName(), filePath+stuff() );
    }
        
    if (returnDestination != null) {
      this.returnDestination = returnDestination;
    }
    else {
      //Find an empty space. Keep the old ones, just in case.
      this.returnDestination = FileFactory.newFile( source.getParentFile(),
        filePath+"download"+stuff() );
    }    
	}
  
  
  public GeneralFile getSource() {
    return source;
  }
  public GeneralFile getRemoteDestination() {
    return remoteDestination;
  }
  public GeneralFile getReturnDestination() {
    return returnDestination;
  }

  
	/**
	 * Uploads then downloads the files
	 */
	public void copy()
		throws IOException
	{
    boolean overwrite = true;
		double time = new Date().getTime();
		System.out.println("remote: "+remoteDestination+" time: "+time);
		remoteDestination.copyFrom(source,overwrite);
		System.out.println("upload time: "+(new Date().getTime()-time));

		remoteDestination.copyTo(returnDestination,overwrite);
		System.out.println("localFile: "+returnDestination);
		System.out.println("total copy time: "+(new Date().getTime()-time));
	}

	/**
	 * Compares the files.
	 *
	 * @return true, if and only if, all md5es match
	 */
	public boolean compare( )
		throws IOException
	{
		return compare( false );
	}

	/**
	 * Compares the files.
	 *
	 * @param remoteMd5 if true compare the source to the remote destination,
	 *  	otherwise compare source to files once again downloaded into
	 *  	source subdirectory.
	 * @return true, if and only if, all md5es match
	 */
	public boolean compare( boolean remoteMd5 )
		throws IOException
	{
		String remoteChk = null;
		String localChk = null;
		Process proc = null;
		InputStream localIn = null, remoteIn = null;
		byte mds[] = new byte[33];
		boolean error = false;

		GeneralFile tempFile = null;

		if (remoteMd5) {
			if (!remoteDestination.exists()) return false;

			//compare source files md5es to md5es on the remote system
			MetaDataCondition conditions[] = {
				MetaDataSet.newCondition( DirectoryMetaData.DIRECTORY_NAME,
					MetaDataCondition.EQUAL, remoteDestination.getAbsolutePath() )
			};

			String[] selectFieldNames = {
				FileMetaData.FILE_NAME,
				FileMetaData.PATH_NAME,
				FileMetaData.SIZE,
			};
			MetaDataSelect selects[] =
				MetaDataSet.newSelection( selectFieldNames );

			//TODO need more remote systems...
			MetaDataRecordList[] rl = 
				remoteDestination.getFileSystem().query( conditions, selects );
			rl = MetaDataRecordList.getAllResults(rl);

      GeneralFileSystem fileSystem = remoteDestination.getFileSystem();
			for (int i=0;i<rl.length;i++) {
				if(rl[i].getIntValue(2) > 0) {
					//TODO other remote systems?
          if (fileSystem instanceof SRBFileSystem) {
  					remoteIn = 
    					((SRBFileSystem)fileSystem).executeProxyCommand(
      					checksum, rl[i].getValue(FileMetaData.PATH_NAME).toString() );
          }
          else {
            //TODO irods
            return true;
          }
					int result = remoteIn.read();
					while (result != -1) {
						remoteChk += ""+(char)result;
						result = remoteIn.read();
					}
					remoteChk += ""+(char)result;
          remoteIn.close();

					tempFile = FileFactory.newFile( source, rl[i].getValue(
							FileMetaData.FILE_NAME ).toString());
					proc = Runtime.getRuntime().exec("md5sum "+
						tempFile.getAbsolutePath());
					localIn = proc.getInputStream();
					localIn.read(mds);
					localChk = new String(mds);
          localIn.close();

//System.out.println("remote md5 "+remoteChk);
//System.out.println("local md5 "+localChk);
					if (remoteChk.indexOf(localChk) < 0) {
						System.out.println( "error "+tempFile );
						error = true;
					}
					remoteChk = "";
				}
				System.out.print(i+" ");
			}
		}
		else {
			if (!returnDestination.exists()) return false;

			//compare source files md5es to local md5es transfered from the remote system
			String[] list = source.list();
			for (int i=0;i<list.length;i++) {
				tempFile = FileFactory.newFile( source, list[i] );
				if (tempFile.isFile()) {
					//find the original md5sum
					proc = Runtime.getRuntime().exec("md5sum "+
						tempFile.getAbsolutePath());
					localIn = proc.getInputStream();
					localIn.read(mds);
					localChk = new String(mds);

					//find the transfered md5sum
					tempFile = FileFactory.newFile(
						returnDestination, tempFile.getName() );
					proc = Runtime.getRuntime().exec("md5sum "+
						tempFile.getAbsolutePath());
					localIn = proc.getInputStream();
					localIn.read(mds);
					remoteChk = new String(mds);

          localIn.close();
//System.out.println("remote md5 "+remoteChk);
//System.out.println("local md5 "+localChk);
					if (!remoteChk.equals(localChk)) {
						System.out.println("error "+tempFile);
						error = true;
					}
				}
			}
		}
		return error;
	}

    
  
//----------------------------------------------------------------------
//  SuperCopy!!!
//----------------------------------------------------------------------
  /**
   * Copy lots of files in parallel. Trying to break things.
   * @param connections number of server connections to start
   * @param treadsEach number of copy threads each connection should run
   * @throws java.io.IOException
   */
  static void superCopy(
    final GeneralFile source, final GeneralFile remote, 
    final int connections, final int threadsEach )
		throws IOException
  {
    Thread[] transferThreads = new Thread[connections];
    for (int i=0;i<connections;i++){
      transferThreads[i] = new Thread(){
        public void run()
        {
          Thread[] copyThreads = new Thread[threadsEach];
          try {
            for (int i=0;i<threadsEach;i++){
              copyThreads[i] = new Thread(){
                public void run()
                {
                  try {
                    GeneralFile s = 
                      FileFactory.newFile(FileFactory.newFileSystem(
                        source.getFileSystem().getAccount()),
                        source.getAbsolutePath()
                    );
                    GeneralFile r = 
                      FileFactory.newFile(FileFactory.newFileSystem(
                        remote.getFileSystem().getAccount()),
                        remote.getAbsolutePath()+stuff()                          
                    );
                    CopyTest copyTest = new CopyTest( s, r, null );
                    copyTest.copy();
                  } catch (Throwable e) {//IOException e) {
                    throw new RuntimeException( "IOException in thread.", e );
                  }
                }
              };
              copyThreads[i].start();
            }
            try {
              for (int i=0;i<threadsEach;i++) {
                if (copyThreads[i].isAlive())
                  copyThreads[i].join();
              }
            }
            catch(InterruptedException e) {
              e.printStackTrace();
            }
          } catch (Throwable e) {//IOException e) {
            throw new RuntimeException( "IOException in thread.", e );
          }
        }
      };
      transferThreads[i].start();
    }

    try {
      for (int i=0;i<connections;i++) {
        if (transferThreads[i].isAlive())
          transferThreads[i].join();
      }
    }
    catch(InterruptedException e) {
      e.printStackTrace();
    }
  }  
  
  private static String stuff( )
  {
    return new Date().getTime()+"_"+(short)(Math.random()*10000);
  }
  
//----------------------------------------------------------------------
//  Main
//----------------------------------------------------------------------
	public static void main(String args[])
	{
    //
    System.setProperty("jargon.debug", "0");
    int err = 0;
		try{
			LocalFile loc = null;
			GeneralFile remote = null, ret = null;

			if ((args != null) && (args.length > 0)) {
				filePath = args[0];
			}

			try {
				loc = new LocalFile( new URI( filePath ) );
				//test to see if loc is a valid filePath
				if (!loc.exists()) {
          if (loc.createNewFile()) {
					  loc.delete();
          }
				}
			} catch (Throwable e) {
        //if not URI
				loc = new LocalFile( System.getProperty("user.home")+
                LocalFile.PATH_SEPARATOR+filePath );
			}

      //upload destination
			if (args.length > 1) {
				remote = FileFactory.newFile( new URI( args[1] ) );
			}

      //download destination
			if (args.length > 2) {
				ret = FileFactory.newFile( new URI( args[2] ) );
			}
      
			CopyTest copyTest = new CopyTest( loc, remote, ret );
			copyTest.copy();
      if (remote instanceof edu.sdsc.grid.io.srb.SRBFile)
        err = copyTest.compare() ? 0 : 1;
      else {     //TODO md5 for irods
        err = MoreTests.fileCompare( 
                copyTest.getSource(), 
                copyTest.getReturnDestination() ) ? 0 : 1;
      }
		}
		catch ( Throwable e ) {
			e.printStackTrace();

			Throwable chain = e.getCause();
			while (chain != null) {
				chain.printStackTrace();
				chain = chain.getCause();
			}
			err = 1;
		}

		System.exit(err);
	}
}
