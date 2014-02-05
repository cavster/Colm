package CourseWorkn;

import java.util.ArrayList;
import java.util.List;

import javax.swing.table.AbstractTableModel;

public class Table extends AbstractTableModel
{
    private List<String> columnNames = new ArrayList();
    private List<List> Ticket = new ArrayList();

    {
        columnNames.add("First Name");
        columnNames.add("Last Name");
        columnNames.add("Address");
        columnNames.add("# of house");
        columnNames.add("city");
        columnNames.add("Email");//adding in colums
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
        return true;
    }

    public Class getColumnClass(int c)
    {
        return getValueAt(0, c).getClass();
    }
};