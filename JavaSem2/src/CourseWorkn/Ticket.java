package CourseWorkn;
import java.io.PrintWriter;
import java.util.ArrayList;
import java.util.List;
import java.util.Scanner;
public class Ticket implements Comparable<Ticket>{
	public String getTname() {
		return Tname;
	}



	public void setTname(String tname) {
		Tname = tname;
	}



	public String getCname() {
		return Cname;
	}



	public void setCname(String cname) {
		Cname = cname;
	}



	public String getDetails() {
		return details;
	}



	public void setDetails(String details) {
		this.details = details;
	}



	public int getPiority() {
		return piority;
	}



	public void setPiority(int piority) {
		this.piority = piority;
	}



	public String getStatus() {
		return status;
	}



	public void setStatus(String status) {
		this.status = status;
	}
	private String Tname;
	private String Cname;
	private String details;
	private int piority;
	private String status;
	
	public Ticket(String Tname,String Cname,String details,int piority,String status)
	{
	this.Tname=Tname;
	this.Cname=Cname;
	this.details=details;
	this.piority=piority;
	this.status=status;
	}

	

	



	@Override
	public int compareTo(Ticket p) {
		 if (piority < p.piority) {
	            return -1;
	        }

	        if (piority > p.piority) {
	            return 1;
	        }
		return 0;
	}
	}



