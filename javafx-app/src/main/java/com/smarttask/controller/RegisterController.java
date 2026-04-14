package com.smarttask.controller;

import com.smarttask.dao.UserDAO;
import com.smarttask.model.User;
import javafx.event.ActionEvent;
import javafx.fxml.FXML;
import javafx.fxml.Initializable;
import javafx.scene.control.Alert;
import javafx.scene.control.Button;
import javafx.scene.control.ChoiceBox;
import javafx.scene.control.Hyperlink;
import javafx.scene.control.PasswordField;
import javafx.scene.control.TextField;

import java.net.URL;
import java.util.ResourceBundle;

public class RegisterController implements Initializable {

    @FXML
    private TextField nameField;

    @FXML
    private TextField emailField;

    @FXML
    private PasswordField passwordField;

    @FXML
    private ChoiceBox<String> typeChoice;

    @FXML
    private Button registerButton;

    @FXML
    private Hyperlink loginLink;

    @Override
    public void initialize(URL location, ResourceBundle resources) {
        typeChoice.getItems().addAll("manager", "collaborator");
    }

    @FXML
    private void handleRegister(ActionEvent event) {
        String name = nameField.getText().trim();
        String email = emailField.getText().trim();
        String password = passwordField.getText().trim();
        String type = typeChoice.getValue() == null ? "" : typeChoice.getValue().trim();

        if (name.isEmpty() || email.isEmpty() || password.isEmpty() || type.isEmpty()) {
            showAlert(Alert.AlertType.WARNING, "Validation Error", "Please fill in all fields.");
            return;
        }

        UserDAO userDAO = new UserDAO();
        if (userDAO.emailExists(email)) {
            showAlert(Alert.AlertType.WARNING, "Validation Error", "Email already registered.");
            return;
        }

        User user = new User();
        user.setName(name);
        user.setEmail(email);
        user.setPassword(password);
        user.setType(type);
        user.setRoles("[]");
        user.setEnabled(true);

        if (userDAO.register(user)) {
            showAlert(Alert.AlertType.INFORMATION, "Success", "Account created successfully!");
            System.out.println("Registered: " + email);
        } else {
            showAlert(Alert.AlertType.ERROR, "Error", "Registration failed. Please try again.");
        }
    }

    @FXML
    private void handleLoginLink(ActionEvent event) {
        System.out.println("Navigate to Login");
    }

    private void showAlert(Alert.AlertType type, String title, String message) {
        Alert alert = new Alert(type);
        alert.setTitle(title);
        alert.setHeaderText(null);
        alert.setContentText(message);
        alert.showAndWait();
    }
}

