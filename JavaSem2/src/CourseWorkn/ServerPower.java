package CourseWorkn;

import java.io.BufferedReader;
import java.io.BufferedWriter;
import java.io.FileReader;
import java.io.FileWriter;
import java.io.IOException;
import java.io.PrintWriter;
import java.net.ServerSocket;
import java.net.Socket;
import java.util.ArrayList;
import java.util.Scanner;
import javax.imageio.IIOException;
public class ServerPower {

	

	/**
	 * @param args
	 */
	public static void main(String[] args) throws IOException {
		ServerSocket server = new ServerSocket(8888);
		
		
		System.out.println("Server running. Waiting for clients to connect...");
		Scanner s=null;
		
		
		
		while(true) {

			Socket soc = server.accept();
			
			System.out.println("Client connected");

			myServiceClass service = new myServiceClass(soc); // instantiate the task class
			
			//service.run();
			
			
			Thread t = new Thread(service);  // instantiate the thread with the task class object
			
			t.start();  // start the client-handling thread 

		}
		
			
		}
		}
	

