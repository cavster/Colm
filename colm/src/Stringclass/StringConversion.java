package Stringclass;
import java.util.Scanner;
import java.util.StringTokenizer;
public class StringConversion 
{
	public static void main(String[] args)

	{
	{
			String sentence;
			Scanner input=new Scanner(System.in);
			System.out.println("Please enter the sentence");
			sentence=input.nextLine(); 
			System.out.println("Convert result\n");
			System.out.println(sentence.toUpperCase());
			System.out.println(sentence.toLowerCase());
			StringTokenizer st = new StringTokenizer(sentence);
			while (st.hasMoreTokens()) 
			{
				System.out.println(st.nextToken());
			}

	}

	}
}
