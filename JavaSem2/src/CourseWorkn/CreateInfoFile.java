package CourseWorkn;
import java.io.*;
import java.lang.*;
import java.util.*;
public class CreateInfoFile {

	/**
	 * @param args
	 */
	
private Formatter file;
public void openfile(){
	try{
		file=new Formatter("Record.txt");
		}
catch(Exception e){
	System.out.println("you have an error");
}
}
public void AddRecord(String Tname,String Cname,String details,String piority,String status){
	file.format(Tname,Cname,details,piority,status);
}//used in writeing to the file
public void CloseFile(){
file.close();	

}
}
