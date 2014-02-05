import java.util.ArrayList;


public class generic<N extends Num>
{

	ArrayList<N> pizza;

	public generic(float[] array) {
		pizza = new ArrayList<N>();
		for (int i = 0; i < array.length; i++) {
			pizza.add((N) new NumFloat(array[i]));//createing new instance of numFloat and adding it to pizza array in this class
		}
	}
	public generic(double[] array) {
		pizza = new ArrayList<N>();
		for (int i = 0; i < array.length; i++) {
			pizza.add((N) new NumDouble(array[i]));//createing new instance of numdouble and adding it to pizza array in this class
			}
		}

	public void neg() {
		for (N n : pizza)
			n.neg();//turing pizza into neg

	}
	
	public void sqrt() {
		for (N n : pizza)
			n.sqrt();//turing pizza into neg

	}
	@Override
	public String toString() {
		String s = "";
		for (N n : pizza)
			s += n + " ";
		return s;
	}
}

	