import java.io.IOException;
import java.io.PrintWriter;
import java.net.*;
import java.util.Scanner;

public class ConversionServerNoConcurrency {
	
	private Socket s;
	private Scanner in;
	private PrintWriter out;
	
	public static void main(String[] args) throws IOException {

		ServerSocket server = new ServerSocket(8888);
		
		ConversionServerNoConcurrency serverInstance = new ConversionServerNoConcurrency();

		System.out.println("Server running. Waiting for a client to connect...");

		while(true) {	
			
			serverInstance.s = server.accept();
			
			System.out.println("Client connected");
			
			serverInstance.run();
			
			System.out.println("Client disconnected. Waiting for a new client to connect...");

		}
	}
	
	public void run() {
		try {
			try {
				in = new Scanner(s.getInputStream());
				out = new PrintWriter(s.getOutputStream());
				doService(); // the actual service            
			} finally {
				s.close();
			}
		} catch (IOException e) {
			System.err.println(e);
		}
	}

	public void doService() throws IOException {
		while(true) {
			if(!in.hasNext())
				return;
			String request = in.next();
			System.out.println("Request received: " + request);
			if(request.equals("QUIT")) // ends connection to this client (not to other clients)
				return;
			else
				handleRequest(request);
		}
	}

	public void handleRequest(String request) {
		double amount = in.nextDouble();
		if(request.equals("CONVERT_TO_POUNDS")) {
			out.println(amount * 2.2d); //server response
		} else if(request.equals("CONVERT_TO_KGS")) {
			out.println(amount / 2.2d); //server response
		} else
			System.err.println("Unknown request!");
		out.flush();
	}

}