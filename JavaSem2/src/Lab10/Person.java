package Lab10;

public class Person {

	private String Fname;
	private String Lname;
	private String Street;
	private String HouseNumber;
	private String city;
	private String Email;
	
	public  Person(String Fname,String Lname,String Street,String HouseNumber,String city,String Email){
		this.Fname=Fname;
		this.Lname=Lname;
		this.Street=Street;
		this.HouseNumber=HouseNumber;
		this.city=city;
		this.Email=Email;
	}
	public String getStreet() {
		return Street;
	}
	public void setStreet(String street) {
		Street = street;
	}
	public String getHouseNumber() {
		return HouseNumber;
	}
	public void setHouseNumber(String houseNumber) {
		HouseNumber = houseNumber;
	}
	public String getCity() {
		return city;
	}
	public void setCity(String city) {
		this.city = city;
	}
	public String getFname() {
		return Fname;
	}

	public void setFname(String fname) {
		Fname = fname;
	}

	public String getLname() {
		return Lname;
	}

	public void setLname(String lname) {
		Lname = lname;
	}


	public String getEmail() {
		return Email;
	}

	public void setEmail(String email) {
		Email = email;
	}
	}

