package org.dao.implimentation;

import java.util.ArrayList;
import java.util.List;

public class StudentDaoImpl implements StudentDao {

	@Override
	public List<Student> getAllStudents() {
		// TODO Auto-generated method stub
		return null;
	}

	List<Student> students;

	public StudentDaoImpl() {
		students = new ArrayList<Student>();
		Student student1 = new Student("Robert", 0);
		Student student2 = new Student("John", 1);
		students.add(student1);
		students.add(student2);
	}

	@Override
	public Student getStudent(int rollNo) {
		// TODO Auto-generated method stub
		return null;
	}

	@Override
	public void updateStudent(Student student) {
	      students.get(student.getRollNo()).setName(student.getName());
	      System.out.println("Student: Roll No " + student.getRollNo() + ", updated in the database");
	   }

	@Override
	public void deleteStudent(Student student) {
		students.remove(student.getRollNo());
		System.out.println("Student: Roll No " + student.getRollNo() + ", deleted from database");
		  

	}

}
