


public class Demo {


	/**
	 * @param args
	 */
	public static void main(String[] args) {
		double pizza[]= {1,2,3,4,5,6,7};
        float eggs[]={2,3,4,323,512,123};
        double eggs2[]= {3,5,7,12,53,321,723};
        float pizza2[]={2132,31234,4,323,512,123};
NumArrayList dog=new NumArrayList(pizza);
NumArrayList cat=new NumArrayList(eggs);

generic katie=new generic(eggs2);
generic kaylie=new generic(pizza2);
dog.sqrt();
cat.neg();
System.out.println(dog);
System.out.println(cat);
katie.sqrt();
kaylie.neg();
System.out.println(katie);
System.out.println(kaylie);
	}

}
