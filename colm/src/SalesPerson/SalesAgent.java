package SalesPerson;
//this is the agent class
public class SalesAgent extends SalesPerson 
{

		public  SalesAgent(String name,int pps) 
		{
			super(name,pps);
		}
			public double getComision( )
		{
				
				totalComision=totalSale*0.15;
			return totalComision;
		}


}
