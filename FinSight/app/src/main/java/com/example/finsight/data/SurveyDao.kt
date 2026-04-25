package com.example.finsight.data

import androidx.room.*
import kotlinx.coroutines.flow.Flow

@Dao
interface SurveyDao {
    @Insert
    suspend fun insert(survey: Survey)

    @Query("SELECT * FROM surveys ORDER BY timestamp DESC")
    fun getAllSurveys(): Flow<List<Survey>>

    @Delete
    suspend fun delete(survey: Survey)

    @Query("DELETE FROM surveys")
    suspend fun deleteAll()
}
