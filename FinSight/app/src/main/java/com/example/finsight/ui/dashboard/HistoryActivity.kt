package com.example.finsight.ui.dashboard

import android.os.Bundle
import androidx.activity.enableEdgeToEdge
import androidx.activity.viewModels
import androidx.appcompat.app.AppCompatActivity
import androidx.core.view.ViewCompat
import androidx.core.view.WindowInsetsCompat
import androidx.recyclerview.widget.LinearLayoutManager
import com.example.finsight.data.AppDatabase
import com.example.finsight.data.SurveyRepository
import com.example.finsight.databinding.ActivityHistoryBinding
import com.example.finsight.ui.survey.SurveyViewModel

class HistoryActivity : AppCompatActivity() {

    private lateinit var binding: ActivityHistoryBinding
    private val viewModel: SurveyViewModel by viewModels {
        SurveyViewModel.Factory(SurveyRepository(AppDatabase.getDatabase(this).surveyDao()))
    }

    override fun onCreate(savedInstanceState: Bundle?) {
        enableEdgeToEdge()
        super.onCreate(savedInstanceState)
        binding = ActivityHistoryBinding.inflate(layoutInflater)
        setContentView(binding.root)

        ViewCompat.setOnApplyWindowInsetsListener(binding.root) { v, insets ->
            val systemBars = insets.getInsets(WindowInsetsCompat.Type.systemBars())
            v.setPadding(systemBars.left, systemBars.top, systemBars.right, systemBars.bottom)
            insets
        }

        val role = intent.getStringExtra("ROLE") ?: "entrevistador"
        val adapter = SurveyAdapter(role) { survey ->
            viewModel.delete(survey)
        }

        binding.rvHistory.layoutManager = LinearLayoutManager(this)
        binding.rvHistory.adapter = adapter

        viewModel.allSurveys.observe(this) { surveys ->
            adapter.submitList(surveys)
        }

        binding.toolbar.setNavigationIcon(android.R.drawable.ic_menu_revert)
        binding.toolbar.setNavigationOnClickListener { finish() }
    }
}
