import java.io.IOException;
import java.io.PrintWriter;

import javax.annotation.Resource;
import javax.persistence.EntityManager;
import javax.persistence.EntityManagerFactory;
import javax.persistence.PersistenceUnit;
import javax.servlet.ServletException;
import javax.servlet.annotation.WebServlet;
import javax.servlet.http.HttpServlet;
import javax.servlet.http.HttpServletRequest;
import javax.servlet.http.HttpServletResponse;
import javax.transaction.HeuristicMixedException;
import javax.transaction.HeuristicRollbackException;
import javax.transaction.NotSupportedException;
import javax.transaction.RollbackException;
import javax.transaction.SystemException;
import javax.transaction.UserTransaction;

class UserData { // alternatively, we could also use, e.g., a String array

	String address;
	String password;

	UserData(String address, String password) {

		this.address = address;

		this.password = password;

	}
}

@WebServlet("/lab2q1test")
public class Lab2Q1Servlet extends HttpServlet {

	static String PAGE_HEADER = "<html><head><title>Lab2Q1 Demo</title><body>";

	static String PAGE_FOOTER = "</body></html>";

	@Override
	protected void doGet(HttpServletRequest request,
			HttpServletResponse response) throws ServletException, IOException {

		response.setContentType("text/html");

		PrintWriter out = response.getWriter();

		out.println(PAGE_HEADER);

		out.println("<body>"
				+ "<h2>Customer management demo</h2>"
				+ "<form method=\"get\">"
				+ "Name: <input type=\"text\" name=\"name\" size=\"20\">"
				+ "<p></p>"
				+ "Address: <input type=\"text\" name=\"address\" size=\"40\">"
				+ "<p></p>"
				+ "Password: <input type=\"text\" name=\"password\" size=\"16\">"
				+ "<p></p>" + "<input type=\"submit\" value=\"Submit\">"
				+ "<input type=\"reset\" value=\"Reset\">" + "</form>");
		String name = request.getParameter("name");
		String address = request.getParameter("address");
		String password = request.getParameter("password");

		if (name != null && name.length() > 0) {

			// Check if user name is already in the system:
			UserData userDataInSystem = (UserData) request.getSession()
					.getAttribute(name);

			if (userDataInSystem == null) {

				UserData userData = new UserData(address, password);

				request.getSession().setAttribute(name, userData);

				out.println("<p>New user " + name
						+ " registered in the system.");

			} else {

				if (userDataInSystem.password.equals(password)) { // correct password?

					if (address != null && address.length() > 0) { // we update the address:

						UserData userData = new UserData(address, password);

						request.getSession().setAttribute(name, userData);

						out.println("<p>User address updated.");

					} else  // we show the address:
						out.println("<p>Stored address is "	+ userDataInSystem.address);

				} else
					out.println("<p>Incorrect password!");

			}

		} else
			out.println("<p>You need to specify a name!");
		out.println(PAGE_FOOTER);
		out.close();
	}
}