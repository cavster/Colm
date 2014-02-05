package CoffeeBags;
//this class  contains the ,methods that do the calcualtions for the other class
public class Calculations {

	int noOfBagsSold;
	double weightPerBag;
	private final double PricePerPound=5.5;//declareing our constants as private can only be used here
	private final double TaxRate=(.1);
	

	
	public Calculations(int noOfBagsSold,double weightPerBag){
		this.noOfBagsSold  = noOfBagsSold;
		this.weightPerBag  = weightPerBag;//this is done so it doesnt confuse the varables with other varables
	}
	
	public double gettotalPriceWithTax(){
		double totalPrice=noOfBagsSold*weightPerBag*PricePerPound;
		double totalPriceWithTax=(totalPrice)+(totalPrice)*TaxRate;
				return totalPriceWithTax;
	}

}
