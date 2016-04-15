package org.sudarhan.manytoone;

import org.hibernate.HibernateException;
import org.hibernate.Session;
import org.hibernate.SessionFactory;
import org.hibernate.cfg.Configuration;

public class Student_Main {

	public static void main(String[] args) {

		SessionFactory sessionFactory = new Configuration().configure().buildSessionFactory();
		Session session = sessionFactory.openSession();
		try {

			Address address = new Address();
			address.setCity("Chennai City");
			address.setState("TN");
			address.setStreet("3rd");
			address.setZipcode("600097");

			Student student1 = new Student();
			student1.setStudentName("Sudarshan");
			student1.setStudentAddress(address);

			Student student2 = new Student();
			student2.setStudentName("Kumar");
			student2.setStudentAddress(address);

			session.beginTransaction();
			session.save(student1);
			session.save(student2);

		} catch (HibernateException e) {
			e.printStackTrace();
		} finally {
			session.close();
		}
	}

}
