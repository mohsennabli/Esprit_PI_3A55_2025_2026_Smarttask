package com.smarttask.controller;

import com.smarttask.dao.UserDAO;
import com.smarttask.model.User;
import javafx.collections.FXCollections;
import javafx.collections.ObservableList;
import javafx.event.ActionEvent;
import javafx.fxml.FXML;
import javafx.fxml.Initializable;
import javafx.scene.control.Button;
import javafx.scene.control.TableColumn;
import javafx.scene.control.TableView;
import javafx.scene.control.cell.PropertyValueFactory;

import java.net.URL;
import java.util.List;
import java.util.ResourceBundle;

public class UserListController implements Initializable {

    @FXML
    private TableView<User> usersTable;

    @FXML
    private TableColumn<User, Integer> colId;

    @FXML
    private TableColumn<User, String> colName;

    @FXML
    private TableColumn<User, String> colEmail;

    @FXML
    private TableColumn<User, String> colType;

    @FXML
    private TableColumn<User, Boolean> colEnabled;

    @FXML
    private Button addButton;

    @FXML
    private Button editButton;

    @FXML
    private Button deleteButton;

    @Override
    public void initialize(URL location, ResourceBundle resources) {
        colId.setCellValueFactory(new PropertyValueFactory<>("iduser"));
        colName.setCellValueFactory(new PropertyValueFactory<>("name"));
        colEmail.setCellValueFactory(new PropertyValueFactory<>("email"));
        colType.setCellValueFactory(new PropertyValueFactory<>("type"));
        colEnabled.setCellValueFactory(new PropertyValueFactory<>("enabled"));

        UserDAO userDAO = new UserDAO();
        List<User> users = userDAO.getAllUsers();
        ObservableList<User> data = FXCollections.observableArrayList(users);
        usersTable.setItems(data);
    }

    @FXML
    private void handleAdd(ActionEvent event) {
        System.out.println("Open Add User form");
    }

    @FXML
    private void handleEdit(ActionEvent event) {
        System.out.println("Open Edit User form");
    }

    @FXML
    private void handleDelete(ActionEvent event) {
        System.out.println("Open Delete User form");
    }
}

