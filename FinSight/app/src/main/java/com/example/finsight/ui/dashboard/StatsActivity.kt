package com.example.finsight.ui.dashboard

import android.os.Bundle
import androidx.activity.enableEdgeToEdge
import androidx.activity.viewModels
import androidx.appcompat.app.AppCompatActivity
import androidx.core.view.ViewCompat
import androidx.core.view.WindowInsetsCompat
import com.example.finsight.data.AppDatabase
import com.example.finsight.data.SurveyRepository
import com.example.finsight.databinding.ActivityStatsBinding
import com.example.finsight.ui.survey.SurveyViewModel
import java.util.Locale

class StatsActivity : AppCompatActivity() {

    private lateinit var binding: ActivityStatsBinding
    private val viewModel: SurveyViewModel by viewModels {
        SurveyViewModel.Factory(SurveyRepository(AppDatabase.getDatabase(this).surveyDao()))
    }

    override fun onCreate(savedInstanceState: Bundle?) {
        enableEdgeToEdge()
        super.onCreate(savedInstanceState)
        binding = ActivityStatsBinding.inflate(layoutInflater)
        setContentView(binding.root)

        ViewCompat.setOnApplyWindowInsetsListener(binding.root) { v, insets ->
            val systemBars = insets.getInsets(WindowInsetsCompat.Type.systemBars())
            v.setPadding(systemBars.left, systemBars.top, systemBars.right, systemBars.bottom)
            insets
        }

        viewModel.allSurveys.observe(this) { surveys ->
            val total = surveys.size
            binding.tvTotalLabel.text = "Quant. de pessoas entrevistadas: $total"

            if (total > 0) {
                val cdb = surveys.count { it.investmentPreference.contains("CDB", true) }
                val acoes = surveys.count { it.investmentPreference.contains("Ações", true) }
                val fiis = surveys.count { it.investmentPreference.contains("FIIs", true) }
                val prev = surveys.count { it.investmentPreference.contains("Previdência", true) }
                val cripto = surveys.count { it.investmentPreference.contains("Cripto", true) }

                binding.tvCdb.text = String.format(Locale.getDefault(), "CDB: %.1f%%", (cdb.toFloat() / total) * 100)
                binding.tvAcoes.text = String.format(Locale.getDefault(), "Ações: %.1f%%", (acoes.toFloat() / total) * 100)
                binding.tvFiis.text = String.format(Locale.getDefault(), "FIIs: %.1f%%", (fiis.toFloat() / total) * 100)
                binding.tvPrev.text = String.format(Locale.getDefault(), "Previdência: %.1f%%", (prev.toFloat() / total) * 100)
                binding.tvCripto.text = String.format(Locale.getDefault(), "Cripto: %.1f%%", (cripto.toFloat() / total) * 100)
            }
        }
    }
}
