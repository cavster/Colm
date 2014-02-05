package Person;

public abstract class Person {

	// Data Members      
	public String  name; // The name of this person
	public int     age; // The age of this person
	public char    gender; // The gender of this person
	// Default constructor
	public Person() {
		this("Not Given", 0, 'U');
	}
	// Constructs a new Person with passed name, age, and gender.
	// example of explicit use of “this” (only for illustration)
	public Person(String name, int age, char gender) {
		this.age    = age;
		this.name   = name;
		this.gender = gender;
	}   
	// Returns the age of this person.
	public int getAge( ) {
		return age;
	}
	// Returns the gender of this person.
	public char getGender( ) {
		return gender;
	}
	// Returns the name of this person.
	public String getName( ) {
		return name;
	}
	// Sets the age of this person.
	public void setAge( int age ) {
		this.age = age;
	}
	// Sets the gender of this person.
	public void setGender( char gender ) {
		this.gender = gender;
	}
	// Sets the name of this person.
	public void setName( String name ) {
		this.name = name;
	}

}
