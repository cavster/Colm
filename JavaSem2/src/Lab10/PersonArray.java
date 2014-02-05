package Lab10;


import java.util.ArrayList;

import javax.swing.JTable;
import javax.swing.table.DefaultTableModel;


public class PersonArray {

public  ArrayList<Person> ArrayPerson = new ArrayList<Person>();

	 public  ArrayList<Person> getArrayPerson() {
		return ArrayPerson;
	}


	String value="null";
	 public void CreateArray(String Fname,String Lname,String Street,String HouseNumber,String city,String Email){
		
		 Person a= new Person(Fname,Lname,Street,HouseNumber,city,Email);
		 ArrayPerson.add(a);
	
	 }
	 
	 @Override
	 public String toString(){	 
		 return ArrayPerson.toString(); 
	 }
	 
	//loop through all elements of arry
		 //in the loop - send in each element for printing
	 
	
		

	public String printArray(){
		String value = null;
		for (Person n : ArrayPerson)
		value=n.getFname()+" "+n.getLname()+" "+n.getStreet()+" "+n.getHouseNumber()+ " "+n.getCity()+" "+n.getEmail();//for every person in array list will perform this action
		return value;
		
	}
	  
public void fillRow(){
	for (Person n : ArrayPerson){
		Object [][] model = {{n.getFname(), n.getLname(),n.getHouseNumber(),n.getCity(),n.getStreet(),n.getEmail()}};
		
}
}
}
	


	
	 
	 
	 

	


