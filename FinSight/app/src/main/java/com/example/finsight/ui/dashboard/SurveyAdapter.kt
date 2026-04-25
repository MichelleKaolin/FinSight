package com.example.finsight.ui.dashboard

import android.graphics.Color
import android.view.LayoutInflater
import android.view.View
import android.view.ViewGroup
import androidx.recyclerview.widget.DiffUtil
import androidx.recyclerview.widget.ListAdapter
import androidx.recyclerview.widget.RecyclerView
import com.example.finsight.data.Survey
import com.example.finsight.databinding.ItemSurveyBinding
import java.text.SimpleDateFormat
import java.util.*

class SurveyAdapter(
    private val role: String,
    private val onDeleteClick: (Survey) -> Unit
) : ListAdapter<Survey, SurveyAdapter.SurveyViewHolder>(SurveyDiffCallback()) {

    override fun onCreateViewHolder(parent: ViewGroup, viewType: Int): SurveyViewHolder {
        val binding = ItemSurveyBinding.inflate(LayoutInflater.from(parent.context), parent, false)
        return SurveyViewHolder(binding)
    }

    override fun onBindViewHolder(holder: SurveyViewHolder, position: Int) {
        holder.bind(getItem(position))
    }

    inner class SurveyViewHolder(private val binding: ItemSurveyBinding) : RecyclerView.ViewHolder(binding.root) {
        fun bind(survey: Survey) {
            binding.tvName.text = survey.name
            binding.tvPhone.text = survey.phone
            binding.tvRisk.text = "RISCO: ${survey.riskLevel.uppercase()}"
            
            // Highlight risk levels with colors
            when (survey.riskLevel.lowercase()) {
                "baixo" -> binding.tvRisk.setTextColor(Color.parseColor("#2E7D32")) // Green
                "médio" -> binding.tvRisk.setTextColor(Color.parseColor("#F57F17")) // Orange/Yellow
                "alto" -> binding.tvRisk.setTextColor(Color.parseColor("#D32F2F"))  // Red
            }
            
            val sdf = SimpleDateFormat("dd/MM/yyyy HH:mm", Locale.getDefault())
            binding.tvDateTime.text = sdf.format(Date(survey.timestamp))
            binding.tvLocation.text = "Lat: ${String.format("%.4f", survey.latitude)}, Lon: ${String.format("%.4f", survey.longitude)}"

            if (role == "admin") {
                binding.btnDelete.visibility = View.VISIBLE
                binding.btnDelete.setOnClickListener { onDeleteClick(survey) }
            } else {
                binding.btnDelete.visibility = View.GONE
            }
        }
    }

    class SurveyDiffCallback : DiffUtil.ItemCallback<Survey>() {
        override fun areItemsTheSame(oldItem: Survey, newItem: Survey): Boolean = oldItem.id == newItem.id
        override fun areContentsTheSame(oldItem: Survey, newItem: Survey): Boolean = oldItem == newItem
    }
}
