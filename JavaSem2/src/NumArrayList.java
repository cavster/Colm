import java.util.ArrayList;

public class NumArrayList implements Num {

	ArrayList<Num> pizza;

	public NumArrayList(float[] array) {
		pizza = new ArrayList<Num>();
		
		for (int i = 0; i < array.length; i++) {
			pizza.add(new NumFloat(array[i]));//createing new instance of numFloat and adding it to pizza array in this class
		}
	}
	public NumArrayList(double[] array) {
		pizza = new ArrayList<Num>();
		for (int i = 0; i < array.length; i++) {
			pizza.add(new NumDouble(array[i]));//createing new instance of numdouble and adding it to pizza array in this class
			}
		}

	@Override
	public void neg() {
		for (Num n : pizza)
			n.neg();//turing pizza into neg

	}

	@Override
	public void sqrt() {
		for (Num n : pizza)
			n.sqrt();//turing pizza into neg

	}
	@Override
	public String toString() {
		String s = "";
		for (Num n : pizza)
			s += n + " ";
		return s;
	}
}
