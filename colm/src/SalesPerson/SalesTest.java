package SalesPerson;
import java.text.DecimalFormat;
import java.util.Scanner;
public class SalesTest 
{
//Colm Cavangh
//12/11/2012
//This program asks the user to total sales for his five employes and calculates there comission 
//based on what sort of employee they are	


	public static void main(String[] args)
	{
		DecimalFormat df =new DecimalFormat("0.00");//This makes it so it only has 2 decimals
		Scanner input=new Scanner(System.in);
		SalesPerson[] sale= new SalesPerson[5];//array of class sales person
		sale[0] = new SalesAgent("Jane Doe", 1245);
		sale[1] = new SalesEmployee("Ringo Star", 1234,34);
		sale[2] = new SalesEmployee("John Doe", 1342,45);
		sale[3] = new SalesAgent("George Harrison", 3212);
		sale[4] = new SalesAgent("John Lennon", 1432);

		for (int i = 0; i < sale.length; i++) 
		{
			System.out.printf("Enter sales  for salesPerson %d : ", i+1);
			sale[i].setSales(input.nextDouble());//filling a value for total sales for each one
		}



		for (int i = 0; i < sale.length; i++) 
		{
			System.out.println(sale[i].getName() + " " + sale[i].getpps() + "£" + df.format(sale[i].getComision()) + "  ");
		}

	}
}







