<template>
  <v-container>
    <v-row>
      <v-col cols="12">
        <div class="d-flex justify-space-between align-center mb-4">
          <div>
            <h1 class="text-h4">Referral #{{ referral?.id }}</h1>
            <div class="text-caption text-grey mt-1" v-if="referral">
              Created {{ referral.created_at }} • Updated {{ referral.updated_at }}
            </div>
          </div>
          <v-btn
            icon="mdi-arrow-left"
            variant="text"
            @click="$router.push({ name: 'staff.referrals' })"
          >
            Back to List
          </v-btn>
        </div>

        <v-alert
          v-if="error"
          type="error"
          class="mb-4"
        >
          {{ error }}
        </v-alert>

        <v-progress-linear
          v-if="loading"
          indeterminate
          class="mb-4"
        ></v-progress-linear>

        <template v-if="referral && !loading">
          <!-- Overview Card -->
          <v-card class="mb-4">
            <v-card-title class="d-flex align-center">
              <v-icon class="mr-2">mdi-information</v-icon>
              Overview
            </v-card-title>
            <v-card-text>
              <v-row>
                <v-col cols="12" md="3">
                  <div class="text-caption text-grey mb-1">Status</div>
                  <v-chip
                    :color="getStatusColor(referral.status)"
                    size="large"
                    class="font-weight-bold"
                  >
                    {{ referral.status }}
                  </v-chip>
                </v-col>
                <v-col cols="12" md="3">
                  <div class="text-caption text-grey mb-1">Urgency</div>
                  <v-chip
                    :color="getUrgencyColor(referral.urgency)"
                    size="large"
                    class="font-weight-bold"
                  >
                    {{ referral.urgency }}
                  </v-chip>
                </v-col>
                <v-col cols="12" md="3">
                  <div class="text-caption text-grey mb-1">AI Confidence</div>
                  <v-chip
                    v-if="referral.ai_confidence_score !== null"
                    :color="getConfidenceColor(referral.ai_confidence_score)"
                    size="large"
                  >
                    {{ (referral.ai_confidence_score * 100).toFixed(1) }}%
                  </v-chip>
                  <span v-else class="text-grey">N/A</span>
                </v-col>
                <v-col cols="12" md="3">
                  <div class="text-caption text-grey mb-1">Department</div>
                  <div v-if="referral.department_resource">
                    {{ referral.department_resource.name }}
                  </div>
                  <div v-else-if="referral.department">{{ referral.department }}</div>
                  <span v-else class="text-grey">-</span>
                </v-col>
              </v-row>
            </v-card-text>
          </v-card>

          <!-- Patient Information -->
          <v-card class="mb-4" v-if="referral.patient">
            <v-card-title class="d-flex align-center">
              <v-icon class="mr-2">mdi-account</v-icon>
              Patient Information
            </v-card-title>
            <v-card-text>
              <v-row>
                <v-col cols="12" md="6">
                  <div class="text-caption text-grey mb-1">Full Name</div>
                  <div class="text-h6">{{ referral.patient.first_name }} {{ referral.patient.last_name }}</div>
                </v-col>
                <v-col cols="12" md="3">
                  <div class="text-caption text-grey mb-1">Date of Birth</div>
                  <div>{{ formatDate(referral.patient.date_of_birth) }}</div>
                  <div class="text-caption text-grey" v-if="referral.patient.date_of_birth">
                    Age: {{ calculateAge(referral.patient.date_of_birth) }}
                  </div>
                </v-col>
                <v-col cols="12" md="3">
                  <div class="text-caption text-grey mb-1">National ID</div>
                  <div>{{ referral.patient.national_id || '-' }}</div>
                </v-col>
                <v-col cols="12" md="6">
                  <div class="text-caption text-grey mb-1">Insurance Number</div>
                  <div>{{ referral.patient.insurance_number || '-' }}</div>
                </v-col>
              </v-row>
            </v-card-text>
          </v-card>

          <!-- Hospital Information -->
          <v-card class="mb-4" v-if="referral.hospital">
            <v-card-title class="d-flex align-center">
              <v-icon class="mr-2">mdi-hospital-building</v-icon>
              Referring Hospital
            </v-card-title>
            <v-card-text>
              <v-row>
                <v-col cols="12" md="6">
                  <div class="text-caption text-grey mb-1">Hospital Name</div>
                  <div class="text-h6">{{ referral.hospital.name }}</div>
                </v-col>
                <v-col cols="12" md="3">
                  <div class="text-caption text-grey mb-1">Code</div>
                  <div>{{ referral.hospital.code }}</div>
                </v-col>
                <v-col cols="12" md="3">
                  <div class="text-caption text-grey mb-1">Status</div>
                  <v-chip
                    :color="referral.hospital.status === 'active' ? 'success' : 'error'"
                    size="small"
                  >
                    {{ referral.hospital.status }}
                  </v-chip>
                </v-col>
              </v-row>
            </v-card-text>
          </v-card>

          <!-- Patient History -->
          <v-card class="mb-4" v-if="patientReferrals && patientReferrals.length > 0">
            <v-card-title class="d-flex align-center">
              <v-icon class="mr-2">mdi-history</v-icon>
              Patient Referral History
            </v-card-title>
            <v-card-text>
              <v-data-table
                :headers="historyHeaders"
                :items="patientReferrals"
                :loading="loadingHistory"
                density="compact"
              >
                <template v-slot:item.urgency="{ item }">
                  <v-chip :color="getUrgencyColor(item.urgency)" size="small">
                    {{ item.urgency }}
                  </v-chip>
                </template>
                <template v-slot:item.status="{ item }">
                  <v-chip :color="getStatusColor(item.status)" size="small">
                    {{ item.status }}
                  </v-chip>
                </template>
                <template v-slot:item.created_at="{ item }">
                  {{ item.created_at }}
                </template>
              </v-data-table>
            </v-card-text>
          </v-card>

          <!-- ICD-10 Diagnosis Codes -->
          <v-card class="mb-4" v-if="referral.icd10_codes && referral.icd10_codes.length > 0">
            <v-card-title class="d-flex align-center">
              <v-icon class="mr-2">mdi-tag-multiple</v-icon>
              ICD-10 Diagnosis Codes
            </v-card-title>
            <v-card-text>
              <div class="d-flex flex-wrap gap-2">
                <v-chip
                  v-for="code in referral.icd10_codes"
                  :key="code.id"
                  color="primary"
                  variant="outlined"
                  size="large"
                >
                  <v-icon start>mdi-tag</v-icon>
                  {{ code.code }}
                  <v-tooltip v-if="code.icd10_code" activator="parent" location="top">
                    <div class="text-caption">
                      <div class="font-weight-bold">{{ code.icd10_code.description }}</div>
                      <div v-if="code.icd10_code.category" class="mt-1">
                        Category: {{ code.icd10_code.category }}
                      </div>
                    </div>
                  </v-tooltip>
                </v-chip>
              </div>
            </v-card-text>
          </v-card>

          <!-- Clinical Notes -->
          <v-card class="mb-4" v-if="referral.clinical_notes">
            <v-card-title class="d-flex align-center">
              <v-icon class="mr-2">mdi-note-text</v-icon>
              Clinical Notes
            </v-card-title>
            <v-card-text>
              <div class="text-body-1" style="white-space: pre-wrap;">{{ referral.clinical_notes }}</div>
            </v-card-text>
          </v-card>

          <!-- Activity Timeline -->
          <v-card class="mb-4" v-if="referral.audit_logs && referral.audit_logs.length > 0">
            <v-card-title class="d-flex align-center">
              <v-icon class="mr-2">mdi-history</v-icon>
              Activity Timeline
            </v-card-title>
            <v-card-text>
              <v-timeline density="compact" side="end">
                <v-timeline-item
                  v-for="log in referral.audit_logs"
                  :key="log.id"
                  :dot-color="getActionColor(log.action)"
                  size="small"
                >
                  <div class="d-flex justify-space-between align-start">
                    <div>
                      <div class="font-weight-bold">{{ log.action }}</div>
                      <div class="text-caption text-grey" v-if="log.field">
                        Field: {{ log.field }}
                      </div>
                      <div class="text-caption" v-if="log.old_value !== null && log.new_value !== null">
                        <span class="text-grey">From:</span> {{ formatValue(log.old_value) }}
                        <span class="text-grey ml-2">To:</span> {{ formatValue(log.new_value) }}
                      </div>
                      <div class="text-caption text-grey mt-1">
                        {{ log.created_at }}
                      </div>
                    </div>
                    <div class="text-caption text-grey" v-if="log.user">
                      {{ log.user.name }}
                    </div>
                  </div>
                </v-timeline-item>
              </v-timeline>
            </v-card-text>
          </v-card>
        </template>
      </v-col>
    </v-row>
  </v-container>
</template>

<script setup>
import { ref, onMounted } from 'vue';
import { useRoute, useRouter } from 'vue-router';
import axios from 'axios';

const route = useRoute();
const router = useRouter();
const referral = ref(null);
const patientReferrals = ref([]);
const loading = ref(false);
const loadingHistory = ref(false);
const updating = ref(false);
const error = ref(null);

const historyHeaders = [
  { title: 'ID', key: 'id' },
  { title: 'Urgency', key: 'urgency' },
  { title: 'Status', key: 'status' },
  { title: 'Department', key: 'department' },
  { title: 'Created', key: 'created_at' },
];

const getStatusColor = (status) => {
  const colors = {
    submitted: 'grey',
    triaged: 'blue',
    assigned: 'purple',
    acknowledged: 'green',
    in_progress: 'info',
    completed: 'success',
    cancelled: 'red',
  };
  return colors[status] || 'grey';
};

const getUrgencyColor = (urgency) => {
  const colors = { emergency: 'red', urgent: 'orange', routine: 'blue' };
  return colors[urgency] || 'grey';
};

const getConfidenceColor = (score) => {
  if (score >= 0.8) return 'success';
  if (score >= 0.6) return 'warning';
  return 'error';
};

const getActionColor = (action) => {
  const colors = {
    assigned: 'purple',
    cancelled: 'red',
    acknowledged: 'green',
    completed: 'success',
    updated: 'blue',
  };
  return colors[action] || 'grey';
};

const formatDate = (dateString) => {
  if (!dateString) return '-';
  try {
    return new Date(dateString).toLocaleDateString();
  } catch (e) {
    return dateString;
  }
};

const calculateAge = (dateOfBirth) => {
  if (!dateOfBirth) return '-';
  try {
    const dob = new Date(dateOfBirth);
    const today = new Date();
    let age = today.getFullYear() - dob.getFullYear();
    const monthDiff = today.getMonth() - dob.getMonth();
    if (monthDiff < 0 || (monthDiff === 0 && today.getDate() < dob.getDate())) {
      age--;
    }
    return `${age} years`;
  } catch (e) {
    return '-';
  }
};

const formatValue = (value) => {
  if (value === null || value === undefined) return 'N/A';
  if (typeof value === 'object') return JSON.stringify(value);
  return String(value);
};

const loadReferral = async () => {
  loading.value = true;
  error.value = null;
  try {
    const response = await axios.get(`/api/v1/staff/referrals/${route.params.id}`);
    if (response.data.success && response.data.data) {
      referral.value = response.data.data;
      // Load patient history if patient exists
      if (referral.value.patient_id) {
        await loadPatientHistory(referral.value.patient_id);
      }
    } else {
      error.value = 'Failed to load referral details';
    }
  } catch (err) {
    console.error('Failed to load referral:', err);
    error.value = err.response?.data?.message || 'Failed to load referral details';
  } finally {
    loading.value = false;
  }
};

const loadPatientHistory = async (patientId) => {
  loadingHistory.value = true;
  try {
    // Patient referrals should be loaded with the referral via patient.referrals relationship
    if (referral.value.patient && referral.value.patient.referrals) {
      const referralsList = Array.isArray(referral.value.patient.referrals) 
        ? referral.value.patient.referrals 
        : [];
      // Filter out current referral and sort by created_at desc
      patientReferrals.value = referralsList
        .filter(r => r.id !== referral.value.id)
        .map(r => ({
          id: r.id,
          urgency: r.urgency,
          status: r.status,
          department: r.department || (r.department_resource ? r.department_resource.name : null),
          created_at: r.created_at,
          created_at_raw: r.created_at_raw,
        }))
        .sort((a, b) => {
          const dateA = new Date(a.created_at_raw || a.created_at);
          const dateB = new Date(b.created_at_raw || b.created_at);
          return dateB - dateA;
        });
    }
  } catch (err) {
    console.error('Failed to load patient history:', err);
    // Don't show error for patient history, just log it
  } finally {
    loadingHistory.value = false;
  }
};

const acknowledge = async () => {
  updating.value = true;
  try {
    await axios.post(`/api/v1/staff/referrals/${referral.value.id}/acknowledge`);
    await loadReferral();
  } catch (err) {
    console.error('Failed to acknowledge:', err);
    alert(err.response?.data?.message || 'Failed to acknowledge referral');
  } finally {
    updating.value = false;
  }
};

const updateStatus = async (status) => {
  updating.value = true;
  try {
    await axios.post(`/api/v1/staff/referrals/${referral.value.id}/update-status`, { status });
    await loadReferral();
  } catch (err) {
    console.error('Failed to update status:', err);
    alert(err.response?.data?.message || 'Failed to update referral status');
  } finally {
    updating.value = false;
  }
};

const completeReferral = async () => {
  if (!confirm(`Are you sure you want to mark referral #${referral.value.id} as complete?`)) {
    return;
  }
  
  updating.value = true;
  try {
    await axios.post(`/api/v1/staff/referrals/${referral.value.id}/complete`);
    await loadReferral();
  } catch (err) {
    console.error('Failed to complete referral:', err);
    alert(err.response?.data?.message || 'Failed to mark referral as complete');
  } finally {
    updating.value = false;
  }
};

onMounted(() => {
  loadReferral();
});
</script>

<style scoped>
.gap-2 {
  gap: 0.5rem;
}
</style>

