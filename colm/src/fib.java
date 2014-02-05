import java.util.InputMismatchException;
import java.util.Scanner;
public class fib {


		


		    /**
		     * @param args
		     */
		    public static void main(String[] args) {//main method begins execution
		        // TODO Auto-generated method stub


		        System.out.println("This program outputs the first N numbers in the Fibonacci Sequence ");


		        Scanner scanner = new Scanner(System.in);
		        int f1=1, f2=0, f3=0, n = 0, counter;//declare variables
		        boolean keepGoing = true;

		        while (keepGoing){
		            try{
		                System.out.print("Enter N:");
		                n=scanner.nextInt();
		                keepGoing = false;
		            }

		            catch (InputMismatchException e){ 
		                scanner.next();
		                System.out.println ("Enter Integer Values only");
		            }
		        }

		        System.out.println("The first 13 numbers in the Fibonacci sequence are as follows: ");

		        for (counter=1; counter<n+1; counter++ ){ //initialization/boolean expression/increment
		            if(f3!=0){
		        	System.out.print(" "+ f3);}
		            f3= f1+f2;
		            f1=f2;
		            f2=f3;        

		        }//end for loop




		    }//end method

		}//end class

	


