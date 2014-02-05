
import java.io.IOException;
import java.net.*;

public class ConversionServer {
	
	public static void main(String[] args) throws IOException {

		ServerSocket server = new ServerSocket(8888);

		System.out.println("Server running. Waiting for clients to connect...");

		while(true) {

			Socket s = server.accept();
			
			System.out.println("Client connected");

			ConversionService service = new ConversionService(s); // instantiate the task class
			
			Thread t = new Thread(service);  // instantiate the thread with the task class object
			
			t.start();  // start the client-handling thread 

		}
	}
}
