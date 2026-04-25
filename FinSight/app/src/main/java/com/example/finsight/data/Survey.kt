package com.example.finsight.data

import androidx.room.Entity
import androidx.room.PrimaryKey

@Entity(tableName = "surveys")
data class Survey(
    @PrimaryKey(autoGenerate = true) val id: Int = 0,
    val name: String,
    val phone: String,
    val surveyType: String,
    val investmentPreference: String,
    val financialChallenges: String,
    val riskLevel: String,
    val latitude: Double,
    val longitude: Double,
    val timestamp: Long
)
