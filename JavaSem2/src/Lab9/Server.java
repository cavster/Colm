package Lab9;





import java.io.IOException;
import java.io.PrintWriter;
import java.net.ServerSocket;
import java.net.Socket;
import java.util.ArrayList;
import java.util.Scanner;
import javax.imageio.IIOException;
public class Server {

	

	/**
	 * @param args
	 */
	public static void main(String[] args) throws IOException {
		ServerSocket server = new ServerSocket(2222);
		ArrayList<String> Tockens=new ArrayList<String>();//to pervent concurrentcy decalred outside
		System.out.println("Server running. Waiting for clients to connect...");

		while(true) {

			Socket s = server.accept();//listens for client
			
			System.out.println("Client connected");

			ServiceClass service = new ServiceClass(s,Tockens); // instantiate the task class
			
			//service.run();
			
			
			Thread t = new Thread(service);  // instantiate the thread with the task class object
			
			t.start();  // start the client-handling thread 

		}
	}
}

		
		
		
		
		
		
		
	


