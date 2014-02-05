package StringCompare;

public class StringTestCompare 
{
	public String wordOne;
	public String wordTwo;

	public  StringTestCompare(String wordOneInThisClass,String wordTwoInThisClass) 
	{
		this.wordOne=wordOneInThisClass;
		this.wordTwo=wordTwoInThisClass;//declareing it so it doesnt get confused

	}
	public boolean doCompare(){
		Boolean test=wordOne.equals(wordTwo);//== will not work in this case
		if(test)
		{
			return true;
		}
		else
		{
			return false;
		}
	}
}
