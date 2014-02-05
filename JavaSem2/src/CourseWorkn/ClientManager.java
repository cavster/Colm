package CourseWorkn;
import java.io.BufferedReader;
import java.io.FileReader;
import java.util.ArrayList;
import java.util.Arrays;
import java.util.Collections;
import java.util.Scanner;

import Lab10.Person;
public class ClientManager {
//ended up not using this code but I put it in anyway to show an altertive approch i was trying earlier with a pure array list solution to the problem
	private static ArrayList<Ticket> myArray = new ArrayList<Ticket>();
	 Scanner scan=null;
	 
	 public void ClearArray()
	 {
		 myArray.clear();
	 }
	 
	 public void CreateArray(String Tname,String Cname,String details,int piority,String status){
		
		 Ticket a= new  Ticket(Tname,Cname,details,piority,status);
		 myArray.add(a);		 
	
	 }

	public ArrayList getMyArray() {
		return myArray;
	}

	public void setMyArray(ArrayList myArray) {
		this.myArray = myArray;
	}
	
	public String printArray(){
		System.out.println("got to printArray!");
		Collections.sort(myArray, new Comparer());
		String value = "";
		for (Ticket n : myArray)
			value+=n.getTname()+"@#"+n.getCname()+"@#"+n.getDetails()+"@#"+n.getPiority()+"@#"+n.getStatus() + "\n";//for every person in array list will perform this action
		return value;
		
	}
	
}
