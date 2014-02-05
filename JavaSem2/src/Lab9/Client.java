package Lab9;




import java.io.IOException;
import java.io.PrintWriter;

import java.io.InputStream;
import java.io.OutputStream;
import java.util.Scanner;
import java.net.Socket;



public class Client {

	/**
	 * @param args
	 */
public static void main(String[] args) throws IOException
{
Socket s=new Socket("localhost",2222);
InputStream instream=s.getInputStream();
OutputStream outstream=s.getOutputStream();

Scanner in=new Scanner(instream);
PrintWriter out=new PrintWriter(outstream);//declareing input outstreams 
for(int i=0;i<11;i++)//looping in 10 tokens 
{
	
	String r ="Submit myToken\n";
	out.print(r);//sending in String to server as stream
	out.flush();
	String responce=in.next();
	System.out.println("Hi my responce is "+responce+i);
}
String r = "Quit\n";
out.print(r);

out.flush();
s.close();

}
	


	}












