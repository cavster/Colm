package Lab10;

import java.util.ArrayList;
import java.util.List;

import javax.swing.table.AbstractTableModel;

public class MyTable extends AbstractTableModel
{
    private List<String> columnNames = new ArrayList<String>();
    private List<List> Ticket = new ArrayList<List>();

    {
        columnNames.add("Tech Name");
        columnNames.add("Customor Name");
        columnNames.add("Details");
        columnNames.add("Piority");
        columnNames.add("Status");
       
    }

    public void addRow(List rowData)
    {
        Ticket.add(rowData);
        fireTableRowsInserted(Ticket.size() - 1, Ticket.size() - 1);//used to fill rows
    }

    public int getColumnCount()
    {
        return columnNames.size();
    }

    public int getRowCount()
    {
        return Ticket.size();
    }//methods added as part of interface 

    public String getColumnName(int col)
    {
        try
        {
            return columnNames.get(col);
        }
        catch(Exception e)
        {
            return null;
        }
    }

    public Object getValueAt(int row, int col)
    {
        return Ticket.get(row).get(col);
    }

    public boolean isCellEditable(int row, int col)
    {
        return false;
    }

    public Class<? extends Object> getColumnClass(int c)
    {
        return getValueAt(0, c).getClass();
    }

	public void addRow(String string) {
		// TODO Auto-generated method stub
		
	}
};