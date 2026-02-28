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
            @click="$router.push({ name: 'admin.referrals' })"
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
                  <div class="text-caption text-grey mb-1">External ID</div>
                  <code v-if="referral.external_referral_id">{{ referral.external_referral_id }}</code>
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
                  <div class="text-caption text-grey" v-if="referral.patient.date_of_birth_human">
                    {{ referral.patient.date_of_birth_human }}
                  </div>
                </v-col>
                <v-col cols="12" md="3">
                  <div class="text-caption text-grey mb-1">Age</div>
                  <div>{{ calculateAge(referral.patient.date_of_birth) }}</div>
                </v-col>
                <v-col cols="12" md="6">
                  <div class="text-caption text-grey mb-1">National ID</div>
                  <code>{{ referral.patient.national_id }}</code>
                </v-col>
                <v-col cols="12" md="6">
                  <div class="text-caption text-grey mb-1">Insurance Number</div>
                  <code>{{ referral.patient.insurance_number }}</code>
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
                  <code>{{ referral.hospital.code }}</code>
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

          <!-- Department & Assignment -->
          <v-card class="mb-4">
            <v-card-title class="d-flex align-center">
              <v-icon class="mr-2">mdi-office-building</v-icon>
              Department & Assignment
            </v-card-title>
            <v-card-text>
              <v-row>
                <v-col cols="12" md="6">
                  <div class="text-caption text-grey mb-1">Department</div>
                  <div v-if="referral.department_resource">
                    <div class="text-h6">{{ referral.department_resource.name }}</div>
                    <div class="text-caption text-grey" v-if="referral.department_resource.code">
                      Code: {{ referral.department_resource.code }}
                    </div>
                  </div>
                  <div v-else-if="referral.department">
                    {{ referral.department }}
                  </div>
                  <span v-else class="text-grey">Not assigned</span>
                </v-col>
                <v-col cols="12" md="6">
                  <div class="text-caption text-grey mb-1">Assigned Staff</div>
                  <div v-if="referral.assigned_staff">
                    <div class="text-h6">{{ referral.assigned_staff.name }}</div>
                    <div class="text-caption text-grey">{{ referral.assigned_staff.email }}</div>
                    <v-chip
                      v-if="referral.assigned_staff.department"
                      size="small"
                      color="info"
                      class="mt-1"
                    >
                      {{ referral.assigned_staff.department }}
                    </v-chip>
                    <v-chip
                      :color="referral.assigned_staff.is_available ? 'success' : 'error'"
                      size="small"
                      class="mt-1 ml-1"
                    >
                      {{ referral.assigned_staff.is_available ? 'Available' : 'Unavailable' }}
                    </v-chip>
                  </div>
                  <span v-else class="text-grey">Not assigned</span>
                </v-col>
              </v-row>
            </v-card-text>
          </v-card>

          <!-- ICD-10 Codes -->
          <v-card class="mb-4" v-if="referral.icd10_codes && referral.icd10_codes.length > 0">
            <v-card-title class="d-flex align-center">
              <v-icon class="mr-2">mdi-code-tags</v-icon>
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

          <!-- AI Triage Information -->
          <v-card class="mb-4" v-if="referral.ai_triage_log">
            <v-card-title class="d-flex align-center">
              <v-icon class="mr-2">mdi-robot</v-icon>
              AI Triage Information
            </v-card-title>
            <v-card-text>
              <v-row>
                <v-col cols="12" md="3">
                  <div class="text-caption text-grey mb-1">Status</div>
                  <v-chip
                    :color="referral.ai_triage_log.status === 'success' ? 'success' : 'error'"
                    size="small"
                  >
                    {{ referral.ai_triage_log.status }}
                  </v-chip>
                </v-col>
                <v-col cols="12" md="3">
                  <div class="text-caption text-grey mb-1">Retry Count</div>
                  <div>{{ referral.ai_triage_log.retry_count || 0 }}</div>
                </v-col>
                <v-col cols="12" md="6">
                  <div class="text-caption text-grey mb-1">Processed At</div>
                  <div>{{ referral.processed_at || '-' }}</div>
                  <div class="text-caption text-grey" v-if="referral.processed_at_raw">
                    {{ formatRawDate(referral.processed_at_raw) }}
                  </div>
                </v-col>
                <v-col cols="12" v-if="referral.ai_triage_log.input_data">
                  <div class="text-caption text-grey mb-1">Input Data</div>
                  <v-card variant="outlined" class="pa-2">
                    <pre class="text-caption">{{ JSON.stringify(referral.ai_triage_log.input_data, null, 2) }}</pre>
                  </v-card>
                </v-col>
                <v-col cols="12" v-if="referral.ai_triage_log.output_data">
                  <div class="text-caption text-grey mb-1">Output Data</div>
                  <v-card variant="outlined" class="pa-2">
                    <pre class="text-caption">{{ JSON.stringify(referral.ai_triage_log.output_data, null, 2) }}</pre>
                  </v-card>
                </v-col>
                <v-col cols="12" v-if="referral.ai_triage_log.error_message">
                  <v-alert type="error" variant="tonal">
                    <strong>Error:</strong> {{ referral.ai_triage_log.error_message }}
                  </v-alert>
                </v-col>
              </v-row>
            </v-card-text>
          </v-card>

          <!-- Cancellation Information -->
          <v-card class="mb-4" v-if="referral.status === 'cancelled' && referral.cancellation_reason">
            <v-card-title class="d-flex align-center text-error">
              <v-icon class="mr-2">mdi-cancel</v-icon>
              Cancellation Details
            </v-card-title>
            <v-card-text>
              <div class="text-body-1" style="white-space: pre-wrap;">{{ referral.cancellation_reason }}</div>
            </v-card-text>
          </v-card>

          <!-- Timeline & Audit Log -->
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

          <!-- Timestamps -->
          <v-card class="mb-4">
            <v-card-title class="d-flex align-center">
              <v-icon class="mr-2">mdi-clock-outline</v-icon>
              Timestamps
            </v-card-title>
            <v-card-text>
              <v-row>
                <v-col cols="12" md="4">
                  <div class="text-caption text-grey mb-1">Created</div>
                  <div>{{ referral.created_at }}</div>
                  <div class="text-caption text-grey" v-if="referral.created_at_raw">
                    {{ formatRawDate(referral.created_at_raw) }}
                  </div>
                </v-col>
                <v-col cols="12" md="4">
                  <div class="text-caption text-grey mb-1">Updated</div>
                  <div>{{ referral.updated_at }}</div>
                  <div class="text-caption text-grey" v-if="referral.updated_at_raw">
                    {{ formatRawDate(referral.updated_at_raw) }}
                  </div>
                </v-col>
                <v-col cols="12" md="4" v-if="referral.acknowledged_at">
                  <div class="text-caption text-grey mb-1">Acknowledged</div>
                  <div>{{ referral.acknowledged_at }}</div>
                  <div class="text-caption text-grey" v-if="referral.acknowledged_at_raw">
                    {{ formatRawDate(referral.acknowledged_at_raw) }}
                  </div>
                </v-col>
              </v-row>
            </v-card-text>
          </v-card>

          <!-- Actions -->
          <v-card>
            <v-card-title class="d-flex align-center">
              <v-icon class="mr-2">mdi-cog</v-icon>
              Actions
            </v-card-title>
            <v-card-text>
              <div class="d-flex gap-2 flex-wrap">
                <v-btn
                  v-if="referral.status !== 'completed' && referral.status !== 'cancelled'"
                  color="primary"
                  prepend-icon="mdi-account-plus"
                  @click="openAssignDialog"
                >
                  Assign to Staff
                </v-btn>
                <v-btn
                  v-if="referral.status !== 'completed' && referral.status !== 'cancelled'"
                  color="error"
                  prepend-icon="mdi-cancel"
                  @click="openCancelDialog"
                >
                  Cancel Referral
                </v-btn>
              </div>
            </v-card-text>
          </v-card>
        </template>
      </v-col>
    </v-row>

    <!-- Assign Staff Dialog -->
    <v-dialog v-model="assignDialog" max-width="500">
      <v-card>
        <v-card-title>Assign Referral to Staff</v-card-title>
        <v-card-text>
          <v-select
            v-model="selectedStaffId"
            label="Select Staff Member"
            :items="availableStaff"
            item-title="name"
            item-value="id"
            :loading="loadingStaff"
            return-object
          ></v-select>
        </v-card-text>
        <v-card-actions>
          <v-spacer></v-spacer>
          <v-btn @click="assignDialog = false">Cancel</v-btn>
          <v-btn color="primary" :disabled="!selectedStaffId" @click="assignReferral">
            Assign
          </v-btn>
        </v-card-actions>
      </v-card>
    </v-dialog>

    <!-- Cancel Referral Dialog -->
    <v-dialog v-model="cancelDialog" max-width="500">
      <v-card>
        <v-card-title>Cancel Referral</v-card-title>
        <v-card-text>
          <p class="mb-2">
            Please provide a reason for cancelling this referral.
          </p>
          <v-textarea
            v-model="cancelReason"
            label="Cancellation Reason"
            auto-grow
            rows="3"
            :counter="500"
            required
          ></v-textarea>
        </v-card-text>
        <v-card-actions>
          <v-spacer></v-spacer>
          <v-btn @click="cancelDialog = false">Close</v-btn>
          <v-btn
            color="error"
            :disabled="!cancelReason.trim()"
            :loading="cancelling"
            @click="cancelReferral"
          >
            Confirm Cancel
          </v-btn>
        </v-card-actions>
      </v-card>
    </v-dialog>
  </v-container>
</template>

<script setup>
import { ref, onMounted } from 'vue';
import { useRoute, useRouter } from 'vue-router';
import axios from 'axios';

const route = useRoute();
const router = useRouter();
const referral = ref(null);
const loading = ref(false);
const error = ref(null);
const assignDialog = ref(false);
const cancelDialog = ref(false);
const selectedStaffId = ref(null);
const availableStaff = ref([]);
const loadingStaff = ref(false);
const cancelReason = ref('');
const cancelling = ref(false);

const getStatusColor = (status) => {
  const colors = {
    submitted: 'grey',
    triaged: 'blue',
    assigned: 'purple',
    acknowledged: 'green',
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

const formatRawDate = (dateString) => {
  if (!dateString) return '';
  try {
    const date = new Date(dateString);
    return date.toLocaleString();
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
    const response = await axios.get(`/api/v1/admin/referrals/${route.params.id}`);
    if (response.data.success && response.data.data) {
      referral.value = response.data.data;
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

const openAssignDialog = async () => {
  selectedStaffId.value = referral.value?.assigned_staff_id || null;
  loadingStaff.value = true;
  assignDialog.value = true;
  
  try {
    const response = await axios.get('/api/v1/admin/staff');
    if (response.data.success && response.data.data) {
      const staffData = Array.isArray(response.data.data) 
        ? response.data.data 
        : (response.data.data.data || []);
      availableStaff.value = staffData.filter(s => s.is_available);
    }
  } catch (err) {
    console.error('Failed to load staff:', err);
    availableStaff.value = [];
  } finally {
    loadingStaff.value = false;
  }
};

const assignReferral = async () => {
  if (!referral.value || !selectedStaffId.value) return;
  
  try {
    await axios.post(`/api/v1/admin/referrals/${referral.value.id}/assign`, {
      staff_id: selectedStaffId.value.id || selectedStaffId.value,
    });
    assignDialog.value = false;
    loadReferral();
  } catch (err) {
    console.error('Failed to assign referral:', err);
    alert(err.response?.data?.message || 'Failed to assign referral');
  }
};

const openCancelDialog = () => {
  cancelReason.value = '';
  cancelDialog.value = true;
};

const cancelReferral = async () => {
  if (!referral.value || !cancelReason.value.trim()) return;

  cancelling.value = true;
  try {
    await axios.post(`/api/v1/admin/referrals/${referral.value.id}/cancel`, {
      reason: cancelReason.value,
    });
    cancelDialog.value = false;
    loadReferral();
  } catch (err) {
    console.error('Failed to cancel referral:', err);
    alert(err.response?.data?.message || 'Failed to cancel referral');
  } finally {
    cancelling.value = false;
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
