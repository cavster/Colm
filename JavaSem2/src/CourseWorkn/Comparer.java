package CourseWorkn;

import java.util.Comparator;

class Comparer implements Comparator<Ticket>{
// a comparer class for ojects in an array list ended up not being utilised 
	
	@Override
	public int compare(Ticket a, Ticket b) {
		 return (a.getPiority() < b.getPiority() ) ? -1: (a.getPiority() > b.getPiority()) ? 1:0 ;
		
	}

	

	}


