package com.smarttask.dao;

import com.smarttask.model.User;
import com.smarttask.util.DatabaseConnection;

import java.sql.Connection;
import java.sql.PreparedStatement;
import java.sql.ResultSet;
import java.sql.SQLException;
import java.sql.Timestamp;

public class UserDAO {
    public boolean register(User user) {
        String sql = "INSERT INTO user (name, email, password, type, roles, is_enabled) VALUES (?, ?, ?, ?, ?, ?)";
        Connection connection = null;

        try {
            connection = DatabaseConnection.getConnection();
            try (PreparedStatement statement = connection.prepareStatement(sql)) {
                String roles = user.getRoles() == null ? "[]" : user.getRoles();

                statement.setString(1, user.getName());
                statement.setString(2, user.getEmail());
                statement.setString(3, user.getPassword());
                statement.setString(4, user.getType());
                statement.setString(5, roles);
                statement.setInt(6, 1);

                return statement.executeUpdate() > 0;
            }
        } catch (SQLException e) {
            System.err.println("Failed to register user: " + e.getMessage());
            return false;
        } finally {
            DatabaseConnection.closeConnection(connection);
        }
    }

    public boolean emailExists(String email) {
        String sql = "SELECT COUNT(*) FROM user WHERE email = ?";
        Connection connection = null;

        try {
            connection = DatabaseConnection.getConnection();
            try (PreparedStatement statement = connection.prepareStatement(sql)) {
                statement.setString(1, email);

                try (ResultSet resultSet = statement.executeQuery()) {
                    if (resultSet.next()) {
                        return resultSet.getInt(1) > 0;
                    }
                }
            }
        } catch (SQLException e) {
            System.err.println("Failed to check if email exists: " + e.getMessage());
        } finally {
            DatabaseConnection.closeConnection(connection);
        }

        return false;
    }

    public User login(String email, String password) {
        String sql = "SELECT * FROM user WHERE email = ? AND password = ? AND is_enabled = 1";
        Connection connection = null;

        try {
            connection = DatabaseConnection.getConnection();
            try (PreparedStatement statement = connection.prepareStatement(sql)) {
                statement.setString(1, email);
                statement.setString(2, password);

                try (ResultSet resultSet = statement.executeQuery()) {
                    if (resultSet.next()) {
                        User user = new User();
                        user.setIduser(resultSet.getInt("iduser"));
                        user.setName(resultSet.getString("name"));
                        user.setEmail(resultSet.getString("email"));
                        user.setPassword(resultSet.getString("password"));
                        user.setType(resultSet.getString("type"));
                        user.setGoogleId(resultSet.getString("google_id"));
                        user.setRoles(resultSet.getString("roles"));
                        user.setEnabled(resultSet.getBoolean("is_enabled"));
                        user.setLinkedinId(resultSet.getString("linkedin_id"));
                        user.setResetToken(resultSet.getString("reset_token"));

                        Timestamp resetTokenExpiresAtTs = resultSet.getTimestamp("reset_token_expires_at");
                        if (resetTokenExpiresAtTs != null) {
                            user.setResetTokenExpiresAt(resetTokenExpiresAtTs.toLocalDateTime());
                        }

                        user.setAvatarName(resultSet.getString("avatar_name"));

                        Timestamp updatedAtTs = resultSet.getTimestamp("updated_at");
                        if (updatedAtTs != null) {
                            user.setUpdatedAt(updatedAtTs.toLocalDateTime());
                        }

                        user.setFaceEmbedding(resultSet.getString("face_embedding"));
                        return user;
                    }
                }
            }
        } catch (SQLException e) {
            System.err.println("Failed to login user: " + e.getMessage());
        } finally {
            DatabaseConnection.closeConnection(connection);
        }

        return null;
    }
}

