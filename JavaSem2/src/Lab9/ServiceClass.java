package Lab9;



import java.io.IOException;
import java.io.PrintWriter;
import java.net.Socket;

import java.util.Scanner;
import java.util.ArrayList;

public class ServiceClass implements Runnable {

	
		private Socket s;
		private Scanner in;
		private PrintWriter out;
		ArrayList<String> Tockens;//=new ArrayList<String>();

		ServiceClass(Socket s,ArrayList<String> Tockens) {//construtor
			this.s = s;
			this.Tockens=Tockens;
		}

		public void run() {//runs from tread
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
				if(request.equals("Quit")) // ends connection to this client (not to other clients)
					return;
				else
					handleRequest(request);
			}
		}

		public void handleRequest(String request) {
			String x = in.next();
			
			 if(Tockens.size()>=10) {
				out.println("Error");//after 10 go in here 
				
			} else if(request.equals("Submit")) {
				out.println("ok");
				Tockens.add(x);
			} else
				System.err.println("Unknown request!");
			out.flush();
		}

	}
