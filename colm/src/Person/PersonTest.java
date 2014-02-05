package Person;
//colm cavanagh
//12231055

public class PersonTest {

	//this program is testing the person class by showing the outputted results
	public static void main(String[] args) {
		Person colm=new Person("colm" ,23,'M');//passing values into Person class
        Person mark=new Person();//passing none in so I get default values back
        System.out.println(colm .age);//getting it directly change to public so this will work
        System.out.println(colm .getGender());
        System.out.println(colm .getName());
        System.out.println(mark .getAge());
        System.out.println(mark .getGender());
        System.out.println(mark .getName());
	}

}
