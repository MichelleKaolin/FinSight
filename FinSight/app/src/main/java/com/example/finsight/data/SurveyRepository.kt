package com.example.finsight.data

import kotlinx.coroutines.flow.Flow

class SurveyRepository(private val surveyDao: SurveyDao) {
    val allSurveys: Flow<List<Survey>> = surveyDao.getAllSurveys()

    suspend fun insert(survey: Survey) {
        surveyDao.insert(survey)
    }

    suspend fun delete(survey: Survey) {
        surveyDao.delete(survey)
    }

    suspend fun deleteAll() {
        surveyDao.deleteAll()
    }
}
