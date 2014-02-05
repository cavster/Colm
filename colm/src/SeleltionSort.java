import java.util.Scanner;
public class SeleltionSort 
{

	//colm cavanagh
	//This method asks the user to summit some numbers and then sorts them using selection sort
	//
	public static void main(String[] args) 
	{

		{

			Scanner input=new Scanner(System.in);
			int[] numbers=new int[20];
			int i = 0;
			int x=0;
			while (x!=-1)//asks users to enter in numbers
			{
				System.out.printf("enter  numbers");
				x=input.nextInt();
				if(x!=-1)
					numbers[i]=x;
					i++;
			}
			
			System.out.println("Array before sorting");
			PrintNumbers(numbers); 
			numbers=selectionSort(numbers);//changes numbers to sorted
			System.out.println("\nArray after sorting");
			PrintNumbers(numbers);	
		}}


	public static void PrintNumbers(int[] b)
	{
		
		for (int i = 0; i < b.length; i++)
		{
			if (b[i]!=0)
			System.out.printf(b[i] + ",");
		}
	}

	//prints out array before being sorted and after
	public static int[] selectionSort(int[] numbers) 
	{ 
		int outer, inner, min; 
		for (outer = 0; outer < numbers.length - 1; outer++) 
		{ // outer counts down
			{ // outer counts down
				min = outer; 
				for (inner = outer + 1; inner <  numbers.length; inner++) 
				{ 
					if (numbers[inner] < numbers[min]) 
					{ 
						min = inner; 
					} 

				} 
				int temp = numbers[outer]; 
				numbers[outer] = numbers[min]; 
				numbers[min] = temp; 
	// this method gets the smallest number in the array and puts it into the frist
	//index and repeats for for the whole array
			} 
			return numbers;//returns num
// this method gets the smallest number in the array and puts it into the frist
//index and repeats for for the whole array
		} 
		return numbers;//returns numbers back as a sorted array
	}}
	
	


