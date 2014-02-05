package StringCompare;

import java.util.Scanner;

public class StringCompareTest 
{

	public StringCompareTest() {
		//colm cavanagh
		//06/11/2012
		//This class will take in two strings and see if they are equal
	}

	/**
	 * @param args
	 */
	public static void main(String[] args) {
		String wordOne;
		String wordTwo;

		Scanner input=new Scanner(System.in);
		System.out.println("Please enter frist string");
		wordOne=input.nextLine();
		System.out.println("please enter second string");
		wordTwo=input.nextLine();
		StringTestCompare cal= new StringTestCompare(wordOne,wordTwo);
		Boolean answer=cal.doCompare();//setting the returning stuff from the other class equal to answer
		if(answer)
		{
			System.out.println("Strings"+wordOne+"&"+wordTwo+" are equal");

		}
		else
		{
			System.out.println("Strings"+wordOne+"&"+wordTwo+" are not equal");
		}
	}
}

