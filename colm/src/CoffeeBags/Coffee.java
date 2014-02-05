
package CoffeeBags;
import java.util.Scanner;
public class Coffee {
//This the main method of the Coffee Package
	//It is calling on other classes to do the work
	//colm cavanagh
	//12231055
	/**
	 * @param args
	 */
	public static void main(String[] args) {
		
		int	noOfBagsSold;
		double	weight;
		// TODO Auto-generated method stub
		
		Scanner input=new Scanner(System.in);
	System.out.println("enter No of Bags Purchased");
	noOfBagsSold=input.nextInt();
	System.out.println("enter weight of the bags");
	weight=input.nextDouble();
	Calculations bag=new Calculations(noOfBagsSold,weight);//going into the Calculations class and passing in these local values
	System.out.printf("Number of bags sold %d\n ",noOfBagsSold);
	System.out.printf("Weight per bag %.2f \n",weight);
	System.out.println("Price Per Pound 5.5");
	System.out.println("Sales Tax 10%\n");
	System.out.printf("Total Price: $%.2f",bag.gettotalPriceWithTax());//calling method form other class
	}

}
