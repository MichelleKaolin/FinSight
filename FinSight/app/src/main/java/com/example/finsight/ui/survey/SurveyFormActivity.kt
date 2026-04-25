package com.example.finsight.ui.survey

import android.Manifest
import android.annotation.SuppressLint
import android.content.pm.PackageManager
import android.location.Location
import android.os.Bundle
import android.widget.CheckBox
import android.widget.RadioButton
import android.widget.Toast
import androidx.activity.enableEdgeToEdge
import androidx.activity.result.contract.ActivityResultContracts
import androidx.activity.viewModels
import androidx.appcompat.app.AppCompatActivity
import androidx.core.app.ActivityCompat
import androidx.core.view.ViewCompat
import androidx.core.view.WindowInsetsCompat
import com.example.finsight.data.AppDatabase
import com.example.finsight.data.SurveyRepository
import com.example.finsight.databinding.ActivitySurveyFormBinding
import com.google.android.gms.location.FusedLocationProviderClient
import com.google.android.gms.location.LocationServices

class SurveyFormActivity : AppCompatActivity() {

    private lateinit var binding: ActivitySurveyFormBinding
    private lateinit var fusedLocationClient: FusedLocationProviderClient
    
    private val viewModel: SurveyViewModel by viewModels {
        val database = AppDatabase.getDatabase(this)
        SurveyViewModel.Factory(SurveyRepository(database.surveyDao()))
    }

    private var currentLatitude: Double = 0.0
    private var currentLongitude: Double = 0.0

    override fun onCreate(savedInstanceState: Bundle?) {
        enableEdgeToEdge()
        super.onCreate(savedInstanceState)
        binding = ActivitySurveyFormBinding.inflate(layoutInflater)
        setContentView(binding.root)

        ViewCompat.setOnApplyWindowInsetsListener(binding.root) { v, insets ->
            val systemBars = insets.getInsets(WindowInsetsCompat.Type.systemBars())
            v.setPadding(systemBars.left, systemBars.top, systemBars.right, systemBars.bottom)
            insets
        }

        fusedLocationClient = LocationServices.getFusedLocationProviderClient(this)
        requestLocationPermissions()

        setupListeners()
        observeViewModel()
    }

    private fun requestLocationPermissions() {
        val locationPermissionRequest = registerForActivityResult(
            ActivityResultContracts.RequestMultiplePermissions()
        ) { permissions ->
            if (permissions[Manifest.permission.ACCESS_FINE_LOCATION] == true ||
                permissions[Manifest.permission.ACCESS_COARSE_LOCATION] == true
            ) {
                getLastLocation()
            }
        }

        locationPermissionRequest.launch(arrayOf(
            Manifest.permission.ACCESS_FINE_LOCATION,
            Manifest.permission.ACCESS_COARSE_LOCATION
        ))
    }

    @SuppressLint("MissingPermission")
    private fun getLastLocation() {
        fusedLocationClient.lastLocation.addOnSuccessListener { location: Location? ->
            location?.let {
                currentLatitude = it.latitude
                currentLongitude = it.longitude
            }
        }
    }

    private fun setupListeners() {
        binding.btnSubmit.setOnClickListener {
            if (validateForm()) {
                submitForm()
            }
        }
    }

    private fun validateForm(): Boolean {
        val name = binding.etName.text.toString()
        val phone = binding.etPhone.text.toString()
        val challenges = getSelectedChallenges()

        if (name.isBlank() || phone.isBlank()) {
            Toast.makeText(this, "Please fill in Name and Phone", Toast.LENGTH_SHORT).show()
            return false
        }

        if (challenges.size !in 1..3) {
            Toast.makeText(this, "Select 1 to 3 financial challenges", Toast.LENGTH_SHORT).show()
            return false
        }

        if (binding.rgPreference.checkedRadioButtonId == -1) {
            Toast.makeText(this, "Please select an investment preference", Toast.LENGTH_SHORT).show()
            return false
        }

        if (binding.rgRisk.checkedRadioButtonId == -1) {
            Toast.makeText(this, "Please select a risk level", Toast.LENGTH_SHORT).show()
            return false
        }

        return true
    }

    private fun getSelectedChallenges(): List<String> {
        val challenges = mutableListOf<String>()
        val checkBoxes = listOf(
            binding.cbDividas, binding.cbInflacao, binding.cbConhecimento,
            binding.cbImpostos, binding.cbRentabilidade, binding.cbPlanejamento,
            binding.cbEmergencias, binding.cbAposentadoria, binding.cbEducacao,
            binding.cbOutros
        )
        for (cb in checkBoxes) {
            if (cb.isChecked) challenges.add(cb.text.toString())
        }
        return challenges
    }

    private fun submitForm() {
        val name = binding.etName.text.toString()
        val phone = binding.etPhone.text.toString()
        val preferenceId = binding.rgPreference.checkedRadioButtonId
        val preference = findViewById<RadioButton>(preferenceId).text.toString()
        val challenges = getSelectedChallenges().joinToString(", ")
        val riskId = binding.rgRisk.checkedRadioButtonId
        val risk = findViewById<RadioButton>(riskId).text.toString()

        viewModel.saveSurvey(
            name, phone, preference, challenges, risk, currentLatitude, currentLongitude
        )
    }

    private fun observeViewModel() {
        viewModel.saveStatus.observe(this) { success ->
            if (success) {
                Toast.makeText(this, "Survey saved successfully!", Toast.LENGTH_LONG).show()
                finish()
            }
        }
    }
}
