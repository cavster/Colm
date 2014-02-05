package height;
import java.util.Scanner;
public class Mainclass {

	public Mainclass() {
		// TODO Auto-generated constructor stub
	}

	/**
	 * @param args
	 */
	public static void main(String[] args) {
		Scanner input=new Scanner(System.in);
double height;
int age;
System.out.println("enter your heigh");
height=input.nextDouble();
System.out.println("enter your age");
age=input.nextInt();
height object=new height(height,age);
double recomendedweight= object.Calweight();
System.out.printf("this is your weight %.2f",recomendedweight);
	}

}
