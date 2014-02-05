import java.util.Scanner;
//Colm Cavanagh
//This class asks the user for the adverage rainfall for the last 12 mouths
//and then makes an array and calculates the adverage
public class AdverageRainfal 
{
	public static void main(String[] args)
	{
		Scanner input=new Scanner(System.in);
		double adverageRainfall[]=new double[12];//12 numbers in array	
		int i;
		double sum=0;
		double adverage;
		for(i=0;i<adverageRainfall.length;i++)
		{
		System.out.printf("enter rainfall in cm for mounth %d ",(i+1));//i=1 so mouths occur correctly
		adverageRainfall[i]=input.nextDouble();//asking user to fill up array index
		sum =sum + adverageRainfall[i];//getting sum
		}
		adverage=sum/12;//getting adverage
		System.out.printf("Annual Adverage Rainfall %.2f",adverage);
	}
}
