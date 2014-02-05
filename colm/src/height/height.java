package height;

public class height {
private double height;
private int age;
public double recommendedHeight;
	public height(double height,int age) {
		this.height=height;
		this.age=age;
	}
public double getheight(){
	return height;
}
public int getage(){
return age;
}
public double Calweight(){
	recommendedHeight=(height-100+age/10)*.9;
	return recommendedHeight;
}
}

