package org.sudarshan;

import javax.persistence.Entity;
import javax.persistence.Id;

@Entity
public class UserDetails {
    @Id
	private int userId;
	public int getUserId() {
		return userId;
	}
	public void setUserId(int userId) {
		this.userId = userId;
	}
	public String getUserNmae() {
		return userNmae;
	}
	public void setUserNmae(String userNmae) {
		this.userNmae = userNmae;
	}
	private String userNmae;
	
}
