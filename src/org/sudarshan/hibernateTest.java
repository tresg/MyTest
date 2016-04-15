package org.sudarshan;


import org.hibernate.Session;
import org.hibernate.SessionFactory;
import org.hibernate.cfg.Configuration;

public class hibernateTest {

	public static void main(String[] args) {

		UserDetails user=new UserDetails();
		user.setUserId(1);
		user.setUserNmae("Anshu");
		SessionFactory sessionFactory=new Configuration().configure().buildSessionFactory();
		Session session=sessionFactory.openSession();
		session.beginTransaction();
		session.save(user);
		session.getTransaction().commit();
	}

}
