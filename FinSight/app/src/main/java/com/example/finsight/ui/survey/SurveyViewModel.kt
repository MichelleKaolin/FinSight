package com.example.finsight.ui.survey

import androidx.lifecycle.*
import com.example.finsight.data.Survey
import com.example.finsight.data.SurveyRepository
import kotlinx.coroutines.launch

class SurveyViewModel(private val repository: SurveyRepository) : ViewModel() {

    val allSurveys: LiveData<List<Survey>> = repository.allSurveys.asLiveData()

    private val _saveStatus = MutableLiveData<Boolean>()
    val saveStatus: LiveData<Boolean> = _saveStatus

    fun saveSurvey(
        name: String,
        phone: String,
        preference: String,
        challenges: String,
        risk: String,
        lat: Double,
        lon: Double
    ) {
        viewModelScope.launch {
            val survey = Survey(
                name = name,
                phone = phone,
                surveyType = "Estimulada",
                investmentPreference = preference,
                financialChallenges = challenges,
                riskLevel = risk,
                latitude = lat,
                longitude = lon,
                timestamp = System.currentTimeMillis()
            )
            repository.insert(survey)
            _saveStatus.value = true
        }
    }

    fun delete(survey: Survey) {
        viewModelScope.launch {
            repository.delete(survey)
        }
    }

    fun deleteAll() {
        viewModelScope.launch {
            repository.deleteAll()
        }
    }

    class Factory(private val repository: SurveyRepository) : ViewModelProvider.Factory {
        override fun <T : ViewModel> create(modelClass: Class<T>): T {
            if (modelClass.isAssignableFrom(SurveyViewModel::class.java)) {
                @Suppress("UNCHECKED_CAST")
                return SurveyViewModel(repository) as T
            }
            throw IllegalArgumentException("Unknown ViewModel class")
        }
    }
}
