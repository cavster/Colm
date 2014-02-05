package CourseWorkn;
import java.awt.Dimension;
import java.awt.FlowLayout;
import java.awt.GridLayout;
import java.awt.event.ActionListener;
import java.awt.event.ActionEvent;
import java.io.IOException;
import java.io.InputStream;
import java.io.OutputStream;
import java.io.PrintWriter;
import java.net.Socket;
import java.util.Arrays;
import java.util.Comparator;
import java.util.Scanner;

import javax.swing.ButtonGroup;
import javax.swing.DefaultCellEditor;
import javax.swing.JButton;
import javax.swing.JComboBox;
import javax.swing.JFrame;
import javax.swing.JLabel;
import javax.swing.JPanel;
import javax.swing.JRadioButton;
import javax.swing.JScrollPane;
import javax.swing.JTable;
import javax.swing.JTextField;
import javax.swing.JOptionPane;
import javax.swing.table.TableColumn;
import javax.swing.table.TableRowSorter;

import Lab10.MyTable;
public class  Gui extends JFrame{
   public int getChoice() {
		return choice;
	}
	public void setChoice(int choice) {
		this.choice = choice;
	}
private int choice=1;
private String end="";
	private static String R;
	private JTextField Cname;
	private JTextField Details;
	private JComboBox TextNameBox;
	private JComboBox Pioritybox;
	private JComboBox Statusbox;
	private JButton Submit;
	private JButton Print;
	private static String[] piority={"1","2","3","4","5"};
	public static String getR() {
		return R;
	}
	public void setR(String r) {
		R = r;
	}
	public String getEnd(){
		return end;	
		}
	private static String[] Techname={"colm","mark","kaylie","rachel","katie"};
	private static String[] status={"sloved","Unsloved"};

	public Gui(){//class for setting up gui
	super("Ticket Fill in");




	setLayout(new GridLayout(12, 2,5,5));//grid layout used
	add(new JLabel("Tech Name"));
	 TextNameBox=new JComboBox(Techname);
	 add(TextNameBox);
     add(new JLabel("Customor Name"));
	 Cname=new JTextField(20);
     add(Cname);
     add(new JLabel("Detail"));
	 Details=new JTextField(20);
	 add(Details);

	add(new JLabel("Piority"));
	Pioritybox=new JComboBox(piority);
	add(Pioritybox);
	add(new JLabel("Status"));
	Statusbox=new JComboBox(status);
	add(Statusbox);
   
	Submit=new JButton("Submit");
	add(Submit);
	handlerStatus handel=new handlerStatus();
	handlerPrint handlemyPrint=new handlerPrint();
	
	Submit.addActionListener(handel);
	Print=new JButton("See all reports");
	Print.addActionListener(handlemyPrint);
	add(Print);
	
	}
	public class handlerStatus implements ActionListener{
//when button for submit is clicked runs this code
		@Override
		public void actionPerformed(ActionEvent event) {
		
		String	Cname1=Cname.getText();
		String detail= Details.getText();
		String Tname = (String)TextNameBox.getSelectedItem();//getting text from slection box saveing it as varable
		String status=(String)Statusbox.getSelectedItem();
		String pirioty=(String)Pioritybox.getSelectedItem();
		Cname1=Cname1.replaceAll(",","");
		detail=detail.replaceAll(",","");//to prevent people from messing with my code with harmful injections since i use , later in my file
		String rt="Submit\n"+Tname+"\n"+Cname1+"\n"+detail+"\n"+pirioty+"\n"+status+"\n";//line breaks used so I can use .nextline to split them up into varables later
	//setting all text as one super string 
try{
	Socket s=new Socket("localhost",8888);
	InputStream instream=s.getInputStream();
	OutputStream outstream=s.getOutputStream();
	Scanner input=new Scanner(System.in);
	Scanner in=new Scanner(instream);
	PrintWriter out=new PrintWriter(outstream,true);
	out.print(rt);//sending in String to server as stream to get work done on it
	out.flush();
	String R=in.nextLine();//getting info back from sever to let client know that he got it
	System.out.println("Got it from server "+R+"\n\n");
	JOptionPane.showMessageDialog(null,"got your request ");
			Cname.setText("");
			Details.setText("");;//SETS THEM BLANK FOR NEXT ENTRY
	
	
	
}
	catch(IOException exception){
		exception.getMessage();}


	}
		
		}
	public class handlerPrint implements ActionListener{
//same done again for the see all button
		@Override
		public void actionPerformed(ActionEvent event) {
		
			
			try{
				Socket s=new Socket("localhost",8888);
				InputStream instream=s.getInputStream();
				OutputStream outstream=s.getOutputStream();
				Scanner input=new Scanner(System.in);
				Scanner in=new Scanner(instream);
				PrintWriter out=new PrintWriter(outstream,true);
				MyTable model = new MyTable();//creating table object from my table class
				String a="Print\n";
				System.out.println(a);

				out.print(a);
				out.flush();
				model = new MyTable();
				String c=in.nextLine();//ask about this in disc;
				System.out.print(c);
				
				while(!c.equals("end"))
				{
					
				String D[]=c.split(",");//using , to split them in the file hence while i removed them from the textfeild varables
					System.out.println("Got it from server "+D[0]);
					String Tname = D[0];
					String Cname=D[1];
					String Details=D[2];
					String piority=D[3];
					String status=D[4];
					model.addRow(Arrays.asList(Tname,Cname,Details,piority,status));//sending in the varables into the table by the add row method
					
					c=in.nextLine();//after every ticket object there is a line break so it goes to the next one and so on and so forth
				}
				System.out.println("work!");
				JTable table = new JTable(model);
				 TableRowSorter<MyTable> mc = new TableRowSorter(model);//uses the table row sorter to sort my table
				 class IntComparator implements Comparator {
			            public int compare(Object o1, Object o2) {
			                Integer int1 = (Integer)o1;
			                Integer int2 = (Integer)o2;
			                return int1.compareTo(int2);
			            }

			            public boolean equals(Object o2) {
			                return this.equals(o2);
			            }//sorts it with regards to ints
			        }

			        mc.setComparator(0, new IntComparator());//creates an instance of that class
			        table.setAutoCreateRowSorter(false);
			        table.setRowSorter(mc);
			        
			    table.setPreferredScrollableViewportSize(new Dimension(5000, 7000));
			    table.setFillsViewportHeight(true);

			    JScrollPane scrollPane = new JScrollPane(table);
			    scrollPane.setBounds(5, 218, 884, 194);
			    //now adding this to the frame where I want to show 
			    JFrame frame = new JFrame();
			    frame.setSize(1000,1000);//setting size
			     frame.add(scrollPane);
			   
			    frame.setVisible(true);//leting people see it
			   
			   
			}catch(IOException exception){
				exception.getMessage();}
			
	}
		
	}


				
			

}



	

