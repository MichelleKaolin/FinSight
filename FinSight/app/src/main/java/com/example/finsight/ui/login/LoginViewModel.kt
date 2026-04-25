package com.example.finsight.ui.login

import androidx.lifecycle.LiveData
import androidx.lifecycle.MutableLiveData
import androidx.lifecycle.ViewModel

class LoginViewModel : ViewModel() {

    private val _loginResult = MutableLiveData<LoginResult>()
    val loginResult: LiveData<LoginResult> = _loginResult

    fun login(username: String, password: String) {
        when {
            username == "admin" && password == "admin" -> {
                _loginResult.value = LoginResult.Success("admin")
            }
            username == "entrevistador" && password == "entrevistador" -> {
                _loginResult.value = LoginResult.Success("entrevistador")
            }
            else -> {
                _loginResult.value = LoginResult.Error("Invalid credentials")
            }
        }
    }

    sealed class LoginResult {
        data class Success(val role: String) : LoginResult()
        data class Error(val message: String) : LoginResult()
    }
}
