package CourseWorkn;

import java.io.BufferedReader;
import java.io.BufferedWriter;
import java.io.File;
import java.io.FileNotFoundException;
import java.io.FileReader;
import java.io.FileWriter;
import java.io.IOException;
import java.io.PrintWriter;
import java.net.Socket;
import java.io.IOException;
import java.util.Arrays;
import java.util.Scanner;
import java.util.ArrayList;
import java.util.StringTokenizer;
import java.util.Collections;
import java.util.Comparator;
public class myServiceClass implements Runnable  {
	
	
	ClientManager cm=new ClientManager();
		private Socket soc;
		private Scanner in;
		private PrintWriter out;
		Scanner s=null;
		
		myServiceClass(Socket s) {//contrutor for setting up the socket
			this.soc = s;
		}
		
		
		public void run() {
			try {
				try {
					in = new Scanner(soc.getInputStream());
					out = new PrintWriter(soc.getOutputStream());
					
					doService(); // the actual service            
				} finally {
					soc.close();
				}
			} catch (IOException exception) {
				exception.getMessage();
			}
		}

		public void doService() throws IOException {
			while(true) {
				if(!in.hasNext()){//while theres words in the strings
					return;}
				String request = in.nextLine();//next word is taken i have spaces in the line so someone can use many words for one of my varables
				System.out.println("Request received got it: " + request);
			
			
			    
				if(request.equals("Quit")){ // ends connection to this client (not to other clients)
					return;}
				else{
					
					handleRequest(request);
					}
			}
		}
	
			
			
		public void handleRequest(String request) throws FileNotFoundException {
			
			
		if(request.equals("Submit")){
			//after the frist word the rest are broken up into varables that the method uses to write to the file
			 String Tname=in.nextLine();
			 String Cname=in.nextLine();
			  String details=in.nextLine();
			 int piority=in.nextInt();
			 String status=in.next();
			 String Blank=in.nextLine();
		
				try{
				    
				 final  File file = new File("Record.txt");
				    FileWriter out1 = new FileWriter(file.getAbsoluteFile(),true); //the true will append the new data instead of wipeing out table

				    BufferedWriter out2 = new BufferedWriter(out1);
				    //appends the string to the file
				        
			
			String responceFromSever="Person added to array";//setting the responce
			out2.write(Tname);//varables are read into file using 
			out2.write(",");
			out2.write(Cname);
			out2.write(",");
			out2.write(details);
			out2.write(",");
			out2.write(String.valueOf(String.valueOf(piority)));
			out2.write(",");
			out2.write(status);
			out2.write("\n");
		
			
				out2.close();
			 out.println(responceFromSever);//sending responce back so user knows it got added
			 out.flush();
			 System.out.println("Request recived:"+ request);
			 out.flush();
			} catch (IOException e1) {
				e1.printStackTrace();
			}
			 
			
		}
		else if(request.equals("Print")){
			Scanner scan = new Scanner(new BufferedReader(new FileReader("Record.txt")));//reads in text file
			
			while (scan.hasNextLine())
			{
				out.println(scan.nextLine());
			}
			out.println("end");
			
			out.flush();
			
		}
		
		else{
			System.out.println("Unknown request!");
			  String Responce="fuck off";
			  out.println(Responce);
			out.flush();
		}

		}
	

}