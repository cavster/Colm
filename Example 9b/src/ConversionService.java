import java.io.IOException;
import java.io.PrintWriter;
import java.net.Socket;
import java.util.Scanner;

public class ConversionService implements Runnable { // task class
	private Socket s;
	private Scanner in;
	private PrintWriter out;

	ConversionService(Socket s) {
		this.s = s;
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
		} catch (IOException exception) {
			exception.getMessage();
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