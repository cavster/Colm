import java.io.IOException;
import java.io.InputStream;
import java.io.OutputStream;
import java.io.PrintWriter;
import java.net.Socket;
import java.util.Scanner;

public class ClientProgram {

	public static void main(String[] args) throws IOException {
		
	     Socket s = new Socket("localhost", 8888);  // Connect to server (host localhost, port 8888)
	     
	     InputStream instream = s.getInputStream();
	     
	     OutputStream outstream = s.getOutputStream();
	     
	     Scanner in = new Scanner(instream);
	     
	     PrintWriter out = new PrintWriter(outstream); 
	     
	     String request = "CONVERT_TO_KGS 123\n";
	     
	     System.out.println("Sending: " + request);
	     
	     out.print(request);
	     
	     out.flush();
	     
	     String response = in.nextLine();
	     
	     System.out.println("Receiving: " + response + "\n\n");  
	     
	     // Submit another request...
	     
	     request = "CONVERT_TO_POUNDS 45\n";
	     
	     System.out.println("Sending: " + request);
	     
	     out.print(request);
	     
	     out.flush();
	     
	     response = in.nextLine();
	     
	     System.out.println("Receiving: " + response + "\n\n");  
	     
	     request = "QUIT\n";
	     
	     System.out.print("Sending: " + request);
	     	     	     
	     out.print(request);
	     
	     out.flush();
	     
	     s.close(); 
	     
	}

}