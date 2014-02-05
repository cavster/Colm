import java.util.Scanner;

96,3,-2,0,18,1,75,13,3,

public class BubbelSort {
	public static void main(String[] args) 
	{
int numbers[]=new int[10];
numbers[0]=96;

			System.out.println("Array before sorting");
			PrintNumbers(numbers); 
			numbers=selectionSort(numbers);//changes numbers to sorted
			System.out.println("\nArray after sorting");
			PrintNumbers(numbers);	
		}


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
		return numbers;//returns numbers back as a sorted array
	}}
	
	


