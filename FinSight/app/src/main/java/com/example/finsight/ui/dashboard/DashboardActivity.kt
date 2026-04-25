package com.example.finsight.ui.dashboard

import android.content.Intent
import android.os.Bundle
import android.view.View
import android.widget.Toast
import androidx.activity.enableEdgeToEdge
import androidx.activity.viewModels
import androidx.appcompat.app.AppCompatActivity
import androidx.core.view.ViewCompat
import androidx.core.view.WindowInsetsCompat
import com.example.finsight.data.AppDatabase
import com.example.finsight.data.SurveyRepository
import com.example.finsight.databinding.ActivityDashboardBinding
import com.example.finsight.ui.survey.SurveyViewModel

class DashboardActivity : AppCompatActivity() {

    private lateinit var binding: ActivityDashboardBinding
    private val viewModel: SurveyViewModel by viewModels {
        SurveyViewModel.Factory(SurveyRepository(AppDatabase.getDatabase(this).surveyDao()))
    }

    override fun onCreate(savedInstanceState: Bundle?) {
        enableEdgeToEdge()
        super.onCreate(savedInstanceState)
        binding = ActivityDashboardBinding.inflate(layoutInflater)
        setContentView(binding.root)

        ViewCompat.setOnApplyWindowInsetsListener(binding.root) { v, insets ->
            val systemBars = insets.getInsets(WindowInsetsCompat.Type.systemBars())
            v.setPadding(systemBars.left, systemBars.top, systemBars.right, systemBars.bottom)
            insets
        }

        val role = intent.getStringExtra("ROLE") ?: "entrevistador"
        
        if (role == "admin") {
            binding.btnClear.visibility = View.VISIBLE
        }

        setupListeners(role)
        observeViewModel()
    }

    private fun setupListeners(role: String) {
        binding.btnList.setOnClickListener {
            val intent = Intent(this, HistoryActivity::class.java)
            intent.putExtra("ROLE", role)
            startActivity(intent)
        }

        binding.btnStats.setOnClickListener {
            startActivity(Intent(this, StatsActivity::class.java))
        }

        binding.btnClear.setOnClickListener {
            viewModel.deleteAll()
            Toast.makeText(this, "Banco de dados limpo!", Toast.LENGTH_SHORT).show()
        }
    }

    private fun observeViewModel() {
        viewModel.allSurveys.observe(this) { surveys ->
            binding.tvTotalCount.text = "Quant. de pessoas Entrevistadas: ${surveys.size}"
        }
    }
}
