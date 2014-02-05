package SalesPerson;
//This is the sales person super class the other two classes are types of persons
abstract class SalesPerson
{
	private String name;	
	private int pps;
	protected double totalSale;
	protected double totalComision;

	public SalesPerson(String salesPersonName,int ppsNumber) 
	{
		name=salesPersonName;
		pps=ppsNumber;
	}
	public String getName(){
		return name;
	}
	public int getpps(){
		return pps;
	}

	abstract double getComision();//abstract so the other two classes must have this method


	public void setSales(double saleAmount) {
		totalSale = saleAmount;
	}



}
