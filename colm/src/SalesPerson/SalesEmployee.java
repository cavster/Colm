package SalesPerson;

public class SalesEmployee extends SalesPerson
{
double hoursworked;
	public SalesEmployee(String name,int pps,double hoursworked) 
	{
		super(name,pps);
		this.hoursworked=hoursworked;
	}
		public double getComision(){
		totalComision=totalSale*(.1);
		return totalComision;
	}

}
